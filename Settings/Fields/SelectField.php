<?php

namespace CRPlugins\MPGatewayCheckout\Settings\Fields;

final class SelectField extends Field
{
    protected $data = [
        'name' => '',
        'slug' => '',
        'type' => 'select',
        'description' => '',
        'default' => '',
        'options' => ''
    ];

    public function __construct(array $args)
    {
        $this->data = wp_parse_args($args, $this->data);
    }

    public function get_options()
    {
        return $this->data['options'];
    }
}
