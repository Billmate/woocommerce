<?php @session_start();
require_once "commonfunctions.php";

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
		$this->lower_threshold		= ( isset( $this->settings['lower_threshold'] ) AND $this->settings['lower_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['lower_threshold'])) : '';
		$this->upper_threshold		= ( isset( $this->settings['upper_threshold'] ) AND $this->settings['upper_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['upper_threshold'])) : '';
		$this->invoice_fee_id		= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
		$this->allowed_countries = (isset($this->settings['billmatecard_allowed_countries'])) ? $this->settings['billmatecard_allowed_countries'] : array();
		$this->testmode				= ( isset( $this->settings['testmode'] ) && $this->settings['testmode'] == 'yes' ) ? true : false;
		$this->logo 				= get_option('billmate_common_logo');
		$this->de_consent_terms		= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->authentication_method= ( isset( $this->settings['authentication_method'] ) ) ? $this->settings['authentication_method'] : '';
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
		$billmate_invoice_icon = plugins_url( '/images/bm_cards.png', __FILE__ );

		// Apply filters to Country and language
		$this->billmate_country 		= apply_filters( 'billmate_country', $billmate_country );
		$this->billmate_language 		= apply_filters( 'billmate_language', $billmate_language );
		$this->billmate_currency 		= apply_filters( 'billmate_currency', $billmate_currency );
		$this->icon 				= apply_filters( 'billmate_cardpay_icon', $billmate_invoice_icon );

		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_payment_method_change_admin',
            'subscription_payment_method_change_customer',
			'subscription_date_changes'
		);

		// Actions
		add_action( 'valid-billmate-cardpay-standard-ipn-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_api_wc_gateway_billmate_cardpay', array( $this, 'check_ipn_response' ) );
		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_receipt_billmate', array(&$this, 'receipt_page'));

		add_action('woocommerce_scheduled_subscription_payment_'.$this->id,array($this,'process_scheduled_payment'),10,3);
        add_action('admin_enqueue_scripts',array(&$this,'injectscripts'));
		$this->subscription_active = false;
		if(!is_admin()){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		}
		if(is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')){
			$this->subscription_active = true;
		}

        //add_action('wp_footer', array(&$this, 'billmate_invoice_terms_js'));

	}
    public function injectscripts(){
        if( is_admin()){
            wp_enqueue_script( 'jquery' );
            wp_register_script('billmateadmin.js',plugins_url('/js/billmateadmin.js',__FILE__),array('jquery'),'1.0',true);
            wp_enqueue_script('billmateadmin.js');
        }
    }

	function check_ipn_response() {

        $checkoutMessageCancel = __('The card payment has been canceled before it was processed. Please try again or choose a different payment method.','billmate');
        $checkoutMessageFail = __('Unfortunately your card payment was not processed with the provided card details. Please try again or choose another payment method.','billmate');
        $transientPrefix = 'billmate_cardpay_order_id_';

        $config = array(
            'testmode' => $this->testmode,
            'method_id' => $this->id,
            'method_title' => $this->title,
            'checkoutMessageCancel' => $checkoutMessageCancel,
            'checkoutMessageFail' => $checkoutMessageFail,
            'transientPrefix' => $transientPrefix,
        );

        $this->common_check_ipn_response( $config );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
		$countries = new WC_Countries();
		if(version_compare(WC_VERSION, '2.2.0', '<')){
			$available = $countries->countries;
		}else {
			$available = $countries->get_countries();
		}

		if(version_compare(WC_VERSION, '2.2.0', '<')){
			$order_statuses['default'] = __('Default','billmate');

			foreach(get_terms('shop_order_status',array( 'hide_empty' => 0 ) ) as $status ){
				if(is_object($status)) {
					$order_statuses[$status->slug] = $status->name;
				}
			}
		} else {
			$order_status = wc_get_order_statuses();
			$order_statuses['default'] = __('Default', 'billmate');
			foreach ($order_status as $key => $value) {
				$order_statuses[$key] = $value;
			}
		}
	   	$this->form_fields = apply_filters('billmate_invoice_form_fields', array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Cardpay', 'billmate' ),
							'default' => 'no'
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
			'testmode' => array(
							'title' => __( 'Test Mode', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Test Mode.', 'billmate' ),
							'default' => 'no'
						),
			'billmatecard_allowed_countries' => array(
				'title' 		=> __( 'Allowed Countries', 'billmate' ),
				'type' 			=> 'multiselect',
				'description' 	=> __( 'Billmate Card activated for customers in these countries, Leave blank to allow all.', 'billmate' ),
				'class'			=> 'chosen_select',
				'css' 			=> 'min-width:350px;',
				'options'		=> $available,
				'default' => ''
			),
			'order_status' => array(
				'title' => __('Custom approved order status','billmate'),
				'type' => 'select',
				'description' => __('Choose a special order status for Billmate Cardpay, if you want to use a own status and not WooCommerce built in.','billmate'),
				'default' => 'default',
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
            <p>
                <a href="https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf" target="_blank">Installationsmanual Billmate Modul ( Manual Svenska )</a><br />
                <a href="https://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf" target="_blank">Installation Manual Billmate ( Manual English )</a>
            </p>

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

            if(is_checkout() == false && is_checkout_pay_page() == false) {
                // Not on store checkout page
                return true;
            }

			// if (!is_ssl()) return false;

			// Currency check
			// if (!in_array(get_option('woocommerce_currency'), array('DKK', 'EUR', 'NOK', 'SEK'))) return false;

			// Base country check
			//if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;

			// Required fields check
			if (!$this->eid || !$this->secret) return false;

			// Cart totals check - Lower threshold
			if ( $this->lower_threshold !== '' ) {
				if ( WC_Payment_Gateway::get_order_total() < $this->lower_threshold ) return false;
			}

			// Cart totals check - Upper threshold
			if ( $this->upper_threshold !== '' ) {
				if ( WC_Payment_Gateway::get_order_total() > $this->upper_threshold ) return false;
			}
			if(!empty($this->allowed_countries)){

				$order_id = absint( get_query_var( 'order-pay' ) );
				if(0 < $order_id){
					$order = wc_get_order( $order_id );
					$address = $order->get_address();
					$country = $address['country'];
				} else {
					$country = "";
                    if(isset($woocommerce) &&
                        is_object($woocommerce) &&
                        isset($woocommerce->customer) &&
                        is_object($woocommerce->customer)
                    ) {
                        if(version_compare(WC_VERSION, '3.0.0', '>=') AND method_exists($woocommerce->customer, "get_billing_country")) {
                            $country = $woocommerce->customer->get_billing_country();
                        } elseif(method_exists($woocommerce->customer, "get_country")) {
                            $country = $woocommerce->customer->get_country();
                        }
                    }
				}
				if(!in_array($country,$this->allowed_countries))
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
	   	?>
        <?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'billmate'); ?></p><?php endif; ?>
        <p><?php echo strlen($this->description)? $this->description: __('Visa & Mastercard','billmate'); ?></p><?php
	}

	/**
	 * Process scheduled payment
	 */
	function process_scheduled_payment($amount_to_charge,$order){
		global $woocommerce;


		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

		$subscription = end($subscriptions);

        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
            $parent_order = $subscription->get_parent();
            $parent_id = $parent_order->get_id();
        } else {
            $parent_id = $subscription->order->id;
        }

        $billmateOrder = new BillmateOrder($order);
        $billmateOrder->setAllowedCountries($woocommerce->countries->get_allowed_countries());

		$billmateToken = get_post_meta($parent_id,'_billmate_card_token',true);
		if(empty($billmateToken))
			$billmateToken = get_post_meta($parent_id,'billmate_card_token',true);

		$total = 0;
		$totalTax = 0;
		$prepareDiscount = array();

        $accept_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'success', 'recurring' => '1'));
        $callback_url   = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'recurring' => '1'));
        $cancel_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'cancel'));

		$url = parse_url($accept_url);
		$language = explode('_',get_locale());
		$orderValues['PaymentData'] = array(
			'method' => 8,
			'currency' => get_woocommerce_currency(),
			'language' => strtolower($language[0]),
			'country' => $this->billmate_country,
			'autoactivate' => 1,
			'orderid' => preg_replace('/#/','',$order->get_order_number()),
			'logo' => (strlen($this->logo)> 0) ? $this->logo : ''

		);

        $orderValues['PaymentInfo'] = $billmateOrder->getPaymentInfoData();

        $orderValues['Customer']['nr'] = $billmateOrder->getCustomerNrData();
        $orderValues['Customer']['Billing'] = $billmateOrder->getCustomerBillingData();
        $orderValues['Customer']['Shipping'] = $billmateOrder->getCustomerShippingData();

		$orderValues['Card'] = array(
			'accepturl' => $accept_url,
			'callbackurl' => $callback_url,
			'cancelurl' => $cancel_url,
			'3dsecure' => (isset($this->do_3dsecure) AND $this->do_3dsecure != 'NO') ? 1 : 0,
			'promptname' => (isset($this->prompt_name_entry) AND $this->prompt_name_entry == 'YES') ? 1 : 0,
			'recurringnr' => $billmateToken,
			'returnmethod' => ($url['scheme'] == 'https') ? 'POST' : 'GET'
		);

        /* Articles, fees, discount */
        $orderValues['Articles'] = $billmateOrder->getArticlesData();
        $total += $billmateOrder->getArticlesTotal();
        $totalTax += $billmateOrder->getArticlesTotalTax();

        $shippingPrices = $billmateOrder->getCartShipping();
        if ($shippingPrices['price'] > 0) {
            $orderValues['Cart']['Shipping'] = $billmateOrder->getCartShippingData();
            $total += $shippingPrices['price'];
            $totalTax += $shippingPrices['tax'];
        }

		$round = 0;//(round($order->order_total,2)*100) - round($total + $totalTax,0);


		$orderValues['Cart']['Total'] = array(
			'withouttax' => round($total),
			'tax' => round($totalTax,0),
			'rounding' => $round,
			'withtax' => round($total) + round($totalTax,0) + $round
		);

		$k = new Billmate( $this->eid, $this->secret, true, $this->testmode, false, $this->getRequestMeta() );
		$result = $k->addPayment($orderValues);
		if(isset($result['code'])){
			wc_bm_errors(__($result['message'],'billmate'));
			$order->update_status('failed',sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
			$order->add_order_note(sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
			return;
		}else{
			if($result['status'] == 'Paid'){
				add_post_meta($order->id,'billmate_invoice_id',$result['number']);

				$order->add_order_note(sprintf(__("Subscription Payment Successful. Invoice ID: %s ",'billmate'), $result['number']));
				$order->payment_complete($result['number']);
				WC_Subscriptions_Manager::process_subscription_payments_on_order($order);
				return array(
					'result' => 'success'
				);
			} else {
				wc_bm_errors(__($result['message'],'billmate'));
				$order->update_status('failed',sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
				$order->add_order_note(sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
				return;
			}
		}



	}
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_order( $order_id );

        $billmateOrder = new BillmateOrder($order);
        $billmateOrder->setAllowedCountries($woocommerce->countries->get_allowed_countries());

        $isSubscriptionOrder = false;

        $wcsVersion = 0;
        if (property_exists('WC_Subscriptions', 'version')) {
            $wcsVersion = WC_Subscriptions::$version;
        }

        if (version_compare($wcsVersion, '2.0.0', '>=')) {
            if (wcs_order_contains_subscription($order)) {
                $isSubscriptionOrder = true;
            }


            if (wcs_order_contains_subscription($order, array( 'parent', 'renewal', 'resubscribe', 'switch'))) {
                $isSubscriptionOrder = true;
            } else {
                /** Order is no subscription, check parent order if subscription in case of changing card information */
                $_orderParentId = $order->get_parent_id();
                if (is_numeric($_orderParentId) AND $_orderParentId > 0 AND wcs_order_contains_subscription($_orderParentId, array( 'parent', 'renewal', 'resubscribe', 'switch'))) {
                    $isSubscriptionOrder = true;
                }
            }

        } else {
            if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) {
                $isSubscriptionOrder = true;
            }
        }

		if($this->subscription_active == true && $isSubscriptionOrder == true) {

				$total = 0;
				$totalTax = 0;
				$prepareDiscount = array();
				$productTax = 0;

                $accept_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'success', 'recurring' => '1'));
                $callback_url   = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'recurring' => '1'));
                $cancel_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'cancel'));

				$url = parse_url($accept_url);
				$language = explode('_',get_locale());
				$orderValues['PaymentData'] = array(
					'method' => 8,
					'currency' => get_woocommerce_currency(),
					'language' => strtolower($language[0]),
					'country' => $this->billmate_country,
					'autoactivate' => 1,
					'orderid' => preg_replace('/#/','',$order->get_order_number()),
					'logo' => (strlen($this->logo)> 0) ? $this->logo : ''

				);

                $orderValues['PaymentInfo'] = $billmateOrder->getPaymentInfoData();

                $orderValues['Customer']['nr'] = $billmateOrder->getCustomerNrData();
                $orderValues['Customer']['Billing'] = $billmateOrder->getCustomerBillingData();
                $orderValues['Customer']['Shipping'] = $billmateOrder->getCustomerShippingData();

				$orderValues['Card'] = array(
					'accepturl' => $accept_url,
					'callbackurl' => $callback_url,
					'cancelurl' => $cancel_url,
					'recurring' => 1,
					'returnmethod' => ($url['scheme'] == 'https') ? 'POST' : 'GET'
				);

                $total = 0;
                $totalTax = 0;

                /* Articles, fees, discount */
                $orderValues['Articles'] = $billmateOrder->getArticlesData();
                $total += $billmateOrder->getArticlesTotal();
                $totalTax += $billmateOrder->getArticlesTotalTax();

                $shippingPrices = $billmateOrder->getCartShipping();
                if ($shippingPrices['price'] > 0) {
                    $orderValues['Cart']['Shipping'] = $billmateOrder->getCartShippingData();
                    $total += $shippingPrices['price'];
                    $totalTax += $shippingPrices['tax'];
                }


                /**
                 * When initiate subscription or update card info for active subscription.
                 * This order will be automatic credited when customer return to store
                 */
                if ($order->get_total() == 0 OR 0 == WC_Payment_Gateway::get_order_total()) {
                    $orderValues['Articles'] = array();
                    $orderValues['Articles'][] = array(
                        'quantity'    => (int)1,
                        'artnr'       => "",
                        'title'       => __('Transaction to be credited', 'billmate'),
                        'aprice'      => 100,
                        'taxrate'     => 0,
                        'discount'    => (float)0,
                        'withouttax'  => 100
                    );
                    if (isset($orderValues['Cart']) AND is_array($orderValues['Cart']) AND isset($orderValues['Cart']['Shipping'])) {
                        unset($orderValues['Cart']['Shipping']);
                    }
                    $total = 100;
                    $totalTax = 0;
                }


				$checkoutTotal = WC_Payment_Gateway::get_order_total();
				$round = (round($checkoutTotal * 100)) - round($total + $totalTax,0);
				$round += ($checkoutTotal == 0) ? 100 : 0;

				$orderValues['Cart']['Total'] = array(
					'withouttax' => round($total),
					'tax' => round($totalTax,0),
					'rounding' => round($round),
					'withtax' => round($total) + round($totalTax,0) + $round
				);
				$k = new Billmate( $this->eid, $this->secret, true, $this->testmode, false, $this->getRequestMeta() );
				$result = $k->addPayment($orderValues);
				if(isset($result['code'])){
					wc_bm_errors(__($result['message']));
					return;
				}

				return array(
					'result' => 'success',
					'redirect' => $result['url']
				);


			// Reqular payment
		} else {


			$language = explode('_',get_locale());
			if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));



            $languageCode = strtoupper($language[0]);
            $languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
            $languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
            $languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;
            $languageCode = ($languageCode == 'NB' OR $languageCode == 'NN' ) ? 'NO' : $languageCode;

			$orderValues = array();
			$orderValues['PaymentData'] = array(
				'method' => 8,
				'currency' => get_woocommerce_currency(),
				'language' => strtolower($languageCode),
				'country' => $this->billmate_country,
				'autoactivate' => ( $this->authentication_method == 'sales') ? 1 : 0,
				'orderid' => preg_replace('/#/','',$order->get_order_number()),
				'logo' => (strlen($this->logo)> 0) ? $this->logo : ''
			);

            $accept_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'success'));
            $callback_url   = billmate_set_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay'));
            $cancel_url     = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Cardpay', 'payment' => 'cancel'));

			$url = parse_url($accept_url);

			$orderValues['Card'] = array(
				'accepturl' => $accept_url,
				'callbackurl' => $callback_url,
				'cancelurl' => $cancel_url,
				'returnmethod' => ($url['scheme'] == 'https') ? 'POST' : 'GET'
			);


            $orderValues['Customer']['nr'] = $billmateOrder->getCustomerNrData();
            $orderValues['Customer']['Billing'] = $billmateOrder->getCustomerBillingData();
            $orderValues['Customer']['Shipping'] = $billmateOrder->getCustomerShippingData();

            $total = 0;
            $totalTax = 0;

            /* Articles, fees, discount */
            $orderValues['Articles'] = $billmateOrder->getArticlesData();
            $total += $billmateOrder->getArticlesTotal();
            $totalTax += $billmateOrder->getArticlesTotalTax();

            $shippingPrices = $billmateOrder->getCartShipping();
            if ($shippingPrices['price'] > 0) {
                $orderValues['Cart']['Shipping'] = $billmateOrder->getCartShippingData();
                $total += $shippingPrices['price'];
                $totalTax += $shippingPrices['tax'];
            }

		$round = round(WC_Payment_Gateway::get_order_total()*100) - round($total + $totalTax,0);


		$orderValues['Cart']['Total'] = array(
			'withouttax' => round($total),
			'tax' => round($totalTax,0),
			'rounding' => round($round),
			'withtax' => round($total + $totalTax + $round)
		);
		$k = new Billmate( $this->eid, $this->secret, true, $this->testmode, false, $this->getRequestMeta() );
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
