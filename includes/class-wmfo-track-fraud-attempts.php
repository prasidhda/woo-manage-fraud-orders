<?php
/**
 * Class to track the behavior of customer and block the customer from future
 * checkout process
 *
 * @package woo-manage-fraud-orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WMFO_Track_Fraud_Attempts' ) ) {

	/**
	 * Class WMFO_Track_Fraud_Attempts
	 */
	class WMFO_Track_Fraud_Attempts {

		/**
		 * The singleton instance.
		 *
		 * @var ?WMFO_Track_Fraud_Attempts $instance
		 */
		protected static $instance = null;

		/**
		 * WMFO_Track_Fraud_Attempts constructor.
		 */
		protected function __construct() {
			add_action( 'woocommerce_after_checkout_validation', array(
				$this,
				'manage_blacklisted_customers_checkout'
			), 10, 2 );

			add_action( 'woocommerce_before_pay_action', array(
				$this,
				'manage_blacklisted_customers_order_pay'
			), 99, 1 );

			add_action( 'woocommerce_after_pay_action', array(
				$this,
				'manage_multiple_failed_attempts_order_pay'
			), 99, 1 );

			// Not part of WooCommerce core.
			add_action( 'woocommerce_api_wc_gateway_eway_payment_failed', array(
				$this,
				'manage_multiple_failed_attempts_eway'
			), 100, 4 );

			add_action( 'woocommerce_checkout_order_processed', array(
				$this,
				'manage_multiple_failed_attempts_checkout'
			), 100, 3 );

			add_action( 'woocommerce_order_status_failed', array(
				$this,
				'manage_multiple_failed_attempts_default'
			), 100, 2 );

		}

		/**
		 * Get the class singleton object.
		 *
		 * @return WMFO_Track_Fraud_Attempts
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 *
		 * @hooked woocommerce_after_checkout_validation
		 *
		 * @param array<string, mixed> $data An array of posted data.
		 * @param WP_Error $errors A WP Error object to add errors to.
		 *
		 * @throws Exception
		 * @see WC_Checkout::get_posted_data()
		 *
		 * @see WC_Checkout::validate_checkout()
		 *
		 */
		public static function manage_blacklisted_customers_checkout( $data, $errors ) {
			// Check if there are any other errors first.
			// If there are, return.
			if ( ! empty( $errors->errors ) ) {
				return;
			}

			// Woo/Payment method saves the payment method validation errors in session.
			// If there are such errors, skip.
			if ( ! isset( WC()->session->reload_checkout ) ) {
				$error_notices = wc_get_notices( 'error' );
			}

			if ( ! empty( $error_notices ) ) {
				return;
			}


			// This is checked for the woocommerce subscription.
			// If allowed to skip the blacklisting for subscription renewal order payment, return.
			if ( function_exists( 'wcs_cart_contains_renewal' ) ) {
				$cart_item = wcs_cart_contains_renewal();

				if ( isset( $cart_item['subscription_renewal']['renewal_order_id'] ) ) {
					$renewal_order = wc_get_order( $cart_item['subscription_renewal']['renewal_order_id'] );

					if ( $renewal_order ) {
						$order_id = $renewal_order->get_id();
						if ( get_post_meta( $order_id, 'wmfo_skip_blacklist', true ) === 'yes' ) {
							return;
						}
					}
				}
			}

			$customer_details = array();

			$first_name                    = $data['billing_first_name'] ?? '';
			$last_name                     = $data['billing_last_name'] ?? '';
			$customer_details['full_name'] = $first_name . ' ' . $last_name;

			$customer_details['billing_email']  = $data['billing_email'] ?? '';
			$customer_details['billing_phone']  = $data['billing_phone'] ?? '';
			$customer_details['payment_method'] = $data['payment_method'] ?? '';

			//customer billing address in single array
			$customer_details['billing_address'] = array();
			if ( isset( $data['billing_address_1'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_1'];
			}
			if ( isset( $data['billing_address_2'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_2'];
			}
			if ( isset( $data['billing_city'] ) ) {
				$customer_details['billing_address'][] = $data['billing_city'];
			}
			if ( isset( $data['billing_state'] ) ) {
				$customer_details['billing_address'][] = $data['billing_state'];
			}
			if ( isset( $data['billing_postcode'] ) ) {
				$customer_details['billing_address'][] = $data['billing_postcode'];
			}
			if ( isset( $data['billing_country'] ) ) {
				$customer_details['billing_address'][] = $data['billing_country'];
			}

			//customer shipping address in single array
			$customer_details['shipping_address'] = array();
			if ( isset( $data['shipping_address_1'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_1'];
			}
			if ( isset( $data['shipping_address_2'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_2'];
			}
			if ( isset( $data['shipping_city'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_city'];
			}
			if ( isset( $data['shipping_state'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_state'];
			}
			if ( isset( $data['shipping_postcode'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_postcode'];
			}
			if ( isset( $data['shipping_country'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_country'];
			}

			if ( count( $customer_details['shipping_address'] ) < 1 ) {
				unset( $customer_details['shipping_address'] );
			}

			$cart_items = WC()->cart->get_cart();

			$product_items = array();
			foreach ( $cart_items as $product_item ) {
				$product_items[] = $product_item['product_id'];
			}
			self::manage_blacklisted_customers( $customer_details, $product_items );
		}

		/**
		 * Before the order is paid, check should the customer be blocked.
		 *
		 * @hooked woocommerce_before_pay_action
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 *
		 * @see WC_Form_Handler::pay_action()
		 *
		 */
		public static function manage_blacklisted_customers_order_pay( $order ) {

			$customer_details = wmfo_get_customer_details_of_order( $order );

			if ( false === $customer_details ) {
				return;
			}

			$product_items = array();
			foreach ( $order->get_items() as $product_item ) {
				if ( ! ( $product_item instanceof WC_Order_Item_Product ) ) {
					continue;
				}
				$product_items[] = $product_item->get_product_id();
			}

			self::manage_blacklisted_customers( $customer_details, $product_items, $order );
		}

		/**
		 *
		 * Returns early if the order has been marked to skip the blacklist.
		 *
		 * @param array<string,string|array> $customer_details The customer details that might be blacklisted.
		 * @param int[] $product_items The product ids in the order.
		 * @param ?WC_Order $order The WooCommerce order.
		 *
		 * @throws Exception
		 * @see wmfo_get_customer_details_of_order()
		 *
		 */
		public static function manage_blacklisted_customers( $customer_details, $product_items, $order = null ) {
			//White list check
			// If chosen payment gateway is on the whitelist, skip the blacklist check
			if ( WMFO_Blacklist_Handler::is_whitelisted( $customer_details ) ) {

				return;
			}

			// As very first step, check if there is skipping set for order pay.
			if ( null !== $order ) {
				$order_id = $order->get_id();
				if ( get_post_meta( $order_id, 'wmfo_skip_blacklist', true ) === 'yes' ) {
					return;
				}
			}

			// If there are values set to this, we should handle the blacklisting only if customer has such products in cart.
			$blacklist_product_types = get_option( 'wmfo_black_list_product_types', array() );
			if ( ! empty( $blacklist_product_types ) && ! self::check_products_in_product_type_blacklist( $product_items ) ) {
				return;
			}

			$customer_details['ip_address'] = method_exists( 'WC_Geolocation', 'get_ip_address' ) ? WC_Geolocation::get_ip_address() : wmfo_get_ip_address();

			// Block this checkout if this customer details are already blacklisted.
			if ( WMFO_Blacklist_Handler::is_blacklisted( $customer_details ) ) {
				if ( method_exists( 'WMFO_Blacklist_Handler', 'show_blocked_message' ) ) {
					WMFO_Blacklist_Handler::show_blocked_message();
					WMFO_Blacklist_Handler::add_to_log( $customer_details );
				}

				return;
			}

			// Check if there are matching records in DB for possible fraud attempts
			$fraud_limit = get_option( 'wmfo_black_list_allowed_fraud_attempts', 5 );

			if ( self::is_possible_fraud_attempts( ($fraud_limit - 1), $customer_details ) ) {
				WMFO_Blacklist_Handler::init( $customer_details, $order );
				WMFO_Blacklist_Handler::show_blocked_message();

				return;

			}

			/**
			 * Block the customer if there is setting for order_status blocking
			 * If the customer previously has blocked order status in setting, He/She will be blocked from placing
			 * order
			 */
			$blacklists_order_status = get_option( 'wmfo_black_list_order_status', array() );

			$data = WC()->checkout()->get_posted_data();

			$billing_email = $data['billing_email'] ?? null;
			$billing_phone = $data['billing_phone'] ?? null;

			// Get all previous orders of current customer.
			$args = array(
				'post_type'      => 'shop_order',
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_customer_user',
						'value'   => is_user_logged_in() ? get_current_user_id() : null, // For logged in.
						'compare' => '=',
					),
					array(
						'key'     => '_billing_email',
						'value'   => $billing_email, // For guest customer.
						'compare' => '=',
					),
					array(
						'key'     => '_billing_phone',
						'value'   => $billing_phone, // For guest customer.
						'compare' => '=',
					),
				),
			);

			$prev_orders_customers = get_posts( $args );

			if ( ! empty( $prev_orders_customers ) ) {
				foreach ( $prev_orders_customers as $prev_order ) {

					if ( in_array( $prev_order->post_status, $blacklists_order_status, true ) ) {
						if ( method_exists( 'WMFO_Blacklist_Handler', 'show_blocked_message' ) ) {
							$GLOBALS['first_caught_blacklisted_reason'] = __( 'Order Status', 'woo-manage-fraud-orders' );
							WMFO_Blacklist_Handler::show_blocked_message();
							WMFO_Blacklist_Handler::add_to_log( $customer_details );
						}
						break;
					}
				}
			}
		}

		/**
		 *
		 * @hooked woocommerce_checkout_order_processed
		 *
		 * @param int $_order_id The order id.
		 * @param array<string, mixed> $_posted_data The checkout data.
		 * @param WC_Order $order The WooCommerce order.
		 *
		 * @throws Exception
		 * @see WC_Checkout::process_checkout()
		 *
		 * @see WC_Checkout::get_posted_data()
		 *
		 */
		public static function manage_multiple_failed_attempts_checkout( $_order_id, $_posted_data, $order ) {
			self::manage_multiple_failed_attempts( $order );
		}

		/**
		 *
		 * @hooked woocommerce_after_pay_action
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 *
		 * @throws Exception
		 * @see WC_Form_Handler::pay_action()
		 *
		 */
		public static function manage_multiple_failed_attempts_order_pay( $order ) {
			self::manage_multiple_failed_attempts( $order, 'order-pay' );
		}

		/**
		 * Triggered when a payment with the gateway fails.
		 *
		 * @param WC_Order $order The order whose payment failed.
		 * @param stdClass $_result The result from the API call.
		 * @param string $_error The error message.
		 * @param WC_Gateway_EWAY $_gateway The instance of the gateway.
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_eway( $order, $_result, $_error, $_gateway ) {
			self::manage_multiple_failed_attempts( $order, 'order-pay-eway' );
		}

		/**
		 * @param $order_id
		 * @param $order
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_default( $order_id, $order ) {
			if ( is_admin() ) {
				return;
			}
			self::manage_multiple_failed_attempts( $order, 'failed' );
		}

		/**
		 *
		 * 'manage_multiple_failed_attempts' will only track the multiple failed attempts after the creating of failed
		 * order by customer, This is helpful when customer enter the correct format of the data but payment gateway
		 * couldn't authorize the payment. Typical example will be Electronic check, CC processing.
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 * @param string $context "front"|"order-pay"|"order-pay-eway".
		 *
		 * @throws Exception
		 */
		protected static function manage_multiple_failed_attempts( $order, string $context = 'front' ) {
			// As very first step, check if there is product type blacklist.
			// If there are values set to this, we should handle the blacklisting only if customer has such products in cart.
			$product_items = array();
			if ( $order->get_items() && ! empty( $order->get_items() ) ) {
				foreach ( $order->get_items() as $product_item ) {
					$product_item_data = $product_item->get_data();
					if ( isset( $product_item_data['product_id'] ) ) {
						$product_items[] = $product_item_data['product_id'];
					}
				}
			}

			// If the product type blacklist is configured but none of the order's products are relevant, return.
			$blacklist_product_types = get_option( 'wmfo_black_list_product_types', array() );
			if ( ! empty( $blacklist_product_types ) && ! self::check_products_in_product_type_blacklist( $product_items ) ) {
				return false;
			}

			if ( $order->get_status() === 'failed' || 'failed' === $context ) {
				// Get the allowed failed order limit, default to 5.
				$fraud_limit = get_option( 'wmfo_black_list_allowed_fraud_attempts', 5 );

				// Get customer details
				$customer                             = wmfo_get_customer_details_of_order( $order );
				$fraudulent_details                   = $customer;
				$fraudulent_details['payment_method'] = $order->get_payment_method();

				// Save to the fraud attempt DB table
				self::save_fraud_attempt_record( $fraudulent_details );

				// Save to the order meta
				$pre_fraud_attempt = (int) $order->get_meta( '_wmfo_fraud_attempts', true );

				$order->update_meta_data( '_wmfo_fraud_attempts', $pre_fraud_attempt + 1 );
				$order->save();

				//SERVER side fraud attempts check
				// Check in the order meta
				$order_meta_fraud_status = $pre_fraud_attempt > (int) $fraud_limit;
				if ( $order_meta_fraud_status ) {
					// Block this customer for future sessions as well.
					// And cancel the order.
					if ( false !== $customer && method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order, 'add', $context );
						WMFO_Blacklist_Handler::show_blocked_message();

						return false;

					}
				}

				// check in the DB
				if ( self::is_possible_fraud_attempts( $fraud_limit, $customer ) ) {
					WMFO_Blacklist_Handler::init( $customer, $order, 'add', $context );
					WMFO_Blacklist_Handler::show_blocked_message();
				}

			}
		}

		/**
		 * The product type blacklist enabled blacklisting only when at least one product in the order is of a specified type.
		 *
		 * @param int[] $product_items Product ids contained in an order to check against the product type blacklist.
		 *
		 * @return bool
		 */
		public static function check_products_in_product_type_blacklist( $product_items = array() ) {
			$blacklist_product_types = get_option( 'wmfo_black_list_product_types', array() );

			if ( empty( $blacklist_product_types ) ) {
				return false;
			}

			$blacklisted_product_type_found = false;

			foreach ( $product_items as $item ) {
				$product_obj = wc_get_product( $item );
				if ( ! ( $product_obj instanceof WC_Product ) ) {
					continue;
				}
				if ( in_array( $product_obj->get_type(), $blacklist_product_types, true ) ) {
					$blacklisted_product_type_found = true;
					break;
				}
			}

			return $blacklisted_product_type_found;
		}

		/**
		 * @param $customer_details
		 */
		protected static function save_fraud_attempt_record( $customer_details ) {
			$fraud_log_data = array(
				'full_name'        => $customer_details['full_name'],
				'billing_phone'    => $customer_details['billing_phone'],
				'ip'               => $customer_details['ip_address'],
				'billing_email'    => $customer_details['billing_email'],
				'billing_address'  => implode( ',', $customer_details['billing_address'] ),
				'shipping_address' => isset( $customer_details['shipping_address'] ) ? implode( ',', $customer_details['shipping_address'] ) : '',
				'payment_method'   => $customer_details['payment_method'],
				'timestamp'        => current_time( 'mysql' ),
			);

			$logs_handler = new WMFO_Fraud_Attempts_DB_Handler();
			$logs_handler->add_fraud_record( $fraud_log_data );
		}

		/**
		 * Check the previous fraud attempts from the DB
		 *
		 * @param $fraud_limit
		 * @param $customer
		 *
		 * @return bool
		 */
		protected static function is_possible_fraud_attempts( $fraud_limit, $customer ) {
			//Check in the DB table
			global $wpdb;
			$checkout_fields = WC()->checkout->get_checkout_fields();

			$where_query = "";
			$args = [];

			if(isset($customer['ip_address'])){
				$where_query .= "ip = %s";
				$args = [$customer['ip_address']];
			}

			if(isset($checkout_fields['billing'])) {
				if(isset($checkout_fields['billing']['billing_email'])
				&& $checkout_fields['billing']['billing_email']['required']) {
					$or_append = $where_query != "" ? " OR " : "";
					$where_query .= $or_append . "billing_email = %s";
					$args[] = $customer['billing_email'];
		 		}

				if(isset($checkout_fields['billing']['billing_phone'])
				&& $checkout_fields['billing']['billing_phone']['required']) {
					$or_append = $where_query != "" ? " OR " : "";
					$where_query .= $or_append . "billing_phone = %s";
					$args[] = $customer['billing_phone'];
				  }
			}

			if($where_query == ""){
				return false;
			}

			$matching_fraud_attempts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wmfo_fraud_attempts WHERE ".$where_query,
					$args
				),
				ARRAY_A );
		
			return count( $matching_fraud_attempts ) > (int) $fraud_limit;
		}
	}
}

WMFO_Track_Fraud_Attempts::instance();
