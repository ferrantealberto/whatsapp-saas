<?php
// Auto-generated stub file
if (!defined('ABSPATH')) exit;


class WSP_WooCommerce {
    public static function init() {
        // Hook base WooCommerce
        add_action("woocommerce_order_status_completed", [__CLASS__, "process_credit_purchase"]);
    }
    
    public static function process_credit_purchase($order_id) {
        // Logica acquisto crediti
    }
}