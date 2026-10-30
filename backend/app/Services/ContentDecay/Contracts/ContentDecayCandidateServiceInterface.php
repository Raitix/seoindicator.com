<?php

namespace App\Services\ContentDecay\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface for Content Decay Candidate Service.
 *
 * Used for mocking in tests. Manages the candidate queue:
 * - Listing and filtering candidates
 * - Candidate detail retrieval
 * - Status updates and workflow transitions
 * - Restore operations
 */
interface ContentDecayCandidateServiceInterface
{
    /**
     * List candidates for a project with filtering and pagination.
     *
     * @param int $projectId
     * @param array $filters {
     *     @type string $status Filter by status
     *     @type string $severity Filter by severity
     *     @type string $page_type Filter by page type
     *     @type string $reason_code Filter by reason code
     *     @type int $min_lost_clicks Minimum lost clicks
     *     @type string $search Search in URL/title
     *     @type string $sort Sort field (decay_score, recovery_upside, lost_clicks, last_detected)
     *     @type string $order Sort order (asc, desc)
     * }
     * @param int $perPage Items per page (default 15, max 100)
     * @return LengthAwarePaginator
     */
    public function listCandidates(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single candidate with all details.
     *
     * @param int $candidateId
     * @return object|null The candidate with signals, evidence, and recommendations
     */
    public function getCandidate(int $candidateId): ?object;

    /**
     * Update a candidate's status and other fields.
     *
     * @param int $candidateId
     * @param array $data {
     *     @type string $status New status
     *     @type string|null $notes User notes
     *     @type string|null $planned_at Planned date
     *     @type string|null $started_at Started date
     *     @type string|null $refreshed_at Refreshed date
     *     @type string|null $recovered_at Recovered date
     * }
     * @return object Updated candidate
     * @throws \InvalidArgumentException If status transition is invalid
     */
    public function updateCandidate(int $candidateId, array $data): object;

    /**
     * Restore an ignored/archived candidate to backlog.
     *
     * @param int $candidateId
     * @return object Restored candidate
     * @throws \InvalidArgumentException If candidate is not in restorable state
     */
    public function restoreCandidate(int $candidateId): object;

    /**
     * Bulk update multiple candidates.
     *
     * @param array $candidateIds Array of candidate IDs
     * @param array $data Update data (typically just status)
     * @return array {
     *     @type int $updated Count of updated candidates
     *     @type array $failed IDs that failed to update with reasons
     * }
     */
    public function bulkUpdate(array $candidateIds, array $data): array;

    /**
     * Get candidates by severity for summary.
     *
     * @param int $projectId
     * @return array Counts by severity
     */
    public function getCountsBySeverity(int $projectId): array;

    /**
     * Get total estimated lost clicks for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getTotalLostClicks(int $projectId): int;

    /**
     * Get total recovery upside for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getTotalRecoveryUpside(int $projectId): int;

    /**
     * Get recovered count for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getRecoveredCount(int $projectId): int;
}
