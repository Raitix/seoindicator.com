<?php

namespace App\Services\ContentDecay;

use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Service for assigning reason codes to content decay signals.
 *
 * Converts numeric signal deltas into human-readable reason codes
 * and builds evidence payloads for UI display.
 *
 * Reason Code Priority (highest to lowest):
 * 1. audit_issues_present - Technical blockers should be fixed first
 * 2. clicks_down - Direct traffic loss
 * 3. ctr_down - SERP presentation issue
 * 4. avg_position_down - Ranking issue
 * 5. tracked_rankings_down - Keyword-specific issue
 * 6. impressions_down - Visibility issue
 * 7. stale_content - Freshness issue
 * 8. ai_visibility_down - GEO issue
 * 9. competitor_gain_suspected - Competitive pressure
 * 10. internal_link_weakness - Site structure issue
 * 11. annotation_recently_changed - Informational (explains volatility)
 *
 * @see TASK-36-4
 * @see SPEC-036
 */
class ContentDecayReasonService implements ContentDecayReasonServiceInterface
{
    /**
     * Reason code priority order (lower = higher priority).
     */
    protected const REASON_PRIORITY = [
        'audit_issues_present' => 1,
        'clicks_down' => 2,
        'ctr_down' => 3,
        'avg_position_down' => 4,
        'tracked_rankings_down' => 5,
        'impressions_down' => 6,
        'stale_content' => 7,
        'ai_visibility_down' => 8,
        'competitor_gain_suspected' => 9,
        'internal_link_weakness' => 10,
        'annotation_recently_changed' => 11,
    ];

    /**
     * Configured thresholds.
     */
    protected array $thresholds;

    /**
     * Create a new ContentDecayReasonService instance.
     *
     * @param array $customThresholds Optional custom thresholds to override defaults
     */
    public function __construct(array $customThresholds = [])
    {
        $this->thresholds = array_merge(
            $this->getDefaultThresholds(),
            $customThresholds
        );
    }

    /**
     * Get default thresholds from config or hardcoded defaults.
     */
    protected function getDefaultThresholds(): array
    {
        return [
            'click_loss' => Config::get('content-decay.thresholds.click_loss', env('CONTENT_DECAY_CLICK_THRESHOLD', 0.10)),
            'impression_loss' => Config::get('content-decay.thresholds.impression_loss', env('CONTENT_DECAY_IMPRESSION_THRESHOLD', 0.15)),
            'ctr_drop' => Config::get('content-decay.thresholds.ctr_drop', env('CONTENT_DECAY_CTR_THRESHOLD', 0.5)),
            'position_decline' => Config::get('content-decay.thresholds.position_decline', env('CONTENT_DECAY_POSITION_THRESHOLD', 3)),
            'staleness_days' => Config::get('content-decay.thresholds.staleness_days', env('CONTENT_DECAY_STALENESS_THRESHOLD', 180)),
            'internal_links' => Config::get('content-decay.thresholds.internal_links', env('CONTENT_DECAY_LINK_THRESHOLD', 3)),
            'annotation_window_days' => Config::get('content-decay.thresholds.annotation_window_days', env('CONTENT_DECAY_ANNOTATION_WINDOW', 30)),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * {@inheritDoc}
     */
    public function assignReasonCodes(array $signals): array
    {
        $reasonCodes = [];
        $evidence = [];

        // Check each reason code
        $this->checkAuditIssues($signals, $reasonCodes, $evidence);
        $this->checkClicksDown($signals, $reasonCodes, $evidence);
        $this->checkCtrDown($signals, $reasonCodes, $evidence);
        $this->checkPositionDown($signals, $reasonCodes, $evidence);
        $this->checkTrackedRankingsDown($signals, $reasonCodes, $evidence);
        $this->checkImpressionsDown($signals, $reasonCodes, $evidence);
        $this->checkStaleContent($signals, $reasonCodes, $evidence);
        $this->checkAiVisibilityDown($signals, $reasonCodes, $evidence);
        $this->checkCompetitorGainSuspected($signals, $reasonCodes, $evidence);
        $this->checkInternalLinkWeakness($signals, $reasonCodes, $evidence);
        $this->checkAnnotationRecentlyChanged($signals, $reasonCodes, $evidence);

        // Select primary reason based on priority
        $primaryReasonCode = $this->selectPrimaryReason($reasonCodes);

        // Mark primary evidence
        if ($primaryReasonCode) {
            foreach ($evidence as $index => $item) {
                $evidence[$index]['primary'] = ($item['reason'] === $primaryReasonCode);
            }
        }

        return [
            'primary_reason_code' => $primaryReasonCode,
            'reason_codes' => $reasonCodes,
            'evidence' => $evidence,
        ];
    }

    /**
     * Check for audit issues present.
     */
    protected function checkAuditIssues(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $issues = Arr::get($signals, 'unresolved_audit_issues');

        if ($issues !== null && $issues > 0) {
            $reasonCodes[] = 'audit_issues_present';
            $evidence[] = [
                'reason' => 'audit_issues_present',
                'primary' => false,
                'evidence' => [
                    'metric' => 'audit_issues',
                    'previous' => null,
                    'current' => $issues,
                    'change_percent' => null,
                    'change_absolute' => null,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'audit_issues'),
                ],
            ];
        }
    }

    /**
     * Check for clicks down.
     */
    protected function checkClicksDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'clicks.previous');
        $current = Arr::get($signals, 'clicks.current');

        if ($previous === null || $current === null) {
            return;
        }

        // Handle zero previous - improvement if current > 0
        if ($previous == 0) {
            return;
        }

        $changePercent = $this->calculateChangePercent($previous, $current);
        $threshold = $this->thresholds['click_loss'];

        // Check for decline exceeding threshold
        if ($changePercent !== null && abs($changePercent) >= ($threshold * 100) && $current < $previous) {
            $reasonCodes[] = 'clicks_down';
            $evidence[] = [
                'reason' => 'clicks_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'clicks',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round($changePercent, 2),
                    'change_absolute' => $current - $previous,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'clicks'),
                ],
            ];
        }
    }

    /**
     * Check for CTR down.
     */
    protected function checkCtrDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'ctr.previous');
        $current = Arr::get($signals, 'ctr.current');
        $impressions = Arr::get($signals, 'impressions.current', 0);

        if ($previous === null || $current === null) {
            return;
        }

        // Require meaningful impressions for CTR to be relevant
        $minImpressionsForCtr = 100;
        if ($impressions < $minImpressionsForCtr) {
            return;
        }

        // Handle zero previous
        if ($previous == 0) {
            return;
        }

        $changeAbsolute = $current - $previous;
        $threshold = $this->thresholds['ctr_drop'];

        // CTR drop is in percentage points
        if ($changeAbsolute <= -$threshold) {
            $reasonCodes[] = 'ctr_down';
            $evidence[] = [
                'reason' => 'ctr_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'ctr',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round(($changeAbsolute / $previous) * 100, 2),
                    'change_absolute' => round($changeAbsolute, 2),
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'ctr'),
                ],
            ];
        }
    }

    /**
     * Check for average position down.
     */
    protected function checkPositionDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'avg_position.previous');
        $current = Arr::get($signals, 'avg_position.current');

        if ($previous === null || $current === null) {
            return;
        }

        // Position is "down" when number increases (worse position)
        $positionChange = $current - $previous;
        $threshold = $this->thresholds['position_decline'];

        if ($positionChange >= $threshold) {
            $reasonCodes[] = 'avg_position_down';
            $evidence[] = [
                'reason' => 'avg_position_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'avg_position',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round(($positionChange / $previous) * 100, 2),
                    'change_absolute' => round($positionChange, 2),
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'avg_position'),
                ],
            ];
        }
    }

    /**
     * Check for tracked rankings down.
     */
    protected function checkTrackedRankingsDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'tracked_rank_visibility.previous');
        $current = Arr::get($signals, 'tracked_rank_visibility.current');

        if ($previous === null || $current === null) {
            return;
        }

        // Visibility score declined
        if ($current < $previous) {
            $reasonCodes[] = 'tracked_rankings_down';
            $evidence[] = [
                'reason' => 'tracked_rankings_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'tracked_rank_visibility',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round((($current - $previous) / $previous) * 100, 2),
                    'change_absolute' => round($current - $previous, 2),
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'rankings'),
                ],
            ];
        }
    }

    /**
     * Check for impressions down.
     */
    protected function checkImpressionsDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'impressions.previous');
        $current = Arr::get($signals, 'impressions.current');

        if ($previous === null || $current === null) {
            return;
        }

        // Handle zero previous
        if ($previous == 0) {
            return;
        }

        $changePercent = $this->calculateChangePercent($previous, $current);
        $threshold = $this->thresholds['impression_loss'];

        // Check for decline exceeding threshold
        if ($changePercent !== null && abs($changePercent) >= ($threshold * 100) && $current < $previous) {
            $reasonCodes[] = 'impressions_down';
            $evidence[] = [
                'reason' => 'impressions_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'impressions',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round($changePercent, 2),
                    'change_absolute' => $current - $previous,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'impressions'),
                ],
            ];
        }
    }

    /**
     * Check for stale content.
     */
    protected function checkStaleContent(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $contentAgeDays = Arr::get($signals, 'content_age_days');

        if ($contentAgeDays === null) {
            return;
        }

        $threshold = $this->thresholds['staleness_days'];

        if ($contentAgeDays >= $threshold) {
            $reasonCodes[] = 'stale_content';
            $evidence[] = [
                'reason' => 'stale_content',
                'primary' => false,
                'evidence' => [
                    'metric' => 'content_age_days',
                    'previous' => null,
                    'current' => $contentAgeDays,
                    'change_percent' => null,
                    'change_absolute' => null,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'stale_content'),
                ],
            ];
        }
    }

    /**
     * Check for AI visibility down.
     */
    protected function checkAiVisibilityDown(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $previous = Arr::get($signals, 'ai_visibility.previous');
        $current = Arr::get($signals, 'ai_visibility.current');

        if ($previous === null || $current === null) {
            return;
        }

        // Handle zero previous
        if ($previous == 0) {
            return;
        }

        // AI visibility declined
        if ($current < $previous) {
            $reasonCodes[] = 'ai_visibility_down';
            $evidence[] = [
                'reason' => 'ai_visibility_down',
                'primary' => false,
                'evidence' => [
                    'metric' => 'ai_visibility',
                    'previous' => $previous,
                    'current' => $current,
                    'change_percent' => round((($current - $previous) / $previous) * 100, 2),
                    'change_absolute' => round($current - $previous, 2),
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'ai_visibility'),
                ],
            ];
        }
    }

    /**
     * Check for competitor gain suspected.
     *
     * Triggered when position worsens but impressions/clicks remain stable,
     * suggesting competitor displacement rather than demand change.
     */
    protected function checkCompetitorGainSuspected(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $prevPosition = Arr::get($signals, 'avg_position.previous');
        $currPosition = Arr::get($signals, 'avg_position.current');
        $prevImpressions = Arr::get($signals, 'impressions.previous', 0);
        $currImpressions = Arr::get($signals, 'impressions.current', 0);
        $prevClicks = Arr::get($signals, 'clicks.previous', 0);
        $currClicks = Arr::get($signals, 'clicks.current', 0);

        if ($prevPosition === null || $currPosition === null) {
            return;
        }

        // Position must worsen
        if ($currPosition <= $prevPosition) {
            return;
        }

        // Position change must be significant
        $positionChange = $currPosition - $prevPosition;
        if ($positionChange < $this->thresholds['position_decline']) {
            return;
        }

        // Impressions should be relatively stable (within 20%)
        $impressionStable = $prevImpressions > 0
            && abs(($currImpressions - $prevImpressions) / $prevImpressions) < 0.20;

        // Clicks should be relatively stable (within 20%)
        $clicksStable = $prevClicks > 0
            && abs(($currClicks - $prevClicks) / $prevClicks) < 0.20;

        // If impressions and clicks are stable but position worsened,
        // competitor likely gained our position
        if ($impressionStable && $clicksStable) {
            $reasonCodes[] = 'competitor_gain_suspected';
            $evidence[] = [
                'reason' => 'competitor_gain_suspected',
                'primary' => false,
                'evidence' => [
                    'metric' => 'competitor_gain',
                    'previous' => $prevPosition,
                    'current' => $currPosition,
                    'change_percent' => round((($currPosition - $prevPosition) / $prevPosition) * 100, 2),
                    'change_absolute' => round($currPosition - $prevPosition, 2),
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'competitor_gain'),
                ],
            ];
        }
    }

    /**
     * Check for internal link weakness.
     */
    protected function checkInternalLinkWeakness(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $internalLinks = Arr::get($signals, 'internal_links');

        if ($internalLinks === null) {
            return;
        }

        $threshold = $this->thresholds['internal_links'];

        if ($internalLinks < $threshold) {
            $reasonCodes[] = 'internal_link_weakness';
            $evidence[] = [
                'reason' => 'internal_link_weakness',
                'primary' => false,
                'evidence' => [
                    'metric' => 'internal_links',
                    'previous' => null,
                    'current' => $internalLinks,
                    'change_percent' => null,
                    'change_absolute' => null,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'internal_links'),
                ],
            ];
        }
    }

    /**
     * Check for annotation recently changed.
     */
    protected function checkAnnotationRecentlyChanged(array $signals, array &$reasonCodes, array &$evidence): void
    {
        $lastAnnotation = Arr::get($signals, 'last_annotation_at');

        if ($lastAnnotation === null) {
            return;
        }

        $annotationDate = \Carbon\Carbon::parse($lastAnnotation);
        $windowDays = $this->thresholds['annotation_window_days'];

        if ($annotationDate->diffInDays(now()) <= $windowDays) {
            $reasonCodes[] = 'annotation_recently_changed';
            $evidence[] = [
                'reason' => 'annotation_recently_changed',
                'primary' => false,
                'evidence' => [
                    'metric' => 'last_annotation',
                    'previous' => null,
                    'current' => $lastAnnotation,
                    'change_percent' => null,
                    'change_absolute' => null,
                    'window' => $this->formatWindow($signals),
                    'context' => $this->formatContext($signals, 'annotation'),
                ],
            ];
        }
    }

    /**
     * Select the primary reason based on priority.
     */
    protected function selectPrimaryReason(array $reasonCodes): ?string
    {
        if (empty($reasonCodes)) {
            return null;
        }

        // Sort by priority (lower number = higher priority)
        usort($reasonCodes, function ($a, $b) {
            $priorityA = self::REASON_PRIORITY[$a] ?? 999;
            $priorityB = self::REASON_PRIORITY[$b] ?? 999;
            return $priorityA <=> $priorityB;
        });

        return $reasonCodes[0];
    }

    /**
     * Calculate percentage change.
     */
    protected function calculateChangePercent(float $previous, float $current): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Format window string for evidence.
     */
    protected function formatWindow(array $signals): string
    {
        $window = Arr::get($signals, 'window', []);

        $previousStart = Arr::get($window, 'previous_start');
        $previousEnd = Arr::get($window, 'previous_end');
        $currentStart = Arr::get($window, 'current_start');
        $currentEnd = Arr::get($window, 'current_end');

        if ($previousStart && $previousEnd && $currentStart && $currentEnd) {
            // Calculate window days
            $previousDays = \Carbon\Carbon::parse($previousStart)->diffInDays(\Carbon\Carbon::parse($previousEnd));
            $currentDays = \Carbon\Carbon::parse($currentStart)->diffInDays(\Carbon\Carbon::parse($currentEnd));

            return "{$previousDays} days vs {$currentDays} days";
        }

        return '28 days vs 28 days';
    }

    /**
     * Format context string for evidence.
     */
    protected function formatContext(array $signals, string $metric): string
    {
        $pageTitle = Arr::get($signals, 'page_title', '');
        $pageType = Arr::get($signals, 'page_type', '');

        $context = [];

        if ($pageTitle) {
            $context[] = $pageTitle;
        }

        if ($pageType) {
            $context[] = "Type: {$pageType}";
        }

        $context[] = "Metric: {$metric}";

        return implode(' | ', $context);
    }
}
