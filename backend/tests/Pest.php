<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| All tests use RefreshDatabase so each test runs against a clean slate.
| The base TestCase also disables event broadcasting to prevent Redis
| calls during tests.
*/

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Integration', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeUnlocked', function () {
    return $this->toHaveKey('unlocked_at');
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create an authenticated user and return [$user, $token, $headers].
 */
function actingAsCustomer(array $attributes = []): array
{
    $user    = \App\Domain\Loyalty\Models\User::factory()->fresh()->create($attributes);
    $token   = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
    $headers = ['Authorization' => "Bearer {$token}"];
    return [$user, $token, $headers];
}

function actingAsAdmin(array $attributes = []): array
{
    $user    = \App\Domain\Loyalty\Models\User::factory()->admin()->create($attributes);
    $token   = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
    $headers = ['Authorization' => "Bearer {$token}"];
    return [$user, $token, $headers];
}
