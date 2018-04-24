<?php
require_once "commonfunctions.php";


class WC_Gateway_Billmate_Partpayment extends WC_Gateway_Billmate {

	/**
     * Class for Billmate Part Payment payment.
     *
     */
	static $country = 'SE';
	static $allowed_countries_static = array('se');

	public function __construct() {
		global $woocommerce, $eid;

		parent::__construct();

		$this->id			= 'billmate_partpayment';
		$this->method_title = __('Billmate Part Payment', 'billmate');
		$this->has_fields 	= true;


		// Billmate warning banner - used for NL only

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Load shortcodes.
		// This is used so that the merchant easily can modify the displayed monthly cost text (on single product and shop page) via the settings page.
		require_once('shortcodes.php');



		// Define user set variables
		$this->enabled							= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title 							= ( isset( $this->settings['title'] ) && strlen($this->settings['title'])) ? $this->settings['title'] : __('Billmate Partpayment','billmate');
		$this->description  					= ( isset( $this->settings['description'] )) ? $this->settings['description'] : '';
		$this->eid	= $eid						= get_option('billmate_common_eid');//( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->secret							= get_option('billmate_common_secret');//( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->logo 				= get_option('billmate_common_logo');

		$this->lower_threshold					= ( isset( $this->settings['lower_threshold'] ) AND $this->settings['lower_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['lower_threshold'])) : '';
		$this->upper_threshold					= ( isset( $this->settings['upper_threshold'] ) AND $this->settings['upper_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['upper_threshold'])) : '';
		$this->show_monthly_cost				= ( isset( $this->settings['show_monthly_cost'] ) ) ? $this->settings['show_monthly_cost'] : '';
		$this->show_monthly_cost_info			= ( isset( $this->settings['show_monthly_cost_info'] ) ) ? $this->settings['show_monthly_cost_info'] : '';
		$this->show_monthly_cost_prio			= ( isset( $this->settings['show_monthly_cost_prio'] ) ) ? $this->settings['show_monthly_cost_prio'] : '15';
		$this->show_monthly_cost_shop			= ( isset( $this->settings['show_monthly_cost_shop'] ) ) ? $this->settings['show_monthly_cost_shop'] : '';
		$this->show_monthly_cost_shop_info		= ( isset( $this->settings['show_monthly_cost_shop_info'] ) ) ? $this->settings['show_monthly_cost_shop_info'] : '';
		$this->show_monthly_cost_shop_prio		= ( isset( $this->settings['show_monthly_cost_shop_prio'] ) ) ? $this->settings['show_monthly_cost_shop_prio'] : '15';
		$this->testmode							= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms					= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->lower_threshold_monthly_cost		= ( isset( $this->settings['lower_threshold_monthly_cost'] ) ) ? $this->settings['lower_threshold_monthly_cost'] : '';
		$this->upper_threshold_monthly_cost		= ( isset( $this->settings['upper_threshold_monthly_cost'] ) ) ? $this->settings['upper_threshold_monthly_cost'] : '';
		$this->allowed_countries		= ( isset( $this->settings['billmateaccount_allowed_countries'] ) && !empty($this->settings['billmateaccount_allowed_countries'])) ? $this->settings['billmateaccount_allowed_countries'] : array('SE');
		$this->shop_country				= strlen($this->shop_country) ? $this->shop_country: 'SE';
		$this->order_status = (isset($this->settings['order_status'])) ? $this->settings['order_status'] : false;

		if ($this->lower_threshold_monthly_cost == '') $this->lower_threshold_monthly_cost = 0;
		if ($this->upper_threshold_monthly_cost == '') $this->upper_threshold_monthly_cost = 10000000;


		// Country and language
		$countrytmp = $this->shop_country;

        $country_data               = self::get_country_data();
        $billmate_country           = isset($country_data['billmate_country']) ? $country_data['billmate_country'] : '';
        $billmate_language          = isset($country_data['billmate_language']) ? $country_data['billmate_language'] : '';
        $billmate_currency          = isset($country_data['billmate_currency']) ? $country_data['billmate_currency'] : '';
        $billmate_partpayment_info  = isset($country_data['billmate_partpayment_info']) ? $country_data['billmate_partpayment_info'] : '';
        $billmate_partpayment_icon  = isset($country_data['billmate_partpayment_icon']) ? $country_data['billmate_partpayment_icon'] : '';
        $billmate_basic_icon        = isset($country_data['billmate_basic_icon']) ? $country_data['billmate_basic_icon'] : '';

		// Apply filters to Country and language
		$this->billmate_country 					= apply_filters( 'billmate_country', $billmate_country );
		$this->billmate_language 					= apply_filters( 'billmate_language', $billmate_language );
		$this->billmate_currency 					= apply_filters( 'billmate_currency', $billmate_currency );
		$this->billmate_partpayment_info 			= apply_filters( 'billmate_partpayment_info', $billmate_partpayment_info );
		$this->icon 							    = apply_filters( 'billmate_partpayment_icon', $billmate_partpayment_icon );
		$this->icon_basic						    = apply_filters( 'billmate_basic_icon', $billmate_basic_icon );



		// Actions

		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_receipt_billmate_partpayment', array(&$this, 'receipt_page'));

		add_action('admin_init', array(&$this, 'update_billmatepclasses_from_billmate'));

        add_action('woocommerce_single_product_summary', 'WC_Gateway_Billmate_Partpayment::print_product_monthly_cost', $this->show_monthly_cost_prio);

		add_action('woocommerce_checkout_process', array(&$this, 'billmate_partpayment_checkout_field_process'));

		add_action('wp_footer', array(&$this, 'billmate_partpayment_terms_js'));


        add_action('admin_enqueue_scripts',array(&$this,'injectscripts'));

    }

    static function get_country_data() {
        $eid = get_option('billmate_common_eid');
        $shop_country = get_option('woocommerce_default_country');

        // Check if woocommerce_default_country includes state as well. If it does, remove state
        if (strstr($shop_country, ':')) {
            $shop_country = current(explode(':', $shop_country));
        }

        switch ($shop_country) {
            case 'DK':
                $billmate_country = 'DK';
                $billmate_language = 'DA';
                $billmate_currency = 'DKK';
                $billmate_partpayment_info = 'https://online.billmate.com/account_dk.yaws?eid=' . $eid;
                break;
            case 'DE' :
                $billmate_country = 'DE';
                $billmate_language = 'DE';
                $billmate_currency = 'EUR';
                $billmate_partpayment_info = 'https://online.billmate.com/account_de.yaws?eid=' . $eid;
                break;
            case 'NL' :
                $billmate_country = 'NL';
                $billmate_language = 'NL';
                $billmate_currency = 'EUR';
                $billmate_partpayment_info = 'https://online.billmate.com/account_nl.yaws?eid=' . $eid;
                $billmate_partpayment_icon = 'https://cdn.billmate.com/public/images/NL/badges/v1/account/NL_account_badge_std_blue.png?width=60&eid=' . $eid;
                $billmate_basic_icon = 'https://cdn.billmate.com/public/images/NL/logos/v1/basic/NL_basic_logo_std_blue-black.png?width=60&eid=' . $eid;
                break;
            case 'NO' :
                $billmate_country = 'NO';
                $billmate_language = 'NB';
                $billmate_currency = 'NOK';
                $billmate_partpayment_info = 'https://online.billmate.com/account_no.yaws?eid=' . $eid;
                break;
            case 'FI' :
                $billmate_country = 'FI';
                $billmate_language = 'FI';
                $billmate_currency = 'EUR';
                $billmate_partpayment_info = 'https://online.billmate.com/account_fi.yaws?eid=' . $eid;
                break;
            case 'SE' :
                $billmate_country = 'SE';
                $billmate_language = 'SV';
                $billmate_currency = 'SEK';
                $billmate_partpayment_info = 'https://online.billmate.com/account_se.yaws?eid=' . $eid;
                break;
            default:
                $billmate_country = '';
                $billmate_language = '';
                $billmate_currency = '';
                $billmate_partpayment_info = '';
        }

        $billmate_partpayment_icon = plugins_url( '/images/bm_delbetalning_l.png', __FILE__ );
        $billmate_basic_icon = plugins_url( '/images/bm_delbetalning_l.png', __FILE__ );

        $return = array(
            "billmate_country"          => $billmate_country,
            "billmate_language"         => $billmate_language,
            "billmate_currency"         => $billmate_currency,
            "billmate_partpayment_info" => $billmate_partpayment_info,
            "billmate_partpayment_icon" => $billmate_partpayment_icon,
            "billmate_basic_icon"       => $billmate_basic_icon
        );
        return $return;
    }

    public function injectscripts(){
        if( is_admin()){
            wp_enqueue_script( 'jquery' );
            wp_register_script('billmateadmin.js',plugins_url('/js/billmateadmin.js',__FILE__),array('jquery'),'1.0',true);
            wp_enqueue_script('billmateadmin.js');
        }
    }

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
		global $woocommerce;

        // Todo Replace this array in future with an Api request
		$available = array(
			'SE' =>__( 'Sweden','woocommerce'),
		);
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
	   	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Part Payment', 'billmate' ),
							'default' => 'no'
						),
			'title' => array(
							'title' => __( 'Title', 'billmate' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'billmate' ),
							'default' => __( 'Billmate Partpayment', 'billmate' )
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
							'description' => __( 'Disable Billmate Part Payment if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'billmate' ),
							'default' => ''
						),
			'upper_threshold' => array(
							'title' => __( 'Upper threshold', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Disable Billmate Part Payment if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'billmate' ),
							'default' => ''
						),
			'show_monthly_cost' => array(
							'title' => __( 'Display monthly cost - product page', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Display monthly cost on single products page.', 'billmate' ),
							'default' => 'yes'
						),
			'show_monthly_cost_info' => array(
							'title' => __( 'Text for Monthly cost - product page', 'billmate' ),
							'type' => 'textarea',
							'description' => __( 'This controls the Monthly cost text displayed on the single product page. You can use the following shortcodes: [billmate_img] [billmate_price] [billmate_currency] & [billmate_partpayment_info_link].', 'billmate' ),
							'default' => __('[billmate_img]<br/>Part pay from [billmate_price] [billmate_currency]/month.', 'billmate')
						),
			'show_monthly_cost_prio' => array(
								'title' => __( 'Placement of monthly cost - product page', 'billmate' ),
								'type' => 'select',
								'options' => array('4'=>__( 'Above Title', 'billmate' ), '7'=>__( 'Between Title and Price', 'billmate'), '15'=>__( 'Between Price and Excerpt', 'billmate'), '25'=>__( 'Between Excerpt and Add to cart-button', 'billmate'), '35'=>__( 'Between Add to cart-button and Product meta', 'billmate'), '45'=>__( 'Between Product meta and Product sharing-buttons', 'billmate'), '55'=>__( 'After Product sharing-buttons', 'billmate' )),
								'description' => __( 'Select where on the products page the Monthly cost information should be displayed.', 'billmate' ),
								'default' => '15'
							),
			'show_monthly_cost_shop' => array(
							'title' => __( 'Display monthly cost - shop page', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Display monthly cost next to each product on shop page.', 'billmate' ),
							'default' => 'no'
						),
			'show_monthly_cost_shop_info' => array(
							'title' => __( 'Text for Monthly cost - shop page', 'billmate' ),
							'type' => 'textarea',
							'description' => __( 'This controls the text displayed next to each product on shop page. You can use the following shortcodes: [billmate_img] [billmate_price] [billmate_currency] & [billmate_partpayment_info_link].', 'billmate' ),
							'default' => __('From [billmate_price] [billmate_currency]/month', 'billmate')
						),
			'show_monthly_cost_shop_prio' => array(
								'title' => __( 'Placement of monthly cost - shop page', 'billmate' ),
								'type' => 'select',
								'options' => array('0'=>__( 'Above Add to cart button', 'billmate' ), '15'=>__( 'Below Add to cart button', 'billmate')),
								'description' => __( 'Select where on the shop page the Monthly cost information should be displayed.', 'billmate' ),
								'default' => '15'
							),
			'lower_threshold_monthly_cost' => array(
							'title' => __( 'Lower threshold for monthly cost', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Disable the monthly cost feature if <i>product price</i> is lower than the specified value. Leave blank to disable.', 'billmate' ),
							'default' => ''
						),
			'upper_threshold_monthly_cost' => array(
							'title' => __( 'Upper threshold for monthly cost', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Disable the monthly cost feature if <i>product price</i> is higher than the specified value. Leave blank to disable.', 'billmate' ),
							'default' => ''
						),

			'testmode' => array(
							'title' => __( 'Testmode', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Test Mode.', 'billmate' ),
							'default' => 'no'
						),
			'order_status' => array(
				'title' => __('Custom approved order status','billmate'),
				'type' => 'select',
				'description' => __('Choose a special order status for Billmate Partpayment, if you want to use a own status and not WooCommerce built in.','billmate'),
				'default' => 'default',
				'options' => $order_statuses
			)
		);
        if(count($available) > 1){
            $this->form_fields['billmateaccount_allowed_countries'] = array(
                'title' => __( 'Allowed Countries', 'billmate'),
                'type' => 'multiselect',
                'description' =>  __( 'Billmate Partpayment activated for customers in these countries.', 'billmate' ),
                'class' => 'choosen_select',
                'css' => 'min-width:350px;',
                'options' => $available,
                'default' => 'SE'

            );
        }
	} // End init_form_fields()


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Billmate Part Payment', 'billmate'); ?></h3>
	    	<p><?php _e('With Billmate your customers can pay by partpayment. Billmate works by adding extra personal information fields and then sending the details to Billmate for verification.', 'billmate'); ?></p>
            <p>
                <a href="https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf" target="_blank">Installationsmanual Billmate Modul ( Manual Svenska )</a><br />
                <a href="https://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf" target="_blank">Installation Manual Billmate ( Manual English )</a>
            </p>

		    <?php

			$content = get_option('wc_gateway_billmate_partpayment_pclasses',false);
			if($content){
				//$fields = array(_e('eid','billmate'),_e('paymentplanid','billmate'),_e('description','billmate'),_e('months','billmate'),_e('interestrate','billmate'),_e('startfee','billmate'),_e('handlingfee','billmate'),_e('minamount','billmate'),_e('maxamount','billmate'),_e('currency','billmate'),_e('country','billmate'),_e('expirydate','billmate'));
				?>
				<table border="0" style="border:1px solid #000">
					<tr>
						<th><?php echo ucfirst(_e('eid','billmate') )?></th>
						<th><?php echo ucfirst(_e('paymentplanid','billmate')); ?></th>
						<th><?php echo ucfirst(_e('description','billmate')); ?></th>
						<th><?php echo ucfirst(_e('months','billmate')); ?></th>
						<th><?php echo ucfirst(_e('interestrate','billmate')); ?></th>
						<th><?php echo ucfirst(_e('startfee','billmate')); ?></th>
						<th><?php echo ucfirst(_e('handlingfee','billmate')); ?></th>
						<th><?php echo ucfirst(_e('minamount','billmate')); ?></th>
						<th><?php echo ucfirst(_e('maxamount','billmate')); ?></th>
						<th><?php echo ucfirst(_e('currency','billmate')); ?></th>
						<th><?php echo ucfirst(_e('country','billmate')); ?></th>
						<th><?php echo ucfirst(_e('expirydate','billmate')); ?></th>
					</tr>
					<?php foreach( $content as $terms ):?>
						<tr>
							<td><?php echo $terms['eid']; ?></td>
							<td><?php echo $terms['paymentplanid']; ?></td>
							<td><?php echo $terms['description']; ?></td>
							<td><?php echo $terms['nbrofmonths']; ?></td>
							<td><?php echo $terms['interestrate']; ?></td>
							<td><?php echo $terms['startfee']; ?></td>
							<td><?php echo $terms['handlingfee'];?></td>
							<td><?php echo $terms['minamount'];?></td>
							<td><?php echo $terms['maxamount'];?></td>
							<td><?php echo $terms['currency'];?></td>
							<td><?php echo $terms['country']; ?></td>
							<td><?php echo $terms['expirydate']; ?></td>

						</tr>
					<?php endforeach;?>
				</table>
			<?php

			} else {
				if (strlen($this->eid) > 0 && strlen($this->secret) > 0)
					echo __('You will need to update the Pclasses','billmate');
			}

			if (isset($_GET['billmate_error_status']) && $_GET['billmate_error_status'] == '0') {
				// billmatepclasses.json file saved sucessfully
				echo '<div class="updated">'.__('The paymentplans is updated.','billmate').'</div>';
			}

			if (isset($_GET['billmate_error_status']) && $_GET['billmate_error_status'] == '1') {
				// billmatepclasses.json file could not be updated
				echo '<div class="error">'.__('Billmate paymentplans couldnt be uppdated, Billmate error message ','billmate').': ' . $_GET['billmate_error_code'] . '</div>';
			}
			?>
			<p>
		    <a class="button" href="<?php echo admin_url('admin.php?'.$_SERVER['QUERY_STRING'].'&billmatePclassListener=1');?>"><?php _e('Update Paymentplans', 'billmate'); ?> </a>
				<a class="button" href="<?php echo admin_url('admin.php?'.$_SERVER['QUERY_STRING'].'&billmatePclassListener=1&resetPclasses=1');?>"><?php _e('Clear Paymentplans', 'billmate'); ?> </a>

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
	function get_title(){
		global $woocommerce;
		if(is_object($woocommerce->cart))
		{
			$cart_total                                = $woocommerce->cart->total;
			$pclasses                                  = get_option('wc_gateway_billmate_partpayment_pclasses');
			$flags                                     = BillmateFlags::CHECKOUT_PAGE;
			$pclass                                    = BillmateCalc::getCheapestPClass($cart_total, $flags, $pclasses);
			$billmate_partpayment_monthly_cost_message = false;
			//Did we get a PClass? (it is false if we didn't)
			if ($pclass)
			{
				//Here we reuse the same values as above:
				$value = BillmateCalc::calc_monthly_cost(
					$cart_total,
					$pclass,
					$flags
				);

				/* $value is now a rounded monthly cost amount to be displayed to the customer. */
				// apply_filters to the monthly cost message so we can filter this if needed

				$billmate_partpayment_monthly_cost_message = sprintf(__('From %s %s/month', 'billmate'), $value, $this->billmate_currency);


			}
			$title = ($billmate_partpayment_monthly_cost_message) ? $billmate_partpayment_monthly_cost_message : '';

			return $this->title.' - '.$title;
		}
		else
		{
			return $this->title;
		}
	}

    function correct_lang_billmate(&$item, $index){

        $item['startfee'] = $item['startfee'] / 100;
        $item['handlingfee'] = $item['handlingfee'] / 100;
        $item['interestrate'] = $item['interestrate'] / 100;
        $item['minamount'] = $item['minamount'] / 100;
        $item['maxamount'] = $item['maxamount'] / 100;
    }
	function is_available() {
		global $woocommerce;

		if ($this->enabled=="yes") :

            if(is_checkout() == false && is_checkout_pay_page() == false) {
                // Not on store checkout page
                return true;
            }

            if (!in_array(get_option('woocommerce_currency'), array('SEK')) OR get_woocommerce_currency() != 'SEK') {
                return false;
            }

			$allowed_countries = array_intersect(array('SE'),is_array($this->allowed_countries) ? $this->allowed_countries : array($this->allowed_countries));
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
			if(is_array($this->allowed_countries) && !in_array($country , $allowed_countries)){
				return false;
			}
			$pclasses_not_available = true;

			$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses',false);

			if($pclasses){
				$billmate_cart_total = WC_Payment_Gateway::get_order_total();
				$sum = apply_filters( 'billmate_cart_total', $billmate_cart_total ); // Cart total.
                $fees = $woocommerce->cart->get_fees();
                $availableFees = array();
                foreach($fees as $fee){
                    $availableFees[$fee->id]['amount'] = $fee->amount;
                    $tax = new WC_Tax();

                    $invoicetax = $tax->get_rates($fee->tax_class);
                    $rate = array_pop($invoicetax);


                    $rate = $rate['rate'];
                    $availableFees[$fee->id]['tax'] = (($rate/100) * $fee->amount);
                }

                if(array_key_exists(sanitize_title(__('Invoice fee','billmate')),$availableFees)){

                    $sum -= ($availableFees[sanitize_title(__('Invoice fee','billmate'))]['amount'] + $availableFees[sanitize_title(__('Invoice fee','billmate'))]['tax']);
                }

				foreach ($pclasses as $pclass) {
					if (strlen($pclass['description']) > 0 ) {
						// If sum over minamount and not over maxamount or maxamount is 0
						if($sum >= $pclass['minamount'] && ($sum <= $pclass['maxamount'] || $pclass['maxamount'] == 0) )  {
							$pclasses_not_available = false;
							break;
						}
					}
				}
			}else{
				return false;
			}

			if($pclasses_not_available) return false;
			// Required fields check
			if (!$this->eid || !$this->secret) return false;

			$billmate_cart_total = $woocommerce->cart->total;
			$sum = apply_filters( 'billmate_cart_total', $billmate_cart_total ); // Cart total.
			$fees = $woocommerce->cart->get_fees();
			$availableFees = array();
			foreach($fees as $fee){
				$availableFees[$fee->id]['amount'] = $fee->amount;
				$tax = new WC_Tax();

				$invoicetax = $tax->get_rates($fee->tax_class);
				$rate = array_pop($invoicetax);


				$rate = $rate['rate'];
				$availableFees[$fee->id]['tax'] = (($rate/100) * $fee->amount);
			}

			if(array_key_exists(sanitize_title(__('Invoice fee','billmate')),$availableFees)){

				$sum -= ($availableFees[sanitize_title(__('Invoice fee','billmate'))]['amount'] + $availableFees[sanitize_title(__('Invoice fee','billmate'))]['tax']);
			}
			// Cart totals check - Lower threshold
			if ( $this->lower_threshold !== '' ) {
				if ( $sum < $this->lower_threshold ) return false;
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
 		* Retrieve the PClasses from Billmate and store it in the file billmatepclasses.json.
 		*/
 		function update_billmatepclasses_from_billmate( ) {

 		global $woocommerce;
			register_setting('wc_gateway_billmate_partpayment','pclasses');

 		if (isset($_GET['billmatePclassListener']) && $_GET['billmatePclassListener'] == '1'):
			if(isset($_GET['resetPclasses']) && $_GET['resetPclasses'] == '1'):
				update_option('wc_gateway_billmate_partpayment_pclasses',false);
			endif;


			// Test mode or Live mode
			if ( $this->testmode == 'yes' ):
				// Disable SSL if in testmode
				$billmate_ssl = 'false';
				$billmate_mode = 'test';
			else :
				// Set SSL if used in webshop
				if (is_ssl()) {
					$billmate_ssl = 'true';
				} else {
					$billmate_ssl = 'false';
				}
				$billmate_mode = 'live';
			endif;
		   		if( empty( $this->eid) ){
		   		    return false;
		   		}

			$eid = (int)$this->eid;
			$secret = $this->secret;
			$country = $this->billmate_country;
			$language = $this->billmate_language;
			$currency = $this->billmate_currency;


			$k = new BillMate( $eid, $secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );


			try {
				$language = explode('_',get_locale());
				$values = array(
					'PaymentData' => array(
						'currency' => $currency,
						'language' => $language[0],
						'country' => strtolower($country)
					)
				);
				$data = $k->getPaymentplans($values);
				if(!is_array($data)){
					throw new Exception($data);
				}
				if(isset($data['code'])){
					throw new Exception($data['message']);
				}
				$output = array();
				array_walk($data, array($this,'correct_lang_billmate'));
				foreach( $data as $row ){
					$row['eid'] = $eid;
					$output[]=$row;
				}
				update_option('wc_gateway_billmate_partpayment_pclasses',$output);

				// Redirect to settings page
				wp_redirect(admin_url('admin.php?page='.$_GET['page'].'&tab='.$_GET['tab'].'&section=WC_Gateway_Billmate_Partpayment&billmate_error_status=0'));
				}
				catch(Exception $e) {
				    //Something went wrong, print the message:
				    wc_bm_errors( sprintf(__('Billmate PClass problem: %s. Error code: ', 'billmate'), utf8_encode($e->getMessage()) ) . '"' . $e->getCode() . '"', 'error' );
				    //$billmate_error_code = utf8_encode($e->getMessage()) . 'Error code: ' . $e->getCode();

				    $redirect_url = 'admin.php?page='.$_GET['page'].'&tab='.$_GET['tab'].'&section=WC_Gateway_Billmate_Partpayment&billmate_error_status=1&billmate_error_code=' . $e->getCode().'&message='.($e->getMessage());

				    //wp_redirect(admin_url($redirect_url));
				    wp_redirect(admin_url($redirect_url));
				}

			endif;

			}




	/**
	 * Payment form on checkout page
	 */

	function payment_fields( ) {
	   	global $woocommerce;

		$enabled_plcass = 'no';
		// Test mode or Live mode
		if ( $this->testmode == 'yes' ):
			// Disable SSL if in testmode
			$billmate_ssl = 'false';
			$billmate_mode = 'test';
		else :
			// Set SSL if used in webshop
			if (is_ssl()) {
				$billmate_ssl = 'true';
			} else {
				$billmate_ssl = 'false';
			}
			$billmate_mode = 'live';
		endif;

   		if( empty( $this->secret) ){
   		    return false;
   		}
		$eid = (int)$this->eid;
		$secret = $this->secret;
		$country = $this->billmate_country;
		$language = $this->billmate_language;
		$currency = $this->billmate_currency;

		$billmate_pclass_file = BILLMATE_DIR . 'srv/billmatepclasses.json';

		// apply_filters to cart total so we can filter this if needed
		$billmate_cart_total = $woocommerce->cart->total;
		$sum = apply_filters( 'billmate_cart_total', $billmate_cart_total ); // Cart total.
		$flag = BillmateFlags::CHECKOUT_PAGE; //or BillmateFlags::PRODUCT_PAGE, if you want to do it for one item.

	   	?>

	   	<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'billmate'); ?></p><?php endif; ?>
		<?php

		// Description
		if ($this->description) :
			// apply_filters to the description so we can filter this if needed
			$billmate_description = strlen($this->description ) ? $this->description : '';
			echo '<p>' . apply_filters( 'billmate_partpayment_description', $billmate_description ) . '</p>';
		endif;

		// Show billmate_warning_banner if NL
		?>
        <?php
        if(isset($_GET['pay_for_order']) && isset($_SESSION['address_verification']) && (isset($_POST['billmate_pno']) && $_POST['billmate_pno'] != '')) {
            echo $_SESSION['address_verification'];
            unset($_SESSION['address_verification']);
        }
        ?>


		<fieldset>
			<p class="form-row">
				<?php $return = $this->payment_fields_options( $sum );  extract($return); ?>
			</p>
			<?php
			// Calculate lowest monthly cost and display it
			if( $enabled_plcass == 'no' ) return false;
			$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses');
			if(!$pclasses) return false;
			/*$pclass = BillmateCalc::getCheapestPClass($sum, $flag, $pclasses);

			//Did we get a PClass? (it is false if we didn't)
			if($pclass) {
	    		//Here we reuse the same values as above:
    			$value = BillmateCalc::calc_monthly_cost(
    	    	$sum,
    	    	$pclass,
    	    	$flag
    			);

	    		/* $value is now a rounded monthly cost amount to be displayed to the customer. */
	    		// apply_filters to the monthly cost message so we can filter this if needed

	    		/*$billmate_partpayment_monthly_cost_message = sprintf(__('From %s %s/month', 'billmate'), $value, $this->billmate_currency );

	    		echo '<p class="form-row form-row-last billmate-monthly-cost">' . apply_filters( 'billmate_partpayment_monthly_cost_message', $billmate_partpayment_monthly_cost_message ) . '</p>';


			}*/
			?>
			<div class="clear"></div>

			<p class="form-row" id="partpay_pno">
				<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>

				<label for="billmate_pno"><?php echo __("Social Security No. / Org. No. ", 'billmate') ?> <span class="required">*</span></label>
                    <select class="dob_select dob_day" name="date_of_birth_day" style="width:60px;">
                        <option value="">
                        <?php echo __("Day", 'billmate') ?>
                        </option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                    </select>
                    <select class="dob_select dob_month" name="date_of_birth_month" style="width:80px;">
                        <option value="">
                        <?php echo __("Month", 'billmate') ?>
                        </option>
                        <option value="01"><?php echo __("Jan", 'billmate') ?></option>
                        <option value="02"><?php echo __("Feb", 'billmate') ?></option>
                        <option value="03"><?php echo __("Mar", 'billmate') ?></option>
                        <option value="04"><?php echo __("Apr", 'billmate') ?></option>
                        <option value="05"><?php echo __("May", 'billmate') ?></option>
                        <option value="06"><?php echo __("Jun", 'billmate') ?></option>
                        <option value="07"><?php echo __("Jul", 'billmate') ?></option>
                        <option value="08"><?php echo __("Aug", 'billmate') ?></option>
                        <option value="09"><?php echo __("Sep", 'billmate') ?></option>
                        <option value="10"><?php echo __("Oct", 'billmate') ?></option>
                        <option value="11"><?php echo __("Nov", 'billmate') ?></option>
                        <option value="12"><?php echo __("Dec", 'billmate') ?></option>
                    </select>
                    <select class="dob_select dob_year" name="date_of_birth_year" style="width:60px;">
                        <option value="">
                        <?php echo __("Year", 'billmate') ?>
                        </option>
                        <option value="1920">1920</option>
                        <option value="1921">1921</option>
                        <option value="1922">1922</option>
                        <option value="1923">1923</option>
                        <option value="1924">1924</option>
                        <option value="1925">1925</option>
                        <option value="1926">1926</option>
                        <option value="1927">1927</option>
                        <option value="1928">1928</option>
                        <option value="1929">1929</option>
                        <option value="1930">1930</option>
                        <option value="1931">1931</option>
                        <option value="1932">1932</option>
                        <option value="1933">1933</option>
                        <option value="1934">1934</option>
                        <option value="1935">1935</option>
                        <option value="1936">1936</option>
                        <option value="1937">1937</option>
                        <option value="1938">1938</option>
                        <option value="1939">1939</option>
                        <option value="1940">1940</option>
                        <option value="1941">1941</option>
                        <option value="1942">1942</option>
                        <option value="1943">1943</option>
                        <option value="1944">1944</option>
                        <option value="1945">1945</option>
                        <option value="1946">1946</option>
                        <option value="1947">1947</option>
                        <option value="1948">1948</option>
                        <option value="1949">1949</option>
                        <option value="1950">1950</option>
                        <option value="1951">1951</option>
                        <option value="1952">1952</option>
                        <option value="1953">1953</option>
                        <option value="1954">1954</option>
                        <option value="1955">1955</option>
                        <option value="1956">1956</option>
                        <option value="1957">1957</option>
                        <option value="1958">1958</option>
                        <option value="1959">1959</option>
                        <option value="1960">1960</option>
                        <option value="1961">1961</option>
                        <option value="1962">1962</option>
                        <option value="1963">1963</option>
                        <option value="1964">1964</option>
                        <option value="1965">1965</option>
                        <option value="1966">1966</option>
                        <option value="1967">1967</option>
                        <option value="1968">1968</option>
                        <option value="1969">1969</option>
                        <option value="1970">1970</option>
                        <option value="1971">1971</option>
                        <option value="1972">1972</option>
                        <option value="1973">1973</option>
                        <option value="1974">1974</option>
                        <option value="1975">1975</option>
                        <option value="1976">1976</option>
                        <option value="1977">1977</option>
                        <option value="1978">1978</option>
                        <option value="1979">1979</option>
                        <option value="1980">1980</option>
                        <option value="1981">1981</option>
                        <option value="1982">1982</option>
                        <option value="1983">1983</option>
                        <option value="1984">1984</option>
                        <option value="1985">1985</option>
                        <option value="1986">1986</option>
                        <option value="1987">1987</option>
                        <option value="1988">1988</option>
                        <option value="1989">1989</option>
                        <option value="1990">1990</option>
                        <option value="1991">1991</option>
                        <option value="1992">1992</option>
                        <option value="1993">1993</option>
                        <option value="1994">1994</option>
                        <option value="1995">1995</option>
                        <option value="1996">1996</option>
                        <option value="1997">1997</option>
                        <option value="1998">1998</option>
                        <option value="1999">1999</option>
                        <option value="2000">2000</option>
                    </select>

				<?php else : ?>
					<label for="billmate_pno"><?php echo __("Social Security Number / Corporate Registration Number", 'billmate') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="billmate_pno" value="<?php echo isset($_POST['billmate_pno']) ? $_POST['billmate_pno'] : '' ?>"/>
				<?php endif; ?>
			</p>

			<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				<p class="form-row form-row-last">
					<label for="billmate_partpayment_gender"><?php echo __("Gender", 'billmate') ?> <span class="required">*</span></label>
					<select id="billmate_partpayment_gender" name="billmate_partpayment_gender" class="woocommerce-select" style="width:120px;">
						<option value=""><?php echo __("Select gender", 'billmate') ?></option>
						<option value="0"><?php echo __("Female", 'billmate') ?></option>
						<option value="1"><?php echo __("Male", 'billmate') ?></option>
					</select>
				</p>
			<?php endif; ?>

			<div class="clear"></div>

<?php
$datatemp = array('billing_email'=>'');
if(!empty($_POST['post_data'])){
parse_str($_POST['post_data'], $datatemp);
}
?>
		<div class="clear"></div>
			<p class="form-row">
				<input type="checkbox" class="input-checkbox" checked="checked" value="yes" name="valid_email_it_is" id="valid_email_it_is" style="float:left;margin-top:6px" />
				<label><?php echo sprintf(__('My e-mail%s is correct och and may be used for billing. I confirm the ', 'billmate'), (strlen($datatemp['billing_email']) > 0) ? ', '.$datatemp['billing_email'].',' : ' '); ?><a class="billmateCheckoutTermLink" href="https://billmate.se/billmate/?cmd=villkor_delbetalning" onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=650');return false;"><?php echo __('terms of partpayment','billmate'); ?></a> <?php echo __('and accept the liability.','billmate') ?></label>
			</p>

			<?php if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes' ) : ?>
				<p class="form-row">
					<label for="billmate_de_terms"></label>
					<input type="checkbox" class="input-checkbox" value="yes" name="billmate_de_consent_terms" />
					<?php echo sprintf(__('Mit der Übermittlung der für die Abwicklungdes Rechnungskaufes und einer Identitäts-und Bonitätsprüfung erforderlichen Daten an Billmate bin ich einverstanden. Meine <a href="%s" target="_blank">Einwilligung</a> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers.', 'billmate'), 'https://online.billmate.com/consent_de.yaws') ?>

				</p>
			<?php endif; ?>
			<div class="clear"></div>

		</fieldset>
		<?php
	}
	public static function update_billmatepclasses_from_frontend(){
		global $wpdb;
		$settings = get_option('woocommerce_billmate_partpayment_settings');
		if ( $settings['testmode'] == 'yes' ):
			// Disable SSL if in testmode
			$billmate_ssl = 'false';
			$billmate_mode = 'test';
		else :
			// Set SSL if used in webshop
			if (is_ssl()) {
				$billmate_ssl = 'true';
			} else {
				$billmate_ssl = 'false';
			}
			$billmate_mode = 'live';
		endif;
		$eid = (int)get_option('billmate_common_eid');

		if( empty( $eid)){
			return false;
		}

		$secret = get_option('billmate_common_secret');
		$country = self::$country;
		$currency = get_woocommerce_currency();
		$language = explode('_',get_locale());
		if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));

		$k = new BillMate( $eid, $secret, true, $settings['testmode'] == 'yes', false, $this->getRequestMeta() );


		try {
			$language = explode('_',get_locale());
			$values = array(
				'PaymentData' => array(
					'currency' => $currency,
					'language' => $language[0],
					'country' => strtolower($country)
				)
			);
			$data = $k->getPaymentplans($values);
			if(!is_array($data)){
				throw new Exception($data);
			}
			if(isset($data['code'])){
				throw new Exception($data['message']);
			}
			$output = array();
			$i = 0;
			foreach($data as $item){
				$data[$i]['startfee'] = $item['startfee'] / 100;
				$data[$i]['handlingfee'] = $item['handlingfee'] / 100;
				$data[$i]['interestrate'] = $item['interestrate'] / 100;
				$data[$i]['minamount'] = $item['minamount'] / 100;
				$data[$i]['maxamount'] = $item['maxamount'] / 100;
				$i++;
			}
			foreach( $data as $row ){
				$row['eid'] = $eid;
				$output[]=$row;
			}
			$wpdb->update($wpdb->options,array('option_value' => maybe_serialize($output)),array('option_name' => 'wc_gateway_billmate_partpayment_pclasses'));

		}
		catch(Exception $e) {
			//Something went wrong, print the message:
			wc_bm_errors( sprintf(__('Billmate PClass problem: %s. Error code: ', 'billmate'), utf8_encode($e->getMessage()) ) . '"' . $e->getCode() . '"', 'error' );
		}
	}

	/**
	 * Payment field opttions
	**/
	public static function payment_fields_options( $sum, $label = true ,$flag = BillmateFlags::CHECKOUT_PAGE){


		$pclasses_not_available = true;
		$enabled_plcass = 'no';

		$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses',false);
		if(date('Y-m-d',strtotime('+1 week')) >= $pclasses[0]['expirydate']){
			$_GET['billmatePclassListener'] = 1;
			self::update_billmatepclasses_from_frontend();
		}
		if($pclasses){
			$pclasses_not_available = false;
		}
		if($flag != BillmateFlags::CHECKOUT_PAGE){

			$pclasses = BillmateCalc::getCheapestPClass($sum,BillmateFlags::CHECKOUT_PAGE,$pclasses);
			$pclasses = array($pclasses);
			$flag = BillmateFlags::CHECKOUT_PAGE;
		}
		// Check if we have any PClasses
		if(!$pclasses_not_available) {
			if( $label ) {
		?>
                <label for="billmate_partpayment_pclass"><?php echo __("Payment plan", 'billmate') ?> <span class="required">*</span></label><br/>
                <select style="width:auto" id="billmate_partpayment_pclass" name="billmate_partpayment_pclass" class="woocommerce-select">

                <?php
                // Loop through the available PClasses stored in the file srv/billmatepclasses.json

                foreach ($pclasses as $pclass2) {

                    $pclass = $pclass2;

                    if (strlen($pclass['description']) > 0 ) {

                        // Get monthly cost for current pclass
                        $monthly_cost = BillmateCalc::calc_monthly_cost(
                                            $sum,
                                            $pclass,
                                            $flag
                                        );

                        // Get total credit purchase cost for current pclass
                        // Only required in Norway
                        $total_credit_purchase_cost = BillmateCalc::total_credit_purchase_cost(
                                            $sum,
                                            $pclass,
                                            $flag
                                        );

                        // Check that Cart total is larger than min amount for current PClass
                        if($sum >= $pclass['minamount'] && ($sum <= $pclass['maxamount'] || $pclass['maxamount'] == 0) ) {
                            $enabled_plcass = 'yes';
                            echo '<option value="' . $pclass['paymentplanid'] . '">';
                            if (self::$country == 'NO') {
                                if ( $pclass['type'] == 1 ) {
                                    //If Account - Do not show startfee. This is always 0.
                                    echo sprintf(__('%s - %s %s/month', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency());
                                    } else {
                                        // Norway - Show total cost
                                        echo sprintf(__('%s - %s %s/month - %s%s - Start %s - Tot %s %s', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency(), $pclass['interestrate'], '%', $pclass['startfee'], $total_credit_purchase_cost, get_woocommerce_currency() );
                                    }
                                } else {
                                    if ( $pclass['type'] == 1 ) {
                                        //If Account - Do not show startfee. This is always 0.
                                        echo sprintf(__('%s - %s %s/month', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency() );
                                    } else {
                                        // Sweden, Denmark, Finland, Germany & Netherlands - Don't show total cost
                                        echo sprintf(__('%s - %s %s/month - %s%s - Start %s', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency(), $pclass['interestrate'], '%', $pclass['startfee'] );
                                    }
                                }
                            echo '</option>';

                        } // End if ($sum > $pclass->getMinAmount())

                    } // End if $pclass->getType() == 0 or 1

                } // End foreach
                ?>

                </select>

			<?php } else { ?>
				<div>

                <?php
                // Loop through the available PClasses stored in the file srv/billmatepclasses.json
                if( in_array(self::$country, is_array(self::$allowed_countries_static) ? self::$allowed_countries_static : array(self::$allowed_countries_static)) ) {

					foreach ($pclasses as $pclass2) {

						$pclass = $pclass2;

						if (strlen($pclass['description']) > 0 ) {

							// Get monthly cost for current pclass
							$monthly_cost = BillmateCalc::calc_monthly_cost(
												$sum,
												$pclass,
												$flag
											);

							// Get total credit purchase cost for current pclass
							// Only required in Norway
							$total_credit_purchase_cost = BillmateCalc::total_credit_purchase_cost(
												$sum,
												$pclass,
												$flag
											);

							// Check that Cart total is larger than min amount for current PClass
							if($sum >= $pclass['minamount'] && ($sum <= $pclass['maxamount'] || $pclass['maxamount'] == 0) ) {
								$enabled_plcass = 'yes';
								echo '<div>';
								if (self::$country == 'NO') {
									if ( $pclass['type'] == 1 ) {
										//If Account - Do not show startfee. This is always 0.
										echo sprintf(__('%s - %s %s/month', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency());
										} else {
											// Norway - Show total cost
											echo sprintf(__('%s - %s %s/month - %s%s - Start %s - Tot %s %s', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency(), $pclass['interestrate'], '%', $pclass['startfee'], $total_credit_purchase_cost, get_woocommerce_currency() );
										}
								} else {
										if ( $pclass['type'] == 1 ) {
											//If Account - Do not show startfee. This is always 0.
											echo sprintf(__('%s - %s %s/month', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency() );
										} else {
											// Sweden, Denmark, Finland, Germany & Netherlands - Don't show total cost
											echo sprintf(__('%s - %s %s/month - %s%s - Start %s', 'billmate'), $pclass['description'], $monthly_cost, get_woocommerce_currency(), $pclass['interestrate'], '%', $pclass['startfee'] );
										}
								}
								echo '</div>';

							} // End if ($sum > $pclass->getMinAmount())

						} // End if $pclass->getType() == 0 or 1

					} // End foreach
				}
                ?>

                </div>
			<?php
            }
		} else {
			echo __('Billmate PClasses seem to be missing. Billmate Part Payment does not work.', 'billmate');
		}

		return array('enabled_plcass'=>$enabled_plcass, 'pclasses'=>$pclasses);
	}


	/**
 	 * Process the gateway specific checkout form fields
 	**/
	function billmate_partpayment_checkout_field_process() {
    	global $woocommerce;


 		// Only run this if Billmate Delbetalning is the choosen payment method
 		if ($_POST['payment_method'] == 'billmate_partpayment') {

 			// SE, NO, DK & FI
 			if ( $this->shop_country == 'SE' || $this->shop_country == 'NO' || $this->shop_country == 'DK' || $this->shop_country == 'FI' ){

    			// Check if set, if its not set add an error.
    			if (!$_POST['billmate_pno'])
        		 	wc_bm_errors( '<i data-error-code="9015"></i>'.__('Non Valid Person / Corporate number. Check the number.', 'billmate') );

			}

			// NL & DE
	 		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ){
	    		// Check if set, if its not set add an error.

	    		// Gender
	    		if (!isset($_POST['billmate_partpayment_gender']))
	        	 	wc_bm_errors( __('Non Valid Person / Corporate number. Check the number.', 'billmate') );

	         	// Personal / Corporate
				if (!$_POST['date_of_birth_day'] || !$_POST['date_of_birth_month'] || !$_POST['date_of_birth_year'])
	         		wc_bm_errors( __('Non Valid Person / Corporate number. Check the number.', 'billmate') );

	         	// Shipping and billing address must be the same
	         	$billmate_shiptobilling = ( isset( $_POST['shiptobilling'] ) ) ? $_POST['shiptobilling'] : '';

	         	if ($billmate_shiptobilling !=1 && isset($_POST['shipping_first_name']) && $_POST['shipping_first_name'] !== $_POST['billing_first_name'])
	        	 	wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate.', 'billmate') );

	        	 if ($billmate_shiptobilling !=1 && isset($_POST['shipping_last_name']) && $_POST['shipping_last_name'] !== $_POST['billing_last_name'])
	        	 	wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate.', 'billmate') );

	        	 if ($billmate_shiptobilling !=1 && isset($_POST['shipping_address_1']) && $_POST['shipping_address_1'] !== $_POST['billing_address_1'])
	        	 	wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate.', 'billmate') );

	        	 if ($billmate_shiptobilling !=1 && isset($_POST['shipping_postcode']) && $_POST['shipping_postcode'] !== $_POST['billing_postcode'])
	        	 	wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate.', 'billmate') );

	        	 if ($billmate_shiptobilling !=1 && isset($_POST['shipping_city']) && $_POST['shipping_city'] !== $_POST['billing_city'])
	        	 	wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate.', 'billmate') );
			}

			// DE
			if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes'){
	    		// Check if set, if its not set add an error.
	    		if (!isset($_POST['billmate_de_consent_terms']))
	        	 	wc_bm_errors( __('You must accept the Billmate consent terms.', 'billmate') );
			}
		}
	}

	public function validate_fields()
	{

	}


	public function getAddressPayment(&$order)
	{

        $billmateOrder = new BillmateOrder($order);

		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$billmate_pno_day 			= isset($_POST['date_of_birth_day']) ? $this->woocommerce_clean($_POST['date_of_birth_day']) : '';
			$billmate_pno_month 			= isset($_POST['date_of_birth_month']) ? $this->woocommerce_clean($_POST['date_of_birth_month']) : '';
			$billmate_pno_year 			= isset($_POST['date_of_birth_year']) ? $this->woocommerce_clean($_POST['date_of_birth_year']) : '';
			$billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
		else :
			$billmate_pno 				= isset($_POST['billmate_pno']) ? $this->woocommerce_clean($_POST['billmate_pno']) : '';
		endif;
		if($billmate_pno == ''){
			return;
		}
		$billmate_gender 					= isset($_POST['billmate_partpayment_gender']) ? $this->woocommerce_clean($_POST['billmate_partpayment_gender']) : '';
		$billmate_de_consent_terms		= isset($_POST['billmate_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_de_consent_terms']) : '';
		// Split address into House number and House extension for NL & DE customers
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
			require_once('split-address.php');
			$billmate_billing_address				= $order->billing_address_1;
			$splitted_address 					= splitAddress($billmate_billing_address);
			$billmate_billing_address				= $splitted_address[0];
			$billmate_billing_house_number		= $splitted_address[1];
			$billmate_billing_house_extension		= $splitted_address[2];
			$billmate_shipping_address			= !empty($order->shipping_address_1) ? $order->shipping_address_1 : $billmate_billing_address;
			$splitted_address 					= splitAddress($billmate_shipping_address);
			$billmate_shipping_address			= $splitted_address[0];
			$billmate_shipping_house_number		= $splitted_address[1];
			$billmate_shipping_house_extension	= $splitted_address[2];
		} else {

            if ($billmateOrder->is_wc3()) {
                $billmate_billing_address           = $order->get_billing_address_1();
                $billmate_billing_house_number      = '';
                $billmate_billing_house_extension   = '';
                $billmate_shipping_address          = $order->get_shipping_address_1();
                $billmate_shipping_address          = ($billmate_shipping_address != '') ? $billmate_shipping_address : $billmate_billing_address;
                $billmate_shipping_house_number     = '';
                $billmate_shipping_house_extension  = '';
            } else {
                $billmate_billing_address           = $order->billing_address_1;
                $billmate_billing_house_number      = '';
                $billmate_billing_house_extension   = '';
                $billmate_shipping_address          = !empty($order->shipping_address_1) ? $order->shipping_address_1 : $billmate_billing_address;
                $billmate_shipping_house_number     = '';
                $billmate_shipping_house_extension  = '';
            }
		}
		$language = explode('_',get_locale());
		if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));


		$k = new Billmate( $this->eid, $this->secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );
		try{
			$addr = $k->getAddress(array('pno' => $billmate_pno));
		}catch( Exception $ex ){
			wc_bm_errors( utf8_encode($ex->getMessage()) );
			return;
		}

		if( !is_array( $addr ) ) {
			wc_bm_errors( __('Unable to find address.'.$addr, 'billmate') );
			return;
		}
		if(isset($addr['code'])){
			wc_bm_errors('<span data-error-code="'.$addr['code'].'"></span>'.utf8_encode($addr['message']));
			return;
		}
		foreach($addr as $key => $value){
			$addr[$key] = utf8_encode(utf8_decode($value));
		}


        if ($billmateOrder->is_wc3()) {
            $billing_first_name     = $order->get_billing_first_name();
            $billing_last_name      = $order->get_billing_last_name();
            $billing_company        = $order->get_billing_company();
            $billing_address_1      = $order->get_billing_address_1();
            $billing_postcode       = $order->get_billing_postcode();
            $billing_city           = $order->get_billing_city();
            $billing_country        = $order->get_billing_country();

            $shipping_first_name    = $order->get_shipping_first_name();
            $shipping_last_name     = $order->get_shipping_last_name();
            $shipping_company       = $order->get_shipping_company();
            $shipping_address_1     = $order->get_shipping_address_1();
            $shipping_postcode      = $order->get_shipping_postcode();
            $shipping_city          = $order->get_shipping_city();
            $shipping_country       = $order->get_shipping_country();

            $usership = $billing_first_name.' '.$billing_last_name.' '.$billing_company;
            $userbill = $shipping_first_name.' '.$shipping_last_name.' '.$shipping_company;
            $userbill = (trim($userbill) != '') ? $userbill : $usership;
        } else {
            $billing_first_name     = $order->billing_first_name;
            $billing_last_name      = $order->billing_last_name;
            $billing_company        = $order->billing_company;
            $billing_address_1      = $order->billing_address_1;
            $billing_postcode       = $order->billing_postcode;
            $billing_city           = $order->billing_city;
            $billing_country        = $order->billing_country;

            $shipping_first_name    = $order->shipping_first_name;
            $shipping_last_name     = $order->shipping_last_name;
            $shipping_company       = $order->shipping_company;
            $shipping_address_1     = $order->shipping_address_1;
            $shipping_postcode      = $order->shipping_postcode;
            $shipping_city          = $order->shipping_city;
            $shipping_country       = $order->shipping_country;

            $usership = $order->billing_first_name.' '.$order->billing_last_name.' '.$order->billing_company;
            $userbill = (isset($order->shipping_first_name) && isset($order->shipping_last_name) && isset($order->shipping_company)) ? $order->shipping_first_name.' '.$order->shipping_last_name.' '.$order->shipping_company : $usership;
        }

        if( strlen( $addr['firstname'] )) {
            $name = $addr['firstname'];
            $lastname = $addr['lastname'];
            $company = '';
            $apiName =  $addr['firstname'].' '.$addr['lastname'];
            $usership = $billing_first_name.' '.$billing_last_name;
            $displayname = $addr['firstname'].' '.$addr['lastname'];
        } else {
            $name = $_POST['billing_first_name'];
            $lastname = $_POST['billing_last_name'];
            $company  =  $addr['company'];
            $usership = $billing_first_name.' '.$billing_last_name.' '.$billing_company;
            $apiName =  $name.' '.$lastname.' '.$addr['company'];
            $displayname = $billing_first_name.' '.$billing_last_name.'<br/>'.$addr['company'];
        }

        $addressNotMatched  = !isEqual( $usership,  $apiName) ||
            !isEqual($addr['street'], $billmate_billing_address ) ||
            !isEqual($addr['zip'], $billing_postcode) ||
            !isEqual($addr['city'], $billing_city) ||
            !isEqual(strtoupper($addr['country']), strtoupper($billing_country));

        if(isset($shipping_address_1) && isset($shipping_postcode) && isset($shipping_city) && isset($shipping_country)) {
            $shippingAndBilling = !isEqual($usership, $userbill) ||
                !isEqual($billing_address_1, $shipping_address_1) ||
                !isEqual($billing_postcode, $shipping_postcode) ||
                !isEqual($billing_city, $shipping_city) ||
                !isEqual($billing_country, $shipping_country);
        } else {
            $shippingAndBilling = false;
        }

		global $woocommerce;

		$importedCountry = isset($addr['country']) ? $addr['country'] : '';

		if( $addressNotMatched || $shippingAndBilling ){
			if( empty($_POST['geturl'] ) ){
				$html = $displayname.'<br>'.$addr['street'].'<br>'.$addr['zip'].' '.$addr['city'].'<br/>'.$importedCountry.'<div style="margin-top:1em"><input type="button" value="'.__('Yes, make purchase with this address','billmate').'" onclick="ajax_load(this);modalWin.HideModalPopUp(); " class="billmate_button"/></div><a onclick="noPressButton()" class="linktag">'.__('No, I want to specify a different number or change payment method','billmate').'</a>';
				$html.= '<span id="hidden_data"><input type="hidden" id="_first_name" value="'.$name.'" />';
				$html.= '<input type="hidden" id="_last_name" value="'.$lastname.'" />';
				$html.= '<input type="hidden" id="_company" value="'.$company.'" />';
				$html.= '<input type="hidden" id="_address_1" value="'.$addr['street'].'" />';
				$html.= '<input type="hidden" id="_postcode" value="'.$addr['zip'].'" />';
				$html.= '<input type="hidden" id="_city" value="'.$addr['city'].'" /></span>';
				if(version_compare(WC_VERSION,'2.4.0','<')) {
					echo $code = '<script type="text/javascript">setTimeout(function(){modalWin.ShowMessage(\'' . $html . '\',350,500,\'' . __('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:', 'billmate') . '\');},1000);</script>';
					//wc_bm_errors($code);
					die;
				} else {
					$_SESSION['address_verification'] = '<script type="text/javascript">setTimeout(function(){modalWin.ShowMessage(\''.$html.'\',350,500,\''.__('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:','billmate').'\');},1000);</script>';
					wc_bm_errors(__('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:','billmate'));
					return false;
				}
			} else {
                if ($billmateOrder->is_wc3()) {

                    $order->set_billing_first_name($name);
                    $order->set_billing_last_name($lastname);
                    $order->set_billing_company($company);
                    $order->set_billing_address_1($addr['street']);
                    $order->set_billing_postcode($addr['zip']);
                    $order->set_billing_city($addr['city']);
                    $order->set_billing_country($addr['country']);

                    $order->set_shipping_first_name($name);
                    $order->set_shipping_last_name($lastname);
                    $order->set_shipping_company($company);
                    $order->set_shipping_address_1($addr['street']);
                    $order->set_shipping_postcode($addr['zip']);
                    $order->set_shipping_city($addr['city']);
                    $order->set_shipping_country($addr['country']);

                    $order->save();

                } else {

                    $order->billing_first_name = $order->shipping_first_name = $name;
                    $order->billing_last_name = $order->shipping_last_name = $lastname;
                    $order->billing_company = $order->shipping_company = $company;
                    $order->billing_address_1 =  $order->shipping_address_1 = $addr['street'];
                    $order->billing_postcode =  $order->shipping_postcode = $addr['zip'];
                    $order->billing_city =  $order->shipping_city = $addr['city'];
                    $order->billing_country =  $order->shipping_country = $addr['country'];
                    $address = array(
                        'first_name' => $name,
                        'last_name'  => $lastname,
                        'company'    => $company,
                        'email'      => $order->billing_email,
                        'phone'      => $order->billing_phone,
                        'address_1'  => $addr['street'],
                        'address_2'  => '',
                        'city'       => $addr['city'],
                        'state'      => '',
                        'postcode'   => $addr['zip'],
                        'country'    => $addr['country']
                    );
                    $order->set_address($address,'billing');
                    $order->set_address($address,'shipping');
                }
                return true;
			}
		}
	}
	public function getAddress( )
	{
		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$billmate_pno_day 			= isset($_POST['billmate_date_of_birth_day']) ? $this->woocommerce_clean($_POST['billmate_date_of_birth_day']) : '';
			$billmate_pno_month 			= isset($_POST['billmate_date_of_birth_month']) ? $this->woocommerce_clean($_POST['billmate_date_of_birth_month']) : '';
			$billmate_pno_year 			= isset($_POST['billmate_date_of_birth_year']) ? $this->woocommerce_clean($_POST['billmate_date_of_birth_year']) : '';
			$billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
		else :
			$billmate_pno 				= isset($_POST['billmate_pno']) ? $this->woocommerce_clean($_POST['billmate_pno']) : '';
		endif;
		if($billmate_pno == ''){
			return;
		}
		$billmate_gender 					= isset($_POST['billmate_gender']) ? $this->woocommerce_clean($_POST['billmate_gender']) : '';
		$billmate_de_consent_terms		= isset($_POST['billmate_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_de_consent_terms']) : '';
		// Split address into House number and House extension for NL & DE customers
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			require_once('split-address.php');
			$billmate_billing_address				= $_POST['billing_address_1'];
			$splitted_address 					= splitAddress($billmate_billing_address);
			$billmate_billing_address				= $splitted_address[0];
			$billmate_billing_house_number		= $splitted_address[1];
			$billmate_billing_house_extension		= $splitted_address[2];
			$billmate_shipping_address			= !empty($_POST['shipping_address_1']) ? $_POST['shipping_address_1'] : $billmate_billing_address;
			$splitted_address 					= splitAddress($billmate_shipping_address);
			$billmate_shipping_address			= $splitted_address[0];
			$billmate_shipping_house_number		= $splitted_address[1];
			$billmate_shipping_house_extension	= $splitted_address[2];
		else :
			$billmate_billing_address				= $_POST['billing_address_1'];
			$billmate_billing_house_number		= '';
			$billmate_billing_house_extension		= '';
			$billmate_shipping_address			= !empty($_POST['shipping_address_1']) ? $_POST['shipping_address_1'] : $billmate_billing_address;
			$billmate_shipping_house_number		= '';
			$billmate_shipping_house_extension	= '';
		endif;
		$language = explode('_',get_locale());
		if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));

		$k = new Billmate( $this->eid, $this->secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );
		try{
			$addr = $k->getAddress(array('pno' => $billmate_pno));
		}catch( Exception $ex ){
			wc_bm_errors( utf8_encode($ex->getMessage()) );
			return;
		}

		if( !is_array( $addr ) ) {
			wc_bm_errors( __('Unable to find address.'.$addr, 'billmate') );
			return;
		}
		if(isset($addr['code'])){
			wc_bm_errors('<i data-error-code="'.$addr['code'].'"></i>'.utf8_encode($addr['message']));
			return;
		}

		foreach($addr as $key => $value){
			$addr[$key] = utf8_encode(utf8_decode($value));
		}

        $post2Trim = array(
            "billing_address_1",
            "billing_address_2",
            "billing_first_name",
            "billing_last_name",
            "billing_city",
            "billing_country",
            "billing_postcode",
            "shipping_address_1",
            "shipping_address_2",
            "shipping_city",
            "shipping_company",
            "shipping_country",
            "shipping_first_name",
            "shipping_last_name",
            "shipping_postcode"
        );

        foreach($post2Trim AS $post2TrimKey) {
            if(isset($_POST[$post2TrimKey]) AND is_string($_POST[$post2TrimKey])) {
                $_POST[$post2TrimKey] = trim($_POST[$post2TrimKey]);
            }
        }

        $addr2Trim = array("firstname", "lastname", "city", "company", "country", "street", "zip");
        foreach($addr2Trim AS $addr2TrimKey) {
            if(isset($addr[$addr2TrimKey]) AND is_string($addr[$addr2TrimKey])) {
                $addr[$addr2TrimKey] = trim($addr[$addr2TrimKey]);
            }
        }

		$fullname = $_POST['billing_last_name'].' '.$_POST['billing_first_name'];
		$firstArr = explode(' ', $_POST['billing_last_name']);
		$lastArr  = explode(' ', $_POST['billing_first_name']);

		$usership = $_POST['billing_first_name'].' '.$_POST['billing_last_name'].' '.$_POST['billing_company'];
		$userbill = $_POST['shipping_first_name'].' '.$_POST['shipping_last_name'].' '.$_POST['shipping_company'];

		if( strlen( $addr['firstname'] )) {
			$name = $addr['firstname'];
			$lastname = $addr['lastname'];
			$company = '';
			$apiName =  $addr['firstname'].' '.$addr['lastname'];
			$displayname = $addr['firstname'].' '.$addr['lastname'];
		} else {
			$name = $_POST['billing_first_name'];
			$lastname = $_POST['billing_last_name'];
			$company  =  $addr['company'];
			$apiName =  $name.' '.$lastname.' '.$addr['company'];
			$displayname = $_POST['billing_first_name'].' '.$_POST['billing_last_name'].'<br/>'.$addr['company'];
		}

        $usership = (is_string($usership)) ? trim($usership) : $usership;
        $userbill = (is_string($userbill)) ? trim($userbill) : $userbill;
        $apiName = (is_string($apiName)) ? trim($apiName) : $apiName;
        $billmate_billing_address = (is_string($billmate_billing_address)) ? trim($billmate_billing_address) : $billmate_billing_address;

		$addressNotMatched  = !isEqual( $usership,  $apiName) ||
			!isEqual($addr['street'], $billmate_billing_address ) ||
			!isEqual($addr['zip'], $_POST['billing_postcode']) ||
			!isEqual($addr['city'], $_POST['billing_city']) ||
			!isEqual(strtoupper($addr['country']), strtoupper($_POST['billing_country']));

		$shippingAndBilling=  !isEqual($usership,$userbill ) ||
			!isEqual($_POST['billing_address_1'], $_POST['shipping_address_1'] ) ||
			!isEqual($_POST['billing_postcode'], $_POST['shipping_postcode']) ||
			!isEqual($_POST['billing_city'], $_POST['shipping_city']) ||
			!isEqual($_POST['billing_country'], $_POST['shipping_country']);

		$shippingAndBilling = (isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1) ? $shippingAndBilling : false;

		global $woocommerce;

		$importedCountry = isset($addr['country']) ? $addr['country'] : '';

		if( $addressNotMatched || $shippingAndBilling ){
			if( empty($_POST['geturl'] ) ){
				$html = $displayname.'<br>'.$addr['street'].'<br>'.$addr['zip'].' '.$addr['city'].'<br/>'.$importedCountry.'<div style="margin-top:1em"><input type="button" value="'.__('Yes, make purchase with this address','billmate').'" onclick="ajax_load(this);modalWin.HideModalPopUp(); " class="billmate_button"/></div><a onclick="noPressButton()" class="linktag">'.__('No, I want to specify a different number or change payment method','billmate').'</a>';
				$html.= '<span id="hidden_data"><input type="hidden" id="_first_name" value="'.$name.'" />';
				$html.= '<input type="hidden" id="_last_name" value="'.$lastname.'" />';
				$html.= '<input type="hidden" id="_company" value="'.$company.'" />';
				$html.= '<input type="hidden" id="_address_1" value="'.$addr['street'].'" />';

                if(isset($_POST['billing_address_2']) AND $_POST['billing_address_2'] != "") {
                    $html.= '<input type="hidden" id="_address_2" value="'.$this->woocommerce_clean($_POST['billing_address_2']).'" />';
                }

				$html.= '<input type="hidden" id="_postcode" value="'.$addr['zip'].'" />';
				$html.= '<input type="hidden" id="_city" value="'.$addr['city'].'" /></span>';

				if(version_compare(WC_VERSION,'2.4.0','<')) {
					echo $code = '<script type="text/javascript">setTimeout(function(){modalWin.ShowMessage(\'' . $html . '\',350,500,\'' . __('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:', 'billmate') . '\');},1000);</script>';
					//wc_bm_errors($code);
					die;
				} else {
					$code['messages'] = '<script type="text/javascript">setTimeout(function(){modalWin.ShowMessage(\''.$html.'\',350,500,\''.__('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:','billmate').'\');},1000);</script>';
					echo json_encode($code);
					die;
				}
			}
		}
	}


	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_order( $order_id );
		if(empty($_POST['valid_email_it_is'])){
            wc_bm_errors( sprintf( __('Please confirm the email %s is correct. The email will be used for invoicing.', 'billmate'), $order->billing_email ));
            return;
		}


        $billmateOrder = new BillmateOrder($order);

		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$billmate_pno_day 			= isset($_POST['date_of_birth_day']) ? $this->woocommerce_clean($_POST['date_of_birth_day']) : '';
			$billmate_pno_month 			= isset($_POST['date_of_birth_month']) ? $this->woocommerce_clean($_POST['date_of_birth_month']) : '';
			$billmate_pno_year 			= isset($_POST['date_of_birth_year']) ? $this->woocommerce_clean($_POST['date_of_birth_year']) : '';
			$billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
		else :
			$billmate_pno 			= isset($_POST['billmate_pno']) ? $this->woocommerce_clean($_POST['billmate_pno']) : '';
		endif;

		if($billmate_pno == ''){
			return;
		}

		$billmate_pclass 				= isset($_POST['billmate_partpayment_pclass']) ? $this->woocommerce_clean($_POST['billmate_partpayment_pclass']) : '';
		$billmate_gender 				= isset($_POST['billmate_partpayment_gender']) ? $this->woocommerce_clean($_POST['billmate_partpayment_gender']) : '';

		$billmate_de_consent_terms	= isset($_POST['billmate_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_de_consent_terms']) : '';


		



        $billmateOrder->setCustomerPno($billmate_pno);

		// Test mode or Live mode
		if ( $this->testmode == 'yes' ):
			// Disable SSL if in testmode
			$billmate_ssl = 'false';
			$billmate_mode = 'test';
		else :
			// Set SSL if used in webshop
			if (is_ssl()) {
				$billmate_ssl = 'true';
			} else {
				$billmate_ssl = 'false';
			}
			$billmate_mode = 'live';
		endif;

   		if( empty( $this->eid) ){
   		    return false;
   		}
		$eid = (int)$this->eid;
		$secret = $this->secret;
		$country = $this->billmate_country;
		$language = $this->billmate_language;
		$currency = $this->billmate_currency;
		$language = explode('_',get_locale());
		if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($language[0]));




		$k = new Billmate( $eid, $secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );

		$orderValues = array();
		$prepareDiscount = array();
		$lang = explode('_',get_locale());
		$orderid = ltrim($order->get_order_number(),'#');
		$orderValues['PaymentData'] = array(
			'method' => 4,
			'paymentplanid' => $billmate_pclass,
			'currency' => get_woocommerce_currency(),
			'language' => $lang[0],
			'country' => $country,
			'orderid' => $orderid,
			'logo' => (strlen($this->logo)> 0) ? $this->logo : ''

		);

        $orderValues['PaymentInfo'] = $billmateOrder->getPaymentInfoData();

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

		$orderValues['Cart']['Total'] = array(
			'withouttax' => round($total),
			'tax' => round($totalTax,0),
			'rounding' => round($round),
			'withtax' => round($total + $totalTax+ $round)
		);

        $orderValues['Customer'] = array();
        $orderValues['Customer']['pno'] = $billmateOrder->getCustomerPnoData();
        $orderValues['Customer']['nr'] = $billmateOrder->getCustomerNrData();
        $orderValues['Customer']['Billing'] = $billmateOrder->getCustomerBillingData();
        $orderValues['Customer']['Shipping'] = $billmateOrder->getCustomerShippingData();

		try {

    		$result = $k->addPayment($orderValues);
			if( !is_array($result)){
				throw new Exception($result);
			}
			// If there are any errors
			if(isset($result['code'])){
				switch($result['code']){

                    case '2401':
                    case '2402':
                    case '2403':
                    case '2404':
                    case '2405':
                        // Address not matching
                        if(!isset($_GET['pay_for_order'])) {
                            $this->getAddress();
                            die();
                        } else {
                            if(!$this->getAddressPayment($order)){
                                return array('result' => 'error');
                            }
                        }
                    break;

					case '9015':
					case '9016':
						wc_bm_errors( '<i data-error-code="'.$result['code'].'"></i>'.__($result['message'], 'billmate') );
						return;
						break;
					case '1001':
					case '2207':
					case '2103':
						$order->update_status('failed',$result['message']);
						$order->add_order_note('Billmate: '.$result['code'].' '.utf8_encode($result['message']));
						throw new Exception($result['message'],$result['code']);
						break;
					default:
						throw new Exception($result['message'],$result['code']);
						break;
				}
			}
    		// Retreive response

            $invno = (isset($result['number'])) ? $result['number'] : '';

            if (!isset($result['code']) AND isset($result['status'])) {
                switch($result['status']) {
                    case 'OK':
                    case 'Created':
                        $order->add_order_note( __('Billmate payment completed. Billmate Invoice number:', 'billmate') . $invno );
                        add_post_meta($order_id,'billmate_invoice_id',$invno);

                        // Payment complete
                        $order->payment_complete();
                        if ($this->order_status != 'default') {
                            $order->update_status($this->order_status);
                            $order->save();
                        }

                        // Remove cart
                        $woocommerce->cart->empty_cart();
                        if (version_compare(WC_VERSION, '2.0.0', '<')) {
                            $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                        } else {
                            $redirect = $this->get_return_url($order);
                        }

                        // Return thank you redirect
                        return array(
                            'result'    => 'success',
                            'redirect'  => $redirect
                        );

                        break;
                    case 'Pending':
                        $order->add_order_note( __('Order is PENDING APPROVAL by Billmate. Please visit Billmate Online for the latest status on this order. Billmate Invoice number: ', 'billmate') . $invno );
                        add_post_meta($order_id,'billmate_invoice_id',$invno);

                        // Payment complete
                        $order->payment_complete();
                        if ($this->order_status != 'default') {
                            $order->update_status($this->order_status);
                            $order->save();
                        }

                        // Remove cart
                        $woocommerce->cart->empty_cart();
                        if (version_compare(WC_VERSION, '2.0.0', '<')) {
                            $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                        } else {
                            $redirect = $this->get_return_url($order);
                        }

                        // Return thank you redirect
                        return array(
                            'result'    => 'success',
                            'redirect'  => $redirect
                        );

                        break;
                    default:
                        //Unknown response, store it in a database.
                        $order->add_order_note( __('Unknown response from Billmate.', 'billmate') );
                        wc_bm_errors( __('Unknown response from Billmate.', 'billmate') );
                        return;
                        break;
                }
            }

        } catch(Exception $e) {
            //The purchase was denied or something went wrong, print the message:
            if(!isset($_GET['pay_for_order'])) {
                if(version_compare(WC_VERSION,'2.4.0','<')) {
                    echo '<ul class="woocommerce-error"><li>'.sprintf(__('%s (Error code: %s)', 'billmate'), utf8_encode($e->getMessage()), $e->getCode() ).'<script type="text/javascript">jQuery("#billmategeturl").remove();</script></li></ul>';

                    die();
                } else {
                    $code['messages'] =  '<ul class="woocommerce-error"><li>'.sprintf(__('%s (Error code: %s)', 'billmate'), utf8_encode($e->getMessage()), $e->getCode() ).'<script type="text/javascript">jQuery("#billmategeturl").remove();</script></li></ul>';

                    echo json_encode($code);
                    die();
                }
            } else {
                echo '<ul class="woocommerce-error"><li>'.sprintf(__('%s (Error code: %s)', 'billmate'), utf8_encode($e->getMessage()), $e->getCode() ).'<script type="text/javascript">jQuery("#billmategeturl").remove(); </script></li></ul>';
            }
        }
	}

	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {

		echo '<p>'.__('Thank you for your order.', 'billmate').'</p>';

	}

	/**
	 * Calc monthly cost on single Product page and print it out
	 **/

    static function print_product_monthly_cost() {

        $queried_object = get_queried_object();

        /* Settings */
        $settings = get_option('woocommerce_billmate_partpayment_settings');
        $enabled = (isset($settings['enabled'])) ? $settings['enabled'] : '';
        $show_monthly_cost = (isset($settings['show_monthly_cost'])) ? $settings['show_monthly_cost'] : '';
        $show_monthly_cost_info = (isset($settings['show_monthly_cost_info'])) ? $settings['show_monthly_cost_info'] : '';
        $testmode = (isset($settings['testmode'])) ? $settings['testmode'] : '';
        $lower_threshold_monthly_cost = (isset($settings['lower_threshold_monthly_cost'])) ? $settings['lower_threshold_monthly_cost'] : '';
        $upper_threshold_monthly_cost = (isset($settings['upper_threshold_monthly_cost'])) ? $settings['upper_threshold_monthly_cost'] : '';

        $lower_threshold_monthly_cost = ($lower_threshold_monthly_cost != '') ? $lower_threshold_monthly_cost : 0;
        $upper_threshold_monthly_cost = ($upper_threshold_monthly_cost != '') ? $upper_threshold_monthly_cost : 10000000;

        $eid = get_option('billmate_common_eid');

        $country_data = self::get_country_data();
        $billmate_country = isset($country_data['billmate_country']) ? $country_data['billmate_country'] : '';
        $billmate_language = isset($country_data['billmate_language']) ? $country_data['billmate_language'] : '';
        $billmate_currency = isset($country_data['billmate_currency']) ? $country_data['billmate_currency'] : '';
        $billmate_partpayment_info = isset($country_data['billmate_partpayment_info']) ? $country_data['billmate_partpayment_info'] : '';
        $billmate_partpayment_icon = isset($country_data['billmate_partpayment_icon']) ? $country_data['billmate_partpayment_icon'] : '';
        $billmate_basic_icon = isset($country_data['billmate_basic_icon']) ? $country_data['billmate_basic_icon'] : '';

        $billmate_country                     = apply_filters( 'billmate_country', $billmate_country );
        $billmate_language                    = apply_filters( 'billmate_language', $billmate_language );
        $billmate_currency                    = apply_filters( 'billmate_currency', $billmate_currency );
        $billmate_partpayment_info            = apply_filters( 'billmate_partpayment_info', $billmate_partpayment_info );
        $icon                                 = apply_filters( 'billmate_partpayment_icon', $billmate_partpayment_icon );
        $icon_basic                           = apply_filters( 'billmate_basic_icon', $billmate_basic_icon );

        if ($enabled != "yes" OR $eid == '') {
            return;
        }

        if (!in_array(get_option('woocommerce_currency'), array('SEK')) OR get_woocommerce_currency() != 'SEK') {
            return false;
        }

		//global $woocommerce, $product, $billmate_partpayment_shortcode_currency, $billmate_partpayment_shortcode_price, $billmate_partpayment_shortcode_img, $billmate_partpayment_shortcode_info_link;
		global $woocommerce, $product, $billmate_partpayment_shortcode_currency, $billmate_partpayment_shortcode_price, $billmate_shortcode_img, $billmate_partpayment_country,$billmate_partpayment_eid;

		$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses',false);
		$billmate_partpayment_eid = $eid;

	 	// Only execute this if the feature is activated in the gateway settings
		if ( $show_monthly_cost == 'yes' && is_array($pclasses) ) {

			// Test mode or Live mode
			if ( $testmode == 'yes' ):
				// Disable SSL if in testmode
				$billmate_ssl = 'false';
				$billmate_mode = 'test';
			else :
				// Set SSL if used in webshop
				if (is_ssl()) {
					$billmate_ssl = 'true';
				} else {
					$billmate_ssl = 'false';
				}
				$billmate_mode = 'live';
			endif;

			$pcURI = BILLMATE_DIR . 'srv/billmatepclasses.json';
			$pclasses_not_available = true;
			if($pclasses) $pclasses_not_available = false;


			// apply_filters to product price so we can filter this if needed
			$billmate_product_total = $product->get_price();
			$sum = apply_filters( 'billmate_product_total', $billmate_product_total ); // Product price.
			$flag = BillmateFlags::PRODUCT_PAGE; //or BillmateFlags::PRODUCT_PAGE, if you want to do it for one item.
			$pclass =  BillmateCalc::getCheapestPClass($sum, $flag, $pclasses);


			//Did we get a PClass? (it is false if we didn't)
			if($pclass) {
	    		//Here we reuse the same values as above:
   				$value = BillmateCalc::calc_monthly_cost(
   		    	$sum,
   		    	$pclass,
   		    	$flag
   				);

	    		/* $value is now a rounded monthly cost amount to be displayed to the customer. */
                if ( $lower_threshold_monthly_cost < $sum && $upper_threshold_monthly_cost > $sum ) {

                    // Asign values to variables used for shortcodes.
                    $billmate_partpayment_shortcode_currency = $billmate_currency;
                    $billmate_partpayment_shortcode_price = $value;
                    $billmate_shortcode_img = $icon_basic;
                    $billmate_partpayment_country = $billmate_country;

                    echo '<div class="billmate-product-monthly-cost">' . do_shortcode( $show_monthly_cost_info );
		    		echo '</div>';

		    	}

			} // End pclass check

		} // End show_monthly_cost check

	}


	/**
	 * Calc monthly cost on Shop page and print it out
	 **/

 	function print_product_monthly_cost_shop() {

 		if ( $this->enabled!="yes" ) return;
        if (!in_array(get_option('woocommerce_currency'), array('SEK')) OR get_woocommerce_currency() != 'SEK') {
            return false;
        }
 		//global $woocommerce, $product, $billmate_partpayment_shortcode_currency, $billmate_partpayment_shortcode_price, $billmate_partpayment_shortcode_img, $billmate_partpayment_shortcode_info_link;
 		global $woocommerce, $product, $billmate_partpayment_shortcode_currency, $billmate_partpayment_shortcode_price, $billmate_shortcode_img, $billmate_partpayment_country;

	 	$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses',false);

	 	// Only execute this if the feature is activated in the gateway settings
		if ( $this->show_monthly_cost_shop == 'yes' && is_array($pclasses) ) {

			// Test mode or Live mode
			if ( $this->testmode == 'yes' ):
				// Disable SSL if in testmode
				$billmate_ssl = 'false';
				$billmate_mode = 'test';
			else :
				// Set SSL if used in webshop
				if (is_ssl()) {
					$billmate_ssl = 'true';
				} else {
					$billmate_ssl = 'false';
				}
				$billmate_mode = 'live';
			endif;
	   		if( empty( $this->eid) ){
	   		    return false;
	   		}

			$eid = (int)$this->eid;
			$secret = $this->secret;
			$country = $this->billmate_country;
			$language = $this->billmate_language;
			$currency = $this->billmate_currency;


			$k = new BillMate( $eid, $secret, true, false, $this->testmode == 'yes', false, $this->getRequestMeta() );

			$pclasses_not_available = true;
			if($pclasses) $pclasses_not_available = false;


			// apply_filters to product price so we can filter this if needed
			$billmate_product_total = $product->get_price();
			$sum = apply_filters( 'billmate_product_total', $billmate_product_total ); // Product price.
			$flag = BillmateFlags::PRODUCT_PAGE; //or BillmateFlags::PRODUCT_PAGE, if you want to do it for one item.
			$pclass = BillmateCalc::getCheapestPClass($sum, $flag, $pclasses);


			//Did we get a PClass? (it is false if we didn't)
			if($pclass) {
	    		//Here we reuse the same values as above:
   				$value = BillmateCalc::calc_monthly_cost(
   		    	$sum,
   		    	$pclass,
   		    	$flag
   				);


	    		// Asign values to variables used for shortcodes.
	    		$billmate_partpayment_shortcode_currency = $this->billmate_currency;
	    		$billmate_partpayment_shortcode_price = $value;
	    		$billmate_shortcode_img = $this->icon_basic;
	    		$billmate_partpayment_country = $this->billmate_country;
	    		//$billmate_partpayment_shortcode_info_link = $this->billmate_partpayment_info;


	    		//$billmate_partpayment_product_monthly_cost_message = sprintf(__('<img src="%s" /> <br/><a href="%s" target="_blank">Part pay from %s %s/month</a>', 'billmate'), $this->icon, $this->billmate_partpayment_info, $value, $this->billmate_currency );

	    		// Monthly cost threshold check. This is done after apply_filters to product price ($sum).
		    	if ( $this->lower_threshold_monthly_cost < $sum && $this->upper_threshold_monthly_cost > $sum ) {

		    		echo '<div class="billmate-product-monthly-cost-shop-page">' . do_shortcode( $this->show_monthly_cost_shop_info );

		    		// Show billmate_warning_banner if NL
					if ( $this->shop_country == 'NL' ) {
						echo '<img src="' . $this->billmate_wb_img_product_list . '" class="billmate-wb" style="max-width: 100%;"/>';
					}

		    		echo '</div>';

	    		}

			} // End pclass check

		} // End show_monthly_cost_shop check
	}


	/**
	 * Javascript for Account info/terms popup on checkout page
	 **/
	function billmate_partpayment_terms_js() {

		if ( is_checkout() && $this->enabled=="yes" ) {
			?>
			<script type="text/javascript">
				var billmate_eid = "<?php echo $this->eid; ?>";
				var billmate_partpayment_linktext = "<?php echo $this->get_account_terms_link_text($this->billmate_country); ?>";
				var billmate_partpayment_country = "<?php echo $this->get_terms_country(); ?>";
				//addBillmatePartPaymentEvent(function(){InitBillmatePartPaymentElements('billmate_partpayment', billmate_eid, billmate_partpayment_country, billmate_partpayment_linktext, 0); });
			</script>
			<?php
		}
	}



	/**
	* get_terms_country function.
 	* Helperfunction - Get Terms Country based on selected Billing Country in the Ceckout form
 	* Defaults to $this->billmate_country
 	* At the moment $this->billmate_country is allways returned. This will change in the next update.
 	**/

	function get_terms_country() {
		global $woocommerce;

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

        if($country != "" AND in_array($country, array('SE', 'NO', 'DK', 'DE', 'FI', 'NL'))) {
			return strtolower($country);
		} else {

			return strtolower($this->billmate_country);

		}
	} // End function get_terms_country()


	/**
	 * get_account_terms_link_text function.
	 * Helperfunction - Get Terms link text based on selected Billing Country in the Ceckout form
	 * Defaults to $this->billmate_country
	 * At the moment $this->billmate_country is allways returned. This will change in the next update.
	 **/

	static function get_account_terms_link_text($country) {

		switch ( $country )
		{
		case 'SE':
			$term_link = 'Villkor delbetalning';
			break;
		case 'NO':
			$term_link = 'Les mer';
			break;
		case 'DK':
			$term_link = 'L&aelig;s mere';
			break;
		case 'DE':
			$term_link = 'Lesen Sie mehr!';
			break;
		case 'FI':
			$term_link = 'Lue lis&auml;&auml;';
			break;
		case 'NL':
			$term_link = 'Lees meer!';
			break;
		default:
			$term_link = __('Read more', 'billmate');
		}

		return $term_link;
	} // end function get_account_terms_link_text()


	// Get Monthly cost prio - product page
	function get_monthly_cost_prio() {
		return $this->show_monthly_cost_prio;
	}

	// Get Monthly cost prio - shop base page (and archives)
	function get_monthly_cost_shop_prio() {
		return $this->show_monthly_cost_shop_prio;
	}



    private function woocommerce_clean($var = "") {
        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
            return wc_clean($var);
        }
        return woocommerce_clean($var);
    }


} // End class WC_Gateway_Billmate_Partpayment



/**
 * Class
 * @class 		WC_Gateway_Billmate_Partpayment_Extra
 * @since		1.5.4 (WC 2.0)
 *
 **/

class WC_Gateway_Billmate_Partpayment_Extra {

	public function __construct() {

		$data = new WC_Gateway_Billmate_Partpayment;
		$this->show_monthly_cost_shop_prio = $data->get_monthly_cost_shop_prio();
		$this->show_monthly_cost_prio = $data->get_monthly_cost_prio();

		// Actions
		add_action('woocommerce_after_shop_loop_item', array(&$this, 'print_product_monthly_cost_shop'), $this->show_monthly_cost_shop_prio);
	}

	function print_product_monthly_cost_shop() {
		$data = new WC_Gateway_Billmate_Partpayment;
		$data->print_product_monthly_cost_shop();
	}
} // End class WC_Gateway_Billmate_Partpayment_Extra

$WC_Gateway_Billmate_Partpayment_extra = new WC_Gateway_Billmate_Partpayment_Extra;
