# Billmate Payment Gateway for WooCommerce
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")

## Documentation
[Installation manual in English](https://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf)

[Installation manual in Swedish](https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf)

## Description
Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (https://billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.


## Important note
The invoice and part payment plugin only works if the currency is set to Swedish Krona (SEK) and the Base country is set to Sweden.

## COMPATIBILITY WordPress versions
4.5.4 - 4.9.1

## COMPATIBILITY WooCommerce versions
2.5.5 - 3.2.6

## COMPATIBILITY WooCommerce checkout
* WooCommerce default checkout 2.5.5 - 3.2.6

## Installation
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> Settings --> Billmate Settings and configure your Billmate ID and Secret.
6. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
7. Billmate Part Payment: Click the button "Update paymentplans" on the settings page to fetch your shops PClasses and store them in the database.

## Can I use Wordfence or other Firewall plugin?
You need to make sure our callback IP, 54.194.217.63, is whitelisted  in Wordfence. Navigate to "Wordfence -> Options" and scroll down to "Other Options". Add our ip-number next to "Whitelisted IP addresses that bypass all rules" and it should go.

## How to place Billmate logo on your site.
Copy the code below for the size that fits your needs.

### Large
<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>
`<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_l.png" alt="Billmate Payment Gateway" /></a>`

### Medium
<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>
`<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_m.png" alt="Billmate Payment Gateway" /></a>`

### Small
<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>
`<a href="https://billmate.se"><img src="https://billmate.se/billmate/logos/billmate_cloud_s.png" alt="Billmate Payment Gateway" /></a>`

## Testing
Tested with [Browserstack](http://www.browserstack.com)

---
Original code created by Niklas Högefjord - http://krokedil.com/
