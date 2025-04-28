<?php
/**
 * Thailand PromptPay Payment Gateway
 *
 * @package Thailand_PromptPay
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Payment_Gateway')) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Thailand PromptPay: WooCommerce Payment Gateway class not found');
    }
    return;
}

/**
 * Thailand PromptPay Payment Gateway Class
 */
class Thailand_PromptPay_Gateway extends WC_Payment_Gateway {
    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Initializing gateway');
        }

        $this->id                 = 'thailand_promptpay';
        $this->icon               = THAILAND_PROMPTPAY_PLUGIN_URL . 'image/promptpay.jpg';
        $this->has_fields         = false;
        $this->method_title       = __('Thailand PromptPay', 'thailand-promptpay');
        $this->method_description = __('Accept payments via Thailand PromptPay QR code.', 'thailand-promptpay');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title           = $this->get_option('title');
        $this->description     = $this->get_option('description');
        $this->instructions    = $this->get_option('instructions');
        $this->promptpay_id    = $this->get_option('promptpay_id');
        $this->promptpay_id_type = $this->get_option('promptpay_id_type', 'phone');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        
        // Add debug logging
        add_action('admin_notices', array($this, 'check_requirements'));

        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Gateway initialized successfully');
        }
    }

    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility(): void {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    /**
     * Check if all requirements are met
     *
     * @return bool
     */
    public function check_requirements(): bool {
        if (!class_exists('WooCommerce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: WooCommerce not found');
            }
            echo '<div class="notice notice-error"><p>' . 
                esc_html__('Thailand PromptPay requires WooCommerce to be installed and active.', 'thailand-promptpay') . 
                '</p></div>';
            return false;
        }
        return true;
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields(): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Initializing form fields');
        }

        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'thailand-promptpay'),
                'type'    => 'checkbox',
                'label'   => __('Enable Thailand PromptPay', 'thailand-promptpay'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'thailand-promptpay'),
                'type'        => 'text',
                'description' => __('Payment method title that the customer will see on your checkout.', 'thailand-promptpay'),
                'default'     => __('PromptPay', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'thailand-promptpay'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'thailand-promptpay'),
                'default'     => __('Pay using Thailand PromptPay QR code.', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'instructions' => array(
                'title'       => __('Instructions', 'thailand-promptpay'),
                'type'        => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', 'thailand-promptpay'),
                'default'     => __('Please scan the QR code below to complete your payment.', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'promptpay_id_type' => array(
                'title'       => __('PromptPay ID Type', 'thailand-promptpay'),
                'type'        => 'select',
                'description' => __('Select the type of PromptPay ID you want to use.', 'thailand-promptpay'),
                'default'     => 'phone',
                'options'     => array(
                    'phone'  => __('Phone Number', 'thailand-promptpay'),
                    'tax_id' => __('Tax ID', 'thailand-promptpay'),
                ),
                'desc_tip'    => true,
            ),
            'promptpay_id' => array(
                'title'       => __('PromptPay ID', 'thailand-promptpay'),
                'type'        => 'text',
                'description' => __('Your PromptPay ID (phone number or tax ID).', 'thailand-promptpay'),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Form fields initialized');
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Processing payment for order ' . $order_id);
        }

        $order = wc_get_order($order_id);
        
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Invalid order ID ' . $order_id);
            }
            return array(
                'result'   => 'failure',
                'messages' => __('Invalid order.', 'thailand-promptpay')
            );
        }
        
        // Mark as on-hold (we're awaiting the payment)
        $order->update_status('on-hold', __('Awaiting PromptPay payment', 'thailand-promptpay'));
        
        // Reduce stock levels
        wc_reduce_stock_levels($order_id);
        
        // Remove cart
        WC()->cart->empty_cart();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Payment processed successfully for order ' . $order_id);
        }

        // Return thankyou redirect
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    /**
     * Output for the order received page.
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page($order_id) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Displaying thank you page for order ' . $order_id);
        }

        if ($this->instructions) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Invalid order ID ' . $order_id . ' for thank you page');
            }
            return;
        }
        
        $amount = $order->get_total();
        
        // Display QR code
        echo '<div class="thailand-promptpay-qr">';
        echo '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr__('PromptPay QR Code', 'thailand-promptpay') . '">';
        echo '<p class="thailand-promptpay-amount">' . 
            esc_html__('Amount:', 'thailand-promptpay') . ' ' . 
            wp_kses_post($order->get_formatted_order_total()) . 
            '</p>';
        
        // Add business account notice if using Tax ID
        if ($this->promptpay_id_type === 'tax_id') {
            echo '<p class="thailand-promptpay-business-notice">' . 
                esc_html__('Pay to Business Account', 'thailand-promptpay') . 
                '</p>';
        }
        
        echo '</div>';
        
        // Add JavaScript for QR code generation
        $this->generate_inline_js($order);
    }
    
    /**
     * Generate inline JavaScript for QR code generation
     *
     * @param WC_Order $order Order object.
     */
    private function generate_inline_js($order) {
        $amount = $order->get_total();
        $id_type = $this->promptpay_id_type;
        $id_value = $this->promptpay_id;
        
        $js = "
        <script type='text/javascript'>
            jQuery(document).ready(function($) {
                var amount = " . esc_js($amount) . ";
                var idType = '" . esc_js($id_type) . "';
                var idValue = '" . esc_js($id_value) . "';
                
                if (idType === 'phone') {
                    promptpay.generatePayload(idValue, { amount: amount });
                } else if (idType === 'tax_id') {
                    promptpay.generatePayload(idValue, { amount: amount, type: 'tax_id' });
                }
            });
        </script>";
        
        echo $js;
    }

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin Sent to admin.
     * @param bool     $plain_text Email format: plain text or HTML.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
        }
    }
} 