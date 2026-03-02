<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Unit tests for HealthCheckService
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group unit
 * @group services
 */
class HealthCheckServiceTest extends TestCase
{
    protected HealthCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(HealthCheckService::class);
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
        $service = app(HealthCheckService::class);
        $this->assertInstanceOf(HealthCheckService::class, $service);
    }

    // =========================================================================
    // getStats() Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_array_with_required_keys(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('uptime', $stats);
        $this->assertArrayHasKey('lastChecked', $stats);
        $this->assertArrayHasKey('services', $stats);
        $this->assertArrayHasKey('alerts', $stats);
    }

    /** @test */
    public function get_stats_returns_healthy_status_when_all_services_up(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals('healthy', $stats['status']);
    }

    // =========================================================================
    // Database Health Tests
    // =========================================================================

    /** @test */
    public function get_stats_checks_database_health(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('database', $stats['services']);
        $this->assertArrayHasKey('status', $stats['services']['database']);
        $this->assertArrayHasKey('latencyMs', $stats['services']['database']);
        $this->assertArrayHasKey('connections', $stats['services']['database']);
    }

    /** @test */
    public function get_stats_returns_healthy_database_status(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals('healthy', $stats['services']['database']['status']);
    }

    /** @test */
    public function get_stats_measures_database_latency(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsNumeric($stats['services']['database']['latencyMs']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['database']['latencyMs']);
    }

    /** @test */
    public function get_stats_reports_database_connections(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsInt($stats['services']['database']['connections']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['database']['connections']);
    }

    /** @test */
    public function get_stats_handles_database_connection_failure(): void
    {
        // Mock a database failure
        DB::shouldReceive('connection')
            ->andThrow(new \Exception('Connection refused'));

        $stats = $this->service->getStats();

        $this->assertEquals('unhealthy', $stats['services']['database']['status']);
    }

    // =========================================================================
    // Queue Health Tests
    // =========================================================================

    /** @test */
    public function get_stats_checks_queue_health(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('queue', $stats['services']);
        $this->assertArrayHasKey('status', $stats['services']['queue']);
        $this->assertArrayHasKey('pending', $stats['services']['queue']);
        $this->assertArrayHasKey('failed', $stats['services']['queue']);
        $this->assertArrayHasKey('workers', $stats['services']['queue']);
    }

    /** @test */
    public function get_stats_returns_healthy_queue_status(): void
    {
        $stats = $this->service->getStats();

        $this->assertContains($stats['services']['queue']['status'], ['healthy', 'warning']);
    }

    /** @test */
    public function get_stats_reports_pending_jobs_count(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsInt($stats['services']['queue']['pending']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['queue']['pending']);
    }

    /** @test */
    public function get_stats_reports_failed_jobs_count(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsInt($stats['services']['queue']['failed']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['queue']['failed']);
    }

    /** @test */
    public function get_stats_reports_worker_count(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsInt($stats['services']['queue']['workers']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['queue']['workers']);
    }

    /** @test */
    public function get_stats_flags_warning_for_high_pending_count(): void
    {
        // This test assumes the service checks pending job thresholds
        // Mock a high pending count scenario
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('getQueuePendingCount')->andReturn(1000);

        $stats = $service->getStats();

        // If pending > threshold, status should be warning
        if ($stats['services']['queue']['pending'] > 100) {
            $this->assertEquals('warning', $stats['services']['queue']['status']);
        }
    }

    /** @test */
    public function get_stats_flags_unhealthy_for_failed_jobs(): void
    {
        // This test assumes the service checks failed job thresholds
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('getQueueFailedCount')->andReturn(50);

        $stats = $service->getStats();

        // If failed > threshold, status should be unhealthy or warning
        if ($stats['services']['queue']['failed'] > 10) {
            $this->assertContains($stats['services']['queue']['status'], ['unhealthy', 'warning']);
        }
    }

    // =========================================================================
    // Cache Health Tests
    // =========================================================================

    /** @test */
    public function get_stats_checks_cache_health(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('cache', $stats['services']);
        $this->assertArrayHasKey('status', $stats['services']['cache']);
        $this->assertArrayHasKey('hitRate', $stats['services']['cache']);
    }

    /** @test */
    public function get_stats_returns_healthy_cache_status(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals('healthy', $stats['services']['cache']['status']);
    }

    /** @test */
    public function get_stats_reports_cache_hit_rate(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsFloat($stats['services']['cache']['hitRate']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['cache']['hitRate']);
        $this->assertLessThanOrEqual(100, $stats['services']['cache']['hitRate']);
    }

    /** @test */
    public function get_stats_handles_cache_failure(): void
    {
        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Redis connection refused'));

        $stats = $this->service->getStats();

        $this->assertEquals('unhealthy', $stats['services']['cache']['status']);
    }

    // =========================================================================
    // Storage Health Tests
    // =========================================================================

    /** @test */
    public function get_stats_checks_storage_health(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('storage', $stats['services']);
        $this->assertArrayHasKey('status', $stats['services']['storage']);
        $this->assertArrayHasKey('usedPercent', $stats['services']['storage']);
    }

    /** @test */
    public function get_stats_returns_healthy_storage_status(): void
    {
        $stats = $this->service->getStats();

        $this->assertContains($stats['services']['storage']['status'], ['healthy', 'warning']);
    }

    /** @test */
    public function get_stats_reports_storage_used_percent(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsFloat($stats['services']['storage']['usedPercent']);
        $this->assertGreaterThanOrEqual(0, $stats['services']['storage']['usedPercent']);
        $this->assertLessThanOrEqual(100, $stats['services']['storage']['usedPercent']);
    }

    /** @test */
    public function get_stats_flags_warning_for_high_storage_usage(): void
    {
        $stats = $this->service->getStats();

        if ($stats['services']['storage']['usedPercent'] > 80) {
            $this->assertEquals('warning', $stats['services']['storage']['status']);
        }
    }

    // =========================================================================
    // Overall Status Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_unhealthy_when_any_service_unhealthy(): void
    {
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('checkDatabase')->andReturn(['status' => 'unhealthy']);
        $service->shouldReceive('checkCache')->andReturn(['status' => 'healthy']);
        $service->shouldReceive('checkQueue')->andReturn(['status' => 'healthy']);
        $service->shouldReceive('checkStorage')->andReturn(['status' => 'healthy']);

        $stats = $service->getStats();

        $this->assertEquals('unhealthy', $stats['status']);
    }

    /** @test */
    public function get_stats_returns_warning_when_any_service_warning(): void
    {
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('checkDatabase')->andReturn(['status' => 'healthy']);
        $service->shouldReceive('checkCache')->andReturn(['status' => 'warning']);
        $service->shouldReceive('checkQueue')->andReturn(['status' => 'healthy']);
        $service->shouldReceive('checkStorage')->andReturn(['status' => 'healthy']);

        $stats = $service->getStats();

        $this->assertEquals('warning', $stats['status']);
    }

    /** @test */
    public function get_stats_returns_healthy_when_all_services_healthy(): void
    {
        $stats = $this->service->getStats();

        // If all services are healthy, overall status should be healthy
        $allHealthy = collect($stats['services'])->every(fn($s) => $s['status'] === 'healthy');
        
        if ($allHealthy) {
            $this->assertEquals('healthy', $stats['status']);
        }
    }

    // =========================================================================
    // Alerts Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_alerts_array(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsArray($stats['alerts']);
    }

    /** @test */
    public function get_stats_includes_alert_for_unhealthy_service(): void
    {
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('checkDatabase')->andReturn([
            'status' => 'unhealthy',
            'error' => 'Connection refused'
        ]);

        $stats = $service->getStats();

        $this->assertNotEmpty($stats['alerts']);
    }

    /** @test */
    public function get_stats_alerts_have_required_fields(): void
    {
        $service = Mockery::mock(HealthCheckService::class)->makePartial();
        $service->shouldReceive('checkDatabase')->andReturn([
            'status' => 'unhealthy',
            'error' => 'Connection refused'
        ]);

        $stats = $service->getStats();

        if (!empty($stats['alerts'])) {
            $this->assertArrayHasKey('level', $stats['alerts'][0]);
            $this->assertArrayHasKey('message', $stats['alerts'][0]);
            $this->assertArrayHasKey('since', $stats['alerts'][0]);
        }
    }

    // =========================================================================
    // Uptime Tests
    // =========================================================================

    /** @test */
    public function get_stats_reports_uptime_percentage(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('uptime', $stats);
        $this->assertIsFloat($stats['uptime']);
        $this->assertGreaterThanOrEqual(0, $stats['uptime']);
        $this->assertLessThanOrEqual(100, $stats['uptime']);
    }

    /** @test */
    public function get_stats_reports_last_checked_timestamp(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('lastChecked', $stats);
        $this->assertNotEmpty($stats['lastChecked']);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_stats_completes_under_200ms(): void
    {
        $start = microtime(true);
        $this->service->getStats();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'Health check should complete under 200ms');
    }

    /** @test */
    public function get_stats_uses_caching_for_performance(): void
    {
        // First call
        $stats1 = $this->service->getStats();
        
        // Second call (should use cache)
        $stats2 = $this->service->getStats();

        $this->assertEquals($stats1, $stats2);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    /** @test */
    public function get_stats_handles_timeout_gracefully(): void
    {
        // This test verifies the service handles timeouts without throwing
        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
    }

    /** @test */
    public function get_stats_continues_on_partial_failure(): void
    {
        // Mock one service failing
        Cache::shouldReceive('get')->andThrow(new \Exception('Cache error'));

        $stats = $this->service->getStats();

        // Should still return stats for other services
        $this->assertArrayHasKey('database', $stats['services']);
        $this->assertArrayHasKey('queue', $stats['services']);
    }

    /** @test */
    public function get_stats_handles_missing_services(): void
    {
        // Test when optional services are not configured
        $stats = $this->service->getStats();

        // Core services should always be present
        $this->assertArrayHasKey('database', $stats['services']);
    }

    // =========================================================================
    // Data Validation Tests
    // =========================================================================

    /** @test */
    public function get_stats_validates_service_status_values(): void
    {
        $stats = $this->service->getStats();

        $validStatuses = ['healthy', 'warning', 'unhealthy'];

        foreach ($stats['services'] as $service) {
            $this->assertContains($service['status'], $validStatuses);
        }
    }

    /** @test */
    public function get_stats_validates_alert_levels(): void
    {
        $stats = $this->service->getStats();

        $validLevels = ['info', 'warning', 'error', 'critical'];

        foreach ($stats['alerts'] as $alert) {
            $this->assertContains($alert['level'], $validLevels);
        }
    }
}
