<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreKeywordRequest;
use App\Http\Requests\V1\UpdateKeywordRequest;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Repositories\KeywordRepository;
use App\Repositories\RankingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Keyword Controller for SPEC-001-4.
 */
class KeywordController extends Controller
{
    protected KeywordRepository $keywordRepository;
    protected RankingRepository $rankingRepository;

    /**
     * Maximum keywords per site (stub for subscription tier).
     */
    protected int $maxKeywords = 500;

    public function __construct(KeywordRepository $keywordRepository, RankingRepository $rankingRepository)
    {
        $this->keywordRepository = $keywordRepository;
        $this->rankingRepository = $rankingRepository;
    }

    /**
     * List keywords for a site.
     */
    public function index(Request $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        $status = $request->query('status');
        if ($status === 'all') {
            $status = null;
        }

        $perPage = (int) $request->query('perPage', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;
        $page = max(1, (int) $request->query('page', 1));

        $sortBy = $request->query('sortBy', 'keyword');
        $sortOrder = strtolower((string) $request->query('sortOrder', 'asc')) === 'desc' ? 'desc' : 'asc';

        $keywords = Keyword::where('site_id', $siteId)
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->get();

        $formatted = $keywords->map(function (Keyword $keyword) {
            [$current, $previous] = $this->getLatestRankings($keyword);
            return $this->formatKeyword($keyword, $current, $previous);
        });

        $sorted = $this->sortKeywords($formatted, $sortBy, $sortOrder);

        $total = $sorted->count();
        $paged = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paged,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Create a keyword for a site.
     */
    public function store(StoreKeywordRequest $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        $currentCount = $this->keywordRepository->getActiveCount($siteId);
        if ($currentCount >= $this->maxKeywords) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'KEYWORD_LIMIT_EXCEEDED',
                    'message' => "Maximum {$this->maxKeywords} active keywords allowed.",
                ],
            ], 400);
        }

        $keywordText = strip_tags(trim((string) $request->input('keyword')));
        $searchEngine = $request->input('searchEngine', 'google');
        $location = $request->input('location', 'us');
        $device = $request->input('device', 'desktop');

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

        $keyword = $this->keywordRepository->create([
            'site_id' => $siteId,
            'keyword' => $keywordText,
            'target_url' => $request->input('targetUrl'),
            'search_engine' => $searchEngine,
            'location' => $location,
            'device' => $device,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword, null, null),
        ], 201);
    }

    /**
     * Get a single keyword.
     */
    public function show(int $id): JsonResponse
    {
        $keyword = $this->keywordRepository->findById($id);
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        [$current, $previous] = $this->getLatestRankings($keyword);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword, $current, $previous),
        ]);
    }

    /**
     * Update a keyword.
     */
    public function update(UpdateKeywordRequest $request, int $id): JsonResponse
    {
        $keyword = $this->keywordRepository->findById($id);
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        if (!$request->hasAny(['targetUrl', 'status'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'No data provided for update.',
                ],
            ], 400);
        }

        $this->keywordRepository->update($id, [
            'target_url' => $request->input('targetUrl', $keyword->target_url),
            'status' => $request->input('status', $keyword->status),
        ]);

        $keyword = $this->keywordRepository->findById($id);
        [$current, $previous] = $this->getLatestRankings($keyword);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyword($keyword, $current, $previous),
        ]);
    }

    /**
     * Delete a keyword.
     */
    public function destroy(Request $request, int $id): Response
    {
        $keyword = $this->keywordRepository->findById($id);
        if (!$keyword) {
            return response()->noContent(404);
        }

        $keepHistoryParam = $request->query('keepHistory', 'true');
        $keepHistory = filter_var($keepHistoryParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($keepHistory === null) {
            $keepHistory = true;
        }

        if (!$keepHistory) {
            Ranking::where('keyword_id', $keyword->id)->delete();
        }

        $this->keywordRepository->delete($keyword->id);

        return response()->noContent();
    }

    /**
     * Format keyword for API response.
     */
    protected function formatKeyword(Keyword $keyword, ?Ranking $current, ?Ranking $previous): array
    {
        $currentPosition = $current?->position;
        $previousPosition = $previous?->position;

        return [
            'id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'targetUrl' => $keyword->target_url,
            'searchEngine' => $keyword->search_engine,
            'location' => $keyword->location,
            'device' => $keyword->device,
            'status' => $keyword->status,
            'currentPosition' => $currentPosition,
            'previousPosition' => $previousPosition,
            'positionChange' => $this->calculatePositionChange($currentPosition, $previousPosition),
            'lastCheckedAt' => $current?->checked_at?->toISOString(),
        ];
    }

    /**
     * Get latest and previous rankings for a keyword.
     *
     * @return array{0:Ranking|null,1:Ranking|null}
     */
    protected function getLatestRankings(Keyword $keyword): array
    {
        $rankings = $keyword->rankingHistory()
            ->orderBy('checked_at', 'desc')
            ->take(2)
            ->get();

        return [$rankings->get(0), $rankings->get(1)];
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

    /**
     * Sort keywords for list response.
     */
    protected function sortKeywords(Collection $keywords, string $sortBy, string $sortOrder): Collection
    {
        $sorted = $keywords;

        switch ($sortBy) {
            case 'position':
                $sorted = $keywords->sortBy(function ($item) use ($sortOrder) {
                    $position = $item['currentPosition'];
                    if ($position === null) {
                        return $sortOrder === 'asc' ? PHP_INT_MAX : PHP_INT_MIN;
                    }
                    return $position;
                });
                break;
            case 'lastChecked':
                $sorted = $keywords->sortBy(function ($item) use ($sortOrder) {
                    $timestamp = $item['lastCheckedAt'] ? strtotime($item['lastCheckedAt']) : null;
                    if ($timestamp === null) {
                        return $sortOrder === 'asc' ? PHP_INT_MAX : PHP_INT_MIN;
                    }
                    return $timestamp;
                });
                break;
            case 'keyword':
            default:
                $sorted = $keywords->sortBy('keyword');
                break;
        }

        if ($sortOrder === 'desc') {
            $sorted = $sorted->reverse()->values();
        }

        return $sorted->values();
    }
}
