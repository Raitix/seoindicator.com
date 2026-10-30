<?php

namespace App\Services\ContentDecay;

use App\Models\ContentRefreshTask;
use App\Models\ContentDecayCandidate;
use App\Services\ContentDecay\Contracts\ContentRefreshTaskServiceInterface;
use Illuminate\Support\Collection;

/**
 * Content Refresh Task Service Implementation for SPEC-036.
 *
 * Manages refresh tasks:
 * - Task listing and filtering
 * - Task creation
 * - Task updates
 * - Task deletion
 */
class ContentRefreshTaskService implements ContentRefreshTaskServiceInterface
{
    /**
     * List tasks for a candidate.
     *
     * @param int $candidateId
     * @param array $filters
     * @return Collection
     */
    public function listTasks(int $candidateId, array $filters = []): Collection
    {
        $query = ContentRefreshTask::where('candidate_id', $candidateId)
            ->with(['owner']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['owner_user_id'])) {
            $query->where('owner_user_id', $filters['owner_user_id']);
        }

        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        $validSortFields = ['due_date', 'created_at', 'status'];
        if (in_array($sortField, $validSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->get();
    }

    /**
     * Get a single task by ID.
     *
     * @param int $taskId
     * @return object|null
     */
    public function getTask(int $taskId): ?object
    {
        return ContentRefreshTask::with(['owner', 'candidate'])->find($taskId);
    }

    /**
     * Create a new task for a candidate.
     *
     * @param int $candidateId
     * @param array $data
     * @return object
     * @throws \InvalidArgumentException
     */
    public function createTask(int $candidateId, array $data): object
    {
        $candidate = ContentDecayCandidate::find($candidateId);

        if (!$candidate) {
            throw new \InvalidArgumentException('Candidate not found.');
        }

        // Check if candidate is ignored
        if ($candidate->status === 'ignored') {
            throw new \InvalidArgumentException('Cannot create tasks for an ignored candidate.');
        }

        // Check max tasks limit
        if ($this->isMaxTasksReached($candidateId)) {
            throw new \InvalidArgumentException('Maximum number of tasks (50) reached for this candidate.');
        }

        $task = ContentRefreshTask::create([
            'candidate_id' => $candidateId,
            'title' => $data['title'],
            'recommendation_type' => $data['recommendation_type'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'source' => $data['source'] ?? 'manual',
            'metadata_json' => $data['metadata_json'] ?? null,
        ]);

        return $task->load(['owner', 'candidate']);
    }

    /**
     * Update a task.
     *
     * @param int $taskId
     * @param array $data
     * @return object
     * @throws \InvalidArgumentException
     */
    public function updateTask(int $taskId, array $data): object
    {
        $task = ContentRefreshTask::find($taskId);

        if (!$task) {
            throw new \InvalidArgumentException('Task not found.');
        }

        // Handle status transition
        if (isset($data['status']) && $data['status'] !== $task->status) {
            if (!$task->canTransitionTo($data['status'])) {
                throw new \InvalidArgumentException(
                    "Invalid status transition from {$task->status} to {$data['status']}."
                );
            }
        }

        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $data['notes'];
        }

        if (array_key_exists('owner_user_id', $data)) {
            $updateData['owner_user_id'] = $data['owner_user_id'];
        }

        if (array_key_exists('due_date', $data)) {
            $updateData['due_date'] = $data['due_date'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (!empty($updateData)) {
            $task->update($updateData);
        }

        return $task->fresh(['owner', 'candidate']);
    }

    /**
     * Delete a task.
     *
     * @param int $taskId
     * @return bool
     */
    public function deleteTask(int $taskId): bool
    {
        $task = ContentRefreshTask::find($taskId);

        if (!$task) {
            return false;
        }

        return $task->delete();
    }

    /**
     * Get tasks by owner.
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getTasksByOwner(int $userId, array $filters = []): Collection
    {
        $query = ContentRefreshTask::where('owner_user_id', $userId)
            ->with(['candidate.project.site']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('due_date', 'asc')->get();
    }

    /**
     * Get overdue tasks for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getOverdueTasks(int $userId): Collection
    {
        return ContentRefreshTask::where('owner_user_id', $userId)
            ->overdue()
            ->with(['candidate.project.site'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get task count for a candidate.
     *
     * @param int $candidateId
     * @return int
     */
    public function getTaskCount(int $candidateId): int
    {
        return ContentRefreshTask::where('candidate_id', $candidateId)->count();
    }

    /**
     * Check if max tasks limit reached for a candidate.
     *
     * @param int $candidateId
     * @param int $maxTasks
     * @return bool
     */
    public function isMaxTasksReached(int $candidateId, int $maxTasks = 50): bool
    {
        return $this->getTaskCount($candidateId) >= $maxTasks;
    }
}
