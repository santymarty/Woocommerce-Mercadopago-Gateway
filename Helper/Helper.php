<?php

namespace Macr1408\MPGatewayCheckout\Helper;

class Helper
{
    use NoticesTrait;
    use LoggerTrait;
    use SettingsTrait;

    public static function convert_array_into_object(array $array)
    {
        $object = new \stdClass();
        foreach ($array as $key => $value)
            $object->$key = $value;
        return $object;
    }
}
