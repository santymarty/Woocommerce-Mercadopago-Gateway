<?php

namespace CRPlugins\MPGatewayCheckout\Gateway;

use CRPlugins\MPGatewayCheckout\Helper\Helper;

/**
 * A wrapper for processing payments with MercadoPago using their PHP Sdk
 */
class MP_Payment_Processor
{
    private $order, $token, $installments, $payment_method_id, $installments_type;

    /**
     * Default constructor
     *
     * @param \WC_Order $order
     * @param array $extradata
     */
    public function __construct(\WC_Order $order, array $extradata)
    {
        $this->order = $order;
        $this->token = $extradata['token'];
        $this->installments = $extradata['installments'];
        $this->payment_method_id = $extradata['payment_method_id'];
        $this->installments_type = $extradata['installments_type'];
    }

    /**
     * Creates a MercadoPago Payment using a WooCommerce Order
     *
     * @return \MercadoPago\Payment
     */
    public function create()
    {
        $payment = new \MercadoPago\Payment();

        $items = $this->get_items();
        $payer = $this->get_payer_main();
        $payer_extra = $this->get_payer_extra();
        $shipment = $this->get_shipment();
        $payment->processing_mode = $this->installments_type;
        $payment->token = $this->token;
        $payment->sponsor_id = $this->get_sponsor_id();
        $payment->binary_mode = $this->get_binary_mode();
        $payment->notification_url = $this->get_notification_url();
        $payment->installments = $this->installments;
        $payment->payment_method_id = $this->payment_method_id;
        $payment->transaction_amount = $this->order->get_total('edit');
        $payment->payer = $payer;
        $payment->additional_info = $this->get_additional_info($payer_extra, $items, $shipment);
        $payment->external_reference = $this->get_external_reference();
        $payment->save();
        return $payment;
    }

    /**
     * Gets an object containing all the items of an order
     *
     * @return mixed
     */
    protected function get_items()
    {
        $items = [];
        $order_items = $this->order->get_items();
        foreach ($order_items as $order_item) {
            $item_qty = $order_item->get_quantity();
            $unit_price = round($order_item->get_subtotal('edit') / $item_qty, 2);
            $product = $order_item->get_product();
            $item = [
                'id' => $product->get_sku(),
                'title' => $order_item->get_name(),
                'description' => $product->get_description(),
                'quantity' => $item_qty,
                'unit_price' => $unit_price
            ];
            $item = Helper::convert_array_into_object($item);
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Gets an object containing all the payer information of an order
     *
     * @return mixed
     */
    protected function get_payer()
    {
        $address = [
            'zip_code' => $this->order->get_billing_postcode(),
            'street_name' =>
            $this->order->get_billing_address_1() . ' / ' .
                $this->order->get_billing_city() . ' ' .
                $this->order->get_billing_state() . ', ' .
                $this->order->get_billing_country()
        ];
        $address = Helper::convert_array_into_object($address);
        $payer = [
            'first_name' => $this->order->get_billing_first_name(),
            'last_name' => $this->order->get_billing_last_name(),
            'address' => $address
        ];
        return $payer;
    }

    /**
     * Gets an object containing a "base"  of the payer information of an order
     *
     * @return mixed
     */
    protected function get_payer_main()
    {
        $payer = $this->get_payer();
        $payer['email'] = $this->order->get_billing_email();
        return Helper::convert_array_into_object($payer);
    }

    /**
     * Gets an object containing all the payer extra information of an order
     *
     * @return mixed
     */
    protected function get_payer_extra()
    {
        $payer = $this->get_payer();
        $phone = ['number' => (string) $this->order->get_billing_phone()];
        $phone = Helper::convert_array_into_object($phone);
        $payer['phone'] = $phone;
        return Helper::convert_array_into_object($payer);
    }

    /**
     * Gets an object containing all the shipment information of an order
     *
     * @return mixed
     */
    protected function get_shipment()
    {
        $shipment = [
            'receiver_address' => [
                'zip_code' => $this->order->get_shipping_postcode(),
                'street_name' =>
                $this->order->get_shipping_address_1() . ' / ' .
                    $this->order->get_shipping_city() . ' ' .
                    $this->order->get_shipping_state() . ', ' .
                    $this->order->get_shipping_country()
            ]
        ];
        return Helper::convert_array_into_object($shipment);
    }

    /**
     * Merges all the aditional info into one single object
     *
     * @param mixed $payer
     * @param mixed $items
     * @param mixed $shipment
     * @return mixed
     */
    protected function get_additional_info($payer, $items, $shipment)
    {
        $additional_info = [
            'items' => $items,
            'payer' => $payer,
            'shipments' => $shipment
        ];
        return Helper::convert_array_into_object($additional_info);
    }

    /**
     * gets external_reference option
     *
     * @return string
     */
    protected function get_external_reference()
    {
        return Helper::get_option('external_reference', 'WC-') . $this->order->get_id();
    }

    /**
     * Gets binary_mode option
     *
     * @return bool
     */
    protected function get_binary_mode()
    {
        $bm = Helper::get_option('binary_mode', true);
        return filter_var($bm, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets notification url for the store
     *
     * @return string
     */
    protected function get_notification_url()
    {
        return get_site_url(null, '/wc-api/mp-gateway-ipn');
    }

    /**
     * Gets sponsor id for the payment
     *
     * @return string
     */
    protected function get_sponsor_id()
    {
        return Helper::get_option('sponsor_id', '');
    }
}
