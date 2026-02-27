<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for Annotation Controller with Authorization.
 * 
 * TASK-9-4: CRUD operations for keyword annotations.
 * 
 * @see TEST-9-4
 */
class AnnotationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Keyword $keyword;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
    }

    // ============================================================
    // INDEX (LIST) TESTS
    // ============================================================

    /**
     * IX-001: List annotations for keyword.
     */
    public function test_index_returns_annotations_for_keyword(): void
    {
        Annotation::factory()->count(5)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'keyword_id',
                        'project_id',
                        'date',
                        'title',
                        'description',
                        'category',
                    ],
                ],
                'meta' => ['total', 'page', 'perPage', 'last_page'],
            ]);
    }

    /**
     * IX-002: List with date range filter.
     */
    public function test_index_filters_by_date_range(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-15',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-28',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?from=2026-02-10&to=2026-02-20");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        $this->assertStringStartsWith('2026-02-15', $response->json('data.0.date'));
    }

    /**
     * IX-003: List with category filter.
     */
    public function test_index_filters_by_category(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'algorithm-update',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'content-update',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?category=algorithm-update");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'algorithm-update');
    }

    /**
     * IX-004: List with pagination.
     */
    public function test_index_paginates_results(): void
    {
        Annotation::factory()->count(25)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?page=2&perPage=10");

        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * IX-005: List empty for keyword with no annotations.
     */
    public function test_index_returns_empty_for_no_annotations(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * IX-006: List ordered by date descending.
     */
    public function test_index_orders_by_date_desc(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
            'title' => 'Oldest',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-28',
            'title' => 'Newest',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200);
        $this->assertEquals('Newest', $response->json('data.0.title'));
    }

    /**
     * IX-007: List with invalid keyword ID.
     */
    public function test_index_returns_404_for_invalid_keyword(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/keywords/9999/annotations');

        $response->assertStatus(404);
    }

    /**
     * IX-008: List with only `from` filter.
     */
    public function test_index_filters_by_from_date_only(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-20',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?from=2026-02-15");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * IX-009: List with only `to` filter.
     */
    public function test_index_filters_by_to_date_only(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-20',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?to=2026-02-15");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * IX-010: Per-page maximum enforced.
     */
    public function test_index_caps_per_page_at_100(): void
    {
        Annotation::factory()->count(50)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?perPage=200");

        $response->assertStatus(200);
        // Controller caps at 100, but we only have 50
        $this->assertLessThanOrEqual(100, $response->json('meta.perPage'));
    }

    // ============================================================
    // STORE (CREATE) TESTS
    // ============================================================

    /**
     * ST-001: Create annotation with valid data.
     */
    public function test_store_creates_annotation(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Annotation')
            ->assertJsonPath('data.keyword_id', $this->keyword->id);

        $this->assertDatabaseHas('annotations', [
            'title' => 'Test Annotation',
            'keyword_id' => $this->keyword->id,
        ]);
    }

    /**
     * ST-002: Create with all fields.
     */
    public function test_store_creates_with_all_fields(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Full Annotation',
            'description' => 'Detailed description here.',
            'category' => 'algorithm-update',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Full Annotation')
            ->assertJsonPath('data.description', 'Detailed description here.')
            ->assertJsonPath('data.category', 'algorithm-update');
    }

    /**
     * ST-003: Create with minimal fields.
     */
    public function test_store_creates_with_minimal_fields(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Minimal Annotation',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Minimal Annotation');
    }

    /**
     * ST-004: Create with invalid keyword ID.
     */
    public function test_store_returns_404_for_invalid_keyword(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/keywords/9999/annotations', $data);

        $response->assertStatus(404);
    }

    /**
     * ST-005: Create without title.
     */
    public function test_store_requires_title(): void
    {
        $data = [
            'date' => '2026-02-27',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * ST-006: Create without date.
     */
    public function test_store_requires_date(): void
    {
        $data = [
            'title' => 'Test Annotation',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * ST-007: Create with invalid date format.
     */
    public function test_store_validates_date_format(): void
    {
        $data = [
            'date' => 'not-a-date',
            'title' => 'Test Annotation',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * ST-008: Create with title > 255 chars.
     */
    public function test_store_validates_title_length(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => str_repeat('a', 256),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * ST-009: Create with description > 65535 chars.
     */
    public function test_store_validates_description_length(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test',
            'description' => str_repeat('a', 65536),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    /**
     * ST-010: Create with category > 50 chars.
     */
    public function test_store_validates_category_length(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test',
            'category' => str_repeat('a', 51),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    /**
     * ST-011: Create with invalid project_id.
     */
    public function test_store_validates_project_id_exists(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test',
            'project_id' => 9999,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    /**
     * ST-012: Project ID derived from keyword site.
     */
    public function test_store_auto_sets_project_id_from_keyword(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Auto Project',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", $data);

        $response->assertStatus(201);
        // project_id should be set automatically
        $this->assertNotNull($response->json('data.project_id'));
    }

    // ============================================================
    // SHOW (SINGLE) TESTS
    // ============================================================

    /**
     * SH-001: Show existing annotation.
     */
    public function test_show_returns_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $annotation->id)
            ->assertJsonPath('data.title', $annotation->title);
    }

    /**
     * SH-002: Show non-existent annotation.
     */
    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/annotations/9999');

        $response->assertStatus(404);
    }

    /**
     * SH-003: Show includes all fields.
     */
    public function test_show_includes_all_fields(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-27',
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'algorithm-update',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.keyword_id', $this->keyword->id)
            ->assertJsonPath('data.project_id', $this->project->id)
            ->assertJsonPath('data.date', '2026-02-27')
            ->assertJsonPath('data.title', 'Test Title')
            ->assertJsonPath('data.description', 'Test Description')
            ->assertJsonPath('data.category', 'algorithm-update');
    }

    /**
     * SH-004: Show with keyword relationship.
     */
    public function test_show_can_load_keyword_relationship(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200);
        // The keyword_id should be present, enabling frontend to fetch keyword if needed
        $response->assertJsonPath('data.keyword_id', $this->keyword->id);
    }

    // ============================================================
    // UPDATE TESTS
    // ============================================================

    /**
     * UP-001: Update title.
     */
    public function test_update_changes_title(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Updated Title',
                'date' => $annotation->date->format('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('annotations', [
            'id' => $annotation->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * UP-002: Update description.
     */
    public function test_update_changes_description(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => $annotation->title,
                'date' => $annotation->date->format('Y-m-d'),
                'description' => 'New description',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'New description');
    }

    /**
     * UP-003: Update date.
     */
    public function test_update_changes_date(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => $annotation->title,
                'date' => '2026-03-15',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.date', '2026-03-15');
    }

    /**
     * UP-004: Update category.
     */
    public function test_update_changes_category(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'other',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => $annotation->title,
                'date' => $annotation->date->format('Y-m-d'),
                'category' => 'content-update',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.category', 'content-update');
    }

    /**
     * UP-005: Update all fields.
     */
    public function test_update_changes_all_fields(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $data = [
            'date' => '2026-03-01',
            'title' => 'All Updated',
            'description' => 'New description',
            'category' => 'link-building',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.date', '2026-03-01')
            ->assertJsonPath('data.title', 'All Updated')
            ->assertJsonPath('data.description', 'New description')
            ->assertJsonPath('data.category', 'link-building');
    }

    /**
     * UP-006: Update non-existent annotation.
     */
    public function test_update_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/annotations/9999', [
                'title' => 'Test',
                'date' => '2026-02-27',
            ]);

        $response->assertStatus(404);
    }

    /**
     * UP-007: Update with invalid title.
     */
    public function test_update_validates_title(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'date' => '2026-02-27',
                // title missing
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * UP-008: Update with invalid date.
     */
    public function test_update_validates_date(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Test',
                'date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * UP-009: Partial update (only title).
     */
    public function test_update_partial_preserves_other_fields(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
            'description' => 'Original description',
            'category' => 'other',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Only Title Changed',
                'date' => '2026-02-01',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Only Title Changed')
            ->assertJsonPath('data.description', 'Original description');
    }

    /**
     * UP-010: Update preserves keyword_id.
     */
    public function test_update_preserves_keyword_id(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Updated',
                'date' => '2026-02-27',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.keyword_id', $this->keyword->id);
    }

    // ============================================================
    // DESTROY (DELETE) TESTS
    // ============================================================

    /**
     * DE-001: Delete existing annotation.
     */
    public function test_destroy_deletes_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(204);
    }

    /**
     * DE-002: Delete non-existent annotation.
     */
    public function test_destroy_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/annotations/9999');

        $response->assertStatus(404);
    }

    /**
     * DE-003: Delete removes from database.
     */
    public function test_destroy_removes_from_database(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $this->assertDatabaseMissing('annotations', [
            'id' => $annotation->id,
        ]);
    }

    /**
     * DE-004: Delete returns no content.
     */
    public function test_destroy_returns_empty_body(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(204);
        $response->assertContent('');
    }

    // ============================================================
    // AUTHORIZATION TESTS - UNAUTHENTICATED
    // ============================================================

    /**
     * AZ-001: Unauthenticated list.
     */
    public function test_index_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(401);
    }

    /**
     * AZ-002: Unauthenticated create.
     */
    public function test_store_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
            'date' => '2026-02-27',
            'title' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    /**
     * AZ-003: Unauthenticated show.
     */
    public function test_show_returns_401_for_unauthenticated_user(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(401);
    }

    /**
     * AZ-004: Unauthenticated update.
     */
    public function test_update_returns_401_for_unauthenticated_user(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->putJson("/api/v1/annotations/{$annotation->id}", [
            'title' => 'Updated',
            'date' => '2026-02-27',
        ]);

        $response->assertStatus(401);
    }

    /**
     * AZ-005: Unauthenticated delete.
     */
    public function test_destroy_returns_401_for_unauthenticated_user(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(401);
    }

    // ============================================================
    // AUTHORIZATION TESTS - CROSS-USER ACCESS
    // ============================================================

    /**
     * AZ-010: List other user's annotations.
     */
    public function test_index_returns_403_for_other_users_keyword(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id, 'user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$otherKeyword->id}/annotations");

        $response->assertStatus(403);
    }

    /**
     * AZ-011: Create on other user's keyword.
     */
    public function test_store_returns_403_for_other_users_keyword(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id, 'user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$otherKeyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    /**
     * AZ-012: Show other user's annotation.
     */
    public function test_show_returns_403_for_other_users_annotation(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id, 'user_id' => $this->otherUser->id]);
        $otherAnnotation = Annotation::factory()->create([
            'keyword_id' => $otherKeyword->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$otherAnnotation->id}");

        $response->assertStatus(403);
    }

    /**
     * AZ-013: Update other user's annotation.
     */
    public function test_update_returns_403_for_other_users_annotation(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id, 'user_id' => $this->otherUser->id]);
        $otherAnnotation = Annotation::factory()->create([
            'keyword_id' => $otherKeyword->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$otherAnnotation->id}", [
                'title' => 'Hacked',
                'date' => '2026-02-27',
            ]);

        $response->assertStatus(403);
    }

    /**
     * AZ-014: Delete other user's annotation.
     */
    public function test_destroy_returns_403_for_other_users_annotation(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id, 'user_id' => $this->otherUser->id]);
        $otherAnnotation = Annotation::factory()->create([
            'keyword_id' => $otherKeyword->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$otherAnnotation->id}");

        $response->assertStatus(403);
    }

    // ============================================================
    // AUTHORIZATION TESTS - OWNER ACCESS
    // ============================================================

    /**
     * AZ-020: Owner can list.
     */
    public function test_owner_can_list_annotations(): void
    {
        Annotation::factory()->count(3)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * AZ-021: Owner can create.
     */
    public function test_owner_can_create_annotation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Owner Annotation',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Owner Annotation');
    }

    /**
     * AZ-022: Owner can show.
     */
    public function test_owner_can_show_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $annotation->id);
    }

    /**
     * AZ-023: Owner can update.
     */
    public function test_owner_can_update_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Owner Updated',
                'date' => '2026-02-27',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Owner Updated');
    }

    /**
     * AZ-024: Owner can delete.
     */
    public function test_owner_can_delete_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(204);
    }

    // ============================================================
    // EDGE CASE TESTS
    // ============================================================

    /**
     * EC-001: Empty title string.
     */
    public function test_store_rejects_empty_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * EC-002: Whitespace-only title.
     */
    public function test_store_rejects_whitespace_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => '   ',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * EC-003: Date in future.
     */
    public function test_store_accepts_future_date(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2099-12-31',
                'title' => 'Future Annotation',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.date', '2099-12-31');
    }

    /**
     * EC-004: Date in past.
     */
    public function test_store_accepts_past_date(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '1990-01-01',
                'title' => 'Past Annotation',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.date', '1990-01-01');
    }

    /**
     * EC-005: Unicode in title.
     */
    public function test_store_handles_unicode_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'SEO 更新 🚀',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'SEO 更新 🚀');
    }

    /**
     * EC-006: HTML in description.
     */
    public function test_store_stores_html_safely(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'description' => '<script>alert(1)</script>',
            ]);

        $response->assertStatus(201);
        // Stored as-is; output should be escaped by frontend
        $this->assertStringContainsString('<script>', $response->json('data.description'));
    }

    /**
     * EC-007: SQL injection attempt in title.
     */
    public function test_store_handles_sql_injection_safely(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => "' OR 1=1--",
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', "' OR 1=1--");
    }

    /**
     * EC-008: Newlines in description.
     */
    public function test_store_preserves_newlines(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'description' => "Line1\nLine2\nLine3",
            ]);

        $response->assertStatus(201);
        $this->assertStringContainsString("\n", $response->json('data.description'));
    }

    /**
     * EC-009: Special characters in category.
     */
    public function test_store_handles_special_category(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'category' => 'special!@#$%',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.category', 'special!@#$%');
    }

    /**
     * EC-010: Empty description.
     */
    public function test_store_accepts_empty_description(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'description' => '',
            ]);

        $response->assertStatus(201);
    }

    /**
     * EC-011: Very long title (255 chars).
     */
    public function test_store_accepts_max_title_length(): void
    {
        $title = str_repeat('a', 255);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => $title,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', $title);
    }

    /**
     * EC-012: Title exceeds max (256 chars).
     */
    public function test_store_rejects_title_over_max_length(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * IT-001: Full CRUD lifecycle.
     */
    public function test_full_crud_lifecycle(): void
    {
        // Create
        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Lifecycle Test',
            ]);

        $createResponse->assertStatus(201);
        $annotationId = $createResponse->json('data.id');

        // Read
        $readResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotationId}");

        $readResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Lifecycle Test');

        // Update
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotationId}", [
                'title' => 'Updated Lifecycle',
                'date' => '2026-02-27',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Lifecycle');

        // Delete
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotationId}");

        $deleteResponse->assertStatus(204);

        // Verify deleted
        $this->assertDatabaseMissing('annotations', [
            'id' => $annotationId,
        ]);
    }

    /**
     * IT-002: Multiple annotations for keyword.
     */
    public function test_multiple_annotations_for_keyword(): void
    {
        Annotation::factory()->count(10)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    /**
     * IT-003: Filter by date after create.
     */
    public function test_filter_by_date_after_create(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-01',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-15',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-28',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?from=2026-02-10&to=2026-02-20");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * IT-004: Filter by category after create.
     */
    public function test_filter_by_category_after_create(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'algorithm-update',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'content-update',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'link-building',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?category=content-update");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'content-update');
    }

    /**
     * IT-005: Delete removes from list.
     */
    public function test_delete_removes_from_list(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        // Verify in list
        $listBefore = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");
        $listBefore->assertJsonCount(1, 'data');

        // Delete
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        // Verify not in list
        $listAfter = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");
        $listAfter->assertJsonCount(0, 'data');
    }

    /**
     * IT-006: Update reflected in show.
     */
    public function test_update_reflected_in_show(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'title' => 'Original Title',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Reflected Update',
                'date' => $annotation->date->format('Y-m-d'),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Reflected Update');
    }
}
