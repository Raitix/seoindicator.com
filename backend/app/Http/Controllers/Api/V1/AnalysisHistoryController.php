<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnalysisHistory;
use Illuminate\Http\JsonResponse;

/**
 * Analysis History API Controller.
 *
 * Provides endpoints for retrieving analysis history records.
 * Implements IDOR protection to ensure users can only access their own analyses.
 *
 * @covers TASK-25-14
 */
class AnalysisHistoryController extends Controller
{
    /**
     * Get a single analysis by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $analysis = AnalysisHistory::find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Analysis not found.',
                ],
            ], 404);
        }

        // IDOR protection: Only allow access to own analyses
        $userId = auth()->id();
        
        // Allow access if:
        // 1. User owns the analysis, OR
        // 2. Analysis has no user (anonymous) - allow any authenticated user
        if ($analysis->user_id !== null && $analysis->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this analysis.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $analysis->id,
                'user_id' => $analysis->user_id,
                'site_id' => $analysis->site_id,
                'tool_name' => $analysis->tool_name,
                'input_url' => $analysis->input_url,
                'status' => $analysis->status,
                'results' => $analysis->results,
                'error_message' => $analysis->error_message,
                'analysis_duration_ms' => $analysis->analysis_duration_ms,
                'analyzed_at' => $analysis->analyzed_at?->toIso8601String(),
                'created_at' => $analysis->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * List analyses for the authenticated user.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();
        
        $analyses = AnalysisHistory::byUser($userId)
            ->latestFirst()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $analyses->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'tool_name' => $analysis->tool_name,
                    'input_url' => $analysis->input_url,
                    'status' => $analysis->status,
                    'analysis_duration_ms' => $analysis->analysis_duration_ms,
                    'analyzed_at' => $analysis->analyzed_at?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $analyses->currentPage(),
                'last_page' => $analyses->lastPage(),
                'per_page' => $analyses->perPage(),
                'total' => $analyses->total(),
            ],
        ]);
    }
}
