<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreKeywordRequest;
use App\Http\Requests\V1\UpdateKeywordRequest;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use App\Models\Site;
use App\Repositories\KeywordRepository;
use App\Repositories\RankingRepository;
use App\Repositories\SerpFeatureRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Keyword Controller for SPEC-001-4.
 */
class KeywordController extends Controller
{
    protected KeywordRepository $keywordRepository;
    protected RankingRepository $rankingRepository;
    protected SerpFeatureRepository $serpFeatureRepository;

    /**
     * Maximum keywords per site (stub for subscription tier).
     */
    protected int $maxKeywords = 500;

    public function __construct(
        KeywordRepository $keywordRepository,
        RankingRepository $rankingRepository,
        SerpFeatureRepository $serpFeatureRepository
    ) {
        $this->keywordRepository = $keywordRepository;
        $this->rankingRepository = $rankingRepository;
        $this->serpFeatureRepository = $serpFeatureRepository;
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

        // SERP feature filter parameters
        $serpFeature = $request->query('serpFeature');
        $serpFeaturePresent = $request->query('present', 'true');

        // Validate SERP feature parameter
        if ($serpFeature !== null && $serpFeature !== '') {
            $features = array_filter(explode(',', $serpFeature));
            
            // Validate each feature type
            foreach ($features as $feature) {
                if (!in_array($feature, SerpFeature::FEATURE_TYPES)) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => "Invalid SERP feature type: {$feature}. Valid types are: " . implode(', ', SerpFeature::FEATURE_TYPES),
                        ],
                    ], 400);
                }
            }

            // Convert present parameter to boolean
            $present = in_array(strtolower((string) $serpFeaturePresent), ['true', '1', 'yes'], true);

            // Get keywords filtered by SERP feature
            $keywords = $this->getKeywordsFilteredBySerpFeature($siteId, $features, $present, $status);
        } else {
            // Standard query without SERP feature filter
            $keywords = Keyword::where('site_id', $siteId)
                ->when($status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->get();
        }

        // Get SERP features for all keywords
        $keywordIds = $keywords->pluck('id')->toArray();
        $serpFeaturesByKeyword = $this->getSerpFeaturesByKeywords($keywordIds);
        
        // Batch fetch latest rankings for all keywords (fixes N+1 query)
        $rankingsByKeyword = $this->getLatestRankingsForKeywords($keywordIds);

        $formatted = $keywords->map(function (Keyword $keyword) use ($serpFeaturesByKeyword, $rankingsByKeyword) {
            $rankings = $rankingsByKeyword[$keyword->id] ?? [];
            $current = $rankings[0] ?? null;
            $previous = $rankings[1] ?? null;
            $features = $serpFeaturesByKeyword[$keyword->id] ?? [];
            return $this->formatKeywordWithFeatures($keyword, $current, $previous, $features);
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
     * Get keywords filtered by SERP feature presence.
     *
     * @param int $siteId
     * @param array $featureTypes
     * @param bool $present
     * @param string|null $status
     * @return Collection
     */
    protected function getKeywordsFilteredBySerpFeature(
        int $siteId,
        array $featureTypes,
        bool $present,
        ?string $status
    ): Collection {
        $query = Keyword::where('site_id', $siteId)
            ->where('status', '!=', 'deleted');

        if ($status) {
            $query->where('status', $status);
        }

        // Get keyword IDs that have the specified SERP features
        $keywordIdsWithFeatures = \DB::table('serp_features')
            ->whereIn('feature_type', $featureTypes)
            ->where('is_present', $present)
            ->whereIn('keyword_id', $query->pluck('id'))
            ->distinct()
            ->pluck('keyword_id');

        return Keyword::whereIn('id', $keywordIdsWithFeatures)
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->get();
    }

    /**
     * Get SERP features grouped by keyword ID.
     *
     * @param array $keywordIds
     * @return array
     */
    protected function getSerpFeaturesByKeywords(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return [];
        }

        $features = \DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->get();

        $grouped = [];
        foreach ($keywordIds as $keywordId) {
            $grouped[$keywordId] = [];
        }

        foreach ($features as $feature) {
            if (!isset($grouped[$feature->keyword_id])) {
                $grouped[$feature->keyword_id] = [];
            }
            $grouped[$feature->keyword_id][$feature->feature_type] = (bool) $feature->is_present;
        }

        // Fill missing feature types with false
        foreach ($grouped as $keywordId => &$featureMap) {
            foreach (SerpFeature::FEATURE_TYPES as $type) {
                if (!isset($featureMap[$type])) {
                    $featureMap[$type] = false;
                }
            }
        }

        return $grouped;
    }

    /**
     * Format keyword for API response with SERP features.
     */
    protected function formatKeywordWithFeatures(
        Keyword $keyword,
        ?Ranking $current,
        ?Ranking $previous,
        array $serpFeatures
    ): array {
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
            'serpFeatures' => $serpFeatures,
        ];
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
     * Batch fetch the latest 2 rankings for multiple keywords.
     * Uses database-agnostic approach for MySQL and SQLite compatibility.
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsForKeywords(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return [];
        }

        $driver = \DB::getDriverName();

        // Use window function for MySQL (efficient in production)
        if ($driver === 'mysql') {
            return $this->getLatestRankingsWithWindowFunction($keywordIds);
        }

        // Use Eloquent for SQLite (compatible with tests)
        return $this->getLatestRankingsWithEloquent($keywordIds);
    }

    /**
     * Get latest rankings using window function (MySQL).
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsWithWindowFunction(array $keywordIds): array
    {
        $rankings = \DB::select("
            SELECT * FROM (
                SELECT 
                    id, keyword_id, position, url, checked_at, created_at, updated_at,
                    ROW_NUMBER() OVER (PARTITION BY keyword_id ORDER BY checked_at DESC) as rn
                FROM rankings
                WHERE keyword_id IN (" . implode(',', array_map('intval', $keywordIds)) . ")
            ) ranked
            WHERE rn <= 2
        ");

        $grouped = [];
        foreach ($keywordIds as $keywordId) {
            $grouped[$keywordId] = [];
        }

        foreach ($rankings as $row) {
            $ranking = new Ranking();
            $ranking->id = $row->id;
            $ranking->keyword_id = $row->keyword_id;
            $ranking->position = $row->position;
            $ranking->url = $row->url;
            $ranking->checked_at = $row->checked_at;
            $ranking->created_at = $row->created_at;
            $ranking->updated_at = $row->updated_at;

            $grouped[$row->keyword_id][] = $ranking;
        }

        return $grouped;
    }

    /**
     * Get latest rankings using Eloquent (SQLite/tests).
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsWithEloquent(array $keywordIds): array
    {
        // Load rankings ordered by keyword and date
        $rankings = Ranking::whereIn('keyword_id', $keywordIds)
            ->orderBy('keyword_id')
            ->orderBy('checked_at', 'desc')
            ->get();

        // Group and take only first 2 per keyword
        $grouped = [];
        foreach ($keywordIds as $keywordId) {
            $grouped[$keywordId] = [];
        }

        foreach ($rankings as $ranking) {
            if (count($grouped[$ranking->keyword_id]) < 2) {
                $grouped[$ranking->keyword_id][] = $ranking;
            }
        }

        return $grouped;
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
