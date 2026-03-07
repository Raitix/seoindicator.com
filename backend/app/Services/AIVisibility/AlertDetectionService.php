<?php

namespace App\Services\AIVisibility;

use App\Models\AIVisibilityAlert;
use App\Models\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AlertDetectionServiceInterface;
use App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface;
use App\Services\AIVisibility\Events\AIVisibilityAlertTriggered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Alert Detection Service
 * 
 * Detects significant changes in AI visibility metrics and creates alerts:
 * - Mention spikes (+20%)
 * - Mention drops (-20%)
 * - Competitor gains (+15%)
 * - Sentiment changes (±0.30)
 * 
 * Features:
 * - 24-hour deduplication window
 * - Configurable thresholds
 * - Notification dispatch via events
 * 
 * @see TASK-34-19
 * @see TEST-34-19
 */
class AlertDetectionService implements AlertDetectionServiceInterface
{
    protected array $thresholds;
    protected int $deduplicationWindow;

    public function __construct(
        protected AnalyticsAggregatorInterface $analyticsAggregator
    ) {
        $this->thresholds = config('ai-visibility.alert_thresholds', [
            'mention_spike' => 20,
            'mention_drop' => 20,
            'competitor_gain' => 15,
            'sentiment_change' => 0.30,
        ]);
        
        $this->deduplicationWindow = config('ai-visibility.deduplication_window', 86400);
    }

    /**
     * {@inheritdoc}
     */
    public function checkMentionSpike(int $projectId, string $period = 'week'): ?AIVisibilityAlert
    {
        $this->validatePeriod($period);
        
        $analytics = $this->analyticsAggregator->getAnalytics($projectId, $period);
        
        if (!$analytics || !isset($analytics['mention_rate'], $analytics['previous_mention_rate'])) {
            return null;
        }
        
        $current = (float) $analytics['mention_rate'];
        $previous = (float) $analytics['previous_mention_rate'];
        
        // Check for division by zero or null values
        if ($previous <= 0 || $current <= 0) {
            return null;
        }
        
        $changePercent = (($current - $previous) / $previous) * 100;
        $threshold = (float) ($this->thresholds['mention_spike'] ?? 20);
        
        // Only alert on positive changes exceeding threshold
        if ($changePercent < $threshold) {
            return null;
        }
        
        return $this->createOrUpdateAlert(
            $projectId,
            'mention_spike',
            $threshold,
            $previous,
            $current,
            round($changePercent, 2),
            ['period_type' => $period]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkMentionDrop(int $projectId, string $period = 'week'): ?AIVisibilityAlert
    {
        $this->validatePeriod($period);
        
        $analytics = $this->analyticsAggregator->getAnalytics($projectId, $period);
        
        if (!$analytics || !isset($analytics['mention_rate'], $analytics['previous_mention_rate'])) {
            return null;
        }
        
        $current = (float) $analytics['mention_rate'];
        $previous = (float) $analytics['previous_mention_rate'];
        
        // Check for division by zero
        if ($previous <= 0) {
            return null;
        }
        
        // 100% drop (current = 0) should still create alert
        if ($current < 0) {
            return null;
        }
        
        $changePercent = (($current - $previous) / $previous) * 100;
        $threshold = (float) ($this->thresholds['mention_drop'] ?? 20);
        
        // Only alert on negative changes exceeding threshold (more negative than -threshold)
        if ($changePercent > -$threshold) {
            return null;
        }
        
        return $this->createOrUpdateAlert(
            $projectId,
            'mention_drop',
            $threshold,
            $previous,
            $current,
            round($changePercent, 2),
            ['period_type' => $period]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkCompetitorGain(int $projectId, string $period = 'week'): ?AIVisibilityAlert
    {
        $this->validatePeriod($period);
        
        $comparison = $this->analyticsAggregator->getComparison($projectId, $period);
        
        if (!$comparison || empty($comparison['competitors'])) {
            return null;
        }
        
        $brandRate = (float) ($comparison['brand']['mention_rate'] ?? 0);
        $threshold = (float) ($this->thresholds['competitor_gain'] ?? 15);
        
        $highestGain = null;
        $highestGainPercent = 0;
        
        foreach ($comparison['competitors'] as $competitor) {
            $compRate = (float) ($competitor['mention_rate'] ?? 0);
            
            // Skip if competitor has no mentions
            if ($compRate <= 0) {
                continue;
            }
            
            // Handle brand rate of 0 (infinite gain)
            if ($brandRate <= 0) {
                $gainPercent = PHP_FLOAT_MAX;
            } else {
                $gainPercent = (($compRate - $brandRate) / $brandRate) * 100;
            }
            
            // Only consider gains exceeding threshold
            if ($gainPercent >= $threshold && $gainPercent > $highestGainPercent) {
                $highestGain = $competitor;
                $highestGainPercent = $gainPercent;
            }
        }
        
        if (!$highestGain) {
            return null;
        }
        
        return $this->createOrUpdateAlert(
            $projectId,
            'competitor_gain',
            $threshold,
            $brandRate,
            (float) ($highestGain['mention_rate'] ?? 0),
            round($highestGainPercent, 2),
            [
                'period_type' => $period,
                'competitor_name' => $highestGain['name'] ?? 'Unknown',
                'brand_rate' => $brandRate,
                'competitor_rate' => $highestGain['mention_rate'] ?? 0,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkSentimentChange(int $projectId, string $period = 'week'): ?AIVisibilityAlert
    {
        $this->validatePeriod($period);
        
        $sentiment = $this->analyticsAggregator->getSentimentBreakdown($projectId, $period);
        
        if (!$sentiment || !isset($sentiment['average_sentiment'], $sentiment['previous_average_sentiment'])) {
            return null;
        }
        
        $current = $sentiment['average_sentiment'];
        $previous = $sentiment['previous_average_sentiment'];
        
        if ($current === null || $previous === null) {
            return null;
        }
        
        $change = abs($current - $previous);
        $threshold = (float) ($this->thresholds['sentiment_change'] ?? 0.30);
        
        if ($change < $threshold) {
            return null;
        }
        
        $direction = $current > $previous ? 'up' : 'down';
        
        return $this->createOrUpdateAlert(
            $projectId,
            'sentiment_change',
            $threshold,
            (float) $previous,
            (float) $current,
            round($change, 2),
            [
                'period_type' => $period,
                'direction' => $direction,
                'previous_breakdown' => $sentiment['previous_breakdown'] ?? null,
                'current_breakdown' => $sentiment['breakdown'] ?? null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function runAllChecks(int $projectId, string $period = 'week'): array
    {
        $this->validatePeriod($period);
        
        $alerts = [];
        
        // Run all checks
        $checks = [
            $this->checkMentionSpike($projectId, $period),
            $this->checkMentionDrop($projectId, $period),
            $this->checkCompetitorGain($projectId, $period),
            $this->checkSentimentChange($projectId, $period),
        ];
        
        // Filter out null values and collect alerts
        foreach ($checks as $alert) {
            if ($alert !== null) {
                $alerts[] = $alert;
            }
        }
        
        Log::info('Alert detection completed', [
            'project_id' => $projectId,
            'period' => $period,
            'alerts_count' => count($alerts),
        ]);
        
        return $alerts;
    }

    /**
     * Create or update an alert with deduplication
     * 
     * @param int $projectId
     * @param string $alertType
     * @param float $threshold
     * @param float $previousValue
     * @param float $currentValue
     * @param float $changePercent
     * @param array $metadata
     * @return AIVisibilityAlert
     */
    protected function createOrUpdateAlert(
        int $projectId,
        string $alertType,
        float $threshold,
        float $previousValue,
        float $currentValue,
        float $changePercent,
        array $metadata = []
    ): AIVisibilityAlert {
        $isNew = false;
        
        // Check for existing unresolved alert within deduplication window
        $existing = AIVisibilityAlert::where('project_id', $projectId)
            ->where('alert_type', $alertType)
            ->where('is_resolved', false)
            ->where('triggered_at', '>=', now()->subSeconds($this->deduplicationWindow))
            ->first();
        
        if ($existing) {
            // Update existing alert
            $existing->update([
                'threshold' => $threshold,
                'previous_value' => $previousValue,
                'current_value' => $currentValue,
                'change_percent' => $changePercent,
                'metadata' => $metadata,
                'triggered_at' => now(),
            ]);
            
            Log::info('Alert updated (deduplication)', [
                'alert_id' => $existing->id,
                'project_id' => $projectId,
                'alert_type' => $alertType,
                'change_percent' => $changePercent,
            ]);
            
            return $existing;
        }
        
        // Create new alert
        $alert = DB::transaction(function () use (
            $projectId,
            $alertType,
            $threshold,
            $previousValue,
            $currentValue,
            $changePercent,
            $metadata
        ) {
            return AIVisibilityAlert::create([
                'project_id' => $projectId,
                'alert_type' => $alertType,
                'threshold' => $threshold,
                'previous_value' => $previousValue,
                'current_value' => $currentValue,
                'change_percent' => $changePercent,
                'triggered_at' => now(),
                'is_read' => false,
                'is_resolved' => false,
                'metadata' => $metadata,
            ]);
        });
        
        Log::info('Alert created', [
            'alert_id' => $alert->id,
            'project_id' => $projectId,
            'alert_type' => $alertType,
            'change_percent' => $changePercent,
        ]);
        
        // Dispatch event for new alert only
        $project = AIVisibilityProject::find($projectId);
        if ($project) {
            Event::dispatch(new AIVisibilityAlertTriggered($alert, $project));
        }
        
        return $alert;
    }

    /**
     * Validate period parameter
     * 
     * @param string $period
     * @throws \InvalidArgumentException
     */
    protected function validatePeriod(string $period): void
    {
        $validPeriods = ['day', 'week', 'month'];
        
        if (!in_array($period, $validPeriods)) {
            throw new \InvalidArgumentException(
                "Invalid period '{$period}'. Must be one of: " . implode(', ', $validPeriods)
            );
        }
    }
}
