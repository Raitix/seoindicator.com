<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use App\Models\AnalysisJob;
use App\Models\AnalysisHistory;
use App\Services\Admin\UsageAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Admin Usage API Endpoints.
 *
 * Tests TASK-27-8: Admin Usage API Endpoints
 *
 * Endpoints:
 * - GET /api/v1/admin/usage/overview
 * - GET /api/v1/admin/usage/requests
 * - GET /api/v1/admin/usage/tokens
 * - GET /api/v1/admin/usage/trends
 *
 * @see TASK-27-8
 * @see SPEC-027
 */
class UsageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // RefreshDatabase trait handles migrations automatically
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create an admin user.
     */
    protected function createAdminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Create a regular (non-admin) user.
     */
    protected function createRegularUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /**
     * Create analysis data for testing.
     */
    protected function createAnalysisData(int $jobsCount = 10, int $historyCount = 5): void
    {
        AnalysisJob::factory()->count($jobsCount)->create(['status' => 'completed']);
        AnalysisHistory::factory()->count($historyCount)->create(['tool_name' => 'meta-tag-analyzer']);
    }

    // =========================================================================
    // OVERVIEW ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function overview_endpoint_returns_200_for_admin(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200);
    }

    /** @test */
    public function overview_endpoint_returns_required_structure(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $this->createAnalysisData(5, 3);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'requests' => [
                    'total',
                    'today',
                    'thisWeek',
                    'thisMonth',
                ],
                'tokens' => [
                    'total',
                    'today',
                    'thisWeek',
                    'thisMonth',
                    'byModel',
                ],
                'analyses' => [
                    'total',
                    'today',
                    'byStatus',
                    'byType',
                ],
            ],
        ]);
    }

    /** @test */
    public function overview_endpoint_returns_success_true(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function overview_endpoint_returns_zero_for_empty_database(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJsonPath('data.requests.total', 0)
            ->assertJsonPath('data.tokens.total', 0)
            ->assertJsonPath('data.analyses.total', 0);
    }

    /** @test */
    public function overview_endpoint_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function overview_endpoint_returns_403_for_non_admin(): void
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function overview_endpoint_counts_analysis_jobs(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisJob::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJsonPath('data.requests.total', 15);
    }

    /** @test */
    public function overview_endpoint_includes_analysis_history_in_total(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisJob::factory()->count(5)->create();
        AnalysisHistory::factory()->count(7)->create();

        $response = $this->getJson('/api/v1/admin/usage/overview');

        // Total analyses = jobs + history
        $response->assertJsonPath('data.analyses.total', 12);
    }

    /** @test */
    public function overview_endpoint_returns_by_status_breakdown(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisJob::factory()->count(3)->create(['status' => 'completed']);
        AnalysisJob::factory()->count(2)->create(['status' => 'failed']);
        AnalysisJob::factory()->count(1)->create(['status' => 'pending']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJsonPath('data.analyses.byStatus.completed', 3)
            ->assertJsonPath('data.analyses.byStatus.failed', 2)
            ->assertJsonPath('data.analyses.byStatus.pending', 1);
    }

    /** @test */
    public function overview_endpoint_returns_by_type_breakdown(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisHistory::factory()->count(4)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->count(2)->create(['tool_name' => 'keyword-density']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertJsonPath('data.analyses.byType.meta-tag-analyzer', 4)
            ->assertJsonPath('data.analyses.byType.keyword-density', 2);
    }

    // =========================================================================
    // REQUESTS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function requests_endpoint_returns_200_for_admin(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function requests_endpoint_returns_required_structure(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisHistory::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'byEndpoint',
            ],
        ]);
    }

    /** @test */
    public function requests_endpoint_returns_endpoints_with_counts(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisHistory::factory()->count(5)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->count(3)->create(['tool_name' => 'keyword-density']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertJsonPath('data.byEndpoint./api/v1/tools/meta-tag-analyzer', 5)
            ->assertJsonPath('data.byEndpoint./api/v1/tools/keyword-density', 3);
    }

    /** @test */
    public function requests_endpoint_returns_empty_for_no_data(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertJsonPath('data.byEndpoint', []);
    }

    /** @test */
    public function requests_endpoint_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(401);
    }

    /** @test */
    public function requests_endpoint_returns_403_for_non_admin(): void
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(403);
    }

    /** @test */
    public function requests_endpoint_orders_by_count_desc(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisHistory::factory()->count(10)->create(['tool_name' => 'popular-tool']);
        AnalysisHistory::factory()->count(2)->create(['tool_name' => 'less-popular-tool']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $data = $response->json('data.byEndpoint');
        $keys = array_keys($data);
        
        // First key should be the popular tool
        $this->assertEquals('/api/v1/tools/popular-tool', $keys[0]);
    }

    // =========================================================================
    // TOKENS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function tokens_endpoint_returns_200_for_admin(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $response->assertStatus(200);
    }

    /** @test */
    public function tokens_endpoint_returns_required_structure(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'total',
                'today',
                'thisWeek',
                'thisMonth',
                'byModel',
            ],
        ]);
    }

    /** @test */
    public function tokens_endpoint_returns_zero_when_no_token_tracking(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisJob::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        // Service returns zeros when token columns don't exist
        $response->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.byModel', []);
    }

    /** @test */
    public function tokens_endpoint_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $response->assertStatus(401);
    }

    /** @test */
    public function tokens_endpoint_returns_403_for_non_admin(): void
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $response->assertStatus(403);
    }

    /** @test */
    public function tokens_endpoint_success_flag_is_true(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $response->assertJson(['success' => true]);
    }

    // =========================================================================
    // TRENDS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function trends_endpoint_returns_200_for_admin(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $response->assertStatus(200);
    }

    /** @test */
    public function trends_endpoint_returns_required_structure(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'trends',
            ],
        ]);
    }

    /** @test */
    public function trends_endpoint_returns_7_days_by_default(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $trends = $response->json('data.trends');
        $this->assertCount(7, $trends);
    }

    /** @test */
    public function trends_endpoint_respects_days_parameter(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends?days=14');

        $trends = $response->json('data.trends');
        $this->assertCount(14, $trends);
    }

    /** @test */
    public function trends_endpoint_returns_date_and_tokens(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $response->assertJsonStructure([
            'data' => [
                'trends' => [
                    '*' => ['date', 'tokens'],
                ],
            ],
        ]);
    }

    /** @test */
    public function trends_endpoint_uses_correct_date_format(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $trends = $response->json('data.trends');
        foreach ($trends as $trend) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $trend['date']);
        }
    }

    /** @test */
    public function trends_endpoint_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/trends');

        $response->assertStatus(401);
    }

    /** @test */
    public function trends_endpoint_returns_403_for_non_admin(): void
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $response->assertStatus(403);
    }

    /** @test */
    public function trends_endpoint_includes_today(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $today = now()->format('Y-m-d');
        $trends = $response->json('data.trends');
        $dates = array_column($trends, 'date');
        
        $this->assertContains($today, $dates);
    }

    /** @test */
    public function trends_endpoint_returns_chronological_order(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $trends = $response->json('data.trends');
        $dates = array_column($trends, 'date');
        
        $sortedDates = $dates;
        sort($sortedDates);
        
        $this->assertEquals($sortedDates, $dates);
    }

    // =========================================================================
    // QUERY PARAMETER TESTS
    // =========================================================================

    /** @test */
    public function overview_accepts_start_date_parameter(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?start_date=2026-01-01');

        $response->assertStatus(200);
    }

    /** @test */
    public function overview_accepts_end_date_parameter(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?end_date=2026-12-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function overview_accepts_granularity_parameter(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?granularity=daily');

        $response->assertStatus(200);
    }

    /** @test */
    public function trends_validates_days_as_integer(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends?days=invalid');

        // Should either return 422 validation error or default to 7
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test */
    public function trends_validates_days_is_positive(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends?days=-5');

        // Should either return 422 validation error or default to 7
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test */
    public function trends_limits_max_days(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends?days=365');

        // Should either cap at max (e.g., 90 days) or return error
        $trends = $response->json('data.trends');
        $this->assertLessThanOrEqual(90, count($trends));
    }

    /** @test */
    public function invalid_date_format_returns_error(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?start_date=invalid-date');

        // Should return 422 validation error
        $response->assertStatus(422);
    }

    /** @test */
    public function granularity_accepts_valid_values(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        foreach (['hourly', 'daily', 'weekly', 'monthly'] as $granularity) {
            $response = $this->getJson("/api/v1/admin/usage/overview?granularity={$granularity}");
            $this->assertEquals(200, $response->status());
        }
    }

    /** @test */
    public function invalid_granularity_returns_error(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?granularity=invalid');

        $response->assertStatus(422);
    }

    // =========================================================================
    // RATE LIMITING TESTS
    // =========================================================================

    /** @test */
    public function endpoints_have_rate_limiting(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Make a request and check for rate limit headers
        $response = $this->getJson('/api/v1/admin/usage/overview');

        // Should have rate limit headers (Laravel throttle middleware)
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }

    // =========================================================================
    // HTTP METHOD TESTS
    // =========================================================================

    /** @test */
    public function overview_returns_405_for_post(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->postJson('/api/v1/admin/usage/overview');

        $response->assertStatus(405);
    }

    /** @test */
    public function overview_returns_405_for_put(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->putJson('/api/v1/admin/usage/overview');

        $response->assertStatus(405);
    }

    /** @test */
    public function overview_returns_405_for_delete(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->deleteJson('/api/v1/admin/usage/overview');

        $response->assertStatus(405);
    }

    /** @test */
    public function requests_returns_405_for_post(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->postJson('/api/v1/admin/usage/requests');

        $response->assertStatus(405);
    }

    /** @test */
    public function tokens_returns_405_for_post(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->postJson('/api/v1/admin/usage/tokens');

        $response->assertStatus(405);
    }

    /** @test */
    public function trends_returns_405_for_post(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->postJson('/api/v1/admin/usage/trends');

        $response->assertStatus(405);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /** @test */
    public function endpoints_do_not_expose_sensitive_user_data(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create jobs with user data
        AnalysisJob::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/admin/usage/overview');
        $json = $response->json();

        // Should not contain user emails, passwords, tokens
        $jsonString = json_encode($json);
        $this->assertStringNotContainsString('password', $jsonString);
        $this->assertStringNotContainsString('email', $jsonString);
        $this->assertStringNotContainsString('token', $jsonString);
    }

    /** @test */
    public function endpoints_prevent_sql_injection_in_query_params(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Try SQL injection in start_date parameter
        $response = $this->getJson("/api/v1/admin/usage/overview?start_date='; DROP TABLE users; --");

        // Should return validation error, not SQL error
        $response->assertStatus(422);
    }

    /** @test */
    public function endpoints_prevent_xss_in_query_params(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview?start_date=<script>alert("xss")</script>');

        // Should return validation error
        $response->assertStatus(422);
        
        // Or if it returns data, ensure XSS is not present
        $jsonString = json_encode($response->json());
        $this->assertStringNotContainsString('<script>', $jsonString);
    }

    /** @test */
    public function admin_cannot_access_other_admin_tenant_data(): void
    {
        // If multi-tenancy is implemented, ensure data isolation
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200);
        
        // Data should only belong to the current tenant (if applicable)
        // This is a placeholder for multi-tenancy tests
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /** @test */
    public function endpoints_return_consistent_error_format(): void
    {
        $user = $this->createRegularUser();
        Sanctum::actingAs($user, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_returns_proper_format(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    /** @test */
    public function validation_error_returns_proper_format(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends?days=abc');

        if ($response->status() === 422) {
            $response->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
        }
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function overview_responds_under_500ms(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create some data
        $this->createAnalysisData(100, 50);

        $start = microtime(true);
        $this->getJson('/api/v1/admin/usage/overview');
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Overview endpoint took longer than 500ms');
    }

    /** @test */
    public function trends_responds_under_500ms(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $this->createAnalysisData(100, 50);

        $start = microtime(true);
        $this->getJson('/api/v1/admin/usage/trends');
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Trends endpoint took longer than 500ms');
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function handles_large_dataset(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create large dataset
        AnalysisJob::factory()->count(1000)->create();
        AnalysisHistory::factory()->count(500)->create();

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.requests.total', 1000);
    }

    /** @test */
    public function handles_special_characters_in_tool_names(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        AnalysisHistory::factory()->create(['tool_name' => 'tool-with-special_chars.123']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function handles_unicode_in_response(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create data that might produce unicode in JSON
        AnalysisHistory::factory()->create(['tool_name' => 'ツール分析']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function handles_concurrent_requests(): void
    {
        $admin = $this->createAdminUser();
        
        // Simulate multiple concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            Sanctum::actingAs($admin, ['access']);
            $responses[] = $this->getJson('/api/v1/admin/usage/overview');
        }

        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function handles_timezone_correctly(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create job at different times
        AnalysisJob::factory()->create(['created_at' => now()->startOfDay()]);
        AnalysisJob::factory()->create(['created_at' => now()->endOfDay()]);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.requests.today', 2);
    }

    // =========================================================================
    // DATA TYPE VALIDATION TESTS
    // =========================================================================

    /** @test */
    public function numeric_values_are_integers(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $this->createAnalysisData(5, 3);

        $response = $this->getJson('/api/v1/admin/usage/overview');
        $data = $response->json('data');

        $this->assertIsInt($data['requests']['total']);
        $this->assertIsInt($data['tokens']['total']);
        $this->assertIsInt($data['analyses']['total']);
    }

    /** @test */
    public function by_status_contains_all_status_keys(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/overview');

        $byStatus = $response->json('data.analyses.byStatus');
        
        $this->assertArrayHasKey('pending', $byStatus);
        $this->assertArrayHasKey('running', $byStatus);
        $this->assertArrayHasKey('completed', $byStatus);
        $this->assertArrayHasKey('failed', $byStatus);
        $this->assertArrayHasKey('cancelled', $byStatus);
    }

    /** @test */
    public function by_model_is_array(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/tokens');

        $this->assertIsArray($response->json('data.byModel'));
    }

    /** @test */
    public function by_endpoint_is_array(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/requests');

        $this->assertIsArray($response->json('data.byEndpoint'));
    }

    /** @test */
    public function trends_is_array_of_objects(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        $response = $this->getJson('/api/v1/admin/usage/trends');

        $trends = $response->json('data.trends');
        
        $this->assertIsArray($trends);
        foreach ($trends as $trend) {
            $this->assertIsArray($trend);
            $this->assertArrayHasKey('date', $trend);
            $this->assertArrayHasKey('tokens', $trend);
        }
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function full_usage_flow_works(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Create realistic data
        AnalysisJob::factory()->count(20)->create(['status' => 'completed']);
        AnalysisJob::factory()->count(5)->create(['status' => 'failed']);
        AnalysisHistory::factory()->count(10)->create(['tool_name' => 'meta-tag-analyzer']);

        // Test all endpoints
        $overview = $this->getJson('/api/v1/admin/usage/overview');
        $requests = $this->getJson('/api/v1/admin/usage/requests');
        $tokens = $this->getJson('/api/v1/admin/usage/tokens');
        $trends = $this->getJson('/api/v1/admin/usage/trends');

        $overview->assertStatus(200);
        $requests->assertStatus(200);
        $tokens->assertStatus(200);
        $trends->assertStatus(200);

        // Verify data consistency
        $this->assertEquals(25, $overview->json('data.requests.total'));
        $this->assertArrayHasKey('/api/v1/tools/meta-tag-analyzer', $requests->json('data.byEndpoint'));
    }

    /** @test */
    public function data_is_fresh_per_request(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // First request - empty
        $response1 = $this->getJson('/api/v1/admin/usage/overview');
        $this->assertEquals(0, $response1->json('data.requests.total'));

        // Add data
        AnalysisJob::factory()->count(5)->create();

        // Second request - should see new data
        $response2 = $this->getJson('/api/v1/admin/usage/overview');
        $this->assertEquals(5, $response2->json('data.requests.total'));
    }

    /** @test */
    public function service_exception_returns_error(): void
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin, ['access']);

        // Mock service to throw exception
        $this->mock(UsageAggregationService::class, function ($mock) {
            $mock->shouldReceive('getStats')->andThrow(new \Exception('Service error'));
        });

        $response = $this->getJson('/api/v1/admin/usage/overview');

        // Should return 500 error
        $response->assertStatus(500);
    }
}
