<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AnalysisRateLimiterService;

/**
 * Polling Rate Limiter Middleware for TASK-19-14.
 *
 * Enforces rate limiting on the polling endpoint with:
 * - Per-job rate limiting (30 requests per minute per job)
 * - Admin bypass capability
 * - Rate limit headers on all responses
 * - 429 response with retry_after when limit exceeded
 *
 * Usage:
 * Route::get('/jobs/{jobId}/progress', [Controller::class, 'progress'])
 *     ->middleware('polling.ratelimit');
 */
class PollingRateLimiter
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
        $jobId = $request->route('jobId');

        // If no authenticated user, skip (let auth middleware handle)
        if ($user === null) {
            return $next($request);
        }

        // If no job ID in route, skip
        if ($jobId === null) {
            return $next($request);
        }

        // Ensure jobId is numeric
        if (!is_numeric($jobId)) {
            return $next($request);
        }

        // Check and increment rate limit
        $result = $this->rateLimiter->hitPollingRateLimit($user->id, (int) $jobId);

        // If rate limit exceeded, return 429
        if (!$result['allowed']) {
            return $this->buildRateLimitedResponse($result);
        }

        // Process request
        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB);
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
                'message' => "Polling rate limit exceeded. Retry after {$result['retry_after']} seconds.",
                'retry_after' => $result['retry_after'],
            ],
        ], 429);

        $response->headers->set('X-RateLimit-Limit', (string) AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $result['reset_at']);
        $response->headers->set('Retry-After', (string) $result['retry_after']);

        return $response;
    }
}
