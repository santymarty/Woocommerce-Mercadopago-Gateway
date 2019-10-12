<?php

namespace Macr1408\MPGatewayCheckout\Settings\Fields;

final class NumberField extends Field
{
    protected $data = [
        'name' => '',
        'slug' => '',
        'type' => 'number',
        'description' => '',
        'default' => '',
        'unit' => ''
    ];

    public function __construct(array $args)
    {
        $this->data = wp_parse_args($args, $this->data);
    }

    public function get_unit()
    {
        return $this->data['unit'];
    }
}
