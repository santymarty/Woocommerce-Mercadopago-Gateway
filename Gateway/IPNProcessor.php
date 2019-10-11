<?php

namespace Macr1408\MPGatewayCheckout\Gateway;

use Macr1408\MPGatewayCheckout\Helper\Helper;

class IPNProcessor
{
    private $access_token;

    public function __construct(string $access_token)
    {
        add_action('woocommerce_api_mp-gateway-ipn', [$this, 'check_ipn']);
        $this->access_token = $access_token;
    }

    public function check_ipn()
    {
        $input = file_get_contents('php://input');
        $input = json_decode($input, true);
        if (empty($input) || !$this->validate_input($input))
            wp_die('WooCommerce MercadoPago Gateway invalid IPN request', 'MP Gateway IPN', ['response' => 500]);
    }

    private function validate_input(array $data)
    {
        $data = wp_unslash($data);
        if (empty($data['type']) || $data['type'] !== 'payment' || empty($data['data']['id'])) return false;
        $payment_id = wc_sanitize_order_id($data['data']['id']);
        Helper::log_debug('https://api.mercadopago.com/v1/payments/' . $payment_id . '?access_token=' . $this->access_token);

        /**
         * We use regular http request instead of SDK because the latter doesn't seem to work with payments notifications
         * https://github.com/mercadopago/dx-php/issues/187
         * https://github.com/mercadopago/dx-php/issues/133
         * 
         * There is actually a workaround in #133 but it should be outdated in the incoming SDK Updates, so we skip it.
         */
        $request = wp_safe_remote_get('https://api.mercadopago.com/v1/payments/' . $payment_id . '?access_token=' . $this->access_token);
        if (is_wp_error($request)) return false;

        $body = wp_remote_retrieve_body($request);
        $body = json_decode($body, true);
        if (!empty($body['error'])) return true;

        $this->handle_payment($body);
        return true;
    }

    private function handle_payment(array $payment)
    {
        $reference = (empty($payment['external_reference']) ? null : filter_var($payment['external_reference'], FILTER_SANITIZE_STRING));
        if (empty($reference)) return;
        $prefix = Helper::get_option('external_reference', 'WC-');
        if (substr($reference, 0, 3) !== $prefix) return;
        $order_id = (int) str_replace($prefix, '', $reference);
        $order = wc_get_order($order_id);
        if (empty($order)) {
            Helper::log_error('IPN notified for: ' . $reference . '. But such order_id: ' . $order_id . ' doesn\'t exist');
            return;
        }
        $mp_data = [
            'status' => $payment['status'],
            'status_detail' => $payment['status_detail'],
            'payment_id' => $payment['id']
        ];
        $this->handle_order_status($order, $mp_data);
    }

    private function handle_order_status(\WC_Order $order, array $mp_data)
    {
        if ($mp_data['status'] === 'approved') {
            $status = Helper::get_option('status_payment_approved', 'wc-completed');
            $order->update_status(
                $status,
                sprintf(__('Mercadopago Gateway - Payment: %s was approved ', \WCMPGatewayCheckout::DOMAIN_NAME), $mp_data['payment_id'])
            );
            Helper::log_debug('IPN - Notification checked, order: ' . $order->get_id() . ' checked and updated to approved (' . $status . ')');
        } else if ($mp_data['status'] === 'in_process') {
            $status = Helper::get_option('status_payment_in_process', 'wc-pending');
            $order->update_status(
                $status,
                sprintf(__('Mercadopago Gateway - Payment: %s is pending ', \WCMPGatewayCheckout::DOMAIN_NAME), $mp_data['payment_id'])
            );
            Helper::log_debug('IPN - Notification checked, order: ' . $order->get_id() . ' checked and updated to in process (' . $status . ')');
        } else if ($mp_data['status'] === 'rejected') {
            $status = Helper::get_option('status_payment_rejected', 'wc-failed');
            $reason = WC_MP_Gateway::handle_rejected_payment($mp_data['status_detail']);
            $order->update_status(
                $status,
                sprintf(
                    __('Mercadopago Gateway - Payment: %s was rejected, Reason: ', \WCMPGatewayCheckout::DOMAIN_NAME),
                    $mp_data['payment_id'],
                    $reason
                )
            );
            Helper::log_debug('IPN - Notification checked, order: ' . $order->get_id() . ' checked and updated to rejected (' . $status . '). Reason: ' . $reason);
        } else {
            Helper::log_error('Tried to updated order from IPN with unknown status: ' . $mp_data['status']);
        }
        $order->save();
        return;
    }
}
