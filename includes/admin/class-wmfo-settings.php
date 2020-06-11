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
            add_filter('woocommerce_admin_settings_sanitize_option', __CLASS__ . '::update_setting_filter', 100, 3);
        }

        /**
         * Add a new settings tab to the WooCommerce settings tabs array.hp
         *
         * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
         *
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

        public static function update_setting_filter($value, $option, $raw_value) {
            if (in_array($option['id'], [
                'wmfo_black_list_names',
                'wmfo_black_list_phones',
                'wmfo_black_list_emails',
                'wmfo_black_list_ips',
            ])) {
                //check if there are  duplication of blacklisted values
                $value = implode(PHP_EOL, array_unique(array_map('trim', explode(PHP_EOL, $value))));
            }

            return apply_filters($option['id'] . '_option', $value, $option, $raw_value);
        }

        /**
         * Get all the settings for this plugin for @return array Array of settings for @see woocommerce_admin_fields() function.
         * @see woocommerce_admin_fields() function.
         *
         */
        public static function get_settings() {
            $settings = [
                'section_title' => [
                    'name' => esc_html__('Blacklisted Customers', 'woo-manage-fraud-orders'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wmfo_settings_title',
                ],
                'blacklists_message' => [
                    'name' => esc_html__('Blacklists Notice Message', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:50px',
                    'type' => 'textarea',
                    'default' => 'Sorry, You are being restricted from placing order.',
                    'desc' => esc_html__('Enter the message to be shown for blocked customers', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_message',
                ],
                'blacklists_allowed_fraud_attempts' => [
                    'name' => esc_html__('Number of allowed Fraud Attempts', 'woo-manage-fraud-orders'),
                    'type' => 'number',
                    'css' => 'width:50px',
                    'default' => 5,
                    'desc' => esc_html__('Enter the number of allowed fraud attempts before blocking automatically', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_allowed_fraud_attempts',
                    'custom_attributes' => [
                        'min' => 0,
                        'step' => 1,
                    ],
                ],
                'blacklists_order_status' => [
                    'name' => esc_html__('Blacklisted Order Statuses', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:auto',
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select',
                    'desc' => esc_html__('Select multiple order statuses. If customer has previous order in one of above selected order items, He/She will not be able to place order.', 'woo-manage-fraud-orders'),
                    'options' => wc_get_order_statuses(),
                    'id' => 'wmfo_black_list_order_status',
                ],
                'allow_blacklist_by_name' => [
                    'name' => esc_html__('Allow blacklist by Name ?', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:200px',
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc' => esc_html__('Check this to blacklist customer by their name. ', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_allow_blacklist_by_name',
                ],
                'blacklists_names' => [
                    'name' => __('Blacklisted Names', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:200px',
                    'type' => 'textarea',
                    'desc' => esc_html__('Enter combined first name and last name in new line. Eg. "John Doe"', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_names',
                ],
                'blacklists_phones' => [
                    'name' => esc_html__('Blacklisted Phones', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:200px',
                    'type' => 'textarea',
                    'desc' => esc_html__('Enter Phones in new line', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_phones',
                ],
                'blacklists_emails' => [
                    'name' => esc_html__('Blacklisted Emails', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:200px',
                    'type' => 'textarea',
                    'desc' => esc_html__('Enter Emails in new line', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_emails',
                ],
                'blacklists_ips' => [
                    'name' => esc_html__('Blacklisted IP Addresses', 'woo-manage-fraud-orders'),
                    'css' => 'width:600px;height:200px',
                    'type' => 'textarea',
                    'desc' => esc_html__('Enter IPs in new line', 'woo-manage-fraud-orders'),
                    'id' => 'wmfo_black_list_ips',
                ],
                'section_end' => [
                    'type' => 'sectionend',
                    'id' => 'wmfo_settings_section_end',
                ],
            ];

            return apply_filters('wmfo_settings', $settings);
        }
    }
}
//init the Setting
WMFO_Settings_Tab::init();

/**
 * Admin Styling
 */
add_action('admin_head', function () {
    if (isset($_GET['tab']) && $_GET['tab'] == 'settings_tab_blacklists') : ?>
        <style>
            .wrap.woocommerce .forminp.forminp-multiselect span.description {
                display: block;
                padding: 10px 0 0;
            }
        </style>
    <?php
    endif;
});
