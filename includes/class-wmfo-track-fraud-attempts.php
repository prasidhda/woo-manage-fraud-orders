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
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'manage_blacklisted_customers_checkout' ), 10, 2 );

			add_action( 'woocommerce_before_pay_action', array( $this, 'manage_blacklisted_customers_order_pay' ), 99, 1 );

			add_action( 'woocommerce_after_pay_action', array( $this, 'manage_multiple_failed_attempts_order_pay' ), 99, 1 );

			// Not part of WooCommerce core.
			add_action( 'woocommerce_api_wc_gateway_eway_payment_failed', array( $this, 'manage_multiple_failed_attempts_eway' ), 100, 4 );

			add_action( 'woocommerce_checkout_order_processed', array( $this, 'manage_multiple_failed_attempts_checkout' ), 100, 3 );

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
		 * @see WC_Checkout::validate_checkout()
		 *
		 * @see WC_Checkout::get_posted_data()
		 *
		 * @param array<string, mixed> $data An array of posted data.
		 * @param WP_Error             $errors A WP Error object to add errors to.
		 */
		public static function manage_blacklisted_customers_checkout( $data, $errors ) {
			// Check if there are any other errors first.
			// If there are, return.
			if ( ! empty( $errors->errors ) ) {
				return;
			}

			// Woo/Payment method saves the payment method validation errors in session.
			// If there such errors, skip.
			if ( ! isset( WC()->session->reload_checkout ) ) {
				$error_notices = wc_get_notices( 'error' );
			}

			if ( ! empty( $error_notices ) ) {
				return;
			}

			// This is check for the woocommerce subscription.
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

			$first_name                    = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
			$last_name                     = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';
			$customer_details['full_name'] = $first_name . ' ' . $last_name;

			$customer_details['billing_email'] = isset( $data['billing_email'] ) ? $data['billing_email'] : '';
			$customer_details['billing_phone'] = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';

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
		 * @see WC_Form_Handler::pay_action()
		 *
		 * @param WC_Order $order The WooCommerce order object.
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
		 * @see wmfo_get_customer_details_of_order()
		 *
		 * @param array<string,string> $customer_details The customer details that might be blacklisted.
		 * @param int[]                $product_items The product ids in the order.
		 * @param ?WC_Order            $order The WooCommerce order.
		 */
		public static function manage_blacklisted_customers( $customer_details, $product_items, $order = null ) {
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

			$domain                  = substr( $customer_details['billing_email'], strpos( $customer_details['billing_email'], '@' ) + 1 );
			$allow_blacklist_by_name = get_option( 'wmfo_allow_blacklist_by_name', 'no' );
			$prev_black_list_names   = get_option( 'wmfo_black_list_names', '' );

			$prev_black_list_ips           = get_option( 'wmfo_black_list_ips', '' );
			$prev_black_list_phones        = get_option( 'wmfo_black_list_phones', '' );
			$prev_black_list_emails        = get_option( 'wmfo_black_list_emails', '' );
			$prev_black_list_email_domains = get_option( 'wmfo_black_list_email_domains', '' );

			// Block this checkout if this customers details are already blacklisted.
			if ( $customer_details['full_name'] && 'yes' === $allow_blacklist_by_name && $prev_black_list_names && in_array( $customer_details['full_name'], explode( PHP_EOL, $prev_black_list_names ), true ) ||
				$customer_details['ip_address'] && $prev_black_list_ips && in_array( $customer_details['ip_address'], explode( PHP_EOL, $prev_black_list_ips ), true ) ||
				$prev_black_list_phones && $customer_details['billing_phone'] && in_array( $customer_details['billing_phone'], explode( PHP_EOL, $prev_black_list_phones ), true ) ||
				$customer_details['billing_email'] && $prev_black_list_emails && in_array( $customer_details['billing_email'], explode( PHP_EOL, $prev_black_list_emails ), true ) ||
				$domain && $prev_black_list_email_domains && in_array( $domain, explode( PHP_EOL, $prev_black_list_email_domains ), true )
			) {
				if ( method_exists( 'WMFO_Blacklist_Handler', 'show_blocked_message' ) ) {
					WMFO_Blacklist_Handler::show_blocked_message();
				}

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
							WMFO_Blacklist_Handler::show_blocked_message();
						}
						break;
					}
				}
			}
		}

		/**
		 *
		 * @hooked woocommerce_checkout_order_processed
		 * @see WC_Checkout::process_checkout()
		 *
		 * @see WC_Checkout::get_posted_data()
		 *
		 * @param int                  $_order_id The order id.
		 * @param array<string, mixed> $_posted_data The checkout data.
		 * @param WC_Order             $order The WooCommerce order.
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_checkout( $_order_id, $_posted_data, $order ) {
			self::manage_multiple_failed_attempts( $order );
		}

		/**
		 *
		 * @hooked woocommerce_after_pay_action
		 * @see WC_Form_Handler::pay_action()
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_order_pay( $order ) {
			self::manage_multiple_failed_attempts( $order, 'order-pay' );
		}

		/**
		 * Triggered when a payment with the gateway fails.
		 *
		 * @param WC_Order        $order The order whose payment failed.
		 * @param stdClass        $_result The result from the API call.
		 * @param string          $_error The error message.
		 * @param WC_Gateway_EWAY $_gateway The instance of the gateway.
		 */
		public static function manage_multiple_failed_attempts_eway( $order, $_result, $_error, $_gateway ) {

			self::manage_multiple_failed_attempts( $order, 'order-pay-eway' );
		}

		/**
		 *
		 * 'manage_multiple_failed_attempts' will only track the multiple failed attempts after the creating of failed
		 * order by customer, This is helpful when customer enter the correct format of the data but payment gateway
		 * couldn't authorize the payment. Typical example will be Electronic check, CC processing.
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 * @param string   $context "front"|"order-pay"|"order-pay-eway".
		 *
		 * @throws Exception
		 */
		protected static function manage_multiple_failed_attempts( $order, $context = 'front' ) {
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
				return;
			}

			if ( $order->get_status() === 'failed' ) {
				// MD5 the name of the cookie for fraud_attempts.
				$fraud_attempts_md5 = md5( 'fraud_attempts' );
				$fraud_attempts     = ! isset( $_COOKIE[ $fraud_attempts_md5 ] ) || empty( $_COOKIE[ $fraud_attempts_md5 ] ) ? 1 : intval( wp_unslash( $_COOKIE[ $fraud_attempts_md5 ] ) );

				$cookie_value = (int) $fraud_attempts + 1;
				setcookie( $fraud_attempts_md5, "{$cookie_value}", time() + ( 60 * 60 * 30 ), '/' ); // 30 days
				// Get the allowed failed order limit, default to 5.
				$fraud_limit = get_option( 'wmfo_black_list_allowed_fraud_attempts', 5 );

				if ( (int) $fraud_attempts >= (int) $fraud_limit ) {
					// Block this customer for future sessions as well.
					// And cancel the order.
					$customer = wmfo_get_customer_details_of_order( $order );
					if ( false !== $customer && method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order, 'add', $context );
					}
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
		public static function check_products_in_product_type_blacklist( $product_items = array() ): bool {
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
	}
}

WMFO_Track_Fraud_Attempts::instance();
