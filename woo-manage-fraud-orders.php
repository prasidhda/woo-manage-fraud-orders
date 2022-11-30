<?php
/**
 * Plugin Name:  Woo Manage Fraud Orders
 * Plugin URI:   https://prasidhda.com.np/how-to-blacklist-customers-from-placing-order-in-woocommerce/
 * Description:  WooCommerce plugin to manage the fraud orders by blacklisting the customer's details.
 * Version:      2.6.1
 * Author:       Prasidhda Malla
 * Author URI:   https://prasidhda.com.np/
 * License:      GPLv2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  woo-manage-fraud-orders
 * WC requires at least: 2.6
 * WC tested up to: 7.1.0
 *
 * @package woo-manage-fraud-orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! defined( 'WMFO_PLUGIN_FILE' ) ) {
	define( 'WMFO_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'Woo_Manage_Fraud_Orders' ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-woo-manage-fraud-orders.php';
}

// Initialize the plugin.
Woo_Manage_Fraud_Orders::instance();
