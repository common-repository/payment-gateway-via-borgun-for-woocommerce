<?php
/*
  Plugin Name: Payment gateway via Teya SecurePay for WooCommerce
  Plugin URI: https://profiles.wordpress.org/tacticais/
  Description: Extends WooCommerce with a <a href="https://docs.borgun.is/hostedpayments/securepay/" target="_blank">Teya SecurePay</a> gateway.
  Version: 1.3.33
  Author: Tactica
  Author URI: http://tactica.is
  Text Domain: borgun_woocommerce
  Domain Path: /languages
  Requires at least: 4.4
  WC requires at least: 3.2.3
  License: GNU General Public License v3.0
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'BORGUN_VERSION', '1.3.33' );
define( 'BORGUN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BORGUN_URL', plugin_dir_url( __FILE__ ) );

function borgun_wc_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	} else {
		return false;
	}
}

add_action( 'plugins_loaded', 'woocommerce_borgun_init', 0 );
function woocommerce_borgun_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	//Add the gateway to woocommerce
	require_once BORGUN_DIR . '/includes/class-wc-gateway-borgun.php';
	add_filter( 'woocommerce_payment_gateways', 'add_borgun_gateway' );
	function add_borgun_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Borgun';

		return $methods;
	}

	add_action( 'woocommerce_cancelled_order', 'borgun_cancel_order' );
	function borgun_cancel_order( $order_id ){
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}
		if( !empty($order) && $order->get_payment_method() == 'borgun' ) {
			$borgun_settings = get_option('woocommerce_borgun_settings');
			if( !empty($borgun_settings) && !empty( $borgun_settings['cancelurl'] ) ){
					wp_safe_redirect( $borgun_settings['cancelurl'] );
					exit;
			}
		}
	}
}

add_action( 'plugins_loaded', 'woocommerce_borgun_textdomain' );
function woocommerce_borgun_textdomain(){
	global $wp_version;

	// Default languages directory for Saltpay.
	$lang_dir = BORGUN_DIR . 'languages/';
	$lang_dir = apply_filters( 'borgun_languages_directory', $lang_dir );

	$current_lang = apply_filters( 'wpml_current_language', NULL );
	if($current_lang){
		$languages = apply_filters( 'wpml_active_languages', NULL );
		$locale = ( isset($languages[$current_lang]) && isset($languages[$current_lang]['default_locale']) ) ? $languages[$current_lang]['default_locale'] : '' ;
	}else{
		$locale = get_locale();
		if ( $wp_version >= 4.7 ) {
			$locale = get_user_locale();
		}
	}

	$mofile = sprintf( '%1$s-%2$s.mo', 'borgun_woocommerce', $locale );

	// Setup paths to current locale file.
	$mofile_local  = $lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/borgun/ folder.
		load_textdomain( 'borgun_woocommerce', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/borgun/languages/ folder.
		load_textdomain( 'borgun_woocommerce', $mofile_local );
	} else {
		// Load the default language files.
		load_plugin_textdomain( 'borgun_woocommerce', false, $lang_dir );
	}
}

add_action( 'woocommerce_blocks_loaded', 'borgun_woocommerce_blocks_support' );
function borgun_woocommerce_blocks_support() {
  if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

	require_once BORGUN_DIR . 'includes/class-payment-method-borgun-registration.php';
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new PaymentMethodBorgunRegistration );
		}
	);
  }
}