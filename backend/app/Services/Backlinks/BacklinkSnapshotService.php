<?php

namespace App\Services\Backlinks;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Service for managing backlink snapshots and historical data.
 * 
 * TASK-23-6: Backlink Snapshot Service
 * TASK-23-11: Used by BacklinkHistoryController
 * 
 * @see SPEC-023
 */
class BacklinkSnapshotService
{
    /**
     * Get snapshot history for a project within a date range.
     *
     * @param int $projectId
     * @param string|null $from Date string (Y-m-d) or null for default
     * @param string|null $to Date string (Y-m-d) or null for default
     * @return Collection
     * @throws \InvalidArgumentException If date format is invalid
     */
    public function getHistory(int $projectId, ?string $from = null, ?string $to = null): Collection
    {
        // Default to last 30 days if no range specified
        $fromDate = $from ?? now()->subDays(30)->toDateString();
        $toDate = $to ?? now()->toDateString();

        // Validate date format
        if (!$this->isValidDateFormat($fromDate) || !$this->isValidDateFormat($toDate)) {
            throw new \InvalidArgumentException('Invalid date format. Expected YYYY-MM-DD.');
        }

        return BacklinkSnapshot::forProject($projectId)
            ->dateRange($fromDate, $toDate)
            ->orderByDateAsc()
            ->get();
    }

    /**
     * Validate if a string is a valid date in YYYY-MM-DD format.
     *
     * @param string $date The date string to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidDateFormat(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        try {
            \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a snapshot for a project.
     *
     * @param Project $project
     * @return BacklinkSnapshot
     */
    public function createSnapshot(Project $project): BacklinkSnapshot
    {
        // Check if project actually exists in database
        $existingProject = Project::find($project->id);
        
        if (!$existingProject) {
            // Return a snapshot with zero values for non-existent project
            return new BacklinkSnapshot([
                'project_id' => $project->id,
                'total_backlinks' => 0,
                'referring_domains_count' => 0,
                'dofollow_count' => 0,
                'nofollow_count' => 0,
                'avg_domain_trust' => 0.0,
                'anchor_diversity_score' => 0.0,
                'snapshot_date' => now()->toDateString(),
            ]);
        }

        $metrics = $this->calculateMetrics($project);

        return BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => $metrics['total_backlinks'],
            'referring_domains_count' => $metrics['referring_domains_count'],
            'dofollow_count' => $metrics['dofollow_count'],
            'nofollow_count' => $metrics['nofollow_count'],
            'avg_domain_trust' => $metrics['avg_domain_trust'],
            'anchor_diversity_score' => $metrics['anchor_diversity_score'],
            'snapshot_date' => now()->toDateString(),
            'created_at' => now(),
        ]);
    }

    /**
     * Calculate anchor diversity score (0-100).
     * Higher score = more diverse anchor text.
     * Score 0 = all backlinks use same anchor.
     * Score 100 = perfectly even distribution.
     *
     * @param int $projectId
     * @return float
     */
    public function calculateAnchorDiversity(int $projectId): float
    {
        $anchors = \App\Models\AnchorText::where('project_id', $projectId)
            ->orderByDesc('backlinks_count')
            ->get();

        if ($anchors->isEmpty()) {
            return 0.0;
        }

        $totalBacklinks = $anchors->sum('backlinks_count');
        if ($totalBacklinks === 0) {
            return 0.0;
        }

        $topAnchorPercent = $anchors->first()->backlinks_count / $totalBacklinks;

        return round(100 - ($topAnchorPercent * 100), 2);
    }

    /**
     * Calculate average domain trust for a project.
     *
     * @param int $projectId
     * @return float
     */
    public function calculateAverageTrust(int $projectId): float
    {
        $avgTrust = \App\Models\ReferringDomain::where('project_id', $projectId)
            ->avg('domain_trust_score');

        return $avgTrust ? round($avgTrust, 2) : 0.0;
    }

    /**
     * Create a snapshot for a specific date.
     *
     * @param Project $project
     * @param string $date Date in Y-m-d format
     * @return BacklinkSnapshot
     */
    public function createSnapshotForDate(Project $project, string $date): BacklinkSnapshot
    {
        $metrics = $this->calculateMetrics($project);

        // Check if snapshot exists for this project and date
        $existing = BacklinkSnapshot::where('project_id', $project->id)
            ->whereDate('snapshot_date', $date)
            ->first();

        if ($existing) {
            $existing->update([
                'total_backlinks' => $metrics['total_backlinks'],
                'referring_domains_count' => $metrics['referring_domains_count'],
                'dofollow_count' => $metrics['dofollow_count'],
                'nofollow_count' => $metrics['nofollow_count'],
                'avg_domain_trust' => $metrics['avg_domain_trust'],
                'anchor_diversity_score' => $metrics['anchor_diversity_score'],
            ]);
            return $existing->fresh();
        }

        return BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => $metrics['total_backlinks'],
            'referring_domains_count' => $metrics['referring_domains_count'],
            'dofollow_count' => $metrics['dofollow_count'],
            'nofollow_count' => $metrics['nofollow_count'],
            'avg_domain_trust' => $metrics['avg_domain_trust'],
            'anchor_diversity_score' => $metrics['anchor_diversity_score'],
            'snapshot_date' => $date,
            'created_at' => now(),
        ]);
    }

    /**
     * Get the latest snapshot for a project.
     *
     * @param int $projectId
     * @return BacklinkSnapshot|null
     */
    public function getLatestSnapshot(int $projectId): ?BacklinkSnapshot
    {
        return BacklinkSnapshot::where('project_id', $projectId)
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Calculate all metrics for a project.
     *
     * @param Project $project
     * @return array
     */
    protected function calculateMetrics(Project $project): array
    {
        $backlinks = \App\Models\Backlink::where('project_id', $project->id)
            ->where('is_active', true);

        $totalBacklinks = $backlinks->count();
        $dofollowCount = (clone $backlinks)->dofollow()->count();
        $nofollowCount = (clone $backlinks)->nofollow()->count();

        $referringDomainsCount = \App\Models\ReferringDomain::where('project_id', $project->id)
            ->count();

        return [
            'total_backlinks' => $totalBacklinks,
            'referring_domains_count' => $referringDomainsCount,
            'dofollow_count' => $dofollowCount,
            'nofollow_count' => $nofollowCount,
            'avg_domain_trust' => $this->calculateAverageTrust($project->id),
            'anchor_diversity_score' => $this->calculateAnchorDiversity($project->id),
        ];
    }
}
