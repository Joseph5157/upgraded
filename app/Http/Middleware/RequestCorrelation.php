<?php

namespace App\Http\Middleware;

use App\Support\LogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestCorrelation
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $startedAt = microtime(true);

        $request->attributes->set('request_id', $requestId);

        // Logging must never break request handling.
        try {
            Log::withContext(['request_id' => $requestId]);
            Log::info('request.started', LogContext::fromRequest($request));
        } catch (Throwable) {
        }

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            try {
                Log::warning('request.failed', array_merge(
                    LogContext::fromRequest($request),
                    [
                        'exception' => class_basename($e),
                        'message' => $e->getMessage(),
                        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                    ],
                ));
            } catch (Throwable) {
            }

            throw $e;
        }

        $response->headers->set('X-Request-Id', $requestId);

        try {
            Log::info(
                'request.finished',
                LogContext::withResponse($request, $response->getStatusCode(), (microtime(true) - $startedAt) * 1000)
            );
        } catch (Throwable) {
        }

        return $response;
    }
}
