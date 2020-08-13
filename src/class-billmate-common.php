<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-02
 * Time: 16:29
 */
require_once "commonfunctions.php";

class BillmateCommon {
	
	private $options;
	
	public function __construct() {
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
		add_action('wp_ajax_verify_credentials', array($this, 'verify_credentials'));
		add_action('wp_ajax_nopriv_getaddress', array($this, 'getaddress'));
		add_action('wp_ajax_getaddress', array($this, 'getaddress'));
		add_action('woocommerce_checkout_before_customer_details', array($this, 'get_address_fields'));
		add_action('woocommerce_order_status_completed', array($this, 'activate_invoice'));
		add_filter('woocommerce_payment_successful_result', array($this, 'clear_pno'));
		
	}
	
	public function activate_invoice($order_id) {
		if(get_option('billmate_common_activateonstatus') == 'active') {
			
			$orderNote = "";
			
			$paymentMethod = get_post_meta($order_id, '_payment_method');
			$method        = false;
			switch($paymentMethod[0]) {
				case 'billmate_partpayment':
					$method = new WC_Gateway_Billmate_Partpayment();
					break;
				case 'billmate_invoice':
					$method = new WC_Gateway_Billmate_Invoice();
					break;
				case 'billmate_bankpay':
					$method = new WC_Gateway_Billmate_Bankpay();
					break;
				case 'billmate_cardpay':
					$method = new WC_Gateway_Billmate_Cardpay();
					break;
				case 'billmate_checkout':
					$method = new WC_Gateway_Billmate_Checkout();
					break;
			}
			
			if($method !== false) {
				
				$billmate = new BillMate(get_option('billmate_common_eid'), get_option('billmate_common_secret'), true, $method->testmode == 'yes', false);
				$order    = new WC_Order($order_id);
				
				if($billmateInvoiceId = get_post_meta($order_id, 'billmate_invoice_id', true)) {
					
					$paymentInfo = $billmate->getPaymentinfo(array('number' => $billmateInvoiceId));
					if(is_array($paymentInfo) AND
					   isset($paymentInfo['PaymentData']) AND
					   is_array($paymentInfo['PaymentData']) AND
					   isset($paymentInfo['PaymentData']['status']) AND
					   $paymentInfo['PaymentData']['status'] == 'Created'
					) {
						$result            = $billmate->activatePayment(array('PaymentData' => array('number' => $billmateInvoiceId)));
						$result['message'] = isset($result['message']) ? utf8_encode($result['message']) : '';
						if(isset($result['code'])) {
							$orderNote = sprintf(__('Billmate: The order payment couldnt be activated, error code: %s error message: %s', 'billmate'), $result['code'], $result['message']);
						} else {
              $orderNote = __('Billmate: The order payment activated successfully', 'billmate');
              add_post_meta($order_id, 'order_has_been_activated', 1);
						}
					} elseif(isset($paymentInfo['code'])) {
						$paymentInfo['message'] = utf8_encode($paymentInfo['message']);
						$orderNote              = sprintf(__('Billmate: The order payment couldnt be activated, error code: %s error message: %s', 'billmate'), $paymentInfo['code'], $paymentInfo['message']);
					}
					
				} else {
					// billmate_invoice_id is missing
					$orderNote = 'The order payment could not be activated, please activate order in online.billmate.se';
				}
				
				if($orderNote != '') {
					$order->add_order_note($orderNote);
				}
			}
		}
	}
	public function clear_pno($result,$order_id = null)
	{
		if(isset($_SESSION['billmate_pno']))
			unset($_SESSION['billmate_pno']);
		return $result;
	}

    public function get_address_fields()
    {
        if(get_option('billmate_common_getaddress') == 'active'){
            ?>
            <div class="col12-set checkout-billmate-getaddress-wrapper">
                <div class="col-1">
                    <p class="form-row">
                        <label for="pno"><?php echo __('Social Security Number / Corporate Registration Number','billmate'); ?></label>
                    </p>
                    <div class="clear"></div>
                    <p class="form-row form-row-first">
                        <input type="text" autocomplete="off" name="pno" label="12345678-1235" class="form-row-wide input-text" value="<?php echo isset($_SESSION['billmate_pno']) ? $_SESSION['billmate_pno'] : ''; ?>"/>
                    </p>
                    <p class="form-row form-row-last">
                        <label></label>
                        <button id="getaddress" class="button getaddress-button"><?php echo __('Get Address','billmate'); ?></button>
                    </p>

                    <p class="form-row">
                        <div id="getaddresserr"></div>
                    </p>
                </div>
            </div>
            <div class="clear"></div>
            <script type="text/javascript">
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
				var nopno = '<?php echo __('You have to type in Social Security number/Corporate number','billmate'); ?>';
            </script>

            <?php
        }
    }

    public function getaddress()
    {

        $billmate = new BillMate(get_option('billmate_common_eid'),get_option('billmate_common_secret'),true,false,false);
		$_SESSION['billmate_pno'] = sanitize_text_field($_POST['pno']);
        $addr = $billmate->getAddress(array('pno' => sanitize_text_field($_POST['pno'])));
        if(isset($addr['code'])) {
            $response['success'] = false;
            $response['message'] = utf8_encode($addr['message']);
        } else {
            $data = array();
            foreach($addr as $key => $value){
                $data[$key] = mb_convert_encoding($value,'UTF-8','auto');
            }
            $response['success'] = true;
            $response['data'] = $data;
        }

        die(json_encode($response));
    }
	public function page_init() {
		register_setting(
			'billmate_common', // Option group
			'billmate_common_eid', // Option name
			array($this, 'sanitize') // Sanitize
		);
		register_setting(
			'billmate_common', // Option group
			'billmate_common_secret', // Option name
			array($this, 'sanitize') // Sanitize
		);
		register_setting(
			'billmate_common',
			'billmate_common_getaddress',
			array($this, 'sanitize')
		);
        register_setting(
            'billmate_common',
            'billmate_common_getaddress',
            array($this,'sanitize')
        );
        register_setting(
            'billmate_common',
            'billmate_common_overlay_enabled',
            array($this,'sanitize')
        );
		register_setting(
			'billmate_common',
			'billmate_common_logo',
			array($this, 'sanitize')
		);
		register_setting(
			'billmate_common',
			'billmate_common_activateonstatus',
			array($this, 'sanitize')
		);
                register_setting(
			'billmate_common',
                        'billmate_common_cancelonstatus',
                        array($this, 'sanitize')
                );
		register_setting(
			'billmate_common',
			'billmate_common_enable_overlay',
			[
				'sanitize_callback' => [$this, 'sanitize'],
				'default'           => false,
				'description'       => __('Test description of toggle overlay setting.'),
				'type'              => 'boolean'
			]
		);
		add_settings_section(
			'setting_credentials', // ID
			__('Common Billmate Settings', 'billmate'), // Title
			array($this, 'print_section_info'), // Callback
			'billmate-settings' // Page
		);
		
		add_settings_field(
			'billmate_common_eid', // ID
			__('Billmate ID', 'billmate'), // Title
			array($this, 'eid_callback'), // Callback
			'billmate-settings', // Page
			'setting_credentials' // Section
		);
		
		add_settings_field(
			'billmate_common_secret',
			__('Secret', 'billmate'),
			array($this, 'secret_callback'),
			'billmate-settings',
			'setting_credentials'
		);
		add_settings_field(
			'billmate_common_getaddress',
			__('Get Address', 'billmate'),
			array($this, 'getaddress_callback'),
			'billmate-settings',
			'setting_credentials'
		);
        add_settings_field(
            'billmate_common_getaddress',
            __('Get Address','billmate'),
            array($this,'getaddress_callback'),
            'billmate-settings',
            'setting_credentials'
        );
		add_settings_field(
			'billmate_common_activateonstatus',
			__('Activate Orders in Billmate Online when completed', 'billmate'),
			array($this, 'activateonstatus_callback'),
			'billmate-settings',
			'setting_credentials'
		);
                add_settings_field(
                    'billmate_common_cancelonstatus',
                    __('Enable crediting/cancelling of payments in Billmate Online', 'billmate'),
                    array($this, 'cancelonstatus_callback'),
                    'billmate-settings',
                    'setting_credentials'
                );
		add_settings_field(
			'billmate_common_logo',
			__('Logo to be displayed in the invoice', 'billmate'),
			array($this, 'logo_callback'),
			'billmate-settings',
			'setting_credentials'
		);
	}
	
	public function add_plugin_page() {
		add_options_page(
			__('Billmate Settings', 'billmate'),
			__('Billmate Settings', 'billmate'),
			'manage_options',
			'billmate-settings',
			array($this, 'create_admin_page')
		);
	}
	
	public function eid_callback() {
		$value = get_option('billmate_common_eid', '');
		echo '<input type="text" id="billmate_common_eid" name="billmate_common_eid" value="' . $value . '" />';
	}
	
	public function secret_callback() {
		$value = get_option('billmate_common_secret', '');
		echo '<input type="text" id="billmate_common_secret" name="billmate_common_secret" value="' . $value . '" />';
	}
	
	public function activateonstatus_callback() {
		$value    = get_option('billmate_common_activateonstatus', '');
		$inactive = ($value == 'inactive') ? 'selected="selected"' : '';
		$active   = ($value == 'active') ? 'selected="selected"' : '';
		echo '<select name="billmate_common_activateonstatus" id="billmate_common_activateonstatus">';
		echo '<option value="inactive"' . $inactive . '>' . __('Inactive', 'billmate') . '</option>';
		echo '<option value="active"' . $active . '>' . __('Active', 'billmate') . '</option>';
		echo '</select>';
	}

    public function cancelonstatus_callback() {
        $value    = get_option('billmate_common_cancelonstatus', '');
        $inactive = ($value == 'inactive') ? 'selected="selected"' : '';
        $active   = ($value == 'active') ? 'selected="selected"' : '';
        echo '<select name="billmate_common_cancelonstatus" id="billmate_common_cancelonstatus">';
        echo '<option value="inactive"' . $inactive . '>' . __('Inactive', 'billmate') . '</option>';
        echo '<option value="active"' . $active . '>' . __('Active', 'billmate') . '</option>';
        echo '</select>';
    }

    public function getaddress_callback()
    {
        $value = get_option('billmate_common_getaddress','');
        $inactive = ($value == 'inactive') ? 'selected="selected"' : '';
        $active = ($value == 'active') ? 'selected="selected"' : '';
        echo '<select name="billmate_common_getaddress" id="billmate_common_getaddress">';
        echo '<option value="inactive"'.$inactive.'>'.__('Inactive','billmate').'</option>';
        echo '<option value="active"'.$active.'>'.__('Active','billmate').'</option>';
        echo '</select>';
    }

	public function logo_callback()
    {
        $value = get_option('billmate_common_logo', '');
        echo '<input type="text" id="billmate_common_logo" name="billmate_common_logo" value="' . $value . '" />';
    }
	
	public function print_section_info() {
		echo __('Here is the common settings for the Billmate Payment module', 'billmate');
	}
	
	public function sanitize($input) {
		return $input;
	}
	
	public function create_admin_page() {
		// Set class property
		$this->options = get_option('billmate_common_settings');
		?>
		<div class="wrap">
			<?php // screen_icon(); Deprecated
			?>
			<h2><?php echo __('Billmate Settings', 'billmate'); ?></h2>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('billmate_common');
				do_settings_sections('billmate-settings');
				submit_button();
				?>
			</form>
		</div>
		<script type="text/javascript">
      jQuery(document).ready(function($) {
        $('form').on('submit', function(e) {
          var credentialStatus = false;

          $.ajax({
            url: ajaxurl,
            type: 'POST',
            async: false,
            data: {
              action: 'verify_credentials',
              billmate_id: $('#billmate_common_eid').val(),
              billmate_secret: $('#billmate_common_secret').val()
            },
            success: function(response) {
              var result = JSON.parse(response);

              if(result.success) {
                $(this).parent('form').submit();
                credentialStatus = true;
              } else {
                alert("<?php echo __('Please, check your credentials', 'billmate')?>");
                credentialStatus = false;


              }
            }
          });
          if(!credentialStatus) {
            e.preventDefault();
            e.stopPropagation();
            e.returnValue = false;
            return false;
          }

        })
      })

		</script>
		<style>
			/* Tooltip container */
			.tooltip {
				position: relative;
				display: inline-block;
				/*border-bottom: 1px dotted #0099D3; /* If you want dots under the hoverable text */
				cursor: help;
				color: #0099D3;
				top: 2px;
			}

			/* Tooltip text */
			.tooltip .tooltiptext {
				visibility: hidden;
				width: 340px;
				background: #0BA9E5 linear-gradient(180deg, #0BA9E5 0%, #0099D3 100%);
				color: #fff;
				text-align: center;
				padding: 5px 0;
				border-radius: 6px;

				/* Position the tooltip text */
				position: absolute;
				z-index: 1;
				bottom: 125%;
				left: -170px;
				margin-left: 10px;

				/* Fade in tooltip */
				opacity: 0;
				transition: opacity 0.3s;
			}

			/* Tooltip arrow */
			.tooltip .tooltiptext::after {
				content: "";
				position: absolute;
				top: 100%;
				left: 50%;
				margin-left: -5px;
				border: 5px solid transparent;
				border-top-color: #0099D3;
			}

			/* Show the tooltip text when you mouse over the tooltip container */
			.tooltip:hover .tooltiptext {
				visibility: visible;
				opacity: 1;
			}
		</style>
		<?php
	}
	
	public function verify_credentials() {
		require_once 'library/Billmate.php';
		$billmate = new BillMate(sanitize_text_field($_POST['billmate_id']),sanitize_text_field($_POST['billmate_secret']),true, false,false);
		$values['PaymentData'] = array(
			'currency' => 'SEK',
			'language' => 'sv',
			'country'  => 'se'
		);

        $result                = $billmate->getPaymentplans($values);
		$response              = array();
		if(isset($result['code']) && ($result['code'] == 9013 || $result['code'] == 9010 || $result['code'] == 9012)) {
			$response['success'] = false;
		} else {
			$response['success'] = true;
		}
		echo json_encode($response);
		wp_die();
	}
}
