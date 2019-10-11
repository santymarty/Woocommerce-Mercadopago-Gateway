<?php

defined('ABSPATH') || exit;

// --- Init Hooks
add_action('admin_notices', 'Macr1408\MPGatewayCheckout\Helper\Helper::check_notices');

// --- Settings
add_filter('plugin_action_links_' . plugin_basename(WCMPGatewayCheckout::MAIN_FILE), 'WCMPGatewayCheckout::create_settings_link');
add_action('admin_init', 'Macr1408\MPGatewayCheckout\Settings\Main::init_settings');
add_action('admin_menu', 'Macr1408\MPGatewayCheckout\Settings\Main::create_menu_option');
add_action('admin_enqueue_scripts', 'Macr1408\MPGatewayCheckout\Settings\Main::add_assets_files');

// --- Payment Method
add_filter('woocommerce_payment_gateways', 'Macr1408\MPGatewayCheckout\Gateway\WC_MP_Gateway_add_method');
