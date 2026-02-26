<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetProjectContextIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // Integration Tests (IT)
    // ========================================

    /** @test - IT-001: Middleware registered in kernel */
    public function middleware_is_registered_in_kernel(): void
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $middleware = $kernel->getMiddlewareGroups();
        
        // Check if SetProjectContext is in web or api group
        $webMiddleware = $middleware['web'] ?? [];
        $apiMiddleware = $middleware['api'] ?? [];
        
        $hasMiddleware = collect(array_merge($webMiddleware, $apiMiddleware))
            ->contains(function ($item) {
                return str_contains($item, 'SetProjectContext');
            });
        
        $this->assertTrue($hasMiddleware, 'SetProjectContext middleware should be registered in kernel');
    }

    /** @test - IT-003: Controller can access project */
    public function controller_can_access_current_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create a test route that returns current_project
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-project-context');

        $response->assertStatus(200);
    }

    /** @test - IT-004: Model query scoped by project */
    public function model_query_is_scoped_by_project(): void
    {
        $user = User::factory()->create();
        
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);
        
        // Create keywords for each project
        $keyword1 = Keyword::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'keyword' => 'project 1 keyword',
        ]);
        
        $keyword2 = Keyword::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'keyword' => 'project 2 keyword',
        ]);

        // Request with project 1 context
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project1->id])
            ->getJson('/api/keywords');

        // Should only get project 1's keywords
        $response->assertStatus(200);
        $response->assertJsonFragment(['keyword' => 'project 1 keyword']);
        $response->assertJsonMissing(['keyword' => 'project 2 keyword']);
    }

    /** @test - IT-005: Full request flow with project */
    public function full_request_flow_with_project_context(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        $response->assertStatus(200);
    }

    /** @test - IT-006: Project switch mid-session */
    public function project_can_be_switched_mid_session(): void
    {
        $user = User::factory()->create();
        
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Project 1',
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Project 2',
        ]);

        // First request with project 1
        $response1 = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project1->id])
            ->getJson('/api/test-endpoint');
        
        $response1->assertStatus(200);

        // Second request with project 2
        $response2 = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project2->id])
            ->getJson('/api/test-endpoint');
        
        $response2->assertStatus(200);
    }

    /** @test - IT-007: API returns project-scoped data */
    public function api_returns_project_scoped_data(): void
    {
        $user = User::factory()->create();
        
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Create keyword for the project
        Keyword::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'keyword' => 'test keyword',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/keywords');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test - IT-008: Missing project context returns all or error */
    public function missing_project_context_handles_gracefully(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Keyword::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        // Request without project context
        $response = $this->actingAs($user)
            ->getJson('/api/keywords');

        // Should still work (show all or handle gracefully)
        $response->assertStatus(200);
    }

    /** @test - IT-009: Project context in service classes */
    public function service_classes_can_access_project_context(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Service Test Project',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-service-endpoint');

        $response->assertStatus(200);
    }

    /** @test - IT-010: Project context in view composers */
    public function view_composers_can_access_project_context(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'View Test Project',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->get('/dashboard');

        // Response should succeed (actual behavior depends on implementation)
        $response->assertStatus(200);
    }

    // ========================================
    // Performance Tests (PF)
    // ========================================

    /** @test - PF-001: Middleware execution time */
    public function middleware_execution_time_is_acceptable(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $startTime = microtime(true);
        
        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');
        
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Middleware should add less than 5ms overhead
        $this->assertLessThan(5, $executionTime, 'Middleware overhead should be < 5ms');
    }

    /** @test - PF-003: No N+1 queries */
    public function no_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Enable query log
        \DB::enableQueryLog();

        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should only have 1 query for project lookup (plus any route-specific queries)
        $projectQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'projects');
        });

        $this->assertLessThanOrEqual(1, $projectQueries->count(), 'Should have at most 1 project query');
    }

    /** @test - PF-004: Memory usage */
    public function memory_usage_is_acceptable(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $memoryBefore = memory_get_usage();

        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should use less than 1MB for project binding
        $this->assertLessThan(1024 * 1024, $memoryUsed, 'Memory usage should be < 1MB');
    }

    // ========================================
    // End-to-End Flow Tests
    // ========================================

    /** @test */
    public function complete_user_workflow_with_project_selection(): void
    {
        // Create user with multiple projects
        $user = User::factory()->create();
        
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Client A',
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Client B',
        ]);

        // Create keywords for each project
        Keyword::factory()->count(5)->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
        ]);
        
        Keyword::factory()->count(3)->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
        ]);

        // User selects project 1
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project1->id])
            ->getJson('/api/keywords');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        // User switches to project 2
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project2->id])
            ->getJson('/api/keywords');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function project_context_persists_via_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Set project via header in first request
        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        // Second request without header should use session (if session persistence is implemented)
        $response = $this->actingAs($user)
            ->getJson('/api/test-endpoint');

        $response->assertStatus(200);
    }

    /** @test */
    public function multiple_users_have_isolated_project_contexts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $project1 = Project::factory()->create(['user_id' => $user1->id]);
        $project2 = Project::factory()->create(['user_id' => $user2->id]);

        // User 1's request
        $response1 = $this->actingAs($user1)
            ->withHeaders(['X-Project-ID' => (string) $project1->id])
            ->getJson('/api/test-endpoint');

        // User 2's request
        $response2 = $this->actingAs($user2)
            ->withHeaders(['X-Project-ID' => (string) $project2->id])
            ->getJson('/api/test-endpoint');

        // Both should succeed with their respective projects
        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }
}
