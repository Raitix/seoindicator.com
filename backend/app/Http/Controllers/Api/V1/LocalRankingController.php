<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessLocation;
use App\Models\Keyword;
use App\Models\LocalCompetitor;
use App\Models\LocalRanking;
use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * LocalRankingController - API endpoints for local SEO rank tracking.
 *
 * TASK-35-15: Local Rankings API Controller
 *
 * Endpoints:
 * - GET /api/v1/locations/{id}/rankings - Current rankings
 * - GET /api/v1/locations/{id}/rankings/history - Historical rankings
 * - POST /api/v1/locations/{id}/rankings/check - Trigger ranking check (all keywords)
 * - POST /api/v1/locations/{id}/rankings/check/{keywordId} - Check single keyword
 * - GET /api/v1/locations/{id}/rankings/quota - Monthly quota usage
 * - GET /api/v1/locations/{id}/competitors - List tracked competitors
 * - POST /api/v1/locations/{id}/competitors - Add competitor
 * - DELETE /api/v1/locations/{id}/competitors/{competitorId} - Remove competitor
 */
class LocalRankingController extends Controller
{
    /**
     * Monthly quota limit per location.
     */
    protected const MONTHLY_QUOTA_LIMIT = 50;

    /**
     * Maximum competitors per location.
     */
    protected const MAX_COMPETITORS = 20;

    protected LocalRankServiceInterface $rankService;

    public function __construct(LocalRankServiceInterface $rankService)
    {
        $this->rankService = $rankService;
    }

    /**
     * Get current rankings for a location's keywords.
     *
     * GET /api/v1/locations/{id}/rankings
     */
    public function rankings(int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        // Check authorization via policy
        $this->authorize('viewRankings', $location);

        // Get the site and its keywords
        $site = $location->site;
        $keywords = $site ? $site->keywords()->where('status', 'active')->get() : collect();

        // Get latest ranking for each keyword
        $rankings = $keywords->map(function ($keyword) use ($location) {
            $latestRanking = LocalRanking::where('location_id', $location->id)
                ->where('keyword_id', $keyword->id)
                ->orderByDesc('checked_at')
                ->first();

            return [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'local_pack_position' => $latestRanking?->local_pack_position,
                'local_organic_position' => $latestRanking?->local_organic_position,
                'competitors_in_pack' => $latestRanking?->competitors_in_pack ?? [],
                'last_checked' => $latestRanking?->checked_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'rankings' => $rankings,
            ],
        ]);
    }

    /**
     * Get historical ranking data for a location.
     *
     * GET /api/v1/locations/{id}/rankings/history
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('viewRankings', $location);

        // Validate query parameters
        $validated = $request->validate([
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'keyword_id' => ['nullable', 'integer', 'exists:keywords,id'],
        ]);

        $startDate = $validated['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $validated['end_date'] ?? now()->format('Y-m-d');

        $query = LocalRanking::where('location_id', $location->id)
            ->whereBetween('checked_at', [$startDate, $endDate . ' 23:59:59']);

        if (!empty($validated['keyword_id'])) {
            $query->where('keyword_id', $validated['keyword_id']);
        }

        $history = $query->orderByDesc('checked_at')->get()->groupBy('keyword_id');

        $formattedHistory = $history->map(function ($rankings, $keywordId) {
            $firstRanking = $rankings->first();

            return [
                'keyword_id' => $keywordId,
                'keyword' => $firstRanking?->keyword?->keyword,
                'snapshots' => $rankings->map(fn ($r) => [
                    'date' => $r->checked_at->format('Y-m-d'),
                    'local_pack_position' => $r->local_pack_position,
                    'local_organic_position' => $r->local_organic_position,
                    'competitors_in_pack' => $r->competitors_in_pack ?? [],
                ])->values(),
            ];
        })->values();

        $response = [
            'success' => true,
            'data' => [
                'location_id' => $location->id,
                'history' => $formattedHistory,
            ],
        ];

        if (!empty($validated['keyword_id'])) {
            $response['data']['keyword_id'] = $validated['keyword_id'];
        }

        return response()->json($response);
    }

    /**
     * Trigger ranking check for all keywords of a location.
     *
     * POST /api/v1/locations/{id}/rankings/check
     */
    public function checkAll(int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('checkRankings', $location);

        // Check quota
        $usedQuota = $this->rankService->getMonthlyCheckCount($location->id);

        if ($usedQuota >= self::MONTHLY_QUOTA_LIMIT) {
            return response()->json([
                'success' => false,
                'error' => 'Quota exceeded',
                'data' => [
                    'reset_date' => now()->addMonth()->startOfMonth()->toDateString(),
                ],
            ], 429)->header('Retry-After', now()->diffInSeconds(now()->addMonth()->startOfMonth()));
        }

        // Get keywords
        $site = $location->site;
        $keywords = $site ? $site->keywords()->where('status', 'active')->get() : collect();

        if ($keywords->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No keywords to check',
            ]);
        }

        // Check if remaining quota is sufficient
        $remainingQuota = self::MONTHLY_QUOTA_LIMIT - $usedQuota;
        $keywordsToCheck = $keywords->take($remainingQuota);

        // In a real implementation, this would dispatch jobs to a queue
        // For now, we return a job ID for status tracking
        $jobId = (string) Str::uuid();

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'location_id' => $location->id,
                'keywords_queued' => $keywordsToCheck->count(),
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Trigger ranking check for a single keyword.
     *
     * POST /api/v1/locations/{id}/rankings/check/{keywordId}
     */
    public function checkSingle(int $id, int $keywordId): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('checkRankings', $location);

        // Find the keyword and verify it belongs to the location's site
        $keyword = Keyword::find($keywordId);

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => 'Keyword not found',
            ], 404);
        }

        // Verify keyword belongs to location's site
        if ($keyword->site_id !== $location->site_id) {
            return response()->json([
                'success' => false,
                'error' => 'Keyword does not belong to this location',
            ], 403);
        }

        // Check quota
        $usedQuota = $this->rankService->getMonthlyCheckCount($location->id);

        if ($usedQuota >= self::MONTHLY_QUOTA_LIMIT) {
            return response()->json([
                'success' => false,
                'error' => 'Quota exceeded',
                'data' => [
                    'reset_date' => now()->addMonth()->startOfMonth()->toDateString(),
                ],
            ], 429)->header('Retry-After', now()->diffInSeconds(now()->addMonth()->startOfMonth()));
        }

        // In a real implementation, this would dispatch a job
        $jobId = (string) Str::uuid();

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'location_id' => $location->id,
                'keyword_id' => $keyword->id,
                'keywords_queued' => 1,
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Get monthly quota usage for a location.
     *
     * GET /api/v1/locations/{id}/rankings/quota
     */
    public function quota(int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('viewQuota', $location);

        $usedQuota = $this->rankService->getMonthlyCheckCount($location->id);
        $remaining = max(0, self::MONTHLY_QUOTA_LIMIT - $usedQuota);

        return response()->json([
            'success' => true,
            'data' => [
                'location_id' => $location->id,
                'monthly_limit' => self::MONTHLY_QUOTA_LIMIT,
                'used_this_month' => $usedQuota,
                'remaining' => $remaining,
                'reset_date' => now()->addMonth()->startOfMonth()->toDateString(),
            ],
        ]);
    }

    /**
     * List tracked competitors for a location.
     *
     * GET /api/v1/locations/{id}/competitors
     */
    public function competitors(int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('viewCompetitors', $location);

        $competitors = LocalCompetitor::where('location_id', $location->id)->get();

        $formatted = $competitors->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->competitor_name,
            'google_place_id' => $c->competitor_gbp_id,
            'address' => $c->competitor_address,
            'rating' => $c->competitor_rating ? (float) $c->competitor_rating : null,
            'review_count' => $c->competitor_review_count,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'location_id' => $location->id,
                'competitors' => $formatted,
            ],
        ]);
    }

    /**
     * Add a competitor for tracking.
     *
     * POST /api/v1/locations/{id}/competitors
     */
    public function addCompetitor(Request $request, int $id): JsonResponse
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found',
            ], 404);
        }

        $this->authorize('addCompetitor', $location);

        // Check max limit
        $currentCount = LocalCompetitor::where('location_id', $location->id)->count();
        if ($currentCount >= self::MAX_COMPETITORS) {
            return response()->json([
                'success' => false,
                'error' => 'Maximum competitors limit reached',
                'errors' => ['competitors' => ['Maximum ' . self::MAX_COMPETITORS . ' competitors allowed per location']],
            ], 422);
        }

        // Validate input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'google_place_id' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check for duplicate
        $exists = LocalCompetitor::where('location_id', $location->id)
            ->where('competitor_gbp_id', $validated['google_place_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'Competitor already exists',
                'errors' => ['google_place_id' => ['This competitor is already being tracked']],
            ], 422);
        }

        // Sanitize name
        $name = strip_tags($validated['name']);

        $competitor = LocalCompetitor::create([
            'location_id' => $location->id,
            'competitor_name' => $name,
            'competitor_gbp_id' => $validated['google_place_id'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'competitor' => [
                    'id' => $competitor->id,
                    'name' => $competitor->competitor_name,
                    'google_place_id' => $competitor->competitor_gbp_id,
                ],
            ],
        ], 201);
    }

    /**
     * Remove a competitor from tracking.
     *
     * DELETE /api/v1/locations/{id}/competitors/{competitorId}
     */
    public function deleteCompetitor(int $id, int $competitorId): Response
    {
        $location = BusinessLocation::find($id);

        if (!$location) {
            return response()->noContent(404);
        }

        $this->authorize('deleteCompetitor', $location);

        $competitor = LocalCompetitor::where('location_id', $location->id)
            ->where('id', $competitorId)
            ->first();

        if (!$competitor) {
            return response()->noContent(404);
        }

        $competitor->delete();

        return response()->noContent();
    }
}
