<?php
/**
 * WooCommerce Billmate Gateway
 * By Billmate (billmate@billmate.se)
 * 
 * Uninstall - removes all Billmate options from DB when user deletes the plugin via WordPress backend.
 * @since 0.3
 **/
 
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

delete_option( 'woocommerce_billmate_settings' );