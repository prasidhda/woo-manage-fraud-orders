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
		 * Get an array of the saved blacklists.
		 *
		 * @used-by self::init()
		 *
		 * @return array<string,string|array>
		 */
		public static function get_blacklists() {
			return array(
				'prev_black_list_ips'        => get_option( 'wmfo_black_list_ips', '' ),
				'prev_wmfo_black_list_names' => get_option( 'wmfo_black_list_names', '' ),
				'prev_black_list_phones'     => get_option( 'wmfo_black_list_phones' ),
				'prev_black_list_emails'     => get_option( 'wmfo_black_list_emails', '' ),
				'prev_black_list_addresses'  => get_option( 'wmfo_black_list_addresses', '' ),
			);

		}

		/**
		 * Add or remove a specified entry from the saved values.
		 *
		 * @param string $key The wp_options name.
		 * @param string $pre_values The preexisting values, as a string, one per line.
		 * @param string $to_add The value(s) to add.
		 * @param string $action "add"|"remove".
		 */
		public static function update_blacklist( $key, $pre_values, $to_add, $action = 'add' ) {
			$new_values = null;
			if ( 'wmfo_black_list_addresses' !== $key ) {
				$to_add = str_replace( PHP_EOL, '', $to_add );
			}

			if ( 'add' === $action ) {
				if ( empty( $pre_values ) ) {
					$new_values = $to_add;
				} else {
					$to_add_entries = explode( PHP_EOL, $to_add );

					foreach ( $to_add_entries as $to_add_entry ) {
						$new_values = ! in_array( $to_add_entry, explode( PHP_EOL, $pre_values ), true ) ? $pre_values . PHP_EOL . $to_add_entry : $pre_values;
					}
				}
			} elseif ( 'remove' === $action ) {

				$in_array_value    = explode( PHP_EOL, $pre_values );
				$to_remove_entries = explode( PHP_EOL, $to_add );

				foreach ( $to_remove_entries as $to_remove_entry ) {
					if ( in_array( $to_remove_entry, $in_array_value, true ) ) {
						$array_key = array_search( $to_remove_entry, $in_array_value, true );
						if ( false !== $array_key ) {
							unset( $in_array_value[ $array_key ] );
						}
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
		 * @param array<string,string|array>|false $customer Customer details (optional if an order is provided).
		 * @param ?WC_Order $order A WooCommerce order (option if customer details are provided).
		 * @param string $action "add"|"remove".
		 * @param string $context "front"|"order-pay-eway".
		 *
		 * @return bool
		 * @throws Exception
		 * @see wmfo_get_customer_details_of_order()
		 *
		 */
		public static function init( $customer = array(), $order = null, $action = 'add', $context = 'front' ) {
			$prev_blacklisted_data = self::get_blacklists();
			if ( empty( $customer ) ) {
				return false;
			}

			$allow_blacklist_by_name         = get_option( 'wmfo_allow_blacklist_by_name', 'no' );
			$wmfo_allow_blacklist_by_address = get_option( 'wmfo_allow_blacklist_by_address', 'yes' );

			if ( 'yes' == $allow_blacklist_by_name ) {
				self::update_blacklist( 'wmfo_black_list_names', $prev_blacklisted_data['prev_wmfo_black_list_names'], $customer['full_name'], $action );

			}
			self::update_blacklist( 'wmfo_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address'], $action );
			self::update_blacklist( 'wmfo_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone'], $action );
			self::update_blacklist( 'wmfo_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email'], $action );

			if ( 'no' != $wmfo_allow_blacklist_by_address ) {
				// If billing and shipping address are the same, only save one.
				if ( ! isset( $customer['shipping_address'] ) ) {
					$addresses = implode( ',', $customer['billing_address'] );
				} else {
					$addresses = implode( PHP_EOL, array_unique( array(
						implode( ',', $customer['billing_address'] ),
						implode( ',', $customer['shipping_address'] ),
					) ) );
				}


				self::update_blacklist( 'wmfo_black_list_addresses', $prev_blacklisted_data['prev_black_list_addresses'], $addresses, $action );

			}

			if ( in_array( $context, array( 'front', 'failed' ), true ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Max Fraud Attempts exceeded', 'woo-manage-fraud-orders' );
				WMFO_Blacklist_Handler::add_to_log( $customer );
			}

			// Handle the cancellation of order.
			if ( null !== $order ) {
				$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'woo-manage-fraud-orders' );
				$wmfo_black_list_message = get_option( 'wmfo_black_list_message', $default_notice );
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
		 * @param WC_Order $order The WooCommerce order.
		 * @param string $action "add"|"remove".
		 *
		 * @return bool Always returns true.
		 */
		public static function cancel_order( $order, $action = 'add' ) {
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

			$order->update_meta_data( '_wmfo_cancelled', 'yes' );
			$order->save();

			return true;
		}

		/**
		 * Show the blocked message to the customer.
		 */
		public static function show_blocked_message() {
			$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'woo-manage-fraud-orders' );
			$wmfo_black_list_message = get_option( 'wmfo_black_list_message', $default_notice );

			// with some reason, get_option with default value not working.

			if ( function_exists( 'wc_has_notice' ) && ! wc_has_notice( $wmfo_black_list_message ) ) {
				wc_add_notice( $wmfo_black_list_message, 'error' );
			}
		}

		/**
		 * @param $customer_details
		 */
		public static function add_to_log( $customer_details ) {
			global $first_caught_blacklisted_reason;
			// Add log to file
			$wmfo_enable_debug_log = get_option( 'wmfo_enable_debug_log', 'no' );

			if ( $wmfo_enable_debug_log === 'yes' ) {
				$debug_log = new WMFO_Debug_Log();
				$debug_log->write( '----------start------------' );
				$debug_log->write( 'Customer Details ==>' );
				$debug_log->write( $customer_details );

				$debug_log->write( 'Block type ==> ' . $first_caught_blacklisted_reason );
				$debug_log->write( 'Timestamp ==> ' . current_time( 'mysql' ) );

				$debug_log->write( '----------end------------' );
				$debug_log->write();
				$debug_log->write();
				$debug_log->save();
			}

			//Add log to DB table
			$wmfo_enable_db_log = get_option( 'wmfo_enable_db_log', 'yes' );

			if ( 'no' !== $wmfo_enable_db_log ) {
				$log_data = array(
					'full_name'          => $customer_details['full_name'] ?? 'N/A',
					'phone'              => $customer_details['billing_phone'] ?? 'N/A',
					'ip'                 => $customer_details['ip_address'] ?? 'N/A',
					'email'              => $customer_details['billing_email'] ?? 'NA',
					'billing_address'    => isset( $customer_details['billing_address'] ) ? implode( ',', $customer_details['billing_address'] ) : '',
					'shipping_address'   => isset( $customer_details['shipping_address'] ) ? implode( ',', $customer_details['shipping_address'] ) : '',
					'blacklisted_reason' => $first_caught_blacklisted_reason ?? 'N/A',
					'timestamp'          => current_time( 'mysql' ),
				);

				$logs_handler = new WMFO_Logs_Handler();
				$logs_handler->add_log( $log_data );
			}

		}

		/**
		 * Check if the current details are whitelisted
		 * Whitelist by payment gateway
		 * Whitelist by user
		 *
		 * @param $customer_details
		 *
		 * @return bool
		 */
		 public static function is_whitelisted( $customer_details ) {
	 			$wmfo_white_listed_payment_gateways = get_option( 'wmfo_white_listed_payment_gateways', array() );
	 			$wmfo_white_listed_customers        = get_option( 'wmfo_white_listed_customers', "" );

	 			$current_user = wp_get_current_user();

	 			if ( in_array( $customer_details['payment_method'], $wmfo_white_listed_payment_gateways, true ) ) {
	 				return true;
	 			} elseif (
	 				 in_array(
	 					 (string) get_current_user_id(),
	 						array_map( 'strtolower',
	 							array_map(
	 								'trim',
	 								explode( PHP_EOL, $wmfo_white_listed_customers )
	 							)
	 						),
	 				 true
	 				 ) ) {
	 				return true;
	 			}elseif(
	 				$current_user->ID &&
	 				in_array(
	 					$current_user->user_email,
	 					array_map( 'strtolower',
	 						array_map(
	 							'trim',
	 							explode( PHP_EOL, $wmfo_white_listed_customers )
	 						)
	 					),
	 					)
	 			){
	 				return true;
	 			}

	 			return false;
	 		}

		/**
		 * The main function in the plugin: checks is the customer details blacklisted against the saved settings.
		 *
		 * @param array<string, string> $customer_details The details to check.
		 *
		 * @return bool
		 * @see wmfo_get_customer_details_of_order()
		 *
		 */
		public static function is_blacklisted( $customer_details ) {
			// Check for ony by one, return TRUE as soon as first matching.
			$allow_blacklist_by_name         = get_option( 'wmfo_allow_blacklist_by_name', 'no' );
			$wmfo_allow_blacklist_by_email_wildcard         = get_option( 'wmfo_allow_blacklist_by_email_wildcard', 'no' );
			$wmfo_allow_blacklist_by_address = get_option( 'wmfo_allow_blacklist_by_address', 'yes' );
			$blacklisted_customer_names      = get_option( 'wmfo_black_list_names' );
			$blacklisted_ips                 = get_option( 'wmfo_black_list_ips' );
			$blacklisted_emails              = get_option( 'wmfo_black_list_emails' );
			$blacklisted_email_domains       = get_option( 'wmfo_black_list_email_domains' );
			$blacklisted_phones              = get_option( 'wmfo_black_list_phones' );
			$blacklisted_addresses           = get_option( 'wmfo_black_list_addresses' );

			$email  = $customer_details['billing_email'];
			$domain = substr( $email, strpos( $email, '@' ) + 1 );

			// Check blacklist by names
			if ( 'yes' === $allow_blacklist_by_name &&
			     ! empty( $blacklisted_customer_names ) &&
			     in_array(
				     strtolower( $customer_details['full_name'] ),
				     array_map( 'strtolower',
					     array_map( 'trim',
						     explode( PHP_EOL, $blacklisted_customer_names )
					     )
				     ),
				     true
			     ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Full Name', 'woo-manage-fraud-orders' );

				return true;
			} elseif ( ! empty( $blacklisted_ips ) &&
			           in_array(
				           strtolower( $customer_details['ip_address'] ),
				           array_map( 'strtolower',
					           array_map( 'trim',
						           explode( PHP_EOL, $blacklisted_ips )
					           )
				           ),
				           true
			           ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'IP Address', 'woo-manage-fraud-orders' );

				return true;
			} elseif ( ! empty( $blacklisted_emails ) &&
			           in_array(
				           strtolower( $customer_details['billing_email'] ),
				           array_map( 'strtolower',
					           array_map( 'trim',
						           explode( PHP_EOL, $blacklisted_emails )
					           )
				           ),
				           true
			           ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Email', 'woo-manage-fraud-orders' );

				return true;

			} elseif ( ! empty( $blacklisted_email_domains ) &&
			           in_array(
				           strtolower( $domain ),
				           array_map( 'strtolower',
					           array_map(
						           'trim',
						           explode( PHP_EOL, $blacklisted_email_domains )
					           )
				           ),
				           true ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Email Domain', 'woo-manage-fraud-orders' );

				return true;
			} elseif ( ! empty( $blacklisted_phones ) &&
			           in_array(
				           strtolower( $customer_details['billing_phone'] ),
				           array_map( 'strtolower',
					           array_map( 'trim',
						           explode( PHP_EOL, $blacklisted_phones )
					           )
				           ),
				           true ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Phone', 'woo-manage-fraud-orders' );

				return true;
			}

			//check for email wildcard
			if($wmfo_allow_blacklist_by_email_wildcard === "yes"){
				$is_wildcard_email_caught = false;
				if ( ! empty( $blacklisted_emails ) ) {
					foreach (
						array_map( 'strtolower',
							array_map( 'trim',
								explode( PHP_EOL, $blacklisted_emails )
							)
						) as $email_wild_card
					) {
						if ( strpos( strtolower( $customer_details['billing_email'] ), $email_wild_card ) !== false ) {
							$is_wildcard_email_caught = true;
							break;
						}
					}
				}


				if ( $is_wildcard_email_caught ) {
					$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Email Wildcard match', 'woo-manage-fraud-orders' );

					return true;
				}
			}
			

			if ( 'no' == $wmfo_allow_blacklist_by_address ) {

				return false;
			}
			// Map country name to country code.
			// AF => Afghanistan.
			$countries_list = WC()->countries->get_countries();
			$countries_list = array_map( 'strtolower', $countries_list );
			$countries_list = array_flip( $countries_list );

			$customer_billing_address_parts = $customer_details['billing_address'] ?? array();
			$customer_billing_address_parts = array_map(
				'strtolower',
				array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$customer_billing_address_parts
				)
			);

			$customer_shipping_address_parts = $customer_details['shipping_address'] ?? array();
			$customer_shipping_address_parts = array_map(
				'strtolower',
				array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$customer_shipping_address_parts
				)
			);

			foreach ( array_filter( explode( PHP_EOL, strtolower( $blacklisted_addresses ) ) ) as $blacklisted_address ) {
				$blacklisted_address_parts = explode( ',', $blacklisted_address );
				$blacklisted_address_parts = array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$blacklisted_address_parts
				);

				/**
				 * Check address by wildcard
				 * It has to be in %address% format
				 */
				if ( count( $blacklisted_address_parts ) === 1 ) {
					if ( substr_compare( $blacklisted_address_parts[0], '%', 0, strlen( '%' ) ) === 0 &&
					     substr_compare( $blacklisted_address_parts[0], '%', - strlen( '%' ) ) === 0
					) {
						$wild_card_val = strtolower( trim( $blacklisted_address_parts[0], '%' ) );
						if ( $wild_card_val != '' ) {

							// check by array
							if ( in_array( $wild_card_val, $customer_billing_address_parts ) ||
							     in_array( $wild_card_val, $customer_shipping_address_parts )
							) {
								$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'woo-manage-fraud-orders' );

								return true;
							}

							// check by string
							if ( strpos( implode( ' ', $customer_billing_address_parts ), $wild_card_val ) !== false ||
							     strpos( implode( ' ', $customer_shipping_address_parts ), $wild_card_val ) !== false
							) {
								$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'woo-manage-fraud-orders' );

								return true;
							}
						}

					}
				}
				/**
				 * If all the parts of the blacklisted address are in the customer's address
				 *
				 * @see https://stackoverflow.com/a/22651134/
				 */
				$address_match = ! array_diff( $blacklisted_address_parts, $customer_billing_address_parts )
				                 || ! array_diff( $blacklisted_address_parts, $customer_shipping_address_parts );

				if ( $address_match ) {
					$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'woo-manage-fraud-orders' );

					return true;
				}
			}

			return false;
		}
	}
}
