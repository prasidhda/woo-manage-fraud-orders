<?php
/**
 *Global functions related fraud management
 * Function to update the block list details
 * Called from backend , through order Action
 * Called also from checkotu page
 */

// function wmbc_update_blacklist_customers($customer = array()) {
//     if (empty($customer) || !$customer) {
//         return false;
//     }

//     $prev_black_list_ips    = get_option('wmbc_black_list_ips', true);
//     $prev_black_list_phones = get_option('wmbc_black_list_phones', true);
//     $prev_black_list_emails = get_option('wmbc_black_list_emails', true);
//     if ($prev_black_list_ips === false || $prev_black_list_ips == '') {
//         $new_black_list_ips = $customer['ip_address'];
//     } else {

//         $new_black_list_ips = !substr_count($prev_black_list_ips, $customer['ip_address']) ?
//         $prev_black_list_ips . ', ' . $customer['ip_address'] :
//         $prev_black_list_ips;
//     }

//     /*----------  Update Blackilists for Phones ----------*/
//     if ($prev_black_list_phones === false || $prev_black_list_phones == '') {
//         $new_black_list_phones = $customer['billing_phone'];
//     } else {
//         $new_black_list_phones = !substr_count($prev_black_list_phones, $customer['billing_phone']) ?
//         $prev_black_list_phones . ', ' . $customer['billing_phone'] :
//         $prev_black_list_phones;
//     }

//     /*---------- Update Blacklists for Emails  ----------*/
//     if ($prev_black_list_emails === false || $prev_black_list_emails == '') {
//         $new_black_list_emails = $customer['billing_email'];
//     } else {
//         $new_black_list_emails = !substr_count($prev_black_list_emails, $customer['billing_email']) ?
//         $prev_black_list_emails . ', ' . $customer['billing_email'] :
//         $prev_black_list_emails;
//     }

//     update_option('wmbc_black_list_ips', $new_black_list_ips);
//     update_option('wmbc_black_list_phones', $new_black_list_phones);
//     update_option('wmbc_black_list_emails', $new_black_list_emails);

//     return true;
// }
// /**
//  *
//  * Function to Show the Blacklist message to customers
//  *
//  */

// function wmbc_show_blocked_message() {
//     $default_notice          = __('Sorry, You are blocked from checking out.', 'wmbc');
//     $wmbc_black_list_message = get_option('wmbc_black_list_message') != '' ?
//     get_option('wmbc_black_list_message') :
//     $default_notice;
//     //with some reason, get_option with default value not working
//     if ($wmbc_black_list_message == '') {
//         $wmbc_black_list_message = $default_notice;
//     }

//     if (!wc_has_notice($wmbc_black_list_message)) {
//         wc_add_notice($wmbc_black_list_message, 'error');
//     }
// }

/**
 * Function to get the customer details
 * Billing Phone, Email and IP address
 */
function wmbc_get_customer_details_of_order($order) {
    if (!$order) {
        return false;
    }
    return array(
        'ip_address'    => $order->get_customer_ip_address(),
        'billing_phone' => $order->get_billing_phone(),
        'billing_email' => $order->get_billing_email(),
    );
}
function wmbc_get_ip_address() {
    if (isset($_SERVER['HTTP_X_REAL_IP'])) { // WPCS: input var ok, CSRF ok.
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP'])); // WPCS: input var ok, CSRF ok.
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { // WPCS: input var ok, CSRF ok.
        // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
        // Make sure we always only send through the first IP in the list which should always be the client IP.
        return (string) rest_is_ip_address(trim(current(preg_split('/[,:]/', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])))))); // WPCS: input var ok, CSRF ok.
    } elseif (isset($_SERVER['REMOTE_ADDR'])) { // @codingStandardsIgnoreLine
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])); // @codingStandardsIgnoreLine
    }
    return '';
}