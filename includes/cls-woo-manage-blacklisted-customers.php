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
