<?php
class WSP_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Tabella clienti SaaS
        $sql_customers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_customers (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) UNSIGNED NOT NULL UNIQUE,
            whatsapp_number varchar(20) NOT NULL,
            whatsapp_name varchar(255),
            whatsapp_verified tinyint(1) DEFAULT 0,
            credits_balance int DEFAULT 0,
            credits_used int DEFAULT 0,
            credits_purchased int DEFAULT 0,
            subscription_status varchar(50) DEFAULT 'free',
            subscription_plan varchar(50),
            subscription_expires datetime,
            api_key varchar(64) UNIQUE,
            webhook_url varchar(500),
            settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_whatsapp (whatsapp_number),
            INDEX idx_status (status, subscription_status)
        ) $charset_collate;";
        
        // 2. Tabella numeri estratti (mittenti QR)
        $sql_extracted = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_extracted_numbers (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED NOT NULL,
            message_id varchar(255) UNIQUE,
            sender_number varchar(20) NOT NULL,
            sender_name varchar(255),
            sender_email varchar(255),
            extraction_method varchar(50),
            extraction_source varchar(100),
            qr_code_id varchar(100),
            campaign_id varchar(100),
            campaign_name varchar(255),
            campaign_date date,
            scan_location varchar(255),
            scan_timestamp datetime,
            is_unique tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_number_date (sender_number, campaign_date),
            INDEX idx_campaign (campaign_id),
            UNIQUE KEY unique_scan (customer_id, sender_number, campaign_date),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}wsp_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 3. Tabella messaggi inviati
        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_messages (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED NOT NULL,
            recipient_number varchar(20) NOT NULL,
            recipient_name varchar(255),
            message_type varchar(50) DEFAULT 'text',
            message_content longtext,
            message_template varchar(100),
            credits_used int DEFAULT 1,
            status varchar(50) DEFAULT 'pending',
            api_response longtext,
            sent_at datetime,
            delivered_at datetime,
            read_at datetime,
            failed_reason text,
            retry_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_recipient (recipient_number),
            INDEX idx_status (status),
            INDEX idx_sent (sent_at),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}wsp_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 4. Tabella transazioni crediti
        $sql_credits = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_credit_transactions (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount int NOT NULL,
            balance_before int DEFAULT 0,
            balance_after int DEFAULT 0,
            description text,
            reference_id varchar(100),
            reference_type varchar(50),
            wc_order_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_type (transaction_type),
            INDEX idx_order (wc_order_id),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}wsp_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 5. Tabella campagne QR
        $sql_campaigns = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_campaigns (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED NOT NULL,
            campaign_name varchar(255) NOT NULL,
            campaign_type varchar(50) DEFAULT 'qr_scan',
            qr_code_url text,
            qr_code_data text,
            landing_page_url text,
            welcome_message longtext,
            auto_reply tinyint(1) DEFAULT 1,
            credits_per_scan int DEFAULT 1,
            total_scans int DEFAULT 0,
            unique_scans int DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            starts_at datetime,
            expires_at datetime,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_status (status),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}wsp_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 6. Tabella piani abbonamento
        $sql_plans = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_subscription_plans (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plan_name varchar(100) NOT NULL UNIQUE,
            plan_type varchar(50) DEFAULT 'monthly',
            credits_included int DEFAULT 0,
            price decimal(10,2) DEFAULT 0,
            features longtext,
            limitations longtext,
            wc_product_id bigint(20) UNSIGNED,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_product (wc_product_id)
        ) $charset_collate;";
        
        // 7. Tabella log API
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wsp_api_logs (
            id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id bigint(20) UNSIGNED,
            endpoint varchar(255),
            method varchar(10),
            request_data longtext,
            response_data longtext,
            response_code int,
            ip_address varchar(45),
            user_agent text,
            execution_time float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_endpoint (endpoint),
            INDEX idx_created (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_customers);
        dbDelta($sql_extracted);
        dbDelta($sql_messages);
        dbDelta($sql_credits);
        dbDelta($sql_campaigns);
        dbDelta($sql_plans);
        dbDelta($sql_logs);
        
        // Inserisci piani di default
        self::insert_default_plans();
    }
    
    private static function insert_default_plans() {
        global $wpdb;
        
        $plans = [
            [
                'plan_name' => 'Free',
                'plan_type' => 'free',
                'credits_included' => 10,
                'price' => 0,
                'features' => json_encode([
                    '10 crediti mensili',
                    '1 campagna QR',
                    'Dashboard base',
                    'Support email'
                ])
            ],
            [
                'plan_name' => 'Starter',
                'plan_type' => 'monthly',
                'credits_included' => 100,
                'price' => 9.90,
                'features' => json_encode([
                    '100 crediti mensili',
                    '5 campagne QR',
                    'Dashboard avanzata',
                    'API access',
                    'Support prioritario'
                ])
            ],
            [
                'plan_name' => 'Professional',
                'plan_type' => 'monthly',
                'credits_included' => 500,
                'price' => 29.90,
                'features' => json_encode([
                    '500 crediti mensili',
                    'Campagne illimitate',
                    'Dashboard completa',
                    'API full access',
                    'Webhook',
                    'Support 24/7'
                ])
            ],
            [
                'plan_name' => 'Enterprise',
                'plan_type' => 'monthly',
                'credits_included' => 2000,
                'price' => 99.90,
                'features' => json_encode([
                    '2000 crediti mensili',
                    'Tutto illimitato',
                    'White label',
                    'API dedicata',
                    'Account manager',
                    'SLA garantito'
                ])
            ]
        ];
        
        foreach ($plans as $plan) {
            $wpdb->insert(
                $wpdb->prefix . 'wsp_subscription_plans',
                $plan,
                ['%s', '%s', '%d', '%f', '%s']
            );
        }
    }
}
