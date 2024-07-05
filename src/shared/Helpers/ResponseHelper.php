<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Illuminate\Http\JsonResponse;

final class ResponseHelper
{
    /**
     * Generate a successful JSON response.
     *
     * @param  mixed|null  $data  The data to include in the response.
     * @param  string|null  $message  An optional message to include in the response.
     * @param  int  $status  The HTTP status code for the response (default is 200).
     * @param  array  $meta  Additional meta information to include in the response.
     *
     * @return JsonResponse
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $response = ['status' => 'success', 'statusCode' => $status];
        if ($data) {
            $response['data'] = $data;
        }

        if ( ! empty($meta)) {
            foreach ($meta as $key => $value) {
                $response[$key] = $value;
            }
        }

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Generate an error JSON response.
     *
     * @param  string  $message  The error message (default is 'Oops something went wrong').
     * @param  int  $status  The HTTP status code for the response (default is 500).
     *
     * @return JsonResponse
     */
    public static function error(string $message = 'Oops something went wrong', int $status = 500): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'statusCode' => $status,
            'message' => $message,
        ], $status);
    }
}
