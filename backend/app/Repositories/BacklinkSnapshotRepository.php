<?php

namespace App\Repositories;

use App\Models\BacklinkSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BacklinkSnapshotRepository for TASK-23-2.
 *
 * Provides data access layer for backlink snapshot history.
 */
class BacklinkSnapshotRepository
{
    /**
     * Find a snapshot by ID.
     */
    public function findById(int $id): ?BacklinkSnapshot
    {
        return BacklinkSnapshot::find($id);
    }

    /**
     * Create a new snapshot.
     */
    public function create(array $data): BacklinkSnapshot
    {
        return BacklinkSnapshot::create($data);
    }

    /**
     * Get all snapshots for a project.
     */
    public function getByProject(int $projectId): Collection
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->orderBy('snapshot_date', 'desc')
            ->get();
    }

    /**
     * Get snapshot by project and date.
     */
    public function getByDate(int $projectId, string $date): ?BacklinkSnapshot
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->whereDate('snapshot_date', $date)
            ->first();
    }

    /**
     * Get snapshots by date range.
     */
    public function getByDateRange(int $projectId, string $fromDate, string $toDate): Collection
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->whereDate('snapshot_date', '>=', $fromDate)
            ->whereDate('snapshot_date', '<=', $toDate)
            ->orderBy('snapshot_date', 'asc')
            ->get();
    }

    /**
     * Get the latest snapshot for a project.
     */
    public function getLatest(int $projectId): ?BacklinkSnapshot
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Get the previous snapshot (before a given date).
     */
    public function getPrevious(int $projectId, string $beforeDate): ?BacklinkSnapshot
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->whereDate('snapshot_date', '<', $beforeDate)
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Insert or update a snapshot by project and date.
     */
    public function upsert(array $data): BacklinkSnapshot
    {
        $existing = BacklinkSnapshot::where('project_id', $data['project_id'])
            ->whereDate('snapshot_date', $data['snapshot_date'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return BacklinkSnapshot::create($data);
    }

    /**
     * Calculate backlinks trend between two snapshots.
     */
    public function calculateBacklinksTrend(int $projectId, string $currentDate): ?array
    {
        $current = $this->getByDate($projectId, $currentDate);

        if (!$current) {
            return null;
        }

        $previous = $this->getPrevious($projectId, $currentDate);

        if (!$previous) {
            return [
                'current' => $current->total_backlinks,
                'previous' => null,
                'change' => null,
                'percentage' => null,
            ];
        }

        $change = $current->total_backlinks - $previous->total_backlinks;
        $percentage = $previous->total_backlinks > 0
            ? round(($change / $previous->total_backlinks) * 100, 2)
            : null;

        return [
            'current' => $current->total_backlinks,
            'previous' => $previous->total_backlinks,
            'change' => $change,
            'percentage' => $percentage,
        ];
    }

    /**
     * Calculate referring domains trend between two snapshots.
     */
    public function calculateReferringDomainsTrend(int $projectId, string $currentDate): ?array
    {
        $current = $this->getByDate($projectId, $currentDate);

        if (!$current) {
            return null;
        }

        $previous = $this->getPrevious($projectId, $currentDate);

        if (!$previous) {
            return [
                'current' => $current->referring_domains_count,
                'previous' => null,
                'change' => null,
                'percentage' => null,
            ];
        }

        $change = $current->referring_domains_count - $previous->referring_domains_count;
        $percentage = $previous->referring_domains_count > 0
            ? round(($change / $previous->referring_domains_count) * 100, 2)
            : null;

        return [
            'current' => $current->referring_domains_count,
            'previous' => $previous->referring_domains_count,
            'change' => $change,
            'percentage' => $percentage,
        ];
    }

    /**
     * Get trend percentage for a metric.
     */
    public function getTrendPercentage(int $projectId, string $currentDate, string $metric): ?float
    {
        $trendMethod = "calculate{$metric}Trend";

        if (!method_exists($this, $trendMethod)) {
            return null;
        }

        $trend = $this->$trendMethod($projectId, $currentDate);

        if (!$trend || $trend['percentage'] === null) {
            return null;
        }

        return $trend['percentage'];
    }

    /**
     * Format snapshots for chart data.
     */
    public function formatForChart(int $projectId, ?int $limit = null): array
    {
        $query = BacklinkSnapshot::where('project_id', $projectId)
            ->orderBy('snapshot_date', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        $snapshots = $query->get();

        return [
            'labels' => $snapshots->pluck('snapshot_date')->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('M d');
            })->toArray(),
            'backlinks' => $snapshots->pluck('total_backlinks')->toArray(),
            'referring_domains' => $snapshots->pluck('referring_domains_count')->toArray(),
            'dofollow_count' => $snapshots->pluck('dofollow_count')->toArray(),
            'nofollow_count' => $snapshots->pluck('nofollow_count')->toArray(),
        ];
    }

    /**
     * Delete old snapshots beyond retention period.
     */
    public function deleteOldSnapshots(int $projectId, int $keepDays = 365): int
    {
        $cutoffDate = now()->subDays($keepDays)->toDateString();

        return BacklinkSnapshot::where('project_id', $projectId)
            ->whereDate('snapshot_date', '<', $cutoffDate)
            ->delete();
    }
}
