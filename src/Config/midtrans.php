<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    */

    // Midtrans API Credentials
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    // Environment
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    // Notification URL
    'notification_url' => env('MIDTRANS_NOTIF_URL', ''),
    'append_notif_url' => env('MIDTRANS_APPEND_NOTIF_URL', ''),
    'override_notif_url' => env('MIDTRANS_OVERRIDE_NOTIF_URL', ''),

    // Default Currency
    'currency' => env('MIDTRANS_CURRENCY', 'IDR'),
];
