<?php
/*----------  Black List Settings  ----------*/
if (!class_exists('WMFO_Settings_Tab')) {
    class WMFO_Settings_Tab {
        /**
         * Bootstraps the class and hooks required actions & filters.
         *
         */
        public static function init() {
            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
            add_action('woocommerce_settings_tabs_settings_tab_blacklists', __CLASS__ . '::settings_tab');
            add_action('woocommerce_update_options_settings_tab_blacklists', __CLASS__ . '::update_settings');
            add_filter( 'woocommerce_admin_settings_sanitize_option', __CLASS__ . '::update_setting_filter', 100, 3 );
        }

        /**
         * Add a new settings tab to the WooCommerce settings tabs array.
         *
         * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
         * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
         */
        public static function add_settings_tab($settings_tabs) {
            $settings_tabs['settings_tab_blacklists'] = esc_html__('Blacklisted Customers', 'woo-manage-fraud-orders');
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

        public static function update_setting_filter( $value, $option, $raw_value  ){
            if( in_array($option['id'], array('wmfo_black_list_phones', 'wmfo_black_list_emails', 'wmfo_black_list_ips')) ){
                //check if there are  duplication of blacklisted values 
                $value = implode(',', array_unique(array_map('trim',explode(',', $value)))); 
            }

            return apply_filters( $option['id']. '_option', $value, $option, $raw_value  ); 
        }
        /**
         * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
         *
         * @return array Array of settings for @see woocommerce_admin_fields() function.
         */
        public static function get_settings() {
            $settings = array(
                'section_title'                     => array(
                    'name' => esc_html__('Blacklisted Customers', 'woo-manage-fraud-orders'),
                    'type' => 'title',
                    'desc' => '',
                    'id'   => 'wmfo_settings_title',
                ),
                'blacklists_message'                => array(
                    'name' => esc_html__('Blacklists Notice Message:', 'woo-manage-fraud-orders'),
                    'css'  => 'width:600px;height:50px',
                    'type' => 'textarea',
                    'desc' => esc_html__('Enter the message tp be shown for blocked customers', 'woo-manage-fraud-orders'),
                    'id'   => 'wmfo_black_list_message',
                ),
                'blacklists_allowed_fraud_attempts' => array(
                    'name'              => esc_html__('Number of allowed Fraud Attempts:', 'woo-manage-fraud-orders'),
                    'type'              => 'number',
                    'css'               => 'width:50px',
                    'desc'              => esc_html__('Enter the number of allowed fraud attempts before blocking automatically', 'woo-manage-fraud-orders'),
                    'id'                => 'wmfo_black_list_allowed_fraud_attemps',
                    'custom_attributes' => array(
                        'min'  => 0,
                        'step' => 1,
                    ),
                ),
                'blacklists_phones'                 => array(
                    'name' => esc_html__('Blacklisted Phones:', 'woo-manage-fraud-orders'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => esc_html__('Enter Phones with comma seperation', 'woo-manage-fraud-orders'),
                    'id'   => 'wmfo_black_list_phones',
                ),
                'blacklists_emails'                 => array(
                    'name' => esc_html__('Blacklisted Emails:', 'woo-manage-fraud-orders'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => esc_html__('Enter Emails with comma seperation', 'woo-manage-fraud-orders'),
                    'id'   => 'wmfo_black_list_emails',
                ),
                'blacklists_ips'                    => array(
                    'name' => esc_html__('Blacklisted IP Addresses:', 'woo-manage-fraud-orders'),
                    'css'  => 'width:600px;height:200px',

                    'type' => 'textarea',
                    'desc' => esc_html__('Enter IPs with comma seperation', 'woo-manage-fraud-orders'),
                    'id'   => 'wmfo_black_list_ips',
                ),
                'section_end'                       => array(
                    'type' => 'sectionend',
                    'id'   => 'wmfo_settings_section_end',
                ),
            );
            return apply_filters('wmfo_settings', $settings);
        }
    }
}
//init the Setting
WMFO_Settings_Tab::init();
