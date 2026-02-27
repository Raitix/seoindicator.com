<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreKeywordApiRequest;
use App\Http\Requests\Api\V1\UpdateKeywordApiRequest;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Models\ApiKey;
use App\Repositories\KeywordRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Keywords API Controller for TASK-6-8.
 *
 * REST API endpoints for external integrations.
 * Uses API key authentication instead of session.
 *
 * Endpoints:
 * - GET /api/v1/keywords - List keywords
 * - POST /api/v1/keywords - Create keyword
 * - GET /api/v1/keywords/{id} - Get single keyword
 * - PUT /api/v1/keywords/{id} - Update keyword
 * - DELETE /api/v1/keywords/{id} - Delete keyword
 *
 * Authentication: Bearer token (API Key)
 * Permissions: keywords:read, keywords:write
 */
class KeywordsApiController extends Controller
{
    protected KeywordRepository $keywordRepository;

    /**
     * Maximum keywords per site.
     */
    protected int $maxKeywords = 500;

    /**
     * Create a new controller instance.
     */
    public function __construct(KeywordRepository $keywordRepository)
    {
        $this->keywordRepository = $keywordRepository;
    }

    /**
     * Get the authenticated API key from the request.
     */
    protected function getApiKey(Request $request): ?ApiKey
    {
        return $request->attributes->get('api_key');
    }

    /**
     * Get the authenticated user from the API key.
     */
    protected function getUser(Request $request)
    {
        $apiKey = $this->getApiKey($request);
        return $apiKey?->user;
    }

    /**
     * List keywords for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        // Build query
        $query = Keyword::where('user_id', $user->id)
            ->where('status', '!=', 'deleted');

        // Filter by site_id
        $siteId = $request->query('site_id');
        if ($siteId !== null) {
            // Only allow filtering by user's own sites
            $site = Site::where('id', $siteId)
                ->where('user_id', $user->id)
                ->first();
            if ($site) {
                $query->where('site_id', $siteId);
            } else {
                // If site doesn't belong to user, return empty
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'page' => 1,
                        'per_page' => 20,
                        'total_pages' => 0,
                    ],
                ]);
            }
        }

        // Filter by status
        $status = $request->query('status');
        if ($status !== null && $status !== 'all') {
            $query->where('status', $status);
        }

        // Pagination
        $perPage = (int) $request->query('per_page', 20);
        $perPage = min(max(1, $perPage), 100); // Clamp 1-100
        $page = max(1, (int) $request->query('page', 1));

        // Get total count before pagination
        $total = $query->count();
        $totalPages = (int) ceil($total / $perPage);

        // Get paginated results
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
                'total_pages' => max(1, $totalPages),
            ],
        ]);
    }

    /**
     * Create a new keyword.
     *
     * @param StoreKeywordApiRequest $request
     * @return JsonResponse
     */
    public function store(StoreKeywordApiRequest $request): JsonResponse
    {
        $user = $this->getUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        $siteId = (int) $request->input('site_id');

        // Verify site ownership
        $site = Site::where('id', $siteId)
            ->where('user_id', $user->id)
            ->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The selected site does not exist or you do not have access to it.',
                ],
            ], 422);
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
                    'message' => "Maximum {$this->maxKeywords} keywords allowed per site.",
                ],
            ], 400);
        }

        // Sanitize keyword
        $keywordText = strip_tags(trim((string) $request->input('keyword')));
        $searchEngine = $request->input('search_engine', 'google');
        $location = $request->input('location', 'us');
        $device = $request->input('device', 'desktop');

        // Check for duplicates
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
                    'message' => 'This keyword already exists for this site with the same configuration.',
                ],
            ], 409);
        }

        // Create keyword using repository (emits webhook event)
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
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        $keyword = Keyword::find($id);

        if (!$keyword) {
            return $this->notFoundResponse();
        }

        // Check authorization using policy
        if (!Gate::forUser($user)->allows('view', $keyword)) {
            return $this->forbiddenResponse();
        }

        // Check if deleted
        if ($keyword->status === 'deleted') {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword),
        ]);
    }

    /**
     * Update a keyword.
     *
     * @param UpdateKeywordApiRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateKeywordApiRequest $request, int $id): JsonResponse
    {
        $user = $this->getUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        $keyword = Keyword::find($id);

        if (!$keyword) {
            return $this->notFoundResponse();
        }

        // Check authorization using policy
        if (!Gate::forUser($user)->allows('update', $keyword)) {
            return $this->forbiddenResponse();
        }

        // Check if deleted
        if ($keyword->status === 'deleted') {
            return $this->notFoundResponse();
        }

        // Validate that at least one field is provided
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

        // Refresh keyword
        $keyword = Keyword::find($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword),
        ]);
    }

    /**
     * Delete a keyword.
     *
     * @param Request $request
     * @param int $id
     * @return Response|JsonResponse
     */
    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        $user = $this->getUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        $keyword = Keyword::find($id);

        if (!$keyword) {
            return response()->noContent(404);
        }

        // Check authorization using policy
        if (!Gate::forUser($user)->allows('delete', $keyword)) {
            return $this->forbiddenResponse();
        }

        // Check if already deleted
        if ($keyword->status === 'deleted') {
            return response()->noContent(404);
        }

        // Handle keep_history parameter
        $keepHistoryParam = $request->query('keep_history', 'true');
        $keepHistory = filter_var($keepHistoryParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($keepHistory === null) {
            $keepHistory = true;
        }

        // Delete rankings if keep_history is false
        if (!$keepHistory) {
            Ranking::where('keyword_id', $keyword->id)->delete();
        }

        // Soft delete via status
        $keyword->status = 'deleted';
        $keyword->save();

        return response()->noContent();
    }

    /**
     * Format keyword for API response.
     *
     * @param Keyword $keyword
     * @return array
     */
    protected function formatKeyword(Keyword $keyword): array
    {
        [$current, $previous] = $this->getLatestRankings($keyword);

        $currentPosition = $current?->position;
        $previousPosition = $previous?->position;

        return [
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
            'last_checked_at' => $current?->checked_at?->toISOString(),
        ];
    }

    /**
     * Get latest and previous rankings for a keyword.
     *
     * @param Keyword $keyword
     * @return array{0:Ranking|null,1:Ranking|null}
     */
    protected function getLatestRankings(Keyword $keyword): array
    {
        $rankings = Ranking::where('keyword_id', $keyword->id)
            ->orderBy('checked_at', 'desc')
            ->take(2)
            ->get();

        return [$rankings->get(0), $rankings->get(1)];
    }

    /**
     * Calculate position change.
     *
     * @param int|null $current
     * @param int|null $previous
     * @return int|null
     */
    protected function calculatePositionChange(?int $current, ?int $previous): ?int
    {
        if ($current === null || $previous === null) {
            return null;
        }

        // Negative means improvement (position went up)
        return $current - $previous;
    }

    /**
     * Build an unauthorized response.
     *
     * @return JsonResponse
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ],
        ], 401);
    }

    /**
     * Build a forbidden response.
     *
     * @return JsonResponse
     */
    protected function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'You do not have permission to access this resource.',
            ],
        ], 403);
    }

    /**
     * Build a not found response.
     *
     * @return JsonResponse
     */
    protected function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Keyword not found.',
            ],
        ], 404);
    }
}
