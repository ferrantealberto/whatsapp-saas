<?php
/**
 * Template: Dashboard Admin
 */

// Security check
if (!defined('ABSPATH')) exit;

// Get stats
global $wpdb;
$stats = [
    'total_customers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wsp_customers"),
    'active_customers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wsp_customers WHERE status = 'active'"),
    'messages_today' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s",
        date('Y-m-d')
    )),
    'credits_used_today' => $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(credits_used), 0) FROM {$wpdb->prefix}wsp_messages WHERE DATE(created_at) = %s",
        date('Y-m-d')
    ))
];

// Recent activity
$recent_messages = $wpdb->get_results(
    "SELECT m.*, c.whatsapp_name 
    FROM {$wpdb->prefix}wsp_messages m
    LEFT JOIN {$wpdb->prefix}wsp_customers c ON m.customer_id = c.id
    ORDER BY m.created_at DESC 
    LIMIT 10"
);

$recent_customers = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}wsp_customers 
    ORDER BY created_at DESC 
    LIMIT 5"
);
?>

<div class="wrap wsp-dashboard">
    <div class="wsp-header">
        <h1>🚀 WhatsApp SaaS Dashboard</h1>
    </div>

    <!-- Statistiche -->
    <div class="wsp-stats-grid">
        <div class="wsp-stat-card">
            <div class="wsp-stat-value wsp-stat-customers"><?php echo number_format($stats['total_customers']); ?></div>
            <div class="wsp-stat-label">Clienti Totali</div>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-value"><?php echo number_format($stats['active_customers']); ?></div>
            <div class="wsp-stat-label">Clienti Attivi</div>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-value wsp-stat-messages"><?php echo number_format($stats['messages_today']); ?></div>
            <div class="wsp-stat-label">Messaggi Oggi</div>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-value wsp-stat-credits"><?php echo number_format($stats['credits_used_today']); ?></div>
            <div class="wsp-stat-label">Crediti Usati Oggi</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin: 30px 0;">
        <button class="wsp-btn wsp-quick-send">💬 Invio Rapido</button>
        <a href="<?php echo admin_url('admin.php?page=wsp-customers'); ?>" class="wsp-btn wsp-btn-secondary">👥 Gestisci Clienti</a>
        <a href="<?php echo admin_url('admin.php?page=wsp-credits'); ?>" class="wsp-btn wsp-btn-secondary">💳 Gestisci Crediti</a>
        <button class="wsp-btn wsp-btn-secondary" onclick="wspExportCSV('messages')">📊 Esporta Report</button>
    </div>

    <!-- Tabs -->
    <div class="wsp-tabs">
        <button class="wsp-tab active" data-target="tab-messages">Messaggi Recenti</button>
        <button class="wsp-tab" data-target="tab-customers">Nuovi Clienti</button>
        <button class="wsp-tab" data-target="tab-charts">Grafici</button>
    </div>

    <!-- Tab Contents -->
    <div id="tab-messages" class="wsp-tab-content">
        <div class="wsp-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Destinatario</th>
                        <th>Tipo</th>
                        <th>Stato</th>
                        <th>Crediti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_messages as $msg): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($msg->created_at)); ?></td>
                        <td><?php echo esc_html($msg->whatsapp_name); ?></td>
                        <td><?php echo esc_html($msg->recipient_number); ?></td>
                        <td><?php echo esc_html($msg->message_type); ?></td>
                        <td>
                            <span class="wsp-badge wsp-badge-<?php echo $msg->status === 'sent' ? 'success' : ($msg->status === 'failed' ? 'error' : 'warning'); ?>">
                                <?php echo ucfirst($msg->status); ?>
                            </span>
                        </td>
                        <td><?php echo $msg->credits_used; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-customers" class="wsp-tab-content" style="display:none;">
        <div class="wsp-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Registrato</th>
                        <th>Nome</th>
                        <th>WhatsApp</th>
                        <th>Crediti</th>
                        <th>Piano</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_customers as $customer): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($customer->created_at)); ?></td>
                        <td><?php echo esc_html($customer->whatsapp_name); ?></td>
                        <td><?php echo esc_html($customer->whatsapp_number); ?></td>
                        <td><?php echo number_format($customer->credits_balance); ?></td>
                        <td>
                            <span class="wsp-badge">
                                <?php echo ucfirst($customer->subscription_status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wsp-customers&action=edit&id=' . $customer->id); ?>" class="button button-small">
                                Modifica
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-charts" class="wsp-tab-content" style="display:none;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="background: white; padding: 20px; border-radius: 8px;">
                <canvas id="wsp-messages-chart"></canvas>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px;">
                <canvas id="wsp-credits-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>