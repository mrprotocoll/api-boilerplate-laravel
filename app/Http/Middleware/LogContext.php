<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LogContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        Log::withContext([
            'request-id' => $requestId,
            'user_id' => auth()?->user()?->getAuthIdentifier(),
            'url' => $request->url(),
            'body' => $request->input(),
            'query' => $request->query(),
            'ip' => $request->ip(),
        ]);

        $request->headers->set('Request-Id', $requestId);

        return $next($request);
    }
}
