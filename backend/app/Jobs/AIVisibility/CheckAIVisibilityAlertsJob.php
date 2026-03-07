<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    use InteractsWithQueue, Queueable, SerializesModels;

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
        Log::info('CheckAIVisibilityAlertsJob starting');

        try {
            // Get latest analytics
            $analytics = $service->getLatestAnalytics();

            if ($analytics->isEmpty()) {
                Log::info('No analytics data to check for alerts');
                return;
            }

            // Get alert thresholds
            $thresholds = $service->getAlertThresholds();

            $alertsCreated = 0;

            foreach ($analytics as $record) {
                // Get previous analytics for comparison
                $previous = $this->getPreviousAnalytics($record);

                if (!$previous) {
                    continue; // No baseline to compare
                }

                // Check for mention rate drop
                $alert = $this->checkMentionRateDrop($record, $previous, $thresholds, $service);
                if ($alert) {
                    $alertsCreated++;
                }

                // Check for share of voice drop
                $alert = $this->checkShareOfVoiceDrop($record, $previous, $thresholds, $service);
                if ($alert) {
                    $alertsCreated++;
                }

                // Check for sentiment change
                $alert = $this->checkSentimentChange($record, $previous, $thresholds, $service);
                if ($alert) {
                    $alertsCreated++;
                }
            }

            Log::info('CheckAIVisibilityAlertsJob completed', [
                'alerts_created' => $alertsCreated,
            ]);
        } catch (\Exception $e) {
            Log::error('CheckAIVisibilityAlertsJob failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
     * Get previous analytics for comparison.
     *
     * @param AIMentionAnalytics $current
     * @return AIMentionAnalytics|null
     */
    protected function getPreviousAnalytics(AIMentionAnalytics $current): ?AIMentionAnalytics
    {
        return AIMentionAnalytics::query()
            ->where('project_id', $current->project_id)
            ->where('platform', $current->platform)
            ->where('period_start', '<', $current->period_start)
            ->orderByDesc('period_start')
            ->first();
    }

    /**
     * Check for mention rate drop alert.
     *
     * @param AIMentionAnalytics $current
     * @param AIMentionAnalytics $previous
     * @param array $thresholds
     * @param AIVisibilityServiceInterface $service
     * @return AIVisibilityAlert|null
     */
    protected function checkMentionRateDrop(
        AIMentionAnalytics $current,
        AIMentionAnalytics $previous,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?AIVisibilityAlert {
        if ($previous->mention_rate === null || $current->mention_rate >= $previous->mention_rate) {
            return null; // No drop
        }

        $drop = $previous->mention_rate - $current->mention_rate;
        $dropPercent = $previous->mention_rate > 0
            ? ($drop / $previous->mention_rate)
            : 0;

        if ($dropPercent < $thresholds['mention_rate_drop']) {
            return null; // Below threshold
        }

        // Determine severity
        $severity = $dropPercent >= $thresholds['mention_rate_critical'] ? 'critical' : 'warning';

        return $service->createAlert([
            'project_id' => $current->project_id,
            'alert_type' => 'mention_rate_drop',
            'threshold' => $thresholds['mention_rate_drop'] * 100,
            'previous_value' => $previous->mention_rate,
            'current_value' => $current->mention_rate,
            'change_percent' => $dropPercent * 100,
            'severity' => $severity,
        ]);
    }

    /**
     * Check for share of voice drop alert.
     *
     * @param AIMentionAnalytics $current
     * @param AIMentionAnalytics $previous
     * @param array $thresholds
     * @param AIVisibilityServiceInterface $service
     * @return AIVisibilityAlert|null
     */
    protected function checkShareOfVoiceDrop(
        AIMentionAnalytics $current,
        AIMentionAnalytics $previous,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?AIVisibilityAlert {
        // Calculate share of voice
        $currentSov = $this->calculateShareOfVoice($current);
        $previousSov = $this->calculateShareOfVoice($previous);

        if ($currentSov >= $previousSov) {
            return null; // No drop
        }

        $drop = $previousSov - $currentSov;

        if ($drop < $thresholds['share_of_voice_drop'] * 100) {
            return null; // Below threshold
        }

        return $service->createAlert([
            'project_id' => $current->project_id,
            'alert_type' => 'share_of_voice_drop',
            'threshold' => $thresholds['share_of_voice_drop'] * 100,
            'previous_value' => $previousSov,
            'current_value' => $currentSov,
            'change_percent' => $drop,
        ]);
    }

    /**
     * Check for sentiment change alert.
     *
     * @param AIMentionAnalytics $current
     * @param AIMentionAnalytics $previous
     * @param array $thresholds
     * @param AIVisibilityServiceInterface $service
     * @return AIVisibilityAlert|null
     */
    protected function checkSentimentChange(
        AIMentionAnalytics $current,
        AIMentionAnalytics $previous,
        array $thresholds,
        AIVisibilityServiceInterface $service
    ): ?AIVisibilityAlert {
        $currentScore = $this->calculateSentimentScore($current);
        $previousScore = $this->calculateSentimentScore($previous);

        if ($currentScore === null || $previousScore === null) {
            return null;
        }

        if ($currentScore >= $previousScore) {
            return null; // No degradation
        }

        $change = abs($currentScore - $previousScore);

        if ($change < $thresholds['sentiment_change']) {
            return null; // Below threshold
        }

        return $service->createAlert([
            'project_id' => $current->project_id,
            'alert_type' => 'sentiment_change',
            'threshold' => $thresholds['sentiment_change'] * 100,
            'previous_value' => $previousScore,
            'current_value' => $currentScore,
            'change_percent' => $change * 100,
        ]);
    }

    /**
     * Calculate share of voice from analytics.
     *
     * @param AIMentionAnalytics $analytics
     * @return float
     */
    protected function calculateShareOfVoice(AIMentionAnalytics $analytics): float
    {
        $totalMentions = $analytics->brand_mentions
            + collect($analytics->competitor_stats ?? [])->sum();

        if ($totalMentions === 0) {
            return 0.0;
        }

        return round(($analytics->brand_mentions / $totalMentions) * 100, 2);
    }

    /**
     * Calculate sentiment score from analytics.
     *
     * @param AIMentionAnalytics $analytics
     * @return float|null
     */
    protected function calculateSentimentScore(AIMentionAnalytics $analytics): ?float
    {
        $total = $analytics->positive_mentions
            + $analytics->neutral_mentions
            + $analytics->negative_mentions;

        if ($total === 0) {
            return null;
        }

        return round(($analytics->positive_mentions - $analytics->negative_mentions) / $total, 2);
    }
}
