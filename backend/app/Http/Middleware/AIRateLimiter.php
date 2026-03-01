<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AI\AIRateLimiter as AIRateLimiterService;

/**
 * AI Rate Limiter Middleware (TASK-21-10).
 *
 * Enforces rate limiting on AI suggestion endpoints.
 * Uses sliding window algorithm for accurate rate limiting.
 *
 * Features:
 * - Per-user rate limiting for authenticated users
 * - Per-IP rate limiting for anonymous requests
 * - Rate limit headers on all responses
 * - 429 response with retry_after when limit exceeded
 * - Admin whitelist support
 *
 * @see \App\Services\AI\AIRateLimiter
 */
class AIRateLimiter
{
    /**
     * Create a new middleware instance.
     *
     * @param AIRateLimiterService $rateLimiter
     */
    public function __construct(
        protected AIRateLimiterService $rateLimiter
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
        // Skip OPTIONS requests (CORS preflight)
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // Get identifier and type based on authentication status
        [$identifier, $type] = $this->resolveIdentifier($request);

        // Check if admin whitelist applies
        if ($this->isAdminWhitelisted($request, $type)) {
            return $next($request);
        }

        // Check rate limit
        if (!$this->rateLimiter->checkLimit($identifier, $type)) {
            return $this->buildLimitExceededResponse($identifier, $type);
        }

        // Process request
        $response = $next($request);

        // Increment rate limit after successful processing
        // Only increment on successful responses (2xx)
        if ($response->isSuccessful()) {
            $this->rateLimiter->increment($identifier, $type);
        }

        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $identifier, $type);

        return $response;
    }

    /**
     * Resolve the rate limit identifier and type from the request.
     *
     * Authenticated users are limited by user ID.
     * Anonymous requests are limited by IP address.
     *
     * @param Request $request
     * @return array{0: string, 1: string} [identifier, type]
     */
    protected function resolveIdentifier(Request $request): array
    {
        $user = $request->user();

        if ($user && $user->exists) {
            // Authenticated user - use user ID
            return ["user:{$user->id}", 'user'];
        }

        // Anonymous - use IP address
        $ip = $this->resolveIpAddress($request);
        return ["ip:{$ip}", 'ip'];
    }

    /**
     * Resolve the client IP address from the request.
     *
     * Checks X-Forwarded-For header first, then falls back to REMOTE_ADDR.
     * Uses the first IP in X-Forwarded-For (client IP).
     *
     * @param Request $request
     * @return string
     */
    protected function resolveIpAddress(Request $request): string
    {
        // Check X-Forwarded-For header
        $forwarded = $request->header('X-Forwarded-For');
        
        if ($forwarded) {
            // X-Forwarded-For format: client, proxy1, proxy2
            $ips = explode(',', $forwarded);
            $clientIp = trim($ips[0]);
            
            // Validate IP format
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // Fallback to REMOTE_ADDR or Laravel's ip() method
        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Check if the request should bypass rate limiting due to admin whitelist.
     *
     * @param Request $request
     * @param string $type The rate limit type ('user' or 'ip')
     * @return bool
     */
    protected function isAdminWhitelisted(Request $request, string $type): bool
    {
        // Only applies to authenticated users
        if ($type !== 'user') {
            return false;
        }

        // Check if whitelist is enabled
        if (!$this->rateLimiter->isAdminWhitelistEnabled()) {
            return false;
        }

        $user = $request->user();
        
        // Check if user has admin role (if applicable)
        if ($user && method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Check for is_admin column/property
        if ($user && isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        return false;
    }

    /**
     * Build the rate limit exceeded response.
     *
     * Returns HTTP 429 with JSON body and appropriate headers.
     *
     * @param string $identifier The rate limit identifier
     * @param string $type The rate limit type
     * @return JsonResponse
     */
    protected function buildLimitExceededResponse(string $identifier, string $type): JsonResponse
    {
        $limit = $this->rateLimiter->getLimit($type);
        $resetTime = $this->rateLimiter->getResetTime($identifier, $type);
        $retryAfter = $this->rateLimiter->getRetryAfter($identifier, $type);

        // Format friendly message
        $minutes = (int) ceil($retryAfter / 60);
        $message = $minutes > 1
            ? "Rate limit exceeded. Try again in {$minutes} minutes."
            : "Rate limit exceeded. Try again in {$retryAfter} seconds.";

        $response = new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => $message,
                'retry_after' => $retryAfter,
            ],
        ], 429);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param mixed $response
     * @param string $identifier
     * @param string $type
     * @return void
     */
    protected function addRateLimitHeaders($response, string $identifier, string $type): void
    {
        if (!method_exists($response, 'headers')) {
            return;
        }

        $limit = $this->rateLimiter->getLimit($type);
        $remaining = $this->rateLimiter->getRemaining($identifier, $type);
        $resetTime = $this->rateLimiter->getResetTime($identifier, $type);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
    }
}
