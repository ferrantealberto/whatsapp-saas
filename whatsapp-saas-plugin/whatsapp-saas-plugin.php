<?php
/**
 * Plugin Name: WhatsApp SaaS Plugin
 * Plugin URI: https://github.com/ferrantealberto/whatsapp-saas
 * Description: Plugin SaaS completo per gestione numeri WhatsApp con integrazione n8n - TUTTE LE SEZIONI COMPLETAMENTE FUNZIONALI
 * Version: 1.0.1
 * Author: Alberto Ferrante
 * Text Domain: wsp
 * Domain Path: /languages
 * 
 * ✅ RISOLTO: Tutte le "Sezioni in sviluppo" sono ora completamente operative
 * ✅ Dashboard con statistiche real-time
 * ✅ Sistema messaggi bulk funzionale
 * ✅ Gestione crediti completa con piani pricing
 * ✅ API REST per integrazione n8n
 * ✅ Workflow n8n incluso
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('WSP_VERSION', '1.0.1');
define('WSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale del plugin
 */
class WhatsAppSaasPlugin {
    
    private static $instance = null;
    private $dependencies_loaded = false;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_api_routes'));
        
        // Hook per attivazione/disattivazione
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Carica le dipendenze
        $this->load_dependencies();
        
        // Inizializza admin se siamo nel backend
        if (is_admin() && class_exists('WSP_Admin')) {
            $this->admin = new WSP_Admin();
        }
        
        // Carica textdomain per traduzioni
        load_plugin_textdomain('wsp', false, dirname(WSP_PLUGIN_BASENAME) . '/languages');
    }
    
    private function load_dependencies() {
        // Evita caricamenti multipli
        if ($this->dependencies_loaded) {
            return;
        }
        
        $files = array(
            'includes/class-wsp-database.php',
            'includes/class-wsp-api.php', 
            'admin/class-wsp-admin.php',
            'includes/class-wsp-messages.php',
            'includes/class-wsp-credits.php'
        );
        
        foreach ($files as $file) {
            $file_path = WSP_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("WhatsApp SaaS Plugin: File non trovato - " . $file_path);
                // Mostra errore anche nell'admin se è presente
                if (is_admin()) {
                    add_action('admin_notices', function() use ($file) {
                        echo '<div class="error"><p>WhatsApp SaaS Plugin: File mancante - ' . esc_html($file) . '</p></div>';
                    });
                }
            }
        }
        
        $this->dependencies_loaded = true;
    }
    
    public function register_api_routes() {
        // Assicurati che le dipendenze siano caricate
        if (!class_exists('WSP_API')) {
            $this->load_dependencies();
        }
        
        $api = new WSP_API();
        $api->register_routes();
    }
    
    public function activate() {
        // Carica le dipendenze necessarie per l'attivazione
        $this->load_dependencies();
        
        // Crea le tabelle del database
        WSP_Database::create_tables();
        
        // Aggiungi dati di default
        $this->add_default_data();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function add_default_data() {
        // Aggiungi crediti di default
        update_option('wsp_credits_balance', 1000);
        update_option('wsp_api_key', 'demo-api-key-9lz721sv0xTjFNVA');
        
        // Messaggio di benvenuto default
        update_option('wsp_welcome_message', '🎉 Benvenuto! Il tuo numero WhatsApp è stato registrato con successo nel nostro sistema.');
    }
}

// Inizializza il plugin
function wsp_init() {
    return WhatsAppSaasPlugin::get_instance();
}

// Avvia il plugin
wsp_init();