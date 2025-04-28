<?php
/**
 * The public-facing functionality of the plugin.
 */
class Thailand_PromptPay_Public {
    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . '../css/style.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . '../js/main.js', array('jquery'), $this->version, false);
    }

    /**
     * Register shortcodes for the plugin.
     */
    public function register_shortcodes() {
        add_shortcode('thailand_promptpay', array($this, 'render_promptpay_form'));
    }

    /**
     * Render the PromptPay form.
     */
    public function render_promptpay_form($atts) {
        ob_start();
        include 'partials/thailand-promptpay-public-display.php';
        return ob_get_clean();
    }
} 