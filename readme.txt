=== Woo Manage Fraud Orders ===
Contributors: prasidhda, sranzan, BrianHenryIE
Tags: Blacklist customers, Anti Fraud orders, Tracking fraud attempts, Prevent fake orders, Blacklist fraud customers
Requires at least: 4.6
Tested up to: 5.7
Stable tag: 2.0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin will add the functionality to blacklist the customer from checking out.

== Description ==
An extension for the WooCommerce that allows for the blacklisting customer details (Billing Phone, Billing Email and IP address). Admin can blacklist the customer information manually through the setting or can edit the order page and black the customer's details into blacklist.

Another key features of this plugin is that it tracks the number of fraud order attempts for payment gateways like Credit Card, Electronic Check and blacklist the customer if the number of fraud attempts exceeds the limit set in the backend setting. When customer knows the correct patterns for payment fields of electronic check or credit card, they may try to create to multiple failed order. This plugin will track those attempts, cancel the order automatically and black the customer automatically from checking out in future.

Blacklist the customer, <a href="https://prasidhda.com.np/how-to-blacklist-customers-from-placing-order-in-woocommerce/" target="_blank">More details here</a>

== Installation ==

1. Download and Upload the plugin folder 'woo-manage-fraud-orders'  to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce>Setting>Blacklisted Customers for the plugin setting and update the settings or you can go to the order edit page and choose the action "Blacklist order" for adding customer details into blacklists.
4. Then, you are done. It will block all the blacklisted customers from checking out.


== Frequently Asked Questions ==

= Can i add zipcode or city Address in the blacklist ? =
No. Only Billing phone, email and IP address. Planning to add those features in next update.

= How do we blacklist the customer's detail ? =
There are three ways to blacklist the customer. First, open the order edit page, choose the option "Blacklist Customer" in the "order actions" section and update the order page and it will add the customer's details into blacklist. Second, navigate to Woocommerce > Settings > Blacklisted Customers tab. You will see three different textarea fields for "Email" "Phones" & "IP Address". You can manually edit those setting to update the blacklisted customers.

= Is there auto blacklisting system as well ? =
Yes, there is. But this auto system will be in action only for those payment gateways which authorizes the payment details and charge instantly (Electronic Check, Credit Card) etc.

= What is the process for automatic blacklisting system ? =
Let us take an example of Electronic check payment gateway. When customer successfully validates the payment fields like "Route Number", "Account Number" & "Check Number" fields, those values will be sent for authorizing. If Bank couldn't authorize those customer details, the woo commerce will mark the order as "Failed". Then, same customer may try to create the multiple number of "Failed" orders. This plugin will track that behavior and blacklist the customer from future checkout.

= Can i remove the customer details from blacklist ? =
Yes, absolutely. You can either edit the setting option in "Woocommerce > Setting > Blacklisted Customers" or you can do it from the order edit page under "Order Actions" option.

= Can i prevent the customer based on their previous order status ? =
Yes, absolutely. You can choose the multiple orders statuses in setting and this is completely compatible with <a href="https://woocommerce.com/products/woocommerce-order-status-manager/" target="_blank">WooCommerce Order Status Manager</a>.


== Screenshots ==

1. Blacklisted Customers Setting & Listing
2. Blocking message in checkout page
3. Blacklisting customer details via order edit page

== Changelog ==
= 2.0.2 =
* fix: order get_type() on bool error check
* Compatibility check WC 5.4.1

= 2.0.1 =
* fix: fraud attempts
* Compatibility check WC 5.4.0

= 2.0.0 =
* feature: blacklisting by product types
* feature: skip blacklisting for order payment page
* Compatibility check WC 5.1.0

= 1.7.2 =
* fix: order status cancelled on blacklisting from backend
* Compatibility check WC 5.0.0

= 1.7.1 =
* add: compatibility check with eWay
* Compatibility check WC 4.9.2

= 1.7.0 =
* Feat: blacklisting with "order-pay" action
* Compatibility check WC 4.9.1

= 1.6.2 =
* Fixes to "conflict with WooCommerce payment hook" issued on github repo.
* "Removed from blacklist" order note added.
* "Bulk blacklist" safe redirect fix.

= 1.6.1 =
* Compatibility check with WP 5.6 & WC 4.8.0

= 1.6.0 =
* Compatibility check with WC 4.6.1
* Blocking by email domain

= 1.5.5 =
* Compatibility check with WP 5.5 & WC 4.4.1

= 1.5.4 =
* Compatibility check with WC 4.3.0

= 1.5.3 =
* Compatibility check with WC 4.2.2

= 1.5.2 =
* Order statuses multiselect setting UI change.
* Blacklist by order status bug fixes.

= 1.5.1 =
* Translation bug fixes.

= 1.5.0 =
* Bocking by customer name feature added.
* Compatibility check with WC 4.2.0.

= 1.4.9 =
* Compatibility check with WC 4.1.0.

= 1.4.8 =
* checkout blocking parameters fixes.

= 1.4.7 =
* default allowed fraud attempts fixed.

= 1.4.6 =
* bulk blacklisting notification fixes.

= 1.4.5 =
* Compatibility with woocommerce 3.7.0

= 1.4.3 =
* Compatibility with woocommerce 3.6.3.

= 1.4.1 =
* "Order Statuses" setting label bug fixed.

= 1.4.0 =
* Order placement prevention based on order status added.

= 1.3.0 =
* Feature of removing customer details from blacklists added. 
* Format of saving the blacklist option changed from comma separated to new line values
* DB update option added. 

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

== Upgrade Notice ==
= 2.0.0 =
Version 2.0.0 supports blacklisting by product types, skipping blacklisting per order payment page

= 1.5.0 =
Version 1.5.0 supports blocking by customer name
