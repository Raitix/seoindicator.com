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
        // Implementation required
        // Should aggregate metrics and call snapshotRepository->upsert()
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
        // Implementation required
        // Should call snapshotRepository->getByDateRange()
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
        // Implementation required
        // Should use anchorTextRepository->getTopAnchorPercentage()
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
        // Implementation required
        // Should use referringDomainRepository->getAverageTrustScore()
    }
}
