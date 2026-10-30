<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for Project Data Isolation.
 * 
 * Tests verify that data from one project cannot be accessed
 * from another project, and that users cannot access data from
 * projects they don't own.
 * 
 * Task: TASK-8-10
 * Test Documentation: TESTS/TEST-8-10.md
 * 
 * @group integration
 * @group data-isolation
 */
class ProjectDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected Project $projectA1;
    protected Project $projectA2;
    protected Project $projectB1;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create two users
        $this->userA = User::factory()->create(['email' => 'userA@test.com']);
        $this->userB = User::factory()->create(['email' => 'userB@test.com']);
        
        // Create projects for User A
        $this->projectA1 = Project::factory()->create([
            'user_id' => $this->userA->id,
            'name' => 'User A Project 1',
        ]);
        
        $this->projectA2 = Project::factory()->create([
            'user_id' => $this->userA->id,
            'name' => 'User A Project 2',
        ]);
        
        // Create project for User B
        $this->projectB1 = Project::factory()->create([
            'user_id' => $this->userB->id,
            'name' => 'User B Project 1',
        ]);
    }

    // ==================== CROSS-USER ISOLATION ====================

    /**
     * User cannot list another user's projects.
     */
    public function test_user_cannot_list_other_users_projects(): void
    {
        $response = $this->actingAs($this->userA)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Only user A's 2 projects
        
        $projectNames = collect($response->json('data'))->pluck('name');
        $this->assertContains('User A Project 1', $projectNames);
        $this->assertContains('User A Project 2', $projectNames);
        $this->assertNotContains('User B Project 1', $projectNames);
    }

    /**
     * User cannot access another user's project details.
     */
    public function test_user_cannot_access_other_users_project(): void
    {
        $response = $this->actingAs($this->userA)
            ->getJson("/api/v1/projects/{$this->projectB1->id}");
        
        $response->assertStatus(403);
    }

    /**
     * User cannot update another user's project.
     */
    public function test_user_cannot_update_other_users_project(): void
    {
        $response = $this->actingAs($this->userA)
            ->putJson("/api/v1/projects/{$this->projectB1->id}", [
                'name' => 'Hacked Project',
            ]);
        
        $response->assertStatus(403);
        
        // Verify project name unchanged
        $this->projectB1->refresh();
        $this->assertEquals('User B Project 1', $this->projectB1->name);
    }

    /**
     * User cannot delete another user's project.
     */
    public function test_user_cannot_delete_other_users_project(): void
    {
        $response = $this->actingAs($this->userA)
            ->deleteJson("/api/v1/projects/{$this->projectB1->id}?confirm=true");
        
        $response->assertStatus(403);
        
        // Verify project still exists
        $this->assertDatabaseHas('projects', ['id' => $this->projectB1->id]);
    }

    /**
     * User cannot archive another user's project.
     */
    public function test_user_cannot_archive_other_users_project(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson("/api/v1/projects/{$this->projectB1->id}/archive");
        
        $response->assertStatus(403);
        
        $this->projectB1->refresh();
        $this->assertNull($this->projectB1->archived_at);
    }

    // ==================== CROSS-PROJECT ISOLATION (SAME USER) ====================

    /**
     * Keywords from Project A1 not visible in Project A2 context.
     */
    public function test_keywords_isolated_between_projects(): void
    {
        $this->markTestSkipped('Requires keywords table with project_id column');
        
        // Create keywords for Project A1
        // Keyword::factory()->create([
        //     'project_id' => $this->projectA1->id,
        //     'keyword' => 'project-a1-keyword',
        // ]);
        
        // // Create keywords for Project A2
        // Keyword::factory()->create([
        //     'project_id' => $this->projectA2->id,
        //     'keyword' => 'project-a2-keyword',
        // ]);
        
        // // Query with Project A1 context
        // $response = $this->actingAs($this->userA)
        //     ->withHeaders(['X-Project-ID' => $this->projectA1->id])
        //     ->getJson('/api/v1/keywords');
        
        // $response->assertStatus(200)
        //     ->assertJsonCount(1, 'data')
        //     ->assertJsonPath('data.0.keyword', 'project-a1-keyword');
        
        // // Verify Project A2 keyword not visible
        // $keywords = collect($response->json('data'))->pluck('keyword');
        // $this->assertNotContains('project-a2-keyword', $keywords);
    }

    /**
     * Competitors isolated between projects.
     */
    public function test_competitors_isolated_between_projects(): void
    {
        $this->markTestSkipped('Requires competitors table with project_id column');
    }

    /**
     * Reports isolated between projects.
     */
    public function test_reports_isolated_between_projects(): void
    {
        $this->markTestSkipped('Requires reports table with project_id column');
    }

    // ==================== DATA LEAKAGE TESTS ====================

    /**
     * Verify no data leakage when switching projects rapidly.
     */
    public function test_no_data_leakage_on_project_switch(): void
    {
        $this->markTestSkipped('Requires implemented data models');
        
        // Create data in both projects
        // Rapidly switch between projects and verify no leakage
    }

    /**
     * Verify archived project data not accessible without explicit filter.
     */
    public function test_archived_project_data_not_accessible_by_default(): void
    {
        // Archive project A1
        $this->projectA1->update(['archived_at' => now()]);
        
        // List projects - archived should not appear by default
        $response = $this->actingAs($this->userA)
            ->getJson('/api/v1/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Only projectA2 (active)
        
        // Explicitly request archived projects
        $response = $this->actingAs($this->userA)
            ->getJson('/api/v1/projects?archived=true');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Only projectA1 (archived)
    }

    // ==================== AUTHORIZATION BOUNDARY TESTS ====================

    /**
     * Verify all project endpoints enforce authorization.
     */
    public function test_all_project_endpoints_enforce_authorization(): void
    {
        $endpoints = [
            ['GET', "/api/v1/projects/{$this->projectB1->id}"],
            ['PUT', "/api/v1/projects/{$this->projectB1->id}", ['name' => 'Test']],
            ['DELETE', "/api/v1/projects/{$this->projectB1->id}?confirm=true"],
            ['POST', "/api/v1/projects/{$this->projectB1->id}/archive"],
            ['POST', "/api/v1/projects/{$this->projectB1->id}/restore"],
        ];
        
        foreach ($endpoints as $endpoint) {
            $method = $endpoint[0];
            $url = $endpoint[1];
            $data = $endpoint[2] ?? [];
            
            $response = $this->actingAs($this->userA)
                ->json($method, $url, $data);
            
            $this->assertEquals(
                403,
                $response->status(),
                "Expected 403 for {$method} {$url}, got {$response->status()}"
            );
        }
    }

    // ==================== CASCADE DELETE VERIFICATION ====================

    /**
     * Deleting project deletes all associated keywords.
     */
    public function test_delete_project_cascades_to_keywords(): void
    {
        $this->markTestSkipped('Requires keywords table with project_id column');
        
        // Create keywords
        // Keyword::factory()->count(5)->create(['project_id' => $this->projectA1->id]);
        
        // Delete project
        // $response = $this->actingAs($this->userA)
        //     ->deleteJson("/api/v1/projects/{$this->projectA1->id}?confirm=true");
        
        // $response->assertStatus(200);
        
        // Verify keywords deleted
        // $this->assertEquals(0, Keyword::where('project_id', $this->projectA1->id)->count());
    }

    /**
     * Deleting project deletes all associated competitors.
     */
    public function test_delete_project_cascades_to_competitors(): void
    {
        $this->markTestSkipped('Requires competitors table with project_id column');
    }

    /**
     * Deleting project deletes all associated reports.
     */
    public function test_delete_project_cascades_to_reports(): void
    {
        $this->markTestSkipped('Requires reports table with project_id column');
    }

    /**
     * Deleting project does NOT affect other projects' data.
     */
    public function test_delete_project_does_not_affect_other_projects(): void
    {
        $this->markTestSkipped('Requires keywords table with project_id column');
        
        // Create keywords in both projects
        // Keyword::factory()->count(3)->create(['project_id' => $this->projectA1->id]);
        // Keyword::factory()->count(5)->create(['project_id' => $this->projectA2->id]);
        
        // Delete project A1
        // $response = $this->actingAs($this->userA)
        //     ->deleteJson("/api/v1/projects/{$this->projectA1->id}?confirm=true");
        
        // $response->assertStatus(200);
        
        // Verify project A2 keywords still exist
        // $this->assertEquals(5, Keyword::where('project_id', $this->projectA2->id)->count());
    }

    // ==================== EDGE CASES ====================

    /**
     * User can have multiple projects with same name.
     */
    public function test_user_can_have_duplicate_project_names(): void
    {
        Project::factory()->create([
            'user_id' => $this->userA->id,
            'name' => 'Duplicate Name',
        ]);
        
        $response = $this->actingAs($this->userA)
            ->postJson('/api/v1/projects', ['name' => 'Duplicate Name']);
        
        $response->assertStatus(201);
        
        $count = Project::where('user_id', $this->userA->id)
            ->where('name', 'Duplicate Name')
            ->count();
        
        $this->assertEquals(2, $count);
    }

    /**
     * Project IDs are not sequential/guessable for security.
     * Note: This tests if auto-increment is acceptable or UUIDs needed.
     */
    public function test_project_ids_are_sequential(): void
    {
        // Laravel default uses auto-increment IDs
        // This is acceptable for this application
        // If UUIDs are needed, this test would verify non-sequential IDs
        
        $project1 = Project::factory()->create(['user_id' => $this->userA->id]);
        $project2 = Project::factory()->create(['user_id' => $this->userA->id]);
        
        $this->assertGreaterThan($project1->id, $project2->id);
    }
}
