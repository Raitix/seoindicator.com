<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AI\AIRateLimiter as AIRateLimiterService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * AI Rate Limiter Middleware.
 *
 * Handles rate limiting for AI suggestion endpoints.
 * Applies per-user limits for authenticated users and per-IP limits for anonymous requests.
 *
 * @see TASK-21-10: Add Rate Limiting
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
     * @return SymfonyResponse
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Skip rate limiting for OPTIONS requests
        if ($request->method() === 'OPTIONS') {
            return $next($request);
        }

        // Determine identifier and type
        [$identifier, $type] = $this->resolveIdentifierAndType($request);

        // Check if rate limit is exceeded
        if (!$this->rateLimiter->checkLimit($identifier, $type)) {
            return $this->buildLimitExceededResponse($identifier, $type);
        }

        // Process request
        $response = $next($request);

        // Increment rate limit on successful processing
        $this->rateLimiter->increment($identifier, $type);

        // Add rate limit headers
        $this->addRateLimitHeaders($response, $identifier, $type);

        return $response;
    }

    /**
     * Resolve the identifier and type for rate limiting.
     *
     * @param Request $request
     * @return array{0: string, 1: string} [identifier, type]
     */
    protected function resolveIdentifierAndType(Request $request): array
    {
        // Check for authenticated user
        if ($user = $request->user()) {
            // Check admin whitelist
            if ($this->isAdminWhitelisted($user)) {
                return ["admin:{$user->id}", 'user'];
            }
            
            return ["user:{$user->id}", 'user'];
        }

        // Fall back to IP-based limiting
        $ip = $this->resolveClientIp($request);
        
        return ["ip:{$ip}", 'ip'];
    }

    /**
     * Check if user is whitelisted from rate limiting (admin users).
     *
     * @param mixed $user
     * @return bool
     */
    protected function isAdminWhitelisted($user): bool
    {
        if (!config('ai.rate_limits.admin_whitelist', true)) {
            return false;
        }

        // Check if user has admin role
        return method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * Resolve the client IP address.
     *
     * @param Request $request
     * @return string
     */
    protected function resolveClientIp(Request $request): string
    {
        // Check X-Forwarded-For header (for proxied requests)
        $forwarded = $request->header('X-Forwarded-For');
        
        if ($forwarded) {
            // X-Forwarded-For can contain multiple IPs: client, proxy1, proxy2
            // We want the first one (client)
            $ips = explode(',', $forwarded);
            $clientIp = trim($ips[0]);
            
            // Validate IP format
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // Fall back to direct connection IP
        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Build the 429 Too Many Requests response.
     *
     * @param string $identifier
     * @param string $type
     * @return SymfonyResponse
     */
    protected function buildLimitExceededResponse(string $identifier, string $type): SymfonyResponse
    {
        $resetTime = $this->rateLimiter->getResetTime($identifier, $type);
        $retryAfter = max(1, $resetTime - time());
        $limit = $this->rateLimiter->getLimit($type);

        $response = response()->json([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => "Rate limit exceeded. Try again in {$this->formatRetryAfter($retryAfter)}.",
                'retry_after' => $retryAfter,
            ],
        ], 429);

        // Add headers
        $response->header('Retry-After', (string) $retryAfter);
        $response->header('X-RateLimit-Limit', (string) $limit);
        $response->header('X-RateLimit-Remaining', '0');
        $response->header('X-RateLimit-Reset', (string) $resetTime);

        return $response;
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param SymfonyResponse $response
     * @param string $identifier
     * @param string $type
     */
    protected function addRateLimitHeaders(SymfonyResponse $response, string $identifier, string $type): void
    {
        $limit = $this->rateLimiter->getLimit($type);
        $remaining = $this->rateLimiter->getRemaining($identifier, $type);
        $resetTime = $this->rateLimiter->getResetTime($identifier, $type);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
    }

    /**
     * Format retry after seconds into human-readable string.
     *
     * @param int $seconds
     * @return string
     */
    protected function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }
        
        $minutes = (int) ($seconds / 60);
        
        if ($minutes < 60) {
            return "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        }
        
        $hours = (int) ($minutes / 60);
        return "{$hours} hour" . ($hours > 1 ? 's' : '');
    }
}
