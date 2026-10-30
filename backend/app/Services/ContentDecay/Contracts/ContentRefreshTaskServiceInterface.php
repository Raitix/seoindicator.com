<?php

namespace App\Services\ContentDecay\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for Content Refresh Task Service.
 *
 * Used for mocking in tests. Manages refresh tasks:
 * - Task listing and filtering
 * - Task creation
 * - Task updates
 * - Task deletion
 */
interface ContentRefreshTaskServiceInterface
{
    /**
     * List tasks for a candidate.
     *
     * @param int $candidateId
     * @param array $filters {
     *     @type string $status Filter by status
     *     @type int $owner_user_id Filter by owner
     *     @type string $due_date_from Filter by due date range start
     *     @type string $due_date_to Filter by due date range end
     *     @type string $sort Sort field (due_date, created_at, status)
     *     @type string $order Sort order (asc, desc)
     * }
     * @return Collection
     */
    public function listTasks(int $candidateId, array $filters = []): Collection;

    /**
     * Get a single task by ID.
     *
     * @param int $taskId
     * @return object|null The task or null if not found
     */
    public function getTask(int $taskId): ?object;

    /**
     * Create a new task for a candidate.
     *
     * @param int $candidateId
     * @param array $data {
     *     @type string $title Task title (required)
     *     @type string $recommendation_type Recommendation type (required)
     *     @type string|null $description Task description
     *     @type string|null $notes User notes
     *     @type int|null $owner_user_id Assigned user
     *     @type string|null $due_date Due date
     *     @type string $status Status (default: 'todo')
     *     @type string $source Source (default: 'manual')
     * }
     * @return object Created task
     * @throws \InvalidArgumentException If validation fails
     */
    public function createTask(int $candidateId, array $data): object;

    /**
     * Update a task.
     *
     * @param int $taskId
     * @param array $data {
     *     @type string|null $title Task title
     *     @type string|null $description Task description
     *     @type string|null $notes User notes
     *     @type int|null $owner_user_id Assigned user
     *     @type string|null $due_date Due date
     *     @type string|null $status Status
     * }
     * @return object Updated task
     * @throws \InvalidArgumentException If status transition is invalid
     */
    public function updateTask(int $taskId, array $data): object;

    /**
     * Delete a task.
     *
     * @param int $taskId
     * @return bool
     */
    public function deleteTask(int $taskId): bool;

    /**
     * Get tasks by owner.
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getTasksByOwner(int $userId, array $filters = []): Collection;

    /**
     * Get overdue tasks for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getOverdueTasks(int $userId): Collection;

    /**
     * Get task count for a candidate.
     *
     * @param int $candidateId
     * @return int
     */
    public function getTaskCount(int $candidateId): int;

    /**
     * Check if max tasks limit reached for a candidate.
     *
     * @param int $candidateId
     * @param int $maxTasks Maximum allowed tasks (default 50)
     * @return bool
     */
    public function isMaxTasksReached(int $candidateId, int $maxTasks = 50): bool;
}
