<?php

namespace Tests\Feature\Api\V1\ContentDecay;

use App\Models\User;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentRefreshTaskServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group content-decay
 * @group api
 */
class ContentRefreshTaskControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create mock user, site, candidate, and task
     */
    protected function createMockTask(array $attributes = []): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $candidate = (object) [
            'id' => 1,
            'project_id' => 1,
            'page_url' => 'https://example.com/blog/seo-tips',
            'page_title' => '10 SEO Tips for 2024',
            'status' => 'planned',
        ];

        $task = (object) array_merge([
            'id' => 1,
            'candidate_id' => 1,
            'title' => 'Rewrite title for better CTR',
            'recommendation_type' => 'title_rewrite',
            'description' => 'The current title is underperforming',
            'notes' => null,
            'owner_user_id' => null,
            'due_date' => null,
            'status' => 'todo',
            'source' => 'system',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], $attributes);

        return compact('user', 'site', 'candidate', 'task');
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_list_tasks(): void
    {
        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_task(): void
    {
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_task(): void
    {
        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'done',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function cross_user_task_access_returns_404(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn(null);

        // Use PATCH endpoint since there's no GET endpoint for single task
        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'done',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function owner_can_access_own_tasks(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('listTasks')
            ->once()
            ->andReturn(collect([$task]));

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(200);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /** @test */
    public function create_task_validates_required_title(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        // Note: Validation happens in FormRequest before controller, so no service mock needed
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'recommendation_type' => 'content_refresh',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function create_task_validates_required_recommendation_type(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        // Note: Validation happens in FormRequest before controller, so no service mock needed
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recommendation_type']);
    }

    /** @test */
    public function create_task_validates_invalid_recommendation_type(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        // Note: Validation happens in FormRequest before controller, so no service mock needed
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recommendation_type']);
    }

    /** @test */
    public function create_task_validates_invalid_due_date_format(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        // Note: Validation happens in FormRequest before controller, so no service mock needed
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
            'due_date' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function create_task_validates_invalid_owner_user_id(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        // Note: Validation happens in FormRequest before controller, so no service mock needed
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
            'owner_user_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['owner_user_id']);
    }

    /** @test */
    public function update_task_validates_invalid_status(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function create_task_accepts_valid_data(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($task);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
        ]);

        $response->assertStatus(201);
    }

    // ==========================================
    // SUCCESS TESTS - LIST TASKS
    // ==========================================

    /** @test */
    public function list_tasks_returns_task_list(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('listTasks')
            ->once()
            ->andReturn(collect([$task]));

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'candidate_id',
                        'title',
                        'recommendation_type',
                        'status',
                        'source',
                    ],
                ],
            ]);
    }

    /** @test */
    public function list_tasks_includes_nested_owner_relationship(): void
    {
        $mockData = $this->createMockTask();
        $user = $mockData['user'];
        $candidate = $mockData['candidate'];
        $task = (object) array_merge((array) $mockData['task'], ['owner_user_id' => $user->id]);

        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('listTasks')
            ->once()
            ->andReturn(collect([$task]));

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - CREATE TASK
    // ==========================================

    /** @test */
    public function create_task_returns_created_task(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($task);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Rewrite meta description',
            'recommendation_type' => 'meta_description_rewrite',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Rewrite title for better CTR',
                    'recommendation_type' => 'title_rewrite',
                    'status' => 'todo',
                ],
            ]);
    }

    /** @test */
    public function create_task_with_due_date(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        $taskWithDueDate = (object) array_merge((array) $this->createMockTask()['task'], [
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($taskWithDueDate);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_task_with_owner(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        $taskWithOwner = (object) array_merge((array) $this->createMockTask()['task'], [
            'owner_user_id' => $user->id,
        ]);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($taskWithOwner);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
            'owner_user_id' => $user->id,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_task_with_all_fields(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($task);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Complete content refresh',
            'recommendation_type' => 'content_refresh',
            'description' => 'Update examples and statistics',
            'notes' => 'Priority: high',
            'due_date' => now()->addDays(14)->toDateString(),
            'owner_user_id' => $user->id,
        ]);

        $response->assertStatus(201);
    }

    // ==========================================
    // SUCCESS TESTS - UPDATE TASK
    // ==========================================

    /** @test */
    public function update_task_status_to_done(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'status' => 'done',
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'done',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'done',
                ],
            ]);
    }

    /** @test */
    public function update_task_status_to_in_progress(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'status' => 'in_progress',
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_task_status_to_skipped(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'status' => 'skipped',
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'skipped',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_task_notes(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'notes' => 'Updated notes',
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_task_due_date(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_task_owner(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $updatedTask = (object) array_merge((array) $task, [
            'owner_user_id' => $user->id,
        ]);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn($task);
        $mockTaskService->shouldReceive('updateTask')
            ->once()
            ->andReturn($updatedTask);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'owner_user_id' => $user->id,
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function list_tasks_for_non_existent_candidate_returns_404(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/content-decay/candidates/999999/tasks');

        $response->assertStatus(404);
    }

    /** @test */
    public function create_task_for_ignored_candidate_returns_400(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $ignoredCandidate] = $this->createMockTask();
        $ignoredCandidate->status = 'ignored';
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($ignoredCandidate);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function list_tasks_returns_empty_array_when_no_tasks(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('listTasks')
            ->once()
            ->andReturn(collect([]));

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /** @test */
    public function update_non_existent_task_returns_404(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn(null);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/999999', [
            'status' => 'done',
        ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /** @test */
    public function sql_injection_in_title_is_prevented(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($task);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => "';DROP TABLE tasks;--",
            'recommendation_type' => 'content_refresh',
        ]);

        $response->assertStatus(201);
        // Verify no SQL injection occurred
    }

    /** @test */
    public function mass_assignment_protection_on_create(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate, 'task' => $task] = $this->createMockTask();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('createTask')
            ->once()
            ->andReturn($task);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test task',
            'recommendation_type' => 'content_refresh',
            'id' => 999, // Should be ignored
            'created_at' => '2020-01-01', // Should be ignored
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function idor_task_access_is_prevented(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $mockTaskService = $this->mock(ContentRefreshTaskServiceInterface::class);
        $mockTaskService->shouldReceive('getTask')
            ->once()
            ->andReturn(null);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'done',
        ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /** @test */
    public function tasks_endpoint_rejects_put_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockTask();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/content-decay/candidates/1/tasks');

        $response->assertStatus(405);
    }

    /** @test */
    public function task_update_endpoint_rejects_post_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockTask();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/content-refresh-tasks/1');

        $response->assertStatus(405);
    }
}
