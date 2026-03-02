<?php

namespace App\Services\SEO;

use App\Models\Project;
use App\Services\SEO\BacklinkProfileScorer;

/**
 * Backlink Suggestions Service.
 *
 * Generates actionable suggestions for improving backlink profile.
 * Analyzes metrics from BacklinkProfileScorer and produces prioritized
 * recommendations based on detected issues.
 *
 * @see TASK-23-12
 * @see TEST-23-12
 */
class BacklinkSuggestionService
{
    /**
     * Threshold constants for triggering suggestions.
     */
    private const THRESHOLD_REFERRING_DOMAINS = 50;
    private const THRESHOLD_EXACT_MATCH_RATIO = 0.25;
    private const THRESHOLD_DOMAIN_TRUST = 40;
    private const THRESHOLD_GROWTH_DECLINE = -5;
    private const THRESHOLD_NOFOLLOW_RATIO = 0.50;

    /**
     * Suggestion definitions.
     */
    private const SUGGESTIONS = [
        'low_referring_domains' => [
            'title' => 'Increase Referring Domains',
            'description' => 'Build more referring domains. Guest posting and content marketing can help.',
            'priority' => 'high',
        ],
        'high_exact_match_anchors' => [
            'title' => 'Diversify Anchor Text',
            'description' => 'Diversify anchor text. Too many exact-match anchors can appear manipulative.',
            'priority' => 'high',
        ],
        'low_domain_trust' => [
            'title' => 'Improve Link Quality',
            'description' => 'Focus on earning links from higher-authority sites in your niche.',
            'priority' => 'medium',
        ],
        'declining_backlinks' => [
            'title' => 'Recover Lost Backlinks',
            'description' => 'Check for lost backlinks. Some may be recoverable with outreach.',
            'priority' => 'medium',
        ],
        'high_nofollow_ratio' => [
            'title' => 'Increase Dofollow Links',
            'description' => 'More dofollow links would improve your link equity.',
            'priority' => 'low',
        ],
    ];

    /**
     * @var BacklinkProfileScorer|null
     */
    protected ?BacklinkProfileScorer $scorer = null;

    /**
     * Generate suggestions for a project.
     *
     * @param Project $project
     * @return array
     */
    public function generate(Project $project): array
    {
        $scorer = $this->getScorer();
        $scoreResult = $scorer->score($project);

        return $this->generateFromMetrics($scoreResult['component_details'] ?? []);
    }

    /**
     * Generate suggestions from metrics array.
     *
     * @param array $metrics
     * @return array
     */
    public function generateFromMetrics(array $metrics): array
    {
        $suggestions = [];

        // Check low referring domains
        $suggestion = $this->checkLowReferringDomains($metrics);
        if ($suggestion !== null) {
            $suggestions[] = $suggestion;
        }

        // Check high exact match anchors
        $suggestion = $this->checkHighExactMatchAnchors($metrics);
        if ($suggestion !== null) {
            $suggestions[] = $suggestion;
        }

        // Check low domain trust
        $suggestion = $this->checkLowDomainTrust($metrics);
        if ($suggestion !== null) {
            $suggestions[] = $suggestion;
        }

        // Check declining backlinks
        $suggestion = $this->checkDecliningBacklinks($metrics);
        if ($suggestion !== null) {
            $suggestions[] = $suggestion;
        }

        // Check high nofollow ratio
        $suggestion = $this->checkHighNofollowRatio($metrics);
        if ($suggestion !== null) {
            $suggestions[] = $suggestion;
        }

        // Sort by priority: high → medium → low
        return $this->sortByPriority($suggestions);
    }

    /**
     * Check for low referring domains issue.
     *
     * @param array $metrics
     * @return array|null
     */
    protected function checkLowReferringDomains(array $metrics): ?array
    {
        $referringDomains = $metrics['referring_domains'] ?? null;

        // Null means no data - no suggestion
        if ($referringDomains === null) {
            return null;
        }

        // Handle negative values - treat as 0
        $referringDomains = max(0, $referringDomains);

        // Check threshold (exclusive - at threshold = no suggestion)
        if ($referringDomains >= self::THRESHOLD_REFERRING_DOMAINS) {
            return null;
        }

        return $this->createSuggestion(
            'low_referring_domains',
            $referringDomains,
            self::THRESHOLD_REFERRING_DOMAINS
        );
    }

    /**
     * Check for high exact match anchors issue.
     *
     * @param array $metrics
     * @return array|null
     */
    protected function checkHighExactMatchAnchors(array $metrics): ?array
    {
        $exactMatchRatio = $metrics['exact_match_anchor_ratio'] ?? $metrics['exact_match_ratio'] ?? null;

        // Null means no data - no suggestion
        if ($exactMatchRatio === null) {
            return null;
        }

        // Cap ratio at 1.0 for invalid values
        $exactMatchRatio = min(1.0, max(0, $exactMatchRatio));

        // Check threshold (exclusive - at threshold = no suggestion)
        if ($exactMatchRatio <= self::THRESHOLD_EXACT_MATCH_RATIO) {
            return null;
        }

        return $this->createSuggestion(
            'high_exact_match_anchors',
            $exactMatchRatio,
            self::THRESHOLD_EXACT_MATCH_RATIO,
            true // is ratio
        );
    }

    /**
     * Check for low domain trust issue.
     *
     * @param array $metrics
     * @return array|null
     */
    protected function checkLowDomainTrust(array $metrics): ?array
    {
        $avgDomainTrust = $metrics['avg_domain_trust'] ?? null;

        // Null means no data - no suggestion
        if ($avgDomainTrust === null) {
            return null;
        }

        // Handle negative values - treat as 0
        $avgDomainTrust = max(0, $avgDomainTrust);

        // Check threshold (exclusive - at threshold = no suggestion)
        if ($avgDomainTrust >= self::THRESHOLD_DOMAIN_TRUST) {
            return null;
        }

        return $this->createSuggestion(
            'low_domain_trust',
            $avgDomainTrust,
            self::THRESHOLD_DOMAIN_TRUST
        );
    }

    /**
     * Check for declining backlinks issue.
     *
     * @param array $metrics
     * @return array|null
     */
    protected function checkDecliningBacklinks(array $metrics): ?array
    {
        $growthRate = $metrics['growth_rate'] ?? null;

        // Null means no data (first snapshot) - no suggestion
        if ($growthRate === null) {
            return null;
        }

        // Check threshold (exclusive - at threshold = no suggestion)
        // Only trigger if below -5% (more negative)
        if ($growthRate >= self::THRESHOLD_GROWTH_DECLINE) {
            return null;
        }

        return $this->createSuggestion(
            'declining_backlinks',
            $growthRate,
            self::THRESHOLD_GROWTH_DECLINE,
            false, // not a ratio
            true   // is growth rate (negative values)
        );
    }

    /**
     * Check for high nofollow ratio issue.
     *
     * @param array $metrics
     * @return array|null
     */
    protected function checkHighNofollowRatio(array $metrics): ?array
    {
        $dofollowCount = $metrics['dofollow_count'] ?? null;
        $nofollowCount = $metrics['nofollow_count'] ?? null;

        // Need both values to calculate ratio
        if ($dofollowCount === null || $nofollowCount === null) {
            return null;
        }

        // Handle zero total
        $total = $dofollowCount + $nofollowCount;
        if ($total <= 0) {
            return null;
        }

        $nofollowRatio = $nofollowCount / $total;

        // Check threshold (exclusive - at threshold = no suggestion)
        if ($nofollowRatio <= self::THRESHOLD_NOFOLLOW_RATIO) {
            return null;
        }

        return $this->createSuggestion(
            'high_nofollow_ratio',
            $nofollowRatio,
            self::THRESHOLD_NOFOLLOW_RATIO,
            true // is ratio
        );
    }

    /**
     * Create a suggestion array.
     *
     * @param string $id
     * @param float|int $current
     * @param float|int $benchmark
     * @param bool $isRatio
     * @param bool $isGrowthRate
     * @return array
     */
    protected function createSuggestion(
        string $id,
        $current,
        $benchmark,
        bool $isRatio = false,
        bool $isGrowthRate = false
    ): array {
        $definition = self::SUGGESTIONS[$id];
        $impact = $this->calculateImpact($id, $current, $benchmark, $isRatio, $isGrowthRate);

        $suggestion = [
            'id' => $id,
            'title' => $definition['title'],
            'description' => $definition['description'],
            'priority' => $definition['priority'],
            'impact' => $impact,
        ];

        // Add metrics if applicable
        if ($isGrowthRate) {
            // For growth rate, show the decline percentage
            $suggestion['metrics'] = [
                'current' => round($current, 1) . '%',
                'benchmark' => $benchmark . '%',
            ];
        } elseif ($isRatio) {
            // For ratios, show as percentage
            $suggestion['metrics'] = [
                'current' => round($current * 100, 1) . '%',
                'benchmark' => round($benchmark * 100, 1) . '%',
            ];
        } else {
            // For counts
            $gap = $benchmark - $current;
            $suggestion['metrics'] = [
                'current' => (int) $current,
                'benchmark' => (int) $benchmark,
                'gap' => (int) $gap,
            ];
        }

        return $suggestion;
    }

    /**
     * Calculate impact level based on severity.
     *
     * @param string $id
     * @param float|int $current
     * @param float|int $benchmark
     * @param bool $isRatio
     * @param bool $isGrowthRate
     * @return string
     */
    protected function calculateImpact(
        string $id,
        $current,
        $benchmark,
        bool $isRatio = false,
        bool $isGrowthRate = false
    ): string {
        if ($isGrowthRate) {
            // For growth rate, impact based on severity of decline
            // -20% or worse = high, -10% to -20% = medium, -5% to -10% = low
            $absDecline = abs($current);
            if ($absDecline >= 20) {
                return 'high';
            } elseif ($absDecline >= 10) {
                return 'medium';
            }
            return 'low';
        }

        if ($isRatio) {
            // For ratios, impact based on how far above threshold
            // High: ratio >= 50% (0.50), Medium: ratio >= 35% (0.35), Low: ratio 25-35%
            if ($current >= 0.50) {
                return 'high';
            } elseif ($current >= 0.35) {
                return 'medium';
            }
            return 'low';
        }

        // For counts (like referring domains)
        $gap = $benchmark - $current;
        $gapPercent = $gap / max(1, $benchmark);

        if ($gapPercent >= 0.7) {
            return 'high';
        } elseif ($gapPercent >= 0.3) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Sort suggestions by priority.
     *
     * @param array $suggestions
     * @return array
     */
    protected function sortByPriority(array $suggestions): array
    {
        $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

        usort($suggestions, function ($a, $b) use ($priorityOrder, $impactOrder) {
            // First sort by priority
            $priorityDiff = $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            // Then by impact
            return $impactOrder[$a['impact']] <=> $impactOrder[$b['impact']];
        });

        return $suggestions;
    }

    /**
     * Get or create the BacklinkProfileScorer instance.
     *
     * @return BacklinkProfileScorer
     */
    protected function getScorer(): BacklinkProfileScorer
    {
        if ($this->scorer === null) {
            $this->scorer = new BacklinkProfileScorer();
        }
        return $this->scorer;
    }

    /**
     * Set the scorer instance (for testing).
     *
     * @param BacklinkProfileScorer $scorer
     * @return self
     */
    public function setScorer(BacklinkProfileScorer $scorer): self
    {
        $this->scorer = $scorer;
        return $this;
    }
}
