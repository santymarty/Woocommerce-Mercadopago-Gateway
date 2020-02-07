<?php

namespace CRPlugins\MPGatewayCheckout\Helper;

trait WooCommerceTrait
{
    /**
     * Gets the current cart price through ajax
     *
     * @return json
     */
    public static function ajax_get_cart_price()
    {
        if (wp_verify_nonce($_POST['nonce'], 'wc-mp-gateway-checkout')) {
            wp_send_json_success(WC()->cart->get_total('edit'));
        } else {
            wp_send_json_error('Invalid call', 400);
        }
    }
}
