<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Define named rate limiters here
            \Illuminate\Support\Facades\RateLimiter::for('auth', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(function () {
                        return ApiResponse::error(
                            'Too many attempts. Please try again in a minute.',
                            429
                        );
                    });
            });

            \Illuminate\Support\Facades\RateLimiter::for('api', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(function () {
                        return ApiResponse::error(
                            'Too many requests. Please slow down.',
                            429
                        );
                    });
            });

            \Illuminate\Support\Facades\RateLimiter::for('webhook', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)
                    ->by($request->ip())
                    ->response(function () {
                        return ApiResponse::error(
                            'Too many webhook requests.',
                            429
                        );
                    });
            });
        }
    )
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Force all /api routes to accept JSON responses.
        // This means Laravel treats every /api request as expecting JSON
        // even if the client forgot to send Accept: application/json
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::notFound('Resource not found.');
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Method not allowed.', 405);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::validationError($e->errors());
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized('Unauthenticated.');
            }
        });

        // JWT exceptions

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized('Token has expired.');
            }
        });

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized('Token is invalid.');
            }
        });

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized('Token not provided.');
            }
        });

        // Catch-all for any unhandled exception on /api routes
        // Prevents Laravel from returning an HTML stack trace in production.
        // In local/dev you still get the full Ignition error page for non-API requests.

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    app()->isProduction() ? 'An unexpected error occurred.' : $e->getMessage(),
                    500
                );
            }
        });
    })->create();
