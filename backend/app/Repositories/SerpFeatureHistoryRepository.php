<?php

namespace App\Repositories;

use App\Models\SerpFeatureHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SerpFeatureHistoryRepository for TASK-7-9.
 *
 * Provides data access for SERP feature history tracking.
 */
class SerpFeatureHistoryRepository
{
    /**
     * Insert a new history entry.
     *
     * @param array $data History entry data
     * @return SerpFeatureHistory
     */
    public function insert(array $data): SerpFeatureHistory
    {
        return SerpFeatureHistory::create([
            'keyword_id' => $data['keyword_id'],
            'feature_type' => $data['feature_type'],
            'action' => $data['action'],
            'previous_url' => $data['previous_url'] ?? null,
            'current_url' => $data['current_url'] ?? null,
            'detected_at' => $data['detected_at'] ?? now(),
        ]);
    }

    /**
     * Insert multiple history entries in a batch.
     *
     * @param array $entries Array of history entry data
     * @return bool
     */
    public function insertBatch(array $entries): bool
    {
        if (empty($entries)) {
            return true;
        }

        $now = now();
        $records = array_map(function ($entry) use ($now) {
            return [
                'keyword_id' => $entry['keyword_id'],
                'feature_type' => $entry['feature_type'],
                'action' => $entry['action'],
                'previous_url' => $entry['previous_url'] ?? null,
                'current_url' => $entry['current_url'] ?? null,
                'detected_at' => $entry['detected_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $entries);

        return DB::table('serp_feature_history')->insert($records);
    }

    /**
     * Get history for a specific keyword.
     *
     * @param int $keywordId
     * @param int $limit
     * @return Collection
     */
    public function getHistoryByKeyword(int $keywordId, int $limit = 50): Collection
    {
        return SerpFeatureHistory::forKeyword($keywordId)
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent changes for a site.
     *
     * @param int $siteId
     * @param int $hours
     * @return Collection
     */
    public function getRecentChanges(int $siteId, int $hours = 24): Collection
    {
        return SerpFeatureHistory::whereHas('keyword', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })
            ->recent($hours)
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    /**
     * Get feature gains for a site within a period.
     *
     * @param int $siteId
     * @param int $days
     * @return Collection
     */
    public function getFeatureGainsBySite(int $siteId, int $days = 7): Collection
    {
        return SerpFeatureHistory::whereHas('keyword', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })
            ->gained()
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    /**
     * Get feature losses for a site within a period.
     *
     * @param int $siteId
     * @param int $days
     * @return Collection
     */
    public function getFeatureLossesBySite(int $siteId, int $days = 7): Collection
    {
        return SerpFeatureHistory::whereHas('keyword', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })
            ->lost()
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    /**
     * Get feature timeline for a keyword.
     *
     * @param int $keywordId
     * @param int $days
     * @return array
     */
    public function getFeatureTimeline(int $keywordId, int $days = 30): array
    {
        return SerpFeatureHistory::forKeyword($keywordId)
            ->where('detected_at', '>=', now()->subDays($days))
            ->orderBy('detected_at', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->detected_at->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map->toApiResponse();
            })
            ->toArray();
    }

    /**
     * Get feature statistics for a site.
     *
     * @param int $siteId
     * @return array
     */
    public function getFeatureStatistics(int $siteId): array
    {
        $keywordIds = DB::table('keywords')
            ->where('site_id', $siteId)
            ->pluck('id');

        $totalFeatures = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->count();

        $byType = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->select('feature_type', 'action', DB::raw('count(*) as count'))
            ->groupBy('feature_type', 'action')
            ->get()
            ->groupBy('feature_type')
            ->map(function ($items) {
                $result = ['gained' => 0, 'lost' => 0];
                foreach ($items as $item) {
                    $result[$item->action] = $item->count;
                }
                return $result;
            })
            ->toArray();

        return [
            'total_active_features' => $totalFeatures,
            'by_type' => $byType,
        ];
    }

    /**
     * Get keywords with the most feature changes (most volatile).
     *
     * @param int $siteId
     * @param int $limit
     * @return Collection
     */
    public function getMostVolatileKeywords(int $siteId, int $limit = 10): Collection
    {
        $keywordIds = DB::table('keywords')
            ->where('site_id', $siteId)
            ->pluck('id');

        return SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->select('keyword_id', DB::raw('count(*) as change_count'))
            ->groupBy('keyword_id')
            ->orderByDesc('change_count')
            ->limit($limit)
            ->with('keyword')
            ->get();
    }

    /**
     * Get features that have been held the longest.
     *
     * @param int $siteId
     * @param int $limit
     * @return Collection
     */
    public function getLongestHeldFeatures(int $siteId, int $limit = 10): Collection
    {
        // Find features that were gained and not lost afterward
        return SerpFeatureHistory::whereHas('keyword', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })
            ->gained()
            ->whereNotIn('id', function ($query) {
                $query->select('id')
                    ->from('serp_feature_history')
                    ->where('action', 'lost');
            })
            ->orderBy('detected_at', 'asc')
            ->limit($limit)
            ->with('keyword')
            ->get();
    }
}
