<?php

namespace App\Repositories;

use App\Models\AnalysisHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for AnalysisHistory model.
 *
 * Handles CRUD operations, filtering, and user-scoped queries for tool analyses.
 *
 * @see TASK-25-12
 * @see SPEC-025
 */
class AnalysisHistoryRepository
{
    /**
     * Create a new analysis history record.
     *
     * @param array $data
     * @return AnalysisHistory
     */
    public function create(array $data): AnalysisHistory
    {
        return AnalysisHistory::create($data);
    }

    /**
     * Find an analysis by ID.
     *
     * @param int $id
     * @return AnalysisHistory|null
     */
    public function findById(int $id): ?AnalysisHistory
    {
        return AnalysisHistory::find($id);
    }

    /**
     * Find analyses by user with optional filters.
     *
     * @param int $userId
     * @param array $filters Optional filters: status, tool_name, site_id, from, to, per_page, page
     * @return LengthAwarePaginator
     */
    public function findByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = AnalysisHistory::byUser($userId);

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply tool filter
        if (isset($filters['tool_name'])) {
            $query->where('tool_name', $filters['tool_name']);
        }

        // Apply site filter
        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        // Apply date range filter
        if (isset($filters['from']) && isset($filters['to'])) {
            $from = is_string($filters['from']) ? \Carbon\Carbon::parse($filters['from'])->startOfDay() : $filters['from'];
            $to = is_string($filters['to']) ? \Carbon\Carbon::parse($filters['to'])->endOfDay() : $filters['to'];
            $query->whereBetween('analyzed_at', [$from, $to]);
        } elseif (isset($filters['from'])) {
            $from = is_string($filters['from']) ? \Carbon\Carbon::parse($filters['from'])->startOfDay() : $filters['from'];
            $query->where('analyzed_at', '>=', $from);
        } elseif (isset($filters['to'])) {
            $to = is_string($filters['to']) ? \Carbon\Carbon::parse($filters['to'])->endOfDay() : $filters['to'];
            $query->where('analyzed_at', '<=', $to);
        }

        // Order by analyzed_at descending
        $query->orderBy('analyzed_at', 'desc');

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Update an analysis.
     *
     * @param int $id
     * @param array $data
     * @return AnalysisHistory|bool
     */
    public function update(int $id, array $data): AnalysisHistory|bool
    {
        $analysis = AnalysisHistory::find($id);

        if (!$analysis) {
            return false;
        }

        $analysis->update($data);

        return $analysis->fresh();
    }

    /**
     * Delete an analysis.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $analysis = AnalysisHistory::find($id);

        if (!$analysis) {
            return false;
        }

        return $analysis->delete();
    }

    /**
     * Get analyses by tool for a user.
     *
     * @param int $userId
     * @param string $tool
     * @return Collection
     */
    public function getByTool(int $userId, string $tool): Collection
    {
        return AnalysisHistory::byUser($userId)
            ->byTool($tool)
            ->orderBy('analyzed_at', 'desc')
            ->get();
    }

    /**
     * Get recent analyses for a user.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $userId, int $limit = 10): Collection
    {
        return AnalysisHistory::byUser($userId)
            ->orderBy('analyzed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Count analyses for a user within a date range.
     *
     * @param int $userId
     * @param \Carbon\Carbon|string $from
     * @param \Carbon\Carbon|string $to
     * @return int
     */
    public function countByUserAndDateRange(int $userId, $from, $to): int
    {
        return AnalysisHistory::byUser($userId)
            ->whereBetween('analyzed_at', [$from, $to])
            ->count();
    }

    /**
     * Get analysis statistics for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getStats(int $userId): array
    {
        $baseQuery = AnalysisHistory::byUser($userId);

        // Total count
        $total = (clone $baseQuery)->count();

        // By status
        $byStatus = [
            'completed' => (clone $baseQuery)->completed()->count(),
            'failed' => (clone $baseQuery)->failed()->count(),
            'pending' => (clone $baseQuery)->pending()->count(),
        ];

        // By tool
        $byTool = (clone $baseQuery)
            ->selectRaw('tool_name, COUNT(*) as count')
            ->groupBy('tool_name')
            ->pluck('count', 'tool_name')
            ->toArray();

        // Average duration
        $avgDuration = (clone $baseQuery)
            ->whereNotNull('analysis_duration_ms')
            ->avg('analysis_duration_ms') ?? 0;

        // Success rate (completed / total * 100)
        $successRate = $total > 0 
            ? round(($byStatus['completed'] / $total) * 100, 1) 
            : 0;

        // Time-period counts
        $now = \Carbon\Carbon::now();
        
        $thisMonth = (clone $baseQuery)
            ->whereBetween('analyzed_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth()
            ])->count();
        
        $thisWeek = (clone $baseQuery)
            ->whereBetween('analyzed_at', [
                $now->copy()->startOfWeek(),
                $now
            ])->count();
        
        $today = (clone $baseQuery)
            ->whereBetween('analyzed_at', [
                $now->copy()->startOfDay(),
                $now
            ])->count();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_tool' => $byTool,
            'avg_duration_ms' => (float) $avgDuration,
            'success_rate' => (float) $successRate,
            'this_month' => $thisMonth,
            'this_week' => $thisWeek,
            'today' => $today,
        ];
    }
}
