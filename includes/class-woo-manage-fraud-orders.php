<?php
/**
 * Main class
 * Handles everything from here, includes  file for the backend settings and
 * blacklisting funcitonalities Inlcudes the frontend handlers as well
 */

if ( !defined('ABSPATH') ) {
    exit();
}

if ( !class_exists('Woo_Manage_Fraud_Orders') ) {
    class Woo_Manage_Fraud_Orders {

        public $version = '1.7.2';
        public static $_instance;

        public function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        /**
         * @return Woo_Manage_Fraud_Orders
         */
        public static function instance(): Woo_Manage_Fraud_Orders {
            if ( is_null(self::$_instance) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Define constants
         */
        private function define_constants() {
            $upload_dir = wp_upload_dir(null, false);

            $this->define('WMFO_ABSPATH', dirname(WMFO_PLUGIN_FILE) . '/');
            $this->define('WMFO_PLUGIN_BASENAME', plugin_basename(WMFO_PLUGIN_FILE));
            $this->define('WMFO_VERSION', $this->version);
            $this->define('WMFO_LOG_DIR', $upload_dir['basedir'] . '/wmfo-logs/');
        }

        /**
         * @param $name
         * @param $value
         */
        private function define( $name, $value ) {
            if ( !defined($name) ) {
                define($name, $value);
            }
        }

        /**
         * Init hooks
         */
        private function init_hooks() {
            register_activation_hook(WMFO_PLUGIN_FILE, array($this, 'install'));

            add_filter('plugin_action_links_' . plugin_basename(WMFO_PLUGIN_FILE), array(
                $this,
                'action_links',
            ), 99, 1);
            add_action('plugins_loaded', array($this, 'load_text_domain'));
        }

        /**
         * plugin dependency check
         */
        public function install() {
            if ( !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

                echo sprintf(esc_html__('Woo Manage Fraud Orders depends on %s to work!', 'woo-manage-fraud-orders'), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . esc_html__('WooCommerce', 'woo-manage-fraud-orders') . '</a>');
                @trigger_error('', E_USER_ERROR);

            }
        }

        /**
         * @param $actions
         * @return array
         */
        public static function action_links( $actions ): array {

            $new_actions = array(
                'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=settings_tab_blacklists') . '">' . __('Settings', 'woo-manage-fraud-orders') . '</a>',
            );

            return array_merge($new_actions, $actions);
        }

        /**
         * Load text domain for translation
         */
        public function load_text_domain() {
            load_plugin_textdomain(
                'woo-manage-fraud-orders',
                false,
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
            );
        }

        /**
         * Include needed files
         */
        public function includes() {
            require_once WMFO_ABSPATH . 'includes/wmfo-functions.php';
            require_once WMFO_ABSPATH . 'includes/class-wmfo-blacklist-handler.php';
            require_once WMFO_ABSPATH . 'includes/class-wmfo-track-fraud-attempts.php';
            if ( is_admin() ) {
                require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-settings.php';
                require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-order-metabox.php';
                require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-blacklist-action.php';
                require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-bulk-blacklist.php';
            }
        }

    }
}
