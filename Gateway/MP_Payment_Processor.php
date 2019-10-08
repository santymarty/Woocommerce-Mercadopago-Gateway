<?php

namespace Macr1408\MPGatewayCheckout\Gateway;

use Macr1408\MPGatewayCheckout\Helper\Helper;


class MP_Payment_Processor
{
    private $order, $token, $installments, $payment_method_id, $installments_type;

    public function __construct(\WC_Order $order, array $extradata)
    {
        $this->order = $order;
        $this->token = $extradata['token'];
        $this->installments = $extradata['installments'];
        $this->payment_method_id = $extradata['payment_method_id'];
        $this->installments_type = $extradata['installments_type'];
    }

    public function create()
    {
        $payment = new \MercadoPago\Payment();

        $items = $this->get_items();
        $payer = $this->get_payer_main();
        $payer_extra = $this->get_payer_extra();
        $shipment = $this->get_shipment();
        $payment->processing_mode = $this->installments_type;
        $payment->token = $this->token;
        $payment->binary_mode = $this->get_binary_mode();
        $payment->notification_url = $this->notification_url();
        $payment->installments = $this->installments;
        $payment->payment_method_id = $this->payment_method_id;
        $payment->transaction_amount = $this->order->get_total('edit');
        $payment->payer = $payer;
        $payment->additional_info = $this->get_additional_info($payer_extra, $items, $shipment);
        $payment->external_reference = $this->get_external_reference();
        $payment->save();
        return $payment;
    }

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

    protected function get_payer_main()
    {
        $payer = $this->get_payer();
        $payer['email'] = $this->order->get_billing_email();
        return Helper::convert_array_into_object($payer);
    }

    protected function get_payer_extra()
    {
        $payer = $this->get_payer();
        $phone = ['number' => (string)$this->order->get_billing_phone()];
        $phone = Helper::convert_array_into_object($phone);
        $payer['phone'] = $phone;
        return Helper::convert_array_into_object($payer);
    }

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

    protected function get_additional_info($payer, $items, $shipment)
    {
        $additional_info = [
            'items' => $items,
            'payer' => $payer,
            'shipments' => $shipment
        ];
        return Helper::convert_array_into_object($additional_info);
    }

    protected function get_external_reference()
    {
        return Helper::get_option('external_reference', 'WC-') . $this->order->get_id();
    }

    protected function get_binary_mode()
    {
        return Helper::get_option('binary_mode');
    }

    protected function get_notification_url()
    {
        // TODO
        return 'https://';
    }
}
