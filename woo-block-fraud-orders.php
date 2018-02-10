<?php
/*
Plugin Name:  Woo Manage Blacklisted Customers
Plugin URI:   https://github.com/prasidhda/woo-block-fraud-orders
Description:  WooCommerce plugin to block the fraud orders.
Version:      0.1
Author:       Prasidhda Malla
Author URI:   https://profiles.wordpress.org/prasidhda
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  woo-block-fraud-orders
Domain Path:  /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!defined('WMBC_PLUGIN_FILE')) {
    define('WMBC_PLUGIN_FILE', __FILE__);
}

if (!class_exists('Woo_Manage_Blacklisted_Customers')) {
    include_once dirname(__FILE__) . '/includes/cls-woo-manage-blacklisted-customers.php';
}

function wmbc() {
    return Woo_Manage_Blacklisted_Customers::instance();
}

// Global for backwards compatibility.
$GLOBALS['wmbc'] = wmbc();

// require_once dirname(__FILE__) . '/functions.php';

// if (is_admin()) {
//     include_once dirname(__FILE__) . '/admin/woo-blacklists-settings.php';
//     include_once dirname(__FILE__) . '/admin/woo-order-blacklists.php';
// }

/**
 *
 * Main function to manage the blacklisted customers
 * Block the orders depending upon the blacklisted order and customoer's behavior
 */

// function woo_blacklist_manage_multiple_failed_attempt($data, $errors) {
//     //Check if there are any other erroes first
//     //If there are, return
//     if (!empty($errors->errors)) {
//         return;
//     }

//     //check if there are error messages saved in session
//     //Woo/Payment method saves the payment method validation errors in session
//     //If there such errors, skip
//     // wc_print_notices();
//     $all_notices = WC()->session->get('wc_notices', array());
//     // var_dump( $all_notices );
//     // var_dump( WC()->session->get( 'wc_notices', array() ));
//     if (!isset(WC()->session->reload_checkout)) {
//         $error_notices = wc_get_notices('error');
//     }

//     if (!empty($error_notices)) {
//         return;
//     }

//     $prev_black_list_ips    = get_option('bold_black_list_ips', true);
//     $prev_black_list_phones = get_option('bold_black_list_phones', true);
//     $prev_black_list_emails = get_option('bold_black_list_emails', true);

//     $billing_email = $_POST['billing_email'];
//     $billing_phone = $_POST['billing_phone'];
//     $ip_address    = WC_Geolocation::get_ip_address();

//     //Block this checkout if this customers details are already blacklisted
//     if (substr_count($prev_black_list_ips, $ip_address) > 0 ||
//         substr_count($prev_black_list_phones, $billing_phone) > 0 ||
//         substr_count($prev_black_list_emails, $billing_email) > 0) {
//         // var_dump($prev_black_list_ips);
//         // var_dump($prev_black_list_phones);
//         // var_dump($prev_black_list_emails);
//         woo_blacklist_show_blocked_message();
//         return;
//     }

//     //check for multiple fraud attempts
//     // $fraud_attempts_md5 = md5('fraud_attempts');
//     // $prev_fraud_attempts = $_COOKIE[$fraud_attempts_md5];
//     // $fraud_limit =     get_option( 'bold_black_list_allowed_fraud_attemps' ) != '' ?
//     //                 get_option( 'bold_black_list_allowed_fraud_attemps' ) :
//     //                 3;

//     // if( (int) $prev_fraud_attempts >= $fraud_limit ){
//     //     woo_blacklist_show_blocked_message();

//     //     //Block this customer for future sessions as well
//     //     woo_blacklist_update_blacklist_customers(
//     //         array(
//     //             'ip_address' => $ip_address,
//     //             'billing_phone' => $billing_phone,
//     //             'billing_email' => $billing_email
//     //         )
//     //     );
//     // }
// }

// //This hook will be helpful for auto detecting multiple failed attempts
// add_action('woocommerce_after_checkout_validation', 'woo_blacklist_manage_multiple_failed_attempt', 10, 2);

// /**
//  *
//  * Function to track the number of fraud attempts
//  * using browser cookie
//  */

// function woo_blacklist_set_fraud_attempts_cookie($order_id, $posted_data, $order) {
//     if ($order->get_status() === 'failed') {
//         //md5 the name of the cookie for fraud_attempts
//         $fraud_attempts_md5 = md5('fraud_attempts');
//         $fraud_attempts     = (!isset($_COOKIE[$fraud_attempts_md5]) || NULL === $_COOKIE[$fraud_attempts_md5]) ?
//         0 :
//         $_COOKIE[$fraud_attempts_md5];

//         $cookie_value = (int) $fraud_attempts + 1;
//         setcookie($fraud_attempts_md5, $cookie_value, time() + (60 * 60), "/"); // 86400 = 1 day

//         $fraud_limit = get_option('bold_black_list_allowed_fraud_attemps') != '' ?
//         get_option('bold_black_list_allowed_fraud_attemps') :
//         3;

//         if ((int) $fraud_attempts >= $fraud_limit) {
//             woo_blacklist_show_blocked_message();

//             //Block this customer for future sessions as well
//             $customer = woo_blacklist_get_customer_details_of_order($order);
//             if (woo_blacklist_update_blacklist_customers($customer)) {
//                 $order_note = __('Order details blacklisted for future checkout.', 'boldpreciosumetals');
//                 //Set the order status to Canceled
//                 if (!$order->has_status('cancelled')) {
//                     $order->update_status('cancelled', $order_note);
//                 }
//             }
//         }
//     }
// }

// add_action('woocommerce_checkout_order_processed', 'woo_blacklist_set_fraud_attempts_cookie', 100, 3);
