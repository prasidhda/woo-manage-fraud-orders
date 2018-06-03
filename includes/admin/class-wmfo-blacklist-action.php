<?php
/**
 * Class to handle the updating of blacklists while editing the order page
 */

if ( ! class_exists( 'WMFO_Order_Actions' ) ) {
	class WMFO_Order_Actions {

		public static $_instance;

		public function __construct() {
			/*----------  Hooks Provided by the Woo Commerce   ----------*/
			/**
			 * woocommerce_order_actions => To add/remove the order actions
			 * We are adding the new action , "Blacklist order"
			 */
			add_filter( 'woocommerce_order_actions', [
				$this,
				'WMFO_add_new_order_action',
			], 99, 1 );
			/**
			 *
			 * 'woocommerce_process_shop_order_meta' => Handling the order action
			 * We are blocking the customer email, phone and IP address of current order
			 */
			add_action( 'woocommerce_process_shop_order_meta', [
				$this,
				'wpmbc_update_blacklists',
			], 60, 2 );
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public static function WMFO_add_new_order_action( $order_actions ) {
			$order_actions['black_list_order'] = __( 'Blacklist order', 'woo_blacklist' );

			return $order_actions;
		}

		public static function wpmbc_update_blacklists( $post_id, $post ) {
			$order = wc_get_order( $post_id );

			// Handle button actions
			if ( ! empty( $_POST['wc_order_action'] ) ) {
				$action = wc_clean( $_POST['wc_order_action'] );

				if ( 'black_list_order' === $action ) {
					// Get customer's IP address, billing phone and Email Address
					$customer = wmfo_get_customer_details_of_order( $order );
					//update the blacklists
					if ( method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
						WMFO_Blacklist_Handler::init( $customer, $order );
					}
				}
			}
		}
	}
}

WMFO_Order_Actions::instance();
