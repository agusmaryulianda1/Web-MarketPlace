<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiagnosticRequestTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $requestStartedAt = defined('LARAVEL_START') ? LARAVEL_START : $startedAt;
        $requestId = bin2hex(random_bytes(8));

        $request->attributes->set('perf_diagnostic', [
            'request_id' => $requestId,
            'started_at' => $requestStartedAt,
            'query_count' => 0,
            'query_total_ms' => 0.0,
            'query_max_ms' => 0.0,
        ]);

        try {
            $response = $next($request);
        } finally {
            $state = $request->attributes->get('perf_diagnostic', []);
            $totalMs = (microtime(true) - ($state['started_at'] ?? $startedAt)) * 1000;
            $downstreamMs = (microtime(true) - $startedAt) * 1000;

            logger()->info('[PERF_DIAGNOSTIC] request', [
                'request_id' => $state['request_id'] ?? $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'status' => isset($response) ? $response->getStatusCode() : 500,
                'inertia' => $request->headers->has('X-Inertia'),
                'total_ms' => round($totalMs, 2),
                'downstream_ms' => round($downstreamMs, 2),
                'query_count' => $state['query_count'] ?? 0,
                'query_total_ms' => round($state['query_total_ms'] ?? 0.0, 2),
                'query_max_ms' => round($state['query_max_ms'] ?? 0.0, 2),
            ]);
        }

        return $response;
    }
}