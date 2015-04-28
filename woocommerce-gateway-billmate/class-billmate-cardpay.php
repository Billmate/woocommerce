<?php @session_start();
class WC_Gateway_Billmate_Cardpay extends WC_Gateway_Billmate {

	/**
     * Class for Billmate Faktura payment.
     *
     */
    private $prod_url = 'https://cardpay.billmate.se/pay';
    private $tst_url = 'https://cardpay.billmate.se/pay/test';
	public function __construct() {
		global $woocommerce;

		if( !empty($_SESSION['order_created']) ) $_SESSION['order_created'] = '';

		parent::__construct();

		$this->id			= 'billmate_cardpay';
		$this->method_title = __('Billmate Cardpay', 'billmate');
		$this->has_fields 	= true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled				= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title 				= ( isset( $this->settings['title'] )  && $this->settings['title'] != '') ? $this->settings['title'] : __('Billmate Cardpay', 'billmate');
		$this->description  		= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->secret				= get_option('billmate_common_secret');//( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->eid					= get_option('billmate_common_eid');//( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->lower_threshold		= ( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold		= ( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->invoice_fee_id		= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
		$this->allowed_countries = (isset($this->settings['billmatecard_allowed_countries'])) ? $this->settings['billmatecard_allowed_countries'] : array();
		$this->testmode				= ( isset( $this->settings['testmode'] ) && $this->settings['testmode'] == 'yes' ) ? true : false;

		$this->de_consent_terms		= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->authentication_method= ( isset( $this->settings['authentication_method'] ) ) ? $this->settings['authentication_method'] : '';
		$this->prompt_name_entry	= ( isset( $this->settings['prompt_name_entry'] ) ) ? $this->settings['prompt_name_entry'] : 'YES';
		$this->do_3dsecure			= ( isset( $this->settings['do_3dsecure'] ) ) ? $this->settings['do_3dsecure'] : 'NO';
		$this->custom_order_status = ( isset($this->settings['custom_order_status']) ) ? $this->settings['custom_order_status'] : false;
		$this->order_status = (isset($this->settings['order_status'])) ? $this->settings['order_status'] : false;
		if ( $this->invoice_fee_id == "") $this->invoice_fee_id = 0;

		if ( $this->invoice_fee_id > 0 ) :

			// Version check - 1.6.6 or 2.0
			if ( function_exists( 'get_product' ) ) {
				$product = get_product($this->invoice_fee_id);
			} else {
				$product = new WC_Product( $this->invoice_fee_id );
			}

			if ( $product->exists() ) :

				// We manually calculate the tax percentage here
				$this->invoice_fee_tax_percentage = number_format( (( $product->get_price() / $product->get_price_excluding_tax() )-1)*100, 2, '.', '');

				// apply_filters to invoice fee price so we can filter this if needed
				$billmate_invoice_fee_price_including_tax = $product->get_price();
				$this->invoice_fee_price = apply_filters( 'billmate_invoice_fee_price_including_tax', $billmate_invoice_fee_price_including_tax );

			else :

				$this->invoice_fee_price = 0;

			endif;

		else :

		$this->invoice_fee_price = 0;

		endif;

		$billmate_country = 'SE';
		$billmate_language = 'SV';
		$billmate_currency = 'SEK';
		$billmate_invoice_terms = '';
		$billmate_invoice_icon = plugins_url( '/images/bm_kort_l.png', __FILE__ );

		// Apply filters to Country and language
		$this->billmate_country 		= apply_filters( 'billmate_country', $billmate_country );
		$this->billmate_language 		= apply_filters( 'billmate_language', $billmate_language );
		$this->billmate_currency 		= apply_filters( 'billmate_currency', $billmate_currency );
		$this->icon 				= apply_filters( 'billmate_invoice_icon', $billmate_invoice_icon );


		// Actions
		add_action( 'valid-billmate-cardpay-standard-ipn-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_api_wc_gateway_billmate_cardpay', array( $this, 'check_ipn_response' ) );
		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_receipt_billmate', array(&$this, 'receipt_page'));

		//add_action('wp_footer', array(&$this, 'billmate_invoice_terms_js'));

	}

	function check_ipn_response() {
		global $woocommerce;
		//header( 'HTTP/1.1 200 OK' );
		$k = new Billmate($this->eid,$this->secret,true,$this->testmode,false);
		if( !empty($_GET['payment']) && $_GET['payment'] == 'success' ) {
			if( empty( $_POST ) ){
				$_POST = $_GET;
			}
			$accept_url_hit = true;
			$payment_note = 'Note: Payment Completed Accept Url.';
		} else {
			$_POST = $_GET = file_get_contents("php://input");
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
		$order = new WC_Order( $order_id );

		if(false !== get_transient('billmate_cardpay_order_id_'.$order_id)){
			if(version_compare(WC_VERSION, '2.0.0', '<')) {
				$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
			} else {
				$redirect = $this->get_return_url($order);
			}
			wp_safe_redirect($redirect);
		}
		// Set Transient if not exists to prevent multiple callbacks
		set_transient('billmate_cardpay_order_id_'.$order_id,true,3600);

		if(isset($data['code']) || isset($data['error'])){
			if($data['error_message'] == 'Invalid credit bank number') {
				$error_message = 'Tyvärr kunde inte din betalning genomföras';
			} else {
				$error_message = $data['message'];
			}
			$order->add_order_note( __($error_message, 'billmate') );
			$woocommerce->add_error( __($error_message, 'billmate') );
			wp_safe_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_checkout_page_id')))));
			return false;
		}

		if( method_exists($order, 'get_status') ) {
			$order_status = $order->get_status();
		} else {
			$order_status_terms = wp_get_object_terms( $order_id, 'shop_order_status', array('fields' => 'slugs') ); $order_status = $order_status_terms[0];
		}

		if( in_array($order_status, array('pending')) ){

			//$order->update_status('completed', $payment_note);
			if($this->custom_order_status == 'no')
			{
				$order->payment_complete();
			} else {
				$order->update_status($this->order_status);
			}
			if( $accept_url_hit ){
				$redirect = '';
				$woocommerce->cart->empty_cart();
				delete_transient('billmate_cardpay_order_id_'.$order_id);
				if(version_compare(WC_VERSION, '2.0.0', '<')){
					$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
				} else {
					$redirect = $this->get_return_url($order);
				}
				wp_safe_redirect($redirect);
			}
			exit;
		}

		if( $accept_url_hit ) {
			// Remove cart
			$woocommerce->cart->empty_cart();
			if(version_compare(WC_VERSION, '2.0.0', '<')){
				$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
			} else {
				$redirect = $this->get_return_url($order);
			}
			wp_safe_redirect($redirect);
		}

		exit;
		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
		$countries = new WC_Countries();
		$available = $countries->get_countries();
		$order_statuses = wc_get_order_statuses();
	   	$this->form_fields = apply_filters('billmate_invoice_form_fields', array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Cardpay', 'billmate' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'billmate' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'billmate' ),
							'default' => __( 'Billmate Cardpay', 'billmate' )
						),
			'description' => array(
							'title' => __( 'Description', 'billmate' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'billmate' ),
							'default' => ''
						),
			'lower_threshold' => array(
							'title' => __( 'Lower threshold', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Disable Billmate Cardpay if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'billmate' ),
							'default' => ''
						),
			'upper_threshold' => array(
							'title' => __( 'Upper threshold', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Disable Billmate Cardpay if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'billmate' ),
							'default' => ''
						),
			'authentication_method' => array(
							'title' => __( 'Authentication Method', 'billmate' ),
							'type' => 'select',
							'options' => array(
								'authentication'  =>__( 'Authentication', 'billmate' ),
								'sales' => __('Sales','billmate'),
								),
							'label' => __( 'Authentication Method', 'billmate' ),
							'default' => ''
						),
			'do_3dsecure' => array(
							'title' => __( 'Enable 3D Secure', 'billmate' ),
							'type' => 'select',
							'options' => array(
								'YES'  => __('Yes','billmate'),
								'NO' => __('No','billmate'),
								),
							'label' => __( 'Enable 3D Secure', 'billmate' ),
							'default' => 'YES'
						),
			'prompt_name_entry' => array(
							'title' => __( 'Enable Name', 'billmate' ),
							'type' => 'select',
							'options' => array(
								'YES'  => __('Yes','billmate'),
								'NO' => __('No','billmate'),
								),
							'label' => __( 'Enable Name', 'billmate' ),
							'default' => 'NO'
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Test Mode.', 'billmate' ),
							'default' => 'no'
						),
			'billmatecard_allowed_countries' => array(
				'title' 		=> __( 'Allowed Countries', 'woocommerce' ),
				'type' 			=> 'multiselect',
				'description' 	=> __( 'Billmate Card activated for customers in these countries, Leave blank to allow all', 'billmate' ),
				'class'			=> 'chosen_select',
				'css' 			=> 'min-width:350px;',
				'options'		=> $available,
				'default' => ''
			),
			'custom_order_status' => array(
				'title' => __('Custom Order status','billmate'),
				'type' => 'checkbox',
				'label' => __('Enable custom order status','billmate'),
				'default' => 'no'
			),
			'order_status' => array(
				'title' => __('Order status'),
				'type' => 'select',
				'description' => __('Choose a special order status for Billmate Cardpay, if you want to use a own status and not WooCommerce built in','billmate'),
				'default' => '',
				'options' => $order_statuses
			)
		) );

	} // End init_form_fields()



	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e('Billmate Cardpay', 'billmate'); ?></h3>

	    	<p><?php _e('With Billmate your customers can pay by cardpay. Billmate works by adding extra personal information fields and then sending the details to Billmate for verification.', 'billmate');?></p>


    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()


	/**
	 * Check if this gateway is enabled and available in the user's country
	 */

	function is_available() {
		global $woocommerce;
		if ($this->enabled=="yes") :

			// if (!is_ssl()) return false;

			// Currency check
			// if (!in_array(get_option('woocommerce_currency'), array('DKK', 'EUR', 'NOK', 'SEK'))) return false;

			// Base country check
			//if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;

			// Required fields check
			if (!$this->eid || !$this->secret) return false;

			// Cart totals check - Lower threshold
			if ( $this->lower_threshold !== '' ) {
				if ( $woocommerce->cart->total < $this->lower_threshold ) return false;
			}

			// Cart totals check - Upper threshold
			if ( $this->upper_threshold !== '' ) {
				if ( $woocommerce->cart->total > $this->upper_threshold ) return false;
			}
			if(!empty($this->allowed_countries)){

				if(!in_array($woocommerce->customer->country,$this->allowed_countries))
					return false;
			}
			// Only activate the payment gateway if the customers country is the same as the filtered shop country ($this->billmate_country)
	   		//if ( $woocommerce->customer->get_country() == true && $woocommerce->customer->get_country() != $this->billmate_country ) return false;

			return true;

		endif;

		return false;
	}
	/**
	 * Payment form on checkout page
	 */

	function payment_fields() {
	   	global $woocommerce;
	   	?><p><?php echo strlen($this->description)? $this->description: __('Visa & Mastercard','billmate'); ?></p><?php
	}
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_order( $order_id );
		$language = explode('_',get_locale());
		if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));

		$orderValues = array();
		$capture_now   = $this->authentication_method == 'sales' ? 'YES' : 'NO';
		$orderValues['PaymentData'] = array(
			'method' => 8,
			'currency' => get_woocommerce_currency(),
			'language' => strtolower($language[0]),
			'country' => $this->billmate_country,
			'autoactivate' => ($capture_now == 'YES') ? 1 : 0,
			'orderid' => preg_replace('/#/','',$order->get_order_number())
		);
		$orderValues['PaymentInfo'] = array(
			'paymentdate' => (string)date('Y-m-d'),
			'paymentterms' => 14,
			'yourreference' => $order->billing_first_name.' '.$order->billing_last_name
		);



		$languageCode = $language[0];
		$languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
		$languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
		$languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;

		/*if(version_compare(WC_VERSION, '2.0.0', '<')){
			$cancel_url= add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_checkout_page_id'))));
		} else {
			$cancel_url= get_permalink(get_option('woocommerce_checkout_page_id'));
		}*/

		$cancel_url = html_entity_decode($order->get_cancel_order_url());
		$accept_url= trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay&payment=success';
		//$callback_url= 'http://api.billmate.se/callback.php';
		$callback_url = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay';
		$actionurl = $this->testmode ? $this->tst_url : $this->prod_url;
		$secret    = substr($this->secret,0,12);
		$eid       = $this->settings['eid'];
		$currency  = 'SEK';
		$return_method = 'GET';


		$pay_method= 'BANK';
		$amount    = $woocommerce->cart->total*100;
		$orderValues['Card'] = array(
			'accepturl' => $accept_url,
			'callbackurl' => $callback_url,
			'cancelurl' => $cancel_url,
			'3dsecure' => ($this->do_3dsecure != 'NO') ? 1 : 0,
			'promptname' => ($this->prompt_name_entry == 'YES') ? 1 : 0
		);

		$orderValues['Customer'] = array(
			'nr' => empty($order->user_id ) || $order->user_id<= 0 ? time(): $order->user_id
		);
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :

			require_once('split-address.php');

			$billmate_billing_address				= $order->billing_address_1;
			$splitted_address 					= splitAddress($billmate_billing_address);

			$billmate_billing_address				= $splitted_address[0];
			$billmate_billing_house_number		= $splitted_address[1];
			$billmate_billing_house_extension		= $splitted_address[2];

			$billmate_shipping_address			= $order->shipping_address_1;
			$splitted_address 					= splitAddress($billmate_shipping_address);

			$billmate_shipping_address			= $splitted_address[0];
			$billmate_shipping_house_number		= $splitted_address[1];
			$billmate_shipping_house_extension	= $splitted_address[2];

		else :

			$billmate_billing_address				= $order->billing_address_1;
			$billmate_billing_house_number		= '';
			$billmate_billing_house_extension		= '';

			$billmate_shipping_address			= $order->shipping_address_1;
			$billmate_shipping_house_number		= '';
			$billmate_shipping_house_extension	= '';

		endif;
		$countries = $woocommerce->countries->get_allowed_countries();
		$orderValues['Customer']['Billing'] = array(
			'firstname' => utf8_encode($order->billing_first_name),
			'lastname' => utf8_encode($order->billing_last_name),
			'company' => utf8_encode($order->billing_company),
			'street' => utf8_encode($billmate_billing_address),
			'street2' => utf8_encode($order->billing_address_2),
			'zip' => $order->billing_postcode,
			'city' => utf8_encode($order->billing_city),
			'country' => $countries[$order->billing_country],
			'phone' => $order->billing_phone,
			'email' => $order->billing_email
		);
		if ( $order->get_shipping_method() == '' ) {

			$email = $order->billing_email;
			$telno = ''; //We skip the normal land line phone, only one is needed.
			$cellno = $order->billing_phone;
			$company = utf8_encode ($order->billing_company);
			$fname = utf8_encode ($order->billing_first_name);
			$lname = utf8_encode ($order->billing_last_name);
			$careof = utf8_encode ($order->billing_address_2);  //No care of; C/O.
			$street = utf8_encode ($billmate_billing_address); //For DE and NL specify street number in houseNo.
			$zip = utf8_encode ($order->billing_postcode);
			$city = utf8_encode ($order->billing_city);
			$country = utf8_encode ($countries[$order->billing_country]);
			$houseNo = utf8_encode ($billmate_billing_house_number); //For DE and NL we need to specify houseNo.
			$houseExt = utf8_encode ($billmate_billing_house_extension); //Only required for NL.
			$countryCode = $order->billing_country;

		} else {
			$email = $order->billing_email;
			$telno = ''; //We skip the normal land line phone; only one is needed.
			$cellno = $order->billing_phone;
			$company = utf8_encode ($order->shipping_company);
			$fname = utf8_encode ($order->shipping_first_name);
			$lname = utf8_encode ($order->shipping_last_name);
			$careof = utf8_encode ($order->shipping_address_2);  //No care of; C/O.
			$street = utf8_encode ($billmate_shipping_address); //For DE and NL specify street number in houseNo.
			$zip = utf8_encode ($order->shipping_postcode);
			$city = utf8_encode ($order->shipping_city);
			$country = utf8_encode ($countries[$order->shipping_country]);
			$houseNo = utf8_encode ($billmate_shipping_house_number); //For DE and NL we need to specify houseNo.
			$houseExt = utf8_encode ($billmate_shipping_house_extension); //Only required for NL.
			$countryCode = $order->shipping_country;

		}
		$orderValues['Customer']['Shipping'] = array(
			'firstname' => $fname,
			'lastname' => $lname,
			'company' => $company,
			'street' => $street,
			'zip' => $zip,
			'city' => $city,
			'country' => $countries[$order->billing_country],
			'phone' => $cellno
		);
		$total = 0;
		$totalTax = 0;
		$prepareDiscount = array();
		if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
			$_product = $order->get_product_from_item( $item );
			if ($_product->exists() && $item['qty']) :

				// is product taxable?
				if ($_product->is_taxable())
				{
					$taxClass = $_product->get_tax_class();
					$tax = new WC_Tax();
					$rates = $tax->get_rates($taxClass);
					$item_tax_percentage = 0;
					foreach($rates as $row){
						// Is it Compound Tax?
						if(isset($row['compund']) && $row['compund'] == 'yes')
							$item_tax_percentage += $row['rate'];
						else
							$item_tax_percentage = $row['rate'];
					}
				} else
					$item_tax_percentage = 0;

				// apply_filters to item price so we can filter this if needed
				$billmate_item_price_including_tax = $order->get_item_total( $item, true );
				$billmate_item_standard_price = $order->get_item_subtotal($item,true);
				$discount = false;
				if($billmate_item_price_including_tax != $billmate_item_standard_price){
					$discount = true;
				}
				$item_price = apply_filters( 'billmate_item_price_including_tax', $billmate_item_price_including_tax );

				if ( $_product->get_sku() ) {
					$sku = $_product->get_sku();
				} else {
					$sku = $_product->id;
				}

				$priceExcl = round($item_price-$order->get_item_tax($item,false),2);

				$orderValues['Articles'][] = array(
					'quantity'   => (int)$item['qty'],
					'artnr'    => $sku,
					'title'    => $item['name'],
					'aprice'    =>  ($discount) ? ($billmate_item_standard_price*100) : ($priceExcl*100), //+$item->unittax
					'taxrate'      => (float)$item_tax_percentage,
					'discount' => ($discount) ? round((1 - ($billmate_item_price_including_tax/$billmate_item_standard_price)) * 100 ,0) : 0,
					'withouttax' => $item['qty'] * ($priceExcl*100)
				);
				$totalTemp = ($item['qty'] * ($priceExcl*100));
				$total += $totalTemp;
				$totalTax += ($totalTemp * $item_tax_percentage/100);
				if(isset($prepareDiscount[$item_tax_percentage])){
					$prepareDiscount[$item_tax_percentage] += $totalTemp;
				} else {
					$prepareDiscount[$item_tax_percentage] = $totalTemp;
				}

			endif;
		endforeach; endif;

		// Discount
		if ($order->order_discount>0) :

			// apply_filters to order discount so we can filter this if needed
			$billmate_order_discount = $order->order_discount;
			$order_discount = apply_filters( 'billmate_order_discount', $billmate_order_discount );
			$total_value = $total;
			foreach($prepareDiscount as $key => $value){
				$percent = $value/$total_value;

				$discountAmount = ($percent * $order_discount) * (1-($key/100)/(1+($key/100)));
				$orderValues['Articles'][] = array(
					'quantity'   => (int)1,
					'artnr'    => "",
					'title'    => sprintf(__('Discount %s%% tax', 'billmate'),round($key,0)),
					'aprice'    => -($discountAmount*100), //+$item->unittax
					'taxrate'      => $key,
					'discount' => (float)0,
					'withouttax' => -($discountAmount*100)

				);
				$total -= ($discountAmount * 100);
				$totalTax -= ($discountAmount * ($key/100))*100;

			}

		endif;

		// Shipping
		if ($order->order_shipping>0) :

			// We manually calculate the shipping taxrate percentage here
			$calculated_shipping_tax_percentage = ($order->order_shipping_tax/$order->order_shipping)*100; //25.00
			$calculated_shipping_tax_decimal = ($order->order_shipping_tax/$order->order_shipping)+1; //0.25

			// apply_filters to Shipping so we can filter this if needed
			$billmate_shipping_price_including_tax = $order->order_shipping*$calculated_shipping_tax_decimal;
			$shipping_price = apply_filters( 'billmate_shipping_price_including_tax', $billmate_shipping_price_including_tax );

			$orderValues['Cart']['Shipping'] = array(
				'withouttax'    => round(($shipping_price-$order->order_shipping_tax)*100,0),
				'taxrate'      => $calculated_shipping_tax_percentage,

			);
			$total += ($shipping_price-$order->order_shipping_tax) * 100;
			$totalTax += (($shipping_price-$order->order_shipping_tax) * ($calculated_shipping_tax_percentage/100))*100;
		endif;
		$round = ($woocommerce->cart->total * 100) - $total - $totalTax;

		$orderValues['Cart']['Total'] = array(
			'withouttax' => $total,
			'tax' => $totalTax,
			'rounding' => $round,
			'withtax' => $total + $totalTax + $round
		);
		$k = new Billmate($this->eid,$this->secret,true,$this->testmode,false);
		$result = $k->addPayment($orderValues);
		if(isset($result['code'])){
			wc_bm_errors(__($result['message']));
			return;
		}
		return array(
			'result' => 'success',
			'redirect' => $result['url']
		);
	}

	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		echo '<p>'.__('Thank you for your order.', 'billmate').'</p>';

	}


	/**
	 * Javascript for Invoice terms popup on checkout page
	 **/
	function billmate_invoice_terms_js() {
		if ( is_checkout() && $this->enabled=="yes" ) {
			?>
			<script type="text/javascript">
				var billmate_eid = "<?php echo $this->eid; ?>";
				var billmate_country = "<?php echo strtolower($this->billmate_country); ?>";
				var billmate_invoice_fee_price = "<?php echo $this->invoice_fee_price; ?>";
				//addBillmateInvoiceEvent(function(){InitBillmateInvoiceElements('billmate_invoice', billmate_eid, billmate_country, billmate_invoice_fee_price); });
			</script>
			<?php
		}
	}


	/**
	 * get_terms_invoice_link_text function.
	 * Helperfunction - Get Invoice Terms link text based on selected Billing Country in the Ceckout form
	 * Defaults to $this->billmate_country
	 * At the moment $this->billmate_country is allways returned. This will change in the next update.
	 **/

	function get_invoice_terms_link_text($country) {

		switch ( $country )
		{
		case 'SE':
			$term_link_account = 'Villkor f&ouml;r faktura';
			break;
		case 'NO':
			$term_link_account = 'Vilk&aring;r for faktura';
			break;
		case 'DK':
			$term_link_account = 'Vilk&aring;r for faktura';
			break;
		case 'DE':
			$term_link_account = 'Rechnungsbedingungen';
			break;
		case 'FI':
			$term_link_account = 'Laskuehdot';
			break;
		case 'NL':
			$term_link_account = 'Factuurvoorwaarden';
			break;
		default:
			$term_link_account = __('Terms for Invoice', 'billmate');
		}

		return $term_link_account;
	} // end function get_account_terms_link_text()


	// Helper function - get Invoice fee id
	function get_billmate_invoice_fee_product() {
		return $this->invoice_fee_id;
	}

	// Helper function - get Shop Country
	function get_billmate_shop_country() {
		return $this->shop_country;
	}
} // End class WC_Gateway_Billmate_Invoice
