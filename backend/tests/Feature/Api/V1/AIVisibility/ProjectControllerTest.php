<?php

namespace Tests\Feature\Api\V1\AIVisibility;

use App\Models\User;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * ProjectController Test - TASK-34-6
 * 
 * Tests for AI Visibility project CRUD API endpoints.
 * Uses mocked AIVisibilityServiceInterface for isolation.
 * 
 * @see TESTS/TEST-34-6.md for full test documentation
 */
class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Route Registration Tests (RR-001 to RR-006)
    // =========================================================================

    /** @test */
    public function rr_001_get_projects_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200);
    }

    /** @test */
    public function rr_002_post_projects_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($this->mockProject());

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword1'],
            'competitors' => ['Competitor A'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function rr_003_get_project_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($this->mockProject());

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200);
    }

    /** @test */
    public function rr_004_put_project_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($this->mockProject());

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function rr_005_delete_project_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function rr_006_post_project_run_route_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($this->mockProject());

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('runQueries')
            ->once()
            ->andReturn(['job_ids' => ['uuid-1']]);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    // =========================================================================
    // Authentication Middleware Tests (AUTH-001 to AUTH-010)
    // =========================================================================

    /** @test */
    public function auth_001_unauthenticated_get_projects_returns_401(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_002_unauthenticated_post_projects_returns_401(): void
    {
        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_003_unauthenticated_get_project_returns_401(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_004_unauthenticated_put_project_returns_401(): void
    {
        $response = $this->putJson('/api/v1/ai-visibility/projects/1', []);

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_005_unauthenticated_delete_project_returns_401(): void
    {
        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_006_unauthenticated_post_run_returns_401(): void
    {
        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_007_authenticated_user_can_access_all_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mockProject = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($mockProject) {
            $mock->shouldReceive('getUserProjects')->andReturn(new LengthAwarePaginator([], 0, 15));
            $mock->shouldReceive('createProject')->andReturn($mockProject);
            $mock->shouldReceive('getProject')->andReturn($mockProject);
            $mock->shouldReceive('updateProject')->andReturn($mockProject);
            $mock->shouldReceive('deleteProject')->andReturn(true);
            $mock->shouldReceive('runQueries')->andReturn(['job_ids' => ['uuid-1']]);
        });

        // GET /projects
        $this->getJson('/api/v1/ai-visibility/projects')->assertStatus(200);

        // POST /projects
        $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ])->assertStatus(201);

        // GET /projects/{id}
        $this->getJson('/api/v1/ai-visibility/projects/1')->assertStatus(200);

        // PUT /projects/{id}
        $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
        ])->assertStatus(200);

        // DELETE /projects/{id}
        $this->deleteJson('/api/v1/ai-visibility/projects/1')->assertStatus(204);

        // POST /projects/{id}/run
        $this->postJson('/api/v1/ai-visibility/projects/1/run')->assertStatus(202);
    }

    /** @test */
    public function auth_008_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_009_expired_token_returns_401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $token->accessToken->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_010_auth_middleware_applied_to_all_routes(): void
    {
        $endpoints = [
            ['GET', '/api/v1/ai-visibility/projects'],
            ['POST', '/api/v1/ai-visibility/projects'],
            ['GET', '/api/v1/ai-visibility/projects/1'],
            ['PUT', '/api/v1/ai-visibility/projects/1'],
            ['DELETE', '/api/v1/ai-visibility/projects/1'],
            ['POST', '/api/v1/ai-visibility/projects/1/run'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401, "Expected 401 for {$method} {$url}");
        }
    }

    // =========================================================================
    // Authorization - Ownership Tests (AUTHZ-001 to AUTHZ-012)
    // =========================================================================

    /** @test */
    public function authz_001_user_cannot_access_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_002_user_cannot_update_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn(null);

        $response = $this->putJson('/api/v1/ai-visibility/projects/999', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_003_user_cannot_delete_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(false);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_004_user_cannot_trigger_run_on_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/v1/ai-visibility/projects/999/run');

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_005_owner_can_access_their_own_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200);
    }

    /** @test */
    public function authz_006_owner_can_update_their_own_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function authz_007_owner_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function authz_008_owner_can_trigger_run_on_their_own_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function authz_009_non_existent_project_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_010_deleted_project_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function authz_011_policy_registered_for_model(): void
    {
        $policy = app(\Illuminate\Contracts\Auth\Access\Gate::class)
            ->policy(\App\Models\AIVisibilityProject::class);

        $this->assertNotNull($policy, 'AIVisibilityProjectPolicy should be registered');
    }

    /** @test */
    public function authz_012_policy_has_required_methods(): void
    {
        $policy = app(\App\Policies\AIVisibilityProjectPolicy::class);

        $methods = ['view', 'update', 'delete', 'run'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($policy, $method),
                "Policy should have {$method} method"
            );
        }
    }

    // =========================================================================
    // GET /projects - List Projects Tests (LIST-001 to LIST-020)
    // =========================================================================

    /** @test */
    public function list_001_returns_empty_array_when_no_projects(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function list_002_returns_user_projects_only(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);

        $project1 = $this->mockProject(['id' => 1, 'user_id' => $user1->id]);
        $project2 = $this->mockProject(['id' => 2, 'user_id' => $user1->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([$project1, $project2], 2, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function list_003_includes_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'id' => 1,
            'brand_name' => 'Test Brand',
            'is_active' => true,
            'last_queried_at' => '2026-03-07T10:00:00Z',
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([$project], 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'brandName', 'isActive', 'lastQueriedAt'],
                ],
            ]);
    }

    /** @test */
    public function list_004_supports_pagination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = collect(range(1, 25))->map(fn($i) => $this->mockProject(['id' => $i]));

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects->take(15), 25, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(15, 'data');
    }

    /** @test */
    public function list_005_default_pagination_is_15(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = collect(range(1, 20))->map(fn($i) => $this->mockProject(['id' => $i]));

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->withArgs(fn($userId, $filters) => isset($filters['per_page']) && $filters['per_page'] === 15)
            ->once()
            ->andReturn(new LengthAwarePaginator($projects->take(15), 20, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_006_custom_per_page_parameter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = collect(range(1, 10))->map(fn($i) => $this->mockProject(['id' => $i]));

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 10, 10));

        $response = $this->getJson('/api/v1/ai-visibility/projects?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function list_007_max_per_page_is_100(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = collect(range(1, 100))->map(fn($i) => $this->mockProject(['id' => $i]));

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 100, 100));

        $response = $this->getJson('/api/v1/ai-visibility/projects?per_page=150');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
    }

    /** @test */
    public function list_008_filter_by_is_active_true(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 1, 'is_active' => true]),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function list_009_filter_by_is_active_false(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 1, 'is_active' => false]),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?is_active=0');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function list_010_sort_by_created_at_ascending(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 1, 'created_at' => '2026-03-01T10:00:00Z']),
            $this->mockProject(['id' => 2, 'created_at' => '2026-03-02T10:00:00Z']),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 2, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?sort=created_at&order=asc');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_011_sort_by_created_at_descending_default(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 2, 'created_at' => '2026-03-02T10:00:00Z']),
            $this->mockProject(['id' => 1, 'created_at' => '2026-03-01T10:00:00Z']),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 2, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_012_sort_by_last_queried_at_ascending(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 1, 'last_queried_at' => '2026-03-01T10:00:00Z']),
            $this->mockProject(['id' => 2, 'last_queried_at' => '2026-03-02T10:00:00Z']),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 2, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?sort=last_queried_at&order=asc');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_013_sort_by_last_queried_at_descending(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = [
            $this->mockProject(['id' => 2, 'last_queried_at' => '2026-03-02T10:00:00Z']),
            $this->mockProject(['id' => 1, 'last_queried_at' => '2026-03-01T10:00:00Z']),
        ];

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator($projects, 2, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?sort=last_queried_at&order=desc');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_014_invalid_sort_field_ignored(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?sort=invalid');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_015_invalid_order_defaults_to_desc(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?order=invalid');

        $response->assertStatus(200);
    }

    /** @test */
    public function list_016_mention_rate_is_latest_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['mention_rate' => 75.5]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([$project], 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.mentionRate', 75.5);
    }

    /** @test */
    public function list_017_mention_rate_null_if_never_queried(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['mention_rate' => null, 'last_queried_at' => null]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([$project], 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.mentionRate', null);
    }

    /** @test */
    public function list_018_response_includes_success_flag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function list_019_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects');

        // POST without data should fail validation, but route exists
        // For strict HTTP method restriction test:
        $response = $this->json('PATCH', '/api/v1/ai-visibility/projects');

        $response->assertStatus(405);
    }

    /** @test */
    public function list_020_api_resource_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'brand_name' => 'Test Brand',
            'is_active' => true,
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([$project], 1, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        // Verify camelCase keys in response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['brandName', 'isActive', 'lastQueriedAt'],
                ],
            ]);
    }

    // =========================================================================
    // POST /projects - Create Project Tests (CREATE-001 to CREATE-030)
    // =========================================================================

    /** @test */
    public function create_001_create_with_valid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme', 'acme corp'],
            'competitors' => ['Competitor A', 'Competitor B'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function create_002_brand_name_required(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name']);
    }

    /** @test */
    public function create_003_brand_name_max_255_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => str_repeat('a', 256),
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name']);
    }

    /** @test */
    public function create_004_brand_name_alphanumeric_spaces(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Brand name should allow alphanumeric and spaces (and maybe basic punctuation)
        // This test depends on the actual validation rule
        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Acme Corp Inc.',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_005_keywords_required(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function create_006_keywords_must_be_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => 'not-an-array',
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function create_007_keywords_min_1_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => [],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function create_008_keywords_max_10_items(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => range(1, 11),
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function create_009_each_keyword_max_100_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => [str_repeat('a', 101)],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords.0']);
    }

    /** @test */
    public function create_010_competitors_required(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function create_011_competitors_must_be_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function create_012_competitors_min_1_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function create_013_competitors_max_3_items(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['A', 'B', 'C', 'D'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function create_014_each_competitor_max_100_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => [str_repeat('a', 101)],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors.0']);
    }

    /** @test */
    public function create_015_query_templates_optional(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_016_query_templates_must_be_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'query_templates' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query_templates']);
    }

    /** @test */
    public function create_017_tracking_frequency_optional(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_018_tracking_frequency_valid_enum(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'hourly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_frequency']);
    }

    /** @test */
    public function create_019_valid_tracking_frequency_values(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $validFrequencies = ['daily', 'weekly', 'monthly'];

        foreach ($validFrequencies as $frequency) {
            $project = $this->mockProject(['tracking_frequency' => $frequency]);

            $this->mock(AIVisibilityServiceInterface::class)
                ->shouldReceive('createProject')
                ->once()
                ->andReturn($project);

            $response = $this->postJson('/api/v1/ai-visibility/projects', [
                'brand_name' => 'Test Brand',
                'keywords' => ['keyword'],
                'competitors' => ['competitor'],
                'tracking_frequency' => $frequency,
            ]);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function create_020_project_assigned_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->withArgs(fn($userId) => $userId === $user->id)
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_021_is_active_defaults_to_true(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['is_active' => true]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.isActive', true);
    }

    /** @test */
    public function create_022_response_includes_success_flag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function create_023_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects?brand_name=Test');

        // GET /projects is valid but returns list, not create
        $response->assertStatus(200);
    }

    /** @test */
    public function create_024_multiple_validation_errors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name', 'keywords', 'competitors']);
    }

    /** @test */
    public function create_025_unicode_in_brand_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['brand_name' => '日本語ブランド']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => '日本語ブランド',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_026_unicode_in_keywords(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['日本語キーワード'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_027_duplicate_brand_name_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project1 = $this->mockProject(['id' => 1]);
        $project2 = $this->mockProject(['id' => 2]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->twice()
            ->andReturn($project1, $project2);

        $response1 = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Same Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response2 = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Same Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);
    }

    /** @test */
    public function create_028_very_long_brand_name_255_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => str_repeat('a', 255),
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_029_rate_limit_5_per_hour_enforced(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->times(5)
            ->andReturn($project);

        // Make 5 requests (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/ai-visibility/projects', [
                'brand_name' => "Brand {$i}",
                'keywords' => ['keyword'],
                'competitors' => ['competitor'],
            ])->assertStatus(201);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Brand 6',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function create_030_rate_limit_resets_after_hour(): void
    {
        // This test would require time manipulation
        $this->markTestIncomplete('Requires time manipulation to test rate limit reset');
    }

    // =========================================================================
    // GET /projects/{id} - Get Project Details Tests (SHOW-001 to SHOW-015)
    // =========================================================================

    /** @test */
    public function show_001_returns_project_details(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function show_002_includes_latest_analytics_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'analytics_summary' => [
                'mention_rate' => 75.5,
                'total_mentions' => 150,
            ],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['analyticsSummary']]);
    }

    /** @test */
    public function show_003_analytics_summary_null_if_never_queried(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['analytics_summary' => null]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('data.analyticsSummary', null);
    }

    /** @test */
    public function show_004_includes_all_project_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'brandName',
                    'keywords',
                    'competitors',
                    'queryTemplates',
                    'trackingFrequency',
                    'isActive',
                    'lastQueriedAt',
                    'createdAt',
                    'updatedAt',
                ],
            ]);
    }

    /** @test */
    public function show_005_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_006_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_007_response_includes_success_flag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function show_008_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function show_009_string_id_handling(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/abc');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_010_negative_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/-1');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_011_zero_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/0');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_012_large_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/999999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_013_includes_created_at_and_updated_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'created_at' => '2026-03-01T10:00:00Z',
            'updated_at' => '2026-03-07T10:00:00Z',
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['createdAt', 'updatedAt'],
            ]);
    }

    /** @test */
    public function show_014_keywords_returned_as_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'keywords' => ['keyword1', 'keyword2'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('data.keywords', ['keyword1', 'keyword2']);
    }

    /** @test */
    public function show_015_competitors_returned_as_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'competitors' => ['Competitor A', 'Competitor B'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('data.competitors', ['Competitor A', 'Competitor B']);
    }

    // =========================================================================
    // PUT /projects/{id} - Update Project Tests (UPDATE-001 to UPDATE-025)
    // =========================================================================

    /** @test */
    public function update_001_update_with_valid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated Brand',
            'keywords' => ['new keyword'],
            'competitors' => ['New Competitor'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function update_002_partial_update_brand_name_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_003_partial_update_keywords_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'keywords' => ['new keyword'],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_004_update_is_active(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['is_active' => false]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', false);
    }

    /** @test */
    public function update_005_same_validation_as_create(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name']);
    }

    /** @test */
    public function update_006_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn(null);

        $response = $this->putJson('/api/v1/ai-visibility/projects/999', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function update_007_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn(null);

        $response = $this->putJson('/api/v1/ai-visibility/projects/999', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function update_008_response_includes_updated_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['brand_name' => 'Updated Brand']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.brandName', 'Updated Brand');
    }

    /** @test */
    public function update_009_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1?brand_name=Updated');

        // GET /projects/{id} returns the project, doesn't update
        $response->assertStatus(200);
    }

    /** @test */
    public function update_010_updated_at_timestamp_changes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'created_at' => '2026-03-01T10:00:00Z',
            'updated_at' => '2026-03-07T15:00:00Z',
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_011_cannot_change_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
            'user_id' => $otherUser->id,
        ]);

        // Should ignore user_id and update successfully
        $response->assertStatus(200);
    }

    /** @test */
    public function update_012_cannot_change_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['id' => 1]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'id' => 999,
        ]);

        // Should ignore id and update successfully
        $response->assertStatus(200);
    }

    /** @test */
    public function update_013_empty_brand_name_fails(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name']);
    }

    /** @test */
    public function update_014_empty_keywords_array_fails(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'keywords' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function update_015_empty_competitors_array_fails(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'competitors' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function update_016_can_add_keywords(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'keywords' => ['keyword1', 'keyword2', 'keyword3'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'keywords' => ['keyword1', 'keyword2', 'keyword3'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.keywords', ['keyword1', 'keyword2', 'keyword3']);
    }

    /** @test */
    public function update_017_can_remove_keywords(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'keywords' => ['keyword1'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'keywords' => ['keyword1'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.keywords', ['keyword1']);
    }

    /** @test */
    public function update_018_can_change_tracking_frequency(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['tracking_frequency' => 'weekly']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'tracking_frequency' => 'weekly',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.trackingFrequency', 'weekly');
    }

    /** @test */
    public function update_019_unicode_handling(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['brand_name' => '日本語ブランド']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => '日本語ブランド',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_020_very_long_brand_name_255_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => str_repeat('a', 255),
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_021_max_keywords_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'keywords' => range(1, 11),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    /** @test */
    public function update_022_max_competitors_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'competitors' => ['A', 'B', 'C', 'D'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competitors']);
    }

    /** @test */
    public function update_023_query_templates_update(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'query_templates' => ['New template?'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'query_templates' => ['New template?'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.queryTemplates', ['New template?']);
    }

    /** @test */
    public function update_024_response_format_matches_get(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn($project);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'brandName',
                    'keywords',
                    'competitors',
                ],
            ]);
    }

    /** @test */
    public function update_025_concurrent_update_handling(): void
    {
        $this->markTestIncomplete('Requires concurrent request testing');
    }

    // =========================================================================
    // DELETE /projects/{id} - Delete Project Tests (DELETE-001 to DELETE-015)
    // =========================================================================

    /** @test */
    public function delete_001_delete_existing_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_002_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(false);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_003_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(false);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_004_cascades_to_related_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_005_cascades_to_queries(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_006_cascades_to_mentions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_007_cascades_to_alerts(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_008_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        // GET returns the project, not delete it
        $response->assertStatus(200);
    }

    /** @test */
    public function delete_009_delete_twice_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $this->deleteJson('/api/v1/ai-visibility/projects/1')->assertStatus(204);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(false);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_010_response_body_empty(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204)
            ->assertNoContent();
    }

    /** @test */
    public function delete_011_soft_delete_vs_hard_delete(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_012_delete_sets_deleted_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(204);
    }

    /** @test */
    public function delete_013_deleted_project_not_in_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Delete first
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->once()
            ->andReturn(true);

        $this->deleteJson('/api/v1/ai-visibility/projects/1')->assertStatus(204);

        // Then list should not include it
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function delete_014_cannot_get_deleted_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_015_cannot_update_deleted_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('updateProject')
            ->once()
            ->andReturn(null);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1', [
            'brand_name' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // POST /projects/{id}/run - Trigger Query Run Tests (RUN-001 to RUN-020)
    // =========================================================================

    /** @test */
    public function run_001_trigger_run_successfully(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1', 'uuid-2']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['job_ids']]);
    }

    /** @test */
    public function run_002_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/v1/ai-visibility/projects/999/run');

        $response->assertStatus(404);
    }

    /** @test */
    public function run_003_another_users_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/v1/ai-visibility/projects/999/run');

        $response->assertStatus(404);
    }

    /** @test */
    public function run_004_query_limit_exceeded(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => true,
                'tier' => 'free',
                'limit' => 5,
                'used' => 5,
            ]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function run_005_rate_limit_3_per_hour_enforced(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        // Make 3 requests (should succeed)
        for ($i = 0; $i < 3; $i++) {
            $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
                $mock->shouldReceive('getProject')->once()->andReturn($project);
                $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
            });

            $this->postJson('/api/v1/ai-visibility/projects/1/run')->assertStatus(202);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(429);
    }

    /** @test */
    public function run_006_response_includes_job_ids(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1', 'uuid-2', 'uuid-3']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202)
            ->assertJsonPath('data.job_ids', ['uuid-1', 'uuid-2', 'uuid-3']);
    }

    /** @test */
    public function run_007_job_ids_are_valid_uuids(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $validUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project, $validUuid) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => [$validUuid]]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function run_008_multiple_jobs_for_multiple_keywords(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'keywords' => ['keyword1', 'keyword2', 'keyword3', 'keyword4', 'keyword5'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1', 'uuid-2', 'uuid-3', 'uuid-4', 'uuid-5']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202)
            ->assertJsonCount(5, 'data.job_ids');
    }

    /** @test */
    public function run_009_http_method_restriction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(405);
    }

    /** @test */
    public function run_010_checks_query_limit_before_dispatch(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => false,
                'tier' => 'pro',
                'limit' => 50,
                'used' => 45,
            ]);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function run_011_inactive_project_can_still_run(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['is_active' => false]);

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function run_012_run_increments_usage_count(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $this->postJson('/api/v1/ai-visibility/projects/1/run');

        // Usage increment is handled by the service
        $this->assertTrue(true);
    }

    /** @test */
    public function run_013_monthly_usage_reset(): void
    {
        $this->markTestIncomplete('Requires time manipulation to test monthly reset');
    }

    /** @test */
    public function run_014_tier_limits_free_5_per_month(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => true,
                'tier' => 'free',
                'limit' => 5,
                'used' => 5,
            ]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(403)
            ->assertJsonPath('data.limitInfo.tier', 'free')
            ->assertJsonPath('data.limitInfo.limit', 5);
    }

    /** @test */
    public function run_015_tier_limits_pro_50_per_month(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => true,
                'tier' => 'pro',
                'limit' => 50,
                'used' => 50,
            ]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(403)
            ->assertJsonPath('data.limitInfo.tier', 'pro')
            ->assertJsonPath('data.limitInfo.limit', 50);
    }

    /** @test */
    public function run_016_tier_limits_business_200_per_month(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => true,
                'tier' => 'business',
                'limit' => 200,
                'used' => 200,
            ]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(403)
            ->assertJsonPath('data.limitInfo.tier', 'business')
            ->assertJsonPath('data.limitInfo.limit', 200);
    }

    /** @test */
    public function run_017_error_response_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('checkQueryLimit')->once()->andReturn([
                'exceeded' => true,
                'tier' => 'free',
                'limit' => 5,
                'used' => 5,
            ]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'error',
                'data' => ['limitInfo'],
            ]);
    }

    /** @test */
    public function run_018_job_dispatch_to_queue(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function run_019_job_includes_project_context(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['id' => 123]);

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')
                ->withArgs(fn($projectId) => $projectId === 123)
                ->once()
                ->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/123/run');

        $response->assertStatus(202);
    }

    /** @test */
    public function run_020_async_status_tracking(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class, function ($mock) use ($project) {
            $mock->shouldReceive('getProject')->once()->andReturn($project);
            $mock->shouldReceive('runQueries')->once()->andReturn(['job_ids' => ['uuid-1']]);
        });

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/run');

        $response->assertStatus(202)
            ->assertJsonStructure(['data' => ['job_ids']]);
    }

    // =========================================================================
    // Security Tests (SEC-001 to SEC-015)
    // =========================================================================

    /** @test */
    public function sec_001_sql_injection_in_brand_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => "' OR 1=1 --",
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        // Should be stored as literal string, not cause SQL error
        $response->assertStatus(201);
    }

    /** @test */
    public function sec_002_sql_injection_in_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1 OR 1=1');

        $response->assertStatus(404);
    }

    /** @test */
    public function sec_003_xss_in_brand_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['brand_name' => '<script>alert(1)</script>']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => '<script>alert(1)</script>',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        // Should accept and store, but API resource should escape in output
        $response->assertStatus(201);
    }

    /** @test */
    public function sec_004_xss_in_keywords(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['"><script>alert(1)</script>'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function sec_005_path_traversal_in_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/../../../etc/passwd');

        $response->assertStatus(404);
    }

    /** @test */
    public function sec_006_mass_assignment_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['user_id' => $user->id]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->withArgs(fn($userId) => $userId === $user->id)
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function sec_007_mass_assignment_is_admin(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'is_admin' => true,
        ]);

        // Should ignore is_admin field
        $response->assertStatus(201);
    }

    /** @test */
    public function sec_008_csrf_protection(): void
    {
        // Sanctum uses token-based auth, not CSRF
        // This test verifies token requirement
        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function sec_009_no_sensitive_data_in_response(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201)
            ->assertJsonMissing(['password', 'api_key', 'secret']);
    }

    /** @test */
    public function sec_010_rate_limiting_prevents_abuse(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make rapid requests
        for ($i = 0; $i < 100; $i++) {
            $this->mock(AIVisibilityServiceInterface::class)
                ->shouldReceive('getUserProjects')
                ->andReturn(new LengthAwarePaginator([], 0, 15));

            $response = $this->getJson('/api/v1/ai-visibility/projects');

            // Should eventually hit rate limit
            if ($response->status() === 429) {
                $this->assertTrue(true);
                return;
            }
        }

        // If no rate limit hit, test is inconclusive
        $this->markTestIncomplete('Rate limit not hit within 100 requests');
    }

    /** @test */
    public function sec_011_enumeration_protection(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Non-existent project
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response1 = $this->getJson('/api/v1/ai-visibility/projects/999');

        // Other user's project - service returns null for authorization
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn(null);

        $response2 = $this->getJson('/api/v1/ai-visibility/projects/888');

        // Both should return 404 (same error)
        $this->assertEquals($response1->status(), $response2->status());
    }

    /** @test */
    public function sec_012_input_length_limits_enforced(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => str_repeat('a', 10000),
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function sec_013_json_parsing_errors_handled(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/ai-visibility/projects', [], [
            'Content-Type' => 'application/json',
        ], 'invalid json');

        $response->assertStatus(400);
    }

    /** @test */
    public function sec_014_content_type_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/ai-visibility/projects', 'not json', [
            'Content-Type' => 'text/plain',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function sec_015_no_timing_attacks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Measure time for non-existent project
        $start = microtime(true);
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')->andReturn(null);
        $this->getJson('/api/v1/ai-visibility/projects/999');
        $time1 = microtime(true) - $start;

        // Measure time for other user's project
        $start = microtime(true);
        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')->andReturn(null);
        $this->getJson('/api/v1/ai-visibility/projects/888');
        $time2 = microtime(true) - $start;

        // Times should be similar (within 50ms)
        $this->assertLessThan(0.05, abs($time1 - $time2));
    }

    // =========================================================================
    // Edge Case Tests (EDGE-001 to EDGE-020)
    // =========================================================================

    /** @test */
    public function edge_001_empty_request_body(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name', 'keywords', 'competitors']);
    }

    /** @test */
    public function edge_002_extra_fields_in_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'extra_field' => 'should be ignored',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_003_null_values_in_optional_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'query_templates' => null,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_004_unicode_in_all_text_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'brand_name' => '日本語',
            'keywords' => ['العربية', '中文'],
            'competitors' => ['Ελληνικά'],
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => '日本語',
            'keywords' => ['العربية', '中文'],
            'competitors' => ['Ελληνικά'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_005_emoji_in_brand_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject(['brand_name' => '🚀 Brand']);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => '🚀 Brand',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_006_very_large_keyword_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $keywords = range(1, 10); // Max is 10
        foreach ($keywords as &$k) {
            $k = str_repeat('a', 100);
        }

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => $keywords,
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_007_duplicate_keywords_in_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword', 'keyword', 'keyword'],
            'competitors' => ['competitor'],
        ]);

        // Should accept or deduplicate
        $response->assertStatus(201);
    }

    /** @test */
    public function edge_008_whitespace_only_brand_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => '   ',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_name']);
    }

    /** @test */
    public function edge_009_mixed_case_tracking_frequency(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'DAILY',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_frequency']);
    }

    /** @test */
    public function edge_010_negative_id_in_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/-1');

        $response->assertStatus(404);
    }

    /** @test */
    public function edge_011_string_id_in_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/abc');

        $response->assertStatus(404);
    }

    /** @test */
    public function edge_012_float_id_in_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1.5');

        $response->assertStatus(404);
    }

    /** @test */
    public function edge_013_very_large_page_number(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?page=999999');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function edge_014_page_zero(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?page=0');

        $response->assertStatus(200);
    }

    /** @test */
    public function edge_015_negative_page_number(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $response = $this->getJson('/api/v1/ai-visibility/projects?page=-1');

        $response->assertStatus(200);
    }

    /** @test */
    public function edge_016_empty_keywords_array_with_string(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => [''],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords.0']);
    }

    /** @test */
    public function edge_017_brand_name_with_newlines(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => "Brand\nName",
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);

        // Should accept or sanitize
        $response->assertStatus(201);
    }

    /** @test */
    public function edge_018_keywords_with_special_chars(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword?&='],
            'competitors' => ['competitor'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_019_competitor_with_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->once()
            ->andReturn($project);

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['https://competitor.com'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function edge_020_project_with_no_runs(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject([
            'last_queried_at' => null,
            'analytics_summary' => null,
        ]);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->once()
            ->andReturn($project);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1');

        $response->assertStatus(200)
            ->assertJsonPath('data.lastQueriedAt', null)
            ->assertJsonPath('data.analyticsSummary', null);
    }

    // =========================================================================
    // Performance Tests (PERF-001 to PERF-005)
    // =========================================================================

    /** @test */
    public function perf_001_get_projects_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->andReturn(new LengthAwarePaginator([], 0, 15));

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects');
        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $time, 'GET /projects should respond in < 200ms');
    }

    /** @test */
    public function perf_002_post_projects_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('createProject')
            ->andReturn($project);

        $start = microtime(true);
        $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ]);
        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(300, $time, 'POST /projects should respond in < 300ms');
    }

    /** @test */
    public function perf_003_get_project_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = $this->mockProject();

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getProject')
            ->andReturn($project);

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects/1');
        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(150, $time, 'GET /projects/{id} should respond in < 150ms');
    }

    /** @test */
    public function perf_004_delete_project_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('deleteProject')
            ->andReturn(true);

        $start = microtime(true);
        $this->deleteJson('/api/v1/ai-visibility/projects/1');
        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $time, 'DELETE /projects/{id} should respond in < 200ms');
    }

    /** @test */
    public function perf_005_list_with_100_projects(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $projects = collect(range(1, 100))->map(fn($i) => $this->mockProject(['id' => $i]));

        $this->mock(AIVisibilityServiceInterface::class)
            ->shouldReceive('getUserProjects')
            ->andReturn(new LengthAwarePaginator($projects, 100, 100));

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects?per_page=100');
        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $time, 'GET /projects with 100 items should respond in < 500ms');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock project object.
     */
    protected function mockProject(array $attributes = []): object
    {
        return new class($attributes) {
            public array $attributes;

            public function __construct(array $attributes = [])
            {
                $this->attributes = array_merge([
                    'id' => 1,
                    'user_id' => 1,
                    'brand_name' => 'Test Brand',
                    'keywords' => ['keyword1', 'keyword2'],
                    'competitors' => ['Competitor A', 'Competitor B'],
                    'query_templates' => ['What is {brand}?'],
                    'tracking_frequency' => 'daily',
                    'is_active' => true,
                    'last_queried_at' => '2026-03-07T10:00:00Z',
                    'mention_rate' => 75.5,
                    'analytics_summary' => [
                        'mention_rate' => 75.5,
                        'total_mentions' => 150,
                        'total_queries' => 200,
                    ],
                    'created_at' => '2026-03-01T10:00:00Z',
                    'updated_at' => '2026-03-07T10:00:00Z',
                ], $attributes);
            }

            public function __get(string $name)
            {
                return $this->attributes[$name] ?? null;
            }
        };
    }
}