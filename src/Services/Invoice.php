<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Carbon\Carbon;
use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Aliziodev\LaravelMidtrans\Utils\Sanitizer;

class Invoice
{
    protected $client;
    protected $config;

    public function __construct()
    {
        $this->client = new MidtransClient();
        $this->config = Config::getInstance();
    }

    public function create(array $params)
    {
        try {
            $payload = $this->buildPayload($params);

            if ($this->config->isSanitized()) {
                $payload = Sanitizer::sanitizeRequest($payload);
            }

            return $this->client->post('/v1/invoices', $payload);
        } catch (\Exception $e) {
            throw new MidtransException("Failed to create invoice: {$e->getMessage()}", $e->getCode());
        }
    }

    public function get(string $invoiceId)
    {
        try {
            if (empty($invoiceId)) {
                throw new MidtransException('Invoice ID cannot be empty');
            }
            return $this->client->get("/v1/invoices/{$invoiceId}");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to get invoice: {$e->getMessage()}", $e->getCode());
        }
    }

    public function void(string $invoiceId)
    {
        try {
            if (empty($invoiceId)) {
                throw new MidtransException('Invoice ID cannot be empty');
            }
            return $this->client->patch("/v1/invoices/{$invoiceId}/void");
        } catch (\Exception $e) {
            throw new MidtransException("Failed to void invoice: {$e->getMessage()}", $e->getCode());
        }
    }

    protected function buildPayload(array $params): array
    {
        try {
            $this->validateRequiredParams($params);

            $now = Carbon::now();
            $dueDate = isset($params['due_date'])
                ? Carbon::parse($params['due_date'])
                : $now->copy()->addDays(1);

            return [
                'order_id' => $params['order_id'] ?? $this->generateOrderId(),
                'invoice_number' => $params['invoice_number'] ?? $this->generateInvoiceNumber(),
                'invoice_date' => $params['invoice_date'] ?? $now->format('Y-m-d H:i:s O'),
                'due_date' => $dueDate->format('Y-m-d H:i:s O'),
                'payment_type' => $params['payment_type'] ?? 'virtual_account',

                'customer_details' => $this->buildCustomerDetails($params['customer'] ?? []),
                'item_details' => $this->buildItemDetails($params['items'] ?? []),
                'virtual_accounts' => $this->buildVirtualAccounts($params['virtual_accounts'] ?? []),
                'amount' => $this->buildAmount($params['amount'] ?? []),

                'notes' => $params['notes'] ?? '',
                'reference' => $params['reference'] ?? '',
            ];
        } catch (\Exception $e) {
            throw new MidtransException("Failed to build payload: {$e->getMessage()}", $e->getCode());
        }
    }

    protected function validateRequiredParams(array $params): void
    {
        if (empty($params['items'])) {
            throw new MidtransException('Items cannot be empty');
        }

        foreach ($params['items'] as $item) {
            if (!isset($item['id']) || !isset($item['price'])) {
                throw new MidtransException('Each item must have id and price');
            }
        }
    }

    protected function buildCustomerDetails(array $customer): array
    {
        return [
            'id' => $customer['id'] ?? null,
            'name' => $customer['name'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
        ];
    }

    protected function buildItemDetails(array $items): array
    {
        return array_map(function ($item) {
            return [
                'item_id' => $item['id'],
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'],
                'name' => $item['name'] ?? $item['description'] ?? 'Item',
            ];
        }, $items);
    }

    protected function buildVirtualAccounts(array $accounts): array
    {
        if (empty($accounts)) {
            return [['bank' => 'bca_va']];
        }

        return array_map(function ($va) {
            return ['bank' => $va['bank']];
        }, $accounts);
    }

    protected function buildAmount(array $amount): array
    {
        return [
            'vat' => $amount['vat'] ?? 0,
            'discount' => $amount['discount'] ?? 0,
            'shipping' => $amount['shipping'] ?? 0,
        ];
    }

    protected function generateOrderId(): string
    {
        return 'INV-' . uniqid() . '-' . time();
    }

    protected function generateInvoiceNumber(): string
    {
        return 'INV/' . date('Ymd') . '/' . uniqid();
    }
}
