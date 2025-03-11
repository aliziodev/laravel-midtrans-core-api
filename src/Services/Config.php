<?php

namespace Aliziodev\LaravelMidtrans\Services;
use Illuminate\Support\Facades\Config as LaravelConfig;

class Config
{
    private static $instance = null;
    protected $config;

    private function __construct()
    {
        $this->config = LaravelConfig::get('midtrans');
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getBaseUrl()
    {
        return $this->isProduction()
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    public function isProduction()
    {
        return $this->config['is_production'] ?? false;
    }

    public function getServerKey()
    {
        return $this->config['server_key'];
    }

    public function getClientKey()
    {
        return $this->config['client_key'];
    }

    public function getMerchantId()
    {
        return $this->config['merchant_id'];
    }

    public function is3ds()
    {
        return $this->config['is_3ds'] ?? true;
    }

    public function isSanitized()
    {
        return $this->config['is_sanitized'] ?? true;
    }

    public function getNotificationUrl()
    {
        return $this->config['notification_url'];
    }

    public function shouldAppendNotifUrl()
    {
        return $this->config['append_notif_url'] ?? true;
    }

    public function shouldOverrideNotifUrl()
    {
        return $this->config['override_notif_url'] ?? true;
    }
}
