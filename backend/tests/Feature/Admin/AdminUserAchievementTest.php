<?php

use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Models\UserBadge;

beforeEach(function () {
    Badge::insert([
        ['name' => 'Bronze', 'slug' => 'bronze', 'min_points' => 0,   'level' => 1, 'cashback_percent' => 2.0, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Silver', 'slug' => 'silver', 'min_points' => 500, 'level' => 2, 'cashback_percent' => 5.0, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// GET /api/admin/users/achievements

it('returns paginated list of all customers for admin', function () {
    [,, $headers] = actingAsAdmin();
    User::factory(5)->create(['role' => 'customer']);

    $this->getJson('/api/admin/users/achievements', $headers)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'data' => [['id', 'name', 'email', 'loyalty_points', 'total_achievements', 'current_badge']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
        ]);
});

it('does not include admin accounts in the customer list', function () {
    [,, $headers] = actingAsAdmin();
    User::factory(3)->create(['role' => 'customer']);
    User::factory(2)->admin()->create();

    $response = $this->getJson('/api/admin/users/achievements', $headers)->assertOk();
    $total = $response->json('data.meta.total');  // ← was 'meta.total'
    expect($total)->toBe(3);
});

it('filters customers by search term', function () {
    [,, $headers] = actingAsAdmin();
    User::factory()->create(['name' => 'Chukwuemeka Obi', 'role' => 'customer']);
    User::factory()->create(['name' => 'Amina Yusuf',     'role' => 'customer']);

    $this->getJson('/api/admin/users/achievements?search=Chukwuemeka', $headers)
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)         // ← was 'meta.total'
        ->assertJsonPath('data.data.0.name', 'Chukwuemeka Obi');  // ← was 'data.0.name'
});

it('filters customers by badge slug', function () {
    [, , $headers] = actingAsAdmin();

    $silverUser = User::factory()->create(['role' => 'customer']);
    $bronze     = Badge::where('slug', 'bronze')->first();
    $silver     = Badge::where('slug', 'silver')->first();

    UserBadge::create(['user_id' => $silverUser->id, 'badge_id' => $silver->id, 'is_current' => true, 'earned_at' => now()]);

    $bronzeUser = User::factory()->create(['role' => 'customer']);
    UserBadge::create(['user_id' => $bronzeUser->id, 'badge_id' => $bronze->id, 'is_current' => true, 'earned_at' => now()]);

    $this->getJson('/api/admin/users/achievements?badge=silver', $headers)
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.data.0.id', $silverUser->id);
});

it('returns 403 for a customer trying to access admin endpoint', function () {
    [$customer, , $headers] = actingAsCustomer();

    $this->getJson('/api/admin/users/achievements', $headers)
        ->assertStatus(403);
});

it('returns 401 for unauthenticated request to admin endpoint', function () {
    $this->getJson('/api/admin/users/achievements')
        ->assertStatus(401);
});

// GET /api/admin/users/{user}

it('returns detailed user profile for admin', function () {
    [,, $headers] = actingAsAdmin();
    $customer = User::factory()->fresh()->create(['role' => 'customer']);

    $this->getJson("/api/admin/users/{$customer->id}", $headers)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user'             => ['id', 'name', 'email', 'loyalty_points', 'total_spent'],
                'achievements',
                'recent_purchases',
                'cashback_summary' => ['total_paid', 'total_pending'],
            ],
        ]);
});

it('returns 404 for non-existent user', function () {
    [, , $headers] = actingAsAdmin();

    $this->getJson('/api/admin/users/99999', $headers)
        ->assertStatus(404);
});
