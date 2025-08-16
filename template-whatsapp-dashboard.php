<?php
/**
 * Template: WhatsApp Dashboard Cliente
 */

// Verifica login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$user_id = get_current_user_id();
$customer = WSP_Frontend::get_customer_data($user_id);

if (!$customer) {
    echo '<div class="wsp-error">Account WhatsApp non trovato. Contatta il supporto.</div>';
    return;
}

// Ottieni statistiche
$stats = WSP_Frontend::get_customer_stats($customer->id);
$recent_messages = WSP_Frontend::get_recent_messages($customer->id, 10);
$recent_scans = WSP_Frontend::get_recent_scans($customer->id, 10);
?>

<div class="wsp-dashboard">
    <!-- Header -->
    <div class="wsp-header">
        <h1>WhatsApp Dashboard</h1>
        <div class="wsp-user-info">
            <span class="wsp-user-name"><?php echo esc_html($customer->whatsapp_name); ?></span>
            <span class="wsp-user-number"><?php echo esc_html($customer->whatsapp_number); ?></span>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="wsp-stats-grid">
        <div class="wsp-stat-card">
            <div class="wsp-stat-icon">💳</div>
            <div class="wsp-stat-content">
                <div class="wsp-stat-value"><?php echo number_format($customer->credits_balance); ?></div>
                <div class="wsp-stat-label">Crediti Disponibili</div>
            </div>
            <a href="<?php echo wc_get_account_endpoint_url('whatsapp-credits'); ?>" class="wsp-stat-action">Acquista Crediti</a>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-icon">📱</div>
            <div class="wsp-stat-content">
                <div class="wsp-stat-value"><?php echo number_format($stats['total_scans']); ?></div>
                <div class="wsp-stat-label">Scansioni QR Totali</div>
            </div>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-icon">💬</div>
            <div class="wsp-stat-content">
                <div class="wsp-stat-value"><?php echo number_format($stats['messages_sent']); ?></div>
                <div class="wsp-stat-label">Messaggi Inviati</div>
            </div>
        </div>
        
        <div class="wsp-stat-card">
            <div class="wsp-stat-icon">👥</div>
            <div class="wsp-stat-content">
                <div class="wsp-stat-value"><?php echo number_format($stats['unique_contacts']); ?></div>
                <div class="wsp-stat-label">Contatti Unici</div>
            </div>
        </div>
    </div>
    
    <!-- API Info -->
    <div class="wsp-api-section">
        <h2>🔐 Credenziali API</h2>
        <div class="wsp-api-box">
            <div class="wsp-api-field">
                <label>API Key:</label>
                <div class="wsp-api-value">
                    <code id="api-key"><?php echo esc_html($customer->api_key); ?></code>
                    <button onclick="copyToClipboard('api-key')" class="wsp-btn-copy">📋 Copia</button>
                </div>
            </div>
            <div class="wsp-api-field">
                <label>Endpoint:</label>
                <div class="wsp-api-value">
                    <code><?php echo rest_url('wsp/v1/'); ?></code>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="wsp-activity-grid">
        <!-- Recent Scans -->
        <div class="wsp-activity-section">
            <h2>📱 Ultime Scansioni QR</h2>
            <div class="wsp-table-wrapper">
                <table class="wsp-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Numero</th>
                            <th>Nome</th>
                            <th>Campagna</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_scans as $scan): ?>
                        <tr>
                            <td><?php echo date('d/m H:i', strtotime($scan->created_at)); ?></td>
                            <td><?php echo esc_html('+' . $scan->sender_number); ?></td>
                            <td><?php echo esc_html($scan->sender_name ?: 'N/D'); ?></td>
                            <td><?php echo esc_html($scan->campaign_name ?: 'Diretta'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Messages -->
        <div class="wsp-activity-section">
            <h2>💬 Ultimi Messaggi</h2>
            <div class="wsp-table-wrapper">
                <table class="wsp-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Destinatario</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th>Crediti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_messages as $msg): ?>
                        <tr>
                            <td><?php echo date('d/m H:i', strtotime($msg->created_at)); ?></td>
                            <td><?php echo esc_html('+' . $msg->recipient_number); ?></td>
                            <td><?php echo esc_html($msg->message_type); ?></td>
                            <td>
                                <span class="wsp-status wsp-status-<?php echo $msg->status; ?>">
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
    </div>
    
    <!-- Quick Actions -->
    <div class="wsp-actions">
        <a href="<?php echo wc_get_account_endpoint_url('whatsapp-credits'); ?>" class="wsp-btn wsp-btn-primary">
            💳 Acquista Crediti
        </a>
        <a href="<?php echo wc_get_account_endpoint_url('whatsapp-messages'); ?>" class="wsp-btn">
            📤 Invia Messaggio
        </a>
        <a href="<?php echo wc_get_account_endpoint_url('whatsapp-settings'); ?>" class="wsp-btn">
            ⚙️ Impostazioni
        </a>
    </div>
</div>

<style>
.wsp-dashboard {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.wsp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.wsp-header h1 {
    margin: 0;
    font-size: 28px;
    color: #333;
}

.wsp-user-info {
    text-align: right;
}

.wsp-user-name {
    display: block;
    font-weight: 600;
    color: #333;
}

.wsp-user-number {
    color: #666;
    font-size: 14px;
}

.wsp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.wsp-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.wsp-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.wsp-stat-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
}

.wsp-stat-content {
    flex: 1;
}

.wsp-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    line-height: 1;
}

.wsp-stat-label {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.wsp-stat-action {
    position: absolute;
    bottom: 20px;
    right: 20px;
    font-size: 12px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.wsp-api-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.wsp-api-section h2 {
    margin-top: 0;
    color: #333;
}

.wsp-api-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.wsp-api-field {
    margin-bottom: 15px;
}

.wsp-api-field:last-child {
    margin-bottom: 0;
}

.wsp-api-field label {
    display: block;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
}

.wsp-api-value {
    display: flex;
    align-items: center;
    gap: 10px;
}

.wsp-api-value code {
    flex: 1;
    padding: 8px 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
}

.wsp-btn-copy {
    padding: 6px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.2s;
}

.wsp-btn-copy:hover {
    background: #5a67d8;
}

.wsp-activity-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .wsp-activity-grid {
        grid-template-columns: 1fr;
    }
}

.wsp-activity-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.wsp-activity-section h2 {
    margin-top: 0;
    color: #333;
    font-size: 20px;
}

.wsp-table-wrapper {
    overflow-x: auto;
}

.wsp-table {
    width: 100%;
    border-collapse: collapse;
}

.wsp-table th {
    text-align: left;
    padding: 10px;
    border-bottom: 2px solid #f0f0f0;
    color: #666;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
}

.wsp-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f8f9fa;
    font-size: 14px;
}

.wsp-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.wsp-status-sent {
    background: #d4edda;
    color: #155724;
}

.wsp-status-pending {
    background: #fff3cd;
    color: #856404;
}

.wsp-status-failed {
    background: #f8d7da;
    color: #721c24;
}

.wsp-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.wsp-btn {
    display: inline-block;
    padding: 12px 24px;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    border: 2px solid #e0e0e0;
    transition: all 0.2s;
}

.wsp-btn:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
}

.wsp-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.wsp-btn-primary:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b4199 100%);
}
</style>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        // Feedback visivo
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✅ Copiato!';
        button.style.background = '#48bb78';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
    });
}
</script>
