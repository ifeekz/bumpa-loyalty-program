<?php

/*
|--------------------------------------------------------------------------
| JWT Configuration
|--------------------------------------------------------------------------
|
| Configuration for php-open-source-saver/jwt-auth.
| Run `php artisan jwt:secret` to generate JWT_SECRET and write it to .env.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    | Used to sign tokens with the HMAC SHA-256 (HS256) algorithm.
    | In a multi-service setup, services that need to verify tokens
    | must share this secret (symmetric) OR you can switch to RS256
    | (asymmetric) so only this service holds the private key and
    | consumers verify with the public key.
    |
    | Generate with: php artisan jwt:secret
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Hashing Algorithm
    |--------------------------------------------------------------------------
    | HS256 — symmetric, simplest. Suitable when all services share the secret.
    | RS256 — asymmetric. Better for public microservice ecosystems where you
    |         don't want to share a secret with every consumer.
    | Switch to RS256 by setting JWT_ALGO=RS256 and providing RSA key paths.
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Token Time-To-Live (minutes)
    |--------------------------------------------------------------------------
    | Access token expires after 60 minutes.
    | The frontend calls POST /api/auth/refresh before expiry.
    | Set to null to disable expiry (not recommended for production).
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh Time-To-Live (minutes)
    |--------------------------------------------------------------------------
    | How long after the original token issue a refresh can be requested.
    | 20160 = 2 weeks. After this, the user must log in again.
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT Blacklist
    |--------------------------------------------------------------------------
    | Enables token invalidation on logout. Uses the Laravel cache driver
    | (Redis in our stack) to store blacklisted token JTIs.
    |
    | This is the one piece of "state" in our otherwise stateless JWT setup —
    | it's a pragmatic tradeoff: pure stateless JWT can't support logout.
    | The blacklist only needs to live as long as JWT_TTL (60 min),
    | so Redis memory usage is minimal.
    */
    'blacklist_enabled'    => env('JWT_BLACKLIST_ENABLED', true),
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'jwt' => PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci::class,

        'auth' => PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate::class,

        // Redis is our storage for the blacklist
        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
