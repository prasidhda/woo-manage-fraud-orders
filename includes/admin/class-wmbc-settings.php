<?php
/*----------  Black List Settings  ----------*/
if (!class_exists('WMBC_Settings_Tab')) {
    class WMBC_Settings_Tab {
        /**
         * Bootstraps the class and hooks required actions & filters.
         *
         */
        public static function init() {
            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
            add_action('woocommerce_settings_tabs_settings_tab_blacklists', __CLASS__ . '::settings_tab');
            add_action('woocommerce_update_options_settings_tab_blacklists', __CLASS__ . '::update_settings');
        }

        /**
         * Add a new settings tab to the WooCommerce settings tabs array.
         *
         * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
         * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
         */
        public static function add_settings_tab($settings_tabs) {
            $settings_tabs['settings_tab_blacklists'] = __('Blacklisted Customers', 'wmbc-settings');
            return $settings_tabs;
        }
        /**
         * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
         *
         * @uses woocommerce_admin_fields()
         * @uses self::get_settings()
         */
        public static function settings_tab() {
            woocommerce_admin_fields(self::get_settings());
        }
        /**
         * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
         *
         * @uses woocommerce_update_options()
         * @uses self::get_settings()
         */
        public static function update_settings() {
            woocommerce_update_options(self::get_settings());
        }
        /**
         * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
         *
         * @return array Array of settings for @see woocommerce_admin_fields() function.
         */
        public static function get_settings() {
            $settings = array(
                'section_title'                     => array(
                    'name' => __('Blacklisted Customers', 'wmbc-settings'),
                    'type' => 'title',
                    'desc' => '',
                    'id'   => 'wmbc_settings_title',
                ),
                'blacklists_message'                => array(
                    'name' => __('Blacklists Notice Message:', 'wmbc-settings'),
                    'css'  => 'width:600px;height:50px',
                    'type' => 'textarea',
                    'desc' => __('Enter the message tp be shown for blocked customers', 'wmbc-settings'),
                    'id'   => 'wmbc_black_list_message',
                ),
                'blacklists_allowed_fraud_attempts' => array(
                    'name'              => __('Number of allowed Fraud Attempts:', 'wmbc-settings'),
                    'type'              => 'number',
                    'css'               => 'width:50px',
                    'desc'              => __('Enter the number of allowed fraud attempts before blocking automatically', 'wmbc-settings'),
                    'id'                => 'wmbc_black_list_allowed_fraud_attemps',
                    'custom_attributes' => array(
                        'min'  => 0,
                        'step' => 1,
                    ),
                ),
                'blacklists_phones'                 => array(
                    'name' => __('Blacklisted Phones:', 'wmbc-settings'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => __('Enter Phones with comma seperation', 'wmbc-settings'),
                    'id'   => 'wmbc_black_list_phones',
                ),
                'blacklists_emails'                 => array(
                    'name' => __('Blacklisted Emails:', 'wmbc-settings'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => __('Enter Emails with comma seperation', 'wmbc-settings'),
                    'id'   => 'wmbc_black_list_emails',
                ),
                'blacklists_ips'                    => array(
                    'name' => __('Blacklisted IP Addresses:', 'wmbc-settings'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => __('Enter IPs with comma seperation', 'wmbc-settings'),
                    'id'   => 'wmbc_black_list_ips',
                ),
                'section_end'                       => array(
                    'type' => 'sectionend',
                    'id'   => 'wmbc_settings_section_end',
                ),
            );
            return apply_filters('wmbc_settings', $settings);
        }
    }
    WMBC_Settings_Tab::init();
}
