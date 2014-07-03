=== WOOCOMMERCE BILLMATE GATEWAY ===

Original code created by Niklas Högefjord - http://krokedil.com/
Modified for Billmate by Gagan Preet, Eminence Technology

Documentation with instructions on how to setup the plugin can be found at https://efinance.se/plugins/woocommerce/Instruktionsmanual_Woocommerce_Billmate_Plugin.pdf

== DESCRIPTION ==

Billmate Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Billmate later (http://www.billmate.com/). This plugin utilizes Billmate Invoice, Billmate Card and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in BillmateOnline. After you (as the merchant) completes the order in WooCommerce, you need to log in to BillmateOnline to approve/send the invoice.


== IMPORTANT NOTE ==

The invoice and part payment plugin only works if the currency is set to Swedish Krona and the Base country is set to Sweden.
PCLASSES AND BILLMATE PART PAYMENT
To enable Billmate Part Payment you need to store your available billmatepclasses in the file billmatepclasses.json located in woocommerce-gateway-billmate/srv/. Make sure that read and write permissions for the directory "srv" is set to 777 in order to fetch the available PClasses from Billmate. To retrieve your PClasses from Billmate go to --> WooCommerce --> Settings --> Payment Gateways --> Billmate Part Payment and click the button "Update the PClass file billmatepclasses.json".

If you want to, you can also manually upload your billmatepclasses.json file via ftp.


INVOICE FEE HANDLING
Since of version 1.9 the Invoice Fee for Billmate Invoice are added as a simple (hidden) product. This is to match order total in WooCommerce and your billmate part payment (in earlier versions the invoice fee only were added to Billmate).

To create a Invoice fee product: 
- Add a simple (hidden) product. Mark it as a taxable product.
- Go to the Billmate Gateway settings page and add the ID of the Invoice Fee product. The ID can be found by hovering the Invoice Fee product on the Products page in WooCommerce.


== INSTALLATION	 ==

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> WooCommerce --> Settings --> Payment Gateways and configure your Billmate settings.
6. Billmate Part Payment: Make sure that read and write permissions for the directory "srv" (located in woocommerce-gateway-billmate) and the containing file "billmatepclasses.json" is set to 777 in order to fetch the available PClasses from Billmate.
7. Billmate Part Payment: Click the button "Update the PClass file billmatepclasses.json" on the settings page to fetch your shops PClasses and store them in the billmatepclasses.json file (or upload your billmatepclasses.json file manually via ftp).


== To Place billmate logo copy this <img> tag to your widget or post or code ==
<img src="http://wordpress.billmate.se/wp-content/plugins/woocommerce-gateway-billmate/images/billmate-logo.png" title="Billmate Payment Gateway" alt="Billmate Payment Gateway" />