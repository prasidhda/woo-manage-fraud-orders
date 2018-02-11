<?php
/*
Plugin Name:  Woo Manage Blacklisted Customers
Plugin URI:   https://github.com/prasidhda/woo-block-fraud-orders
Description:  WooCommerce plugin to block the fraud orders.
Version:      0.3
Author:       Prasidhda Malla
Author URI:   https://profiles.wordpress.org/prasidhda
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  woo-manage-blacklisted-customers
Domain Path:  /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!defined('WMFO_PLUGIN_FILE')) {
    define('WMFO_PLUGIN_FILE', __FILE__);
}

if (!class_exists('Woo_Manage_Fraud_Orders')) {
    include_once dirname(__FILE__) . '/includes/class-woo-manage-fraud-orders.php';
}

//Initialize the plugin
Woo_Manage_Fraud_Orders::instance();
