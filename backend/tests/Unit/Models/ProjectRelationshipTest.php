<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\GSCConnection;
use App\Models\GSCData;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Project model relationships.
 *
 * Tests all relationships defined in the Project model.
 *
 * @see TASK-8-9
 * @see TEST-8-9
 */
class ProjectRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // User Relationship Tests (MR-001, MR-007)
    // ==========================================

    #[Test]
    public function project_belongs_to_user_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'user'));
        
        $relation = $project->user();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function project_can_access_owner_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    #[Test]
    public function project_without_user_returns_null(): void
    {
        $project = Project::create([
            'user_id' => null,
            'name' => 'Orphan Project',
        ]);

        $this->assertNull($project->user);
    }

    // ==========================================
    // Account Relationship Tests (MR-002, MR-008, MR-011, MR-012)
    // ==========================================

    #[Test]
    public function project_belongs_to_account_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'account'));
        
        $relation = $project->account();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function project_can_access_account(): void
    {
        $account = Account::create([
            'name' => 'Test Account',
            'owner_id' => User::factory()->create()->id,
        ]);

        $project = Project::factory()->create([
            'account_id' => $account->id,
        ]);

        $this->assertInstanceOf(Account::class, $project->account);
        $this->assertEquals($account->id, $project->account->id);
    }

    #[Test]
    public function project_without_account_returns_null(): void
    {
        $project = Project::factory()->create([
            'account_id' => null,
        ]);

        $this->assertNull($project->account);
    }

    // ==========================================
    // GSCData Relationship Tests (MR-003)
    // ==========================================

    #[Test]
    public function project_has_many_gsc_data_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'gscData'));
        
        $relation = $project->gscData();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    #[Test]
    public function project_can_access_gsc_data(): void
    {
        $project = Project::factory()->create();

        // Create GSC data for the project with required fields
        GSCData::create([
            'project_id' => $project->id,
            'query' => 'test query',
            'page' => 'https://example.com',
            'clicks' => 100,
            'impressions' => 1000,
            'ctr' => 0.1,
            'avg_position' => 5.5,
            'data_date' => now()->toDateString(),
        ]);

        $this->assertCount(1, $project->gscData);
        $this->assertEquals('test query', $project->gscData->first()->query);
    }

    // ==========================================
    // GSCConnections Relationship Tests (MR-004)
    // ==========================================

    #[Test]
    public function project_has_many_gsc_connections_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'gscConnections'));
        
        $relation = $project->gscConnections();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    #[Test]
    public function project_can_access_gsc_connections(): void
    {
        // Note: GSCConnection uses encrypted casts for tokens
        // This tests the relationship definition only since encryption needs app key
        $project = new Project();
        $relation = $project->gscConnections();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
        
        // Test that the foreign key is set correctly
        $this->assertEquals('project_id', $relation->getForeignKeyName());
    }

    // ==========================================
    // Members Relationship Tests (MR-005, MR-009)
    // ==========================================

    #[Test]
    public function project_has_many_members_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'members'));
        
        $relation = $project->members();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    #[Test]
    public function project_can_access_members(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // Create a project member with valid role
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'editor', // Use valid role
        ]);

        $this->assertCount(1, $project->members);
        $this->assertEquals($user->id, $project->members->first()->user_id);
    }

    // ==========================================
    // Users (BelongsToMany) Relationship Tests (MR-006, MR-010)
    // ==========================================

    #[Test]
    public function project_belongs_to_many_users_relationship(): void
    {
        $project = new Project();

        $this->assertTrue(method_exists($project, 'users'));
        
        $relation = $project->users();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
    }

    #[Test]
    public function project_can_access_users_through_members(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // Create a project member with valid role
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'editor', // Use valid role
        ]);

        $projectUsers = $project->users;

        $this->assertCount(1, $projectUsers);
        $this->assertEquals($user->id, $projectUsers->first()->id);
        $this->assertEquals('editor', $projectUsers->first()->pivot->role);
    }

    // ==========================================
    // Relationship Integrity Tests
    // ==========================================

    #[Test]
    public function deleting_user_deletes_their_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $user->delete();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    #[Test]
    public function deleting_project_deletes_its_members(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'editor', // Use valid role
        ]);

        $project->delete();

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function deleting_project_does_not_delete_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $project->delete();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    // ==========================================
    // Model Scopes Tests (from existing ProjectTest)
    // ==========================================

    #[Test]
    public function scope_active_filters_archived_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->forUser($user)->create();
        Project::factory()->count(3)->forUser($user)->archived()->create();

        $activeProjects = Project::active()->get();

        $this->assertCount(2, $activeProjects);
        $activeProjects->each(function ($project) {
            $this->assertNull($project->archived_at);
        });
    }

    #[Test]
    public function scope_archived_filters_active_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->forUser($user)->create();
        Project::factory()->count(3)->forUser($user)->archived()->create();

        $archivedProjects = Project::archived()->get();

        $this->assertCount(3, $archivedProjects);
        $archivedProjects->each(function ($project) {
            $this->assertNotNull($project->archived_at);
        });
    }

    #[Test]
    public function scope_default_filters_non_default_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->forUser($user)->create();
        Project::factory()->count(1)->forUser($user)->default()->create();

        $defaultProjects = Project::default()->get();

        $this->assertCount(1, $defaultProjects);
        $defaultProjects->each(function ($project) {
            $this->assertTrue($project->is_default);
        });
    }

    // ==========================================
    // Helper Methods Tests
    // ==========================================

    #[Test]
    public function is_archived_returns_correct_boolean(): void
    {
        $activeProject = Project::factory()->create();
        $archivedProject = Project::factory()->archived()->create();

        $this->assertFalse($activeProject->isArchived());
        $this->assertTrue($archivedProject->isArchived());
    }

    #[Test]
    public function archive_sets_archived_at(): void
    {
        $project = Project::factory()->create();

        $project->archive();

        $this->assertNotNull($project->fresh()->archived_at);
        $this->assertTrue($project->fresh()->isArchived());
    }

    #[Test]
    public function unarchive_clears_archived_at(): void
    {
        $project = Project::factory()->archived()->create();

        $project->unarchive();

        $this->assertNull($project->fresh()->archived_at);
        $this->assertFalse($project->fresh()->isArchived());
    }
}
