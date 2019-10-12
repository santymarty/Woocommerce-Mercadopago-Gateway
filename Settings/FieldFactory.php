<?php

namespace Macr1408\MPGatewayCheckout\Settings;

use Macr1408\MPGatewayCheckout\Settings\Fields\NumberField;
use Macr1408\MPGatewayCheckout\Settings\Fields\SelectField;
use Macr1408\MPGatewayCheckout\Settings\Fields\TextField;

class FieldFactory
{

    /**
     * Creates a Field
     *
     * @param string $slug
     * @return FieldInterface|false
     */
    public function create(string $slug)
    {
        $fields = Main::get_settings_fields();
        if (empty($fields[$slug])) return false;
        switch ($fields[$slug]['type']) {
            case 'text':
                $field = new TextField($fields[$slug]);
                break;
            case 'select':
                $field = new SelectField($fields[$slug]);
                break;
            case 'number':
                $field = new NumberField($fields[$slug]);
                break;
            default:
                $field = false;
        }
        return $field;
    }
}
