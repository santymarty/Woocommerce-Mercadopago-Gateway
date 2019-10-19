<?php

namespace CRPlugins\MPGatewayCheckout\Api;

abstract class ApiConnector
{
    protected function exec(string $method, string $url, array $body, array $headers)
    {
        if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
            $body = json_encode($body);
        }
        $args = [
            'method' => $method,
            'headers' => $headers,
            'body' => $body
        ];
        $request = wp_safe_remote_request($url, $args);
        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        return json_decode($body, true);
    }

    public function get(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        if (!empty($body))
            $url .= '?' . http_build_query($body);
        return $this->exec('GET', $url, [], $headers);
    }

    public function post(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('POST', $url, $body, $headers);
    }

    public function put(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('PUT', $url, $body, $headers);
    }

    public function delete(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        return $this->exec('DELETE', $url, $body, $headers);
    }

    protected function add_params_to_url($url, $params)
    {
        if (strpos($url, '?') !== false) {
            $url .= '&' . $params;
        } else {
            $url .= '?' . $params;
        }
        return $url;
    }

    public abstract function get_base_url();
}
