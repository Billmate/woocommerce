#Billmate Payment Gateway for WooCommerce
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")
Original code created by Niklas Högefjord - http://krokedil.com/

Documentation with instructions on how to setup the plugin can be found at http://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf

##Description
Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (https://www.billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in Prestashop, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.


##Important note
The invoice and part payment plugin only works if the currency is set to Swedish Krona (SEK) and the Base country is set to Sweden.

##PClasses and Billmate Part Payment
To enable Billmate Part Payment you need to store your available billmatepclasses in the file billmatepclasses.json located in woocommerce-gateway-billmate/srv/. Make sure that read and write permissions for the directory "srv" is set to 777 in order to fetch the available PClasses from Billmate. To retrieve your PClasses from Billmate go to --> WooCommerce --> Settings --> Payment Gateways --> Billmate Part Payment and click the button "Update the PClass file billmatepclasses.json".
If you want to, you can also manually upload your billmatepclasses.json file via ftp.

##Invoice fee handling
Since of version 1.9 the Invoice Fee for Billmate Invoice are added as a simple (hidden) product. This is to match order total in WooCommerce and your billmate part payment (in earlier versions the invoice fee only were added to Billmate).

To create a Invoice fee product:
1. Add a simple (hidden) product. Mark it as a taxable product.
2. Go to the Billmate Gateway settings page and add the ID of the Invoice Fee product. The ID can be found by hovering the Invoice Fee product on the Products page in WooCommerce.

##Installation
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
6. Billmate Part Payment: Make sure that read and write permissions for the directory "srv" (located in woocommerce-gateway-billmate) and the containing file "billmatepclasses.json" is set to 777 in order to fetch the available PClasses from Billmate.
7. Billmate Part Payment: Click the button "Update the PClass file billmatepclasses.json" on the settings page to fetch your shops PClasses and store them in the billmatepclasses.json file (or upload your billmatepclasses.json file manually via ftp).


##How to place Billmate logo on your site.
Copy the code below for the size that fits your needs.

###Large  

<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>  

`<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>`

###Medium  

<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>

`<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>`

###Small  

<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>  

`<a href="http://billmate.se" target="_blank"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>`

##Changelog

###1.23.2 (2015-01-30)
* Fix - Fixed a bug that if you enterd a matching adress, the verification of the adress popup still appeared.
 

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
