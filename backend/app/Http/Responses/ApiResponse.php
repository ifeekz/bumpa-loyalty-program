<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * ApiResponse
 *
 * Central factory for all JSON responses in the application.
 * Every controller method and exception handler goes through here,
 * guaranteeing a consistent envelope shape:
 *
 * {
 *   "success":     true | false,
 *   "status_code": 200,
 *   "message":     "Human-readable summary",
 *   "data":        { ... }   // present on success, omitted when null
 *   "errors":      { ... }   // present on validation failure only
 * }
 */
class ApiResponse
{
    // Success responses

    public static function success(
        string $message,
        mixed  $data = null,
        int    $statusCode = 200,
        array  $headers = []
    ): JsonResponse {
        $body = [
            'success'     => true,
            'status_code' => $statusCode,
            'message'     => $message,
        ];

        // Only include 'data' key when there is actual data.
        // Omitting it keeps the response clean for endpoints that
        // return no payload (e.g. logout, delete).
        if (! is_null($data)) {
            $body['data'] = $data;
        }

        return response()->json($body, $statusCode, $headers);
    }

    // Error responses

    public static function error(
        string $message,
        int    $statusCode = 400,
        mixed  $errors = null,
        array  $headers = []
    ): JsonResponse {
        $body = [
            'success'     => false,
            'status_code' => $statusCode,
            'message'     => $message,
        ];

        // 'errors' is only included for validation failures (422).
        // For all other errors it's omitted to avoid leaking internals.
        if (! is_null($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $statusCode, $headers);
    }

    // Semantic shorthand methods
    // These make controller code read like English and remove magic numbers.

    public static function ok(string $message, mixed $data = null): JsonResponse
    {
        return static::success($message, $data, 200);
    }

    public static function created(string $message, mixed $data = null): JsonResponse
    {
        return static::success($message, $data, 201);
    }

    public static function noContent(string $message = 'Done'): JsonResponse
    {
        // Returns 200 with no data key — used for logout, soft deletes, etc.
        return static::success($message, null, 200);
    }

    public static function validationError(array $errors, string $message = null): JsonResponse
    {
        return static::error(
            $message ?? static::summariseErrors($errors),
            422,
            $errors
        );
    }

    /**
     * Build a human-readable summary from the errors bag.
     *
     * Takes the very first error message across all fields, then appends
     * a count of any remaining messages:
     *
     *   "The email field is required."
     *   "The email field is required. (+2 more)"
     *
     * Keeping the first real message (rather than a generic "Validation failed.")
     * means the client gets actionable feedback without needing to parse `errors`.
     */
    private static function summariseErrors(array $errors): string
    {
        // Flatten all error message arrays into a single list, preserving order.
        // e.g. ['email' => ['msg1', 'msg2'], 'password' => ['msg3']]
        //   →  ['msg1', 'msg2', 'msg3']
        $all = array_merge(...array_values($errors));

        $first     = $all[0];
        $remaining = count($all) - 1;

        return $remaining > 0
            ? "{$first} (+{$remaining} more error" . ($remaining > 1 ? 's' : '') . ")"
            : $first;
    }

    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return static::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return static::error($message, 403);
    }

    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return static::error($message, 404);
    }

    public static function serverError(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return static::error($message, 500);
    }
}
