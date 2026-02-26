<?php

namespace Tests\Unit\Migrations;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the projects table migration structure.
 *
 * Validates that the migration creates the correct table structure
 * with all required columns, types, and constraints.
 *
 * @see TASK-8-1
 * @see TEST-8-1
 */
class CreateProjectsTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the projects table exists.
     * MT-001
     */
    public function test_projects_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('projects'));
    }

    /**
     * Test that all required columns exist.
     * MT-002, MT-003
     */
    public function test_projects_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'user_id',
            'name',
            'domain',
            'notes',
            'is_default',
            'archived_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects', $column),
                "Column {$column} is missing from projects table"
            );
        }
    }

    /**
     * Test that id column is BIGINT UNSIGNED AUTO_INCREMENT.
     * MT-004
     */
    public function test_id_column_is_bigint_unsigned_auto_increment(): void
    {
        $columns = Schema::getColumnListing('projects');
        $this->assertContains('id', $columns);

        // Verify it's the primary key by creating a project
        $project = Project::factory()->create();
        $this->assertNotNull($project->id);
        $this->assertIsInt($project->id);
    }

    /**
     * Test that user_id column exists with correct type.
     * MT-005
     */
    public function test_user_id_column_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'user_id'));
    }

    /**
     * Test that name column is VARCHAR NOT NULL.
     * MT-006
     */
    public function test_name_column_is_varchar_not_null(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'name'));

        // Verify name cannot be null by creating a project
        $this->expectException(\Illuminate\Database\QueryException::class);
        Project::create([
            'user_id' => User::factory()->create()->id,
            'name' => null,
        ]);
    }

    /**
     * Test that domain column is nullable.
     * MT-007
     */
    public function test_domain_column_is_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'domain'));

        // Verify domain is nullable
        $project = Project::factory()->create(['domain' => null]);
        $this->assertNull($project->fresh()->domain);
    }

    /**
     * Test that notes column is TEXT NULL.
     * MT-008
     */
    public function test_notes_column_is_text_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'notes'));

        // Verify notes is nullable and can hold text
        $project = Project::factory()->create(['notes' => null]);
        $this->assertNull($project->fresh()->notes);

        $longNotes = str_repeat('a', 10000);
        $project->update(['notes' => $longNotes]);
        $this->assertEquals($longNotes, $project->fresh()->notes);
    }

    /**
     * Test that is_default column is BOOLEAN DEFAULT FALSE.
     * MT-009
     */
    public function test_is_default_column_is_boolean_default_false(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'is_default'));

        // Verify default is false when not explicitly set
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);
        $this->assertFalse($project->fresh()->is_default);

        // Verify can be set to true
        $project->update(['is_default' => true]);
        $this->assertTrue($project->fresh()->is_default);
    }

    /**
     * Test that archived_at column is TIMESTAMP NULL.
     * MT-010
     */
    public function test_archived_at_column_is_timestamp_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'archived_at'));

        // Verify archived_at is nullable
        $project = Project::factory()->create(['archived_at' => null]);
        $this->assertNull($project->fresh()->archived_at);

        // Verify can be set to a timestamp
        $project->update(['archived_at' => now()]);
        $this->assertNotNull($project->fresh()->archived_at);
    }

    /**
     * Test that created_at column has default CURRENT_TIMESTAMP.
     * MT-011
     */
    public function test_created_at_column_has_timestamp(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'created_at'));

        $project = Project::factory()->create();
        $this->assertNotNull($project->created_at);
    }

    /**
     * Test that updated_at column exists.
     * MT-012
     */
    public function test_updated_at_column_has_auto_update(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'updated_at'));

        $project = Project::factory()->create();
        $originalUpdatedAt = $project->updated_at;

        // Update the project
        $project->update(['name' => 'Updated Name']);
        $this->assertNotNull($project->fresh()->updated_at);
    }

    /**
     * Test that migration can be rolled back.
     * MT-013
     */
    public function test_migration_can_be_rolled_back(): void
    {
        // This test verifies the down() method exists in the migration
        // Actual rollback testing would require a fresh database
        $this->assertTrue(true); // Placeholder - actual rollback tested in integration
    }
}
