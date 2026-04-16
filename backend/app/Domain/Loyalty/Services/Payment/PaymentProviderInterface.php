<?php

namespace App\Domain\Loyalty\Services\Payment;

/**
 * PaymentProviderInterface
 *
 * All payment providers (Paystack, future providers) must implement this
 * contract. This is the key architectural decision that makes the payment
 * layer swappable without touching business logic — classic Dependency
 * Inversion Principle.
 *
 * The service container binds the concrete implementation in AppServiceProvider.
 */
interface PaymentProviderInterface
{
    /**
     * Initialise a payment and return the checkout URL + reference.
     *
     * @param  string $email     Customer email
     * @param  float  $amount    Amount in Naira (service converts to kobo internally)
     * @param  string $reference Unique transaction reference
     * @param  array  $metadata  Optional metadata to attach to the transaction
     * @return array{authorization_url: string, reference: string, access_code: string}
     *
     * @throws \App\Exceptions\PaymentException
     */
    public function initializePayment(
        string $email,
        float  $amount,
        string $reference,
        array  $metadata = []
    ): array;

    /**
     * Verify a transaction by reference.
     *
     * @return array{status: string, amount: float, reference: string, paid_at: string|null}
     * @throws \App\Exceptions\PaymentException
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Create a transfer recipient (required before sending cashback).
     *
     * @param  string $name          Account holder name
     * @param  string $accountNumber Bank account number
     * @param  string $bankCode      Paystack bank code
     * @return array{recipient_code: string}
     * @throws \App\Exceptions\PaymentException
     */
    public function createTransferRecipient(
        string $name,
        string $accountNumber,
        string $bankCode
    ): array;

    /**
     * Initiate a transfer (cashback payout) to a recipient.
     *
     * @param  float  $amount         Amount in Naira
     * @param  string $recipientCode  Paystack recipient code
     * @param  string $reference      Unique transfer reference
     * @param  string $reason         Human-readable reason
     * @return array{transfer_code: string, status: string}
     * @throws \App\Exceptions\PaymentException
     */
    public function initiateTransfer(
        float  $amount,
        string $recipientCode,
        string $reference,
        string $reason = 'Loyalty cashback'
    ): array;
}
