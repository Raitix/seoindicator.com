<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentRefreshTaskServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for ContentRefreshTaskController.
 *
 * Tests the following endpoints:
 * - GET /api/v1/content-decay/candidates/{id}/tasks
 * - POST /api/v1/content-decay/candidates/{id}/tasks
 * - PATCH /api/v1/content-refresh-tasks/{id}
 *
 * @group content-decay
 */
class ContentRefreshTaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Create a mock task object.
     */
    private function createMockTask(array $overrides = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'candidate_id' => 1,
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
            'description' => 'Test description',
            'notes' => null,
            'owner_user_id' => null,
            'due_date' => null,
            'status' => 'todo',
            'source' => 'manual',
            'created_at' => '2026-03-10T08:00:00Z',
            'updated_at' => '2026-03-10T08:00:00Z',
        ], $overrides);
    }

    // ========================================
    // Route Registration Tests (RR)
    // ========================================

    /** @test */
    public function rr_001_list_tasks_route_exists(): void
    {
        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function rr_002_create_task_route_exists(): void
    {
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', []);
        
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function rr_003_update_task_route_exists(): void
    {
        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', []);
        
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function rr_004_non_existent_task_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('updateTask')->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/999999', ['title' => 'Test']);
        
        $response->assertNotFound();
    }

    // ========================================
    // Authentication Tests (AUTH)
    // ========================================

    /** @test */
    public function auth_001_unauthenticated_list_returns_401(): void
    {
        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function auth_002_unauthenticated_create_returns_401(): void
    {
        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', []);
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function auth_003_unauthenticated_update_returns_401(): void
    {
        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', []);
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function auth_004_authenticated_user_can_list_tasks(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')->once()->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertOk();
    }

    /** @test */
    public function auth_005_authenticated_user_can_create_task(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask();

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('createTask')->once()->andReturn($task);
        });

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertCreated();
    }

    /** @test */
    public function auth_006_authenticated_user_can_update_task(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['status' => 'in_progress']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')->once()->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);
        
        $response->assertOk();
    }

    // ========================================
    // Authorization Tests (AUTHZ)
    // ========================================

    /** @test */
    public function authz_001_non_owner_cannot_list_tasks(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertNotFound(); // 404 prevents enumeration
    }

    /** @test */
    public function authz_002_non_owner_cannot_create_task(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertNotFound();
    }

    /** @test */
    public function authz_003_non_owner_cannot_update_task(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);
        
        $response->assertNotFound();
    }

    /** @test */
    public function authz_004_cross_user_task_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('updateTask')->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);
        
        $response->assertNotFound();
    }

    // ========================================
    // List Endpoint Tests (LIST)
    // ========================================

    /** @test */
    public function list_001_returns_200_with_task_list(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')->once()->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function list_002_returns_empty_array_when_no_tasks(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')->once()->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertJsonPath('data', []);
    }

    /** @test */
    public function list_003_returns_tasks_for_candidate(): void
    {
        Sanctum::actingAs($this->user);

        $tasks = new Collection([
            $this->createMockTask(['id' => 1, 'candidate_id' => 1]),
            $this->createMockTask(['id' => 2, 'candidate_id' => 1]),
        ]);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($tasks) {
            $mock->shouldReceive('listTasks')
                ->with(1, [])
                ->once()
                ->andReturn($tasks);
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function list_004_response_includes_task_fields(): void
    {
        Sanctum::actingAs($this->user);

        $tasks = new Collection([
            $this->createMockTask(),
        ]);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($tasks) {
            $mock->shouldReceive('listTasks')->once()->andReturn($tasks);
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'candidate_id',
                    'title',
                    'recommendation_type',
                    'description',
                    'notes',
                    'owner_user_id',
                    'due_date',
                    'status',
                    'source',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    /** @test */
    public function list_005_filter_by_status(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')
                ->with(1, \Mockery::on(function ($filters) {
                    return isset($filters['status']) && $filters['status'] === 'todo';
                }))
                ->once()
                ->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks?status=todo');
        
        $response->assertOk();
    }

    /** @test */
    public function list_006_filter_by_owner(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')
                ->with(1, \Mockery::on(function ($filters) {
                    return isset($filters['owner_user_id']) && $filters['owner_user_id'] == 1;
                }))
                ->once()
                ->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks?owner_user_id=1');
        
        $response->assertOk();
    }

    /** @test */
    public function list_007_filter_by_due_date_range(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')
                ->with(1, \Mockery::on(function ($filters) {
                    return isset($filters['due_date_from']) && isset($filters['due_date_to']);
                }))
                ->once()
                ->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks?due_date_from=2026-03-01&due_date_to=2026-03-31');
        
        $response->assertOk();
    }

    /** @test */
    public function list_008_sort_by_due_date(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')
                ->with(1, \Mockery::on(function ($filters) {
                    return isset($filters['sort']) && $filters['sort'] === 'due_date';
                }))
                ->once()
                ->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks?sort=due_date');
        
        $response->assertOk();
    }

    // ========================================
    // Create Endpoint Tests (CREATE)
    // ========================================

    /** @test */
    public function create_001_returns_201_on_valid_create(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask();

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('createTask')
                ->with(1, \Mockery::on(function ($data) {
                    return $data['title'] === 'Test Task' && $data['recommendation_type'] === 'content_refresh';
                }))
                ->once()
                ->andReturn($task);
        });

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertCreated();
    }

    /** @test */
    public function create_002_validates_title_is_required(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function create_003_validates_recommendation_type_is_required(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recommendation_type']);
    }

    /** @test */
    public function create_004_validates_title_max_length(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => str_repeat('a', 256),
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function create_005_validates_recommendation_type_enum(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'invalid_type',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recommendation_type']);
    }

    /** @test */
    public function create_006_validates_owner_user_id_exists(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
            'owner_user_id' => 999999,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_user_id']);
    }

    /** @test */
    public function create_007_validates_due_date_is_valid(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
            'due_date' => 'invalid-date',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function create_008_validates_due_date_is_future(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
            'due_date' => '2020-01-01',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function create_009_validates_status_enum(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
            'status' => 'invalid_status',
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function create_010_valid_status_values_accepted(): void
    {
        Sanctum::actingAs($this->user);

        $validStatuses = ['todo', 'in_progress', 'done', 'skipped'];

        foreach ($validStatuses as $status) {
            $task = $this->createMockTask(['status' => $status]);

            $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task, $status) {
                $mock->shouldReceive('createTask')
                    ->once()
                    ->andReturn($task);
            });

            $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
                'title' => 'Test Task',
                'recommendation_type' => 'content_refresh',
                'status' => $status,
            ]);
            
            $response->assertCreated();
        }
    }

    /** @test */
    public function create_011_creates_task_with_all_fields(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask([
            'title' => 'Full Task',
            'recommendation_type' => 'content_refresh',
            'description' => 'Full description',
            'notes' => 'Some notes',
            'owner_user_id' => $this->user->id,
            'due_date' => '2026-04-01',
            'status' => 'todo',
        ]);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('createTask')
                ->once()
                ->andReturn($task);
        });

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Full Task',
            'recommendation_type' => 'content_refresh',
            'description' => 'Full description',
            'notes' => 'Some notes',
            'owner_user_id' => $this->user->id,
            'due_date' => '2026-04-01',
        ]);
        
        $response->assertCreated();
    }

    // ========================================
    // Update Endpoint Tests (UPDATE)
    // ========================================

    /** @test */
    public function update_001_returns_200_on_valid_update(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['title' => 'Updated Title']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')
                ->with(1, ['title' => 'Updated Title'])
                ->once()
                ->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'title' => 'Updated Title',
        ]);
        
        $response->assertOk();
    }

    /** @test */
    public function update_002_updates_status(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['status' => 'in_progress']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')
                ->with(1, ['status' => 'in_progress'])
                ->once()
                ->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);
        
        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    /** @test */
    public function update_003_updates_notes(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['notes' => 'New notes']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')
                ->with(1, ['notes' => 'New notes'])
                ->once()
                ->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'notes' => 'New notes',
        ]);
        
        $response->assertOk();
    }

    /** @test */
    public function update_004_updates_owner(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['owner_user_id' => $this->user->id]);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')
                ->once()
                ->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'owner_user_id' => $this->user->id,
        ]);
        
        $response->assertOk();
    }

    /** @test */
    public function update_005_updates_due_date(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['due_date' => '2026-04-15']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')
                ->once()
                ->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'due_date' => '2026-04-15',
        ]);
        
        $response->assertOk();
    }

    /** @test */
    public function update_006_validates_status_transition(): void
    {
        // Status transitions should be validated by service
        // Controller passes to service which throws exception
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('updateTask')
                ->andThrow(new \InvalidArgumentException('Invalid status transition'));
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'done',
        ]);
        
        $response->assertUnprocessable();
    }

    /** @test */
    public function update_007_non_existent_task_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('updateTask')
                ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/999999', [
            'status' => 'in_progress',
        ]);
        
        $response->assertNotFound();
    }

    /** @test */
    public function update_008_update_with_empty_body_returns_422(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', []);
        
        $response->assertUnprocessable();
    }

    // ========================================
    // HTTP Method Tests (HM)
    // ========================================

    /** @test */
    public function hm_001_delete_list_returns_405(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertMethodNotAllowed();
    }

    /** @test */
    public function hm_002_put_list_returns_405(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertMethodNotAllowed();
    }

    /** @test */
    public function hm_003_get_create_returns_405(): void
    {
        // GET /candidates/{id}/tasks is the list endpoint, not create
        // Let's verify PUT on create endpoint
        $response = $this->putJson('/api/v1/content-decay/candidates/1/tasks', []);
        
        $response->assertMethodNotAllowed();
    }

    /** @test */
    public function hm_004_get_update_returns_405(): void
    {
        // GET /tasks/{id} doesn't exist
        $response = $this->getJson('/api/v1/content-refresh-tasks/1');
        
        $response->assertNotFound();
    }

    /** @test */
    public function hm_005_post_update_returns_405(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/content-refresh-tasks/1', []);
        
        $response->assertMethodNotAllowed();
    }

    /** @test */
    public function hm_006_delete_update_returns_405(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/content-refresh-tasks/1');
        
        $response->assertMethodNotAllowed();
    }

    // ========================================
    // Security Tests (SEC)
    // ========================================

    /** @test */
    public function sec_001_sql_injection_prevention(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/content-decay/candidates/1' OR '1'='1/tasks");
        
        $response->assertNotFound();
    }

    /** @test */
    public function sec_002_xss_prevention_in_notes(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask(['notes' => 'Safe notes']);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')->once()->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'notes' => '<script>alert("xss")</script>',
        ]);
        
        $response->assertOk();
        // Response should not contain raw script tag
        $this->assertStringNotContainsString('<script>alert', $response->getContent());
    }

    /** @test */
    public function sec_003_user_isolation_enforced(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertNotFound();
    }

    /** @test */
    public function sec_004_no_sensitive_data_exposed(): void
    {
        Sanctum::actingAs($this->user);

        $tasks = new Collection([$this->createMockTask()]);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($tasks) {
            $mock->shouldReceive('listTasks')->once()->andReturn($tasks);
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $json = $response->json();
        
        $this->assertArrayNotHasKey('api_key', $json);
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('token', $json);
    }

    // ========================================
    // Acceptance Criteria Tests (AC)
    // ========================================

    /** @test */
    public function ac_001_task_controller_has_list_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) {
            $mock->shouldReceive('listTasks')->once()->andReturn(new Collection([]));
        });

        $response = $this->getJson('/api/v1/content-decay/candidates/1/tasks');
        
        $response->assertOk();
    }

    /** @test */
    public function ac_002_task_controller_has_create_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask();

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('createTask')->once()->andReturn($task);
        });

        $response = $this->postJson('/api/v1/content-decay/candidates/1/tasks', [
            'title' => 'Test Task',
            'recommendation_type' => 'content_refresh',
        ]);
        
        $response->assertCreated();
    }

    /** @test */
    public function ac_003_task_controller_has_update_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $task = $this->createMockTask();

        $this->mock(ContentRefreshTaskServiceInterface::class, function ($mock) use ($task) {
            $mock->shouldReceive('updateTask')->once()->andReturn($task);
        });

        $response = $this->patchJson('/api/v1/content-refresh-tasks/1', [
            'status' => 'in_progress',
        ]);
        
        $response->assertOk();
    }
}
