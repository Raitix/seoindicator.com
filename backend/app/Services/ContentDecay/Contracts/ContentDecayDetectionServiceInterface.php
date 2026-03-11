<?php

namespace App\Services\ContentDecay\Contracts;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Site;

/**
 * Interface for ContentDecayDetectionService.
 *
 * The main orchestration service that gathers signals from multiple sources
 * (GSC, rankings, audits, annotations, AI visibility), compares windows,
 * and creates/updates decay candidates.
 */
interface ContentDecayDetectionServiceInterface
{
    /**
     * Run decay detection for all projects belonging to a site.
     *
     * @param Site $site The site to detect decay for
     * @return array Detection summary with processed pages, candidates created, etc.
     */
    public function detectForSite(Site $site): array;

    /**
     * Run decay detection for a specific project.
     *
     * @param ContentDecayProject $project The project to detect decay for
     * @return array Detection summary with processed pages, candidates created, etc.
     */
    public function detectForProject(ContentDecayProject $project): array;

    /**
     * Gather signals from all available sources for a page.
     *
     * Sources:
     * - GSC (required): clicks, impressions, CTR, avg position
     * - Rank tracking (optional): tracked keyword visibility
     * - Site audit (optional): unresolved issues for page
     * - Annotations (optional): recent update annotations
     * - AI visibility (optional): mention rate delta
     *
     * @param string $pageUrl The URL to gather signals for
     * @param ContentDecayProject $project The project context
     * @return array Signals array with source keys
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project): array;

    /**
     * Compare current vs previous window signals.
     *
     * @param array $signals The signals array from gatherSignals()
     * @return array Comparison with deltas, percentages, and directions
     */
    public function compareWindows(array $signals): array;

    /**
     * Create or update a decay candidate record.
     *
     * @param array $detectionData Detection data including:
     *   - project_id (int)
     *   - page_url (string)
     *   - decay_score (float)
     *   - severity (string)
     *   - detection_confidence (float)
     *   - reason_codes (array)
     *   - signal_sources (array)
     *   - evidence_json (array)
     * @return ContentDecayCandidate|null The created/updated candidate, or null if skipped
     */
    public function upsertCandidate(array $detectionData): ?ContentDecayCandidate;

    /**
     * Check if a page is eligible for decay detection.
     *
     * Eligibility criteria:
     * - Has GSC data for both windows
     * - Previous impressions >= threshold (default: 100) OR clicks >= threshold (default: 10)
     * - Page has existed long enough for 2 full windows
     * - Page is not in ignore state
     *
     * @param array $gscData The GSC data for the page
     * @param array $options Optional eligibility options
     * @return bool True if eligible, false otherwise
     */
    public function isPageEligible(array $gscData, array $options = []): bool;

    /**
     * Check if cooldown is active for a candidate.
     *
     * Cooldown applies when:
     * - Candidate was marked as "refreshed" within the last 14 days
     * - High-confidence flag when annotation indicates recent content update
     *
     * @param ContentDecayCandidate $candidate The candidate to check
     * @return bool True if cooldown is active, false otherwise
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool;

    /**
     * Check if comparison is below low-signal threshold.
     *
     * @param array $comparison The window comparison data
     * @return bool True if below threshold, false otherwise
     */
    public function isBelowLowSignalThreshold(array $comparison): bool;

    /**
     * Get the current configuration.
     *
     * @return array Configuration array
     */
    public function getConfiguration(): array;

    /**
     * Get the last used window days setting.
     *
     * @return int Window days
     */
    public function getLastUsedWindowDays(): int;

    /**
     * Get the scoring service instance.
     *
     * @return ContentDecayScoringServiceInterface
     */
    public function getScoringService(): ContentDecayScoringServiceInterface;

    /**
     * Get the reason service instance.
     *
     * @return ContentDecayReasonServiceInterface
     */
    public function getReasonService(): ContentDecayReasonServiceInterface;

    /**
     * Get the planner service instance.
     *
     * @return ContentRefreshPlannerServiceInterface
     */
    public function getPlannerService(): ContentRefreshPlannerServiceInterface;
}
