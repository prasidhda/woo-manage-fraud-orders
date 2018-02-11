=== Woo Manage Fraud Orders ===
Contributors: prasidhda
Tags: WooCommerce, Blacklist customers, fraud orders
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 0.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin will add the functionality to black list the customer from checking out.

== Description ==
An extension for the WooCommerce that allows for the blacklisting customer details (Billing Phone, Billing Email and IP address). Admin can blacklist the customer information manually through the setting or can edit the order page and black the customer's details into blacklist.

This plugin also allows the tracking of customer's failed order attempts in checkout page and prevent them from checking out and subsequently adding the customer details to black list. 

== Installation ==

1. Download and Upload the plugin folder 'woo-manage-blacklisted-customers'  to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce>Setting>Blacklisted Customers for the plugin setting and update the settings or you can go to the order edit page and choose the action "Blacklist order" for adding customer details into blacklists. 
4. Then, you are done. It will block all the blacklisted customers from checking out. 


== Frequently Asked Questions ==

= Can i add zipcode or city adrees in the blacklist> =
No. Only Billing phone, email and IP address. Planning to add those features in next update. 

== Screenshots ==

1. Blacklisted Customers Setting & Listing
2. Blocking message in checkout page

== Changelog ==
= 0.3 =
* Patch to the intial version, plus screenshots added, managed stable version. 

= 0.1 =
* First Version
