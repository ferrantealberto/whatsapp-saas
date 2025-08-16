<?php
class WSP_API {
    
    public static function register_routes() {
        // Endpoint per n8n - ricevi numeri estratti
        register_rest_route('wsp/v1', '/extract', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_extraction'],
            'permission_callback' => [__CLASS__, 'verify_api_key']
        ]);
        
        // Endpoint per inviare messaggi
        register_rest_route('wsp/v1', '/send', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'send_message'],
            'permission_callback' => [__CLASS__, 'verify_api_key']
        ]);
        
        // Endpoint per verificare crediti
        register_rest_route('wsp/v1', '/credits', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_credits'],
            'permission_callback' => [__CLASS__, 'verify_api_key']
        ]);
        
        // Endpoint per statistiche
        register_rest_route('wsp/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_stats'],
            'permission_callback' => [__CLASS__, 'verify_api_key']
        ]);
        
        // Webhook per WooCommerce
        register_rest_route('wsp/v1', '/webhook/woocommerce', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_woocommerce_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Verifica API Key
     */
    public static function verify_api_key($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API Key mancante', ['status' => 401]);
        }
        
        global $wpdb;
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM {$wpdb->prefix}wsp_customers WHERE api_key = %s",
            $api_key
        ));
        
        if (!$customer) {
            return new WP_Error('invalid_api_key', 'API Key non valida', ['status' => 401]);
        }
        
        if ($customer->status !== 'active') {
            return new WP_Error('inactive_account', 'Account non attivo', ['status' => 403]);
        }
        
        // Salva customer_id nel request per uso successivo
        $request->set_param('_customer_id', $customer->id);
        
        return true;
    }
    
    /**
     * Gestisci estrazione numeri da n8n
     */
    public static function handle_extraction($request) {
        global $wpdb;
        
        $customer_id = $request->get_param('_customer_id');
        $data = $request->get_json_params();
        
        // Log richiesta
        self::log_api_request($customer_id, 'extract', $request);
        
        $results = [
            'success' => true,
            'processed' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'messages_sent' => 0,
            'credits_used' => 0,
            'errors' => []
        ];
        
        // Ottieni dati cliente
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_customers WHERE id = %d",
            $customer_id
        ));
        
        if (!$customer) {
            return new WP_Error('customer_not_found', 'Cliente non trovato');
        }
        
        // Processa numeri estratti
        $numbers = isset($data['numbers']) ? $data['numbers'] : [$data];
        
        foreach ($numbers as $number_data) {
            $results['processed']++;
            
            // Normalizza numero
            $sender_number = self::normalize_italian_number($number_data['senderNumber'] ?? '');
            
            if (empty($sender_number)) {
                $results['errors'][] = 'Numero non valido: ' . ($number_data['senderNumber'] ?? 'vuoto');
                continue;
            }
            
            // Estrai data campagna
            $campaign_date = self::extract_date($number_data);
            
            // Controlla duplicato
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}wsp_extracted_numbers 
                WHERE customer_id = %d AND sender_number = %s AND campaign_date = %s",
                $customer_id, $sender_number, $campaign_date
            ));
            
            if ($exists) {
                $results['duplicates']++;
                continue;
            }
            
            // Inserisci numero estratto
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'wsp_extracted_numbers',
                [
                    'customer_id' => $customer_id,
                    'message_id' => $number_data['messageId'] ?? uniqid('msg_'),
                    'sender_number' => $sender_number,
                    'sender_name' => sanitize_text_field($number_data['senderName'] ?? ''),
                    'sender_email' => sanitize_email($number_data['senderEmail'] ?? ''),
                    'extraction_method' => $number_data['extractionMethod'] ?? 'qr_scan',
                    'extraction_source' => $number_data['source'] ?? 'gmail',
                    'qr_code_id' => $number_data['qrCodeId'] ?? '',
                    'campaign_id' => $number_data['campaignId'] ?? '',
                    'campaign_name' => $number_data['campaignName'] ?? '',
                    'campaign_date' => $campaign_date,
                    'scan_location' => $number_data['location'] ?? '',
                    'scan_timestamp' => current_time('mysql')
                ]
            );
            
            if ($inserted) {
                $results['imported']++;
                
                // Invia messaggio di benvenuto se abilitato
                if ($customer->settings) {
                    $settings = json_decode($customer->settings, true);
                    
                    if (!empty($settings['auto_welcome']) && WSP_Credits::get_balance($customer_id) > 0) {
                        $message_sent = self::send_welcome_message(
                            $customer_id,
                            $sender_number,
                            $number_data['senderName'] ?? 'Cliente',
                            $settings['welcome_template'] ?? 'default'
                        );
                        
                        if ($message_sent && !is_wp_error($message_sent)) {
                            $results['messages_sent']++;
                            $results['credits_used']++;
                        }
                    }
                }
                
                // Trigger hook per integrazioni
                do_action('wsp_number_extracted', $customer_id, $sender_number, $number_data);
            }
        }
        
        return new WP_REST_Response($results, 200);
    }
    
    /**
     * Invia messaggio WhatsApp
     */
    public static function send_message($request) {
        $customer_id = $request->get_param('_customer_id');
        $data = $request->get_json_params();
        
        // Verifica parametri richiesti
        if (empty($data['to']) || empty($data['message'])) {
            return new WP_Error('missing_params', 'Parametri mancanti: to, message');
        }
        
        // Normalizza numero destinatario
        $recipient = self::normalize_italian_number($data['to']);
        
        // Calcola costo in crediti
        $message_type = $data['type'] ?? 'text';
        $credits_needed = WSP_Credits::calculate_message_cost($message_type);
        
        // Verifica crediti
        $current_balance = WSP_Credits::get_balance($customer_id);
        if ($current_balance < $credits_needed) {
            return new WP_Error('insufficient_credits', 'Crediti insufficienti', [
                'balance' => $current_balance,
                'needed' => $credits_needed
            ]);
        }
        
        // Prepara messaggio
        $message_data = [
            'customer_id' => $customer_id,
            'recipient_number' => $recipient,
            'recipient_name' => $data['name'] ?? '',
            'message_type' => $message_type,
            'message_content' => $data['message'],
            'message_template' => $data['template'] ?? '',
            'credits_used' => $credits_needed,
            'status' => 'pending'
        ];
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wsp_messages', $message_data);
        $message_id = $wpdb->insert_id;
        
        // Invia tramite API Mail2Wa.it
        $api_response = self::send_via_mail2wa(
            $recipient,
            $data['message'],
            $message_type
        );
        
        if (is_wp_error($api_response)) {
            // Aggiorna stato messaggio
            $wpdb->update(
                $wpdb->prefix . 'wsp_messages',
                [
                    'status' => 'failed',
                    'failed_reason' => $api_response->get_error_message()
                ],
                ['id' => $message_id]
            );
            
            return $api_response;
        }
        
        // Messaggio inviato con successo
        $wpdb->update(
            $wpdb->prefix . 'wsp_messages',
            [
                'status' => 'sent',
                'sent_at' => current_time('mysql'),
                'api_response' => json_encode($api_response)
            ],
            ['id' => $message_id]
        );
        
        // Scala crediti
        WSP_Credits::use_credits(
            $customer_id,
            $credits_needed,
            'Messaggio WhatsApp a ' . $recipient,
            $message_id,
            'message'
        );
        
        return new WP_REST_Response([
            'success' => true,
            'message_id' => $message_id,
            'credits_used' => $credits_needed,
            'balance' => WSP_Credits::get_balance($customer_id)
        ], 200);
    }
    
    /**
     * Invia tramite Mail2Wa API
     */
    private static function send_via_mail2wa($number, $message, $type = 'text') {
        $api_key = get_option('wsp_mail2wa_api_key', '1f06d5c8bd0cd19f7c99b660b504bb25');
        $api_url = 'https://api.Mail2Wa.it/';
        
        $args = [
            'timeout' => 30,
            'body' => [
                'action' => 'send',
                'apiKey' => $api_key,
                'to' => $number,
                'message' => $message
            ]
        ];
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['success'])) {
            return new WP_Error('api_error', $data['error'] ?? 'Errore API sconosciuto');
        }
        
        return $data;
    }
    
    /**
     * Invia messaggio di benvenuto
     */
    private static function send_welcome_message($customer_id, $recipient, $name, $template = 'default') {
        global $wpdb;
        
        // Ottieni template messaggio
        $templates = [
            'default' => "🎉 Ciao {name}!\n\nGrazie per aver scansionato il nostro QR Code!\n\n✅ Il tuo numero WhatsApp è stato registrato.\n📱 Numero: +{number}\n\nRiceverai presto aggiornamenti e offerte esclusive!\n\n💬 Per info scrivi STOP per cancellarti.",
            'shop' => "🛍️ Benvenuto {name}!\n\nGrazie per lo shopping con noi!\n\n🎁 Hai ricevuto un BUONO SCONTO del 10%\nCodice: WELCOME10\n\n📱 Salva questo numero per ricevere offerte esclusive!",
            'event' => "🎊 Ciao {name}!\n\nGrazie per la partecipazione!\n\n📅 Ti confermiamo la registrazione all'evento.\n📱 Riceverai aggiornamenti su questo numero.\n\n✨ A presto!"
        ];
        
        $message = $templates[$template] ?? $templates['default'];
        $message = str_replace(
            ['{name}', '{number}'],
            [$name, '+' . $recipient],
            $message
        );
        
        // Usa crediti e invia
        $credits_result = WSP_Credits::use_credits($customer_id, 1, 'Welcome message to ' . $recipient);
        
        if (is_wp_error($credits_result)) {
            return $credits_result;
        }
        
        return self::send_via_mail2wa($recipient, $message);
    }
    
    /**
     * Normalizza numero italiano
     */
    private static function normalize_italian_number($input) {
        if (empty($input)) return '';
        
        $digits = preg_replace('/\D+/', '', $input);
        
        if (substr($digits, 0, 4) === '0039') $digits = substr($digits, 4);
        if (substr($digits, 0, 2) === '00') $digits = substr($digits, 2);
        if (strlen($digits) === 10 && $digits[0] === '3') $digits = '39' . $digits;
        
        // Valida numero italiano
        if (substr($digits, 0, 2) === '39' && strlen($digits) >= 11) {
            return $digits;
        }
        
        return '';
    }
    
    /**
     * Estrai data da vari formati
     */
    private static function extract_date($data) {
        // Priorità: campaignDate, processedDate, oggi
        if (!empty($data['campaignDate'])) {
            return $data['campaignDate'];
        }
        
        if (!empty($data['processedDate'])) {
            // Converti formato italiano DD/MM/YYYY
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $data['processedDate'], $matches)) {
                return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
        }
        
        return date('Y-m-d');
    }
    
    /**
     * Log richieste API
     */
    private static function log_api_request($customer_id, $endpoint, $request) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wsp_api_logs',
            [
                'customer_id' => $customer_id,
                'endpoint' => $endpoint,
                'method' => $request->get_method(),
                'request_data' => json_encode($request->get_json_params()),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
    }
}
