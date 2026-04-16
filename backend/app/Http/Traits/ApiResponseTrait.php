<?php

namespace App\Http\Traits;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * ApiResponseTrait
 *
 * Mixed into controllers to give clean, readable response calls:
 *
 *   return $this->success('Login successful', $data);
 *   return $this->validationError($errors);
 *   return $this->notFound('User not found.');
 *
 * All methods proxy to ApiResponse so there is exactly one
 * place where the envelope shape is defined.
 */
trait ApiResponseTrait
{
    protected function success(
        string $message,
        mixed  $data = null,
        int    $statusCode = 200,
        array  $headers = []
    ): JsonResponse {
        return ApiResponse::success($message, $data, $statusCode, $headers);
    }

    protected function created(string $message, mixed $data = null): JsonResponse
    {
        return ApiResponse::created($message, $data);
    }

    protected function noContent(string $message = 'Done.'): JsonResponse
    {
        return ApiResponse::noContent($message);
    }

    protected function error(
        string $message,
        int    $statusCode = 400,
        mixed  $errors = null
    ): JsonResponse {
        return ApiResponse::error($message, $statusCode, $errors);
    }

    protected function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return ApiResponse::validationError($errors, $message);
    }

    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return ApiResponse::unauthorized($message);
    }

    protected function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return ApiResponse::forbidden($message);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return ApiResponse::notFound($message);
    }

    protected function serverError(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return ApiResponse::serverError($message);
    }
}
