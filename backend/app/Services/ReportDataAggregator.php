<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Models\SerpFeature;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataAggregator
{
    /**
     * SERP feature icons for display.
     */
    public const SERP_FEATURE_ICONS = [
        'featured_snippet' => '🏆',
        'local_pack' => '📍',
        'image_pack' => '🖼️',
        'video' => '🎬',
        'people_also_ask' => '❓',
        'knowledge_panel' => '📋',
        'sitelinks' => '🔗',
        'reviews' => '⭐',
        'faq' => '📝',
        'product' => '🛒',
    ];
    /**
     * Aggregate report data for a site.
     */
    public function aggregate(int $siteId, array $dateRange, ?array $compareRange = null): array
    {
        $site = Site::find($siteId);
        $keywords = Keyword::where('site_id', $siteId)->get();
        $keywordIds = $keywords->pluck('id')->all();

        $start = $this->parseDate($dateRange['start'] ?? $dateRange['from'] ?? ($dateRange[0] ?? null))?->startOfDay();
        $end = $this->parseDate($dateRange['end'] ?? $dateRange['to'] ?? ($dateRange[1] ?? null))?->endOfDay();

        $compareStart = $this->parseDate($compareRange['start'] ?? $compareRange['from'] ?? ($compareRange[0] ?? null))?->startOfDay();
        $compareEnd = $this->parseDate($compareRange['end'] ?? $compareRange['to'] ?? ($compareRange[1] ?? null))?->endOfDay();

        $rankingsInRange = $this->fetchRankings($keywordIds, $start, $end);
        $rankingsInCompare = $compareStart && $compareEnd
            ? $this->fetchRankings($keywordIds, $compareStart, $compareEnd)
            : collect();

        // Fetch SERP features per keyword (batch query for efficiency)
        $keywordSerpFeatures = $this->fetchKeywordSerpFeatures($keywordIds);

        $keywordsData = [];
        $trends = [];

        foreach ($keywords as $keyword) {
            $rangeRankings = $rankingsInRange->get($keyword->id, collect());
            $compareRankings = $rankingsInCompare->get($keyword->id, collect());

            $current = $rangeRankings->sortByDesc('checked_at')->first();
            $previous = $compareRankings->sortByDesc('checked_at')->first();

            $currentPosition = $current?->position;
            $previousPosition = $previous?->position;
            $change = $this->calculateChange($currentPosition, $previousPosition, $compareStart && $compareEnd);

            // Get SERP feature icons for this keyword
            $serpFeatureIcons = $keywordSerpFeatures[$keyword->id] ?? '';

            $keywordsData[] = [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'current_position' => $currentPosition,
                'previous_position' => $previousPosition,
                'change' => $change,
                'search_engine' => $keyword->search_engine,
                'location' => $keyword->location,
                'last_checked_at' => $current?->checked_at?->toISOString(),
                'serp_features' => $serpFeatureIcons,
            ];

            $trends[] = [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'points' => $rangeRankings
                    ->sortBy('checked_at')
                    ->map(fn (Ranking $ranking) => [
                        'checked_at' => $ranking->checked_at?->toISOString(),
                        'position' => $ranking->position,
                    ])
                    ->values()
                    ->toArray(),
            ];
        }

        // Top performers: most negative change (biggest improvement) -> sort ascending
        $topPerformers = $this->selectTopChanges($keywordsData, 'asc');
        // Needs attention: most positive change (biggest drop) -> sort descending
        $needsAttention = $this->selectTopChanges($keywordsData, 'desc');

        $competitorSummary = null;
        if ($site?->user_id && class_exists(ComparisonService::class)) {
            $competitorSummary = (new ComparisonService())->getSummary($site->user_id);
        }

        // Get SERP feature data
        $serpFeatureData = $this->getSerpFeatureData($keywordIds);

        return [
            'site_id' => $siteId,
            'date_range' => [
                'start' => $start?->toDateString(),
                'end' => $end?->toDateString(),
            ],
            'compare_range' => $compareStart && $compareEnd ? [
                'start' => $compareStart->toDateString(),
                'end' => $compareEnd->toDateString(),
            ] : null,
            'keywords' => $keywordsData,
            'top_performers' => $topPerformers,
            'needs_attention' => $needsAttention,
            'trends' => $trends,
            'competitors' => $competitorSummary,
            'serp_features' => $serpFeatureData,
        ];
    }

    protected function fetchRankings(array $keywordIds, ?Carbon $start, ?Carbon $end): Collection
    {
        if (empty($keywordIds) || !$start || !$end) {
            return collect();
        }

        $query = Ranking::whereIn('keyword_id', $keywordIds)
            ->whereBetween('checked_at', [$start, $end]);
        $results = $query->orderBy('checked_at', 'asc')->get()->groupBy('keyword_id');

        return $results;
    }

    /**
     * Fetch SERP feature icons for multiple keywords (batch query).
     *
     * @param array $keywordIds
     * @return array Map of keyword_id => icon string
     */
    protected function fetchKeywordSerpFeatures(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return [];
        }

        // Get all present SERP features for these keywords
        $features = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->select('keyword_id', 'feature_type')
            ->get();

        // Group by keyword_id and build icon strings
        $result = [];
        foreach ($features as $feature) {
            $keywordId = $feature->keyword_id;
            if (!isset($result[$keywordId])) {
                $result[$keywordId] = '';
            }
            $icon = self::SERP_FEATURE_ICONS[$feature->feature_type] ?? '';
            $result[$keywordId] .= $icon;
        }

        return $result;
    }

    protected function calculateChange(?int $current, ?int $previous, bool $compareEnabled): ?int
    {
        if (!$compareEnabled || $current === null || $previous === null) {
            return null;
        }

        if ($current === null || $previous === null) {
    return null;
} else {
    return $current - $previous;
}
    }

    protected function selectTopChanges(array $keywordsData, string $direction): array
    {
        $filtered = array_filter($keywordsData, fn($k) => $k['change'] !== null);

        usort($filtered, function (array $a, array $b) use ($direction) {
            // For 'asc': most negative change first (biggest improvement)
            // For 'desc': most positive change first (biggest drop)
            return $direction === 'asc' ? $a['change'] <=> $b['change'] : $b['change'] <=> $a['change'];
        });

        return array_slice($filtered, 0, 5);
    }

    protected function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * Get SERP feature data for report.
     *
     * @param array $keywordIds
     * @return array
     */
    protected function getSerpFeatureData(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return [
                'totalKeywords' => 0,
                'keywordsWithFeatures' => 0,
                'featureCounts' => [],
                'recentChanges' => [
                    'gained' => [],
                    'lost' => [],
                ],
            ];
        }

        // Get total keywords with features
        $keywordsWithFeatures = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->distinct()
            ->count('keyword_id');

        // Get feature counts by type
        $featureCounts = DB::table('serp_features')
            ->whereIn('keyword_id', $keywordIds)
            ->where('is_present', true)
            ->select('feature_type', DB::raw('count(*) as count'))
            ->groupBy('feature_type')
            ->pluck('count', 'feature_type')
            ->toArray();

        // Ensure all feature types are present
        foreach (SerpFeature::FEATURE_TYPES as $type) {
            if (!isset($featureCounts[$type])) {
                $featureCounts[$type] = 0;
            }
        }

        // Get keyword names for lookup
        $keywords = Keyword::whereIn('id', $keywordIds)->pluck('keyword', 'id');

        // Get recent gained features (last 7 days)
        $gained = DB::table('serp_feature_history')
            ->whereIn('keyword_id', $keywordIds)
            ->where('action', 'gained')
            ->where('detected_at', '>=', now()->subDays(7))
            ->orderBy('detected_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) use ($keywords) {
                return [
                    'keyword' => $keywords[$item->keyword_id] ?? 'Unknown',
                    'feature' => $item->feature_type,
                    'detectedAt' => Carbon::parse($item->detected_at)->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();

        // Get recent lost features (last 7 days)
        $lost = DB::table('serp_feature_history')
            ->whereIn('keyword_id', $keywordIds)
            ->where('action', 'lost')
            ->where('detected_at', '>=', now()->subDays(7))
            ->orderBy('detected_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) use ($keywords) {
                return [
                    'keyword' => $keywords[$item->keyword_id] ?? 'Unknown',
                    'feature' => $item->feature_type,
                    'detectedAt' => Carbon::parse($item->detected_at)->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();

        return [
            'totalKeywords' => count($keywordIds),
            'keywordsWithFeatures' => $keywordsWithFeatures,
            'featureCounts' => $featureCounts,
            'recentChanges' => [
                'gained' => $gained,
                'lost' => $lost,
            ],
        ];
    }

    /**
     * Get SERP features for a specific keyword.
     *
     * @param int $keywordId
     * @return array
     */
    public function getKeywordSerpFeatures(int $keywordId): array
    {
        $features = DB::table('serp_features')
            ->where('keyword_id', $keywordId)
            ->where('is_present', true)
            ->pluck('feature_type')
            ->toArray();

        $icons = [];
        foreach ($features as $type) {
            $icons[] = self::SERP_FEATURE_ICONS[$type] ?? '';
        }

        return [
            'types' => $features,
            'icons' => implode('', $icons),
        ];
    }
}
