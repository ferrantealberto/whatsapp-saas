<?php
/**
 * Plugin Name: WhatsApp SaaS Pro
 * Description: Sistema completo SaaS con crediti per messaggi WhatsApp
 * Version: 2.0.1
 * Author: Alby
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Costanti globali
define('WSP_VERSION', '2.0.1');
define('WSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSP_CREDIT_COST', 1);

/**
 * Classe Core principale
 */
class WSP_Core {
    
    private static $instance = null;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Carica dipendenze PRIMA degli hook
     */
    private function load_dependencies() {
        // Carica sempre il database per primo
        require_once WSP_PLUGIN_DIR . 'includes/class-wsp-database.php';
        
        $required_files = [
            'includes/class-wsp-credits.php',
            'includes/class-wsp-messages.php',
            'includes/class-wsp-api.php',
            'includes/class-wsp-admin.php',
            'includes/class-wsp-frontend.php',
            'includes/class-wsp-woocommerce.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = WSP_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                $this->create_stub_file($file);
                require_once $file_path;
            }
        }
    }
    
    /**
     * Crea file stub se mancanti
     */
    private function create_stub_file($file) {
        $file_path = WSP_PLUGIN_DIR . $file;
        $class_name = str_replace(['includes/class-', '.php', '-'], ['', '', '_'], $file);
        $class_name = strtoupper($class_name);
        
        $stub_content = "<?php\nif (!defined('ABSPATH')) exit;\n\n";
        
        switch (basename($file)) {
            case 'class-wsp-credits.php':
                $stub_content .= 'class WSP_Credits {
    public function __construct() {}
    public static function get_balance($customer_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(credits_balance, 0) FROM {$wpdb->prefix}wsp_customers WHERE id = %d",
            $customer_id
        ));
    }
    public static function add_credits($customer_id, $amount, $description = "") {
        global $wpdb;
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}wsp_customers SET credits_balance = credits_balance + %d WHERE id = %d",
            $amount, $customer_id
        ));
    }
    public static function use_credits($customer_id, $amount, $description = "") {
        return self::add_credits($customer_id, -$amount, $description);
    }
    public static function calculate_message_cost($type = "text", $count = 1) { return 1 * $count; }
}';
                break;
            case 'class-wsp-messages.php':
                $stub_content .= 'class WSP_Messages { public function __construct() {} }';
                break;
            case 'class-wsp-api.php':
                $stub_content .= 'class WSP_API { 
                    public static function register_routes() {}
                    public static function normalize_italian_number($input) {
                        $digits = preg_replace("/\D+/", "", $input);
                        if (substr($digits, 0, 4) === "0039") $digits = substr($digits, 4);
                        if (strlen($digits) === 10 && $digits[0] === "3") $digits = "39" . $digits;
                        return $digits;
                    }
                }';
                break;
            case 'class-wsp-admin.php':
                $stub_content .= 'class WSP_Admin { 
                    public function __construct() {}
                    public static function render_dashboard() { echo "<h1>Dashboard in caricamento...</h1>"; }
                    public static function render_customers() { echo "<h1>Clienti</h1>"; }
                    public static function render_messages() { echo "<h1>Messaggi</h1>"; }
                    public static function render_credits() { echo "<h1>Crediti</h1>"; }
                    public static function render_settings() { echo "<h1>Impostazioni</h1>"; }
                }';
                break;
            case 'class-wsp-frontend.php':
                $stub_content .= 'class WSP_Frontend { 
                    public function __construct() {}
                    public static function render_dashboard() {}
                    public static function get_customer_data($user_id) { return null; }
                    public static function get_customer_stats($customer_id) { return []; }
                    public static function get_recent_messages($customer_id, $limit = 10) { return []; }
                    public static function get_recent_scans($customer_id, $limit = 10) { return []; }
                }';
                break;
            case 'class-wsp-woocommerce.php':
                $stub_content .= 'class WSP_WooCommerce { 
                    public static function init() {}
                    public static function get_or_create_customer($user_id) { return 1; }
                }';
                break;
        }
        
        file_put_contents($file_path, $stub_content);
    }
    
    private function init_hooks() {
        // Hook di attivazione con dipendenze già caricate
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Init componenti
        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_api']);
        add_action('admin_menu', [$this, 'admin_menu']);
        
        // Inizializza componenti
        $this->init_components();
    }
    
    /**
     * Attivazione con controllo robusto
     */
    public function activate() {
        // Forza caricamento classe database
        if (!class_exists('WSP_Database')) {
            require_once WSP_PLUGIN_DIR . 'includes/class-wsp-database.php';
        }
        
        // Crea tabelle
        WSP_Database::create_tables();
        
        // Verifica creazione
        $verification = WSP_Database::verify_tables();
        if (!$verification['all_exist']) {
            error_log('WSP: Alcune tabelle non sono state create: ' . implode(', ', $verification['missing']));
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function init_components() {
        if (class_exists('WSP_Credits')) new WSP_Credits();
        if (class_exists('WSP_Messages')) new WSP_Messages();
        if (class_exists('WSP_API')) new WSP_API();
        if (class_exists('WSP_Admin')) new WSP_Admin();
        if (class_exists('WSP_Frontend')) new WSP_Frontend();
        if (class_exists('WooCommerce') && class_exists('WSP_WooCommerce')) {
            new WSP_WooCommerce();
        }
    }
    
    public function init() {
        add_rewrite_endpoint('whatsapp-dashboard', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('whatsapp-credits', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('whatsapp-messages', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('whatsapp-settings', EP_ROOT | EP_PAGES);
    }
    
    public function register_api() {
        if (class_exists('WSP_API')) {
            WSP_API::register_routes();
        }
    }
    
    public function admin_menu() {
        if (!class_exists('WSP_Admin')) return;
        
        add_menu_page(
            'WhatsApp SaaS',
            'WhatsApp SaaS',
            'manage_options',
            'wsp-dashboard',
            [WSP_Admin::class, 'render_dashboard'],
            'dashicons-whatsapp',
            25
        );
        
        add_submenu_page('wsp-dashboard', 'Clienti', 'Clienti', 'manage_options', 'wsp-customers', [WSP_Admin::class, 'render_customers']);
        add_submenu_page('wsp-dashboard', 'Messaggi', 'Messaggi', 'manage_options', 'wsp-messages', [WSP_Admin::class, 'render_messages']);
        add_submenu_page('wsp-dashboard', 'Crediti', 'Crediti', 'manage_options', 'wsp-credits', [WSP_Admin::class, 'render_credits']);
        add_submenu_page('wsp-dashboard', 'Impostazioni', 'Impostazioni', 'manage_options', 'wsp-settings', [WSP_Admin::class, 'render_settings']);
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Inizializzazione
add_action('plugins_loaded', function() {
    WSP_Core::get_instance();
});
