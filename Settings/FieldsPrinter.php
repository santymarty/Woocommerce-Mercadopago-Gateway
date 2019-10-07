<?php

namespace Macr1408\MPGatewayCheckout\Settings;

class FieldsPrinter
{

    public static function print(FieldInterface $field)
    {
        if ($field->get_type() === 'text') {
            self::print_text_input($field);
        }
    }

    private static function print_text_input(FieldInterface $field)
    {
        $previous_config = $field->get_option();
        if (empty($previous_config) && !empty($field->get_default())) {
            $previous_config = $field->get_default();
        }
        echo '<input type="text" id=' . $field->get_slug() . ' name="' . $field->get_slug() . '" value="' . ($previous_config ? $previous_config : '') . '" />';
        $desc = $field->get_description();
        if (!empty($desc)) {
            echo '<br>';
            echo $desc;
        }
    }
}
