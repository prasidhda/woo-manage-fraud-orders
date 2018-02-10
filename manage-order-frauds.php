<?php
/*====================================================
=            Managing Order Fraud Section            =
====================================================*/
// $test = new SV_WC_Payment_Gateway_Payment_Form();
// remove_action( 'wc_first_data_payeezy_gateway_echeck_payment_form_end', array('SV_WC_Payment_Gateway_Payment_Form', 'render_js'), 5 );

include_once get_template_directory() . '/inc/fraud-orders-manager/functions.php'; 

if( is_admin() ){
	include_once get_template_directory() . '/inc/fraud-orders-manager/admin/woo-blacklists-settings.php'; 
	include_once get_template_directory() . '/inc/fraud-orders-manager/admin/woo-order-blacklists.php'; 
}

/*----------  Remove the JS validation for eCheck fields  ----------*/
function boldpreciousmetals_remove_js_echeck_validation( $args, $object ){
	return array();
}

add_filter( 'wc_first_data_payeezy_gateway_echeck_payment_form_js_args', 'boldpreciousmetals_remove_js_echeck_validation', 999, 2 );
/**
 *
 * Main function to manage the blacklisted customers
 * Block the orders depending upon the blacklisted order and customoer's behavior
 */

function boldpreciousmetals_manage_multiple_failed_attempt( $data, $errors ){
	//Check if there are any other erroes first
	//If there are, return
	if( ! empty( $errors->errors ) )
		return;

	//check if there are error messages saved in session
	//Woo/Payment method saves the payment method validation errors in session
	//If there such errors, skip
	// wc_print_notices();
	$all_notices  = WC()->session->get( 'wc_notices', array() );
	// var_dump( $all_notices ); 
	// var_dump( WC()->session->get( 'wc_notices', array() ));
	if ( ! isset( WC()->session->reload_checkout ) ) {
		$error_notices = wc_get_notices('error'); 
	}

	if( ! empty( $error_notices ) )
		return;


	$prev_black_list_ips =  get_option( 'bold_black_list_ips', true ); 
	$prev_black_list_phones =  get_option( 'bold_black_list_phones', true ); 
	$prev_black_list_emails = get_option( 'bold_black_list_emails', true  ); 

	$billing_email = $_POST['billing_email'];
	$billing_phone = $_POST['billing_phone'];
	$ip_address = WC_Geolocation::get_ip_address(); 

	//Block this checkout if this customers details are already blacklisted
	if( substr_count( $prev_black_list_ips, $ip_address ) > 0 ||
		substr_count( $prev_black_list_phones, $billing_phone ) > 0 ||
		substr_count( $prev_black_list_emails, $billing_email ) > 0 ){
		// var_dump($prev_black_list_ips);
		// var_dump($prev_black_list_phones);
		// var_dump($prev_black_list_emails);
		boldpreciousmetals_show_blocked_message();
		return;
	}

	//check for multiple fraud attempts
	// $fraud_attempts_md5 = md5('fraud_attempts'); 
	// $prev_fraud_attempts = $_COOKIE[$fraud_attempts_md5];
	// $fraud_limit = 	get_option( 'bold_black_list_allowed_fraud_attemps' ) != '' ?
	// 				get_option( 'bold_black_list_allowed_fraud_attemps' ) : 
	// 				3;
	
	// if( (int) $prev_fraud_attempts >= $fraud_limit ){
	// 	boldpreciousmetals_show_blocked_message();

	// 	//Block this customer for future sessions as well
	// 	boldpreciousmetals_update_blacklist_customers(
	// 		array(
	// 			'ip_address' => $ip_address, 
	// 			'billing_phone' => $billing_phone, 
	// 			'billing_email' => $billing_email
	// 		)
	// 	);
	// }
}
//This hook will be helpful for auto detecting multiple failed attempts
add_action( 'woocommerce_after_checkout_validation','boldpreciousmetals_manage_multiple_failed_attempt', 10, 2 );


/**
 *
 * Function to track the number of fraud attempts
 * using browser cookie 
 */


function boldpreciousmetals_set_fraud_attempts_cookie( $order_id, $posted_data, $order ){
	if( $order->get_status() === 'failed' ){
		//md5 the name of the cookie for fraud_attempts
		$fraud_attempts_md5 = md5('fraud_attempts'); 
		$fraud_attempts = ( ! isset( $_COOKIE[$fraud_attempts_md5] ) || NULL === $_COOKIE[$fraud_attempts_md5] ) ? 
							0 :
							$_COOKIE[$fraud_attempts_md5];
		
		$cookie_value = (int)$fraud_attempts + 1;
		setcookie( $fraud_attempts_md5, $cookie_value, time() + ( 60 * 60 ), "/"); // 86400 = 1 day

		$fraud_limit = 	get_option( 'bold_black_list_allowed_fraud_attemps' ) != '' ?
						get_option( 'bold_black_list_allowed_fraud_attemps' ) : 
						3;
	
		
		if( (int) $fraud_attempts >= $fraud_limit ){
			boldpreciousmetals_show_blocked_message();

			//Block this customer for future sessions as well
			$customer = boldpreciousmetals_get_customer_details_of_order( $order ); 
			if( boldpreciousmetals_update_blacklist_customers( $customer ) ){
				$order_note = __('Order details blacklisted for future checkout.', 'boldpreciosumetals'); 
				//Set the order status to Canceled
				if( ! $order->has_status('cancelled') ){
					$order->update_status('cancelled', $order_note) ; 
				}
			}
		}
	}
}
add_action( 'woocommerce_checkout_order_processed', 'boldpreciousmetals_set_fraud_attempts_cookie', 100, 3 );

// function boldpreciousmetals_add_metabox_to_order_edit_page( $post_type, $post ){
// 	add_meta_box( 
//         'bold-mark-as-fraud', 
//         __( 'Mark as Fraud Order' ), 
//         'boldpreciousmetals_add_checkbox_to_mark_as_fraud_order', 
//         'shop_order', 
//         'side', 
//         'default' 
//     );
// }

// function boldpreciousmetals_add_checkbox_to_mark_as_fraud_order(){
// 	wp_nonce_field( 'mark_as_fraud', 'mark_as_fraud' );
// 	echo '<label for="mark_as_fraud">Mark as Fraud</label>';
// 	echo '<input type="checkbox" name="mark_as_fraud" value="no">';
// }
// add_action( 'add_meta_boxes', 'boldpreciousmetals_add_metabox_to_order_edit_page' , 100, 2 );


// function boldpreciousmetals_save_order_as_fruad_order( $post_id ){
// 	// Check if our nonce is set.
// 	if ( ! isset( $_POST['mark_as_fraud'] ) ) {
// 		return;
// 	}
// 	// Verify that the nonce is valid.
// 	if ( ! wp_verify_nonce( $_POST['mark_as_fraud'], 'mark_as_fraud' ) ) {
// 		return;
// 	}

// 	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
// 	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
// 		return;
// 	}
// 	if ( ! current_user_can( 'edit_page', $post_id ) ) {
// 			return;
// 		}
// 	/* Check the user's permissions. */
// 	if ( isset( $_POST['post_type'] ) && 'shop_order' == $_POST['post_type'] ) {
// 		if ( isset( $_POST['mark_as_fraud'] ) ) {

// 			// Sanitize user input.
// 			$_data = sanitize_text_field( $_POST['mark_as_fraud'] );
// 			if($_data === 'yes'){
				
// 			}
// 			elseif( $_data === 'no' ){

// 			}
// 		}			
// 	}
// }
// add_action('save_post', 'boldpreciousmetals_save_order_as_fruad_order', 100 , 1);