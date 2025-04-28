=== PromptPay ===
Contributors: jojoee
Donate link: https://wordpress.org/plugins/promptpay/
Tags: promptpay, qrcode, payment, thailand, thai, bank, transfer, mobile, banking, woocommerce
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

PromptPay integration for WordPress with dynamic QR code generation.

== Description ==

PromptPay integration for WordPress with dynamic QR code generation. This plugin allows you to easily add PromptPay QR codes to your WordPress site, with WooCommerce integration support.

= Features =

* Dynamic QR code generation
* WooCommerce integration
* Customizable display options
* Mobile-friendly design
* Admin settings panel
* Shortcode support

= Requirements =

* WordPress 6.0 or higher
* PHP 8.0 or higher
* WooCommerce 8.0 or higher (optional)

== Installation ==

1. Upload the `promptpay` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > PromptPay to configure your settings

== Frequently Asked Questions ==

= What is PromptPay? =

PromptPay is a Thai payment system that allows users to make payments using QR codes.

= How do I use the shortcode? =

Use the shortcode `[promptpayqr]` to display a QR code. You can add an amount with `[promptpayqr amount="100"]`.

= Is this plugin compatible with WooCommerce? =

Yes, this plugin integrates with WooCommerce and will automatically display QR codes on order pages when using bank transfer payment.

== Screenshots ==

1. Admin settings page
2. Frontend display example

== Changelog ==

= 2.0.0 =
* Modernized codebase with PHP 8.x syntax
* Added dynamic QR code generation
* Improved WooCommerce integration
* Enhanced admin settings interface
* Updated WordPress and WooCommerce compatibility
* Removed build tools (Gulp/Webpack) in favor of simpler structure

= 1.2.2 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
This version requires PHP 8.0 or higher and WordPress 6.0 or higher. Please ensure your server meets these requirements before upgrading. 