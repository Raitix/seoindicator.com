<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreKeywordApiRequest;
use App\Http\Requests\Api\V1\UpdateKeywordApiRequest;
use App\Models\ApiKey;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Repositories\KeywordRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Keywords API Controller for TASK-6-8.
 *
 * REST API endpoints for keywords CRUD operations.
 * Uses API key authentication (not session).
 *
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 *
 * @see \App\Http\Middleware\ApiKeyAuth
 * @see \App\Http\Middleware\ApiKeyRateLimiter
 */
class KeywordsApiController extends Controller
{
    /**
     * Maximum keywords per site.
     */
    protected int $maxKeywords = 500;

    protected KeywordRepository $keywordRepository;

    public function __construct(KeywordRepository $keywordRepository)
    {
        $this->keywordRepository = $keywordRepository;
    }

    /**
     * List keywords for the authenticated user.
     *
     * GET /api/v1/keywords
     */
    public function index(Request $request): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        // Query parameters
        $siteId = $request->query('site_id');
        $status = $request->query('status');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        // Build query - only user's keywords
        $query = Keyword::where('user_id', $user->id)
            ->where('status', '!=', 'deleted');

        // Filter by site_id (only if user owns the site)
        if ($siteId) {
            $site = Site::where('id', $siteId)
                ->where('user_id', $user->id)
                ->first();

            if (!$site) {
                // Return empty result if site doesn't belong to user
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'page' => $page,
                        'per_page' => $perPage,
                        'total_pages' => 0,
                    ],
                ]);
            }

            $query->where('site_id', $siteId);
        }

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Get total count before pagination
        $total = $query->count();
        $totalPages = (int) ceil($total / $perPage);

        // Paginate
        $keywords = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format response with ranking data
        $data = $keywords->map(function (Keyword $keyword) {
            return $this->formatKeyword($keyword);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    /**
     * Create a new keyword.
     *
     * POST /api/v1/keywords
     */
    public function store(StoreKeywordApiRequest $request): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        $siteId = (int) $request->input('site_id');

        // Verify site ownership
        $site = Site::where('id', $siteId)
            ->where('user_id', $user->id)
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Check keyword limit
        $currentCount = Keyword::where('site_id', $siteId)
            ->where('status', '!=', 'deleted')
            ->count();

        if ($currentCount >= $this->maxKeywords) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'KEYWORD_LIMIT_EXCEEDED',
                    'message' => "Maximum {$this->maxKeywords} active keywords allowed.",
                ],
            ], 400);
        }

        // Sanitize keyword
        $keywordText = strip_tags(trim((string) $request->input('keyword')));
        $searchEngine = $request->input('search_engine', 'google');
        $location = $request->input('location', 'us');
        $device = $request->input('device', 'desktop');

        // Check for duplicate
        $duplicateExists = Keyword::where('site_id', $siteId)
            ->where('keyword', $keywordText)
            ->where('search_engine', $searchEngine)
            ->where('location', $location)
            ->where('device', $device)
            ->where('status', '!=', 'deleted')
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'KEYWORD_EXISTS',
                    'message' => 'This keyword is already being tracked for this site.',
                ],
            ], 409);
        }

        // Create keyword
        $keyword = $this->keywordRepository->create([
            'site_id' => $siteId,
            'user_id' => $user->id,
            'keyword' => $keywordText,
            'target_url' => $request->input('target_url'),
            'search_engine' => $searchEngine,
            'location' => $location,
            'device' => $device,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword),
        ], 201);
    }

    /**
     * Get a single keyword.
     *
     * GET /api/v1/keywords/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        // First check if keyword exists (even if deleted)
        $keyword = Keyword::where('id', $id)->first();

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        // Check if keyword belongs to the user
        if ($keyword->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ], 403);
        }

        // Check if keyword is deleted
        if ($keyword->status === 'deleted') {
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
            'data' => $this->formatKeyword($keyword, true),
        ]);
    }

    /**
     * Update a keyword.
     *
     * PUT /api/v1/keywords/{id}
     */
    public function update(UpdateKeywordApiRequest $request, int $id): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        $keyword = Keyword::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'deleted')
            ->first();

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        // Check if any fields provided
        if (!$request->hasAny(['target_url', 'status'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'No data provided for update.',
                ],
            ], 400);
        }

        // Update keyword
        $updateData = [];
        if ($request->has('target_url')) {
            $updateData['target_url'] = $request->input('target_url');
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->input('status');
        }

        $this->keywordRepository->update($id, $updateData);

        // Refresh and return
        $keyword = Keyword::find($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword),
        ]);
    }

    /**
     * Delete a keyword.
     *
     * DELETE /api/v1/keywords/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        $keyword = Keyword::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'deleted')
            ->first();

        if (!$keyword) {
            return response()->noContent(404);
        }

        // Check keep_history parameter
        $keepHistoryParam = $request->query('keep_history', 'true');
        $keepHistory = filter_var($keepHistoryParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($keepHistory === null) {
            $keepHistory = true;
        }

        // Delete rankings if requested
        if (!$keepHistory) {
            Ranking::where('keyword_id', $keyword->id)->delete();
        }

        // Soft delete via status
        $this->keywordRepository->delete($keyword->id);

        return response()->noContent();
    }

    /**
     * Get the API key from the request.
     */
    protected function getApiKey(Request $request): ApiKey
    {
        $apiKey = $request->attributes->get('api_key')
            ?? $request->attributes->get('current_api_key');

        if (!$apiKey instanceof ApiKey) {
            abort(401, 'API key not found in request.');
        }

        return $apiKey;
    }

    /**
     * Format keyword for API response.
     */
    protected function formatKeyword(Keyword $keyword, bool $includeDetails = false): array
    {
        // Get latest rankings
        $rankings = $keyword->rankingHistory()
            ->orderBy('checked_at', 'desc')
            ->take(2)
            ->get();

        $currentRanking = $rankings->get(0);
        $previousRanking = $rankings->get(1);

        $currentPosition = $currentRanking?->position;
        $previousPosition = $previousRanking?->position;

        $data = [
            'id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'site_id' => $keyword->site_id,
            'target_url' => $keyword->target_url,
            'search_engine' => $keyword->search_engine,
            'location' => $keyword->location,
            'device' => $keyword->device,
            'status' => $keyword->status,
            'current_position' => $currentPosition,
            'previous_position' => $previousPosition,
            'position_change' => $this->calculatePositionChange($currentPosition, $previousPosition),
            'last_checked_at' => $currentRanking?->checked_at?->toISOString(),
        ];

        if ($includeDetails) {
            $data['created_at'] = $keyword->created_at?->toISOString();
            $data['updated_at'] = $keyword->updated_at?->toISOString();
        }

        return $data;
    }

    /**
     * Calculate position change.
     */
    protected function calculatePositionChange(?int $current, ?int $previous): ?int
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return $current - $previous;
    }
}
