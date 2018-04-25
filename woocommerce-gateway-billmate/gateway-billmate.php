<?php
/*
Plugin Name: WooCommerce Billmate Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Receive payments on your WooCommerce store via Billmate. Invoice, partpayment, credit/debit card and direct bank transfers. Secure and 100&#37; free plugin.
Version: 3.2.0
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
add_action( 'admin_notices', 'BillmateAdminNotice::show_notices');

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

function billmate_gateway_admin_info_message($message = "") {
    $class = 'notice notice-info';
    if($message != "") {
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), '<img style="height:14px;margin-right:6px;" src="https://online.billmate.se/wp-content/uploads/2013/03/billmate_247x50.png">'.esc_html( $message ) );
    }
}


/*
 * Remind administrator about settings that need to be correctly defined when Billmate is active
 */
add_action( 'admin_init', 'billmate_settings_nag');
function billmate_settings_nag() {
    // Billmate payment method is enabled but id or secret is not defined
    $billmateInvoiceSettings        = get_option('woocommerce_billmate_invoice_settings');
    $billmatePartpaymentSettings    = get_option('woocommerce_billmate_partpayment_settings');
    $billmateCardpaySettings        = get_option('woocommerce_billmate_cardpay_settings');
    $billmateBankpaySettings        = get_option('woocommerce_billmate_bankpay_settings');
    $checkoutSettings               = get_option("woocommerce_billmate_checkout_settings", array());
    if (((isset($billmateInvoiceSettings['enabled']) && $billmateInvoiceSettings['enabled'] == 'yes')
            || (isset($billmatePartpaymentSettings['enabled']) && $billmatePartpaymentSettings['enabled'] == 'yes')
            || (isset($billmateCardpaySettings['enabled']) && $billmateCardpaySettings['enabled'] == 'yes')
            || (isset($billmateBankpaySettings['enabled']) && $billmateBankpaySettings['enabled'] == 'yes')
            || (isset($checkoutSettings['enabled']) && $checkoutSettings['enabled'] == 'yes'))
        && (get_option('billmate_common_eid') == ''
            || get_option('billmate_common_secret') == '')
    ) {
        BillmateAdminNotice::add_warning( __( 'Billmate id or secret are not defined', 'billmate' ), admin_url( 'options-general.php?page=billmate-settings' ), __( 'Settings' ) );
    }
}

/**
 * Remind administrator about settings that need to be correctly defined when Billmate Checkout is active
 */
add_action( 'admin_init', 'billmate_checkout_settings_nag');
function billmate_checkout_settings_nag() {
    $checkoutSettings = get_option("woocommerce_billmate_checkout_settings", array());

    if(isset($checkoutSettings['enabled']) AND $checkoutSettings['enabled'] == 'yes') {
        if(!isset($checkoutSettings['checkout_url']) OR intval($checkoutSettings['checkout_url']) != $checkoutSettings['checkout_url'] OR intval($checkoutSettings['checkout_url']) < 1) {
            $link_url = get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=billmate_checkout';
            BillmateAdminNotice::add_error( __( 'Billmate Checkout must have a Billmate Checkout page to be able to function', 'billmate' ), $link_url, __( 'Settings' ) );
        }

        if(!isset($checkoutSettings['terms_url']) OR intval($checkoutSettings['terms_url']) != $checkoutSettings['terms_url'] OR intval($checkoutSettings['terms_url']) < 1) {
            $link_url = get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=billmate_checkout';
            BillmateAdminNotice::add_error( __( 'Billmate Checkout must have a terms page to be able to function', 'billmate' ), $link_url, __( 'Settings' ) );
        }

        if (isset($checkoutSettings['testmode']) AND $checkoutSettings['testmode'] == 'yes') {
            $link_url = get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=billmate_checkout';
            BillmateAdminNotice::add_warning( __( 'Billmate Checkout is currently in test-mode', 'billmate' ), $link_url, __( 'Settings' ) );
        }

        // Check supported language is set
        $wpLanguage = strtolower(current(explode('_',get_locale())));
        if($wpLanguage != "sv") {

            // When WPML is active Do not display language note in admin
            $wpml_is_active = false;
            $active_plugins = get_option( 'active_plugins', array() );
            foreach ($active_plugins AS $_plugin) {
                if (substr(strtolower($_plugin), 0, 5) == 'wpml-') {
                    $wpml_is_active = true;
                    break;
                }
            }

            if ($wpml_is_active ==  false) {
                $link_url = get_admin_url().'options-general.php';
                BillmateAdminNotice::add_error( __( 'Billmate Checkout need the website language to be set as SV (Swedish) to be able to function', 'billmate' ), $link_url, __( 'Settings' ) );
            }
        }

        /**
         * WooCommerce price decimals
         * Need 2 to prevent miscalculation
         */
        if ( wc_get_price_decimals() < 2 ) {
            $link_url = get_admin_url().'admin.php?page=wc-settings';
            BillmateAdminNotice::add_info( __( 'WooCommerce need to have set prices with 2 decimals to ensure prices are correctly when sent to Billmate', 'billmate' ) , $link_url, __( 'Settings' ) );
        }
    }
}

// Update invoice
add_action( 'update_option_woocommerce_billmate_invoice_settings', 'billmate_gateway_admin_invoice_settings_update');
function billmate_gateway_admin_invoice_settings_update($old_value) {
    $settings = get_option("woocommerce_billmate_invoice_settings", array());
    if (    ((   is_array($old_value) AND
                isset($old_value['enabled']) AND
                $old_value['enabled'] == 'no' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            ) OR (
                is_array($old_value) AND
                isset($old_value['testmode']) AND
                $old_value['testmode'] == 'yes' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            )) AND $settings['testmode'] == 'no'
    ) {
        // Enabled and NOT test mode
        $methods = billmate_get_available_payment_methods();

        // If method 1 is not available and method 2 is, use method 2
        if (!isset($methods['1']) AND isset($methods['2'])) {
            $settings['method'] = '2';
            update_option('woocommerce_billmate_invoice_settings', $settings);
        } elseif (!isset($methods['1'])) {
            $settings['enabled'] = 'no';
            update_option('woocommerce_billmate_invoice_settings', $settings);
        }
        if (isset($settings['method']) AND $settings['method'] == '2' AND isset($methods['1'])) {
            $settings['method'] = '1';
            update_option('woocommerce_billmate_invoice_settings', $settings);
        }

        if (!isset($methods['1']) AND isset($methods['2'])) {
            billmate_gateway_admin_info_message("Invoice Factoring is unavailable. Invoice Service is available and activated. For information about Invoice Factoring and Invoice Service please contact support@billmate.se .");
        } elseif (!isset($methods['1'])) {
            billmate_gateway_admin_error_message("Payment option is not available for your Billmate account. Please contact support@billmate.se for more information.");
        }

    }
}

// Update part payment
add_action( 'update_option_woocommerce_billmate_partpayment_settings', 'billmate_gateway_admin_partpayment_settings_update');
function billmate_gateway_admin_partpayment_settings_update($old_value) {
    $settings = get_option("woocommerce_billmate_partpayment_settings", array());
    if (    ((   is_array($old_value) AND
                isset($old_value['enabled']) AND
                $old_value['enabled'] == 'no' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            ) OR (
                is_array($old_value) AND
                isset($old_value['testmode']) AND
                $old_value['testmode'] == 'yes' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            )) AND $settings['testmode'] == 'no'
    ) {
        // Enabled and NOT test mode
        $methods = billmate_get_available_payment_methods();
        if (!isset($methods['4'])) {
            $settings['enabled'] = 'no';
            update_option('woocommerce_billmate_partpayment_settings', $settings);
            billmate_gateway_admin_error_message("Payment option is not available for your Billmate account. Please contact support@billmate.se for more information.");
        }
    }
}

// Update card
add_action( 'update_option_woocommerce_billmate_cardpay_settings', 'billmate_gateway_admin_cardpay_settings_update');
function billmate_gateway_admin_cardpay_settings_update($old_value) {
    $settings = get_option("woocommerce_billmate_cardpay_settings", array());
    if (    ((   is_array($old_value) AND
                isset($old_value['enabled']) AND
                $old_value['enabled'] == 'no' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            ) OR (
                is_array($old_value) AND
                isset($old_value['testmode']) AND
                $old_value['testmode'] == 'yes' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            )) AND $settings['testmode'] == 'no'
    ) {
        // Enabled and NOT test mode
        $methods = billmate_get_available_payment_methods();
        if (!isset($methods['8'])) {
            billmate_gateway_admin_error_message("Payment option is not available for your Billmate account. Please contact support@billmate.se for more information.");
            $settings['enabled'] = 'no';
            update_option('woocommerce_billmate_cardpay_settings', $settings);
        }
    }
}

// Update bank
add_action( 'update_option_woocommerce_billmate_bankpay_settings', 'billmate_gateway_admin_bankpay_settings_update');
function billmate_gateway_admin_bankpay_settings_update($old_value) {
    $settings = get_option("woocommerce_billmate_bankpay_settings", array());
    if (    ((   is_array($old_value) AND
                isset($old_value['enabled']) AND
                $old_value['enabled'] == 'no' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            ) OR (
                is_array($old_value) AND
                isset($old_value['testmode']) AND
                $old_value['testmode'] == 'yes' AND
                isset($settings['enabled']) AND
                $settings['enabled'] == 'yes'
            )) AND $settings['testmode'] == 'no'
    ) {
        // Enabled and NOT test mode
        $methods = billmate_get_available_payment_methods();
        if (!isset($methods['16'])) {
            billmate_gateway_admin_error_message("Payment option is not available for your Billmate account. Please contact support@billmate.se for more information.");
            $settings['enabled'] = 'no';
            update_option('woocommerce_billmate_bankpay_settings', $settings);
        }
    }
}

function billmate_get_available_payment_methods() {
    $billmate = new Billmate(get_option('billmate_common_eid'), get_option('billmate_common_secret'), false);
    $accountInfo =  $billmate->getAccountinfo(array());
    $methods = array();

    if (isset($accountInfo['paymentoptions'])) {
        foreach ($accountInfo['paymentoptions'] AS $method => $row) {
            $methods[$row['method']] = $row['method'];
        }
    }
    return $methods;
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

        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this, 'get_plugin_action_links'));
    }

    public static function get_plugin_action_links( $links ) {
        return array_unique(array_merge(array(
            '<a href="' . admin_url( 'options-general.php?page=billmate-settings' ) . '">' . __( 'Settings', 'billmate' ) . '</a>',
            '<a href="http://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf" target="_BLANK">' . __( 'Docs', 'billmate' ) . '</a>',
            '<a href="http://billmate.se/kontakt" target="_BLANK">' . __( 'Support', 'billmate' ) . '</a>'
        ), $links));
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


        public function common_check_ipn_response($config = array()) {

            global $woocommerce;

            $testmode = (isset($config['testmode'])) ? $config['testmode'] : $this->testmode;
            $recurring =        false;
            $cancel_url_hit =   false;
            $accept_url_hit =   false;
            $checkout =         false;
            $k =                new Billmate($this->eid,$this->secret,true,$testmode == 'yes',false);
            $payment_note =     '';

            if(!empty($_GET['method']) && $_GET['method'] == 'checkout')
                $checkout = true;
            if( !empty($_GET['payment']) && $_GET['payment'] == 'success' ) {
                if(!empty($_GET['recurring']) && $_GET['recurring'] == 1){
                    $recurring = true;
                }
                if( empty( $_POST ) ){
                    $_POST = $_GET;
                }
                $input = file_get_contents('php://input');
                if(is_array($input))
                    $_POST = array_merge($_POST, $input);

                $accept_url_hit = true;
                $payment_note = 'Note: Payment Completed Accept Url.';
            } elseif (!empty($_GET['payment']) && $_GET['payment'] == 'cancel'){
                if( empty( $_POST ) ){
                    $_POST = $_GET;
                }
                $input = file_get_contents('php://input');
                if(is_array($input))
                    $_POST = array_merge($_POST, $input);

                $cancel_url_hit = true;
                $payment_note = 'Note: Payment Cancelled.';
            } else {
                $_POST = (is_array($_GET) && isset($_GET['data'])) ? $_GET : file_get_contents("php://input");
                $accept_url_hit = false;
                $payment_note = 'Note: Payment Completed (callback success).';
            }
            if(is_array($_POST))
            {
                foreach($_POST as $key => $value)
                    $_POST[$key] = stripslashes($value);
            }

            $data = $k->verify_hash($_POST);
            $order_id = $data['orderid'];

            if(function_exists('wc_seq_order_number_pro')){
                $order_id = wc_seq_order_number_pro()->find_order_by_order_number( $data['orderid'] );

            }
            if(isset($GLOBALS['wc_seq_order_number'])){
                $order_id = $GLOBALS['wc_seq_order_number']->find_order_by_order_number($order_id);
            }
            $order = new WC_Order( $order_id );


            $method_id = ( isset( $config['method_id'] ) ) ? $config['method_id'] : $this->id;
            $method_title = ( isset( $config['method_title'] ) ) ? $config['method_title'] : $this->method_title;
            $transientPrefix = ( isset( $config['transientPrefix'] ) ) ? $config['transientPrefix'] : 'billmate_order_id_';
            $checkoutMessageCancel = ( isset($config['checkoutMessageCancel']) ) ? $config['checkoutMessageCancel'] : '';
            $checkoutMessageFail = ( isset($config['checkoutMessageFail']) ) ? $config['checkoutMessageFail'] : '';


            // Save card token if paid with card and is recurring ( WooCommerce Subscription )
            if ( $method_id == 'billmate_cardpay' AND $recurring == true ) {
                update_post_meta($order_id, '_billmate_card_token', $data['number']);
                update_post_meta($order_id, 'billmate_card_token', $data['number']);


                /**
                 * Save card token on available subscription parent
                 */
                $subscription_parent_id = '';
                if (function_exists('wcs_get_subscriptions_for_order')) {
                    $orderParentId = $order->get_parent_id();
                    $parentOrder = new WC_Order( $orderParentId );
                    $subscriptions = wcs_get_subscriptions_for_order($parentOrder);
                    if (is_array($subscriptions) AND count($subscriptions) > 0) {
                        $subscription = end($subscriptions);
                        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                            $parent_order = $subscription->get_parent();
                            $subscription_parent_id = $parent_order->get_id();
                        } else {
                            $subscription_parent_id = $subscription->order->id;
                        }
                    }
                }

                if (function_exists('wcs_get_subscriptions_for_renewal_order')) {
                    $subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
                    if (is_array($subscriptions) AND count($subscriptions) > 0) {
                        $subscription = end($subscriptions);

                        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                            $parent_order = $subscription->get_parent();
                            $subscription_parent_id = $parent_order->get_id();
                        } else {
                            $subscription_parent_id = $subscription->order->id;
                        }
                    }
                }

                if ('' != $subscription_parent_id) {
                    update_post_meta($subscription_parent_id, '_billmate_card_token', $data['number']);
                    update_post_meta($subscription_parent_id, 'billmate_card_token', $data['number']);
                }


                /**
                 * When true, the order is a subscription initiation order and should be credited
                 * @var bool
                 */
                $isCreditSubscriptionInitOrder = false;

                if ($order->get_total() == 0) {
                    $isCreditSubscriptionInitOrder = true;
                } else {

                    /**
                     * When changing card values for active subscription an initiation order is created
                     * But the $order contain a live sunscription order and total value is over 0
                     * To know that this order should be credited as well, get paymentinfo and check if the order
                     * - Have only 1 article
                     * - Article price is 100 and 0 vat
                     * - Article name match __('Transaction to be credited', 'billmate')
                     * - Amount to pay is 100
                     */

                    $billmateOrderNumber = (isset($data['number'])) ? $data['number'] : '';
                    $billmateOrder = isset($billmateOrder) ? $billmateOrder : array();
                    if (!isset($billmateOrder['PaymentData']) AND $billmateOrderNumber != '') {
                        $billmateOrder = $k->getPaymentinfo(array('number' => $billmateOrderNumber));
                    }

                    if (
                        isset($billmateOrder['Articles']) AND
                        is_array($billmateOrder['Articles']) AND
                        count($billmateOrder['Articles']) == 1 AND
                        isset($billmateOrder['Articles'][0]) AND
                        is_array($billmateOrder['Articles'][0]) AND
                        isset($billmateOrder['Articles'][0]['artnr']) AND
                        isset($billmateOrder['Articles'][0]['title']) AND
                        isset($billmateOrder['Articles'][0]['aprice']) AND
                        isset($billmateOrder['Articles'][0]['taxrate']) AND
                        isset($billmateOrder['Articles'][0]['withouttax']) AND
                        isset($billmateOrder['Cart']) AND
                        is_array($billmateOrder['Cart']) AND
                        isset($billmateOrder['Cart']['Total']) AND
                        is_array($billmateOrder['Cart']['Total']) AND
                        $billmateOrder['Articles'][0]['artnr'] == '' AND
                        $billmateOrder['Articles'][0]['title'] == __('Transaction to be credited', 'billmate') AND
                        $billmateOrder['Articles'][0]['aprice'] == 100 AND
                        $billmateOrder['Articles'][0]['taxrate'] == 0 AND
                        $billmateOrder['Articles'][0]['withouttax'] == 100 AND
                        $billmateOrder['Cart']['Total']['withouttax'] == 100 AND
                        $billmateOrder['Cart']['Total']['tax'] == 0 AND
                        $billmateOrder['Cart']['Total']['withtax'] == 100
                        ) {

                        /** Order matches expectation of a subscription initiation order and will be credited */
                        $isCreditSubscriptionInitOrder = true;
                    }
                }

                if ($isCreditSubscriptionInitOrder == true) {
                    $result = $k->creditPayment(array('PaymentData' => array('number' => $data['number'], 'partcredit' => false)));
                    if (isset($result['number']) AND $result['number'] != '') {
                        $activateResult = $k->activatePayment(array('PaymentData' => array('number' => $result['number'])));
                    }
                }
            }

            // Check if transient is set(Success url is processing)
            if( false !== get_transient( $transientPrefix.$order_id ) ){
                if(version_compare(WC_VERSION, '2.0.0', '<')) {
                    $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                } else {
                    $redirect = $this->get_return_url($order);
                }
                if($accept_url_hit) {
                    WC()->session->__unset( 'billmate_checkout_hash' );
                    WC()->session->__unset( 'billmate_checkout_order' );
                    wp_safe_redirect($redirect);
                    exit;
                } elseif($cancel_url_hit) {
                    if(isset($data['status']) AND $data['status'] == 'Failed') {
                        wc_bm_errors($checkoutMessageFail);
                    } elseif(isset($data['status']) AND $data['status'] == 'Cancelled') {
                        wc_bm_errors($checkoutMessageCancel);
                    }

                    wp_safe_redirect($woocommerce->cart->get_checkout_url());
                    exit;

                }
                else
                    wp_die('OK','ok',array('response' => 200));
            }

            // Set Transient if not exists to prevent multiple callbacks
            set_transient( $transientPrefix.$order_id, true, 3600 );
            if(isset($data['code']) || isset($data['error']) || ($cancel_url_hit) || $data['status'] == 'Failed'){
                if($data['status'] == 'Failed') {
                    $error_message = $checkoutMessageFail;
                } else {
                    $error_message = $checkoutMessageCancel;
                }

                $order->add_order_note( __($error_message, 'billmate') );

                if($accept_url_hit) {
                    delete_transient($transientPrefix.$order_id);
                    wp_safe_redirect(add_query_arg('key', $order->order_key,
                            add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_checkout_page_id')))));
                    die();
                    return false;
                }
                elseif($cancel_url_hit){
                    wc_bm_errors($checkoutMessageCancel);
                    delete_transient($transientPrefix.$order_id);
                    wp_safe_redirect($woocommerce->cart->get_checkout_url());
                    die();
                }else{
                    wp_die('OK','ok',array('response' => 200));
                }
            }

            if( method_exists($order, 'get_status') ) {
                $order_status = $order->get_status();
            } else {
                $order_status_terms = wp_get_object_terms( $order_id, 'shop_order_status', array('fields' => 'slugs') );
                $order_status = $order_status_terms[0];
            }


            if (in_array($order_status, array('pending', 'cancelled', 'bm-incomplete', 'failed'))) {

                // Make sure the selected payment method is saved on order
                if ( version_compare(WC_VERSION, '3.0.0', '>=') AND $method_id != get_post_meta($order_id, '_payment_method') ) {
                    $order->set_payment_method($method_id);
                    $order->set_payment_method_title($method_title);
                    update_post_meta($order_id, '_payment_method', $method_id);
                    update_post_meta($order_id, '_payment_method_title', $method_title);
                }

                // Bank payment can be pending
                if ( $data['status'] == 'Pending' AND $checkout == true) {
                    $order->add_order_note(__($payment_note,'billmate'));
                    $order->update_status('pending');
                    delete_transient($transientPrefix.$order_id);
                }

                if($data['status'] == 'Paid' OR $data['status'] == 'Created') {

                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $orderId = $order->get_id();
                    } else {
                        $orderId = $order->id;
                    }

                    $billmateOrderNumber = (isset($data['number'])) ? $data['number'] : '';
                    $billmateOrder = isset($billmateOrder) ? $billmateOrder : array();
                    if (!isset($billmateOrder['PaymentData']) AND $billmateOrderNumber != '') {
                        $billmateOrder = $k->getPaymentinfo(array('number' => $billmateOrderNumber));
                    }

                    /**
                     * Update customer billing and shipping address on store order from Billmate Checkout order
                     */
                    if (    $checkout == true
                            && isset($billmateOrder['Customer'])
                            && is_array($billmateOrder['Customer'])
                            && count($billmateOrder['Customer']) > 0
                    ) {
                        if (isset($billmateOrder['Customer']['Billing'])) {
                            $billmateOrder['Customer']['Billing'] = array_map("utf8_decode",$billmateOrder['Customer']['Billing']);
                        }

                        if (isset($billmateOrder['Customer']['Shipping'])) {
                            $billmateOrder['Customer']['Shipping'] = array_map("utf8_decode",$billmateOrder['Customer']['Shipping']);
                        }

                        $billing_address = array(
                            'first_name' => $billmateOrder['Customer']['Billing']['firstname'],
                            'last_name'  => $billmateOrder['Customer']['Billing']['lastname'],
                            'company'    => (isset($billmateOrder['Customer']['Billing']['company']) ? $billmateOrder['Customer']['Billing']['company'] : ''),
                            'email'      => $billmateOrder['Customer']['Billing']['email'],
                            'phone'      => $billmateOrder['Customer']['Billing']['phone'],
                            'address_1'  => $billmateOrder['Customer']['Billing']['street'],
                            'address_2'  => (isset($billmateOrder['Customer']['Billing']['street2']) ? $billmateOrder['Customer']['Billing']['street2'] : ''),
                            'city'       => $billmateOrder['Customer']['Billing']['city'],
                            'state'      => '',
                            'postcode'   => $billmateOrder['Customer']['Billing']['zip'],
                            'country'    => $billmateOrder['Customer']['Billing']['country']
                        );

                        if( isset($billmateOrder['Customer']['Shipping'])
                            && is_array($billmateOrder['Customer']['Shipping'])
                            && count($billmateOrder['Customer']['Shipping']) > 0
                        ) {
                            $shipping_address = array(
                                'first_name' => $billmateOrder['Customer']['Shipping']['firstname'],
                                'last_name'  => $billmateOrder['Customer']['Shipping']['lastname'],
                                'company'    => (isset($billmateOrder['Customer']['Shipping']['company']) ? $billmateOrder['Customer']['Shipping']['company'] : ''),
                                'email'      => $billmateOrder['Customer']['Shipping']['email'],
                                'phone'      => $billmateOrder['Customer']['Shipping']['phone'],
                                'address_1'  => $billmateOrder['Customer']['Shipping']['street'],
                                'address_2'  => (isset($billmateOrder['Customer']['Shipping']['street2']) ? $billmateOrder['Customer']['Shipping']['street2'] : ''),
                                'city'       => $billmateOrder['Customer']['Shipping']['city'],
                                'state'      => '',
                                'postcode'   => $billmateOrder['Customer']['Shipping']['zip'],
                                'country'    => $billmateOrder['Customer']['Shipping']['country']
                            );
                        } else {
                            $shipping_address = $billing_address;
                        }

                        $billingEmail = isset($billmateOrder['Customer']['Billing']['email']) ? sanitize_text_field($billmateOrder['Customer']['Billing']['email']) : '';
                        $isEmail = is_email($billingEmail);
                        if ($isEmail != false AND is_string($isEmail) AND $isEmail == $billingEmail) {
                            // Valid email
                            $order->set_address($billing_address,'billing');
                            $order->set_address($shipping_address,'shipping');
                        } else {
                            if (version_compare($wp_version, '2.8.0', '>=') AND version_compare(WC_VERSION, '3.1.0', '>=')) {
                                // Prevent error when saving address with invalid email
                                if (isset($billing_address['email'])) {
                                    unset($billing_address['email']);
                                }

                                $order->set_address($billing_address, 'billing');
                                $order->set_address($shipping_address, 'shipping');
                                update_metadata('post', $order->get_id(), '_billing_email', $billingEmail);
                            } else {
                                $order->set_address($billing_address, 'billing');
                                $order->set_address($shipping_address, 'shipping');
                            }
                        }
                    }

                    // If paid with Billmate Checkout, get payment method from Billmate order
                    if ( $checkout == true AND version_compare(WC_VERSION, '3.0.0', '>=') AND $method_id != get_post_meta($order_id, '_payment_method') ) {
                        $_method_title = $method_title;
                        if ( $billmateOrderNumber != '' ) {
                            if ( isset($billmateOrder['PaymentData']['method_name']) AND $billmateOrder['PaymentData']['method_name'] != "" ) {
                                $_method_title = $_method_title . ' (' .$billmateOrder['PaymentData']['method_name']. ')';
                            } else {
                                $billmateOrderMethod = 1;   // 8 = card, 16 = bank
                                if (isset($billmateOrder['PaymentData']['method'])) {
                                    $billmateOrderMethod = $billmateOrder['PaymentData']['method'];
                                }
                            }

                        }
                        $order->set_payment_method($method_id);
                        $order->set_payment_method_title($_method_title);
                        update_post_meta($order_id, '_payment_method', $method_id);
                        update_post_meta($order_id, '_payment_method_title', $_method_title);
                    }

                    add_post_meta($orderId, 'billmate_invoice_id', $data['number']);
                    $order->add_order_note(sprintf(__('Billmate Invoice id: %s','billmate'),$data['number']));

                    $billmateOrderTotal = isset($billmateOrder['Cart']['Total']['withtax']) ? $billmateOrder['Cart']['Total']['withtax'] : 0;



                    $checkoutSettings = get_option("woocommerce_billmate_checkout_settings", array());
                    if (    $this->order_status == 'default'
                            || ($checkout == true && $order->get_status() != $checkoutSettings['order_status'])
                    ) {

                        if($checkout) {
                            $order->update_status('pending');
                        }

                        $order->add_order_note(__($payment_note,'billmate'));

                        $woocommerce_billmate_invoice_settings = get_option('woocommerce_billmate_invoice_settings');

                        $storeOrderTotalCompare     = intval( strval( $order->get_total() * 100 ) );
                        $billmateOrderTotalCompare  = intval($billmateOrderTotal);

                        // Allow diff in case of store rounding
                        if (    $storeOrderTotalCompare - 300 <= $billmateOrderTotalCompare
                                && $storeOrderTotalCompare + 300 >= $billmateOrderTotalCompare
                        ) {
                            // Set order as paid if paid amount matches order total amount
                            $order->payment_complete();
                            if ($this->order_status != 'default') {
                                $order->update_status($this->order_status);
                                $order->save();
                            }
                        } else {
                            // To pay not match, maybe add handling fee to WC order
                            if (isset($billmateOrder['Cart']['Handling']['withouttax']) AND isset($billmateOrder['Cart']['Handling']['taxrate'])) {

                                $feeTaxclass = 0;
                                if (isset($woocommerce_billmate_invoice_settings['billmate_invoice_fee_tax_class'])) {
                                    $feeTaxclass = $woocommerce_billmate_invoice_settings['billmate_invoice_fee_tax_class'];
                                }

                                $feeTaxrate = intval($billmateOrder['Cart']['Handling']['taxrate']);

                                $feeAmount = intval($billmateOrder['Cart']['Handling']['withouttax']);
                                if ($feeAmount > 0) {
                                    $feeAmount /= 100;
                                }

                                $feeTax = 0;
                                if ($feeTaxrate > 0) {
                                    $feeTax = ($feeAmount * (1 + ($feeTaxrate/100))) - $feeAmount;
                                }

                                $compare = ($billmateOrderTotal / 100) - $feeAmount - $feeTax;
                                $floatCompare = round(floatval($compare), 2);
                                $floatGettotal = round(floatval($order->get_total()), 2);

                                // Allow diff in case of store rounding
                                if ($floatGettotal - 3 <= $floatCompare && $floatGettotal + 3 >= $floatCompare) {
                                    // Assume handling fee is missing, add handling fee and mark order as paid

                                    // Handling fee tax rates
                                    $tax = new WC_Tax();
                                    $rates = $tax->get_rates($feeTaxclass);
                                    $rate = $rates;
                                    $rate = array_pop($rate);
                                    $rate = $rate['rate'];

                                    $feeTaxdata = array();
                                    foreach($rates AS $i => $rate) {
                                        $feeTaxdata[$i] = wc_format_decimal(0);
                                        if ($rate['rate'] > 0) {
                                            $feeTaxdata[$i] = wc_format_decimal(($feeAmount * (1 + ($rate['rate']/100))) - $feeAmount);
                                        }
                                    }

                                    $fee            = new stdClass();
                                    $fee->name      = __('Invoice fee','billmate');
                                    $fee->tax_class = $feeTaxclass;
                                    $fee->taxable   = ($feeTax > 0) ? true : false;
                                    $fee->amount    = wc_format_decimal($feeAmount);
                                    $fee->tax       = wc_format_decimal($feeTax);
                                    $fee->tax_data  = $feeTaxdata;

                                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                                        $item = new WC_Order_Item_Fee();
                                        $item->set_props( array(
                                            'name'      => $fee->name,
                                            'tax_class' => $fee->tax_class,
                                            'total'     => $fee->amount,
                                            'total_tax' => $fee->tax,
                                            'taxes'     => array(
                                                'total' => $fee->tax_data,
                                            ),
                                            'order_id'  => $orderId,
                                        ));

                                        $item->save();
                                        $order->add_item( $item );
                                        $item_id = $item->get_id();

                                        $order->calculate_totals(); // Recalculate order totals after fee is added

                                    } else {
                                        $item_id = $order->add_fee( $fee );
                                    }

                                    $order->payment_complete();
                                    if ($this->order_status != 'default') {
                                        $order->update_status($this->order_status);
                                        $order->save();
                                    }
                                }

                            }
                        }
                    } else {
                        if($checkout)
                            $order->update_status('pending');
                        $order->add_order_note(__($payment_note,'billmate'));
                        $order->update_status($this->order_status);
                        $order->save();
                    }
                    delete_transient($transientPrefix.$order_id);

                }
                if($data['status'] == 'Failed'){
                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $order->update_status('cancelled', 'Failed payment');
                    } else {
                        $order->cancel_order('Failed payment');
                    }
                    delete_transient($transientPrefix.$order_id);

                    if($cancel_url_hit) {
                        wc_bm_errors($checkoutMessageFail);
                        wp_safe_redirect($woocommerce->cart->get_checkout_url());
                        exit;
                    }
                    else
                        wp_die('OK','ok',array('response' => 200));
                }
                if($data['status'] == 'Cancelled'){
                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $order->update_status('cancelled', 'Cancelled Order');
                    } else {
                        $order->cancel_order('Cancelled Order');
                    }
                    delete_transient($transientPrefix.$order_id);

                    if($cancel_url_hit) {
                        wc_bm_errors($checkoutMessageCancel);
                        wp_safe_redirect($woocommerce->cart->get_checkout_url());
                        exit;
                    }
                    else
                        wp_die('OK','ok',array('response' => 200));
                }

                if($cancel_url_hit) {
                    /* In case of cancel and we not received cancel or failed status */
                    wc_bm_errors($checkoutMessageCancel);
                    delete_transient($transientPrefix.$order_id);
                    wp_safe_redirect($woocommerce->cart->get_checkout_url());
                    exit;
                }

                if( $accept_url_hit ){
                    $redirect = '';
                    WC()->cart->empty_cart();
                    delete_transient($transientPrefix.$order_id);
                    if(version_compare(WC_VERSION, '2.0.0', '<')){
                        $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                    } else {
                        $redirect = $this->get_return_url($order);
                    }
                    WC()->session->__unset( 'billmate_checkout_hash' );
                    WC()->session->__unset( 'billmate_checkout_order' );
                    wp_safe_redirect($redirect);
                    exit;
                }
                wp_die('OK','ok',array('response' => 200));
            }


            if( $accept_url_hit ) {
                // Remove cart
                WC()->cart->empty_cart();
                if(version_compare(WC_VERSION, '2.0.0', '<')){
                    $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                } else {
                    $redirect = $this->get_return_url($order);
                }
                WC()->session->__unset( 'billmate_checkout_hash' );
                WC()->session->__unset( 'billmate_checkout_order' );
                wp_safe_redirect($redirect);
                exit;
            }
            delete_transient( $transientPrefix.$order_id );

            wp_die('OK','ok',array('response' => 200));
        }

        public function getRequestMeta() {
            global $wp_version;

            // Meta to add to API requests, will be used for debug
            $meta = array(
                'PHP_VERSION' => phpversion(),
                'WORDPRESS_VERSION' => $wp_version,
                'WOOCOMMERCE_VERSION' => WC_VERSION
            );

            return $meta;
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

