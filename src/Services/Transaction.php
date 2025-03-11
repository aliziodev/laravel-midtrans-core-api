<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Aliziodev\LaravelMidtrans\Utils\Sanitizer;

class Transaction
{
    protected $client;
    protected $signature;
    protected $config;

    public function __construct()
    {
        $this->client = new MidtransClient();
        $this->signature = new Signature();
        $this->config = Config::getInstance();
    }

    public function status(string $id)
    {
        try {
            $response = $this->client->get("/v2/{$id}/status");

            $this->signature->validateTransaction([
                'order_id' => $response->order_id,
                'status_code' => $response->status_code,
                'gross_amount' => $response->gross_amount,
                'signature_key' => $response->signature_key
            ]);

            return $response;
        } catch (\Exception $e) {
            throw new MidtransException("Failed to get transaction status: {$e->getMessage()}", $e->getCode());
        }
    }

    public function statusB2b(string $id)
    {
        try {
            return $this->client->get("/v2/{$id}/status/b2b");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to get B2B transaction status: {$e->getMessage()}", $e->getCode());
        }
    }

    public function approve(string $id)
    {
        try {
            return $this->client->post("/v2/{$id}/approve");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to approve transaction: {$e->getMessage()}", $e->getCode());
        }
    }

    public function cancel(string $id)
    {
        try {
            return $this->client->post("/v2/{$id}/cancel");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to cancel transaction: {$e->getMessage()}", $e->getCode());
        }
    }

    public function expire(string $id)
    {
        try {
            return $this->client->post("/v2/{$id}/expire");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to expire transaction: {$e->getMessage()}", $e->getCode());
        }
    }

    public function refund(string $id, array $params = [])
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest($params);
            }
            return $this->client->post("/v2/{$id}/refund", $params);
        } catch (\Exception $e) {
            throw new MidtransException("Failed to refund transaction: {$e->getMessage()}", $e->getCode());
        }
    }

    public function refundDirect(string $id, array $params = [])
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest($params);
            }
            return $this->client->post("/v2/{$id}/refund/online/direct", $params);
        } catch (\Exception $e) {
            throw new MidtransException("Failed to direct refund transaction: {$e->getMessage()}", $e->getCode());
        }
    }

    public function deny(string $id)
    {
        try {
            return $this->client->post("/v2/{$id}/deny");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to deny transaction: {$e->getMessage()}", $e->getCode());
        }
    }
}
