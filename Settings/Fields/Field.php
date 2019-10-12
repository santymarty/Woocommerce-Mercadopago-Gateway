<?php

namespace Macr1408\MPGatewayCheckout\Settings\Fields;

use Macr1408\MPGatewayCheckout\Settings\FieldsPrinter;

class Field implements FieldInterface
{
    protected $data = [
        'name' => '',
        'slug' => '',
        'type' => '',
        'description' => '',
        'default' => '',
    ];

    public function __construct(array $args)
    {
        $this->data = wp_parse_args($args, $this->data);
    }

    public function get_name()
    {
        return $this->data['name'];
    }

    public function get_slug()
    {
        return $this->data['slug'];
    }

    public function get_type()
    {
        return $this->data['type'];
    }

    public function get_default()
    {
        return $this->data['default'];
    }

    public function get_description()
    {
        return $this->data['description'];
    }

    public function get_value()
    {
        return get_option('wc-mp-gateway-checkout-' . $this->data['slug'], $this->data['default']);
    }

    public function render()
    {
        return FieldsPrinter::print($this);
    }
}
