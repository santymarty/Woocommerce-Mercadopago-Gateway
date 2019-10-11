<?php

namespace Macr1408\MPGatewayCheckout\Gateway;

use Macr1408\MPGatewayCheckout\Helper\Helper;


defined('ABSPATH') || class_exists('\WC_Payment_Gateway') || exit;

function wc_mp_gateway_add_method($gateways)
{
    $gateways[] = 'Macr1408\MPGatewayCheckout\Gateway\WC_MP_Gateway';
    return $gateways;
}

class WC_MP_Gateway extends \WC_Payment_Gateway_CC
{
    public function __construct()
    {
        require_once \WCMPGatewayCheckout::MAIN_DIR . '/vendor/autoload.php';
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Setup general properties.
        $this->setup_properties();
    }

    private function setup_properties()
    {
        $this->id = 'wc_mp_gateway';
        $this->method_title = 'MercadoPago Gateway Checkout';
        $this->method_description = __('Let your customers pay with MercadoPago and MercadoPago Gateway using the innovative Gateway Checkout', \WCMPGatewayCheckout::DOMAIN_NAME);
        $this->description = $this->get_option('description');
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->countries = 'AR';
        $this->has_fields = false;

        $access_token = Helper::get_option('access_token');
        if (empty($access_token)) $this->enabled = false;
        \MercadoPago\SDK::setAccessToken($access_token);
        new IPNProcessor($access_token);
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the customer sees during checkout.', \WCMPGatewayCheckout::DOMAIN_NAME),
                'default' => __('MercadoPago', \WCMPGatewayCheckout::DOMAIN_NAME)
            ),
            'description' => array(
                'title' => __('Message in checkout', \WCMPGatewayCheckout::DOMAIN_NAME),
                'type' => 'textarea',
                'description' => __('Set your custom message to be shown in the checkout when the customer selects this payment method. Can be empty', \WCMPGatewayCheckout::DOMAIN_NAME),
                'default' => __('Pay with Mercadopago, up to 12 installments with all credit cards', \WCMPGatewayCheckout::DOMAIN_NAME)
            )
        );
    }

    public function form()
    {
        wp_enqueue_script('wcmp-gateway-mp-sdk');
        wp_enqueue_script('wcmp-gateway-cc-card');
        wp_enqueue_script('wcmp-gateway-cc-card-form');
        wp_localize_script('wcmp-gateway-cc-card-form', 'wc_mp_gateway_settings', [
            'public_key' => Helper::get_option('public_key'),
            'cart_amount' => WC()->cart->get_total('edit')
        ]);
        wp_enqueue_style('wcmp-gateway-grid');
        ?>
        <div class="wcmp-gateway-form-card"></div>
        <div class="wcmp-gateway-form wcmp-gateway-wrapper">
            <input placeholder="Card number" type="text" name="ccNumber" autocomplete="cc-number">
            <input placeholder="Full name" type="text" name="ccName" autocomplete="cc-name" data-checkout="cardholderName">
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
                    <input type="text" name="docNumber" data-checkout="docNumber" placeholder="Document Number" />
                </div>
            </div>
            <select name="installments" id="installments">
                <option disabled selected>Seleccionar Cuotas</option>
            </select>
            <input type="hidden" name="hiddenCcNumber" data-checkout="cardNumber">
            <input type="hidden" name="hiddenExpiryMonth" data-checkout="cardExpirationMonth">
            <input type="hidden" name="hiddenExpiryYear" data-checkout="cardExpirationYear">
            <input type="hidden" name="hiddenPaymentMethodId" />
            <input type="hidden" name="hiddenInstallmentsType">
        </div>
<?php

    }

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
        $mp_preference = new MP_Payment_Processor($order, $extradata);
        $mp_preference = $mp_preference->create();

        // If our payment fails for whatever reason, catch it
        if (empty($mp_preference->status)) {
            wc_add_notice(__('There was an error in the payment, please try again', \WCMPGatewayCheckout::DOMAIN_NAME), 'error');
            return false;
        }


        return $this->handle_payment_response($mp_preference->status, $mp_preference->status_detail, $this->get_return_url($order));
    }

    protected function handle_payment_response(string $status, string $status_detail, string $success_url)
    {
        if ($status === 'approved' || $status === 'in_process') {
            return [
                'result' => 'success',
                'redirect' => $success_url
            ];
        } else if ($status === 'rejected') {
            $msg = self::handle_rejected_payment($status_detail);
            wc_add_notice($msg, 'error');
            return false;
        } else {
            wc_add_notice(__('There was an error in the payment, please try again', \WCMPGatewayCheckout::DOMAIN_NAME), 'error');
            return false;
        }
        return [
            'result' => 'failure',
            'messages' => $msg
        ];
    }

    public static function handle_rejected_payment(string $status_detail)
    {
        $errors = [
            'cc_rejected_bad_filled_card_number' => 'Revisa el número de tu tarjeta.',
            'cc_rejected_bad_filled_date' => 'Revisa la fecha de vencimient de tu tarjetao.',
            'cc_rejected_bad_filled_other' => 'Revisa los datos ingresados.',
            'cc_rejected_bad_filled_security_code' => 'Revisa el código de seguridad ingresado.',
            'cc_rejected_blacklist' => 'No pudimos procesar tu pago. Intenta con otra tarjeta',
            'cc_rejected_call_for_authorize' => 'Debes autorizar este pago ante el emisor de tu tarjeta. El teléfono está al dorso de tu tarjeta.',
            'cc_rejected_card_disabled' => 'Llama a tu emisor para que active tu tarjeta. El teléfono está al dorso de tu tarjeta.',
            'cc_rejected_card_error' => 'No pudimos procesar tu pago.',
            'cc_rejected_duplicated_payment' => 'Ya hiciste un pago por ese valor. Si necesitas volver a pagar usa otra tarjeta u otro medio de pago.',
            'cc_rejected_high_risk' => 'Tu pago fue rechazado. Intenta nuevamente con otra tarjeta.',
            'cc_rejected_insufficient_amount' => 'Tu tarjeta no tiene fondos suficientes.',
            'cc_rejected_invalid_installments' => 'La tarjeta usada no procesa pagos cuotas.',
            'cc_rejected_max_attempts' => 'Llegaste al límite de intentos permitidos. Intenta nuevamente con otra tarjeta.',
            'cc_rejected_other_reason' => 'No se pudo realizar el pago, por favor intentá nuevamente.'
        ];
        return (!empty($errors[$status_detail]) ? $errors[$status_detail] : $errors['cc_rejected_other_reason']);
    }
}
