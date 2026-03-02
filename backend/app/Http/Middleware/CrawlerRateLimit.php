<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Crawler\RateLimiterService;
use App\Models\User;

/**
 * Rate limiting middleware for crawler API endpoints.
 *
 * Checks all rate limits before allowing crawler requests:
 * - User daily limit
 * - User concurrent limit
 * - Per-domain rate limit
 * - Global concurrent limit
 *
 * Admin users bypass user limits only (not domain/global).
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class CrawlerRateLimit
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected RateLimiterService $rateLimiter
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
        // Get authenticated user
        $user = $request->user();

        // If no user, return 401 Unauthorized
        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        // Get user ID
        $userId = $user->id;

        // Check if user is admin
        $isAdmin = $this->rateLimiter->isAdmin($user);

        // Extract domain from request
        $domain = $this->extractDomain($request);

        // Check all rate limits
        $checkResult = $this->rateLimiter->checkAllLimits($userId, $domain, $isAdmin);

        // If not allowed, return 429 Too Many Requests
        if (!$checkResult['allowed']) {
            $reason = $checkResult['reason'];
            $result = $checkResult['results'][$reason] ?? null;

            if ($result === null) {
                // Fallback to global limit
                $result = $checkResult['results']['global'] ?? $this->rateLimiter->checkGlobalLimit();
            }

            return $this->rateLimitExceededResponse($result, $reason);
        }

        // Process request
        $response = $next($request);

        // Add rate limit headers to response
        $this->addHeaders($response, $userId, $isAdmin);

        return $response;
    }

    /**
     * Extract target domain from request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractDomain(Request $request): ?string
    {
        // Check for domain in header (X-Target-Domain)
        $domain = $request->header('X-Target-Domain');

        if (!empty($domain)) {
            return $domain;
        }

        // Check for URL in request body
        $url = $request->input('url');

        if (!empty($url)) {
            return $this->parseDomainFromUrl($url);
        }

        // Check for domain in request body
        $domain = $request->input('domain');

        if (!empty($domain)) {
            return $domain;
        }

        return null;
    }

    /**
     * Parse domain from a URL string.
     *
     * @param string $url
     * @return string|null
     */
    protected function parseDomainFromUrl(string $url): ?string
    {
        $parsed = parse_url($url);

        if (isset($parsed['host'])) {
            return $parsed['host'];
        }

        // If no scheme, try prepending http://
        if (!isset($parsed['scheme'])) {
            $parsed = parse_url('http://' . $url);
            return $parsed['host'] ?? null;
        }

        return null;
    }

    /**
     * Add rate limit headers to response.
     *
     * @param mixed $response
     * @param int $userId
     * @param bool $isAdmin
     */
    protected function addHeaders(mixed $response, int $userId, bool $isAdmin): void
    {
        if (!method_exists($response, 'headers')) {
            return;
        }

        $headers = $this->rateLimiter->getHeaders($userId, $isAdmin);

        foreach ($headers as $name => $value) {
            $response->headers->set($name, (string) $value);
        }
    }

    /**
     * Build unauthorized response.
     *
     * @return JsonResponse
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    /**
     * Build rate limit exceeded response.
     *
     * @param mixed $result RateLimitResult
     * @param string $reason Reason for blocking
     * @return JsonResponse
     */
    protected function rateLimitExceededResponse(mixed $result, string $reason): JsonResponse
    {
        $message = $this->getRateLimitMessage($reason);

        $response = new JsonResponse([
            'message' => $message,
            'limit' => $result->limit,
            'remaining' => $result->remaining,
            'resetAt' => $result->resetAt->toIso8601ZuluString(),
            'retryAfter' => $result->retryAfter,
        ], 429);

        // Add rate limit headers
        $headers = $result->getHeaders();
        foreach ($headers as $name => $value) {
            $response->headers->set($name, (string) $value);
        }

        return $response;
    }

    /**
     * Get human-readable message for rate limit reason.
     *
     * @param string $reason
     * @return string
     */
    protected function getRateLimitMessage(string $reason): string
    {
        return match ($reason) {
            'daily' => 'Daily crawl limit exceeded. Please try again tomorrow.',
            'concurrent' => 'Maximum concurrent crawl jobs reached. Please wait for existing jobs to complete.',
            'domain' => 'Domain rate limit exceeded. Please wait before requesting this domain again.',
            'global' => 'Service is currently at capacity. Please try again shortly.',
            default => 'Rate limit exceeded.',
        };
    }
}
