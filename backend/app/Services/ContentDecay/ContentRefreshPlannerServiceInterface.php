<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;

/**
 * Interface for the Content Refresh Planner Service.
 * 
 * This service generates actionable recommendations and task suggestions
 * based on content decay reason codes and evidence.
 */
interface ContentRefreshPlannerServiceInterface
{
    /**
     * Generate recommendations for a content decay candidate.
     *
     * @param ContentDecayCandidate $candidate The decay candidate to analyze
     * @return array{
     *     primary_recommendation: string,
     *     supporting_recommendations: array<int, string>,
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
}
