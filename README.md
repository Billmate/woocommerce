#Billmate Payment Gateway for WooCommerce
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")
Original code created by Niklas Högefjord - http://krokedil.com/

## Documentation
[Installation manual in English](http://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf)

[Installation manual in Swedish](http://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf)

##Description
Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (https://www.billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.


##Important note
The invoice and part payment plugin only works if the currency is set to Swedish Krona (SEK) and the Base country is set to Sweden.

## COMPATIBILITY WordPress versions
4.5.4 - 4.6.1

## COMPATIBILITY WooCommerce versions
2.5.5 - 2.6.6

## COMPATIBILITY WooCommerce checkout
* WooCommerce default checkout 2.5.5 - 2.6.6

##Installation
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> Settings --> Billmate Settings and configure your Billmate ID and Secret.
6. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
7. Billmate Part Payment: Click the button "Update paymentplans" on the settings page to fetch your shops PClasses and store them in the database.

## Can I use Wordfence or other Firewall plugin?
You need to make sure our callback IP, 54.194.217.63, is whitelisted  in Wordfence. Navigate to "Wordfence -> Options" and scroll down to "Other Options". Add our ip-number next to "Whitelisted IP addresses that bypass all rules" and it should go.

##How to place Billmate logo on your site.
Copy the code below for the size that fits your needs.


###Large

<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>

`<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>`

###Medium

<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>

`<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>`

###Small

<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>

`<a href="http://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>`

##Testing
Tested with [Browserstack](http://www.browserstack.com)

## Changelog

### 2.2.8 (2016-11-18)
* Fix - Get address support for IE8 IE9
* Enhancement - Display message in checkout when cancel/fail cardpayment
* Enhancement - Display message in checkout when cancel/fail bankpayment
* Enhancement - Links to manual in settings
* Enhancement - Button to reset Pclasses in settings


### 2.2.7 (2016-08-05)
* Fix - Discount and VAT.

### 2.2.6 (2016-04-07)
* Fix - Address popup utf8.
* Enhancement - Not send in zero fees.
* Enhancement - Fixed some rounding issues.

### 2.2.5 (2016-03-21)
* Fix - Not send in Payment terms.

### 2.2.4 (2016-02-29)
* Fix - Subscriptions
* Fix - Status for subscriptions.


### 2.2.3 (2016-02-25)
* Fix - Callback with post.


### 2.2.2 (2016-02-16)  
* Fix - Exit after all redirects.

### 2.2.1 (2016-02-12)
* Fix - Recurring number on order. 

### 2.2 (2016-01-08)
* Enhancement - Autoactivate invoices on complete.
* Enhancement - Compatibility with Woocommerce Subscription. Recurring payment.
* Translation - Improved the translations.
* Enhancement - Update totals on getAddress click.
* Engancement - Better tracking of invoices in order history.

### 2.1.7(2016-01-21)
* Fix - Rounding totals.

### 2.1.6(2015-12-10)
* Fix - Customer Nr.
* Fix - Compatibility wc_seq_order_number.

### 2.1.5 (2015-12-09)
* Fix - Rounding.
* Fix - Callback issue.

###2.1.4 (2015-10-29)
* Fix - Fix bank and partpayment calculations.
* Enhancement - Change classname from Encoding to BillmateStringEncoding, compatibility with Duplicator Pro plugin.

###2.1.3 (2015-10-12)
* Fix - Php 5.4 compatibility, activate plugin issue.

###2.1.2 (2015-10-07)
* Fix - Activate plugin issue.
* Enhancement - Added filters for our icons.

###2.1.1 (2015-10-05)
* Fix - UTF-8 encoding, payment denied message and Card and Bank payment addresses.
* Fix - Rounding calculations.

###2.1 (2015-10-01)
* Enhancement - Possibility to Choose logo on the invoice created in Billmate Online.
* Optimization - Less load time when show partpayment from on product/shop page.
* Enhancement - Communication error messages.
* Fix - Cancel callback.

###2.0.6.2 (2015-09-25)
* Fix - Callback issue with GET setting
* Fix - Terms for invoice
* Fix - Sequential Order number compatibility

###2.0.6 (2015-09-04)
2 commits

* Fix - Order status when order is denied.
* Fix - Order note when order is denied.

###2.0.5 (2015-09-04)
2 commits
* Fix - Added transaction method on card again.

###2.0.4 (2015-08-24)
2 commits
* Enchancement - Prettified if no email is input in invoice and partpayment.
* Fix - Order status Issue with partpayment and invoice.

###2.0.3 (2015-08-17)
1 commit 2 issues closed.

* Enchangement - Clean up logging.
* Fix - Same encoding on all error messages.

###2.0.2 (2015-08-14)
1 commit

* Fix - Corrected a typo.

###2.0.1 (2015-08-13)
1 commit
* Compatibility - WooCommerce above 2.4

###2.0 (2015-08-05)
110 commits and 79 issues closed.

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


###1.23.2 (2015-01-30)
* Fix - Fixed a bug that if you entered a matching adress, the verification of the adress popup still appeared.


###1.23.1 (2015-01-19)
* Fix - The payment status is set to the correct on all payment methods.


###1.23 (2015-01-08)
* Feature - Added the functionality so that if you put a file called billmatecustom.css in the plugin it will include that. Useful if you need to overwrite some CSS to make the Billmate plugin look good in you checkout without everwriting the themes css.
* Fix - Fixed so that an error on the checkout page does not occur if you enter wrong "personnummer" twice.
* Fix - If you enter a wroong ID for the invoice fee the checkout and settings page does not crash.
* Fix - Removed references to WPLANG and now uses get_locale function instead. According to Wordpres coding standard.
* Tweak - Updated to new company name : Billmate AB.
* Tweak - Change to correct include of colorbox.css to follow Wordpress coding standard
* Tweak - Fixed so that partpayment prices is shown as 12 mounths instead of 3
