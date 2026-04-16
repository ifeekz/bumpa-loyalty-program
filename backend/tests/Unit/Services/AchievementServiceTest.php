<?php

use App\Domain\Loyalty\Events\AchievementUnlocked;
use App\Domain\Loyalty\Models\Achievement;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\AchievementService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(AchievementService::class);

    // Seed the achievements we'll test against
    Achievement::create([
        'name'            => 'First Purchase',
        'slug'            => 'first-purchase',
        'points_reward'   => 50,
        'condition_type'  => 'purchase_count',
        'condition_value' => 1,
    ]);
    Achievement::create([
        'name'            => 'Regular Shopper',
        'slug'            => 'regular-shopper',
        'points_reward'   => 100,
        'condition_type'  => 'purchase_count',
        'condition_value' => 5,
    ]);
    Achievement::create([
        'name'            => 'Big Spender',
        'slug'            => 'big-spender',
        'points_reward'   => 300,
        'condition_type'  => 'total_spent',
        'condition_value' => 50000,
    ]);
    Achievement::create([
        'name'            => 'Whale',
        'slug'            => 'whale',
        'points_reward'   => 250,
        'condition_type'  => 'single_purchase',
        'condition_value' => 20000,
    ]);
});

// purchase_count condition

it('unlocks first-purchase achievement on first completed purchase', function () {
    Event::fake();

    $user     = User::factory()->fresh()->create();
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 1000]);

    $unlocked = $this->service->processForPurchase($user, $purchase);

    expect($unlocked->pluck('slug'))->toContain('first-purchase');
    expect($user->fresh()->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue();
});

it('grants points when achievement is unlocked', function () {
    Event::fake();

    $user     = User::factory()->fresh()->create();
    $purchase = Purchase::factory()->create(['user_id' => $user->id]);

    $pointsBefore = (float) $user->fresh()->loyalty_points;

    $this->service->processForPurchase($user, $purchase);

    expect((float) $user->fresh()->loyalty_points)->toBeGreaterThan($pointsBefore);
});

it('does not unlock first-purchase achievement twice', function () {
    Event::fake();

    $user = User::factory()->fresh()->create();
    $p1   = Purchase::factory()->create(['user_id' => $user->id]);
    $p2   = Purchase::factory()->create(['user_id' => $user->id]);

    $this->service->processForPurchase($user, $p1);
    $this->service->processForPurchase($user, $p2);

    expect($user->achievements()->where('slug', 'first-purchase')->count())->toBe(1);
});

it('unlocks regular-shopper after 5 purchases', function () {
    Event::fake();

    $user = User::factory()->fresh()->create();

    foreach (range(1, 5) as $i) {
        $purchase = Purchase::factory()->create(['user_id' => $user->id]);
        $this->service->processForPurchase($user, $purchase);
    }

    expect($user->achievements()->where('slug', 'regular-shopper')->exists())->toBeTrue();
});

it('does not unlock regular-shopper after only 4 purchases', function () {
    Event::fake();

    $user = User::factory()->fresh()->create();

    foreach (range(1, 4) as $i) {
        $purchase = Purchase::factory()->create(['user_id' => $user->id]);
        $this->service->processForPurchase($user, $purchase);
    }

    expect($user->achievements()->where('slug', 'regular-shopper')->exists())->toBeFalse();
});

// total_spent condition

it('unlocks big-spender when total_spent crosses threshold', function () {
    Event::fake();

    $user     = User::factory()->create(['total_spent' => 50000]);
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 1000]);

    $unlocked = $this->service->processForPurchase($user, $purchase);

    expect($unlocked->pluck('slug'))->toContain('big-spender');
});

it('does not unlock big-spender when total_spent is below threshold', function () {
    Event::fake();

    $user     = User::factory()->create(['total_spent' => 10000]);
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 500]);

    $unlocked = $this->service->processForPurchase($user, $purchase);

    expect($unlocked->pluck('slug'))->not->toContain('big-spender');
});

// single_purchase condition

it('unlocks whale achievement on a single purchase >= threshold', function () {
    Event::fake();

    $user     = User::factory()->fresh()->create();
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 25000]);

    $unlocked = $this->service->processForPurchase($user, $purchase);

    expect($unlocked->pluck('slug'))->toContain('whale');
});

it('does not unlock whale achievement when single purchase is below threshold', function () {
    Event::fake();

    $user     = User::factory()->fresh()->create();
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 19999]);

    $unlocked = $this->service->processForPurchase($user, $purchase);

    expect($unlocked->pluck('slug'))->not->toContain('whale');
});

// Event dispatch

it('fires AchievementUnlocked event when achievement is unlocked', function () {
    Event::fake();

    $user     = User::factory()->fresh()->create();
    $purchase = Purchase::factory()->create(['user_id' => $user->id]);

    $this->service->processForPurchase($user, $purchase);

    Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user) {
        return $event->user->id === $user->id
            && $event->achievement->slug === 'first-purchase';
    });
});

it('does not fire AchievementUnlocked when no achievement is earned', function () {
    Event::fake();

    // User already has the first-purchase achievement
    $user        = User::factory()->fresh()->create();
    $achievement = Achievement::where('slug', 'first-purchase')->first();
    $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

    // Second purchase - no new achievements qualify
    $purchase = Purchase::factory()->create(['user_id' => $user->id, 'amount' => 100]);
    $this->service->processForPurchase($user, $purchase);

    Event::assertNotDispatched(AchievementUnlocked::class);
});
