<?php

use App\Domain\Loyalty\Services\Payment\PaystackService;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.paystack.secret_key' => 'sk_test_fake_key',
        'services.paystack.base_url'   => 'https://api.paystack.co',
    ]);

    $this->service = app(PaystackService::class);
});

// initializePayment

it('initializes a payment and returns authorization url', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status'  => true,
            'message' => 'Authorization URL created',
            'data'    => [
                'authorization_url' => 'https://checkout.paystack.com/xyz123',
                'access_code'       => 'xyz123',
                'reference'         => 'LYL-TESTREFERENCE',
            ],
        ], 200),
    ]);

    $result = $this->service->initializePayment(
        email:     'customer@test.com',
        amount:    5000.00,
        reference: 'LYL-TESTREFERENCE',
    );

    expect($result['authorization_url'])->toBe('https://checkout.paystack.com/xyz123');
    expect($result['reference'])->toBe('LYL-TESTREFERENCE');
});

it('converts Naira to kobo when calling Paystack', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data'   => [
                'authorization_url' => 'https://checkout.paystack.com/abc',
                'access_code'       => 'abc',
                'reference'         => 'REF-001',
            ],
        ]),
    ]);

    $this->service->initializePayment('test@test.com', 1500.00, 'REF-001');

    Http::assertSent(function ($request) {
        $body = $request->data();
        // ₦1,500 must be sent as 150000 kobo
        return $body['amount'] === 150000;
    });
});

it('throws PaymentException when Paystack returns false status', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status'  => false,
            'message' => 'Invalid key',
        ], 200),
    ]);

    expect(fn () => $this->service->initializePayment('a@b.com', 1000, 'REF'))
        ->toThrow(PaymentException::class, 'Invalid key');
});

it('throws PaymentException on HTTP 4xx error', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response(
            ['status' => false, 'message' => 'Unauthorized'],
            401
        ),
    ]);

    expect(fn () => $this->service->initializePayment('a@b.com', 1000, 'REF'))
        ->toThrow(PaymentException::class);
});

// verifyTransaction

it('verifies a successful transaction and converts kobo to naira', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/LYL-REF123' => Http::response([
            'status' => true,
            'data'   => [
                'status'    => 'success',
                'amount'    => 500000,        // 500,000 kobo = ₦5,000
                'reference' => 'LYL-REF123',
                'paid_at'   => '2024-01-15T10:00:00.000Z',
                'channel'   => 'card',
                'id'        => 12345,
            ],
        ]),
    ]);

    $result = $this->service->verifyTransaction('LYL-REF123');

    expect($result['status'])->toBe('success');
    expect($result['amount'])->toBe(5000.0);   // Correctly converted from kobo
    expect($result['reference'])->toBe('LYL-REF123');
});

it('returns failed status for an abandoned transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/LYL-ABANDONED' => Http::response([
            'status' => true,
            'data'   => [
                'status'    => 'abandoned',
                'amount'    => 100000,
                'reference' => 'LYL-ABANDONED',
                'paid_at'   => null,
                'channel'   => null,
                'id'        => 99999,
            ],
        ]),
    ]);

    $result = $this->service->verifyTransaction('LYL-ABANDONED');

    expect($result['status'])->toBe('abandoned');
});

// initiateTransfer

it('initiates a cashback transfer successfully', function () {
    Http::fake([
        'api.paystack.co/transfer' => Http::response([
            'status' => true,
            'data'   => [
                'transfer_code' => 'TRF_abc123',
                'status'        => 'pending',
            ],
        ]),
    ]);

    $result = $this->service->initiateTransfer(
        amount:        250.00,
        recipientCode: 'RCP_xyz',
        reference:     'CB-REF001',
    );

    expect($result['transfer_code'])->toBe('TRF_abc123');
    expect($result['status'])->toBe('pending');
});

it('sends cashback amount in kobo to Paystack', function () {
    Http::fake([
        'api.paystack.co/transfer' => Http::response([
            'status' => true,
            'data'   => ['transfer_code' => 'TRF_x', 'status' => 'pending'],
        ]),
    ]);

    $this->service->initiateTransfer(250.00, 'RCP_xyz', 'CB-REF001');

    Http::assertSent(function ($request) {
        return $request->data()['amount'] === 25000; // ₦250 = 25,000 kobo
    });
});
