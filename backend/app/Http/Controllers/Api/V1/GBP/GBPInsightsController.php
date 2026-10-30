<?php

namespace App\Http\Controllers\Api\V1\GBP;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GBP\GBPInsightsRequest;
use App\Http\Resources\Api\V1\GBP\GBPInsightsResource;
use App\Http\Resources\Api\V1\GBP\GBPTrendResource;
use App\Models\Location;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @Task TASK-35-14
 * @Description GBP Insights API Controller
 */
class GBPInsightsController extends Controller
{
    /**
     * Get insights for a location.
     *
     * @param int $id Location ID
     * @param GBPInsightsRequest $request
     * @param GBPInsightsServiceInterface $service
     * @return JsonResponse
     */
    public function index(int $id, GBPInsightsRequest $request, GBPInsightsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('view', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        // Determine date range (default: last 30 days)
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::yesterday();

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : $endDate->copy()->subDays(29);

        // Get insights from service
        $insights = $service->getInsights($location->id, $startDate->toDateString(), $endDate->toDateString());

        return response()->json([
            'data' => new GBPInsightsResource($insights),
            'daily_breakdown' => $insights['daily_breakdown'] ?? [],
            'totals' => $insights['totals'] ?? ['queries' => 0, 'views' => 0, 'actions' => 0],
        ])->header('X-RateLimit-Limit', 60);
    }

    /**
     * Get trend analysis for a location.
     *
     * @param int $id Location ID
     * @param Request $request
     * @param GBPInsightsServiceInterface $service
     * @return JsonResponse
     */
    public function trend(int $id, Request $request, GBPInsightsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('view', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        // Get days parameter (default: 30)
        $days = min((int) $request->input('days', 30), 90);

        // Get trend from service
        $trend = $service->getTrend($location->id, $days);

        return response()->json([
            'data' => new GBPTrendResource($trend),
            'wow' => $trend['wow'] ?? [],
            'mom' => $trend['mom'] ?? [],
        ])->header('X-RateLimit-Limit', 60);
    }

    /**
     * Trigger a background sync of insights.
     *
     * @param int $id Location ID
     * @param GBPInsightsServiceInterface $service
     * @return JsonResponse
     */
    public function sync(int $id, GBPInsightsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('sync', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        try {
            $jobId = $service->syncInsights($location->id);

            return response()->json([
                'message' => 'Insights sync initiated.',
                'job_id' => $jobId,
            ], 202);
        } catch (\Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json([
                    'message' => 'A sync is already in progress for this location.',
                ], 409);
            }

            return response()->json([
                'message' => 'Failed to initiate insights sync.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
