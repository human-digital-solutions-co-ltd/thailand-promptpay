<?php
/**
 * The admin-specific functionality of the plugin.
 */
class Thailand_PromptPay_Admin {
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
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . '../css/main.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . '../js/main.js', array('jquery'), $this->version, false);
    }

    /**
     * Add menu items to the admin menu.
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Thailand PromptPay Settings',
            'Thailand PromptPay',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-money-alt',
            56
        );
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        include_once 'partials/thailand-promptpay-admin-display.php';
    }
} 