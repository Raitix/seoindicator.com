<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Comparison Controller for SPEC-003-5.
 */
class ComparisonController extends Controller
{
    protected ComparisonService $service;

    public function __construct(ComparisonService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/rankings/comparison
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 20);

        $allowedFilters = ['all', 'im-winning', 'theyre-winning', 'gap', 'both-rank', 'only-i-rank'];
        if (!in_array($filter, $allowedFilters, true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_FILTER',
                    'message' => 'Invalid filter value.',
                ],
            ], 400);
        }

        $summary = $this->service->getSummary($user->id);
        $comparison = $this->service->getComparisonResults($user->id, $filter, $page, $perPage);

        return response()->json([
            'summary' => $summary,
            'results' => $comparison['results'],
            'meta' => $comparison['meta'],
        ]);
    }

    /**
     * GET /api/v1/competitors/{id}/rankings
     */
    public function competitorRankings(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 20);

        $data = $this->service->getCompetitorRankings($user->id, $id, $page, $perPage);

        if (!$data['competitor']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Competitor not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'competitor' => [
                    'id' => $data['competitor']->id,
                    'domain' => $data['competitor']->domain,
                    'displayName' => $data['competitor']->display_name,
                ],
                'rankings' => $data['rankings'],
                'meta' => $data['meta'],
            ],
        ]);
    }

    /**
     * GET /api/v1/competitors/{id}/gaps
     */
    public function competitorGaps(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 20);

        $data = $this->service->getCompetitorGaps($user->id, $id, $page, $perPage);

        if (!$data['competitor']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Competitor not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'gaps' => $data['gaps'],
            'meta' => $data['meta'],
        ]);
    }

    /**
     * GET /api/v1/rankings/comparison/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $keywordId = (int) $request->query('keywordId');
        $days = (int) $request->query('days', 30);
        $competitorId = $request->query('competitorId');

        if ($keywordId <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_KEYWORD',
                    'message' => 'keywordId is required.',
                ],
            ], 400);
        }

        $history = $this->service->getHistory($user->id, $keywordId, $days, $competitorId ? (int) $competitorId : null);

        if (!$history['keyword']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
