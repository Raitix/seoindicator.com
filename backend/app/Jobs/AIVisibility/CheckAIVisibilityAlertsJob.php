<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to check for AI visibility alerts.
 *
 * Compares current analytics with previous values and creates alerts for threshold breaches.
 *
 * @see TASK-34-7
 * @see SPEC-034
 */
class CheckAIVisibilityAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('ai-visibility');
    }

    /**
     * Execute the job.
     *
     * @param AIVisibilityServiceInterface $service
     * @return void
     */
    public function handle(AIVisibilityServiceInterface $service): void
    {
        try {
            // Get latest analytics (includes previous values for comparison)
            $analytics = $service->getLatestAnalytics();

            // Handle both Collection and array returns from mocks
            if (is_array($analytics)) {
                $analytics = collect($analytics);
            }

            if ($analytics->isEmpty()) {
                Log::info('No analytics data to check for alerts');
                return;
            }

            // Get alert thresholds
            $thresholds = $service->getAlertThresholds();

            $alertsCreated = 0;

            foreach ($analytics as $record) {
                // Normalize record to array for consistent access
                $recordData = $this->normalizeRecord($record);

                // Previous values are included in the analytics data (previous_* fields)
                // No need to query the database separately

                // Check for mention rate drop
                try {
                    $alert = $this->checkMentionRateDrop($recordData, $thresholds, $service);
                    if ($alert) {
                        $alertsCreated++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create mention rate drop alert', [
                        'project_id' => $recordData['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Check for share of voice drop
                try {
                    $alert = $this->checkShareOfVoiceDrop($recordData, $thresholds, $service);
                    if ($alert) {
                        $alertsCreated++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create share of voice drop alert', [
                        'project_id' => $recordData['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Check for sentiment change
                try {
                    $alert = $this->checkSentimentChange($recordData, $thresholds, $service);
                    if ($alert) {
                        $alertsCreated++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create sentiment change alert', [
                        'project_id' => $recordData['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('CheckAIVisibilityAlertsJob failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Normalize record to array for consistent access.
     *
     * @param mixed $record
     * @return array
     */
    protected function normalizeRecord(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        }

        if ($record instanceof \Illuminate\Support\Collection) {
            return $record->toArray();
        }

        if (is_object($record)) {
            return (array) $record;
        }

        return [];
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAIVisibilityAlertsJob failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new \Illuminate\Queue\Middleware\WithoutOverlapping('check-ai-visibility-alerts', 1800),
        ];
    }

    /**
     * Check for mention rate drop alert.
     *
     * @param array $current Current analytics data (includes previous_* values)
     * @param array $thresholds Alert thresholds
     * @param AIVisibilityServiceInterface $service
     * @return array|null
     */
    protected function checkMentionRateDrop(
        array $current,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?array {
        $currentRate = (float) ($current['mention_rate'] ?? 0);
        $previousRate = (float) ($current['previous_mention_rate'] ?? null);

        // No previous data to compare
        if ($previousRate === null || $previousRate === 0.0) {
            return null;
        }

        // No drop (improvement or same)
        if ($currentRate >= $previousRate) {
            return null;
        }

        $drop = $previousRate - $currentRate;
        $dropPercent = $previousRate > 0
            ? ($drop / $previousRate)
            : 0;

        $thresholdValue = $thresholds['mention_rate_drop'] ?? 0.1;

        if ($dropPercent < $thresholdValue) {
            return null; // Below threshold
        }

        // Determine severity
        $criticalThreshold = $thresholds['mention_rate_critical'] ?? 0.3;
        $severity = $dropPercent >= $criticalThreshold ? 'critical' : 'warning';

        return $service->createAlert([
            'project_id' => $current['project_id'],
            'alert_type' => 'mention_rate_drop',
            'threshold' => $thresholdValue * 100,
            'previous_value' => $previousRate,
            'current_value' => $currentRate,
            'change_percent' => $dropPercent * 100,
            'severity' => $severity,
        ]);
    }

    /**
     * Check for share of voice drop alert.
     *
     * @param array $current Current analytics data (includes previous_* values)
     * @param array $thresholds Alert thresholds
     * @param AIVisibilityServiceInterface $service
     * @return array|null
     */
    protected function checkShareOfVoiceDrop(
        array $current,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?array {
        // Use pre-calculated share_of_voice if available, otherwise calculate
        $currentSov = isset($current['share_of_voice'])
            ? $this->normalizeToPercentage($current['share_of_voice'])
            : $this->calculateShareOfVoice($current);
        $previousSov = $this->getPreviousShareOfVoice($current);

        // No previous data
        if ($previousSov === null) {
            return null;
        }

        if ($currentSov >= $previousSov) {
            return null; // No drop
        }

        $drop = $previousSov - $currentSov;
        $thresholdValue = $thresholds['share_of_voice_drop'] ?? 0.05;

        // Threshold is a decimal (0.1 = 10%), convert to percentage points for comparison
        if ($drop < $thresholdValue * 100) {
            return null; // Below threshold
        }

        return $service->createAlert([
            'project_id' => $current['project_id'],
            'alert_type' => 'share_of_voice_drop',
            'threshold' => $thresholdValue * 100,
            'previous_value' => $previousSov,
            'current_value' => $currentSov,
            'change_percent' => $drop,
        ]);
    }

    /**
     * Normalize a value to percentage (0-100 scale).
     * Handles both decimal (0.1) and percentage (10) inputs.
     *
     * @param mixed $value
     * @return float
     */
    protected function normalizeToPercentage(mixed $value): float
    {
        $floatValue = (float) $value;
        // If value is <= 1, assume it's a decimal and convert to percentage
        if ($floatValue <= 1 && $floatValue >= 0) {
            return $floatValue * 100;
        }
        return $floatValue;
    }

    /**
     * Check for sentiment change alert.
     *
     * @param array $current Current analytics data (includes previous_* values)
     * @param array $thresholds Alert thresholds
     * @param AIVisibilityServiceInterface $service
     * @return array|null
     */
    protected function checkSentimentChange(
        array $current,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?array {
        // Use pre-calculated sentiment_score if available, otherwise calculate
        $currentScore = isset($current['sentiment_score'])
            ? (float) $current['sentiment_score']
            : $this->calculateSentimentScore($current);
        $previousScore = $this->getPreviousSentimentScore($current);

        if ($currentScore === null || $previousScore === null) {
            return null;
        }

        if ($currentScore >= $previousScore) {
            return null; // No degradation
        }

        $change = abs($currentScore - $previousScore);
        $thresholdValue = $thresholds['sentiment_change'] ?? 0.1;

        if ($change < $thresholdValue) {
            return null; // Below threshold
        }

        return $service->createAlert([
            'project_id' => $current['project_id'],
            'alert_type' => 'sentiment_change',
            'threshold' => $thresholdValue * 100,
            'previous_value' => $previousScore,
            'current_value' => $currentScore,
            'change_percent' => $change * 100,
        ]);
    }

    /**
     * Calculate share of voice from analytics.
     *
     * @param array $analytics
     * @return float
     */
    protected function calculateShareOfVoice(array $analytics): float
    {
        $brandMentions = (float) ($analytics['brand_mentions'] ?? 0);
        $competitorStats = $analytics['competitor_stats'] ?? [];
        $competitorSum = is_array($competitorStats) ? array_sum($competitorStats) : 0;
        $totalMentions = $brandMentions + $competitorSum;

        if ($totalMentions == 0) {
            return 0.0;
        }

        return round(($brandMentions / $totalMentions) * 100, 2);
    }

    /**
     * Calculate previous share of voice from analytics.
     *
     * @param array $analytics
     * @return float|null
     */
    protected function calculatePreviousShareOfVoice(array $analytics): ?float
    {
        $brandMentions = (float) ($analytics['previous_brand_mentions'] ?? 0);
        $competitorStats = $analytics['previous_competitor_stats'] ?? null;

        // If no previous data, return null
        if (!isset($analytics['previous_brand_mentions'])) {
            return null;
        }

        $competitorSum = is_array($competitorStats) ? array_sum($competitorStats) : 0;
        $totalMentions = $brandMentions + $competitorSum;

        if ($totalMentions == 0) {
            return 0.0;
        }

        return round(($brandMentions / $totalMentions) * 100, 2);
    }

    /**
     * Get previous share of voice - either from pre-calculated value or by calculation.
     *
     * @param array $analytics
     * @return float|null
     */
    protected function getPreviousShareOfVoice(array $analytics): ?float
    {
        // Use pre-calculated value if available
        if (isset($analytics['previous_share_of_voice'])) {
            return $this->normalizeToPercentage($analytics['previous_share_of_voice']);
        }

        // Otherwise calculate from raw data
        return $this->calculatePreviousShareOfVoice($analytics);
    }

    /**
     * Calculate sentiment score from analytics.
     *
     * @param array $analytics
     * @return float|null
     */
    protected function calculateSentimentScore(array $analytics): ?float
    {
        $positive = (int) ($analytics['positive_mentions'] ?? 0);
        $neutral = (int) ($analytics['neutral_mentions'] ?? 0);
        $negative = (int) ($analytics['negative_mentions'] ?? 0);

        $total = $positive + $neutral + $negative;

        if ($total === 0) {
            return null;
        }

        return round(($positive - $negative) / $total, 2);
    }

    /**
     * Calculate previous sentiment score from analytics.
     *
     * @param array $analytics
     * @return float|null
     */
    protected function calculatePreviousSentimentScore(array $analytics): ?float
    {
        $positive = (int) ($analytics['previous_positive_mentions'] ?? 0);
        $neutral = (int) ($analytics['previous_neutral_mentions'] ?? 0);
        $negative = (int) ($analytics['previous_negative_mentions'] ?? 0);

        // If no previous data, return null
        if (!isset($analytics['previous_positive_mentions'])) {
            return null;
        }

        $total = $positive + $neutral + $negative;

        if ($total === 0) {
            return 0.0;
        }

        return round(($positive - $negative) / $total, 2);
    }

    /**
     * Get previous sentiment score - either from pre-calculated value or by calculation.
     *
     * @param array $analytics
     * @return float|null
     */
    protected function getPreviousSentimentScore(array $analytics): ?float
    {
        // Use pre-calculated value if available
        if (isset($analytics['previous_sentiment_score'])) {
            return (float) $analytics['previous_sentiment_score'];
        }

        // Otherwise calculate from raw data
        return $this->calculatePreviousSentimentScore($analytics);
    }
}
