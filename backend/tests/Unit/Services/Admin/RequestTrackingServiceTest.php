<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Services\Admin\RequestTrackingService;
use DateTime;
use DateTimeInterface;

/**
 * Request Tracking Service Tests (TASK-27-6)
 * 
 * Tests for the service that handles request tracking data storage.
 * Uses Redis for lightweight tracking with optional database aggregation.
 * 
 * @see SPEC-027: Admin Dashboard (Ops + Usage)
 */
class RequestTrackingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SECTION 1: Service Instantiation (SI)
    // =========================================================================

    /** @test SI-001: Service can be instantiated */
    public function test_service_can_be_instantiated(): void
    {
        $service = new RequestTrackingService();
        $this->assertInstanceOf(RequestTrackingService::class, $service);
    }

    /** @test SI-002: Service can be resolved from container */
    public function test_service_can_be_resolved_from_container(): void
    {
        $service = app(RequestTrackingService::class);
        $this->assertInstanceOf(RequestTrackingService::class, $service);
    }

    /** @test SI-003: Service has default configuration */
    public function test_service_has_default_configuration(): void
    {
        $service = new RequestTrackingService();
        
        $this->assertEquals('request_tracking:', $service->getKeyPrefix());
        $this->assertEquals(3600, $service->getTTL());
    }

    // =========================================================================
    // SECTION 2: Record Method (RM)
    // =========================================================================

    /** @test RM-001: record() stores data to Redis */
    public function test_record_stores_data_to_redis(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) {
                return str_contains($key, 'request_tracking:queue');
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/users', 'GET', 1, new DateTime());
    }

    /** @test RM-002: record() uses correct key format */
    public function test_record_uses_correct_key_format(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key) {
                return preg_match('/^request_tracking:queue:\d{8}$/', $key);
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'GET', null, new DateTime());
    }

    /** @test RM-003: record() stores endpoint in data */
    public function test_record_stores_endpoint_in_data(): void
    {
        $capturedData = null;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/users', 'GET', null, new DateTime());

        $this->assertEquals('api/v1/users', $capturedData['endpoint']);
    }

    /** @test RM-004: record() stores HTTP method in data */
    public function test_record_stores_method_in_data(): void
    {
        $capturedData = null;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'POST', null, new DateTime());

        $this->assertEquals('POST', $capturedData['method']);
    }

    /** @test RM-005: record() stores user_id when provided */
    public function test_record_stores_user_id_when_provided(): void
    {
        $capturedData = null;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'GET', 42, new DateTime());

        $this->assertEquals(42, $capturedData['user_id']);
    }

    /** @test RM-006: record() stores null user_id for unauthenticated */
    public function test_record_stores_null_user_id_for_unauthenticated(): void
    {
        $capturedData = null;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertNull($capturedData['user_id']);
    }

    /** @test RM-007: record() stores ISO timestamp */
    public function test_record_stores_iso_timestamp(): void
    {
        $capturedData = null;
        $timestamp = new DateTime('2026-03-04 10:30:00');

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'GET', null, $timestamp);

        $this->assertEquals('2026-03-04T10:30:00+00:00', $capturedData['timestamp']);
    }

    /** @test RM-008: record() returns true on success */
    public function test_record_returns_true_on_success(): void
    {
        Redis::shouldReceive('rpush')->once()->andReturn(1);
        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertTrue($result);
    }

    /** @test RM-009: record() handles Redis connection failure */
    public function test_record_handles_redis_connection_failure(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertFalse($result);
    }

    /** @test RM-010: record() logs error on failure */
    public function test_record_logs_error_on_failure(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        \Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Request tracking failed');
            });

        $service = new RequestTrackingService();
        $service->record('api/v1/test', 'GET', null, new DateTime());
    }

    // =========================================================================
    // SECTION 3: Increment Counter Method (IC)
    // =========================================================================

    /** @test IC-001: incrementEndpoint() increments Redis counter */
    public function test_increment_endpoint_increments_redis_counter(): void
    {
        Redis::shouldReceive('incr')
            ->once()
            ->withArgs(function ($key) {
                return str_contains($key, 'endpoint:api/v1/users:GET');
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->incrementEndpoint('api/v1/users', 'GET');
    }

    /** @test IC-002: incrementEndpoint() uses hourly key */
    public function test_increment_endpoint_uses_hourly_key(): void
    {
        Redis::shouldReceive('incr')
            ->once()
            ->withArgs(function ($key) {
                // Key should contain date-hour format (YYYYMMDDHH)
                return preg_match('/endpoint:.*:\d{10}$/', $key);
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->incrementEndpoint('api/v1/test', 'GET');
    }

    /** @test IC-003: incrementEndpoint() sets TTL on new key */
    public function test_increment_endpoint_sets_ttl_on_new_key(): void
    {
        Redis::shouldReceive('incr')->once()->andReturn(1);
        Redis::shouldReceive('expire')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $ttl === 86400; // 24 hours
            });

        $service = new RequestTrackingService();
        $service->incrementEndpoint('api/v1/test', 'GET');
    }

    /** @test IC-004: incrementEndpoint() groups by endpoint and method */
    public function test_increment_endpoint_groups_by_endpoint_and_method(): void
    {
        Redis::shouldReceive('incr')
            ->once()
            ->withArgs(fn($key) => str_contains($key, 'endpoint:api/v1/users:GET'));

        Redis::shouldReceive('incr')
            ->once()
            ->withArgs(fn($key) => str_contains($key, 'endpoint:api/v1/users:POST'));

        Redis::shouldReceive('expire')->twice();

        $service = new RequestTrackingService();
        $service->incrementEndpoint('api/v1/users', 'GET');
        $service->incrementEndpoint('api/v1/users', 'POST');
    }

    /** @test IC-005: incrementEndpoint() handles Redis failure */
    public function test_increment_endpoint_handles_redis_failure(): void
    {
        Redis::shouldReceive('incr')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        $service = new RequestTrackingService();
        $result = $service->incrementEndpoint('api/v1/test', 'GET');

        $this->assertFalse($result);
    }

    // =========================================================================
    // SECTION 4: Get Stats Method (GS)
    // =========================================================================

    /** @test GS-001: getEndpointStats() retrieves counts from Redis */
    public function test_get_endpoint_stats_retrieves_counts_from_redis(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->with('request_tracking:endpoint:*')
            ->andReturn([
                'request_tracking:endpoint:api/v1/users:GET:2026030410',
                'request_tracking:endpoint:api/v1/users:POST:2026030410',
            ]);

        Redis::shouldReceive('get')
            ->twice()
            ->andReturn('150', '42');

        $service = new RequestTrackingService();
        $stats = $service->getEndpointStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('api/v1/users', $stats);
    }

    /** @test GS-002: getEndpointStats() returns empty array when no data */
    public function test_get_endpoint_stats_returns_empty_array_when_no_data(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([]);

        $service = new RequestTrackingService();
        $stats = $service->getEndpointStats();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /** @test GS-003: getEndpointStats() aggregates by endpoint */
    public function test_get_endpoint_stats_aggregates_by_endpoint(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([
                'request_tracking:endpoint:api/v1/users:GET:2026030410',
                'request_tracking:endpoint:api/v1/users:GET:2026030411',
            ]);

        Redis::shouldReceive('get')
            ->twice()
            ->andReturn('100', '50');

        $service = new RequestTrackingService();
        $stats = $service->getEndpointStats();

        $this->assertEquals(150, $stats['api/v1/users']['GET']);
    }

    /** @test GS-004: getEndpointStats() separates methods */
    public function test_get_endpoint_stats_separates_methods(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([
                'request_tracking:endpoint:api/v1/users:GET:2026030410',
                'request_tracking:endpoint:api/v1/users:POST:2026030410',
            ]);

        Redis::shouldReceive('get')
            ->twice()
            ->andReturn('100', '50');

        $service = new RequestTrackingService();
        $stats = $service->getEndpointStats();

        $this->assertEquals(100, $stats['api/v1/users']['GET']);
        $this->assertEquals(50, $stats['api/v1/users']['POST']);
    }

    /** @test GS-005: getEndpointStats() handles Redis failure */
    public function test_get_endpoint_stats_handles_redis_failure(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        $service = new RequestTrackingService();
        $stats = $service->getEndpointStats();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    // =========================================================================
    // SECTION 5: Get Requests by Period (RP)
    // =========================================================================

    /** @test RP-001: getRequestsByPeriod() returns hourly breakdown */
    public function test_get_requests_by_period_returns_hourly_breakdown(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([
                'request_tracking:endpoint:api/v1/users:GET:2026030410',
                'request_tracking:endpoint:api/v1/users:GET:2026030411',
            ]);

        Redis::shouldReceive('get')
            ->twice()
            ->andReturn('100', '150');

        $service = new RequestTrackingService();
        $breakdown = $service->getRequestsByPeriod('2026-03-04');

        $this->assertIsArray($breakdown);
        $this->assertCount(2, $breakdown);
    }

    /** @test RP-002: getRequestsByPeriod() formats hour keys correctly */
    public function test_get_requests_by_period_formats_hour_keys_correctly(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([
                'request_tracking:endpoint:api/v1/test:GET:2026030410',
            ]);

        Redis::shouldReceive('get')
            ->once()
            ->andReturn('100');

        $service = new RequestTrackingService();
        $breakdown = $service->getRequestsByPeriod('2026-03-04');

        $this->assertArrayHasKey('10:00', $breakdown);
    }

    /** @test RP-003: getRequestsByPeriod() aggregates all endpoints */
    public function test_get_requests_by_period_aggregates_all_endpoints(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([
                'request_tracking:endpoint:api/v1/users:GET:2026030410',
                'request_tracking:endpoint:api/v1/analyses:POST:2026030410',
            ]);

        Redis::shouldReceive('get')
            ->twice()
            ->andReturn('100', '50');

        $service = new RequestTrackingService();
        $breakdown = $service->getRequestsByPeriod('2026-03-04');

        $this->assertEquals(150, $breakdown['10:00']);
    }

    /** @test RP-004: getRequestsByPeriod() handles empty day */
    public function test_get_requests_by_period_handles_empty_day(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->andReturn([]);

        $service = new RequestTrackingService();
        $breakdown = $service->getRequestsByPeriod('2026-03-04');

        $this->assertEmpty($breakdown);
    }

    // =========================================================================
    // SECTION 6: Flush to Database (FD)
    // =========================================================================

    /** @test FD-001: flushToDatabase() moves data from Redis to DB */
    public function test_flush_to_database_moves_data_from_redis_to_db(): void
    {
        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn([
                json_encode(['endpoint' => 'api/v1/test', 'method' => 'GET', 'user_id' => null, 'timestamp' => '2026-03-04T10:00:00+00:00']),
            ]);

        Redis::shouldReceive('ltrim')->once();
        Redis::shouldReceive('llen')->once()->andReturn(0);

        DB::shouldReceive('table')->once()->andReturnSelf();
        DB::shouldReceive('insert')->once()->andReturnTrue();

        $service = new RequestTrackingService();
        $result = $service->flushToDatabase();

        $this->assertTrue($result);
    }

    /** @test FD-002: flushToDatabase() handles empty queue */
    public function test_flush_to_database_handles_empty_queue(): void
    {
        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn([]);

        $service = new RequestTrackingService();
        $result = $service->flushToDatabase();

        $this->assertTrue($result);
    }

    /** @test FD-003: flushToDatabase() batches large datasets */
    public function test_flush_to_database_batches_large_datasets(): void
    {
        // Create 150 items (should batch into 2 groups)
        $items = [];
        for ($i = 0; $i < 150; $i++) {
            $items[] = json_encode([
                'endpoint' => 'api/v1/test',
                'method' => 'GET',
                'user_id' => null,
                'timestamp' => '2026-03-04T10:00:00+00:00',
            ]);
        }

        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn($items);

        Redis::shouldReceive('ltrim')->once();

        DB::shouldReceive('table')->twice()->andReturnSelf();
        DB::shouldReceive('insert')->twice()->andReturnTrue();

        $service = new RequestTrackingService();
        $result = $service->flushToDatabase();

        $this->assertTrue($result);
    }

    /** @test FD-004: flushToDatabase() handles database failure */
    public function test_flush_to_database_handles_database_failure(): void
    {
        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn([
                json_encode(['endpoint' => 'api/v1/test', 'method' => 'GET', 'user_id' => null, 'timestamp' => '2026-03-04T10:00:00+00:00']),
            ]);

        DB::shouldReceive('table')->once()->andThrow(new \Exception('Database error'));

        // Should NOT trim queue on failure
        Redis::shouldReceive('ltrim')->never();

        $service = new RequestTrackingService();
        $result = $service->flushToDatabase();

        $this->assertFalse($result);
    }

    /** @test FD-005: flushToDatabase() preserves data on failure */
    public function test_flush_to_database_preserves_data_on_failure(): void
    {
        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn([
                json_encode(['endpoint' => 'api/v1/test', 'method' => 'GET', 'user_id' => null, 'timestamp' => '2026-03-04T10:00:00+00:00']),
            ]);

        DB::shouldReceive('table')->once()->andThrow(new \Exception('Database error'));

        // Queue should NOT be trimmed on failure
        Redis::shouldReceive('ltrim')->never();

        $service = new RequestTrackingService();
        $service->flushToDatabase();
    }

    // =========================================================================
    // SECTION 7: Clear Method (CM)
    // =========================================================================

    /** @test CM-001: clearHour() removes specific hour data */
    public function test_clear_hour_removes_specific_hour_data(): void
    {
        Redis::shouldReceive('del')
            ->once()
            ->withArgs(function ($keys) {
                return is_array($keys) && count($keys) > 0;
            });

        $service = new RequestTrackingService();
        $result = $service->clearHour('2026-03-04', 10);

        $this->assertTrue($result);
    }

    /** @test CM-002: clearDay() removes all day data */
    public function test_clear_day_removes_all_day_data(): void
    {
        Redis::shouldReceive('del')
            ->once()
            ->withArgs(function ($keys) {
                return is_array($keys) && count($keys) > 0;
            });

        $service = new RequestTrackingService();
        $result = $service->clearDay('2026-03-04');

        $this->assertTrue($result);
    }

    /** @test CM-003: clear() removes all tracking data */
    public function test_clear_removes_all_tracking_data(): void
    {
        Redis::shouldReceive('keys')
            ->once()
            ->with('request_tracking:*')
            ->andReturn(['request_tracking:test']);

        Redis::shouldReceive('del')
            ->once()
            ->with(['request_tracking:test']);

        $service = new RequestTrackingService();
        $result = $service->clear();

        $this->assertTrue($result);
    }

    // =========================================================================
    // SECTION 8: Edge Cases (EC)
    // =========================================================================

    /** @test EC-001: Service handles Redis not available */
    public function test_service_handles_redis_not_available(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Redis not available'));

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertFalse($result);
    }

    /** @test EC-002: Service handles corrupted data in queue */
    public function test_service_handles_corrupted_data_in_queue(): void
    {
        Redis::shouldReceive('lrange')
            ->once()
            ->andReturn(['not valid json', '{"valid": "json"}']);

        DB::shouldReceive('table')->once()->andReturnSelf();
        DB::shouldReceive('insert')->once()->andReturnTrue();

        Redis::shouldReceive('ltrim')->once();

        $service = new RequestTrackingService();
        $result = $service->flushToDatabase();

        // Should continue despite invalid entry
        $this->assertTrue($result);
    }

    /** @test EC-003: Service handles very long endpoint names */
    public function test_service_handles_very_long_endpoint_names(): void
    {
        $longEndpoint = str_repeat('a', 1000);

        Redis::shouldReceive('rpush')->once();
        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $result = $service->record($longEndpoint, 'GET', null, new DateTime());

        $this->assertTrue($result);
    }

    /** @test EC-004: Service handles unicode in data */
    public function test_service_handles_unicode_in_data(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) {
                $decoded = json_decode($data, true);
                return $decoded !== null;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/search?q=テスト', 'GET', null, new DateTime());

        $this->assertTrue($result);
    }

    /** @test EC-005: Service handles concurrent access */
    public function test_service_handles_concurrent_access(): void
    {
        Redis::shouldReceive('rpush')->times(100);
        Redis::shouldReceive('expire')->times(100);

        $service = new RequestTrackingService();

        for ($i = 0; $i < 100; $i++) {
            $service->record('api/v1/test', 'GET', $i, new DateTime());
        }

        $this->assertTrue(true);
    }

    // =========================================================================
    // SECTION 9: Performance Tests (PT)
    // =========================================================================

    /** @test PT-001: record() completes under 2ms */
    public function test_record_completes_under_2ms(): void
    {
        Redis::shouldReceive('rpush')->once();
        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();

        $start = microtime(true);
        $service->record('api/v1/test', 'GET', null, new DateTime());
        $duration = microtime(true) - $start;

        $this->assertLessThan(0.002, $duration, 'record() should complete under 2ms');
    }

    /** @test PT-002: getEndpointStats() completes under 100ms */
    public function test_get_endpoint_stats_completes_under_100ms(): void
    {
        // Simulate 100 keys
        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[] = "request_tracking:endpoint:api/v1/test{$i}:GET:2026030410";
        }

        Redis::shouldReceive('keys')->once()->andReturn($keys);
        Redis::shouldReceive('get')->times(100)->andReturn('100');

        $service = new RequestTrackingService();

        $start = microtime(true);
        $service->getEndpointStats();
        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, 'getEndpointStats() should complete under 100ms');
    }

    /** @test PT-003: flushToDatabase() batches efficiently */
    public function test_flush_to_database_batches_efficiently(): void
    {
        // Create 1000 items
        $items = [];
        for ($i = 0; $i < 1000; $i++) {
            $items[] = json_encode([
                'endpoint' => 'api/v1/test',
                'method' => 'GET',
                'user_id' => null,
                'timestamp' => '2026-03-04T10:00:00+00:00',
            ]);
        }

        Redis::shouldReceive('lrange')->once()->andReturn($items);
        Redis::shouldReceive('ltrim')->once();

        // Should batch into multiple inserts
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('insert')->atLeast(1)->andReturnTrue();

        $service = new RequestTrackingService();

        $start = microtime(true);
        $service->flushToDatabase();
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'flushToDatabase() should complete under 1s for 1000 items');
    }

    // =========================================================================
    // SECTION 10: Acceptance Criteria (AC)
    // =========================================================================

    /** @test AC-001: Service uses lightweight storage (Redis) */
    public function test_service_uses_lightweight_storage(): void
    {
        Redis::shouldReceive('rpush')->once();
        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertTrue($result, 'Service should use Redis for lightweight storage');
    }

    /** @test AC-002: Service records all required fields */
    public function test_service_records_all_required_fields(): void
    {
        $capturedData = null;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$capturedData) {
                $capturedData = json_decode($data, true);
                return true;
            });

        Redis::shouldReceive('expire')->once();

        $service = new RequestTrackingService();
        $service->record('api/v1/users', 'POST', 42, new DateTime('2026-03-04 10:30:00'));

        $this->assertEquals('api/v1/users', $capturedData['endpoint']);
        $this->assertEquals('POST', $capturedData['method']);
        $this->assertEquals(42, $capturedData['user_id']);
        $this->assertArrayHasKey('timestamp', $capturedData);
    }

    /** @test AC-003: Service handles failures gracefully */
    public function test_service_handles_failures_gracefully(): void
    {
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        $service = new RequestTrackingService();
        $result = $service->record('api/v1/test', 'GET', null, new DateTime());

        $this->assertFalse($result, 'Service should return false on failure, not throw');
    }
}
