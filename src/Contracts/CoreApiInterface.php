<?php

namespace Aliziodev\LaravelMidtrans\Contracts;

interface CoreApiInterface
{
    /**
     * Transaction Methods
     */
    public function charge(array $params);
    public function status(string $orderId);
    public function cancel(string $orderId);
    public function expire(string $orderId);
    public function refund(string $orderId, array $params = []);

    /**
     * Invoice Methods
     */
    public function createInvoice(array $params);
    public function getInvoice(string $invoiceId);
    public function voidInvoice(string $invoiceId);

    /**
     * Subscription Methods
     */
    public function createSubscription(array $params);
    public function getSubscription(string $subscriptionId);
    public function disableSubscription(string $subscriptionId);
    public function enableSubscription(string $subscriptionId);
    public function cancelSubscription(string $subscriptionId);
    public function updateSubscription(string $subscriptionId, array $params);

    /**
     * Card Methods
     */
    public function cardRegister(string $cardNumber, string $expMonth, string $expYear);
    public function generateCardToken(array $params);
    public function getCardBIN(string $cardNumber);
    public function cardRegisterWithBIN(string $cardNumber, string $expMonth, string $expYear);

    /**
     * GoPay Methods
     */
    public function createPayAccount(array $params);
    public function getPayAccount(string $accountId);
    public function unbindPayAccount(string $accountId);

    /**
     * Notification Methods
     */
    public function notification();
}
