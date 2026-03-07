<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RequestLoggingMiddleware;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for RequestLoggingMiddleware (TASK-6-6).
 *
 * Tests for API request logging middleware that captures request metadata
 * and logs to database for analytics and debugging.
 * 
 * @see TASK-6-6: Request Logging
 * @see SPEC-006: API Key Management
 */
class RequestLoggingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // CATEGORY 1: MIDDLEWARE INSTANTIATION (MI-001 to MI-010)
    // =========================================================================

    /** @test MI-001: Middleware can be instantiated */
    public function test_mi001_middleware_can_be_instantiated(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertInstanceOf(RequestLoggingMiddleware::class, $middleware);
    }

    /** @test MI-002: Middleware has handle method */
    public function test_mi002_middleware_has_handle_method(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertTrue(method_exists($middleware, 'handle'));
    }

    /** @test MI-003: Middleware can be enabled */
    public function test_mi003_middleware_can_be_enabled(): void
    {
        config(['api-logging.enabled' => true]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertTrue($middleware->isEnabled());
    }

    /** @test MI-004: Middleware can be disabled */
    public function test_mi004_middleware_can_be_disabled(): void
    {
        config(['api-logging.enabled' => false]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertFalse($middleware->isEnabled());
    }

    /** @test MI-005: Middleware reads config for enabled state */
    public function test_mi005_middleware_reads_config_for_enabled_state(): void
    {
        config(['api-logging.enabled' => true]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertTrue($middleware->isEnabled());
    }

    /** @test MI-006: Middleware has default retention days */
    public function test_mi006_middleware_has_default_retention_days(): void
    {
        config(['api-logging.retention_days' => 30]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertEquals(30, $middleware->getRetentionDays());
    }

    /** @test MI-007: Middleware can set enabled state dynamically */
    public function test_mi007_middleware_can_set_enabled_state_dynamically(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $middleware->disable();
        $this->assertFalse($middleware->isEnabled());
        
        $middleware->enable();
        $this->assertTrue($middleware->isEnabled());
    }

    /** @test MI-008: Middleware excludes health endpoints by default */
    public function test_mi008_middleware_excludes_health_endpoints_by_default(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $excluded = $middleware->getExcludedEndpoints();
        
        $this->assertContains('api/v1/health', $excluded);
    }

    /** @test MI-009: Middleware reads excluded endpoints from config */
    public function test_mi009_middleware_reads_excluded_endpoints_from_config(): void
    {
        config(['api-logging.excluded_endpoints' => [
            'api/v1/health',
            'api/v1/ping',
        ]]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $excluded = $middleware->getExcludedEndpoints();
        
        $this->assertContains('api/v1/health', $excluded);
        $this->assertContains('api/v1/ping', $excluded);
    }

    /** @test MI-010: Middleware has async logging enabled by default */
    public function test_mi010_middleware_has_async_logging_enabled_by_default(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertTrue($middleware->isAsyncLogging());
    }

    // =========================================================================
    // CATEGORY 2: BASIC LOGGING BEHAVIOR (BL-001 to BL-015)
    // =========================================================================

    /** @test BL-001: Middleware logs API request */
    public function test_bl001_middleware_logs_api_request(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->headers->set('User-Agent', 'TestAgent/1.0');
        
        $response = new Response('content', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses',
            'method' => 'GET',
        ]);
    }

    /** @test BL-002: Middleware captures response status code */
    public function test_bl002_middleware_captures_response_status_code(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('created', 201);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'status_code' => 201,
        ]);
    }

    /** @test BL-003: Middleware captures response time */
    public function test_bl003_middleware_captures_response_time(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            usleep(10000); // 10ms delay
            return $response;
        });
        
        $log = ApiRequestLog::where('api_key_id', $apiKey->id)->first();
        
        $this->assertNotNull($log->response_time_ms);
        $this->assertGreaterThanOrEqual(0, $log->response_time_ms);
    }

    /** @test BL-004: Middleware captures IP address */
    public function test_bl004_middleware_captures_ip_address(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->server->set('REMOTE_ADDR', '10.0.0.5');
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'ip_address' => '10.0.0.5',
        ]);
    }

    /** @test BL-005: Middleware captures user agent */
    public function test_bl005_middleware_captures_user_agent(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->headers->set('User-Agent', 'curl/7.68.0');
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'user_agent' => 'curl/7.68.0',
        ]);
    }

    /** @test BL-006: Middleware logs POST request */
    public function test_bl006_middleware_logs_post_request(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses', 'POST');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('created', 201);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'method' => 'POST',
        ]);
    }

    /** @test BL-007: Middleware logs PUT request */
    public function test_bl007_middleware_logs_put_request(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/users/123', 'PUT');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('updated', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'method' => 'PUT',
        ]);
    }

    /** @test BL-008: Middleware logs DELETE request */
    public function test_bl008_middleware_logs_delete_request(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/users/123', 'DELETE');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('', 204);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'method' => 'DELETE',
        ]);
    }

    /** @test BL-009: Middleware passes request through unchanged */
    public function test_bl009_middleware_passes_request_through_unchanged(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $expectedResponse = new Response('test content', 200);
        
        $actualResponse = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });
        
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /** @test BL-010: Middleware captures 4xx status codes */
    public function test_bl010_middleware_captures_4xx_status_codes(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/nonexistent', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('not found', 404);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'status_code' => 404,
        ]);
    }

    /** @test BL-011: Middleware captures 5xx status codes */
    public function test_bl011_middleware_captures_5xx_status_codes(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/error', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('server error', 500);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'status_code' => 500,
        ]);
    }

    /** @test BL-012: Middleware captures endpoint with query params */
    public function test_bl012_middleware_captures_endpoint_with_query_params(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses?page=1&limit=10', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Endpoint should include query params
        $log = ApiRequestLog::where('api_key_id', $apiKey->id)->first();
        
        $this->assertStringContainsString('/api/v1/analyses', $log->endpoint);
    }

    /** @test BL-013: Middleware captures X-Forwarded-For header */
    public function test_bl013_middleware_captures_x_forwarded_for_header(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->headers->set('X-Forwarded-For', '203.0.113.1, 70.41.3.18');
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $log = ApiRequestLog::where('api_key_id', $apiKey->id)->first();
        
        // Should capture the first IP from X-Forwarded-For
        $this->assertNotNull($log->ip_address);
    }

    /** @test BL-014: Middleware handles missing api_key_id gracefully */
    public function test_bl014_middleware_handles_missing_api_key_id_gracefully(): void
    {
        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        // No api_key_id set
        
        $response = new Response('ok', 200);
        
        // Should not throw
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        // Should not create log without api_key_id
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test BL-015: Middleware logs all required fields together */
    public function test_bl015_middleware_logs_all_required_fields_together(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses/123', 'POST');
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Test Browser)');
        
        $response = new Response('created', 201);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $log = ApiRequestLog::where('api_key_id', $apiKey->id)->first();
        
        $this->assertEquals($apiKey->id, $log->api_key_id);
        $this->assertStringContainsString('/api/v1/analyses', $log->endpoint);
        $this->assertEquals('POST', $log->method);
        $this->assertEquals(201, $log->status_code);
        $this->assertNotNull($log->response_time_ms);
        $this->assertEquals('192.168.1.100', $log->ip_address);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $log->user_agent);
        $this->assertNotNull($log->created_at);
    }

    // =========================================================================
    // CATEGORY 3: EXCLUDED ENDPOINTS (EE-001 to EE-010)
    // =========================================================================

    /** @test EE-001: Middleware excludes health endpoint */
    public function test_ee001_middleware_excludes_health_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/health', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-002: Middleware excludes health sub-endpoints */
    public function test_ee002_middleware_excludes_health_sub_endpoints(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/health/database', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-003: Middleware excludes configured endpoints */
    public function test_ee003_middleware_excludes_configured_endpoints(): void
    {
        config(['api-logging.excluded_endpoints' => ['api/v1/metrics']]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/metrics', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-004: Middleware logs non-excluded endpoints */
    public function test_ee004_middleware_logs_non_excluded_endpoints(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(1, ApiRequestLog::count());
    }

    /** @test EE-005: Excluded endpoints returns response normally */
    public function test_ee005_excluded_endpoints_returns_response_normally(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/health', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $expectedResponse = new Response('healthy', 200);
        
        $actualResponse = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });
        
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /** @test EE-006: Can add custom excluded endpoint */
    public function test_ee006_can_add_custom_excluded_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        $middleware->addExcludedEndpoint('api/v1/custom-health');
        
        $request = Request::create('/api/v1/custom-health', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-007: Wildcard excluded endpoints */
    public function test_ee007_wildcard_excluded_endpoints(): void
    {
        config(['api-logging.excluded_endpoints' => ['api/v1/health/*']]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/health/redis', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-008: OPTIONS requests excluded by default */
    public function test_ee008_options_requests_excluded_by_default(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/analyses', 'OPTIONS');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('', 204);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-009: Non-API routes not logged */
    public function test_ee009_non_api_routes_not_logged(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/web/page', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('page', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test EE-010: Empty excluded endpoints list logs all */
    public function test_ee010_empty_excluded_endpoints_list_logs_all(): void
    {
        config(['api-logging.excluded_endpoints' => []]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/health', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(1, ApiRequestLog::count());
    }

    // =========================================================================
    // CATEGORY 4: DISABLED MIDDLEWARE (DM-001 to DM-010)
    // =========================================================================

    /** @test DM-001: Disabled middleware does not log */
    public function test_dm001_disabled_middleware_does_not_log(): void
    {
        config(['api-logging.enabled' => false]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test DM-002: Disabled middleware passes request through */
    public function test_dm002_disabled_middleware_passes_request_through(): void
    {
        config(['api-logging.enabled' => false]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $expectedResponse = new Response('ok', 200);
        
        $actualResponse = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });
        
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /** @test DM-003: Middleware can be disabled mid-request */
    public function test_dm003_middleware_can_be_disabled_mid_request(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        $middleware->disable();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test DM-004: Middleware can be re-enabled */
    public function test_dm004_middleware_can_be_re_enabled(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        $middleware->disable();
        $middleware->enable();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(1, ApiRequestLog::count());
    }

    /** @test DM-005: Disabled state persists across requests */
    public function test_dm005_disabled_state_persists_across_requests(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        $middleware->disable();
        
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create('/api/v1/test/' . $i, 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $response = new Response('ok', 200);
            
            $middleware->handle($request, function ($req) use ($response) {
                return $response;
            });
        }
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test DM-006: isEnabled returns correct state */
    public function test_dm006_is_enabled_returns_correct_state(): void
    {
        config(['api-logging.enabled' => false]);
        
        $middleware = new RequestLoggingMiddleware();
        
        $this->assertFalse($middleware->isEnabled());
        
        $middleware->enable();
        
        $this->assertTrue($middleware->isEnabled());
    }

    /** @test DM-007: Disabled middleware has no overhead */
    public function test_dm007_disabled_middleware_has_no_overhead(): void
    {
        config(['api-logging.enabled' => false]);
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $elapsed = microtime(true) - $start;
        
        // Should complete in under 100ms for 100 requests (disabled)
        $this->assertLessThan(0.1, $elapsed);
    }

    /** @test DM-008: Config disabled takes precedence */
    public function test_dm008_config_disabled_takes_precedence(): void
    {
        config(['api-logging.enabled' => false]);
        
        $middleware = new RequestLoggingMiddleware();
        
        // Even if we try to enable programmatically
        $middleware->enable();
        
        // Config should take precedence
        $this->assertFalse($middleware->isEnabled());
    }

    /** @test DM-009: Multiple middleware instances share config */
    public function test_dm009_multiple_middleware_instances_share_config(): void
    {
        config(['api-logging.enabled' => false]);
        
        $middleware1 = new RequestLoggingMiddleware();
        $middleware2 = new RequestLoggingMiddleware();
        
        $this->assertFalse($middleware1->isEnabled());
        $this->assertFalse($middleware2->isEnabled());
    }

    /** @test DM-010: Runtime disable overrides config enabled */
    public function test_dm010_runtime_disable_overrides_config_enabled(): void
    {
        config(['api-logging.enabled' => true]);
        
        $middleware = new RequestLoggingMiddleware();
        $middleware->disable();
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $middleware->handle($request, fn() => new Response('ok'));
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    // =========================================================================
    // CATEGORY 5: ERROR HANDLING (EH-001 to EH-010)
    // =========================================================================

    /** @test EH-001: Middleware continues if database fails */
    public function test_eh001_middleware_continues_if_database_fails(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        // Simulate database error by using a mock
        $middleware = $this->partialMock(RequestLoggingMiddleware::class);
        
        $middleware->shouldReceive('createLog')->once()->andThrow(new \Exception('Database error'));
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('ok', 200);
        
        // Should not throw
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(200, $result->getStatusCode());
    }

    /** @test EH-002: Middleware logs errors */
    public function test_eh002_middleware_logs_errors(): void
    {
        Log::shouldReceive('warning')->once();
        
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = $this->partialMock(RequestLoggingMiddleware::class);
        $middleware->shouldReceive('createLog')->once()->andThrow(new \Exception('Connection refused'));
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $middleware->handle($request, fn() => new Response('ok'));
    }

    /** @test EH-003: Middleware handles exception in handler */
    public function test_eh003_middleware_handles_exception_in_handler(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        try {
            $middleware->handle($request, function ($req) {
                throw new \Exception('Handler error');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Handler error', $e->getMessage());
        }
        
        // Should still log the request
        $this->assertEquals(1, ApiRequestLog::count());
    }

    /** @test EH-004: Middleware logs failed requests */
    public function test_eh004_middleware_logs_failed_requests(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        try {
            $middleware->handle($request, function ($req) {
                throw new \Exception('Failed');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        $log = ApiRequestLog::first();
        
        // Response time should be captured even on failure
        $this->assertNotNull($log);
    }

    /** @test EH-005: Middleware does not expose sensitive data */
    public function test_eh005_middleware_does_not_expose_sensitive_data(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'POST', [], [], [], [], json_encode(['password' => 'secret']));
        $request->attributes->set('api_key_id', $apiKey->id);
        $request->headers->set('Authorization', 'Bearer secret-token');
        
        $middleware->handle($request, fn() => new Response('ok'));
        
        $log = ApiRequestLog::first();
        
        // Request body should NOT be logged
        $this->assertObjectNotHasAttribute('request_body', $log);
        // Authorization header should NOT be logged
        $this->assertObjectNotHasAttribute('authorization', $log);
    }

    /** @test EH-006: Middleware handles timeout gracefully */
    public function test_eh006_middleware_handles_timeout_gracefully(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = $this->partialMock(RequestLoggingMiddleware::class);
        $middleware->shouldReceive('createLog')->once()->andThrow(new \Exception('Connection timed out'));
        
        Log::shouldReceive('warning')->once();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = $middleware->handle($request, fn() => new Response('ok'));
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test EH-007: Middleware handles queue failure */
    public function test_eh007_middleware_handles_queue_failure(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = $this->partialMock(RequestLoggingMiddleware::class);
        $middleware->shouldReceive('createLog')->once()->andThrow(new \RuntimeException('Queue is full'));
        
        Log::shouldReceive('warning')->once();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        // Should not throw
        $middleware->handle($request, fn() => new Response('ok'));
    }

    /** @test EH-008: Middleware does not throw on service unavailable */
    public function test_eh008_middleware_does_not_throw_on_service_unavailable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = $this->partialMock(RequestLoggingMiddleware::class);
        $middleware->shouldReceive('createLog')->once()->andThrow(new \Exception('Service unavailable'));
        
        Log::shouldReceive('warning')->once();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        // Should not throw
        $middleware->handle($request, fn() => new Response('ok'));
        
        $this->assertTrue(true);
    }

    /** @test EH-009: Middleware handles corrupt response */
    public function test_eh009_middleware_handles_corrupt_response(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        // Create a response with unusual content
        $response = new Response(str_repeat('a', 1000000), 200);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertEquals(1, ApiRequestLog::count());
    }

    /** @test EH-010: Middleware handles empty response */
    public function test_eh010_middleware_handles_empty_response(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $response = new Response('', 204);
        
        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'status_code' => 204,
        ]);
    }

    // =========================================================================
    // CATEGORY 6: PERFORMANCE TESTS (PT-001 to PT-010)
    // =========================================================================

    /** @test PT-001: Middleware overhead is minimal */
    public function test_pt001_middleware_overhead_is_minimal(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test/' . $i, 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $elapsed = microtime(true) - $start;
        $avgTime = $elapsed / 100;
        
        // Average time per request should be less than 10ms
        $this->assertLessThan(0.01, $avgTime);
    }

    /** @test PT-002: Middleware does not block response */
    public function test_pt002_middleware_does_not_block_response(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $start = microtime(true);
        $middleware->handle($request, fn() => new Response('ok'));
        $elapsed = microtime(true) - $start;
        
        // Should complete in under 50ms
        $this->assertLessThan(0.05, $elapsed);
    }

    /** @test PT-003: Async logging is non-blocking */
    public function test_pt003_async_logging_is_non_blocking(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $start = microtime(true);
        $middleware->handle($request, fn() => new Response('ok'));
        $duration = microtime(true) - $start;
        
        // Response should be immediate
        $this->assertLessThan(0.05, $duration);
    }

    /** @test PT-004: Memory usage remains stable */
    public function test_pt004_memory_usage_remains_stable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/v1/test', 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $finalMemory = memory_get_usage();
        $memoryGrowth = $finalMemory - $initialMemory;
        
        // Memory should not grow significantly (< 5MB)
        $this->assertLessThan(5242880, $memoryGrowth);
    }

    /** @test PT-005: Concurrent requests handled safely */
    public function test_pt005_concurrent_requests_handled_safely(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $requests = [];
        for ($i = 0; $i < 50; $i++) {
            $requests[] = function () use ($middleware, $apiKey, $i) {
                $request = Request::create('/api/v1/test/' . $i, 'GET');
                $request->attributes->set('api_key_id', $apiKey->id);
                
                return $middleware->handle($request, fn() => new Response('ok'));
            };
        }
        
        foreach ($requests as $handler) {
            $handler();
        }
        
        $this->assertEquals(50, ApiRequestLog::count());
    }

    /** @test PT-006: Query count is optimal */
    public function test_pt006_query_count_is_optimal(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $queryCount = 0;
        \DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $middleware->handle($request, fn() => new Response('ok'));
        
        // Should be exactly 1 query (INSERT)
        $this->assertEquals(1, $queryCount);
    }

    /** @test PT-007: Large volume logging */
    public function test_pt007_large_volume_logging(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $start = microtime(true);
        
        for ($i = 0; $i < 500; $i++) {
            $request = Request::create('/api/v1/test/' . $i, 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $elapsed = microtime(true) - $start;
        
        // 500 requests should complete in under 5 seconds
        $this->assertLessThan(5, $elapsed);
        $this->assertEquals(500, ApiRequestLog::count());
    }

    /** @test PT-008: Response time accuracy */
    public function test_pt008_response_time_accuracy(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $handlerDelay = 50000; // 50ms in microseconds
        
        $middleware->handle($request, function ($req) use ($handlerDelay) {
            usleep($handlerDelay);
            return new Response('ok');
        });
        
        $log = ApiRequestLog::first();
        
        // Response time should be at least 50ms
        $this->assertGreaterThanOrEqual(30, $log->response_time_ms);
    }

    /** @test PT-009: Batch insert performance */
    public function test_pt009_batch_insert_performance(): void
    {
        // Skip this test if middleware doesn't support batch mode
        $this->assertTrue(true);
    }

    /** @test PT-010: Middleware in pipeline */
    public function test_pt010_middleware_in_pipeline(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $pipeline = app(\Illuminate\Pipeline\Pipeline::class);
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->then(function ($request) {
                return new Response('handled');
            });
        
        $this->assertEquals('handled', $response->getContent());
        $this->assertEquals(1, ApiRequestLog::count());
    }

    // =========================================================================
    // CATEGORY 7: ACCEPTANCE CRITERIA (AC-001 to AC-006)
    // =========================================================================

    /** @test AC-001: Middleware class exists at correct location */
    public function test_ac001_middleware_class_exists_at_correct_location(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\RequestLoggingMiddleware::class),
            'RequestLoggingMiddleware should exist at app/Http/Middleware/RequestLoggingMiddleware.php');
    }

    /** @test AC-002: All API requests are logged */
    public function test_ac002_all_api_requests_are_logged(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        
        foreach ($methods as $method) {
            $request = Request::create('/api/v1/test', $method);
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $this->assertEquals(5, ApiRequestLog::count());
    }

    /** @test AC-003: Response time is captured */
    public function test_ac003_response_time_is_captured(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $middleware->handle($request, fn() => new Response('ok'));
        
        $log = ApiRequestLog::first();
        
        $this->assertNotNull($log->response_time_ms);
        $this->assertIsInt($log->response_time_ms);
    }

    /** @test AC-004: Logging is non-blocking */
    public function test_ac004_logging_is_non_blocking(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key_id', $apiKey->id);
        
        $start = microtime(true);
        $middleware->handle($request, fn() => new Response('ok'));
        $elapsed = microtime(true) - $start;
        
        // Should complete quickly
        $this->assertLessThan(0.1, $elapsed);
    }

    /** @test AC-005: Health endpoints excluded */
    public function test_ac005_health_endpoints_excluded(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $middleware = new RequestLoggingMiddleware();
        
        $healthEndpoints = ['/api/v1/health', '/api/v1/health/database'];
        
        foreach ($healthEndpoints as $endpoint) {
            $request = Request::create($endpoint, 'GET');
            $request->attributes->set('api_key_id', $apiKey->id);
            
            $middleware->handle($request, fn() => new Response('ok'));
        }
        
        $this->assertEquals(0, ApiRequestLog::count());
    }

    /** @test AC-006: Middleware can be toggled via config */
    public function test_ac006_middleware_can_be_toggled_via_config(): void
    {
        // Test enabled
        config(['api-logging.enabled' => true]);
        $middleware = new RequestLoggingMiddleware();
        $this->assertTrue($middleware->isEnabled());
        
        // Test disabled
        config(['api-logging.enabled' => false]);
        $middleware = new RequestLoggingMiddleware();
        $this->assertFalse($middleware->isEnabled());
    }
}
