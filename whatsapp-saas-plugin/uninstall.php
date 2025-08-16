<?php
/**
 * Uninstall script per WhatsApp SaaS Plugin
 * Eseguito quando il plugin viene disinstallato
 */

// Previeni accesso diretto
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Elimina tabelle database
global $wpdb;

$tables = array(
    $wpdb->prefix . 'wsp_whatsapp_numbers',
    $wpdb->prefix . 'wsp_messages', 
    $wpdb->prefix . 'wsp_activity_logs'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Elimina opzioni WordPress
$options = array(
    'wsp_credits_balance',
    'wsp_api_key',
    'wsp_mail2wa_api_key', 
    'wsp_welcome_message',
    'wsp_low_credits_threshold',
    'wsp_auto_recharge_enabled',
    'wsp_auto_recharge_threshold',
    'wsp_auto_recharge_plan'
);

foreach ($options as $option) {
    delete_option($option);
}

// Rimuovi scheduled events  
wp_clear_scheduled_hook('wsp_daily_credit_check');

// Log finale
error_log('WhatsApp SaaS Plugin: Disinstallazione completata');