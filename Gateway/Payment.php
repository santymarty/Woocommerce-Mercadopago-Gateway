<?php

namespace Macr1408\MPGatewayCheckout\Gateway;

use Macr1408\MPGatewayCheckout\Helper\Helper;


class MP_Payment
{
    private $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function create()
    {
        $payment = new \MercadoPago\Payment();

        $items = $this->order->get_items();
        $payer = $this->get_payer();
        $shipment = $this->get_shipment();
        $payment->transaction_amount = $this->order->get_total('edit');
        $payment->processing_modes = ['gateway'];
        $payment->items = $items;
        $payment->payer = $payer;
        $payment->additional_info = $this->get_additional_info($payer, $items, $shipment);
        $payment->back_urls = $this->get_back_urls();
        $payment->external_reference = $this->get_external_reference();
        $payment->save();
        return $payment;
    }

    private function get_items()
    {
        $items = [];
        $order_items = $this->order->get_items();
        foreach ($order_items as $order_item) {
            $item = new \MercadoPago\Item();
            $item->title = $order_item->get_name();
            $item_qty = $order_item->get_quantity();
            $item->quantity = $item_qty;
            $unit_price = round($order_item->get_subtotal('edit') / $item_qty, 2);
            $item->unit_price = $unit_price;
            $items[] = $item;
        }
        return $items;
    }

    private function get_payer()
    {
        $payer = [
            'first_name' => $this->order->get_billing_first_name(),
            'last_name' => $this->order->get_billing_last_name(),
            'email' => $this->order->get_billing_email(),
            'phone' => [
                'number' => $this->order->get_billing_phone()
            ],
            'address' => [
                'zip_code' => $this->order->get_billing_postcode(),
                'street_name' =>
                    $this->order->get_billing_address_1() . ' / ' .
                    $this->order->get_billing_city() . ' ' .
                    $this->order->get_billing_state() . ', ' .
                    $this->order->get_billing_country()
            ]
        ];
        return Helper::convert_array_into_object($payer);
    }

    private function get_shipment()
    {
        $shipment = [];
        return Helper::convert_array_into_object($shipment);
    }

    private function get_back_urls()
    {
        $default_url = WC_MP_Gateway::wrapper_get_return_url($this->order);

        $success_url = Helper::get_option('sucess_url', null);
        $success_url = $success_url ?? $default_url;
        $pending_url = Helper::get_option('pending_url', null);
        $pending_url = $pending_url ?? $default_url;
        $failure_url = Helper::get_option('failure_url', null);
        $failure_url = $failure_url ?? $default_url;

        $urls = ['success' => $success_url, 'failure' => $failure_url, 'pending' => $pending_url];
        return Helper::convert_array_into_object($urls);
    }

    private function get_additional_info($payer, $items, $shipment)
    {
        $additional_info = [
            'items' => $items,
            'payer' => $payer,
            'shipments' => $shipment
        ];
        return Helper::convert_array_into_object($additional_info);
    }

    private function get_external_reference()
    {
        return Helper::get_option('external_reference', 'WC-') . $this->order->get_id();
    }
}
