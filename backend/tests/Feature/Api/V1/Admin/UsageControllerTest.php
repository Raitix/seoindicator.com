<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\AnalysisJob;
use App\Services\Admin\UsageAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Feature tests for Admin Usage API endpoints
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group feature
 * @group api
 */
class UsageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // GET /api/v1/admin/usage/overview Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200);
    }

    /** @test */
    public function overview_endpoint_returns_403_for_non_admin_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function overview_endpoint_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function overview_endpoint_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');

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
                    'thisWeek',
                    'thisMonth',
                ],
            ],
        ]);
    }

    /** @test */
    public function overview_endpoint_returns_success_true(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertJson(['success' => true]);
    }

    // =========================================================================
    // GET /api/v1/admin/usage/requests Tests
    // =========================================================================

    /** @test */
    public function requests_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function requests_endpoint_returns_endpoint_breakdown(): void
    {
        AnalysisJob::factory()->count(20)->create(['endpoint' => '/api/v1/analyze']);
        AnalysisJob::factory()->count(15)->create(['endpoint' => '/api/v1/batch']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/requests');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'byEndpoint',
            ],
        ]);
    }

    /** @test */
    public function requests_endpoint_supports_date_filtering(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/requests?from=2026-01-01&to=2026-03-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function requests_endpoint_validates_date_parameters(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/requests?from=invalid-date');

        $response->assertStatus(422);
    }

    // =========================================================================
    // GET /api/v1/admin/usage/tokens Tests
    // =========================================================================

    /** @test */
    public function tokens_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/tokens');

        $response->assertStatus(200);
    }

    /** @test */
    public function tokens_endpoint_returns_model_breakdown(): void
    {
        AnalysisJob::factory()->count(10)->create([
            'model' => 'gpt-4',
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);
        
        AnalysisJob::factory()->count(5)->create([
            'model' => 'gpt-3.5-turbo',
            'input_tokens' => 2000,
            'output_tokens' => 1000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/tokens');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'byModel',
            ],
        ]);
    }

    /** @test */
    public function tokens_endpoint_returns_total_counts(): void
    {
        AnalysisJob::factory()->count(5)->create([
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/tokens');

        $response->assertJsonPath('data.totalInputTokens', 5000);
        $response->assertJsonPath('data.totalOutputTokens', 2500);
    }

    // =========================================================================
    // GET /api/v1/admin/usage/trends Tests
    // =========================================================================

    /** @test */
    public function trends_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/trends');

        $response->assertStatus(200);
    }

    /** @test */
    public function trends_endpoint_returns_daily_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/trends?days=7');

        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'date',
                    'requests',
                    'tokens',
                    'analyses',
                ],
            ],
        ]);
    }

    /** @test */
    public function trends_endpoint_respects_days_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/trends?days=14');

        $data = $response->json('data');
        $this->assertLessThanOrEqual(14, count($data));
    }

    /** @test */
    public function trends_endpoint_defaults_to_7_days(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/trends');

        $data = $response->json('data');
        $this->assertLessThanOrEqual(7, count($data));
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_responds_under_200ms(): void
    {
        AnalysisJob::factory()->count(100)->create();

        $start = microtime(true);
        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'API should respond under 200ms');
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_handles_service_exception_gracefully(): void
    {
        $mockService = Mockery::mock(UsageAggregationService::class);
        $mockService->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Database error'));
        
        $this->app->instance(UsageAggregationService::class, $mockService);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }
}
