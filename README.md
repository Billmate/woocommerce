# Billmate Payment Gateway for WooCommerce
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")

## Documentation
[Installation manual in English](https://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf)

[Installation manual in Swedish](https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf)

## Description 
Billmate Gateway is a payment plugin for WooCommerce that gives your customers the ability to pay with their favorite payment options. This plugin supports the WooCommerce standard checkout as well as the improved checkout experience that Billmate Checkout brings. Billmate Checkout integrates via a iframe solution.

## Available payment methods
* Invoice
* Part payment
* Card
* Bank (Direct Bank payment through [Trustly](https://www.trustly.com))

## Important note
The invoice and part payment plugin only work if the currency is set to Swedish Krona (SEK) and the Base country is set to Sweden.

## Compatibility WordPress versions
4.5.4 - 5.2.3

## Compatibility WooCommerce versions
2.5.5 - 4.0.1

## Compatibility PHP versions
1.0 - 8.1

## Installation
The simplest way to install this module is through the WordPress Plugin Directory. However, if you want to install manually, follow the steps below-
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> Settings --> Billmate Settings and configure your Billmate ID and Secret.
6. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
7. Go to --> WooCommerce --> Settings --> General --> Default Customer Location and set it to any setting except 'No location by default'
8. Billmate Part Payment: Click the button "Update paymentplans" on the settings page to fetch your shops PClasses and store them in the database.

## Can I use Wordfence or any other Firewall plugin?
You need to make sure our callback IP, 54.194.217.63, is whitelisted  in Wordfence. Navigate to "Wordfence -> Options" and scroll down to "Other Options". Add our ip-number next to "Whitelisted IP addresses that bypass all rules" and it should work. If not, please contact our support, support@billmate.se.

## Verified compatible external plugins
* [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/ "WooCommerce Subscriptions")
* [Product Add-Ons](https://woocommerce.com/products/product-add-ons/ "Product Add-Ons")

## How to place Billmate logo on your site.
Copy the code below for the size that fits your needs.

### Large
<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/L.png" alt="Billmate Payment Gateway" /></a>

`<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/L.png" alt="Billmate Payment Gateway" /></a>`

### Medium
<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/M.png" alt="Billmate Payment Gateway" /></a>

`<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/M.png" alt="Billmate Payment Gateway" /</a>`
### Small
<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/S.png" alt="Billmate Payment Gateway" /></a>

`<a href="https://billmate.se"><img src="https://www.billmate.se/wp-content/uploads/2019/09/S.png" alt="Billmate Payment Gateway" /></a>`

## Testing
Tested with [Browserstack](http://www.browserstack.com)

The e2e tests are using Cypress (https://www.cypress.io/). 
To run the tests there is a need to do the following.

    npm install

Once all dependencies are installed then there is set of commands that can be run to test.

    npm run cypress:run

When developing there is a live reload mode.

    npm run cypress:open

## Local environment
To setup a local Wordpress with Woocommerce and Billmate Payment Gateway locally, the following steps are needed.

Start a Docker instance:

    docker-compose up

A new instance of the CMS are now up and running at the following url:

    If you are using 'local' or 'remote' docker contexts:
        http://localhost:8282

    If you are using 'ngrok' docker context:
        The public url can be found:
            1. In the logs of the woocommerce container
            2. At http://localhost:4040/status

To login to Wordpress the credentials are:

    user: admin
    password: 4dm1n

Docker contexts:

    docker/remote - will pull all source from github

    docker/local - will mount the local source (./src) for the Billmate payment gateway plugin

    docker/ngrok - will publish port 80 of the woocommerce container to a temporary public url powered by Ngrok.


## Development
The development tools are available through Composer scripts.

To get all the necessary dependencies from composer, run this command in root directory:

    composer install

Run PHP codesniffer with WP coding standards:

    composer run php:codesniffer

To run the full test suite:

    composer run tests:all

---
Original code created by Niklas Högefjord - http://krokedil.com/
