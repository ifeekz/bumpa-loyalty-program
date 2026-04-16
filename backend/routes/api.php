<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\UserAchievementController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Middleware stack:
|   auth:api      → validates JWT signature + expiry (jwt-auth package)
|   admin         → checks 'role' claim in decoded payload (no DB call)
|
| Route structure:
|   Public
|     POST /api/auth/register
|     POST /api/auth/login
|     POST /api/purchases/webhook
|
|   Authenticated (JWT required)
|     POST  /api/auth/logout
|     POST  /api/auth/refresh
|     GET   /api/auth/me
|     GET   /api/users/{user}/achievements
|     POST  /api/purchases
|     GET   /api/purchases
|
|   Admin only (JWT + role:admin claim)
|     GET  /api/admin/users/achievements
|     GET  /api/admin/users/{user}
|
*/

// Public routes

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// Paystack webhook - must be public, no auth middleware
Route::post('purchases/webhook', [PurchaseController::class, 'webhook'])
    ->name('purchases.webhook');


// JWT-authenticated routes

Route::middleware('auth:api')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });

    Route::get('users/{user}/achievements', [UserAchievementController::class, 'show'])
        ->name('users.achievements');

    Route::prefix('purchases')->group(function () {
        Route::get('/',  [PurchaseController::class, 'index']);
        Route::post('/', [PurchaseController::class, 'initiate']);
    });

    // Admin routes — JWT + role claim check
    Route::middleware('admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            // Assessment required: GET /api/admin/users/achievements
            Route::get('users/achievements', [Admin\UserAchievementController::class, 'index'])
                ->name('users.achievements');

            Route::get('users/{user}', [Admin\UserAchievementController::class, 'show'])
                ->name('users.show');
        });
});
