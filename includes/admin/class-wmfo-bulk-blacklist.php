<?php
/**
 * Class to handle the Bulk black-listing of of order
 */
if (!class_exists('WMFO_Bulk_Blacklist')) {
    class WMFO_Bulk_Blacklist {

        public function __construct() {
            add_filter('bulk_actions-edit-shop_order', array(
                $this,
                'register_bulk_action',
            ), 99, 1);
            add_filter('handle_bulk_actions-edit-shop_order', array(
                $this,
                'handle_bulk_blacklisting',
            ), 10, 3);
            add_action('admin_notices', array($this, 'admin_notice'));

        }

        public function register_bulk_action($bulk_actions) {
            $bulk_actions['blacklist-customer'] = __('Blacklist Customer', 'woo-manage-fraud-orders');

            return $bulk_actions;
        }

        public function handle_bulk_blacklisting($redirect_to, $action, $post_ids) {
            if ('blacklist-customer' !== $action) {
                return $redirect_to;
            }
            foreach ($post_ids as $post_id) {
                $order = wc_get_order($post_id);
                // Get customer's IP address, billing phone and Email Address
                $customer = wmfo_get_customer_details_of_order($order);
                //update the blacklists
                if (method_exists('WMFO_Blacklist_Handler', 'init')) {
                    WMFO_Blacklist_Handler::init($customer, $order);
                }
            }
            $redirect_to = add_query_arg(array(
                'bulk_action' => $action,
                'changed' => count($post_ids),
                'ids' => join(',', $post_ids),
            ), $redirect_to);

            return $redirect_to;
        }

        public function admin_notice() {
            global $post_type, $pagenow;
            // Bail out if not on shop order list page.
            if ('edit.php' !== $pagenow || 'shop_order' !== $post_type) { // WPCS: input var ok, CSRF ok.
                return;
            }

            if( !isset($_REQUEST['bulk_action'])) {
                return;
            }

            if('blacklist-customer' != $_REQUEST['bulk_action']) {
                return;
            }

            $orders_count = isset($_REQUEST['changed']) ? absint($_REQUEST['changed']) : 0;
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
