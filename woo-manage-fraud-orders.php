<?php
/*
Plugin Name:  Woo Manage Fraud Orders
Plugin URI:   https://github.com/prasidhda/woo-manage-fraud-orders
Description:  WooCommerce plugin to manage the fraud orders by blackilisting the customer's details.
Version:      1.3.0
Author:       Prasidhda Malla
Author URI:   https://profiles.wordpress.org/prasidhda
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  woo-manage-fraud-orders
Domain Path:  /languages
WC requires at least: 2.6
WC tested up to: 3.4.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! defined( 'WMFO_PLUGIN_FILE' ) ) {
	define( 'WMFO_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'Woo_Manage_Fraud_Orders' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-woo-manage-fraud-orders.php';
}

//Initialize the plugin
Woo_Manage_Fraud_Orders::instance();
