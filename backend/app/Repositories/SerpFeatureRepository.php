<?php

namespace App\Repositories;

use App\Models\SerpFeature;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * SerpFeatureRepository for TASK-7-3.
 *
 * Provides data access for SERP feature tracking.
 */
class SerpFeatureRepository
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected int $cacheTtl = 300;

    /**
     * Find a SERP feature by ID.
     */
    public function findById(int $id): ?SerpFeature
    {
        return SerpFeature::find($id);
    }

    /**
     * Get all features for a keyword.
     *
     * @param int $keywordId
     * @return Collection
     */
    public function getByKeyword(int $keywordId): Collection
    {
        return SerpFeature::where('keyword_id', $keywordId)
            ->orderBy('feature_type')
            ->get();
    }

    /**
     * Get all features for a site.
     *
     * @param int $siteId
     * @return Collection
     */
    public function getBySite(int $siteId): Collection
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        return SerpFeature::whereIn('keyword_id', $keywordIds)
            ->present()
            ->orderBy('checked_at', 'desc')
            ->get();
    }

    /**
     * Get features for a keyword grouped by type.
     *
     * @param int $keywordId
     * @return array
     */
    public function getGroupedByKeyword(int $keywordId): array
    {
        $features = $this->getByKeyword($keywordId);

        $grouped = [];
        foreach (SerpFeature::FEATURE_TYPES as $type) {
            $feature = $features->firstWhere('feature_type', $type);
            $grouped[$type] = $feature ? $feature->is_present : false;
        }

        return $grouped;
    }

    /**
     * Get total keyword count for a site.
     *
     * @param int $siteId
     * @return int
     */
    public function getTotalKeywords(int $siteId): int
    {
        return Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get count of keywords with at least one SERP feature.
     *
     * @param int $siteId
     * @return int
     */
    public function getKeywordsWithFeaturesCount(int $siteId): int
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        return SerpFeature::whereIn('keyword_id', $keywordIds)
            ->present()
            ->distinct('keyword_id')
            ->count('keyword_id');
    }

    /**
     * Get feature counts by type for a site.
     *
     * @param int $siteId
     * @return array
     */
    public function getFeatureCountsBySite(int $siteId): array
    {
        $cacheKey = "serp_feature_counts:{$siteId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($siteId) {
            $keywordIds = Keyword::where('site_id', $siteId)
                ->where('status', 'active')
                ->pluck('id');

            $counts = SerpFeature::whereIn('keyword_id', $keywordIds)
                ->present()
                ->select('feature_type', DB::raw('count(*) as count'))
                ->groupBy('feature_type')
                ->pluck('count', 'feature_type')
                ->toArray();

            // Ensure all feature types are present with at least 0
            $result = [];
            foreach (SerpFeature::FEATURE_TYPES as $type) {
                $result[$type] = $counts[$type] ?? 0;
            }

            return $result;
        });
    }

    /**
     * Insert or update a SERP feature.
     *
     * @param array $data
     * @return SerpFeature
     */
    public function upsert(array $data): SerpFeature
    {
        $existing = SerpFeature::where('keyword_id', $data['keyword_id'])
            ->where('feature_type', $data['feature_type'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return SerpFeature::create($data);
    }

    /**
     * Insert multiple features in batch.
     *
     * @param array $features
     * @return bool
     */
    public function insertBatch(array $features): bool
    {
        if (empty($features)) {
            return true;
        }

        $now = now();
        $records = array_map(function ($feature) use ($now) {
            return [
                'keyword_id' => $feature['keyword_id'],
                'feature_type' => $feature['feature_type'],
                'is_present' => $feature['is_present'] ?? false,
                'position' => $feature['position'] ?? null,
                'url' => $feature['url'] ?? null,
                'title' => $feature['title'] ?? null,
                'snippet' => $feature['snippet'] ?? null,
                'checked_at' => $feature['checked_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $features);

        return DB::table('serp_features')->insert($records);
    }

    /**
     * Get features for multiple keywords.
     *
     * @param array $keywordIds
     * @return Collection
     */
    public function getByKeywordIds(array $keywordIds): Collection
    {
        return SerpFeature::whereIn('keyword_id', $keywordIds)
            ->orderBy('keyword_id')
            ->orderBy('feature_type')
            ->get();
    }

    /**
     * Get keywords filtered by SERP feature presence.
     *
     * @param int $siteId
     * @param string $featureType
     * @param bool $present
     * @return Collection
     */
    public function getKeywordsByFeature(int $siteId, string $featureType, bool $present = true): Collection
    {
        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        $featureKeywordIds = SerpFeature::whereIn('keyword_id', $keywordIds)
            ->ofType($featureType)
            ->where('is_present', $present)
            ->pluck('keyword_id');

        return Keyword::whereIn('id', $featureKeywordIds)
            ->orderBy('keyword')
            ->get();
    }

    /**
     * Invalidate cache for a site.
     *
     * @param int $siteId
     * @return void
     */
    public function invalidateCache(int $siteId): void
    {
        Cache::forget("serp_feature_counts:{$siteId}");
        Cache::forget("serp_feature_summary:{$siteId}");
    }

    /**
     * Get latest features for a keyword with history.
     *
     * @param int $keywordId
     * @param int $days
     * @return array
     */
    public function getKeywordFeaturesWithHistory(int $keywordId, int $days = 30): array
    {
        $currentFeatures = $this->getGroupedByKeyword($keywordId);

        $history = DB::table('serp_features')
            ->where('keyword_id', $keywordId)
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->checked_at)->format('Y-m-d');
            })
            ->map(function ($items) {
                $features = [];
                foreach ($items as $item) {
                    $features[$item->feature_type] = (bool) $item->is_present;
                }
                return $features;
            })
            ->toArray();

        return [
            'current' => $currentFeatures,
            'history' => $history,
        ];
    }
}
