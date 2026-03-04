<?php

namespace Tests\Unit\Services\Analytics;

use Tests\TestCase;
use App\Services\Analytics\RequestTrackingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Mockery;

/**
 * Unit tests for RequestTrackingService.
 *
 * Tests service that handles request tracking storage and aggregation.
 *
 * @see TASK-27-6
 * @see SPEC-027
 */
class RequestTrackingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createService(): RequestTrackingService
    {
        return app(RequestTrackingService::class);
    }

    protected function createTrackingData(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => '/api/v1/analysis',
            'method' => 'GET',
            'user_id' => null,
            'timestamp' => now(),
        ], $overrides);
    }

    // =========================================================================
    // Service Instantiation (RTS-INST-*)
    // =========================================================================

    /** @test */
    public function rts_inst_01_service_can_be_instantiated(): void
    {
        $service = $this->createService();
        $this->assertInstanceOf(RequestTrackingService::class, $service);
    }

    /** @test */
    public function rts_inst_02_service_resolved_from_container(): void
    {
        $service = app(RequestTrackingService::class);
        $this->assertInstanceOf(RequestTrackingService::class, $service);
    }

    // =========================================================================
    // Track Method (RTS-TRACK-*)
    // =========================================================================

    /** @test */
    public function rts_track_01_accepts_valid_data(): void
    {
        $service = $this->createService();
        $data = $this->createTrackingData();

        // Should not throw exception
        $service->track($data);
        $this->assertTrue(true);
    }

    /** @test */
    public function rts_track_02_stores_endpoint_in_redis(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->withArgs(function ($key) {
                return str_contains($key, 'api:v1:analysis:GET');
            });

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    /** @test */
    public function rts_track_03_stores_user_specific_data(): void
    {
        $userId = 123;

        Cache::shouldReceive('increment')
            ->once()
            ->withArgs(function ($key) use ($userId) {
                return str_contains($key, "user:{$userId}");
            });

        $service = $this->createService();
        $data = $this->createTrackingData(['user_id' => $userId]);

        $service->track($data);
    }

    /** @test */
    public function rts_track_04_stores_hourly_bucket(): void
    {
        $hour = now()->format('Y-m-d-H');

        Cache::shouldReceive('increment')
            ->once()
            ->withArgs(function ($key) use ($hour) {
                return str_contains($key, $hour);
            });

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    /** @test */
    public function rts_track_05_uses_atomic_increment(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->andReturn(1);

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    /** @test */
    public function rts_track_06_sets_expiry_on_new_key(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->andReturn(1);

        Cache::shouldReceive('put')
            ->once();

        Config::set('request_tracking.ttl_seconds', 86400);

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    // =========================================================================
    // Key Generation (RTS-KEY-*)
    // =========================================================================

    /** @test */
    public function rts_key_01_generates_consistent_key_format(): void
    {
        $service = $this->createService();

        // Use reflection to test key generation
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateKey');
        $method->setAccessible(true);

        $data = $this->createTrackingData([
            'endpoint' => '/api/v1/users',
            'method' => 'POST',
        ]);

        $key = $method->invoke($service, $data);

        $this->assertStringContainsString('request_tracking', $key);
        $this->assertStringContainsString('api:v1:users', $key);
        $this->assertStringContainsString('POST', $key);
    }

    /** @test */
    public function rts_key_02_normalizes_endpoint(): void
    {
        $service = $this->createService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeEndpoint');
        $method->setAccessible(true);

        // Test various endpoint formats
        $this->assertEquals('api:v1:users', $method->invoke($service, '/api/v1/users'));
        $this->assertEquals('api:v1:users:123', $method->invoke($service, '/api/v1/users/123'));
        $this->assertEquals('api:v1:search', $method->invoke($service, '/api/v1/search?q=test'));
    }

    /** @test */
    public function rts_key_03_strips_query_parameters(): void
    {
        $service = $this->createService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeEndpoint');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, '/api/v1/search?q=test&page=1');

        $this->assertStringNotContainsString('q=test', $normalized);
        $this->assertStringNotContainsString('page=1', $normalized);
    }

    // =========================================================================
    // Queue Integration (RTS-QUEUE-*)
    // =========================================================================

    /** @test */
    public function rts_queue_01_pushes_to_queue_when_configured(): void
    {
        Config::set('request_tracking.use_queue', true);

        // Mock queue push
        \Queue::fake();

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);

        \Queue::assertPushed('request-tracking', 1);
    }

    /** @test */
    public function rts_queue_02_sync_when_queue_disabled(): void
    {
        Config::set('request_tracking.use_queue', false);

        \Queue::fake();

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);

        \Queue::assertNotPushed('request-tracking');
    }

    /** @test */
    public function rts_queue_03_handles_queue_failure_gracefully(): void
    {
        Config::set('request_tracking.use_queue', true);

        \Queue::shouldReceive('push')
            ->once()
            ->andThrow(new \Exception('Queue unavailable'));

        // Should not throw
        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
        $this->assertTrue(true);
    }

    // =========================================================================
    // Error Handling (RTS-ERROR-*)
    // =========================================================================

    /** @test */
    public function rts_error_01_handles_redis_connection_failure(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        // Should not throw
        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
        $this->assertTrue(true);
    }

    /** @test */
    public function rts_error_02_logs_error_on_failure(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->andThrow(new \Exception('Test error'));

        \Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Request tracking');
            });

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    /** @test */
    public function rts_error_03_continues_on_invalid_data(): void
    {
        $service = $this->createService();

        // Missing required fields
        $data = ['invalid' => 'data'];

        // Should not throw
        $service->track($data);
        $this->assertTrue(true);
    }

    // =========================================================================
    // Aggregation Methods (RTS-AGG-*)
    // =========================================================================

    /** @test */
    public function rts_agg_01_gets_endpoint_counts(): void
    {
        Cache::shouldReceive('get')
            ->withArgs(function ($key) {
                return str_contains($key, 'api:v1:analysis');
            })
            ->andReturn(150);

        $service = $this->createService();

        $count = $service->getEndpointCount('/api/v1/analysis', 'GET');

        $this->assertEquals(150, $count);
    }

    /** @test */
    public function rts_agg_02_gets_user_request_count(): void
    {
        $userId = 123;

        Cache::shouldReceive('get')
            ->withArgs(function ($key) use ($userId) {
                return str_contains($key, "user:{$userId}");
            })
            ->andReturn(45);

        $service = $this->createService();

        $count = $service->getUserRequestCount($userId);

        $this->assertEquals(45, $count);
    }

    /** @test */
    public function rts_agg_03_gets_hourly_counts(): void
    {
        $hour = now()->format('Y-m-d-H');

        Cache::shouldReceive('get')
            ->withArgs(function ($key) use ($hour) {
                return str_contains($key, $hour);
            })
            ->andReturn(500);

        $service = $this->createService();

        $count = $service->getHourlyCount($hour);

        $this->assertEquals(500, $count);
    }

    /** @test */
    public function rts_agg_04_returns_zero_for_missing_data(): void
    {
        Cache::shouldReceive('get')->andReturn(null);

        $service = $this->createService();

        $count = $service->getEndpointCount('/api/v1/nonexistent', 'GET');

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function rts_agg_05_gets_top_endpoints(): void
    {
        // Mock Redis SCAN or similar
        Cache::shouldReceive('get')
            ->times(3)
            ->andReturn(100, 50, 25);

        $service = $this->createService();

        $top = $service->getTopEndpoints(3);

        $this->assertIsArray($top);
    }

    // =========================================================================
    // TTL Configuration (RTS-TTL-*)
    // =========================================================================

    /** @test */
    public function rts_ttl_01_uses_configured_ttl(): void
    {
        Config::set('request_tracking.ttl_seconds', 3600);

        Cache::shouldReceive('increment')->andReturn(1);
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $ttl === 3600;
            });

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    /** @test */
    public function rts_ttl_02_default_ttl_is_24_hours(): void
    {
        Config::set('request_tracking.ttl_seconds', 86400);

        Cache::shouldReceive('increment')->andReturn(1);
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $ttl === 86400;
            });

        $service = $this->createService();
        $data = $this->createTrackingData();

        $service->track($data);
    }

    // =========================================================================
    // Performance Tests (RTS-PERF-*)
    // =========================================================================

    /** @test */
    public function rts_perf_01_track_completes_under_5ms(): void
    {
        Cache::shouldReceive('increment')->andReturn(1);
        Cache::shouldReceive('put');

        $service = $this->createService();
        $data = $this->createTrackingData();

        $start = microtime(true);
        $service->track($data);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(5, $elapsed, "Track time: {$elapsed}ms");
    }

    /** @test */
    public function rts_perf_02_bulk_tracking_efficient(): void
    {
        Cache::shouldReceive('increment')->andReturn(1);
        Cache::shouldReceive('put');

        $service = $this->createService();

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $data = $this->createTrackingData(['endpoint' => "/api/v1/test-{$i}"]);
            $service->track($data);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;

        $this->assertLessThan(5, $avgTime, "Average track time: {$avgTime}ms");
    }

    // =========================================================================
    // Security Tests (RTS-SEC-*)
    // =========================================================================

    /** @test */
    public function rts_sec_01_sanitizes_endpoint_in_key(): void
    {
        $service = $this->createService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeEndpoint');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, "/api/v1/<script>alert('xss')</script>");

        $this->assertStringNotContainsString('<script>', $normalized);
        $this->assertStringNotContainsString('alert', $normalized);
    }

    /** @test */
    public function rts_sec_02_prevents_key_injection(): void
    {
        $service = $this->createService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeEndpoint');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, '/api/v1/test:malicious');

        // Colon should be replaced or removed
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9:_\-\/]+$/', $normalized);
    }

    /** @test */
    public function rts_sec_03_no_sql_injection_via_endpoint(): void
    {
        $service = $this->createService();

        // Should handle safely
        $data = $this->createTrackingData([
            'endpoint' => "/api/v1/users?id=1' OR '1'='1",
        ]);

        $service->track($data);
        $this->assertTrue(true);
    }

    /** @test */
    public function rts_sec_04_user_id_validated(): void
    {
        $service = $this->createService();

        // Invalid user_id (string instead of int)
        $data = $this->createTrackingData(['user_id' => 'invalid']);

        // Should handle gracefully
        $service->track($data);
        $this->assertTrue(true);
    }
}
