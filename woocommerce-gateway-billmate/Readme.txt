=== Billmate for WooCommerce ===
Contributors: Billmate
Donate link:
Tags: woocomerce, billmate, payments, cardpayments, invoice, partpayment, recurring, bankpayment
Requires at least: 4.0
Tested up to: 4.9.1
Stable tag: 3.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Billmate offers a complete paymentsolution to your webshop.

== Description ==

Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (https://billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment.

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Billmate to approve/send the invoice.

All our plugins is tested with Browserstack - https://www.browserstack.com

Billmate is a great payment alternative for merchants and customers in Sweden.

If you have any questions please visit https://billmate.se/kontakt to make contact with us.


== Installation ==

= Minimum Requirements =

* Account at Billmate.se
* WooCommerce version 2.5.5 - 3.1.2

= Installation in Wordpress =

Navigate to Plugins-Add New. Search for Billmate and then install the plugin.

= Manual Installation =
Quickinstallation
1. Download the plugin
2. Unzip the zip-file
3. Upload the folder "billmate-payment-gateway-for-woocommerce" to /wp-content/plugins
4. Login to your Wordpress and navigate to Plugins-Installed Plugins and Activate "Woocommerce Billmate Gateway"
5. Navigate to Settings under Wordpress, then "Billmate Settings" and enter your Billmate ID and Secret
6. Navigate to Woocomerce-Settings-Checkout and setup the payment methods.

Link to full documentation: https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf (Swedish)

= Update =
Before updating the plugin, please save your settings and make a backup of your store. If you have a teststore, then do the update on the teststore before updating the real store.

== Frequently Asked Questions ==

= How do I get started? =

The first thing you have to do is to get an account at Billmate.se. Yo can create an account at https://billmate.se/anslut-webbshop/. Then you download the plugin and install it. If you like to make test purchases please contact Billmate, and we will help you.


= How do I make contact with Billmate? =

You can contact us by phone or e-mail. You can find phonenumbers and mailadresses at https://billmate.se/kontakt/

= Is there anything else I need before I can use Billmate plugin on my webstore? =

I depends on what payment methods you will use. If you use cardpayment you need to sign a acquiring agreement with a acquirer. If you use bankpayment, you need a contract with Trustly. If you use Invoce or/and partpayment, then you need a contract with Billmate.

= How much does it cost to use Billmate? =

We have different prices depending on what you choose. You can read more about it at https://billmate.se/priser

= Can I use Wordfence or other Firewall plugin? =
You need to make sure our callback IP, 54.194.217.63, is whitelisted  in Wordfence. Navigate to "Wordfence -> Options" and scroll down to "Other Options". Add our ip-number next to "Whitelisted IP addresses that bypass all rules" and it should go.

== Screenshots ==

1. Checkout - https://billmate.se/plugins/images/woocommerce/checkout.png
2. Billmate Settings - https://billmate.se/plugins/images/woocommerce/billmate_settings.png
3. Billmate Invoice - https://billmate.se/plugins/images/woocommerce/invoice.png
4. Billmate Cardpayment - https://billmate.se/plugins/images/woocommerce/card.png
5. Billmate BankPayment - https://billmate.se/plugins/images/woocommerce/bank.png
6. Billmate Partpayment - https://billmate.se/plugins/images/woocommerce/partpayment.png

If you would like to use our logo on your site then choose following:

1. Large - https://billmate.se/billmate/logos/billmate_cloud_l.png
2. Medium - https://billmate.se/billmate/logos/billmate_cloud_m.png
3. Small - https://billmate.se/billmate/logos/billmate_cloud_s.png


== Changelog ==

= 3.2.0 (2018-04-25) =
* Feature - Support for WooCommerc cart shipping calculator for Billmate Checkout
* Feature - Show Select another payment button above Billmate Checkout when available
* Fix - Improve clear cart when complete order with Billmate Checkout
* Fix - Make sure file with function is_plugin_active is included
* Fix - Set custom order status when paid after order is set as paid
* Enhancement - Show paid with Billmate Checkout on order for recent version of WC
* Enhancement - Update store order address from Billmate checkout
* Enhancement - Improve admin notifications
* Enhancement - Improve remind administrator about admin settings

= 3.1.1 (2018-01-29) =
* Fix - Bank payments now always use the same string where it's referenced.
* Fix - Rounding improvements. Under some rare circumstances the amount can be different from payment window to callback and/or accept url.
* Enhancement - Improved loading animation for Checkout - The loading animation is more precise triggered when needed.
* Feature - Support for plugin "Product Add-Ons" in combination with Billmate Checkout.

= 3.1.0 (2017-12-19) =
* Fix - BC add handling fee on store order when set orderstatus is not default
* Fix - BC support for autoactivate order on creation
* Fix - Tax calculation when free shipping
* Enhancement - Improve rounding accuracy for prices set including tax and zero decimals
* Enhancement - WooCommerce subscription support for change card information and manually pay failed subscription order
* Enhancement - Invoice fee support for WPML currency
* Enhancement - Improve shipping tax calculation
* Enhancement - Replace Cardpayment logo

= 3.0.8 (2017-10-10) =
* Fix - Fix order completed when not paid with Billmate

= 3.0.7 (2017-10-06) =
* Fix - Add missing handling fee on order paid with checkout and rely on callback
* Enhancement - Use method 2 if 1 is unavailable
* Fix - handle shipping with no tax
* Enhancement - Prevent cache of checkout page
* Enhancement - Improve error messages on order note if order activation not success
* Enhancement - Change order of getAddress to be done after try first with addPayment
* Fix - Add handling fee to store order when pay_for_order isset
* Fix - Display partpayment info on product page once

= 3.0.6 (2017-09-14) =
* Improve - Improve the selected currency check when display payment methods
* Improve - Support for additional get parameters
* Improve - Support for activate Billmate Checkout invoice on callback

= 3.0.5 (2017-07-28) =
* Fix - Improve Billmate Checkout speed and communication

= 3.0.4 (2017-07-15) =
* Fix - WooCommerce Subscription 2.2.8 compatibility
* Enhancement - Add links in plugin list

= 3.0.3 (2017-06-12) =
* Fix - Support discount 12% vat
* Enhancement - Discount can be on item level or order level
* Enhancement - Use order item name if standard product

= 3.0.2 (2017-06-07) =
* Fix - Improve compatibility with WC 3
* Fix - Improve Billmate Checkout

= 3.0.1 (2017-05-16) =
* Fix - Improve compatibility with WC 3
* Fix - Improve compatibility with PHP 7
* Fix - Improve Billmate Checkout

= 3.0 (2017-04-24) =
* Feature - Add support for Billmate Checkout
* Fix - Prevent admin menu would be affected
* Fix - Prevent handling fee added as order item

= 2.2.11 (2017-01-03) =
* Fix - New Years issue with update payment plans.
* Fix - Paymentlink issue partpayment and invoice.

= 2.2.10 (2016-12-21) =
* Fix - Abort payment, then do a new with same method.


= 2.2.9 (2016-12-14) =
* Fix - Adjust som styling in checkout for smaller devices
* Enhancement - Add product option to order
* Enhancement - Add additional fees to order as products

= 2.2.8 (2016-11-18) =
* Fix - Get address support for IE8 IE9
* Enhancement - Display message in checkout when cancel/fail cardpayment
* Enhancement - Display message in checkout when cancel/fail bankpayment
* Enhancement - Links to manual in settings
* Enhancement - Button to reset Pclasses in settings

= 2.2.7 (2016-08-05) =
* Fix - Discount and VAT.

= 2.2.6 (2016-04-07) =
* Fix - Address popup utf8.
* Enhancement - Not send in zero fees.
* Enhancement - Fixed some rounding issues.

= 2.2.5 (2016-03-21) =
* Fix - Not send in Payment terms.

= 2.2.4 (2016-02-29) =
* Fix - Subscriptions
* Fix - Status for subscriptions

= 2.2.3 (2016-02-25) =
* Fix - Callback with post.

= 2.2.2 (2016-02-16) =
* Fix - Exit after all redirects.

= 2.2.1 (2016-02-12) =
* Fix - Recurring number on order.

= 2.2 (2016-01-25) =
* Enhancement - Autoactivate invoices on complete.
* Enhancement - Compatibility with Woocommerce Subscription. Recurring payment.
* Translation - Improved the translations.
* Enhancement - Update totals on getAddress click.
* Engancement - Better tracking of invoices in order history.

= 2.1.7(2016-01-21) =
* Fix - Rounding totals.

= 2.1.7(2016-01-21) =
* Fix - Rounding totals.

= 2.1.6 =
* Release date:2015-12-10
* Fix - Customer Nr.
* Fix - Compatibility wc_seq_order_number.

= 2.1.5 =
* Release date: 2015-12-09
* Fix - Rounding.
* Fix - Callback issue.

= 2.1.4 =
* Release date: 2015-10-29
* Fix - Fix bank and partpayment calculations.
* Enhancement - Change classname from Encoding to BillmateStringEncoding, compatibility with Duplicator Pro plugin.

= 2.1.3 =
* Release date: 2015-10-12
* Fix - Php 5.4 compatibility, activate plugin issue.

= 2.1.2 =
* Release date: 2015-10-07
* Fix - Activate plugin issue.
* Enhancement - Added filters for our icons.

= 2.1.1 =
* Release date: 2015-10-05
* Fix - UTF-8 encoding, payment denied message and Card and Bank payment addresses.
* Fix - Rounding calculations.

= 2.1 =
* Release date: 2015-10-01
* Enhancement - Possibility to Choose logo on the invoice created in Billmate Online.
* Optimization - Less load time when show partpayment from on product/shop page.
* Enhancement - Communication error messages.
* Fix - Cancel callback.

= 2.0.6.2 =
* Release date: 2015-09-25
* Fix - Callback issue with GET setting
* Fix - Terms for invoice
* Fix - Sequential Order number compatibility

= 2.0.6 =
* Release date: 2015-09-04
* Fix - Order status when order is denied.
* Fix - Order note when order is denied.

= 2.0.5 =
* Release date: 2015-09-04
* Fix - Added transaction method on card again.

= 2.0.4 =
* Release date: 2015-08-24
* Enchancement - Prettified if no email is input in invoice and partpayment.
* Fix - Order status Issue with partpayment and invoice.

= 2.0.3 =
* Release date: 2015-08-17
* Enchangement - Clean up logging.
* Fix - Same encoding on all error messages.

= 2.0.2 =
* Release date: 2015-08-14
* Fix - Corrected a typo.

= 2.0.1 =
* Release date: 2015-08-13
* Compatibility - WooCommerce above 2.4

= 2.0 =
* Release date: 2015-08-05
* Compatibility - WooCommerce 2.0 and above.
* Enchancement - Get Address for customers in the Checkout.
* Tweak - Layout improvements.
* Tweak - Automatic update of paymentplans.
* Tweak - Clearify Invoice fee in Invoice payment title.
* Tweak - Validation of Billmate Credentials.
* Tweak - Can choose status of payment.
* Tweak - Remove need of product for the Invoice Fee.
* Fix - Consequent display of testmode.
* Fix - Added better support for plugin dynamic pricing.
* Fix - Discount is now divided per Vat rate.
* Fix - Can now order when create account on checkout.

= 1.23.2 =
* Release date: 2015-01-30
* Fix - Fixed a bug that if you entered a matching adress, the verification of the adress popup still appeared.

= 1.23.1 =
* Release date: 2015-01-19
* Fix - The payment status is set to the correct on all payment methods.

= 1.23 =
* Release date: 2015-01-08
* Feature - Added the functionality so that if you put a file called billmatecustom.css in the plugin it will include that. Useful if you need to overwrite some CSS to make the Billmate plugin look good in you checkout without everwriting the themes css.
* Fix - Fixed so that an error on the checkout page does not occur if you enter wrong "personnummer" twice.
* Fix - If you enter a wroong ID for the invoice fee the checkout and settings page does not crash.
* Fix - Removed references to WPLANG and now uses get_locale function instead. According to Wordpres coding standard.
* Tweak - Updated to new company name : Billmate AB.
* Tweak - Change to correct include of colorbox.css to follow Wordpress coding standard
* Tweak - Fixed so that partpayment prices is shown as 12 mounths instead of 3
