<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response helper.
 *
 * Every response follows the envelope:
 * {
 *   "success": bool,
 *   "message": string,
 *   "data": mixed|null,
 *   "errors": array|null,
 *   "meta": array|null
 * }
 */
class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed       $data
     * @param  string      $message
     * @param  int         $statusCode
     * @return JsonResponse
     */
    public static function success(mixed $data, string $message = 'OK', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
            'meta'    => [
                'timestamp' => now()->toIso8601String(),
                'version'   => '1.0',
            ],
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string      $message
     * @param  mixed       $errors
     * @param  int         $statusCode
     * @return JsonResponse
     */
    public static function error(string $message, mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
            'meta'    => [
                'timestamp' => now()->toIso8601String(),
                'version'   => '1.0',
            ],
        ], $statusCode);
    }

    /**
     * Return a 404 Not Found response.
     *
     * @param  string      $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Return a 401 Unauthorized response.
     *
     * @param  string      $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, null, 401);
    }
}
