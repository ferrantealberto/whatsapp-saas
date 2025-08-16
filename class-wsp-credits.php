<?php
class WSP_Credits {
    
    /**
     * Ottieni bilancio crediti cliente
     */
    public static function get_balance($customer_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT credits_balance FROM {$wpdb->prefix}wsp_customers WHERE id = %d",
            $customer_id
        ));
    }
    
    /**
     * Aggiungi crediti
     */
    public static function add_credits($customer_id, $amount, $description = '', $reference_id = null, $reference_type = 'manual') {
        global $wpdb;
        
        // Ottieni bilancio attuale
        $current_balance = self::get_balance($customer_id);
        $new_balance = $current_balance + $amount;
        
        // Inizia transazione
        $wpdb->query('START TRANSACTION');
        
        try {
            // Aggiorna bilancio
            $updated = $wpdb->update(
                $wpdb->prefix . 'wsp_customers',
                [
                    'credits_balance' => $new_balance,
                    'credits_purchased' => ['credits_purchased + %d', $amount]
                ],
                ['id' => $customer_id],
                ['%d', '%d'],
                ['%d']
            );
            
            if (!$updated) {
                throw new Exception('Failed to update balance');
            }
            
            // Registra transazione
            $wpdb->insert(
                $wpdb->prefix . 'wsp_credit_transactions',
                [
                    'customer_id' => $customer_id,
                    'transaction_type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $current_balance,
                    'balance_after' => $new_balance,
                    'description' => $description,
                    'reference_id' => $reference_id,
                    'reference_type' => $reference_type
                ]
            );
            
            $wpdb->query('COMMIT');
            
            // Trigger evento
            do_action('wsp_credits_added', $customer_id, $amount, $new_balance);
            
            return $new_balance;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('WSP Credits Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Usa crediti
     */
    public static function use_credits($customer_id, $amount, $description = '', $reference_id = null, $reference_type = 'message') {
        global $wpdb;
        
        $current_balance = self::get_balance($customer_id);
        
        // Verifica disponibilità
        if ($current_balance < $amount) {
            return new WP_Error('insufficient_credits', 'Crediti insufficienti');
        }
        
        $new_balance = $current_balance - $amount;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Aggiorna bilancio
            $updated = $wpdb->update(
                $wpdb->prefix . 'wsp_customers',
                [
                    'credits_balance' => $new_balance,
                    'credits_used' => ['credits_used + %d', $amount]
                ],
                ['id' => $customer_id],
                ['%d', '%d'],
                ['%d']
            );
            
            if (!$updated) {
                throw new Exception('Failed to update balance');
            }
            
            // Registra transazione
            $wpdb->insert(
                $wpdb->prefix . 'wsp_credit_transactions',
                [
                    'customer_id' => $customer_id,
                    'transaction_type' => 'debit',
                    'amount' => -$amount,
                    'balance_before' => $current_balance,
                    'balance_after' => $new_balance,
                    'description' => $description,
                    'reference_id' => $reference_id,
                    'reference_type' => $reference_type
                ]
            );
            
            $wpdb->query('COMMIT');
            
            // Trigger evento
            do_action('wsp_credits_used', $customer_id, $amount, $new_balance);
            
            // Notifica se crediti bassi
            if ($new_balance < 10) {
                do_action('wsp_low_credits_warning', $customer_id, $new_balance);
            }
            
            return $new_balance;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('WSP Credits Error: ' . $e->getMessage());
            return new WP_Error('credit_error', $e->getMessage());
        }
    }
    
    /**
     * Ottieni storico transazioni
     */
    public static function get_transactions($customer_id, $limit = 50, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsp_credit_transactions 
            WHERE customer_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $customer_id, $limit, $offset
        ));
    }
    
    /**
     * Calcola crediti necessari per messaggio
     */
    public static function calculate_message_cost($message_type = 'text', $recipient_count = 1) {
        $costs = [
            'text' => 1,
            'image' => 2,
            'video' => 3,
            'document' => 2,
            'audio' => 2,
            'location' => 1,
            'template' => 1
        ];
        
        $base_cost = $costs[$message_type] ?? 1;
        $total_cost = $base_cost * $recipient_count;
        
        return apply_filters('wsp_message_cost', $total_cost, $message_type, $recipient_count);
    }
}
