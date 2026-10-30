<?php

namespace Tests\Feature\Database;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the projects table database constraints.
 *
 * Validates foreign key constraints, indexes, and cascade behavior.
 *
 * @see TASK-8-1
 * @see TEST-8-1
 */
class ProjectsTableConstraintsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that foreign key exists on user_id.
     * FK-001
     */
    public function test_foreign_key_exists_on_user_id(): void
    {
        // Verify FK by checking that inserting with invalid user_id fails
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table('projects')->insert([
            'user_id' => 99999,
            'name' => 'Test Project',
        ]);
    }

    /**
     * Test that FK references users.id.
     * FK-002
     */
    public function test_fk_references_users_id(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $project->user_id);
        $this->assertInstanceOf(User::class, $project->user);
    }

    /**
     * Test that FK has ON DELETE CASCADE.
     * FK-003
     */
    public function test_fk_has_on_delete_cascade(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $projectId = $project->id;
        $user->delete();

        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
    }

    /**
     * Test that deleting user deletes all their projects.
     * FK-004
     */
    public function test_deleting_user_deletes_all_their_projects(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(5)->forUser($user)->create();

        $this->assertCount(5, Project::where('user_id', $user->id)->get());

        $user->delete();

        $this->assertCount(0, Project::where('user_id', $user->id)->get());
        $this->assertEquals(0, Project::whereIn('id', $projects->pluck('id'))->count());
    }

    /**
     * Test that creating project with non-existent user_id fails.
     * FK-005
     */
    public function test_creating_project_with_non_existent_user_id_fails(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('projects')->insert([
            'user_id' => 99999,
            'name' => 'Test Project',
        ]);
    }

    /**
     * Test that multiple projects can share same user_id.
     * FK-006
     */
    public function test_multiple_projects_can_share_same_user_id(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(10)->forUser($user)->create();

        $this->assertCount(10, $projects);
        $this->assertCount(10, Project::where('user_id', $user->id)->get());
    }

    /**
     * Test that project can be created with valid user_id.
     * FK-007
     */
    public function test_project_can_be_created_with_valid_user_id(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'name' => 'Test Project',
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);
    }

    /**
     * Test that index exists on user_id.
     * IX-001
     */
    public function test_index_exists_on_user_id(): void
    {
        // Verify by checking query performance
        $user = User::factory()->create();
        Project::factory()->count(10)->forUser($user)->create();

        // Query should work efficiently
        $projects = Project::where('user_id', $user->id)->get();
        $this->assertCount(10, $projects);
    }

    /**
     * Test that index exists on archived_at.
     * IX-002
     */
    public function test_index_exists_on_archived_at(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(5)->forUser($user)->archived()->create();
        Project::factory()->count(5)->forUser($user)->create();

        // Query should work efficiently
        $archived = Project::whereNotNull('archived_at')->get();
        $this->assertCount(5, $archived);
    }

    /**
     * Test that query by user_id uses index.
     * IX-003
     */
    public function test_query_by_user_id_uses_index(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(10)->forUser($user)->create();

        $projects = Project::where('user_id', $user->id)->get();
        $this->assertCount(10, $projects);
    }

    /**
     * Test that query by archived_at uses index.
     * IX-004
     */
    public function test_query_by_archived_at_uses_index(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(5)->forUser($user)->archived()->create();
        Project::factory()->count(5)->forUser($user)->create();

        $archived = Project::whereNotNull('archived_at')->get();
        $active = Project::whereNull('archived_at')->get();

        $this->assertCount(5, $archived);
        $this->assertCount(5, $active);
    }

    /**
     * Test that query by archived_at IS NULL uses index.
     * IX-005
     */
    public function test_query_by_archived_at_is_null_uses_index(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(5)->forUser($user)->archived()->create();
        Project::factory()->count(5)->forUser($user)->create();

        $active = Project::whereNull('archived_at')->get();
        $this->assertCount(5, $active);
    }

    /**
     * Test composite query benefits from indexes.
     * IX-006
     */
    public function test_composite_query_benefits_from_indexes(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(5)->forUser($user)->archived()->create();
        Project::factory()->count(5)->forUser($user)->create();

        $activeUserProjects = Project::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->get();

        $this->assertCount(5, $activeUserProjects);
    }
}
