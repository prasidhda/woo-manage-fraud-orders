<?php
/**
 * Main class
 * Handles everything from here, includes  file for the backend settings and
 * blacklisting funcitonalities Inlcudes the frontend handlers as well
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('Woo_Manage_Fraud_Orders')) {
    class Woo_Manage_Fraud_Orders {

        public $version = '1.5.2';
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
            $upload_dir = wp_upload_dir(NULL, FALSE);

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
            register_activation_hook(WMFO_PLUGIN_FILE, [$this, 'install']);

            add_filter('plugin_action_links_' . plugin_basename(WMFO_PLUGIN_FILE), [
                $this,
                'action_links',
            ]);
            add_action('plugins_loaded', [$this, 'load_text_domain']);
            add_action('admin_init', array($this, 'db_update_handler'));
            add_action('admin_notices', array($this, 'admin_notices'));
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

        public static function action_links($links) {
            $links[] = '<a href="' . esc_url(get_admin_url(NULL, 'admin.php?page=wc-settings&tab=settings_tab_blacklists')) . '">Settings</a>';

            return $links;
        }

        public function load_text_domain() {
            load_plugin_textdomain(
                'woo-manage-fraud-orders',
                false,
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
            );
        }

        public function includes() {
            include_once WMFO_ABSPATH . 'includes/wmfo-functions.php';
            include_once WMFO_ABSPATH . 'includes/class-wmfo-blacklist-handler.php';
            include_once WMFO_ABSPATH . 'includes/class-wmfo-track-fraud-attempts.php';
            if (is_admin()) {
                include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-settings.php';
                include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-blacklist-action.php';
                require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-bulk-blacklist.php';
            }
        }

        public function admin_notices() {
            if (get_option('wmfo_db_version', null) != $this->version && !isset($_GET['wmfo_action'])) {
                $class = 'notice notice-error';
                $message = __('Please update the database to be compatible with latest version of Woo Manage Fraud Order plugin. ', 'woo-manage-fraud-orders');
                $message .= '<a href="' . add_query_arg('wmfo_action', 'update_db') . '">' . __('Update', 'woo-manage-fraud-orders') . '</a>';

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            } elseif (get_option('wmfo_db_version', null) == $this->version && get_user_meta(get_current_user_id(), 'wmfo_db_update_complete_notice_hide', true) != 'yes') {
                $class = 'notice notice-success updated woocommerce-message wc-connect woocommerce-message--success';
                $dismiss_link = '<a class="" style="float:right;" href="' . add_query_arg('wmfo_action', 'hide-update-notice') . '">' . __('Dismiss', 'woo-manage-fraud-orders') . '</a>';
                $message = '<p>' . __('Woo Manage Fraud Orders data update complete. Thank you for updating to the latest version!' . $dismiss_link, 'woo-manage-fraud-orders') . '</p>';
                printf('<div class="%1$s">%2$s</div>', esc_attr($class), $message);
            }

        }

        public static function db_update_handler() {
            //check for $_GET values
            if (isset($_GET['wmfo_action']) && $_GET['wmfo_action'] == 'update_db') {
                $wmfo_settings = array(
                    'wmfo_black_list_names',
                    'wmfo_black_list_ips',
                    'wmfo_black_list_phones',
                    'wmfo_black_list_emails',
                );
                foreach ($wmfo_settings as $key => $setting_key) {
                    $setting_value = WMFO_Blacklist_Handler::get_setting($setting_key);
                    $setting_value_array = explode(',', $setting_value);
                    if (count($setting_value_array) > 1) {
                        $new_setting_value = implode(PHP_EOL, array_map('trim', $setting_value_array));

                        update_option($setting_key, $new_setting_value);
                    }
                }

                update_option('wmfo_db_update', 'updated');
                update_option('wmfo_db_version', WMFO_VERSION);

                //Delete user meta so that we can show the DB update message
                update_user_meta(get_current_user_id(), 'wmfo_db_update_complete_notice_hide', 'no');
            }
            //Hide Update complete notice
            if (isset($_GET['wmfo_action']) && $_GET['wmfo_action'] == 'hide-update-notice') {
                update_user_meta(get_current_user_id(), 'wmfo_db_update_complete_notice_hide', 'yes');
            }
        }
    }
}
