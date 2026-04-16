<?php

use App\Http\Controllers\Api\AuthController;
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
|
|   Authenticated (JWT required)
|     POST  /api/auth/logout
|     POST  /api/auth/refresh
|     GET   /api/auth/me
|
*/

// Public routes

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// JWT-authenticated routes

Route::middleware('auth:api')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });
});
