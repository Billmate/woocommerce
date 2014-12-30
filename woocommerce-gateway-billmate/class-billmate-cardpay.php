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
		$this->title 				= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description  		= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->secret				= ( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->eid					= ( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->lower_threshold		= ( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold		= ( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->invoice_fee_id		= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';

		$this->testmode				= ( isset( $this->settings['testmode'] ) && $this->settings['testmode'] == 'yes' ) ? true : false;

		$this->de_consent_terms		= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->authentication_method= ( isset( $this->settings['authentication_method'] ) ) ? $this->settings['authentication_method'] : '';
		$this->prompt_name_entry	= ( isset( $this->settings['prompt_name_entry'] ) ) ? $this->settings['prompt_name_entry'] : 'YES';
		$this->do_3dsecure			= ( isset( $this->settings['do_3dsecure'] ) ) ? $this->settings['do_3dsecure'] : 'NO';
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
		if( !empty($_GET['payment']) && $_GET['payment'] == 'success' ) {
			if( empty( $_POST ) ){
				$_POST = $_GET;
			}
			$accept_url_hit = true;
			$payment_note = 'Note: Payment Completed (callback failure).';
		} else {
			$input = file_get_contents("php://input");
			$_POST = $_GET = (array)json_decode($input);
			$accept_url_hit = false;
			$payment_note = 'Note: Payment Completed (callback success).';
		}

		$order_id = $_POST['order_id'];


		$order = new WC_Order( $order_id );


		if( $_POST['status'] != 0 ) {
				if($_POST['error_message'] == 'Invalid credit card number') {
					$error_message = 'Tyvärr kunde inte din betalning genomföras';
				} else {
					$error_message = $_POST['error_message'];
				}
				$order->add_order_note( __($error_message, 'billmate') );
				$woocommerce->add_error( __($error_message, 'billmate') );
				wp_safe_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_checkout_page_id')))));
				return false;
		}

		if( method_exists($order, 'get_status') ) {
			$order_status = $order->get_status();
		} else {
			$order_status_terms = wp_get_object_terms( $order_id, 'shop_order_status', array('fields' => 'slugs') );
			$order_status = $order_status_terms[0];
		}
		// Check if lockstate equals false.
		if(false === ($lockState = get_transient('billmate_cardpay_order_id_'.$order_id))){
			// Set Transient to prevent multiple running
			set_transient('billmate_cardpay_order_id_'.$order_id,'locked',3600);
			if( in_array($order_status, array('pending')) ) {
				$data = $this->sendBillmate($order_id, $order );
				// Not update status to completed as its against WooCommerce Bestpractice
				//$order->update_status('completed', $payment_note);
				if( $accept_url_hit ) wp_safe_redirect($data['redirect']);
				exit;
			}
		}

		if( $accept_url_hit ) {
			// Remove cart
			$woocommerce->cart->empty_cart();
			if(version_compare(WC_VERSION, '2.0.0', '<')) {
				$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
			} else {
				$redirect = $this->get_return_url($order);
			}
			wp_safe_redirect($redirect);
		}

    exit;
    return true;

	}
    function sendBillmate($order_id,$order, $addorder = false){
        global $woocommerce;

		if( method_exists($order, 'get_status') ) { $order_status = $order->get_status(); }
		else { $order_status_terms = wp_get_object_terms( $order_id, 'shop_order_status', array('fields' => 'slugs') ); $order_status = $order_status_terms[0]; }

		if( !in_array($order_status, array('pending')) ){
			return false;
		}

		if( !empty($_SESSION['order_created']) ) return;
	       $billmate_pno = '';
		// Split address into House number and House extension for NL & DE customers
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

		// Store Billmate specific form values in order as post meta
		$pno = '';

        $eid  = (int)$this->settings['eid'] ;
        $key = (float)$this->settings['secret'];
		$ssl = true;
		$debug = false;
		$k = new BillMate($eid,$key,$ssl,$debug, $this->testmode);
		$goods_list = array();
		// Cart Contents
		if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :

			if ( function_exists( 'get_product' ) ) {

				// Version 2.0
				$_product = $order->get_product_from_item($item);

				// Get SKU or product id
					if ( $_product->get_sku() ) {
						$sku = $_product->get_sku();
					} else {
						$sku = $_product->id;
					}

			} else {

				// Version 1.6.6
				$_product = new WC_Product( $item['id'] );

				// Get SKU or product id
				if ( $_product->get_sku() ) {
					$sku = $_product->get_sku();
				} else {
					$sku = $item['id'];
				}

			}

			// We manually calculate the tax percentage here
			if ($order->get_total_tax() >0) :
				// Calculate tax percentage
				$item_tax_percentage = number_format( ( $order->get_line_tax($item) / $order->get_line_total( $item, false ) )*100, 2, '.', '');
			else :
				$item_tax_percentage = 0.00;
			endif;


			// apply_filters to item price so we can filter this if needed
			$billmate_item_price_including_tax = $order->get_item_total( $item, true );
			$item_price = apply_filters( 'billmate_item_price_including_tax', $billmate_item_price_including_tax );

	        $goods_list[] = array(
		        'qty'   => (int)$item['qty'],
		        'goods' => array(
			        'artno'    => $sku,
			        'title'    => $item['name'],
			        'price'    => ($item_price*100), //+$item->unittax
			        'vat'      => (float)$item_tax_percentage,
			        'discount' => (float)0,
			        'flags'    => BillmateFlags::INC_VAT,
		        )
	        );
		//endif;
		endforeach; endif;
		// Discount
		if ($order->order_discount>0) :

			// apply_filters to order discount so we can filter this if needed
			$billmate_order_discount = $order->order_discount;
			$order_discount = apply_filters( 'billmate_order_discount', $billmate_order_discount );

	        $goods_list[] = array(
		        'qty'   => (int)1,
		        'goods' => array(
			        'artno'    => "",
			        'title'    => __('Rabatt', 'billmate'),
			        'price'    => -($order_discount*100), //+$item->unittax
			        'vat'      => 0,
			        'discount' => (float)0,
			        'flags'    => BillmateFlags::INC_VAT,
		        )
	        );
		endif;

		// Shipping
		if ($order->order_shipping>0) :

			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ($order->order_shipping_tax/$order->order_shipping)*100; //25.00
			$calculated_shipping_tax_decimal = ($order->order_shipping_tax/$order->order_shipping)+1; //0.25

			// apply_filters to Shipping so we can filter this if needed
			$billmate_shipping_price_including_tax = $order->order_shipping*$calculated_shipping_tax_decimal;
			$shipping_price = apply_filters( 'billmate_shipping_price_including_tax', $billmate_shipping_price_including_tax );

	        $goods_list[] = array(
		        'qty'   => (int)1,
		        'goods' => array(
			        'artno'    => "",
			        'title'    => __('Shipping cost', 'billmate'),
			        'price'    => round($shipping_price*100,0),
			        'vat'      => $calculated_shipping_tax_percentage,
			        'discount' => (float)0,
			        'flags'    => BillmateFlags::INC_VAT + BillmateFlags::IS_SHIPMENT,
		        )
	        );
		endif;

        $fullname = $order->billing_last_name.' '.$order->billing_first_name.' '.$order->billing_company;

        $usership = $order->billing_last_name.' '.$order->billing_first_name.' '.$order->billing_company;
        $userbill = $order->shipping_last_name.' '.$order->shipping_first_name.' '.$order->shipping_company;
        $countryData = BillmateCountry::getSwedenData();
		$countries = $woocommerce->countries->get_allowed_countries();

		$countryname = $order->billing_country != 'SE' ? utf8_decode ($countries[$order->billing_country]) : 209;

  	    $bill_address = array(
		    'email'           => $order->billing_email,
		    'telno'           => $order->billing_phone,
		    'cellno'          => '',
		    'fname'           => utf8_decode ($order->billing_first_name),
		    'lname'           => utf8_decode ($order->billing_last_name),
		    'careof'          => utf8_decode ($order->billing_address_2),
		    'street'          => utf8_decode ($billmate_billing_address),
		    'house_number'    => isset($house_no)? $house_no: '',
		    'house_extension' => isset($house_ext)?$house_ext:'',
		    'zip'             => utf8_decode ($order->billing_postcode),
		    'city'            => utf8_decode ($order->billing_city),
		    'country'         => $countryname,
	    );
		if ( $order->get_shipping_method() == '' ) {
			$email = $order->billing_email;
			$telno = ''; //We skip the normal land line phone, only one is needed.
			$cellno = $order->billing_phone;
			$company = utf8_decode ($order->billing_company);
			$fname = utf8_decode ($order->billing_first_name);
			$lname = utf8_decode ($order->billing_last_name);
			$careof = utf8_decode ($order->billing_address_2);  //No care of; C/O.
			$street = utf8_decode ($billmate_billing_address); //For DE and NL specify street number in houseNo.
			$zip = utf8_decode ($order->billing_postcode);
			$city = utf8_decode ($order->billing_city);
			$country = utf8_decode ($countries[$order->billing_country]);
			$houseNo = utf8_decode ($billmate_billing_house_number); //For DE and NL we need to specify houseNo.
			$houseExt = utf8_decode ($billmate_billing_house_extension); //Only required for NL.
			$countryCode = $order->billing_country;
		} else {

			$email = $order->billing_email;
			$telno = ''; //We skip the normal land line phone; only one is needed.
			$cellno = $order->billing_phone;
			$company = utf8_decode ($order->shipping_company);
			$fname = utf8_decode ($order->shipping_first_name);
			$lname = utf8_decode ($order->shipping_last_name);
			$careof = utf8_decode ($order->shipping_address_2);  //No care of; C/O.
			$street = utf8_decode ($billmate_shipping_address); //For DE and NL specify street number in houseNo.
			$zip = utf8_decode ($order->shipping_postcode);
			$city = utf8_decode ($order->shipping_city);
			$country = utf8_decode ($countries[$order->shipping_country]);
			$houseNo = utf8_decode ($billmate_shipping_house_number); //For DE and NL we need to specify houseNo.
			$houseExt = utf8_decode ($billmate_shipping_house_extension); //Only required for NL.
			$countryCode = $order->shipping_country;
		}
		$countryname = $countryCode != 'SE' ? utf8_decode ($country) : 209;
	    $ship_address = array(
		    'email'           => $email,
		    'telno'           => $telno,
		    'cellno'          => $cellno,
		    'fname'           => $fname,
		    'lname'           => $lname,
		    'company'         => $company,
		    'careof'          => $careof,
		    'street'          => $street,
		    'house_number'    => '',
		    'house_extension' => '',
		    'zip'             => $zip,
		    'city'            => $city,
		    'country'         => $country,
	    );
		$pclass = -1;
		$languageCode = get_locale();

		$lang = explode('_', strtoupper($languageCode));
		$languageCode = $lang[0];
		$languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
		$languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
		$languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;
		$transaction = array(
			"order1"=>(string)$order_id,
			'order2'=>'',
			"comment"=>(string)"",
			"flags"=>0,
			'gender'=>1,
			"reference"=>"",
			"reference_code"=>"",
			"currency"=>get_woocommerce_currency(),//$countryData['currency'],
			"country"=>209,
			"language"=>$languageCode,
			"pclass"=>$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>empty($order->user_id ) || $order->user_id<= 0 ? time(): $order->user_id , "creditcard_data"=>$_POST ))
		);
		if( $this->authentication_method == 'sales') $transaction["extraInfo"][0]["status"] = 'Paid';
 		try {
			if(empty($goods_list)) return false;
    		//Transmit all the specified data, from the steps above, to Billmate.
			if( $addorder ){
				return $k->AddOrder('',$bill_address,$ship_address,$goods_list,$transaction);
			}


    		$result = $k->AddInvoice('',$bill_address,$ship_address,$goods_list,$transaction);
    		if( !is_array($result) ){
                $result = utf8_encode(strip_tags( $result ));
            	//Unknown response, store it in a database.
				$order->add_order_note( __($result, 'billmate') );
				$woocommerce->add_error( __((string)$result, 'billmate') );

				// Delete Transient
				delete_transient('billmate_cardpay_order_id_'.$order_id);
				return array(
						'result' 	=> 'failed',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('checkout'))))
				);
    		} else {
		        $invno = $result[0];
				$_SESSION['order_created'] = $result[0];
                $order->add_order_note( __('Billmate payment completed. Billmate Invoice number:', 'billmate') . $invno );

                // Payment complete
				$order->payment_complete();

				// Remove cart
				$woocommerce->cart->empty_cart();

				if(version_compare(WC_VERSION, '2.0.0', '<')){
					$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
				} else {
					$redirect = $this->get_return_url($order);;
				}
				// Delete Transient
				delete_transient('billmate_cardpay_order_id_'.$order_id);
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> $redirect
				);
    		}
		}catch(Exception $e) {
    		//The purchase was denied or something went wrong, print the message:
			$woocommerce->add_error( sprintf(__('%s (Error code: %s)', 'billmate'), utf8_encode($e->getMessage()), $e->getCode() ) );
			return;
		}
    }

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {

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
			'eid' => array(
							'title' => __( 'Eid', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Please enter your Billmate Eid; this is needed in order to take payment!', 'billmate' ),
							'default' => ''
						),
			'secret' => array(
							'title' => __( 'Shared Secret', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Please enter your Billmate Shared Secret; this is needed in order to take payment!', 'billmate' ),
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
	   	?><p><?php echo strlen($this->description)? $this->description: 'Visa & MasterCard.'; ?></p><?php
	}
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_order( $order_id );

		$languageCode = get_locale();

		$lang = explode('_', strtoupper($languageCode));
		$languageCode = $lang[0];
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
		$currency  = get_woocommerce_currency();
		$return_method = 'GET';

		$do_3dsecure   = $this->do_3dsecure;
		$prompt_name_entry = $this->prompt_name_entry;

		$capture_now   = $this->authentication_method == 'sales' ? 'YES' : 'NO';

		$pay_method= 'CARD';
		$amount    = $woocommerce->cart->total*100;

        $mac_str = $accept_url. $amount . $callback_url .$cancel_url.$capture_now . $currency. $do_3dsecure. $languageCode . $eid . $order_id . $pay_method . $prompt_name_entry . $return_method . $secret;

        $mac = hash ( "sha256", $mac_str );
	    echo <<<EOD
	    <form action="{$actionurl}" id="{$this->id}" method="POST">
	    <input type="hidden" name="currency" value="{$currency}" />
	    <input type="hidden" name="merchant_id" value="{$eid}" />
	    <input type="hidden" name="language" value="{$languageCode}" />
	    <input type="hidden" name="amount" value="{$amount}" />
	    <input type="hidden" name="order_id" value="{$order_id}" />
	    <input type="hidden" name="pay_method" value="{$pay_method}" />
	    <input type="hidden" name="callback_url" value="{$callback_url}" />
	    <input type="hidden" name="capture_now" value="{$capture_now}" />
	    <input type="hidden" name="prompt_name_entry" value="{$prompt_name_entry}" />
	    <input type="hidden" name="do_3d_secure" value="{$do_3dsecure}" />
	    <input type="hidden" name="accept_url" value="{$accept_url}" />
	    <input type="hidden" name="return_method" value="{$return_method}" />
	    <input type="hidden" name="cancel_url" value="{$cancel_url}" />
	    <input type="hidden" name="mac" value="{$mac}" />
	    </form>
	    <script type="text/javascript">
	        document.getElementById("{$this->id}").submit();
	    </script>
EOD;
		$this->sendBillmate($order_id, $order , true);
die;
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
