<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class ResponseHelper
{
    /**
     * Return a standardized JSON success response.
     *
     * @param mixed $data The response data, which can include a resource collection.
     * @param string|null $message A custom success message. Defaults to null.
     * @param int $status HTTP status code for the response. Defaults to 200 (OK).
     * @param array $meta Additional metadata to include in the response. Defaults to an empty array.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the success details.
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $response = [
            'status' => 'success',
            'statusCode' => $status,
        ];

        // Handle data
        if ($data) {
            if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                // Transform JsonResource into an array with pagination if available
                $dataArray = $data->response()->getData(true);
                $response = array_merge($response, $dataArray);
            } else {
                $response['data'] = $data;
            }
        }

        // Add meta information
        if ( ! empty($meta)) {
            $response = array_merge($response, $meta);
        }

        // Add message
        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a standardized JSON error response and log the exception if provided.
     *
     * @param  string  $message  Custom error message for the response. Defaults to 'Oops something went wrong'.
     * @param  int  $status  HTTP status code for the response. Defaults to 500 (Internal Server Error).
     * @param  Exception|null  $exception  The exception to log, if any. Defaults to null.
     * @return \Illuminate\Http\JsonResponse JSON response containing the error details.
     *
     * @example
     * // Example 1: Return a 404 error response with a custom message.
     * return MyHelper::error('Resource not found', 404);
     *
     * // Example 2: Log an exception and return a generic 500 error response.
     * try {
     *     throw new \Exception("Something went wrong");
     * } catch (\Exception $e) {
     *     return MyHelper::error(exception: $e);
     * }
     */
    public static function error(string $message = 'Oops something went wrong', int $status = 500, ?Exception $exception = null): JsonResponse
    {
        $exception && Log::error($exception);

        return response()->json([
            'status' => 'error',
            'statusCode' => $status,
            'message' => $message,
        ], $status);
    }
}
