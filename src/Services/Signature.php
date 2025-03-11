<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;

class Signature
{
    protected $config;
    private const HASH_ALGO = 'sha512';

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function validateTransaction(array $data): bool
    {
        try {
            $requiredFields = ['order_id', 'status_code', 'gross_amount', 'signature_key'];
            $this->validateRequiredFields($data, $requiredFields);

            $this->validateDataTypes($data);

            $expectedSignature = $this->generateTransactionSignature(
                $data['order_id'],
                $data['status_code'],
                $data['gross_amount']
            );

            if (!hash_equals($expectedSignature, $data['signature_key'])) {
                throw new MidtransException('Invalid signature key');
            }

            return true;
        } catch (\Exception $e) {
            throw new MidtransException("Signature validation failed: {$e->getMessage()}", $e->getCode());
        }
    }

    public function generateTransactionSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        try {
            if (empty($orderId) || empty($statusCode) || empty($grossAmount)) {
                throw new MidtransException('All signature parameters must not be empty');
            }

            $serverKey = $this->config->getServerKey();
            if (empty($serverKey)) {
                throw new MidtransException('Server key is not configured');
            }

            $input = $orderId . $statusCode . $grossAmount . $serverKey;
            return hash(self::HASH_ALGO, $input);
        } catch (\Exception $e) {
            throw new MidtransException("Failed to generate signature: {$e->getMessage()}", $e->getCode());
        }
    }

    protected function validateRequiredFields(array $data, array $required): void
    {
        $missingFields = array_filter($required, fn($field) => !isset($data[$field]));
        
        if (!empty($missingFields)) {
            throw new MidtransException(
                'Missing required fields: ' . implode(', ', $missingFields)
            );
        }
    }

    protected function validateDataTypes(array $data): void
    {
        if (!is_string($data['order_id'])) {
            throw new MidtransException('order_id must be a string');
        }

        if (!is_string($data['status_code']) && !is_numeric($data['status_code'])) {
            throw new MidtransException('status_code must be a string or numeric');
        }

        if (!is_string($data['gross_amount']) && !is_numeric($data['gross_amount'])) {
            throw new MidtransException('gross_amount must be a string or numeric');
        }

        if (!is_string($data['signature_key'])) {
            throw new MidtransException('signature_key must be a string');
        }
    }
}