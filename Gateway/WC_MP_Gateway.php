<?php

namespace Macr1408\MPGatewayCheckout\Gateway;

use Macr1408\MPGatewayCheckout\Settings\Main;
use Macr1408\MPGatewayCheckout\Helper\Helper;


defined('ABSPATH') || exit;
if (!class_exists('\WC_Payment_Gateway')) return;

add_filter('woocommerce_payment_gateways', __NAMESPACE__ . '\WC_MP_Gateway_add_method');
function wc_mp_gateway_add_method($gateways)
{
    $gateways[] = 'Macr1408\MPGatewayCheckout\Gateway\WC_MP_Gateway';
    return $gateways;
}

class WC_MP_Gateway extends \WC_Payment_Gateway_CC
{
    public function __construct()
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Setup general properties.
        $this->setup_properties();
        require_once \WCMPGatewayCheckout::MAIN_DIR . '/vendor/autoload.php';
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
        $access_token = Helper::get_option('access_token');
        if (empty($access_token)) return;
        \MercadoPago\SDK::setAccessToken($access_token);

        $order = wc_get_order($order_id);
        if (empty($order)) return false;
        $mp_preference = new MP_Payment($order);
        $mp_preference = $mp_preference->create();
        Helper::log_debug($_POST);
        //Helper::log_debug($mp_preference);

        return false;

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    public static function wrapper_get_return_url($order = null)
    {
        if ($order) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_page_permalink('checkout'));
        }

        if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
            $return_url = str_replace('http:', 'https:', $return_url);
        }

        return apply_filters('woocommerce_get_return_url', $return_url, $order);
    }
}