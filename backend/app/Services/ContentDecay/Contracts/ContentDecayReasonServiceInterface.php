<?php

namespace App\Services\ContentDecay\Contracts;

/**
 * Interface for Content Decay Reason Service.
 *
 * Converts numeric signal deltas into human-readable reason codes
 * and builds evidence payloads for UI display.
 *
 * @see TASK-36-4
 * @see SPEC-036
 */
interface ContentDecayReasonServiceInterface
{
    /**
     * Assign reason codes based on signal analysis.
     *
     * @param array $signals Signal data including metrics, page info, and audit data
     * @return array{
     *     primary_reason_code: string|null,
     *     reason_codes: array<string>,
     *     evidence: array<array{reason: string, primary: bool, evidence: array}>
     * }
     */
    public function assignReasonCodes(array $signals): array;

    /**
     * Get the configured thresholds.
     *
     * @return array{
     *     click_loss: float,
     *     impression_loss: float,
     *     ctr_drop: float,
     *     position_decline: int,
     *     staleness_days: int,
     *     internal_links: int,
     *     annotation_window_days: int
     * }
     */
    public function getThresholds(): array;
}
