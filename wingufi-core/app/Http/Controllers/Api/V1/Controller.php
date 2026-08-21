<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;

abstract class Controller extends BaseController
{
    protected function success(mixed $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function tenantId(): ?int
    {
        $tenant = request()->attributes->get('tenant');

        return $tenant?->id;
    }

    protected function credential(): mixed
    {
        return request()->attributes->get('credential');
    }
}
