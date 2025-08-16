<?php
class WSP_Frontend {
    public function __construct() {
        // Inizializzazione
    }
    
    public static function render_dashboard() {
        echo '<div class="wsp-dashboard"><h1>WhatsApp Dashboard</h1><p>Dashboard cliente in costruzione...</p></div>';
    }
    
    public static function get_customer_data($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_customers WHERE user_id = %d",
            $user_id
        ));
    }
    
    public static function get_customer_stats($customer_id) {
        return [
            'total_scans' => 0,
            'messages_sent' => 0,
            'unique_contacts' => 0
        ];
    }
    
    public static function get_recent_messages($customer_id, $limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_messages 
            WHERE customer_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $customer_id, $limit
        ));
    }
    
    public static function get_recent_scans($customer_id, $limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_extracted_numbers 
            WHERE customer_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $customer_id, $limit
        ));
    }
}