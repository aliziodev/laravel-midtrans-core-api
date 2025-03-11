<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;

class MidtransClient
{
    protected $config;
    protected $httpClient;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->initializeHttpClient();
    }

    protected function initializeHttpClient()
    {
        $this->httpClient = Http::baseUrl($this->config->getBaseUrl())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->config->getServerKey() . ':')
            ])
            ->timeout(30)
            ->retry(3, 100);

        if (!$this->config->isProduction()) {
            $this->httpClient->withoutVerifying();
        }
    }

    protected function request(string $method, string $endpoint, array $data = [], array $additionalHeaders = [])
    {
        try {
            if (!empty($additionalHeaders)) {
                $this->httpClient->withHeaders($additionalHeaders);
            }

            // For GET requests, don't send data in the body
            if ($method === 'get') {
                $response = $this->httpClient->$method($endpoint);
            } else {
                $response = $this->httpClient->$method($endpoint, $data);
            }
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    protected function handleResponse(Response $response)
    {
        if ($response->successful()) {
            return $response->object();
        }

        $error = $response->json();
        throw new MidtransException(
            $error['error_messages'][0] ?? $response->body(),
            $response->status(),
            $error
        );
    }

    protected function handleException(\Exception $e)
    {
        $message = $e->getMessage();
        $code = $e->getCode() ?: 500;

        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            $message = 'Failed to connect to Midtrans API';
            $code = 503;
        }

        throw new MidtransException($message, $code);
    }

    public function post(string $endpoint, array $data = [], array $headers = [])
    {
        return $this->request('post', $endpoint, $data, $headers);
    }

    public function get(string $endpoint, array $params = [], array $headers = [])
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request('get', $endpoint, [], $headers);
    }

    public function patch(string $endpoint, array $data = [], array $headers = [])
    {
        return $this->request('patch', $endpoint, $data, $headers);
    }

    public function delete(string $endpoint, array $data = [], array $headers = [])
    {
        return $this->request('delete', $endpoint, $data, $headers);
    }
}