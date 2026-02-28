<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SerpFeatureService;
use App\Models\Keyword;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SerpFeatureController for SERP feature API endpoints.
 *
 * TASK-7-10: SERP Features API Endpoints
 * TASK-7-11: Feature Summary Endpoint
 */
class SerpFeatureController extends Controller
{
    protected SerpFeatureService $serpFeatureService;

    public function __construct(SerpFeatureService $serpFeatureService)
    {
        $this->serpFeatureService = $serpFeatureService;
    }

    /**
     * Check if user owns the site (IDOR protection).
     *
     * @param Site $site
     * @return bool
     */
    protected function userOwnsSite(Site $site): bool
    {
        return $site->user_id === auth()->id();
    }

    /**
     * Check if user owns the keyword (IDOR protection).
     *
     * @param Keyword $keyword
     * @return bool
     */
    protected function userOwnsKeyword(Keyword $keyword): bool
    {
        // Check direct ownership
        if ($keyword->user_id === auth()->id()) {
            return true;
        }
        // Check ownership via site
        if ($keyword->site && $keyword->site->user_id === auth()->id()) {
            return true;
        }
        return false;
    }

    /**
     * Get SERP feature summary for a site.
     *
     * GET /api/v1/sites/:siteId/serp-features/summary
     *
     * @param Request $request
     * @param int $siteId
     * @return JsonResponse
     */
    public function summary(Request $request, int $siteId): JsonResponse
    {
        // Validate siteId
        if ($siteId <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid site ID. Must be a positive integer.',
                ],
            ], 400);
        }

        // Check if site exists
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SITE_NOT_FOUND',
                    'message' => "Site with ID {$siteId} not found",
                ],
            ], 404);
        }

        // IDOR protection - TASK-7-10-SEC
        if (!$this->userOwnsSite($site)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Validate days parameter
        $validator = Validator::make($request->all(), [
            'days' => [
                'sometimes',
                'integer',
                'min:1',
                'max:365',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid days parameter. Must be an integer between 1 and 365.',
                    'details' => $validator->errors(),
                ],
            ], 400);
        }

        $days = (int) $request->query('days', 7);

        // Ensure days is within valid range
        $days = max(1, min(365, $days));

        $result = $this->serpFeatureService->getSummary($siteId, $days);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $result['error'],
                    'message' => $result['message'],
                ],
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * Get SERP features for a specific keyword.
     *
     * GET /api/v1/keywords/:id/serp-features
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function keywordFeatures(Request $request, int $id): JsonResponse
    {
        // Validate keywordId
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid keyword ID. Must be a positive integer.',
                ],
            ], 400);
        }

        // Check if keyword exists and user owns it - TASK-7-10-SEC
        $keyword = Keyword::with('site')->find($id);
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'KEYWORD_NOT_FOUND',
                    'message' => "Keyword with ID {$id} not found",
                ],
            ], 404);
        }

        // IDOR protection - TASK-7-10-SEC
        if (!$this->userOwnsKeyword($keyword)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ], 403);
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'from' => ['sometimes', 'date', 'before_or_equal:to'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid query parameters.',
                    'details' => $validator->errors(),
                ],
            ], 400);
        }

        $options = [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'days' => (int) $request->query('days', 30),
        ];

        $result = $this->serpFeatureService->getKeywordFeatures($id, $options);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $result['error'],
                    'message' => $result['message'],
                ],
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * Get SERP features for a site with pagination.
     *
     * GET /api/v1/sites/:siteId/serp-features
     *
     * @param Request $request
     * @param int $siteId
     * @return JsonResponse
     */
    public function siteFeatures(Request $request, int $siteId): JsonResponse
    {
        // Validate siteId
        if ($siteId <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid site ID. Must be a positive integer.',
                ],
            ], 400);
        }

        // Check if site exists
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SITE_NOT_FOUND',
                    'message' => "Site with ID {$siteId} not found",
                ],
            ], 404);
        }

        // IDOR protection - TASK-7-10-SEC
        if (!$this->userOwnsSite($site)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Validate pagination parameters
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid pagination parameters.',
                    'details' => $validator->errors(),
                ],
            ], 400);
        }

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 20);
        // Cap perPage to maximum of 100
        $perPage = min(max(1, $perPage), 100);

        $result = $this->serpFeatureService->getSiteFeatures($siteId, $page, $perPage);

        return response()->json($result);
    }

    /**
     * Invalidate summary cache for a site.
     *
     * DELETE /api/v1/sites/:siteId/serp-features/cache
     *
     * @param int $siteId
     * @return JsonResponse
     */
    public function invalidateCache(int $siteId): JsonResponse
    {
        // Validate siteId
        if ($siteId <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid site ID.',
                ],
            ], 400);
        }

        // Check if site exists - TASK-7-10-SEC
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SITE_NOT_FOUND',
                    'message' => "Site with ID {$siteId} not found",
                ],
            ], 404);
        }

        // IDOR protection - TASK-7-10-SEC
        if (!$this->userOwnsSite($site)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $this->serpFeatureService->invalidateSummaryCache($siteId);

        return response()->json([
            'success' => true,
            'message' => "Cache invalidated for site {$siteId}",
        ]);
    }
}
