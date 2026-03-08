<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Logging Middleware for TASK-6-6.
 *
 * Captures API request metadata and logs to database for analytics and debugging.
 * Supports async logging, configurable exclusion, and graceful error handling.
 */
class RequestLoggingMiddleware
{
    /**
     * Whether logging is enabled.
     */
    protected bool $enabled = true;

    /**
     * Endpoints to exclude from logging.
     *
     * @var array<int, string>
     */
    protected array $excludedEndpoints = [];

    /**
     * Whether to use async logging (dispatch).
     */
    protected bool $asyncLogging = true;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        $this->enabled = config('api-logging.enabled', true);
        $this->excludedEndpoints = config('api-logging.excluded_endpoints', [
            'api/v1/health',
            'api/v1/health/*',
        ]);
        $this->asyncLogging = config('api-logging.async_logging', true);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if disabled
        if (!$this->isEnabled()) {
            return $next($request);
        }

        // Skip excluded endpoints
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        // Skip non-API routes
        if (!$this->isApiRoute($request)) {
            return $next($request);
        }

        // Skip OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // Skip if no API key ID (not authenticated via API key)
        if (!$request->attributes->has('api_key_id')) {
            return $next($request);
        }

        $startTime = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            // Log the request even if an exception occurred
            $this->logRequest($request, null, $startTime);

            throw $e;
        }

        // Log the request
        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * Check if logging is enabled.
     * Config disabled takes precedence over runtime state.
     */
    public function isEnabled(): bool
    {
        // If config is disabled, always return false
        if (!config('api-logging.enabled', true)) {
            return false;
        }

        return $this->enabled;
    }

    /**
     * Enable logging (only effective if config is enabled).
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable logging.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Get retention days from config.
     */
    public function getRetentionDays(): int
    {
        return config('api-logging.retention_days', 30);
    }

    /**
     * Get excluded endpoints.
     *
     * @return array<int, string>
     */
    public function getExcludedEndpoints(): array
    {
        return $this->excludedEndpoints;
    }

    /**
     * Add an endpoint to the exclusion list.
     */
    public function addExcludedEndpoint(string $endpoint): void
    {
        $this->excludedEndpoints[] = $endpoint;
    }

    /**
     * Check if async logging is enabled.
     */
    public function isAsyncLogging(): bool
    {
        return config('api-logging.async_logging', true);
    }

    /**
     * Check if the request path is excluded from logging.
     */
    protected function isExcluded(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedEndpoints as $excluded) {
            // Support wildcard patterns
            if (str_contains($excluded, '*')) {
                // Convert wildcard to regex pattern
                // First escape special regex chars, then convert \* back to .*
                $pattern = preg_quote($excluded, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/', $path)) {
                    return true;
                }
            } elseif ($path === $excluded) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the request is an API route.
     */
    protected function isApiRoute(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/');
    }

    /**
     * Log the request to the database.
     */
    protected function logRequest(Request $request, ?Response $response, float $startTime): void
    {
        try {
            $data = [
                'api_key_id' => $request->attributes->get('api_key_id'),
                'endpoint' => '/' . $request->path(),
                'method' => $request->method(),
                'status_code' => $response?->getStatusCode(),
                'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'ip_address' => $this->getIpAddress($request),
                'user_agent' => $request->userAgent(),
            ];

            if ($this->isAsyncLogging()) {
                // Dispatch async to avoid blocking
                dispatch(function () use ($data) {
                    $this->createLog($data);
                });
            } else {
                $this->createLog($data);
            }
        } catch (\Throwable $e) {
            // Log error but don't fail the request
            Log::warning('Failed to log API request', [
                'error' => $e->getMessage(),
                'endpoint' => $request->path(),
            ]);
        }
    }

    /**
     * Get the client IP address, respecting X-Forwarded-For header.
     */
    protected function getIpAddress(Request $request): ?string
    {
        // Check X-Forwarded-For header first (for proxied requests)
        $forwardedFor = $request->headers->get('X-Forwarded-For');
        if ($forwardedFor) {
            // Get the first IP in the chain (original client)
            $ips = array_map('trim', explode(',', $forwardedFor));
            if (!empty($ips[0])) {
                return $ips[0];
            }
        }

        return $request->ip();
    }

    /**
     * Create a log entry (for testing/mocking purposes).
     */
    public function createLog(array $data): void
    {
        ApiRequestLog::create($data);
    }
}
