<?php
// Auto-generated stub file
if (!defined('ABSPATH')) exit;


class WSP_Database {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabella clienti
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_customers (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) UNSIGNED NOT NULL UNIQUE,
            whatsapp_number varchar(20),
            whatsapp_name varchar(255),
            credits_balance int DEFAULT 0,
            api_key varchar(64) UNIQUE,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Tabella messaggi
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_messages (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED,
            recipient_number varchar(20),
            message_content longtext,
            credits_used int DEFAULT 1,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        
        dbDelta($sql2);
        
        // Tabella numeri estratti
        $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_extracted_numbers (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED,
            sender_number varchar(20),
            sender_name varchar(255),
            campaign_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_number_date (customer_id, sender_number, campaign_date)
        ) $charset_collate;";
        
        dbDelta($sql3);
        
        // Tabella transazioni crediti
        $sql4 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_credit_transactions (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED,
            transaction_type varchar(50),
            amount int,
            balance_after int,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        
        dbDelta($sql4);
    }
}