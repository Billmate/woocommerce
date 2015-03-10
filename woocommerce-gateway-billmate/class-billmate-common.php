<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-02
 * Time: 16:29
 */

class BillmateCommon {

	private $options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action('wp_ajax_verify_credentials', array($this,'verify_credentials'));
	}

	public function page_init() {
		register_setting(
			'billmate_common', // Option group
			'billmate_common_eid', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);
		register_setting(
			'billmate_common', // Option group
			'billmate_common_secret', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_credentials', // ID
			__('Common Billmate Settings'), // Title
			array( $this, 'print_section_info' ), // Callback
			'billmate-settings' // Page
		);

		add_settings_field(
			'billmate_common_eid', // ID
			__('Billmate ID'), // Title
			array( $this, 'eid_callback' ), // Callback
			'billmate-settings', // Page
			'setting_credentials' // Section
		);

		add_settings_field(
			'billmate_common_secret',
			__('Secret'),
			array( $this, 'secret_callback' ),
			'billmate-settings',
			'setting_credentials'
		);
	}

	public function add_plugin_page() {
		add_options_page(
			'Billmate Common',
			'Billmate Settings',
			'manage_options',
			'billmate-settings',
			array( $this, 'create_admin_page' )
		);
	}

	public function eid_callback(){
		$value = get_option('billmate_common_eid','');
		echo '<input type="text" id="billmate_common_eid" name="billmate_common_eid" value="'.$value.'" />';
	}

	public function secret_callback(){
		$value = get_option('billmate_common_secret','');
		echo '<input type="text" id="billmate_common_secret" name="billmate_common_secret" value="'.$value.'" />';
	}

	public function sanitize($input){
		return $input;
	}
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option( 'billmate_common_settings' );
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo __('Billmate Settings'); ?></h2>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'billmate_common' );
				do_settings_sections( 'billmate-settings' );
				submit_button();
				?>
			</form>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('form').on('submit',function(e){
				var credentialStatus = false;

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						async: false,
						data: {
							action:'verify_credentials',
							billmate_id: $('#billmate_common_eid').val(),
							billmate_secret: $('#billmate_common_secret').val()
						},
						success: function(response){
							var result = JSON.parse(response);
							if(result.success){
								$(this).parent('form').submit();
								credentialStatus = true;
							} else {
								alert("<?php echo __('Please, check your credentials')?>");
								credentialStatus = false;


							}
						}
					});
					if(!credentialStatus){
						e.preventDefault();
						e.stopPropagation();
						e.returnValue = false;
						return false;
					}

				})
			})

		</script>
	<?php
	}

	public function verify_credentials()
	{
		require_once 'library/Billmate.php';
		$billmate = new BillMate($_POST['billmate_id'],$_POST['billmate_secret'],true, false,false);
		$values['PaymentData'] = array(
			'currency' => 'SEK',
			'language' => 'sv',
			'country' => 'se'
		);
		$result = $billmate->getPaymentplans($values);
		$response = array();
		if(isset($result['code']) && $result['code'] == 9013 || $result['code'] == 9010){
			$response['success'] = false;
		}
		else{
			$response['success'] = true;
		}
		echo json_encode($response);
		wp_die();
	}
}