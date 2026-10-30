<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\PWAAnalysisJob;
use App\Models\PWAAnalysis;
use App\Models\Site;
use App\Repositories\PWAAnalysisRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * API Controller for PWA analysis endpoints.
 *
 * Provides endpoints to retrieve PWA analysis data and trigger new analyses.
 *
 * @see TASK-24-8
 * @see SPEC-024
 * @see TEST-24-8
 */
class PWAController extends Controller
{
    /**
     * Rate limit: maximum analyses per site per hour.
     */
    private const RATE_LIMIT_PER_HOUR = 10;

    /**
     * Cache TTL for GET responses (24 hours in seconds).
     */
    private const CACHE_TTL = 86400;

    /**
     * Create a new PWAController instance.
     */
    public function __construct(
        protected PWAAnalysisRepository $repository
    ) {
    }

    /**
     * Get PWA analysis for a site.
     *
     * GET /api/v1/sites/{siteId}/pwa
     *
     * @param int $siteId
     * @return JsonResponse
     */
    public function show(int $siteId): JsonResponse
    {
        $site = $this->findAndAuthorizeSite($siteId);

        if ($site instanceof JsonResponse) {
            return $site;
        }

        // Check cache first
        $cacheKey = "pwa-analysis:{$siteId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return response()->json([
                'success' => true,
                'data' => $cached,
            ]);
        }

        // Get latest analysis
        $analysis = $this->repository->getLatestForSite($siteId);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ANALYSIS_NOT_FOUND',
                    'message' => 'No PWA analysis found for this site.',
                    'suggestion' => "POST /api/v1/sites/{$siteId}/pwa/analyze to trigger an analysis.",
                ],
            ], 404);
        }

        $data = $this->formatAnalysisData($analysis);

        // Cache the result
        Cache::put($cacheKey, $data, self::CACHE_TTL);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Trigger a new PWA analysis.
     *
     * POST /api/v1/sites/{siteId}/pwa/analyze
     *
     * @param int $siteId
     * @return JsonResponse
     */
    public function analyze(int $siteId): JsonResponse
    {
        $site = $this->findAndAuthorizeSite($siteId);

        if ($site instanceof JsonResponse) {
            return $site;
        }

        // Check rate limit
        $rateLimitResponse = $this->checkRateLimit($siteId);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        // Invalidate cache
        $this->invalidateCache($siteId);

        // Generate job ID
        $jobId = 'pwa-analyze-' . Str::uuid()->toString();

        // Dispatch job
        PWAAnalysisJob::dispatch($siteId, $jobId);

        // Increment rate limit counter
        $this->incrementRateLimit($siteId);

        return response()->json([
            'success' => true,
            'data' => [
                'jobId' => $jobId,
                'status' => 'queued',
                'message' => 'PWA analysis queued for processing.',
            ],
        ], 202);
    }

    /**
     * Refresh an existing PWA analysis.
     *
     * POST /api/v1/sites/{siteId}/pwa/refresh
     *
     * @param int $siteId
     * @return JsonResponse
     */
    public function refresh(int $siteId): JsonResponse
    {
        $site = $this->findAndAuthorizeSite($siteId);

        if ($site instanceof JsonResponse) {
            return $site;
        }

        // Check rate limit
        $rateLimitResponse = $this->checkRateLimit($siteId);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        // Invalidate cache
        $this->invalidateCache($siteId);

        // Generate job ID
        $jobId = 'pwa-analyze-' . Str::uuid()->toString();

        // Dispatch job
        PWAAnalysisJob::dispatch($siteId, $jobId);

        // Increment rate limit counter
        $this->incrementRateLimit($siteId);

        return response()->json([
            'success' => true,
            'data' => [
                'jobId' => $jobId,
                'status' => 'queued',
                'message' => 'PWA analysis refresh queued for processing.',
            ],
        ], 202);
    }

    /**
     * Find a site and authorize access.
     *
     * @param int $siteId
     * @return Site|JsonResponse
     */
    protected function findAndAuthorizeSite(int $siteId): Site|JsonResponse
    {
        $site = Site::find($siteId);

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        // Check authorization via policy
        $user = request()->user();
        if ($site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        return $site;
    }

    /**
     * Check rate limit for analysis requests.
     *
     * @param int $siteId
     * @return JsonResponse|null
     */
    protected function checkRateLimit(int $siteId): ?JsonResponse
    {
        $cacheKey = "pwa-analyze:{$siteId}";
        $current = (int) Cache::get($cacheKey, 0);

        if ($current >= self::RATE_LIMIT_PER_HOUR) {
            $ttl = Cache::get("{$cacheKey}:ttl", 3600);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many analysis requests. Please try again later.',
                    'retryAfter' => $ttl,
                ],
            ], 429);
        }

        return null;
    }

    /**
     * Increment rate limit counter.
     *
     * @param int $siteId
     */
    protected function incrementRateLimit(int $siteId): void
    {
        $cacheKey = "pwa-analyze:{$siteId}";
        $current = (int) Cache::get($cacheKey, 0);

        if ($current === 0) {
            // First request, set with 1 hour TTL
            Cache::put($cacheKey, 1, 3600);
            Cache::put("{$cacheKey}:ttl", 3600, 3600);
        } else {
            // Increment existing counter
            Cache::increment($cacheKey);
        }
    }

    /**
     * Invalidate the analysis cache for a site.
     *
     * @param int $siteId
     */
    protected function invalidateCache(int $siteId): void
    {
        Cache::forget("pwa-analysis:{$siteId}");
    }

    /**
     * Format analysis data for API response.
     *
     * @param PWAAnalysis $analysis
     * @return array
     */
    protected function formatAnalysisData(PWAAnalysis $analysis): array
    {
        return [
            'hasManifest' => $analysis->has_manifest,
            'manifestUrl' => $analysis->manifest_url,
            'manifestValid' => $analysis->manifest_valid,
            'pwaScore' => $analysis->pwa_score,
            'readinessLevel' => $analysis->readiness_level,
            'fields' => $this->formatFields($analysis),
            'icons' => $this->formatIcons($analysis),
            'recommendations' => $this->generateRecommendations($analysis),
            'analyzedAt' => $analysis->analyzed_at?->toIso8601String(),
        ];
    }

    /**
     * Format manifest fields for API response.
     *
     * @param PWAAnalysis $analysis
     * @return array
     */
    protected function formatFields(PWAAnalysis $analysis): array
    {
        $manifest = $analysis->manifest_json ?? [];

        return [
            'name' => [
                'present' => $analysis->has_name,
                'value' => $manifest['name'] ?? null,
            ],
            'short_name' => [
                'present' => $analysis->has_short_name,
                'value' => $manifest['short_name'] ?? null,
            ],
            'start_url' => [
                'present' => $analysis->has_start_url,
                'value' => $manifest['start_url'] ?? null,
            ],
            'display' => [
                'present' => $analysis->has_display,
                'value' => $manifest['display'] ?? null,
            ],
            'background_color' => [
                'present' => $analysis->has_background_color,
                'value' => $manifest['background_color'] ?? null,
            ],
            'theme_color' => [
                'present' => $analysis->has_theme_color,
                'value' => $manifest['theme_color'] ?? null,
            ],
            'description' => [
                'present' => $analysis->has_description,
                'value' => $manifest['description'] ?? null,
            ],
        ];
    }

    /**
     * Format icon validation results for API response.
     *
     * @param PWAAnalysis $analysis
     * @return array
     */
    protected function formatIcons(PWAAnalysis $analysis): array
    {
        return [
            'total' => $analysis->icons_total,
            'valid' => $analysis->icons_valid,
            'required' => [
                '192x192' => [
                    'present' => $analysis->icons_192_present,
                ],
                '512x512' => [
                    'present' => $analysis->icons_512_present,
                ],
                '180x180' => [
                    'present' => $analysis->icons_180_present,
                ],
            ],
            'errors' => $analysis->icon_errors ?? [],
        ];
    }

    /**
     * Generate recommendations based on analysis gaps.
     *
     * @param PWAAnalysis $analysis
     * @return array
     */
    protected function generateRecommendations(PWAAnalysis $analysis): array
    {
        $recommendations = [];

        // High priority: no manifest
        if (!$analysis->has_manifest) {
            $recommendations[] = [
                'type' => 'missing_manifest',
                'field' => null,
                'message' => 'Add a web app manifest file to enable PWA features.',
                'priority' => 'high',
                'guide' => '#manifest',
            ];

            return $recommendations; // No other recommendations if no manifest
        }

        // High priority: invalid manifest
        if (!$analysis->manifest_valid) {
            $recommendations[] = [
                'type' => 'invalid_manifest',
                'field' => null,
                'message' => 'Fix manifest validation errors.',
                'priority' => 'high',
                'guide' => '#manifest-validation',
            ];
        }

        // High priority: missing required fields
        $requiredFields = [
            'name' => $analysis->has_name,
            'short_name' => $analysis->has_short_name,
            'start_url' => $analysis->has_start_url,
            'display' => $analysis->has_display,
        ];

        foreach ($requiredFields as $field => $present) {
            if (!$present) {
                $recommendations[] = [
                    'type' => 'missing_field',
                    'field' => $field,
                    'message' => "Add the '{$field}' field to your manifest.",
                    'priority' => 'high',
                    'guide' => "#manifest-{$field}",
                ];
            }
        }

        // High priority: missing required icons
        if (!$analysis->icons_192_present) {
            $recommendations[] = [
                'type' => 'missing_icon',
                'field' => '192x192',
                'message' => 'Add a 192x192 icon for PWA installation.',
                'priority' => 'high',
                'guide' => '#icons',
            ];
        }

        // Medium priority: missing 512x512 icon
        if (!$analysis->icons_512_present) {
            $recommendations[] = [
                'type' => 'missing_icon',
                'field' => '512x512',
                'message' => 'Add a 512x512 icon for better splash screens.',
                'priority' => 'medium',
                'guide' => '#icons',
            ];
        }

        // Medium priority: missing theme_color
        if (!$analysis->has_theme_color) {
            $recommendations[] = [
                'type' => 'missing_field',
                'field' => 'theme_color',
                'message' => 'Add theme_color to customize the browser UI.',
                'priority' => 'medium',
                'guide' => '#theme-color',
            ];
        }

        // Medium priority: missing background_color
        if (!$analysis->has_background_color) {
            $recommendations[] = [
                'type' => 'missing_field',
                'field' => 'background_color',
                'message' => 'Add background_color for the splash screen.',
                'priority' => 'medium',
                'guide' => '#background-color',
            ];
        }

        // Low priority: missing description
        if (!$analysis->has_description) {
            $recommendations[] = [
                'type' => 'missing_field',
                'field' => 'description',
                'message' => 'Add a description to your manifest.',
                'priority' => 'low',
                'guide' => '#description',
            ];
        }

        // Low priority: missing 180x180 icon (apple-touch-icon)
        if (!$analysis->icons_180_present) {
            $recommendations[] = [
                'type' => 'missing_icon',
                'field' => '180x180',
                'message' => 'Add a 180x180 icon for iOS devices.',
                'priority' => 'low',
                'guide' => '#icons',
            ];
        }

        return $recommendations;
    }
}
