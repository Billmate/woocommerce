<?php

// Shortcodes for display cost/month
add_shortcode( 'billmate_price', 'return_billmate_price' );
add_shortcode( 'billmate_currency', 'return_billmate_currency' );
add_shortcode( 'billmate_img', 'return_billmate_basic_img' );
add_shortcode( 'billmate_partpayment_info_link', 'return_billmate_partpayment_info_link' );
add_action('wp_enqueue_scripts','add_billmate_popup');
// Return Monthly price
function return_billmate_price() {
	global $billmate_partpayment_shortcode_price, $eid;
	$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses');
	$flag = BillmateFlags::CHECKOUT_PAGE;
	$pclasses_not_available = true;
	$enabled_plcass = 'no';
	if($pclasses)
		$pclasses_not_available = false;
	$WC_Gateway_Billmate_Partpayment = new WC_Gateway_Billmate_Partpayment;
	$product = new WC_Product( get_the_ID() );
  	$price = $product->price;

	$settings = get_option('woocommerce_billmate_partpayment_settings');
	$eid = get_option('billmate_common_eid');;


	if(!$pclasses_not_available) {

		foreach ($pclasses as $pclass) {

			if (strlen($pclass['description']) > 0 ) {
				// Get monthly cost for current pclass
				$billmate_partpayment_shortcode_price = BillmateCalc::calc_monthly_cost(
									$price,
									$pclass,
									$flag
								);
			} // End if $pclass->getType() == 0 or 1

		} // End foreach
	}
		return $billmate_partpayment_shortcode_price;
}

// Return Currency
function return_billmate_currency() {
	global $billmate_partpayment_shortcode_currency;
	return $billmate_partpayment_shortcode_currency;
}

// Return Billmate basic image
function return_billmate_basic_img() {
	global $billmate_shortcode_img;
	return '<img class="billmate-logo-img" src="' . $billmate_shortcode_img . '" />';
}

// Return Account info popup link
function return_billmate_partpayment_info_link() {
	global $billmate_partpayment_country, $billmate_partpayment_eid,$billmate_shortcode;
	$billmate_shortcode = true;
	//global $billmate_partpayment_shortcode_info_link;
	//return '<a id="billmate_partpayment" onclick="ShowBillmatePartPaymentPopup();return false;" href="javascript://">' . __('Read more', 'billmate') . '</a>';

	$WC_Gateway_Billmate_Partpayment = new WC_Gateway_Billmate_Partpayment;
	$product = new WC_Product( get_the_ID() );
  	$price = $product->price;

	ob_start();
	$WC_Gateway_Billmate_Partpayment->payment_fields_options( $price, false , BillmateFlags::PRODUCT_PAGE);
	echo '<a id="billmate_partpayment" href="#">' . $WC_Gateway_Billmate_Partpayment->get_account_terms_link_text($billmate_partpayment_country) . '</a>';

	$output_string = ob_get_clean();
	return $output_string;
}

function add_billmate_popup(){
	global $billmate_shortcode;
	if($billmate_shortcode) {
		wp_register_script('billmate-popup-js', plugins_url('/js/billmatepopup.js', __FILE__), array(), false, true);
		wp_enqueue_script('billmate-popup-js');

	}
}
?>
