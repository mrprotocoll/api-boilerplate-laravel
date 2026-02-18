<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Shared\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

final class PreventDuplicateRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $lockTimeInSeconds = 10): Response
    {
        // Skip for GET requests (they should be idempotent)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Create a unique key for this request
        $key = $this->generateRequestKey($request);

        // Check if this request is already being processed
        if (Cache::has($key)) {
            return ResponseHelper::error('Duplicate request detected. Please wait before trying again.', status: 429); // Too Many Requests
        }

        // Lock this request
        Cache::put($key, true, $lockTimeInSeconds);

        try {
            // Process the request
            $response = $next($request);

            // If the request failed (4xx or 5xx), release the lock immediately
            // so the user can retry if needed
            if ($response->getStatusCode() >= 400) {
                Cache::forget($key);
            }

            return $response;
        } catch (Exception $e) {
            // Release the lock on exception
            Cache::forget($key);
            throw $e;
        }
    }

    /**
     * Generate a unique key for the request based on multiple factors
     */
    private function generateRequestKey(Request $request): string
    {
        // Base components for the key
        $components = [
            $request->method(),
            $request->getPathInfo(),
            $request->ip(),
        ];

        // Add user ID if authenticated
        if ($request->user()) {
            $components[] = 'user:' . $request->user()->id;
        } else {
            // For non-authenticated requests, use session ID if available
            if ($request->hasSession() && $request->session()->isStarted()) {
                $components[] = 'session:' . $request->session()->getId();
            } else {
                // For API routes without session, use User-Agent as additional identifier
                $userAgent = $request->userAgent();
                if ($userAgent) {
                    $components[] = 'ua:' . md5($userAgent);
                }
            }
        }

        // Add request body hash for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $body = $request->getContent();
            if ($body) {
                $components[] = 'body:' . md5($body);
            }
        }

        // Create the cache key
        $key = 'duplicate_request:' . md5(implode('|', $components));

        return $key;
    }
}
