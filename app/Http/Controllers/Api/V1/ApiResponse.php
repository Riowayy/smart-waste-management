<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function respond(mixed $data, string $message, int $status = 200, ?array $pagination = null): JsonResponse
    {
        $response = [
            'success' => $status < 400,
            'message' => $message,
            'data' => $data,
        ];

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }
}
