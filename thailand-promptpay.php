<?php
/**
 * Plugin Name: Thailand PromptPay
 * Plugin URI: https://github.com/human-digital-solutions-co-ltd/thailand-promptpay
 * Description: A WordPress plugin for integrating PromptPay payment system in Thailand
 * Version: 1.0.0
 * Author: Human Digital Solutions Co., Ltd
 * Author URI: https://github.com/human-digital-solutions-co-ltd
 * License: MIT
 * License URI: https://github.com/human-digital-solutions-co-ltd/thailand-promptpay/blob/main/LICENSE
 * Text Domain: thailand-promptpay
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * WC requires at least: 9.0.0
 * WC tested up to: 9.0.0
 *
 * @package Thailand_PromptPay
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('THAILAND_PROMPTPAY_VERSION', '1.0.0');
define('THAILAND_PROMPTPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THAILAND_PROMPTPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THAILAND_PROMPTPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Declare HPOS compatibility
 */
add_action(
    'before_woocommerce_init',
    function() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }
);

/**
 * Check if WooCommerce is active
 *
 * @return bool True if WooCommerce is active, false otherwise
 */
function thailand_promptpay_check_woocommerce(): bool {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'thailand_promptpay_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * Display WooCommerce missing notice
 */
function thailand_promptpay_woocommerce_missing_notice(): void {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %1$s: Plugin name, %2$s: WooCommerce link */
                esc_html__('%1$s requires %2$s to be installed and active.', 'thailand-promptpay'),
                '<strong>' . esc_html__('Thailand PromptPay', 'thailand-promptpay') . '</strong>',
                '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . esc_html__('WooCommerce', 'thailand-promptpay') . '</a>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Add the gateway to WooCommerce
 *
 * @param array $methods Array of payment gateways.
 * @return array
 */
function add_thailand_promptpay_gateway(array $methods): array {
    $methods[] = 'Thailand_PromptPay_Gateway';
    return $methods;
}

/**
 * Initialize the plugin
 */
function thailand_promptpay_init(): void {
    if (!thailand_promptpay_check_woocommerce()) {
        return;
    }

    // Include the gateway class
    require_once THAILAND_PROMPTPAY_PLUGIN_DIR . 'includes/class-thailand-promptpay-gateway.php';

    // Add the gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', 'add_thailand_promptpay_gateway');

    // Load plugin text domain
    load_plugin_textdomain('thailand-promptpay', false, dirname(THAILAND_PROMPTPAY_PLUGIN_BASENAME) . '/languages/');
}
add_action('plugins_loaded', 'thailand_promptpay_init');

/**
 * Add plugin action links
 *
 * @param array $links Array of plugin action links.
 * @return array
 */
function thailand_promptpay_plugin_action_links(array $links): array {
    $plugin_links = array(
        '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=thailand_promptpay')) . '">' . 
        esc_html__('Settings', 'thailand-promptpay') . '</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . THAILAND_PROMPTPAY_PLUGIN_BASENAME, 'thailand_promptpay_plugin_action_links');

/**
 * Add custom styling
 */
function thailand_promptpay_enqueue_styles(): void {
    if (is_checkout() || is_account_page()) {
        wp_enqueue_style(
            'thailand-promptpay-style',
            THAILAND_PROMPTPAY_PLUGIN_URL . 'css/style.css',
            array(),
            THAILAND_PROMPTPAY_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'thailand_promptpay_enqueue_styles');

/**
 * Add custom scripts
 */
function thailand_promptpay_enqueue_scripts(): void {
    $should_enqueue = false;
    
    // Standard WooCommerce pages
    if (is_checkout() || is_account_page()) {
        $should_enqueue = true;
    }
    
    // Order received/thank you pages
    if (is_wc_endpoint_url('order-received') || is_order_received_page()) {
        $should_enqueue = true;
    }
    
    // View order page in My Account
    if (is_wc_endpoint_url('view-order')) {
        $should_enqueue = true;
    }
    
    // Admin order pages (for testing)
    if (is_admin() && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
        $should_enqueue = true;
    }
    
    // Check if we're on a page that might display PromptPay QR codes
    global $post;
    if ($post && (has_shortcode($post->post_content, 'promptpay') || 
                  has_shortcode($post->post_content, 'thailand_promptpay'))) {
        $should_enqueue = true;
    }
    
    if ($should_enqueue) {
        // Enqueue QRCode library from WooCommerce
        wp_enqueue_script(
            'qrcode-lib',
            WC()->plugin_url() . '/assets/js/jquery-qrcode/jquery.qrcode.min.js',
            array('jquery'),
            WC_VERSION,
            true
        );
        
        wp_enqueue_script(
            'thailand-promptpay-script',
            THAILAND_PROMPTPAY_PLUGIN_URL . 'js/main.js',
            array('jquery', 'qrcode-lib'),
            THAILAND_PROMPTPAY_VERSION,
            true
        );
        
        wp_localize_script(
            'thailand-promptpay-script',
            'thailandPromptPayParams',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('thailand-promptpay-nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'thailand_promptpay_enqueue_scripts'); 