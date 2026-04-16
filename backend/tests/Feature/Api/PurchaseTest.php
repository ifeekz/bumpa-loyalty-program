<?php

use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Exceptions\PaymentException;
use App\Jobs\ProcessPurchaseJob;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

// POST /api/purchases

it('initiates a payment and returns authorization url', function () {
    [$user,, $headers] = actingAsCustomer();

    $this->mock(PaymentProviderInterface::class)
        ->shouldReceive('initializePayment')
        ->once()
        ->andReturn([
            'authorization_url' => 'https://checkout.paystack.com/test',
            'reference'         => 'LYL-TESTREF',
            'access_code'       => 'acc_xyz',
        ]);

    $this->postJson('/api/purchases', ['amount' => 5000], $headers)
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => ['reference', 'authorization_url', 'access_code'],
        ]);
});

it('creates a pending purchase record on initiation', function () {
    [$user,, $headers] = actingAsCustomer();

    $this->mock(PaymentProviderInterface::class)
        ->shouldReceive('initializePayment')
        ->once()
        ->andReturn([
            'authorization_url' => 'https://checkout.paystack.com/test',
            'reference'         => 'LYL-TESTREF2',
            'access_code'       => 'acc_abc',
        ]);

    $this->postJson('/api/purchases', ['amount' => 3000], $headers);

    $this->assertDatabaseHas('purchases', [
        'user_id' => $user->id,
        'amount'  => 3000,
        'status'  => 'pending',
    ]);
});

it('rejects purchase below minimum amount', function () {
    [$user,, $headers] = actingAsCustomer();

    $this->postJson('/api/purchases', ['amount' => 50], $headers)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

it('returns 401 for unauthenticated purchase attempt', function () {
    $this->postJson('/api/purchases', ['amount' => 5000])
        ->assertStatus(401);
});

// POST /api/purchases/webhook

it('processes a valid Paystack webhook and dispatches job', function () {
    [$user] = actingAsCustomer();

    $purchase = Purchase::factory()->pending()->create([
        'user_id'   => $user->id,
        'reference' => 'LYL-WEBHOOKTEST',
    ]);

    $payload = json_encode([
        'event' => 'charge.success',
        'data'  => ['reference' => 'LYL-WEBHOOKTEST'],
    ]);

    $signature = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

    $this->postJson(
        '/api/purchases/webhook',
        json_decode($payload, true),
        ['x-paystack-signature' => $signature]
    )->assertOk();

    Queue::assertPushed(ProcessPurchaseJob::class);
});

it('rejects webhook with invalid signature', function () {
    $this->postJson(
        '/api/purchases/webhook',
        ['event' => 'charge.success', 'data' => ['reference' => 'REF']],
        ['x-paystack-signature' => 'bad_signature']
    )->assertStatus(400);

    Queue::assertNothingPushed();
});

it('ignores non-charge webhook events', function () {
    $payload = json_encode(['event' => 'transfer.success', 'data' => []]);
    $signature = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

    $this->postJson(
        '/api/purchases/webhook',
        json_decode($payload, true),
        ['x-paystack-signature' => $signature]
    )->assertOk()
        ->assertJsonPath('message', 'Event ignored.');

    Queue::assertNothingPushed();
});

// GET /api/purchases

it('returns paginated purchase history for authenticated user', function () {
    [$user,, $headers] = actingAsCustomer();
    Purchase::factory(5)->create(['user_id' => $user->id]);

    $this->getJson('/api/purchases', $headers)
        ->assertOk()
        ->assertJsonPath('data.total', 5);
});

it('does not return other users purchases', function () {
    [$user,, $headers] = actingAsCustomer();
    $other = \App\Domain\Loyalty\Models\User::factory()->create();

    Purchase::factory(3)->create(['user_id' => $other->id]);
    Purchase::factory(2)->create(['user_id' => $user->id]);

    $this->getJson('/api/purchases', $headers)
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});
