<?php
/**
 * Thailand PromptPay Blocks Support
 *
 * @package Thailand_PromptPay
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Thailand PromptPay payment method integration for WooCommerce Blocks
 */
class Thailand_PromptPay_Blocks_Support extends AbstractPaymentMethodType {
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'thailand_promptpay';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_thailand_promptpay_settings', []);
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        $payment_gateways_class = WC()->payment_gateways();
        $payment_gateways       = $payment_gateways_class->payment_gateways();

        return isset($payment_gateways['thailand_promptpay']) && $payment_gateways['thailand_promptpay']->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path       = '/js/frontend/blocks.js';
        $script_asset_path = THAILAND_PROMPTPAY_PLUGIN_DIR . 'js/frontend/blocks.asset.php';
        $script_asset      = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => THAILAND_PROMPTPAY_VERSION,
            );

        wp_register_script(
            'thailand-promptpay-blocks-integration',
            THAILAND_PROMPTPAY_PLUGIN_URL . $script_path,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        return array('thailand-promptpay-blocks-integration');
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports'    => $this->get_supported_features(),
            'icon'        => THAILAND_PROMPTPAY_PLUGIN_URL . 'image/promptpay.jpg',
        );
    }

    /**
     * Returns an array of supported features.
     *
     * @return string[]
     */
    public function get_supported_features() {
        return array();
    }
} 