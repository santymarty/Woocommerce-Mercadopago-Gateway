<?php

namespace Macr1408\MPGatewayCheckout\Settings;

use Macr1408\MPGatewayCheckout\Helper\Helper;

defined('ABSPATH') || exit;

class Main
{
    public static function get_all_settings()
    {
        $settings = self::get_settings_fields();
        $data = [];
        foreach ($settings as $setting)
            $data[$setting['slug']] = Helper::get_option($setting['slug']);
        return $data;
    }

    public static function get_settings_fields()
    {
        return [
            'public_key' => [
                'name' => 'Public Key',
                'slug' => 'public_key',
                'type' => 'text',
                'description' => __('Your MercadoPago Public Key', \WCMPGatewayCheckout::DOMAIN_NAME)
            ],
            'access_token' => [
                'name' => 'Access Token',
                'slug' => 'access_token',
                'type' => 'text',
                'description' => __('Your MercadoPago Access Token', \WCMPGatewayCheckout::DOMAIN_NAME)
            ],
            'success_url' => [
                'name' => __('Success URL', \WCMPGatewayCheckout::DOMAIN_NAME),
                'slug' => 'success_url',
                'type' => 'text',
                'description' => __('URL where the customer should be redirected after checkout when payment is successful. Leavy empty to use default', \WCMPGatewayCheckout::DOMAIN_NAME),
            ],
            'pending_url' => [
                'name' => __('Pending URL', \WCMPGatewayCheckout::DOMAIN_NAME),
                'slug' => 'pending_url',
                'type' => 'text',
                'description' => __('URL where the customer should be redirected after checkout when payment is pending. Leavy empty to use default', \WCMPGatewayCheckout::DOMAIN_NAME),
            ],
            'failure_url' => [
                'name' => __('Failure URL', \WCMPGatewayCheckout::DOMAIN_NAME),
                'slug' => 'failure_url',
                'type' => 'text',
                'description' => __('URL where the customer should be redirected after checkout when payment fails. Leavy empty to use default', \WCMPGatewayCheckout::DOMAIN_NAME),
            ],
            'store_prefix' => [
                'name' => __('Store prefix', \WCMPGatewayCheckout::DOMAIN_NAME),
                'slug' => 'store_prefix',
                'type' => 'text',
                'description' => __('This is the prefix of the order receipt that your customers will see', \WCMPGatewayCheckout::DOMAIN_NAME),
                'default' => 'WC-'
            ]
        ];
    }

    public static function init_settings()
    {
        register_setting('wcmp-gateway-checkout', 'wcmp-gateway-checkout_options');

        add_settings_section(
            'wcmp-gateway-checkout',
            __('Configuration', \WCMPGatewayCheckout::DOMAIN_NAME),
            '',
            'wcmp-gateway-checkout-settings'
        );

        $settings_fields = self::get_settings_fields();
        foreach ($settings_fields as $setting) {
            add_settings_field(
                'wcmp-gateway-checkout-' . $setting['slug'],
                $setting['name'],
                __class__ . '::print_' . $setting['slug'],
                'wcmp-gateway-checkout-settings',
                'wcmp-gateway-checkout'
            );
        }
    }

    public static function print_public_key()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('public_key');
        if ($field !== false) $field->render();
    }

    public static function print_access_token()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('access_token');
        if ($field !== false) $field->render();
    }

    public static function print_success_url()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('success_url');
        if ($field !== false) $field->render();
    }

    public static function print_pending_url()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('pending_url');
        if ($field !== false) $field->render();
    }

    public static function print_failure_url()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('failure_url');
        if ($field !== false) $field->render();
    }

    public static function print_store_prefix()
    {
        $fFactory = new FieldFactory();
        $field = $fFactory->create('store_prefix');
        if ($field !== false) $field->render();
    }

    public static function add_assets_files($hook)
    {
        if ($hook === 'settings_page_wcmp-gateway-checkout-settings') {
            wp_enqueue_style(\WCMPGatewayCheckout::DOMAIN_NAME . '-admin.css', plugin_dir_url(\WCMPGatewayCheckout::MAIN_FILE) . 'Assets/css/admin.css', [], null);
        }
    }

    public static function create_menu_option()
    {
        add_options_page(
            'MercadoPago Gateway Checkout',
            'MercadoPago Gateway Checkout',
            'manage_options',
            'wcmp-gateway-checkout-settings',
            __class__ . '::settings_page_content'
        );
    }

    public static function settings_page_content()
    {

        if (!current_user_can('manage_options'))
            die('what are you doing here?');

        $nonce = $_REQUEST['_wpnonce'] ?? null;
        if (!empty($_POST) && $nonce && !wp_verify_nonce($nonce, 'wcmp-gateway-checkout-settings-options'))
            die('what are you doing here?');

        $settings_saved = FieldsVerifier::save_settings($_POST);
        if ($settings_saved)
            Helper::add_success(__('Settings saved', \WCMPGatewayCheckout::DOMAIN_NAME), true);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options-general.php?page=wcmp-gateway-checkout-settings" method="post" class="form-wrapper">
                <?php
                settings_fields('wcmp-gateway-checkout-settings');
                do_settings_sections('wcmp-gateway-checkout-settings');
                submit_button(__('Save', \WCMPGatewayCheckout::DOMAIN_NAME));
                ?>
            </form>
        </div>
        <?php

    }
}


