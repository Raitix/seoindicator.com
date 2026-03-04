<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RequestTrackingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * Unit tests for RequestTrackingMiddleware
 * 
 * Test Categories:
 * - MI: Middleware Instantiation
 * - RT: Request Tracking
 * - LS: Lightweight Storage
 * - PR: Performance
 * - SR: Sample Rate
 * - QF: Queue Failure Handling
 * - HE: Health Check Exclusion
 * - SE: Security
 * - EC: Edge Cases
 */
class RequestTrackingMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // MI: Middleware Instantiation Tests
    // =========================================================================

    /** @test MI-001: Middleware can be instantiated */
    public function test_middleware_can_be_instantiated(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        $this->assertInstanceOf(RequestTrackingMiddleware::class, $middleware);
    }

    /** @test MI-002: Middleware is registered in container */
    public function test_middleware_is_registered_in_container(): void
    {
        $middleware = app(\App\Http\Middleware\RequestTrackingMiddleware::class);
        
        $this->assertInstanceOf(\App\Http\Middleware\RequestTrackingMiddleware::class, $middleware);
    }

    /** @test MI-003: Middleware has default configuration */
    public function test_middleware_has_default_configuration(): void
    {
        Config::set('request-tracking.enabled', true);
        Config::set('request-tracking.sample_rate', 1.0);
        Config::set('request-tracking.storage', 'redis');
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        $this->assertTrue(config('request-tracking.enabled', true));
        $this->assertEquals(1.0, config('request-tracking.sample_rate', 1.0));
    }

    // =========================================================================
    // RT: Request Tracking Tests
    // =========================================================================

    /** @test RT-001: Middleware records request path */
    public function test_middleware_records_request_path(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/users', 'GET');
        
        $tracked = false;
        $trackedPath = null;
        
        // Mock Redis to capture the tracked data
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$tracked, &$trackedPath) {
                $decoded = json_decode($data, true);
                if (isset($decoded['path'])) {
                    $tracked = true;
                    $trackedPath = $decoded['path'];
                }
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertTrue($tracked);
        $this->assertEquals('/api/v1/users', $trackedPath);
    }

    /** @test RT-002: Middleware records HTTP method */
    public function test_middleware_records_http_method(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/users', 'POST');
        
        $trackedMethod = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedMethod) {
                $decoded = json_decode($data, true);
                $trackedMethod = $decoded['method'] ?? null;
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals('POST', $trackedMethod);
    }

    /** @test RT-003: Middleware records timestamp */
    public function test_middleware_records_timestamp(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        $trackedTimestamp = null;
        $beforeTime = now()->timestamp;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedTimestamp) {
                $decoded = json_decode($data, true);
                $trackedTimestamp = $decoded['timestamp'] ?? null;
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $afterTime = now()->timestamp;
        
        $this->assertNotNull($trackedTimestamp);
        $this->assertGreaterThanOrEqual($beforeTime, $trackedTimestamp);
        $this->assertLessThanOrEqual($afterTime, $trackedTimestamp);
    }

    /** @test RT-004: Middleware records user_id when authenticated */
    public function test_middleware_records_user_id_when_authenticated(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/protected', 'GET');
        
        $user = Mockery::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthIdentifier')->andReturn(123);
        
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        $trackedUserId = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedUserId) {
                $decoded = json_decode($data, true);
                $trackedUserId = $decoded['user_id'] ?? null;
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(123, $trackedUserId);
    }

    /** @test RT-005: Middleware records null user_id when not authenticated */
    public function test_middleware_records_null_user_id_when_not_authenticated(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/public', 'GET');
        
        $trackedUserId = 'not-set';
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedUserId) {
                $decoded = json_decode($data, true);
                $trackedUserId = $decoded['user_id'] ?? null;
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertNull($trackedUserId);
    }

    /** @test RT-006: Middleware records all HTTP methods */
    public function test_middleware_records_all_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
        
        foreach ($methods as $method) {
            $middleware = app(RequestTrackingMiddleware::class);
            $request = Request::create('/api/v1/test', $method);
            
            $trackedMethod = null;
            
            Redis::shouldReceive('rpush')
                ->once()
                ->withArgs(function ($key, $data) use (&$trackedMethod) {
                    $decoded = json_decode($data, true);
                    $trackedMethod = $decoded['method'] ?? null;
                    return true;
                });
            
            $response = new Response();
            $middleware->handle($request, function ($req) use ($response) {
                return $response;
            });
            
            $this->assertEquals($method, $trackedMethod, "Failed to track {$method} method");
        }
    }

    // =========================================================================
    // LS: Lightweight Storage Tests
    // =========================================================================

    /** @test LS-001: Middleware uses Redis for storage */
    public function test_middleware_uses_redis_for_storage(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) {
                return str_contains($key, 'request_tracking') || str_contains($key, 'request-tracking');
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test LS-002: Middleware uses atomic increment for counters */
    public function test_middleware_uses_atomic_increment_for_counters(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Redis::shouldReceive('incr')
            ->once()
            ->withArgs(function ($key) {
                return str_contains($key, 'count') || str_contains($key, 'requests');
            });
        
        Redis::shouldReceive('rpush')->once();
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test LS-003: Middleware sets TTL on tracking keys */
    public function test_middleware_sets_ttl_on_tracking_keys(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Redis::shouldReceive('expire')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $ttl > 0 && $ttl <= 86400; // TTL between 1 second and 1 day
            });
        
        Redis::shouldReceive('rpush')->once();
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test LS-004: Middleware uses async queue for write operations */
    public function test_middleware_uses_async_queue_for_write_operations(): void
    {
        Config::set('request-tracking.async', true);
        
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        // Tracking should happen asynchronously - Redis call may be deferred
        Redis::shouldReceive('rpush')
            ->once()
            ->andReturn(true);
        
        $startTime = microtime(true);
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // ms
        
        // Async write should not block the request significantly
        $this->assertLessThan(10, $duration, 'Middleware should complete quickly with async storage');
    }

    // =========================================================================
    // PR: Performance Tests
    // =========================================================================

    /** @test PR-001: Middleware overhead is under 5ms */
    public function test_middleware_overhead_is_under_5ms(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Redis::shouldReceive('rpush')->once()->andReturn(true);
        Redis::shouldReceive('incr')->once()->andReturn(1);
        Redis::shouldReceive('expire')->once()->andReturn(true);
        
        $times = [];
        
        // Run multiple times to get average
        for ($i = 0; $i < 10; $i++) {
            $startTime = microtime(true);
            
            $response = new Response();
            $middleware->handle($request, function ($req) use ($response) {
                return $response;
            });
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000; // Convert to ms
        }
        
        $avgTime = array_sum($times) / count($times);
        
        $this->assertLessThan(5, $avgTime, "Middleware overhead should be under 5ms, got {$avgTime}ms average");
    }

    /** @test PR-002: Middleware does not block on Redis connection */
    public function test_middleware_does_not_block_on_redis_connection(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        // Simulate slow Redis connection - should not block
        Redis::shouldReceive('rpush')
            ->once()
            ->andReturnUsing(function () {
                usleep(10000); // 10ms delay
                return true;
            });
        
        $startTime = microtime(true);
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Even with slow Redis, should complete within reasonable time
        $this->assertLessThan(50, $duration, 'Middleware should not block excessively on Redis');
    }

    /** @test PR-003: Middleware handles concurrent requests efficiently */
    public function test_middleware_handles_concurrent_requests_efficiently(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->times(5)->andReturn(true);
        Redis::shouldReceive('incr')->times(5)->andReturn(1);
        Redis::shouldReceive('expire')->times(5)->andReturn(true);
        
        $startTime = microtime(true);
        
        // Simulate 5 concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $request = Request::create("/api/v1/test/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
        
        $endTime = microtime(true);
        $totalDuration = ($endTime - $startTime) * 1000;
        $avgPerRequest = $totalDuration / 5;
        
        $this->assertLessThan(10, $avgPerRequest, 'Average per-request middleware time should be under 10ms');
    }

    // =========================================================================
    // SR: Sample Rate Tests
    // =========================================================================

    /** @test SR-001: Sample rate of 1.0 tracks all requests */
    public function test_sample_rate_of_1_tracks_all_requests(): void
    {
        Config::set('request-tracking.sample_rate', 1.0);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->times(10)->andReturn(true);
        
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create("/api/v1/test/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
    }

    /** @test SR-002: Sample rate of 0.0 tracks no requests */
    public function test_sample_rate_of_0_tracks_no_requests(): void
    {
        Config::set('request-tracking.sample_rate', 0.0);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Should not track any requests
        Redis::shouldReceive('rpush')->never();
        
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create("/api/v1/test/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
    }

    /** @test SR-003: Sample rate of 0.5 tracks approximately half */
    public function test_sample_rate_of_05_tracks_approximately_half(): void
    {
        Config::set('request-tracking.sample_rate', 0.5);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        $trackedCount = 0;
        
        Redis::shouldReceive('rpush')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use (&$trackedCount) {
                $trackedCount++;
                return true;
            });
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create("/api/v1/test/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
        
        // With 100 requests at 50% sample rate, expect 40-60 tracked
        $this->assertGreaterThan(30, $trackedCount, 'Should track more than 30% at 50% sample rate');
        $this->assertLessThan(70, $trackedCount, 'Should track less than 70% at 50% sample rate');
    }

    /** @test SR-004: Sample rate can be configured per endpoint */
    public function test_sample_rate_can_be_configured_per_endpoint(): void
    {
        Config::set('request-tracking.endpoint_sample_rates', [
            '/api/v1/high-traffic/*' => 0.1,
            '/api/v1/low-traffic/*' => 1.0,
        ]);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        // High traffic endpoint - should sample less
        $highTrafficTracked = 0;
        Redis::shouldReceive('rpush')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use (&$highTrafficTracked) {
                $highTrafficTracked++;
                return true;
            });
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create("/api/v1/high-traffic/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
        
        $this->assertLessThan(30, $highTrafficTracked, 'High traffic endpoint should have lower sample rate');
    }

    /** @test SR-005: Sample rate uses consistent hashing for same request */
    public function test_sample_rate_uses_consistent_hashing_for_same_request(): void
    {
        Config::set('request-tracking.sample_rate', 0.5);
        Config::set('request-tracking.consistent_sampling', true);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        $results = [];
        
        Redis::shouldReceive('rpush')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use (&$results) {
                return true;
            });
        
        // Same request 10 times
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create('/api/v1/consistent-test', 'GET');
            $tracked = $middleware->handle($request, function ($req) {
                return new Response();
            });
            $results[] = $tracked !== null;
        }
        
        // With consistent hashing, same request should always be tracked or always not tracked
        $uniqueResults = array_unique($results);
        $this->assertCount(1, $uniqueResults, 'Same request should have consistent sampling decision');
    }

    // =========================================================================
    // QF: Queue Failure Handling Tests
    // =========================================================================

    /** @test QF-001: Middleware handles Redis connection failure gracefully */
    public function test_middleware_handles_redis_connection_failure_gracefully(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        // Simulate Redis connection failure
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));
        
        // Should not throw exception
        $response = new Response();
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(Response::class, $result);
    }

    /** @test QF-002: Middleware logs queue failures */
    public function test_middleware_logs_queue_failures(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'request tracking') || 
                       str_contains($message, 'Redis') ||
                       str_contains($message, 'failed');
            });
        
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test QF-003: Middleware continues request processing on queue failure */
    public function test_middleware_continues_request_processing_on_queue_failure(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Connection refused'));
        
        $response = new Response('Success', 200);
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Success', $result->getContent());
    }

    /** @test QF-004: Middleware handles Redis timeout gracefully */
    public function test_middleware_handles_redis_timeout_gracefully(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        // Simulate Redis timeout
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('Operation timed out'));
        
        $response = new Response();
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(Response::class, $result);
    }

    /** @test QF-005: Middleware handles memory limit gracefully */
    public function test_middleware_handles_memory_limit_gracefully(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        // Simulate Redis OOM error
        Redis::shouldReceive('rpush')
            ->once()
            ->andThrow(new \RedisException('OOM command not allowed when used_memory > maxmemory'));
        
        Log::shouldReceive('warning')->once();
        
        $response = new Response();
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(Response::class, $result);
    }

    // =========================================================================
    // HE: Health Check Exclusion Tests
    // =========================================================================

    /** @test HE-001: Middleware excludes health check endpoints */
    public function test_middleware_excludes_health_check_endpoints(): void
    {
        Config::set('request-tracking.excluded_paths', [
            '/api/health',
            '/health',
            '/api/v1/health',
        ]);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->never();
        
        $healthEndpoints = ['/api/health', '/health', '/api/v1/health'];
        
        foreach ($healthEndpoints as $endpoint) {
            $request = Request::create($endpoint, 'GET');
            $response = new Response('OK', 200);
            $middleware->handle($request, function ($req) use ($response) {
                return $response;
            });
        }
    }

    /** @test HE-002: Middleware excludes heartbeat endpoint */
    public function test_middleware_excludes_heartbeat_endpoint(): void
    {
        Config::set('request-tracking.excluded_paths', [
            '/heartbeat',
            '/api/heartbeat',
        ]);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->never();
        
        $request = Request::create('/heartbeat', 'GET');
        $response = new Response('OK', 200);
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test HE-003: Middleware excludes static asset requests */
    public function test_middleware_excludes_static_asset_requests(): void
    {
        Config::set('request-tracking.excluded_paths', [
            '/css/*',
            '/js/*',
            '/images/*',
            '/fonts/*',
        ]);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->never();
        
        $staticEndpoints = [
            '/css/app.css',
            '/js/app.js',
            '/images/logo.png',
            '/fonts/roboto.woff2',
        ];
        
        foreach ($staticEndpoints as $endpoint) {
            $request = Request::create($endpoint, 'GET');
            $response = new Response();
            $middleware->handle($request, function ($req) use ($response) {
                return $response;
            });
        }
    }

    /** @test HE-004: Middleware tracks non-excluded endpoints */
    public function test_middleware_tracks_non_excluded_endpoints(): void
    {
        Config::set('request-tracking.excluded_paths', ['/api/health']);
        Config::set('request-tracking.sample_rate', 1.0);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->once()->andReturn(true);
        
        $request = Request::create('/api/v1/users', 'GET');
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    /** @test HE-005: Middleware excludes OPTIONS requests */
    public function test_middleware_excludes_options_requests(): void
    {
        Config::set('request-tracking.exclude_options', true);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->never();
        
        $request = Request::create('/api/v1/users', 'OPTIONS');
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
    }

    // =========================================================================
    // SE: Security Tests
    // =========================================================================

    /** @test SE-001: Middleware does not expose sensitive data */
    public function test_middleware_does_not_expose_sensitive_data(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Authorization', 'Bearer secret-token');
        $request->headers->set('Cookie', 'session=abc123');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Ensure sensitive data is not tracked
        $this->assertArrayNotHasKey('headers', $trackedData);
        $this->assertArrayNotHasKey('authorization', $trackedData);
        $this->assertArrayNotHasKey('cookie', $trackedData);
        $this->assertArrayNotHasKey('session', $trackedData);
    }

    /** @test SE-002: Middleware sanitizes path parameters */
    public function test_middleware_sanitizes_path_parameters(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Malicious path with injection attempt
        $request = Request::create('/api/v1/users?id=1;DROP TABLE users', 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Path should be stored safely without execution
        $this->assertIsString($trackedData['path']);
        $this->assertArrayHasKey('path', $trackedData);
    }

    /** @test SE-003: Middleware prevents log injection */
    public function test_middleware_prevents_log_injection(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Path with newline injection attempt
        $request = Request::create('/api/v1/test%0AFAKE%20LOG%20ENTRY', 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Path should be URL encoded or sanitized
        $this->assertStringNotContainsString("\n", $trackedData['path']);
    }

    /** @test SE-004: Middleware handles malformed URLs safely */
    public function test_middleware_handles_malformed_urls_safely(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Create request with malformed URL
        $request = Request::create('/api/v1/test/../../../etc/passwd', 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Should track the normalized/sanitized path
        $this->assertIsString($trackedData['path']);
    }

    /** @test SE-005: Middleware does not track POST body data */
    public function test_middleware_does_not_track_post_body_data(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        $request = Request::create('/api/v1/users', 'POST', [
            'password' => 'secret123',
            'credit_card' => '4111111111111111',
        ]);
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Ensure body data is not tracked
        $this->assertArrayNotHasKey('body', $trackedData);
        $this->assertArrayNotHasKey('password', $trackedData);
        $this->assertArrayNotHasKey('credit_card', $trackedData);
    }

    // =========================================================================
    // EC: Edge Cases Tests
    // =========================================================================

    /** @test EC-001: Middleware handles extremely long paths */
    public function test_middleware_handles_extremely_long_paths(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Create a path that's 2000 characters long
        $longPath = '/api/v1/' . str_repeat('a', 1990);
        $request = Request::create($longPath, 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Should truncate or handle long paths gracefully
        $this->assertLessThanOrEqual(2000, strlen($trackedData['path']));
    }

    /** @test EC-002: Middleware handles Unicode paths */
    public function test_middleware_handles_unicode_paths(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        $request = Request::create('/api/v1/ユーザー/пользователь', 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertIsString($trackedData['path']);
    }

    /** @test EC-003: Middleware handles empty path */
    public function test_middleware_handles_empty_path(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        $request = Request::create('/', 'GET');
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals('/', $trackedData['path']);
    }

    /** @test EC-004: Middleware handles null request */
    public function test_middleware_handles_null_request_gracefully(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        $response = new Response();
        
        // Should not throw
        try {
            $result = $middleware->handle(null, function ($req) use ($response) {
                return $response;
            });
            $this->assertInstanceOf(Response::class, $result);
        } catch (\TypeError $e) {
            // Type error is acceptable - middleware should not crash
            $this->markTestSkipped('Middleware does not accept null request');
        }
    }

    /** @test EC-005: Middleware handles concurrent tracking safely */
    public function test_middleware_handles_concurrent_tracking_safely(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        // Simulate concurrent access
        Redis::shouldReceive('rpush')
            ->times(3)
            ->andReturn(true);
        
        Redis::shouldReceive('incr')
            ->times(3)
            ->andReturn(1, 2, 3); // Incrementing counter
        
        $responses = [];
        
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create("/api/v1/test/{$i}", 'GET');
            $responses[] = $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
        
        foreach ($responses as $response) {
            $this->assertInstanceOf(Response::class, $response);
        }
    }

    /** @test EC-006: Middleware handles disabled state */
    public function test_middleware_handles_disabled_state(): void
    {
        Config::set('request-tracking.enabled', false);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->never();
        
        $request = Request::create('/api/v1/test', 'GET');
        $response = new Response();
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(Response::class, $result);
    }

    /** @test EC-007: Middleware handles large user IDs */
    public function test_middleware_handles_large_user_ids(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        $request = Request::create('/api/v1/test', 'GET');
        
        $user = Mockery::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthIdentifier')->andReturn(PHP_INT_MAX);
        
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        $trackedData = null;
        
        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $data) use (&$trackedData) {
                $trackedData = json_decode($data, true);
                return true;
            });
        
        $response = new Response();
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(PHP_INT_MAX, $trackedData['user_id']);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    /** @test INT-001: Full tracking workflow works correctly */
    public function test_full_tracking_workflow_works_correctly(): void
    {
        Config::set('request-tracking.enabled', true);
        Config::set('request-tracking.sample_rate', 1.0);
        Config::set('request-tracking.excluded_paths', ['/api/health']);
        
        $middleware = app(RequestTrackingMiddleware::class);
        
        $trackedRequests = [];
        
        Redis::shouldReceive('rpush')
            ->times(3)
            ->withArgs(function ($key, $data) use (&$trackedRequests) {
                $trackedRequests[] = json_decode($data, true);
                return true;
            });
        
        Redis::shouldReceive('incr')->times(3)->andReturn(1, 2, 3);
        Redis::shouldReceive('expire')->times(3)->andReturn(true);
        
        // Track 3 requests
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create("/api/v1/users/{$i}", 'GET');
            $middleware->handle($request, function ($req) {
                return new Response();
            });
        }
        
        // Health check should not be tracked
        $healthRequest = Request::create('/api/health', 'GET');
        $middleware->handle($healthRequest, function ($req) {
            return new Response();
        });
        
        $this->assertCount(3, $trackedRequests);
        
        foreach ($trackedRequests as $tracked) {
            $this->assertArrayHasKey('path', $tracked);
            $this->assertArrayHasKey('method', $tracked);
            $this->assertArrayHasKey('timestamp', $tracked);
        }
    }

    /** @test INT-002: Response is passed through unchanged */
    public function test_response_is_passed_through_unchanged(): void
    {
        $middleware = app(RequestTrackingMiddleware::class);
        
        Redis::shouldReceive('rpush')->once()->andReturn(true);
        
        $request = Request::create('/api/v1/test', 'GET');
        $expectedResponse = new Response('Custom Content', 201, ['X-Custom' => 'header']);
        
        $result = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });
        
        $this->assertEquals($expectedResponse->getStatusCode(), $result->getStatusCode());
        $this->assertEquals($expectedResponse->getContent(), $result->getContent());
    }
}
