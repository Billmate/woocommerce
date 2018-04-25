<?php

// Shortcodes for display cost/month
add_shortcode( 'billmate_price', 'return_billmate_price' );
add_shortcode( 'billmate_currency', 'return_billmate_currency' );
add_shortcode( 'billmate_img', 'return_billmate_basic_img' );
add_shortcode( 'billmate_partpayment_info_link', 'return_billmate_partpayment_info_link' );
add_shortcode('billmate_checkout','get_billmate_checkout');
add_shortcode('billmate_cart','get_billmate_cart');
add_action('wp_enqueue_scripts','add_billmate_popup');
// Return Monthly price
function return_billmate_price() {
	global $billmate_partpayment_shortcode_price, $eid;
	$pclasses = get_option('wc_gateway_billmate_partpayment_pclasses');
	$flag = BillmateFlags::CHECKOUT_PAGE;
	$pclasses_not_available = true;

	if($pclasses)
		$pclasses_not_available = false;
	//$WC_Gateway_Billmate_Partpayment = new WC_Gateway_Billmate_Partpayment;
	$product = new WC_Product( get_the_ID() );

    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
        $price = $product->get_price();
    } else {
        $price = $product->price;
    }

	$settings = get_option('woocommerce_billmate_partpayment_settings');
	$eid = get_option('billmate_common_eid');;


	if(!$pclasses_not_available) {

		foreach ($pclasses as $pclass) {

			if (strlen($pclass['description']) > 0 ) {
				// Get monthly cost for current pclass
				$billmate_partpayment_shortcode_price = BillmateCalc::calc_monthly_cost(
									$price,
									$pclass,
									$flag
								);
			} // End if $pclass->getType() == 0 or 1

		} // End foreach
	}
		return $billmate_partpayment_shortcode_price;
}

function get_billmate_checkout() {

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', 1);
    }

    $checkout   = new WC_Gateway_Billmate_Checkout();
    $return     = '';

    if(isset(WC()->cart) AND is_object(WC()->cart) AND method_exists(WC()->cart, "is_empty") AND !WC()->cart->is_empty()) {
        $checkoutUrl = $checkout->get_url();
        $wpLanguage = strtolower(current(explode('_',get_locale())));

        // Button to WooCommerce checkout page when another payment method is available
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (is_array($available_gateways) AND count($available_gateways) > 0) {
            $return .= '<div class="billmate-checkout-another-payment-wrapper">
                <a class="button" href="'.get_permalink(wc_get_page_id('checkout')).'">'.__('Select another payment method', 'billmate').'</a>
            </div>';
        }

        if($checkoutUrl != "") {
            // Billmate Checkout iframe
            $return .= '<div id="checkoutdiv"><iframe id="checkout" src="' . $checkoutUrl . '" sandbox="allow-same-origin allow-scripts allow-modals allow-popups allow-forms allow-top-navigation" style="width:100%;min-height:800px;border:none;" scrolling="no"></iframe>';
        } else {
            $checkoutError = $checkout->get_error();
            if($wpLanguage != "sv") {
                $return .= '<div id="checkoutdiv">'.sprintf(__('Billmate Checkout could not be loaded, please contact store manager. Language need to be set to SV. Error code: %s','billmate'), $checkoutError['code']).'</div>';
            } else {
                $return .= '<div id="checkoutdiv">'.sprintf(__('Billmate Checkout could not be loaded, please contact store manager. Error code: %s','billmate'), $checkoutError['code']).'</div>';
            }
        }
    }
    return $return;
}

function get_billmate_cart(){
	wc_print_notices();

	do_action( 'woocommerce_before_cart' ); ?>

	<form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<table class="shop_table shop_table_responsive cart" cellspacing="0">
		<thead>
		<tr>
			<th class="product-remove">&nbsp;</th>
			<th class="product-thumbnail">&nbsp;</th>
			<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-price"><?php _e( 'Price', 'woocommerce' ); ?></th>
			<th class="product-quantity"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
			<th class="product-subtotal"><?php _e( 'Total', 'woocommerce' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

					<td class="product-remove">
						<?php
						echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
							'<a href="%s" class="remove billmate-remove-item" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
							esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
							__( 'Remove this item', 'woocommerce' ),
							esc_attr( $product_id ),
							esc_attr( $_product->get_sku() )
						), $cart_item_key );
						?>
					</td>

					<td class="product-thumbnail">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

						if ( ! $product_permalink ) {
							echo $thumbnail;
						} else {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
						}
						?>
					</td>

					<td class="product-name" data-title="<?php _e( 'Product', 'woocommerce' ); ?>">
						<?php
						if ( ! $product_permalink ) {
							echo apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;';
						} else {
							echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() ), $cart_item, $cart_item_key );
						}

						// Meta data
						echo WC()->cart->get_item_data( $cart_item );

						// Backorder notification
						if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
							echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>';
						}
						?>
					</td>

					<td class="product-price" data-title="<?php _e( 'Price', 'woocommerce' ); ?>">
						<?php
						echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
						?>
					</td>

					<td class="product-quantity" data-title="<?php _e( 'Quantity', 'woocommerce' ); ?>" data-cart_item="<?php echo $cart_item_key; ?>">
						<?php
						if ( $_product->is_sold_individually() ) {
							$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
						} else {
							$product_quantity = woocommerce_quantity_input( array(
								'input_name'  => "cart[{$cart_item_key}][qty]",
								'input_value' => $cart_item['quantity'],
								'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
								'min_value'   => '0'
							), $_product, false );
						}

						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
						?>
					</td>

					<td class="product-subtotal" data-title="<?php _e( 'Total', 'woocommerce' ); ?>">
						<?php
						echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
						?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_cart_contents' );
		?>
		<tr>
			<td colspan="6" class="actions">

				<?php if ( wc_coupons_enabled() ) { ?>
					<div class="coupon">

						<label for="coupon_code"><?php _e( 'Coupon:', 'woocommerce' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="billmate_coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <input type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply Coupon', 'woocommerce' ); ?>" />

						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php } ?>

				<input type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update Cart', 'woocommerce' ); ?>" />

				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart' ); ?>
			</td>
		</tr>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</tbody>
	</table>

	<?php do_action( 'woocommerce_after_cart_table' ); ?>

</form>

<div class="cart-collaterals">

	<?php do_action( 'woocommerce_cart_collaterals' ); ?>

</div>

<?php do_action( 'woocommerce_after_cart' );
}
// Return Currency
function return_billmate_currency() {
	global $billmate_partpayment_shortcode_currency;
	return $billmate_partpayment_shortcode_currency;
}

// Return Billmate basic image
function return_billmate_basic_img() {
	global $billmate_shortcode_img;
	return '<img class="billmate-logo-img" src="' . $billmate_shortcode_img . '" />';
}

// Return Account info popup link
function return_billmate_partpayment_info_link() {
	global $billmate_partpayment_country, $billmate_partpayment_eid,$billmate_shortcode;
	$billmate_shortcode = true;

	$product = new WC_Product( get_the_ID() );

    if(version_compare(WC_VERSION, '3.0.0', '>=')) {
        $price = $product->get_price();
    } else {
        $price = $product->price;
    }

	ob_start();
	WC_Gateway_Billmate_Partpayment::payment_fields_options( $price, false , BillmateFlags::PRODUCT_PAGE);
	echo '<script>var billmate_eid = "'.get_option('billmate_common_eid').'"; var billmate_invoice_fee_price = 0;</script>';
	echo '<a href="https://efinance.se/billmate/?cmd=villkor_delbetalning" onclick="'; ?>window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=650');return false;<?php echo '">' . WC_Gateway_Billmate_Partpayment::get_account_terms_link_text($billmate_partpayment_country) . '</a>';

	$output_string = ob_get_clean();
	return $output_string;
}

function add_billmate_popup(){
	global $billmate_shortcode;
	if($billmate_shortcode) {
		wp_register_script('billmate-popup-js', plugins_url('/js/billmatepopup.js', __FILE__), array(), false, true);
		wp_enqueue_script('billmate-popup-js');

	}
}


// Return home url with added params
function billmate_add_query_arg($args = array()) {
    if (is_string($args)) {
        parse_str(end(explode('?',$args)), $args);
    }

    $homeUrlParams = array();

    $homeUrl = rtrim(home_url(), '/');
    $homeUrlParamString = parse_url($homeUrl, PHP_URL_QUERY);
    $baseUrl = current(explode("?", $homeUrl));

    parse_str($homeUrlParamString, $homeUrlParams);
    $homeUrlParams = array_merge($homeUrlParams, $args);
    return trailingslashit($baseUrl)."?".http_build_query($homeUrlParams);
}

// Return home url with set params
function billmate_set_query_arg($args = array()) {
    if (is_string($args)) {
        parse_str(end(explode('?',$args)), $args);
    }

    $args = (is_array($args)) ? $args : array($args);
    $homeUrl = rtrim(home_url(), '/');
    $baseUrl = current(explode("?", $homeUrl));
    return trailingslashit($baseUrl)."?".http_build_query($args);
}

?>
