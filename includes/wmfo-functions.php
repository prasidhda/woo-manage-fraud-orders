<?php
/**
 * Global functions related fraud management
 * Function to update the block list details
 *
 * @package woo-manage-fraud-orders
 */

/**
 * Function to get the customer details
 * Billing Phone, Email and IP address
 *
 * @param WC_Order $order The WooCommerce order object.
 *
 * @return array<string,string>|false
 */
function wmfo_get_customer_details_of_order( $order ) {
	if ( ! ( $order instanceof WC_Order ) ) {
		return false;
	}

	return array(
		'full_name'     => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
		'ip_address'    => $order->get_customer_ip_address(),
		'billing_phone' => $order->get_billing_phone(),
		'billing_email' => $order->get_billing_email(),
	);
}

/**
 *
 * In case woo commerce changes the function name to get IP address,
 */
function wmfo_get_ip_address(): string {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return '';
}
