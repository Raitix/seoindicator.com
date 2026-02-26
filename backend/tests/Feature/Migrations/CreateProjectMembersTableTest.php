<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProjectMembersTableTest extends TestCase
{
    /**
     * Test that migration creates project_members table with all required columns.
     */
    public function test_migration_creates_project_members_table(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(Schema::hasTable('project_members'));

        $columns = [
            'id',
            'project_id',
            'user_id',
            'role',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('project_members', $column),
                "Missing column: {$column}"
            );
        }
    }

    /**
     * Test that role enum has correct values.
     */
    public function test_role_enum_validation(): void
    {
        $this->artisan('migrate:fresh');

        // Create test project and user
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test valid role: editor
        $editorId = DB::table('project_members')->insertGetId([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertNotNull($editorId);

        // Test valid role: viewer
        $viewerId = DB::table('project_members')->insertGetId([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertNotNull($viewerId);
    }

    /**
     * Test that default role is 'viewer'.
     */
    public function test_default_role_is_viewer(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'default@example.com',
            'name' => 'Default User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert without specifying role
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $member = DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        $this->assertEquals('viewer', $member->role);
    }

    /**
     * Test that unique constraint on (project_id, user_id) exists.
     */
    public function test_unique_constraint_on_project_user(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Unique Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'unique@example.com',
            'name' => 'Unique User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // First insert should succeed
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Duplicate insert should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that same user can be in multiple projects.
     */
    public function test_same_user_in_multiple_projects(): void
    {
        $this->artisan('migrate:fresh');

        $project1Id = DB::table('projects')->insertGetId([
            'name' => 'Project 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project2Id = DB::table('projects')->insertGetId([
            'name' => 'Project 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'multi@example.com',
            'name' => 'Multi Project User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // User in project 1
        DB::table('project_members')->insert([
            'project_id' => $project1Id,
            'user_id' => $userId,
            'role' => 'editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Same user in project 2 - should succeed
        DB::table('project_members')->insert([
            'project_id' => $project2Id,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberships = DB::table('project_members')
            ->where('user_id', $userId)
            ->count();

        $this->assertEquals(2, $memberships);
    }

    /**
     * Test that multiple users can be in same project.
     */
    public function test_multiple_users_in_same_project(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Shared Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user1Id = DB::table('users')->insertGetId([
            'email' => 'user1@example.com',
            'name' => 'User 1',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user2Id = DB::table('users')->insertGetId([
            'email' => 'user2@example.com',
            'name' => 'User 2',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // User 1 in project
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $user1Id,
            'role' => 'editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // User 2 in same project - should succeed
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $user2Id,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $members = DB::table('project_members')
            ->where('project_id', $projectId)
            ->count();

        $this->assertEquals(2, $members);
    }

    /**
     * Test foreign key constraint: insert with non-existent project fails.
     */
    public function test_foreign_key_project_enforced(): void
    {
        $this->artisan('migrate:fresh');

        $userId = DB::table('users')->insertGetId([
            'email' => 'fk@example.com',
            'name' => 'FK User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SQLite with foreign keys enabled should reject this
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('project_members')->insert([
            'project_id' => 99999,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test foreign key constraint: insert with non-existent user fails.
     */
    public function test_foreign_key_user_enforced(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'FK Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SQLite with foreign keys enabled should reject this
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => 99999,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test ON DELETE CASCADE for project_id.
     */
    public function test_cascade_delete_project(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Cascade Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'cascade@example.com',
            'name' => 'Cascade User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify member exists
        $member = DB::table('project_members')
            ->where('project_id', $projectId)
            ->first();
        $this->assertNotNull($member);

        // Delete project
        DB::table('projects')->where('id', $projectId)->delete();

        // Member should also be deleted
        $member = DB::table('project_members')
            ->where('project_id', $projectId)
            ->first();
        $this->assertNull($member);
    }

    /**
     * Test ON DELETE CASCADE for user_id.
     */
    public function test_cascade_delete_user(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'User Cascade Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'usercascade@example.com',
            'name' => 'User Cascade',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify member exists
        $member = DB::table('project_members')
            ->where('user_id', $userId)
            ->first();
        $this->assertNotNull($member);

        // Delete user
        DB::table('users')->where('id', $userId)->delete();

        // Member should also be deleted
        $member = DB::table('project_members')
            ->where('user_id', $userId)
            ->first();
        $this->assertNull($member);
    }

    /**
     * Test CRUD operations on project_members.
     */
    public function test_crud_operations(): void
    {
        $this->artisan('migrate:fresh');

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'CRUD Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'email' => 'crud@example.com',
            'name' => 'CRUD User',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // CREATE
        $memberId = DB::table('project_members')->insertGetId([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'viewer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertNotNull($memberId);

        // READ
        $member = DB::table('project_members')->find($memberId);
        $this->assertEquals('viewer', $member->role);

        // UPDATE
        DB::table('project_members')
            ->where('id', $memberId)
            ->update(['role' => 'editor', 'updated_at' => now()]);

        $member = DB::table('project_members')->find($memberId);
        $this->assertEquals('editor', $member->role);

        // DELETE
        DB::table('project_members')->where('id', $memberId)->delete();
        $member = DB::table('project_members')->find($memberId);
        $this->assertNull($member);
    }

    /**
     * Test migration rollback drops table.
     */
    public function test_migration_rollback(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(Schema::hasTable('project_members'));

        // Rollback last migration
        $this->artisan('migrate:rollback', ['--step' => 1]);

        $this->assertFalse(Schema::hasTable('project_members'));
    }

    /**
     * Test migration can be re-run after rollback.
     */
    public function test_migration_rerun_after_rollback(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('migrate:rollback', ['--step' => 1]);
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('project_members'));
    }
}
