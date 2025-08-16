<?php
/**
 * Gestione Database per WhatsApp SaaS Plugin
 * COMPLETAMENTE FUNZIONALE - Database ottimizzato con deduplicazione
 */

if (!defined('ABSPATH')) {
    exit;
}

class WSP_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabella per i numeri WhatsApp estratti
        $table_numbers = $wpdb->prefix . 'wsp_whatsapp_numbers';
        $sql_numbers = "CREATE TABLE $table_numbers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            message_id varchar(255) DEFAULT '',
            thread_id varchar(255) DEFAULT '',
            sender_number varchar(20) NOT NULL,
            sender_name varchar(255) DEFAULT '',
            sender_formatted varchar(25) DEFAULT '',
            sender_email varchar(255) DEFAULT '',
            extraction_method varchar(50) DEFAULT '',
            raw_match varchar(255) DEFAULT '',
            context_match text,
            email_date datetime DEFAULT '0000-00-00 00:00:00',
            processed_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_date varchar(20) DEFAULT '',
            processed_time varchar(20) DEFAULT '',
            subject varchar(255) DEFAULT '',
            snippet text,
            is_new_sender tinyint(1) DEFAULT 1,
            has_recipient tinyint(1) DEFAULT 0,
            campaign_date date DEFAULT NULL,
            unique_visitor_id varchar(100) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_daily (sender_number, campaign_date),
            KEY idx_sender_number (sender_number),
            KEY idx_campaign_date (campaign_date),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // Tabella per i messaggi inviati
        $table_messages = $wpdb->prefix . 'wsp_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            whatsapp_number_id mediumint(9) NOT NULL,
            recipient_number varchar(20) NOT NULL,
            message_content text NOT NULL,
            message_type varchar(20) DEFAULT 'welcome',
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            delivery_status varchar(20) DEFAULT 'pending',
            api_response text,
            credits_used int(5) DEFAULT 1,
            campaign_id varchar(50) DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_recipient (recipient_number),
            KEY idx_sent_at (sent_at),
            KEY idx_status (delivery_status)
        ) $charset_collate;";
        
        // Tabella per il log delle operazioni
        $table_logs = $wpdb->prefix . 'wsp_activity_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            description text,
            data text,
            user_id int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT '',
            user_agent text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_action (action),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_numbers);
        dbDelta($sql_messages);
        dbDelta($sql_logs);
        
        // Log dell'installazione
        self::log_activity('plugin_install', 'Plugin WhatsApp SaaS installato con successo');
    }
    
    public static function save_whatsapp_numbers($numbers_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wsp_whatsapp_numbers';
        $saved_count = 0;
        $errors = array();
        
        foreach ($numbers_data as $number_data) {
            $data = array(
                'message_id' => sanitize_text_field($number_data['messageId'] ?? ''),
                'thread_id' => sanitize_text_field($number_data['threadId'] ?? ''),
                'sender_number' => sanitize_text_field($number_data['senderNumber'] ?? ''),
                'sender_name' => sanitize_text_field($number_data['senderName'] ?? ''),
                'sender_formatted' => sanitize_text_field($number_data['senderFormatted'] ?? ''),
                'sender_email' => sanitize_email($number_data['senderEmail'] ?? ''),
                'extraction_method' => sanitize_text_field($number_data['extractionMethod'] ?? ''),
                'raw_match' => sanitize_text_field($number_data['rawMatch'] ?? ''),
                'context_match' => sanitize_textarea_field($number_data['contextMatch'] ?? ''),
                'email_date' => sanitize_text_field($number_data['emailDate'] ?? ''),
                'processed_date' => sanitize_text_field($number_data['processedDate'] ?? ''),
                'processed_time' => sanitize_text_field($number_data['processedTime'] ?? ''),
                'subject' => sanitize_text_field($number_data['subject'] ?? ''),
                'snippet' => sanitize_textarea_field($number_data['snippet'] ?? ''),
                'is_new_sender' => isset($number_data['isNewSender']) ? 1 : 0,
                'has_recipient' => isset($number_data['hasRecipient']) ? 1 : 0,
                'campaign_date' => date('Y-m-d'),
                'unique_visitor_id' => sanitize_text_field($number_data['uniqueVisitorId'] ?? ''),
                'status' => 'active'
            );
            
            $result = $wpdb->insert($table_name, $data);
            
            if ($result !== false) {
                $saved_count++;
                self::log_activity('number_added', 
                    sprintf('Nuovo numero WhatsApp aggiunto: %s', $data['sender_number']),
                    $data
                );
            } else {
                $errors[] = $wpdb->last_error;
            }
        }
        
        return array(
            'saved' => $saved_count,
            'errors' => $errors,
            'total' => count($numbers_data)
        );
    }
    
    public static function get_whatsapp_numbers($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wsp_whatsapp_numbers';
        $where_conditions = array('status = %s');
        $where_values = array('active');
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(sender_number LIKE %s OR sender_name LIKE %s OR sender_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($limit, $offset))
        );
        
        return $wpdb->get_results($query);
    }
    
    public static function get_statistics() {
        global $wpdb;
        
        $table_numbers = $wpdb->prefix . 'wsp_whatsapp_numbers';
        $table_messages = $wpdb->prefix . 'wsp_messages';
        
        $stats = array();
        
        // Totale numeri
        $stats['total_numbers'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_numbers WHERE status = 'active'");
        
        // Numeri oggi
        $stats['numbers_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_numbers WHERE status = 'active' AND DATE(created_at) = %s",
            date('Y-m-d')
        ));
        
        // Messaggi inviati
        $stats['total_messages'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_messages");
        
        return $stats;
    }
    
    public static function log_activity($action, $description, $data = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wsp_activity_logs';
        
        $log_data = array(
            'action' => sanitize_text_field($action),
            'description' => sanitize_text_field($description),
            'data' => $data ? wp_json_encode($data) : null,
            'user_id' => get_current_user_id(),
            'ip_address' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
        
        $wpdb->insert($table_name, $log_data);
    }
    
    private static function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return sanitize_text_field(trim($ip));
            }
        }
        
        return '127.0.0.1';
    }
}