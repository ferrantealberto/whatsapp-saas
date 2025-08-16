<?php
/**
 * API REST per WhatsApp SaaS Plugin
 * COMPLETAMENTE FUNZIONALE - API per integrazione n8n
 */

if (!defined('ABSPATH')) {
    exit;
}

class WSP_API {
    
    public function register_routes() {
        // Endpoint principale per ricevere i numeri da n8n
        register_rest_route('wsp/v1', '/extract', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_extract_numbers'),
            'permission_callback' => array($this, 'check_api_key'),
            'args' => array(
                'numbers' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array di numeri WhatsApp estratti'
                )
            )
        ));
        
        // Endpoint per statistiche
        register_rest_route('wsp/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_api_key')
        ));
        
        // Endpoint per test connessione
        register_rest_route('wsp/v1', '/ping', array(
            'methods' => 'GET',
            'callback' => array($this, 'ping'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function check_api_key($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (!$api_key) {
            $api_key = $request->get_param('apiKey');
        }
        
        $valid_api_key = get_option('wsp_api_key', 'demo-api-key-9lz721sv0xTjFNVA');
        
        if ($api_key !== $valid_api_key) {
            return new WP_Error(
                'invalid_api_key',
                __('API Key non valida', 'wsp'),
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    public function handle_extract_numbers($request) {
        try {
            $numbers_data = $request->get_param('numbers');
            
            if (empty($numbers_data) || !is_array($numbers_data)) {
                return new WP_Error(
                    'invalid_data',
                    __('Dati numeri non validi', 'wsp'),
                    array('status' => 400)
                );
            }
            
            // Salva i numeri nel database
            $result = WSP_Database::save_whatsapp_numbers($numbers_data);
            
            // Log dell'operazione
            WSP_Database::log_activity(
                'api_extract_numbers',
                sprintf('Ricevuti %d numeri via API, salvati %d', count($numbers_data), $result['saved']),
                array(
                    'total_received' => count($numbers_data),
                    'saved' => $result['saved'],
                    'errors' => $result['errors']
                )
            );
            
            // Decrementa crediti se configurato
            $this->consume_credits($result['saved']);
            
            $response = array(
                'success' => true,
                'message' => sprintf(__('Elaborati %d numeri, salvati %d', 'wsp'), count($numbers_data), $result['saved']),
                'data' => array(
                    'total_received' => count($numbers_data),
                    'saved' => $result['saved'],
                    'errors_count' => count($result['errors']),
                    'errors' => $result['errors'],
                    'credits_remaining' => get_option('wsp_credits_balance', 0)
                ),
                'timestamp' => current_time('mysql')
            );
            
            return rest_ensure_response($response);
            
        } catch (Exception $e) {
            WSP_Database::log_activity(
                'api_error',
                'Errore durante elaborazione numeri: ' . $e->getMessage()
            );
            
            return new WP_Error(
                'processing_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    public function get_stats($request) {
        try {
            $stats = WSP_Database::get_statistics();
            $credits = get_option('wsp_credits_balance', 0);
            
            $response = array(
                'success' => true,
                'data' => array(
                    'statistics' => $stats,
                    'credits' => array(
                        'balance' => (int) $credits,
                        'formatted' => number_format($credits)
                    ),
                    'api_status' => 'active',
                    'last_update' => current_time('mysql')
                )
            );
            
            return rest_ensure_response($response);
            
        } catch (Exception $e) {
            return new WP_Error(
                'stats_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    public function ping($request) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'WhatsApp SaaS Plugin API is active',
            'version' => WSP_VERSION,
            'timestamp' => current_time('mysql')
        ));
    }
    
    private function consume_credits($amount) {
        $current_credits = (int) get_option('wsp_credits_balance', 0);
        $new_balance = max(0, $current_credits - $amount);
        
        update_option('wsp_credits_balance', $new_balance);
        
        WSP_Database::log_activity(
            'credits_consumed',
            sprintf('Utilizzati %d crediti, saldo: %d', $amount, $new_balance),
            array(
                'consumed' => $amount,
                'previous_balance' => $current_credits,
                'new_balance' => $new_balance
            )
        );
        
        return $new_balance;
    }
}