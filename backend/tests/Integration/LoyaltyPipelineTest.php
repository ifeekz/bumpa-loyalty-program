<?php

use App\Domain\Loyalty\Events\AchievementUnlocked;
use App\Domain\Loyalty\Events\BadgeUnlocked;
use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Models\Achievement;
use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Jobs\ProcessPurchaseJob;
use App\Domain\Loyalty\DTOs\PurchaseData;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Seed full reference data
    Badge::insert([
        ['name' => 'Bronze',   'slug' => 'bronze',   'min_points' => 0,    'level' => 1, 'cashback_percent' => 2.0,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Silver',   'slug' => 'silver',   'min_points' => 500,  'level' => 2, 'cashback_percent' => 5.0,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Gold',     'slug' => 'gold',     'min_points' => 2000, 'level' => 3, 'cashback_percent' => 8.0,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Platinum', 'slug' => 'platinum', 'min_points' => 5000, 'level' => 4, 'cashback_percent' => 12.0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    Achievement::insert([
        ['name' => 'First Purchase', 'slug' => 'first-purchase', 'points_reward' => 50,  'condition_type' => 'purchase_count',  'condition_value' => 1,     'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Spender',        'slug' => 'spender',        'points_reward' => 100, 'condition_type' => 'total_spent',     'condition_value' => 10000, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Whale',          'slug' => 'whale',          'points_reward' => 250, 'condition_type' => 'single_purchase', 'condition_value' => 20000, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// Full pipeline: job → event → listeners

it('runs the full pipeline: verified purchase → achievements → badge → cashback', function () {
    // Don't fake events — we want real listeners to fire
    $user = User::factory()->fresh()->create();

    $purchase = Purchase::factory()->pending()->unprocessed()->create([
        'user_id'   => $user->id,
        'amount'    => 5000,
        'reference' => 'LYL-PIPELINE-TEST',
    ]);

    // Mock Paystack to return a successful verification
    $this->mock(PaymentProviderInterface::class)
        ->shouldReceive('verifyTransaction')
        ->with('LYL-PIPELINE-TEST')
        ->andReturn([
            'status'    => 'success',
            'amount'    => 5000.00,
            'reference' => 'LYL-PIPELINE-TEST',
            'paid_at'   => now()->toIso8601String(),
            'channel'   => 'card',
            'raw'       => ['id' => 111, 'status' => 'success'],
        ])
        ->shouldReceive('createTransferRecipient')->andReturn(['recipient_code' => 'RCP_test'])
        ->shouldReceive('initiateTransfer')->andReturn(['transfer_code' => 'TRF_test', 'status' => 'pending']);

    // Run the job synchronously
    $job = new ProcessPurchaseJob(PurchaseData::fromArray([
        'user_id'   => $user->id,
        'amount'    => 5000,
        'reference' => 'LYL-PIPELINE-TEST',
        'email'     => $user->email,
    ]));

    app()->call([$job, 'handle'], ['paymentProvider' => app(PaymentProviderInterface::class)]);

    // Assertions

    // Purchase marked completed
    $this->assertDatabaseHas('purchases', [
        'reference'             => 'LYL-PIPELINE-TEST',
        'status'                => 'completed',
        'processed_for_loyalty' => true,
    ]);

    // User total_spent updated
    expect($user->fresh()->total_spent)->toBe('5000.00');

    // Achievement unlocked
    expect($user->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue();

    // Points awarded
    expect((float) $user->fresh()->loyalty_points)->toBe(50.0);

    // Badge promoted (50 points → Bronze)
    $this->assertDatabaseHas('user_badges', [
        'user_id'    => $user->id,
        'is_current' => true,
    ]);

    // Cashback transaction created
    $this->assertDatabaseHas('cashback_transactions', [
        'user_id'     => $user->id,
        'purchase_id' => $purchase->id,
    ]);
});

it('is idempotent — running the job twice does not double-credit points', function () {
    $user = User::factory()->fresh()->create();

    $purchase = Purchase::factory()->pending()->unprocessed()->create([
        'user_id'   => $user->id,
        'amount'    => 5000,
        'reference' => 'LYL-IDEMPOTENT',
    ]);

    $mock = $this->mock(PaymentProviderInterface::class);
    $mock->shouldReceive('verifyTransaction')->andReturn([
        'status' => 'success', 'amount' => 5000.0,
        'reference' => 'LYL-IDEMPOTENT', 'paid_at' => now()->toIso8601String(),
        'channel' => 'card', 'raw' => ['id' => 222],
    ]);
    $mock->shouldReceive('createTransferRecipient')->andReturn(['recipient_code' => 'RCP_x']);
    $mock->shouldReceive('initiateTransfer')->andReturn(['transfer_code' => 'TRF_x', 'status' => 'pending']);

    $purchaseData = PurchaseData::fromArray([
        'user_id' => $user->id, 'amount' => 5000,
        'reference' => 'LYL-IDEMPOTENT', 'email' => $user->email,
    ]);

    $run = fn () => app()->call(
        [new ProcessPurchaseJob($purchaseData), 'handle'],
        ['paymentProvider' => app(PaymentProviderInterface::class)]
    );

    $run();
    $run(); // Second run — should be a no-op

    // Points should only be granted once
    $achievementPoints = 50; // first-purchase reward
    expect((float) $user->fresh()->loyalty_points)->toBe((float) $achievementPoints);

    // Only one achievement record
    expect($user->achievements()->count())->toBe(1);
});

it('marks purchase as failed when Paystack reports failure', function () {
    $user = User::factory()->fresh()->create();

    $purchase = Purchase::factory()->pending()->unprocessed()->create([
        'user_id'   => $user->id,
        'amount'    => 5000,
        'reference' => 'LYL-FAIL-TEST',
    ]);

    $this->mock(PaymentProviderInterface::class)
        ->shouldReceive('verifyTransaction')
        ->andReturn([
            'status'    => 'failed',
            'amount'    => 5000.0,
            'reference' => 'LYL-FAIL-TEST',
            'paid_at'   => null,
            'channel'   => null,
            'raw'       => [],
        ]);

    $job = new ProcessPurchaseJob(PurchaseData::fromArray([
        'user_id' => $user->id, 'amount' => 5000,
        'reference' => 'LYL-FAIL-TEST', 'email' => $user->email,
    ]));

    app()->call([$job, 'handle'], ['paymentProvider' => app(PaymentProviderInterface::class)]);

    $this->assertDatabaseHas('purchases', [
        'reference' => 'LYL-FAIL-TEST',
        'status'    => 'failed',
    ]);

    // No loyalty points, no achievements
    expect((float) $user->fresh()->loyalty_points)->toBe(0.0);
    expect($user->achievements()->count())->toBe(0);
});

it('dispatches PurchaseRecorded event after successful verification', function () {
    Event::fake([PurchaseRecorded::class]);

    $user = User::factory()->fresh()->create();

    Purchase::factory()->pending()->unprocessed()->create([
        'user_id'   => $user->id,
        'amount'    => 2000,
        'reference' => 'LYL-EVENT-TEST',
    ]);

    $this->mock(PaymentProviderInterface::class)
        ->shouldReceive('verifyTransaction')
        ->andReturn([
            'status' => 'success', 'amount' => 2000.0,
            'reference' => 'LYL-EVENT-TEST', 'paid_at' => now()->toIso8601String(),
            'channel' => 'card', 'raw' => ['id' => 333],
        ]);

    $job = new ProcessPurchaseJob(PurchaseData::fromArray([
        'user_id' => $user->id, 'amount' => 2000,
        'reference' => 'LYL-EVENT-TEST', 'email' => $user->email,
    ]));

    app()->call([$job, 'handle'], ['paymentProvider' => app(PaymentProviderInterface::class)]);

    Event::assertDispatched(PurchaseRecorded::class, fn ($e) =>
        $e->purchase->reference === 'LYL-EVENT-TEST'
    );
});
