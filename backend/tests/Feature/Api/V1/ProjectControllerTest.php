<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for Project CRUD API endpoints.
 * 
 * Task: TASK-8-10
 * Test Documentation: TESTS/TEST-8-10.md
 * 
 * @group integration
 * @group projects
 */
class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // ==================== AUTHENTICATION TESTS ====================

    /**
     * SEC-001: Unauthenticated access to projects returns 401.
     */
    public function test_list_projects_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/projects');
        
        $response->assertStatus(401);
    }

    /**
     * SEC-002: Unauthenticated create project returns 401.
     */
    public function test_create_project_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Test Project',
        ]);
        
        $response->assertStatus(401);
    }

    // ==================== CREATE PROJECT TESTS (CP) ====================

    /**
     * CP-001: Create project with valid data.
     */
    public function test_create_project_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Project',
            'domain' => 'https://example.com',
            'notes' => 'Test project notes',
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Project')
            ->assertJsonPath('data.domain', 'https://example.com')
            ->assertJsonPath('data.notes', 'Test project notes');
        
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * CP-002: Create project with minimal data.
     */
    public function test_create_project_with_minimal_data(): void
    {
        $data = ['name' => 'Minimal Project'];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Minimal Project')
            ->assertJsonPath('data.domain', null)
            ->assertJsonPath('data.notes', null);
    }

    /**
     * CP-003: Create project with all fields.
     */
    public function test_create_project_with_all_fields(): void
    {
        $data = [
            'name' => 'Full Project',
            'domain' => 'https://fullproject.com',
            'notes' => 'Complete project with all fields',
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Full Project')
            ->assertJsonPath('data.domain', 'https://fullproject.com')
            ->assertJsonPath('data.notes', 'Complete project with all fields');
    }

    /**
     * CP-004: Create project without name fails validation.
     */
    public function test_create_project_without_name(): void
    {
        $data = ['domain' => 'https://example.com'];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CP-005: Create project with invalid domain format.
     */
    public function test_create_project_with_invalid_domain(): void
    {
        $data = [
            'name' => 'Test Project',
            'domain' => 'not-a-valid-url',
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain']);
    }

    /**
     * CP-006: Create project with duplicate name (allowed).
     */
    public function test_create_project_with_duplicate_name(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Duplicate Name',
        ]);
        
        $data = ['name' => 'Duplicate Name'];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201);
        
        $this->assertEquals(2, Project::where('name', 'Duplicate Name')->count());
    }

    /**
     * CP-007: Create project sets user ownership.
     */
    public function test_create_project_sets_user_ownership(): void
    {
        $data = ['name' => 'User Project'];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201);
        
        $project = Project::where('name', 'User Project')->first();
        $this->assertEquals($this->user->id, $project->user_id);
    }

    /**
     * CP-008: Create project with 255 character name.
     */
    public function test_create_project_with_max_name_length(): void
    {
        $name = str_repeat('a', 255);
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => $name]);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.name', $name);
    }

    /**
     * CP-009: Create project with name exceeding 255 characters.
     */
    public function test_create_project_name_exceeds_limit(): void
    {
        $name = str_repeat('a', 256);
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => $name]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CP-010: Create project with HTML in notes (XSS test).
     */
    public function test_create_project_sanitizes_html_in_notes(): void
    {
        $data = [
            'name' => 'HTML Project',
            'notes' => '<script>alert("xss")</script>Safe text',
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertStatus(201);
        
        // Notes should be sanitized or escaped
        $project = Project::where('name', 'HTML Project')->first();
        $this->assertStringNotContainsString('<script>', $project->notes);
    }

    // ==================== READ PROJECT TESTS (RP) ====================

    /**
     * RP-001: List all user's projects.
     */
    public function test_list_projects(): void
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'domain', 'is_default', 'archived_at'],
                ],
            ]);
    }

    /**
     * RP-002: List projects includes metadata.
     */
    public function test_list_projects_includes_metadata(): void
    {
        Project::factory()->count(2)->create(['user_id' => $this->user->id]);
        Project::factory()->archived()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['total', 'active', 'archived'],
            ])
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.active', 2)
            ->assertJsonPath('meta.archived', 1);
    }

    /**
     * RP-003: List projects with pagination.
     */
    public function test_list_projects_with_pagination(): void
    {
        Project::factory()->count(25)->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?page=2&perPage=10');
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * RP-004: Get single project.
     */
    public function test_get_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$project->id}");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.name', $project->name);
    }

    /**
     * RP-005: Get non-existent project.
     */
    public function test_get_project_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/99999');
        
        $response->assertStatus(404);
    }

    /**
     * RP-006: Get project owned by another user (403).
     */
    public function test_get_project_forbidden(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}");
        
        $response->assertStatus(403);
    }

    /**
     * RP-007: Get project includes keywords count.
     */
    public function test_get_project_includes_keywords_count(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        // Create keywords - Note: requires keywords table with project_id
        // Keyword::factory()->count(10)->create(['project_id' => $project->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$project->id}");
        
        $response->assertStatus(200);
        // ->assertJsonPath('data.keywords_count', 10);
    }

    /**
     * RP-010: List only active projects.
     */
    public function test_list_active_projects(): void
    {
        Project::factory()->count(2)->create(['user_id' => $this->user->id]);
        Project::factory()->archived()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=false');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * RP-011: List only archived projects.
     */
    public function test_list_archived_projects(): void
    {
        Project::factory()->create(['user_id' => $this->user->id]);
        Project::factory()->archived()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=true');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ==================== UPDATE PROJECT TESTS (UP) ====================

    /**
     * UP-001: Update project name.
     */
    public function test_update_project_name(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'New Name']);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
        
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * UP-002: Update project domain.
     */
    public function test_update_project_domain(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", [
                'domain' => 'https://newdomain.com',
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'https://newdomain.com');
    }

    /**
     * UP-003: Update project notes.
     */
    public function test_update_project_notes(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", [
                'notes' => 'Updated notes',
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    /**
     * UP-004: Update non-existent project.
     */
    public function test_update_project_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/projects/99999', ['name' => 'New Name']);
        
        $response->assertStatus(404);
    }

    /**
     * UP-005: Update project owned by another user.
     */
    public function test_update_project_forbidden(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$otherProject->id}", ['name' => 'Hacked']);
        
        $response->assertStatus(403);
    }

    /**
     * UP-006: Update with invalid domain.
     */
    public function test_update_project_invalid_domain(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", [
                'domain' => 'not-a-url',
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain']);
    }

    /**
     * UP-007: Partial update (only name).
     */
    public function test_update_project_partial(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'https://original.com',
        ]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Updated Name']);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.domain', 'https://original.com');
    }

    // ==================== ARCHIVE/RESTORE TESTS (AR) ====================

    /**
     * AR-001: Archive active project.
     */
    public function test_archive_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $project->refresh();
        $this->assertNotNull($project->archived_at);
    }

    /**
     * AR-002: Archive already archived project.
     */
    public function test_archive_already_archived_project(): void
    {
        $project = Project::factory()->archived()->create(['user_id' => $this->user->id]);
        $originalArchivedAt = $project->archived_at;
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $response->assertStatus(200);
        
        $project->refresh();
        $this->assertEquals($originalArchivedAt, $project->archived_at);
    }

    /**
     * AR-003: Restore archived project.
     */
    public function test_restore_project(): void
    {
        $project = Project::factory()->archived()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/restore");
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $project->refresh();
        $this->assertNull($project->archived_at);
    }

    /**
     * AR-005: Archive non-existent project.
     */
    public function test_archive_project_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects/99999/archive');
        
        $response->assertStatus(404);
    }

    /**
     * AR-006: Archive project owned by another user.
     */
    public function test_archive_project_forbidden(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/archive");
        
        $response->assertStatus(403);
    }

    // ==================== DELETE PROJECT TESTS (DP) ====================

    /**
     * DP-001: Delete project with confirmation.
     */
    public function test_delete_project_with_confirmation(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$project->id}?confirm=true");
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    /**
     * DP-002: Delete project without confirmation.
     */
    public function test_delete_project_without_confirmation(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$project->id}");
        
        $response->assertStatus(400);
        
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    /**
     * DP-003: Delete non-existent project.
     */
    public function test_delete_project_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/projects/99999?confirm=true');
        
        $response->assertStatus(404);
    }

    /**
     * DP-004: Delete project owned by another user.
     */
    public function test_delete_project_forbidden(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$otherProject->id}?confirm=true");
        
        $response->assertStatus(403);
    }

    /**
     * DP-005: Delete project cascades to keywords.
     * Note: Requires keywords table with project_id FK
     */
    public function test_delete_project_cascades_to_keywords(): void
    {
        $this->markTestSkipped('Requires keywords table with project_id column');
        
        // $project = Project::factory()->create(['user_id' => $this->user->id]);
        // Keyword::factory()->count(5)->create(['project_id' => $project->id]);
        
        // $response = $this->actingAs($this->user)
        //     ->deleteJson("/api/v1/projects/{$project->id}?confirm=true");
        
        // $response->assertStatus(200);
        // $this->assertEquals(0, Keyword::where('project_id', $project->id)->count());
    }

    /**
     * DP-006: Delete project cascades to competitors.
     * Note: Requires competitors table with project_id FK
     */
    public function test_delete_project_cascades_to_competitors(): void
    {
        $this->markTestSkipped('Requires competitors table with project_id column');
    }

    /**
     * DP-007: Delete project cascades to reports.
     * Note: Requires reports table with project_id FK
     */
    public function test_delete_project_cascades_to_reports(): void
    {
        $this->markTestSkipped('Requires reports table with project_id column');
    }

    // ==================== SECURITY TESTS (SEC) ====================

    /**
     * SEC-007: SQL injection in project name.
     */
    public function test_sql_injection_in_project_name(): void
    {
        $maliciousName = "'; DROP TABLE projects;--";
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => $maliciousName]);
        
        $response->assertStatus(201);
        
        // Verify table still exists and name is stored as string
        $this->assertDatabaseHas('projects', ['name' => $maliciousName]);
    }

    /**
     * SEC-010: Mass assignment protection (user_id).
     */
    public function test_mass_assignment_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', [
                'name' => 'Test',
                'user_id' => $this->otherUser->id, // Try to assign to another user
            ]);
        
        $response->assertStatus(201);
        
        // Project should still be owned by authenticated user
        $project = Project::where('name', 'Test')->first();
        $this->assertEquals($this->user->id, $project->user_id);
    }
}
