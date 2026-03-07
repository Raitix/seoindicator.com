<?php

namespace Tests\Feature\Http\Controllers\Api\V1\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\User;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use App\Services\AIVisibility\Exceptions\QueryLimitExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for AIVisibilityProjectController.
 *
 * @see TASK-34-6
 * @see SPEC-034
 */
class AIVisibilityProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the AIVisibilityService
        $this->mockService();
    }

    protected function mockService(): void
    {
        $mock = Mockery::mock(AIVisibilityServiceInterface::class);

        $this->app->instance(AIVisibilityServiceInterface::class, $mock);
    }

    protected function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    // ==================== INDEX TESTS ====================

    public function test_index_returns_projects_for_authenticated_user(): void
    {
        $user = $this->actingAsUser();

        // Create projects for the user
        $projects = AIVisibilityProject::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Create projects for another user
        AIVisibilityProject::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'brand_name',
                        'keywords',
                        'competitors',
                        'is_active',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_returns_unauthenticated_for_guest(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects');

        $response->assertStatus(401);
    }

    public function test_index_filters_by_is_active(): void
    {
        $user = $this->actingAsUser();

        AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/ai-visibility/projects?is_active=true');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    // ==================== STORE TESTS ====================

    public function test_store_creates_project_successfully(): void
    {
        $user = $this->actingAsUser();

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('createProject')
            ->once()
            ->with($user->id, Mockery::on(function ($data) {
                return $data['brand_name'] === 'Test Brand'
                    && isset($data['keywords'])
                    && isset($data['competitors']);
            }))
            ->andReturnUsing(function ($userId, $data) {
                $project = new AIVisibilityProject();
                $project->id = 1;
                $project->user_id = $userId;
                $project->brand_name = $data['brand_name'];
                $project->keywords = $data['keywords'] ?? [];
                $project->competitors = $data['competitors'] ?? [];
                $project->is_active = true;
                $project->tracking_frequency = 'weekly';
                return $project;
            });

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['seo', 'marketing'],
            'competitors' => ['Competitor A', 'Competitor B'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.brand_name', 'Test Brand');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/v1/ai-visibility/projects', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'details',
                ],
            ]);
    }

    public function test_store_validates_max_competitors(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/v1/ai-visibility/projects', [
            'brand_name' => 'Test Brand',
            'keywords' => ['seo'],
            'competitors' => ['A', 'B', 'C', 'D'], // 4 competitors (max is 3)
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ==================== SHOW TESTS ====================

    public function test_show_returns_project_for_owner(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('getAnalytics')
            ->once()
            ->andReturn([
                'mention_rate_by_platform' => ['chatgpt' => 80.0, 'perplexity' => 60.0],
                'share_of_voice' => 45.5,
                'sentiment_breakdown' => ['positive' => 5, 'neutral' => 3, 'negative' => 1],
                'trend_direction' => 'up',
                'total_queries' => 10,
                'brand_mentions' => 8,
            ]);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonStructure([
                'data' => [
                    'analytics_summary',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_project(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/v1/ai-visibility/projects/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $this->actingAsUser();

        $otherUser = User::factory()->create();
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$project->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    // ==================== UPDATE TESTS ====================

    public function test_update_modifies_project_successfully(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('updateProject')
            ->once()
            ->andReturnUsing(function ($projectId, $data) use ($project) {
                $project->brand_name = $data['brand_name'] ?? $project->brand_name;
                return $project;
            });

        $response = $this->putJson("/api/v1/ai-visibility/projects/{$project->id}", [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $this->actingAsUser();

        $otherUser = User::factory()->create();
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/v1/ai-visibility/projects/{$project->id}", [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertStatus(403);
    }

    // ==================== DESTROY TESTS ====================

    public function test_destroy_deletes_project_successfully(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('deleteProject')
            ->once()
            ->with($project->id, $user->id)
            ->andReturn(true);

        $response = $this->deleteJson("/api/v1/ai-visibility/projects/{$project->id}");

        $response->assertStatus(204);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $this->actingAsUser();

        $otherUser = User::factory()->create();
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("/api/v1/ai-visibility/projects/{$project->id}");

        $response->assertStatus(403);
    }

    // ==================== RUN TESTS ====================

    public function test_run_dispatches_queries_successfully(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('runQueries')
            ->once()
            ->with($project->id)
            ->andReturn(['job_1', 'job_2']);

        $response = $this->postJson("/api/v1/ai-visibility/projects/{$project->id}/run");

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'job_ids',
                    'message',
                ],
            ]);
    }

    public function test_run_returns_429_when_limit_exceeded(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('runQueries')
            ->once()
            ->andThrow(new QueryLimitExceededException(5, 5, 'free'));

        $response = $this->postJson("/api/v1/ai-visibility/projects/{$project->id}/run");

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'QUERY_LIMIT_EXCEEDED');
    }

    public function test_run_returns_400_for_inactive_project(): void
    {
        $user = $this->actingAsUser();

        $project = AIVisibilityProject::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $mock = $this->app->make(AIVisibilityServiceInterface::class);
        $mock->shouldReceive('runQueries')
            ->once()
            ->andThrow(new \RuntimeException('Project is not active.'));

        $response = $this->postJson("/api/v1/ai-visibility/projects/{$project->id}/run");

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'PROJECT_INACTIVE');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
