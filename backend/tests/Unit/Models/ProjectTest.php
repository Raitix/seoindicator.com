<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Project model.
 *
 * Validates model behavior, relationships, fillable attributes,
 * casts, and helper methods.
 *
 * @see TASK-8-1
 * @see TEST-8-1
 */
class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Project model exists.
     * MD-001
     */
    public function test_project_model_exists(): void
    {
        $this->assertTrue(class_exists(Project::class));
    }

    /**
     * Test that model has correct fillable attributes.
     * MD-002
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $project = new Project();
        $expectedFillable = [
            'user_id',
            'name',
            'domain',
            'notes',
            'is_default',
            'archived_at',
            'backlinks_last_synced_at',
            'account_id',
        ];

        $this->assertEquals($expectedFillable, $project->getFillable());
    }

    /**
     * Test that model has correct cast types.
     * MD-003
     */
    public function test_model_has_correct_cast_types(): void
    {
        $project = new Project();
        $casts = $project->getCasts();

        $this->assertEquals('boolean', $casts['is_default']);
        $this->assertEquals('datetime', $casts['archived_at']);
        $this->assertEquals('datetime', $casts['backlinks_last_synced_at']);
    }

    /**
     * Test that Project belongs to User relationship.
     * MD-004
     */
    public function test_project_belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    /**
     * Test that User has many Projects relationship.
     * MD-005
     */
    public function test_user_has_many_projects_relationship(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->forUser($user)->create();

        $this->assertCount(3, $user->projects);
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }

    /**
     * Test that model can be created with factory.
     * MD-006
     */
    public function test_model_can_be_created_with_factory(): void
    {
        $project = Project::factory()->create();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    /**
     * Test that model timestamps are auto-managed.
     * MD-007
     */
    public function test_model_timestamps_are_auto_managed(): void
    {
        $project = Project::factory()->create();

        $this->assertNotNull($project->created_at);
        $this->assertNotNull($project->updated_at);
    }

    /**
     * Test that archived_at can be set and cleared.
     * MD-008
     */
    public function test_archived_at_can_be_set_and_cleared(): void
    {
        $project = Project::factory()->create();

        // Set archived_at
        $project->archived_at = now();
        $project->save();
        $this->assertNotNull($project->fresh()->archived_at);

        // Clear archived_at
        $project->archived_at = null;
        $project->save();
        $this->assertNull($project->fresh()->archived_at);
    }

    /**
     * Test that is_default defaults to false.
     * MD-009
     */
    public function test_is_default_defaults_to_false(): void
    {
        // Create project without specifying is_default
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);

        $this->assertFalse($project->fresh()->is_default);
    }

    /**
     * Test model archive behavior.
     * MD-010
     */
    public function test_model_soft_archive_behavior(): void
    {
        $project = Project::factory()->create();
        $this->assertFalse($project->isArchived());

        $project->archive();
        $this->assertTrue($project->fresh()->isArchived());
        $this->assertNotNull($project->fresh()->archived_at);

        $project->unarchive();
        $this->assertFalse($project->fresh()->isArchived());
        $this->assertNull($project->fresh()->archived_at);
    }

    /**
     * Test scope for active (non-archived) projects.
     */
    public function test_scope_active_returns_non_archived_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->forUser($user)->create();
        Project::factory()->count(1)->forUser($user)->archived()->create();

        $activeProjects = Project::active()->get();

        $this->assertCount(2, $activeProjects);
    }

    /**
     * Test scope for archived projects.
     */
    public function test_scope_archived_returns_archived_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->forUser($user)->create();
        Project::factory()->count(3)->forUser($user)->archived()->create();

        $archivedProjects = Project::archived()->get();

        $this->assertCount(3, $archivedProjects);
    }

    /**
     * Test scope for default projects.
     */
    public function test_scope_default_returns_default_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->forUser($user)->create();
        Project::factory()->count(1)->forUser($user)->default()->create();

        $defaultProjects = Project::default()->get();

        $this->assertCount(1, $defaultProjects);
        $this->assertTrue($defaultProjects->first()->is_default);
    }
}
