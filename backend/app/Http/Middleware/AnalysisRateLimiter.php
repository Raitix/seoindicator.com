<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AnalysisRateLimiterService;

/**
 * Analysis Rate Limiter Middleware for TASK-19-14.
 *
 * Enforces rate limiting on analysis API endpoints with:
 * - Per-user rate limiting (60 requests per minute)
 * - Admin bypass capability
 * - Rate limit headers on all responses
 * - 429 response with retry_after when limit exceeded
 *
 * Usage:
 * Route::middleware(['analysis.ratelimit'])->group(function () {
 *     // Analysis endpoints
 * });
 */
class AnalysisRateLimiter
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected AnalysisRateLimiterService $rateLimiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        // If no authenticated user, skip (let auth middleware handle)
        if ($user === null) {
            return $next($request);
        }

        // Check and increment rate limit
        $result = $this->rateLimiter->hitApiRateLimit($user->id);

        // If rate limit exceeded, return 429
        if (!$result['allowed']) {
            return $this->buildRateLimitedResponse($result);
        }

        // Process request
        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE);
        $response->headers->set('X-RateLimit-Remaining', (string) $result['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $result['reset_at']);

        return $response;
    }

    /**
     * Build a rate limit exceeded response.
     *
     * @param array $result
     * @return JsonResponse
     */
    protected function buildRateLimitedResponse(array $result): JsonResponse
    {
        $response = new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => "Rate limit exceeded. Retry after {$result['retry_after']} seconds.",
                'retry_after' => $result['retry_after'],
            ],
        ], 429);

        $response->headers->set('X-RateLimit-Limit', (string) AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $result['reset_at']);
        $response->headers->set('Retry-After', (string) $result['retry_after']);

        return $response;
    }
}
