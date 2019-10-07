<?php

namespace Macr1408\MPGatewayCheckout\Settings;

use Macr1408\MPGatewayCheckout\Helper\Helper;

defined('ABSPATH') || exit;

class FieldsVerifier
{
    public static function save_settings($post_data)
    {
        $saved = false;
        $settings_fields = Main::get_settings_fields();
        foreach ($settings_fields as $setting) {
            if (empty($post_data[$setting['slug']])) continue;
            $value = $post_data[$setting['slug']];
            $value = filter_var($value, FILTER_SANITIZE_STRING);
            $value = strip_tags($value);
            if (!empty($value)) {
                update_option(\WCMPGatewayCheckout::DOMAIN_NAME . '-' . $setting['slug'], $value);
                $saved = true;
            }
        }
        return $saved;
    }
}