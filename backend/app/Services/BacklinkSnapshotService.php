<?php

namespace App\Services;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Repositories\AnchorTextRepository;
use App\Repositories\BacklinkRepository;
use App\Repositories\BacklinkSnapshotRepository;
use App\Repositories\ReferringDomainRepository;
use Illuminate\Support\Collection;

/**
 * Service for generating and managing historical backlink snapshots.
 * 
 * Creates daily snapshots with aggregated metrics for trend analysis
 * and tracks backlink profile changes over time.
 */
class BacklinkSnapshotService
{
    public function __construct(
        private BacklinkRepository $backlinkRepository,
        private ReferringDomainRepository $referringDomainRepository,
        private AnchorTextRepository $anchorTextRepository,
        private BacklinkSnapshotRepository $snapshotRepository
    ) {}

    /**
     * Create a snapshot for a project.
     *
     * Aggregates all backlink metrics and stores a daily snapshot.
     * Uses upsert to handle concurrent calls for the same project/date.
     *
     * @param Project $project The project to create a snapshot for
     * @return BacklinkSnapshot The created or updated snapshot
     */
    public function createSnapshot(Project $project): BacklinkSnapshot
    {
        // Aggregate metrics
        $totalBacklinks = $this->backlinkRepository->countActiveByProject($project->id);
        $referringDomainsCount = $this->referringDomainRepository->countByProject($project->id);
        $dofollowCount = $this->backlinkRepository->countByType($project->id, 'dofollow');
        $nofollowCount = $this->backlinkRepository->countByType($project->id, 'nofollow');
        $avgDomainTrust = $this->referringDomainRepository->getAverageTrustScore($project->id);
        $anchorDiversityScore = $this->calculateAnchorDiversity($project->id);

        // Prepare snapshot data
        $data = [
            'project_id' => $project->id,
            'snapshot_date' => now()->toDateString(),
            'total_backlinks' => $totalBacklinks,
            'referring_domains_count' => $referringDomainsCount,
            'dofollow_count' => $dofollowCount,
            'nofollow_count' => $nofollowCount,
            'avg_domain_trust' => $avgDomainTrust,
            'anchor_diversity_score' => $anchorDiversityScore,
        ];

        return $this->snapshotRepository->upsert($data);
    }

    /**
     * Get snapshot history for a date range.
     *
     * Returns all snapshots for a project within the specified date range,
     * ordered by date ascending.
     *
     * @param int $projectId The project ID
     * @param string $from Start date (YYYY-MM-DD)
     * @param string $to End date (YYYY-MM-DD)
     * @return Collection<int, BacklinkSnapshot>
     */
    public function getHistory(int $projectId, string $from, string $to): Collection
    {
        return $this->snapshotRepository->getByDateRange($projectId, $from, $to);
    }

    /**
     * Calculate anchor diversity score (0-100).
     *
     * Higher score = more diverse anchor text distribution.
     * Score 0 = all backlinks use same anchor.
     * Score 100 = perfectly even distribution.
     *
     * Formula: 100 - (top_anchor_percentage * 100)
     *
     * @param int $projectId The project ID
     * @return float Diversity score between 0.0 and 100.0
     */
    public function calculateAnchorDiversity(int $projectId): float
    {
        $topAnchorPercentage = $this->anchorTextRepository->getTopAnchorPercentage($projectId);

        return 100.0 - ($topAnchorPercentage * 100.0);
    }

    /**
     * Calculate average domain trust from referring domains.
     *
     * Returns the average domain_trust_score of all referring domains
     * for a project. Returns 0.0 if no domains exist.
     *
     * @param int $projectId The project ID
     * @return float Average trust score between 0.0 and 100.0
     */
    public function calculateAverageTrust(int $projectId): float
    {
        return $this->referringDomainRepository->getAverageTrustScore($projectId);
    }
}
