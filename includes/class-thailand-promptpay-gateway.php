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
     * @var string Payment instructions
     */
    public $instructions;

    /**
     * @var string PromptPay ID (phone number or tax ID)
     */
    public $promptpay_id;

    /**
     * @var string PromptPay ID type (phone or tax_id)
     */
    public $promptpay_id_type;

    /**
     * @var string PromptPay Account Name
     */
    public $promptpay_account_name;

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
        $this->promptpay_account_name = $this->get_option('promptpay_account_name');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        // Only show QR on order details if not on thank you page
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_qr_on_order_details'));
        
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
            // Add PromptPay Account Name
            'promptpay_account_name' => array(
                'title'       => __('PromptPay Account Name', 'thailand-promptpay'),
                'type'        => 'text',
                'description' => __('Your PromptPay Account Name.', 'thailand-promptpay'),
                'default'     => '',
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
            $msg = __('Invalid order.', 'thailand-promptpay');
            wc_add_notice($msg, 'error');
            return array(
                'result'   => 'failure',
                'messages' => is_string($msg) && !empty($msg) ? $msg : json_encode($msg)
            );
        }

        // Validate required fields
        if (empty($this->promptpay_id)) {
            $msg = __('PromptPay ID is required.', 'thailand-promptpay');
            wc_add_notice($msg, 'error');
            return array(
                'result'   => 'failure',
                'messages' => is_string($msg) && !empty($msg) ? $msg : json_encode($msg)
            );
        }
        
        // Mark as pending (we're awaiting the payment)
        $order->update_status('pending', __('Awaiting PromptPay payment', 'thailand-promptpay'));
        
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
            error_log('Thailand PromptPay: thankyou_page called for order ' . $order_id);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Invalid order ID ' . $order_id . ' for thank you page');
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Order found, payment method: ' . $order->get_payment_method());
            error_log('Thailand PromptPay: Gateway ID: ' . $this->id);
            error_log('Thailand PromptPay: PromptPay ID: ' . $this->promptpay_id);
        }
        
        // Use the shared QR code rendering method
        $this->render_qr_code($order);
    }
    
    /**
     * Generate a PromptPay payload according to EMVCo standards
     * 
     * @param string $id PromptPay ID (phone number or tax ID)
     * @param float $amount Payment amount
     * @return string EMVCo QR Code payload
     */
    private function generate_promptpay_payload($id, $amount) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Generating payload for ID: ' . $id . ', Amount: ' . $amount);
        }
        
        // Sanitize and format the ID
        $id = preg_replace('/[^0-9]/', '', $id);
        
        if (empty($id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Empty or invalid ID provided');
            }
            return '';
        }
        
        // Format amount
        $amount = number_format((float)$amount, 2, '.', '');
        
        // Build EMVCo QR Code payload
        $payload = '';
        
        // Payload Format Indicator (Tag 00)
        $payload .= '000201';
        
        // Point of Initiation Method (Tag 01) - Static QR
        $payload .= '010211';
        
        // Merchant Account Information (Tag 29) - PromptPay
        $promptpay_data = '0016A0000006770101110';
        $ppt_type_phone = '11300'; // PromptPay AID
        $ppt_type_id = '213';
        $promptpay_data .= $this->promptpay_id_type === 'tax_id' ? $ppt_type_id : $ppt_type_phone; // check if id is more than 10 digits
        // Handle promptpay type phonenumber. if user provide phone number start with 0 should replace with 66
        if($this->promptpay_id_type === 'phone' && $id[0] == '0'){
            $id = '66' . substr($id, 1);
        }
        
        $promptpay_data .= $id;
        $payload .= '29' . sprintf('%02d', strlen($promptpay_data)) . $promptpay_data;
        
        // Country Code (Tag 58) - Thailand
        $payload .= '5802TH';
        
        // Transaction Amount (Tag 54) - Only if amount > 0
        if ($amount > 0) {
            $payload .= '5405'  . $amount;
        }
        
        // Currency Code (Tag 53) - THB (764)
        $payload .= '5303764';
        
        // CRC16 (Tag 63) - Calculate checksum
        $payload .= '6304';
        $crc = $this->calculate_crc16($payload);
        $payload .= strtoupper(sprintf('%04x', $crc));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Generated payload: ' . $payload);
        }
        
        return $payload;
    }
    
    /**
     * Calculate CRC16-CCITT checksum for PromptPay QR code
     * 
     * @param string $data Input data
     * @return int CRC16 checksum
     */
    private function calculate_crc16($data) {
        $crc = 0xFFFF;
        $polynomial = 0x1021;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        
        return $crc;
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
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
    }

    /**
     * Display QR code on order details page (not on thank you page to avoid duplication)
     */
    public function display_qr_on_order_details($order) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: display_qr_on_order_details called');
        }
        
        // Don't display on thank you page as thankyou_page method already handles it
        if (is_wc_endpoint_url('order-received') || is_order_received_page()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Skipping QR display on thank you page to avoid duplication');
            }
            return;
        }
        
        if (is_numeric($order)) {
            $order = wc_get_order($order);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Converted order ID to order object');
            }
        }
        
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: No order found in display_qr_on_order_details');
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Order payment method: ' . $order->get_payment_method());
            error_log('Thailand PromptPay: Gateway ID: ' . $this->id);
        }
        
        if ($order && $order->get_payment_method() === $this->id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Payment method matches, displaying QR on order details page');
            }
            // Display QR code for PromptPay orders on order details pages (like My Account > Orders > View Order)
            $this->render_qr_code($order);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Payment method does not match, skipping QR display');
            }
        }
    }
    
    /**
     * Render the QR code display (shared method to avoid code duplication)
     */
    private function render_qr_code($order) {
        $amount = $order->get_total();
        
        if ($this->instructions) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
        
        echo '<div class="thailand-promptpay-qr">';
        echo '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr__('PromptPay QR Code', 'thailand-promptpay') . '">';

         // Add business account notice if using Tax ID
         if ($this->promptpay_id_type === 'tax_id') {
            echo '<p class="thailand-promptpay-business-notice">' . esc_html__('Pay to Business Account', 'thailand-promptpay') . '</p>';
        }

        // Add PromptPay Account Name
        if ($this->promptpay_account_name) {
            echo '<p class="thailand-promptpay-account-name">' . esc_html__('Account Name:', 'thailand-promptpay') . ' ' . wp_kses_post($this->promptpay_account_name) . '</p>';
        }
        
        echo '<p class="thailand-promptpay-amount">' . esc_html__('Amount:', 'thailand-promptpay') . ' ' . wp_kses_post($order->get_formatted_order_total()) . '</p>';
        
       
        
        // QR code container with unique ID to avoid conflicts
        $container_id = 'promptpay-qr-container-' . $order->get_id();
        echo '<div id="' . esc_attr($container_id) . '"></div>';
        echo '</div>';
        
        // Generate PromptPay payload
        $payload = $this->generate_promptpay_payload($this->promptpay_id, $amount);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Generated payload for order ' . $order->get_id() . ': ' . $payload);
        }
        
        // Output JS to render QR code using jQuery qrcode plugin
        echo "<script>
jQuery(document).ready(function($) {
    
    var qrContainer = $('#" . esc_js($container_id) . "');
   
    
    if (qrContainer.length && typeof $.fn.qrcode === 'function') {
        console.log('Thailand PromptPay: Generating QR code with payload: " . esc_js($payload) . "');
        
        try {
            qrContainer.qrcode({
                text: '" . esc_js($payload) . "',
                width: 256,
                height: 256,
                render: 'canvas',
                background: '#ffffff',
                foreground: '#000000'
            });
            
        } catch (error) {
            console.error('Thailand PromptPay: Error generating QR code:', error);
            qrContainer.html('<p style=\"color: red;\">Error generating QR code. Please contact support.</p>');
        }
    } else {
        // Fallback: show payload as text
        if (qrContainer.length) {
            qrContainer.html('<div style=\"font-family: monospace; word-break: break-all; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;\"><strong>QR Code Data:</strong><br>" . esc_js($payload) . "</div>');
        }
    }
});
</script>";
    }
} 