#Billmate Payment Gateway for WooCommerce
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")
Original code created by Niklas Högefjord - http://krokedil.com/

Documentation with instructions on how to setup the plugin can be found at http://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf

##Description
Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (https://www.billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.


##Important note
The invoice and part payment plugin only works if the currency is set to Swedish Krona (SEK) and the Base country is set to Sweden.


##Installation
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> Settings --> Billmate Settings and configure your Billmate ID and Secret.
6. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
7. Billmate Part Payment: Click the button "Update paymentplans" on the settings page to fetch your shops PClasses and store them in the database.


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

##Changelog
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
