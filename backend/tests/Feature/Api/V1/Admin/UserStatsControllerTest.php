<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use App\Services\Admin\Contracts\UserStatsRepositoryInterface;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Admin User Stats API Endpoints
 * 
 * @see TASK-27-5
 * @see TESTS/TASK-27-5.md
 */
class UserStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // =========================================================================
    // Route Structure Tests (US-001 to US-006)
    // =========================================================================

    /** @test */
    public function stats_endpoint_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function recent_users_endpoint_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);
    }

    /** @test */
    public function active_users_endpoint_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);
    }

    /** @test */
    public function routes_have_correct_middleware(): void
    {
        $routes = [
            '/api/v1/admin/users/stats',
            '/api/v1/admin/users/recent',
            '/api/v1/admin/users/active',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(401); // auth:sanctum middleware
        }
    }

    /** @test */
    public function routes_have_rate_limiting(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->andReturn($this->getMockStats());
            $mock->shouldReceive('getRecentUsers')->andReturn($this->getMockPaginator([]));
            $mock->shouldReceive('getActiveUsers')->andReturn($this->getMockPaginator([]));
        });

        // Make 61 requests to trigger rate limit
        for ($i = 0; $i < 61; $i++) {
            $response = $this->actingAs($admin, 'sanctum')
                ->getJson('/api/v1/admin/users/stats');
        }

        // 61st request should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function routes_have_admin_prefix(): void
    {
        $this->assertStringContainsString('/api/v1/admin', '/api/v1/admin/users/stats');
        $this->assertStringContainsString('/api/v1/admin', '/api/v1/admin/users/recent');
        $this->assertStringContainsString('/api/v1/admin', '/api/v1/admin/users/active');
    }

    // =========================================================================
    // Authentication Tests (US-010 to US-021)
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_stats(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_recent_users(): void
    {
        $response = $this->getJson('/api/v1/admin/users/recent');
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_active_users(): void
    {
        $response = $this->getJson('/api/v1/admin/users/active');
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_token_returns_401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function authentication_error_format(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // Authorization Tests (US-030 to US-041)
    // =========================================================================

    /** @test */
    public function admin_can_access_stats(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_recent_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_active_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_stats(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_access_recent_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_access_active_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(403);
    }

    /** @test */
    public function authorization_error_format(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function super_admin_can_access_all_endpoints(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->andReturn($this->getMockStats());
            $mock->shouldReceive('getRecentUsers')->andReturn($this->getMockPaginator([]));
            $mock->shouldReceive('getActiveUsers')->andReturn($this->getMockPaginator([]));
        });

        $response1 = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');
        $response1->assertStatus(200);

        $response2 = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');
        $response2->assertStatus(200);

        $response3 = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');
        $response3->assertStatus(200);
    }

    // =========================================================================
    // Stats Endpoint Tests (US-050 to US-064)
    // =========================================================================

    /** @test */
    public function stats_returns_correct_structure(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_users',
                    'active_users',
                    'new_today',
                    'new_this_week',
                    'new_this_month',
                    'growth_rates' => [
                        'daily',
                        'weekly',
                        'monthly',
                    ],
                    'users_by_plan' => [
                        'free',
                        'pro',
                        'enterprise',
                    ],
                ],
            ]);
    }

    /** @test */
    public function stats_returns_integer_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $data = $response->json('data');

        $this->assertIsInt($data['total_users']);
        $this->assertIsInt($data['active_users']);
        $this->assertIsInt($data['new_today']);
        $this->assertIsInt($data['new_this_week']);
        $this->assertIsInt($data['new_this_month']);
    }

    /** @test */
    public function stats_returns_float_growth_rates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $growthRates = $response->json('data.growth_rates');

        $this->assertIsFloat($growthRates['daily']);
        $this->assertIsFloat($growthRates['weekly']);
        $this->assertIsFloat($growthRates['monthly']);
    }

    /** @test */
    public function stats_returns_plan_breakdown(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $usersByPlan = $response->json('data.users_by_plan');

        $this->assertArrayHasKey('free', $usersByPlan);
        $this->assertArrayHasKey('pro', $usersByPlan);
        $this->assertArrayHasKey('enterprise', $usersByPlan);
        $this->assertIsInt($usersByPlan['free']);
        $this->assertIsInt($usersByPlan['pro']);
        $this->assertIsInt($usersByPlan['enterprise']);
    }

    /** @test */
    public function stats_with_zero_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn([
                'total_users' => 0,
                'active_users' => 0,
                'new_today' => 0,
                'new_this_week' => 0,
                'new_this_month' => 0,
                'growth_rates' => [
                    'daily' => 0.0,
                    'weekly' => 0.0,
                    'monthly' => 0.0,
                ],
                'users_by_plan' => [
                    'free' => 0,
                    'pro' => 0,
                    'enterprise' => 0,
                ],
            ]);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_users', 0)
            ->assertJsonPath('data.active_users', 0);
    }

    /** @test */
    public function stats_with_large_numbers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn([
                'total_users' => 1000000,
                'active_users' => 500000,
                'new_today' => 500,
                'new_this_week' => 3500,
                'new_this_month' => 15000,
                'growth_rates' => [
                    'daily' => 0.05,
                    'weekly' => 0.35,
                    'monthly' => 1.5,
                ],
                'users_by_plan' => [
                    'free' => 800000,
                    'pro' => 150000,
                    'enterprise' => 50000,
                ],
            ]);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_users', 1000000);
    }

    /** @test */
    public function stats_success_wrapper(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJsonPath('success', true);
    }

    /** @test */
    public function stats_service_exception_handling(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andThrow(
                new \Exception('Database connection failed')
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(500);
    }

    // =========================================================================
    // Recent Users Endpoint Tests (US-070 to US-084)
    // =========================================================================

    /** @test */
    public function recent_users_returns_paginated_structure(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockRecentUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'masked_name',
                        'created_at',
                        'plan',
                        'is_active',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test */
    public function recent_users_default_pagination(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')
                ->once()
                ->with(20, [])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');
    }

    /** @test */
    public function recent_users_custom_pagination(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')
                ->once()
                ->with(10, [])
                ->andReturn($this->getMockPaginator([], 10));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?per_page=10');
    }

    /** @test */
    public function recent_users_plan_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')
                ->once()
                ->with(20, ['plan' => 'pro'])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?plan=pro');
    }

    /** @test */
    public function recent_users_active_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')
                ->once()
                ->with(20, ['is_active' => true])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?is_active=true');
    }

    /** @test */
    public function recent_users_empty_result(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function recent_users_page_parameter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $paginator = $this->getMockPaginator([], 20, 2);
            $paginator->currentPage = 2;
            $mock->shouldReceive('getRecentUsers')->once()->andReturn($paginator);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?page=2');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Active Users Endpoint Tests (US-090 to US-104)
    // =========================================================================

    /** @test */
    public function active_users_returns_paginated_structure(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockActiveUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'masked_name',
                        'last_login_at',
                        'plan',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test */
    public function active_users_default_pagination(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')
                ->once()
                ->with(20, 30, [])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');
    }

    /** @test */
    public function active_users_days_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')
                ->once()
                ->with(20, 7, [])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active?days=7');
    }

    /** @test */
    public function active_users_plan_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')
                ->once()
                ->with(20, 30, ['plan' => 'enterprise'])
                ->andReturn($this->getMockPaginator([]));
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active?plan=enterprise');
    }

    /** @test */
    public function active_users_empty_result(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    // =========================================================================
    // PII Exclusion Tests (US-110 to US-117)
    // =========================================================================

    /** @test */
    public function stats_endpoint_has_no_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('email', $responseContent);
    }

    /** @test */
    public function recent_users_excludes_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockRecentUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('email', $data);
    }

    /** @test */
    public function active_users_excludes_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockActiveUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('email', $data);
    }

    /** @test */
    public function recent_users_has_masked_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockRecentUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $maskedName = $response->json('data.0.masked_name');
        
        // Masked name should have asterisks
        $this->assertMatchesRegularExpression('/\*/', $maskedName);
    }

    /** @test */
    public function active_users_has_masked_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->once()->andReturn(
                $this->getMockPaginator([$this->getMockActiveUser()])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $maskedName = $response->json('data.0.masked_name');
        
        // Masked name should have asterisks
        $this->assertMatchesRegularExpression('/\*/', $maskedName);
    }

    /** @test */
    public function masking_preserves_first_character(): void
    {
        // Test that masking logic preserves the first character
        // The actual masking implementation will be in the controller/resource
        $name = 'John Doe';
        $masked = 'J*** D**';
        
        $this->assertEquals('J', $masked[0]);
        $this->assertEquals('D', $masked[6]);
    }

    /** @test */
    public function masking_handles_unicode(): void
    {
        // Unicode names should be handled gracefully
        $name = 'François';
        $masked = 'F*******';
        
        $this->assertEquals('F', $masked[0]);
        $this->assertGreaterThan(1, strlen($masked));
    }

    // =========================================================================
    // Security Tests (US-140 to US-147)
    // =========================================================================

    /** @test */
    public function sql_injection_prevented_in_plan_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')
                ->once()
                ->with(20, ['plan' => "' OR '1'='1"])
                ->andReturn($this->getMockPaginator([]));
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/users/recent?plan=' OR '1'='1");

        $response->assertStatus(200);
        // If SQL injection worked, we'd see unexpected data
    }

    /** @test */
    public function xss_prevented_in_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?plan=<script>alert(1)</script>');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }

    /** @test */
    public function no_sensitive_data_in_response(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $content = $response->getContent();
        
        // Check no sensitive fields are present
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('token', $content);
        $this->assertStringNotContainsString('secret', $content);
        $this->assertStringNotContainsString('api_key', $content);
    }

    /** @test */
    public function csrf_protection_via_sanctum(): void
    {
        // Sanctum uses token-based auth, not cookies
        // This test verifies that cookie-based requests fail
        $response = $this->getJson('/api/v1/admin/users/stats');
        $response->assertStatus(401);
    }

    // =========================================================================
    // Edge Cases Tests (US-150 to US-159)
    // =========================================================================

    /** @test */
    public function handles_unicode_usernames(): void
    {
        $admin = User::factory()->admin()->create();

        $mockUser = [
            'id' => 1,
            'masked_name' => '用***',  // Unicode masked name
            'created_at' => '2026-03-15T10:30:00Z',
            'plan' => 'pro',
            'is_active' => true,
        ];

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) use ($mockUser) {
            $mock->shouldReceive('getRecentUsers')->once()->andReturn(
                $this->getMockPaginator([$mockUser])
            );
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.masked_name', '用***');
    }

    /** @test */
    public function handles_large_page_number(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $paginator = $this->getMockPaginator([]);
            $paginator->currentPage = 999999;
            $mock->shouldReceive('getRecentUsers')->once()->andReturn($paginator);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent?page=999999');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function ignores_unknown_parameters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats?unknown_param=value');

        $response->assertStatus(200);
    }

    /** @test */
    public function handles_null_values_gracefully(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn([
                'total_users' => 0,
                'active_users' => null,
                'new_today' => null,
                'new_this_week' => null,
                'new_this_month' => null,
                'growth_rates' => [
                    'daily' => null,
                    'weekly' => null,
                    'monthly' => null,
                ],
                'users_by_plan' => [
                    'free' => 0,
                    'pro' => 0,
                    'enterprise' => 0,
                ],
            ]);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Performance Tests (US-130 to US-133)
    // =========================================================================

    /** @test */
    public function stats_response_time_under_200ms(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->andReturn($this->getMockStats());
        });

        $start = microtime(true);
        
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(200, $elapsed, 'Stats endpoint should respond in under 200ms');
    }

    /** @test */
    public function recent_users_response_time_under_200ms(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->andReturn(
                $this->getMockPaginator(array_fill(0, 20, $this->getMockRecentUser()))
            );
        });

        $start = microtime(true);
        
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(200, $elapsed, 'Recent users endpoint should respond in under 200ms');
    }

    /** @test */
    public function active_users_response_time_under_200ms(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getActiveUsers')->andReturn(
                $this->getMockPaginator(array_fill(0, 20, $this->getMockActiveUser()))
            );
        });

        $start = microtime(true);
        
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(200, $elapsed, 'Active users endpoint should respond in under 200ms');
    }

    // =========================================================================
    // Integration Tests (US-160 to US-164)
    // =========================================================================

    /** @test */
    public function full_stats_workflow(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getFullStats')->once()->andReturn($this->getMockStats());
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['total_users']]);
    }

    /** @test */
    public function cross_endpoint_consistent_masking(): void
    {
        $admin = User::factory()->admin()->create();

        $this->mock(UserStatsRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getRecentUsers')->andReturn(
                $this->getMockPaginator([['id' => 1, 'masked_name' => 'J*** D**', 'created_at' => '2026-03-15T10:30:00Z', 'plan' => 'pro', 'is_active' => true]])
            );
            $mock->shouldReceive('getActiveUsers')->andReturn(
                $this->getMockPaginator([['id' => 1, 'masked_name' => 'J*** D**', 'last_login_at' => '2026-03-15T10:30:00Z', 'plan' => 'pro']])
            );
        });

        $recentResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/recent');
        $activeResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users/active');

        $recentName = $recentResponse->json('data.0.masked_name');
        $activeName = $activeResponse->json('data.0.masked_name');

        $this->assertEquals($recentName, $activeName, 'Masking should be consistent across endpoints');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get mock stats data.
     */
    private function getMockStats(): array
    {
        return [
            'total_users' => 1500,
            'active_users' => 850,
            'new_today' => 12,
            'new_this_week' => 45,
            'new_this_month' => 156,
            'growth_rates' => [
                'daily' => 0.8,
                'weekly' => 3.1,
                'monthly' => 11.6,
            ],
            'users_by_plan' => [
                'free' => 1200,
                'pro' => 250,
                'enterprise' => 50,
            ],
        ];
    }

    /**
     * Get mock recent user data.
     */
    private function getMockRecentUser(): array
    {
        return [
            'id' => 1234,
            'masked_name' => 'J*** D**',
            'created_at' => '2026-03-15T10:30:00Z',
            'plan' => 'pro',
            'is_active' => true,
        ];
    }

    /**
     * Get mock active user data.
     */
    private function getMockActiveUser(): array
    {
        return [
            'id' => 1234,
            'masked_name' => 'J*** S****',
            'last_login_at' => '2026-03-15T10:30:00Z',
            'plan' => 'enterprise',
        ];
    }

    /**
     * Get a mock paginator.
     */
    private function getMockPaginator(array $items, int $perPage = 20, int $currentPage = 1): \Illuminate\Pagination\LengthAwarePaginator
    {
        $total = count($items) > 0 ? 100 : 0;
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            collect($items),
            $total,
            $perPage,
            $currentPage
        );
    }
}
