=== Payment gateway via Teya SecurePay for WooCommerce ===
Contributors: tacticais
Tags: credit card, gateway, saltPay, teya, woocommerce
Requires at least: 4.4
Tested up to: 6.6.1
WC tested up to: 9.1.4
WC requires at least: 3.2.3
Stable tag: 1.3.33
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Take payments in your WooCommerce store using the Teya SecurePay Gateway

== Description ==

Teya SecurePay is a simple and easily integrated payment solution for small businesses. The payment page enables merchants to process online transactions securely with minimal integration. Credit card data is  handled by Teya in a secure manner. SecurePay supports iFrame solutions as well as redirecting the card holder from the merchant’s website to Teya hosted website.
SecurePay support 3D Secure transactions, both Mastercard SecureCode and Verified by Visa.

This plugin is maintained and supported by Tactica

== Installation ==

Once you have installed WooCommerce on your Wordpress setup, follow these simple steps:

1. Upload the plugin files to the `/wp-content/plugins/borgun-payment-gateway-for-woocommerce` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Insert the merchant ID, Payment Gateway ID and Secret Key in the Checkout settings for the Teya payment plugin and activate it.

== Frequently Asked Questions ==

= Does the plugin support test mode? =

Yes, the plugin supports test mode.

== Screenshots ==

1. The settings panel for the Teya gateway
2. Checkout screen
3. Payment screen

== Changelog ==

= 1.3.33 =
* Add skipreceiptpage option to settings
* Tested with WordPress 6.6.1 and WooCommerce 9.1.4
* Make mbstring optional

= 1.3.32 =
* Add Debug option to settings
* Tested with WordPress 6.5.4 and WooCommerce 8.9.3

= 1.3.31 =
* Added High-performance order storage compatibility
* Tested with WordPress 6.5.3 and WooCommerce 8.9.0

= 1.3.30 =
* Added more currencies and languages support
* Tested with WordPress 6.4.3 and WooCommerce 8.5.2

= 1.3.29 =
* Tested with WordPress 6.4.2
* Fixed warning 'The use statement with non-compound name 'Exception''(PHP 7.4)

= 1.3.28 =
* Tested with WordPress 6.4.1 and WooCommerce 8.3.1
* Payment Method Integration for the Checkout Block

= 1.3.27 =
* Tested with Wordpress 6.4, Woocommerce 8.2.1
* Changed plugin name 'Payment gateway via SaltPay SecurePay for WooCommerce' to 'Payment gateway via Teya SecurePay for WooCommerce'
* Updated template translations strings
* Fixed 'dynamic property declaration' warnings(PHP 8.2+)

= 1.3.26 =
* Tested with Wordpress 6.3, Woocommerce 7.9.0

= 1.3.25 =
* Fixed Cancel order functionality
* Tested with Wordpress 6.2.2, Woocommerce 7.7.2

= 1.3.24 =
* Tested with Wordpress 6.2, Woocommerce 7.5.1

= 1.3.23 =
* Added refund functionality
* Tested with Wordpress 6.0.1, Woocommerce 6.8.1

= 1.3.22 =
* Tested with Wordpress 5.7.1, Woocommerce 5.2.2
* Fixed warning in logs

= 1.3.21 =
* Added borgun line items grouping ability
* Tested with Wordpress 5.7, Woocommerce 5.1.0

= 1.3.20 =
* Added filters to hook Borgun request and response params

= 1.3.19 =
* Updated error handling

= 1.3.18 =
* Fixed error handling when payment returning error.
* Tested with Woocommerce 4.7.1

= 1.3.17 =
* Tested with Wordpress 5.6

= 1.3.16 =
* Added support with old WooCommerce Multilingual plugin versions.
* Tested with Woocommerce 4.7.0

= 1.3.15 =
* Changed plugin name and description to meet Wordpress naming requirements.
* Added user input sanitizing.

= 1.3.14 =
* Added multi currency integration

= 1.3.13 =
* Tested with Wordpress 5.4.2 and Woocommerce 4.2.0

= 1.3.12 =
* Added multilang support
* Added ability to change some Woocommerce checkout texts
* Tested with Wordpress 5.4 and Woocommerce 4.0.1

= 1.3.11 =
* Tested with Wordpress 5.3.2 and Woocommerce 4.0.1

= 1.3.10 =
* Tested with Wordpress 5.3.2 and Woocommerce 3.9.3

= 1.3.9 =
* Fixed order status updating after payment if success or cancel urls were added in payment setttings.
* Tested with Wordpress 5.2.3 and Woocommerce 3.7.0

= 1.3.8 =
* Stripped tags from markup fields
* Tested with Wordpress 5.1 and Woocommerce 3.5.5

= 1.3.7 =
* Tested with Wordpress 5.0.3 and Woocommerce 3.5.4

= 1.3.6 =
* Changed logic to use original order id and added reference parameter

= 1.3.5 =
* Updated to add better compatibility with WooCommerce 3.2.3 and higher
* Fixed discount calculation when prices include tax.

= 1.3.4 =
* Updated notification email usage.

= 1.3.3 =
* Removed duplicate confirmation on order payment

= 1.3.2 =
* Fixed billing email being called directly

= 1.3.1 =
* Fixed billing email being called directly

= 1.3 =
* Added notification email option
* Added tax rounding
* Added system info and removed buyer name

= 1.2.9 =
* Fixed version related issue

= 1.2.8 =
* Fixed fatal error

= 1.2.7 =
* Updated to add better compatibility with WooCommerce 3.0

= 1.2.6 =
* Removed manual status change to be compliant with WooCommerce standard

= 1.2.5 =
* Fixed checkhash issue related to url encoding

= 1.2.3 =
* Changed admin order of fields

= 1.2.2 =
* Adapted rounding to use woocommerce

= 1.2.1 =
* Fixed rounding problem on line total

= 1.0 =
* Initial release

