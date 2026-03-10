<?php

namespace App\Services\GBP;

use App\Models\BusinessLocation;
use App\Models\GBPInsight;
use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\Contracts\GBPInsightsServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Domain service for syncing and aggregating Google Business Profile insights.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
class GBPInsightsService implements GBPInsightsServiceInterface
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    public function __construct(
        protected GBPApiServiceInterface $apiService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function syncInsights(BusinessLocation $location, ?array $dateRange = null): void
    {
        $placeId = $location->gbp_place_id;
        $accessToken = $location->gbp_access_token;

        if (empty($placeId) || empty($accessToken)) {
            Log::warning('GBPInsightsService: Missing GBP credentials for location', [
                'location_id' => $location->id,
            ]);

            return;
        }

        $range = $dateRange ?? $this->getDefaultDateRange();

        try {
            $response = $this->apiService->getInsights($placeId, $accessToken, $range);

            $dailyMetrics = $response['daily_metrics'] ?? [];

            foreach ($dailyMetrics as $metric) {
                $this->upsertInsight($location->id, $metric, $response);
            }

            // Clear cache for this location after sync
            $this->clearLocationCache($location->id);
        } catch (\Throwable $e) {
            Log::error('GBPInsightsService: Failed to sync insights', [
                'location_id' => $location->id,
                'date_range' => $range,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getInsights(BusinessLocation $location, array $dateRange): Collection
    {
        $cacheKey = $this->getCacheKey($location->id, 'insights', $dateRange);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $dateRange) {
            $startDate = Carbon::parse($dateRange['start'])->format('Y-m-d');
            $endDate = Carbon::parse($dateRange['end'])->format('Y-m-d');

            $query = GBPInsight::query()
                ->where('location_id', $location->id)
                ->whereBetween('insights_date', [$startDate, $endDate])
                ->orderBy('insights_date', 'asc');

            return $query->get()->map(function (GBPInsight $insight) {
                return $this->formatInsightRow($insight);
            });
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getInsightsTrend(BusinessLocation $location, int $days = 90): array
    {
        $cacheKey = $this->getCacheKey($location->id, 'trend', ['days' => $days]);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $days) {
            $endDate = Carbon::today()->format('Y-m-d');
            $startDate = Carbon::today()->subDays($days - 1)->format('Y-m-d');

            $insights = GBPInsight::query()
                ->where('location_id', $location->id)
                ->whereBetween('insights_date', [$startDate, $endDate])
                ->orderBy('insights_date', 'asc')
                ->get();

            $dailyBreakdown = $insights->map(function (GBPInsight $insight) {
                return $this->formatInsightRow($insight);
            })->values()->toArray();

            $totals = $this->calculateTotals($insights);

            $wow = $this->calculateWeekOverWeek($location);
            $mom = $this->calculateMonthOverMonth($location);

            return [
                'daily_breakdown' => $dailyBreakdown,
                'totals' => $totals,
                'wow' => $wow,
                'mom' => $mom,
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalMetrics(BusinessLocation $location, array $dateRange): array
    {
        $cacheKey = $this->getCacheKey($location->id, 'totals', $dateRange);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $dateRange) {
            $startDate = Carbon::parse($dateRange['start'])->format('Y-m-d');
            $endDate = Carbon::parse($dateRange['end'])->format('Y-m-d');

            $insights = GBPInsight::query()
                ->where('location_id', $location->id)
                ->whereBetween('insights_date', [$startDate, $endDate])
                ->get();

            return $this->calculateTotals($insights);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTopPerformingDay(BusinessLocation $location, int $days = 30): ?array
    {
        $cacheKey = $this->getCacheKey($location->id, 'top_day', ['days' => $days]);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $days) {
            $endDate = Carbon::today()->format('Y-m-d');
            $startDate = Carbon::today()->subDays($days - 1)->format('Y-m-d');

            $topDay = GBPInsight::query()
                ->where('location_id', $location->id)
                ->whereBetween('insights_date', [$startDate, $endDate])
                ->get()
                ->sortByDesc(function (GBPInsight $insight) {
                    return $insight->calculateTotalActions();
                })
                ->first();

            if (!$topDay) {
                return null;
            }

            return array_merge($this->formatInsightRow($topDay), [
                'actions' => $topDay->calculateTotalActions(),
            ]);
        });
    }

    /**
     * Get default date range (last 30 days).
     *
     * @return array{start: string, end: string}
     */
    private function getDefaultDateRange(): array
    {
        return [
            'start' => Carbon::today()->subDays(29)->format('Y-m-d'),
            'end' => Carbon::today()->format('Y-m-d'),
        ];
    }

    /**
     * Upsert a single insight record.
     */
    private function upsertInsight(int $locationId, array $metric, array $rawResponse): void
    {
        $date = Carbon::parse($metric['date'])->format('Y-m-d');

        $data = [
            'location_id' => $locationId,
            'views' => (int) ($metric['views'] ?? 0),
            'searches' => (int) ($metric['searches'] ?? 0),
            'calls' => (int) ($metric['calls'] ?? 0),
            'directions' => (int) ($metric['directions'] ?? 0),
            'website_clicks' => (int) ($metric['website_clicks'] ?? 0),
            'photos_views' => (int) ($metric['photos_views'] ?? 0),
            'insights_date' => $date,
            'created_at' => now(),
        ];

        // Use raw query to handle raw_response and upsert
        $existing = DB::table('gbp_insights')
            ->where('location_id', $locationId)
            ->where('insights_date', $date)
            ->first();

        if ($existing) {
            DB::table('gbp_insights')
                ->where('id', $existing->id)
                ->update(array_merge($data, [
                    'raw_response' => json_encode($metric, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]));
        } else {
            DB::table('gbp_insights')->insert(array_merge($data, [
                'raw_response' => json_encode($metric, JSON_THROW_ON_ERROR),
            ]));
        }
    }

    /**
     * Format an insight model for output.
     */
    private function formatInsightRow(GBPInsight $insight): array
    {
        return [
            'date' => $insight->insights_date?->format('Y-m-d'),
            'views' => (int) $insight->views,
            'searches' => (int) $insight->searches,
            'queries' => (int) $insight->searches,
            'calls' => (int) $insight->calls,
            'directions' => (int) $insight->directions,
            'website_clicks' => (int) $insight->website_clicks,
            'actions' => $insight->calculateTotalActions(),
            'photos_views' => (int) $insight->photos_views,
        ];
    }

    /**
     * Calculate aggregate totals from a collection of insights.
     */
    private function calculateTotals(Collection $insights): array
    {
        return [
            'queries' => (int) $insights->sum('searches'),
            'views' => (int) $insights->sum('views'),
            'actions' => (int) $insights->sum(function (GBPInsight $i) {
                return $i->calculateTotalActions();
            }),
            'photos_views' => (int) $insights->sum('photos_views'),
        ];
    }

    /**
     * Calculate week-over-week changes.
     */
    private function calculateWeekOverWeek(BusinessLocation $location): array
    {
        $today = Carbon::today();

        $thisWeekStart = $today->copy()->subDays(6)->format('Y-m-d');
        $thisWeekEnd = $today->copy()->format('Y-m-d');

        $lastWeekStart = $today->copy()->subDays(13)->format('Y-m-d');
        $lastWeekEnd = $today->copy()->subDays(7)->format('Y-m-d');

        $thisWeek = GBPInsight::query()
            ->where('location_id', $location->id)
            ->whereBetween('insights_date', [$thisWeekStart, $thisWeekEnd])
            ->get();

        $lastWeek = GBPInsight::query()
            ->where('location_id', $location->id)
            ->whereBetween('insights_date', [$lastWeekStart, $lastWeekEnd])
            ->get();

        $thisWeekTotals = $this->calculateTotals($thisWeek);
        $lastWeekTotals = $this->calculateTotals($lastWeek);

        return [
            'queries' => $this->calculatePercentageChange($lastWeekTotals['queries'], $thisWeekTotals['queries']),
            'views' => $this->calculatePercentageChange($lastWeekTotals['views'], $thisWeekTotals['views']),
            'actions' => $this->calculatePercentageChange($lastWeekTotals['actions'], $thisWeekTotals['actions']),
        ];
    }

    /**
     * Calculate month-over-month changes.
     */
    private function calculateMonthOverMonth(BusinessLocation $location): array
    {
        $today = Carbon::today();

        $thisMonthStart = $today->copy()->subDays(29)->format('Y-m-d');
        $thisMonthEnd = $today->copy()->format('Y-m-d');

        $lastMonthStart = $today->copy()->subDays(59)->format('Y-m-d');
        $lastMonthEnd = $today->copy()->subDays(30)->format('Y-m-d');

        $thisMonth = GBPInsight::query()
            ->where('location_id', $location->id)
            ->whereBetween('insights_date', [$thisMonthStart, $thisMonthEnd])
            ->get();

        $lastMonth = GBPInsight::query()
            ->where('location_id', $location->id)
            ->whereBetween('insights_date', [$lastMonthStart, $lastMonthEnd])
            ->get();

        $thisMonthTotals = $this->calculateTotals($thisMonth);
        $lastMonthTotals = $this->calculateTotals($lastMonth);

        return [
            'queries' => $this->calculatePercentageChange($lastMonthTotals['queries'], $thisMonthTotals['queries']),
            'views' => $this->calculatePercentageChange($lastMonthTotals['views'], $thisMonthTotals['views']),
            'actions' => $this->calculatePercentageChange($lastMonthTotals['actions'], $thisMonthTotals['actions']),
        ];
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange(int $oldValue, int $newValue): float
    {
        if ($oldValue === 0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Generate a cache key for a location and operation.
     */
    private function getCacheKey(int $locationId, string $operation, array $params = []): string
    {
        $paramsString = md5(serialize($params));

        return "gbp_insights:{$locationId}:{$operation}:{$paramsString}";
    }

    /**
     * Clear all cached data for a location.
     */
    private function clearLocationCache(int $locationId): void
    {
        // Clear specific cache patterns - using tags if available, otherwise pattern-based
        $patterns = [
            "gbp_insights:{$locationId}:insights:",
            "gbp_insights:{$locationId}:totals:",
            "gbp_insights:{$locationId}:trend:",
            "gbp_insights:{$locationId}:top_day:",
        ];

        // Since we can't easily clear pattern-based cache in all drivers,
        // we rely on TTL expiration. For Redis, we could use tags.
        // For now, the cache will naturally expire after TTL.
    }
}
