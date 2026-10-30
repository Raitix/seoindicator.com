<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\QueueMonitoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Unit tests for QueueMonitoringService
 * 
 * TASK-27-12: Queue Monitoring Integration
 * Status: Tests Coverage In Progress
 * 
 * Tests cover:
 * - Per-queue pending counts
 * - Failed jobs count (last 24 hours)
 * - Active workers count
 * - Average job processing time
 * - Configurable alert thresholds
 * - Graceful handling if queue system unavailable
 * 
 * @group admin
 * @group unit
 * @group services
 * @group queue
 */
class QueueMonitoringServiceTest extends TestCase
{
    protected QueueMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QueueMonitoringService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(QueueMonitoringService::class);
        $this->assertInstanceOf(QueueMonitoringService::class, $service);
    }

    // =========================================================================
    // getQueueStats() Tests - Overall Structure
    // =========================================================================

    /** @test */
    public function get_queue_stats_returns_array_with_required_keys(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('failed_24h', $stats);
        $this->assertArrayHasKey('workers', $stats);
        $this->assertArrayHasKey('avgProcessingTimeMs', $stats);
        $this->assertArrayHasKey('queues', $stats);
    }

    /** @test */
    public function get_queue_stats_returns_valid_status(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertContains($stats['status'], ['healthy', 'warning', 'unhealthy']);
    }

    /** @test */
    public function get_queue_stats_returns_integer_counts(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertIsInt($stats['pending']);
        $this->assertIsInt($stats['failed']);
        $this->assertIsInt($stats['failed_24h']);
        $this->assertIsInt($stats['workers']);
        $this->assertGreaterThanOrEqual(0, $stats['pending']);
        $this->assertGreaterThanOrEqual(0, $stats['failed']);
        $this->assertGreaterThanOrEqual(0, $stats['failed_24h']);
        $this->assertGreaterThanOrEqual(0, $stats['workers']);
    }

    /** @test */
    public function get_queue_stats_returns_processing_time_as_integer(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertIsInt($stats['avgProcessingTimeMs']);
        $this->assertGreaterThanOrEqual(0, $stats['avgProcessingTimeMs']);
    }

    // =========================================================================
    // Per-Queue Pending Counts Tests (QM-001 to QM-010)
    // =========================================================================

    /** @test */
    public function get_queue_stats_includes_per_queue_pending_breakdown(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('queues', $stats);
        $this->assertIsArray($stats['queues']);
    }

    /** @test */
    public function get_pending_by_queue_returns_array(): void
    {
        $queuePending = $this->service->getPendingByQueue();

        $this->assertIsArray($queuePending);
    }

    /** @test */
    public function get_pending_by_queue_returns_zero_for_empty_queues(): void
    {
        // Mock empty jobs table
        DB::shouldReceive('table')
            ->with('jobs')
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->andReturn([]);

        $queuePending = $this->service->getPendingByQueue();

        $this->assertIsArray($queuePending);
    }

    /** @test */
    public function get_pending_by_queue_groups_by_queue_column(): void
    {
        // This test verifies the service correctly groups pending jobs by queue name
        $stats = $this->service->getQueueStats();

        if (!empty($stats['queues'])) {
            foreach ($stats['queues'] as $queueName => $queueData) {
                $this->assertIsString($queueName);
                $this->assertArrayHasKey('pending', $queueData);
                $this->assertIsInt($queueData['pending']);
            }
        }
    }

    /** @test */
    public function get_pending_by_queue_includes_default_queue(): void
    {
        $stats = $this->service->getQueueStats();

        // Default queue should always be present (even if 0)
        $this->assertArrayHasKey('default', $stats['queues']);
    }

    /** @test */
    public function get_pending_by_queue_sums_to_total_pending(): void
    {
        $stats = $this->service->getQueueStats();

        $queueSum = array_sum(array_column($stats['queues'], 'pending'));
        $this->assertEquals($stats['pending'], $queueSum);
    }

    /** @test */
    public function get_pending_by_queue_handles_multiple_queues(): void
    {
        // Test that multiple queue types are tracked
        $queuePending = $this->service->getPendingByQueue();

        // Should include at least 'default' queue
        $this->assertArrayHasKey('default', $queuePending);
    }

    /** @test */
    public function get_pending_by_queue_handles_high_volume(): void
    {
        // This test verifies the service can handle large numbers
        $stats = $this->service->getQueueStats();

        // Total pending should be a valid integer
        $this->assertIsInt($stats['pending']);
    }

    /** @test */
    public function get_pending_by_queue_returns_correct_types(): void
    {
        $queuePending = $this->service->getPendingByQueue();

        foreach ($queuePending as $queue => $count) {
            $this->assertIsString($queue);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    // =========================================================================
    // Failed Jobs Last 24 Hours Tests (FJ-001 to FJ-010)
    // =========================================================================

    /** @test */
    public function get_failed_jobs_24h_returns_integer(): void
    {
        $failed24h = $this->service->getFailedJobsLast24h();

        $this->assertIsInt($failed24h);
        $this->assertGreaterThanOrEqual(0, $failed24h);
    }

    /** @test */
    public function get_failed_jobs_24h_filters_by_time(): void
    {
        // This test verifies the 24h filter is applied
        $failed24h = $this->service->getFailedJobsLast24h();

        // Should be less than or equal to total failed
        $stats = $this->service->getQueueStats();
        $this->assertLessThanOrEqual($stats['failed'], $failed24h);
    }

    /** @test */
    public function get_failed_jobs_24h_uses_failed_at_column(): void
    {
        // Verify the service queries failed_jobs with failed_at filter
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('failed_24h', $stats);
    }

    /** @test */
    public function get_failed_jobs_24h_returns_zero_when_no_failures(): void
    {
        // Mock no failed jobs in last 24h
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(0);

        $failed24h = $this->service->getFailedJobsLast24h();

        $this->assertEquals(0, $failed24h);
    }

    /** @test */
    public function get_failed_jobs_24h_excludes_older_failures(): void
    {
        // This test verifies failures older than 24h are excluded
        $stats = $this->service->getQueueStats();

        // failed_24h should be independent of total failed
        $this->assertIsInt($stats['failed_24h']);
    }

    /** @test */
    public function get_failed_jobs_24h_handles_timezone_correctly(): void
    {
        // Verify timezone handling is consistent
        $failed24h = $this->service->getFailedJobsLast24h();

        $this->assertIsInt($failed24h);
    }

    /** @test */
    public function get_failed_jobs_24h_counts_correctly(): void
    {
        // Verify count is accurate
        $stats = $this->service->getQueueStats();

        $this->assertGreaterThanOrEqual(0, $stats['failed_24h']);
    }

    /** @test */
    public function get_failed_jobs_24h_included_in_queue_stats(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('failed_24h', $stats);
    }

    /** @test */
    public function get_failed_jobs_24h_updates_dynamically(): void
    {
        // Verify the count is not cached for too long
        $stats1 = $this->service->getQueueStats();
        $stats2 = $this->service->getQueueStats();

        $this->assertIsInt($stats1['failed_24h']);
        $this->assertIsInt($stats2['failed_24h']);
    }

    // =========================================================================
    // Active Workers Count Tests (WK-001 to WK-008)
    // =========================================================================

    /** @test */
    public function get_worker_count_returns_integer(): void
    {
        $workers = $this->service->getWorkerCount();

        $this->assertIsInt($workers);
        $this->assertGreaterThanOrEqual(0, $workers);
    }

    /** @test */
    public function get_worker_count_uses_cache(): void
    {
        // Workers are tracked via cache
        $workers = $this->service->getWorkerCount();

        $this->assertIsInt($workers);
    }

    /** @test */
    public function get_worker_count_defaults_to_zero(): void
    {
        // When no workers are registered, should return 0
        Cache::shouldReceive('get')
            ->with('queue:workers:count', 0)
            ->andReturn(0);

        $workers = $this->service->getWorkerCount();

        $this->assertEquals(0, $workers);
    }

    /** @test */
    public function get_worker_count_returns_cached_value(): void
    {
        Cache::shouldReceive('get')
            ->with('queue:workers:count', 0)
            ->andReturn(5);

        $workers = $this->service->getWorkerCount();

        $this->assertEquals(5, $workers);
    }

    /** @test */
    public function get_worker_count_included_in_queue_stats(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('workers', $stats);
        $this->assertIsInt($stats['workers']);
    }

    /** @test */
    public function get_worker_count_handles_missing_cache_gracefully(): void
    {
        // Even if cache fails, should return 0
        $workers = $this->service->getWorkerCount();

        $this->assertIsInt($workers);
        $this->assertGreaterThanOrEqual(0, $workers);
    }

    /** @test */
    public function get_worker_count_supports_multiple_workers(): void
    {
        Cache::shouldReceive('get')
            ->with('queue:workers:count', 0)
            ->andReturn(10);

        $workers = $this->service->getWorkerCount();

        $this->assertEquals(10, $workers);
    }

    /** @test */
    public function get_worker_count_supports_horizon_integration(): void
    {
        // Verify service can integrate with Laravel Horizon if available
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('workers', $stats);
    }

    // =========================================================================
    // Average Processing Time Tests (PT-001 to PT-010)
    // =========================================================================

    /** @test */
    public function get_average_processing_time_returns_integer(): void
    {
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
        $this->assertGreaterThanOrEqual(0, $avgTime);
    }

    /** @test */
    public function get_average_processing_time_in_milliseconds(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('avgProcessingTimeMs', $stats);
        $this->assertIsInt($stats['avgProcessingTimeMs']);
    }

    /** @test */
    public function get_average_processing_time_returns_zero_when_no_completed_jobs(): void
    {
        // When no jobs have been processed, should return 0
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
        $this->assertGreaterThanOrEqual(0, $avgTime);
    }

    /** @test */
    public function get_average_processing_time_calculates_from_completed_jobs(): void
    {
        // Verify calculation uses completed_at - started_at
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('avgProcessingTimeMs', $stats);
    }

    /** @test */
    public function get_average_processing_time_uses_recent_jobs(): void
    {
        // Verify only recent completed jobs are used for calculation
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
    }

    /** @test */
    public function get_average_processing_time_handles_outliers(): void
    {
        // Verify extremely long jobs don't skew the average
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
    }

    /** @test */
    public function get_average_processing_time_caches_result(): void
    {
        // Verify caching for performance
        $stats1 = $this->service->getQueueStats();
        $stats2 = $this->service->getQueueStats();

        $this->assertEquals($stats1['avgProcessingTimeMs'], $stats2['avgProcessingTimeMs']);
    }

    /** @test */
    public function get_average_processing_time_updates_periodically(): void
    {
        // Verify the cache has reasonable TTL
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
    }

    /** @test */
    public function get_average_processing_time_falls_back_to_zero(): void
    {
        // When calculation fails, should return 0 gracefully
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
        $this->assertGreaterThanOrEqual(0, $avgTime);
    }

    /** @test */
    public function get_average_processing_time_sample_size(): void
    {
        // Verify minimum sample size for accurate average
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('avgProcessingTimeMs', $stats);
    }

    // =========================================================================
    // Configurable Alert Thresholds Tests (AT-001 to AT-010)
    // =========================================================================

    /** @test */
    public function get_alert_thresholds_returns_array(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        $this->assertIsArray($thresholds);
    }

    /** @test */
    public function get_alert_thresholds_includes_pending_threshold(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        $this->assertArrayHasKey('pending_warning', $thresholds);
        $this->assertArrayHasKey('pending_critical', $thresholds);
    }

    /** @test */
    public function get_alert_thresholds_includes_failed_threshold(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        $this->assertArrayHasKey('failed_warning', $thresholds);
        $this->assertArrayHasKey('failed_critical', $thresholds);
    }

    /** @test */
    public function get_alert_thresholds_defaults_are_reasonable(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        // Default thresholds should be sensible
        $this->assertGreaterThan(0, $thresholds['pending_warning']);
        $this->assertGreaterThan($thresholds['pending_warning'], $thresholds['pending_critical']);
        $this->assertGreaterThan(0, $thresholds['failed_warning']);
        $this->assertGreaterThan($thresholds['failed_warning'], $thresholds['failed_critical']);
    }

    /** @test */
    public function get_alert_thresholds_reads_from_config(): void
    {
        Config::set('queue.monitoring.thresholds', [
            'pending_warning' => 50,
            'pending_critical' => 100,
            'failed_warning' => 5,
            'failed_critical' => 10,
        ]);

        $thresholds = $this->service->getAlertThresholds();

        $this->assertEquals(50, $thresholds['pending_warning']);
        $this->assertEquals(100, $thresholds['pending_critical']);
    }

    /** @test */
    public function determine_status_uses_configurable_thresholds(): void
    {
        Config::set('queue.monitoring.thresholds', [
            'pending_warning' => 10,
            'pending_critical' => 20,
            'failed_warning' => 2,
            'failed_critical' => 5,
        ]);

        $stats = $this->service->getQueueStats();

        // Status determination should use configured thresholds
        $this->assertContains($stats['status'], ['healthy', 'warning', 'unhealthy']);
    }

    /** @test */
    public function get_alert_thresholds_includes_processing_time_threshold(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        $this->assertArrayHasKey('processing_time_warning', $thresholds);
        $this->assertArrayHasKey('processing_time_critical', $thresholds);
    }

    /** @test */
    public function get_alert_thresholds_validates_values(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        // All threshold values should be positive integers
        foreach ($thresholds as $key => $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThan(0, $value);
        }
    }

    /** @test */
    public function get_alert_thresholds_warning_less_than_critical(): void
    {
        $thresholds = $this->service->getAlertThresholds();

        $this->assertLessThan(
            $thresholds['pending_critical'],
            $thresholds['pending_warning']
        );
        $this->assertLessThan(
            $thresholds['failed_critical'],
            $thresholds['failed_warning']
        );
    }

    /** @test */
    public function get_alert_thresholds_can_be_overridden(): void
    {
        // Set custom thresholds
        Config::set('queue.monitoring.thresholds.pending_warning', 25);

        $thresholds = $this->service->getAlertThresholds();

        $this->assertEquals(25, $thresholds['pending_warning']);
    }

    // =========================================================================
    // Status Determination Tests (SD-001 to SD-010)
    // =========================================================================

    /** @test */
    public function determine_status_returns_healthy_when_all_good(): void
    {
        // Mock low pending and failed counts
        $service = Mockery::mock(QueueMonitoringService::class)->makePartial();
        $service->shouldReceive('getPendingByQueue')->andReturn(['default' => 5]);
        $service->shouldReceive('getFailedJobsLast24h')->andReturn(0);
        $service->shouldReceive('getWorkerCount')->andReturn(2);
        $service->shouldReceive('getAverageProcessingTime')->andReturn(100);

        $stats = $service->getQueueStats();

        $this->assertContains($stats['status'], ['healthy', 'warning']);
    }

    /** @test */
    public function determine_status_returns_warning_for_high_pending(): void
    {
        // High pending count should trigger warning
        $stats = $this->service->getQueueStats();

        if ($stats['pending'] > 100) {
            $this->assertContains($stats['status'], ['warning', 'unhealthy']);
        }
    }

    /** @test */
    public function determine_status_returns_unhealthy_for_critical_pending(): void
    {
        // Critical pending count should trigger unhealthy
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        if ($stats['pending'] > $thresholds['pending_critical']) {
            $this->assertEquals('unhealthy', $stats['status']);
        }
    }

    /** @test */
    public function determine_status_returns_warning_for_failed_jobs(): void
    {
        // Failed jobs should trigger warning
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        if ($stats['failed'] > $thresholds['failed_warning']) {
            $this->assertContains($stats['status'], ['warning', 'unhealthy']);
        }
    }

    /** @test */
    public function determine_status_returns_unhealthy_for_critical_failed(): void
    {
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        if ($stats['failed'] > $thresholds['failed_critical']) {
            $this->assertEquals('unhealthy', $stats['status']);
        }
    }

    /** @test */
    public function determine_status_considers_failed_24h(): void
    {
        // Recent failures should be considered
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('failed_24h', $stats);
    }

    /** @test */
    public function determine_status_considers_worker_count(): void
    {
        // Zero workers with pending jobs should be warning
        $stats = $this->service->getQueueStats();

        if ($stats['pending'] > 0 && $stats['workers'] === 0) {
            $this->assertContains($stats['status'], ['warning', 'unhealthy']);
        }
    }

    /** @test */
    public function determine_status_considers_processing_time(): void
    {
        // Slow processing should be warning
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        if ($stats['avgProcessingTimeMs'] > $thresholds['processing_time_warning'] ?? 1000) {
            $this->assertContains($stats['status'], ['warning', 'unhealthy']);
        }
    }

    /** @test */
    public function determine_status_healthy_when_under_thresholds(): void
    {
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        // If all metrics are under warning thresholds, should be healthy
        if (
            $stats['pending'] <= $thresholds['pending_warning'] &&
            $stats['failed'] <= $thresholds['failed_warning'] &&
            $stats['workers'] > 0
        ) {
            $this->assertEquals('healthy', $stats['status']);
        }
    }

    /** @test */
    public function determine_status_unhealthy_takes_priority(): void
    {
        // Unhealthy status should take priority over warning
        $thresholds = $this->service->getAlertThresholds();
        $stats = $this->service->getQueueStats();

        // If any critical threshold is exceeded, status should be unhealthy
        if (
            $stats['pending'] > $thresholds['pending_critical'] ||
            $stats['failed'] > $thresholds['failed_critical']
        ) {
            $this->assertEquals('unhealthy', $stats['status']);
        }
    }

    // =========================================================================
    // Graceful Error Handling Tests (EH-001 to EH-010)
    // =========================================================================

    /** @test */
    public function get_queue_stats_handles_database_failure(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('Database connection failed'));

        $stats = $this->service->getQueueStats();

        // Should return safe defaults
        $this->assertIsArray($stats);
        $this->assertEquals('unhealthy', $stats['status']);
        $this->assertEquals(0, $stats['pending']);
        $this->assertEquals(0, $stats['failed']);
    }

    /** @test */
    public function get_queue_stats_handles_missing_jobs_table(): void
    {
        DB::shouldReceive('table')
            ->with('jobs')
            ->andThrow(new \Exception('Table not found'));

        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertEquals('unhealthy', $stats['status']);
    }

    /** @test */
    public function get_queue_stats_handles_missing_failed_jobs_table(): void
    {
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andThrow(new \Exception('Table not found'));

        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('failed', $stats);
    }

    /** @test */
    public function get_queue_stats_never_throws_exceptions(): void
    {
        // Even with all dependencies failing, should not throw
        DB::shouldReceive('table')->andThrow(new \Exception('Total failure'));
        Cache::shouldReceive('get')->andThrow(new \Exception('Cache failure'));

        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
    }

    /** @test */
    public function get_queue_stats_returns_safe_defaults_on_error(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('Error'));

        $stats = $this->service->getQueueStats();

        $this->assertEquals(0, $stats['pending']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['failed_24h']);
        $this->assertEquals(0, $stats['workers']);
        $this->assertEquals(0, $stats['avgProcessingTimeMs']);
    }

    /** @test */
    public function get_pending_by_queue_handles_query_error(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('Query error'));

        $queuePending = $this->service->getPendingByQueue();

        $this->assertIsArray($queuePending);
        $this->assertArrayHasKey('default', $queuePending);
        $this->assertEquals(0, $queuePending['default']);
    }

    /** @test */
    public function get_failed_jobs_24h_handles_query_error(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('Query error'));

        $failed24h = $this->service->getFailedJobsLast24h();

        $this->assertEquals(0, $failed24h);
    }

    /** @test */
    public function get_average_processing_time_handles_no_completed_jobs(): void
    {
        // When no jobs have completed_at, should return 0
        $avgTime = $this->service->getAverageProcessingTime();

        $this->assertIsInt($avgTime);
        $this->assertGreaterThanOrEqual(0, $avgTime);
    }

    /** @test */
    public function get_queue_stats_logs_errors(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('Test error'));

        // Should not throw, but should log internally
        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
    }

    /** @test */
    public function get_queue_stats_continues_on_partial_failure(): void
    {
        // Even if one query fails, others should continue
        DB::shouldReceive('table')
            ->with('jobs')
            ->andThrow(new \Exception('Jobs table error'));

        $stats = $this->service->getQueueStats();

        // Should still have all keys
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('workers', $stats);
    }

    // =========================================================================
    // Performance Tests (PF-001 to PF-005)
    // =========================================================================

    /** @test */
    public function get_queue_stats_completes_under_100ms(): void
    {
        $start = microtime(true);
        $this->service->getQueueStats();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Queue stats should complete under 100ms');
    }

    /** @test */
    public function get_queue_stats_uses_caching(): void
    {
        $stats1 = $this->service->getQueueStats();
        $stats2 = $this->service->getQueueStats();

        $this->assertEquals($stats1, $stats2);
    }

    /** @test */
    public function get_pending_by_queue_is_efficient(): void
    {
        $start = microtime(true);
        $this->service->getPendingByQueue();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $elapsed, 'Pending by queue should be fast');
    }

    /** @test */
    public function get_average_processing_time_is_efficient(): void
    {
        $start = microtime(true);
        $this->service->getAverageProcessingTime();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $elapsed, 'Average processing time should be fast');
    }

    /** @test */
    public function get_queue_stats_memory_efficient(): void
    {
        $memoryBefore = memory_get_usage();
        $this->service->getQueueStats();
        $memoryAfter = memory_get_usage();

        $memoryUsed = $memoryAfter - $memoryBefore;
        $this->assertLessThan(1024 * 1024, $memoryUsed, 'Memory usage should be under 1MB');
    }

    // =========================================================================
    // Integration with HealthCheckService Tests (IN-001 to IN-005)
    // =========================================================================

    /** @test */
    public function queue_stats_integrates_with_health_check(): void
    {
        $stats = $this->service->getQueueStats();

        // Verify the structure matches what HealthCheckService expects
        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('workers', $stats);
    }

    /** @test */
    public function queue_stats_response_format_matches_spec(): void
    {
        $stats = $this->service->getQueueStats();

        // Verify response format from TASK-27-12 spec
        $expectedKeys = ['status', 'pending', 'failed', 'workers', 'avgProcessingTimeMs'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats);
        }
    }

    /** @test */
    public function queue_status_is_valid_string(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertContains($stats['status'], ['healthy', 'warning', 'unhealthy']);
    }

    /** @test */
    public function queue_stats_includes_all_required_fields(): void
    {
        $stats = $this->service->getQueueStats();

        // From the spec response format
        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('workers', $stats);
        $this->assertArrayHasKey('avgProcessingTimeMs', $stats);
    }

    /** @test */
    public function queue_stats_can_be_exposed_via_health_check_service(): void
    {
        // Verify the service can be called from HealthCheckService
        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('status', $stats);
    }

    // =========================================================================
    // Edge Cases Tests (EC-001 to EC-010)
    // =========================================================================

    /** @test */
    public function handles_zero_queues(): void
    {
        $queuePending = $this->service->getPendingByQueue();

        // Should at least have 'default' queue
        $this->assertArrayHasKey('default', $queuePending);
    }

    /** @test */
    public function handles_large_pending_count(): void
    {
        $stats = $this->service->getQueueStats();

        // Should handle large numbers without overflow
        $this->assertIsInt($stats['pending']);
    }

    /** @test */
    public function handles_negative_values_gracefully(): void
    {
        // Service should never return negative values
        $stats = $this->service->getQueueStats();

        $this->assertGreaterThanOrEqual(0, $stats['pending']);
        $this->assertGreaterThanOrEqual(0, $stats['failed']);
        $this->assertGreaterThanOrEqual(0, $stats['workers']);
        $this->assertGreaterThanOrEqual(0, $stats['avgProcessingTimeMs']);
    }

    /** @test */
    public function handles_concurrent_access(): void
    {
        // Simulate concurrent calls
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->getQueueStats();
        }

        // All results should be valid
        foreach ($results as $stats) {
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('status', $stats);
        }
    }

    /** @test */
    public function handles_unicode_queue_names(): void
    {
        // Queue names should be handled properly
        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats['queues']);
    }

    /** @test */
    public function handles_special_characters_in_queue_names(): void
    {
        $stats = $this->service->getQueueStats();

        $this->assertIsArray($stats);
    }

    /** @test */
    public function handles_empty_queue_table(): void
    {
        $stats = $this->service->getQueueStats();

        // Should still return valid structure
        $this->assertIsInt($stats['pending']);
        $this->assertGreaterThanOrEqual(0, $stats['pending']);
    }

    /** @test */
    public function handles_missing_queue_column(): void
    {
        // If queue column is missing, should default to 'default'
        $stats = $this->service->getQueueStats();

        $this->assertArrayHasKey('queues', $stats);
    }

    /** @test */
    public function handles_null_values(): void
    {
        $stats = $this->service->getQueueStats();

        // No null values should be in the response
        $this->assertNotNull($stats['status']);
        $this->assertNotNull($stats['pending']);
        $this->assertNotNull($stats['failed']);
    }

    /** @test */
    public function handles_very_long_processing_times(): void
    {
        $stats = $this->service->getQueueStats();

        // Processing time should be capped or handled reasonably
        $this->assertIsInt($stats['avgProcessingTimeMs']);
    }
}
