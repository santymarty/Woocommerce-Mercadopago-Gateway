<?php

namespace CRPlugins\MPGatewayCheckout\Settings;

use CRPlugins\MPGatewayCheckout\Settings\Fields\FieldInterface;
use CRPlugins\MPGatewayCheckout\Settings\Fields\NumberField;
use CRPlugins\MPGatewayCheckout\Settings\Fields\SelectField;
use CRPlugins\MPGatewayCheckout\Settings\Fields\TextField;

/**
 * Class that will print all our settings fields
 */
class FieldsPrinter
{

    /**
     * Pints a Field
     *
     * @param FieldInterface $field
     * @return void
     */
    public static function print(FieldInterface $field)
    {
        if ($field->get_type() === 'text') {
            self::print_text_input($field);
        } elseif ($field->get_type() === 'select') {
            self::print_select_input($field);
        } elseif ($field->get_type() === 'number') {
            self::print_number_input($field);
        }
    }

    /**
     * Prints a Textfield
     *
     * @param TextField $field
     * @return void
     */
    private static function print_text_input(TextField $field)
    {
        $previous_config = $field->get_value();
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" />',
            $field->get_slug(),
            $previous_config
        );
        $desc = $field->get_description();
        if (!empty($desc)) {
            printf('<span class="field-description">%s<span>', $desc);
        }
    }

    /**
     * Prints a NumberField
     *
     * @param NumberField $field
     * @return void
     */
    private static function print_number_input(NumberField $field)
    {
        $previous_config = $field->get_value();
        printf(
            '<input type="number" id="%1$s" name="%1$s" value="%2$s" />',
            $field->get_slug(),
            $previous_config
        );
        $unit = $field->get_unit();
        if (!empty($unit)) {
            printf('<span class="field-unit">%s<span>', $unit);
        }
        $desc = $field->get_description();
        if (!empty($desc)) {
            printf('<span class="field-description">%s<span>', $desc);
        }
    }

    /**
     * Prints a SelectField
     *
     * @param SelectField $field
     * @return void
     */
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
        if (!empty($desc)) {
            printf('<span class="field-description">%s<span>', $desc);
        }
    }
}
