<?php
/*
Plugin Name: WooCommerce Billmate Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides a <a href="http://www.billmate.se" target="_blank">Billmate</a> gateway for WooCommerce.
Version: 1.23.1
Author: Billmate
Author URI: http://billmate.se
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

function init_billmate_gateway() {

	// If the WooCommerce payment gateway class is not available, do nothing
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;


	/**
	 * Localisation
	 */
	load_plugin_textdomain('billmate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

	class WC_Gateway_Billmate extends WC_Payment_Gateway {

		public function __construct() {
			global $woocommerce;


			$this->shop_country	= get_option('woocommerce_default_country');

			// Check if woocommerce_default_country includes state as well. If it does, remove state
    	if (strstr($this->shop_country, ':')) :
    		$this->shop_country = current(explode(':', $this->shop_country));
    	else :
    		$this->shop_country = $this->shop_country;
    	endif;

    	add_action( 'wp_enqueue_scripts', array(&$this, 'billmate_load_scripts_styles'), 6 );

    	// Loads the billmatecustom.css if it exists, loads with prio 999 so it loads at the end
    	add_action( 'wp_enqueue_scripts', array(&$this, 'billmate_load_custom_css'), 999 );
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
			if ( is_checkout() ) {
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
add_action('wp_footer','get_billmate_woocomm_version');
function get_billmate_woocomm_version(){
	echo '<!-- billmate version '.BILLPLUGIN_VERSION.' -->';
	if(!empty($_GET['debug-bill'])){
		echo '<h1>billmate version '.BILLPLUGIN_VERSION.' </h1>';
		phpinfo();
		die;
	}
}
