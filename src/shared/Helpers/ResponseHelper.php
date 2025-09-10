<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\V1\Logging\Enums\LogChannelEnums;

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

    public static function error(
        string $message = 'Oops something went wrong',
        int $status = 500,
        ?Exception $exception = null,
        ?array $errors = [],
        ?LogChannelEnums $channel = null,
        ?string $errorCode = null
    ): JsonResponse {
        if ($channel && $exception) {
            Log::channel($channel->value)->error($exception);
        } elseif ($exception && ! $channel) {
            Log::error($exception);
        }

        // If no specific errors provided, use the main message as a single error
        if (empty($errors)) {
            $errors = [$message];
        }

        $response = [
            'status' => 'error',
            'statusCode' => $status,
            'message' => $message,
            'errors' => $errors,
        ];

        // Add error code if provided
        if ($errorCode) {
            $response['errorCode'] = $errorCode;
        }

        // Add exception details in development environment
        if ($exception && config('app.debug')) {
            $response['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Return a standardized JSON error response for validation failures.
     *
     * @param array $errors Validation errors array.
     * @param string|null $message Custom error message. Defaults to 'Validation failed'.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the validation error details.
     */
    public static function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return self::error(
            message: $message ?? 'Validation failed',
            status: 422,
            errors: $errors,
            errorCode: 'VALIDATION_ERROR'
        );
    }

    /**
     * Return a standardized JSON error response for authentication failures.
     *
     * @param string|null $message Custom error message. Defaults to 'Unauthenticated'.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the authentication error details.
     */
    public static function unauthenticated(?string $message = null): JsonResponse
    {
        return self::error(
            message: $message ?? 'Unauthenticated',
            status: 401,
            errorCode: 'UNAUTHENTICATED'
        );
    }

    /**
     * Return a standardized JSON error response for authorization failures.
     *
     * @param string|null $message Custom error message. Defaults to 'Unauthorized'.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the authorization error details.
     */
    public static function unauthorized(?string $message = null): JsonResponse
    {
        return self::error(
            message: $message ?? 'Unauthorized',
            status: 403,
            errorCode: 'UNAUTHORIZED'
        );
    }

    /**
     * Return a standardized JSON error response for not found resources.
     *
     * @param string|null $message Custom error message. Defaults to 'Resource not found'.
     * @param string|null $resourceName Name of the resource that wasn't found.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the not found error details.
     */
    public static function notFound(?string $message = null, ?string $resourceName = null): JsonResponse
    {
        $message = $message ?? ($resourceName ? "$resourceName not found" : 'Resource not found');

        return self::error(
            message: $message,
            status: 404,
            errorCode: 'NOT_FOUND'
        );
    }

    /**
     * Return a standardized JSON error response for invalid headers.
     *
     * @param string|null $message Custom error message. Defaults to 'Invalid header'.
     * @param string|null $headerName Name of the invalid header.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the invalid header error details.
     */
    public static function invalidHeader(?string $message = null, ?string $headerName = null): JsonResponse
    {
        $message = $message ?? ($headerName ? "Invalid $headerName header" : 'Invalid header');

        return self::error(
            message: $message,
            status: 400,
            errorCode: 'INVALID_HEADER'
        );
    }

    /**
     * Return a standardized JSON error response for missing required headers.
     *
     * @param string $headerName Name of the missing header.
     * @param string|null $message Custom error message.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the missing header error details.
     */
    public static function missingHeader(string $headerName, ?string $message = null): JsonResponse
    {
        $message = $message ?? "$headerName header is required";

        return self::error(
            message: $message,
            status: 400,
            errorCode: 'MISSING_HEADER'
        );
    }

    /**
     * Return a standardized JSON error response for server errors.
     *
     * @param Exception|null $exception The exception that occurred.
     * @param string|null $message Custom error message. Defaults to 'Internal server error'.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the server error details.
     */
    public static function serverError(?Exception $exception = null, ?string $message = null): JsonResponse
    {
        return self::error(
            message: $message ?? 'Internal server error',
            exception: $exception,
            errorCode: 'SERVER_ERROR'
        );
    }

    /**
     * Return a standardized JSON error response for rate limiting.
     *
     * @param string|null $message Custom error message. Defaults to 'Too many requests'.
     * @param int $retryAfter Seconds until retry is allowed.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the rate limit error details.
     */
    public static function tooManyRequests(?string $message = null, int $retryAfter = 60): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'statusCode' => 429,
            'message' => $message ?? 'Too many requests',
            'errorCode' => 'RATE_LIMITED',
            'retryAfter' => $retryAfter,
        ], 429);
    }
}
