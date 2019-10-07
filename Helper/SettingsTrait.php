<?php

namespace Macr1408\MPGatewayCheckout\Helper;

trait SettingsTrait
{
    public static function get_option($key, $default = false)
    {
        return get_option(\WCMPGatewayCheckout::DOMAIN_NAME . '-' . $key, $default);
    }
}
