<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GSCData;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GSC Data Controller for TASK-12-8.
 *
 * Exposes GSC metrics and opportunity keywords via API.
 */
class GSCDataController extends Controller
{
    /**
     * List GSC data for a site with date filters.
     *
     * GET /api/v1/projects/{projectId}/gsc
     */
    public function index(int $projectId, Request $request): JsonResponse
    {
        $user = $request->user();

        $site = Site::where('id', $projectId)
            ->where('user_id', $user->id)
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Project not found.',
                ],
            ], 404);
        }

        $perPage = (int) ($request->query('perPage', 20));
        $perPage = max(1, min(100, $perPage));

        $query = GSCData::where('site_id', $site->id)
            ->orderByDesc('data_date')
            ->orderByDesc('clicks');

        $from = $request->query('from');
        $to = $request->query('to');

        if ($from && $to) {
            $query->whereBetween('data_date', [$from, $to]);
        } elseif ($from) {
            $query->where('data_date', '>=', $from);
        } elseif ($to) {
            $query->where('data_date', '<=', $to);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'perPage' => $paginated->perPage(),
                'lastPage' => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * List opportunity keywords for a site.
     *
     * GET /api/v1/projects/{projectId}/gsc/opportunities
     */
    public function opportunities(int $projectId, Request $request): JsonResponse
    {
        $user = $request->user();

        $site = Site::where('id', $projectId)
            ->where('user_id', $user->id)
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Project not found.',
                ],
            ], 404);
        }

        $impressionThreshold = (int) $request->query('impressions', 1000);
        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min(200, $limit));

        $data = GSCData::where('site_id', $site->id)
            ->opportunities($impressionThreshold)
            ->orderByDesc('impressions')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
