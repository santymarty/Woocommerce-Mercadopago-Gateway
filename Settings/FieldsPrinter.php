<?php

namespace Macr1408\MPGatewayCheckout\Settings;

class FieldsPrinter
{

    public static function print(FieldInterface $field)
    {
        if ($field->get_type() === 'text') {
            self::print_text_input($field);
        } else if ($field->get_type() === 'select') {
            self::print_select_input($field);
        }
    }

    private static function print_text_input(TextField $field)
    {
        $previous_config = $field->get_value();
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" />',
            $field->get_slug(),
            $previous_config
        );
        $desc = $field->get_description();
        if (!empty($desc))
            printf('<br> %s', $desc);
    }

    private static function print_select_input(SelectField $field)
    {
        $previous_config = $field->get_value();
        printf(
            '<select id="%1$s" name="%1$s" value="%2$s">',
            $field->get_slug(),
            $previous_config
        );
        $options = $field->get_options();
        foreach ($options as $value => $text) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                ($value === $previous_config ? 'selected' : ''),
                $text
            );
        }
        print('</select>');
        $desc = $field->get_description();
        if (!empty($desc))
            printf('<br> %s', $desc);
    }
}
