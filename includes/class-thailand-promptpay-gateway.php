<?php
/**
 * Thailand PromptPay Payment Gateway
 *
 * @package Thailand_PromptPay
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include PromptPayGenerate class
require_once plugin_dir_path(__FILE__) . 'class-promptpay-generate.php';

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
     * @var string LINE destination URL
     */
    public $line_destination;

    /**
     * @var PromptPayGenerate PromptPay payload generator
     */
    private $PromptPay;

    /**
     * @var string Show QR on pay page
     */
    public $show_on_pay_page;

    /**
     * @var string First line of instructions
     */
    public $instructions_line1;

    /**
     * @var string Second line of instructions
     */
    public $instructions_line2;

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
        $this->method_title       = __('Thailand PromptPay', 'thailand-promptpay-admin');
        $this->method_description = __('Accept payments via Thailand PromptPay QR code.', 'thailand-promptpay-admin');

        // Initialize PromptPay generator
        $this->PromptPay = new PromptPayGenerate();

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title           = $this->get_option('title');
        $this->description     = $this->get_option('description');
        $this->instructions    = $this->get_option('instructions');
        $this->instructions_line1 = $this->get_option('instructions_line1');
        $this->instructions_line2 = $this->get_option('instructions_line2');
        $this->promptpay_id    = $this->get_option('promptpay_id');
        $this->promptpay_id_type = $this->get_option('promptpay_id_type', 'phone');
        $this->promptpay_account_name = $this->get_option('promptpay_account_name');
        $this->line_destination = $this->get_option('line_destination', '');
        $this->show_on_pay_page = $this->get_option('show_on_pay_page', 'no');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        // Only show QR on order details if not on thank you page
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_qr_on_order_details'));
        // Add QR code display on pay order page if enabled
        if ($this->show_on_pay_page === 'yes') {
            add_action('woocommerce_pay_order_before_payment', array($this, 'display_qr_on_pay_order_page'));
        }
        
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
                esc_html__('Thailand PromptPay requires WooCommerce to be installed and active.', 'thailand-promptpay-admin') . 
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
                'title'   => __('Enable/Disable', 'thailand-promptpay-admin'),
                'type'    => 'checkbox',
                'label'   => __('Enable Thailand PromptPay', 'thailand-promptpay-admin'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('Payment method title that the customer will see on your checkout.', 'thailand-promptpay-admin'),
                'default'     => __('PromptPay', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'thailand-promptpay-admin'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'thailand-promptpay-admin'),
                'default'     => __('Pay using Thailand PromptPay QR code.', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'instructions_line1' => array(
                'title'       => __('Instructions Line 1', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('First line of instructions that will be shown under the QR code. at Order Review page.', 'thailand-promptpay-admin'),
                'default'     => __('Please scan the QR code above to complete your payment.', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'instructions_line2' => array(
                'title'       => __('Instructions Line 2', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('Second line of instructions that will be shown under the QR code. at Order Review page.', 'thailand-promptpay-admin'),
                'default'     => __('Send us your payment slip via LINE Account. Once we verify the transfer we will confirm your order by e-mail.', 'thailand-promptpay'),
                'desc_tip'    => true,
            ),
            'show_on_pay_page' => array(
                'title'       => __('Show QR on Pay Page', 'thailand-promptpay-admin'),
                'type'        => 'checkbox',
                'label'       => __('Show QR code on the pay order page', 'thailand-promptpay-admin'),
                'description' => __('Enable this to display the QR code on the pay order page when PromptPay is selected.', 'thailand-promptpay-admin'),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'promptpay_id_type' => array(
                'title'       => __('PromptPay ID Type', 'thailand-promptpay-admin'),
                'type'        => 'select',
                'description' => __('Select the type of PromptPay ID you want to use.', 'thailand-promptpay-admin'),
                'default'     => 'phone',
                'options'     => array(
                    'phone'  => __('Phone Number', 'thailand-promptpay-admin'),
                    'tax_id' => __('Tax ID', 'thailand-promptpay-admin'),
                ),
                'desc_tip'    => true,
            ),
            'promptpay_account_name' => array(
                'title'       => __('PromptPay Account Name', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('Your PromptPay Account Name.', 'thailand-promptpay-admin'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'promptpay_id' => array(
                'title'       => __('PromptPay ID', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('Your PromptPay ID (phone number or tax ID).', 'thailand-promptpay-admin'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'line_destination' => array(
                'title'       => __('LINE Destination', 'thailand-promptpay-admin'),
                'type'        => 'text',
                'description' => __('Your LINE destination URL (e.g., https://line.me/R/ti/p/@yourid). Leave empty to hide LINE instructions.', 'thailand-promptpay-admin'),
                'default'     => '',
                'placeholder' => 'https://line.me/R/ti/p/@yourid',
                'desc_tip'    => true,
            ),
        );
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
            error_log('Thailand PromptPay: Starting thankyou_page render for order ' . $order_id);
            error_log('Thailand PromptPay: Current LINE destination: ' . $this->line_destination);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Invalid order ID ' . $order_id);
            }
            return;
        }

        if ($order->get_payment_method() !== $this->id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Order ' . $order_id . ' is not using PromptPay. Method: ' . $order->get_payment_method());
            }
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Payment method matches, proceeding with render');
        }


        // Render QR code
        $this->render_qr_code($order);

        // Add LINE instruction block only if LINE destination is set
        
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: LINE destination is set, preparing to display instructions');
            }
            
            $line_url = esc_url($this->line_destination);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Escaped LINE URL: ' . $line_url);
            }
            
            // Output LINE instructions directly
            echo '<div class="woocommerce-message thailand-promptpay-line-instr" style="margin-top:1rem; background: #e6f9ec; border: 1px solid #39c24a; color: #222; box-shadow: none;">';
           
            echo esc_html__('We have received your order and it is now awaiting payment confirmation.', 'thailand-promptpay') . '<br>';
            echo '<div style="margin-top: 15px;">';
            echo '<strong>' . esc_html__('Next step', 'thailand-promptpay') . ':</strong><br>';
            echo '1. '.esc_html($this->instructions_line1, 'thailand-promptpay').'<br>';
            echo '2. '.esc_html($this->instructions_line2, 'thailand-promptpay').'<br>';
            if (!empty($this->line_destination)) {
            echo '<a href="' . esc_url($line_url) . '" target="_blank" rel="noopener" style="display: inline-block; background-color: #06C755; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background-color 0.3s ease;">';
            echo '<span style="display: inline-block; vertical-align: middle; margin-right: 8px;">📱</span>';
            echo esc_html__('Open LINE', 'thailand-promptpay');
            echo '</a>';
            }
            echo '</div>';
            echo '</div>';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: LINE instructions HTML output complete');
        }
    }
    
    /**
     * Generate a PromptPay payload using PromptPayGenerate class
     * 
     * @param string $id PromptPay ID (phone number or tax ID)
     * @param float $amount Payment amount
     * @return string EMVCo QR Code payload
     */
    private function generate_promptpay_payload($id, $amount) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: Generating payload for ID: ' . $id . ', Amount: ' . $amount);
        }
        
        if (empty($id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Empty or invalid ID provided');
            }
            return '';
        }
        
        // Format amount - pass null for static QR (no amount), otherwise pass the amount
        $formatted_amount = ($amount > 0) ? (float)$amount : null;
        
        try {
            // Use PromptPayGenerate class to generate payload
            $payload = $this->PromptPay->generatePayload($id, $formatted_amount);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Generated payload: ' . $payload);
            }
            
            return $payload;
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: Error generating payload: ' . $e->getMessage());
            }
            return '';
        }
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

    /**
     * Display QR code on pay order page
     */
    public function display_qr_on_pay_order_page() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Thailand PromptPay: display_qr_on_pay_order_page called');
        }

        // Get the current order
        global $wp;
        $order_id = absint($wp->query_vars['order-pay']);
        $order = wc_get_order($order_id);

        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Thailand PromptPay: No order found in display_qr_on_pay_order_page');
            }
            return;
        }

        // Add container for QR code with initial hidden state
        echo '<div id="thailand-promptpay-qr-container" style="display: none;">';
        $this->render_qr_code($order);
        echo '</div>';

        // Add JavaScript to handle payment method selection
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to check if PromptPay is selected
            function checkPaymentMethod() {
                var selectedMethod = $('input[name="payment_method"]:checked').val();
                if (selectedMethod === '<?php echo esc_js($this->id); ?>') {
                    $('#thailand-promptpay-qr-container').show();
                } else {
                    $('#thailand-promptpay-qr-container').hide();
                }
            }

            // Check on page load
            checkPaymentMethod();

            // Check when payment method changes
            $('form.checkout, form#order_review').on('change', 'input[name="payment_method"]', function() {
                checkPaymentMethod();
            });
        });
        </script>
        <?php
    }
} 