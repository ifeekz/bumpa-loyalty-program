<?php

namespace App\Domain\Loyalty\Services\Payment;

use App\Exceptions\PaymentException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PaystackService
 *
 * Concrete implementation of PaymentProviderInterface for Paystack.
 * All amounts are received in Naira and converted to kobo (×100) before
 * hitting the API, as Paystack works in the smallest currency unit.
 *
 * Docs: https://paystack.com/docs/api
 */
class PaystackService implements PaymentProviderInterface
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl   = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    // Payment Initialization

    public function initializePayment(
        string $email,
        float  $amount,
        string $reference,
        array  $metadata = []
    ): array {
        $payload = [
            'email'     => $email,
            'amount'    => $this->toKobo($amount),  // Paystack expects kobo
            'reference' => $reference,
            'currency'  => 'NGN',
            'metadata'  => $metadata,
        ];

        $response = $this->post('/transaction/initialize', $payload);

        return [
            'authorization_url' => $response['data']['authorization_url'],
            'reference'         => $response['data']['reference'],
            'access_code'       => $response['data']['access_code'],
        ];
    }

    // Transaction Verification

    public function verifyTransaction(string $reference): array
    {
        $response = $this->get("/transaction/verify/{$reference}");

        $data = $response['data'];

        return [
            'status'    => $data['status'],               // 'success', 'failed', 'abandoned'
            'amount'    => $this->fromKobo($data['amount']), // Convert back to Naira
            'reference' => $data['reference'],
            'paid_at'   => $data['paid_at'] ?? null,
            'channel'   => $data['channel'] ?? null,
            'raw'       => $data,                         // Full response for audit storage
        ];
    }

    // Cashback Transfer

    public function createTransferRecipient(
        string $name,
        string $accountNumber,
        string $bankCode
    ): array {
        $response = $this->post('/transferrecipient', [
            'type'           => 'nuban',       // Nigerian bank account
            'name'           => $name,
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
            'currency'       => 'NGN',
        ]);

        return [
            'recipient_code' => $response['data']['recipient_code'],
        ];
    }

    public function initiateTransfer(
        float  $amount,
        string $recipientCode,
        string $reference,
        string $reason = 'Loyalty cashback'
    ): array {
        $response = $this->post('/transfer', [
            'source'    => 'balance',            // Debit from Paystack balance
            'amount'    => $this->toKobo($amount),
            'recipient' => $recipientCode,
            'reason'    => $reason,
            'reference' => $reference,
        ]);

        return [
            'transfer_code' => $response['data']['transfer_code'],
            'status'        => $response['data']['status'],
        ];
    }

    // HTTP Helpers

    private function post(string $endpoint, array $payload): array
    {
        try {
            $http = Http::withToken($this->secretKey)->acceptJson();

            if (! app()->isProduction()) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post("{$this->baseUrl}{$endpoint}", $payload)->throw();

            $body = $response->json();

            if (! ($body['status'] ?? false)) {
                throw new PaymentException(
                    "Paystack error on {$endpoint}: " . ($body['message'] ?? 'Unknown error')
                );
            }

            return $body;

        } catch (RequestException $e) {
            Log::error('Paystack HTTP error', [
                'endpoint' => $endpoint,
                'status'   => $e->response->status(),
                'body'     => $e->response->json(),
            ]);

            throw new PaymentException(
                "Paystack request failed [{$e->response->status()}]: " .
                ($e->response->json('message') ?? $e->getMessage())
            );
        }
    }

    private function get(string $endpoint): array
    {
        try {
            $http = Http::withToken($this->secretKey)->acceptJson();

            if (! app()->isProduction()) {
                $http = $http->withoutVerifying();
            }

            $response = $http->get("{$this->baseUrl}{$endpoint}")->throw();

            $body = $response->json();

            if (! ($body['status'] ?? false)) {
                throw new PaymentException(
                    "Paystack error on {$endpoint}: " . ($body['message'] ?? 'Unknown error')
                );
            }

            return $body;

        } catch (RequestException $e) {
            Log::error('Paystack HTTP error', [
                'endpoint' => $endpoint,
                'status'   => $e->response->status(),
                'body'     => $e->response->json(),
            ]);

            throw new PaymentException(
                "Paystack request failed [{$e->response->status()}]: " .
                ($e->response->json('message') ?? $e->getMessage())
            );
        }
    }

    // Currency Helpers

    /** Convert Naira to kobo (Paystack's unit) */
    private function toKobo(float $naira): int
    {
        return (int) round($naira * 100);
    }

    /** Convert kobo back to Naira */
    private function fromKobo(int $kobo): float
    {
        return round($kobo / 100, 2);
    }
}
