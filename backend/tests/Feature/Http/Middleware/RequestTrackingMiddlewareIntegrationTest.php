<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\RequestTrackingMiddleware;
use App\Services\Analytics\RequestTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Feature tests for RequestTrackingMiddleware integration.
 *
 * Tests middleware behavior in the full request lifecycle.
 *
 * @see TASK-27-6
 * @see SPEC-027
 */
class RequestTrackingMiddlewareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        // Test routes with middleware
        $router->get('/api/v1/test-tracked', function () {
            return response()->json(['message' => 'tracked']);
        })->middleware(RequestTrackingMiddleware::class);

        $router->post('/api/v1/test-post', function () {
            return response()->json(['message' => 'posted']);
        })->middleware(RequestTrackingMiddleware::class);

        $router->get('/api/health', function () {
            return response()->json(['status' => 'ok']);
        })->middleware(RequestTrackingMiddleware::class);

        $router->get('/api/v1/protected', function () {
            return response()->json(['message' => 'protected']);
        })->middleware(['auth:api', RequestTrackingMiddleware::class]);
    }

    // =========================================================================
    // Route Integration (RTM-ROUTE-*)
    // =========================================================================

    /** @test */
    public function rtm_route_01_middleware_works_on_route(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->once();
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_route_02_tracks_get_request(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['endpoint'] === '/api/v1/test-tracked' &&
                       $data['method'] === 'GET';
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_route_03_tracks_post_request(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['endpoint'] === '/api/v1/test-post' &&
                       $data['method'] === 'POST';
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->postJson('/api/v1/test-post', ['test' => 'data']);

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_route_04_authenticated_request_tracks_user(): void
    {
        $user = User::factory()->create();

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) use ($user) {
                return $data['user_id'] === $user->id;
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_route_05_unauthenticated_request_tracks_null_user(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->withArgs(function ($data) {
                return $data['user_id'] === null;
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Exclusion Integration (RTM-EXCL-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_excl_int_01_health_endpoint_excluded(): void
    {
        Config::set('request_tracking.excluded_paths', ['api/health']);

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('track');
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_excl_int_02_non_excluded_endpoint_tracked(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->once();
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Error Handling Integration (RTM-ERR-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_err_int_01_request_continues_after_tracking_failure(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \Exception('Tracking failed'));
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_err_int_02_tracking_failure_does_not_affect_response(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->once()
            ->andThrow(new \Exception('Tracking failed'));
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertJson(['message' => 'tracked']);
    }

    // =========================================================================
    // Sample Rate Integration (RTM-SAMPLE-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_sample_int_01_disabled_tracking_no_calls(): void
    {
        Config::set('request_tracking.sample_rate', 0.0);

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldNotReceive('track');
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_sample_int_02_full_tracking_all_calls(): void
    {
        Config::set('request_tracking.sample_rate', 1.0);

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->times(3);
        $this->app->instance(RequestTrackingService::class, $service);

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/v1/test-tracked')->assertStatus(200);
        }
    }

    // =========================================================================
    // Response Preservation (RTM-RESP-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_resp_int_01_response_unchanged_by_tracking(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->once();
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        $response->assertExactJson(['message' => 'tracked']);
    }

    /** @test */
    public function rtm_resp_int_02_status_code_preserved(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->once();
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->postJson('/api/v1/test-post', ['test' => 'data']);

        $response->assertStatus(200);
    }

    /** @test */
    public function rtm_resp_int_03_headers_not_modified(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')->once();
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->getJson('/api/v1/test-tracked');

        // Should not have tracking-specific headers
        $this->assertFalse($response->headers->has('X-Tracked'));
        $this->assertFalse($response->headers->has('X-Request-ID'));
    }

    // =========================================================================
    // Multiple Requests (RTM-MULTI-*)
    // =========================================================================

    /** @test */
    public function rtm_multi_01_multiple_requests_tracked_independently(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->times(3)
            ->withArgs(function ($data) {
                static $count = 0;
                $count++;
                return true;
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $this->getJson('/api/v1/test-tracked')->assertStatus(200);
        $this->postJson('/api/v1/test-post')->assertStatus(200);
        $this->getJson('/api/v1/test-tracked')->assertStatus(200);
    }

    /** @test */
    public function rtm_multi_02_different_users_tracked_separately(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $trackedUsers = [];

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track')
            ->twice()
            ->withArgs(function ($data) use (&$trackedUsers) {
                $trackedUsers[] = $data['user_id'];
                return true;
            });
        $this->app->instance(RequestTrackingService::class, $service);

        $this->actingAs($user1)->getJson('/api/v1/test-tracked');
        $this->actingAs($user2)->getJson('/api/v1/test-tracked');

        $this->assertEquals([$user1->id, $user2->id], $trackedUsers);
    }

    // =========================================================================
    // Performance Integration (RTM-PERF-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_perf_int_01_response_time_minimal_overhead(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track');
        $this->app->instance(RequestTrackingService::class, $service);

        $start = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/v1/test-tracked')->assertStatus(200);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 10;

        // Each request should complete in reasonable time
        $this->assertLessThan(100, $avgTime, "Average response time: {$avgTime}ms");
    }

    // =========================================================================
    // Security Integration (RTM-SEC-INT-*)
    // =========================================================================

    /** @test */
    public function rtm_sec_int_01_sensitive_data_not_in_response(): void
    {
        $user = User::factory()->create(['email' => 'secret@example.com']);

        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track');
        $this->app->instance(RequestTrackingService::class, $service);

        $response = $this->actingAs($user)->getJson('/api/v1/test-tracked');

        $content = $response->getContent();
        $this->assertStringNotContainsString('secret@example.com', $content);
        $this->assertStringNotContainsString('password', $content);
    }

    /** @test */
    public function rtm_sec_int_02_malicious_path_handled_safely(): void
    {
        $service = Mockery::mock(RequestTrackingService::class);
        $service->shouldReceive('track');
        $this->app->instance(RequestTrackingService::class, $service);

        // This should not cause any security issues
        $response = $this->getJson('/api/v1/test-tracked?malicious=<script>alert(1)</script>');

        $response->assertStatus(200);
    }
}
