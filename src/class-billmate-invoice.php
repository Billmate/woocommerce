<?php
load_plugin_textdomain('billmate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
require_once "commonfunctions.php";

class WC_Gateway_Billmate_Invoice extends WC_Gateway_Billmate {

    /**
     * Class for Billmate Invoice payment.
     *
     */

    public function __construct() {
        global $woocommerce;

        parent::__construct();

        $this->id			= 'billmate_invoice';
        $this->method_title = __('Billmate Invoice', 'billmate');
        $this->has_fields 	= true;

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->enabled				= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
        $this->title 				= ( isset( $this->settings['title'] )  && $this->settings['title'] != '') ? $this->settings['title'] : __('Billmate Invoice - Pay within 14-days','billmate');
        $this->description  		= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
        $this->eid					= get_option('billmate_common_eid');//( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
        $this->secret				= get_option('billmate_common_secret');//( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
        $this->logo 				= get_option('billmate_common_logo');

        $this->lower_threshold		= ( isset( $this->settings['lower_threshold'] ) AND $this->settings['lower_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['lower_threshold'])) : '';
        $this->upper_threshold		= ( isset( $this->settings['upper_threshold'] ) AND $this->settings['upper_threshold'] != '' ) ? floatval(str_replace(",",".",$this->settings['upper_threshold'])) : '';
        $this->invoice_fee_id		= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
        $this->testmode				= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
        $this->de_consent_terms		= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
        $this->invoice_fee          = ( isset( $this->settings['billmate_invoice_fee'] ) ) ? floatval(str_replace(",",".",$this->settings['billmate_invoice_fee'])) : 0;
        $this->invoice_fee_tax_class = (isset( $this->settings['billmate_invoice_fee_tax_class'] ) ) ? $this->settings['billmate_invoice_fee_tax_class'] : '';
        $this->allowed_countries 		= ( isset( $this->settings['billmateinvoice_allowed_countries'] ) && !empty($this->settings['billmateinvoice_allowed_countries'])) ? $this->settings['billmateinvoice_allowed_countries'] : array('SE');
        $this->order_status = (isset($this->settings['order_status'])) ? $this->settings['order_status'] : false;

        //if ( $this->handlingfee == "") $this->handlingfee = 0;
        //if ( $this->handlingfee_tax == "") $this->handlingfee_tax = 0;
        if ( $this->invoice_fee_id == "") $this->invoice_fee_id = 0;



        // Country and language
        switch ( $this->shop_country )
        {
            case 'DK':
                $billmate_country = 'DK';
                $billmate_language = 'DA';
                $billmate_currency = 'DKK';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor_dk.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                //$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_dk.png");
                $billmate_invoice_icon =  plugins_url( '/images/bm_faktura_l.png', __FILE__ );
                break;
            case 'DE' :
                $billmate_country = 'DE';
                $billmate_language = 'DE';
                $billmate_currency = 'EUR';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor_de.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                //$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_de.png");
                $billmate_invoice_icon = plugins_url( '/images/bm_faktura_l.png', __FILE__ );;
                break;
            case 'NL' :
                $billmate_country = 'NL';
                $billmate_language = 'NL';
                $billmate_currency = 'EUR';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor_nl.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                //$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_nl.png");
                break;
            case 'NO' :
                $billmate_country = 'NO';
                $billmate_language = 'NB';
                $billmate_currency = 'NOK';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor_no.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                //$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_no.png");
                break;
            case 'FI' :
                $billmate_country = 'FI';
                $billmate_language = 'FI';
                $billmate_currency = 'EUR';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor_fi.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                break;
            case 'SE' :
                $billmate_country = 'SE';
                $billmate_language = 'SV';
                $billmate_currency = 'SEK';
                $billmate_invoice_terms = 'https://online.billmate.com/villkor.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee;
                break;
            default:
                $billmate_country = '';
                $billmate_language = '';
                $billmate_currency = '';
                $billmate_invoice_terms = '';
        }

        $billmate_invoice_icon = plugins_url( '/images/bm_faktura_l.png', __FILE__ );;
        // Apply filters to Country and language
        $this->billmate_country 		= apply_filters( 'billmate_country', $billmate_country );
        $this->billmate_language 		= apply_filters( 'billmate_language', $billmate_language );
        $this->billmate_currency 		= apply_filters( 'billmate_currency', $billmate_currency );
        $this->billmate_invoice_terms   = apply_filters( 'billmate_invoice_terms', $billmate_invoice_terms );
        $this->icon 				    = apply_filters( 'billmate_invoice_icon', $billmate_invoice_icon );


        // Actions

        /* 1.6.6 */
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

        /* 2.0.0 */
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action('woocommerce_receipt_billmate', array(&$this, 'receipt_page'));

        add_action('wp_footer', array(&$this, 'billmate_invoice_terms_js'));

        add_action('admin_enqueue_scripts',array(&$this,'injectscripts'));


        // WooCommerce Subscription
        $this->subscription_active = false;
        if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
            $this->subscription_active = true;
        }
        add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id,array( $this, 'process_scheduled_payment' ), 10, 3 );
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



        $this->form_fields = apply_filters('billmate_invoice_form_fields', array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'billmate' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Billmate Invoice', 'billmate' ),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'billmate' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'billmate' ),
                'default' => __( 'Billmate Invoice - pay within 14 days', 'billmate' )
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
                'description' => __( 'Disable Billmate Invoice if Cart Total is lower than the specified value. Leave blank to disable this feature.','billmate' ),
                'default' => ''
            ),
            'upper_threshold' => array(
                'title' => __( 'Upper threshold', 'billmate' ),
                'type' => 'text',
                'description' => __( 'Disable Billmate Invoice if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'billmate' ),
                'default' => ''
            ),
            'billmate_invoice_fee' => array(
                'title' => __( 'Invoice fee', 'billmate'),
                'type' => 'text',
                'description' => __( 'Add an invoice fee cost without vat, leave empty to disable.','billmate'),
                'default' => ''
            ),
            'billmate_invoice_fee_tax_class' => array(
                'title' => __('Invoice fee tax class', 'billmate'),
                'type' => 'select',
                'description' => __('The tax class for Invoice fee.','billmate'),
                'default' => '',
                'options' => $classes_options
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
            )
        ) );
        if(count($available) > 1){
            $this->form_fields['billmateinvoice_allowed_countries'] = array(
                'title' 		=> __( 'Allowed Countries', 'billmate' ),
                'type' 			=> 'multiselect',
                'description' 	=> __( 'Billmate Invoice activated for customers in these countries.', 'billmate' ),
                'class'			=> 'chosen_select',
                'css' 			=> 'min-width:350px;',
                'options'		=> $available,
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
        <h3><?php _e('Billmate Invoice', 'billmate'); ?></h3>

        <p><?php _e('With Billmate your customers can pay by invoice. Billmate works by adding extra personal information fields and then sending the details to Billmate for verification.','billmate'); ?></p>
        <p>
            <a href="https://billmate.se/plugins/manual/Installationsmanual_Woocommerce_Billmate.pdf" target="_blank">Installationsmanual Billmate Modul ( Manual Svenska )</a><br />
            <a href="https://billmate.se/plugins/manual/Installation_Manual_Woocommerce_Billmate.pdf" target="_blank">Installation Manual Billmate ( Manual English )</a>
        </p>
        <?php if(isset($this->invoice_fee_id) && $this->invoice_fee_id != ''): ?>

            <i class="ui-icon ui-icon-info"></i><div><?php printf(__('You may inactivate or remove the invoice fee product with id %s','billmate'),$this->invoice_fee_id); ?></div>
        <?php endif ?>

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

            $supportedCurrencies = array('SEK', 'DKK', 'NOK', 'GBP', 'EUR', 'USD');
            if (in_array(get_option('woocommerce_currency'), $supportedCurrencies) == false OR in_array(get_woocommerce_currency(), $supportedCurrencies) == false) {
                return false;
            }

            // Required fields check

            if (!$this->eid || !$this->secret) return false;
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

            // Cart totals check - Lower threshold
            if ( $this->lower_threshold !== '' ) {
                if ( ( WC_Payment_Gateway::get_order_total() - $this->invoice_fee ) < $this->lower_threshold ) return false;
            }

            // Cart totals check - Upper threshold
            if ( $this->upper_threshold !== '' ) {
                if ( ( WC_Payment_Gateway::get_order_total() - $this->invoice_fee) > $this->upper_threshold ) return false;
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

        $billmate_description = strlen($this->description) ? $this->description : '';
        ?>

        <?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'billmate'); ?></p><?php endif; ?>
        <?php if ($billmate_description) : ?><p><?php echo $billmate_description; ?></p><?php endif; ?>

        <?php
        if(isset($_GET['pay_for_order']) && isset($_SESSION['address_verification']) && (isset($_POST['billmate_invo_pno']) && $_POST['billmate_invo_pno'] != "")) {
            echo esc_html($_SESSION['address_verification']);
            unset($_SESSION['address_verification']);
        }
        ?>

        <?php if ($this->invoice_fee>0): ?>

            <p>
                <?php
                $tax = new WC_Tax();
                $rate = $tax->get_rates($this->invoice_fee_tax_class);
                $rate = array_pop($rate);
                $rate = $rate['rate'];
                $taxAmount = ($rate/100) * $this->invoice_fee;

                $invoice_fee = $this->invoice_fee;
                $invoice_fee_withtax = $invoice_fee;
                if ($rate > 0) {
                    $invoice_fee_withtax = $invoice_fee * (1 + ($rate / 100));
                }

                $invoice_fee_withtax = BillmateOrder::maybeConvertCurrency($invoice_fee_withtax);

                printf(__('An invoice fee of %1$s will be added to your order.', 'billmate'), wc_price($invoice_fee_withtax));
                ?>
            </p>
        <?php endif; ?>

        <fieldset>
            <?php if (get_option('billmate_common_getaddress') != 'active') : ?>
                <p class="form-row" id="invoice_pno">
                    <?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>

                        <label for="billmate_pno"><?php echo __("Personal / Corporate ", 'billmate') ?> <span class="required">*</span></label>
                        <span class="dob">
                    <select class="dob_select dob_day" name="billmate_invo_date_of_birth_day" style="width:60px;">
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
                    <select class="dob_select dob_month" name="billmate_invo_date_of_birth_month" style="width:80px;">
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
                    <select class="dob_select dob_year" name="billmate_invo_date_of_birth_year" style="width:60px;">
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
                </span><!-- .dob -->

                    <?php else : ?>
                        <label for="billmate_invo_pno"><?php echo __("Social Security Number / Corporate Registration Number", 'billmate') ?> <span class="required">*</span></label>
                        <input type="text" class="input-text" name="billmate_invo_pno" id="billmate_invo_pno" value="<?php echo isset($_POST['billmate_invo_pno']) ? esc_html($_POST['billmate_invo_pno']) : '' ?>" />
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
                <p class="form-row form-row-last">
                    <label for="billmate_invo_gender"><?php echo __("Gender", 'billmate') ?> <span class="required">*</span></label>
                    <select id="billmate_invo_gender" name="billmate_invo_gender" class="woocommerce-select" style="width:120px;">
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
                <label><input type="checkbox" class="input-checkbox" value="yes" name="valid_email_it_is_invoice" id="valid_email_it_is_invoice" style="float:left;margin-top:6px" />
                    <?php echo esc_html(sprintf(__('My e-mail%s is correct och and may be used for billing. I confirm the ', 'billmate'), (strlen($datatemp['billing_email']) > 0) ? ', '.$datatemp['billing_email'].',' : ' ')); ?><a class="billmateCheckoutTermLink" href="https://billmate.se/billmate/?cmd=villkor" onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=650');return false;"><?php echo __('terms of invoice','billmate'); ?></a>, <a class="billmateCheckoutTermLink" href="https://www.billmate.se/integritetspolicy/" onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=650');return false;"><?php echo __('Privacy Policy','billmate'); ?></a> <?php echo __('and accept the liability.','billmate') ?></label>
            </p>
            <div class="clear"></div>

            <?php if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes' ) : ?>
                <p class="form-row">
                    <label for="billmate_invo_de_consent_terms"></label>
                    <input type="checkbox" class="input-checkbox" value="yes" name="billmate_invo_de_consent_terms" />
                    <?php echo sprintf(__('Mit der Übermittlung der für die Abwicklungdes Rechnungskaufes und einer Identitäts-und Bonitätsprüfung erforderlichen Daten an Billmate bin ich einverstanden. Meine <a href="%s" target="_blank">Einwilligung</a> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers.', 'billmate'), 'https://online.billmate.com/consent_de.yaws') ?>

                </p>
            <?php endif; ?>

        </fieldset>
        <?php
    }

    public function validate_fields()
    {

    }

    public function getAddress( )
    {
        // Collect the dob different depending on country
        if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
            $billmate_pno_day 			= isset($_POST['billmate_invo_date_of_birth_day']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_day']) : '';
            $billmate_pno_month 			= isset($_POST['billmate_invo_date_of_birth_month']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_month']) : '';
            $billmate_pno_year 			= isset($_POST['billmate_invo_date_of_birth_year']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_year']) : '';
            $billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
        } else {
            if (isset($_POST['pno']) && $_POST['pno'] != '' && get_option('billmate_common_getaddress') == 'active') {
                $billmate_pno = $this->woocommerce_clean($_POST['pno']);
            } else {
                $billmate_pno = isset($_POST['billmate_invo_pno']) ? $this->woocommerce_clean($_POST['billmate_invo_pno']) : '';
            }
        }

        if($billmate_pno == ''){
            return;
        }
        $billmate_gender 					= isset($_POST['billmate_invo_gender']) ? $this->woocommerce_clean($_POST['billmate_invo_gender']) : '';
        $billmate_de_consent_terms		= isset($_POST['billmate_invo_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_invo_de_consent_terms']) : '';
        // Split address into House number and House extension for NL & DE customers
        if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
            require_once('split-address.php');
            $billmate_billing_address				= isset($_POST['billing_address_1']) ? $this->woocommerce_clean($_POST['billing_address_1']) : '';
            $splitted_address 					= splitAddress($billmate_billing_address);
            $billmate_billing_address				= $splitted_address[0];
            $billmate_billing_house_number		= $splitted_address[1];
            $billmate_billing_house_extension		= $splitted_address[2];
            $billmate_shipping_address			= !empty($_POST['shipping_address_1']) ? $this->woocommerce_clean($_POST['shipping_address_1']) : $billmate_billing_address;
            $splitted_address 					= splitAddress($billmate_shipping_address);
            $billmate_shipping_address			= $splitted_address[0];
            $billmate_shipping_house_number		= $splitted_address[1];
            $billmate_shipping_house_extension	= $splitted_address[2];
        else :
            $billmate_billing_address				= isset($_POST['billing_address_1']) ? $this->woocommerce_clean($_POST['billing_address_1']) : '';
            $billmate_billing_house_number		= '';
            $billmate_billing_house_extension		= '';
            $billmate_shipping_address			= !empty($_POST['shipping_address_1']) ? $this->woocommerce_clean($_POST['shipping_address_1']) : $billmate_billing_address;
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
            wc_bm_errors('<span data-error-code="'.$addr['code'].'"></span>'.utf8_encode($addr['message']));
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

        $fullname = $this->woocommerce_clean($_POST['billing_last_name'].' '.$_POST['billing_first_name']);
        $firstArr = explode(' ', $this->woocommerce_clean($_POST['billing_last_name']));
        $lastArr  = explode(' ', $this->woocommerce_clean($_POST['billing_first_name']));

        $usership = $this->woocommerce_clean($_POST['billing_first_name'].' '.$_POST['billing_last_name'].' '.$_POST['billing_company']);
        $userbill = (isset($_POST['shipping_first_name']) && isset($_POST['shipping_last_name']) && isset($_POST['shipping_company'])) ? $this->woocommerce_clean($_POST['shipping_first_name'].' '.$_POST['shipping_last_name'].' '.$_POST['shipping_company']) : $usership;

        if( strlen( $addr['firstname'] )) {
            $name = $addr['firstname'];
            $lastname = $addr['lastname'];
            $company = '';
            $apiName =  $addr['firstname'].' '.$addr['lastname'];
            $usership = $this->woocommerce_clean($_POST['billing_first_name'].' '.$_POST['billing_last_name']);
            $displayname = $addr['firstname'].' '.$addr['lastname'];
        } else {
            $name = $this->woocommerce_clean($_POST['billing_first_name']);
            $lastname = $this->woocommerce_clean($_POST['billing_last_name']);
            $company  =  $addr['company'];
            $apiName =  $name.' '.$lastname.' '.$addr['company'];
            $usership = $this->woocommerce_clean($_POST['billing_first_name'].' '.$_POST['billing_last_name'].' '.$_POST['billing_company']);
            $displayname = $this->woocommerce_clean($_POST['billing_first_name'].' '.$_POST['billing_last_name']).'<br/>'.$addr['company'];
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
        if(isset($_POST['shipping_address_1']) && isset($_POST['shipping_postcode']) && isset($_POST['shipping_city']) && isset($_POST['shipping_country'])) {
            $shippingAndBilling = !isEqual($usership, $userbill) ||
                !isEqual($_POST['billing_address_1'], $_POST['shipping_address_1']) ||
                !isEqual($_POST['billing_postcode'], $_POST['shipping_postcode']) ||
                !isEqual($_POST['billing_city'], $_POST['shipping_city']) ||
                !isEqual($_POST['billing_country'], $_POST['shipping_country']);

            $shippingAndBilling = (isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1) ? $shippingAndBilling : false;
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


    public function getAddressPayment(&$order)
    {

        $billmateOrder = new BillmateOrder($order);

        // Collect the dob different depending on country
        if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
            $billmate_pno_day 			= isset($_POST['billmate_invo_date_of_birth_day']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_day']) : '';
            $billmate_pno_month 			= isset($_POST['billmate_invo_date_of_birth_month']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_month']) : '';
            $billmate_pno_year 			= isset($_POST['billmate_invo_date_of_birth_year']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_year']) : '';
            $billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
        } else {
            if (isset($_POST['pno']) && $_POST['pno'] != '' && get_option('billmate_common_getaddress') == 'active') {
                $billmate_pno = $this->woocommerce_clean($_POST['pno']);
            } else {
                $billmate_pno = isset($_POST['billmate_invo_pno']) ? $this->woocommerce_clean($_POST['billmate_invo_pno']) : '';
            }
        }
        if($billmate_pno == ''){
            return;
        }
        $billmate_gender 					= isset($_POST['billmate_invo_gender']) ? $this->woocommerce_clean($_POST['billmate_invo_gender']) : '';
        $billmate_de_consent_terms		= isset($_POST['billmate_invo_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_invo_de_consent_terms']) : '';
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
            $name = $this->woocommerce_clean($_POST['billing_first_name']);
            $lastname = $this->woocommerce_clean($_POST['billing_last_name']);
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

    function process_scheduled_payment( $amount_to_charge, $order ) {
        global $woocommerce;

        $k = new Billmate( $this->eid, $this->secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );

        $subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
        $subscription = end($subscriptions);

        if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
            $parent_order = $subscription->get_parent();
            $parent_id = $parent_order->get_id();
        } else {
            $parent_id = $subscription->order->id;
        }

        $parent_billmate_order = array();
        $parent_billmate_order_customer_pno = '';
        $parent_invoice_number = intval( get_post_meta( $parent_id, 'billmate_invoice_id', true ) );
        if ($parent_invoice_number > 0) {
            $parent_billmate_order = $k->getPaymentinfo( array( 'number' => $parent_invoice_number ) );
            if ( isset( $parent_billmate_order['Customer'] ) && isset( $parent_billmate_order['Customer']['pno'] ) ) {
                $parent_billmate_order_customer_pno = $parent_billmate_order['Customer']['pno'];
            }
        }

        $total = 0;
        $totalTax = 0;
        $prepareDiscount = array();

        $billmateOrder = new BillmateOrder($order);
        $billmateOrder->setAllowedCountries($woocommerce->countries->get_allowed_countries());

        $language = explode('_',get_locale());
        $orderValues = array();
        $orderValues['PaymentData'] = array(
            'method' => 1,
            'currency' => get_woocommerce_currency(),
            'language' => strtolower($language[0]),
            'country' => $this->billmate_country,
            'orderid' => preg_replace('/#/','',$order->get_order_number()),
            'logo' => (strlen($this->logo)> 0) ? $this->logo : ''

        );

        $orderValues['PaymentInfo'] = $billmateOrder->getPaymentInfoData();
        $orderValues['Customer']['nr'] = $billmateOrder->getCustomerNrData();
        $orderValues['Customer']['pno'] = $parent_billmate_order_customer_pno;
        $orderValues['Customer']['Billing'] = $billmateOrder->getCustomerBillingData();
        $orderValues['Customer']['Shipping'] = $billmateOrder->getCustomerShippingData();

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

        $handling = $billmateOrder->getFormattedInvoiceFee();
        if ( $handling['price'] > 0 ) {
            $orderValues['Cart']['Handling'] = array(
                'withouttax' => $handling['price'],
                'taxrate' => $handling['taxrate']
            );
            $total += $handling['price'];
            $totalTax += $handling['tax'];
            $this->maybe_add_handling_to_order($order);
        }

        $round = 0;

        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => $round,
            'withtax' => round($total) + round($totalTax,0) + $round
        );

        $result = $k->addPayment($orderValues);
        if ( isset( $result['code'] ) ) {
            wc_bm_errors(__($result['message'],'billmate'));
            $order->update_status('failed',sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
            $order->add_order_note(sprintf(__("Subscription Payment Failed: Invoice ID: None" , 'billmate'),$result['message']));
            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
            return;
        }else{
            if ( $result['status'] == 'Paid' || $result['status'] == 'Created' ) {
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

        $countries = $woocommerce->countries->get_allowed_countries();

        $billmateOrder = new BillmateOrder($order);
        $billmateOrder->setAllowedCountries($countries);

        if(empty($_POST['valid_email_it_is_invoice'])){
            wc_bm_errors( sprintf( __('Please confirm the email %s is correct. The email will be used for invoicing.', 'billmate'), $order->billing_email ));
            return;
        }

        // Collect the dob different depending on country
        if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
            $billmate_pno_day 			= isset($_POST['billmate_invo_date_of_birth_day']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_day']) : '';
            $billmate_pno_month 			= isset($_POST['billmate_invo_date_of_birth_month']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_month']) : '';
            $billmate_pno_year 			= isset($_POST['billmate_invo_date_of_birth_year']) ? $this->woocommerce_clean($_POST['billmate_invo_date_of_birth_year']) : '';
            $billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
        } else {
            if (isset($_POST['pno']) && $_POST['pno'] != '' && get_option('billmate_common_getaddress') == 'active') {
                $billmate_pno = $this->woocommerce_clean($_POST['pno']);
            } else {
                $billmate_pno = isset($_POST['billmate_invo_pno']) ? $this->woocommerce_clean($_POST['billmate_invo_pno']) : '';
            }
        }

        if($billmate_pno == ''){
            return;
        }
        $billmate_gender 					= isset($_POST['billmate_invo_gender']) ? $this->woocommerce_clean($_POST['billmate_invo_gender']) : '';
        $billmate_de_consent_terms		= isset($_POST['billmate_invo_de_consent_terms']) ? $this->woocommerce_clean($_POST['billmate_invo_de_consent_terms']) : '';


        $billmateOrder->setCustomerPno($billmate_pno);


        // Test mode or Live mode

        if ( $this->testmode == 'yes' ):
            // Disable SSL if in testmode
            $billmate_ssl = 'false';
            $billmate_mode = true;
        else :
            // Set SSL if used in webshop
            if (is_ssl()) {
                $billmate_ssl = 'true';
            } else {
                $billmate_ssl = 'false';
            }
            $billmate_mode = false;
        endif;

        $eid = (int)$this->eid;
        $secret = $this->secret;
        $country = $this->billmate_country;
        $language = $this->billmate_language;
        $currency = $this->billmate_currency;
        $lang = explode('_',get_locale());

        $billmate_pclass_file = BILLMATE_DIR . 'srv/billmatepclasses.json';
        if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',strtolower($lang[0]));

        $method = (isset($this->settings['method']) AND $this->settings['method'] == '2') ? '2' : '1';

        $k = new Billmate( $eid, $secret, true, $this->testmode == 'yes', false, $this->getRequestMeta() );
        $goods_list = array();
        $orderValues = array();
        $prepareDiscount = array();
        $orderid = ltrim($order->get_order_number(),'#');
        $orderValues['PaymentData'] = array(
            'method' => $method,
            'currency' => get_woocommerce_currency(),
            'language' => $lang[0],
            'country' => $country,
            'orderid' => $orderid,
            'logo' => ($this->logo && strlen($this->logo)> 0) ? $this->logo : ''
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

        // Invoice/handling fee

        // Get the invoice fee product if invoice fee is used
        if ( $this->invoice_fee > 0 ) {

            // We have already checked that the product exists in billmate_invoice_init()
            // Version check - 1.6.6 or 2.0
            if ( function_exists( 'get_product' ) ) {
                if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                    $product = wc_get_product($this->invoice_fee_id);
                } else {
                    $product = get_product($this->invoice_fee_id);
                }
            } else {
                $product = new WC_Product( $this->invoice_fee_id );
            }


            if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
                $tax = new WC_Tax();
                $rate = array_pop($tax->get_rates($this->invoice_fee_tax_class));
                $rate = $rate['rate'];

                // Add the invoice fee to the order
                // Get all order items and unserialize the array
                //$woocommerce->cart->add_fee(__('Invoice Fee',$this->invoice_fee,true,$this->invoice_fee_tax_class));
                $originalarray = unserialize($order->order_custom_fields['_order_items'][0]);


                // TODO: check that Invoice fee can't be added multiple times to order?
                $addfee[] = array (
                    'id' => $this->invoice_fee_id,
                    'variation_id' => '',
                    'name' => __('Invoice fee','billmate'),
                    'qty' => '1',
                    'item_meta' =>
                        array (
                        ),
                    'line_subtotal' => $this->invoice_fee,
                    'line_subtotal_tax' => ($this->invoice_fee * ($rate/100)),
                    'line_total' => $this->invoice_fee,
                    'line_tax' => ($this->invoice_fee * ($rate/100)),
                    'tax_class' => $product->get_tax_class(),
                );

                // Merge the invoice fee product to order items
                $newarray = array_merge($originalarray, $addfee);

                // Update order items with the added invoice fee product
                update_post_meta( $order->id, '_order_items', $newarray );

                // Update _order_total
                $old_order_total = $order->order_custom_fields['_order_total'][0];
                $new_order_total = $old_order_total+$this->invoice_fee;
                update_post_meta( $order->id, '_order_total', $new_order_total );

                // Update _order_tax
                $invoice_fee_tax = $product->get_price()-$product->get_price_excluding_tax();
                $old_order_tax = $order->order_custom_fields['_order_tax'][0];
                $new_order_tax = $old_order_tax+($this->invoice_fee*($rate/100));
                update_post_meta( $order->id, '_order_tax', $new_order_tax );

            } else {

                if (isset($_GET['pay_for_order'])) {
                    // Cart unavailable
                    $handling = $this->maybe_add_handling_to_order($order);
                } else {
                    // Cart available
                    $woocommerce->cart->add_fee(__('Invoice fee','billmate'),$this->invoice_fee,true,$this->invoice_fee_tax_class);
                }
            } // End version check

            $invoice_fee_data = BillmateOrder::getFormattedInvoiceFee();
            $orderValues['Cart']['Handling'] = array(
                'withouttax'    => $invoice_fee_data['price'],
                'taxrate'       => $invoice_fee_data['taxrate']
            );

            $total      += $invoice_fee_data['price'];
            $totalTax   += $invoice_fee_data['tax'];

        } // End invoice_fee_price > 0

        $round = (round(WC_Payment_Gateway::get_order_total() * 100)) - round($total + $totalTax,0);

        $orderValues['Cart']['Total'] = array(
            'withouttax' => round($total),
            'tax' => round($totalTax,0),
            'rounding' => round($round),
            'withtax' => round($total + $totalTax + $round)
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
                        // Unknown response
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


    /*
     * Return array with handling fee to use in API request
     * Add available handling fee to order if order do not have handling
     */
    private function maybe_add_handling_to_order( &$order ) {
        global $woocommerce;

        $handling = array(
            'price' => 0,
            'tax' => 0,
            'taxrate' => 0,
            'total' => 0
        );

        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
            $orderId = $order->get_id();
        } else {
            $orderId = $order->id;
        }

        $invoice_fee = $this->invoice_fee;
        $invoice_fee = BillmateOrder::maybeConvertCurrency($invoice_fee);
        $invoice_fee_tax_class = $this->invoice_fee_tax_class;

        // Get the invoice fee product if invoice fee is used
        if ( $invoice_fee > 0 ) {

            if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
                $tax = new WC_Tax();
                $rate = array_pop($tax->get_rates($invoice_fee_tax_class));
                $rate = $rate['rate'];

                $handling['price']      = $invoice_fee;
                $handling['taxrate']    = round($rate);
                $handling['total']      = $invoice_fee;

                if ($rate > 0) {
                    $handling['tax']    = $invoice_fee * ($rate / 100);
                    $handling['total']  = $invoice_fee * (1 + ($rate / 100));
                }

                $originalarray = unserialize($order->order_custom_fields['_order_items'][0]);

                $addfee[] = array (
                    'id' => $invoice_fee_id,
                    'variation_id' => '',
                    'name' => __('Invoice fee','billmate'),
                    'qty' => '1',
                    'item_meta' =>
                        array (
                        ),
                    'line_subtotal' => $invoice_fee,
                    'line_subtotal_tax' => ($invoice_fee * ($rate/100)),
                    'line_total' => $invoice_fee,
                    'line_tax' => ($invoice_fee * ($rate/100)),
                    'tax_class' => $product->get_tax_class(),
                );

                // Merge the invoice fee product to order items
                $newarray = array_merge($originalarray, $addfee);

                // Update order items with the added invoice fee product
                update_post_meta( $order->id, '_order_items', $newarray );

                // Update _order_total
                $old_order_total = $order->order_custom_fields['_order_total'][0];
                $new_order_total = $old_order_total+$invoice_fee;
                update_post_meta( $order->id, '_order_total', $new_order_total );

                // Update _order_tax
                $invoice_fee_tax = $product->get_price()-$product->get_price_excluding_tax();
                $old_order_tax = $order->order_custom_fields['_order_tax'][0];
                $new_order_tax = $old_order_tax+($invoice_fee*($rate/100));
                update_post_meta( $order->id, '_order_tax', $new_order_tax );

            } else {

                $fees = $order->get_fees();
                $hasFee = false;
                foreach ($fees AS $fee) {
                    $_feeName = '';
                    if (version_compare(WC_VERSION, '3.0.0', '>=')) {
                        $_feeName =  $fee['name'];
                    } else {
                        $_feeName = $fee->get_name();
                    }

                    if ($_feeName == __('Invoice fee','billmate')) {
                        $hasFee = true;
                    }
                }

                $feeTaxclass = $invoice_fee_tax_class;
                $feeAmount = $invoice_fee;

                // Handling fee tax rates
                $tax = new WC_Tax();
                $rates = $tax->get_rates($feeTaxclass);
                $rate = $rates;
                $rate = array_pop($rate);
                $rate = $rate['rate'];

                $feeTax = 0;
                if ($rate > 0) {
                    $feeTax = ($feeAmount * (1 + ($rate/100))) - $feeAmount;
                }

                $feeTaxdata = array();
                foreach($rates AS $i => $_rate) {
                    $feeTaxdata[$i] = wc_format_decimal(0);
                    if ($_rate['rate'] > 0) {
                        $feeTaxdata[$i] = wc_format_decimal(($feeAmount * (1 + ($_rate['rate']/100))) - $feeAmount);
                    }
                }

                $fee            = new stdClass();
                $fee->name      = __('Invoice fee','billmate');
                $fee->tax_class = $feeTaxclass;
                $fee->taxable   = ($feeTax > 0) ? true : false;
                $fee->amount    = wc_format_decimal($feeAmount);
                $fee->tax       = wc_format_decimal($feeTax);
                $fee->tax_data  = $feeTaxdata;

                $handling['taxrate'] = round($rate);
                $handling['price'] = $invoice_fee;
                $handling['total'] = $invoice_fee;

                if ($rate > 0) {
                    $handling['tax'] = $invoice_fee * ($rate / 100);
                    $handling['total'] = $invoice_fee * (1 + ($rate / 100));
                }

                if (version_compare(WC_VERSION, '3.0.0', '>=')) {
                    if ($hasFee == false) {
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
                        $order->calculate_totals();
                    }

                } else {
                    if ($hasFee == false) {
                        $item_id = $order->add_fee( $fee );
                        $order->calculate_totals();
                    }
                }
            } // End version check
        } // End invoice_fee_price > 0

        return $handling;
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
                var billmate_invoice_fee_price = "<?php echo $this->invoice_fee; ?>";
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

    private function woocommerce_clean($var = "") {
        if(version_compare(WC_VERSION, '3.0.0', '>=')) {
            return wc_clean($var);
        }
        return woocommerce_clean($var);
    }

} // End class WC_Gateway_Billmate_Invoice


/**
 * Class WC_Gateway_Billmate_Invoice_Extra
 * Extra class for functions that needs to be executed outside the payment gateway class.
 * Since version 1.5.4 (WooCommerce version 2.0)
 **/

class WC_Gateway_Billmate_Invoice_Extra {

    public function __construct() {

        // Add Invoice fee via the new Fees API
        //add_action( 'woocommerce_checkout_process', array($this, 'add_invoice_fee_process') );
        add_action( 'woocommerce_cart_calculate_fees', array($this, 'add_invoice_fee_process') );

        // Check Billmate specific fields on Checkout
        if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.4.5', '>' ) ) {
            add_action( 'woocommerce_checkout_process', array( &$this, 'billmate_invoice_checkout_field_process' ) );
        } else {
            add_action( 'woocommerce_cart_calculate_fees', array( &$this, 'billmate_invoice_checkout_field_process' ) );
        }
    }

    /**
     * Add the invoice fee to the cart if Billmate Invoice is selected payment method, if this is WC 2.0 and if invoice fee is used.
     **/
    function add_invoice_fee_process() {
        global $woocommerce;

        // Only run this if Billmate Invoice is the choosen payment method and this is WC +2.0
        if (isset($_POST['payment_method']) && $_POST['payment_method'] == 'billmate_invoice' && version_compare( WOOCOMMERCE_VERSION, '2.0', '>=' )) {

            $billmateInvoice        = new WC_Gateway_Billmate_Invoice();
            $invoice_fee            = $billmateInvoice->invoice_fee;
            $invoice_fee_tax_class  = $billmateInvoice->invoice_fee_tax_class;

            $tax = new WC_Tax();
            $rate = $tax->get_rates($invoice_fee_tax_class);
            $rate = array_pop($rate);
            $rate = $rate['rate'];

            $invoice_fee = BillmateOrder::maybeConvertCurrency($invoice_fee);
            $woocommerce->cart->add_fee(__('Invoice fee','billmate'), $invoice_fee, true, $invoice_fee_tax_class);
        }
    } // End function add_invoice_fee_process



    /**
     * Process the gateway specific checkout form fields
     **/
    function billmate_invoice_checkout_field_process() {
        global $woocommerce;

        $data = new WC_Gateway_Billmate_Invoice;
        $this->shop_country = $data->get_billmate_shop_country();

        // Only run this if Billmate Invoice is the choosen payment method
        if (isset($_POST['payment_method']) && $_POST['payment_method'] == 'billmate_invoice') {

            // SE, NO, DK & FI
            if ( $this->shop_country == 'SE' || $this->shop_country == 'NO' || $this->shop_country == 'DK' || $this->shop_country == 'FI' ){

                $post_pno = '';
                if (isset($_POST['pno']) && $_POST['pno'] != '' && get_option('billmate_common_getaddress') == 'active') {
                    $post_pno = $_POST['pno'];
                } else {
                    $post_pno = isset($_POST['billmate_invo_pno']) ? $_POST['billmate_invo_pno'] : '';
                }
                if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                    $post_pno = wc_clean($post_pno);
                } else {
                    $post_pno = woocommerce_clean($post_pno);
                }

                if ($post_pno == '') {
                    // Missing pno, show error
                    wc_bm_errors( '<span data-error-code="9015"></span>'.__('Non Valid Person / Corporate number. Check the number.', 'billmate') );
                }
            }

            // NL & DE
            if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ){
                // Check if set, if its not set add an error.

                // Gender
                if (!isset($_POST['billmate_invo_gender']))
                    wc_bm_errors( __('<strong>Gender</strong> is a required field', 'billmate') );

                // Personal / Corporate
                if (!$_POST['billmate_invo_date_of_birth_day'] || !$_POST['billmate_invo_date_of_birth_month'] || !$_POST['billmate_invo_date_of_birth_year'])
                    wc_bm_errors( __('Non Valid Person / Corporate number. Check the number.', 'billmate') );

                // Shipping and billing address must be the same
                $billmate_shiptobilling = ( isset( $_POST['shiptobilling'] ) ) ? sanitize_text_field($_POST['shiptobilling']) : '';

                if ($billmate_shiptobilling !=1 && isset($_POST['shipping_first_name']) && $_POST['shipping_first_name'] !== $_POST['billing_first_name'])
                    wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate', 'billmate') );

                if ($billmate_shiptobilling !=1 && isset($_POST['shipping_last_name']) && $_POST['shipping_last_name'] !== $_POST['billing_last_name'])
                    wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate', 'billmate') );

                if ($billmate_shiptobilling !=1 && isset($_POST['shipping_address_1']) && $_POST['shipping_address_1'] !== $_POST['billing_address_1'])
                    wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate', 'billmate') );

                if ($billmate_shiptobilling !=1 && isset($_POST['shipping_postcode']) && $_POST['shipping_postcode'] !== $_POST['billing_postcode'])
                    wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate', 'billmate') );

                if ($billmate_shiptobilling !=1 && isset($_POST['shipping_city']) && $_POST['shipping_city'] !== $_POST['billing_city'])
                    wc_bm_errors( __('Shipping and billing address must be the same when paying via Billmate', 'billmate') );
            }

            // DE
            if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes'){
                // Check if set, if its not set add an error.
                if (!isset($_POST['billmate_invo_de_consent_terms']))
                    wc_bm_errors( __('You must accept the Billmate consent terms.', 'billmate') );
            }
        }
    } // End function billmate_invoice_checkout_field_process
}
$wc_billmate_invoice_extra = new WC_Gateway_Billmate_Invoice_Extra;
