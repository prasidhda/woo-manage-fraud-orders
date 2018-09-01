<?php
/**
 * Class to track the behavior of customer and block the customer from future
 * checkout process
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WMFO_Track_Customers' ) ) {

	class WMFO_Track_Customers {

		public static $_instance;

		public function __construct() {
			global $woocommerce;
			// var_dump($woocommerce);
			add_action( 'woocommerce_after_checkout_validation', [
				$this,
				'manage_blacklisted_customers',
			], 10, 2 );
			add_action( 'woocommerce_checkout_order_processed', [
				$this,
				'manage_multiple_failed_attempts',
			], 100, 3 );
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public static function manage_blacklisted_customers( $data, $errors ) {
			//Check if there are any other errors first
			//If there are, return
			if ( ! empty( $errors->errors ) ) {
				return;
			}

			//Woo/Payment method saves the payment method validation errors in session
			//If there such errors, skip
			if ( ! isset( WC()->session->reload_checkout ) ) {
				$error_notices = wc_get_notices( 'error' );
			}

			if ( ! empty( $error_notices ) ) {
				return;
			}

			$prev_black_list_ips = get_option( 'WMFO_black_list_ips', TRUE );
			$prev_black_list_phones = get_option( 'WMFO_black_list_phones', TRUE );
			$prev_black_list_emails = get_option( 'WMFO_black_list_emails', TRUE );

			$billing_email = isset( $_POST['billing_email'] ) ? wc_clean( $_POST['billing_email'] ) : '';
			$billing_phone = isset( $_POST['billing_phone'] ) ? wc_clean( $_POST['billing_phone'] ) : '';

			$ip_address = method_exists( 'WC_Geolocation', 'get_ip_address' ) ? WC_Geolocation::get_ip_address() : wmfo_get_ip_address();

			//Block this checkout if this customers details are already blacklisted
			if ( in_array( $ip_address, explode( PHP_EOL, $prev_black_list_ips ) ) || in_array( $billing_phone, explode( PHP_EOL, $prev_black_list_phones ) ) || in_array( $billing_email, explode( PHP_EOL, $prev_black_list_emails ) ) ) {

				if ( method_exists( 'WMFO_Blacklist_Handler', 'show_blocked_message' ) ) {
					WMFO_Blacklist_Handler::show_blocked_message();
				}

				return;
			}
		}

		/**
		 *
		 * 'manage_multiple_failed_attempts' will only track the multiple failed attempts after the creating of failed
		 * order by customer, This is helpful when customer enter the correct format of the data but payment gateway
		 * couldn't authorize the payment. Typical example willl be Electronic check, CC processing
		 */
		public static function manage_multiple_failed_attempts( $order_id, $posted_data, $order ) {
			if ( $order->get_status() === 'failed' ) {
				//md5 the name of the cookie for fraud_attempts
				$fraud_attempts_md5 = md5( 'fraud_attempts' );
				$fraud_attempts     = ( ! isset( $_COOKIE[ $fraud_attempts_md5 ] ) || NULL === $_COOKIE[ $fraud_attempts_md5 ] ) ? 0 : $_COOKIE[ $fraud_attempts_md5 ];

				$cookie_value = (int) $fraud_attempts + 1;
				setcookie( $fraud_attempts_md5, $cookie_value, time() + ( 60 * 60 ), "/" ); // 86400 = 1 day
				//Get the allowed failed order limit, default to 3
				$fraud_limit = get_option( 'wmfo_black_list_allowed_fraud_attemps' ) != '' ? get_option( 'wmfo_black_list_allowed_fraud_attemps' ) : 3;

				if ( (int) $fraud_attempts >= $fraud_limit ) {
					//Show the blocking message in the checkout page.
					if ( method_exists( 'WMFO_Blacklist_Handler', 'show_blocked_message' ) ) {
						WMFO_Blacklist_Handler::show_blocked_message();
					}

					//Block this customer for future sessions as well
					//And cancel the order
					$customer = wmfo_get_customer_details_of_order( $order );
					if ( method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order );
					}
				}
			}
		}
	}
}

WMFO_Track_Customers::instance();
