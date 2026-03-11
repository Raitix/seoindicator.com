<?php

namespace App\Services\ContentDecay;

/**
 * Content Decay Scoring Service
 *
 * Calculates 0-100 decay scores from normalized signals with weight renormalization
 * when optional signals are missing or have no contribution.
 */
class ContentDecayScoringService
{
    /**
     * Base weights for decay factors.
     * Must sum to 1.0.
     */
    public const BASE_WEIGHTS = [
        'click_loss' => 0.35,
        'impression_loss' => 0.15,
        'ctr_drop' => 0.15,
        'position_decline' => 0.15,
        'ranking_decline' => 0.10,
        'audit_pressure' => 0.05,
        'staleness' => 0.05,
    ];

    /**
     * Optional signal keys that can be missing.
     */
    private const OPTIONAL_SIGNALS = ['ranking_decline', 'audit_pressure', 'staleness'];

    /**
     * Core signal keys that must always be present in weights (if not explicitly null).
     */
    private const CORE_SIGNALS = ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline'];

    /**
     * Severity bands for decay scores.
     */
    private const SEVERITY_BANDS = [
        'low' => [0, 19],
        'medium' => [20, 39],
        'high' => [40, 69],
        'critical' => [70, 100],
    ];

    /**
     * Confidence multipliers based on data source availability.
     */
    private const CONFIDENCE_LEVELS = [
        'gsc_only' => 0.85,
        'gsc_rankings' => 0.93,
        'full' => 1.00,
    ];

    /**
     * Get the base weights.
     *
     * @return array<string, float>
     */
    public function getBaseWeights(): array
    {
        return self::BASE_WEIGHTS;
    }

    /**
     * Get renormalized weights based on available signals.
     *
     * Renormalization occurs when:
     * - Optional signals are null (missing)
     * - Any signal (core or optional) is explicitly null
     * - Optional signals have zero value (no contribution)
     *
     * @param array<string, mixed> $signals
     * @return array<string, float>
     */
    public function getRenormalizedWeights(array $signals): array
    {
        $weights = self::BASE_WEIGHTS;
        $missingWeight = 0.0;

        // Process all signals
        foreach (array_keys(self::BASE_WEIGHTS) as $signalKey) {
            $shouldRemove = false;

            // Check if signal is explicitly null
            if (array_key_exists($signalKey, $signals) && $signals[$signalKey] === null) {
                $shouldRemove = true;
            }

            // For optional signals: check if missing from array
            if (in_array($signalKey, self::OPTIONAL_SIGNALS) && !array_key_exists($signalKey, $signals)) {
                $shouldRemove = true;
            }

            // For optional signals: check if value is zero (no contribution scenario)
            // This ensures confidence boost actually increases the score
            if (in_array($signalKey, self::OPTIONAL_SIGNALS) && 
                array_key_exists($signalKey, $signals) && 
                $signals[$signalKey] === 0) {
                $shouldRemove = true;
            }

            if ($shouldRemove) {
                $missingWeight += $weights[$signalKey];
                unset($weights[$signalKey]);
            }
        }

        // Renormalize if any signals were removed
        if ($missingWeight > 0) {
            $remainingWeight = array_sum($weights);
            if ($remainingWeight > 0) {
                $scaleFactor = 1.0 / $remainingWeight;
                foreach ($weights as $key => $weight) {
                    $weights[$key] = $weight * $scaleFactor;
                }
            }
        }

        return $weights;
    }

    /**
     * Calculate the decay score from normalized signals.
     *
     * @param array<string, mixed> $signals Normalized signal values (0-1)
     * @return array{
     *     decay_score: float,
     *     severity: string,
     *     detection_confidence: float,
     *     contributing_factors: array<int, array{factor: string, contribution: float}>,
     *     score_breakdown: array<string, array{value: float, weight: float, contribution: float}>
     * }
     */
    public function calculateDecayScore(array $signals): array
    {
        // Get renormalized weights
        $weights = $this->getRenormalizedWeights($signals);

        // Calculate confidence multiplier
        $confidence = $this->calculateConfidence($signals);

        // Process each signal
        $scoreBreakdown = [];
        $totalContribution = 0.0;

        foreach ($weights as $factor => $weight) {
            $value = $this->normalizeSignalValue($signals[$factor] ?? null);

            // Only positive values (decay) contribute
            $normalizedValue = max(0.0, min(1.0, $value));
            $contribution = $normalizedValue * $weight * 100 * $confidence;

            $scoreBreakdown[$factor] = [
                'value' => $normalizedValue,
                'weight' => $weight,
                'contribution' => round($contribution, 4),
            ];

            $totalContribution += $contribution;
        }

        // Final decay score (clamped to 0-100)
        $decayScore = round(max(0.0, min(100.0, $totalContribution)), 2);

        // Determine severity
        $severity = $this->determineSeverity($decayScore);

        // Get contributing factors (sorted by contribution, excluding negligible ones)
        $contributingFactors = $this->getContributingFactors($scoreBreakdown);

        return [
            'decay_score' => $decayScore,
            'severity' => $severity,
            'detection_confidence' => $confidence,
            'contributing_factors' => $contributingFactors,
            'score_breakdown' => $scoreBreakdown,
        ];
    }

    /**
     * Normalize click loss signal.
     *
     * Formula: max((previous - current) / max(previous, 1), 0)
     *
     * @param int|float $previous Previous clicks
     * @param int|float $current Current clicks
     * @return float Normalized value (0-1)
     */
    public function normalizeClickLoss(int|float $previous, int|float $current): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        $loss = ($previous - $current) / max($previous, 1);
        return max(0.0, min(1.0, (float) $loss));
    }

    /**
     * Normalize impression loss signal.
     *
     * Formula: max((previous - current) / max(previous, 1), 0)
     *
     * @param int|float $previous Previous impressions
     * @param int|float $current Current impressions
     * @return float Normalized value (0-1)
     */
    public function normalizeImpressionLoss(int|float $previous, int|float $current): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        $loss = ($previous - $current) / max($previous, 1);
        return max(0.0, min(1.0, (float) $loss));
    }

    /**
     * Normalize CTR drop signal.
     *
     * Formula: max((previous - current) / max(previous, 0.01), 0)
     *
     * @param int|float $previous Previous CTR (as percentage, e.g., 5.0 for 5%)
     * @param int|float $current Current CTR (as percentage)
     * @return float Normalized value (0-1)
     */
    public function normalizeCtrDrop(int|float $previous, int|float $current): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        $loss = ($previous - $current) / max($previous, 0.01);
        return max(0.0, min(1.0, (float) $loss));
    }

    /**
     * Normalize position decline signal.
     *
     * Formula: clamp((current - previous) / 10, 0, 1)
     * Note: Higher position number = worse ranking = more decline
     *
     * @param int $previous Previous position (1 is best)
     * @param int $current Current position
     * @return float Normalized value (0-1)
     */
    public function normalizePositionDecline(int $previous, int $current): float
    {
        // If position improved (current < previous), no decline
        if ($current <= $previous) {
            return 0.0;
        }

        $decline = ($current - $previous) / 10.0;
        return max(0.0, min(1.0, $decline));
    }

    /**
     * Calculate confidence multiplier based on available data sources.
     *
     * - GSC only: 0.85
     * - GSC + rankings: 0.93
     * - GSC + rankings + audit/annotations: 1.00
     *
     * @param array<string, mixed> $signals
     * @return float
     */
    private function calculateConfidence(array $signals): float
    {
        $hasRankings = isset($signals['ranking_decline']) && $signals['ranking_decline'] !== null;
        $hasAudit = (isset($signals['audit_pressure']) && $signals['audit_pressure'] !== null)
            || (isset($signals['staleness']) && $signals['staleness'] !== null);

        if ($hasRankings && $hasAudit) {
            return self::CONFIDENCE_LEVELS['full'];
        }

        if ($hasRankings) {
            return self::CONFIDENCE_LEVELS['gsc_rankings'];
        }

        return self::CONFIDENCE_LEVELS['gsc_only'];
    }

    /**
     * Normalize a signal value to a valid float.
     *
     * Handles: null, INF, NAN, strings, booleans, negative values.
     *
     * @param mixed $value
     * @return float
     */
    private function normalizeSignalValue(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_string($value)) {
            $value = (float) $value;
        }

        if (!is_numeric($value)) {
            return 0.0;
        }

        if (is_infinite($value)) {
            return 1.0;
        }

        if (is_nan($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    /**
     * Determine severity from decay score.
     *
     * @param float $score
     * @return string
     */
    private function determineSeverity(float $score): string
    {
        if ($score >= self::SEVERITY_BANDS['critical'][0]) {
            return 'critical';
        }

        if ($score >= self::SEVERITY_BANDS['high'][0]) {
            return 'high';
        }

        if ($score >= self::SEVERITY_BANDS['medium'][0]) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get contributing factors sorted by contribution.
     *
     * Excludes factors with negligible contribution (< 1%).
     *
     * @param array<string, array{value: float, weight: float, contribution: float}> $breakdown
     * @return array<int, array{factor: string, contribution: float}>
     */
    private function getContributingFactors(array $breakdown): array
    {
        $factors = [];

        foreach ($breakdown as $factor => $data) {
            // Only include factors with meaningful contribution (> 1%)
            if ($data['contribution'] >= 1.0) {
                $factors[] = [
                    'factor' => $factor,
                    'contribution' => $data['contribution'],
                ];
            }
        }

        // Sort by contribution descending
        usort($factors, function ($a, $b) {
            return $b['contribution'] <=> $a['contribution'];
        });

        return $factors;
    }
}
