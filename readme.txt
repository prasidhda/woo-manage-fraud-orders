=== Woo Manage Fraud Orders ===
Contributors: prasidhda
Tags: WooCommerce, Blacklist customers, Anti Fraud orders, Tracking fraud attempts
Requires at least: 4.6
Tested up to: 4.9.6
Stable tag: 1.2.2
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin will add the functionality to black list the customer from checking out.

== Description ==
An extension for the WooCommerce that allows for the blacklisting customer details (Billing Phone, Billing Email and IP address). Admin can blacklist the customer information manually through the setting or can edit the order page and black the customer's details into blacklist.

Another key features of this plugin is that it tracks the number of fraud order attempts for payment gateways like Credit Card, Electronic Check and blacklist the customer if the number of fraud attempts exceeds the limit set in the backend setting. When customer knows the correct patterns for payment fields of electronic check or credit card, they may try to create to multiple failed order. This plugin will track those attempts, cancel the order automatically and black the customer automatically from checking out in future.  

== Installation ==

1. Download and Upload the plugin folder 'woo-manage-blacklisted-customers'  to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce>Setting>Blacklisted Customers for the plugin setting and update the settings or you can go to the order edit page and choose the action "Blacklist order" for adding customer details into blacklists. 
4. Then, you are done. It will block all the blacklisted customers from checking out. 


== Frequently Asked Questions ==

= Can i add zipcode or city Address in the blacklist ? =
No. Only Billing phone, email and IP address. Planning to add those features in next update. 

= How do we blacklist the customer's detail ? =
There are three ways to black list the customer. First, open the order edit page, choose the option "Blacklist Customer" in the "order actions" section and update the order page and it will add the customer's details into blacklist. Second, navigate to Woocommerce > Settings > Blacklisted Customers tab. You will see three different textarea fields for "Email" "Phones" & "IP Address". You can manually edit those setting to update the blacklisted customers. 

= Is there auto blacklisting system as well ? =
Yes, there is. But this auto system will be in action only for those payment gateways which authorizes the payment details and charge instantly (Electronic Check, Credit Card) etc.  

= What is the process for automatic blacklisting system ? =
Let us take an example of Electronic check payment gateway. When customer successfully validates the payment fields like "Route Number", "Account Number" & "Check Number" fields, those values will be sent for authorizing. If Bank couldn't authorize those customer details, the woo commerce will mark the order as "Failed". Then, same customer may try to create the multiple number of "Failed" orders. This plugin will track that behavior and black list the customer from future checkout. 


== Screenshots ==

1. Blacklisted Customers Setting & Listing
2. Blocking message in checkout page
3. Blacklisting customer details via order edit page

== Changelog ==
= 1.2.2 =
* Translation text domain added.

= 1.2 =
* Bulk Blacklisting options added in orders listing page.

= 1.0.3 =
* Duplication of the blacklisted emails, phones and IPs removed.

= 1.0.2 =
* Minor bug fixed.

= 1.0.1 =
* Dependency check added.  

= 1.0.0 =
* First Version
