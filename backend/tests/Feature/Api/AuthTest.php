<?php

use App\Domain\Loyalty\Models\User;
use Illuminate\Support\Facades\Hash;

// Registration

it('registers a new customer successfully', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'             => 'Jane Doe',
        'email'            => 'jane@example.com',
        'password'         => 'Password123',
        'confirm_password' => 'Password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'email', 'role', 'loyalty_points'],
        ])
        ->assertJsonPath('data.role', 'customer')
        ->assertHeader('Authorization');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/api/auth/register', [
        'name'                  => 'Duplicate',
        'email'                 => 'existing@example.com',
        'password'              => 'password123',
        'confirm_password' => 'password123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects registration when passwords do not match', function () {
    $this->postJson('/api/auth/register', [
        'name'                  => 'Test',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'confirm_password' => 'different',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

// Login

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email'    => 'login@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/api/auth/login', [
        'email'    => 'login@example.com',
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email']])
        ->assertHeader('Authorization');
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email'    => 'user@example.com',
        'password' => Hash::make('correct'),
    ]);

    $this->postJson('/api/auth/login', [
        'email'    => 'user@example.com',
        'password' => 'wrong',
    ])->assertStatus(401);
});

it('rejects login for non-existent user', function () {
    $this->postJson('/api/auth/login', [
        'email'    => 'ghost@example.com',
        'password' => 'anything',
    ])->assertStatus(401);
});

// Protected routes

it('returns current user on GET /api/auth/me', function () {
    [$user,, $headers] = actingAsCustomer();

    $this->getJson('/api/auth/me', $headers)
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('rejects unauthenticated request to /api/auth/me', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});

it('logs out and invalidates token', function () {
    [$user, $token, $headers] = actingAsCustomer();

    $this->postJson('/api/auth/logout', [], $headers)->assertOk();

    // Token should now be rejected
    $this->getJson('/api/auth/me', $headers)->assertStatus(401);
});
