<?php
/*
Plugin Name:  Woo Manage Blacklisted Customers
Plugin URI:   https://github.com/prasidhda/woo-block-fraud-orders
Description:  WooCommerce plugin to block the fraud orders.
Version:      0.1
Author:       Prasidhda Malla
Author URI:   https://profiles.wordpress.org/prasidhda
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  woo-block-fraud-orders
Domain Path:  /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!defined('WMBC_PLUGIN_FILE')) {
    define('WMBC_PLUGIN_FILE', __FILE__);
}

if (!class_exists('Woo_Manage_Blacklisted_Customers')) {
    include_once dirname(__FILE__) . '/includes/cls-woo-manage-blacklisted-customers.php';
}

//Initialize the plugin
Woo_Manage_Blacklisted_Customers::instance();
