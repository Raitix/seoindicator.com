<?php

namespace App\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @Task TASK-35-14
 * @Description GBP Insights Service - Stub implementation for controller testing
 * @Note This is a placeholder. Full implementation will be in TASK-35-9
 */
class GBPInsightsService implements GBPInsightsServiceInterface
{
    /**
     * Check if the GBP connection is active.
     */
    public function getConnectionStatus(int $locationId): bool
    {
        $location = \App\Models\Location::find($locationId);
        return $location?->gbp_connected ?? false;
    }

    /**
     * Get insights for a location within a date range.
     */
    public function getInsights(int $locationId, string $startDate, string $endDate): array
    {
        $cacheKey = "gbp_insights:{$locationId}:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($locationId, $startDate, $endDate) {
            // Check if insights table exists and has data
            if (!$this->insightsTableExists()) {
                return $this->emptyInsights();
            }

            $dailyBreakdown = DB::table('gbp_insights')
                ->where('location_id', $locationId)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->date,
                        'queries' => $row->queries ?? 0,
                        'views' => $row->views ?? 0,
                        'actions' => $row->actions ?? 0,
                        'photo_views' => $row->photo_views ?? 0,
                        'driving_directions' => $row->driving_directions ?? 0,
                        'phone_calls' => $row->phone_calls ?? 0,
                        'map_views' => $row->map_views ?? 0,
                        'search_views' => $row->search_views ?? 0,
                    ];
                })
                ->toArray();

            $totals = [
                'queries' => array_sum(array_column($dailyBreakdown, 'queries')),
                'views' => array_sum(array_column($dailyBreakdown, 'views')),
                'actions' => array_sum(array_column($dailyBreakdown, 'actions')),
                'photo_views' => array_sum(array_column($dailyBreakdown, 'photo_views')),
                'driving_directions' => array_sum(array_column($dailyBreakdown, 'driving_directions')),
                'phone_calls' => array_sum(array_column($dailyBreakdown, 'phone_calls')),
                'map_views' => array_sum(array_column($dailyBreakdown, 'map_views')),
                'search_views' => array_sum(array_column($dailyBreakdown, 'search_views')),
            ];

            return [
                'daily_breakdown' => $dailyBreakdown,
                'totals' => $totals,
            ];
        });
    }

    /**
     * Get trend analysis for a location.
     */
    public function getTrend(int $locationId, int $days = 30): array
    {
        $cacheKey = "gbp_trend:{$locationId}:{$days}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($locationId, $days) {
            if (!$this->insightsTableExists()) {
                return ['wow' => [], 'mom' => []];
            }

            $endDate = Carbon::yesterday();
            $startDate = $endDate->copy()->subDays($days - 1);

            // Previous periods
            $prevWeekStart = $startDate->copy()->subWeek();
            $prevWeekEnd = $endDate->copy()->subWeek();
            $prevMonthStart = $startDate->copy()->subMonth();
            $prevMonthEnd = $endDate->copy()->subMonth();

            // Current period data
            $currentData = $this->getAggregatedData($locationId, $startDate->toDateString(), $endDate->toDateString());

            // Week-over-week data
            $prevWeekData = $this->getAggregatedData($locationId, $prevWeekStart->toDateString(), $prevWeekEnd->toDateString());

            // Month-over-month data
            $prevMonthData = $this->getAggregatedData($locationId, $prevMonthStart->toDateString(), $prevMonthEnd->toDateString());

            return [
                'wow' => $this->calculateTrendChanges($currentData, $prevWeekData),
                'mom' => $this->calculateTrendChanges($currentData, $prevMonthData),
            ];
        });
    }

    /**
     * Get the latest insights for a location.
     */
    public function getLatestInsights(int $locationId): ?object
    {
        if (!$this->insightsTableExists()) {
            return null;
        }

        return DB::table('gbp_insights')
            ->where('location_id', $locationId)
            ->orderByDesc('date')
            ->first();
    }

    /**
     * Trigger a background sync of insights from GBP API.
     */
    public function syncInsights(int $locationId): string
    {
        $jobId = 'insights-sync-' . $locationId . '-' . now()->timestamp;

        // Dispatch job (will be implemented in TASK-35-9)
        // For now, just return a job ID
        // \App\Jobs\SyncGBPInsightsJob::dispatch($locationId);

        return $jobId;
    }

    /**
     * Check if the insights table exists.
     */
    protected function insightsTableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('gbp_insights');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return empty insights structure.
     */
    protected function emptyInsights(): array
    {
        return [
            'daily_breakdown' => [],
            'totals' => [
                'queries' => 0,
                'views' => 0,
                'actions' => 0,
                'photo_views' => 0,
                'driving_directions' => 0,
                'phone_calls' => 0,
                'map_views' => 0,
                'search_views' => 0,
            ],
        ];
    }

    /**
     * Get aggregated data for a date range.
     */
    protected function getAggregatedData(int $locationId, string $startDate, string $endDate): array
    {
        $data = DB::table('gbp_insights')
            ->where('location_id', $locationId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(queries), 0) as queries,
                COALESCE(SUM(views), 0) as views,
                COALESCE(SUM(actions), 0) as actions
            ')
            ->first();

        return [
            'queries' => $data->queries ?? 0,
            'views' => $data->views ?? 0,
            'actions' => $data->actions ?? 0,
        ];
    }

    /**
     * Calculate trend changes between two periods.
     */
    protected function calculateTrendChanges(array $current, array $previous): array
    {
        $result = [];

        foreach (['queries', 'views', 'actions'] as $metric) {
            $currentValue = $current[$metric] ?? 0;
            $previousValue = $previous[$metric] ?? 0;

            if ($previousValue > 0) {
                $change = round((($currentValue - $previousValue) / $previousValue) * 100, 1);
            } elseif ($currentValue > 0) {
                $change = 100; // New data, show 100% increase
            } else {
                $change = 0;
            }

            $direction = 'stable';
            if ($change > 2) {
                $direction = 'up';
            } elseif ($change < -2) {
                $direction = 'down';
            }

            $result[$metric] = [
                'change' => $change,
                'absolute_change' => $currentValue - $previousValue,
                'direction' => $direction,
            ];
        }

        return $result;
    }
}
