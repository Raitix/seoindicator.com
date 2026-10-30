<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\AISuggestionCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin AI Cache Controller.
 *
 * Provides admin endpoints for managing the AI suggestion cache,
 * including invalidation, clearing, and statistics.
 *
 * @task TASK-21-6
 */
class AICacheController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AISuggestionCache $cacheService
    ) {}

    /**
     * Invalidate specific cache entry or entries by indicator.
     *
     * POST /api/v1/admin/ai/cache/invalidate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function invalidate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hash' => ['sometimes', 'string', 'max:255'],
            'indicator' => ['sometimes', 'string', 'in:' . implode(',', $this->cacheService->getValidIndicators())],
        ]);

        // Require at least one of hash or indicator
        if (!isset($validated['hash']) && !isset($validated['indicator'])) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'hash' => ['The hash field is required when indicator is not present.'],
                    'indicator' => ['The indicator field is required when hash is not present.'],
                ],
            ], 422);
        }

        // Invalidate by hash
        if (isset($validated['hash'])) {
            $result = $this->cacheService->invalidate($validated['hash']);
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cache entry not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cache entry invalidated',
            ]);
        }

        // Invalidate by indicator
        if (isset($validated['indicator'])) {
            $count = $this->cacheService->invalidateByIndicator($validated['indicator']);
            
            return response()->json([
                'success' => true,
                'message' => "Invalidated {$count} cache entries for indicator: {$validated['indicator']}",
                'invalidated_count' => $count,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Either hash or indicator is required',
        ], 422);
    }

    /**
     * Clear all AI suggestion cache.
     *
     * DELETE /api/v1/admin/ai/cache
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        $count = $this->cacheService->clear();
        
        return response()->json([
            'success' => true,
            'message' => 'All AI suggestion cache cleared',
            'cleared_count' => $count,
        ]);
    }

    /**
     * Get cache statistics.
     *
     * GET /api/v1/admin/ai/cache/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $stats = $this->cacheService->getStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Reset statistics counters.
     *
     * POST /api/v1/admin/ai/cache/stats/reset
     *
     * @return JsonResponse
     */
    public function resetStats(): JsonResponse
    {
        $this->cacheService->resetStats();
        
        return response()->json([
            'success' => true,
            'message' => 'Cache statistics reset',
        ]);
    }
}
