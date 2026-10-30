<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UserDashboardController - User-scoped dashboard endpoints.
 *
 * Provides dashboard data for authenticated users including stats,
 * recent analyses, cost summary, and quick insights.
 *
 * @see TASK-25-15
 * @see SPEC-025
 */
class UserDashboardController extends Controller
{
    /**
     * Create a new UserDashboardController instance.
     *
     * @param DashboardAggregator $aggregator
     */
    public function __construct(
        protected DashboardAggregator $aggregator
    ) {}

    /**
     * Get full dashboard data for the authenticated user.
     *
     * GET /api/v1/dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $this->aggregator->getDashboardData($userId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * Get stats for the authenticated user.
     *
     * GET /api/v1/dashboard/stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = $this->aggregator->getStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200, [
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * Get recent analyses for the authenticated user.
     *
     * GET /api/v1/dashboard/recent-analyses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentAnalyses(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $analyses = $this->aggregator->getRecentAnalyses($userId);

        return response()->json([
            'success' => true,
            'data' => $analyses,
        ], 200, [
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * Get cost summary for the authenticated user.
     *
     * GET /api/v1/dashboard/cost-summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function costSummary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $summary = $this->aggregator->getCostSummary($userId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ], 200, [
            'Cache-Control' => 'private, max-age=60',
        ]);
    }
}
