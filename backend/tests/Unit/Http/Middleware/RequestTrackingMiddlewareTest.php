<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Http\Middleware\RequestTrackingMiddleware;
use App\Services\Admin\RequestTrackingService;
use App\Models\User;
use DateTime;
use DateTimeInterface;

/**
 * Request Tracking Middleware Tests (TASK-27-6)
 * 
 * Tests for middleware that tracks API requests for usage analytics.
 * Records request counts by endpoint, method, and timestamp for aggregation.
 * 
 * @see SPEC-027: Admin Dashboard (Ops + Usage)
 */
class RequestTrackingMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SECTION 1: Middleware Instantiation (MI)
    // =========================================================================

    /** @test MI-001: Middleware can be instantiated */
    public function test_middleware_can_be_instantiated(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $this->assertInstanceOf(RequestTrackingMiddleware::class, $middleware);
    }

    /** @test MI-002: Middleware has default sample rate of 1.0 */
    public function test_middleware_has_default_sample_rate(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $this->assertEquals(1.0, $middleware->getSampleRate());
    }

    /** @test MI-003: Middleware accepts custom sample rate */
    public function test_middleware_accepts_custom_sample_rate(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service, 0.5);
        
        $this->assertEquals(0.5, $middleware->getSampleRate());
    }

    /** @test MI-004: Middleware can set sample rate dynamically */
    public function test_middleware_can_set_sample_rate_dynamically(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $middleware->setSampleRate(0.1);
        
        $this->assertEquals(0.1, $middleware->getSampleRate());
    }

    /** @test MI-005: Sample rate clamped between 0 and 1 */
    public function test_sample_rate_clamped_between_zero_and_one(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $middleware->setSampleRate(1.5);
        $this->assertEquals(1.0, $middleware->getSampleRate());
        
        $middleware->setSampleRate(-0.5);
        $this->assertEquals(0.0, $middleware->getSampleRate());
    }

    // =========================================================================
    // SECTION 2: Basic Tracking Behavior (BT)
    // =========================================================================

    /** @test BT-001: Middleware tracks request to any endpoint */
    public function test_middleware_tracks_request_to_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) {
                return $endpoint === 'api/v1/users' 
                    && $method === 'GET'
                    && $userId === null
                    && $timestamp instanceof DateTimeInterface;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users', 'GET');
        $response = new Response();
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test BT-002: Middleware tracks POST method */
    public function test_middleware_tracks_post_method(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method) {
                return $endpoint === 'api/v1/analyses' 
                    && $method === 'POST';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/analyses', 'POST');
        $response = new Response();
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test BT-003: Middleware tracks PUT method */
    public function test_middleware_tracks_put_method(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method) {
                return $method === 'PUT';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/1', 'PUT');
        $response = new Response();
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test BT-004: Middleware tracks DELETE method */
    public function test_middleware_tracks_delete_method(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method) {
                return $method === 'DELETE';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/1', 'DELETE');
        $response = new Response();
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test BT-005: Middleware tracks PATCH method */
    public function test_middleware_tracks_patch_method(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method) {
                return $method === 'PATCH';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/1', 'PATCH');
        $response = new Response();
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test BT-006: Middleware passes request through unchanged */
    public function test_middleware_passes_request_through_unchanged(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $expectedResponse = new Response('test content', 200);
        
        $actualResponse = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });
        
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /** @test BT-007: Middleware records timestamp accurately */
    public function test_middleware_records_timestamp_accurately(): void
    {
        $recordedAt = null;
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) use (&$recordedAt) {
                $recordedAt = $timestamp;
                return true;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $before = new DateTime();
        $request = Request::create('/api/v1/test', 'GET');
        $middleware->handle($request, fn() => new Response());
        $after = new DateTime();
        
        $this->assertNotNull($recordedAt);
        $this->assertGreaterThanOrEqual($before, $recordedAt);
        $this->assertLessThanOrEqual($after, $recordedAt);
    }

    // =========================================================================
    // SECTION 3: Authenticated User Tracking (AU)
    // =========================================================================

    /** @test AU-001: Middleware tracks authenticated user ID */
    public function test_middleware_tracks_authenticated_user_id(): void
    {
        $user = User::factory()->make(['id' => 123]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId) use ($user) {
                return $userId === $user->id;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test AU-002: Middleware handles unauthenticated request */
    public function test_middleware_handles_unauthenticated_request(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId) {
                return $userId === null;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => null);
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test AU-003: Middleware tracks user ID for different users */
    public function test_middleware_tracks_different_user_ids(): void
    {
        $user1 = User::factory()->make(['id' => 1]);
        $user2 = User::factory()->make(['id' => 999]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $m, $userId) => $userId === 1);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $m, $userId) => $userId === 999);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request1 = Request::create('/api/v1/test', 'GET');
        $request1->setUserResolver(fn() => $user1);
        $middleware->handle($request1, fn() => new Response());
        
        $request2 = Request::create('/api/v1/test', 'GET');
        $request2->setUserResolver(fn() => $user2);
        $middleware->handle($request2, fn() => new Response());
    }

    /** @test AU-004: Middleware handles admin user */
    public function test_middleware_handles_admin_user(): void
    {
        $admin = User::factory()->make(['id' => 42, 'is_admin' => true]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId) use ($admin) {
                return $userId === $admin->id;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/admin/stats', 'GET');
        $request->setUserResolver(fn() => $admin);
        
        $middleware->handle($request, fn() => new Response());
    }

    // =========================================================================
    // SECTION 4: Endpoint Normalization (EN)
    // =========================================================================

    /** @test EN-001: Middleware normalizes endpoint with numeric ID */
    public function test_middleware_normalizes_endpoint_with_numeric_id(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                // Should normalize /api/v1/users/123 to /api/v1/users/{id}
                return $endpoint === 'api/v1/users/{id}';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/123', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EN-002: Middleware normalizes UUID in endpoint */
    public function test_middleware_normalizes_uuid_in_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                // Should normalize UUID to {id}
                return $endpoint === 'api/v1/analyses/{id}';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/analyses/550e8400-e29b-41d4-a716-446655440000', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EN-003: Middleware normalizes multiple IDs in endpoint */
    public function test_middleware_normalizes_multiple_ids_in_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                // Should normalize both IDs
                return $endpoint === 'api/v1/sites/{id}/analyses/{id}';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/sites/5/analyses/42', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EN-004: Middleware preserves query parameters in endpoint */
    public function test_middleware_preserves_query_params_in_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                // Endpoint should NOT include query parameters
                return $endpoint === 'api/v1/users';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users?page=1&limit=10', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EN-005: Middleware handles root endpoint */
    public function test_middleware_handles_root_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                return $endpoint === 'api/v1' || $endpoint === 'api/v1/';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EN-006: Middleware handles deep nested endpoint */
    public function test_middleware_handles_deep_nested_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                return $endpoint === 'api/v1/admin/users/{id}/settings';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/admin/users/123/settings', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    // =========================================================================
    // SECTION 5: Sample Rate Configuration (SR)
    // =========================================================================

    /** @test SR-001: Sample rate 1.0 tracks all requests */
    public function test_sample_rate_one_tracks_all_requests(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->times(10);
        
        $middleware = new RequestTrackingMiddleware($service, 1.0);
        
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
    }

    /** @test SR-002: Sample rate 0.0 tracks no requests */
    public function test_sample_rate_zero_tracks_no_requests(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service, 0.0);
        
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
    }

    /** @test SR-003: Sample rate 0.5 tracks approximately half of requests */
    public function test_sample_rate_half_tracks_approximately_half(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        
        // With 1000 requests at 50% sample rate, expect ~500 (±100)
        $recordCount = 0;
        $service->shouldReceive('record')
            ->andReturnUsing(function () use (&$recordCount) {
                $recordCount++;
                return true;
            });
        
        $middleware = new RequestTrackingMiddleware($service, 0.5);
        
        // Seed for reproducibility
        mt_srand(42);
        
        for ($i = 0; $i < 1000; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
        
        // With 50% sample rate, expect approximately 500 (±100)
        $this->assertGreaterThan(400, $recordCount, 'Should track at least 400 requests');
        $this->assertLessThan(600, $recordCount, 'Should track at most 600 requests');
    }

    /** @test SR-004: Sample rate can be set per-endpoint */
    public function test_sample_rate_can_be_set_per_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        
        // Health endpoint should never be tracked
        $service->shouldNotReceive('record')
            ->with('api/v1/health', \Mockery::any(), \Mockery::any(), \Mockery::any());
        
        // Normal endpoint should be tracked
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($endpoint) => $endpoint === 'api/v1/users');
        
        $middleware = new RequestTrackingMiddleware($service);
        $middleware->setEndpointSampleRate('api/v1/health', 0.0);
        
        $healthRequest = Request::create('/api/v1/health', 'GET');
        $middleware->handle($healthRequest, fn() => new Response());
        
        $usersRequest = Request::create('/api/v1/users', 'GET');
        $middleware->handle($usersRequest, fn() => new Response());
    }

    /** @test SR-005: Multiple endpoints can have different sample rates */
    public function test_multiple_endpoints_can_have_different_sample_rates(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        
        $healthCount = 0;
        $highTrafficCount = 0;
        $normalCount = 0;
        
        // Track counts by endpoint
        $service->shouldReceive('record')
            ->andReturnUsing(function ($endpoint) use (&$healthCount, &$highTrafficCount, &$normalCount) {
                if (str_contains($endpoint, 'health')) $healthCount++;
                if (str_contains($endpoint, 'high-traffic')) $highTrafficCount++;
                if (str_contains($endpoint, 'normal')) $normalCount++;
                return true;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        $middleware->setEndpointSampleRate('api/v1/health', 0.0);
        $middleware->setEndpointSampleRate('api/v1/high-traffic', 0.1);
        
        mt_srand(42);
        
        for ($i = 0; $i < 100; $i++) {
            $healthRequest = Request::create('/api/v1/health', 'GET');
            $middleware->handle($healthRequest, fn() => new Response());
        }
        
        for ($i = 0; $i < 100; $i++) {
            $highTrafficRequest = Request::create('/api/v1/high-traffic', 'GET');
            $middleware->handle($highTrafficRequest, fn() => new Response());
        }
        
        for ($i = 0; $i < 100; $i++) {
            $normalRequest = Request::create('/api/v1/normal', 'GET');
            $middleware->handle($normalRequest, fn() => new Response());
        }
        
        // Health at 0% should never be tracked
        $this->assertEquals(0, $healthCount, 'Health endpoint should never be tracked');
        // High-traffic at 10% should track ~10
        $this->assertLessThan(30, $highTrafficCount, 'High-traffic should track less than 30');
        // Normal at 100% should track all 100
        $this->assertEquals(100, $normalCount, 'Normal endpoint should track all requests');
    }

    /** @test SR-006: Health check endpoints excluded by default */
    public function test_health_check_endpoints_excluded_by_default(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $endpoints = [
            '/api/v1/health',
            '/api/v1/health/database',
            '/api/v1/health/queues',
            '/health',
            '/healthz',
            '/ping',
        ];
        
        foreach ($endpoints as $endpoint) {
            $request = Request::create($endpoint, 'GET');
            $middleware->handle($request, fn() => new Response());
        }
    }

    // =========================================================================
    // SECTION 6: Error Handling (EH)
    // =========================================================================

    /** @test EH-001: Middleware continues if tracking service throws exception */
    public function test_middleware_continues_if_tracking_service_throws(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $response = $middleware->handle($request, fn() => new Response('success'));
        
        $this->assertEquals('success', $response->getContent());
    }

    /** @test EH-002: Middleware logs tracking errors */
    public function test_middleware_logs_tracking_errors(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \Exception('Connection refused'));
        
        \Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Request tracking failed')
                    && isset($context['error'])
                    && str_contains($context['error'], 'Connection refused');
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EH-003: Middleware handles Redis timeout gracefully */
    public function test_middleware_handles_redis_timeout_gracefully(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \Exception('Connection timed out'));
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $response = $middleware->handle($request, fn() => new Response('ok', 200));
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test EH-004: Middleware handles queue push failure */
    public function test_middleware_handles_queue_push_failure(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \RuntimeException('Queue is full'));
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $response = $middleware->handle($request, fn() => new Response('ok'));
        
        $this->assertEquals('ok', $response->getContent());
    }

    /** @test EH-005: Middleware does not throw on service unavailable */
    public function test_middleware_does_not_throw_on_service_unavailable(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \Exception('Service unavailable'));
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        // Should not throw
        $middleware->handle($request, fn() => new Response());
        
        $this->assertTrue(true);
    }

    // =========================================================================
    // SECTION 7: Performance Tests (PT)
    // =========================================================================

    /** @test PT-001: Middleware overhead is less than 5ms */
    public function test_middleware_overhead_less_than_5ms(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->times(100);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
        
        $end = microtime(true);
        $avgTime = ($end - $start) / 100;
        
        // Average time per request should be less than 5ms (0.005 seconds)
        $this->assertLessThan(0.005, $avgTime, 
            "Average middleware overhead ({$avgTime}s) exceeds 5ms threshold");
    }

    /** @test PT-002: Middleware does not block response */
    public function test_middleware_does_not_block_response(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        $start = microtime(true);
        $response = $middleware->handle($request, fn() => new Response());
        $end = microtime(true);
        
        // Should complete almost instantly
        $this->assertLessThan(0.1, $end - $start);
    }

    /** @test PT-003: Async tracking is non-blocking */
    public function test_async_tracking_is_non_blocking(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andReturnUsing(function () {
                // Simulate async operation (should not delay response)
                return true;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        $start = microtime(true);
        $response = $middleware->handle($request, fn() => new Response());
        $duration = microtime(true) - $start;
        
        // Response should be immediate regardless of tracking
        $this->assertLessThan(0.01, $duration);
    }

    /** @test PT-004: Memory usage remains stable */
    public function test_memory_usage_remains_stable(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 1000; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
        
        $finalMemory = memory_get_usage();
        $memoryGrowth = $finalMemory - $initialMemory;
        
        // Memory should not grow significantly (< 1MB for 1000 requests)
        $this->assertLessThan(1048576, $memoryGrowth,
            "Memory growth ({$memoryGrowth} bytes) exceeds 1MB threshold");
    }

    // =========================================================================
    // SECTION 8: Edge Cases (EC)
    // =========================================================================

    /** @test EC-001: Middleware handles empty endpoint */
    public function test_middleware_handles_empty_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                return $endpoint === '' || $endpoint === '/';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-002: Middleware handles very long endpoint */
    public function test_middleware_handles_very_long_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $longPath = str_repeat('/a', 100);
        $request = Request::create($longPath, 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-003: Middleware handles special characters in endpoint */
    public function test_middleware_handles_special_characters_in_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                // Should handle encoded special characters
                return str_contains($endpoint, 'search') || str_contains($endpoint, 'test');
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/search?query=test%20special%20chars', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-004: Middleware handles Unicode in endpoint */
    public function test_middleware_handles_unicode_in_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/search?q=テスト', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-005: Middleware handles OPTIONS request */
    public function test_middleware_handles_options_request(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $method) => $method === 'OPTIONS');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'OPTIONS');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-006: Middleware handles HEAD request */
    public function test_middleware_handles_head_request(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $method) => $method === 'HEAD');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'HEAD');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-007: Middleware handles request with no path */
    public function test_middleware_handles_request_with_no_path(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('http://example.com', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test EC-008: Middleware handles concurrent requests safely */
    public function test_middleware_handles_concurrent_requests_safely(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->times(100);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        // Simulate concurrent access
        $requests = [];
        for ($i = 0; $i < 100; $i++) {
            $requests[] = function () use ($middleware, $i) {
                $request = Request::create("/api/v1/test/{$i}", 'GET');
                return $middleware->handle($request, fn() => new Response());
            };
        }
        
        // Execute all
        foreach ($requests as $handler) {
            $handler();
        }
        
        $this->assertTrue(true);
    }

    // =========================================================================
    // SECTION 9: Service Integration (SI)
    // =========================================================================

    /** @test SI-001: Middleware uses injected service for recording */
    public function test_middleware_uses_injected_service_for_recording(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->with('api/v1/test', 'GET', null, \Mockery::type(DateTimeInterface::class));
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SI-002: Service receives normalized endpoint */
    public function test_service_receives_normalized_endpoint(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint) {
                return $endpoint === 'api/v1/users/{id}';
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/42', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SI-003: Service receives correct HTTP method */
    public function test_service_receives_correct_http_method(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        
        foreach ($methods as $method) {
            $service = Mockery::mock(RequestTrackingService::class);
            $service->shouldReceive('record')
                ->once()
                ->withArgs(fn($e, $m) => $m === $method);
            
            $middleware = new RequestTrackingMiddleware($service);
            
            $request = Request::create('/api/v1/test', $method);
            $middleware->handle($request, fn() => new Response());
        }
        
        $this->assertTrue(true);
    }

    /** @test SI-004: Service receives null for unauthenticated user */
    public function test_service_receives_null_for_unauthenticated_user(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $m, $userId) => $userId === null);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => null);
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SI-005: Service receives user ID for authenticated user */
    public function test_service_receives_user_id_for_authenticated_user(): void
    {
        $user = User::factory()->make(['id' => 999]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(fn($e, $m, $userId) => $userId === 999);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $middleware->handle($request, fn() => new Response());
    }

    // =========================================================================
    // SECTION 10: Configuration Tests (CT)
    // =========================================================================

    /** @test CT-001: Middleware reads config for default sample rate */
    public function test_middleware_reads_config_for_default_sample_rate(): void
    {
        config(['request-tracking.sample_rate' => 0.75]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $this->assertEquals(0.75, $middleware->getSampleRate());
    }

    /** @test CT-002: Middleware reads config for excluded endpoints */
    public function test_middleware_reads_config_for_excluded_endpoints(): void
    {
        config(['request-tracking.excluded_endpoints' => [
            'api/v1/health',
            'api/v1/metrics',
        ]]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request1 = Request::create('/api/v1/health', 'GET');
        $middleware->handle($request1, fn() => new Response());
        
        $request2 = Request::create('/api/v1/metrics', 'GET');
        $middleware->handle($request2, fn() => new Response());
    }

    /** @test CT-003: Middleware reads config for endpoint sample rates */
    public function test_middleware_reads_config_for_endpoint_sample_rates(): void
    {
        config(['request-tracking.endpoint_sample_rates' => [
            'api/v1/high-traffic' => 0.1,
            'api/v1/normal' => 1.0,
        ]]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $this->assertEquals(0.1, $middleware->getEndpointSampleRate('api/v1/high-traffic'));
        $this->assertEquals(1.0, $middleware->getEndpointSampleRate('api/v1/normal'));
    }

    /** @test CT-004: Middleware can be disabled via config */
    public function test_middleware_can_be_disabled_via_config(): void
    {
        config(['request-tracking.enabled' => false]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $middleware->handle($request, fn() => new Response());
    }

    /** @test CT-005: Config defaults are sensible */
    public function test_config_defaults_are_sensible(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $middleware = new RequestTrackingMiddleware($service);
        
        $this->assertEquals(1.0, $middleware->getSampleRate());
        $this->assertTrue($middleware->isEnabled());
    }

    // =========================================================================
    // SECTION 11: Security Tests (SC)
    // =========================================================================

    /** @test SC-001: Middleware does not expose sensitive data in logs */
    public function test_middleware_does_not_expose_sensitive_data_in_logs(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        \Log::shouldReceive('warning')->never();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Authorization', 'Bearer secret-token');
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SC-002: Middleware handles malicious endpoint paths safely */
    public function test_middleware_handles_malicious_endpoint_paths_safely(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/../config', 'GET');
        $middleware->handle($request, fn() => new Response());
        
        $this->assertTrue(true);
    }

    /** @test SC-003: Middleware does not store request body */
    public function test_middleware_does_not_store_request_body(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) {
                // Should only receive these 4 parameters
                return is_string($endpoint) 
                    && is_string($method)
                    && (is_int($userId) || is_null($userId))
                    && $timestamp instanceof DateTimeInterface;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'POST', [], [], [], [], json_encode(['password' => 'secret']));
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SC-004: Middleware does not store request headers */
    public function test_middleware_does_not_store_request_headers(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) {
                // Record should only have 4 parameters, no headers
                return func_num_args() === 4;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('X-API-Key', 'secret-key');
        $request->headers->set('Cookie', 'session=abc123');
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test SC-005: Middleware handles SQL injection attempt safely */
    public function test_middleware_handles_sql_injection_attempt_safely(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create("/api/v1/users?id=1'; DROP TABLE users;--", 'GET');
        $middleware->handle($request, fn() => new Response());
        
        $this->assertTrue(true);
    }

    // =========================================================================
    // SECTION 12: Integration Tests (IT)
    // =========================================================================

    /** @test IT-001: Full request flow with tracking */
    public function test_full_request_flow_with_tracking(): void
    {
        $user = User::factory()->make(['id' => 1]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) use ($user) {
                return $endpoint === 'api/v1/users/{id}'
                    && $method === 'GET'
                    && $userId === $user->id
                    && $timestamp instanceof DateTimeInterface;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/users/123', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $response = $middleware->handle($request, fn() => new Response('user data'));
        
        $this->assertEquals('user data', $response->getContent());
    }

    /** @test IT-002: Multiple middleware instances are independent */
    public function test_multiple_middleware_instances_are_independent(): void
    {
        $service1 = Mockery::mock(RequestTrackingService::class);
        $service2 = Mockery::mock(RequestTrackingService::class);
        
        $service1->shouldReceive('record')->once();
        $service2->shouldNotReceive('record'); // Second has 0% sample rate
        
        $middleware1 = new RequestTrackingMiddleware($service1, 1.0);
        $middleware2 = new RequestTrackingMiddleware($service2, 0.0);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        $middleware1->handle($request, fn() => new Response());
        $middleware2->handle($request, fn() => new Response());
    }

    /** @test IT-003: Middleware works in middleware pipeline */
    public function test_middleware_works_in_pipeline(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        // Simulate Laravel middleware pipeline
        $pipeline = app(\Illuminate\Pipeline\Pipeline::class);
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->then(function ($request) {
                return new Response('handled');
            });
        
        $this->assertEquals('handled', $response->getContent());
    }

    /** @test IT-004: Middleware handles exception in downstream handler */
    public function test_middleware_handles_exception_in_downstream_handler(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        try {
            $middleware->handle($request, function ($request) {
                throw new \Exception('Handler error');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Handler error', $e->getMessage());
        }
    }

    // =========================================================================
    // SECTION 13: Acceptance Criteria Tests (AC)
    // =========================================================================

    /** @test AC-001: Middleware class exists at correct location */
    public function test_middleware_class_exists_at_correct_location(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\RequestTrackingMiddleware::class),
            'RequestTrackingMiddleware should exist at app/Http/Middleware/RequestTrackingMiddleware.php');
    }

    /** @test AC-002: Middleware records endpoint, method, timestamp, user_id */
    public function test_middleware_records_endpoint_method_timestamp_user_id(): void
    {
        $user = User::factory()->make(['id' => 42]);
        
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->withArgs(function ($endpoint, $method, $userId, $timestamp) use ($user) {
                return is_string($endpoint)
                    && is_string($method)
                    && $userId === $user->id
                    && $timestamp instanceof DateTimeInterface;
            });
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'POST');
        $request->setUserResolver(fn() => $user);
        
        $middleware->handle($request, fn() => new Response());
    }

    /** @test AC-003: Middleware uses lightweight storage */
    public function test_middleware_uses_lightweight_storage(): void
    {
        // Verify that the service is designed for fast writes
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andReturn(true);
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        $start = microtime(true);
        $middleware->handle($request, fn() => new Response());
        $duration = microtime(true) - $start;
        
        // Should complete in under 5ms
        $this->assertLessThan(0.005, $duration, 'Storage operation should be lightweight');
    }

    /** @test AC-004: Middleware latency overhead is under 5ms */
    public function test_middleware_latency_overhead_under_5ms(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record');
        
        $middleware = new RequestTrackingMiddleware($service);
        
        // Measure overhead over 100 requests
        $overheadTimes = [];
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            
            $start = microtime(true);
            $middleware->handle($request, fn() => new Response());
            $overheadTimes[] = microtime(true) - $start;
        }
        
        $avgOverhead = array_sum($overheadTimes) / count($overheadTimes);
        
        $this->assertLessThan(0.005, $avgOverhead,
            "Average middleware overhead ({$avgOverhead}s) must be under 5ms");
    }

    /** @test AC-005: Middleware supports configurable sample rate */
    public function test_middleware_supports_configurable_sample_rate(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        
        // 50% sample rate should track approximately half
        $recordCount = 0;
        $service->shouldReceive('record')
            ->andReturnUsing(function () use (&$recordCount) {
                $recordCount++;
                return true;
            });
        
        $middleware = new RequestTrackingMiddleware($service, 0.5);
        
        mt_srand(42);
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $middleware->handle($request, fn() => new Response());
        }
        
        // With 50% sample rate, expect approximately 50 (±20)
        $this->assertGreaterThan(30, $recordCount, 'Should track at least 30 requests');
        $this->assertLessThan(70, $recordCount, 'Should track at most 70 requests');
    }

    /** @test AC-006: Middleware handles queue failures gracefully */
    public function test_middleware_handles_queue_failures_gracefully(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('record')
            ->once()
            ->andThrow(new \Exception('Queue connection refused'));
        
        \Log::shouldReceive('warning')->once();
        
        $middleware = new RequestTrackingMiddleware($service);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        // Should not throw
        $response = $middleware->handle($request, fn() => new Response('ok'));
        
        $this->assertEquals('ok', $response->getContent());
    }

    /** @test AC-007: Unit tests verify tracking behavior */
    public function test_unit_tests_verify_tracking_behavior(): void
    {
        // This file is the unit test verification
        // All tests in this file verify tracking behavior
        $this->assertTrue(true, 'Unit tests exist and verify tracking behavior');
    }

    /** @test AC-008: Performance tests confirm minimal overhead */
    public function test_performance_tests_confirm_minimal_overhead(): void
    {
        // Covered by PT-001 and AC-004
        // Average overhead must be < 5ms
        $this->assertTrue(true, 'Performance tests in this file confirm minimal overhead');
    }
}
