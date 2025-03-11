<?php

namespace Aliziodev\LaravelMidtrans\Services;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;
use Illuminate\Http\Request;

class Notification
{
    protected $response;
    protected $transaction;
    protected $signature;

    public function __construct(Request $request)
    {
        $this->signature = new Signature();
        $this->transaction = new Transaction();
        $this->processNotification($request);
    }

    protected function processNotification($request)
    {
        try {
            $this->response = json_decode($request->getContent());
            
            if (empty($this->response)) {
                throw new MidtransException('Invalid notification payload');
            }
            $this->signature->validateTransaction((array) $this->response);

        } catch (\Exception $e) {
            throw new MidtransException('Failed to process notification: ' . $e->getMessage());
        }
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function __get($name)
    {
        return $this->response->$name ?? null;
    }
}