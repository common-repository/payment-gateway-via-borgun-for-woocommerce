<?php

/**
 * @property string testmode
 * @property string merchantid
 * @property string paymentgatewayid
 * @property string secretkey
 * @property string langpaymentpage
 * @property string successurl
 * @property string cancelurl
 * @property string errorurl
 * @property string notification_email
 */
class WC_Gateway_Borgun extends WC_Payment_Gateway {

	const BORGUN_ENDPOINT_SANDBOX = 'https://test.borgun.is/securepay/';
	const BORGUN_ENDPOINT_LIVE = 'https://securepay.borgun.is/securepay/';

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * Debug
	 *
	 * @var bool
	 */
	private $debug;

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Gateway testmode
	 *
	 * @var string
	 */
	private $testmode;

	/**
	 * MerchantId that identifies the merchant
	 *
	 * @var string
	 */
	private $merchantid;

	/**
	 * Payment Gateway Id that identifies the payment method
	 *
	 * @var string
	 */
	private $paymentgatewayid;

	/**
	 * Saltpay secretkey
	 *
	 * @var string
	 */
	private $secretkey;

	/**
	 * Language of Payment Page
	 *
	 * @var string
	 */
	private $langpaymentpage;

	/**
	 * Receipt text
	 *
	 * @var string
	 */
	private $receipttext;

	/**
	 * Redirect text
	 *
	 * @var string
	 */
	private $redirecttext;

	/**
	 * Skip receipt page
	 *
	 * @var string
	 */
	private $skipreceiptpage;

	/**
	 * Success Page URL
	 *
	 * @var string
	 */
	private $successurl;

	/**
	 * Cancel Page URL
	 *
	 * @var string
	 */
	private $cancelurl;

	/**
	 * Error Page URL
	 *
	 * @var string
	 */
	private $errorurl;

	/**
	 * Notification Email
	 *
	 * @var string
	 */
	private $notification_email;

	/**
	 * Order line items grouping
	 *
	 * @var string
	 */
	private $TotalLineItem;

	public function __construct() {
		$this->id                 = 'borgun';
		$this->icon               = BORGUN_URL . '/cards.png';
		$this->has_fields         = false;
		$this->method_title       =  __('Teya', 'borgun_woocommerce');
		$this->method_description = __('Teya Secure Payment Page enables merchants to sell products securely on the web with minimal integration effort', 'borgun_woocommerce');

		// What methods do support plugin
		$this->supports = array(
			'products',
			'refunds',
		);


		// Load the form fields
		$this->init_form_fields();
		$this->init_settings();
		// Get setting values
		$this->enabled            = $this->get_option( 'enabled' );
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->testmode           = $this->get_option( 'testmode' );
		$this->merchantid         = $this->get_option( 'merchantid' );
		$this->paymentgatewayid   = $this->get_option( 'paymentgatewayid' );
		$this->secretkey          = $this->get_option( 'secretkey' );
		$this->langpaymentpage    = $this->get_option( 'langpaymentpage' );
		$this->receipttext 		  = $this->get_option( 'receipttext', __('Thank you - your order is now pending payment. We are now redirecting you to Teya to make payment.', 'borgun_woocommerce') );
		$this->redirecttext 	  = $this->get_option( 'redirecttext',  __('Thank you for your order. We are now redirecting you to Teya to make payment.', 'borgun_woocommerce') );
		$this->skipreceiptpage    = 'yes' === $this->get_option( 'skipreceiptpage', 'no' );
		$this->successurl         = $this->get_option( 'successurl' );
		$this->cancelurl          = $this->get_option( 'cancelurl' );
		$this->errorurl           = $this->get_option( 'errorurl' );
		$this->notification_email = $this->get_option( 'notification_email' );
		$this->TotalLineItem      = 'yes' === $this->get_option( 'TotalLineItem', 'no' );
		$this->debug              = 'yes' === $this->get_option( 'debug', 'no' );
		self::$log_enabled        = $this->debug;
		// Filters
		add_filter( 'wcml_gateway_text_keys_to_translate', array( $this, 'borgun_text_keys_to_translate' ) );
		// Hooks
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_gateway_borgun', array( $this, 'check_borgun_response' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'check_borgun_response' ) );
		add_action( 'before_woocommerce_pay', array( $this, 'checkout_payment_handler'), 9 );
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if (self::$log_enabled) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'borgun' ) );
		}
	}

	public function admin_options() {
		?>
		<h3> <?php _e( 'Teya', 'borgun_woocommerce' ); ?></h3>
		<p> <?php _e( 'Pay with your credit card via Teya.', 'borgun_woocommerce' ); ?></p>
		<?php if ( $this->is_valid_for_use() ) : ?>
			<table class="form-table"><?php $this->generate_settings_html(); ?></table>
		<?php else : ?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled:', 'borgun_woocommerce' ); ?></strong> <?php _e( 'Current Store currency is not supported by Teya SecurePay. Allowed values are GBP, USD, EUR, DKK, NOK, SEK, CHF, CAD, HUF, BHD, AUD, RUB, PLN, RON, HRK, CZK and ISK.', 'borgun_woocommerce' ); ?></p></div>
			<?php
		endif;
	}

	//Check if this gateway is enabled and available in the user's country
	function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), array(
			'ISK',
			'GBP',
			'USD',
			'EUR',
			'DKK',
			'NOK',
			'SEK',
			'CHF',
			'CAD',
			'HUF',
			'BHD',
			'AUD',
			'RUB',
			'PLN',
			'RON',
			'HRK',
			'CZK',
		) )
		) {
			return false;
		}

		return true;
	}

	//Initialize Gateway Settings Form Fields
	function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'borgun_woocommerce' ),
				'label'       => __( 'Enable Teya', 'borgun_woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'              => array(
				'title'       =>  __( 'Title', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' =>  __( 'This controls the title which the user sees during checkout.', 'borgun_woocommerce' ),
				'default'     =>  __( 'Teya', 'borgun_woocommerce' )
			),
			'description'        => array(
				'title'       => __( 'Description', 'borgun_woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'borgun_woocommerce' ),
				'default'     => __( 'Pay with your credit card via Teya.', 'borgun_woocommerce' )
			),
			'testmode'           => array(
				'title'       => __( 'Teya Test Mode', 'borgun_woocommerce' ),
				'label'       => __( 'Enable Test Mode', 'borgun_woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in development mode.', 'borgun_woocommerce' ),
				'default'     => 'no'
			),
			'paymentgatewayid'   => array(
				'title'       => __( 'Payment Gateway ID', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This is the Payment Gateway ID supplied by Teya.', 'borgun_woocommerce' ),
				'default'     => '16'
			),
			'merchantid'         => array(
				'title'       => __( 'Merchant ID', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' =>  __( 'This is the ID supplied by Teya.', 'borgun_woocommerce' ),
				'default'     => '9275444'
			),
			'secretkey'          => array(
				'title'       => __( 'Secret Key', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This is the Secret Key supplied by Teya.', 'borgun_woocommerce' ),
				'default'     => '99887766'
			),
			'notification_email' => array(
				'title'       => __( 'Notification Email', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This is the email Teya will send payment receipts to.', 'borgun_woocommerce' ),
				'default'     => get_option( 'admin_email' )
			),
			'langpaymentpage'    => array(
				'title'       => __( 'Language of Payment Page', 'borgun_woocommerce' ),
				'type'        => 'select',
				'description' => __('Select which language to show on Payment Page.', 'borgun_woocommerce' ),
				'default'     => 'en',
				'options'     => array(
					'is' => __('Icelandic', 'borgun_woocommerce' ),
					'en' => __('English', 'borgun_woocommerce' ),
					'de' => __('German', 'borgun_woocommerce' ),
					'fr' => __('French', 'borgun_woocommerce' ),
					'ru' => __('Russian', 'borgun_woocommerce' ),
					'es' => __('Spanish', 'borgun_woocommerce' ),
					'it' => __('Italian', 'borgun_woocommerce' ),
					'pt' => __('Portugese', 'borgun_woocommerce' ),
					'si' => __('Slovenian', 'borgun_woocommerce' ),
					'hu' => __('Hungarian', 'borgun_woocommerce' ),
					'se' => __('Swedish', 'borgun_woocommerce' ),
					'nl' => __('Dutch', 'borgun_woocommerce' ),
					'pl' => __('Polish', 'borgun_woocommerce' ),
					'no' => __('Norwegian', 'borgun_woocommerce' ),
					'cz' => __('Czech', 'borgun_woocommerce' ),
					'sk' => __('Slovak', 'borgun_woocommerce' ),
					'hr' => __('Hrvatski', 'borgun_woocommerce' ),
					'ro' => __('Romanian', 'borgun_woocommerce' ),
					'dk' => __('Danish', 'borgun_woocommerce' ),
					'fi' => __('Finnish', 'borgun_woocommerce' ),
					'fo' => __('Faroese', 'borgun_woocommerce' ),
					'sr' => __('Serbian', 'borgun_woocommerce' ),
					'bg' => __('Bulgarian', 'borgun_woocommerce' ),
					'lt' => __('Lithuanian', 'borgun_woocommerce' ),
				)
			),
			'receipttext'     	=> array(
				'title'       =>  __('Receipt text', 'borgun_woocommerce'),
				'type'        => 'textarea',
				'description' =>  __('Buyer will see this text after woocommerce order create.', 'borgun_woocommerce'),
				'default'     =>  __('Thank you - your order is now pending payment. We are now redirecting you to Teya to make payment.', 'borgun_woocommerce'),
			),
			'redirecttext'     	=> array(
				'title'       =>  __('Redirect text', 'borgun_woocommerce'),
				'type'        => 'textarea',
				'description' => __('Buyer will see this text before redirecting to Teya', 'borgun_woocommerce'),
				'default'     => __('Thank you for your order. We are now redirecting you to Teya to make payment.', 'borgun_woocommerce'),
			),
			'skipreceiptpage'=> array(
				'title'       =>  __('Skip Teya receipt page', 'borgun_woocommerce'),
				'type'        => 'checkbox',
				'description' => __('If checked Teya receipt page is not displayed and the buyer is redirected to the Success Page URL upon successful payment.'),
				'default'     => 'yes'
			),
			'successurl'         => array(
				'title'       =>  __('Success Page URL', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' =>  __('Buyer will be sent to this page after a successful payment.', 'borgun_woocommerce' ),
				'default'     => ''
			),
			'cancelurl'          => array(
				'title'       =>  __('Cancel Page URL', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' =>  __('Buyer will be sent to this page if he pushes the cancel button instead of finalizing the payment.', 'borgun_woocommerce' ),
				'default'     => ''
			),
			'errorurl'           => array(
				'title'       =>  __('Error Page URL', 'borgun_woocommerce' ),
				'type'        => 'text',
				'description' =>  __('Buyer will be sent to this page if an unexpected error occurs.', 'borgun_woocommerce' ),
				'default'     => ''
			),
			'TotalLineItem' => array(
				'title'       => __('Order line items grouping', 'borgun_woocommerce'),
				'label'       => __('Send order as 1 line item', 'borgun_woocommerce'),
				'type'        => 'checkbox',
				'description' => __('You can uncheck this if you don\'t use discounts and have integer quantities.', 'borgun_woocommerce'),
				'default'     => 'yes'
			),
			'debug' => array(
				'title'       => __( 'Debug', 'borgun_woocommerce' ),
				'label'       => __( 'Enable Debug Mode', 'borgun_woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'desc_tip'    => true
			)
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return false|string
	 */
	function check_hash( $order ) {
		$ipnUrl           = WC()->api_request_url( 'WC_Gateway_Borgun' );
		$hash             = array();
		$hash[]           = $this->merchantid;
		$hash[]           = esc_url_raw( $this->get_return_url( $order ) );
		$hash[]           = $ipnUrl;
		$hash[]           = 'WC-' . $order->get_id();
		$hash[]           = number_format( $order->get_total(), wc_get_price_decimals(), '.', '' );
		$hash[]           = $order->get_currency();
		$hash = apply_filters( 'borgun_'.$this->id.'_check_hash', $hash, $order );
		$message          = implode( '|', $hash );

		$CheckHashMessage = trim( $message );
		if (extension_loaded('mbstring')) {
			$CheckHashMessage = mb_convert_encoding($CheckHashMessage, 'UTF-8');
		}else{
			$CheckHashMessage = utf8_encode($CheckHashMessage);
		}
		$Checkhash = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );



		return $Checkhash;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return false|string
	 */
	function check_order_hash( $order ) {
		$hash             = array();
		$hash[]           = 'WC-' . $order->get_id();
		$hash[]           = number_format( $order->get_total(), wc_get_price_decimals(), '.', '' );
		$hash[]           = $order->get_currency();
		$hash = apply_filters( 'borgun_'.$this->id.'_check_order_hash', $hash, $order );
		$message          = implode( '|', $hash );
		//$CheckHashMessage = utf8_encode( trim( $message ) );
		$CheckHashMessage = trim( $message );

		if (extension_loaded('mbstring')) {
			$CheckHashMessage = mb_convert_encoding($CheckHashMessage, 'UTF-8');
		}else{
			$CheckHashMessage = utf8_encode($CheckHashMessage);
		}
		$Checkhash = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );

		return $Checkhash;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return false|string
	 */
	function check_order_refund_hash( $order ) {
		$hash             = array();
		$hash[]           = $this->merchantid;
		$refundid = get_post_meta( $order->get_id(), '_' . $this->id . '_refundid', true );
		if(empty($refundid)){
			$refundid = $order->get_meta('_' . $this->id . '_refundid');
		}
		$hash[]           = $refundid;
		$hash = apply_filters( 'borgun_'.$this->id.'_check_order_refund_hash', $hash, $order );
		$message          = implode( '|', $hash );
		$CheckHashMessage = trim( $message );
		if (extension_loaded('mbstring')) {
			$CheckHashMessage = mb_convert_encoding($CheckHashMessage, 'UTF-8');
		}else{
			$CheckHashMessage = utf8_encode($CheckHashMessage);
		}
		$Checkhash = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );

		return $Checkhash;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	function get_borgun_args( $order ) {
		//Teya Args
		global $wp_version;
		$ipnUrl = WC()->api_request_url( 'WC_Gateway_Borgun' );
		$borgun_args = array(
			'merchantid'             => $this->merchantid,
			'paymentgatewayid'       => $this->paymentgatewayid,
			'checkhash'              => $this->check_hash( $order ),
			'orderid'                => 'WC-' . $order->get_id(),
			'reference'              => $order->get_order_number(),
			'currency'               => $order->get_currency(),
			'language'               => $this->langpaymentpage,
			'SourceSystem'           => 'WP' . $wp_version . ' - WC' . WC()->version . ' - BRG' . BORGUN_VERSION,
			'buyeremail'             => $order->get_billing_email(),
			'returnurlsuccess'       => esc_url_raw($this->get_return_url( $order )),
			'returnurlsuccessserver' => $ipnUrl,
			'returnurlcancel'        =>$order->get_checkout_payment_url( true ),
			'returnurlerror'         => $order->get_checkout_payment_url( true ),
			'amount'                 => number_format( $order->get_total(), wc_get_price_decimals(), '.', '' ),
			'pagetype'               => '0',
			'skipreceiptpage'        => ($this->skipreceiptpage) ? '1' : '0',
			'merchantemail'          => $this->notification_email,
		);
		$borgun_args = apply_filters( 'borgun_get_'.$this->id.'_args', $borgun_args, $order );
		// Cart Contents
		$total_line_item = $this->TotalLineItem;
		$include_tax = $this->tax_display();
		$item_loop = 0;
		if ( sizeof( $order->get_items( array( 'line_item', 'fee' ) ) ) > 0 ) {
			if ( $total_line_item == "yes" ) {
				$item_description = '';
				foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
					$item_name = strip_tags( $item->get_name() );
					if( !empty($item_description) ) $item_description .= ', ';
					$item_description .= $item_name;
				}
				if (strlen($item_description) > 499) $item_description = substr($item_description, 0, 496) . '...';
				$borgun_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_description, ENT_NOQUOTES, 'UTF-8' );
				$borgun_args[ 'itemcount_' . $item_loop ]       = 1;
				$borgun_args[ 'itemunitamount_' . $item_loop ]  = $borgun_args['amount'];
				$borgun_args[ 'itemamount_' . $item_loop ]      = $borgun_args['amount'];
			}else{
				foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
					if ( 'fee' === $item['type'] ) {
						$fee = $item->get_total();
						if ( $include_tax && $this->fee_tax_display($item) ){
							$fee += $item->get_total_tax();
						}
						$fee_total = $this->round( $fee, $order );
						$item_name = strip_tags( $item->get_name() );
						$borgun_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
						$borgun_args[ 'itemcount_' . $item_loop ]       = 1;
						$borgun_args[ 'itemunitamount_' . $item_loop ]  = $fee_total;
						$borgun_args[ 'itemamount_' . $item_loop ]      = $fee_total;

						$item_loop ++;
					}
					if ( $item['qty'] ) {
						$item_name = $item['name'];
						if ( $meta = wc_display_item_meta( $item ) ) {
							$item_name .= ' ( ' . $meta . ' )';
						}
						$item_name = strip_tags($item_name);
						$item_subtotal = number_format( $order->get_item_subtotal( $item, $include_tax ), wc_get_price_decimals(), '.', '' );
						$itemamount = $item_subtotal * $item['qty'];
						$borgun_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
						$borgun_args[ 'itemcount_' . $item_loop ]       = $item['qty'];
						$borgun_args[ 'itemunitamount_' . $item_loop ]  = number_format( $item_subtotal, wc_get_price_decimals(), '.', '' );
						$borgun_args[ 'itemamount_' . $item_loop ]      = number_format( $itemamount, wc_get_price_decimals(), '.', '' );
						$item_loop ++;
					}
				}
				if ( $order->get_shipping_total() > 0 ) {
					$shipping_total = $order->get_shipping_total();
					if( $include_tax ) $shipping_total += $order->get_shipping_tax();
					$shipping_total = $this->round( $shipping_total, $order );
					$borgun_args[ 'itemdescription_' . $item_loop ] = sprintf( /* translators: %s: Shipping */ __('Shipping (%s)', 'borgun_woocommerce' ), $order->get_shipping_method() );
					$borgun_args[ 'itemcount_' . $item_loop ]       = 1;
					$borgun_args[ 'itemunitamount_' . $item_loop ]  = number_format( $shipping_total, wc_get_price_decimals(), '.', '' );
					$borgun_args[ 'itemamount_' . $item_loop ]      = number_format( $shipping_total, wc_get_price_decimals(), '.', '' );
					$item_loop ++;
				}
				if (!$include_tax && $order->get_total_tax() > 0){
					$borgun_args[ 'itemdescription_' . $item_loop ] = __('Taxes', 'borgun_woocommerce' );
					$borgun_args[ 'itemcount_' . $item_loop ]       = 1;
					$borgun_args[ 'itemunitamount_' . $item_loop ]  = number_format( $order->get_total_tax(), wc_get_price_decimals(), '.', '' );
					$borgun_args[ 'itemamount_' . $item_loop ]      = number_format( $order->get_total_tax(), wc_get_price_decimals(), '.', '' );
					$item_loop ++;
				}
				if ( $order->get_total_discount() > 0 ) {
					$total_discount = $order->get_total_discount();
/*				Woocommerce can see any tax adjustments made thus far using subtotals.
					Since Woocommerce 3.2.3*/
					if(wc_tax_enabled() && method_exists('WC_Discounts','set_items') && $include_tax){
						$total_discount += $order->get_discount_tax();
					}
					if(wc_tax_enabled() && !method_exists('WC_Discounts','set_items') && !$include_tax){
						$total_discount -= $order->get_discount_tax();
					}

					$total_discount = $this->round($total_discount, $order);
					$borgun_args[ 'itemdescription_' . $item_loop ] = __('Discount', 'borgun_woocommerce' );
					$borgun_args[ 'itemcount_' . $item_loop ]       = 1;
					$borgun_args[ 'itemunitamount_' . $item_loop ]  = - number_format( $total_discount, wc_get_price_decimals(), '.', '' );
					$borgun_args[ 'itemamount_' . $item_loop ]      = - number_format( $total_discount, wc_get_price_decimals(), '.', '' );
					$item_loop ++;
				}
			}
		}

		return $borgun_args;
	}

	//Generate the borgun button link
	function generate_borgun_form( $order_id, $redirect = true ) {
		global $woocommerce;
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}

		if ( 'yes' == $this->testmode ) {
			$borgun_adr = self::BORGUN_ENDPOINT_SANDBOX . 'default.aspx';
		} else {
			$borgun_adr = self::BORGUN_ENDPOINT_LIVE . 'default.aspx';
		}
		$borgun_args       = $this->get_borgun_args( $order );
		$borgun_args_array = array();
		foreach ( $borgun_args as $key => $value ) {
			$borgun_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}

		if($redirect){
			$redirecttext = $this->get_translated_string($this->redirecttext, 'redirecttext');
			$code = sprintf( '$.blockUI({
					message: "%s",
					baseZ: 99999,
                    overlayCSS: { background: "#fff", opacity: 0.6 },
                    css: {
                        padding:        "20px",
                        zindex:         "9999999",
                        textAlign:      "center",
                        color:          "#555",
                        border:         "3px solid #aaa",
                        backgroundColor:"#fff",
                        cursor:         "wait",
                        lineHeight:     "24px",
                    }
                });

                jQuery("#borgun_payment_form").submit();', $redirecttext );
			wc_enqueue_js( $code );
		}

		$cancel_btn_html = ( current_user_can( 'cancel_order', $order_id ) ) ? '<a class="button cancel" href="' . htmlspecialchars_decode($order->get_cancel_order_url()) . '">' . __( 'Cancel order &amp; restore cart', 'borgun_woocommerce' ) . '</a>' : '';
		$html_form = '<form action="' . esc_url( $borgun_adr ) . '" method="post" id="borgun_payment_form">'
		             . implode( '', $borgun_args_array )
		             . '<input type="submit" class="button" id="wc_submit_borgun_payment_form" value="' . __( 'Pay via Teya', 'borgun_woocommerce' ) . '" /> ' . $cancel_btn_html . '</form>';

		return $html_form;
	}

	function process_payment( $order_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

	function check_borgun_response() {
		global $woocommerce;
		global $wp;
		if( empty($_POST) ) return;

		$posted = array();
		$posted['amount'] = !empty( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : '';
		$posted['status'] = !empty( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$posted['orderid'] = !empty( $_POST['orderid'] ) ? sanitize_text_field( $_POST['orderid'] ) : '';
		$posted['currency'] = !empty( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : '';
		$posted['reference'] = !empty( $_POST['reference'] ) ? sanitize_text_field( $_POST['reference'] ) : '';
		$posted['orderhash'] = !empty( $_POST['orderhash'] ) ? sanitize_text_field( $_POST['orderhash'] ) : '';
		$posted['step'] = !empty( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
		$posted['errorcode'] = !empty( $_POST['errorcode'] ) ? sanitize_text_field( $_POST['errorcode'] ) : '';
		$posted['errordescription'] = !empty( $_POST['errordescription'] ) ? sanitize_text_field( $_POST['errordescription'] ) : '';
		$posted['authorizationcode'] = !empty( $_POST['authorizationcode'] ) ? sanitize_text_field( $_POST['authorizationcode'] ) : '';
		$posted['maskedcardnumber'] = !empty( $_POST['creditcardnumber'] ) ? sanitize_text_field( $_POST['creditcardnumber'] ) : '';
		$posted['refundid'] = !empty( $_POST['refundid'] ) ? sanitize_text_field( $_POST['refundid'] ) : '';
		$posted['buyername'] = !empty( $_POST['buyername'] ) ? sanitize_text_field( $_POST['buyername'] ) : '';
		$posted['buyeremail'] = !empty( $_POST['buyeremail'] ) ? sanitize_text_field( $_POST['buyeremail'] ) : '';
		self::log( sprintf( __( 'check_borgun_response, posted: %s', 'borgun_woocommerce' ), wc_print_r($posted, true) ) );

		if ( $posted['status'] == 'OK' ) {
			if ( ! empty( $posted['orderid'] ) ) {
				$order_id = NULL;
				$order_id = apply_filters( 'borgun_'.$this->id.'_get_order_id', $order_id, $posted );
				if ( $order_id === NULL ) {
					$order_id = (int) str_replace( 'WC-', '', $posted['orderid'] );
				}
				if( !empty($order_id) ) {
					if ( function_exists( 'wc_get_order' ) ) {
						$order = wc_get_order( $order_id );
					} else {
						$order = new WC_Order( $order_id );
					}
					if( !empty($order) && ! $order->is_paid() ) {
						$payment_method = $order->get_payment_method();
						if( $payment_method == $this->id ) {
							$hash = $this->check_order_hash( $order );
							if ( $hash == $posted['orderhash'] ) {
								$order_metas = array(
									'authorizationcode'=> $posted['authorizationcode'],
									'maskedcardnumber'=> $posted['maskedcardnumber'],
									'refundid'=> $posted['refundid']
								);
								$this->save_order_metas( $order, $order_metas );
								$order->add_order_note(  __( 'Teya payment completed', 'borgun_woocommerce' ) );
								$order->payment_complete();
								$woocommerce->cart->empty_cart();
								if ( 'yes' == $this->testmode ) {
									$borgun_adr = self::BORGUN_ENDPOINT_SANDBOX . 'default.aspx';
								} else {
									$borgun_adr = self::BORGUN_ENDPOINT_LIVE . 'default.aspx';
								}
								if ( strpos( $posted['step'], 'Payment' ) !== false ) {
									$xml = '<PaymentNotification>Accepted</PaymentNotification>';
									wp_remote_post(
										$borgun_adr,
										array(
											'method'      => 'POST',
											'timeout'     => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'headers'     => array( 'Content-Type' => 'text/xml' ),
											'body'        => array( 'postdata' => $xml, 'postfield' => 'value' ),
											'sslverify'   => false
										)
									);
								}
								if(  !empty( $this->successurl ) ){
									wp_safe_redirect( $this->successurl );
									exit;
								}
							} else {
								$order->add_order_note( __( 'Order hash doesn\'t match', 'borgun_woocommerce' ) );
								wp_safe_redirect( wc_get_checkout_url() );
								exit;
							}
						}
					}
				}
			}
		}
		elseif( $posted['status'] == 'ERROR' ) {
			$order_id = '';
			if ( ! empty( $posted['orderid'] ) ) {
				$order_id = (int) str_replace( 'WC-', '', $posted['orderid'] );
			}
			elseif ( !empty( $posted['reference'] )) {
				$order_id = (int) $posted['reference'];
			}
			elseif ( !empty( $wp->query_vars['order-received']) ) {
				$order_id = (int) $wp->query_vars['order-received'];
			}

			if( !empty($order_id) ) {
				if ( function_exists( 'wc_get_order' ) ) {
					$order = wc_get_order( $order_id );
				} else {
					$order = new WC_Order( $order_id );
				}
				if( !empty($order) ) {
					$order->add_order_note( $message );
					wc_add_notice( $error, 'error' );
					if(!empty($this->errorurl)){
						$redirect = $this->errorurl;
					}else{
						$redirect = $order->get_checkout_payment_url( true ) . '&status=ERROR';
					}
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}
	}

	function receipt_page( $order_id ) {

		if(isset($_REQUEST['status'])){
			$posted = array();
			$posted['status'] = sanitize_text_field( $_REQUEST['status'] );
			if($posted['status'] == 'ERROR'){
				echo $this->generate_borgun_form( $order_id, false);
			}
		}else{
			$receipttext = $this->get_translated_string($this->receipttext, 'receipttext');
			if( !empty($receipttext) ) printf('<p>%s</p>', $receipttext );
			echo $this->generate_borgun_form( $order_id);
		}
	}

	/**
	 * Round prices.
	 * @param  double $price
	 * @param  WC_Order $order
	 * @return double
	 */
	protected function round( $price, $order ) {
		$precision = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$precision = 0;
		}

		return round( $price, $precision );
	}

	/**
	 * Check if currency has decimals.
	 * @param  string $currency
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD', 'ISK' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check tax display.
	 * @return bool
	 */
	protected function tax_display() {
		$prices_include_tax = wc_tax_enabled() ? get_option( 'woocommerce_prices_include_tax' ) : 'yes';
		return ( $prices_include_tax === 'yes' ) ? true : false ;
	}

	/**
	 * Check fee tax display.
	 * @param  WC_Order_Item_Fee $item
	 * @return bool
	 */
	protected function fee_tax_display( $item ) {
		$tax_display = $item->get_tax_status();
		return ( $tax_display == 'taxable' ) ? true : false ;
	}

	/**
	 * Adds text keys to_translate.
	 * @param  $text_keys array
	 * @return array
	 */
	public function borgun_text_keys_to_translate( $text_keys ){
		if( !in_array( 'receipttext', $text_keys ) )
			$text_keys[] = 'receipttext';
		if( !in_array( 'redirecttext', $text_keys ) )
			$text_keys[] = 'redirecttext';

		return $text_keys;
	}

	/**
	 * Getting translated value
	 * @param  $string string Original field value
	 * @param  $name string Field key
	 * @return string
	 */
	public function get_translated_string( $string, $name ) {
		$translated_string = $string;
		$current_lang = apply_filters( 'wpml_current_language', NULL );
		if($current_lang && class_exists('WCML_WC_Gateways') ){
			if(defined('WCML_WC_Gateways::STRINGS_CONTEXT')){
				$domain = WCML_WC_Gateways::STRINGS_CONTEXT;
			} else {
				$domain = 'woocommerce';
			}
			$translated_string = apply_filters(
				'wpml_translate_single_string',
				$string,
				$domain,
				$this->id . '_gateway_' . $name,
				$current_lang
			);
		}

		if ( $translated_string === $string ) {
			$translated_string = __( $string, 'borgun_woocommerce' );
		}

		return $translated_string;
	}

	/**
	 * Get cancel order url
	 */
	function checkout_payment_handler(){
		global $wp;
		$order_id = '';
		if( !empty( $wp->query_vars['order-pay']) ){
			$order_id = (int) $wp->query_vars['order-pay'];
		}

		if(empty($order_id)) return;

		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}

		if(empty($order)) return;

		if($order->get_payment_method() != $this->id ) return;

		$borgun_settings = get_option('woocommerce_borgun_settings');

		if (isset($_REQUEST['status'])) {
			if($_REQUEST['status'] === 'ERROR'){
				$error = ( isset($_REQUEST['errordescription']) && !empty( $_REQUEST['errordescription'] ) ) ? sanitize_text_field( $_REQUEST['errordescription'] ) : __('Payment error','borgun_woocommerce');

				$order->add_order_note( $error );
				wc_add_notice( $error, 'error' );
				if( !empty($borgun_settings) && !empty( $borgun_settings['errorurl'] ) ){
					$redirect = $borgun_settings['errorurl'];
					$redirect = $borgun_settings['errorurl'];
					wp_safe_redirect($redirect);
					exit;
				}
			}
			elseif($_REQUEST['status'] === 'CANCEL'){
				if(current_user_can( 'cancel_order', $order->get_id() )){
					$message = __('Payment canceled by the customer','borgun_woocommerce');
					$order->add_order_note($message);
					$redirect = htmlspecialchars_decode($order->get_cancel_order_url());
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}
	}

	/**
	 * Save order metas
	 * @since 1.0.0
	 * @param WC_Order $order The order which is in a transitional state.
	 * @param array $meta Response meta data
	 */
	public function save_order_metas($order, $metas ){
		if( !empty($metas) ){
			foreach ($metas as $key => $meta) {
				if( !empty($meta) ) $order->update_meta_data( '_' . $this->id . '_' . $key, $meta );
			}
			$order->save();

		}
	}

	/**
	 * Process refund.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}
		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Refund failed.', 'borgun_woocommerce' ) );
		}

		$refundid = get_post_meta( $order_id , '_' . $this->id . '_refundid', true );
		if(empty($refundid)){
			$refundid = $order->get_meta('_' . $this->id . '_refundid');
		}

		if ( 'yes' == $this->testmode ) {
			$borgun_adr = self::BORGUN_ENDPOINT_SANDBOX . 'refund.aspx';
		} else {
			$borgun_adr = self::BORGUN_ENDPOINT_LIVE . 'refund.aspx';
		}

		$data = [];
		$data['RefundId'] = $refundid;
		$data['MerchantId'] = $this->merchantid;
		$data['PaymentGatewayId'] = $this->paymentgatewayid;
		$data['Checkhash'] = $this->check_order_refund_hash( $order );
		$data['amount'] = $amount;

		$response = wp_remote_post(
			$borgun_adr,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'headers'     => array(),
				'body'        => $data,
				'sslverify'   => false
			)
		);

		if ( empty( $response['body'] ) ) {
			$message = __( 'Empty Response', 'borgun');
			$order->add_order_note( $message );
			return new WP_Error( 'error', $message );
		} elseif ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			$order->add_order_note( $message );
			return new WP_Error( 'error', $message );
		}

		$body = wp_remote_retrieve_body( $response );
		if( !empty($body) ) {
			parse_str($body, $result);
			if( isset($result['action_code']) && $result['action_code'] == '000' && isset($result['ret']) && $result['ret'] == true ) {
				$message = sprintf( __('Refunded %s %s via Teya', 'borgun_woocommerce' ), $amount, $order->get_currency() );
				$order->add_order_note( $message );
				return true;
			}
			else {
				$message = sprintf( __('Teya error: %s, Amount: %s %s', 'borgun_woocommerce' ), $result['message'], $amount, $order->get_currency() );
				$order->add_order_note( $message );
				return new WP_Error( 'error', $result['message'] );
			}
		}

		return false;
	}
}