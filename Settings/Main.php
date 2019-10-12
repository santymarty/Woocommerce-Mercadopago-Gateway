<?php

namespace Macr1408\MPGatewayCheckout\Settings;

use Macr1408\MPGatewayCheckout\Helper\Helper;
use Macr1408\MPGatewayCheckout\Settings\Sections\FrontendSection;
use Macr1408\MPGatewayCheckout\Settings\Sections\MpSection;

defined('ABSPATH') || exit;

class Main
{
    public static function get_settings_fields()
    {
        $mp_settings_fields = MpSection::get_fields();
        $frontend_settings_fields = FrontendSection::get_fields();
        return array_merge($mp_settings_fields, $frontend_settings_fields);
    }

    public static function get_all_settings()
    {
        $settings = self::get_settings_fields();
        $data = [];
        foreach ($settings as $setting)
            $data[$setting['slug']] = Helper::get_option($setting['slug']);
        return $data;
    }

    public static function init_settings()
    {
        register_setting('wc-mp-gateway-checkout', 'wc-mp-gateway-checkout_options');

        $section = new MpSection();
        $section->add();
        $section = new FrontendSection();
        $section->add();
    }

    public static function add_assets_files(string $hook)
    {
        if ($hook === 'settings_page_wc-mp-gateway-checkout-settings')
            wp_enqueue_style('wc-mp-gateway-settings-css', Helper::get_main_folder_url() . 'Assets/css/settings.css');
    }

    public static function create_menu_option()
    {
        add_options_page(
            'MercadoPago Gateway Checkout',
            'MercadoPago Gateway Checkout',
            'manage_options',
            'wc-mp-gateway-checkout-settings',
            [__CLASS__, 'settings_page_content']
        );
    }

    public static function settings_page_content()
    {

        if (!current_user_can('manage_options'))
            die('what are you doing here?');

        $nonce = $_REQUEST['_wpnonce'] ?? null;
        if (!empty($_POST) && $nonce && !wp_verify_nonce($nonce, 'wc-mp-gateway-checkout-settings-options'))
            die('what are you doing here?');

        $settings_saved = FieldsVerifier::save_settings($_POST);
        if ($settings_saved)
            Helper::add_success(__('Settings saved', 'wc-mp-gateway-checkout'), true);

        $logo_url = Helper::get_main_folder_url() . 'Assets/img/mercadopago-logo.png';
        ?>
        <div class="mp-wrapper wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <img src="<?php echo $logo_url; ?>" class="mp-logo">
            <form action="options-general.php?page=wc-mp-gateway-checkout-settings" method="post" class="form-wrapper">
                <?php
                        settings_fields('wc-mp-gateway-checkout-settings');
                        do_settings_sections('wc-mp-gateway-checkout-settings');
                        submit_button(__('Save', 'wc-mp-gateway-checkout'));
                        ?>
            </form>
        </div>
<?php

    }
}
