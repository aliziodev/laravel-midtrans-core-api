<?php

namespace Aliziodev\LaravelMidtrans\Utils;

use Aliziodev\LaravelMidtrans\Exceptions\MidtransException;

class Sanitizer
{
    private const MAX_STRING_LENGTH = 255;
    private const MAX_ITEM_LENGTH = 50;
    private const MAX_PHONE_LENGTH = 19;
    private const MAX_POSTAL_LENGTH = 10;
    private const MAX_COUNTRY_LENGTH = 3;

    public static function sanitizeRequest(array $payload): array
    {
        $sanitized = $payload;

        if (isset($payload['item_details'])) {
            $sanitized['item_details'] = self::sanitizeItems($payload['item_details']);
        }

        if (isset($payload['customer_details'])) {
            $sanitized['customer_details'] = self::sanitizeCustomer($payload['customer_details']);
        }

        if (isset($payload['transaction_details'])) {
            $sanitized['transaction_details'] = self::sanitizeTransactionDetails($payload['transaction_details']);
        }

        return $sanitized;
    }

    public static function sanitizeCard(array $card): array
    {
        self::validateCardRequiredFields($card);

        return [
            'card_number' => self::sanitizeCardNumber($card['card_number']),
            'card_exp_month' => self::sanitizeCardExpMonth($card['card_exp_month']),
            'card_exp_year' => self::sanitizeCardExpYear($card['card_exp_year']),
            'card_cvv' => isset($card['card_cvv']) ? self::sanitizeCardCvv($card['card_cvv']) : null,
        ];
    }

    private static function sanitizeItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => self::sanitizeString($item['id'] ?? '', self::MAX_ITEM_LENGTH),
                'name' => self::sanitizeString($item['name'] ?? '', self::MAX_ITEM_LENGTH),
                'price' => self::sanitizeAmount($item['price'] ?? 0),
                'quantity' => self::sanitizeQuantity($item['quantity'] ?? 1),
                'category' => isset($item['category']) ? self::sanitizeString($item['category'], self::MAX_ITEM_LENGTH) : null,
            ];
        }, $items);
    }

    private static function sanitizeCustomer(array $customer): array
    {
        $sanitized = [
            'first_name' => self::sanitizeString($customer['first_name'] ?? '', self::MAX_STRING_LENGTH),
            'last_name' => self::sanitizeString($customer['last_name'] ?? '', self::MAX_STRING_LENGTH),
            'email' => self::sanitizeEmail($customer['email'] ?? ''),
            'phone' => self::sanitizePhone($customer['phone'] ?? ''),
        ];

        if (isset($customer['billing_address'])) {
            $sanitized['billing_address'] = self::sanitizeAddress($customer['billing_address']);
        }

        if (isset($customer['shipping_address'])) {
            $sanitized['shipping_address'] = self::sanitizeAddress($customer['shipping_address']);
        }

        return array_filter($sanitized);
    }

    private static function sanitizeAddress(array $address): array
    {
        return array_filter([
            'first_name' => self::sanitizeString($address['first_name'] ?? '', self::MAX_STRING_LENGTH),
            'last_name' => self::sanitizeString($address['last_name'] ?? '', self::MAX_STRING_LENGTH),
            'address' => self::sanitizeString($address['address'] ?? '', self::MAX_STRING_LENGTH),
            'city' => self::sanitizeString($address['city'] ?? '', self::MAX_STRING_LENGTH),
            'postal_code' => self::sanitizePostalCode($address['postal_code'] ?? ''),
            'phone' => self::sanitizePhone($address['phone'] ?? ''),
            'country_code' => self::sanitizeString($address['country_code'] ?? '', self::MAX_COUNTRY_LENGTH),
        ]);
    }

    private static function sanitizeTransactionDetails(array $details): array
    {
        return [
            'order_id' => self::sanitizeString($details['order_id'] ?? '', self::MAX_STRING_LENGTH),
            'gross_amount' => self::sanitizeAmount($details['gross_amount'] ?? 0),
        ];
    }

    private static function sanitizeString(string $value, int $maxLength): string
    {
        return trim(substr($value, 0, $maxLength));
    }

    private static function sanitizeEmail(string $email): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return substr($email, 0, self::MAX_STRING_LENGTH);
    }

    private static function sanitizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9\-\(\) ]/', '', $phone);
        return substr($phone, 0, self::MAX_PHONE_LENGTH);
    }

    private static function sanitizePostalCode(string $postalCode): string
    {
        $postalCode = preg_replace('/[^A-Za-z0-9\-]/', '', $postalCode);
        return substr($postalCode, 0, self::MAX_POSTAL_LENGTH);
    }

    private static function sanitizeAmount(mixed $amount): float
    {
        return (float) max(0, filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
    }

    private static function sanitizeQuantity(mixed $quantity): int
    {
        return (int) max(1, filter_var($quantity, FILTER_SANITIZE_NUMBER_INT));
    }

    private static function validateCardRequiredFields(array $card): void
    {
        $required = ['card_number', 'card_exp_month', 'card_exp_year'];
        foreach ($required as $field) {
            if (empty($card[$field])) {
                throw new MidtransException("Missing required field: {$field}");
            }
        }
    }

    private static function sanitizeCardNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (!preg_match('/^[0-9]{8,19}$/', $number)) {
            throw new MidtransException('Invalid card number format');
        }
        return $number;
    }

    private static function sanitizeCardExpMonth(string $month): string
    {
        $month = trim($month);
        if (!preg_match('/^(0?[1-9]|1[0-2])$/', $month)) {
            throw new MidtransException('Invalid card expiry month');
        }
        return str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    private static function sanitizeCardExpYear(string $year): string
    {
        $year = trim($year);
        if (!preg_match('/^[0-9]{2,4}$/', $year)) {
            throw new MidtransException('Invalid card expiry year');
        }
        return strlen($year) == 2 ? '20' . $year : $year;
    }

    private static function sanitizeCardCvv(string $cvv): string
    {
        $cvv = trim($cvv);
        if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            throw new MidtransException('Invalid CVV format');
        }
        return $cvv;
    }
}
