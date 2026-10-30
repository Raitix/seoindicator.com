<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecaySignal;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Content Decay Candidate Service Implementation for SPEC-036.
 *
 * Manages the candidate queue:
 * - Listing and filtering candidates
 * - Candidate detail retrieval
 * - Status updates and workflow transitions
 * - Restore operations
 */
class ContentDecayCandidateService implements ContentDecayCandidateServiceInterface
{
    /**
     * List candidates for a project with filtering and pagination.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listCandidates(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ContentDecayCandidate::where('project_id', $projectId)
            ->with(['signals' => function ($q) {
                $q->latest('created_at')->limit(1);
            }]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['page_type'])) {
            $query->where('page_type', $filters['page_type']);
        }

        if (!empty($filters['reason_code'])) {
            $query->whereJsonContains('reason_codes', $filters['reason_code']);
        }

        if (!empty($filters['min_lost_clicks'])) {
            $query->where('lost_click_estimate', '>=', $filters['min_lost_clicks']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('page_url', 'like', "%{$filters['search']}%")
                    ->orWhere('page_title', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'decay_score';
        $sortOrder = $filters['order'] ?? 'desc';

        $validSortFields = ['decay_score', 'recovery_upside_estimate', 'lost_click_estimate', 'last_detected_at', 'created_at'];
        if (in_array($sortField, $validSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('decay_score', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a single candidate with all details.
     *
     * @param int $candidateId
     * @return object|null
     */
    public function getCandidate(int $candidateId): ?object
    {
        $candidate = ContentDecayCandidate::with(['signals', 'tasks', 'project.site'])
            ->find($candidateId);

        if (!$candidate) {
            return null;
        }

        return $candidate;
    }

    /**
     * Update a candidate's status and other fields.
     *
     * @param int $candidateId
     * @param array $data
     * @return object
     * @throws \InvalidArgumentException
     */
    public function updateCandidate(int $candidateId, array $data): object
    {
        $candidate = ContentDecayCandidate::find($candidateId);

        if (!$candidate) {
            throw new \InvalidArgumentException('Candidate not found.');
        }

        // Handle status transition
        if (isset($data['status']) && $data['status'] !== $candidate->status) {
            if (!$candidate->canTransitionTo($data['status'])) {
                throw new \InvalidArgumentException(
                    "Invalid status transition from {$candidate->status} to {$data['status']}."
                );
            }

            $candidate->transitionTo($data['status']);
            unset($data['status']); // Already handled by transitionTo
        }

        // Update other fields
        $updateData = [];

        if (isset($data['notes'])) {
            // Store notes in evidence_json
            $evidence = $candidate->evidence_json ?? [];
            $evidence['notes'] = $data['notes'];
            $updateData['evidence_json'] = $evidence;
        }

        if (isset($data['planned_at'])) {
            $updateData['planned_at'] = $data['planned_at'];
        }

        if (isset($data['started_at'])) {
            $updateData['started_at'] = $data['started_at'];
        }

        if (isset($data['refreshed_at'])) {
            $updateData['refreshed_at'] = $data['refreshed_at'];
        }

        if (isset($data['recovered_at'])) {
            $updateData['recovered_at'] = $data['recovered_at'];
        }

        if (!empty($updateData)) {
            $candidate->update($updateData);
        }

        return $candidate->fresh(['signals', 'tasks']);
    }

    /**
     * Restore an ignored/archived candidate to backlog.
     *
     * @param int $candidateId
     * @return object
     * @throws \InvalidArgumentException
     */
    public function restoreCandidate(int $candidateId): object
    {
        $candidate = ContentDecayCandidate::find($candidateId);

        if (!$candidate) {
            throw new \InvalidArgumentException('Candidate not found.');
        }

        if (!$candidate->canBeRestored()) {
            throw new \InvalidArgumentException(
                "Candidate with status {$candidate->status} cannot be restored. Only ignored candidates can be restored."
            );
        }

        $candidate->restore();

        return $candidate->fresh(['signals', 'tasks']);
    }

    /**
     * Bulk update multiple candidates.
     *
     * @param array $candidateIds
     * @param array $data
     * @return array
     */
    public function bulkUpdate(array $candidateIds, array $data): array
    {
        $updated = 0;
        $failed = [];

        foreach ($candidateIds as $id) {
            try {
                $this->updateCandidate($id, $data);
                $updated++;
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $id,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * Get candidates by severity for summary.
     *
     * @param int $projectId
     * @return array
     */
    public function getCountsBySeverity(int $projectId): array
    {
        return ContentDecayCandidate::where('project_id', $projectId)
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get total estimated lost clicks for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getTotalLostClicks(int $projectId): int
    {
        return (int) ContentDecayCandidate::where('project_id', $projectId)
            ->sum('lost_click_estimate');
    }

    /**
     * Get total recovery upside for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getTotalRecoveryUpside(int $projectId): int
    {
        return (int) ContentDecayCandidate::where('project_id', $projectId)
            ->sum('recovery_upside_estimate');
    }

    /**
     * Get recovered count for a project.
     *
     * @param int $projectId
     * @return int
     */
    public function getRecoveredCount(int $projectId): int
    {
        return ContentDecayCandidate::where('project_id', $projectId)
            ->where('status', 'recovered')
            ->count();
    }
}
