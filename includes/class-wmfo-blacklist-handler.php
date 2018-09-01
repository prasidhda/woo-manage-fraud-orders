<?php
/**
 *
 *Handler class to update the blacklisted settings
 *Show the message in checkout page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WMFO_Blacklist_Handler' ) ) {
	class WMFO_Blacklist_Handler {

		public static function get_setting( $key, $default = '' ) {
			return get_option( $key ) ? get_option( $key ) : $default;
		}

		public static function get_blacklists() {
			return [
				'prev_black_list_ips'    => self::get_setting( 'wmfo_black_list_ips' ),
				'prev_black_list_phones' => self::get_setting( 'wmfo_black_list_phones' ),
				'prev_black_list_emails' => self::get_setting( 'wmfo_black_list_emails' ),
			];

		}

		public static function update_blacklist( $key, $pre_values, $to_add, $action = 'add' ) {
			if( $action == 'add' ){
				if ( $pre_values === FALSE || $pre_values == '' ) {
					$new_values = $to_add;
				} else {

					$new_values = ! in_array($to_add, explode( PHP_EOL, $pre_values )) ? $pre_values . PHP_EOL . $to_add : $pre_values;
				}
			}
			elseif( $action == 'remove' ){
				$in_array_value = explode( PHP_EOL, $pre_values ); 
				if( in_array( $to_add, $in_array_value ) ){
					$array_key = array_search( $to_add, $in_array_value ); 
					if( $array_key !== false ) {
						unset( $in_array_value[ $array_key ] );
					}
				}
				$new_values = implode( PHP_EOL, $in_array_value); 
			}
			
			update_option( $key, trim( $new_values ) );
		}

		public static function init( $customer = [], $order = NULL, $action='add' ) {
			$prev_blacklisted_data = self::get_blacklists();
			if ( empty( $customer ) || ! $customer ) {
				return FALSE;
			}

			self::update_blacklist( 'wmfo_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address'], $action );
			self::update_blacklist( 'wmfo_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone'], $action );
			self::update_blacklist( 'wmfo_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email'], $action );

			//handle the cancelation of order
			if ( NULL !== $order ) {
				self::cancel_order( $order );
			}

			return TRUE;
		}

		public static function cancel_order( $order ) {
			$order_note = apply_filters( 'wmfo_cancel_order_note', esc_html__( 'Order details blacklisted for future checkout.', 'woo-manage-fraud-orders' ), $order );

			//Set the order status to Canceled
			if ( ! $order->has_status( 'cancelled' ) ) {
				$order->update_status( 'cancelled', $order_note );
			}
		}

		public static function show_blocked_message() {
			$default_notice          = esc_html__( 'Sorry, You are blocked from checking out.', 'woo-manage-fraud-orders' );
			$wmfo_black_list_message = self::get_setting( 'wmfo_black_list_message', $default_notice );

			//with some reason, get_option with default value not working

			if ( ! wc_has_notice( $wmfo_black_list_message ) ) {
				wc_add_notice( $wmfo_black_list_message, 'error' );
			}
		}

		public static function is_blacklisted( $customer_details ){
			//Check for ony by one, return TRUE as soon as first matching 
			$blacklisted_ips = self::get_setting( 'wmfo_black_list_ips' ); 
			$blacklisted_emails = self::get_setting( 'wmfo_black_list_emails' ); 
			$blacklisted_phones = self::get_setting( 'wmfo_black_list_phones' ); 

			if( in_array( $customer_details['ip_address'], array_map('trim', explode(PHP_EOL, $blacklisted_ips ) ) ) ){
				return true;
			}
			elseif( in_array( $customer_details['billing_email'], array_map('trim', explode( PHP_EOL, $blacklisted_emails ) ) ) ){
				return true;
			}
			elseif( in_array( $customer_details['billing_phone'], array_map('trim', explode( PHP_EOL, $blacklisted_phones ) ) ) ){
				return true;
			}

			return false; 
		}
	}
}