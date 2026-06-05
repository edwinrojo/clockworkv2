<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $data
     */
    public static function error(
        string $message,
        int $status = 400,
        ?string $code = null,
        array $errors = [],
        array $data = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ];

        if ($data !== []) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }
}
