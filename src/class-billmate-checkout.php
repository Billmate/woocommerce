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
        $this->privacy_policy_url       = (isset($this->settings['privacy_policy_url'])) ? $this->settings['privacy_policy_url'] : false;
        $this->checkout_url            = (isset($this->settings['checkout_url'])) ? $this->settings['checkout_url'] : false;

        $this->testmode				= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';

        $this->order_status = (isset($this->settings['order_status'])) ? $this->settings['order_status'] : false;
        $this->show_order_comments = ( isset( $this->settings['show_order_comments'] ) ) ? $this->settings['show_order_comments'] : '';

        $this->errorCode = "";
        $this->errorMessage = "";

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

        add_action( 'wp_ajax_billmate_update_order', array( $this, 'billmate_update_order' ) );
        add_action( 'wp_ajax_nopriv_billmate_update_order', array( $this, 'billmate_update_order' ) );

        add_action( 'wp_ajax_billmate_update_order_comments', array( $this, 'billmate_update_order_comments' ) );
        add_action( 'wp_ajax_nopriv_billmate_update_order_comments', array( $this, 'billmate_update_order_comments' ) );

        add_action('wp_ajax_billmate_complete_order',array($this,'billmate_complete_order'));
        add_action('wp_ajax_nopriv_billmate_complete_order',array($this,'billmate_complete_order'));

        // Cart quantity
        add_action( 'wp_ajax_billmate_checkout_cart_callback_update', array(
            $this,
            'billmate_checkout_cart_callback_update'
        ) );
        add_action( 'wp_ajax_nopriv_billmate_checkout_cart_callback_update', array(
            $this,
            'billmate_checkout_cart_callback_update'
        ) );
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


        add_action( 'woocommerce_api_wc_gateway_billmate_checkout', array( $this, 'check_ipn_response' ) );

        add_action( 'woocommerce_cart_updated' , array( &$this, 'woocommerce_cart_updated' ) );
    }

    public function woocommerce_cart_updated( $cart ) {
        $previous_order_total = (int)WC()->session->get('billmate_previous_calculated_order_total');
        $current_order_total = (int)WC_Payment_Gateway::get_order_total();
        if (WC()->session->get('billmate_checkout_hash') == "" || $previous_order_total == $current_order_total) {
            return true;
        }
        WC()->session->set('billmate_previous_calculated_order_total', $current_order_total);
        $order_id = $this->create_order();
        $this->updateCheckoutFromOrderId( $order_id );
        return true;
    }

    public function get_title()
    {
        if (array_key_exists('post', $_GET)) {
            if (ctype_digit($_GET['post'])) {
                if (get_post_status($_GET['post']) !== FALSE) {
                    return esc_html(get_post_meta($_GET['post'], '_payment_method_title', true));
                } else {
                    return $this->method_title;
                }
            } else {
                return $this->method_title;
            }
        } else {
            return $this->method_title;
        }
    }

    function change_to_bco($url){
        $redirect = true;
        $traces = debug_backtrace();
        foreach ($traces as $trace){
            if ($trace['function'] == 'get_checkout_order_received_url'){
                $redirect = false;
            }
        }
        if (!$redirect){
            return $url;
        }
        if(!is_admin()) {
            if($this->enabled == 'yes') {
                if("sv" == strtolower(current(explode('_',get_locale())))) {
                    return get_permalink($this->checkout_url);
                }
            }
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

    function get_order(){
        return wc_get_order(WC()->session->get( 'billmate_checkout_order' ));
    }
    
    function billmate_complete_order(){
        $order = $this->get_order();
        $connection = $this->getBillmateConnection();

        $result = array();

        $hash = WC()->session->get('billmate_checkout_hash');
        if ( $hash != '' ) {
            $result = $connection->getCheckout(array('PaymentData' => array('hash' => $hash)));
        }


        if(is_object($order)) {

            if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                $orderId = $order->get_id();
            } else {
                $orderId = $order->id;
            }

            if (!isset($result['code']) AND isset($result['PaymentData']['order']['status'])) {

                $_method_title = $this->method_title;
                $method_id = $this->id;

                $billmateOrderNumber = (isset($result['PaymentData']['order']['number'])) ? $result['PaymentData']['order']['number']  : '';
                $billmateOrder = array();

                if ( $billmateOrderNumber != '' ) {
                    $billmateOrder = $connection->getPaymentinfo(array('number' => $billmateOrderNumber));
                }

                if ( isset($billmateOrder['PaymentData']['method_name']) AND $billmateOrder['PaymentData']['method_name'] != "" ) {
                    $_method_title = $_method_title . ' (' . utf8_decode($billmateOrder['PaymentData']['method_name']). ')';
                } else {
                    $billmateOrderMethod = 1;   // 8 = card, 16 = bank
                    if (isset($billmateOrder['PaymentData']['method'])) {
                        $billmateOrderMethod = $billmateOrder['PaymentData']['method'];
                    }

                    if ( $billmateOrderMethod == '1' ) {
                        $_method_title = __('Billmate Invoice', 'billmate');
                    }

                    if( $billmateOrderMethod == '4' ) {
                        $_method_title = __('Billmate Part Payment', 'billmate');
                    }
                }

                update_post_meta($orderId, '_payment_method', $method_id);
                update_post_meta($orderId, '_payment_method_title', $_method_title);

                if ( version_compare(WC_VERSION, '3.0.0', '>=') ) {
                    $order->set_payment_method($method_id);
                    $order->set_payment_method_title($_method_title);
                }

                switch (strtolower($result['PaymentData']['order']['status'])) {
                    case 'pending':
                        $order->update_status('pending');
                        $order->add_order_note(__('Order is PENDING APPROVAL by Billmate. Please visit Billmate Online for the latest status on this order. Billmate Invoice number: ', 'billmate') . $result['PaymentData']['order']['number']);
                        add_post_meta($orderId, 'billmate_invoice_id', $result['PaymentData']['order']['number']);
                        // Remove cart
                        WC()->cart->empty_cart();
                        if (version_compare(WC_VERSION, '2.0.0', '<')) {
                            $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $orderId, get_permalink(get_option('woocommerce_thanks_page_id'))));
                        } else {
                            $redirect = $this->get_return_url($order);
                        }

                        $response = array('url' => $redirect);
                        WC()->session->__unset('billmate_checkout_hash');
                        WC()->session->__unset('billmate_checkout_order');


                        wp_send_json_success($response);

                        break;
                    case 'created':
                    case 'paid':
                        $order->update_status('pending');
                        $order->payment_complete(((isset($result['PaymentInfo']['number'])) ? $result['PaymentInfo']['number'] : 0));
                        $order->add_order_note(__('Billmate payment completed. Billmate Invoice number:', 'billmate') . $result['PaymentData']['order']['number']);
                        add_post_meta($orderId, 'billmate_invoice_id', $result['PaymentData']['order']['number']);
                        // Remove cart
                        WC()->cart->empty_cart();
                        if (version_compare(WC_VERSION, '2.0.0', '<')) {
                            $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $orderId, get_permalink(get_option('woocommerce_thanks_page_id'))));
                        } else {
                            $redirect = $this->get_return_url($order);
                        }

                        $response = array('url' => $redirect);

                        WC()->session->__unset('billmate_checkout_hash');
                        WC()->session->__unset('billmate_checkout_order');

                        wp_send_json_success($response);
                        break;
                    case 'cancelled':
                        break;
                    case 'failed':
                        break;
                }
            }
        } else {
            wp_redirect(wc_get_cart_url());
            exit();
        }


    }

    function billmate_update_order()
    {
        if ( ! $this->is_cart_items_in_stock() ) {
            wp_send_json_success(array('reload_checkout' => true));
            return false;
        }

        $previous_order_total = (int)WC()->session->get('billmate_previous_calculated_order_total');
        $current_order_total = (int)WC_Payment_Gateway::get_order_total();

        if ($previous_order_total == $current_order_total) {
            wp_send_json_success(array(
                "success" => true,
                "data" => array(
                    "update_checkout" => true,
                    "order_total" => $current_order_total
                )
            ));
            return true;
        }

        $result = array("code" => "no hash");
        $hash   = WC()->session->get('billmate_checkout_hash');
        $number = WC()->session->get('billmate_checkout_number');

        if ($number == '' && $hash != '') {
            $connection = $this->getBillmateConnection();
            $result = $connection->getCheckout(array('PaymentData' => array('hash' => $hash)));
            $number = isset($result['PaymentData']['number']) ? $result['PaymentData']['number'] : '';
        }

        if ($number != '') {
            $result = array(
                'PaymentData' => array(
                    'number' => $number
                )
            );

            WC()->cart->calculate_shipping();
            WC()->cart->calculate_fees();
            WC()->cart->calculate_totals();

            $orderId = $this->create_order();
            $order = wc_get_order( $orderId );
            $order->calculate_totals();

            $data = $this->updateCheckout($result, $order);
            wp_send_json_success($data);
        }
        wp_send_json_error();
        return false;
    }

    function billmate_update_address(){
        global $woocommerce;
        global $wp_version;

        if ( ! $this->is_cart_items_in_stock() ) {
            wp_send_json_success(array('reload_checkout' => true));
            return false;
        }

        $connection = $this->getBillmateConnection();
        $result = array("code" => "no hash");

        $hash = WC()->session->get('billmate_checkout_hash');
        $number = WC()->session->get('billmate_checkout_number');

        if ($number == '' && $hash != '') {
            $result = $connection->getCheckout(array('PaymentData' => array('hash' => $hash)));
            $number = isset($result['PaymentData']['number']) ? $result['PaymentData']['number'] : '';
        }

        if ($number != '') {
            $result = array(
                'PaymentData' => array(
                    'number' => $number
                )
            );

            $orderId = $this->create_order();
            $order = wc_get_order( $orderId );
            $post = $this->woocommerce_clean($_POST);

            // shipping_pre_address_save will be used later to check if new address affect shipping cost
            $billmateOrder = new BillmateOrder($order);
            $shipping_pre_address_save = $billmateOrder->getCartShipping();
            if( isset($post['Customer'])
                && is_array($post['Customer'])
                && count($post['Customer']) > 0
                && isset($post['Customer']['Billing'])
                && isset($post['Customer']['Billing']['email'])
                && $post['Customer']['Billing']['email'] != ''
            ) {
                $billing_address = array(
                    'first_name' => $post['Customer']['Billing']['firstname'],
                    'last_name'  => $post['Customer']['Billing']['lastname'],
                    'company'    => (isset($post['Customer']['Billing']['company']) ? $post['Customer']['Billing']['company'] : ''),
                    'email'      => $post['Customer']['Billing']['email'],
                    'phone'      => $post['Customer']['Billing']['phone'],
                    'address_1'  => $post['Customer']['Billing']['street'],
                    'address_2'  => '',
                    'city'       => $post['Customer']['Billing']['city'],
                    'state'      => '',
                    'postcode'   => $post['Customer']['Billing']['zip'],
                    'country'    => $post['Customer']['Billing']['country']
                );
                if( isset($post['Customer']['Shipping'])
                    && is_array($post['Customer']['Shipping'])
                    && count($post['Customer']['Shipping']) > 0
                    && isset($post['Customer']['Shipping']['street'])
                    && isset($post['Customer']['Shipping']['city'])
                    && isset($post['Customer']['Shipping']['zip'])
                    && $post['Customer']['Shipping']['street'] != ''
                    && $post['Customer']['Shipping']['city'] != ''
                    && $post['Customer']['Shipping']['zip'] != ''
                ) {
                    $shipping_address = array(
                        'first_name' => (isset($post['Customer']['Shipping']['firstname']) ? $post['Customer']['Shipping']['firstname'] : ''),
                        'last_name'  => (isset($post['Customer']['Shipping']['lastname']) ? $post['Customer']['Shipping']['lastname'] : ''),
                        'company'    => (isset($post['Customer']['Shipping']['company']) ? $post['Customer']['Shipping']['company'] : ''),
                        'email'      => $post['Customer']['Shipping']['email'],
                        'phone'      => $post['Customer']['Shipping']['phone'],
                        'address_1'  => $post['Customer']['Shipping']['street'],
                        'address_2'  => '',
                        'city'       => $post['Customer']['Shipping']['city'],
                        'state'      => '',
                        'postcode'   => $post['Customer']['Shipping']['zip'],
                        'country'    => ''
                    );
                    if (isset($post['Customer']['Shipping']['country']) && $post['Customer']['Shipping']['country'] != '') {
                        $shipping_address['country'] = $post['Customer']['Shipping']['country'];
                    } else {
                        $shipping_address['country'] = $billing_address['country'];
                    }
                } else {
                    $shipping_address = $billing_address;
                }
                foreach ($billing_address AS $key => $val) {
                    $billing_address[$key] = sanitize_text_field( $val );
                }
                foreach ($shipping_address AS $key => $val) {
                    $shipping_address[$key] = sanitize_text_field( $val );
                }
                $order_billing_address = $order->get_address( 'billing' );
                $order_shipping_address = $order->get_address( 'shipping' );

                /**
                 * When customer shipping or billing address is changed
                 * - Save store customer address
                 * - Update Billmate Checkout order with current calculated order totals
                 */
                if (
                    $order_billing_address['first_name'] != $billing_address['first_name']
                    || $order_billing_address['last_name'] != $billing_address['last_name']
                    || $order_billing_address['country'] != strtoupper( $billing_address['country'] )
                    || $order_billing_address['address_1'] != $billing_address['address_1']
                    || $order_billing_address['address_2'] != $billing_address['address_2']
                    || $order_billing_address['city'] != $billing_address['city']
                    || $order_billing_address['postcode'] != $billing_address['postcode']
                    || $order_billing_address['phone'] != $billing_address['phone']
                    || $order_billing_address['email'] != $billing_address['email']
                    || $order_shipping_address['first_name'] != $shipping_address['first_name']
                    || $order_shipping_address['last_name'] != $shipping_address['last_name']
                    || $order_shipping_address['country'] != strtoupper( $shipping_address['country'] )
                    || $order_shipping_address['address_1'] != $shipping_address['address_1']
                    || $order_shipping_address['address_2'] != $shipping_address['address_2']
                    || $order_shipping_address['city'] != $shipping_address['city']
                    || $order_shipping_address['postcode'] != $shipping_address['postcode']
                ) {
                    $isEmail = is_email($billing_address['email']);
                    if ($isEmail != false AND is_string($isEmail) AND $isEmail == $billing_address['email']) {
                        // Email is valid, continue
                        $order->set_address($billing_address,'billing');
                        $order->set_address($shipping_address,'shipping');
                    } else {
                        /* Email not valid */
                        if (version_compare(WC_VERSION, '2.8.0', '>=') AND version_compare(WC_VERSION, '3.1.0', '>=')) {
                            // Prevent exception when invalid email
                            if (isset($billing_address['email'])) {
                                unset($billing_address['email']);
                            }
                            $order->set_address($billing_address, 'billing');
                            $order->set_address($shipping_address, 'shipping');
                            update_metadata('post', $order->get_id(), '_billing_email', $billing_address['email']);
                        } else {
                            $order->set_address($billing_address, 'billing');
                            $order->set_address($shipping_address, 'shipping');
                        }
                    }

                    WC()->customer->set_billing_country( $billing_address['country'] );
                    WC()->customer->set_shipping_country( $shipping_address['country'] );
                    WC()->customer->set_billing_postcode( $billing_address['postcode'] );
                    WC()->customer->set_shipping_postcode( $shipping_address['postcode'] );
                    WC()->customer->save();

                    WC()->session->set('billmate_checkout_billing_country', $billing_address['country']);
                    WC()->session->set('billmate_checkout_billing_postcode', $billing_address['postcode']);
                    WC()->session->set('billmate_checkout_shipping_country', $shipping_address['country']);
                    WC()->session->set('billmate_checkout_shipping_postcode', $shipping_address['postcode']);


                    WC()->cart->calculate_shipping();
                    WC()->cart->calculate_fees();
                    WC()->cart->calculate_totals();

                    $orderId = $this->create_order();
                    $order = wc_get_order( $orderId );
                    $order->calculate_totals();

                    $billmateOrder = new BillmateOrder($order);
                    $shipping_post_address_save = $billmateOrder->getCartShipping();
                    if (
                        $shipping_pre_address_save['price'] != $shipping_post_address_save['price']
                        || $shipping_pre_address_save['taxrate'] != $shipping_post_address_save['taxrate']
                        || $shipping_pre_address_save['tax'] != $shipping_post_address_save['tax']
                        || $shipping_pre_address_save['price_with_tax'] != $shipping_post_address_save['price_with_tax']
                    ) {
                        // New address affect shipping price Update checkout-order
                        $this->billmate_update_order();
                    }

                } else {
                    $current_order_total = (int)WC_Payment_Gateway::get_order_total();
                    wp_send_json_success(array('update_checkout' => false, "order_total" => $current_order_total));
                }
                $current_order_total = (int)WC_Payment_Gateway::get_order_total();
                wp_send_json_success(array('update_checkout' => false, "order_total" => $current_order_total));
            }

        }
        wp_send_json_error();
        return false;
    }

    function billmate_update_order_comments() {
        $order_comments = (isset($_POST['order_comments'])) ? esc_html($_POST['order_comments']) : '';
        $orderId = $this->create_order();
        $order = wc_get_order( $orderId );
        $order->set_customer_note( $order_comments );
        $order->save();
    }

    function billmate_checkout_cart_callback_update() {
        if ( ! wp_verify_nonce( $_REQUEST['billmate_checkout_nonce'], 'billmate_checkout_nonce' ) ) {
            exit( 'Nonce can not be verified.' );
        }
        global $woocommerce;
        $updated_item_key = $this->woocommerce_clean($_REQUEST['cart_item_key']);
        $new_quantity     = $this->woocommerce_clean($_REQUEST['new_quantity']);
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
        $orderId = $this->create_order();
        $order = wc_get_order($orderId);
        $billmate = $this->getBillmateConnection();

        $result = $billmate->getCheckout(array('PaymentData' => array('hash' => WC()->session->get( 'billmate_checkout_hash' ))));

        $data = $this->updateCheckout($result,$order);

        wp_send_json_success( $data );
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

        if ( ! $this->is_cart_items_in_stock() ) {
            return;
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

        // Create the order.
        $order = wc_create_order( $order_data );

        if ( is_wp_error( $order ) ) {
            throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
        }


        return $order;
    }

    function check_if_order_should_be_updated_or_created($customer_email = ''){
        if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
            define( 'WOOCOMMERCE_CART', true );
        }

        // Check if store order already exists and if have connection to an existing Billmate payment
        // If that is the case, the store order can no longer be used by cart and a new order need to be created
        if (WC()->session->get('billmate_checkout_order')) {
            if (get_post_meta(WC()->session->get('billmate_checkout_order'), 'billmate_invoice_id', true) != '') {
                    // Store order exists and have Billmate payment connection. Store order can no longer be used by cart
                    WC()->session->__unset('billmate_checkout_hash');
                    WC()->session->__unset('billmate_checkout_order');
            }
        }

        if (
                is_object(WC())
                && property_exists(WC(), 'session')
                && is_object(WC()->session)
                && method_exists(WC()->session, 'get')
                && WC()->session->get('billmate_checkout_billing_postcode') != ''
        ) {
            WC()->customer->set_billing_country( WC()->session->get('billmate_checkout_billing_country') );
            WC()->customer->set_billing_postcode( WC()->session->get('billmate_checkout_billing_postcode') );
            WC()->customer->set_shipping_country( WC()->session->get('billmate_checkout_shipping_country') );
            WC()->customer->set_shipping_postcode( WC()->session->get('billmate_checkout_shipping_postcode') );
            WC()->customer->save();
            WC()->cart->calculate_totals();
        }

        if ( WC()->session->get( 'billmate_checkout_order' ) && wc_get_order( WC()->session->get( 'billmate_checkout_order' ) ) ) {
            $orderid = WC()->session->get( 'billmate_checkout_order' );
            $order   = wc_get_order( $orderid );
        } else {
            // Create order in WooCommerce if we have an email.
            $order = $this->create_wc_order();

            if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                $orderId = $order->get_id();
            } else {
                $orderId = $order->id;
            }

            update_post_meta( $orderId, '_billmatecheckout_incomplete_customer_email', $customer_email, true );
            WC()->session->set( 'billmate_checkout_order', $orderId );
        }

        if(isset($order)){
            $order->remove_order_items();

            /** WooCommerce >= 3.0 */
            if (is_callable(array(WC()->checkout, 'create_order_line_items'))) {
                $order_items = $order->get_items( array( 'line_item' ) );
                if (empty($order_items)) {
                    WC()->checkout->create_order_line_items($order, WC()->cart);
                }
            }

            /** WooCommerce < 3.0 */
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

                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $orderId = $order->get_id();
                    } else {
                        $orderId = $order->id;
                    }

                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $item = new WC_Order_Item_Fee();
                        $item->set_props( array(
                            'name'      => $fee->name,
                            'tax_class' => $fee->taxable ? $fee->tax_class : 0,
                            'total'     => $fee->amount,
                            'total_tax' => $fee->tax,
                            'taxes'     => array(
                                'total' => $fee->tax_data,
                            ),
                            'order_id'  => $orderId,
                        ) );
                        $item->save();
                        $order->add_item( $item );
                        $item_id = $item->get_id();
                    } else {
                        $item_id = $order->add_fee( $fee );
                    }

                    if ( ! $item_id ) {
                        throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                    }

                    do_action( 'woocommerce_add_order_fee_meta', $orderId, $item_id, $fee, $key );
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

                        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                            $shipping_rate = $package['rates'][ $this_shipping_methods[ $package_key ] ];
                            $item = new WC_Order_Item_Shipping();
                            $item->set_props( array(
                                'method_title' => $shipping_rate->label,
                                'method_id'    => $shipping_rate->id,
                                'total'        => wc_format_decimal( $shipping_rate->cost ),
                                'taxes'        => $shipping_rate->taxes,
                                'order_id'     => $order->get_id()
                            ) );
                            foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
                                $item->add_meta_data( $key, $value, true );
                            }
                            $item->save();
                            $order->add_item( $item );
                            $item_id = $item->get_id();
                        } else {
                            $item_id = $order->add_shipping( $package['rates'][ $this_shipping_methods[ $package_key ] ] );
                        }

                        if ( ! $item_id ) {
                            throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                        }

                    }
                }
            }

            $order_taxes = $order->get_items( array( 'tax' ) );
            if ( empty( $order_taxes ) ) {

                if(version_compare(WC_VERSION, '3.2.0', '>=')) {
                    $tax_ids = array_keys( WC()->cart->get_cart_contents_taxes() + WC()->cart->get_shipping_taxes() );
                } else {
                    $tax_ids = array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes );
                }

                foreach ( $tax_ids as $tax_id ) {
                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $tax_code = WC_Tax::get_rate_code( $tax_id );
                        if(!is_numeric($tax_id) OR $tax_id < 1 OR $tax_code == false) {
                            throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
                        }
                    } else {
                        if ($tax_id && ! $order->add_tax( $tax_id, WC()->cart->get_tax_amount( $tax_id ), WC()->cart->get_shipping_tax_amount( $tax_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_id ) {
                            throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
                        }
                    }
                }
            }

            $order_coupons = $order->get_items( array( 'coupon' ) );
            if ( empty( $order_coupons ) ) {
                foreach ( WC()->cart->get_coupons() as $code => $coupon ) {

                    if(version_compare(WC_VERSION, '3.0.0', '>=')) {

                        $item = new WC_Order_Item_Coupon();
                        $item->set_props( array(
                            'code'         => $code,
                            'discount'     => WC()->cart->get_coupon_discount_amount( $code ),
                            'discount_tax' => WC()->cart->get_coupon_discount_tax_amount( $code ),
                            'order_id'     => $order->get_id()
                        ) );
                        $item->save();
                        $order->add_item( $item );

                    } else {
                        if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) ) ) {
                            throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
                        }
                    }
                }
            }

            $available_gateways = WC()->payment_gateways->payment_gateways();
            $payment_method     = $available_gateways['billmate_checkout'];

            $order->set_payment_method( $payment_method );

            if ( $this->checkout_url == get_the_ID() && defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
                define( 'WOOCOMMERCE_CHECKOUT', true );
            }

            if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
                define( 'WOOCOMMERCE_CART', true );
            }

            WC()->cart->calculate_shipping();
            WC()->cart->calculate_fees();
            WC()->cart->calculate_totals();

            $order->calculate_totals();

            if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                $orderId = $order->get_id();
            } else {
                $orderId = $order->id;
            }

            if ( email_exists( $customer_email ) ) {
                $user    = get_user_by( 'email', $customer_email );
                $user_id = $user->ID;
                update_post_meta( $orderId, '_customer_user', $user_id );
            }
            do_action( 'woocommerce_checkout_update_order_meta', $orderId, array() );
        }
        return $orderId;
    }

    function get_url(){
        $orderId = $this->create_order();

        if (is_int($orderId) && $orderId > 0) {
            $isInit = true;
            if( WC()->session->get( 'billmate_checkout_hash' )){
                $isInit = false;
                $billmate = $this->getBillmateConnection();

                $previous_order_total = (int)WC()->session->get('billmate_previous_calculated_order_total');
                $current_order_total = (int)WC_Payment_Gateway::get_order_total();
                if ($previous_order_total != $current_order_total) {
                    $this->updateCheckoutFromOrderId( $orderId );
                }

                $checkout = $billmate->getCheckout(array('PaymentData' => array('hash' => WC()->session->get( 'billmate_checkout_hash' ))));
                if(!isset($checkout['code'])){
                    return $checkout['PaymentData']['url'];
                } else {
                    $this->errorCode = (isset($checkout['code'])) ? $checkout['code'] : $this->errorCode;
                    $this->errorMessage = (isset($checkout['message'])) ? $checkout['message'] : $this->errorMessage;
                    $isInit = true;
                }
            }

            if ( $isInit == true ) {
                $result = $this->initCheckout($orderId);
                if(!isset($result['code'])){
                    return $result['url'];
                } else {
                    $this->errorCode = (isset($result['code'])) ? $result['code'] : $this->errorCode;
                    $this->errorMessage = (isset($result['message'])) ? $result['message'] : $this->errorMessage;
                }
            }
        }
        return '';
    }


    /* Return variables to make an checkout request except customer data */
    public function getCheckoutDataFromOrderId( $orderId = null ) {

        global $woocommerce;
        $order = new WC_order( $orderId );

        $billmateOrder = new BillmateOrder($order);

        $orderValues = array();
        $orderValues['CheckoutData'] = array(
            'windowmode' => 'iframe',
            'redirectOnSuccess' => 'true',
            'sendreciept' => 'yes',
            'terms' => get_permalink($this->terms_url)
        );

        if ($this->privacy_policy_url > 0) {
            $orderValues['CheckoutData']['privacyPolicy'] = get_permalink($this->privacy_policy_url);
        }
        if (isset(get_option('woocommerce_billmate_checkout_settings')['billmate_checkout_mode'])) {
            if (get_option('woocommerce_billmate_checkout_settings')['billmate_checkout_mode'] == "business") {
                $orderValues['CheckoutData']['companyView'] = "true";
            }
        }

        $lang = explode('_',get_locale());

        $location = wc_get_base_location();
        $orderValues['PaymentData'] = array(
            'method' => 93,
            'currency' => get_woocommerce_currency(),
            'language' => $lang[0],
            'country' => $location['country'],
            'orderid' => ltrim($order->get_order_number(),'#'),
            'logo' => (strlen($this->logo)> 0) ? $this->logo : ''
        );

        $orderValues['PaymentData']['accepturl']    = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Checkout', 'payment' => 'success','method' => 'checkout'));
        $orderValues['PaymentData']['callbackurl']  = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Checkout', 'method' => 'checkout'));
        $orderValues['PaymentData']['cancelurl']    = billmate_add_query_arg(array('wc-api' => 'WC_Gateway_Billmate_Checkout', 'payment' => 'cancel','method' => 'checkout'));
        $orderValues['PaymentData']['returnmethod'] = is_ssl() ? 'POST' : 'GET';

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


        $round = (round(WC_Payment_Gateway::get_order_total() * 100)) - round($total + $totalTax,0);

        /* Make sure available handling fee is added when init checkout, at this point, no payment will be made */
        if(!isset($orderValues['Cart']['Handling'])) {
            $invoice_fee = new WC_Gateway_Billmate_Invoice;
            $tax = new WC_Tax();
            $rate = $tax->get_rates($invoice_fee->invoice_fee_tax_class);
            $rate = array_pop($rate);
            $rate = (isset($rate['rate'])) ? round($rate['rate']) : 0;
            $invoiceFee = $invoice_fee->invoice_fee * 100;

            if($invoiceFee > 0) {
                $orderValues['Cart']['Handling'] = array(
                    'withouttax' => $invoiceFee,
                    'taxrate' => $rate
                );
                $rateTimes = 1;
                if($rate > 0) {
                    $rateTimes = 1 + ($rate / 100);
                }

                $invoiceFeeTotal = $invoiceFee;
                $invoiceFeeTax = ($invoiceFee * $rateTimes) - $invoiceFee;
                $total += $invoiceFeeTotal;
                $totalTax += $invoiceFeeTax;
            }
        }


        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => round($round),
            'withtax' => round($total + $totalTax + $round)
        );

        return $orderValues;
    }

    public function updateCheckoutFromOrderId( $orderId = null )
    {
        $billmate = $this->getBillmateConnection();
        $checkoutOrderNumber = WC()->session->get('billmate_checkout_number');
        if ($checkoutOrderNumber == '') {
            $checkoutOrder = $billmate->getCheckout(array('PaymentData' => array('hash' => WC()->session->get('billmate_checkout_hash'))));
            $checkoutOrderNumber = (isset($checkoutOrder['PaymentData']['number'])) ? $checkoutOrder['PaymentData']['number'] : 0;
        }

        if ( $checkoutOrderNumber > 0 ) {
            $orderValues = $this->getCheckoutDataFromOrderId($orderId);
            $orderValues['PaymentData'] = array(
                'number' => $checkoutOrderNumber
            );
            WC()->session->set('billmate_previous_calculated_order_total', WC_Payment_Gateway::get_order_total());
            return $billmate->updateCheckout($orderValues);
        }
        return array();
    }

    function initCheckout($orderId = null){
        $orderValues = $this->getCheckoutDataFromOrderId($orderId);

        $billmate = $this->getBillmateConnection();
        $result = $billmate->initCheckout($orderValues);

        // Save checkout hash
        if(!isset($result['code']) AND isset($result['url']) AND $result['url'] != "") {
           $url = $result['url'];
           $parts = explode('/',$url);
           $sum = count($parts);
           $hash = ($parts[$sum-1] == 'test') ? str_replace('\\','',$parts[$sum-2]) : str_replace('\\','',$parts[$sum-1]);
           $number = (isset($result['number'])) ? $result['number'] : '';
           WC()->session->set('billmate_checkout_hash', $hash);
           WC()->session->set('billmate_checkout_number', $number);
       }

        return $result;

    }

    public function is_cart_items_in_stock()
    {
        if (!isset(WC()->cart) || !is_object(WC()->cart)) {
            return false;
        }
        $check_cart_item_stock_result = WC()->cart->check_cart_item_stock();
        if (!is_bool($check_cart_item_stock_result) || (is_bool($check_cart_item_stock_result) && true != $check_cart_item_stock_result)) {
            return false;
        }
        return true;
    }

    public function updateCheckout($result, $order)
    {
        if ( ! $this->is_cart_items_in_stock() ) {
            return false;
        }

        $billmate = $this->getBillmateConnection();

        $billmateOrder = new BillmateOrder($order);

        $orderValues = array(
            'Articles' => $result['Articles'],
            'Cart' => $result['Cart'],
            'PaymentData' => array(
                'number' => $result['PaymentData']['number']
            )
        );

        $total = 0;
        $totalTax = 0;
        $previousTotal = $result['Cart']['Total']['withtax'];

        /* Articles, fees, discount */
        $orderValues['Articles'] = $billmateOrder->getArticlesData();
        $total += $billmateOrder->getArticlesTotal();
        $totalTax += $billmateOrder->getArticlesTotalTax();

        // Shipping
        unset($orderValues['Cart']);
        $shipping = $billmateOrder->getCartShipping();
        if ($shipping['price'] > 0) {
            $orderValues['Cart']['Shipping'] = $billmateOrder->getCartShippingData();
            $total      += $shipping['price'];
            $totalTax   += $shipping['tax'];
        }

        $round = (round($order->get_total() * 100)) - round($total + $totalTax,0);

        // Always add available handling fee to checkout order
        if(!isset($orderValues['Cart']['Handling'])) {
            $invoice_fee = new WC_Gateway_Billmate_Invoice;
            $tax = new WC_Tax();
            $rate = $tax->get_rates($invoice_fee->invoice_fee_tax_class);
            $rate = array_pop($rate);
            $rate = round($rate['rate']);
            $invoiceFee = $invoice_fee->invoice_fee * 100;

            if($invoiceFee > 0) {
                $orderValues['Cart']['Handling'] = array(
                    'withouttax' => $invoiceFee,
                    'taxrate' => $rate
                );
                $rateTimes = 1;
                if($rate > 0) {
                    $rateTimes = 1 + ($rate / 100);
                }

                $invoiceFeeTotal = $invoiceFee;
                $invoiceFeeTax = ($invoiceFee * $rateTimes) - $invoiceFee;
                $total += $invoiceFeeTotal;
                $totalTax += $invoiceFeeTax;
            }
        }

        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => round($round),
            'withtax' => round($total + $totalTax + $round)
        );

        WC()->session->set('billmate_previous_calculated_order_total', WC_Payment_Gateway::get_order_total());
        $data = $billmate->updateCheckout($orderValues);

        if(!isset($data['code'])){
            $current_order_total = (int)WC_Payment_Gateway::get_order_total();
            if($previousTotal != $orderValues['Cart']['Total']['withtax']){
                return array('update_checkout' => true, "order_total" => $current_order_total);
            } else {
                return array('update_checkout' => false, "order_total" => $current_order_total);
            }
        }

    }

    function check_ipn_response() {

        $checkoutMessageCancel = '';
        $checkoutMessageFail = '';
        $transientPrefix = 'billmate_order_id_';

        $config = array(
            'testmode' => $this->testmode,
            'method_id' => $this->id,
            'method_title' => $this->method_title,
            'checkoutMessageCancel' => $checkoutMessageCancel,
            'checkoutMessageFail' => $checkoutMessageFail,
            'transientPrefix' => $transientPrefix,
        );

        $this->common_check_ipn_response( $config );
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

        $checkoutModeOptions = array(
            'private' => __('Consumer', 'billmate'),
            'business' => __('Company', 'billmate')
        );


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
                'description' => __('Before enabling Billmate Checkout, please contact Billmate to make sure that Billmate Checkout is active on your account.', 'billmate'),
                'default' => 'no'
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
            'show_order_comments' => array(
                'title' => __( 'Show order comments', 'billmate' ),
                'type' => 'checkbox',
                'label' => __( 'Show order comments on checkout page.', 'billmate' ),
                'default' => 'no'
            ),
            'terms_url'                    => array(
                'title'       => __( 'Terms Page', 'billmate' ),
                'type'        => 'select',
                'description' => __( 'Please select the terms page.', 'billmate' ),
                'default'     => '',
                'options' => $pageOption
            ),
            'privacy_policy_url'                    => array(
                'title'       => __( 'Privacy Policy Page', 'billmate' ),
                'type'        => 'select',
                'description' => __( 'Please select the Privacy Policy page.', 'billmate' ),
                'default'     => '',
                'options' => $pageOption
            ),
            'billmate_common_enable_overlay' => array(
                'title' => __('Enable Overlay','billmate'),
                'type' => 'checkbox',
                'description' => __('Enable visual focus in Billmate Checkout', 'billmate'),
                'default' => 'no'
            ),
            'billmate_checkout_mode' => array(
                'title' => __('Checkout MODE','billmate'),
                'type' => 'select',
                'description' => __('Choose whether you want to emphasize shopping as a company or consumer first in Billmate Checkout.','billmate'),
                'default' => 'default',
                'options' => $checkoutModeOptions
            )
        ) );


    }

    public function is_available() {
        if (is_checkout()){
            return false;
        }
        $redirect = true;
        $traces = debug_backtrace();
        foreach ($traces as $trace){
            if ($trace['function'] == 'get_checkout_order_received_url'){
                $redirect = false;
            }
        }
        if (!$redirect){
            return false;
        }
        if(!is_admin()) {
            if($this->enabled == 'yes') {
                if("sv" == strtolower(current(explode('_',get_locale())))) {
                    return true;
                }
            }
        }
        return false;
    }

    public function get_error() {
        return array(
            "code" => $this->errorCode,
            "message" => $this->errorMessage
        );
    }

    public function getBillmateConnection() {
        return new BillMate( $this->eid, $this->secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );
    }

    private function woocommerce_clean($var = "") {
        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
            return wc_clean($var);
        }
        return woocommerce_clean($var);
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