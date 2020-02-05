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
        wp_send_json_success(WC()->cart->get_total('edit'));
    }
}
