<?php
/**
 * Handler class to update the blacklisted settings
 * Show the message in checkout page
 *
 * @package woo-manage-fraud-orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WMFO_Blacklist_Handler' ) ) {

	/**
	 * Class WMFO_Blacklist_Handler
	 */
	class WMFO_Blacklist_Handler {

		/**
		 * Fetch the setting from wp_options.
		 *
		 * @param string $key The wp_options name.
		 * @param string $default Possibly mixed, but always string in this plugin.
		 *
		 * @return string|mixed
		 */
		public static function get_setting( $key, $default = '' ) {
			return get_option( $key ) ? get_option( $key ) : $default;
		}

		/**
		 * Get an array of the saved blacklists.
		 *
		 * @used-by self::init()
		 *
		 * @return array<string,string>
		 */
		public static function get_blacklists(): array {
			return array(
				'prev_black_list_ips'        => self::get_setting( 'wmfo_black_list_ips' ),
				'prev_wmfo_black_list_names' => self::get_setting( 'wmfo_black_list_names' ),
				'prev_black_list_phones'     => self::get_setting( 'wmfo_black_list_phones' ),
				'prev_black_list_emails'     => self::get_setting( 'wmfo_black_list_emails' ),
			);

		}

		/**
		 * Add or remove a specified entry from the saved values.
		 *
		 * @param string $key The wp_options name.
		 * @param string $pre_values The preexisting values, as a string, one per line.
		 * @param string $to_add The value to add.
		 * @param string $action "add"|"remove".
		 */
		public static function update_blacklist( $key, $pre_values, $to_add, $action = 'add' ) {
			$new_values = null;
			if ( 'add' === $action ) {
				if ( empty( $pre_values ) ) {
					$new_values = $to_add;
				} else {
					$new_values = ! in_array( $to_add, explode( PHP_EOL, $pre_values ), true ) ? $pre_values . PHP_EOL . $to_add : $pre_values;
				}
			} elseif ( 'remove' === $action ) {
				$in_array_value = explode( PHP_EOL, $pre_values );
				if ( in_array( $to_add, $in_array_value, true ) ) {
					$array_key = array_search( $to_add, $in_array_value, true );
					if ( false !== $array_key ) {
						unset( $in_array_value[ $array_key ] );
					}
				}
				$new_values = implode( PHP_EOL, $in_array_value );
			}

			if ( ! is_null( $new_values ) ) {
				update_option( $key, trim( $new_values ) );
			}
		}

		/**
		 *
		 * When $context is front, we are customer facing so throw an exception to display an error to them.
		 *
		 * @see wmfo_get_customer_details_of_order()
		 *
		 * @param array<string,string>|false $customer Customer details (optional if an order is provided).
		 * @param ?WC_Order                  $order A WooCommerce order (option if customer details are provided).
		 * @param string                     $action "add"|"remove".
		 * @param string                     $context "front"|"order-pay-eway".
		 *
		 * @return bool
		 * @throws Exception
		 */
		public static function init( $customer = array(), $order = null, $action = 'add', $context = 'front' ): bool {
			$prev_blacklisted_data = self::get_blacklists();
			if ( empty( $customer ) ) {
				return false;
			}

			self::update_blacklist( 'wmfo_black_list_names', $prev_blacklisted_data['prev_wmfo_black_list_names'], $customer['full_name'], $action );
			self::update_blacklist( 'wmfo_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address'], $action );
			self::update_blacklist( 'wmfo_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone'], $action );
			self::update_blacklist( 'wmfo_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email'], $action );

			// Handle the cancellation of order.
			if ( null !== $order ) {
				$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'woo-manage-fraud-orders' );
				$wmfo_black_list_message = self::get_setting( 'wmfo_black_list_message', $default_notice );

				self::cancel_order( $order, $action );

				if ( 'front' === $context ) {
					throw new Exception( $wmfo_black_list_message );
				}

				if ( in_array( $context, array( 'order-pay', 'order-pay-eway' ), true ) ) {
					if ( ! wc_has_notice( $wmfo_black_list_message, 'error' ) ) {
						wc_add_notice( $wmfo_black_list_message, 'error' );
					}
				}

				if ( 'order-pay-eway' === $context ) {
				    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['AccessCode'] ) ) {
						wp_safe_redirect( $order->get_checkout_payment_url( false ) );
						exit();
					} else {
						throw new Exception();
					}
				}
			}

			return true;
		}

		/**
		 * Sets the order status to cancelled and adds a note saying the details are blacklisted.
		 *
		 * When $action=='remove' it adds a note saying the details are no longer blacklisted.
		 *
		 * @param WC_Order $order   The WooCommerce order.
		 * @param string   $action  "add"|"remove".
		 *
		 * @return bool Always returns true.
		 */
		public static function cancel_order( $order, $action = 'add' ): bool {
			if ( 'remove' === $action ) {
				$order->add_order_note( apply_filters( 'wmfo_remove_blacklisted_order_note', esc_html__( 'Order details removed from blacklist.', 'woo-manage-fraud-orders' ) ) );

				return true;
			}
			$blacklisted_order_note = apply_filters( 'wmfo_blacklisted_order_note', esc_html__( 'Order details blacklisted for future checkout.', 'woo-manage-fraud-orders' ), $order );

			// Set the order status to "Cancelled".
			if ( ! $order->has_status( 'cancelled' ) && $order->get_type() === 'shop_order' ) {
				$order->update_status( 'cancelled', $blacklisted_order_note );
			}
			$order->add_order_note( $blacklisted_order_note );

			return true;
		}

		/**
		 * Show the blocked message to the customer.
		 */
		public static function show_blocked_message() {
			$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'woo-manage-fraud-orders' );
			$wmfo_black_list_message = self::get_setting( 'wmfo_black_list_message', $default_notice );

			// with some reason, get_option with default value not working.

			if ( ! wc_has_notice( $wmfo_black_list_message ) ) {
				wc_add_notice( $wmfo_black_list_message, 'error' );
			}
		}

		/**
		 * The main function in the plugin: checks is the customer details blacklisted against the saved settings.
		 *
		 * @see wmfo_get_customer_details_of_order()
		 *
		 * @param array<string, string> $customer_details The details to check.
		 *
		 * @return bool
		 */
		public static function is_blacklisted( $customer_details ): bool {
			// Check for ony by one, return TRUE as soon as first matching.
			$allow_blacklist_by_name    = get_option( 'wmfo_allow_blacklist_by_name', 'no' );
			$blacklisted_customer_names = self::get_setting( 'wmfo_black_list_names' );
			$blacklisted_ips            = self::get_setting( 'wmfo_black_list_ips' );
			$blacklisted_emails         = self::get_setting( 'wmfo_black_list_emails' );
			$blacklisted_email_domains  = self::get_setting( 'wmfo_black_list_email_domains' );
			$blacklisted_phones         = self::get_setting( 'wmfo_black_list_phones' );

			$email  = $customer_details['billing_email'];
			$domain = substr( $email, strpos( $email, '@' ) + 1 );

			if ( 'yes' === $allow_blacklist_by_name && in_array( $customer_details['full_name'], array_map( 'trim', explode( PHP_EOL, $blacklisted_customer_names ) ), true ) ) {
				return true;
			} elseif ( in_array( $customer_details['ip_address'], array_map( 'trim', explode( PHP_EOL, $blacklisted_ips ) ), true ) ) {
				return true;
			} elseif ( in_array( $customer_details['billing_email'], array_map( 'trim', explode( PHP_EOL, $blacklisted_emails ) ), true ) ) {
				return true;
			} elseif ( in_array( $domain, array_map( 'trim', explode( PHP_EOL, $blacklisted_email_domains ) ), true ) ) {
				return true;
			} elseif ( in_array( $customer_details['billing_phone'], array_map( 'trim', explode( PHP_EOL, $blacklisted_phones ) ), true ) ) {
				return true;
			}

			return false;
		}
	}
}
