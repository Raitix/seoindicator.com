<?php

namespace App\Services\ContentDecay;

/**
 * Content Decay Scoring Service for SPEC-036.
 *
 * Core scoring service that calculates 0-100 decay scores from normalized signals
 * with weight renormalization when optional signals are missing.
 *
 * Base Weights:
 * - click_loss: 0.35
 * - impression_loss: 0.15
 * - ctr_drop: 0.15
 * - position_decline: 0.15
 * - ranking_decline: 0.10 (optional)
 * - audit_pressure: 0.05 (optional)
 * - staleness: 0.05 (optional)
 *
 * Severity Bands:
 * - 0-19: Low
 * - 20-39: Medium
 * - 40-69: High
 * - 70-100: Critical
 *
 * Confidence Multipliers:
 * - GSC only: 0.85
 * - GSC + rankings: 0.93
 * - GSC + rankings + audit/annotations: 1.00
 */
class ContentDecayScoringService
{
    /**
     * Base weights for decay factors.
     * These weights sum to 1.0.
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
     * These trigger weight renormalization when absent.
     */
    private const OPTIONAL_SIGNALS = [
        'ranking_decline',
        'audit_pressure',
        'staleness',
    ];

    /**
     * Core signals that must always be present (GSC data).
     */
    private const CORE_SIGNALS = [
        'click_loss',
        'impression_loss',
        'ctr_drop',
        'position_decline',
    ];

    /**
     * Severity band thresholds.
     */
    private const SEVERITY_BANDS = [
        'low' => [0, 19],
        'medium' => [20, 39],
        'high' => [40, 69],
        'critical' => [70, 100],
    ];

    /**
     * Confidence levels based on data source availability.
     */
    private const CONFIDENCE_LEVELS = [
        'gsc_only' => 0.85,
        'gsc_rankings' => 0.93,
        'full' => 1.00,
    ];

    /**
     * Get the base weights array.
     *
     * @return array<string, float>
     */
    public function getBaseWeights(): array
    {
        return self::BASE_WEIGHTS;
    }

    /**
     * Get renormalized weights when optional signals are missing.
     *
     * @param array<string, mixed> $availableSignals
     * @return array<string, float>
     */
    public function getRenormalizedWeights(array $availableSignals): array
    {
        $missingSignals = [];
        
        foreach (self::OPTIONAL_SIGNALS as $optionalSignal) {
            if (!array_key_exists($optionalSignal, $availableSignals) || 
                $availableSignals[$optionalSignal] === null) {
                $missingSignals[] = $optionalSignal;
            }
        }

        if (empty($missingSignals)) {
            return self::BASE_WEIGHTS;
        }

        // Calculate total weight of missing signals
        $missingWeight = 0.0;
        foreach ($missingSignals as $signal) {
            $missingWeight += self::BASE_WEIGHTS[$signal];
        }

        // Calculate scaling factor to renormalize
        $availableWeight = 1.0 - $missingWeight;
        $scaleFactor = $availableWeight > 0 ? 1.0 / $availableWeight : 1.0;

        // Build renormalized weights
        $renormalizedWeights = [];
        foreach (self::BASE_WEIGHTS as $signal => $weight) {
            if (in_array($signal, $missingSignals)) {
                continue; // Skip missing signals
            }
            $renormalizedWeights[$signal] = $weight * $scaleFactor;
        }

        return $renormalizedWeights;
    }

    /**
     * Calculate decay score from normalized signals.
     *
     * @param array<string, float|null> $signals Normalized signal values (0-1 range)
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
        // Handle empty signals
        if (empty($signals)) {
            return $this->buildResult(0.0, 'low', 0.85, [], []);
        }

        // Get renormalized weights based on available signals
        $weights = $this->getRenormalizedWeights($signals);

        // Calculate confidence based on available data sources
        $confidence = $this->calculateConfidence($signals);

        // Calculate weighted sum
        $weightedSum = 0.0;
        $contributions = [];
        $breakdown = [];

        foreach ($weights as $signal => $weight) {
            $value = $this->getSignalValue($signals, $signal);
            
            // Only negative movement contributes to decay
            $contribution = max(0, $value) * $weight;
            $weightedSum += $contribution;
            
            $contributions[$signal] = $contribution;
            $breakdown[$signal] = [
                'value' => $value,
                'weight' => $weight,
                'contribution' => $contribution * 100, // As percentage
            ];
        }

        // Calculate final decay score (0-100) with confidence multiplier
        $decayScore = $weightedSum * 100 * $confidence;
        $decayScore = max(0, min(100, $decayScore)); // Clamp to 0-100

        // Determine severity
        $severity = $this->determineSeverity($decayScore);

        // Build contributing factors (sorted by contribution)
        $contributingFactors = $this->buildContributingFactors($contributions);

        return $this->buildResult(
            $decayScore,
            $severity,
            $confidence,
            $contributingFactors,
            $breakdown
        );
    }

    /**
     * Normalize click loss value.
     *
     * Formula: max((previous - current) / max(previous, 1), 0)
     *
     * @param int|float $previous Previous click count
     * @param int|float $current Current click count
     * @return float Normalized value (0-1)
     */
    public function normalizeClickLoss(int|float $previous, int|float $current): float
    {
        return $this->normalizeLoss($previous, $current, 1);
    }

    /**
     * Normalize impression loss value.
     *
     * Formula: max((previous - current) / max(previous, 1), 0)
     *
     * @param int|float $previous Previous impression count
     * @param int|float $current Current impression count
     * @return float Normalized value (0-1)
     */
    public function normalizeImpressionLoss(int|float $previous, int|float $current): float
    {
        return $this->normalizeLoss($previous, $current, 1);
    }

    /**
     * Normalize CTR drop value.
     *
     * Formula: max((previous - current) / max(previous, 0.01), 0)
     *
     * @param int|float $previous Previous CTR (as percentage or decimal)
     * @param int|float $current Current CTR (as percentage or decimal)
     * @return float Normalized value (0-1)
     */
    public function normalizeCtrDrop(int|float $previous, int|float $current): float
    {
        return $this->normalizeLoss($previous, $current, 0.01);
    }

    /**
     * Normalize position decline value.
     *
     * Formula: clamp((current - previous) / 10, 0, 1)
     * Higher position number = worse ranking
     *
     * @param int $previous Previous position
     * @param int $current Current position
     * @return float Normalized value (0-1)
     */
    public function normalizePositionDecline(int $previous, int $current): float
    {
        // Position decline: current > previous means drop in rankings
        $decline = $current - $previous;
        
        if ($decline <= 0) {
            return 0.0; // No decline or improvement
        }
        
        // Normalize by dividing by 10 positions, cap at 1.0
        return min(1.0, $decline / 10);
    }

    /**
     * Build the result array.
     *
     * @param float $decayScore
     * @param string $severity
     * @param float $confidence
     * @param array $contributingFactors
     * @param array $breakdown
     * @return array
     */
    private function buildResult(
        float $decayScore,
        string $severity,
        float $confidence,
        array $contributingFactors,
        array $breakdown
    ): array {
        return [
            'decay_score' => round($decayScore, 2),
            'severity' => $severity,
            'detection_confidence' => $confidence,
            'contributing_factors' => $contributingFactors,
            'score_breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate confidence multiplier based on available data sources.
     *
     * @param array<string, float|null> $signals
     * @return float
     */
    private function calculateConfidence(array $signals): float
    {
        $hasRankings = isset($signals['ranking_decline']) && $signals['ranking_decline'] !== null;
        $hasAudit = (isset($signals['audit_pressure']) && $signals['audit_pressure'] !== null) ||
                    (isset($signals['staleness']) && $signals['staleness'] !== null);

        if ($hasRankings && $hasAudit) {
            return self::CONFIDENCE_LEVELS['full'];
        }

        if ($hasRankings) {
            return self::CONFIDENCE_LEVELS['gsc_rankings'];
        }

        return self::CONFIDENCE_LEVELS['gsc_only'];
    }

    /**
     * Get signal value, handling nulls and invalid values.
     *
     * @param array<string, float|null> $signals
     * @param string $signal
     * @return float
     */
    private function getSignalValue(array $signals, string $signal): float
    {
        if (!isset($signals[$signal])) {
            return 0.0;
        }

        $value = $signals[$signal];

        if ($value === null) {
            return 0.0;
        }

        // Handle NaN
        if (is_float($value) && is_nan($value)) {
            return 0.0;
        }

        // Handle INF
        if (is_float($value) && is_infinite($value)) {
            return 1.0;
        }

        // Convert string to float
        if (is_string($value)) {
            $value = (float) $value;
        }

        // Convert boolean to float
        if (is_bool($value)) {
            $value = $value ? 1.0 : 0.0;
        }

        // Handle negative values (only positive decay counts)
        if ($value < 0) {
            return 0.0;
        }

        // Clamp to 0-1
        return max(0, min(1, (float) $value));
    }

    /**
     * Determine severity based on decay score.
     *
     * @param float $score
     * @return string
     */
    private function determineSeverity(float $score): string
    {
        if ($score >= 70) {
            return 'critical';
        }
        if ($score >= 40) {
            return 'high';
        }
        if ($score >= 20) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Build contributing factors array sorted by contribution.
     *
     * @param array<string, float> $contributions
     * @return array<int, array{factor: string, contribution: float}>
     */
    private function buildContributingFactors(array $contributions): array
    {
        $factors = [];
        
        foreach ($contributions as $factor => $contribution) {
            // Only include factors with significant contribution (> 0.01 = 1%)
            if ($contribution > 0.01) {
                $factors[] = [
                    'factor' => $factor,
                    'contribution' => round($contribution * 100, 2),
                ];
            }
        }

        // Sort by contribution descending
        usort($factors, function ($a, $b) {
            return $b['contribution'] <=> $a['contribution'];
        });

        return $factors;
    }

    /**
     * Generic loss normalization helper.
     *
     * @param int|float $previous
     * @param int|float $current
     * @param float $minDivisor Minimum divisor to prevent division by zero
     * @return float
     */
    private function normalizeLoss(int|float $previous, int|float $current, float $minDivisor): float
    {
        $previous = (float) $previous;
        $current = (float) $current;

        // Handle case where previous is 0 or negative
        if ($previous <= 0) {
            return 0.0;
        }

        $loss = $previous - $current;
        
        // Only negative movement counts
        if ($loss <= 0) {
            return 0.0;
        }

        $divisor = max($previous, $minDivisor);
        return $loss / $divisor;
    }
}
