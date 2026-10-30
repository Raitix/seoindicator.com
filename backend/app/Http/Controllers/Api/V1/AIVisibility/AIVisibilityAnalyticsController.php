<?php

namespace App\Http\Controllers\Api\V1\AIVisibility;

use App\Http\Controllers\Controller;
use App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * AI Visibility Analytics Controller.
 *
 * Handles REST API endpoints for AI Visibility analytics data.
 *
 * @see TASK-34-14
 * @see SPEC-034
 */
class AIVisibilityAnalyticsController extends Controller
{
    protected AnalyticsAggregatorInterface $analyticsAggregator;

    /**
     * Valid period values for analytics endpoint.
     */
    private const VALID_PERIODS = ['day', 'week', 'month'];

    /**
     * Valid platform values.
     */
    private const VALID_PLATFORMS = ['chatgpt', 'perplexity', 'gemini', 'claude', 'copilot'];

    /**
     * Maximum days for trend endpoint.
     */
    private const MAX_TREND_DAYS = 365;

    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Create a new controller instance.
     */
    public function __construct(AnalyticsAggregatorInterface $analyticsAggregator)
    {
        $this->analyticsAggregator = $analyticsAggregator;
    }

    /**
     * Get aggregated analytics for a project.
     *
     * GET /api/v1/ai-visibility/projects/{id}/analytics
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        // Validate project exists and user owns it
        $project = $this->analyticsAggregator->getProject($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => 'Project not found.',
            ], 404);
        }

        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this project.',
            ], 403);
        }

        // Validate query parameters
        $period = $request->input('period', 'week');
        $platform = $request->input('platform');

        if (!in_array($period, self::VALID_PERIODS)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid period. Must be one of: day, week, month.',
                'message' => 'Invalid period. Must be one of: day, week, month.',
            ], 422);
        }

        if ($platform !== null && !in_array($platform, self::VALID_PLATFORMS)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid platform. Must be one of: ' . implode(', ', self::VALID_PLATFORMS) . '.',
            ], 422);
        }

        // Check ETag for cache validation
        $cacheKey = $this->getCacheKey($id, 'analytics', compact('period', 'platform'));

        // Get analytics data with caching
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id, $period, $platform) {
            return $this->analyticsAggregator->getAnalytics($id, $period, $platform);
        });

        $etag = md5(json_encode($data));

        if ($request->hasHeader('If-None-Match') && $request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('ETag', $etag);
    }

    /**
     * Get brand vs competitors comparison.
     *
     * GET /api/v1/ai-visibility/projects/{id}/comparison
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function comparison(Request $request, int $id): JsonResponse
    {
        // Validate project exists and user owns it
        $project = $this->analyticsAggregator->getProject($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => 'Project not found.',
            ], 404);
        }

        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this project.',
            ], 403);
        }

        // Get comparison data with caching
        $cacheKey = $this->getCacheKey($id, 'comparison');
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->analyticsAggregator->getComparison($id);
        });

        $etag = md5(json_encode($data));

        if ($request->hasHeader('If-None-Match') && $request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('ETag', $etag);
    }

    /**
     * Get trend analysis over time.
     *
     * GET /api/v1/ai-visibility/projects/{id}/trend
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function trend(Request $request, int $id): JsonResponse
    {
        // Validate project exists and user owns it
        $project = $this->analyticsAggregator->getProject($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => 'Project not found.',
            ], 404);
        }

        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this project.',
            ], 403);
        }

        // Validate days parameter
        $days = $request->input('days', 90);

        if (!is_numeric($days) || (int) $days < 1) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid days parameter. Must be a positive integer.',
            ], 422);
        }

        $days = (int) $days;

        if ($days > self::MAX_TREND_DAYS) {
            return response()->json([
                'success' => false,
                'error' => 'Days parameter exceeds maximum of ' . self::MAX_TREND_DAYS . '.',
            ], 422);
        }

        // Get trend data with caching
        $cacheKey = $this->getCacheKey($id, 'trend', ['days' => $days]);
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id, $days) {
            return $this->analyticsAggregator->getTrend($id, $days);
        });

        $etag = md5(json_encode($data));

        if ($request->hasHeader('If-None-Match') && $request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('ETag', $etag);
    }

    /**
     * Get paginated query history.
     *
     * GET /api/v1/ai-visibility/projects/{id}/queries
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function queries(Request $request, int $id): JsonResponse
    {
        // Validate project exists and user owns it
        $project = $this->analyticsAggregator->getProject($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => 'Project not found.',
            ], 404);
        }

        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this project.',
            ], 403);
        }

        // Validate query parameters
        $page = $request->input('page', 1);
        $platform = $request->input('platform');
        $mentionedOnly = $request->boolean('mentioned_only', false);

        if (!is_numeric($page) || (int) $page < 1) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid page parameter. Must be a positive integer.',
            ], 422);
        }

        $page = (int) $page;

        if ($platform !== null && !in_array($platform, self::VALID_PLATFORMS)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid platform. Must be one of: ' . implode(', ', self::VALID_PLATFORMS) . '.',
            ], 422);
        }

        // Get queries data with caching
        $cacheKey = $this->getCacheKey($id, 'queries', compact('page', 'platform', 'mentionedOnly'));
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id, $page, $platform, $mentionedOnly) {
            return $this->analyticsAggregator->getQueries($id, $page, $platform, $mentionedOnly);
        });

        $etag = md5(json_encode($data));

        if ($request->hasHeader('If-None-Match') && $request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? $data,
            'meta' => $data['meta'] ?? [],
        ])->header('ETag', $etag);
    }

    /**
     * Generate a cache key for the given parameters.
     *
     * @param int $projectId
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    private function getCacheKey(int $projectId, string $endpoint, array $params = []): string
    {
        $paramsString = empty($params) ? '' : ':' . md5(json_encode($params));
        return "ai_visibility:{$projectId}:{$endpoint}{$paramsString}";
    }
}
