<?php

namespace App\Services\AIVisibility\Contracts;

use App\Models\AIVisibility\AIVisibilityProject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for AI Visibility Service
 * 
 * Orchestrates project management, query execution, and analytics retrieval
 * for AI visibility tracking (Generative Engine Optimization).
 * 
 * @task TASK-34-5
 * @spec SPEC-034
 */
interface AIVisibilityServiceInterface
{
    /**
     * Get paginated list of user's projects with optional filters.
     *
     * @param int $userId The user ID
     * @param array $filters Optional filters (is_active, sort_by, sort_order, per_page)
     * @return LengthAwarePaginator
     */
    public function getUserProjects(int $userId, array $filters = []): LengthAwarePaginator;

    /**
     * Get a single project by ID for a specific user.
     *
     * @param int $projectId The project ID
     * @param int $userId The user ID (for ownership validation)
     * @return AIVisibilityProject|null
     */
    public function getProject(int $projectId, int $userId): ?AIVisibilityProject;

    /**
     * Create a new AI visibility project.
     *
     * @param int $userId The user ID who owns the project
     * @param array $data Project data (brand_name, keywords, competitors, etc.)
     * @return AIVisibilityProject
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createProject(int $userId, array $data): AIVisibilityProject;

    /**
     * Update an existing AI visibility project.
     *
     * @param int $projectId The project ID to update
     * @param array $data Updated project data
     * @return AIVisibilityProject
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateProject(int $projectId, array $data): AIVisibilityProject;

    /**
     * Delete an AI visibility project.
     *
     * @param int $projectId The project ID to delete
     * @param int $userId The user ID (for ownership validation)
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteProject(int $projectId, int $userId): bool;

    /**
     * Run queries for a project across all configured platforms.
     *
     * @param int $projectId The project ID
     * @return array Array of dispatched job IDs
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \App\Services\AIVisibility\Exceptions\QueryLimitExceededException
     */
    public function runQueries(int $projectId): array;

    /**
     * Get analytics for a project.
     *
     * @param int $projectId The project ID
     * @param string $period Time period: 'day', 'week', or 'month'
     * @return array Analytics data (mention_rate_by_platform, share_of_voice, sentiment_breakdown, trend_direction)
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAnalytics(int $projectId, string $period = 'week'): array;

    /**
     * Get competitor comparison for a project.
     *
     * @param int $projectId The project ID
     * @return array Brand vs competitors comparison data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCompetitorComparison(int $projectId): array;

    /**
     * Check for alerts on a project.
     *
     * @param int $projectId The project ID
     * @return array Array of triggered alerts
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function checkAlerts(int $projectId): array;

    /**
     * Get the query limit for a user based on their tier.
     *
     * @param int $userId The user ID
     * @return int Maximum queries allowed per month
     */
    public function getQueryLimit(int $userId): int;

    /**
     * Get the current month's query usage for a user.
     *
     * @param int $userId The user ID
     * @return int Number of queries used this month
     */
    public function getQueryUsage(int $userId): int;

    /**
     * Get the remaining queries for a user.
     *
     * @param int $userId The user ID
     * @return int Number of remaining queries
     */
    public function getRemainingQueries(int $userId): int;

    /**
     * Check if a user can run a certain number of queries.
     *
     * @param int $userId The user ID
     * @param int $count Number of queries to check (default: 1)
     * @return bool
     */
    public function canRunQueries(int $userId, int $count = 1): bool;

    /**
     * Get active projects that are due for querying.
     *
     * @param string|null $frequency Filter by tracking frequency (daily, weekly, monthly)
     * @return array Array of project data with id, brand_name, platforms, default_query, use_free_model
     */
    public function getActiveProjectsForQuery(?string $frequency = null): array;

    /**
     * Get unprocessed query results for aggregation.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function getUnprocessedQueryResults(): \Illuminate\Support\Collection|array;

    /**
     * Aggregate results for a project and platform.
     *
     * @param int $projectId
     * @param string $platform
     * @param \Illuminate\Support\Collection $results
     * @return array
     */
    public function aggregateResults(int $projectId, string $platform, \Illuminate\Support\Collection $results): array;

    /**
     * Mark results as processed.
     *
     * @param array $ids
     * @return void
     */
    public function markResultsProcessed(array $ids): void;

    /**
     * Get latest analytics for alert checking.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function getLatestAnalytics(): \Illuminate\Support\Collection|array;

    /**
     * Get alert thresholds from configuration.
     *
     * @return array
     */
    public function getAlertThresholds(): array;

    /**
     * Create an alert for a project.
     *
     * @param array $data Alert data (project_id, alert_type, threshold, previous_value, current_value, change_percent, severity)
     * @return array Created alert data
     */
    public function createAlert(array $data): array;
}
