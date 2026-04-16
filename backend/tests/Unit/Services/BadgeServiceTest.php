<?php

use App\Domain\Loyalty\Events\BadgeUnlocked;
use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Models\UserBadge;
use App\Domain\Loyalty\Services\BadgeService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(BadgeService::class);

    // Seed badge tiers
    Badge::insert([
        ['name' => 'Bronze',   'slug' => 'bronze',   'min_points' => 0,    'level' => 1, 'cashback_percent' => 2.00,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Silver',   'slug' => 'silver',   'min_points' => 500,  'level' => 2, 'cashback_percent' => 5.00,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Gold',     'slug' => 'gold',     'min_points' => 2000, 'level' => 3, 'cashback_percent' => 8.00,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Platinum', 'slug' => 'platinum', 'min_points' => 5000, 'level' => 4, 'cashback_percent' => 12.00, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// Badge promotion

it('assigns Bronze badge to a new user with 0 points', function () {
    Event::fake();

    $user = User::factory()->fresh()->create();

    $badge = $this->service->processForUser($user);

    expect($badge->slug)->toBe('bronze');
    expect($this->service->getCurrentBadge($user)->slug)->toBe('bronze');
});

it('promotes user to Silver when points reach 500', function () {
    Event::fake();

    $user = User::factory()->withPoints(500)->create();

    $badge = $this->service->processForUser($user);

    expect($badge->slug)->toBe('silver');
});

it('promotes user to Gold when points reach 2000', function () {
    Event::fake();

    $user = User::factory()->withPoints(2000)->create();

    $badge = $this->service->processForUser($user);

    expect($badge->slug)->toBe('gold');
});

it('promotes user to Platinum when points reach 5000', function () {
    Event::fake();

    $user = User::factory()->withPoints(5000)->create();

    $badge = $this->service->processForUser($user);

    expect($badge->slug)->toBe('platinum');
});

it('returns null when user already holds the qualifying badge', function () {
    Event::fake();

    $user       = User::factory()->withPoints(500)->create();
    $silverBadge = Badge::where('slug', 'silver')->first();

    // Give the user Silver already
    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $silverBadge->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    $result = $this->service->processForUser($user);

    expect($result)->toBeNull();
});

it('does not demote a user who has lost points', function () {
    Event::fake();

    $user        = User::factory()->withPoints(100)->create(); // Below Silver
    $silverBadge = Badge::where('slug', 'silver')->first();

    // User already holds Silver (earned previously)
    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $silverBadge->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    $result = $this->service->processForUser($user);

    // Should return null — no promotion, and no demotion
    expect($result)->toBeNull();
    expect($this->service->getCurrentBadge($user)->slug)->toBe('silver');
});

it('marks only one badge as current after promotion', function () {
    Event::fake();

    $user        = User::factory()->fresh()->create();
    $bronzeBadge = Badge::where('slug', 'bronze')->first();

    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $bronzeBadge->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    // Now promote to Silver
    $user->loyalty_points = 600;
    $user->save();

    $this->service->processForUser($user);

    $currentCount = UserBadge::where('user_id', $user->id)
        ->where('is_current', true)
        ->count();

    expect($currentCount)->toBe(1);
});

// Event dispatch

it('fires BadgeUnlocked event on promotion', function () {
    Event::fake();

    $user = User::factory()->withPoints(500)->create();

    $this->service->processForUser($user);

    Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user) {
        return $event->user->id === $user->id
            && $event->badge->slug === 'silver';
    });
});

it('includes previous badge in BadgeUnlocked event', function () {
    Event::fake();

    $user        = User::factory()->withPoints(500)->create();
    $bronzeBadge = Badge::where('slug', 'bronze')->first();

    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $bronzeBadge->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    $this->service->processForUser($user);

    Event::assertDispatched(BadgeUnlocked::class, function ($event) {
        return $event->previousBadge?->slug === 'bronze';
    });
});
