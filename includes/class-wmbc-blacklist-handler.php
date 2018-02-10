<?php
if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WMBC_Blacklist_Handler')) {
    class WMBC_Blacklist_Handler {

        private function get_setting($key, $default = '') {
            return get_option($key) ? get_option($key) : $default;
        }

        private function get_blacklists() {
            return array(
                'prev_black_list_ips'    => self::get_setting('wmbc_black_list_ips'),
                'prev_black_list_phones' => self::get_setting('wmbc_black_list_phones'),
                'prev_black_list_emails' => self::get_setting('wmbc_black_list_emails'),
            );

        }

        private function update_blacklist($key, $pre_values, $to_add) {
            if ($pre_values === false || $pre_values == '') {
                $new_values = $to_add;
            } else {
                $new_values = !substr_count($pre_values, $to_add) ? $pre_values . ', ' . $to_add : $pre_values;
            }
            update_option($key, $new_values);
        }
        public static function init($customer = array(), $order = null) {
            $prev_blacklisted_data = self::get_blacklists();
            if (empty($customer) || !$customer) {
                return false;
            }

            self::update_blacklist('wmbc_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address']);
            self::update_blacklist('wmbc_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone']);
            self::update_blacklist('wmbc_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email']);

            //handle the cancelation of order
            if (null !== $order) {
                self::cancel_order($order);
            }

            return true;
        }

        private function cancel_order($order) {
            $order_note = apply_filters('wmbc_cancel_order_note', __('Order details blacklisted for future checkout.', 'wmbc'), $order);

            //Set the order status to Canceled
            if (!$order->has_status('cancelled')) {
                $order->update_status('cancelled', $order_note);
            }
        }

        public static function show_blocked_message() {
            $default_notice          = __('Sorry, You are blocked from checking out.', 'wmbc');
            $wmbc_black_list_message = self::get_setting('wmbc_black_list_message', $default_notice);

            //with some reason, get_option with default value not working

            if (!wc_has_notice($wmbc_black_list_message)) {
                wc_add_notice($wmbc_black_list_message, 'error');
            }
        }
    }
}