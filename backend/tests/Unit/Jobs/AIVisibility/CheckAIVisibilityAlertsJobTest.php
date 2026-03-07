<?php

namespace Tests\Unit\Jobs\AIVisibility;

use App\Jobs\AIVisibility\CheckAIVisibilityAlertsJob;
use App\Services\AIVisibilityServiceInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for CheckAIVisibilityAlertsJob.
 *
 * Tests alert detection, threshold checking, scheduling, and error handling.
 *
 * @group ai-visibility-jobs
 */
class CheckAIVisibilityAlertsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    // ============================================
    // Alert Detection Tests (JCA-ALERT)
    // ============================================

    /** @test JCA-ALERT-01: Detects mention rate drop */
    public function detects_mention_rate_drop(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.3,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1, // 10% threshold
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['alert_type'] === 'mention_rate_drop'
                    && $alert['project_id'] === 1;
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);

        // Alert was created
        $this->assertTrue(true);
    }

    /** @test JCA-ALERT-02: Detects share of voice drop */
    public function detects_share_of_voice_drop(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'share_of_voice' => 0.15,
                    'previous_share_of_voice' => 0.30,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'share_of_voice_drop' => 0.1,
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['alert_type'] === 'share_of_voice_drop';
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-03: Detects sentiment change */
    public function detects_sentiment_change(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'sentiment_score' => 0.3,
                    'previous_sentiment_score' => 0.7,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'sentiment_change' => 0.3,
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['alert_type'] === 'sentiment_change';
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-04: Respects threshold settings */
    public function respects_threshold_settings(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.45,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.2, // 20% threshold - this 10% drop should NOT alert
            ]);

        // Should NOT create alert (drop is only 10%, threshold is 20%)
        $mockService->shouldNotReceive('createAlert');

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-05: Creates alert record */
    public function creates_alert_record(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['project_id'] === 1
                    && $alert['alert_type'] === 'mention_rate_drop'
                    && isset($alert['threshold'])
                    && isset($alert['previous_value'])
                    && isset($alert['current_value']);
            })
            ->andReturn([
                'id' => 1,
                'project_id' => 1,
                'alert_type' => 'mention_rate_drop',
                'severity' => 'warning',
                'message' => 'Mention rate dropped by 60%',
            ]);

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);

        // Alert was created (verified by mock expectation)
        $this->assertTrue(true);
    }

    /** @test JCA-ALERT-06: Links alert to project */
    public function links_alert_to_project(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 42,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['project_id'] === 42;
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-07: Skips if no previous data */
    public function skips_if_no_previous_data(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.5,
                    'previous_mention_rate' => null, // No previous data
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        // Should NOT create alert without baseline
        $mockService->shouldNotReceive('createAlert');

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-08: Handles multiple metrics with multiple alerts */
    public function handles_multiple_metrics_with_multiple_alerts(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                    'share_of_voice' => 0.1,
                    'previous_share_of_voice' => 0.3,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
                'share_of_voice_drop' => 0.1,
            ]);

        // Should create 2 alerts (one for each metric breach)
        $mockService->shouldReceive('createAlert')
            ->twice();

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-09: No alert when metrics improve */
    public function no_alert_when_metrics_improve(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.6,
                    'previous_mention_rate' => 0.5, // Improved!
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        // Should NOT create alert when metrics improve
        $mockService->shouldNotReceive('createAlert');

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ALERT-10: Alert severity based on threshold breach */
    public function alert_severity_based_on_threshold_breach(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.1,
                    'previous_mention_rate' => 0.5, // 80% drop - critical!
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
                'mention_rate_critical' => 0.5, // 50% drop = critical
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['severity'] === 'critical';
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    // ============================================
    // Scheduling Tests (JCA-SCHED)
    // ============================================

    /** @test JCA-SCHED-01: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new CheckAIVisibilityAlertsJob();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test JCA-SCHED-02: Job has correct queue name */
    public function job_has_correct_queue_name(): void
    {
        $job = new CheckAIVisibilityAlertsJob();

        $this->assertEquals('ai-visibility', $job->queue);
    }

    /** @test JCA-SCHED-03: Job has single try */
    public function job_has_single_try(): void
    {
        $job = new CheckAIVisibilityAlertsJob();

        $this->assertEquals(1, $job->tries);
    }

    // ============================================
    // Error Handling Tests (JCA-ERR)
    // ============================================

    /** @test JCA-ERR-01: Handles no analytics data */
    public function handles_no_analytics_data(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([]);

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);

        // Job completes without errors
        $this->assertTrue(true);
    }

    /** @test JCA-ERR-02: Handles database error */
    public function handles_database_error(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->andThrow(new \Exception('Database connection failed'));

        Log::shouldReceive('error')
            ->once();

        $this->expectException(\Exception::class);

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test JCA-ERR-03: Failed job is logged */
    public function failed_job_is_logged(): void
    {
        $job = new CheckAIVisibilityAlertsJob();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'CheckAIVisibilityAlertsJob failed');
            });

        $job->failed(new \Exception('Test failure'));
    }

    /** @test JCA-ERR-04: Continues after single alert creation failure */
    public function continues_after_single_alert_creation_failure(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
                [
                    'project_id' => 2,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        $callCount = 0;
        $mockService->shouldReceive('createAlert')
            ->twice()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    throw new \Exception('Alert creation failed');
                }
                return ['id' => 2];
            });

        Log::shouldReceive('error')->once();

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);

        // Both attempts were made
        $this->assertEquals(2, $callCount);
    }

    // ============================================
    // Edge Cases Tests
    // ============================================

    /** @test EC-01: Handles null previous values */
    public function handles_null_previous_values(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.5,
                    'previous_mention_rate' => null,
                    'share_of_voice' => 0.3,
                    'previous_share_of_voice' => null,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
                'share_of_voice_drop' => 0.1,
            ]);

        // Should NOT create alerts without baseline
        $mockService->shouldNotReceive('createAlert');

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test EC-02: Handles zero values correctly */
    public function handles_zero_values_correctly(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0,
                    'previous_mention_rate' => 0.5, // 100% drop
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        $mockService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($alert) {
                return $alert['severity'] === 'critical'; // 100% drop is critical
            });

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test EC-03: Handles multiple platforms per project */
    public function handles_multiple_platforms_per_project(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn([
                [
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
                [
                    'project_id' => 1,
                    'platform' => 'perplexity',
                    'mention_rate' => 0.2,
                    'previous_mention_rate' => 0.5,
                ],
            ]);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        // Should create 2 alerts (one per platform)
        $mockService->shouldReceive('createAlert')
            ->twice();

        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
    }

    /** @test SEC-01: No sensitive data in error logs */
    public function no_sensitive_data_in_error_logs(): void
    {
        $job = new CheckAIVisibilityAlertsJob();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                $contextString = json_encode($context);
                return !str_contains($contextString, 'api_key')
                    && !str_contains($contextString, 'token')
                    && !str_contains($contextString, 'password');
            });

        $job->failed(new \Exception('Test error'));
    }

    /** @test PERF-01: Job handles large dataset efficiently */
    public function job_handles_large_dataset_efficiently(): void
    {
        // Generate 100 analytics records
        $analytics = [];
        for ($i = 1; $i <= 100; $i++) {
            $analytics[] = [
                'project_id' => $i,
                'platform' => 'chatgpt',
                'mention_rate' => 0.2,
                'previous_mention_rate' => 0.5,
            ];
        }

        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getLatestAnalytics')
            ->once()
            ->andReturn($analytics);

        $mockService->shouldReceive('getAlertThresholds')
            ->once()
            ->andReturn([
                'mention_rate_drop' => 0.1,
            ]);

        $mockService->shouldReceive('createAlert')
            ->times(100);

        $startTime = microtime(true);
        $job = new CheckAIVisibilityAlertsJob();
        $job->handle($mockService);
        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(2, $executionTime, 'Job should complete in under 2 seconds');
    }
}
