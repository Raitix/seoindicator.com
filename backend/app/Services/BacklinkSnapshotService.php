<?php

namespace App\Services;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Repositories\AnchorTextRepository;
use App\Repositories\BacklinkRepository;
use App\Repositories\BacklinkSnapshotRepository;
use App\Repositories\ReferringDomainRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * BacklinkSnapshotService for TASK-23-6.
 *
 * Generates and manages historical backlink snapshots.
 * Enables trend analysis and tracks backlink profile changes over time.
 *
 * @see TASK-23-6
 * @see SPEC-023
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
     * Generates a daily snapshot with all backlink metrics:
     * - total_backlinks: Count of active backlinks
     * - referring_domains_count: Distinct source domains
     * - dofollow_count: Backlinks with link_type='dofollow'
     * - nofollow_count: Backlinks with link_type='nofollow'
     * - avg_domain_trust: Average of referring_domains.domain_trust_score
     * - anchor_diversity_score: 100 - (top anchor % * 100)
     *
     * @param Project $project The project to snapshot
     * @return BacklinkSnapshot The created or updated snapshot
     */
    public function createSnapshot(Project $project): BacklinkSnapshot
    {
        $projectId = $project->id;

        // Calculate all metrics
        $totalBacklinks = $this->backlinkRepository->getActiveCount($projectId);
        $referringDomainsCount = $this->referringDomainRepository->getTotalCount($projectId);
        $dofollowCount = $this->backlinkRepository->getDofollowCount($projectId);
        $nofollowCount = $this->getNofollowCount($projectId);
        $avgDomainTrust = $this->calculateAverageTrust($projectId);
        $anchorDiversityScore = $this->calculateAnchorDiversity($projectId);

        // Create or update snapshot (upsert by project_id + snapshot_date)
        return $this->snapshotRepository->upsert([
            'project_id' => $projectId,
            'total_backlinks' => $totalBacklinks,
            'referring_domains_count' => $referringDomainsCount,
            'dofollow_count' => $dofollowCount,
            'nofollow_count' => $nofollowCount,
            'avg_domain_trust' => $avgDomainTrust,
            'anchor_diversity_score' => $anchorDiversityScore,
            'snapshot_date' => today()->toDateString(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get snapshot history for a date range.
     *
     * Returns snapshots ordered by date ascending for charting.
     *
     * @param int $projectId The project ID
     * @param string $from Start date (Y-m-d)
     * @param string $to End date (Y-m-d)
     * @return Collection<BacklinkSnapshot>
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
     * Calculation: 100 - (top anchor % * 100)
     *
     * @param int $projectId The project ID
     * @return float Diversity score between 0 and 100
     */
    public function calculateAnchorDiversity(int $projectId): float
    {
        $anchorTexts = $this->anchorTextRepository->getByProject($projectId);

        if ($anchorTexts->isEmpty()) {
            return 0.0;
        }

        $totalBacklinks = $anchorTexts->sum('backlinks_count');

        if ($totalBacklinks === 0) {
            return 0.0;
        }

        // Get the top anchor by backlinks count
        $topAnchor = $anchorTexts->sortByDesc('backlinks_count')->first();
        $topAnchorPercent = $topAnchor->backlinks_count / $totalBacklinks;

        return round(100 - ($topAnchorPercent * 100), 2);
    }

    /**
     * Calculate average domain trust for a project.
     *
     * Returns the average domain_trust_score of all referring domains.
     *
     * @param int $projectId The project ID
     * @return float Average trust score (0-100)
     */
    public function calculateAverageTrust(int $projectId): float
    {
        return round($this->referringDomainRepository->getAverageTrustScore($projectId), 2);
    }

    /**
     * Get count of nofollow backlinks for a project.
     *
     * @param int $projectId The project ID
     * @return int Count of active nofollow backlinks
     */
    private function getNofollowCount(int $projectId): int
    {
        $linkTypes = $this->backlinkRepository->countByLinkType($projectId);
        return $linkTypes['nofollow'] ?? 0;
    }
}
