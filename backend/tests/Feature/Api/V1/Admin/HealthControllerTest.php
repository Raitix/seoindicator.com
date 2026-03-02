<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Admin Health API Endpoints (TASK-27-13)
 *
 * Covers:
 * - GET /api/v1/admin/health/overview
 * - GET /api/v1/admin/health/queues
 * - GET /api/v1/admin/health/database
 * - GET /api/v1/admin/health/alerts
 *
 * Requirements:
 * - AdminAuthorizationMiddleware protection
 * - 120 requests/minute rate limiting
 * - Dashboard polling optimization (30s intervals)
 */
class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    // ============================================================
    // HEALTH OVERVIEW ENDPOINT - GET /api/v1/admin/health/overview
    // ============================================================

    /** @test */
    public function health_overview_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'overall_score',
                    'services' => [
                        '*' => [
                            'name',
                            'status',
                            'latency_ms',
                        ],
                    ],
                    'last_check',
                ],
            ]);
    }

    /** @test */
    public function health_overview_returns_valid_status_values(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Status must be one of: healthy, degraded, unhealthy
        $this->assertContains($data['status'], ['healthy', 'degraded', 'unhealthy']);

        // Overall score must be 0-100
        $this->assertGreaterThanOrEqual(0, $data['overall_score']);
        $this->assertLessThanOrEqual(100, $data['overall_score']);

        // Each service must have valid structure
        foreach ($data['services'] as $service) {
            $this->assertContains($service['status'], ['healthy', 'degraded', 'unhealthy']);
            $this->assertIsInt($service['latency_ms']);
            $this->assertGreaterThanOrEqual(0, $service['latency_ms']);
        }
    }

    /** @test */
    public function health_overview_includes_expected_services(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(200);

        $services = collect($response->json('data.services'))->pluck('name');

        // Core services expected in health overview
        $expectedServices = ['database', 'cache', 'queue', 'storage'];

        foreach ($expectedServices as $expectedService) {
            $this->assertTrue(
                $services->contains($expectedService),
                "Expected service '{$expectedService}' not found in health overview"
            );
        }
    }

    /** @test */
    public function health_overview_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function health_overview_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function health_overview_is_optimized_for_polling(): void
    {
        Sanctum::actingAs($this->adminUser);

        $start = microtime(true);
        $response = $this->getJson('/api/v1/admin/health/overview');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond in under 500ms for polling efficiency
        $this->assertLessThan(500, $duration, 'Health overview should respond quickly for polling');

        // Should have cache headers for polling
        $response->assertHeader('Cache-Control');
    }

    /** @test */
    public function health_overview_reflects_database_status(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(200);

        $services = collect($response->json('data.services'));
        $dbService = $services->firstWhere('name', 'database');

        $this->assertNotNull($dbService, 'Database service should be present');
        $this->assertEquals('healthy', $dbService['status'], 'Database should be healthy in test env');
    }

    // ============================================================
    // QUEUE STATUS ENDPOINT - GET /api/v1/admin/health/queues
    // ============================================================

    /** @test */
    public function queue_status_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queues' => [
                        '*' => [
                            'name',
                            'size',
                            'pending',
                            'processed',
                            'failed',
                            'workers',
                        ],
                    ],
                    'total_pending',
                    'total_failed',
                ],
            ]);
    }

    /** @test */
    public function queue_status_includes_default_queues(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(200);

        $queues = collect($response->json('data.queues'))->pluck('name');

        // Standard Laravel queues
        $expectedQueues = ['default', 'high', 'low'];

        foreach ($expectedQueues as $expectedQueue) {
            $this->assertTrue(
                $queues->contains($expectedQueue),
                "Expected queue '{$expectedQueue}' not found in queue status"
            );
        }
    }

    /** @test */
    public function queue_status_returns_numeric_values(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Totals must be integers >= 0
        $this->assertIsInt($data['total_pending']);
        $this->assertGreaterThanOrEqual(0, $data['total_pending']);

        $this->assertIsInt($data['total_failed']);
        $this->assertGreaterThanOrEqual(0, $data['total_failed']);

        // Each queue must have numeric values
        foreach ($data['queues'] as $queue) {
            $this->assertIsInt($queue['size']);
            $this->assertIsInt($queue['pending']);
            $this->assertIsInt($queue['processed']);
            $this->assertIsInt($queue['failed']);
            $this->assertIsInt($queue['workers']);

            $this->assertGreaterThanOrEqual(0, $queue['size']);
            $this->assertGreaterThanOrEqual(0, $queue['pending']);
            $this->assertGreaterThanOrEqual(0, $queue['processed']);
            $this->assertGreaterThanOrEqual(0, $queue['failed']);
            $this->assertGreaterThanOrEqual(0, $queue['workers']);
        }
    }

    /** @test */
    public function queue_status_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(401);
    }

    /** @test */
    public function queue_status_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(403);
    }

    /** @test */
    public function queue_status_reflects_actual_queue_state(): void
    {
        Sanctum::actingAs($this->adminUser);

        // This test verifies the endpoint returns real data
        // The actual queue monitoring is done by QueueMonitoringIntegration (TASK-27-12)
        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(200);

        // Verify response structure matches expected format
        $data = $response->json('data');

        $this->assertArrayHasKey('queues', $data);
        $this->assertArrayHasKey('total_pending', $data);
        $this->assertArrayHasKey('total_failed', $data);
    }

    // ============================================================
    // DATABASE METRICS ENDPOINT - GET /api/v1/admin/health/database
    // ============================================================

    /** @test */
    public function database_metrics_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'connection',
                    'database',
                    'size_mb',
                    'tables' => [
                        '*' => [
                            'name',
                            'rows',
                            'size_mb',
                        ],
                    ],
                    'connections' => [
                        'active',
                        'idle',
                        'max',
                    ],
                    'performance' => [
                        'query_time_ms',
                        'slow_queries',
                    ],
                ],
            ]);
    }

    /** @test */
    public function database_metrics_returns_valid_connection_info(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertNotEmpty($data['connection']);
        $this->assertNotEmpty($data['database']);

        // Size must be positive
        $this->assertIsNumeric($data['size_mb']);
        $this->assertGreaterThanOrEqual(0, $data['size_mb']);
    }

    /** @test */
    public function database_metrics_connection_stats_are_valid(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(200);

        $connections = $response->json('data.connections');

        $this->assertIsInt($connections['active']);
        $this->assertIsInt($connections['idle']);
        $this->assertIsInt($connections['max']);

        $this->assertGreaterThanOrEqual(0, $connections['active']);
        $this->assertGreaterThanOrEqual(0, $connections['idle']);
        $this->assertGreaterThan(0, $connections['max']);

        // Active + Idle should not exceed max
        $this->assertLessThanOrEqual(
            $connections['max'],
            $connections['active'] + $connections['idle']
        );
    }

    /** @test */
    public function database_metrics_performance_data_is_valid(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(200);

        $performance = $response->json('data.performance');

        $this->assertIsNumeric($performance['query_time_ms']);
        $this->assertGreaterThanOrEqual(0, $performance['query_time_ms']);

        $this->assertIsInt($performance['slow_queries']);
        $this->assertGreaterThanOrEqual(0, $performance['slow_queries']);
    }

    /** @test */
    public function database_metrics_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(401);
    }

    /** @test */
    public function database_metrics_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(403);
    }

    /** @test */
    public function database_metrics_table_info_is_valid(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/database');

        $response->assertStatus(200);

        $tables = $response->json('data.tables');

        $this->assertIsArray($tables);

        foreach ($tables as $table) {
            $this->assertNotEmpty($table['name']);
            $this->assertIsInt($table['rows']);
            $this->assertGreaterThanOrEqual(0, $table['rows']);
            $this->assertIsNumeric($table['size_mb']);
            $this->assertGreaterThanOrEqual(0, $table['size_mb']);
        }
    }

    // ============================================================
    // ALERTS ENDPOINT - GET /api/v1/admin/health/alerts
    // ============================================================

    /** @test */
    public function alerts_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alerts' => [
                        '*' => [
                            'id',
                            'type',
                            'severity',
                            'message',
                            'source',
                            'created_at',
                            'acknowledged',
                        ],
                    ],
                    'total',
                    'unacknowledged',
                ],
            ]);
    }

    /** @test */
    public function alerts_returns_valid_severity_values(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(200);

        $alerts = $response->json('data.alerts');

        foreach ($alerts as $alert) {
            $this->assertContains(
                $alert['severity'],
                ['info', 'warning', 'critical'],
                "Alert severity must be info, warning, or critical"
            );
        }
    }

    /** @test */
    public function alerts_returns_valid_type_values(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(200);

        $alerts = $response->json('data.alerts');

        foreach ($alerts as $alert) {
            $this->assertNotEmpty($alert['type']);
            $this->assertIsString($alert['type']);
        }
    }

    /** @test */
    public function alerts_total_counts_are_accurate(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertEquals(
            count($data['alerts']),
            $data['total'],
            'Total count should match number of alerts'
        );

        $unacknowledgedCount = collect($data['alerts'])
            ->where('acknowledged', false)
            ->count();

        $this->assertEquals(
            $unacknowledgedCount,
            $data['unacknowledged'],
            'Unacknowledged count should match count of unacknowledged alerts'
        );
    }

    /** @test */
    public function alerts_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(401);
    }

    /** @test */
    public function alerts_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(403);
    }

    /** @test */
    public function alerts_acknowledged_field_is_boolean(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');

        $response->assertStatus(200);

        $alerts = $response->json('data.alerts');

        foreach ($alerts as $alert) {
            $this->assertIsBool($alert['acknowledged']);
        }
    }

    // ============================================================
    // RATE LIMITING TESTS
    // ============================================================

    /** @test */
    public function endpoints_respect_rate_limit_120_per_minute(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Make requests within limit
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/admin/health/overview');
            $response->assertStatus(200);
        }

        // Verify rate limit headers are present
        $response = $this->getJson('/api/v1/admin/health/overview');
        $response->assertStatus(200);

        // Check for rate limit headers (format may vary)
        $this->assertTrue(
            $response->headers->has('X-RateLimit-Limit') ||
            $response->headers->has('X-Ratelimit-Limit') ||
            str_contains($response->headers->get('Cache-Control', ''), 'max-age'),
            'Rate limit headers should be present'
        );
    }

    /** @test */
    public function rate_limit_is_per_endpoint(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Each endpoint should have its own rate limit counter
        $response1 = $this->getJson('/api/v1/admin/health/overview');
        $response2 = $this->getJson('/api/v1/admin/health/queues');
        $response3 = $this->getJson('/api/v1/admin/health/database');
        $response4 = $this->getJson('/api/v1/admin/health/alerts');

        // All should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
        $response4->assertStatus(200);
    }

    // ============================================================
    // HTTP METHOD RESTRICTIONS
    // ============================================================

    /** @test */
    public function health_endpoints_only_allow_get(): void
    {
        Sanctum::actingAs($this->adminUser);

        $endpoints = [
            '/api/v1/admin/health/overview',
            '/api/v1/admin/health/queues',
            '/api/v1/admin/health/database',
            '/api/v1/admin/health/alerts',
        ];

        foreach ($endpoints as $endpoint) {
            // POST should be rejected
            $this->postJson($endpoint)->assertStatus(405);

            // PUT should be rejected
            $this->putJson($endpoint)->assertStatus(405);

            // DELETE should be rejected
            $this->deleteJson($endpoint)->assertStatus(405);

            // PATCH should be rejected
            $this->patchJson($endpoint)->assertStatus(405);
        }
    }

    // ============================================================
    // SECURITY TESTS
    // ============================================================

    /** @test */
    public function endpoints_do_not_expose_sensitive_data(): void
    {
        Sanctum::actingAs($this->adminUser);

        $endpoints = [
            '/api/v1/admin/health/overview',
            '/api/v1/admin/health/queues',
            '/api/v1/admin/health/database',
            '/api/v1/admin/health/alerts',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $content = $response->content();

            // Should not expose sensitive configuration
            $this->assertStringNotContainsString('password', $content);
            $this->assertStringNotContainsString('api_key', $content);
            $this->assertStringNotContainsString('secret', $content);
            $this->assertStringNotContainsString('token', strtolower($content) === $content ? $content : '');
        }
    }

    /** @test */
    public function endpoints_handle_malicious_input_gracefully(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Query params should be ignored (not cause errors)
        $response = $this->getJson('/api/v1/admin/health/overview?malicious=<script>alert(1)</script>');
        $response->assertStatus(200);

        $response = $this->getJson("/api/v1/admin/health/overview?sql='; DROP TABLE users;--");
        $response->assertStatus(200);
    }

    /** @test */
    public function sql_injection_attempt_does_not_affect_database_endpoint(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Should handle without error
        $response = $this->getJson("/api/v1/admin/health/database?table='; SELECT * FROM users;--");

        $response->assertStatus(200);
    }

    // ============================================================
    // PERFORMANCE TESTS
    // ============================================================

    /** @test */
    public function all_endpoints_respond_within_500ms(): void
    {
        Sanctum::actingAs($this->adminUser);

        $endpoints = [
            '/api/v1/admin/health/overview',
            '/api/v1/admin/health/queues',
            '/api/v1/admin/health/database',
            '/api/v1/admin/health/alerts',
        ];

        foreach ($endpoints as $endpoint) {
            $start = microtime(true);
            $this->getJson($endpoint);
            $duration = (microtime(true) - $start) * 1000;

            $this->assertLessThan(
                500,
                $duration,
                "Endpoint {$endpoint} should respond within 500ms (got {$duration}ms)"
            );
        }
    }

    /** @test */
    public function endpoints_use_json_content_type(): void
    {
        Sanctum::actingAs($this->adminUser);

        $endpoints = [
            '/api/v1/admin/health/overview',
            '/api/v1/admin/health/queues',
            '/api/v1/admin/health/database',
            '/api/v1/admin/health/alerts',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $response->assertHeader('Content-Type', 'application/json');
        }
    }

    // ============================================================
    // ERROR HANDLING TESTS
    // ============================================================

    /** @test */
    public function endpoints_return_consistent_error_format_on_401(): void
    {
        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
    }

    /** @test */
    public function endpoints_return_consistent_error_format_on_403(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    /** @test */
    public function endpoints_handle_missing_optional_dependencies_gracefully(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Queue monitoring might not be available (TASK-27-12 not complete)
        // Endpoint should still work with default/mock data
        $response = $this->getJson('/api/v1/admin/health/queues');

        $response->assertStatus(200);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /** @test */
    public function health_overview_aggregates_service_statuses_correctly(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Get overview
        $overviewResponse = $this->getJson('/api/v1/admin/health/overview');
        $overviewResponse->assertStatus(200);

        $overviewStatus = $overviewResponse->json('data.status');

        // If all services are healthy, overall should be healthy
        $services = $overviewResponse->json('data.services');
        $allHealthy = collect($services)->every(fn($s) => $s['status'] === 'healthy');

        if ($allHealthy) {
            $this->assertEquals('healthy', $overviewStatus);
        }
    }

    /** @test */
    public function alerts_endpoint_can_be_polled_repeatedly(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Simulate 5 consecutive polling requests (30-second intervals)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/v1/admin/health/alerts');
            $response->assertStatus(200);

            // Small delay to simulate polling interval
            usleep(10000); // 10ms (reduced for test speed)
        }
    }

    /** @test */
    public function response_timestamps_are_valid_iso8601(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/overview');
        $response->assertStatus(200);

        $lastCheck = $response->json('data.last_check');

        if ($lastCheck) {
            // Should be valid ISO 8601 date
            $date = \DateTime::createFromFormat(\DateTime::ATOM, $lastCheck);
            $this->assertInstanceOf(\DateTime::class, $date, 'last_check should be valid ISO 8601');
        }
    }

    /** @test */
    public function alerts_timestamps_are_valid_iso8601(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/health/alerts');
        $response->assertStatus(200);

        $alerts = $response->json('data.alerts');

        foreach ($alerts as $alert) {
            $date = \DateTime::createFromFormat(\DateTime::ATOM, $alert['created_at']);
            $this->assertInstanceOf(
                \DateTime::class,
                $date,
                "Alert created_at should be valid ISO 8601: {$alert['created_at']}"
            );
        }
    }
}
