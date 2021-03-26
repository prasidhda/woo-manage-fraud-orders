<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WMFO_Order_MetaBox {
	public function __construct() {
		// Meta-box to order edit page
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_meta_box' ), 99, 1 );
		add_action( 'save_post', array( $this, 'save_order_meta_box_data' ), 99, 1 );
	}

	public function add_meta_box( $post ) {
		$order = wc_get_order( $post->ID );

		if ( $order->get_status() !== 'pending' ) {
			return;
		}
		add_meta_box(
			'wmfo-order-metabox',
			__( 'WMFO', 'woo-manage-fraud-orders' ),
			array( $this, 'actions_meta_box' ),
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * @param $post
	 */
	public function actions_meta_box( $post ) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'wmfo_skip_blacklisting_nonce', 'wmfo_skip_blacklist_nonce' );

		$value   = get_post_meta( $post->ID, 'wmfo_skip_blacklist', true );
		$checked = $value === 'yes' ? 'checked' : '';
		echo '<label for="wmfo_skip_blacklist">' . __( 'Check this to bypass this order payment from blacklisting.' ) . '</label>';
		echo '<input type="checkbox" name="wmfo_skip_blacklist" id="wmfo_skip_blacklist" style="margin: 4px 12px 0;" ' . $checked . ' value="yes" />';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id
	 */
	function save_order_meta_box_data( int $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['wmfo_skip_blacklist_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wmfo_skip_blacklist_nonce'], 'wmfo_skip_blacklisting_nonce' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'shop_order' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Sanitize user input.
		$wmfo_skip_blacklist = sanitize_text_field( $_POST['wmfo_skip_blacklist'] );
		// Update the meta field in the database.
		update_post_meta( $post_id, 'wmfo_skip_blacklist', $wmfo_skip_blacklist );
	}
}

new WMFO_Order_MetaBox();
