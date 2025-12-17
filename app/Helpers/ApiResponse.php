<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function fromServiceResult(ServiceResponse $response): JsonResponse
    {
        if ($response->errors) {
            return self::validationError(
                $response->errors,
                $response->status
            );
        } else if ($response->error) {
            return self::error(
                $response->error,
                $response->status
            );
        }

        return self::success(
            $response->data,
            $response->message,
            $response->status
        );
    }

    /**
     * Send a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public static function success(
        $data = null,
        $message = "Success",
        $status = 200 // HTTP 200 OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Send an error response.
     *
     * @param string $error
     * @param int $status
     * @return JsonResponse
     */
    public static function error(
        $message,
        $status = 400, // HTTP 400 Bad Request
        $meta = null
    ) {
        return response()->json([
            'success' => false,
            'error' => $message,
            'meta' => $meta,
        ], $status);
    }

    /**
     * Send a response with validation errors.
     *
     * @param array $errors
     * @param int $status
     * @return JsonResponse
     */
    public static function validationError(
        $errors,
        $status = 422 // HTTP 422 Unprocessable Content
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'errors' => $errors
        ], $status);
    }
}
