<?php

namespace CRPlugins\MPGatewayCheckout\Settings\Sections;

use CRPlugins\MPGatewayCheckout\Settings\FieldFactory;

/**
 * Base Section class
 */
class Section
{
    private $data = [];

    /**
     * Default constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Adds the section itself into the settings page
     *
     * @return void
     */
    public function add()
    {
        add_settings_section(
            $this->data['slug'],
            $this->data['name'],
            '',
            'wc-mp-gateway-checkout-settings'
        );

        $settings_fields = $this->get_fields();
        foreach ($settings_fields as $setting) {
            add_settings_field(
                'wc-mp-gateway-checkout-' . $setting['slug'],
                $setting['name'],
                function () use ($setting) {
                    $fFactory = new FieldFactory();
                    $field = $fFactory->create($setting['slug']);
                    if ($field !== false) $field->render();
                },
                'wc-mp-gateway-checkout-settings',
                $this->data['slug']
            );
        }
    }
}
