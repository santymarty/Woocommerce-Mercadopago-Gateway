<?php

namespace CRPlugins\MPGatewayCheckout\Api;

class MPApi extends ApiConnector implements ApiInterface
{
    const BASE_URL = 'https://api.mercadopago.com/v1';

    private $access_token;

    public function __construct(string $access_token = '')
    {
        $this->access_token = $access_token;
    }

    public function get(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        if (!empty($body)) {
            $url .= '?' . http_build_query($body);
        }
        $url = $this->add_params_to_url($url, http_build_query(['access_token' => $this->access_token]));
        $headers[] = ['Content-Type' => 'application/json'];
        return $this->exec('GET', $url, [], $headers);
    }

    public function post(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query(['access_token' => $this->access_token]));
        $headers['Content-Type'] = 'application/json';
        return $this->exec('POST', $url, $body, $headers);
    }

    public function put(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query(['access_token' => $this->access_token]));
        $headers['Content-Type'] = 'application/json';
        return $this->exec('PUT', $url, $body, $headers);
    }

    public function delete(string $endpoint, array $body = [], array $headers = [])
    {
        $url = $this->get_base_url() . $endpoint;
        $url = $this->add_params_to_url($url, http_build_query(['access_token' => $this->access_token]));
        $headers['Content-Type'] = 'application/json';
        return $this->exec('DELETE', $url, $body, $headers);
    }

    public function get_base_url()
    {
        return self::BASE_URL;
    }
}
