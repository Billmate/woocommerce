<?php

/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-01
 * Time: 09:08
 */
class WC_Gateway_Billmate_Checkout extends WC_Gateway_Billmate
{
    public function __construct()
    {
        global $woocommerce;
        parent::__construct();


        $this->id			= 'billmate_checkout';
        $this->method_title = __('Billmate Checkout', 'billmate');
        $this->has_fields 	= false;

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $this->enabled				= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';

        $this->eid					= get_option('billmate_common_eid');//( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
        $this->secret				= get_option('billmate_common_secret');//( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
        $this->logo 				= get_option('billmate_common_logo');
        $this->terms_url            = (isset($this->settings['terms_url'])) ? $this->settings['terms_url'] : false;
        $this->checkout_url            = (isset($this->settings['checkout_url'])) ? $this->settings['checkout_url'] : false;

        $this->testmode				= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';


        /* 1.6.6 */
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

        /* 2.0.0 */
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action('woocommerce_receipt_billmate', array(&$this, 'receipt_page'));

        add_action('init','register_billmate_incomplete_order_status');
        add_filter( 'wc_order_statuses', array( $this, 'add_billmate_incomplete_to_order_statuses' ) );

        // Update Address from Iframe
        add_action( 'wp_ajax_billmate_update_address', array(
            $this,
            'billmate_update_address'
        ) );
        add_action( 'wp_ajax_nopriv_billmate_update_address', array(
            $this,
            'billmate_update_address'
        ) );

        // Update Address from Iframe
        add_action( 'wp_ajax_billmate_set_method', array(
            $this,
            'billmate_set_method'
        ) );
        add_action( 'wp_ajax_nopriv_billmate_set_method', array(
            $this,
            'billmate_set_method'
        ) );
        add_action('wp_ajax_billmate_complete_order',array($this,'billmate_complete_order'));
        add_action('wp_ajax_nopriv_billmate_complete_order',array($this,'billmate_complete_order'));

        // Cart quantity
        /*add_action( 'wp_ajax_billmate_checkout_cart_callback_update', array(
            $this,
            'billmate_checkout_cart_callback_update'
        ) );
        add_action( 'wp_ajax_nopriv_billmate_checkout_cart_callback_update', array(
            $this,
            'billmate_checkout_cart_callback_update'
        ) );*/
        // Cart remove
        add_action( 'wp_ajax_billmate_checkout_remove_item', array(
            $this,
            'billmate_checkout_cart_callback_remove'
        ) );
        add_action( 'wp_ajax_nopriv_billmate_checkout_remove_item', array(
            $this,
            'billmate_checkout_cart_callback_remove'
        ) );
        // Shipping method selector
        /*add_action( 'wp_ajax_billmate_checkout_shipping_callback', array( $this, 'billmate_checkout_shipping_callback' ) );
        add_action( 'wp_ajax_nopriv_billmate_checkout_shipping_callback', array(
            $this,
            'billmate_checkout_shipping_callback'
        ) );*/

        add_filter('woocommerce_get_checkout_url',array($this,'change_to_bco'),20);



    }

    function change_to_bco($url){
        if(!is_admin()) {
            $checkout_url = get_post($this->checkout_url);

            return $checkout_url->guid;
        }
        return $url;
    }

    function add_billmate_incomplete_to_order_statuses($order_statuses){
        if ( ! is_account_page() ) {
            $order_statuses['wc-bm-incomplete'] = 'Billmate Checkout Incomplete';
        }

        return $order_statuses;
    }

    function add_billmate_incomplete_order_statuses(){
        if ( 'yes' == $this->testmode ) {
            $show_in_admin_status_list = true;
        } else {
            $show_in_admin_status_list = false;
        }
        register_post_status( 'wc-bm-incomplete', array(
            'label'                     => 'Billmate Checkout incomplete',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => $show_in_admin_status_list,
            'label_count'               => _n_noop( 'Billmate Checkout incomplete <span class="count">(%s)</span>', 'Billmate Checkout incomplete <span class="count">(%s)</span>' ),
        ) );
    }

    function add_invoice_fee_process() {
        global $woocommerce;

        // Only run this if Billmate Invoice is the choosen payment method and this is WC +2.0

            $invoice_fee = new WC_Gateway_Billmate_Invoice;
            $tax = new WC_Tax();
            $rate = $tax->get_rates($invoice_fee->invoice_fee_tax_class);
            $rate = array_pop($rate);
            $rate = $rate['rate'];
            $new_fee            = new stdClass();
            $new_fee->id        = sanitize_title( __('Invoice fee','billmate') );
            $new_fee->name      = esc_attr(__('Invoice fee','billmate'));
            $new_fee->amount    = (float) esc_attr( $invoice_fee->invoice_fee );
            $new_fee->tax_class = $invoice_fee->invoice_fee_tax_class;
            $new_fee->taxable   = true;
            $new_fee->tax       = 0;
            $new_fee->tax_data  = array();

            return $new_fee;


    }


    private function get_fee_id($order,$name)
    {
        $fees = $order->get_fees();
        foreach($fees as $key=>$fee)
        {
            if($fee['name']==$name)
                return $key;
        }

    }
    function billmate_set_method(){

        $connection = new BillMate($this->eid,$this->secret,true,$this->testmode == 'yes');
        $result = $connection->getCheckout(array('PaymentData' => array('hash' => $_REQUEST['hash'])));
        if(!isset($result['code'])) {
            $class = '';

            if($result['PaymentData']['method'] == 1){

                $invoice_fee = new WC_Gateway_Billmate_Invoice;
                $tax = new WC_Tax();
                $rate = $tax->get_rates($invoice_fee->invoice_fee_tax_class);
                $rate = array_pop($rate);
                $rate = $rate['rate'];

                WC()->cart->add_fee(__('Invoice fee','billmate'),$invoice_fee->invoice_fee,true,$invoice_fee->invoice_fee_tax_class);
            }
            $orderId = $this->create_order();
            $order = wc_get_order( $orderId );
            // Clear invoice fee
            switch ($result['PaymentData']['method']) {
                case 1:
                    $method = 'billmate_invoice';
                    //$class = new WC_Gateway_Billmate_Invoice();

                    break;
                case 4:
                    $method = 'billmate_partpayment';
                    //$class = new WC_Gateway_Billmate_Partpayment();
                    break;
                case 8:
                    $method = 'billmate_cardpay';
                    $result['PaymentData']['accepturl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay&payment=success';
                    $result['PaymentData']['callbackurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay';
                    $result['PaymentData']['cancelurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay&payment=cancel';
                    $result['PaymentData']['returnmethod'] = is_ssl() ? 'POST' : 'GET';
                    //$class = new WC_Gateway_Billmate_Cardpay();
                    break;
                case 16:
                    $method = 'billmate_bankpay';
                    $result['PaymentData']['accepturl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay&payment=success';
                    $result['PaymentData']['callbackurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay';
                    $result['PaymentData']['cancelurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay&payment=cancel';
                    $result['PaymentData']['returnmethod'] = is_ssl() ? 'POST' : 'GET';
                    //$class = new WC_Gateway_Billmate_Bankpay();
                    break;
            }
            //error_log('className'.get_class($class));


            $available_gateways = WC()->payment_gateways->payment_gateways();
            $payment_method = $available_gateways[$method];
            $order->set_payment_method($payment_method);
            $order->calculate_taxes();
            $order->calculate_shipping();
            $order->calculate_totals();
            $this->updateCheckout($result, $order);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    function get_order(){
        return wc_get_order(WC()->session->get( 'billmate_checkout_order' ));
    }
    
    function billmate_complete_order(){
        $order = $this->get_order();
        $connection = new BillMate($this->eid,$this->secret,true,$this->testmode == 'yes');

        $result = $connection->getCheckout(array('PaymentData' => array('hash' => $_REQUEST['hash'])));
        if(!isset($result['code'])){
            switch (strtolower($result['PaymentData']['order']['status'])){
                case 'pending':
                    $order->update_status( 'pending' );
                    $order->add_order_note( __('Order is PENDING APPROVAL by Billmate. Please visit Billmate Online for the latest status on this order. Billmate Invoice number: ', 'billmate') .$result['PaymentData']['order']['number']);
                    add_post_meta($order->id,'billmate_invoice_id',$result['PaymentData']['order']['number']);
                    // Remove cart
                    WC()->cart->empty_cart();
                    if(version_compare(WC_VERSION, '2.0.0', '<')){
                        $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                    } else {
                        $redirect = $this->get_return_url($order);
                    }

                    $response = array('url' => $redirect);
                    wp_send_json_success($response);
                    break;
                case 'created':
                case 'paid':
                    $order->update_status( 'pending' );
                    $order->payment_complete($result['PaymentInfo']['number']);
                    $order->add_order_note( __('Billmate payment completed. Billmate Invoice number:', 'billmate') .$result['PaymentData']['order']['number'] );
                    add_post_meta($order->id,'billmate_invoice_id',$result['PaymentData']['order']['number']);
                    // Remove cart
                    WC()->cart->empty_cart();
                    if(version_compare(WC_VERSION, '2.0.0', '<')){
                        $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                    } else {
                        $redirect = $this->get_return_url($order);
                    }

                    $response = array('url' => $redirect);
                    wp_send_json_success($response);
                break;
                case 'cancelled':
                    break;
                case 'failed':
                    break;
            }
        }


    }

    function billmate_update_address(){
        global $woocommerce;

        $connection = new BillMate($this->eid,$this->secret,true,$this->testmode == 'yes');
        $result = $connection->getCheckout(array('PaymentData' => array('hash' => $_REQUEST['hash'])));
        if(!isset($result['code'])){
            WC()->session->set( 'billmate_checkout_hash',$_REQUEST['hash'] );
            if($result['PaymentData']['method'] == 1){

                $invoice_fee = new WC_Gateway_Billmate_Invoice;
                $tax = new WC_Tax();
                $rate = $tax->get_rates($invoice_fee->invoice_fee_tax_class);
                $rate = array_pop($rate);
                $rate = $rate['rate'];

                WC()->cart->add_fee(__('Invoice fee','billmate'),$invoice_fee->invoice_fee,true,$invoice_fee->invoice_fee_tax_class);
            }
            $orderId = $this->create_order();
            $order = wc_get_order( $orderId );

            $billing_address = array(
                'first_name' => $result['Customer']['Billing']['firstname'],
                'last_name'  => $result['Customer']['Billing']['lastname'],
                'company'    => $result['Customer']['Billing']['company'],
                'email'      => $result['Customer']['Billing']['email'],
                'phone'      => $result['Customer']['Billing']['phone'],
                'address_1'  => $result['Customer']['Billing']['street'],
                'address_2'  => '',
                'city'       => $result['Customer']['Billing']['city'],
                'state'      => '',
                'postcode'   => $result['Customer']['Billing']['zip'],
                'country'    => $result['Customer']['Billing']['country']
            );
            if (!isset($result['Customer']['Shipping']) ||(isset($result['Customer']['Shipping']) && count($result['Customer']['Shipping']) == 0)) {
                $result['Customer']['Shipping'] = $result['Customer']['Billing'];
            }
            $shipping_address = array(
                'first_name' => $result['Customer']['Shipping']['firstname'],
                'last_name'  => $result['Customer']['Shipping']['lastname'],
                'company'    => $result['Customer']['Shipping']['company'],
                'email'      => $result['Customer']['Shipping']['email'],
                'phone'      => $result['Customer']['Shipping']['phone'],
                'address_1'  => $result['Customer']['Shipping']['street'],
                'address_2'  => '',
                'city'       => $result['Customer']['Shipping']['city'],
                'state'      => '',
                'postcode'   => $result['Customer']['Shipping']['zip'],
                'country'    => $result['Customer']['Shipping']['country']
            );
            $order->set_address($billing_address,'billing');
            $order->set_address($shipping_address,'shipping');

            switch ($result['PaymentData']['method']) {
                case 1:
                    $method = 'billmate_invoice';
                    //$class = new WC_Gateway_Billmate_Invoice();

                    break;
                case 4:
                    $method = 'billmate_partpayment';
                    //$class = new WC_Gateway_Billmate_Partpayment();
                    break;
                case 8:
                    $method = 'billmate_cardpay';
                    $result['PaymentData']['accepturl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay&payment=success';
                    $result['PaymentData']['callbackurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay';
                    $result['PaymentData']['cancelurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Cardpay&payment=cancel';
                    $result['PaymentData']['returnmethod'] = is_ssl() ? 'POST' : 'GET';
                    //$class = new WC_Gateway_Billmate_Cardpay();
                    break;
                case 16:
                    $method = 'billmate_bankpay';
                    $result['PaymentData']['accepturl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay&payment=success';
                    $result['PaymentData']['callbackurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay';
                    $result['PaymentData']['cancelurl'] = trailingslashit (home_url()) . '?wc-api=WC_Gateway_Billmate_Bankpay&payment=cancel';
                    $result['PaymentData']['returnmethod'] = is_ssl() ? 'POST' : 'GET';
                    //$class = new WC_Gateway_Billmate_Bankpay();
                    break;
            }
            $available_gateways = WC()->payment_gateways->payment_gateways();
            $payment_method = $available_gateways[$method];
            $order->set_payment_method($payment_method);

            $order->calculate_taxes();
            $order->calculate_shipping();
            $order->calculate_totals();
            $this->updateCheckout($result, $order);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    function billmate_checkout_cart_callback_update() {
        if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'billmate_checkout_nonce' ) ) {
            exit( 'Nonce can not be verified.' );
        }
        global $woocommerce;
        $updated_item_key = $_REQUEST['cart_item_key'];
        $new_quantity     = $_REQUEST['new_quantity'];
        if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
            define( 'WOOCOMMERCE_CART', true );
        }
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
            define( 'WOOCOMMERCE_CHECKOUT', true );
        }
        $cart_items      = $woocommerce->cart->get_cart();
        $updated_item    = $cart_items[ $updated_item_key ];
        $updated_product = wc_get_product( $updated_item['product_id'] );


        $billmete_sid = $woocommerce->session->get( 'billmate_sid' );
        $woocommerce->cart->set_quantity( $updated_item_key, $new_quantity );
        $woocommerce->cart->calculate_shipping();
        $woocommerce->cart->calculate_fees();
        $woocommerce->cart->calculate_totals();
        $this->create_order();
        $this->get_url();
        $data['success'] = true;
        $data['update'] = true;

        wp_send_json_success( $data );
        wp_die();
    }

    function create_order( $customer_email = '' ) {
        if ( is_user_logged_in() ) {
            global $current_user;
            $customer_email = $current_user->user_email;
        }
        if ( '' == $customer_email ) {
            $customer_email = 'no-reply@billmate.se';
        }
        if ( ! is_email( $customer_email ) ) {
            return;
        }
        // Check quantities
        global $woocommerce;
        $result = $woocommerce->cart->check_cart_item_stock();
        if ( is_wp_error( $result ) ) {
            return $result->get_error_message();
        }

        if ( $customer_email ) {
            // Customer is logged in
            $orderId = $this->check_if_order_should_be_updated_or_created($customer_email);
        } else {
            // Customer is guest.
            $orderId = $this->check_if_order_should_be_updated_or_created();
        }
        return $orderId;
    }

    function create_wc_order(){


        // Customer accounts.
        $customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

        // Order data.
        $order_data = array(
            'status'      => apply_filters( 'billmate_checkout_incomplete_order_status', 'bm-incomplete' ),
            'customer_id' => $customer_id,
            'created_via' => 'billmate_checkout',
        );
        error_log('$order_data'.print_r($order_data,true));

        // Create the order.
        $order = wc_create_order( $order_data );
        error_log('$order'.print_r($order,true));

        if ( is_wp_error( $order ) ) {
            throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
        }


        return $order;
    }

    function check_if_order_should_be_updated_or_created($customer_email = ''){
        if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
            define( 'WOOCOMMERCE_CART', true );
        }

        if ( WC()->session->get( 'billmate_checkout_order' ) && wc_get_order( WC()->session->get( 'billmate_checkout_order' ) ) ) {
            $orderid = WC()->session->get( 'billmate_checkout_order' );
            $order   = wc_get_order( $orderid );
        } else {
            // Create order in WooCommerce if we have an email.
            $order = $this->create_wc_order();
            update_post_meta( $order->id, '_billmatecheckout_incomplete_customer_email', $customer_email, true );
            WC()->session->set( 'billmate_checkout_order', $order->id );
        }

        if(isset($order)){
            $order->remove_order_items();

            $order_items = $order->get_items( array( 'line_item' ) );
            if ( empty( $order_items ) ) {
                foreach ( WC()->cart->get_cart() as $key => $values ) {
                    $item_id = $order->add_product( $values['data'], $values['quantity'], array(
                        'variation' => $values['variation'],
                        'totals'    => array(
                            'subtotal'     => $values['line_subtotal'],
                            'subtotal_tax' => $values['line_subtotal_tax'],
                            'total'        => $values['line_total'],
                            'tax'          => $values['line_tax'],
                            'tax_data'     => $values['line_tax_data'],
                        ),
                    ) );

                    if ( ! $item_id ) {


                        throw new Exception( __( 'Error: Unable to add item. Please try again.', 'woocommerce' ) );
                    }

                    // Allow plugins to add order item meta.
                    do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $key );
                }
            }


            $order_fees = $order->get_items( array( 'fee' ) );
            if ( empty( $order_fees ) ) {
                foreach ( WC()->cart->get_fees() as $key => $fee ) {
                    $item_id = $order->add_fee( $fee );

                    if ( ! $item_id ) {


                        throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                    }


                    do_action( 'woocommerce_add_order_fee_meta', $order->id, $item_id, $fee, $key );
                }
            }

            $order_shipping = $order->get_items( array( 'shipping' ) );
            if ( empty( $order_shipping ) ) {
                WC()->cart->calculate_shipping();
                WC()->cart->calculate_fees();
                WC()->cart->calculate_totals();

                $this_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

                // Store shipping for all packages.
                foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
                    if ( isset( $package['rates'][ $this_shipping_methods[ $package_key ] ] ) ) {
                        $item_id = $order->add_shipping( $package['rates'][ $this_shipping_methods[ $package_key ] ] );

                        if ( ! $item_id ) {

                            throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                        }

                    }
                }
            }

            $order_taxes = $order->get_items( array( 'tax' ) );
            if ( empty( $order_taxes ) ) {
                foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_id ) {
                    if ( $tax_id && ! $order->add_tax( $tax_id, WC()->cart->get_tax_amount( $tax_id ), WC()->cart->get_shipping_tax_amount( $tax_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_id ) {

                        throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
                    }
                }
            }

            $order_coupons = $order->get_items( array( 'coupon' ) );
            if ( empty( $order_coupons ) ) {
                foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
                    if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) ) ) {


                        throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                    } else {

                    }
                }
            }

            $available_gateways = WC()->payment_gateways->payment_gateways();
            $payment_method     = $available_gateways['billmate_checkout'];

            $order->set_payment_method( $payment_method );

            if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
                define( 'WOOCOMMERCE_CHECKOUT', true );
            }

            if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
                define( 'WOOCOMMERCE_CART', true );
            }

            WC()->cart->calculate_shipping();
            WC()->cart->calculate_fees();
            WC()->cart->calculate_totals();

            $order->calculate_totals();

            if ( email_exists( $customer_email ) ) {
                $user    = get_user_by( 'email', $customer_email );
                $user_id = $user->ID;
                update_post_meta( $order->id, '_customer_user', $user_id );
            }
            do_action( 'woocommerce_checkout_update_order_meta', $order->id, array() );
        }
        return $order->id;
    }

    function get_url(){
        $orderId = $this->create_order();
        if( WC()->session->get( 'billmate_checkout_hash' )){
            $billmate = new Billmate($this->eid,$this->secret,true, $this->testmode == 'yes',false);

            $checkout = $billmate->getCheckout(array('PaymentData' => array('hash' => WC()->session->get( 'billmate_checkout_hash' ))));
            if(!isset($checkout['code'])){
                return $checkout['PaymentData']['url'];
            }
        } else {
            $result = $this->initCheckout($orderId);
            if(!isset($result['code'])){
                return $result['url'];
            }

        }
        
    }

    function initCheckout($orderId = null){
        global $woocommerce;
        $billmate = new Billmate($this->eid,$this->secret,true, $this->testmode == 'yes',false);
        $order = new WC_order( $orderId );

        $orderValues = array();
        $terms = get_post($this->terms_url);
        $orderValues['CheckoutData'] = array(
            'windowmode' => 'iframe',
            'sendreciept' => 'yes',
            'terms' => $terms->guid
        );
        $lang = explode('_',get_locale());


        $location = wc_get_base_location();
        $orderValues['PaymentData'] = array(
            'method' => 93,
            'currency' => get_woocommerce_currency(),
            'language' => $lang[0],
            'country' => $location['country'],
            'orderid' => $orderId
        );
        $total = 0;
        $totalTax = 0;
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

            /* Formatting the product data that will be sent as api requests */
            $billmateProduct = new BillmateProduct($_product);

            // is product taxable?
            if ($_product->is_taxable())
            {
                $taxClass = $_product->get_tax_class();
                $tax = new WC_Tax();
                $rates = $tax->get_rates($taxClass);
                $item_tax_percentage = 0;
                foreach($rates as $row){
                    // Is it Compound Tax?
                    if(isset($row['compund']) && $row['compound'] == 'yes')
                        $item_tax_percentage += $row['rate'];
                    else
                        $item_tax_percentage = $row['rate'];
                }
            } else
                $item_tax_percentage = 0;


            // apply_filters to item price so we can filter this if needed
            $billmate_item_price_including_tax = round($order->get_item_total( $item, true )*100);
            $billmate_item_standard_price = round($order->get_item_subtotal($item,true)*100);
            $billmate_item_standard_price_without_tax = $billmate_item_standard_price / (1 + ((int)$item_tax_percentage / 100));
            $discount = false;
            if($billmate_item_price_including_tax != $billmate_item_standard_price){
                $discount = true;
            }
            $item_price = apply_filters( 'billmate_item_price_including_tax', $billmate_item_price_including_tax);

            if ( $_product->get_sku() ) {
                $sku = $_product->get_sku();
            } else {
                $sku = $_product->id;
            }

            $priceExcl = round($item_price - (100 * $order->get_item_tax($item,false)));

            $orderValues['Articles'][] = array(
                'quantity'   => (int)$item['qty'],
                'artnr'    => $sku,
                'title'    =>  $billmateProduct->getTitle(),
                'aprice'    =>  ($discount) ? ($billmate_item_standard_price_without_tax) : ($priceExcl),
                'taxrate'      => (int)$item_tax_percentage,
                'discount' => ($discount) ? round((1 - ($billmate_item_price_including_tax/$billmate_item_standard_price)) * 100 ,0) : 0,
                'withouttax' => $item['qty'] * ($priceExcl)
            );
            $totalTemp = ($item['qty'] * ($priceExcl));
            $total += $totalTemp;
            $totalTax += ($totalTemp * $item_tax_percentage/100);
            if(isset($prepareDiscount[$item_tax_percentage])){
                $prepareDiscount[$item_tax_percentage] += $totalTemp;
            } else {
                $prepareDiscount[$item_tax_percentage] = $totalTemp;
            }

            //endif;
        endforeach; endif;

        /* Add additional fees that are not invoice fee to order API request as articles */
        $orderFeesArticles = BillmateOrder::getOrderFeesAsOrderArticles();
        $orderValues['Articles'] = array_merge($orderValues['Articles'], $orderFeesArticles);
        foreach($orderFeesArticles AS $orderFeesArticle) {
            $total += $orderFeesArticle['aprice'];
            $totalTax += ($orderFeesArticle['aprice'] * ($orderFeesArticle['taxrate']/100));
        }

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
                    'taxrate'      => (int)$key,
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
                'withouttax'    => ($shipping_price-$order->order_shipping_tax)*100,
                'taxrate'      => round($calculated_shipping_tax_percentage),

            );
            $total += ($shipping_price-$order->order_shipping_tax) * 100;
            $totalTax += (($shipping_price-$order->order_shipping_tax) * ($calculated_shipping_tax_percentage/100))*100;
        endif;



        $round = (round(WC_Payment_Gateway::get_order_total() * 100)) - round($total + $totalTax,0);

        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => round($round),
            'withtax' => round($total + $totalTax + $round)
        );

        return $billmate->initCheckout($orderValues);

    }

    public function updateCheckout($result, $order)
    {
        $billmate = new Billmate($this->eid,$this->secret,true, $this->testmode == 'yes',false);

        $orderValues = $result;
        $total = 0;
        $totalTax = 0;

        unset($orderValues['Articles']);
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

            /* Formatting the product data that will be sent as api requests */
            $billmateProduct = new BillmateProduct($_product);

            // is product taxable?
            if ($_product->is_taxable())
            {
                $taxClass = $_product->get_tax_class();
                $tax = new WC_Tax();
                $rates = $tax->get_rates($taxClass);
                $item_tax_percentage = 0;
                foreach($rates as $row){
                    // Is it Compound Tax?
                    if(isset($row['compund']) && $row['compound'] == 'yes')
                        $item_tax_percentage += $row['rate'];
                    else
                        $item_tax_percentage = $row['rate'];
                }
            } else
                $item_tax_percentage = 0;


            // apply_filters to item price so we can filter this if needed
            $billmate_item_price_including_tax = round($order->get_item_total( $item, true )*100);
            $billmate_item_standard_price = round($order->get_item_subtotal($item,true)*100);
            $billmate_item_standard_price_without_tax = $billmate_item_standard_price / (1 + ((int)$item_tax_percentage / 100));
            $discount = false;
            if($billmate_item_price_including_tax != $billmate_item_standard_price){
                $discount = true;
            }
            $item_price = apply_filters( 'billmate_item_price_including_tax', $billmate_item_price_including_tax);

            if ( $_product->get_sku() ) {
                $sku = $_product->get_sku();
            } else {
                $sku = $_product->id;
            }

            $priceExcl = round($item_price - (100 * $order->get_item_tax($item,false)));

            $orderValues['Articles'][] = array(
                'quantity'   => (int)$item['qty'],
                'artnr'    => $sku,
                'title'    =>  $billmateProduct->getTitle(),
                'aprice'    =>  ($discount) ? ($billmate_item_standard_price_without_tax) : ($priceExcl),
                'taxrate'      => (int)$item_tax_percentage,
                'discount' => ($discount) ? round((1 - ($billmate_item_price_including_tax/$billmate_item_standard_price)) * 100 ,0) : 0,
                'withouttax' => $item['qty'] * ($priceExcl)
            );
            $totalTemp = ($item['qty'] * ($priceExcl));
            $total += $totalTemp;
            $totalTax += ($totalTemp * $item_tax_percentage/100);
            if(isset($prepareDiscount[$item_tax_percentage])){
                $prepareDiscount[$item_tax_percentage] += $totalTemp;
            } else {
                $prepareDiscount[$item_tax_percentage] = $totalTemp;
            }

            //endif;
        endforeach; endif;

        /* Add additional fees that are not invoice fee to order API request as articles */
        $orderFeesArticles = BillmateOrder::getOrderFeesAsOrderArticles();
        $orderValues['Articles'] = array_merge($orderValues['Articles'], $orderFeesArticles);
        foreach($orderFeesArticles AS $orderFeesArticle) {
            $total += $orderFeesArticle['aprice'];
            $totalTax += ($orderFeesArticle['aprice'] * ($orderFeesArticle['taxrate']/100));
        }

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
                    'taxrate'      => (int)$key,
                    'discount' => (float)0,
                    'withouttax' => -($discountAmount*100)

                );
                $total -= ($discountAmount * 100);
                $totalTax -= ($discountAmount * ($key/100))*100;

            }

        endif;

        // Shipping
        unset($orderValues['Cart']);
        
        if(count($order->get_fees()) > 0 ):
            foreach ($order->get_fees() as $fee){
                if($fee['name'] == esc_attr(__('Invoice fee','billmate'))) {
                    $orderValues['Cart']['Handling'] = array(
                        'withouttax' => round($fee['line_total'] * 100),
                        'taxrate' => ($fee['line_tax'] / $fee['line_total']) * 100
                    );
                    $total += $orderValues['Cart']['Handling']['withouttax'];
                    $totalTax += $fee['line_tax'] * 100;
                }

            }
        endif;
        if ($order->order_shipping>0) :

            // We manually calculate the shipping taxrate percentage here
            $calculated_shipping_tax_percentage = ($order->order_shipping_tax/$order->order_shipping)*100; //25.00
            $calculated_shipping_tax_decimal = ($order->order_shipping_tax/$order->order_shipping)+1; //0.25

            // apply_filters to Shipping so we can filter this if needed
            $billmate_shipping_price_including_tax = $order->order_shipping*$calculated_shipping_tax_decimal;
            $shipping_price = apply_filters( 'billmate_shipping_price_including_tax', $billmate_shipping_price_including_tax );

            $orderValues['Cart']['Shipping'] = array(
                'withouttax'    => ($shipping_price-$order->order_shipping_tax)*100,
                'taxrate'      => round($calculated_shipping_tax_percentage),

            );
            $total += ($shipping_price-$order->order_shipping_tax) * 100;
            $totalTax += (($shipping_price-$order->order_shipping_tax) * ($calculated_shipping_tax_percentage/100))*100;
        endif;



        $round = (round($order->get_total() * 100)) - round($total + $totalTax,0);

        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => round($round),
            'withtax' => round($total + $totalTax + $round)
        );
        return $billmate->updateCheckout($orderValues);

    }

    function init_form_fields() {

        // TODO Update with api request in future

        $available = array(
            'SE' =>__( 'Sweden','woocommerce')
        );

        $tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
        $classes_options = array();
        $classes_options[''] = __( 'Standard', 'woocommerce' );
        if ( $tax_classes )
            foreach ( $tax_classes as $class )
                $classes_options[ sanitize_title( $class ) ] = esc_html( $class );

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


        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);

        $pageOption = array();
        $pageOption[0] = __('Choose','billmate');
        foreach ($pages as $page){
            $pageOption[$page->ID] = $page->post_title;
        }

        $this->form_fields = apply_filters('billmate_checkout_form_fields', array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'billmate' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Billmate Checkout', 'billmate' ),
                'default' => 'yes'
            ),
            'testmode' => array(
                'title' => __( 'Test Mode', 'billmate' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Billmate Test Mode.', 'billmate' ),
                'default' => 'no'
            ),
            'order_status' => array(
                'title' => __('Custom approved order status','billmate'),
                'type' => 'select',
                'description' => __('Choose a special order status for Billmate Invoice, if you want to use a own status and not WooCommerce built in','billmate'),
                'default' => 'default',
                'options' => $order_statuses
            ),
            'checkout_url' => array(
                'title'       => __( 'Checkout Page', 'billmate' ),
                'type'        => 'select',
                'description' => __( 'Please select checkout page.', 'billmate' ),
                'default'     => '',
                'options' => $pageOption

            ),
            'terms_url'                    => array(
                'title'       => __( 'Terms Page', 'billmate' ),
                'type'        => 'select',
                'description' => __( 'Please select the terms page.', 'billmate' ),
                'default'     => '',
                'options' => $pageOption
            )
        ) );
        

    }
}
class WC_Gateway_Billmate_Checkout_Extra{
    public function __construct()
    {
        add_action('init',array($this,'start'));
    }

    public function start()
    {
        $checkout = new WC_Gateway_Billmate_Checkout();
    }
}

$extra = new WC_Gateway_Billmate_Checkout_Extra();