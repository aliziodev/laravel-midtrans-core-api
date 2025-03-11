<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Aliziodev\LaravelMidtrans\Utils\Sanitizer;

class GoPay
{
    protected $client;
    protected $config;

    public function __construct()
    {
        $this->client = new MidtransClient();
        $this->config = Config::getInstance();
    }

    public function createAccount(array $params)
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest($params);
            }
            
            return $this->client->post('/v2/pay/account', $params);
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function getAccount(string $accountId)
    {
        try {
            return $this->client->get("/v2/pay/account/{$accountId}");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function unbindAccount(string $accountId)
    {
        try {
            return $this->client->post("/v2/pay/account/{$accountId}/unbind");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }
}