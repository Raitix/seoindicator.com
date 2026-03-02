<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Services\Admin\UserAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Feature tests for Admin User Stats API endpoints
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group feature
 * @group api
 */
class UserStatsControllerTest extends TestCase
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
    // GET /api/v1/admin/users/stats Tests
    // =========================================================================

    /** @test */
    public function stats_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function stats_endpoint_returns_403_for_non_admin_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function stats_endpoint_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function stats_endpoint_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'total',
                'active',
                'newThisMonth',
                'newThisWeek',
                'newToday',
                'growthRate' => [
                    'monthly',
                    'weekly',
                    'daily',
                ],
                'byPlan',
            ],
        ]);
    }

    /** @test */
    public function stats_endpoint_returns_success_true(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function stats_endpoint_returns_user_aggregation_data(): void
    {
        User::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJsonPath('data.total', 26); // 25 + 1 admin
    }

    // =========================================================================
    // GET /api/v1/admin/users/recent Tests
    // =========================================================================

    /** @test */
    public function recent_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);
    }

    /** @test */
    public function recent_endpoint_returns_403_for_non_admin_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(403);
    }

    /** @test */
    public function recent_endpoint_returns_paginated_results(): void
    {
        User::factory()->count(30)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent');

        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'current_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /** @test */
    public function recent_endpoint_respects_per_page_parameter(): void
    {
        User::factory()->count(30)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent?per_page=10');

        $response->assertJsonPath('meta.per_page', 10);
    }

    /** @test */
    public function recent_endpoint_orders_by_created_at_desc(): void
    {
        $user1 = User::factory()->create(['created_at' => now()->subDays(2)]);
        $user2 = User::factory()->create(['created_at' => now()->subDays(1)]);
        $user3 = User::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent');

        $response->assertJsonPath('data.0.id', $user3->id);
    }

    /** @test */
    public function recent_endpoint_excludes_sensitive_fields(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent');

        $response->assertJsonMissing(['password']);
        $response->assertJsonMissing(['remember_token']);
    }

    // =========================================================================
    // GET /api/v1/admin/users/active Tests
    // =========================================================================

    /** @test */
    public function active_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);
    }

    /** @test */
    public function active_endpoint_returns_403_for_non_admin_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(403);
    }

    /** @test */
    public function active_endpoint_returns_users_logged_in_last_30_days(): void
    {
        $activeUser = User::factory()->create(['last_login_at' => now()->subDays(5)]);
        $inactiveUser = User::factory()->create(['last_login_at' => now()->subDays(45)]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/active');

        $userIds = collect($response->json('data'))->pluck('id');
        
        $this->assertTrue($userIds->contains($activeUser->id));
        $this->assertFalse($userIds->contains($inactiveUser->id));
    }

    /** @test */
    public function active_endpoint_returns_paginated_results(): void
    {
        User::factory()->count(50)->create(['last_login_at' => now()]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/active');

        $response->assertJsonStructure([
            'success',
            'data',
            'meta',
        ]);
    }

    // =========================================================================
    // Rate Limiting Tests
    // =========================================================================

    /** @test */
    public function endpoints_are_rate_limited(): void
    {
        // Make more than 60 requests in a minute
        for ($i = 0; $i < 65; $i++) {
            $response = $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/users/stats');
        }

        // The 61st+ request should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function rate_limit_headers_are_present(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    /** @test */
    public function stats_endpoint_handles_service_exception_gracefully(): void
    {
        $mockService = Mockery::mock(UserAggregationService::class);
        $mockService->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Database error'));
        
        $this->app->instance(UserAggregationService::class, $mockService);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function endpoints_return_json_error_format(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJsonStructure([
            'success',
            'error',
        ]);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function stats_endpoint_responds_under_200ms(): void
    {
        User::factory()->count(100)->create();

        $start = microtime(true);
        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'API should respond under 200ms');
    }

    // =========================================================================
    // Caching Tests
    // =========================================================================

    /** @test */
    public function stats_endpoint_uses_cache(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['total' => 100]);

        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');
    }

    // =========================================================================
    // HTTP Method Tests
    // =========================================================================

    /** @test */
    public function stats_endpoint_rejects_post_requests(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/users/stats');

        $response->assertStatus(405);
    }

    /** @test */
    public function stats_endpoint_rejects_put_requests(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/admin/users/stats');

        $response->assertStatus(405);
    }

    /** @test */
    public function stats_endpoint_rejects_delete_requests(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/users/stats');

        $response->assertStatus(405);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    /** @test */
    public function recent_endpoint_validates_per_page_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent?per_page=1000');

        $response->assertStatus(422);
    }

    /** @test */
    public function recent_endpoint_validates_per_page_is_integer(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/recent?per_page=abc');

        $response->assertStatus(422);
    }
}
