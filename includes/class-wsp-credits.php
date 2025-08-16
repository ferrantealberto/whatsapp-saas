<?php
// Auto-generated stub file
if (!defined('ABSPATH')) exit;


class WSP_Credits {
    public function __construct() {}
    
    public static function get_balance($customer_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT credits_balance FROM {$wpdb->prefix}wsp_customers WHERE id = %d",
            $customer_id
        ));
    }
    
    public static function add_credits($customer_id, $amount, $description = "") {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}wsp_customers SET credits_balance = credits_balance + %d WHERE id = %d",
            $amount, $customer_id
        ));
        return self::get_balance($customer_id);
    }
    
    public static function use_credits($customer_id, $amount, $description = "") {
        global $wpdb;
        $current = self::get_balance($customer_id);
        if ($current < $amount) {
            return new WP_Error("insufficient_credits", "Crediti insufficienti");
        }
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}wsp_customers SET credits_balance = credits_balance - %d WHERE id = %d",
            $amount, $customer_id
        ));
        return self::get_balance($customer_id);
    }
    
    public static function calculate_message_cost($type = "text", $count = 1) {
        return 1 * $count;
    }
}