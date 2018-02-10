<?php
/**
 * Class to track the behavior of customer and block the customer from future checkout process
 */
if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WMBC_Track_Customers')) {
    class WMBC_Track_Customers {
        public static $_instance;

        public function __construct() {
            global $woocommerce;
            // var_dump($woocommerce);
            add_action('woocommerce_after_checkout_validation', array($this, 'wmbc_manage_multiple_failed_attempt'), 10, 2);
            add_action('woocommerce_checkout_order_processed', array($this, 'wmbc_set_fraud_attempts_cookie'), 100, 3);
        }

        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public static function wmbc_manage_multiple_failed_attempt($data, $erros) {
            //Check if there are any other erroes first
            //If there are, return
            if (!empty($errors->errors)) {
                return;
            }

            //check if there are error messages saved in session
            //Woo/Payment method saves the payment method validation errors in session
            //If there such errors, skip
            if (!isset(WC()->session->reload_checkout)) {
                $error_notices = wc_get_notices('error');
            }

            if (!empty($error_notices)) {
                return;
            }

            $prev_black_list_ips    = get_option('wmbc_black_list_ips', true);
            $prev_black_list_phones = get_option('wmbc_black_list_phones', true);
            $prev_black_list_emails = get_option('wmbc_black_list_emails', true);

            $billing_email = isset($_POST['billing_email']) ? wc_clean($_POST['billing_email']) : '';
            $billing_phone = isset($_POST['billing_phone']) ? wc_clean($_POST['billing_phone']) : '';

            $ip_address = method_exists('WC_Geolocation', 'get_ip_address') ? WC_Geolocation::get_ip_address() : wmbc_get_ip_address();

            //Block this checkout if this customers details are already blacklisted
            if (substr_count($prev_black_list_ips, $ip_address) > 0 ||
                substr_count($prev_black_list_phones, $billing_phone) > 0 ||
                substr_count($prev_black_list_emails, $billing_email) > 0) {

                if (method_exists('WMBC_Blacklist_Handler', 'show_blocked_message')) {
                    WMBC_Blacklist_Handler::show_blocked_message();
                }

                return;
            }
            // die('here');
        }

        public static function wmbc_set_fraud_attempts_cookie($order_id, $posted_data, $order) {
            if ($order->get_status() === 'failed') {
                //md5 the name of the cookie for fraud_attempts
                $fraud_attempts_md5 = md5('fraud_attempts');
                $fraud_attempts     = (!isset($_COOKIE[$fraud_attempts_md5]) || NULL === $_COOKIE[$fraud_attempts_md5]) ? 0 :
                $_COOKIE[$fraud_attempts_md5];

                $cookie_value = (int) $fraud_attempts + 1;
                setcookie($fraud_attempts_md5, $cookie_value, time() + (60 * 60), "/"); // 86400 = 1 day

                $fraud_limit = get_option('wmbc_black_list_allowed_fraud_attemps') != '' ?
                get_option('wmbc_black_list_allowed_fraud_attemps') :
                3;

                if ((int) $fraud_attempts >= $fraud_limit) {
                    //Show the blocking message in the front end.
                    if (method_exists('WMBC_Blacklist_Handler', 'show_blocked_message')) {
                        WMBC_Blacklist_Handler::show_blocked_message();
                    }

                    //Block this customer for future sessions as well
                    //And cancel the order
                    $customer = wmbc_get_customer_details_of_order($order);
                    if (method_exists('WMBC_Blacklist_Handler', 'init')) {
                        WMBC_Blacklist_Handler::init($customer, $order);
                    }
                }
            }
        }
    }
}

WMBC_Track_Customers::instance();
