<?php

namespace CRPlugins\MPGatewayCheckout\Settings;

use CRPlugins\MPGatewayCheckout\Settings\Fields\NumberField;
use CRPlugins\MPGatewayCheckout\Settings\Fields\SelectField;
use CRPlugins\MPGatewayCheckout\Settings\Fields\TextField;

/**
 * This factory creates a FieldInterface
 */
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
        if (empty($fields[$slug])) {
            return false;
        }
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
