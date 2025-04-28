<?php
/*
Plugin Name: PromptPay
Plugin URI: https://wordpress.org/plugins/promptpay/
Description: PromptPay integration for WordPress with dynamic QR code generation
Version: 2.0.0
Author: Nathachai Thongniran
Author URI: http://jojoee.com/
Text Domain: ppy
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.0
Requires PHP: 8.0
WC requires at least: 8.0
WC tested up to: 8.0
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('PPY_BASE_FILE', plugin_basename(__FILE__));
define('PPY_PLUGIN_NAME', 'PromptPay');
define('PPY_PLUGIN_VERSION', '2.0.0');

class PromptPayFieldKey {
    public function __construct(
        public string $field_promptpay_id = 'field_promptpay_id',
        public string $field_show_promptpay_logo = 'field_show_promptpay_logo',
        public string $field_show_promptpay_id = 'field_show_promptpay_id',
        public string $field_account_name = 'field_account_name',
        public string $field_shop_name = 'field_shop_name'
    ) {}
}

class PromptPay {
    private bool $is_debug = false;
    private string $menu_page = 'promptpay';
    private string $option_group_name = 'ppy_option_group';
    private string $option_field_name = 'ppy_option_field';
    private string $setting_section_id = 'ppy_setting_section_id';
    private PromptPayFieldKey $field_key;
    private array $options;

    public function __construct() {
        $this->field_key = new PromptPayFieldKey();
        $this->options = get_option($this->option_field_name) ?: [];
        $this->set_default_prop();

        // Admin hooks
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('plugin_action_links', [$this, 'plugin_action_links'], 10, 4);

        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('promptpayqr', [$this, 'shortcode_qrcode']);

        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_after_order_table']);
        }
    }

    public function order_details_after_order_table(\WC_Order $order): void {
        if ($order->get_payment_method() === 'bacs') {
            echo $this->shortcode_qrcode(['amount' => $order->get_total()]);
        }
    }

    public function shortcode_qrcode(array $atts = []): string {
        $options = $this->options;
        $custom = shortcode_atts([
            'id' => $options[$this->field_key->field_promptpay_id] ?? '',
            'amount' => 0
        ], $atts);

        return sprintf(
            '<div class="ppy-card"
                data-promptpay-id="%s"
                data-amount="%f"
                data-show-promptpay-logo="%s"
                data-show-promptpay-id="%s"
                data-account-name="%s"
                data-shop-name="%s"
                data-card-style="%s">
            </div>',
            esc_attr($custom['id']),
            floatval($custom['amount']),
            esc_attr($options[$this->field_key->field_show_promptpay_logo] ?? '1'),
            esc_attr($options[$this->field_key->field_show_promptpay_id] ?? '1'),
            esc_attr($options[$this->field_key->field_account_name] ?? ''),
            esc_attr($options[$this->field_key->field_shop_name] ?? ''),
            1
        );
    }

    public function admin_menu(): void {
        add_options_page(
            PPY_PLUGIN_NAME,
            PPY_PLUGIN_NAME,
            'manage_options',
            $this->menu_page,
            [$this, 'admin_page']
        );
    }

    public function admin_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(PPY_PLUGIN_NAME); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group_name);
                do_settings_sections($this->menu_page);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function admin_init(): void {
        register_setting(
            $this->option_group_name,
            $this->option_field_name,
            [$this, 'sanitize']
        );

        add_settings_section(
            $this->setting_section_id,
            'Settings',
            [$this, 'print_section_info'],
            $this->menu_page
        );

        $this->add_settings_fields();
    }

    private function add_settings_fields(): void {
        $fields = [
            $this->field_key->field_promptpay_id => 'PromptPay ID',
            $this->field_key->field_show_promptpay_logo => 'Show PromptPay logo',
            $this->field_key->field_show_promptpay_id => 'Show PromptPay ID',
            $this->field_key->field_account_name => 'Account name',
            $this->field_key->field_shop_name => 'Shop name'
        ];

        foreach ($fields as $field => $title) {
            add_settings_field(
                $field,
                $title,
                [$this, $field . '_callback'],
                $this->menu_page,
                $this->setting_section_id
            );
        }
    }

    public function set_default_prop(): void {
        $defaults = [
            $this->field_key->field_promptpay_id => '',
            $this->field_key->field_show_promptpay_logo => '1',
            $this->field_key->field_show_promptpay_id => '1',
            $this->field_key->field_account_name => '',
            $this->field_key->field_shop_name => ''
        ];

        $this->options = array_merge($defaults, $this->options);
        update_option($this->option_field_name, $this->options);
    }

    public function sanitize(array $input): array {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitized[$key] = sanitize_text_field($value);
        }
        return $sanitized;
    }

    public function print_section_info(): void {
        echo '<p>Configure your PromptPay settings below:</p>';
    }

    public function field_promptpay_id_callback(): void {
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            $this->field_key->field_promptpay_id,
            $this->option_field_name,
            $this->field_key->field_promptpay_id,
            esc_attr($this->options[$this->field_key->field_promptpay_id] ?? '')
        );
    }

    public function field_show_promptpay_logo_callback(): void {
        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
            $this->field_key->field_show_promptpay_logo,
            $this->option_field_name,
            $this->field_key->field_show_promptpay_logo,
            checked(1, $this->options[$this->field_key->field_show_promptpay_logo] ?? 0, false)
        );
    }

    public function field_show_promptpay_id_callback(): void {
        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
            $this->field_key->field_show_promptpay_id,
            $this->option_field_name,
            $this->field_key->field_show_promptpay_id,
            checked(1, $this->options[$this->field_key->field_show_promptpay_id] ?? 0, false)
        );
    }

    public function field_account_name_callback(): void {
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            $this->field_key->field_account_name,
            $this->option_field_name,
            $this->field_key->field_account_name,
            esc_attr($this->options[$this->field_key->field_account_name] ?? '')
        );
    }

    public function field_shop_name_callback(): void {
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            $this->field_key->field_shop_name,
            $this->option_field_name,
            $this->field_key->field_shop_name,
            esc_attr($this->options[$this->field_key->field_shop_name] ?? '')
        );
    }

    public function plugin_action_links(array $links, string $plugin_file): array {
        if (PPY_BASE_FILE === $plugin_file) {
            $settings_link = sprintf(
                '<a href="%s">%s</a>',
                admin_url('options-general.php?page=' . $this->menu_page),
                __('Settings', 'ppy')
            );
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    public function enqueue_scripts(): void {
        wp_enqueue_style(
            'ppy-style',
            plugins_url('css/main.css', __FILE__),
            [],
            PPY_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ppy-script',
            plugins_url('js/main.js', __FILE__),
            ['jquery'],
            PPY_PLUGIN_VERSION,
            true
        );
    }
}

// Initialize the plugin
new PromptPay(); 