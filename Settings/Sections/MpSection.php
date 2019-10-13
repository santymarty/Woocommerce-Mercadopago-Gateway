<?php

namespace CRPlugins\MPGatewayCheckout\Settings\Sections;

/**
 * MpSection class
 */
class MpSection extends Section implements SectionInterface
{
    private $data = [
        'slug' => 'wc-mp-gateway-checkout-mp-config'
    ];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->data['name'] = __('MercadoPago Configuration', 'wc-mp-gateway-checkout');
        parent::__construct($this->data);
    }

    /**
     * Gets all our fields in this section
     *
     * @return array
     */
    public static function get_fields()
    {
        $order_statuses = wc_get_order_statuses();
        return [
            'public_key' => [
                'name' => 'Public Key',
                'slug' => 'public_key',
                'type' => 'text',
                'description' => __('Your MercadoPago Public Key', 'wc-mp-gateway-checkout')
            ],
            'access_token' => [
                'name' => 'Access Token',
                'slug' => 'access_token',
                'type' => 'text',
                'description' => __('Your MercadoPago Access Token', 'wc-mp-gateway-checkout')
            ],
            'binary_mode' => [
                'name' => __('Set MercadoPago in binary mode', 'wc-mp-gateway-checkout'),
                'slug' => 'binary_mode',
                'type' => 'select',
                'description' => __('When this is activated, the payment goes approved or failed, it can\'t go to the pending state', 'wc-mp-gateway-checkout'),
                'default' => 'true',
                'options' => ['true' => 'Si', 'false' => 'No']
            ],
            'store_prefix' => [
                'name' => __('Store prefix', 'wc-mp-gateway-checkout'),
                'slug' => 'store_prefix',
                'type' => 'text',
                'description' => __('This is the prefix of the order receipt that your customers will see', 'wc-mp-gateway-checkout'),
                'default' => 'WC-'
            ],
            'status_payment_approved' => [
                'name' => __('Status when the payment is approved', 'wc-mp-gateway-checkout'),
                'slug' => 'status_payment_approved',
                'type' => 'select',
                'description' => __('This is the status the order will have when the payment is approved', 'wc-mp-gateway-checkout'),
                'default' => 'wc-processing',
                'options' => $order_statuses
            ],
            'status_payment_in_process' => [
                'name' => __('Status when the payment is in process', 'wc-mp-gateway-checkout'),
                'slug' => 'status_payment_in_process',
                'type' => 'select',
                'description' => __('This is the status the order will have when the payment is in process', 'wc-mp-gateway-checkout'),
                'default' => 'wc-pending',
                'options' => $order_statuses
            ],
            'status_payment_rejected' => [
                'name' => __('Status when the payment is rejected', 'wc-mp-gateway-checkout'),
                'slug' => 'status_payment_rejected',
                'type' => 'select',
                'description' => __('This is the status the order will have when the payment is rejected', 'wc-mp-gateway-checkout'),
                'default' => 'wc-failed',
                'options' => $order_statuses
            ],
        ];
    }
}
