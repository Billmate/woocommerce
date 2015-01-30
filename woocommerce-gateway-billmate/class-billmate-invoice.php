<?php
load_plugin_textdomain('billmate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

class WC_Gateway_Billmate_Invoice extends WC_Gateway_Billmate {

	/**
     * Class for Billmate Invoice payment.
     *
     */

	public function __construct() {
		global $woocommerce;

		parent::__construct();

		$this->id			= 'billmate';
		$this->method_title = __('Billmate Invoice', 'billmate');
		$this->has_fields 	= true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled				= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title 				= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description  		= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->eid					= ( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->secret				= ( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->lower_threshold		= ( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold		= ( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->invoice_fee_id		= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
		$this->testmode				= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms		= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->allowed_countries		= ( isset( $this->settings['billmateinvoice_allowed_countries'] ) ) ? $this->settings['billmateinvoice_allowed_countries'] : array();

		//if ( $this->handlingfee == "") $this->handlingfee = 0;
		//if ( $this->handlingfee_tax == "") $this->handlingfee_tax = 0;
		if ( $this->invoice_fee_id == "") $this->invoice_fee_id = 0;


		if ( $this->invoice_fee_id > 0 ) :

			// Version check - 1.6.6 or 2.0
			if ( function_exists( 'get_product' ) ) {
				$product = get_product($this->invoice_fee_id);
			} else {
				$product = new WC_Product( $this->invoice_fee_id );
			}

      if ((is_object($product) && $product->exists())) :

				// We manually calculate the tax percentage here
				@$this->invoice_fee_tax_percentage = number_format( (( $product->get_price() / $product->get_price_excluding_tax() )-1)*100, 2, '.', '');

				// apply_filters to invoice fee price so we can filter this if needed
				$billmate_invoice_fee_price_including_tax = $product->get_price();
				$this->invoice_fee_price = apply_filters( 'billmate_invoice_fee_price_including_tax', $billmate_invoice_fee_price_including_tax );
			else :

				$this->invoice_fee_price = 0;

			endif;

		else :

		$this->invoice_fee_price = 0;

		endif;

		// Country and language
		switch ( $this->shop_country )
		{
		case 'DK':
			$billmate_country = 'DK';
			$billmate_language = 'DA';
			$billmate_currency = 'DKK';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor_dk.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_dk.png");
			$billmate_invoice_icon =  plugins_url( '/images/bm_faktura_l.png', __FILE__ );
			break;
		case 'DE' :
			$billmate_country = 'DE';
			$billmate_language = 'DE';
			$billmate_currency = 'EUR';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor_de.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_de.png");
			$billmate_invoice_icon = plugins_url( '/images/bm_faktura_l.png', __FILE__ );;
			break;
		case 'NL' :
			$billmate_country = 'NL';
			$billmate_language = 'NL';
			$billmate_currency = 'EUR';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor_nl.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_nl.png");
			break;
		case 'NO' :
			$billmate_country = 'NO';
			$billmate_language = 'NB';
			$billmate_currency = 'NOK';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor_no.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$billmate_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/billmate_invoice_no.png");
			break;
		case 'FI' :
			$billmate_country = 'FI';
			$billmate_language = 'FI';
			$billmate_currency = 'EUR';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor_fi.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			break;
		case 'SE' :
			$billmate_country = 'SE';
			$billmate_language = 'SV';
			$billmate_currency = 'SEK';
			$billmate_invoice_terms = 'https://online.billmate.com/villkor.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
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

	}




	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
		$available = array(
			'SE' =>__( 'Sweden','woocommerce'),
			'FI' =>__('Finland', 'woocommerce'),
			'DK' =>__('Danmark', 'woocommerce'),
			'NO' =>__( 'Norway' ,'woocommerce')
		);

	   	$this->form_fields = apply_filters('billmate_invoice_form_fields', array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'billmate' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Billmate Invoice', 'billmate' ),
							'default' => 'yes'
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
							'description' => __( 'Avaktivera Billmate Faktura om varukorgen är mindre än den angivna summan. Lämna tomt för att avaktivera denna funktion.' ),
							'default' => ''
						),
			'upper_threshold' => array(
							'title' => __( 'Upper threshold', 'billmate' ),
							'type' => 'text',
							'description' => __( 'Avaktivera Billmate Faktura om varukorgen är mindre än den angivna summan. Lämna tomt för att avaktivera denna funktion.', 'billmate' ),
							'default' => ''
						),
			'invoice_fee_id' => array(
								'title' => __( 'Product ID for Invoice Fee', 'billmate' ),
								'type' => 'text',
								'description' => __( 'Create a hidden (simple) product that acts as the invoice fee. Enter the ID number in this textfield. Leave blank to disable. ', 'billmate' ),
								'default' => ''
							),
			'billmateinvoice_allowed_countries' => array(
				'title' 		=> __( 'Allowed Countries', 'woocommerce' ),
				'type' 			=> 'multiselect',
				'description' 	=> __( 'Billmate Invoice activated for customers in these countries', 'billmate' ),
				'class'			=> 'chosen_select',
				'css' 			=> 'min-width:350px;',
				'options'		=> $available
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
    	<h3><?php _e('Billmate Invoice', 'billmate'); ?></h3>

	    	<p><?php _e('With Billmate your customers can pay by invoice. Billmate works by adding extra personal information fields and then sending the details to Billmate for verification.','billmate'); ?></p>


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

			if(is_array($this->allowed_countries) && !in_array($woocommerce->customer->get_country() , $this->allowed_countries)){
				return false;
			}

			// Cart totals check - Lower threshold
			if ( $this->lower_threshold !== '' ) {
				if ( ( $woocommerce->cart->total - $this->invoice_fee_price ) < $this->lower_threshold ) return false;
			}

			// Cart totals check - Upper threshold
			if ( $this->upper_threshold !== '' ) {
				if ( ( $woocommerce->cart->total - $this->invoice_fee_price ) > $this->upper_threshold ) return false;
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
		<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
		<?php if ($this->invoice_fee_price>0) : ?><p><?php printf(__('An invoice fee of %1$s %2$s will be added to your order.', 'billmate'), $this->invoice_fee_price, $this->billmate_currency ); ?></p><?php endif; ?>

		<fieldset>
			<p class="form-row form-row-first">
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
					<label for="billmate_invo_pno"><?php echo __("Personal / Corporate ", 'billmate') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="billmate_invo_pno" id="billmate_invo_pno" />
				<?php endif; ?>
			</p>

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

			<p><a id="billmate_invoice" href="javascript://"><?php echo $this->get_invoice_terms_link_text($this->billmate_country); ?></a></p>
 <script type="text/javascript">
 jQuery(document).ready(function(){
	window.$ = $ = jQuery;
	$.getScript("https://efinance.se/billmate/base.js", function(){
			$("#billmate_invoice").Terms("villkor",{invoicefee: billmate_invoice_fee_price}, "#billmate_invoice");
	});

});

</script>
<?php
$datatemp = array('email'=>' ');
if(!empty($_POST['post_data'])){
parse_str($_POST['post_data'], $datatemp);
}
?>
		<div class="clear"></div>
			<p class="form-row">
				<input type="checkbox" class="input-checkbox" checked="checked" value="yes" name="valid_email_it_is_invoice" id="valid_email_it_is_invoice" style="float:left;margin-top:6px" />
				<label for="valid_email_it_is_invoice" ><?php echo sprintf(__('My e-mail address %s is correct and may be used for billing', 'billmate'), $datatemp['billing_email']) ?></label>
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





	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_order( $order_id );

		if(empty($_POST['valid_email_it_is_invoice'])){
            wc_bm_errors( sprintf( __('Vänligen bekräfta att e-postadressen "%s" är korrekt. Denna kommer att användas för fakturering.', 'billmate'), $order->billing_email ));
            return;
		}

		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$billmate_pno_day 			= isset($_POST['billmate_invo_date_of_birth_day']) ? woocommerce_clean($_POST['billmate_invo_date_of_birth_day']) : '';
			$billmate_pno_month 			= isset($_POST['billmate_invo_date_of_birth_month']) ? woocommerce_clean($_POST['billmate_invo_date_of_birth_month']) : '';
			$billmate_pno_year 			= isset($_POST['billmate_invo_date_of_birth_year']) ? woocommerce_clean($_POST['billmate_invo_date_of_birth_year']) : '';
			$billmate_pno 				= $billmate_pno_day . $billmate_pno_month . $billmate_pno_year;
		else :
			$billmate_pno 				= isset($_POST['billmate_invo_pno']) ? woocommerce_clean($_POST['billmate_invo_pno']) : '';
		endif;

		$billmate_gender 					= isset($_POST['billmate_invo_gender']) ? woocommerce_clean($_POST['billmate_invo_gender']) : '';
		$billmate_de_consent_terms		= isset($_POST['billmate_invo_de_consent_terms']) ? woocommerce_clean($_POST['billmate_invo_de_consent_terms']) : '';


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

		$eid = (int)$this->settings['eid'];
		$secret = $this->settings['secret'];
		$country = $this->billmate_country;
		$language = $this->billmate_language;
		$currency = $this->billmate_currency;

		$billmate_pclass_file = BILLMATE_DIR . 'srv/billmatepclasses.json';

		$k = new BillMate($eid,$secret,true,false, $this->testmode == 'yes');
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
				$item_tax_percentage = number_format( ( $order->get_item_tax($item, false) / $order->get_item_total( $item, false, false ) )*100, 2, '.', '');
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


		// Invoice/handling fee

		// Get the invoice fee product if invoice fee is used
		if ( $this->invoice_fee_price > 0 ) {

			// We have already checked that the product exists in billmate_invoice_init()
			// Version check - 1.6.6 or 2.0
			if ( function_exists( 'get_product' ) ) {
				$product = get_product($this->invoice_fee_id);
			} else {
				$product = new WC_Product( $this->invoice_fee_id );
			}


			if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {

				$goods_list[] = array(
					'qty'   => (int)1,
					'goods' => array(
						'artno'    => "",
						'title'    => __('Invoice Fee', 'billmate'),
						'price'    => round($this->invoice_fee_price*100,0),
						'vat'      => $this->invoice_fee_tax_percentage,
						'discount' => (float)0,
						'flags'    => BillmateFlags::INC_VAT + BillmateFlags::IS_HANDLING,
					)
				);

			    // Add the invoice fee to the order
			    // Get all order items and unserialize the array
			    $originalarray = unserialize($order->order_custom_fields['_order_items'][0]);


			    // TODO: check that Invoice fee can't be added multiple times to order?
			    $addfee[] = array (
   					'id' => $this->invoice_fee_id,
   					'variation_id' => '',
   					'name' => $product->get_title(),
   					'qty' => '1',
   					'item_meta' =>
    					array (
    					),
    				'line_subtotal' => $product->get_price_excluding_tax(),
    				'line_subtotal_tax' => $product->get_price()-$product->get_price_excluding_tax(),
    				'line_total' => $product->get_price_excluding_tax(),
    				'line_tax' => $product->get_price()-$product->get_price_excluding_tax(),
    				'tax_class' => $product->get_tax_class(),
    			);

    			// Merge the invoice fee product to order items
    			$newarray = array_merge($originalarray, $addfee);

    			// Update order items with the added invoice fee product
    			update_post_meta( $order->id, '_order_items', $newarray );

    			// Update _order_total
    			$old_order_total = $order->order_custom_fields['_order_total'][0];
    			$new_order_total = $old_order_total+$product->get_price();
    			update_post_meta( $order->id, '_order_total', $new_order_total );

    			// Update _order_tax
    			$invoice_fee_tax = $product->get_price()-$product->get_price_excluding_tax();
    			$old_order_tax = $order->order_custom_fields['_order_tax'][0];
    			$new_order_tax = $old_order_tax+$invoice_fee_tax;
    			update_post_meta( $order->id, '_order_tax', $new_order_tax );

    		} else {

				$goods_list[] = array(
					'qty'   => (int)1,
					'goods' => array(
						'artno'    => "",
						'title'    => __('Invoice Fee', 'billmate'),
						'price'    => round($this->invoice_fee_price*100,0),
						'vat'      => $this->invoice_fee_tax_percentage,
						'discount' => (float)0,
						'flags'    => BillmateFlags::INC_VAT + BillmateFlags::IS_HANDLING,
					)
				);

    		} // End version check

		} // End invoice_fee_price > 0


        try{
			$addr = $k->GetAddress($billmate_pno);
		}catch( Exception $ex ){
			wc_bm_errors( utf8_encode($ex->getMessage()) );
            return;
		}

        if( !is_array( $addr ) ) {
		    wc_bm_errors( __('Unable to find address.'.$addr, 'billmate') );
            return;
        }
		for($a=0; $a< count($addr[0]); $a++){
			$addr[0][$a] = utf8_encode($addr[0][$a]);
		}
        $fullname = $order->billing_last_name.' '.$order->billing_first_name;
        $firstArr = explode(' ', $order->billing_first_name);
        $lastArr  = explode(' ', $order->billing_last_name);

        $usership = $order->billing_first_name.' '.$order->billing_last_name.' '.$order->billing_company;
        $userbill = $order->shipping_first_name.' '.$order->shipping_last_name.' '.$order->shipping_company;

		if( strlen( $addr[0][0] )) {
			$name = $addr[0][0];
			$lastname = $addr[0][1];
			$company = '';
			$apiName =  $addr[0][0].' '.$addr[0][1];
			$displayname = $addr[0][0].' '.$addr[0][1];
		} else {
			$name = $order->billing_first_name;
			$lastname=$order->billing_last_name;
			$company = $addr[0][1];
			$apiName =  $name.' '.$lastname.' '.$addr[0][1];
			$displayname = $order->billing_first_name.' '.$order->billing_last_name.'<br/>'.$addr[0][1];
		}

		$shippingAndBilling = !isEqual( $usership, $userbill ) ||
		    !isEqual($addr[0][2], $billmate_billing_address ) ||
		    !isEqual($addr[0][3], $order->shipping_postcode) ||
		    !isEqual($addr[0][4], $order->shipping_city) ||
		    !isEqual(BillmateCountry::getCode($addr[0][5]), $order->shipping_country);

        $addressNotMatched =  !isEqual($usership, $apiName) ||
		    !isEqual($order->billing_address_1, $order->shipping_address_1 ) ||
		    !isEqual($order->billing_postcode, $order->shipping_postcode) ||
		    !isEqual($order->billing_city, $order->shipping_city) ||
		    !isEqual($order->billing_country, $order->shipping_country) ;

		$shippingAndBilling = $order->get_shipping_method() == '' ? false : $shippingAndBilling;

		global $woocommerce;

		$importedCountry = '';
		if(!(BillmateCountry::getCode($addr[0][5]) == 'se' && get_locale() == 'sv_SE' )){
			$importedCountry = $woocommerce->countries->countries[BillmateCountry::getCode($addr[0][5])];
		}

		if( $addressNotMatched || $shippingAndBilling ){
		    if( empty($_POST['geturl'] ) ){
			    $html = $displayname.'<br>'.$addr[0][2].'<br>'.$addr[0][3].' '.$addr[0][4].'<br/>'.$importedCountry.'<div style="margin-top:1em"><input type="button" value="'.__('Yes, make purchase with this address','billmate').'" onclick="ajax_load(this);modalWin.HideModalPopUp(); " class="billmate_button"/></div><a onclick="noPressButton()" class="linktag">'.__('No, I want to specify a different number or change payment method','billmate').'</a>';
			    $html.= '<span id="hidden_data"><input type="hidden" id="_first_name" value="'.$name.'" />';
			    $html.= '<input type="hidden" id="_last_name" value="'.$lastname.'" />';
			    $html.= '<input type="hidden" id="_company" value="'.$company.'" />';
			    $html.= '<input type="hidden" id="_address_1" value="'.$addr[0][2].'" />';
			    $html.= '<input type="hidden" id="_postcode" value="'.$addr[0][3].'" />';
			    $html.= '<input type="hidden" id="_city" value="'.$addr[0][4].'" /></span>';

			    echo $code = '<script type="text/javascript">setTimeout(function(){modalWin.ShowMessage(\''.$html.'\',350,500,\''.__('Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:','billmate').'\');},1000);</script>';
				//wc_bm_errors($code);
			    die;
			}
		}

        $countryData = BillmateCountry::getSwedenData();
		$countries = $woocommerce->countries->get_allowed_countries();
		$countryname = (int)$order->billing_country != 'SE' ? utf8_decode ($countries[$order->billing_country]) : 209;

	    $bill_address = array(
		    'email'           => $order->billing_email,
		    'telno'           => '',
		    'cellno'          => $order->billing_phone,
		    'fname'           => utf8_decode ($order->billing_first_name),
		    'lname'           => utf8_decode ($order->billing_last_name),
		    'careof'          => utf8_decode ($order->billing_address_2),
		    'street'          => utf8_decode ($billmate_billing_address),
		    'zip'             => utf8_decode ($order->billing_postcode),
		    'city'            => utf8_decode ($order->billing_city),
		    'country'         => $countryname,
	    );

		// Shipping address
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
		    'country'         => $countryname,
	    );

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
			"pclass"=>-1,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>empty($order->user_id ) || $order->user_id<= 0 ? time(): $order->user_id ))
		);
		/** Shipment type? **/

		//Normal shipment is defaulted, delays the start of invoice expiration/due-date.
		// $k->setShipmentInfo('delay_adjust', BillmateFlags::EXPRESS_SHIPMENT);
		try {
    		$result = $k->AddInvoice($billmate_pno,$bill_address,$ship_address,$goods_list,$transaction);
    		if( !is_array($result)){
				throw new Exception($result);
			}

    		// Retreive response
    		$invno = $result[0];
    		switch($result[2]) {
            case BillmateFlags::ACCEPTED:
                $order->add_order_note( __('Billmate payment completed. Billmate Invoice number:', 'billmate') . $invno );

                // Payment complete
				$order->payment_complete();

				// Remove cart
				$woocommerce->cart->empty_cart();

				if(version_compare(WC_VERSION, '2.0.0', '<')){
					$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
				} else {
					$redirect = $this->get_return_url($order);
				}

				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> $redirect
				);

                break;
            case BillmateFlags::PENDING:
                $order->add_order_note( __('Order is PENDING APPROVAL by Billmate. Please visit Billmate Online for the latest status on this order. Billmate Invoice number: ', 'billmate') . $invno );

                // Payment complete
				$order->payment_complete();

				// Remove cart
				$woocommerce->cart->empty_cart();
				if(version_compare(WC_VERSION, '2.0.0', '<')){
					$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
				} else {
					$redirect = $this->get_return_url($order);
				}

				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> $redirect
				);

                break;
            case BillmateFlags::DENIED:
                //Order is denied, store it in a database.
				$order->add_order_note( __('Billmate payment denied.', 'billmate') );
				wc_bm_errors( __('Billmate payment denied.', 'billmate') );
                return;
                break;
            default:
            	//Unknown response, store it in a database.
				$order->add_order_note( __('Unknown response from Billmate.', 'billmate') );
				wc_bm_errors( __('Unknown response from Billmate.', 'billmate') );
                return;
                break;
        	}


			}

		catch(Exception $e) {
    		//The purchase was denied or something went wrong, print the message:

			echo '<ul class="woocommerce-error"><li>';
			echo sprintf(__('%s (Error code: %s)', 'billmate'), utf8_encode($e->getMessage()), $e->getCode() );
			echo '<script type="text/javascript">jQuery("#billmategeturl").remove();</script></li></ul>';
			die;
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

		// Chcek Billmate specific fields on Checkout
		//add_action('woocommerce_checkout_process', array(&$this, 'billmate_invoice_checkout_field_process'));
		add_action('woocommerce_cart_calculate_fees', array(&$this, 'billmate_invoice_checkout_field_process'));
	}

	/**
	 * Add the invoice fee to the cart if Billmate Invoice is selected payment method, if this is WC 2.0 and if invoice fee is used.
	 **/
    function add_invoice_fee_process() {
      global $woocommerce;

      // Only run this if Billmate Invoice is the choosen payment method and this is WC +2.0
      if (isset($_POST['payment_method']) && $_POST['payment_method'] == 'billmate' && version_compare( WOOCOMMERCE_VERSION, '2.0', '>=' )) {
        $invoice_fee = new WC_Gateway_Billmate_Invoice;
        $this->invoice_fee_id = $invoice_fee->get_billmate_invoice_fee_product();
        //if( empty( $this->invoice_fee_id  ) ) throw new Exception(__('Missing Invoice Fee') );
        $product = get_product($this->invoice_fee_id);

        if ( function_exists( 'get_product' ) ) {
          $product = get_product($this->invoice_fee_id);
        } else {
          $product = new WC_Product( $this->invoice_fee_id );
        }

        if ((is_object($product) && $product->exists())) :

          if ( !empty($this->invoice_fee_id) ) :

            // Is this a taxable product?
            if ( $product->is_taxable() ) {
  			 		  $product_tax = true;
            } else {
              $product_tax = false;
            }

            $woocommerce->cart->add_fee($product->get_title(),$product->get_price_excluding_tax(),$product_tax,$product->get_tax_class());

          endif;
        endif;
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
 		if (isset($_POST['payment_method']) && $_POST['payment_method'] == 'billmate') {

 			// SE, NO, DK & FI
	 		if ( $this->shop_country == 'SE' || $this->shop_country == 'NO' || $this->shop_country == 'DK' || $this->shop_country == 'FI' ){

    			// Check if set, if its not set add an error.
    			if (isset($_POST['billmate_invo_pno']) && !$_POST['billmate_invo_pno'])
    	    	 	wc_bm_errors( __('Ej giltigt organisations-/personnummer. Kontrollera numret.', 'billmate') );

			}
			// NL & DE
	 		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ){
	    		// Check if set, if its not set add an error.

	    		// Gender
	    		if (!isset($_POST['billmate_invo_gender']))
	        	 	wc_bm_errors( __('<strong>Gender</strong> is a required field', 'billmate') );

	         	// Personal / Corporate
				if (!$_POST['billmate_invo_date_of_birth_day'] || !$_POST['billmate_invo_date_of_birth_month'] || !$_POST['billmate_invo_date_of_birth_year'])
	         		wc_bm_errors( __('Ej giltigt organisations-/personnummer. Kontrollera numret.', 'billmate') );

	         	// Shipping and billing address must be the same
	         	$billmate_shiptobilling = ( isset( $_POST['shiptobilling'] ) ) ? $_POST['shiptobilling'] : '';

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
