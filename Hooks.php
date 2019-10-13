<?php

defined('ABSPATH') || exit;

// --- Init Hooks
add_action('admin_notices', 'CRPlugins\MPGatewayCheckout\Helper\Helper::check_notices');

// --- Settings
add_filter('plugin_action_links_' . plugin_basename(WCMPGatewayCheckout::MAIN_FILE), 'WCMPGatewayCheckout::create_settings_link');
add_action('admin_init', ['CRPlugins\MPGatewayCheckout\Settings\Main', 'init_settings']);
add_action('admin_menu', 'CRPlugins\MPGatewayCheckout\Settings\Main::create_menu_option');
add_action('admin_enqueue_scripts', 'CRPlugins\MPGatewayCheckout\Settings\Main::add_assets_files');

// --- Payment Method
add_filter('woocommerce_payment_gateways', 'CRPlugins\MPGatewayCheckout\Gateway\WC_MP_Gateway_add_method');
