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
 * @return array<string,string|array>|false
 */
function wmfo_get_customer_details_of_order( $order ) {
	if ( ! ( $order instanceof WC_Order ) ) {
		return false;
	}

	$address_keys     = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
	$billing_address  = array_intersect_key( $order->get_address( 'billing' ), array_flip( $address_keys ) );
	$shipping_address = array_intersect_key( $order->get_address( 'shipping' ), array_flip( $address_keys ) );

	return array(
		'full_name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
		'ip_address'       => $order->get_customer_ip_address(),
		'billing_phone'    => $order->get_billing_phone(),
		'billing_email'    => $order->get_billing_email(),
		'billing_address'  => array_filter( array_map( 'trim', array_values( $billing_address ) ) ),
		'shipping_address' => array_filter( array_map( 'trim', array_values( $shipping_address ) ) ) ?? array(),
	);
}

/**
 *
 * In case woo commerce changes the function name to get IP address,
 */
function wmfo_get_ip_address() {
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

/**
 * @return array
 */
function wmfp_get_customers() {
	$all_users = get_users();

	$formatted_all_users = array();
	foreach ( $all_users as $key => $user ) {
		$formatted_all_users[ $user->get( 'ID' ) ] = $user->get( 'user_login' );
	}

	return $formatted_all_users;
}


/**
 * get enabled gateways
 * @return mixed
 */
function wmfp_get_enabled_payment_gateways() {
	$available_payment_gateways           = WC()->payment_gateways->get_available_payment_gateways();
	$formatted_available_payment_gateways = array();
	foreach ( $available_payment_gateways as $key => $available_payment_gateway ) {
		$method_title = $available_payment_gateway->title;
		if(!$method_title){
			$method_title = $available_payment_gateway->method_title;
		}
		$formatted_available_payment_gateways[ $key ] = $method_title;
	}

	return $formatted_available_payment_gateways;
}
