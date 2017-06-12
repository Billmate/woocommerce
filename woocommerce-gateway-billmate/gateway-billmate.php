<?php
/*
Plugin Name: WooCommerce Billmate Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Receive payments on your WooCommerce store via Billmate. Invoice, partpayment, credit/debit card and direct bank transfers. Secure and 100&#37; free plugin.
Version: 3.0.3
Author: Billmate
Text Domain: billmate
Author URI: https://billmate.se
Domain Path: /languages/
*/


/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
//woothemes_queue_update( plugin_basename( __FILE__ ), '4edd8b595d6d4b76f31b313ba4e4f3f6', '18624' );

// Init Billmate Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_billmate_gateway', 0);

//echo $cssfile = plugins_url( '/colorbox.css', __FILE__ );

define('BILLMATE_DIR', dirname(__FILE__) . '/');
define('BILLMATE_LIB', dirname(__FILE__) . '/library/');
require_once 'commonfunctions.php';
/** Change invoice fee to field instead of product. */
function activate_billmate_gateway(){

	// Get settings for Billmate gateway
	$invoiceSettings = get_option('woocommerce_billmate_settings');

	// No settings, new installation
	if($invoiceSettings === false){
		// Initialize plugin
	}

	// If settings and Product for invoice fee is set.
	elseif($invoiceSettings !== false && isset($invoiceSettings['invoice_fee_id']) && $invoiceSettings['invoice_fee_id']){

		// Version check - 1.6.6 or 2.0
		if ( function_exists( 'get_product' ) ) {
			$product = get_product($invoiceSettings['invoice_fee_id']);
		} else {
			$product = new WC_Product( $invoiceSettings['invoice_fee_id']);

		}
		if($product) {
			$fee = $product->get_price_excluding_tax();
			$taxClass = $product->get_tax_class();
			$invoiceSettings['billmate_invoice_fee'] = $fee;
			$invoiceSettings['billmate_invoice_fee_tax_class'] = $taxClass;
		}
		$invoiceSettings['plugin_version'] = BILLPLUGIN_VERSION;
		unset($invoiceSettings['invoice_fee_id']);
		update_option('billmate_common_eid',$invoiceSettings['eid']);
		update_option('billmate_common_secret',$invoiceSettings['secret']);
		update_option('woocommerce_billmate_settings',$invoiceSettings);

	// Else Plugin version in DB differs from Billmate Version.
	}elseif(BILLPLUGIN_VERSION != $invoiceSettings['plugin_version']){
		// Plugin update after Billmate gateway 2.0.0
		$invoiceSettings['plugin_version'] = BILLPLUGIN_VERSION;
		update_option('woocommerce_billmate_settings',$invoiceSettings);
	}

	if(is_plugin_active('wordfence/wordfence.php')){
		add_action('admin_notices','wordfence_notice');
	}

    maby_update_billmate_gateway();
}

function wordfence_notice(){
	echo '<div id="message" class="warning">';
	echo '<p>'.__("To make Wordfence and Billmate Gateway work toghether you have to add the Callback IP to the whitelist. To do so navigate to Wordfence->Options and then scroll down to \"Other Options\". Find \"Whitelisted IP addresses that bypass all rules\" and add the IP 54.194.217.63.",'billmate').'</p>';
	echo '</div>';
}
register_activation_hook(__FILE__,'activate_billmate_gateway');

add_action( 'admin_init', 'maby_update_billmate_gateway' );
function maby_update_billmate_gateway() {
    if(version_compare(get_option("woocommerce_billmate_version"), BILLPLUGIN_VERSION, '<')) {
        update_billmate_gateway();
    }
}

function update_billmate_gateway() {
    // Maby create new page for Billmate Checkout
    $checkoutSettings = get_option("woocommerce_billmate_checkout_settings", array());
    if(!isset($checkoutSettings['checkout_url']) OR intval($checkoutSettings['checkout_url']) != $checkoutSettings['checkout_url']) {
        if(function_exists("wc_create_page")) {
            $pageId = wc_create_page('billmate-checkout','','Billmate Checkout', '[woocommerce_cart] [billmate_checkout]',0);
            if($pageId == intval($pageId) AND intval($pageId) > 0) {
                $checkoutSettings['checkout_url'] = $pageId;
                update_option('woocommerce_billmate_checkout_settings', $checkoutSettings);
            }
        }
    }

    // Maby use WooCommerce terms page for Billmate Checkout
    if(!isset($checkoutSettings['terms_url']) OR intval($checkoutSettings['terms_url']) != $checkoutSettings['terms_url']) {
        if(function_exists("wc_get_page_id")) {
            $wcTermsPageId = wc_get_page_id("terms");
            if(is_int($wcTermsPageId) AND $wcTermsPageId > 0) {
                $checkoutSettings['terms_url'] = $wcTermsPageId;
                update_option('woocommerce_billmate_checkout_settings', $checkoutSettings);
            }
        }
    }

    // Display message in admin that Billmate checkout is available
    add_action( 'admin_notices', 'billmate_gateway_admin_message_checkout_available' );

    update_option("woocommerce_billmate_version", BILLPLUGIN_VERSION);
}


function billmate_gateway_admin_message_checkout_available() {
    $class = 'notice notice-info';
    $message = __('Billmate Checkout is released! Contact Billmate (support@billmate.se) to get started with Billmate Checkout.', 'billmate');
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), '<img style="height:14px;margin-right:6px;" src="https://online.billmate.se/wp-content/uploads/2013/03/billmate_247x50.png">'.esc_html( $message ) );
}

function billmate_gateway_admin_error_message($message = "") {
    $class = 'notice notice-error';
    if($message != "") {
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), '<img style="height:14px;margin-right:6px;" src="https://online.billmate.se/wp-content/uploads/2013/03/billmate_247x50.png">'.esc_html( $message ) );
    }
}

add_action( 'update_option_woocommerce_billmate_checkout_settings', 'billmate_gateway_admin_checkout_settings_update');
function billmate_gateway_admin_checkout_settings_update() {

    // Display admin message when update Billmate Checkout settings and one or more setting need adjustments

    $checkoutSettings = get_option("woocommerce_billmate_checkout_settings", array());

    if(isset($checkoutSettings['enabled']) AND $checkoutSettings['enabled'] == 'yes') {
        // Billmate checkout is enabled
        if(!isset($checkoutSettings['checkout_url']) OR intval($checkoutSettings['checkout_url']) != $checkoutSettings['checkout_url'] OR intval($checkoutSettings['checkout_url']) < 1) {
            billmate_gateway_admin_error_message('Billmate checkut must have Billmate Checkout page to be able to function');
        }

        if(!isset($checkoutSettings['terms_url']) OR intval($checkoutSettings['terms_url']) != $checkoutSettings['terms_url'] OR intval($checkoutSettings['terms_url']) < 1) {
            billmate_gateway_admin_error_message('Billmate Checkout must have a terms page to be able to function');
        }

        // Check supported language is set
        $wpLanguage = strtolower(current(explode('_',get_locale())));
        if($wpLanguage != "sv") {
            billmate_gateway_admin_error_message('Billmate Checkout need the language to be set as SV to be able to function');
        }

        // Get avaliable payment methods and check if is enabled in store. If available and not enabled, display admin messages
        $availablePaymentMethods = array();
        $billmate = new Billmate(get_option('billmate_common_eid'), get_option('billmate_common_secret'), false);
        $accountInfo =  $billmate->getAccountinfo(array());
        if(isset($accountInfo) AND is_array($accountInfo) AND isset($accountInfo['paymentoptions']) AND is_array($accountInfo['paymentoptions'])) {
            foreach($accountInfo['paymentoptions'] AS $paymentoption) {
                if(isset($paymentoption['method'])) {
                    $availablePaymentMethods[$paymentoption['method']] = $paymentoption['method'];
                }
            }
        }

        $billmateInvoiceSettings = get_option('woocommerce_billmate_invoice_settings');
        $billmatePartpaymentSettings = get_option('woocommerce_billmate_partpayment_settings');
        $billmateCardpaySettings = get_option('woocommerce_billmate_cardpay_settings');
        $billmateBankpaySettings = get_option('woocommerce_billmate_bankpay_settings');

        $enabledPaymentMethods = array(
            "1" => array(
                "enabled" => ((isset($billmateInvoiceSettings['enabled']) AND $billmateInvoiceSettings['enabled'] == 'yes') ? 'yes' : 'no'),
                "method" => "1",
                "name" => "Billmate Invoice"
            ),
            "4" => array(
                "enabled" => ((isset($billmatePartpaymentSettings['enabled']) AND $billmatePartpaymentSettings['enabled'] == 'yes') ? 'yes' : 'no'),
                "method" => "4",
                "name" => "Billmate partpayment"
            ),
            "8" => array(
                "enabled" => ((isset($billmateCardpaySettings['enabled']) AND $billmateCardpaySettings['enabled'] == 'yes') ? 'yes' : 'no'),
                "method" => "8",
                "name" => "Billmate Cardpayment"
            ),
            "16" => array(
                "enabled" => ((isset($billmateBankpaySettings['enabled']) AND $billmateBankpaySettings['enabled'] == 'yes') ? 'yes' : 'no'),
                "method" => "16",
                "name" => "Billmate Bankpayment"
            )
        );

        foreach($enabledPaymentMethods AS $method) {
            if((!isset($method['enabled']) OR $method['enabled'] != 'yes') AND in_array($method['method'], $availablePaymentMethods)) {
                // Payment method is enabled and not active
                billmate_gateway_admin_error_message("Billmate Checkout need ".$method['name']." to be activated to be able to function");
            }
        }
    }

}

function init_billmate_gateway() {

	// If the WooCommerce payment gateway class is not available, do nothing
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;


	/**
	 * Localisation
	 */
	load_plugin_textdomain('billmate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	$dummy = __('Receive payments on your WooCommerce store via Billmate. Invoice, partpayment, credit/debit card and direct bank transfers. Secure and 100&#37; free plugin','billmate');
	class WC_Gateway_Billmate extends WC_Payment_Gateway {

		public function __construct() {
			global $woocommerce;
			if(!defined('WC_VERSION')) define('WC_VERSION',$woocommerce->version);

				$this->shop_country	= get_option('woocommerce_default_country');

			// Check if woocommerce_default_country includes state as well. If it does, remove state
    	if (strstr($this->shop_country, ':')) :
    		$this->shop_country = current(explode(':', $this->shop_country));
    	else :
    		$this->shop_country = $this->shop_country;
    	endif;
		add_filter('wp_kses_allowed_html',array($this,'add_data_attribute_filter'),10,2);
    	add_action( 'wp_enqueue_scripts', array(&$this, 'billmate_load_scripts_styles'), 6 );

    	// Loads the billmatecustom.css if it exists, loads with prio 999 so it loads at the end
    	add_action( 'wp_enqueue_scripts', array(&$this, 'billmate_load_custom_css'), 999 );
    }


		function add_data_attribute_filter($tags,$context){

			if($context == 'post') {
				$tags['i']['data-error-code'] = true;
				return $tags;
			}
			return $tags;
		}
    /**
     * Includes a billmatecustom.css if it exists, in case you need to make any special css edits on the css for Billmate regarding the shop.
		 * It could take a minute or two before it shows up withhour cache depending on server. Clear cache on server if it does not show up.
		 * The file automaticlly creates a version depending on the md5 for the file.
		 */
    function billmate_load_custom_css() {
			$filepath = plugin_dir_path( __FILE__ ) . 'billmatecustom.css';
			if ( file_exists( $filepath ) ) {
				wp_enqueue_style( 'billmate-custom', plugins_url( '/billmatecustom.css', __FILE__ ), array(), md5_file($filepath), 'all');
			}
    }

		/**
	 	 * Register and Enqueue Billmate scripts & styles
	 	 */
		function billmate_load_scripts_styles() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_style( 'billmate-colorbox', plugins_url( '/colorbox.css', __FILE__ ), array(), '1.0', 'all');

			// Invoice terms popup
			if ( is_checkout()) {
				wp_register_script( 'billmate-invoice-js', plugins_url( '/js/billmateinvoice.js', __FILE__ ), array('jquery'), '1.0', false );
				wp_enqueue_script( 'billmate-invoice-js' );
				wp_register_script( 'billmate-popup-js', plugins_url( '/js/billmatepopup.js', __FILE__ ),array(),false, true );
				wp_enqueue_script( 'billmate-popup-js' );


			}
			$checkout = new WC_Gateway_Billmate_Checkout();
			if($checkout->enabled == 'yes' && is_page($checkout->checkout_url)){
				wp_enqueue_style( 'billmate-checkous', plugins_url( '/billmatecheckout.css', __FILE__ ), array(), '1.0', 'all');

				wp_register_script( 'billmate-checkout-js', plugins_url( '/js/billmatecheckout.js', __FILE__ ),array(),false, true );
				wp_enqueue_script( 'billmate-checkout-js' );
				wp_localize_script( 'billmate-checkout-js', 'billmate',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'billmate_checkout_nonce' => wp_create_nonce('billmate_checkout_nonce')) );

			}



			// Account terms popup
			if ( is_checkout() || is_product() || is_shop() || is_product_category() || is_product_tag() ) {
				// Original file: https://static.billmate.com:444/external/js/billmatepart.js
				// wp_register_script( 'billmate-part-js', plugins_url( '/js/billmatepart.js', __FILE__ ), array('jquery'), '1.0', false );
				// wp_enqueue_script( 'billmate-part-js' );
				wp_register_script( 'billmate-popup-js', plugins_url( '/js/billmatepopup.js', __FILE__ ),array(),false, true );
				wp_enqueue_script( 'billmate-popup-js' );
			}

		}


	} // End class WC_Gateway_Billmate


	// Include our Billmate Faktura class
	require_once 'class-billmate-invoice.php';

	// Include our Billmate Delbetalning class
	require_once 'class-billmate-account.php';

	// Include our Billmate Special campaign class
	require_once 'class-billmate-cardpay.php';
	require_once 'class-billmate-bankpay.php';

	require_once 'class-billmate-common.php';
	require_once 'class-billmate-checkout.php';
	$common = new BillmateCommon();
	


} // End init_billmate_gateway.

/**
 * Add the gateway to WooCommerce
 **/
function add_billmate_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Billmate_Invoice';
	$methods[] = 'WC_Gateway_Billmate_Partpayment';
	$methods[] = 'WC_Gateway_Billmate_Cardpay';
	$methods[] = 'WC_Gateway_Billmate_Bankpay';
	$methods[] = 'WC_Gateway_Billmate_Checkout';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_billmate_gateway' );

