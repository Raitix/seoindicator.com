<?php

namespace App\Repositories;

use App\Models\SerpFeatureHistory;
use App\Models\Keyword;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SerpFeatureHistoryRepository for TASK-7-9.
 *
 * Provides data access for SERP feature history tracking with:
 * - Feature timelines with daily presence
 * - Feature gains/losses with pagination
 * - Statistics aggregation
 * - Volatility analysis
 * - Longest-held feature tracking
 */
class SerpFeatureHistoryRepository
{
    /**
     * All valid SERP feature types.
     */
    public const FEATURE_TYPES = [
        'featured_snippet',
        'local_pack',
        'image_pack',
        'video',
        'people_also_ask',
        'knowledge_panel',
        'sitelinks',
        'reviews',
        'faq',
        'product'
    ];

    /**
     * Default pagination limit.
     */
    protected int $defaultLimit = 50;

    /**
     * Maximum pagination limit.
     */
    protected int $maxLimit = 500;

    /**
     * Insert a new history entry.
     *
     * @param array $data History entry data
     * @return SerpFeatureHistory
     * @throws \InvalidArgumentException If validation fails
     */
    public function insert(array $data): SerpFeatureHistory
    {
        $this->validateHistoryEntry($data);

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
     * @throws \InvalidArgumentException If any entry is invalid
     */
    public function insertBatch(array $entries): bool
    {
        if (empty($entries)) {
            return true;
        }

        // Validate all entries
        foreach ($entries as $index => $entry) {
            try {
                $this->validateHistoryEntry($entry);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException("Entry at index {$index}: " . $e->getMessage());
            }
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
     * @throws \InvalidArgumentException If keywordId is invalid
     */
    public function getHistoryByKeyword(int $keywordId, int $limit = 50): Collection
    {
        $this->validatePositiveInteger($keywordId, 'keywordId');
        $this->validatePositiveInteger($limit, 'limit', 1, $this->maxLimit);

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
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getRecentChanges(int $siteId, int $hours = 24): Collection
    {
        $this->validatePositiveInteger($siteId, 'siteId');
        $this->validatePositiveInteger($hours, 'hours', 1, 8760); // Max 1 year in hours

        return SerpFeatureHistory::whereHas('keyword', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })
            ->recent($hours)
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    /**
     * Get feature timeline for a keyword - returns daily feature presence.
     *
     * @param int $keywordId
     * @param int $days
     * @return array Array of {date: string, features: {feature_type: boolean}}
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getFeatureTimeline(int $keywordId, int $days = 30): array
    {
        $this->validatePositiveInteger($keywordId, 'keywordId');
        $this->validatePositiveInteger($days, 'days', 1, 3650); // Max 10 years

        $startDate = now()->subDays($days);

        // Get all history for this keyword in the period
        $history = SerpFeatureHistory::forKeyword($keywordId)
            ->where('detected_at', '>=', $startDate)
            ->orderBy('detected_at', 'asc')
            ->get();

        if ($history->isEmpty()) {
            return [];
        }

        // Build a map of feature states over time
        $featureStates = [];
        foreach (self::FEATURE_TYPES as $type) {
            $featureStates[$type] = false;
        }

        // Process history chronologically and track state changes
        $timelineByDate = [];
        foreach ($history as $entry) {
            $date = $entry->detected_at->format('Y-m-d');

            // Update feature state
            if ($entry->action === 'gained') {
                $featureStates[$entry->feature_type] = true;
            } elseif ($entry->action === 'lost') {
                $featureStates[$entry->feature_type] = false;
            }

            // Store the state at this point
            $timelineByDate[$date] = array_map('boolval', $featureStates);
        }

        // Format output - newest first
        $result = [];
        foreach (array_reverse(array_keys($timelineByDate)) as $date) {
            $result[] = [
                'date' => $date,
                'features' => $timelineByDate[$date],
            ];
        }

        return $result;
    }

    /**
     * Get feature gains for a site within a period with pagination.
     *
     * @param int $siteId
     * @param int $days
     * @param array $options Pagination options (limit, offset, page)
     * @return array Array with 'data' and 'pagination' keys
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getFeatureGainsBySite(int $siteId, int $days = 7, array $options = []): array
    {
        $this->validatePositiveInteger($siteId, 'siteId');
        $this->validatePositiveInteger($days, 'days', 1, 365);

        $limit = $this->normalizeLimit($options['limit'] ?? $this->defaultLimit);
        $offset = $options['offset'] ?? 0;
        $page = $options['page'] ?? 1;

        $keywordIds = Keyword::where('site_id', $siteId)->pluck('id');

        // Get total count
        $total = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->where('detected_at', '>=', now()->subDays($days))
            ->count();

        // Get paginated results
        $query = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->where('detected_at', '>=', now()->subDays($days))
            ->with('keyword')
            ->orderBy('detected_at', 'desc');

        $data = $query->skip($offset)->take($limit)->get()->map(function ($item) {
            return [
                'keyword_id' => $item->keyword_id,
                'keyword' => $item->keyword?->keyword,
                'feature_type' => $item->feature_type,
                'current_url' => $item->current_url,
                'detected_at' => $item->detected_at->toIso8601String(),
            ];
        })->toArray();

        return $this->formatPaginatedResult($data, $total, $page, $limit);
    }

    /**
     * Get feature losses for a site within a period with pagination.
     *
     * @param int $siteId
     * @param int $days
     * @param array $options Pagination options (limit, offset, page)
     * @return array Array with 'data' and 'pagination' keys
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getFeatureLossesBySite(int $siteId, int $days = 7, array $options = []): array
    {
        $this->validatePositiveInteger($siteId, 'siteId');
        $this->validatePositiveInteger($days, 'days', 1, 365);

        $limit = $this->normalizeLimit($options['limit'] ?? $this->defaultLimit);
        $offset = $options['offset'] ?? 0;
        $page = $options['page'] ?? 1;

        $keywordIds = Keyword::where('site_id', $siteId)->pluck('id');

        // Get total count
        $total = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->lost()
            ->where('detected_at', '>=', now()->subDays($days))
            ->count();

        // Get paginated results
        $query = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->lost()
            ->where('detected_at', '>=', now()->subDays($days))
            ->with('keyword')
            ->orderBy('detected_at', 'desc');

        $data = $query->skip($offset)->take($limit)->get()->map(function ($item) {
            return [
                'keyword_id' => $item->keyword_id,
                'keyword' => $item->keyword?->keyword,
                'feature_type' => $item->feature_type,
                'previous_url' => $item->previous_url,
                'detected_at' => $item->detected_at->toIso8601String(),
            ];
        })->toArray();

        return $this->formatPaginatedResult($data, $total, $page, $limit);
    }

    /**
     * Get feature statistics for a site.
     *
     * @param int $siteId
     * @return array Statistics object with totalKeywordsWithFeatures, totalFeatures, featuresByType, gainsThisWeek, lossesThisWeek
     * @throws \InvalidArgumentException If siteId is invalid
     */
    public function getFeatureStatistics(int $siteId): array
    {
        $this->validatePositiveInteger($siteId, 'siteId');

        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        if ($keywordIds->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        // Count distinct keywords with at least one feature history entry
        $totalKeywordsWithFeatures = DB::table('serp_feature_history')
            ->whereIn('keyword_id', $keywordIds)
            ->distinct('keyword_id')
            ->count('keyword_id');

        // Count total features (gained events represent total feature count)
        $totalFeatures = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->count();

        // Get features by type with gains and losses
        $byTypeRaw = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->select('feature_type', 'action', DB::raw('count(*) as count'))
            ->groupBy('feature_type', 'action')
            ->get();

        $featuresByType = [];
        foreach (self::FEATURE_TYPES as $type) {
            $featuresByType[$type] = 0;
        }

        foreach ($byTypeRaw as $item) {
            if ($item->action === 'gained') {
                $featuresByType[$item->feature_type] = $item->count;
            }
        }

        // Gains and losses this week
        $oneWeekAgo = now()->subWeek();
        $gainsThisWeek = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->where('detected_at', '>=', $oneWeekAgo)
            ->count();

        $lossesThisWeek = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->lost()
            ->where('detected_at', '>=', $oneWeekAgo)
            ->count();

        return [
            'totalKeywordsWithFeatures' => $totalKeywordsWithFeatures,
            'totalFeatures' => $totalFeatures,
            'featuresByType' => $featuresByType,
            'gainsThisWeek' => $gainsThisWeek,
            'lossesThisWeek' => $lossesThisWeek,
        ];
    }

    /**
     * Get features that have been held the longest.
     *
     * @param int $siteId
     * @param int $limit
     * @return array Array of {keyword_id, keyword, feature_type, held_since, days_held}
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getLongestHeldFeatures(int $siteId, int $limit = 10): array
    {
        $this->validatePositiveInteger($siteId, 'siteId');
        $this->validatePositiveInteger($limit, 'limit', 1, 100);

        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        if ($keywordIds->isEmpty()) {
            return [];
        }

        // Get all gained features for this site
        $gainedFeatures = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->gained()
            ->orderBy('detected_at', 'asc')
            ->get();

        // Get all lost features for this site
        $lostFeatures = SerpFeatureHistory::whereIn('keyword_id', $keywordIds)
            ->lost()
            ->get()
            ->groupBy(function ($item) {
                return $item->keyword_id . '_' . $item->feature_type;
            });

        // Track currently held features with their gain dates
        $heldFeatures = [];
        $featureStates = []; // Track last known state per keyword/feature_type

        foreach ($gainedFeatures as $gain) {
            $key = $gain->keyword_id . '_' . $gain->feature_type;
            
            // Check if there's a loss after this gain
            $hasLossAfter = false;
            if (isset($lostFeatures[$key])) {
                foreach ($lostFeatures[$key] as $loss) {
                    if ($loss->detected_at > $gain->detected_at) {
                        $hasLossAfter = true;
                        break;
                    }
                }
            }

            if (!$hasLossAfter) {
                // This feature is still held
                $featureStates[$key] = [
                    'keyword_id' => $gain->keyword_id,
                    'feature_type' => $gain->feature_type,
                    'held_since' => $gain->detected_at,
                ];
            }
        }

        // Calculate days held and sort
        $now = now();
        $result = [];
        foreach ($featureStates as $feature) {
            $daysHeld = (int) $now->diffInDays($feature['held_since']);
            $result[] = [
                'keyword_id' => $feature['keyword_id'],
                'feature_type' => $feature['feature_type'],
                'held_since' => $feature['held_since'],
                'days_held' => $daysHeld,
            ];
        }

        // Sort by days_held DESC (oldest held_since = most days_held)
        usort($result, function ($a, $b) {
            // Compare held_since timestamps - older date = more days held
            $aTime = $a['held_since']->timestamp;
            $bTime = $b['held_since']->timestamp;
            return $aTime <=> $bTime; // ASC by date = DESC by days_held
        });

        // Get keyword text for each result
        $keywordsById = Keyword::whereIn('id', array_column($result, 'keyword_id'))
            ->pluck('keyword', 'id');

        // Add keyword text and limit results
        $result = array_slice($result, 0, $limit);
        foreach ($result as &$item) {
            $item['keyword'] = $keywordsById[$item['keyword_id']] ?? null;
        }

        return $result;
    }

    /**
     * Get keywords with the most feature changes (most volatile).
     *
     * @param int $siteId
     * @param int $limit
     * @return array Array of {keyword_id, keyword, change_count, gains, losses}
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getMostVolatileKeywords(int $siteId, int $limit = 10): array
    {
        $this->validatePositiveInteger($siteId, 'siteId');
        $this->validatePositiveInteger($limit, 'limit', 1, 100);

        $keywordIds = Keyword::where('site_id', $siteId)
            ->where('status', 'active')
            ->pluck('id');

        if ($keywordIds->isEmpty()) {
            return [];
        }

        // Count changes per keyword with gains/losses breakdown
        $results = DB::table('serp_feature_history')
            ->select('keyword_id')
            ->selectRaw('COUNT(*) as change_count')
            ->selectRaw('SUM(CASE WHEN action = "gained" THEN 1 ELSE 0 END) as gains')
            ->selectRaw('SUM(CASE WHEN action = "lost" THEN 1 ELSE 0 END) as losses')
            ->whereIn('keyword_id', $keywordIds)
            ->groupBy('keyword_id')
            ->orderByDesc('change_count')
            ->limit($limit)
            ->get();

        // Fetch keyword text
        $keywordsById = Keyword::whereIn('id', $results->pluck('keyword_id'))
            ->pluck('keyword', 'id');

        return $results->map(function ($item) use ($keywordsById) {
            return [
                'keyword_id' => $item->keyword_id,
                'keyword' => $keywordsById[$item->keyword_id] ?? null,
                'change_count' => (int) $item->change_count,
                'gains' => (int) $item->gains,
                'losses' => (int) $item->losses,
            ];
        })->toArray();
    }

    // ========================================
    // Protected Helper Methods
    // ========================================

    /**
     * Validate a history entry.
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    protected function validateHistoryEntry(array $data): void
    {
        if (!isset($data['keyword_id']) || !is_int($data['keyword_id']) || $data['keyword_id'] <= 0) {
            throw new \InvalidArgumentException('keyword_id must be a positive integer');
        }

        if (!isset($data['feature_type']) || !in_array($data['feature_type'], self::FEATURE_TYPES)) {
            throw new \InvalidArgumentException('feature_type must be a valid SERP feature type');
        }

        if (!isset($data['action']) || !in_array($data['action'], ['gained', 'lost'])) {
            throw new \InvalidArgumentException('action must be "gained" or "lost"');
        }
    }

    /**
     * Validate a positive integer parameter.
     *
     * @param mixed $value
     * @param string $name
     * @param int $min
     * @param int $max
     * @throws \InvalidArgumentException
     */
    protected function validatePositiveInteger($value, string $name, int $min = 1, int $max = PHP_INT_MAX): void
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException("{$name} must be an integer");
        }
        if ($value < $min) {
            throw new \InvalidArgumentException("{$name} must be at least {$min}");
        }
        if ($value > $max) {
            throw new \InvalidArgumentException("{$name} must be at most {$max}");
        }
    }

    /**
     * Normalize limit parameter.
     *
     * @param mixed $limit
     * @return int
     */
    protected function normalizeLimit($limit): int
    {
        if (!is_int($limit) || $limit < 1) {
            return $this->defaultLimit;
        }
        return min($limit, $this->maxLimit);
    }

    /**
     * Format paginated result.
     *
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $limit
     * @return array
     */
    protected function formatPaginatedResult(array $data, int $total, int $page, int $limit): array
    {
        $totalPages = (int) ceil($total / max($limit, 1));

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'hasMore' => $page < $totalPages,
            ],
        ];
    }

    /**
     * Get empty statistics structure.
     *
     * @return array
     */
    protected function getEmptyStatistics(): array
    {
        $featuresByType = [];
        foreach (self::FEATURE_TYPES as $type) {
            $featuresByType[$type] = 0;
        }

        return [
            'totalKeywordsWithFeatures' => 0,
            'totalFeatures' => 0,
            'featuresByType' => $featuresByType,
            'gainsThisWeek' => 0,
            'lossesThisWeek' => 0,
        ];
    }
}
