# Laravel Midtrans Core API

A Laravel package for integrating Midtrans Payment Gateway Core API. This is an unofficial Laravel integration package that wraps the official [Midtrans PHP Library](https://github.com/Midtrans/midtrans-php) into a more Laravel-friendly package with additional features and improvements.

This package provides a seamless integration with Midtrans payment gateway services while maintaining Laravel's best practices and conventions. While unofficial, it strictly follows Midtrans's official API specifications and security guidelines.

## Features

-   Transaction Management (Charge, Status, Cancel, Expire, Refund)
-   Invoice Management (Create, Get, Void)
-   Subscription Management (Create, Get, Enable, Disable, Cancel, Update)
-   Card Management (Register, Token Generation)
-   Request Sanitization
-   Notification Handling
-   3DS Support

## Installation

### Requirements

-   PHP 7.4 or higher
-   Laravel 8.0 or higher

### Via Composer

```bash
composer require aliziodev/laravel-midtrans-core-api\
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=midtrans-config
```

### Configuration

Add these variables to your .env file:

```env
MIDTRANS_SERVER_KEY=your-server-key
MIDTRANS_CLIENT_KEY=your-client-key
MIDTRANS_MERCHANT_ID=your-merchant-id
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true
```

## Usage

### Transaction Methods Create Charge Transaction

```php
use Aliziodev\LaravelMidtrans\Facades\Midtrans;

$params = [
    'transaction_details' => [
        'order_id' => 'ORDER-123',
        'gross_amount' => 10000
    ],
    'customer_details' => [
        'first_name' => 'John',
        'email' => 'john@example.com',
        'phone' => '08111222333'
    ],
    'item_details' => [
        [
            'id' => 'ITEM1',
            'price' => 10000,
            'quantity' => 1,
            'name' => 'Product 1'
        ]
    ]
];

$response = Midtrans::charge($params);
```

Get Transaction Status

```php
$status = Midtrans::status('ORDER-123');
```

Cancel Transaction

```php
$cancel = Midtrans::cancel('ORDER-123');
```

Expire Transaction

```php
$expire = Midtrans::expire('ORDER-123');
```

Refund Transaction

```php
$refundParams = [
    'refund_key' => 'reference1',
    'amount' => 10000,
    'reason' => 'Customer request'
];

$refund = Midtrans::refund('ORDER-123', $refundParams);
```

### Invoice Methods Create Invoice

```php
$invoiceParams = [
    'transaction_details' => [
        'order_id' => 'INV-123',
        'gross_amount' => 10000
    ]
];

$invoice = Midtrans::createInvoice($invoiceParams);
```

Get Invoice Details

```php
$invoice = Midtrans::getInvoice('INV-123');
```

Void Invoice

```php
$void = Midtrans::voidInvoice('INV-123');
```

### Subscription Methods Create Subscription

```php
$subscriptionParams = [
    'name' => 'MONTHLY_2023',
    'amount' => '50000',
    'currency' => 'IDR',
    'payment_type' => 'credit_card',
    'token' => 'CARD-TOKEN',
    'schedule' => [
        'interval' => 1,
        'interval_unit' => 'month',
        'start_time' => '2023-12-01 07:00:00 +0700'
    ]
];

$subscription = Midtrans::createSubscription($subscriptionParams);
```

````
 Get Subscription Details
```php
$subscription = Midtrans::getSubscription('SUBSCRIPTION-123');
````

````
 Enable Subscription
```php
$enable = Midtrans::enableSubscription('SUBSCRIPTION-123');
````

````
 Disable Subscription
```php
$disable = Midtrans::disableSubscription('SUBSCRIPTION-123');
````

````
 Cancel Subscription
```php
$cancel = Midtrans::cancelSubscription('SUBSCRIPTION-123');
````

````
 Update Subscription
```php
$updateParams = [
    'name' => 'MONTHLY_2023_UPDATED',
    'amount' => '75000',
    'currency' => 'IDR',
    'token' => 'NEW-CARD-TOKEN'
];

$update = Midtrans::updateSubscription('SUBSCRIPTION-123', $updateParams);
````

````

### Card Methods Register Card
```php
$card = Midtrans::cardRegister(
    '4811111111111114', // Card Number
    '12',               // Expiry Month
    '2025'             // Expiry Year
);
````

Generate Card Token

```php
$cardParams = [
    'card_number' => '4811111111111114',
    'card_exp_month' => '12',
    'card_exp_year' => '2025',
    'card_cvv' => '123'
];

$token = Midtrans::generateCardToken($cardParams);
```

### Handling Notifications

```php
use Aliziodev\LaravelMidtrans\Services\Notification;

Route::post('midtrans/notification', function(Request $request) {
    $notification = new Notification($request);

    $transaction_status = $notification->getTransactionStatus();
    $order_id = $notification->getOrderId();
    $payment_type = $notification->getPaymentType();

    // Process the notification...
});
```

````

## Error Handling
All methods will throw MidtransException on error. Use try-catch to handle errors:

```php
use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;

try {
    $response = Midtrans::charge($params);
} catch (MidtransException $e) {
    // Handle error
    echo $e->getMessage();
}
````

```

## License
This package is open-sourced software licensed under the MIT license .
```
