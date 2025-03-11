<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Contracts\CoreApiInterface;
use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Aliziodev\LaravelMidtrans\Utils\Sanitizer;
use Illuminate\Http\Request;

class CoreApi implements CoreApiInterface
{
    protected $client;
    protected $config;
    protected $invoice;
    protected $transaction;
    protected $cardToken;
    protected $notification = null;
    protected $gopay;

    public function __construct()
    {
        $this->client = new MidtransClient();
        $this->config = Config::getInstance();
        $this->invoice = new Invoice();
        $this->transaction = new Transaction();
        $this->cardToken = new CardToken();
        $this->gopay = new GoPay();
    }

    // Transaction Methods
    public function charge(array $params)
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest($params);
            }

            $headers = $this->prepareNotificationHeaders($params);

            if ($this->config->is3ds() && isset($params['credit_card'])) {
                $params['credit_card']['secure'] = true;
            }

            return $this->client->post('/v2/charge', $params, $headers);
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function status(string $orderId)
    {
        return $this->transaction->status($orderId);
    }

    public function cancel(string $orderId)
    {
        return $this->transaction->cancel($orderId);
    }

    public function expire(string $orderId)
    {
        return $this->transaction->expire($orderId);
    }

    public function refund(string $orderId, array $params = [])
    {
        return $this->transaction->refund($orderId, $params);
    }

    // Invoice Methods
    public function createInvoice(array $params)
    {
        return $this->invoice->create($params);
    }

    public function getInvoice(string $invoiceId)
    {
        return $this->invoice->get($invoiceId);
    }

    public function voidInvoice(string $invoiceId)
    {
        return $this->invoice->void($invoiceId);
    }

    // Subscription Methods
    public function createSubscription(array $params)
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest($params);
            }
            return $this->client->post('/v1/subscriptions', $params);
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function getSubscription(string $subscriptionId)
    {
        try {
            return $this->client->get("/v1/subscriptions/{$subscriptionId}");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function disableSubscription(string $subscriptionId)
    {
        try {
            return $this->client->post("/v1/subscriptions/{$subscriptionId}/disable");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function enableSubscription(string $subscriptionId)
    {
        try {
            return $this->client->post("/v1/subscriptions/{$subscriptionId}/enable");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function cancelSubscription(string $subscriptionId)
    {
        try {
            return $this->client->post("/v1/subscriptions/{$subscriptionId}/cancel");
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function updateSubscription(string $subscriptionId, array $params)
    {
        try {
            if ($this->config->isSanitized()) {
                $params = Sanitizer::sanitizeRequest([
                    'subscription_details' => $params
                ]);
                $params = $params['subscription_details'];
            }

            return $this->client->patch("/v1/subscriptions/{$subscriptionId}", $params);
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    // Card Methods
    public function cardRegister(string $cardNumber, string $expMonth, string $expYear)
    {
        try {
            $params = [
                'card_number' => $cardNumber,
                'card_exp_month' => $expMonth,
                'card_exp_year' => $expYear,
                'client_key' => $this->config->getClientKey()
            ];

            return $this->client->get('/v2/card/register', $params);
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function generateCardToken(array $params)
    {
        return $this->cardToken->generate($params);
    }

    public function getCardBIN(string $cardNumber)
    {
        try {
            // Clean card number and get first 8 digits for BIN
            $bin_number = substr(preg_replace('/\D/', '', $cardNumber), 0, 8);

            // Make request to Midtrans BIN API
            $response = $this->client->get("/v1/bins/{$bin_number}");

            // Return only the data part of the response
            return $response->data ?? $response;
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    public function cardRegisterWithBIN(string $cardNumber, string $expMonth, string $expYear)
    {
        try {
            // First, get the BIN information
            $binInfo = $this->getCardBIN($cardNumber);

            // Then, register the card
            $cardRegistration = $this->cardRegister($cardNumber, $expMonth, $expYear);

            // Combine both responses
            return (object) [
                'card_registration' => $cardRegistration,
                'bin_data' => $binInfo,
            ];
        } catch (\Exception $e) {
            throw new MidtransException($e->getMessage(), $e->getCode());
        }
    }

    // GoPay Account Methods
    public function createPayAccount(array $params)
    {
        return $this->gopay->createAccount($params);
    }

    public function getPayAccount(string $accountId)
    {
        return $this->gopay->getAccount($accountId);
    }

    public function unbindPayAccount(string $accountId)
    {
        return $this->gopay->unbindAccount($accountId);
    }

    // Notification Methods
    public function notification(?Request $request = null)
    {
        if (!$this->notification) {
            $this->notification = new Notification($request ?? Request::capture());
        }
        return $this->notification->getResponse();
    }

    protected function prepareNotificationHeaders(array &$params): array
    {
        $headers = [];

        if (isset($params['override_notif_url'])) {
            $headers['X-Override-Notification'] = $params['override_notif_url'];
            unset($params['override_notif_url']);
        } elseif (isset($params['append_notif_url'])) {
            $headers['X-Append-Notification'] = $params['append_notif_url'];
            unset($params['append_notif_url']);
        } elseif ($notifUrl = $this->config->shouldOverrideNotifUrl()) {
            $headers['X-Override-Notification'] = $notifUrl;
        } elseif ($appendUrl = $this->config->shouldAppendNotifUrl()) {
            $headers['X-Append-Notification'] = $appendUrl;
        }

        return $headers;
    }
}
