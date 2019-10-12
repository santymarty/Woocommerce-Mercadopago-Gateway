<?php

use Macr1408\MPGatewayCheckout\Helper\Helper;

/**
 * Plugin Name: WooCommerce Mercadopago Gateway Checkout
 * Plugin URI: https://macr1408.github.io/
 * Description: Integration between Mercadopago Gateway and WooCommerce, using custom checkout.
 * Version: 1.0
 * Author: Macr1408
 * Author URI: https://macr1408.github.io/
 * Text Domain: wc-mp-gateway-checkout
 * Domain Path: /i18n/languages/
 */

defined('ABSPATH') || exit;

add_action('plugins_loaded', ['WCMPGatewayCheckout', 'init']);
add_action('wp_enqueue_scripts', ['WCMPGatewayCheckout', 'load_scripts']);

class WCMPGatewayCheckout
{
    const PLUGIN_NAME = 'WooCommerce Mercadopago Gateway Checkout';
    const MAIN_FILE = __FILE__;
    const MAIN_DIR = __DIR__;

    public static function check_system()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $system = self::check_components();
        if ($system['flag']) {
            deactivate_plugins(plugin_basename(__FILE__));
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf(__('<strong>WooCommerce Mercadopago Gateway Checkout</strong> Requires at least %s version %s or greater.</p>', 'wc-mp-gateway-checkout'), $system['flag'], $system['version']) . '</p>';
            echo '</div>';
            return false;
        }
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . __('WooCommerce must be active before using <strong>WooCommerce Mercadopago Gateway Checkout</strong>', 'wc-mp-gateway-checkout') . '</p>';
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
        require_once __DIR__ . '/Hooks.php';
        require_once __DIR__ . '/Helper/NoticesTrait.php';
        require_once __DIR__ . '/Helper/LoggerTrait.php';
        require_once __DIR__ . '/Helper/SettingsTrait.php';
        require_once __DIR__ . '/Helper/Helper.php';

        require_once __DIR__ . '/Settings/Main.php';
        require_once __DIR__ . '/Settings/FieldsPrinter.php';
        require_once __DIR__ . '/Settings/FieldsVerifier.php';
        require_once __DIR__ . '/Settings/FieldFactory.php';
        require_once __DIR__ . '/Settings/Fields/FieldInterface.php';
        require_once __DIR__ . '/Settings/Fields/Field.php';
        require_once __DIR__ . '/Settings/Fields/TextField.php';
        require_once __DIR__ . '/Settings/Fields/SelectField.php';
        require_once __DIR__ . '/Settings/Fields/NumberField.php';
        require_once __DIR__ . '/Settings/Sections/SectionInterface.php';
        require_once __DIR__ . '/Settings/Sections/Section.php';
        require_once __DIR__ . '/Settings/Sections/MpSection.php';
        require_once __DIR__ . '/Settings/Sections/FrontendSection.php';

        require_once __DIR__ . '/Gateway/WC_MP_Gateway.php';
        require_once __DIR__ . '/Gateway/MP_Payment_Processor.php';
        require_once __DIR__ . '/Gateway/IPNProcessor.php';
        Helper::init();
        self::load_textdomain();
    }

    public static function load_scripts()
    {
        wp_register_script('wc-mp-gateway-cc-card', plugin_dir_url(__FILE__) . 'Assets/js/card.min.js');
        wp_register_script('wc-mp-gateway-cc-card-form', plugin_dir_url(__FILE__) . 'Assets/js/checkout-form.min.js', ['jquery']);
        wp_register_script('wc-mp-gateway-mp-sdk', 'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js');
        wp_register_style('wc-mp-gateway-grid', plugin_dir_url(__FILE__) . 'Assets/css/grids-responsive-min.css');
        wp_register_style('wc-mp-gateway-settings-css', plugin_dir_url(__FILE__) . 'Assets/css/settings.min.css');
    }

    public static function create_settings_link(array $links)
    {
        $links[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=wc-mp-gateway-checkout-settings')) . '">' . __('Settings', 'wc-mp-gateway-checkout') . '</a>';
        return $links;
    }

    public static function load_textdomain()
    {
        load_plugin_textdomain('wc-mp-gateway-checkout', false, basename(dirname(__FILE__)) . '/i18n/languages');
    }
}
