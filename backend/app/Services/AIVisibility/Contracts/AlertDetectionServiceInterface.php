<?php

namespace App\Services\AIVisibility\Contracts;

use App\Models\AIVisibilityAlert;

/**
 * Interface for Alert Detection Service
 * 
 * Defines the contract for detecting and creating alerts
 * for AI visibility metrics changes.
 * 
 * @see TASK-34-19
 */
interface AlertDetectionServiceInterface
{
    /**
     * Check for mention spike (+20% threshold)
     * 
     * @param int $projectId
     * @param string $period day|week|month
     * @return AIVisibilityAlert|null Returns alert if spike detected, null otherwise
     */
    public function checkMentionSpike(int $projectId, string $period = 'week'): ?AIVisibilityAlert;

    /**
     * Check for mention drop (-20% threshold)
     * 
     * @param int $projectId
     * @param string $period day|week|month
     * @return AIVisibilityAlert|null Returns alert if drop detected, null otherwise
     */
    public function checkMentionDrop(int $projectId, string $period = 'week'): ?AIVisibilityAlert;

    /**
     * Check for competitor gain (+15% threshold)
     * 
     * @param int $projectId
     * @param string $period day|week|month
     * @return AIVisibilityAlert|null Returns alert if competitor gain detected, null otherwise
     */
    public function checkCompetitorGain(int $projectId, string $period = 'week'): ?AIVisibilityAlert;

    /**
     * Check for sentiment change (±0.30 threshold)
     * 
     * @param int $projectId
     * @param string $period day|week|month
     * @return AIVisibilityAlert|null Returns alert if sentiment change detected, null otherwise
     */
    public function checkSentimentChange(int $projectId, string $period = 'week'): ?AIVisibilityAlert;

    /**
     * Run all alert checks for a project
     * 
     * @param int $projectId
     * @param string $period day|week|month
     * @return array Array of AIVisibilityAlert models
     */
    public function runAllChecks(int $projectId, string $period = 'week'): array;
}
