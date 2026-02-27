<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Annotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * End-to-End Integration Tests for Annotations
 * 
 * Tests the complete workflow from API to database for annotation CRUD operations.
 * Covers API integration, error scenarios, authorization, data consistency, and edge cases.
 * 
 * @see TEST-9-10 for full test documentation
 */
class AnnotationE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;
    protected Keyword $keyword;
    protected Keyword $otherUserKeyword;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create project and keyword for main user
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create([
            'site_id' => 1, // Adjust based on your schema
            'keyword' => 'test keyword',
        ]);

        // Create keyword for other user
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        $this->otherUserKeyword = Keyword::factory()->create([
            'site_id' => 2,
            'keyword' => 'other user keyword',
        ]);
    }

    // ============================================
    // API Integration Tests (API-001 to API-012)
    // ============================================

    /** @test API-001: Create annotation via API */
    public function test_create_annotation_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test Annotation',
                'description' => 'Test description',
                'category' => 'algorithm-update',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'keyword_id',
                    'project_id',
                    'date',
                    'title',
                    'description',
                    'category',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('annotations', [
            'keyword_id' => $this->keyword->id,
            'title' => 'Test Annotation',
            'category' => 'algorithm-update',
        ]);
    }

    /** @test API-002: Create annotation with minimal data */
    public function test_create_annotation_with_minimal_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Minimal Annotation',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', [
            'keyword_id' => $this->keyword->id,
            'title' => 'Minimal Annotation',
            'category' => 'other', // Default value
        ]);
    }

    /** @test API-003: Create annotation with all fields */
    public function test_create_annotation_with_all_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Full Annotation',
                'description' => 'Detailed description with special chars: <>&"\'',
                'category' => 'content-update',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', [
            'title' => 'Full Annotation',
            'description' => 'Detailed description with special chars: <>&"\'',
        ]);
    }

    /** @test API-004: Read annotation list */
    public function test_read_annotation_list(): void
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

    /** @test API-005: Read single annotation */
    public function test_read_single_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'title' => 'Single Test',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Single Test');
    }

    /** @test API-006: Update annotation */
    public function test_update_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Updated Title',
                'description' => 'New description',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('annotations', [
            'id' => $annotation->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test API-007: Delete annotation */
    public function test_delete_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('annotations', [
            'id' => $annotation->id,
        ]);
    }

    /** @test API-008: Filter by date range */
    public function test_filter_by_date_range(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-01-15',
            'title' => 'January Annotation',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-15',
            'title' => 'February Annotation',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?from=2026-02-01&to=2026-02-28");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'February Annotation');
    }

    /** @test API-009: Filter by category */
    public function test_filter_by_category(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'algorithm-update',
            'title' => 'Algorithm Update',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'category' => 'content-update',
            'title' => 'Content Update',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?category=algorithm-update");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'algorithm-update');
    }

    /** @test API-010: Pagination works */
    public function test_pagination_works(): void
    {
        Annotation::factory()->count(25)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?page=1&perPage=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.perPage', 10);
    }

    /** @test API-011: Annotations ordered by date DESC */
    public function test_annotations_ordered_by_date_desc(): void
    {
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-01-01',
            'title' => 'Oldest',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-15',
            'title' => 'Newest',
        ]);
        Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-01-15',
            'title' => 'Middle',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200);
        $dates = collect($response->json('data'))->pluck('date');
        $this->assertEquals(['2026-02-15', '2026-01-15', '2026-01-01'], $dates->toArray());
    }

    /** @test API-012: API response matches data model */
    public function test_api_response_matches_data_model(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'date' => '2026-02-27',
            'title' => 'Model Test',
            'description' => 'Description',
            'category' => 'recovery',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'keyword_id',
                    'project_id',
                    'date',
                    'title',
                    'description',
                    'category',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.keyword_id', $this->keyword->id)
            ->assertJsonPath('data.title', 'Model Test')
            ->assertJsonPath('data.category', 'recovery');
    }

    // ============================================
    // Error Scenario Tests (ERR-001 to ERR-015)
    // ============================================

    /** @test ERR-001: Create with missing title */
    public function test_create_with_missing_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test ERR-002: Create with missing date */
    public function test_create_with_missing_date(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'title' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test ERR-003: Create with invalid category (too long) */
    public function test_create_with_invalid_category(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'category' => str_repeat('a', 51), // 51 chars
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    /** @test ERR-004: Create with invalid date format */
    public function test_create_with_invalid_date_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => 'not-a-date',
                'title' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test ERR-005: Read non-existent annotation */
    public function test_read_non_existent_annotation(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/annotations/99999');

        $response->assertStatus(404);
    }

    /** @test ERR-006: Update non-existent annotation */
    public function test_update_non_existent_annotation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/annotations/99999', [
                'title' => 'Updated',
            ]);

        $response->assertStatus(404);
    }

    /** @test ERR-007: Delete non-existent annotation */
    public function test_delete_non_existent_annotation(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/annotations/99999');

        $response->assertStatus(404);
    }

    /** @test ERR-008: Create for non-existent keyword */
    public function test_create_for_non_existent_keyword(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/keywords/99999/annotations', [
                'date' => '2026-02-27',
                'title' => 'Test',
            ]);

        $response->assertStatus(404);
    }

    /** @test ERR-009: Unauthorized access - no token */
    public function test_unauthorized_access_no_token(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(401);
    }

    /** @test ERR-010: Unauthorized access - wrong user */
    public function test_unauthorized_access_wrong_user(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(403);
    }

    /** @test ERR-011: Create annotation for other user's keyword */
    public function test_create_for_other_users_keyword(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    /** @test ERR-012: Update other user's annotation */
    public function test_update_other_users_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    /** @test ERR-013: Delete other user's annotation */
    public function test_delete_other_users_annotation(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(403);
    }

    /** @test ERR-014: SQL injection attempt in title */
    public function test_sql_injection_attempt_in_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => "'; DROP TABLE annotations;--",
            ]);

        $response->assertStatus(201);
        
        // Verify the title was stored as-is (no SQL injection)
        $this->assertDatabaseHas('annotations', [
            'title' => "'; DROP TABLE annotations;--",
        ]);
    }

    /** @test ERR-015: XSS attempt in description */
    public function test_xss_attempt_in_description(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Test',
                'description' => "<script>alert('xss')</script>",
            ]);

        $response->assertStatus(201);
        
        // Verify the description was stored (sanitization happens on output)
        $this->assertDatabaseHas('annotations', [
            'description' => "<script>alert('xss')</script>",
        ]);
    }

    // ============================================
    // Authorization Tests (AUTH-001 to AUTH-010)
    // ============================================

    /** @test AUTH-001: Owner can view annotations */
    public function test_owner_can_view_annotations(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(200);
    }

    /** @test AUTH-002: Owner can create annotations */
    public function test_owner_can_create_annotations(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Owner Test',
            ]);

        $response->assertStatus(201);
    }

    /** @test AUTH-003: Owner can update annotations */
    public function test_owner_can_update_annotations(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Updated',
            ]);

        $response->assertStatus(200);
    }

    /** @test AUTH-004: Owner can delete annotations */
    public function test_owner_can_delete_annotations(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/annotations/{$annotation->id}");

        $response->assertStatus(204);
    }

    /** @test AUTH-009: Guest cannot access annotations */
    public function test_guest_cannot_access_annotations(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(401);
    }

    /** @test AUTH-010: Guest cannot create annotations */
    public function test_guest_cannot_create_annotations(): void
    {
        $response = $this->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
            'date' => '2026-02-27',
            'title' => 'Guest Test',
        ]);

        $response->assertStatus(401);
    }

    // ============================================
    // Data Consistency Tests (DC-001 to DC-010)
    // ============================================

    /** @test DC-001: Created annotation persists in database */
    public function test_created_annotation_persists(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Persist Test',
                'category' => 'algorithm-update',
            ]);

        $this->assertDatabaseHas('annotations', [
            'keyword_id' => $this->keyword->id,
            'title' => 'Persist Test',
            'category' => 'algorithm-update',
        ]);
    }

    /** @test DC-002: Updated annotation reflects changes */
    public function test_updated_annotation_reflects_changes(): void
    {
        $annotation = Annotation::factory()->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
            'title' => 'Original',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/annotations/{$annotation->id}", [
                'title' => 'Changed',
                'description' => 'New desc',
            ]);

        $this->assertDatabaseHas('annotations', [
            'id' => $annotation->id,
            'title' => 'Changed',
            'description' => 'New desc',
        ]);
    }

    /** @test DC-003: Deleted annotation removed from database */
    public function test_deleted_annotation_removed(): void
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

    /** @test DC-004: Annotation keyword_id matches */
    public function test_annotation_keyword_id_matches(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Keyword Match Test',
            ]);

        $annotationId = $response->json('data.id');
        
        $this->assertDatabaseHas('annotations', [
            'id' => $annotationId,
            'keyword_id' => $this->keyword->id,
        ]);
    }

    /** @test DC-008: Timestamps auto-managed */
    public function test_timestamps_auto_managed(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Timestamp Test',
            ]);

        $response->assertJsonStructure([
            'data' => ['created_at', 'updated_at'],
        ]);
    }

    /** @test DC-010: Category defaults to 'other' */
    public function test_category_defaults_to_other(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'No Category',
            ]);

        $response->assertJsonPath('data.category', 'other');
    }

    // ============================================
    // Edge Case Tests (EC-001 to EC-014)
    // ============================================

    /** @test EC-001: Empty annotations list response */
    public function test_empty_annotations_list_response(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test EC-002: Special characters in title */
    public function test_special_characters_in_title(): void
    {
        $specialTitle = 'Test & Co. <special> "quotes" \'apostrophes\'';

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => $specialTitle,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', ['title' => $specialTitle]);
    }

    /** @test EC-003: Unicode in description */
    public function test_unicode_in_description(): void
    {
        $unicode = '日本語 עברית العربية 中文';

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Unicode Test',
                'description' => $unicode,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', ['description' => $unicode]);
    }

    /** @test EC-004: Emoji in title */
    public function test_emoji_in_title(): void
    {
        $emoji = 'Rocket 🚀 Launch 📈 SEO 🎯';

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => $emoji,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', ['title' => $emoji]);
    }

    /** @test EC-005: Very long title (255 chars) */
    public function test_very_long_title_255_chars(): void
    {
        $title = str_repeat('a', 255);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => $title,
            ]);

        $response->assertStatus(201);
    }

    /** @test EC-006: Title exceeds 255 chars */
    public function test_title_exceeds_255_chars(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test EC-008: Date in far future */
    public function test_date_in_far_future(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2099-12-31',
                'title' => 'Future Date',
            ]);

        $response->assertStatus(201);
    }

    /** @test EC-009: Date in far past */
    public function test_date_in_far_past(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2000-01-01',
                'title' => 'Past Date',
            ]);

        $response->assertStatus(201);
    }

    /** @test EC-012: Category with spaces */
    public function test_category_with_spaces(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Category with Spaces',
                'category' => 'content update',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('annotations', ['category' => 'content update']);
    }

    /** @test EC-013: Category exactly 50 chars */
    public function test_category_exactly_50_chars(): void
    {
        $category = str_repeat('a', 50);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Max Category',
                'category' => $category,
            ]);

        $response->assertStatus(201);
    }

    /** @test EC-014: Category exceeds 50 chars */
    public function test_category_exceeds_50_chars(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Too Long Category',
                'category' => str_repeat('a', 51),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    // ============================================
    // Performance Tests (PERF-001 to PERF-007)
    // ============================================

    /** @test PERF-001: List 100 annotations < 200ms */
    public function test_list_100_annotations_performance(): void
    {
        Annotation::factory()->count(100)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $start = microtime(true);
        
        $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations");
        
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, 'List query should complete in under 200ms');
    }

    /** @test PERF-002: Create annotation < 100ms */
    public function test_create_annotation_performance(): void
    {
        $start = microtime(true);
        
        $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$this->keyword->id}/annotations", [
                'date' => '2026-02-27',
                'title' => 'Performance Test',
            ]);
        
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, 'Create query should complete in under 100ms');
    }

    /** @test PERF-005: Pagination with many annotations */
    public function test_pagination_with_many_annotations(): void
    {
        Annotation::factory()->count(1000)->create([
            'keyword_id' => $this->keyword->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/annotations?page=5&perPage=20");

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 5)
            ->assertJsonCount(20, 'data');
    }
}
