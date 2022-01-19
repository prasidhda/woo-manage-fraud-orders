<?php
/**
 * Class to handle the Bulk blacklisting of orders. i.e. on the WP_List_Table edit-orders screen.
 *
 * @package woo-manage-fraud-orders
 */

if ( ! class_exists( 'WMFO_Bulk_Blacklist' ) ) {

	/**
	 * Class WMFO_Bulk_Blacklist
	 */
	class WMFO_Bulk_Blacklist {

		/**
		 * WMFO_Bulk_Blacklist constructor.
		 */
		public function __construct() {
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_action' ), 99, 1 );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_blacklisting' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'print_admin_notice' ) );

		}

		/**
		 * Add the "Blacklist Customer" action to the bulk actions drop-down above the shop orders list table.
		 *
		 * @hooked bulk_actions-edit-shop_order
		 *
		 * @param array<string, string> $bulk_actions The bulk actions for the shop order list table.
		 *
		 * @return array<string, string>
		 * @see WP_List_Table::bulk_actions()
		 *
		 */
		public function register_bulk_action( $bulk_actions ) {
			$bulk_actions['blacklist-customer'] = __( 'Blacklist Customer', 'woo-manage-fraud-orders' );

			return $bulk_actions;
		}

		/**
		 * Execute the bulk blacklisting and return the URL to redirect to.
		 *
		 * @hooked handle_bulk_actions-edit-shop_order
		 *
		 * @param string $redirect_to The previously set/default redirect URL.
		 * @param string $action The current action.
		 * @param int[] $post_ids The selected post ids (order ids).
		 *
		 * @return string The URL to redirect to.
		 * @throws Exception
		 * @see WP_List_Table::current_action()
		 *
		 */
		public function handle_bulk_blacklisting( $redirect_to, $action, $post_ids ) {
			if ( 'blacklist-customer' !== $action ) {

                return esc_url_raw( $redirect_to );
			}
			foreach ( $post_ids as $post_id ) {
				$order = wc_get_order( $post_id );

				if ( ! ( $order instanceof WC_Order ) ) {
					continue;
				}

				// Get customer's IP address, billing phone and Email Address.
				$customer = wmfo_get_customer_details_of_order( $order );
				// update the blacklists.
				if ( method_exists( 'WMFO_Blacklist_Handler', 'init' ) ) {
					WMFO_Blacklist_Handler::init( $customer, $order, 'add', 'back' );
				}
			}
			$redirect_to = wp_nonce_url(
				add_query_arg(
					array(
						'bulk_action' => $action,
						'changed'     => count( $post_ids ),
						'ids'         => join( ',', $post_ids ),
					),
					$redirect_to
				),
				'handle_bulk_blacklisting'
			);

			wp_safe_redirect( $redirect_to );
			exit();
		}

		/**
		 * If the bulk blacklisting action was just run, show an admin notice detailing the number of orders included.
		 *
		 * @see WMFO_Bulk_Blacklist::handle_bulk_blacklisting()
		 *
		 * @hooked admin_notices
		 */
		public function print_admin_notice() {
			global $post_type, $pagenow;
			// Bail out if not on shop order list page.
			if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) { // WPCS: input var ok, CSRF ok.
				return;
			}

			if ( ! wp_verify_nonce( 'handle_bulk_blacklisting' ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['bulk_action'] ) ) {
				return;
			}

			if ( 'blacklist-customer' !== $_REQUEST['bulk_action'] ) {
				return;
			}

			$orders_count = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
			?>
            <div id="message" class="updated fade">
				<?php
				echo esc_html(
					sprintf(
					// translators: a number of orders.
						_n(
							'%d order has been affected by bulk blacklisting.',
							'%d orders have been affected by bulk blacklisting.',
							$orders_count,
							'woo-manage-fraud-orders'
						),
						$orders_count
					)
				);
				?>
            </div>
			<?php
		}
	}
}
new WMFO_Bulk_Blacklist();
