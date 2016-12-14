<?php
/*
Plugin Name: WooCommerce Billmate Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Receive payments on your WooCommerce store via Billmate. Invoice, partpayment, credit/debit card and direct bank transfers. Secure and 100&#37; free plugin.
Version: 2.2.9
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

}
function wordfence_notice(){
	echo '<div id="message" class="warning">';
	echo '<p>'.__("To make Wordfence and Billmate Gateway work toghether you have to add the Callback IP to the whitelist. To do so navigate to Wordfence->Options and then scroll down to \"Other Options\". Find \"Whitelisted IP addresses that bypass all rules\" and add the IP 54.194.217.63.",'billmate').'</p>';
	echo '</div>';
}
register_activation_hook(__FILE__,'activate_billmate_gateway');
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
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_billmate_gateway' );

