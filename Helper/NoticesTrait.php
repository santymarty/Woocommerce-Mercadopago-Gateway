<?php

namespace CRPlugins\MPGatewayCheckout\Helper;

trait NoticesTrait
{
    public static function check_notices()
    {
        $notices_types = ['error', 'success', 'info'];
        foreach ($notices_types as $type) {
            $notices = get_transient('wc-mp-gateway-checkout-' . $type . '-notices');
            if (empty($notices)) continue;
            foreach ($notices as $notice) {
                echo '<div class="notice notice-' . $type . ' is-dismissible">';
                echo '<p>' . $notice . '</p>';
                echo '</div>';
            }
            delete_transient('wc-mp-gateway-checkout-' . $type . '-notices');
        }
    }

    private static function add_notice(string $type, string $msg, bool $do_action = false)
    {
        $notices = get_transient('wc-mp-gateway-checkout-' . $type . '-notices');
        if (!empty($notices)) {
            $notices[] = $msg;
        } else {
            $notices = [$msg];
        }
        set_transient('wc-mp-gateway-checkout-' . $type . '-notices', $notices, 60);
        if ($do_action) do_action('admin_notices');
    }

    public static function add_error(string $msg, bool $do_action = false)
    {
        self::add_notice('error', $msg, $do_action);
    }

    public static function add_success(string $msg, bool $do_action = false)
    {
        self::add_notice('success', $msg, $do_action);
    }

    public static function add_info(string $msg, bool $do_action = false)
    {
        self::add_notice('info', $msg, $do_action);
    }
}
