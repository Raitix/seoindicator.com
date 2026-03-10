<?php

namespace App\Services\ContentDecay\Contracts;

/**
 * Interface for Content Decay Service.
 *
 * Used for mocking in tests. Main service for content decay operations:
 * - Summary statistics retrieval
 * - Settings management
 */
interface ContentDecayServiceInterface
{
    /**
     * Get summary statistics for a site's decay queue.
     *
     * @param int $siteId
     * @return array {
     *     @type int $total_candidates
     *     @type int $critical_count
     *     @type int $high_count
     *     @type int $medium_count
     *     @type int $low_count
     *     @type int $estimated_upside_clicks
     *     @type int $recovered_pages
     *     @type string|null $last_scan_at
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
     * @return object Updated settings
     */
    public function updateSettings(int $siteId, array $settings): object;
}
