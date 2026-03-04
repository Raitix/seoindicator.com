<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\RequestTrackingMiddleware;
use App\Services\Analytics\RequestTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Unit tests for RequestTrackingMiddleware.
 *
 * Tests middleware that tracks API requests for usage analytics.
 * Records request counts by endpoint, method, and timestamp.
 *
 * @see TASK-27-6
 * @see SPEC-027
 */
class RequestTrackingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createRequest(
        string $path = '/api/v1/analysis',
        string $method = 'GET',
        ?User $user = null
    ): Request {
        $request = Request::create($path, $method);
        if ($user) {
            $request->setUserResolver(fn() => $user);
        }
        return $request;
    }

    protected function createMockTrackingService(): RequestTrackingService
    {
        return Mockery::mock(RequestTrackingService::class);
    }

    // =========================================================================
    // Middleware Instantiation (RTM-INST-*)
    // =========================================================================

    /** @test */
    public function rtm_inst_01_middleware_can_be_instantiated(): void
    {
        $service = $this->createMockTrackingService();
        $middleware = new RequestTrackingMiddleware($service);

        $this->assertInstanceOf(RequestTrackingMiddleware::class, $middleware);
    }

    /** @test */
    public function rtm_inst_02_middleware_resolved_from_container(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $this->assertInstanceOf(RequestTrackingMiddleware::class, $middleware);
    }

    // =========================================================================
    // Request Tracking - Basic (RTM-TRACK-*)
    // =========================================================================

    /** @test */
    public function rtm_track_01_tracks_endpoint_path(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['endpoint'] === '/api/v1/analysis';
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET');

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_track_02_tracks_http_method(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['method'] === 'POST';
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'POST');

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_track_03_tracks_all_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $service = $this->createMockTrackingService();
            $service->shouldReceive('track')
                ->once()
                ->withArgs(function ($data) use ($method) {
                    return $data['method'] === $method;
                });

            $middleware = new RequestTrackingMiddleware($service);
            $request = $this->createRequest('/api/v1/test', $method);

            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    /** @test */
    public function rtm_track_04_tracks_authenticated_user_id(): void
    {
        $user = $this->createUser();

        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) use ($user) {
                return $data['user_id'] === $user->id;
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET', $user);

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_track_05_tracks_null_user_id_for_unauthenticated(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['user_id'] === null;
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET');

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_track_06_tracks_timestamp(): void
    {
        $now = now();

        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) use ($now) {
                $timestamp = $data['timestamp'];
                return $timestamp >= $now && $timestamp <= now();
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET');

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_track_07_tracks_complete_request_data(): void
    {
        $user = $this->createUser();

        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) use ($user) {
                return $data['endpoint'] === '/api/v1/analysis'
                    && $data['method'] === 'GET'
                    && $data['user_id'] === $user->id
                    && isset($data['timestamp']);
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET', $user);

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    // =========================================================================
    // Pass-Through Behavior (RTM-PASS-*)
    // =========================================================================

    /** @test */
    public function rtm_pass_01_passes_request_to_next_middleware(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('OK', 200);
        });

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_pass_02_returns_response_from_next_middleware(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('Custom Response', 201));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Custom Response', $response->getContent());
    }

    /** @test */
    public function rtm_pass_03_does_not_modify_response(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $originalResponse = new Response('Original', 200);
        $response = $middleware->handle($request, fn($req) => $originalResponse);

        $this->assertSame($originalResponse, $response);
    }

    // =========================================================================
    // Exclusion Rules (RTM-EXCLUDE-*)
    // =========================================================================

    /** @test */
    public function rtm_exclude_01_excludes_health_check_endpoints(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['api/health', 'api/health/*']);

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/health', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_exclude_02_excludes_telescope_endpoints(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['telescope/*']);

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/telescope/requests', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_exclude_03_excludes_horizon_endpoints(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['horizon/*']);

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/horizon/dashboard', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_exclude_04_excludes_static_assets(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['css/*', 'js/*', 'images/*']);

        $middleware = new RequestTrackingMiddleware($service);

        $paths = ['/css/app.css', '/js/app.js', '/images/logo.png'];
        foreach ($paths as $path) {
            $request = Request::create($path, 'GET');
            $response = $middleware->handle($request, fn($req) => new Response('OK'));
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /** @test */
    public function rtm_exclude_05_excludes_options_requests(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.exclude_options', true);

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'OPTIONS');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_exclude_06_wildcard_path_matching(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['api/v1/admin/*']);

        $middleware = new RequestTrackingMiddleware($service);

        $paths = ['/api/v1/admin/users', '/api/v1/admin/settings', '/api/v1/admin/reports/daily'];
        foreach ($paths as $path) {
            $request = Request::create($path, 'GET');
            $response = $middleware->handle($request, fn($req) => new Response('OK'));
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    // =========================================================================
    // Sample Rate Configuration (RTM-SAMPLE-*)
    // =========================================================================

    /** @test */
    public function rtm_sample_01_default_sample_rate_is_100_percent(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->times(10);

        Config::set('request_tracking.sample_rate', 1.0);

        $middleware = new RequestTrackingMiddleware($service);

        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    /** @test */
    public function rtm_sample_02_zero_sample_rate_tracks_nothing(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.sample_rate', 0.0);

        $middleware = new RequestTrackingMiddleware($service);

        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    /** @test */
    public function rtm_sample_03_50_percent_sample_rate_tracks_approximately_half(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->between(3, 7);

        Config::set('request_tracking.sample_rate', 0.5);

        $middleware = new RequestTrackingMiddleware($service);

        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    /** @test */
    public function rtm_sample_04_endpoint_specific_sample_rate(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->times(10);

        Config::set('request_tracking.sample_rate', 0.5);
        Config::set('request_tracking.endpoint_sample_rates', [
            'api/v1/high-traffic' => 1.0, // Override to 100%
        ]);

        $middleware = new RequestTrackingMiddleware($service);

        // High traffic endpoint with 100% sample rate
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest('/api/v1/high-traffic', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    /** @test */
    public function rtm_sample_05_sample_rate_boundary_at_threshold(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        Config::set('request_tracking.sample_rate', 0.1);

        $middleware = new RequestTrackingMiddleware($service);

        // Run enough requests that 10% should hit at least once
        for ($i = 0; $i < 100; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    // =========================================================================
    // Error Handling (RTM-ERROR-*)
    // =========================================================================

    /** @test */
    public function rtm_error_01_continues_on_tracking_exception(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \Exception('Tracking failed'));

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        // Should not throw exception
        $response = $middleware->handle($request, fn($req) => new Response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_error_02_handles_redis_connection_failure(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_error_03_handles_queue_failure_gracefully(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \RuntimeException('Queue unavailable'));

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_error_04_logs_error_without_exposing_to_user(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \Exception('Tracking error'));

        \Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Request tracking failed');
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('Tracking error', $response->getContent());
    }

    /** @test */
    public function rtm_error_05_error_response_not_exposed_to_client(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \Exception('Sensitive error details'));

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        // No error information in response
        $this->assertStringNotContainsString('Sensitive', $response->getContent());
        $this->assertStringNotContainsString('Exception', $response->getContent());
    }

    // =========================================================================
    // Performance Tests (RTM-PERF-*)
    // =========================================================================

    /** @test */
    public function rtm_perf_01_tracking_overhead_under_5ms(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->times(100);

        $middleware = new RequestTrackingMiddleware($service);

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms
        $avgOverhead = $elapsed / 100;

        $this->assertLessThan(5, $avgOverhead, "Average overhead per request: {$avgOverhead}ms");
    }

    /** @test */
    public function rtm_perf_02_excluded_paths_no_overhead(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldNotReceive('track');

        Config::set('request_tracking.excluded_paths', ['health/*']);

        $middleware = new RequestTrackingMiddleware($service);

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $request = $this->createRequest('/health/check', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgOverhead = $elapsed / 100;

        // Excluded paths should have minimal overhead (< 0.5ms)
        $this->assertLessThan(0.5, $avgOverhead, "Excluded path overhead: {$avgOverhead}ms");
    }

    /** @test */
    public function rtm_perf_03_no_memory_leak_on_repeated_requests(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track');

        $middleware = new RequestTrackingMiddleware($service);

        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 1000; $i++) {
            $request = $this->createRequest('/api/v1/test', 'GET');
            $middleware->handle($request, fn($req) => new Response('OK'));
        }

        $finalMemory = memory_get_usage();
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024; // KB

        // Memory growth should be minimal (< 100KB for 1000 requests)
        $this->assertLessThan(100, $memoryGrowth, "Memory growth: {$memoryGrowth}KB");
    }

    // =========================================================================
    // Edge Cases (RTM-EDGE-*)
    // =========================================================================

    /** @test */
    public function rtm_edge_01_handles_very_long_url(): void
    {
        $longPath = str_repeat('/segment', 100);
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) use ($longPath) {
                return str_starts_with($data['endpoint'], '/segment');
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest($longPath, 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_edge_02_handles_unicode_in_path(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return str_contains($data['endpoint'], 'café');
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/café/test', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_edge_03_handles_query_parameters(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                // Query params should be stripped or normalized
                return $data['endpoint'] === '/api/v1/search' ||
                       str_starts_with($data['endpoint'], '/api/v1/search?');
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/api/v1/search?q=test&page=1', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_edge_04_handles_empty_path(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['endpoint'] === '/' || $data['endpoint'] === '';
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_edge_05_handles_special_characters_in_path(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/api/v1/test%20space/encoded', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_edge_06_concurrent_requests_tracked_separately(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->times(3);

        $middleware = new RequestTrackingMiddleware($service);

        $requests = [
            $this->createRequest('/api/v1/endpoint-1', 'GET'),
            $this->createRequest('/api/v1/endpoint-2', 'POST'),
            $this->createRequest('/api/v1/endpoint-3', 'DELETE'),
        ];

        foreach ($requests as $request) {
            $middleware->handle($request, fn($req) => new Response('OK'));
        }
    }

    // =========================================================================
    // Security Tests (RTM-SEC-*)
    // =========================================================================

    /** @test */
    public function rtm_sec_01_no_sensitive_data_in_tracking(): void
    {
        $user = $this->createUser();
        $user->email = 'sensitive@example.com';

        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                // Should not contain sensitive user data
                $serialized = json_encode($data);
                return !str_contains($serialized, 'sensitive@example.com') &&
                       !str_contains($serialized, 'password') &&
                       !str_contains($serialized, 'token');
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest('/api/v1/analysis', 'GET', $user);

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_sec_02_path_traversal_attempt_safe(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/api/v1/../../../etc/passwd', 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_sec_03_sql_injection_in_path_safe(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create("/api/v1/users?id=1' OR '1'='1", 'GET');

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_sec_04_no_request_body_logged(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                // Only endpoint, method, user_id, timestamp
                $keys = array_keys($data);
                sort($keys);
                $expectedKeys = ['endpoint', 'method', 'timestamp', 'user_id'];
                sort($expectedKeys);
                return $keys === $expectedKeys;
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/api/v1/users', 'POST', [
            'password' => 'secret123',
            'credit_card' => '4111-1111-1111-1111',
        ]);

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    /** @test */
    public function rtm_sec_05_headers_not_logged(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                // Should not contain headers
                return !isset($data['headers']) &&
                       !isset($data['authorization']) &&
                       !isset($data['cookie']);
            });

        $middleware = new RequestTrackingMiddleware($service);
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Authorization', 'Bearer secret-token');
        $request->headers->set('Cookie', 'session=abc123');

        $middleware->handle($request, fn($req) => new Response('OK'));
    }

    // =========================================================================
    // Integration with Response (RTM-RESP-*)
    // =========================================================================

    /** @test */
    public function rtm_resp_01_tracks_on_successful_response(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rtm_resp_02_tracks_on_error_response(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('Not Found', 404));

        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function rtm_resp_03_tracks_on_exception_response(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, function ($req) {
            throw new \Exception('Test exception');
        });

        // Exception handling is done by Laravel, but tracking should still occur
        $this->assertTrue(true);
    }

    /** @test */
    public function rtm_resp_04_does_not_add_tracking_headers_to_response(): void
    {
        $service = $this->createMockTrackingService();
        $service->shouldReceive('track')->once();

        $middleware = new RequestTrackingMiddleware($service);
        $request = $this->createRequest();

        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        // Tracking middleware should not modify response headers
        $this->assertFalse($response->headers->has('X-Tracked'));
        $this->assertFalse($response->headers->has('X-Request-ID'));
    }
}
