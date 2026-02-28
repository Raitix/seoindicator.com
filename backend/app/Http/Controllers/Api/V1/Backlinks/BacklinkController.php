<?php

namespace App\Http\Controllers\Api\V1\Backlinks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Repositories\BacklinkRepository;
use App\Repositories\ReferringDomainRepository;
use App\Repositories\BacklinkSnapshotRepository;
use App\Services\SEO\BacklinkProfileScorer;
use Illuminate\Http\JsonResponse;

/**
 * Backlink Overview Controller.
 *
 * Provides API endpoint for backlinks overview with summary metrics,
 * recent backlinks, and top referring domains.
 *
 * @see TASK-23-9
 * @see TEST-23-9
 */
class BacklinkController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private BacklinkRepository $backlinks,
        private ReferringDomainRepository $domains,
        private BacklinkSnapshotRepository $snapshots,
        private BacklinkProfileScorer $scorer
    ) {}

    /**
     * Get backlinks overview for a project.
     *
     * @param mixed $projectId
     * @return JsonResponse
     */
    public function index(mixed $projectId): JsonResponse
    {
        // Validate projectId is numeric
        if (!is_numeric($projectId)) {
            return response()->json([
                'message' => 'Invalid project ID.',
            ], 404);
        }

        $project = Project::findOrFail((int) $projectId);

        $this->authorize('view', $project);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->getSummary($project),
                'recentBacklinks' => $this->getRecentBacklinks($project),
                'topReferringDomains' => $this->getTopReferringDomains($project),
            ],
        ]);
    }

    /**
     * Get summary metrics for the project.
     *
     * @param Project $project
     * @return array
     */
    private function getSummary(Project $project): array
    {
        $snapshot = $this->snapshots->getLatest($project->id);

        if (!$snapshot) {
            return [
                'totalBacklinks' => 0,
                'referringDomains' => 0,
                'dofollowRatio' => 0.0,
                'avgDomainTrust' => 0.0,
                'growthRate' => null,
                'profileScore' => null,
            ];
        }

        $dofollowRatio = $this->calculateDofollowRatio($snapshot);
        $growthRate = $this->calculateGrowthRate($project, $snapshot);
        $profileScore = $this->getProfileScore($project);

        return [
            'totalBacklinks' => $snapshot->total_backlinks ?? 0,
            'referringDomains' => $snapshot->referring_domains_count ?? 0,
            'dofollowRatio' => $dofollowRatio,
            'avgDomainTrust' => (float) ($snapshot->avg_domain_trust ?? 0.0),
            'growthRate' => $growthRate,
            'profileScore' => $profileScore,
        ];
    }

    /**
     * Get recent backlinks for the project.
     *
     * @param Project $project
     * @return array
     */
    private function getRecentBacklinks(Project $project): array
    {
        $backlinks = $this->backlinks->getRecent($project->id, 10);

        return $backlinks->map(function ($backlink) {
            return [
                'source_url' => $backlink->source_url,
                'source_domain' => $this->escapeHtml($backlink->source_domain),
                'target_url' => $backlink->target_url,
                'anchor_text' => $this->escapeHtml($backlink->anchor_text),
                'link_type' => $backlink->link_type,
                'first_seen_at' => $backlink->first_seen_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Escape HTML special characters for XSS prevention.
     *
     * @param string|null $value
     * @return string|null
     */
    private function escapeHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get top referring domains for the project.
     *
     * @param Project $project
     * @return array
     */
    private function getTopReferringDomains(Project $project): array
    {
        $domains = $this->domains->getTopByBacklinks($project->id, 5);

        return $domains->map(function ($domain) {
            return [
                'domain' => $domain->domain,
                'backlinks_count' => $domain->backlinks_count,
                'domain_trust_score' => $domain->domain_trust_score,
                'first_seen_at' => $domain->first_seen_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Calculate dofollow ratio from snapshot.
     *
     * @param mixed $snapshot
     * @return float
     */
    private function calculateDofollowRatio($snapshot): float
    {
        $totalBacklinks = $snapshot->total_backlinks ?? 0;

        if ($totalBacklinks <= 0) {
            return 0.0;
        }

        $dofollowCount = $snapshot->dofollow_count ?? 0;

        return round($dofollowCount / $totalBacklinks, 2);
    }

    /**
     * Calculate growth rate from previous snapshot.
     *
     * @param Project $project
     * @param mixed $currentSnapshot
     * @return float|null
     */
    private function calculateGrowthRate(Project $project, $currentSnapshot): ?float
    {
        if (!$currentSnapshot || !$currentSnapshot->snapshot_date) {
            return null;
        }

        $previousSnapshot = $this->snapshots->getPrevious(
            $project->id,
            $currentSnapshot->snapshot_date->toDateString()
        );

        if (!$previousSnapshot) {
            return null;
        }

        $previousBacklinks = $previousSnapshot->total_backlinks ?? 0;

        if ($previousBacklinks <= 0) {
            return null;
        }

        $currentBacklinks = $currentSnapshot->total_backlinks ?? 0;
        $change = $currentBacklinks - $previousBacklinks;

        return round(($change / $previousBacklinks) * 100, 2);
    }

    /**
     * Get profile score from the scorer service.
     *
     * @param Project $project
     * @return int|null
     */
    private function getProfileScore(Project $project): ?int
    {
        $scoreResult = $this->scorer->score($project);

        $overallScore = $scoreResult['overall_score'] ?? 0;

        // Return null if no profile data (overall_score = 0 and no backlinks)
        if ($overallScore === 0.0) {
            $details = $scoreResult['component_details'] ?? [];
            if (($details['total_backlinks'] ?? 0) === 0) {
                return null;
            }
        }

        return (int) round($overallScore);
    }
}
