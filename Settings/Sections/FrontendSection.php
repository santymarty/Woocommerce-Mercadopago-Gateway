<?php

namespace CRPlugins\MPGatewayCheckout\Settings\Sections;

/**
 * FrontendSection class
 */
class FrontendSection extends Section implements SectionInterface
{
    private $data = [
        'slug' => 'wc-mp-gateway-checkout-frontend-settings'
    ];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->data['name'] = __('Checkout Configuration', 'wc-mp-gateway-checkout');
        parent::__construct($this->data);
    }

    /**
     * Gets all our fields in this section
     *
     * @return array
     */
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
