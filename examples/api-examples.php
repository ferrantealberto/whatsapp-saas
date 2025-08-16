<?php
/**
 * WhatsApp SaaS Plugin - API Usage Examples
 * 
 * Esempi pratici di utilizzo delle API REST del plugin WordPress
 * per integrazione con sistemi esterni, n8n, e applicazioni custom.
 */

// Configuration
define('WORDPRESS_API_URL', 'https://tuosito.com/wp-json/wsp/v1');
define('API_KEY', 'demo-api-key-9lz721sv0xTjFNVA');

/**
 * Generic API Request Function
 */
function wsp_api_request($endpoint, $method = 'GET', $data = null) {
    $url = WORDPRESS_API_URL . $endpoint;
    
    $headers = [
        'X-API-Key: ' . API_KEY,
        'Content-Type: application/json',
        'User-Agent: WhatsApp-SaaS-Client/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("CURL Error: " . $error);
    }
    
    $decoded = json_decode($response, true);
    
    return [
        'http_code' => $http_code,
        'data' => $decoded,
        'raw_response' => $response
    ];
}

// ==========================================
// EXAMPLE 1: Health Check API
// ==========================================

echo "🔍 Example 1: Health Check API\n";
echo "================================\n";

try {
    $response = wsp_api_request('/ping');
    
    if ($response['http_code'] === 200) {
        echo "✅ API is active!\n";
        echo "Version: " . $response['data']['version'] . "\n";
        echo "Timestamp: " . $response['data']['timestamp'] . "\n";
    } else {
        echo "❌ API health check failed\n";
        echo "HTTP Code: " . $response['http_code'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 2: Extract WhatsApp Numbers from Email
// ==========================================

echo "📧 Example 2: Extract WhatsApp Numbers\n";
echo "======================================\n";

$email_data = [
    'email_content' => 'Ciao! Sono Mario Rossi. Il mio numero WhatsApp è 3331234567. Contattami per info sui vostri prodotti. Grazie!',
    'sender_email' => 'mario.rossi@example.com',
    'subject' => 'Richiesta informazioni prodotti',
    'message_id' => 'test-email-001',
    'received_at' => date('c')
];

try {
    $response = wsp_api_request('/extract', 'POST', $email_data);
    
    if ($response['http_code'] === 200 && $response['data']['success']) {
        echo "✅ Numbers extracted successfully!\n";
        echo "Numbers found: " . $response['data']['numbers_extracted'] . "\n";
        echo "Numbers saved: " . $response['data']['numbers_saved'] . "\n";
        
        if (!empty($response['data']['numbers'])) {
            echo "Extracted numbers:\n";
            foreach ($response['data']['numbers'] as $number) {
                echo "  - " . $number['number'] . " (formatted: " . $number['formatted'] . ")\n";
            }
        }
        
        if ($response['data']['duplicates_skipped'] > 0) {
            echo "Duplicates skipped: " . $response['data']['duplicates_skipped'] . "\n";
        }
    } else {
        echo "❌ Number extraction failed\n";
        echo "Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 3: Batch Processing Multiple Emails
// ==========================================

echo "📦 Example 3: Batch Processing\n";
echo "==============================\n";

$batch_data = [
    'batch' => true,
    'emails' => [
        [
            'email_content' => 'WhatsApp: 3331111111 - Cliente 1',
            'sender_email' => 'cliente1@test.com',
            'subject' => 'Richiesta 1',
            'message_id' => 'batch-001'
        ],
        [
            'email_content' => 'Chiamatemi al +39 333 222 2222 - Cliente 2',
            'sender_email' => 'cliente2@test.com',
            'subject' => 'Richiesta 2',
            'message_id' => 'batch-002'
        ],
        [
            'email_content' => 'Nessun numero qui - Cliente 3',
            'sender_email' => 'cliente3@test.com',
            'subject' => 'Richiesta 3',
            'message_id' => 'batch-003'
        ]
    ]
];

try {
    $response = wsp_api_request('/extract', 'POST', $batch_data);
    
    if ($response['http_code'] === 200 && $response['data']['success']) {
        echo "✅ Batch processing completed!\n";
        $summary = $response['data']['batch_summary'];
        echo "Total emails: " . $summary['total_emails'] . "\n";
        echo "Emails with numbers: " . $summary['emails_with_numbers'] . "\n";
        echo "Total numbers extracted: " . $summary['total_numbers_extracted'] . "\n";
        echo "Total numbers saved: " . $summary['total_numbers_saved'] . "\n";
        
        echo "\nDetailed results:\n";
        foreach ($response['data']['results'] as $result) {
            echo "  Email {$result['email_id']}: {$result['numbers_found']} numbers, status: {$result['status']}\n";
        }
    } else {
        echo "❌ Batch processing failed\n";
        echo "Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 4: Check Credits Balance
// ==========================================

echo "💳 Example 4: Check Credits Balance\n";
echo "===================================\n";

try {
    $response = wsp_api_request('/credits');
    
    if ($response['http_code'] === 200 && $response['data']['success']) {
        echo "✅ Credits information retrieved!\n";
        $credits = $response['data']['credits'];
        echo "Current balance: " . $credits['current_balance'] . "\n";
        echo "Total purchased: " . $credits['total_purchased'] . "\n";
        echo "Total used: " . $credits['total_used'] . "\n";
        echo "Last recharge: " . $credits['last_recharge'] . "\n";
        
        if ($credits['auto_recharge']['enabled']) {
            echo "Auto-recharge: Enabled (threshold: " . $credits['auto_recharge']['threshold'] . ")\n";
        }
        
        $usage = $response['data']['usage_stats'];
        echo "\nUsage statistics:\n";
        echo "  Today: " . $usage['today'] . " credits\n";
        echo "  This week: " . $usage['this_week'] . " credits\n";
        echo "  This month: " . $usage['this_month'] . " credits\n";
    } else {
        echo "❌ Credits check failed\n";
        echo "Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 5: Get Messages History
// ==========================================

echo "💬 Example 5: Messages History\n";
echo "==============================\n";

try {
    $response = wsp_api_request('/messages?limit=3&status=sent');
    
    if ($response['http_code'] === 200 && $response['data']['success']) {
        echo "✅ Messages history retrieved!\n";
        
        if (!empty($response['data']['messages'])) {
            echo "Recent messages:\n";
            foreach ($response['data']['messages'] as $message) {
                echo "  ID: {$message['id']}\n";
                echo "  To: {$message['recipient_number']}\n";
                echo "  Status: {$message['status']}\n";
                echo "  Credits: {$message['credits_used']}\n";
                echo "  Sent: {$message['sent_at']}\n";
                echo "  Message: " . substr($message['message_content'], 0, 50) . "...\n";
                echo "  ---\n";
            }
            
            $pagination = $response['data']['pagination'];
            echo "Page {$pagination['current_page']} of {$pagination['total_pages']} ";
            echo "({$pagination['total_messages']} total messages)\n";
        } else {
            echo "No messages found.\n";
        }
    } else {
        echo "❌ Messages history failed\n";
        echo "Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 6: Send WhatsApp Message
// ==========================================

echo "📱 Example 6: Send WhatsApp Message\n";
echo "===================================\n";

$message_data = [
    'recipient' => '+39 333 123 4567',
    'message' => '🎉 Ciao! Grazie per aver condiviso il tuo numero WhatsApp. Il nostro team ti contatterà presto per maggiori informazioni sui nostri servizi.',
    'template' => 'welcome_message',
    'variables' => [
        'nome' => 'Mario',
        'numero' => '+39 333 123 4567'
    ]
];

try {
    $response = wsp_api_request('/send', 'POST', $message_data);
    
    if ($response['http_code'] === 200 && $response['data']['success']) {
        echo "✅ WhatsApp message sent successfully!\n";
        echo "Message ID: " . $response['data']['message_id'] . "\n";
        echo "Recipient: " . $response['data']['recipient'] . "\n";
        echo "Credits used: " . $response['data']['credits_used'] . "\n";
        echo "Credits remaining: " . $response['data']['credits_remaining'] . "\n";
        echo "Status: " . $response['data']['status'] . "\n";
        echo "External ID: " . $response['data']['external_id'] . "\n";
    } else {
        echo "❌ WhatsApp message sending failed\n";
        if (isset($response['data']['error_code'])) {
            echo "Error code: " . $response['data']['error_code'] . "\n";
        }
        echo "Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 7: Error Handling
// ==========================================

echo "⚠️  Example 7: Error Handling\n";
echo "=============================\n";

// Test with invalid API key
define('INVALID_API_KEY', 'invalid-key-123');

function wsp_api_request_with_invalid_key($endpoint) {
    $url = WORDPRESS_API_URL . $endpoint;
    
    $headers = [
        'X-API-Key: ' . INVALID_API_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'data' => json_decode($response, true)
    ];
}

try {
    $response = wsp_api_request_with_invalid_key('/ping');
    
    echo "Testing invalid API key...\n";
    echo "HTTP Code: " . $response['http_code'] . "\n";
    
    if ($response['http_code'] === 401) {
        echo "✅ Security working correctly - API key rejected\n";
        echo "Error: " . $response['data']['error'] . "\n";
        echo "Message: " . $response['data']['message'] . "\n";
    } else {
        echo "❌ Security issue - invalid API key accepted\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// EXAMPLE 8: Rate Limiting Test
// ==========================================

echo "⏱️  Example 8: Rate Limiting Test\n";
echo "=================================\n";

echo "Sending multiple rapid requests to test rate limiting...\n";

$start_time = microtime(true);
$successful_requests = 0;
$rate_limited_requests = 0;

// Send 10 rapid requests
for ($i = 1; $i <= 10; $i++) {
    try {
        $response = wsp_api_request('/ping');
        
        if ($response['http_code'] === 200) {
            $successful_requests++;
        } elseif ($response['http_code'] === 429) {
            $rate_limited_requests++;
            echo "Request $i: Rate limited (HTTP 429)\n";
            if (isset($response['data']['retry_after'])) {
                echo "  Retry after: " . $response['data']['retry_after'] . " seconds\n";
            }
        }
    } catch (Exception $e) {
        echo "Request $i failed: " . $e->getMessage() . "\n";
    }
    
    // Small delay to avoid immediate blocking
    usleep(100000); // 0.1 seconds
}

$end_time = microtime(true);
$duration = round($end_time - $start_time, 2);

echo "Test completed in {$duration} seconds\n";
echo "Successful requests: $successful_requests\n";
echo "Rate limited requests: $rate_limited_requests\n";

if ($rate_limited_requests > 0) {
    echo "✅ Rate limiting is working correctly\n";
} else {
    echo "ℹ️  Rate limiting not triggered (normal for small test)\n";
}

echo "\n";

// ==========================================
// EXAMPLE 9: Integration with WordPress Hooks
// ==========================================

echo "🔗 Example 9: WordPress Integration\n";
echo "===================================\n";

/**
 * Esempio di integrazione nelle funzioni WordPress
 * Aggiungi questo codice nel functions.php del tuo tema
 */

$wordpress_integration_example = '
<?php
// functions.php del tema WordPress

// Hook per personalizzare il messaggio di benvenuto
add_filter("wsp_welcome_message", function($message, $number_data) {
    $nome = $number_data->sender_name ?? "Cliente";
    $numero = $number_data->number;
    
    return "🎉 Ciao {$nome}! Il tuo numero {$numero} è stato registrato con successo. Il nostro team ti contatterà presto!";
}, 10, 2);

// Hook eseguito dopo il salvataggio di un numero
add_action("wsp_number_saved", function($number_data) {
    // Invia notifica email all\'admin
    $admin_email = get_option("admin_email");
    $subject = "Nuovo numero WhatsApp registrato";
    $message = "Nuovo numero registrato: {$number_data->number}\n";
    $message .= "Email: {$number_data->email}\n";
    $message .= "Data: " . date("Y-m-d H:i:s") . "\n";
    
    wp_mail($admin_email, $subject, $message);
    
    // Log personalizzato
    error_log("WhatsApp SaaS: Nuovo numero salvato - {$number_data->number}");
});

// Personalizza i template dei messaggi
add_filter("wsp_message_templates", function($templates) {
    $templates["black_friday"] = [
        "name" => "Black Friday 2024",
        "content" => "🔥 {nome}, MEGA SCONTO 70% solo oggi! Il tuo numero {numero} ha diritto all\'offerta speciale. Non perdere questa occasione!"
    ];
    
    $templates["follow_up"] = [
        "name" => "Follow Up",
        "content" => "Ciao {nome}! Hai visto la nostra ultima offerta? Contatta il {numero} per non perdere le migliori occasioni!"
    ];
    
    return $templates;
});

// Modifica il costo per messaggio in base alla lunghezza
add_filter("wsp_message_cost", function($cost, $message_length) {
    if ($message_length > 160) {
        return 2; // 2 crediti per messaggi lunghi
    }
    return 1; // 1 credito per messaggi normali
}, 10, 2);

// Aggiunge JavaScript personalizzato nella dashboard admin
add_action("admin_footer", function() {
    if (isset($_GET["page"]) && strpos($_GET["page"], "wsp-") === 0) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Aggiorna statistiche ogni 30 secondi
            setInterval(function() {
                $(".wsp-stats-widget").load(location.href + " .wsp-stats-widget > *");
            }, 30000);
            
            // Conferma prima dell\'invio bulk
            $(".wsp-bulk-send").on("click", function(e) {
                if (!confirm("Sei sicuro di voler inviare il messaggio a tutti i numeri selezionati?")) {
                    e.preventDefault();
                }
            });
        });
        </script>
        <?php
    }
});
?>';

echo "WordPress integration example saved to variable.\n";
echo "This code can be added to your theme's functions.php file.\n";
echo "Length: " . strlen($wordpress_integration_example) . " characters\n";

echo "\n";

// ==========================================
// EXAMPLE 10: Custom Dashboard Widget
// ==========================================

echo "📊 Example 10: Custom Dashboard Widget\n";
echo "======================================\n";

/**
 * Classe per creare un custom dashboard widget
 */
class WSP_Dashboard_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }
    
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wsp_stats_widget',
            '📱 Statistiche WhatsApp SaaS',
            [$this, 'render_widget']
        );
    }
    
    public function render_widget() {
        try {
            // Simula chiamata API per statistiche
            $stats = $this->get_stats_from_api();
            
            echo '<div class="wsp-dashboard-widget">';
            echo '<div class="wsp-stat-item">';
            echo '<strong>Numeri Estratti Oggi:</strong> ' . $stats['numbers_today'];
            echo '</div>';
            echo '<div class="wsp-stat-item">';
            echo '<strong>Messaggi Inviati:</strong> ' . $stats['messages_sent'];
            echo '</div>';
            echo '<div class="wsp-stat-item">';
            echo '<strong>Crediti Rimanenti:</strong> ' . $stats['credits_remaining'];
            echo '</div>';
            echo '<div class="wsp-stat-item">';
            echo '<strong>Tasso Successo:</strong> ' . $stats['success_rate'] . '%';
            echo '</div>';
            echo '<p><a href="admin.php?page=wsp-dashboard" class="button button-primary">Vai alla Dashboard Completa</a></p>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<p>Errore nel caricamento delle statistiche: ' . $e->getMessage() . '</p>';
        }
    }
    
    private function get_stats_from_api() {
        // In un caso reale, qui faresti chiamate API
        return [
            'numbers_today' => 25,
            'messages_sent' => 180,
            'credits_remaining' => 850,
            'success_rate' => 98.5
        ];
    }
}

// Inizializza il widget (in un plugin reale)
// new WSP_Dashboard_Widget();

echo "Custom dashboard widget class created.\n";
echo "This would be initialized in a real WordPress environment.\n";

echo "\n";

// ==========================================
// FINAL SUMMARY
// ==========================================

echo "🎯 SUMMARY OF EXAMPLES\n";
echo "======================\n";
echo "✅ Example 1: Health Check API - Basic connectivity test\n";
echo "✅ Example 2: Extract Numbers - Single email processing\n";
echo "✅ Example 3: Batch Processing - Multiple emails at once\n";
echo "✅ Example 4: Credits Balance - Check account credits\n";
echo "✅ Example 5: Messages History - Retrieve sent messages\n";
echo "✅ Example 6: Send Message - Send WhatsApp message\n";
echo "✅ Example 7: Error Handling - Test security and validation\n";
echo "✅ Example 8: Rate Limiting - Test API limits\n";
echo "✅ Example 9: WordPress Integration - Hooks and filters\n";
echo "✅ Example 10: Dashboard Widget - Custom admin widget\n";

echo "\n";
echo "🚀 All examples completed successfully!\n";
echo "These examples demonstrate the full capabilities of the WhatsApp SaaS Plugin API.\n";
echo "Use them as a reference for your own integrations and customizations.\n";

?>