<?php

namespace App\Services\SEO;

use App\Models\Project;
use App\Models\BacklinkSnapshot;
use App\Models\ReferringDomain;
use App\Models\AnchorText;

/**
 * Backlink Profile Health Scoring Service.
 *
 * Calculates overall backlink profile health score (0-100) by combining
 * four scoring components with equal weights of 25 points each:
 * - Diversity Score: Ratio of referring domains to backlinks
 * - Trust Score: Average domain trust of referring domains
 * - Growth Score: Month-over-month backlink change
 * - Anchor Health Score: Exact match anchor ratio
 *
 * @see TASK-23-8
 * @see TEST-23-8
 */
class BacklinkProfileScorer
{
    /**
     * Maximum score for each component.
     */
    private const MAX_DIVERSITY_SCORE = 25.0;
    private const MAX_TRUST_SCORE = 25.0;
    private const MAX_GROWTH_SCORE = 25.0;
    private const MAX_ANCHOR_SCORE = 25.0;
    private const MAX_OVERALL_SCORE = 100.0;

    /**
     * Neutral scores when data is missing.
     */
    private const NEUTRAL_GROWTH_SCORE = 12.5;
    private const NEUTRAL_ANCHOR_SCORE = 12.5;

    /**
     * Calculate overall backlink profile health score for a project.
     *
     * @param Project $project
     * @return array{
     *     overall_score: float,
     *     diversity_score: float,
     *     trust_score: float,
     *     growth_score: float,
     *     anchor_health_score: float,
     *     component_details: array{
     *         referring_domains: int,
     *         total_backlinks: int,
     *         avg_domain_trust: float,
     *         growth_rate: float|null,
     *         exact_match_ratio: float|null,
     *     }
     * }
     */
    public function score(Project $project): array
    {
        $metrics = $this->getMetrics($project);

        return $this->calculateScore($metrics);
    }

    /**
     * Calculate score from metrics array.
     *
     * @param array $metrics
     * @return array
     */
    public function calculateScore(array $metrics): array
    {
        $diversityScore = $this->scoreDiversity($metrics);
        $trustScore = $this->scoreTrust($metrics);
        $growthScore = $this->scoreGrowth($metrics);
        $anchorHealthScore = $this->scoreAnchorHealth($metrics);

        $overallScore = $diversityScore + $trustScore + $growthScore + $anchorHealthScore;

        // Cap at max overall score
        $overallScore = min(self::MAX_OVERALL_SCORE, max(0, $overallScore));

        return [
            'overall_score' => $overallScore,
            'diversity_score' => $diversityScore,
            'trust_score' => $trustScore,
            'growth_score' => $growthScore,
            'anchor_health_score' => $anchorHealthScore,
            'component_details' => [
                'referring_domains' => $metrics['referring_domains'] ?? 0,
                'total_backlinks' => $metrics['total_backlinks'] ?? 0,
                'avg_domain_trust' => $metrics['avg_domain_trust'] ?? 0.0,
                'growth_rate' => $metrics['growth_rate'] ?? null,
                'exact_match_ratio' => $metrics['exact_match_anchor_ratio'] ?? null,
            ],
        ];
    }

    /**
     * Check if the profile has any data.
     *
     * @param array $metrics
     * @return bool
     */
    protected function hasProfileData(array $metrics): bool
    {
        $totalBacklinks = $metrics['total_backlinks'] ?? 0;
        return $totalBacklinks > 0;
    }

    /**
     * Calculate diversity score (0-25).
     *
     * Higher ratio of referring domains to backlinks = better diversity.
     * Formula: min(25, (referring_domains / max(1, total_backlinks)) * 100)
     *
     * @param array $metrics
     * @return float
     */
    public function scoreDiversity(array $metrics): float
    {
        $totalBacklinks = $metrics['total_backlinks'] ?? 0;
        $referringDomains = $metrics['referring_domains'] ?? 0;

        // Handle zero backlinks - no diversity possible
        if ($totalBacklinks <= 0) {
            return 0.0;
        }

        // Calculate ratio and scale to 0-100, then cap at 25
        $ratio = ($referringDomains / $totalBacklinks) * 100;

        return min(self::MAX_DIVERSITY_SCORE, max(0, $ratio));
    }

    /**
     * Calculate trust score (0-25).
     *
     * Based on average domain trust of referring domains.
     * Formula: min(25, avg_domain_trust / 4)
     *
     * @param array $metrics
     * @return float
     */
    public function scoreTrust(array $metrics): float
    {
        $avgDomainTrust = $metrics['avg_domain_trust'] ?? null;

        // Null trust defaults to 0
        if ($avgDomainTrust === null) {
            return 0.0;
        }

        // Divide by 4 to scale 0-100 to 0-25
        $score = $avgDomainTrust / 4;

        return min(self::MAX_TRUST_SCORE, max(0, $score));
    }

    /**
     * Calculate growth score (0-25).
     *
     * Based on month-over-month backlink change.
     * Formula: min(25, max(0, 12.5 + growth_rate / 2))
     * - 25% growth = max score (25)
     * - 0% growth = neutral (12.5)
     * - -25% decline = min score (0)
     *
     * @param array $metrics
     * @return float
     */
    public function scoreGrowth(array $metrics): float
    {
        // If no profile data, return 0
        if (!$this->hasProfileData($metrics)) {
            return 0.0;
        }

        $growthRate = $metrics['growth_rate'] ?? null;

        // Null growth rate (no previous snapshot) = neutral score
        if ($growthRate === null) {
            return self::NEUTRAL_GROWTH_SCORE;
        }

        // Calculate score: 12.5 + (growth_rate / 2)
        // - growth_rate = 25 -> 12.5 + 12.5 = 25 (max)
        // - growth_rate = 0 -> 12.5 + 0 = 12.5 (neutral)
        // - growth_rate = -25 -> 12.5 - 12.5 = 0 (min)
        $score = self::NEUTRAL_GROWTH_SCORE + ($growthRate / 2);

        return min(self::MAX_GROWTH_SCORE, max(0, $score));
    }

    /**
     * Calculate anchor health score (0-25).
     *
     * Lower exact-match anchor ratio = better.
     * Formula: min(25, max(0, 25 - exact_match_ratio * 50))
     * - 0% exact match = max score (25)
     * - 25% exact match = moderate (12.5)
     * - 50%+ exact match = min score (0)
     *
     * @param array $metrics
     * @return float
     */
    public function scoreAnchorHealth(array $metrics): float
    {
        // If no profile data, return 0
        if (!$this->hasProfileData($metrics)) {
            return 0.0;
        }

        $exactMatchRatio = $metrics['exact_match_anchor_ratio'] ?? null;

        // Null ratio (no anchor data) = neutral score
        if ($exactMatchRatio === null) {
            return self::NEUTRAL_ANCHOR_SCORE;
        }

        // Calculate penalty: higher ratio = lower score
        // 50% ratio = 0 score
        $score = self::MAX_ANCHOR_SCORE - ($exactMatchRatio * 50);

        return min(self::MAX_ANCHOR_SCORE, max(0, $score));
    }

    /**
     * Get metrics from project's latest snapshot and related data.
     *
     * @param Project $project
     * @return array
     */
    protected function getMetrics(Project $project): array
    {
        // Get latest snapshot
        $latestSnapshot = BacklinkSnapshot::where('project_id', $project->id)
            ->orderByDesc('snapshot_date')
            ->first();

        if (!$latestSnapshot) {
            return [
                'total_backlinks' => 0,
                'referring_domains' => 0,
                'avg_domain_trust' => null,
                'growth_rate' => null,
                'exact_match_anchor_ratio' => null,
            ];
        }

        // Get growth rate from previous snapshot
        $growthRate = $this->calculateGrowthRate($project, $latestSnapshot);

        // Get exact match anchor ratio
        $exactMatchRatio = $this->calculateExactMatchRatio($project, $latestSnapshot);

        // Get average domain trust (prefer snapshot value, fallback to calculated)
        $avgDomainTrust = $latestSnapshot->avg_domain_trust;
        if ($avgDomainTrust === null) {
            $avgDomainTrust = $this->calculateAvgDomainTrust($project);
        }

        return [
            'total_backlinks' => $latestSnapshot->total_backlinks ?? 0,
            'referring_domains' => $latestSnapshot->referring_domains_count ?? 0,
            'avg_domain_trust' => $avgDomainTrust,
            'growth_rate' => $growthRate,
            'exact_match_anchor_ratio' => $exactMatchRatio,
        ];
    }

    /**
     * Calculate growth rate between current and previous snapshot.
     *
     * @param Project $project
     * @param BacklinkSnapshot $currentSnapshot
     * @return float|null
     */
    protected function calculateGrowthRate(Project $project, BacklinkSnapshot $currentSnapshot): ?float
    {
        // Get previous snapshot
        $previousSnapshot = BacklinkSnapshot::where('project_id', $project->id)
            ->where('snapshot_date', '<', $currentSnapshot->snapshot_date)
            ->orderByDesc('snapshot_date')
            ->first();

        if (!$previousSnapshot || $previousSnapshot->total_backlinks <= 0) {
            return null;
        }

        // Calculate percentage change
        $change = $currentSnapshot->total_backlinks - $previousSnapshot->total_backlinks;
        $growthRate = ($change / $previousSnapshot->total_backlinks) * 100;

        return $growthRate;
    }

    /**
     * Calculate exact match anchor ratio from anchor texts.
     *
     * @param Project $project
     * @param BacklinkSnapshot $snapshot
     * @return float|null
     */
    protected function calculateExactMatchRatio(Project $project, BacklinkSnapshot $snapshot): ?float
    {
        $totalBacklinks = $snapshot->total_backlinks ?? 0;

        if ($totalBacklinks <= 0) {
            return null;
        }

        // Get exact match anchor count
        $exactMatchCount = AnchorText::where('project_id', $project->id)
            ->where('is_exact_match', true)
            ->sum('backlinks_count');

        if ($exactMatchCount <= 0) {
            return 0.0;
        }

        return $exactMatchCount / $totalBacklinks;
    }

    /**
     * Calculate average domain trust from referring domains.
     *
     * @param Project $project
     * @return float|null
     */
    protected function calculateAvgDomainTrust(Project $project): ?float
    {
        $domains = ReferringDomain::where('project_id', $project->id)
            ->whereNotNull('domain_trust_score')
            ->get(['domain_trust_score']);

        if ($domains->isEmpty()) {
            return null;
        }

        return $domains->avg('domain_trust_score');
    }
}
