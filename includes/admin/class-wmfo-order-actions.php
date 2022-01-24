<?php
/**
 * Class to handle the updating of blacklists while editing the order page
 *
 * @package woo-manage-fraud-orders
 */

if ( ! class_exists( 'WMFO_Order_Actions' ) ) {

	/**
	 * Class WMFO_Order_Actions
	 */
	class WMFO_Order_Actions {

		/**
		 * The stored singleton.
		 *
		 * @var ?WMFO_Order_Actions $instance
		 */
		protected static $instance = null;

		/**
		 * WMFO_Order_Actions constructor.
		 */
		protected function __construct() {

			add_filter( 'woocommerce_order_actions', array( $this, 'add_new_order_action' ), 99, 1 );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'update_blacklist' ), 60, 2 );
		}

		/**
		 * Get an instance of the class.
		 *
		 * @return WMFO_Order_Actions
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add an order action. i.e. on the single order edit screen beside the "update" button.
		 *
		 * @hooked woocommerce_order_actions
		 * @see WC_Meta_Box_Order_Actions::output()
		 *
		 * @param array<string, string> $order_actions The existing order actions.
		 *
		 * @return array<string, string>
		 */
		public static function add_new_order_action( $order_actions ) {
			// Show this only if customer details of this order is in blacklist.

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : null;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : null;

			if ( ! empty( $action ) && ! empty( $order_id ) ) {
				$order = wc_get_order( $order_id );

				if ( ! ( $order instanceof WC_Order ) ) {
					return $order_actions;
				}

				if ( 'shop_order' !== $order->get_type() ) {
					return $order_actions;
				}

				$order_actions['black_list_order'] = __( 'Blacklist order', 'woo-manage-fraud-orders' );
				// Check if the order details of this current order is in blacklist.
				$customer = wmfo_get_customer_details_of_order( $order );
				if ( false !== $customer && WMFO_Blacklist_Handler::is_blacklisted( $customer ) ) {
					$order_actions['remove_from_black_list'] = __( 'Remove from Blacklist', 'woo-manage-fraud-orders' );
				}
			}

			return $order_actions;
		}

		/**
		 * Execute the order action (Add|Remove from blacklist).
		 *
		 * @hooked woocommerce_process_shop_order_meta
		 * @see WC_Admin_Meta_Boxes::save_meta_boxes()
		 * @see save_post
		 *
		 * @param int     $post_id The post id, i.e. the order id, being saved.
		 * @param WP_Post $post The WordPress post being saved.
		 *
		 * @throws Exception
		 */
		public static function update_blacklist( $post_id, $post ) {
			$order = wc_get_order( $post_id );

			if ( ! ( $order instanceof WC_Order ) ) {
				return;
			}

            // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = isset( $_POST['wc_order_action'] ) ? sanitize_text_field( wp_unslash( $_POST['wc_order_action'] ) ) : null;
			if ( ! empty( $action ) ) {
				// Get customer's IP address, billing phone and Email Address.
				$customer = wmfo_get_customer_details_of_order( $order );
				// Add the customer details to Blacklist.
				if ( 'black_list_order' === $action ) {
					// update the blacklists.
					if ( method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order, 'add', 'back' );
					}
				} elseif ( 'remove_from_black_list' === $action ) {
					// Remove the customer details from blacklist.
					// update the blacklists.
					if ( method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order, 'remove', 'back' );
					}
				}
			}
		}
	}
}

WMFO_Order_Actions::instance();
