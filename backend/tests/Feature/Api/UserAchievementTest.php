<?php

use App\Domain\Loyalty\Models\Achievement;
use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Models\UserBadge;

beforeEach(function () {
    Badge::insert([
        ['name' => 'Bronze', 'slug' => 'bronze', 'min_points' => 0,   'level' => 1, 'cashback_percent' => 2.0, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Silver', 'slug' => 'silver', 'min_points' => 500, 'level' => 2, 'cashback_percent' => 5.0, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// GET /api/users/{user}/achievements

it('returns loyalty profile for authenticated user', function () {
    [$user,, $headers] = actingAsCustomer();

    $achievement = Achievement::create([
        'name'            => 'First Purchase',
        'slug'            => 'first-purchase',
        'points_reward'   => 50,
        'condition_type'  => 'purchase_count',
        'condition_value' => 1,
    ]);
    $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

    $bronze = Badge::where('slug', 'bronze')->first();
    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $bronze->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    $this->getJson("/api/users/{$user->id}/achievements", $headers)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [                    // ← wrap everything in 'data'
                'user'         => ['id', 'name', 'loyalty_points', 'total_spent'],
                'current_badge',
                'next_badge',
                'achievements',
                'badge_history',
                'stats'        => ['total_achievements', 'purchase_count'],
            ],
        ])
        ->assertJsonPath('data.stats.total_achievements', 1)
        ->assertJsonPath('data.current_badge.slug', 'bronze');
});

it('returns next badge progress when user is not at max tier', function () {
    [$user,, $headers] = actingAsCustomer(['loyalty_points' => 100]);

    $this->getJson("/api/users/{$user->id}/achievements", $headers)
        ->assertOk()
        ->assertJsonPath('data.next_badge.name', 'Bronze')
        ->assertJsonPath('data.next_badge.points_needed', 0);
});

it('returns null next_badge when user is at Platinum', function () {
    Badge::insert([
        ['name' => 'Gold',     'slug' => 'gold',     'min_points' => 2000, 'level' => 3, 'cashback_percent' => 8.0,  'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Platinum', 'slug' => 'platinum', 'min_points' => 5000, 'level' => 4, 'cashback_percent' => 12.0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    [$user,, $headers] = actingAsCustomer(['loyalty_points' => 6000]);

    $platinum = Badge::where('slug', 'platinum')->first();
    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $platinum->id,
        'is_current' => true,
        'earned_at'  => now(),
    ]);

    $this->getJson("/api/users/{$user->id}/achievements", $headers)
        ->assertOk()
        ->assertJsonPath('next_badge', null);
});

it('prevents a customer from viewing another customer profile', function () {
    [$user,, $headers] = actingAsCustomer();
    $other = User::factory()->create();

    $this->getJson("/api/users/{$other->id}/achievements", $headers)
        ->assertStatus(403);
});

it('allows an admin to view any user profile', function () {
    [,, $adminHeaders] = actingAsAdmin();
    $customer = User::factory()->fresh()->create();

    $this->getJson("/api/users/{$customer->id}/achievements", $adminHeaders)
        ->assertOk();
});

it('returns 401 for unauthenticated request', function () {
    $user = User::factory()->create();

    $this->getJson("/api/users/{$user->id}/achievements")
        ->assertStatus(401);
});
