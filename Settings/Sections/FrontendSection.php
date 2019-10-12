<?php

namespace Macr1408\MPGatewayCheckout\Settings\Sections;

class FrontendSection extends Section implements SectionInterface
{
    private $data = [
        'slug' => 'wc-mp-gateway-checkout-frontend-settings'
    ];

    public function __construct()
    {
        $this->data['name'] = __('Checkout Configuration', 'wc-mp-gateway-checkout');
        parent::__construct($this->data);
    }

    public static function get_fields()
    {
        return [
            'card_size' => [
                'name' => __('Card Size', 'wc-mp-gateway-checkout'),
                'slug' => 'card_size',
                'type' => 'number',
                'unit' => 'px',
                'default' => 300,
                'description' => __('If the card doesn\'t show properly in your checkout, you can adjust its size here', 'wc-mp-gateway-checkout')
            ]
        ];
    }
}
