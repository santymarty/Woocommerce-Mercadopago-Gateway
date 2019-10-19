<?php

namespace CRPlugins\MPGatewayCheckout\Gateway;

use CRPlugins\MPGatewayCheckout\Helper\Helper;

defined('ABSPATH') || class_exists('\WC_Payment_Gateway') || exit;

/**
 * Adds our payment method to WooCommerce
 *
 * @param array $gateways
 * @return array
 */
function wc_mp_gateway_add_method($gateways)
{
    $gateways[] = '\CRPlugins\MPGatewayCheckout\Gateway\WC_MP_Gateway';
    return $gateways;
}

/**
 * Our main payment method class
 */
class WC_MP_Gateway extends \WC_Payment_Gateway_CC
{
    /**
     * Default constructor, loads settings and MercadoPago's SDK
     */
    public function __construct()
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Setup general properties.
        $this->setup_properties();
    }

    /**
     * Establishes default settings, and loads IPN Processor
     *
     * @return void
     */
    private function setup_properties()
    {
        $this->id = 'wc_mp_gateway';
        $this->method_title = 'MercadoPago Gateway Checkout';
        $this->method_description = __('Let your customers pay with MercadoPago Gateway', 'wc-mp-gateway-checkout');
        $this->description = $this->get_option('description');
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->countries = 'AR';
        $this->has_fields = false;

        $access_token = Helper::get_option('access_token');
        if (empty($access_token)) {
            $this->enabled = false;
        }
        new IPNProcessor($access_token);
    }

    /**
     * Declares our instance configuration
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable', 'woocommerce'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the customer sees during checkout.', 'wc-mp-gateway-checkout'),
                'default' => __('MercadoPago', 'wc-mp-gateway-checkout')
            ],
            'description' => [
                'title' => __('Message in checkout', 'wc-mp-gateway-checkout'),
                'type' => 'textarea',
                'description' => __('Set your custom message to be shown in the checkout when the customer selects this payment method. Can be empty', 'wc-mp-gateway-checkout'),
                'default' => __('Pay with Mercadopago, up to 12 installments with all credit cards', 'wc-mp-gateway-checkout')
            ]
        ];
    }

    /**
     * Renders our form in the checkout page
     *
     * @return void
     */
    public function form()
    {
        wp_enqueue_script('wc-mp-gateway-mp-sdk');
        wp_enqueue_script('wc-mp-gateway-cc-card');
        wp_enqueue_script('wc-mp-gateway-cc-card-form');
        wp_localize_script('wc-mp-gateway-cc-card-form', 'wc_mp_gateway_settings', [
            'public_key' => Helper::get_option('public_key'),
            'cart_amount' => WC()->cart->get_total('edit'),
            'card_size' => (float) Helper::get_option('card_size'),
            'invalid_card_error_alert' => __('Please check your card details before proceeding', 'wc-mp-gateway-checkout')
        ]);
        wp_enqueue_style('wc-mp-gateway-grid');
        ?>
        <div class="wc-mp-gateway-form-card"></div>
        <div class="wc-mp-gateway-form wc-mp-gateway-wrapper">
            <div class="row">
                <div class="col l12">
                    <input placeholder="Número de Tarjeta" type="text" name="ccNumber" autocomplete="cc-number">
                </div>
            </div>
            <div class="row">
                <div class="col l12">
                    <input placeholder="Nombre y Apellido" type="text" name="ccName" autocomplete="cc-name" data-checkout="cardholderName">
                </div>
            </div>
            <div class="row">
                <div class="col l6">
                    <input placeholder="MM/YY" type="text" name="ccExpiry" autocomplete="cc-exp">
                </div>
                <div class="col l6">
                    <input placeholder="CVC" type="number" name="ccCvc" autocomplete="cc-csc" data-checkout="securityCode">
                </div>
            </div>
            <div class="row">
                <div class="col l5">
                    <select name="docType" data-checkout="docType">
                        <option disabled selected>Documento</option>
                    </select>
                </div>
                <div class="col l7">
                    <input type="text" name="docNumber" data-checkout="docNumber" placeholder="Nro. de Documento" />
                </div>
            </div>
            <div class="row">
                <div class="col l12">
                    <select name="installments" id="installments">
                        <option disabled selected>Seleccionar Cuotas</option>
                    </select>
                    <span class="installments_rate"></span>
                </div>
            </div>
            <input type="hidden" name="hiddenCcNumber" data-checkout="cardNumber">
            <input type="hidden" name="hiddenExpiryMonth" data-checkout="cardExpirationMonth">
            <input type="hidden" name="hiddenExpiryYear" data-checkout="cardExpirationYear">
            <input type="hidden" name="hiddenPaymentMethodId" />
            <input type="hidden" name="hiddenInstallmentsType">
        </div>
<?php

    }

    /**
     * Process a payment when an order is placed
     *
     * @param int $order_id
     * @return array|bool
     * @throws \Exception
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (
            empty($order) ||
            empty($_POST['CcToken']) ||
            empty($_POST['hiddenPaymentMethodId']) ||
            empty($_POST['hiddenInstallmentsType']) ||
            empty($_POST['installments'])
        )
            return false;

        $extradata = [
            'token' => filter_var($_POST['CcToken'], FILTER_SANITIZE_STRING),
            'installments' => filter_var($_POST['installments'], FILTER_SANITIZE_STRING),
            'payment_method_id' => filter_var($_POST['hiddenPaymentMethodId'], FILTER_SANITIZE_STRING),
            'installments_type' => filter_var($_POST['hiddenInstallmentsType'], FILTER_SANITIZE_STRING)
        ];
        $mp_payment = new MP_Payment_Processor($order, $extradata);
        $mp_payment = $mp_payment->process();
        if (empty($mp_payment['status'])) {
            throw new \Exception(__('There was an error in the payment, please try again', 'wc-mp-gateway-checkout'));
        }

        $this->handle_order_status_post_payment($order, $mp_payment['status'], $mp_payment['status_detail'], $mp_payment['id']);
        $res = $this->handle_payment_response($mp_payment['status'], $mp_payment['status_detail'], $this->get_return_url($order));
        WC()->cart->empty_cart();
        return $res;
    }

    /**
     * Takes care of the order status after the payment is sent to MercadoPago
     *
     * @param \WC_Order $order
     * @param string $status
     * @param string|null $status_reason
     * @param integer|null $payment_id
     * @return void
     */
    protected function handle_order_status_post_payment(\WC_Order $order, string $status, $status_reason, $payment_id)
    {
        if ($status === 'approved') {
            $status = Helper::get_option('status_payment_approved', 'wc-completed');
            $order->update_status(
                $status,
                sprintf(__('Mercadopago Gateway - Payment: %s was approved.', 'wc-mp-gateway-checkout'), $payment_id)
            );
        } elseif ($status === 'in_process') {
            $status = Helper::get_option('status_payment_in_process', 'wc-pending');
            $order->update_status(
                $status,
                sprintf(__('Mercadopago Gateway - Payment: %s is pending.', 'wc-mp-gateway-checkout'), $payment_id)
            );
        } else {
            $status = Helper::get_option('status_payment_rejected', 'wc-failed');
            $order->update_status(
                $status,
                sprintf(
                    __('Mercadopago Gateway - Payment: %s was rejected. Reason: %s.', 'wc-mp-gateway-checkout'),
                    $payment_id,
                    self::handle_rejected_payment($status_reason)
                )
            );
        }
    }

    /**
     * Reads and handle the MercadoPago's response when a payment is sent
     *
     * @param string $status
     * @param string|null $status_detail
     * @param string $success_url
     * @return void
     */
    protected function handle_payment_response(string $status, $status_detail, string $success_url)
    {
        if ($status === 'approved' || $status === 'in_process') {
            return [
                'result' => 'success',
                'redirect' => $success_url
            ];
        } elseif ($status === 'rejected') {
            $msg = self::handle_rejected_payment($status_detail);
            throw new \Exception($msg);
        } else {
            throw new \Exception(__('There was an error in the payment, please try again', 'wc-mp-gateway-checkout'));
        }
    }

    /**
     * Translates MercadoPago's errors into human format
     *
     * @param string|null $status_detail
     * @return string
     */
    public static function handle_rejected_payment($status_detail)
    {
        $errors = [
            'cc_rejected_bad_filled_card_number' => __('Check your credit card number', 'wc-mp-gateway-checkout'),
            'cc_rejected_bad_filled_date' => __('Check your credit card expiry date', 'wc-mp-gateway-checkout'),
            'cc_rejected_bad_filled_other' => __('Check your credit card details', 'wc-mp-gateway-checkout'),
            'cc_rejected_bad_filled_security_code' => __('Check your credit card security code', 'wc-mp-gateway-checkout'),
            'cc_rejected_blacklist' => __('We could not process your payment. Please use another card', 'wc-mp-gateway-checkout'),
            'cc_rejected_call_for_authorize' => __('You must authorize this payment with your credit card issuer. The phone number is in the back of your card', 'wc-mp-gateway-checkout'),
            'cc_rejected_card_disabled' => __('Call your credit card issuer to activate your card. The phone number is in the back of your card', 'wc-mp-gateway-checkout'),
            'cc_rejected_card_error' => __('We could not process your payment. Please use another card', 'wc-mp-gateway-checkout'),
            'cc_rejected_duplicated_payment' => __('You already made a payment for this quantity, if you still need to pay, please use another credit card', 'wc-mp-gateway-checkout'),
            'cc_rejected_high_risk' => __('Your payment was rejected. Please use another credit card', 'wc-mp-gateway-checkout'),
            'cc_rejected_insufficient_amount' => __('Your credit card doesn\'t have enough funds to process this payment', 'wc-mp-gateway-checkout'),
            'cc_rejected_invalid_installments' => __('Your credit card doesn\'t process installments', 'wc-mp-gateway-checkout'),
            'cc_rejected_max_attempts' => __('You\'ve reached the limit of payment attempts. Please use another credit card', 'wc-mp-gateway-checkout'),
            'cc_rejected_other_reason' => __('We could not process your payment. Please try again', 'wc-mp-gateway-checkout')
        ];
        return (!empty($errors[$status_detail]) ? $errors[$status_detail] : $errors['cc_rejected_other_reason']);
    }
}
