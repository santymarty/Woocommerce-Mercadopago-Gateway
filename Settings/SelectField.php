<?php

namespace Macr1408\MPGatewayCheckout\Settings;

final class SelectField extends Field
{
    protected $data = [
        'name' => '',
        'slug' => '',
        'type' => '',
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