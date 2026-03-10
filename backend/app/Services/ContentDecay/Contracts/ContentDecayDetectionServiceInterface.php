<?php

namespace App\Services\ContentDecay\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Content Decay Detection Service.
 *
 * Used for mocking in tests. Implements the detection pipeline:
 * - GSC data collection
 * - Signal enrichment
 * - Candidate scoring
 * - Reason code assignment
 */
interface ContentDecayDetectionServiceInterface
{
    /**
     * Run detection for a site.
     *
     * @param int $siteId The site to analyze
     * @param array $options {
     *     @type bool $force Override cooldown period
     *     @type int $window_days Override project window
     * }
     * @return array {
     *     @type string $job_id The queued job ID
     *     @type string $status 'queued' or 'processing'
     * }
     */
    public function runDetection(int $siteId, array $options = []): array;

    /**
     * Get summary statistics for a site's decay queue.
     *
     * @param int $siteId
     * @return array {
     *     @type int $total_candidates
     *     @type int $critical_count
     *     @type int $high_count
     *     @type int $medium_count
     *     @type int $estimated_lost_clicks
     *     @type int $recovery_upside
     *     @type int $recovered_count
     *     @type string|null $last_scanned_at
     * }
     */
    public function getSummary(int $siteId): array;

    /**
     * Update detection settings for a site.
     *
     * @param int $siteId
     * @param array $settings {
     *     @type bool $detection_enabled
     *     @type int $window_days (7, 14, 28, 56)
     *     @type int $min_clicks_threshold
     *     @type int $min_impressions_threshold
     *     @type float $min_decay_score (0-100)
     *     @type bool $digest_enabled
     *     @type string $digest_frequency ('weekly', 'monthly')
     *     @type array $settings_json
     * }
     * @return array Updated settings
     */
    public function updateSettings(int $siteId, array $settings): array;

    /**
     * Check if detection is enabled for a site.
     *
     * @param int $siteId
     * @return bool
     */
    public function isDetectionEnabled(int $siteId): bool;

    /**
     * Get the last scan timestamp for a site.
     *
     * @param int $siteId
     * @return string|null ISO 8601 timestamp or null if never scanned
     */
    public function getLastScanAt(int $siteId): ?string;

    /**
     * Get the project for a site (creates if not exists).
     *
     * @param int $siteId
     * @return object|null The ContentDecayProject model
     */
    public function getProjectForSite(int $siteId): ?object;
}
