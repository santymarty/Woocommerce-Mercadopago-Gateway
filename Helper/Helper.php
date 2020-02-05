<?php

namespace CRPlugins\MPGatewayCheckout\Helper;

class Helper
{
    use NoticesTrait;
    use LoggerTrait;
    use SettingsTrait;
    use WooCommerceTrait;

    public static function convert_array_into_object(array $array)
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }
        return $object;
    }

    public static function get_main_folder_url()
    {
        return plugin_dir_url(\WCMPGatewayCheckout::MAIN_FILE);
    }
}
