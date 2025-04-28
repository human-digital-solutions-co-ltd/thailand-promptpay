# PromptPay WordPress Plugin

A modern WordPress plugin for PromptPay integration with dynamic QR code generation and WooCommerce support.

## Features

- Dynamic QR code generation
- WooCommerce integration
- Customizable display options
- Mobile-friendly design
- Admin settings panel
- Shortcode support

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- WooCommerce 8.0 or higher (optional)

## Installation

1. Upload the `promptpay` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > PromptPay to configure your settings

## Usage

### Shortcode

Use the shortcode `[promptpayqr]` to display a QR code. You can add an amount with `[promptpayqr amount="100"]`.

### WooCommerce Integration

The plugin automatically integrates with WooCommerce and will display QR codes on order pages when using bank transfer payment.

## Development

This version of the plugin has been modernized with:

- PHP 8.x syntax
- Improved code organization
- Enhanced security measures
- Better WordPress and WooCommerce compatibility
- Simplified build process (no Gulp/Webpack)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Nathachai Thongniran](http://jojoee.com/) 