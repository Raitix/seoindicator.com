<?php

namespace App\Services;

use App\Repositories\SerpFeatureRepository;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Models\Site;
use App\Models\Keyword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SerpFeatureService for SERP feature tracking.
 *
 * Provides business logic for SERP feature summary and data.
 */
class SerpFeatureService
{
    protected SerpFeatureRepository $featureRepository;
    protected SerpFeatureHistoryRepository $historyRepository;

    /**
     * Cache TTL in seconds (5 minutes default).
     */
    protected int $cacheTtl = 300;

    public function __construct(
        SerpFeatureRepository $featureRepository,
        SerpFeatureHistoryRepository $historyRepository
    ) {
        $this->featureRepository = $featureRepository;
        $this->historyRepository = $historyRepository;
    }

    /**
     * Get SERP feature summary for a site.
     *
     * @param int $siteId
     * @param int $days Time range for recentChanges (default 7)
     * @return array
     */
    public function getSummary(int $siteId, int $days = 7): array
    {
        // Validate site exists
        $site = Site::find($siteId);
        if (!$site) {
            return [
                'success' => false,
                'error' => 'SITE_NOT_FOUND',
                'message' => "Site with ID {$siteId} not found",
            ];
        }

        // Validate days parameter
        $days = max(1, min(365, $days));

        $cacheKey = "serp_feature_summary:{$siteId}:{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($siteId, $days) {
            return $this->buildSummary($siteId, $days);
        });
    }

    /**
     * Build the summary data structure.
     *
     * @param int $siteId
     * @param int $days
     * @return array
     */
    protected function buildSummary(int $siteId, int $days): array
    {
        // Get total keywords
        $totalKeywords = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->count();

        // Get keywords with features
        $keywordsWithFeatures = $this->getKeywordsWithFeaturesCount($siteId);

        // Get feature counts
        $featureCounts = $this->getFeatureCounts($siteId);

        // Get recent changes
        $recentChanges = $this->getRecentChanges($siteId, $days);

        return [
            'success' => true,
            'data' => [
                'totalKeywords' => $totalKeywords,
                'keywordsWithFeatures' => $keywordsWithFeatures,
                'featureCounts' => $featureCounts,
                'recentChanges' => $recentChanges,
            ],
        ];
    }

    /**
     * Get count of keywords with at least one SERP feature.
     *
     * @param int $siteId
     * @return int
     */
    protected function getKeywordsWithFeaturesCount(int $siteId): int
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        return DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->distinct()
            ->count('keyword_id');
    }

    /**
     * Get feature counts by type.
     *
     * @param int $siteId
     * @return array
     */
    protected function getFeatureCounts(int $siteId): array
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        $counts = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->select('feature_type', DB::raw('count(*) as count'))
            ->groupBy('feature_type')
            ->pluck('count', 'feature_type')
            ->toArray();

        // Ensure all feature types are present with at least 0
        $featureTypes = [
            'featured_snippet',
            'local_pack',
            'image_pack',
            'video',
            'people_also_ask',
            'knowledge_panel',
            'sitelinks',
            'reviews',
            'faq',
            'product',
        ];

        $result = [];
        foreach ($featureTypes as $type) {
            $result[$type] = $counts[$type] ?? 0;
        }

        return $result;
    }

    /**
     * Get recent feature changes (gained/lost) for a site.
     *
     * @param int $siteId
     * @param int $days
     * @return array
     */
    protected function getRecentChanges(int $siteId, int $days): array
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        // Get keywords for lookup
        $keywords = Keyword::whereIn('id', $keywordIds)
            ->pluck('keyword', 'id');

        // Get gained features
        $gained = DB::table('serp_feature_history')
            ->whereIn('keyword_id', $keywordIds)
            ->where('action', 'gained')
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($item) use ($keywords) {
                return [
                    'keyword' => $keywords[$item->keyword_id] ?? 'Unknown',
                    'keywordId' => $item->keyword_id,
                    'feature' => $item->feature_type,
                    'detectedAt' => \Carbon\Carbon::parse($item->detected_at)->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();

        // Get lost features
        $lost = DB::table('serp_feature_history')
            ->whereIn('keyword_id', $keywordIds)
            ->where('action', 'lost')
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($item) use ($keywords) {
                return [
                    'keyword' => $keywords[$item->keyword_id] ?? 'Unknown',
                    'keywordId' => $item->keyword_id,
                    'feature' => $item->feature_type,
                    'detectedAt' => \Carbon\Carbon::parse($item->detected_at)->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();

        return [
            'gained' => $gained,
            'lost' => $lost,
        ];
    }

    /**
     * Get features for a specific keyword.
     *
     * @param int $keywordId
     * @param array $options
     * @return array
     */
    public function getKeywordFeatures(int $keywordId, array $options = []): array
    {
        $keyword = Keyword::find($keywordId);
        if (!$keyword) {
            return [
                'success' => false,
                'error' => 'KEYWORD_NOT_FOUND',
                'message' => "Keyword with ID {$keywordId} not found",
            ];
        }

        $days = $options['days'] ?? 30;
        $from = $options['from'] ?? null;
        $to = $options['to'] ?? null;

        // Get current features
        $currentFeatures = $this->featureRepository->getGroupedByKeyword($keywordId);

        // Format current features for response
        $formattedFeatures = [];
        foreach ($currentFeatures as $type => $isPresent) {
            $formattedFeatures[] = [
                'type' => $type,
                'isPresent' => $isPresent,
            ];
        }

        // Get history if requested
        $history = [];
        if ($from || $to) {
            $history = $this->getKeywordHistory($keywordId, $from, $to);
        }

        // Get changes
        $changes = $this->getKeywordChanges($keywordId, $days);

        return [
            'success' => true,
            'data' => [
                'keyword' => [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                ],
                'currentFeatures' => $formattedFeatures,
                'history' => $history,
                'changes' => $changes,
            ],
        ];
    }

    /**
     * Get feature history for a keyword within date range.
     *
     * @param int $keywordId
     * @param string|null $from
     * @param string|null $to
     * @return array
     */
    protected function getKeywordHistory(int $keywordId, ?string $from, ?string $to): array
    {
        $query = DB::table('serp_features')
            ->where('keyword_id', $keywordId);

        if ($from) {
            $query->where('checked_at', '>=', $from);
        }

        if ($to) {
            $query->where('checked_at', '<=', $to);
        }

        return $query->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->checked_at)->format('Y-m-d');
            })
            ->map(function ($items) {
                $features = [];
                foreach ($items as $item) {
                    $features[$item->feature_type] = (bool) $item->is_present;
                }
                return [
                    'date' => $items->first()->checked_at,
                    'features' => $features,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get feature changes for a keyword.
     *
     * @param int $keywordId
     * @param int $days
     * @return array
     */
    protected function getKeywordChanges(int $keywordId, int $days): array
    {
        return DB::table('serp_feature_history')
            ->where('keyword_id', $keywordId)
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->feature_type,
                    'action' => $item->action,
                    'detectedAt' => \Carbon\Carbon::parse($item->detected_at)->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get all features for a site with pagination.
     *
     * @param int $siteId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getSiteFeatures(int $siteId, int $page = 1, int $perPage = 20): array
    {
        $site = Site::find($siteId);
        if (!$site) {
            return [
                'success' => false,
                'error' => 'SITE_NOT_FOUND',
                'message' => "Site with ID {$siteId} not found",
            ];
        }

        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        $total = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->count();

        $features = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->orderBy('checked_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Get keyword names
        $keywords = Keyword::whereIn('id', $features->pluck('keyword_id'))->pluck('keyword', 'id');

        $data = $features->map(function ($item) use ($keywords) {
            return [
                'keywordId' => $item->keyword_id,
                'keyword' => $keywords[$item->keyword_id] ?? 'Unknown',
                'featureType' => $item->feature_type,
                'isPresent' => (bool) $item->is_present,
                'position' => $item->position,
                'url' => $item->url,
                'checkedAt' => \Carbon\Carbon::parse($item->checked_at)->toIso8601String(),
            ];
        })->values()->toArray();

        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Invalidate summary cache for a site.
     *
     * @param int $siteId
     * @return void
     */
    public function invalidateSummaryCache(int $siteId): void
    {
        // Clear all summary caches for this site (various day ranges)
        for ($i = 1; $i <= 365; $i++) {
            Cache::forget("serp_feature_summary:{$siteId}:{$i}");
        }

        // Also clear the feature counts cache
        $this->featureRepository->invalidateCache($siteId);

        Log::info("SERP feature summary cache invalidated for site {$siteId}");
    }
}
