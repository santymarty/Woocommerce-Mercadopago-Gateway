<?php

use Macr1408\MPGatewayCheckout\Helper\Helper;

/**
 * Plugin Name: WooCommerce Mercadopago Gateway Checkout
 * Plugin URI: https://macr1408.github.io/
 * Description: Integration between Mercadopago and WooCommerce, using smart checkout.
 * Version: 1.0
 * Author: Macr1408
 * Author URI: https://macr1408.github.io/
 * Text Domain: wcmp-gateway-checkout
 * Domain Path: /i18n/languages/
 *
 */

defined('ABSPATH') || exit;

add_action('plugins_loaded', 'WCMPGatewayCheckout::init');
add_action('wp_enqueue_scripts', 'WCMPGatewayCheckout::load_scripts');

class WCMPGatewayCheckout
{
    const PLUGIN_NAME = 'WooCommerce Mercadopago Gateway Checkout';
    const DOMAIN_NAME = 'wcmp-gateway-checkout';
    const MAIN_FILE = __FILE__;
    const MAIN_DIR = __DIR__;

    public static function check_system()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $system = self::check_components();
        if ($system['flag']) {
            deactivate_plugins(plugin_basename(self::MAIN_FILE));
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf(__('<strong>WooCommerce Mercadopago Gateway Checkout</strong> Requires at least %s version %s or greater.</p>', self::DOMAIN_NAME), $system['flag'], $system['version']) . '</p>';
            echo '</div>';
            return false;
        }
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(self::MAIN_FILE));
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . __('WooCommerce must be active before using <strong>WooCommerce Mercadopago Gateway Checkout</strong>', self::DOMAIN_NAME) . '</p>';
            echo '</div>';
            return false;
        }
        return true;
    }

    private static function check_components()
    {

        global $wp_version;
        $flag = $version = false;
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            $flag = 'PHP';
            $version = '7.0';
        } else if (version_compare($wp_version, '4.9', '<')) {
            $flag = 'WordPress';
            $version = '4.9';
        } else if (!defined('WC_VERSION') || version_compare(WC_VERSION, '3.3', '<')) {
            $flag = 'WooCommerce';
            $version = '3.3';
        }
        return ['flag' => $flag, 'version' => $version];
    }

    public static function init()
    {
        if (!self::check_system()) return false;;
        require_once self::MAIN_DIR . '/Hooks.php';
        require_once self::MAIN_DIR . '/Helper/NoticesTrait.php';
        require_once self::MAIN_DIR . '/Helper/LoggerTrait.php';
        require_once self::MAIN_DIR . '/Helper/SettingsTrait.php';
        require_once self::MAIN_DIR . '/Helper/Helper.php';

        require_once self::MAIN_DIR . '/Settings/Main.php';
        require_once self::MAIN_DIR . '/Settings/FieldsPrinter.php';
        require_once self::MAIN_DIR . '/Settings/FieldsVerifier.php';
        require_once self::MAIN_DIR . '/Settings/FieldInterface.php';
        require_once self::MAIN_DIR . '/Settings/FieldFactory.php';
        require_once self::MAIN_DIR . '/Settings/Field.php';
        require_once self::MAIN_DIR . '/Settings/TextField.php';
        require_once self::MAIN_DIR . '/Settings/SelectField.php';

        require_once self::MAIN_DIR . '/Gateway/WC_MP_Gateway.php';
        require_once self::MAIN_DIR . '/Gateway/MP_Payment_Processor.php';
        require_once self::MAIN_DIR . '/Gateway/IPNProcessor.php';
        Helper::init();
    }

    public static function load_scripts()
    {
        wp_register_script('wcmp-gateway-cc-card', plugin_dir_url(self::MAIN_FILE) . 'Assets/js/card.js');
        wp_register_script('wcmp-gateway-cc-card-form', plugin_dir_url(self::MAIN_FILE) . 'Assets/js/checkout-form.js', ['jquery']);
        wp_register_script('wcmp-gateway-mp-sdk', 'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js');
        wp_register_style('wcmp-gateway-grid', plugin_dir_url(self::MAIN_FILE) . 'Assets/css/grids-responsive-min.css');
    }

    public static function create_settings_link(array $links)
    {
        $links[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=wcmp-gateway-checkout-settings')) . '">' . __('Settings', self::DOMAIN_NAME) . '</a>';
        return $links;
    }
}
