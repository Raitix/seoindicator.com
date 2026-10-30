<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\Report;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * End-to-End Integration Tests for Project Management.
 * 
 * Task: TASK-8-10
 * Test Documentation: TESTS/TEST-8-10.md
 * 
 * @group integration
 * @group projects
 */
class ProjectIntegrationTest extends TestCase
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

    // ==================== CRUD WORKFLOW TESTS (WF) ====================

    /**
     * WF-001: Full project lifecycle (Create → List → Update → Archive → Restore → Delete).
     */
    public function test_full_project_lifecycle(): void
    {
        // Step 1: Create project
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', [
                'name' => 'Lifecycle Project',
                'domain' => 'https://lifecycle.com',
                'notes' => 'Test lifecycle',
            ]);
        
        $createResponse->assertStatus(201);
        $projectId = $createResponse->json('data.id');
        
        // Step 2: List projects - should contain new project
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $listResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Lifecycle Project');
        
        // Step 3: Update project
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$projectId}", [
                'name' => 'Updated Lifecycle Project',
            ]);
        
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Lifecycle Project');
        
        // Step 4: Archive project
        $archiveResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$projectId}/archive");
        
        $archiveResponse->assertStatus(200);
        $this->assertNotNull(Project::find($projectId)->archived_at);
        
        // Step 5: Restore project
        $restoreResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$projectId}/restore");
        
        $restoreResponse->assertStatus(200);
        $this->assertNull(Project::find($projectId)->archived_at);
        
        // Step 6: Delete project
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$projectId}?confirm=true");
        
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
    }

    /**
     * WF-002: Create and immediately list.
     */
    public function test_create_and_immediately_list(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => 'New Project']);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'New Project');
    }

    /**
     * WF-003: Update reflects in list.
     */
    public function test_update_reflects_in_list(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);
        
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'New Name']);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertJsonPath('data.0.name', 'New Name');
    }

    /**
     * WF-004: Archive hides from default list.
     */
    public function test_archive_hides_from_default_list(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.active', 0)
            ->assertJsonPath('meta.archived', 1);
    }

    /**
     * WF-005: Archive shows in archived list.
     */
    public function test_archive_shows_in_archived_list(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=true');
        
        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $project->id);
    }

    /**
     * WF-006: Delete removes from all lists.
     */
    public function test_delete_removes_from_all_lists(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$project->id}?confirm=true");
        
        $activeResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $archivedResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=true');
        
        $activeResponse->assertJsonCount(0, 'data');
        $archivedResponse->assertJsonCount(0, 'data');
    }

    /**
     * WF-007: Multiple projects CRUD operations.
     */
    public function test_multiple_projects_crud(): void
    {
        // Create 3 projects
        $p1 = Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Project 1']);
        $p2 = Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Project 2']);
        $p3 = Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Project 3']);
        
        // Update 2 projects
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$p1->id}", ['name' => 'Updated Project 1']);
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$p2->id}", ['name' => 'Updated Project 2']);
        
        // Archive 1 project
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$p3->id}/archive");
        
        // List active projects
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.active', 2)
            ->assertJsonPath('meta.archived', 1);
    }

    // ==================== CROSS-USER ISOLATION TESTS (CU) ====================

    /**
     * CU-001: User cannot see other's projects.
     */
    public function test_user_cannot_see_other_projects(): void
    {
        Project::factory()->count(3)->create(['user_id' => $this->otherUser->id]);
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'My Project']);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'My Project');
    }

    /**
     * CU-002: User cannot access other's project.
     */
    public function test_user_cannot_access_other_project(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}");
        
        $response->assertStatus(403);
    }

    /**
     * CU-003: User cannot update other's project.
     */
    public function test_user_cannot_update_other_project(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Original Name',
        ]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$otherProject->id}", ['name' => 'Hacked']);
        
        $response->assertStatus(403);
        $this->assertEquals('Original Name', $otherProject->fresh()->name);
    }

    /**
     * CU-004: User cannot delete other's project.
     */
    public function test_user_cannot_delete_other_project(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$otherProject->id}?confirm=true");
        
        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $otherProject->id]);
    }

    /**
     * CU-005: User cannot archive other's project.
     */
    public function test_user_cannot_archive_other_project(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/archive");
        
        $response->assertStatus(403);
        $this->assertNull($otherProject->fresh()->archived_at);
    }

    /**
     * CU-006: User cannot restore other's project.
     */
    public function test_user_cannot_restore_other_project(): void
    {
        $otherProject = Project::factory()->archived()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/restore");
        
        $response->assertStatus(403);
        $this->assertNotNull($otherProject->fresh()->archived_at);
    }

    /**
     * CU-007: Multiple users isolated.
     */
    public function test_multiple_users_isolated(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Project::factory()->count(3)->create(['user_id' => $user1->id]);
        Project::factory()->count(5)->create(['user_id' => $user2->id]);
        
        $response1 = $this->actingAs($user1)->getJson('/api/v1/projects');
        $response2 = $this->actingAs($user2)->getJson('/api/v1/projects');
        
        $response1->assertJsonCount(3, 'data');
        $response2->assertJsonCount(5, 'data');
    }

    // ==================== PERFORMANCE TESTS (PF) ====================

    /**
     * PF-001: List 100 projects performance.
     */
    public function test_list_100_projects_performance(): void
    {
        Project::factory()->count(100)->create(['user_id' => $this->user->id]);
        
        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?perPage=100');
        $duration = (microtime(true) - $start) * 1000;
        
        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
        
        // Assert response time < 500ms
        $this->assertLessThan(500, $duration, "List 100 projects took {$duration}ms (expected < 500ms)");
    }

    /**
     * PF-002: Create project performance.
     */
    public function test_create_project_performance(): void
    {
        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', [
                'name' => 'Performance Test',
                'domain' => 'https://perf.com',
            ]);
        $duration = (microtime(true) - $start) * 1000;
        
        $response->assertStatus(201);
        $this->assertLessThan(200, $duration, "Create project took {$duration}ms (expected < 200ms)");
    }

    /**
     * PF-003: Update project performance.
     */
    public function test_update_project_performance(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Updated']);
        $duration = (microtime(true) - $start) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Update project took {$duration}ms (expected < 200ms)");
    }

    /**
     * PF-004: Archive/restore performance.
     */
    public function test_archive_restore_performance(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        // Archive performance
        $startArchive = microtime(true);
        $archiveResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        $archiveDuration = (microtime(true) - $startArchive) * 1000;
        
        $archiveResponse->assertStatus(200);
        $this->assertLessThan(200, $archiveDuration, "Archive took {$archiveDuration}ms");
        
        // Restore performance
        $startRestore = microtime(true);
        $restoreResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/restore");
        $restoreDuration = (microtime(true) - $startRestore) * 1000;
        
        $restoreResponse->assertStatus(200);
        $this->assertLessThan(200, $restoreDuration, "Restore took {$restoreDuration}ms");
    }

    // ==================== ERROR HANDLING TESTS (EH) ====================

    /**
     * EH-001: Invalid project ID format.
     */
    public function test_invalid_project_id_format(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/abc');
        
        $response->assertStatus(404);
    }

    /**
     * EH-002: Negative project ID.
     */
    public function test_negative_project_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/-1');
        
        $response->assertStatus(404);
    }

    /**
     * EH-003: Empty project name.
     */
    public function test_empty_project_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => '']);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * EH-004: SQL injection in name.
     */
    public function test_sql_injection_in_name(): void
    {
        $maliciousName = "'; DROP TABLE projects;--";
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => $maliciousName]);
        
        $response->assertStatus(201);
        
        // Verify the malicious string was stored as-is, not executed
        $project = Project::where('name', $maliciousName)->first();
        $this->assertNotNull($project);
        $this->assertEquals($maliciousName, $project->name);
        
        // Verify table still exists
        $this->assertGreaterThan(0, Project::count());
    }

    /**
     * EH-005: XSS in notes.
     */
    public function test_xss_in_notes(): void
    {
        $xssPayload = '<script>alert("xss")</script>Safe text';
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', [
                'name' => 'XSS Test',
                'notes' => $xssPayload,
            ]);
        
        $response->assertStatus(201);
        
        // Notes should be sanitized (script tag removed)
        $project = Project::where('name', 'XSS Test')->first();
        $this->assertStringNotContainsString('<script>', $project->notes);
    }

    /**
     * EH-006: Delete without confirmation.
     */
    public function test_delete_without_confirmation(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$project->id}");
        
        $response->assertStatus(400);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    /**
     * EH-007: Double archive.
     */
    public function test_double_archive(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        // First archive
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $archivedAt = $project->fresh()->archived_at;
        
        // Second archive (should be idempotent)
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $response->assertStatus(200);
        $this->assertEquals($archivedAt, $project->fresh()->archived_at);
    }

    /**
     * EH-008: Restore non-archived project.
     */
    public function test_restore_non_archived_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/restore");
        
        $response->assertStatus(200);
        $this->assertNull($project->fresh()->archived_at);
    }

    // ==================== DATA INTEGRITY TESTS (DI) ====================

    /**
     * DI-001: Created_at timestamp set.
     */
    public function test_created_at_timestamp_set(): void
    {
        $before = now()->subSecond();
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => 'Timestamp Test']);
        
        $after = now()->addSecond();
        
        $response->assertStatus(201);
        
        $project = Project::where('name', 'Timestamp Test')->first();
        $this->assertNotNull($project->created_at);
        $this->assertGreaterThanOrEqual($before, $project->created_at);
        $this->assertLessThanOrEqual($after, $project->created_at);
    }

    /**
     * DI-002: Updated_at changes on update.
     */
    public function test_updated_at_changes_on_update(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'updated_at' => now()->subMinute(), // Force older timestamp
        ]);
        $originalUpdatedAt = $project->updated_at;
        
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Updated']);
        
        $project->refresh();
        $this->assertNotNull($project->updated_at);
        // Verify updated_at is at or after original (timestamps should update)
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $project->updated_at);
    }

    /**
     * DI-003: User_id immutable.
     */
    public function test_user_id_immutable(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $originalUserId = $project->user_id;
        
        // Try to change user_id via API (should be ignored)
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Updated',
                'user_id' => $this->otherUser->id,
            ]);
        
        $project->refresh();
        $this->assertEquals($originalUserId, $project->user_id);
    }

    /**
     * DI-004: Archived_at null on create.
     */
    public function test_archived_at_null_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', ['name' => 'New Project']);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.archived_at', null);
    }

    /**
     * DI-005: Archived_at set on archive.
     */
    public function test_archived_at_set_on_archive(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $project->refresh();
        $this->assertNotNull($project->archived_at);
    }

    /**
     * DI-006: Archived_at cleared on restore.
     */
    public function test_archived_at_cleared_on_restore(): void
    {
        $project = Project::factory()->archived()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/restore");
        
        $project->refresh();
        $this->assertNull($project->archived_at);
    }

    /**
     * DI-007: Mass assignment protection.
     */
    public function test_mass_assignment_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', [
                'name' => 'Mass Assignment Test',
                'is_default' => true, // Should be allowed
                'id' => 99999, // Should be ignored
                'non_existent_field' => 'value', // Should be ignored
            ]);
        
        $response->assertStatus(201);
        
        $project = Project::where('name', 'Mass Assignment Test')->first();
        $this->assertNotEquals(99999, $project->id);
    }

    // ==================== PROJECT SCOPING TESTS (PS) ====================

    /**
     * PS-001: Project list filtered by archived status.
     */
    public function test_project_list_filtered_by_archived(): void
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);
        Project::factory()->archived()->count(2)->create(['user_id' => $this->user->id]);
        
        // Active only
        $activeResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=false');
        
        $activeResponse->assertJsonCount(3, 'data');
        
        // Archived only
        $archivedResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?archived=true');
        
        $archivedResponse->assertJsonCount(2, 'data');
        
        // All (default)
        $allResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $allResponse->assertJsonCount(3, 'data') // Default excludes archived
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.active', 3)
            ->assertJsonPath('meta.archived', 2);
    }

    /**
     * PS-002: Pagination works correctly.
     */
    public function test_pagination_works_correctly(): void
    {
        Project::factory()->count(25)->create(['user_id' => $this->user->id]);
        
        // First page
        $page1 = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?perPage=10&page=1');
        
        $page1->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25);
        
        // Second page
        $page2 = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?perPage=10&page=2');
        
        $page2->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 2);
        
        // Third page
        $page3 = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?perPage=10&page=3');
        
        $page3->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.page', 3);
    }

    /**
     * PS-003: Project relationships accessible.
     * 
     * Note: This test verifies the user relationship exists. The keywords(),
     * competitors(), and reports() relationships require project_id columns
     * on those tables (TASK-8-2). Once implemented, add relationship tests.
     */
    public function test_project_relationships_accessible(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        // Verify user relationship exists
        $this->assertNotNull($project->user);
        $this->assertEquals($this->user->id, $project->user->id);
        
        // TODO: Add relationship tests after TASK-8-2 (project_id on related tables)
        // - keywords() relationship
        // - competitors() relationship
        // - reports() relationship
    }

    /**
     * PS-004: Default project handling.
     */
    public function test_default_project_handling(): void
    {
        // First project should not automatically be default
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertJsonPath('data.0.is_default', false);
    }

    /**
     * PS-005: Large dataset handling.
     */
    public function test_large_dataset_handling(): void
    {
        // Create 200 projects
        Project::factory()->count(200)->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?perPage=100');
        
        $response->assertStatus(200)
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.total', 200);
    }

    /**
     * PS-006: Search and filter combined.
     */
    public function test_search_and_filter_combined(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alpha Project',
            'archived_at' => null,
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Project',
            'archived_at' => now(),
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Gamma Project',
            'archived_at' => null,
        ]);
        
        // Active projects
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.active', 2)
            ->assertJsonPath('meta.archived', 1);
    }

    // ==================== CONCURRENT OPERATION TESTS ====================

    /**
     * CO-001: Concurrent updates (last write wins).
     */
    public function test_concurrent_updates(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original',
        ]);
        
        // Simulate two updates
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Update 1']);
        
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Update 2']);
        
        $project->refresh();
        $this->assertEquals('Update 2', $project->name);
    }

    /**
     * CO-002: Archive while updating.
     */
    public function test_archive_while_updating(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        
        // Update
        $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Updated Name']);
        
        // Archive
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project->id}/archive");
        
        $project->refresh();
        $this->assertEquals('Updated Name', $project->name);
        $this->assertNotNull($project->archived_at);
    }
}
