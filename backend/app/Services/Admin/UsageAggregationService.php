<?php

namespace App\Services\Admin;

use App\Models\AnalysisJob;
use App\Models\AnalysisHistory;
use Illuminate\Support\Facades\DB;

/**
 * Usage Aggregation Service for Admin Dashboard.
 *
 * Aggregates request counts, token usage, and analysis statistics.
 * Uses AnalysisJob and AnalysisHistory as primary data sources.
 *
 * @see TASK-27-18
 * @see SPEC-027
 */
class UsageAggregationService
{
    /**
     * Get all aggregated usage metrics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'requests' => $this->getRequestStats(),
            'tokens' => $this->getTokenStats(),
            'analyses' => $this->getAnalysisStats(),
        ];
    }

    /**
     * Get request statistics.
     *
     * @return array
     */
    protected function getRequestStats(): array
    {
        // Use AnalysisJob as proxy for requests
        $total = AnalysisJob::count();
        $today = AnalysisJob::whereDate('created_at', today())->count();
        $thisWeek = AnalysisJob::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
        $thisMonth = AnalysisJob::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'thisWeek' => $thisWeek,
            'thisMonth' => $thisMonth,
        ];
    }

    /**
     * Get token statistics.
     * Note: Token tracking may not be fully implemented. Returns defaults if data unavailable.
     *
     * @return array
     */
    protected function getTokenStats(): array
    {
        // Check if AnalysisJob has token columns
        $hasTokenFields = $this->hasTokenFields();
        
        if ($hasTokenFields) {
            return $this->getTokenStatsFromJobs();
        }
        
        // Return estimated/default stats if token tracking not available
        return $this->getDefaultTokenStats();
    }

    /**
     * Check if AnalysisJob table has token-related columns.
     *
     * @return bool
     */
    protected function hasTokenFields(): bool
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('analysis_jobs');
            return in_array('input_tokens', $columns) && in_array('output_tokens', $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get token stats from AnalysisJob records.
     *
     * @return array
     */
    protected function getTokenStatsFromJobs(): array
    {
        $stats = DB::table('analysis_jobs')
            ->selectRaw('
                COALESCE(SUM(input_tokens), 0) + COALESCE(SUM(output_tokens), 0) as total,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN input_tokens + output_tokens ELSE 0 END), 0) as today,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN input_tokens + output_tokens ELSE 0 END), 0) as thisWeek,
                COALESCE(SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN input_tokens + output_tokens ELSE 0 END), 0) as thisMonth
            ', [now()->startOfWeek(), now()->month, now()->year])
            ->first();

        $byModel = $this->getTokensByModel();

        return [
            'total' => (int) ($stats->total ?? 0),
            'today' => (int) ($stats->today ?? 0),
            'thisWeek' => (int) ($stats->thisWeek ?? 0),
            'thisMonth' => (int) ($stats->thisMonth ?? 0),
            'byModel' => $byModel,
        ];
    }

    /**
     * Get default token stats when token tracking is not available.
     *
     * @return array
     */
    protected function getDefaultTokenStats(): array
    {
        return [
            'total' => 0,
            'today' => 0,
            'thisWeek' => 0,
            'thisMonth' => 0,
            'byModel' => [],
        ];
    }

    /**
     * Get token breakdown by model.
     *
     * @return array
     */
    protected function getTokensByModel(): array
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('analysis_jobs');
            
            if (!in_array('model', $columns) || !in_array('input_tokens', $columns)) {
                return [];
            }

            $results = DB::table('analysis_jobs')
                ->select('model', DB::raw('SUM(input_tokens + output_tokens) as total_tokens'))
                ->whereNotNull('model')
                ->groupBy('model')
                ->get();

            return $results->pluck('total_tokens', 'model')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get analysis statistics.
     *
     * @return array
     */
    protected function getAnalysisStats(): array
    {
        // Combine AnalysisJob and AnalysisHistory stats
        $jobTotal = AnalysisJob::count();
        $historyTotal = AnalysisHistory::count();

        return [
            'total' => $jobTotal + $historyTotal,
            'today' => AnalysisJob::whereDate('created_at', today())->count() +
                      AnalysisHistory::whereDate('created_at', today())->count(),
            'byStatus' => $this->getAnalysisByStatus(),
            'byType' => $this->getAnalysisByType(),
        ];
    }

    /**
     * Get analysis counts by status.
     *
     * @return array
     */
    protected function getAnalysisByStatus(): array
    {
        $jobStatuses = AnalysisJob::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $historyStatuses = AnalysisHistory::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Merge and sum overlapping statuses
        $merged = [];
        foreach (['pending', 'running', 'completed', 'failed', 'cancelled'] as $status) {
            $merged[$status] = ($jobStatuses[$status] ?? 0) + ($historyStatuses[$status] ?? 0);
        }

        return $merged;
    }

    /**
     * Get analysis counts by type.
     *
     * @return array
     */
    protected function getAnalysisByType(): array
    {
        // AnalysisHistory has tool_name which represents analysis type
        return AnalysisHistory::query()
            ->select('tool_name', DB::raw('count(*) as count'))
            ->groupBy('tool_name')
            ->pluck('count', 'tool_name')
            ->toArray();
    }

    /**
     * Get requests by endpoint.
     *
     * @return array
     */
    public function getRequestsByEndpoint(): array
    {
        // Use tool_name from AnalysisHistory as endpoint proxy
        $results = AnalysisHistory::query()
            ->select('tool_name', DB::raw('count(*) as count'))
            ->groupBy('tool_name')
            ->orderByDesc('count')
            ->get();

        $byEndpoint = [];
        foreach ($results as $row) {
            $endpoint = '/api/v1/tools/' . $row->tool_name;
            $byEndpoint[$endpoint] = $row->count;
        }

        return $byEndpoint;
    }

    /**
     * Get token trends over time.
     *
     * @param int $days
     * @return array
     */
    public function getTokenTrends(int $days = 7): array
    {
        $trends = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            // If token fields exist, get actual data
            if ($this->hasTokenFields()) {
                $tokens = DB::table('analysis_jobs')
                    ->whereDate('created_at', $date)
                    ->sum(DB::raw('COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)'));
            } else {
                // Use job count as proxy
                $tokens = AnalysisJob::whereDate('created_at', $date)->count() * 1000; // Estimate
            }
            
            $trends[] = [
                'date' => $date,
                'tokens' => (int) $tokens,
            ];
        }

        return $trends;
    }
}
