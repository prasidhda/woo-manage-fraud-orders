<?php
/**
 * Main class
 * Handles everything from here, includes  file for the backend settings and blacklisting funcitonalities
 * Inlcudes the frontend handlers as well
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('Woo_Manage_Blacklisted_Customers')) {
    class Woo_Manage_Blacklisted_Customers {
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

            $this->define('WMBC_ABSPATH', dirname(WMBC_PLUGIN_FILE) . '/');
            $this->define('WMBC_PLUGIN_BASENAME', plugin_basename(WMBC_PLUGIN_FILE));
            $this->define('WMBC_VERSION', $this->version);
            $this->define('WMBC_LOG_DIR', $upload_dir['basedir'] . '/wmbc-logs/');
        }
        private function define($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        private function init_hooks() {
            register_activation_hook(WMBC_PLUGIN_FILE, array($this, 'install'));
        }
        public static function install() {
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

                // Deactivate the plugin
                deactivate_plugins(__FILE__);

                // Throw an error in the wordpress admin console
                $error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'woocommerce');
                die($error_message);

            }
        }

        public function includes() {
            include_once WMBC_ABSPATH . 'includes/wmbc-functions.php';
            include_once WMBC_ABSPATH . 'includes/class-wmbc-blacklist-handler.php';
            include_once WMBC_ABSPATH . 'includes/class-wmbc-track-fraud-attempts.php';
            if (is_admin()) {
                include_once WMBC_ABSPATH . 'includes/admin/class-wmbc-settings.php';
                include_once WMBC_ABSPATH . 'includes/admin/class-wmbc-blacklist-action.php';
            }
        }
    }
}
