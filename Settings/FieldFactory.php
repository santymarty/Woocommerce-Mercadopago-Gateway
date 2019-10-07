<?php

namespace Macr1408\MPGatewayCheckout\Settings;

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
        return new Field($fields[$slug]);
    }
}