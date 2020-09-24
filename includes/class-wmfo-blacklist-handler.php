<?php
/**
 *
 *Handler class to update the blacklisted settings
 *Show the message in checkout page
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WMFO_Blacklist_Handler')) {
    /**
     * Class WMFO_Blacklist_Handler
     */
    class WMFO_Blacklist_Handler {
        /**
         * @param $key
         * @param string $default
         * @return mixed|string|void
         */
        public static function get_setting($key, $default = '') {
            return get_option($key) ? get_option($key) : $default;
        }

        /**
         * @return array
         */
        public static function get_blacklists() {
            return array(
                'prev_black_list_ips' => self::get_setting('wmfo_black_list_ips'),
                'prev_wmfo_black_list_names' => self::get_setting('wmfo_black_list_names'),
                'prev_black_list_phones' => self::get_setting('wmfo_black_list_phones'),
                'prev_black_list_emails' => self::get_setting('wmfo_black_list_emails'),
            );

        }

        /**
         * @param $key
         * @param $pre_values
         * @param $to_add
         * @param string $action
         */
        public static function update_blacklist($key, $pre_values, $to_add, $action = 'add') {
            if ('add' == $action) {
                if (false === $pre_values || '' == $pre_values) {
                    $new_values = $to_add;
                } else {

                    $new_values = !in_array($to_add, explode(PHP_EOL, $pre_values)) ? $pre_values . PHP_EOL . $to_add : $pre_values;
                }
            } elseif ('remove' == $action) {
                $in_array_value = explode(PHP_EOL, $pre_values);
                if (in_array($to_add, $in_array_value)) {
                    $array_key = array_search($to_add, $in_array_value);
                    if (false !== $array_key) {
                        unset($in_array_value[$array_key]);
                    }
                }
                $new_values = implode(PHP_EOL, $in_array_value);
            }

            update_option($key, trim($new_values));
        }

        /**
         * @param array $customer
         * @param null $order
         * @param string $action
         * @return bool
         */
        public static function init($customer = array(), $order = null, $action = 'add') {
            $prev_blacklisted_data = self::get_blacklists();
            if (empty($customer) || !$customer) {
                return false;
            }

            self::update_blacklist('wmfo_black_list_names', $prev_blacklisted_data['prev_wmfo_black_list_names'], $customer['full_name'], $action);
            self::update_blacklist('wmfo_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address'], $action);
            self::update_blacklist('wmfo_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone'], $action);
            self::update_blacklist('wmfo_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email'], $action);

            //handle the cancellation of order
            if (null !== $order) {
                self::cancel_order($order);
            }

            return true;
        }

        /**
         * @param $order
         */
        public static function cancel_order($order) {

            $order_note = apply_filters('wmfo_cancel_order_note', esc_html__('Order details blacklisted for future checkout.', 'woo-manage-fraud-orders'), $order);

            //Set the order status to Cancelled
            if (!$order->has_status('cancelled')) {
                $order->update_status('cancelled', $order_note);
            }
        }

        /**
         * Show the blocked message to the customer
         */
        public static function show_blocked_message() {
            $default_notice = esc_html__('Sorry, You are blocked from checking out.', 'woo-manage-fraud-orders');
            $wmfo_black_list_message = self::get_setting('wmfo_black_list_message', $default_notice);

            //with some reason, get_option with default value not working

            if (!wc_has_notice($wmfo_black_list_message)) {
                wc_add_notice($wmfo_black_list_message, 'error');
            }
        }

        /**
         * @param $customer_details
         * @return bool
         */
        public static function is_blacklisted($customer_details) {
            //Check for ony by one, return TRUE as soon as first matching
            $allow_blacklist_by_name = get_option('wmfo_allow_blacklist_by_name', 'no');
            $blacklisted_customer_names = self::get_setting('wmfo_black_list_names');
            $blacklisted_ips = self::get_setting('wmfo_black_list_ips');
            $blacklisted_emails = self::get_setting('wmfo_black_list_emails');
            $blacklisted_phones = self::get_setting('wmfo_black_list_phones');

            if ('yes' == $allow_blacklist_by_name && in_array($customer_details['full_name'], array_map('trim', explode(PHP_EOL, $blacklisted_customer_names)))) {
                return true;
            } elseif (in_array($customer_details['ip_address'], array_map('trim', explode(PHP_EOL, $blacklisted_ips)))) {
                return true;
            } elseif (in_array($customer_details['billing_email'], array_map('trim', explode(PHP_EOL, $blacklisted_emails)))) {
                return true;
            } elseif (in_array($customer_details['billing_phone'], array_map('trim', explode(PHP_EOL, $blacklisted_phones)))) {
                return true;
            }

            return false;
        }
    }
}
