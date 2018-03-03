<?php
/**
 * Main class
 * Handles everything from here, includes  file for the backend settings and blacklisting funcitonalities
 * Inlcudes the frontend handlers as well
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('Woo_Manage_Fraud_Orders')) {
    class Woo_Manage_Fraud_Orders {
        public $version = '1.0.0';
        public static $_instance;

        public function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function define_constants() {
            $upload_dir = wp_upload_dir(null, false);

            $this->define('WMFO_ABSPATH', dirname(WMFO_PLUGIN_FILE) . '/');
            $this->define('WMFO_PLUGIN_BASENAME', plugin_basename(WMFO_PLUGIN_FILE));
            $this->define('WMFO_VERSION', $this->version);
            $this->define('WMFO_LOG_DIR', $upload_dir['basedir'] . '/wmfo-logs/');
        }
        private function define($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        private function init_hooks() {
            register_activation_hook(WMFO_PLUGIN_FILE, array($this, 'install'));
            
            add_filter( 'plugin_action_links_' . plugin_basename(WMFO_PLUGIN_FILE), array( $this, 'action_links') );
            add_action('plugins_loaded', array($this, 'load_text_domain'));
        }
        public static function install() {
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

                // Deactivate the plugin
                deactivate_plugins(__FILE__);

                // Throw an error in the wordpress admin console
                $error_message = __(sprintf('This plugin requires <a href="%s">WooCommerce plugins to be active!</a>', 'http://wordpress.org/extend/plugins/woocommerce/'), 'woo-manage-fraud-orders');
                die($error_message);

            }
        }

        public static function action_links($links){
            $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=settings_tab_blacklists') ) .'">Settings</a>';
            return $links;
        }

        public function load_text_domain() {
            load_plugin_textdomain('woo-preview-emails', WMFO_PLUGIN_FILE, plugin_basename(dirname(__FILE__)) . '/languages/');
        }
        public function includes() {
            include_once WMFO_ABSPATH . 'includes/wmfo-functions.php';
            include_once WMFO_ABSPATH . 'includes/class-wmfo-blacklist-handler.php';
            include_once WMFO_ABSPATH . 'includes/class-wmfo-track-fraud-attempts.php';
            if (is_admin()) {
                include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-settings.php';
                include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-blacklist-action.php';
            }
        }
    }
}
