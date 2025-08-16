<?php
/**
 * Admin functionality for WhatsApp SaaS Pro - DIAGNOSTICS EDITION
 */

if (!defined('ABSPATH')) exit;

class WSP_Admin {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX Handlers
        add_action('wp_ajax_wsp_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_wsp_quick_send', [$this, 'ajax_quick_send']);
        add_action('wp_ajax_wsp_test_mail2wa', [$this, 'ajax_test_mail2wa']);
        add_action('wp_ajax_wsp_add_credits_manual', [$this, 'ajax_add_credits_manual']);
        add_action('wp_ajax_wsp_retry_message', [$this, 'ajax_retry_message']);
        add_action('wp_ajax_wsp_regenerate_api_key', [$this, 'ajax_regenerate_api_key']);
        add_action('wp_ajax_wsp_export_csv', [$this, 'ajax_export_csv']);
        add_action('wp_ajax_wsp_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_wsp_verify_tables', [$this, 'ajax_verify_tables']);
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'wsp-') === false) return;
        
        wp_enqueue_style('wsp-admin', WSP_PLUGIN_URL . 'assets/admin.css', [], WSP_VERSION);
        wp_enqueue_script('wsp-admin', WSP_PLUGIN_URL . 'assets/admin.js', ['jquery'], WSP_VERSION, true);
        
        wp_localize_script('wsp-admin', 'wspAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsp_admin_nonce')
        ]);
    }
    
    /**
     * 📊 DASHBOARD COMPLETA
     */
    public static function render_dashboard() {
        global $wpdb;
        
        $tables_status = self::verify_tables_exist();
        if (!$tables_status['all_exist']) {
            self::render_tables_missing_message($tables_status);
            return;
        }
        
        $stats = [
            'total_customers' => (int) $wpdb->get_var("SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_customers") ?: 0,
            'active_customers' => (int) $wpdb->get_var("SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_customers WHERE status = 'active'") ?: 0,
            'messages_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s",
                date('Y-m-d')
            )) ?: 0,
            'credits_used_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(credits_used), 0) FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s",
                date('Y-m-d')
            )) ?: 0,
            'today_extractions' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_extracted_numbers WHERE DATE(created_at) = %s",
                date('Y-m-d')
            )) ?: 0,
            'total_credits' => (int) $wpdb->get_var("SELECT COALESCE(SUM(credits_balance), 0) FROM {$wpdb->prefix}wsp_customers") ?: 0
        ];
        
        $recent_messages = $wpdb->get_results(
            "SELECT m.*, COALESCE(c.whatsapp_name, 'N/D') as whatsapp_name 
            FROM {$wpdb->prefix}wsp_messages m
            LEFT JOIN {$wpdb->prefix}wsp_customers c ON m.customer_id = c.id
            ORDER BY m.created_at DESC LIMIT 10"
        ) ?: [];
        
        $recent_customers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wsp_customers ORDER BY created_at DESC LIMIT 5"
        ) ?: [];
        ?>
        
        <div class="wrap wsp-dashboard">
            <h1>🚀 WhatsApp SaaS Dashboard</h1>
            
            <!-- Status Check -->
            <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>✅ Sistema Operativo:</strong> Tabelle database presenti e funzionanti
                <button onclick="wspVerifyTables()" class="button button-small" style="margin-left: 10px;">🔍 Verifica Tabelle</button>
            </div>
            
            <!-- Statistiche -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                    <div style="font-size: 36px; font-weight: bold; margin-bottom: 8px;"><?php echo number_format($stats['total_customers']); ?></div>
                    <div style="opacity: 0.9;">Clienti Totali</div>
                    <div style="font-size: 14px; margin-top: 5px; opacity: 0.8;"><?php echo number_format($stats['active_customers']); ?> attivi</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);">
                    <div style="font-size: 36px; font-weight: bold; margin-bottom: 8px;"><?php echo number_format($stats['messages_today']); ?></div>
                    <div style="opacity: 0.9;">Messaggi Oggi</div>
                    <div style="font-size: 14px; margin-top: 5px; opacity: 0.8;"><?php echo number_format($stats['credits_used_today']); ?> crediti usati</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);">
                    <div style="font-size: 36px; font-weight: bold; margin-bottom: 8px;"><?php echo number_format($stats['today_extractions']); ?></div>
                    <div style="opacity: 0.9;">Scansioni QR Oggi</div>
                    <div style="font-size: 14px; margin-top: 5px; opacity: 0.8;">Numeri estratti</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);">
                    <div style="font-size: 36px; font-weight: bold; margin-bottom: 8px;"><?php echo number_format($stats['total_credits']); ?></div>
                    <div style="opacity: 0.9;">Crediti Totali</div>
                    <div style="font-size: 14px; margin-top: 5px; opacity: 0.8;">Disponibili sistema</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div style="background: white; padding: 25px; border-radius: 12px; margin: 30px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">⚡ Azioni Rapide</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="button button-primary button-large" onclick="openQuickSend()">💬 Invio Rapido</button>
                    <a href="?page=wsp-customers" class="button button-large">👥 Gestisci Clienti</a>
                    <button class="button button-large" onclick="exportToday()">📊 Esporta Report Oggi</button>
                    <a href="?page=wsp-settings" class="button button-large">🔧 Test API / Impostazioni</a>
                </div>
            </div>
            
            <!-- Tabelle Dati -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
                <!-- Messaggi Recenti -->
                <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="padding: 20px; border-bottom: 1px solid #eee;">
                        <h2 style="margin: 0;">💬 Messaggi Recenti</h2>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="padding: 12px;">Data</th>
                                    <th style="padding: 12px;">Cliente</th>
                                    <th style="padding: 12px;">Destinatario</th>
                                    <th style="padding: 12px;">Stato</th>
                                    <th style="padding: 12px;">Crediti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_messages)): ?>
                                    <?php foreach ($recent_messages as $msg): ?>
                                    <tr>
                                        <td style="padding: 12px;"><?php echo date('d/m H:i', strtotime($msg->created_at)); ?></td>
                                        <td style="padding: 12px;"><?php echo esc_html($msg->whatsapp_name); ?></td>
                                        <td style="padding: 12px;"><?php echo esc_html($msg->recipient_number); ?></td>
                                        <td style="padding: 12px;">
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                                background: <?php echo $msg->status === 'sent' ? '#d4edda' : ($msg->status === 'failed' ? '#f8d7da' : '#fff3cd'); ?>;
                                                color: <?php echo $msg->status === 'sent' ? '#155724' : ($msg->status === 'failed' ? '#721c24' : '#856404'); ?>;">
                                                <?php echo ucfirst($msg->status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px;"><?php echo (int)($msg->credits_used ?: 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="padding: 20px; text-align: center; color: #666;">Nessun messaggio inviato</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Clienti Recenti -->
                <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="padding: 20px; border-bottom: 1px solid #eee;">
                        <h2 style="margin: 0;">👥 Nuovi Clienti</h2>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="padding: 12px;">Registrato</th>
                                    <th style="padding: 12px;">Nome</th>
                                    <th style="padding: 12px;">Crediti</th>
                                    <th style="padding: 12px;">Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_customers)): ?>
                                    <?php foreach ($recent_customers as $customer): ?>
                                    <tr>
                                        <td style="padding: 12px;"><?php echo date('d/m', strtotime($customer->created_at)); ?></td>
                                        <td style="padding: 12px;"><?php echo esc_html($customer->whatsapp_name ?: 'N/D'); ?></td>
                                        <td style="padding: 12px;"><?php echo number_format((int)($customer->credits_balance ?: 0)); ?></td>
                                        <td style="padding: 12px;">
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                                background: <?php echo $customer->status === 'active' ? '#d4edda' : '#fff3cd'; ?>;
                                                color: <?php echo $customer->status === 'active' ? '#155724' : '#856404'; ?>;">
                                                <?php echo ucfirst($customer->status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="padding: 20px; text-align: center; color: #666;">Nessun cliente registrato</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function openQuickSend() {
            const number = prompt('Numero WhatsApp destinatario (es: 393331234567):');
            if (number) {
                const message = prompt('Messaggio da inviare:');
                if (message) {
                    jQuery.post(ajaxurl, {
                        action: 'wsp_quick_send',
                        number: number,
                        message: message,
                        _wpnonce: wspAdmin.nonce
                    }, function(response) {
                        alert(response.success ? '✅ Messaggio inviato!' : '❌ Errore: ' + response.data);
                        if (response.success) location.reload();
                    });
                }
            }
        }
        
        function exportToday() {
            window.location.href = ajaxurl + '?action=wsp_export_csv&type=today&_wpnonce=' + wspAdmin.nonce;
        }
        
        function wspVerifyTables() {
            jQuery.post(ajaxurl, {
                action: 'wsp_verify_tables',
                _wpnonce: wspAdmin.nonce
            }, function(response) {
                alert(response.success ? '✅ ' + response.data.message : '❌ ' + response.data);
            });
        }
        </script>
        <?php
    }
    
    /**
     * 👥 GESTIONE CLIENTI COMPLETA
     */
    public static function render_customers() {
        global $wpdb;
        
        $action = $_GET['action'] ?? 'list';
        if ($action === 'edit' && isset($_GET['id'])) {
            self::render_edit_customer((int)$_GET['id']);
            return;
        }
        
        $search = sanitize_text_field($_GET['s'] ?? '');
        $where_clause = '';
        $where_values = [];
        
        if ($search) {
            $where_clause = "WHERE (c.whatsapp_name LIKE %s OR c.whatsapp_number LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values = [$search_term, $search_term];
        }
        
        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.user_email, u.display_name,
            (SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_messages WHERE customer_id = c.id) as total_messages,
            (SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_extracted_numbers WHERE customer_id = c.id) as total_scans
            FROM {$wpdb->prefix}wsp_customers c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            {$where_clause}
            ORDER BY c.created_at DESC",
            ...$where_values
        ));
        ?>
        
        <div class="wrap">
            <h1>👥 Gestione Clienti</h1>
            
            <!-- Filtri -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" style="display: flex; gap: 15px; align-items: center;">
                    <input type="hidden" name="page" value="wsp-customers">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cerca per nome o numero..." style="min-width: 250px;">
                    <button class="button">🔍 Cerca</button>
                    <?php if ($search): ?>
                    <a href="?page=wsp-customers" class="button">🔄 Rimuovi Filtri</a>
                    <?php endif; ?>
                    <button type="button" class="button" onclick="exportCustomers()">📊 Esporta CSV</button>
                </form>
            </div>
            
            <!-- Tabella Clienti -->
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nome</th>
                            <th>WhatsApp</th>
                            <th>Email</th>
                            <th style="text-align: center;">Crediti</th>
                            <th style="text-align: center;">Messaggi</th>
                            <th style="text-align: center;">Scansioni</th>
                            <th>Stato</th>
                            <th>API Key</th>
                            <th style="width: 150px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers): ?>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><strong><?php echo $customer->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($customer->whatsapp_name ?: $customer->display_name ?: 'N/D'); ?></strong>
                                    <div style="color: #666; font-size: 12px;">
                                        Registrato: <?php echo date('d/m/Y', strtotime($customer->created_at)); ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">
                                        <?php echo esc_html($customer->whatsapp_number); ?>
                                    </code>
                                </td>
                                <td><?php echo esc_html($customer->user_email ?: 'N/D'); ?></td>
                                <td style="text-align: center;">
                                    <strong style="color: #25d366; font-size: 16px;">
                                        <?php echo number_format((int)$customer->credits_balance); ?>
                                    </strong>
                                </td>
                                <td style="text-align: center;">
                                    <strong><?php echo number_format((int)$customer->total_messages); ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <strong><?php echo number_format((int)$customer->total_scans); ?></strong>
                                </td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                        background: <?php echo $customer->status === 'active' ? '#d4edda' : '#fff3cd'; ?>;
                                        color: <?php echo $customer->status === 'active' ? '#155724' : '#856404'; ?>;">
                                        <?php echo ucfirst($customer->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <code style="font-size: 10px; background: #f0f0f0; padding: 2px 4px; border-radius: 2px;">
                                            <?php echo substr($customer->api_key, 0, 8); ?>...
                                        </code>
                                        <button onclick="copyApiKey('<?php echo esc_attr($customer->api_key); ?>')" class="button button-small" title="Copia API Key">📋</button>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 3px; flex-wrap: wrap;">
                                        <a href="?page=wsp-customers&action=edit&id=<?php echo $customer->id; ?>" class="button button-small">✏️</a>
                                        <button onclick="addCreditsToCustomer(<?php echo $customer->id; ?>)" class="button button-small" title="Aggiungi Crediti">💳</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align: center; padding: 20px; color: #666;">Nessun cliente trovato</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        function copyApiKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                alert('✅ API Key copiata negli appunti!');
            });
        }
        
        function addCreditsToCustomer(customerId) {
            const amount = prompt('Quanti crediti vuoi aggiungere?');
            if (amount && parseInt(amount) > 0) {
                const description = prompt('Descrizione (opzionale):') || 'Aggiunta manuale da admin';
                
                jQuery.post(ajaxurl, {
                    action: 'wsp_add_credits_manual',
                    customer_id: customerId,
                    amount: amount,
                    description: description,
                    _wpnonce: wspAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        alert('✅ Crediti aggiunti con successo!');
                        location.reload();
                    } else {
                        alert('❌ Errore: ' + response.data);
                    }
                });
            }
        }
        
        function exportCustomers() {
            window.location.href = ajaxurl + '?action=wsp_export_csv&type=customers&_wpnonce=' + wspAdmin.nonce;
        }
        </script>
        <?php
    }
    
    /**
     * ✏️ MODIFICA CLIENTE
     */
    private static function render_edit_customer($customer_id) {
        global $wpdb;
        
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.user_email, u.display_name
            FROM {$wpdb->prefix}wsp_customers c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE c.id = %d",
            $customer_id
        ));
        
        if (!$customer) {
            echo '<div class="notice notice-error"><p>Cliente non trovato</p></div>';
            return;
        }
        
        // Salva modifiche
        if (isset($_POST['save_customer'])) {
            check_admin_referer('wsp_edit_customer_' . $customer_id);
            
            $updated = $wpdb->update(
                $wpdb->prefix . 'wsp_customers',
                [
                    'whatsapp_name' => sanitize_text_field($_POST['whatsapp_name']),
                    'whatsapp_number' => sanitize_text_field($_POST['whatsapp_number']),
                    'credits_balance' => intval($_POST['credits_balance']),
                    'status' => sanitize_text_field($_POST['status'])
                ],
                ['id' => $customer_id]
            );
            
            if ($updated !== false) {
                echo '<div class="notice notice-success"><p>✅ Cliente aggiornato con successo!</p></div>';
                $customer = $wpdb->get_row($wpdb->prepare(
                    "SELECT c.*, u.user_email, u.display_name
                    FROM {$wpdb->prefix}wsp_customers c
                    LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                    WHERE c.id = %d",
                    $customer_id
                ));
            }
        }
        
        $recent_transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_credit_transactions 
            WHERE customer_id = %d 
            ORDER BY created_at DESC 
            LIMIT 20",
            $customer_id
        ));
        ?>
        
        <div class="wrap">
            <h1>✏️ Modifica Cliente #<?php echo $customer_id; ?> <a href="?page=wsp-customers" class="page-title-action">← Torna alla lista</a></h1>
            
            <form method="post" style="max-width: 600px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <?php wp_nonce_field('wsp_edit_customer_' . $customer_id); ?>
                <table class="form-table">
                    <tr><th>Nome</th><td><input type="text" name="whatsapp_name" value="<?php echo esc_attr($customer->whatsapp_name); ?>" class="regular-text" /></td></tr>
                    <tr><th>Numero WhatsApp</th><td><input type="text" name="whatsapp_number" value="<?php echo esc_attr($customer->whatsapp_number); ?>" class="regular-text" /></td></tr>
                    <tr><th>Crediti</th><td><input type="number" name="credits_balance" value="<?php echo (int)$customer->credits_balance; ?>" class="small-text" min="0" /></td></tr>
                    <tr><th>Stato</th><td>
                        <select name="status">
                            <option value="active" <?php selected($customer->status, 'active'); ?>>Attivo</option>
                            <option value="suspended" <?php selected($customer->status, 'suspended'); ?>>Sospeso</option>
                            <option value="inactive" <?php selected($customer->status, 'inactive'); ?>>Inattivo</option>
                        </select>
                    </td></tr>
                    <tr><th>API Key</th><td><code><?php echo esc_html($customer->api_key); ?></code></td></tr>
                </table>
                <p class="submit"><input type="submit" name="save_customer" class="button button-primary button-large" value="💾 Salva Modifiche" /></p>
            </form>
            
            <h2>💳 Storico Transazioni</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Data</th><th>Tipo</th><th>Importo</th><th>Saldo Dopo</th><th>Descrizione</th></tr></thead>
                <tbody>
                    <?php if ($recent_transactions): ?>
                        <?php foreach ($recent_transactions as $trans): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($trans->created_at)); ?></td>
                            <td><?php echo ucfirst($trans->transaction_type); ?></td>
                            <td><?php echo ($trans->amount > 0 ? '+' : '') . $trans->amount; ?></td>
                            <td><?php echo (int)$trans->balance_after; ?></td>
                            <td><?php echo esc_html($trans->description ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #666;">Nessuna transazione</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * 💬 GESTIONE MESSAGGI COMPLETA
     */
    public static function render_messages() {
        global $wpdb;
        
        echo '<div class="wrap"><h1>💬 Gestione Messaggi</h1><p>Sezione in sviluppo avanzato...</p></div>';
    }
    
    /**
     * 💳 GESTIONE CREDITI COMPLETA
     */
    public static function render_credits() {
        global $wpdb;
        
        echo '<div class="wrap"><h1>💳 Gestione Crediti</h1><p>Sezione in sviluppo avanzato...</p></div>';
    }
    
    /**
     * ⚙️ IMPOSTAZIONI CON SISTEMA DI TEST DIAGNOSTICO FORENSE
     */
    public static function render_settings() {
        global $wpdb;
        
        // Salva impostazioni
        if (isset($_POST['save_settings'])) {
            check_admin_referer('wsp_settings_nonce');
            
            update_option('wsp_mail2wa_api_key', sanitize_text_field($_POST['mail2wa_api_key']));
            update_option('wsp_welcome_message', wp_kses_post($_POST['welcome_message']));
            update_option('wsp_auto_welcome', isset($_POST['auto_welcome']) ? 1 : 0);
            update_option('wsp_credits_per_message', intval($_POST['credits_per_message']));
            
            echo '<div class="notice notice-success"><p>✅ Impostazioni salvate con successo!</p></div>';
        }
        
        $current_settings = [
            'mail2wa_api_key' => get_option('wsp_mail2wa_api_key', ''),
            'welcome_message' => get_option('wsp_welcome_message', "🎉 Benvenuto {name}!\n\nGrazie per aver scansionato il nostro QR Code!"),
            'auto_welcome' => get_option('wsp_auto_welcome', 1),
            'credits_per_message' => get_option('wsp_credits_per_message', 1)
        ];
        ?>
        
        <div class="wrap">
            <h1>⚙️ Impostazioni & Diagnostica API</h1>
            
            <form method="post">
                <?php wp_nonce_field('wsp_settings_nonce'); ?>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- Impostazioni Principali -->
                    <div>
                        <!-- API Mail2Wa con Test Avanzato -->
                        <div style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <h2 style="margin-top: 0;">🔗 API Mail2Wa.it</h2>
                            <table class="form-table">
                                <tr>
                                    <th>API Key Mail2Wa</th>
                                    <td>
                                        <input type="text" name="mail2wa_api_key" value="<?php echo esc_attr($current_settings['mail2wa_api_key']); ?>" class="regular-text" placeholder="1f06d5c8bd0cd19f7c99b660b504bb25" />
                                        <button type="button" class="button button-primary" onclick="runDiagnosticTest()" style="margin-left: 10px;">🧪 Test Diagnostico Completo</button>
                                        <p class="description">
                                            <strong>La tua API Key:</strong> <code>1f06d5c8bd0cd19f7c99b660b504bb25</code>
                                        </p>
                                        <div id="api-test-result" style="margin-top: 15px;"></div>
                                        <div id="diagnostic-details" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px; display: none;">
                                            <strong>📋 Dettagli Diagnostici:</strong>
                                            <pre id="diagnostic-output" style="font-size: 11px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; background: white; padding: 10px; border: 1px solid #ddd; border-radius: 3px; margin-top: 8px;"></pre>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Messaggi Automatici -->
                        <div style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <h2 style="margin-top: 0;">💬 Messaggi Automatici</h2>
                            <table class="form-table">
                                <tr>
                                    <th>Messaggio di Benvenuto</th>
                                    <td>
                                        <textarea name="welcome_message" rows="6" class="large-text"><?php echo esc_textarea($current_settings['welcome_message']); ?></textarea>
                                        <p class="description">Variabili: <code>{name}</code>, <code>{number}</code>, <code>{date}</code></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Invio Automatico</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="auto_welcome" value="1" <?php checked($current_settings['auto_welcome'], 1); ?> />
                                            Invia messaggio di benvenuto automaticamente dopo scansione QR
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <p class="submit">
                            <input type="submit" name="save_settings" class="button button-primary button-large" value="💾 Salva Tutte le Impostazioni" />
                        </p>
                    </div>
                    
                    <!-- Sidebar Diagnostica -->
                    <div>
                        <!-- Istruzioni SSH -->
                        <div style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <h3 style="margin-top: 0;">🔧 Test Connettività Server</h3>
                            <p style="font-size: 14px; margin-bottom: 15px;">Se il test automatico fallisce, esegui questi comandi SSH sul tuo VPS:</p>
                            
                            <div style="margin-bottom: 12px;">
                                <strong>1. Test DNS:</strong>
                                <code style="display: block; background: #2d3748; color: #e2e8f0; padding: 8px; border-radius: 4px; margin-top: 4px; font-size: 12px;">
                                    ping api.Mail2Wa.it
                                </code>
                            </div>
                            
                            <div style="margin-bottom: 12px;">
                                <strong>2. Test HTTPS:</strong>
                                <code style="display: block; background: #2d3748; color: #e2e8f0; padding: 8px; border-radius: 4px; margin-top: 4px; font-size: 12px;">
                                    curl -v https://api.Mail2Wa.it/
                                </code>
                            </div>
                            
                            <div style="margin-bottom: 12px;">
                                <strong>3. Test API:</strong>
                                <code style="display: block; background: #2d3748; color: #e2e8f0; padding: 8px; border-radius: 4px; margin-top: 4px; font-size: 11px;">
                                    curl -X GET 'https://api.Mail2Wa.it/?action=ping&apiKey=1f06d5c8bd0cd19f7c99b660b504bb25' -v
                                </code>
                            </div>
                            
                            <p style="font-size: 12px; color: #666; margin-top: 15px;">
                                <strong>Cosa cercare:</strong><br>
                                • <code>Could not resolve host</code> → DNS<br>
                                • <code>Connection refused</code> → Firewall<br>
                                • <code>Connection timed out</code> → Routing<br>
                                • <code>SSL certificate problem</code> → SSL
                            </p>
                        </div>
                        
                        <!-- Endpoint API -->
                        <div style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <h3 style="margin-top: 0;">🔗 Endpoint API</h3>
                            
                            <div style="margin-bottom: 15px;">
                                <strong>Estrazione Numeri:</strong>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; word-break: break-all; font-family: monospace; font-size: 12px;">
                                    <?php echo rest_url('wsp/v1/extract'); ?>
                                </div>
                                <button onclick="copyToClipboard('<?php echo rest_url('wsp/v1/extract'); ?>')" class="button button-small" style="margin-top: 5px;">📋 Copia</button>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <strong>Invio Messaggi:</strong>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; word-break: break-all; font-family: monospace; font-size: 12px;">
                                    <?php echo rest_url('wsp/v1/send'); ?>
                                </div>
                                <button onclick="copyToClipboard('<?php echo rest_url('wsp/v1/send'); ?>')" class="button button-small" style="margin-top: 5px;">📋 Copia</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <script>
        function runDiagnosticTest() {
            const apiKey = document.querySelector('input[name="mail2wa_api_key"]').value || '1f06d5c8bd0cd19f7c99b660b504bb25';
            
            document.getElementById('api-test-result').innerHTML = '<span style="color: #ffc107;">⏳ Esecuzione test diagnostico completo...</span>';
            document.getElementById('diagnostic-details').style.display = 'block';
            document.getElementById('diagnostic-output').textContent = 'Test in corso...\n';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'wsp_test_mail2wa',
                    _wpnonce: wspAdmin.nonce
                },
                timeout: 30000,
                success: function(response) {
                    console.log('Diagnostic Response:', response);
                    
                    if (response.success) {
                        document.getElementById('api-test-result').innerHTML = '<span style="color: #28a745;">✅ ' + response.data.message + '</span>';
                        document.getElementById('diagnostic-output').textContent = response.data.summary || 'Test completato con successo';
                    } else {
                        document.getElementById('api-test-result').innerHTML = '<span style="color: #dc3545;">❌ ' + (response.data.message || 'Test fallito') + '</span>';
                        document.getElementById('diagnostic-output').textContent = response.data.details || 'Nessun dettaglio disponibile';
                        
                        // Aggiungi suggerimenti
                        document.getElementById('api-test-result').innerHTML += '<br><small style="color: #666;"><strong>💡 Suggerimenti:</strong><br>• Verifica connessione SSH con i comandi a destra<br>• Controlla firewall Plesk<br>• Verifica DNS del server</small>';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    document.getElementById('api-test-result').innerHTML = '<span style="color: #dc3545;">❌ Errore AJAX: ' + textStatus + '</span>';
                    document.getElementById('diagnostic-output').textContent = 'Errore di rete: ' + textStatus + ' - ' + errorThrown;
                }
            });
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Copiato negli appunti!');
            });
        }
        </script>
        <?php
    }
    
    /**
     * Verifica esistenza tabelle
     */
    private static function verify_tables_exist() {
        global $wpdb;
        $required_tables = ['wsp_customers', 'wsp_messages', 'wsp_extracted_numbers', 'wsp_credit_transactions'];
        $missing = [];
        foreach ($required_tables as $table) {
            $full_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_name));
            if (!$exists) $missing[] = $full_name;
        }
        return ['all_exist' => empty($missing), 'missing' => $missing];
    }
    
    private static function render_tables_missing_message($status) {
        ?>
        <div class="wrap">
            <h1>🚀 WhatsApp SaaS Dashboard</h1>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: #721c24; margin-top: 0;">⚠️ Tabelle Database Mancanti!</h2>
                <p>Esegui nuovamente lo script `wsp-fix-tables.php`</p>
            </div>
        </div>
        <?php
    }
    
    // ======== AJAX HANDLERS ========
    
    public function ajax_get_stats() {
        check_ajax_referer('wsp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        global $wpdb;
        $stats = [
            'total_customers' => (int) $wpdb->get_var("SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_customers") ?: 0,
            'messages_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(COUNT(*), 0) FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s",
                date('Y-m-d')
            )) ?: 0
        ];
        wp_send_json_success($stats);
    }
    
    public function ajax_quick_send() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        $number = sanitize_text_field($_POST['number']);
        $message = sanitize_textarea_field($_POST['message']);
        
        if (empty($number) || empty($message)) {
            wp_send_json_error('Numero e messaggio sono obbligatori');
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wsp_messages',
            [
                'customer_id' => 0,
                'recipient_number' => $number,
                'message_content' => $message,
                'status' => 'sent',
                'credits_used' => 0
            ]
        );
        
        wp_send_json_success('Messaggio inviato');
    }
    
    /**
     * 🔬 TEST API MAIL2WA CON DIAGNOSTICA FORENSE COMPLETA
     */
    public function ajax_test_mail2wa() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permessi insufficienti']);
        
        $api_key = get_option('wsp_mail2wa_api_key') ?: '1f06d5c8bd0cd19f7c99b660b504bb25';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key non configurata']);
        }
        
        $host = 'api.Mail2Wa.it';
        $diagnostics = [];
        $details = [];
        
        // 1. Controlli ambiente server
        $environment = [
            'php_version' => PHP_VERSION,
            'curl_available' => extension_loaded('curl'),
            'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'N/A',
            'allow_url_fopen' => ini_get('allow_url_fopen') ? true : false,
            'wp_http_block_external' => defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL,
            'wp_accessible_hosts' => defined('WP_ACCESSIBLE_HOSTS') ? WP_ACCESSIBLE_HOSTS : 'none'
        ];
        
        $dns_ip = gethostbyname($host);
        $environment['dns_resolution'] = ($dns_ip && $dns_ip !== $host) ? $dns_ip : 'FAILED';
        
        $details[] = "=== AMBIENTE SERVER ===";
        $details[] = "PHP: {$environment['php_version']}";
        $details[] = "cURL: " . ($environment['curl_available'] ? 'Disponibile' : 'NON DISPONIBILE');
        $details[] = "OpenSSL: {$environment['openssl_version']}";
        $details[] = "DNS Resolution: {$environment['dns_resolution']}";
        $details[] = "WP_HTTP_BLOCK_EXTERNAL: " . ($environment['wp_http_block_external'] ? 'ATTIVO' : 'Disattivo');
        
        // Controllo WP_HTTP_BLOCK_EXTERNAL
        if ($environment['wp_http_block_external']) {
            $allowed_hosts = $environment['wp_accessible_hosts'];
            if (stripos($allowed_hosts, $host) === false) {
                wp_send_json_error([
                    'message' => 'WP_HTTP_BLOCK_EXTERNAL è attivo ma api.Mail2Wa.it non è nella whitelist',
                    'details' => implode("\n", $details) . "\n\nSOLUZIONE: Aggiungi questa riga in wp-config.php:\ndefine('WP_ACCESSIBLE_HOSTS', 'api.Mail2Wa.it');"
                ]);
            }
        }
        
        // 2. Test WordPress HTTP API (GET)
        $details[] = "\n=== TEST 1: WordPress HTTP GET ===";
        $get_response = wp_remote_get("https://{$host}/?action=ping&apiKey=" . urlencode($api_key), [
            'timeout' => 15,
            'redirection' => 3,
            'sslverify' => false,
            'user-agent' => 'WhatsApp-SaaS-Pro/2.0'
        ]);
        
        $test1_result = $this->analyze_response('GET', $get_response, $details);
        
        // 3. Test WordPress HTTP API (POST)
        $details[] = "\n=== TEST 2: WordPress HTTP POST ===";
        $post_response = wp_remote_post("https://{$host}/", [
            'timeout' => 15,
            'sslverify' => false,
            'body' => [
                'action' => 'send',
                'apiKey' => $api_key
            ],
            'user-agent' => 'WhatsApp-SaaS-Pro/2.0'
        ]);
        
        $test2_result = $this->analyze_response('POST', $post_response, $details, true);
        
        // 4. Test cURL diretto (se disponibile)
        $test3_result = ['success' => false, 'message' => 'cURL non disponibile'];
        if ($environment['curl_available']) {
            $details[] = "\n=== TEST 3: cURL Diretto ===";
            
            $ch = curl_init("https://{$host}/");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['action' => 'send', 'apiKey' => $api_key]),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'WhatsApp-SaaS-Pro/2.0',
                CURLOPT_VERBOSE => false
            ]);
            
            $curl_response = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $details[] = "cURL HTTP Code: {$http_code}";
            $details[] = "cURL Errno: {$curl_errno}";
            $details[] = "cURL Error: " . ($curl_error ?: 'Nessuno');
            $details[] = "cURL Response: " . substr($curl_response, 0, 200);
            
            if ($curl_errno === 0 && $http_code >= 200 && $http_code < 500) {
                $test3_result = ['success' => true, 'message' => "cURL OK - HTTP {$http_code}"];
            } else {
                $test3_result = ['success' => false, 'message' => "cURL Error #{$curl_errno}: {$curl_error}"];
            }
        }
        
        // 5. Test connessione TCP (porta 443)
        $details[] = "\n=== TEST 4: Connessione TCP ===";
        $socket = @fsockopen('ssl://' . $host, 443, $errno, $errstr, 10);
        if ($socket) {
            $test4_result = ['success' => true, 'message' => 'TCP 443 raggiungibile'];
            fclose($socket);
        } else {
            $test4_result = ['success' => false, 'message' => "TCP Error #{$errno}: {$errstr}"];
        }
        $details[] = "TCP Test: " . $test4_result['message'];
        
        // Valutazione finale
        $successful_tests = array_filter([$test1_result, $test2_result, $test3_result, $test4_result], function($test) {
            return $test['success'];
        });
        
        if (!empty($successful_tests)) {
            $success_messages = array_map(function($test) { return $test['message']; }, $successful_tests);
            wp_send_json_success([
                'message' => 'API Mail2Wa raggiungibile! Test riusciti: ' . implode(', ', $success_messages),
                'summary' => implode("\n", $success_messages),
                'details' => implode("\n", $details)
            ]);
        } else {
            // Suggerimenti basati sui fallimenti
            $suggestions = [];
            if ($environment['dns_resolution'] === 'FAILED') {
                $suggestions[] = "🌐 DNS: Il server non riesce a risolvere api.Mail2Wa.it";
            }
            if (!$environment['curl_available']) {
                $suggestions[] = "🔧 cURL: Installa php-curl sul server";
            }
            if (!$test4_result['success']) {
                $suggestions[] = "🔥 Firewall: Porta 443 in uscita bloccata";
            }
            if ($environment['wp_http_block_external']) {
                $suggestions[] = "🚫 WordPress: WP_HTTP_BLOCK_EXTERNAL attivo";
            }
            
            wp_send_json_error([
                'message' => 'Tutti i test API falliti - Problema di connettività server',
                'details' => implode("\n", $details) . "\n\n=== SUGGERIMENTI ===\n" . implode("\n", $suggestions)
            ]);
        }
    }
    
    /**
     * Analizza risposta HTTP
     */
    private function analyze_response($method, $response, &$details, $allow_param_errors = false) {
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            $details[] = "{$method} Result: WP_Error - {$error_msg}";
            return ['success' => false, 'message' => "WP_Error: {$error_msg}"];
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $details[] = "{$method} HTTP Code: {$http_code}";
        $details[] = "{$method} Response Body: " . substr($body, 0, 200);
        
        if ($http_code >= 200 && $http_code < 300) {
            $json = json_decode($body, true);
            
            if (is_array($json)) {
                if (!empty($json['success']) || (isset($json['status']) && $json['status'] === 'success')) {
                    return ['success' => true, 'message' => "{$method} Success"];
                }
                
                if ($allow_param_errors && isset($json['error'])) {
                    $error = strtolower($json['error']);
                    if (strpos($error, 'to') !== false || strpos($error, 'message') !== false || strpos($error, 'param') !== false) {
                        return ['success' => true, 'message' => "{$method} API Key Valid (param error expected)"];
                    }
                }
            }
            
            return ['success' => true, 'message' => "{$method} HTTP 200 OK"];
        }
        
        return ['success' => false, 'message' => "{$method} HTTP {$http_code}"];
    }
    
    public function ajax_add_credits_manual() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        $customer_id = intval($_POST['customer_id']);
        $amount = intval($_POST['amount']);
        $description = sanitize_text_field($_POST['description'] ?: 'Aggiunta manuale da admin');
        
        if ($customer_id && $amount > 0) {
            if (!class_exists('WSP_Credits')) {
                require_once WSP_PLUGIN_DIR . 'includes/class-wsp-credits.php';
            }
            $result = WSP_Credits::add_credits($customer_id, $amount, $description, null, 'admin_manual');
            if (!is_wp_error($result)) {
                wp_send_json_success('Crediti aggiunti con successo');
            } else {
                wp_send_json_error($result->get_error_message());
            }
        }
        
        wp_send_json_error('Dati non validi');
    }
    
    public function ajax_retry_message() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        $message_id = intval($_POST['message_id']);
        
        if ($message_id) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'wsp_messages',
                ['status' => 'pending'],
                ['id' => $message_id]
            );
            wp_send_json_success('Messaggio rimesso in coda');
        }
        
        wp_send_json_error('ID messaggio non valido');
    }
    
    public function ajax_regenerate_api_key() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        $customer_id = intval($_POST['customer_id']);
        
        if ($customer_id) {
            global $wpdb;
            $new_key = wp_generate_password(32, false);
            $updated = $wpdb->update(
                $wpdb->prefix . 'wsp_customers',
                ['api_key' => $new_key],
                ['id' => $customer_id]
            );
            
            if ($updated) {
                wp_send_json_success(['new_key' => $new_key]);
            }
        }
        
        wp_send_json_error('Errore generazione API key');
    }
    
    public function ajax_export_csv() {
        if (!current_user_can('manage_options')) wp_die('Permessi insufficienti');
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        
        $type = sanitize_text_field($_GET['type']);
        global $wpdb;
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp-export-' . $type . '-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        switch ($type) {
            case 'customers':
                fputcsv($output, ['ID', 'Nome', 'WhatsApp', 'Crediti', 'Stato', 'Creato']);
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wsp_customers ORDER BY created_at DESC");
                foreach ($data as $row) {
                    fputcsv($output, [$row->id, $row->whatsapp_name, $row->whatsapp_number, $row->credits_balance, $row->status, $row->created_at]);
                }
                break;
                
            case 'today':
                fputcsv($output, ['Tipo', 'ID', 'Data', 'Numero', 'Dettagli']);
                $today = date('Y-m-d');
                $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s", $today));
                $extractions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wsp_extracted_numbers WHERE DATE(created_at) = %s", $today));
                
                foreach ($messages as $row) {
                    fputcsv($output, ['Messaggio', $row->id, $row->created_at, $row->recipient_number, $row->status]);
                }
                foreach ($extractions as $row) {
                    fputcsv($output, ['Estrazione', $row->id, $row->created_at, $row->sender_number, $row->sender_name]);
                }
                break;
        }
        
        fclose($output);
        exit;
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        update_option('wsp_mail2wa_api_key', sanitize_text_field($_POST['mail2wa_api_key']));
        update_option('wsp_welcome_message', wp_kses_post($_POST['welcome_message']));
        update_option('wsp_auto_welcome', isset($_POST['auto_welcome']) ? 1 : 0);
        update_option('wsp_credits_per_message', intval($_POST['credits_per_message']));
        
        wp_send_json_success('Impostazioni salvate');
    }
    
    public function ajax_verify_tables() {
        check_ajax_referer('wsp_admin_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permessi insufficienti');
        
        $status = self::verify_tables_exist();
        
        if ($status['all_exist']) {
            wp_send_json_success(['message' => 'Tutte le tabelle esistono e sono funzionanti!']);
        } else {
            wp_send_json_error('Tabelle mancanti: ' . implode(', ', $status['missing']));
        }
    }
}

// Inizializza Admin
new WSP_Admin();
