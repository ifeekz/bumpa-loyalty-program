<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    | In production replace * with your actual frontend domain.
    | e.g. 'https://yourapp.com'
    */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),    // Customer dashboard
        env('ADMIN_URL',    'http://localhost:5173'),    // Admin panel (Vite default)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',     // JWT token on login/register/refresh
        'X-Token-TTL',       // Token expiry hint for the frontend
    ],

    'max_age' => 86400,      // Cache preflight for 24 hours

    'supports_credentials' => false,   // true only if using cookies
];
