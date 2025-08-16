<?php
class WSP_WooCommerce {
    
    public static function init() {
        // Crea prodotti virtuali per crediti
        add_action('init', [__CLASS__, 'create_credit_products']);
        
        // Hook ordine completato
        add_action('woocommerce_order_status_completed', [__CLASS__, 'process_credit_purchase']);
        
        // Aggiungi campo WhatsApp al checkout
        add_action('woocommerce_after_order_notes', [__CLASS__, 'add_whatsapp_field']);
        add_action('woocommerce_checkout_process', [__CLASS__, 'validate_whatsapp_field']);
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_whatsapp_field']);
        
        // Tab account cliente
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_account_menu_items']);
        add_action('init', [__CLASS__, 'add_endpoints']);
    }
    
    /**
     * Crea prodotti crediti
     */
    public static function create_credit_products() {
        // Verifica se esistono già
        if (get_option('wsp_credit_products_created')) {
            return;
        }
        
        $products = [
            [
                'name' => '10 Crediti WhatsApp',
                'price' => 2.99,
                'credits' => 10,
                'sku' => 'wsp-credits-10'
            ],
            [
                'name' => '50 Crediti WhatsApp',
                'price' => 9.99,
                'credits' => 50,
                'sku' => 'wsp-credits-50'
            ],
            [
                'name' => '100 Crediti WhatsApp',
                'price' => 17.99,
                'credits' => 100,
                'sku' => 'wsp-credits-100'
            ],
            [
                'name' => '500 Crediti WhatsApp',
                'price' => 79.99,
                'credits' => 500,
                'sku' => 'wsp-credits-500'
            ]
        ];
        
        foreach ($products as $product_data) {
            $product = new WC_Product_Simple();
            $product->set_name($product_data['name']);
            $product->set_regular_price($product_data['price']);
            $product->set_sku($product_data['sku']);
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_stock_status('instock');
            $product->set_catalog_visibility('visible');
            
            // Meta data per crediti
            $product->update_meta_data('_wsp_credits', $product_data['credits']);
            
            $product->save();
        }
        
        update_option('wsp_credit_products_created', true);
    }
    
    /**
     * Processa acquisto crediti
     */
    public static function process_credit_purchase($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Ottieni o crea customer
        $customer_id = self::get_or_create_customer($user_id);
        
        $total_credits = 0;
        
        // Calcola crediti totali dall'ordine
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $credits = $product->get_meta('_wsp_credits');
            
            if ($credits) {
                $total_credits += $credits * $item->get_quantity();
            }
        }
        
        if ($total_credits > 0) {
            // Aggiungi crediti
            WSP_Credits::add_credits(
                $customer_id,
                $total_credits,
                sprintf('Acquisto crediti - Ordine #%d', $order_id),
                $order_id,
                'purchase'
            );
            
            // Aggiungi nota all'ordine
            $order->add_order_note(
                sprintf('%d crediti WhatsApp aggiunti al cliente', $total_credits)
            );
            
            // Invia email conferma
            self::send_credit_confirmation_email($user_id, $total_credits, $order_id);
        }
    }
    
    /**
     * Ottieni o crea customer
     */
    private static function get_or_create_customer($user_id) {
        global $wpdb;
        
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wsp_customers WHERE user_id = %d",
            $user_id
        ));
        
        if ($customer) {
            return $customer->id;
        }
        
        // Crea nuovo customer
        $user = get_user_by('id', $user_id);
        $whatsapp = get_user_meta($user_id, 'whatsapp_number', true);
        
        $wpdb->insert(
            $wpdb->prefix . 'wsp_customers',
            [
                'user_id' => $user_id,
                'whatsapp_number' => $whatsapp ?: '',
                'whatsapp_name' => $user->display_name,
                'api_key' => wp_generate_password(32, false),
                'credits_balance' => 10, // Crediti di benvenuto
                'subscription_status' => 'free',
                'status' => 'active'
            ]
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Aggiungi campo WhatsApp al checkout
     */
    public static function add_whatsapp_field($checkout) {
        echo '<div id="whatsapp_field">';
        
        woocommerce_form_field('whatsapp_number', [
            'type' => 'tel',
            'class' => ['form-row-wide'],
            'label' => __('Numero WhatsApp', 'wsp'),
            'placeholder' => __('+39 3XX XXX XXXX', 'wsp'),
            'required' => false,
            'custom_attributes' => [
                'pattern' => '[+]?[0-9]{10,15}'
            ]
        ], $checkout->get_value('whatsapp_number'));
        
        echo '</div>';
    }
    
    /**
     * Valida campo WhatsApp
     */
    public static function validate_whatsapp_field() {
        if (!empty($_POST['whatsapp_number'])) {
            $number = WSP_API::normalize_italian_number($_POST['whatsapp_number']);
            
            if (!$number) {
                wc_add_notice(__('Numero WhatsApp non valido', 'wsp'), 'error');
            }
        }
    }
    
    /**
     * Salva campo WhatsApp
     */
    public static function save_whatsapp_field($order_id) {
        if (!empty($_POST['whatsapp_number'])) {
            $number = WSP_API::normalize_italian_number($_POST['whatsapp_number']);
            update_post_meta($order_id, '_whatsapp_number', $number);
            
            // Salva anche nel profilo utente
            $order = wc_get_order($order_id);
            if ($user_id = $order->get_user_id()) {
                update_user_meta($user_id, 'whatsapp_number', $number);
            }
        }
    }
}
