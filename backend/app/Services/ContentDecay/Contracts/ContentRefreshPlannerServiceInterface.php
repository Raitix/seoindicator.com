<?php

namespace App\Services\ContentDecay\Contracts;

use App\Models\ContentDecayCandidate;

/**
 * Interface for Content Refresh Planner Service.
 *
 * Generates actionable recommendations and task suggestions
 * based on content decay reason codes and evidence.
 *
 * @see TASK-36-5
 * @see SPEC-036
 */
interface ContentRefreshPlannerServiceInterface
{
    /**
     * Generate recommendations for a decay candidate.
     *
     * @param ContentDecayCandidate $candidate
     * @return array{
     *     primary_recommendation: string,
     *     supporting_recommendations: array<string>,
     *     task_suggestions: array<int, array{
     *         title: string,
     *         recommendation_type: string,
     *         description: string,
     *         due_date: \Carbon\Carbon,
     *         priority: string,
     *         source: string,
     *         metadata_json: array
     *     }>
     * }
     */
    public function generateRecommendations(ContentDecayCandidate $candidate): array;

    /**
     * Get the recommendation templates.
     *
     * @return array<string, array{
     *     title: string,
     *     description: string,
     *     due_days: int,
     *     priority: string,
     *     recommendation_type: string,
     *     source: string
     * }>
     */
    public function getTemplates(): array;

    /**
     * Get the page type configurations.
     *
     * @return array<string, array{
     *     description_suffix: string,
     *     focus_areas: array<string>
     * }>
     */
    public function getPageTypeConfigs(): array;
}
