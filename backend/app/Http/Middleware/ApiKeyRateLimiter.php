<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ApiKeyRateLimiterService;
use App\Models\ApiKey;

/**
 * API Key Rate Limiter Middleware for TASK-6-5.
 *
 * Enforces per-API-key rate limiting on API endpoints.
 * Uses sliding window algorithm for accurate rate limiting.
 *
 * Features:
 * - Per-API-key configurable limits (from ApiKey model)
 * - Rate limit headers on all responses
 * - 429 response with retry_after when limit exceeded
 * - Automatic cleanup of expired rate limit entries
 *
 * Must run AFTER authentication middleware to have access to API key.
 *
 * @see \App\Services\ApiKeyRateLimiterService
 */
class ApiKeyRateLimiter
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected ApiKeyRateLimiterService $rateLimiter
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
        // Get the authenticated API key
        $apiKey = $this->getApiKey($request);

        // If no API key, skip rate limiting (let auth middleware handle)
        if ($apiKey === null) {
            return $next($request);
        }

        // Get rate limit for this key
        $maxRequests = $this->getMaxRequests($apiKey);

        // Default window: 60 seconds (1 minute)
        $windowMs = 60000;

        // Generate unique key for this API key
        $rateLimitKey = $this->resolveRequestSignature($apiKey);

        // Check and increment rate limit
        $result = $this->rateLimiter->hit($rateLimitKey, $maxRequests, $windowMs);

        // Add rate limit headers to response
        $response = $next($request);

        // Add headers
        $response->headers->set('X-RateLimit-Limit', (string) $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', (string) $result['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $result['reset_at']);

        // If rate limit exceeded, return 429
        if (!$result['allowed']) {
            return $this->buildResponse($rateLimitKey, $maxRequests, $windowMs);
        }

        return $response;
    }

    /**
     * Handle rate limit exceeded after response.
     *
     * This is called when we need to return a 429 response.
     *
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse
     */
    public function handleRateLimited(Request $request, Closure $next): JsonResponse
    {
        $apiKey = $this->getApiKey($request);

        if ($apiKey === null) {
            return $next($request);
        }

        $maxRequests = $this->getMaxRequests($apiKey);
        $windowMs = 60000;
        $rateLimitKey = $this->resolveRequestSignature($apiKey);

        return $this->buildResponse($rateLimitKey, $maxRequests, $windowMs);
    }

    /**
     * Get the API key from the request.
     *
     * @param Request $request
     * @return ApiKey|null
     */
    protected function getApiKey(Request $request): ?ApiKey
    {
        // Check for API key set by auth middleware
        if ($request->attributes->has('api_key')) {
            $apiKey = $request->attributes->get('api_key');
            if ($apiKey instanceof ApiKey) {
                return $apiKey;
            }
        }

        // Check for authenticated user with API key
        if ($request->user() && $request->attributes->has('current_api_key')) {
            $apiKey = $request->attributes->get('current_api_key');
            if ($apiKey instanceof ApiKey) {
                return $apiKey;
            }
        }

        return null;
    }

    /**
     * Resolve the rate limit key for the request.
     *
     * Uses API key ID for per-key rate limiting.
     *
     * @param ApiKey $apiKey
     * @return string
     */
    protected function resolveRequestSignature(ApiKey $apiKey): string
    {
        return 'api_key:' . $apiKey->id;
    }

    /**
     * Get the maximum requests for the API key.
     *
     * @param ApiKey $apiKey
     * @return int
     */
    protected function getMaxRequests(ApiKey $apiKey): int
    {
        // Use API key's rate_limit if set, otherwise default
        return $apiKey->rate_limit ?? ApiKey::DEFAULT_RATE_LIMIT;
    }

    /**
     * Build the rate limit exceeded response.
     *
     * Returns 429 with JSON body matching SPEC-006 format.
     *
     * @param string $rateLimitKey
     * @param int $maxRequests
     * @param int $windowMs
     * @return JsonResponse
     */
    protected function buildResponse(string $rateLimitKey, int $maxRequests, int $windowMs): JsonResponse
    {
        $retryAfter = $this->rateLimiter->getRetryAfter($rateLimitKey, $windowMs);
        $resetTime = $this->rateLimiter->getResetTime($rateLimitKey, $windowMs);

        $response = new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => "Rate limit exceeded. Retry after {$retryAfter} seconds.",
                'retry_after' => $retryAfter,
            ],
        ], 429);

        // Add headers
        $response->headers->set('X-RateLimit-Limit', (string) $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }
}
