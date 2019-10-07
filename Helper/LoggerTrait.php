<?php

namespace Macr1408\MPGatewayCheckout\Helper;

trait LoggerTrait
{
    private static $logger;

    public static function init()
    {
        if (function_exists('wc_get_logger'))
            if (!isset(self::$logger)) self::$logger = wc_get_logger();
    }

    public static function log_info($msg)
    {
        self::$logger->info(wc_print_r($msg, true), ['source' => 'WooCommerce Mercadopago Gateway Checkout']);
    }

    public static function log_error($msg)
    {
        self::$logger->error(wc_print_r($msg, true), ['source' => 'WooCommerce Mercadopago Gateway Checkout']);
    }

    public static function log_warning($msg)
    {
        self::$logger->warning(wc_print_r($msg, true), ['source' => 'WooCommerce Mercadopago Gateway Checkout']);
    }

    public static function log_debug($msg)
    {
        self::$logger->debug(wc_print_r($msg, true), ['source' => 'WooCommerce Mercadopago Gateway Checkout']);
    }
}
