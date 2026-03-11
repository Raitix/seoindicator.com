<?php

namespace App\Services\ContentDecay\Contracts;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Site;

/**
 * Interface for Content Decay Detection Service.
 *
 * Main orchestration service that gathers signals from multiple sources,
 * compares windows, and creates/updates decay candidates.
 *
 * @see TASK-36-6
 * @see SPEC-036
 */
interface ContentDecayDetectionServiceInterface
{
    /**
     * Run decay detection for all projects belonging to a site.
     *
     * @param Site $site
     * @return array{
     *     processed_pages: int,
     *     eligible_pages: int,
     *     candidates_created: int,
     *     candidates_updated: int,
     *     candidates_archived: int,
     *     errors: int,
     *     duration_ms: int,
     *     pages: array<int, array{
     *         page_url: string,
     *         decay_score: float,
     *         severity: string,
     *         status: string,
     *         reason_codes: array<string>,
     *         recommendation: string
     *     }>,
     *     error?: string
     * }
     */
    public function detectForSite(Site $site): array;

    /**
     * Run decay detection for a specific project.
     *
     * @param ContentDecayProject $project
     * @return array{
     *     project_id: int,
     *     processed_pages: int,
     *     eligible_pages: int,
     *     candidates_created: int,
     *     candidates_updated: int,
     *     candidates_archived: int,
     *     errors: int,
     *     duration_ms: int,
     *     pages: array
     * }
     */
    public function detectForProject(ContentDecayProject $project): array;

    /**
     * Gather signals from all available sources for a page.
     *
     * @param string $pageUrl
     * @param ContentDecayProject $project
     * @return array{
     *     page_url: string,
     *     gathered_at: string,
     *     window: array{previous_start: string, previous_end: string, current_start: string, current_end: string},
     *     gsc: array{previous: array, current: array}|null,
     *     rankings: array{previous: array, current: array}|null,
     *     audit: array|null,
     *     annotations: array|null,
     *     ai_visibility: array{previous: array, current: array}|null
     * }
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project): array;

    /**
     * Compare current vs previous window signals.
     *
     * @param array $signals
     * @return array{
     *     deltas: array<string, float|int>,
     *     percentages: array<string, float>,
     *     direction: array<string, string>,
     *     compared_at: string
     * }
     */
    public function compareWindows(array $signals): array;

    /**
     * Create or update a decay candidate record.
     *
     * @param array $detectionData
     * @return ContentDecayCandidate|null
     */
    public function upsertCandidate(array $detectionData): ?ContentDecayCandidate;

    /**
     * Check if a page is eligible for decay detection.
     *
     * @param array $gscData
     * @param array $options
     * @return bool
     */
    public function isPageEligible(array $gscData, array $options = []): bool;

    /**
     * Check if cooldown is active for a candidate.
     *
     * @param ContentDecayCandidate $candidate
     * @return bool
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool;

    /**
     * Check if comparison is below low-signal threshold.
     *
     * @param array $comparison
     * @return bool
     */
    public function isBelowLowSignalThreshold(array $comparison): bool;
}
