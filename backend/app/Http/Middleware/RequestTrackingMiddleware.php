<?php

namespace App\Http\Middleware;

use App\Services\Admin\RequestTrackingService;
use Closure;
use DateTime;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Tracking Middleware (TASK-27-6)
 * 
 * Tracks API requests for usage analytics. Records request counts by endpoint,
 * method, and timestamp for aggregation. Uses lightweight Redis storage.
 * 
 * Features:
 * - Endpoint normalization (IDs and UUIDs replaced with {id})
 * - Configurable sample rates (global and per-endpoint)
 * - Health endpoint exclusion
 * - Graceful error handling (never throws)
 * - <5ms latency overhead
 * 
 * @see SPEC-027: Admin Dashboard (Ops + Usage)
 */
class RequestTrackingMiddleware
{
    /**
     * The request tracking service instance.
     */
    protected RequestTrackingService $service;

    /**
     * The global sample rate (0.0 to 1.0).
     */
    protected float $sampleRate;

    /**
     * Per-endpoint sample rates.
     */
    protected array $endpointSampleRates = [];

    /**
     * Whether tracking is enabled.
     */
    protected bool $enabled;

    /**
     * Default excluded endpoints (health checks).
     */
    protected const DEFAULT_EXCLUDED_ENDPOINTS = [
        'api/v1/health',
        'api/v1/health/*',
        'health',
        'healthz',
        'ping',
    ];

    /**
     * Create a new middleware instance.
     */
    public function __construct(
        RequestTrackingService $service,
        ?float $sampleRate = null
    ) {
        $this->service = $service;
        $this->enabled = config('request-tracking.enabled', true);
        $this->sampleRate = $this->clampSampleRate(
            $sampleRate ?? config('request-tracking.sample_rate', 1.0)
        );
        
        // Load per-endpoint sample rates from config
        $this->endpointSampleRates = config('request-tracking.endpoint_sample_rates', []);
        
        // Set excluded endpoints to 0% sample rate
        $excludedEndpoints = config('request-tracking.excluded_endpoints', self::DEFAULT_EXCLUDED_ENDPOINTS);
        foreach ($excludedEndpoints as $endpoint) {
            // Normalize endpoint (remove leading slash, wildcard support)
            $normalized = ltrim($endpoint, '/');
            $this->endpointSampleRates[$normalized] = 0.0;
        }
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tracking is disabled
        if (!$this->isEnabled()) {
            return $next($request);
        }

        // Get normalized endpoint
        $endpoint = $this->normalizeEndpoint($request->path());

        // Check if endpoint is excluded or should be sampled
        if (!$this->shouldTrack($endpoint)) {
            return $next($request);
        }

        // Record request BEFORE processing (so we track even if handler throws)
        try {
            $method = $request->method();
            $userId = $this->getUserId($request);
            $timestamp = new DateTime();

            $this->service->record($endpoint, $method, $userId, $timestamp);
        } catch (\Throwable $e) {
            // Log warning but continue
            Log::warning('Request tracking failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);
        }

        // Process request (tracking should not block)
        return $next($request);
    }

    /**
     * Normalize endpoint path by replacing IDs and UUIDs with {id}.
     * 
     * Examples:
     * - /api/v1/users/123 → api/v1/users/{id}
     * - /api/v1/analyses/550e8400-e29b-41d4-a716-446655440000 → api/v1/analyses/{id}
     * - /api/v1/sites/5/analyses/42 → api/v1/sites/{id}/analyses/{id}
     */
    protected function normalizeEndpoint(string $path): string
    {
        // Remove leading slash and query parameters
        $path = ltrim($path, '/');
        $path = preg_replace('/\?.*$/', '', $path);

        // Normalize numeric IDs (sequences of digits)
        $path = preg_replace('/\/\d+(?=\/|$)/', '/{id}', $path);

        // Normalize UUIDs (8-4-4-4-12 format)
        $path = preg_replace(
            '/\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}(?=\/|$)/i',
            '/{id}',
            $path
        );

        return $path;
    }

    /**
     * Determine if the endpoint should be tracked based on sample rate.
     */
    protected function shouldTrack(string $endpoint): bool
    {
        // Get effective sample rate for this endpoint
        $rate = $this->getEndpointSampleRate($endpoint);

        // 0% sample rate means never track
        if ($rate <= 0.0) {
            return false;
        }

        // 100% sample rate means always track
        if ($rate >= 1.0) {
            return true;
        }

        // Use random sampling for intermediate rates
        return (mt_rand() / mt_getrandmax()) < $rate;
    }

    /**
     * Get the user ID from the request, or null if unauthenticated.
     */
    protected function getUserId(Request $request): ?int
    {
        $user = $request->user();
        return $user?->id;
    }

    /**
     * Clamp sample rate to valid range [0.0, 1.0].
     */
    protected function clampSampleRate(float $rate): float
    {
        return max(0.0, min(1.0, $rate));
    }

    /**
     * Get the current global sample rate.
     */
    public function getSampleRate(): float
    {
        return $this->sampleRate;
    }

    /**
     * Set the global sample rate.
     */
    public function setSampleRate(float $rate): void
    {
        $this->sampleRate = $this->clampSampleRate($rate);
    }

    /**
     * Get the sample rate for a specific endpoint.
     * Falls back to global sample rate if not set.
     */
    public function getEndpointSampleRate(string $endpoint): float
    {
        // Check for exact match
        if (isset($this->endpointSampleRates[$endpoint])) {
            return $this->endpointSampleRates[$endpoint];
        }

        // Check for wildcard matches
        foreach ($this->endpointSampleRates as $pattern => $rate) {
            if (str_ends_with($pattern, '/*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($endpoint, $prefix)) {
                    return $rate;
                }
            }
        }

        // Fall back to global sample rate
        return $this->sampleRate;
    }

    /**
     * Set the sample rate for a specific endpoint.
     */
    public function setEndpointSampleRate(string $endpoint, float $rate): void
    {
        $this->endpointSampleRates[$endpoint] = $this->clampSampleRate($rate);
    }

    /**
     * Check if tracking is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable tracking.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable tracking.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }
}
