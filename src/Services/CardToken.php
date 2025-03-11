<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Aliziodev\LaravelMidtrans\Utils\Sanitizer;

class CardToken
{
    protected $client;
    protected $config;

    public function __construct()
    {
        $this->client = new MidtransClient();
        $this->config = Config::getInstance();
    }

    public function generate(array $params)
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeCard($params);
            }

            $queryParams = [
                'client_key' => $this->config->getClientKey(),
                'card_number' => $params['card_number'],
                'card_exp_month' => $params['card_exp_month'],
                'card_exp_year' => $params['card_exp_year'],
                'card_cvv' => $params['card_cvv'] ?? null,
            ];

            return $this->client->get('/v2/token', $queryParams);
        } catch (\Exception $e) {
            throw new MidtransException("Failed to generate card token: {$e->getMessage()}", $e->getCode());
        }
    }
}