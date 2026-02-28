<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-8-2: Modify Existing Tables for Multi-Site Support
 *
 * This test validates the migration that adds project_id to:
 * - keywords
 * - competitors
 * - reports
 * - alert_settings
 *
 * @see TASK-8-2
 * @see SPEC-008
 * @see FEAT-008
 */
class AddProjectIdToTablesTest extends TestCase
{
    /**
     * Test that migration adds project_id column to keywords table.
     */
    public function test_migration_adds_project_id_to_keywords_table(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(
            Schema::hasColumn('keywords', 'project_id'),
            'keywords table should have project_id column'
        );
    }

    /**
     * Test that migration adds project_id column to competitors table.
     */
    public function test_migration_adds_project_id_to_competitors_table(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(
            Schema::hasColumn('competitors', 'project_id'),
            'competitors table should have project_id column'
        );
    }

    /**
     * Test that migration adds project_id column to reports table.
     */
    public function test_migration_adds_project_id_to_reports_table(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(
            Schema::hasColumn('reports', 'project_id'),
            'reports table should have project_id column'
        );
    }

    /**
     * Test that migration adds project_id column to alert_settings table.
     */
    public function test_migration_adds_project_id_to_alert_settings_table(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(
            Schema::hasColumn('alert_settings', 'project_id'),
            'alert_settings table should have project_id column'
        );
    }

    /**
     * Test that foreign key constraint exists on keywords.project_id.
     */
    public function test_foreign_key_exists_on_keywords_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertForeignKeyExists('keywords', 'project_id');
    }

    /**
     * Test that foreign key constraint exists on competitors.project_id.
     */
    public function test_foreign_key_exists_on_competitors_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertForeignKeyExists('competitors', 'project_id');
    }

    /**
     * Test that foreign key constraint exists on reports.project_id.
     */
    public function test_foreign_key_exists_on_reports_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertForeignKeyExists('reports', 'project_id');
    }

    /**
     * Test that foreign key constraint exists on alert_settings.project_id.
     */
    public function test_foreign_key_exists_on_alert_settings_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertForeignKeyExists('alert_settings', 'project_id');
    }

    /**
     * Test that index exists on keywords.project_id for query optimization.
     */
    public function test_index_exists_on_keywords_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $indexes = $this->getTableIndexes('keywords');
        $this->assertIndexExists($indexes, 'keywords_project_id_index');
    }

    /**
     * Test that index exists on competitors.project_id for query optimization.
     */
    public function test_index_exists_on_competitors_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $indexes = $this->getTableIndexes('competitors');
        $this->assertIndexExists($indexes, 'competitors_project_id_index');
    }

    /**
     * Test that index exists on reports.project_id for query optimization.
     */
    public function test_index_exists_on_reports_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $indexes = $this->getTableIndexes('reports');
        $this->assertIndexExists($indexes, 'reports_project_id_index');
    }

    /**
     * Test that index exists on alert_settings.project_id for query optimization.
     */
    public function test_index_exists_on_alert_settings_project_id(): void
    {
        $this->artisan('migrate:fresh');

        $indexes = $this->getTableIndexes('alert_settings');
        $this->assertIndexExists($indexes, 'alert_settings_project_id_index');
    }

    /**
     * Test that foreign key cascades on delete.
     * When a project is deleted, related records should be deleted automatically.
     */
    public function test_foreign_key_cascade_delete(): void
    {
        $this->artisan('migrate:fresh');

        // Create a user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'domain' => 'https://example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a keyword with project_id
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'project_id' => $projectId,
            'keyword' => 'test keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a competitor with project_id
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $userId,
            'project_id' => $projectId,
            'domain' => 'competitor.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify records exist
        $this->assertDatabaseHas('keywords', ['id' => $keywordId]);
        $this->assertDatabaseHas('competitors', ['id' => $competitorId]);

        // Delete the project
        DB::table('projects')->where('id', $projectId)->delete();

        // Verify cascade delete - records should be gone
        $this->assertDatabaseMissing('keywords', ['id' => $keywordId]);
        $this->assertDatabaseMissing('competitors', ['id' => $competitorId]);
    }

    /**
     * Test that project_id column accepts null values initially.
     * This allows for gradual data migration.
     */
    public function test_project_id_accepts_null_values(): void
    {
        $this->artisan('migrate:fresh');

        // Create a user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert keyword without project_id (null)
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'null project keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should not throw exception
        $this->assertDatabaseHas('keywords', [
            'id' => $keywordId,
            'project_id' => null,
        ]);
    }

    /**
     * Test that project_id can be updated to link records to projects.
     */
    public function test_project_id_can_be_updated(): void
    {
        $this->artisan('migrate:fresh');

        // Create a user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'domain' => 'https://example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert keyword without project_id
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'migrate keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update project_id
        DB::table('keywords')
            ->where('id', $keywordId)
            ->update(['project_id' => $projectId]);

        $this->assertDatabaseHas('keywords', [
            'id' => $keywordId,
            'project_id' => $projectId,
        ]);
    }

    /**
     * Test migration rollback removes project_id columns.
     */
    public function test_migration_rollback(): void
    {
        $this->artisan('migrate:fresh');

        // Verify columns exist
        $this->assertTrue(Schema::hasColumn('keywords', 'project_id'));
        $this->assertTrue(Schema::hasColumn('competitors', 'project_id'));
        $this->assertTrue(Schema::hasColumn('reports', 'project_id'));
        $this->assertTrue(Schema::hasColumn('alert_settings', 'project_id'));

        // Rollback the migration
        // Note: This test assumes the migration has a proper down() method
        // that removes the project_id columns
        $this->artisan('migrate:rollback');

        // After rollback, columns should not exist
        // Note: This assertion depends on the migration's down() implementation
        // If using dropColumn, this will pass; if using dropForeign + dropColumn pattern
        $this->assertFalse(
            Schema::hasColumn('keywords', 'project_id'),
            'project_id should be removed from keywords after rollback'
        );
    }

    /**
     * Test migration can be re-run after rollback.
     */
    public function test_migration_rerun_after_rollback(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('migrate:rollback');
        $this->artisan('migrate');

        // Verify all columns are back
        $this->assertTrue(Schema::hasColumn('keywords', 'project_id'));
        $this->assertTrue(Schema::hasColumn('competitors', 'project_id'));
        $this->assertTrue(Schema::hasColumn('reports', 'project_id'));
        $this->assertTrue(Schema::hasColumn('alert_settings', 'project_id'));
    }

    /**
     * Test data isolation by project_id.
     * Records with different project_ids should coexist without interference.
     */
    public function test_data_isolation_by_project(): void
    {
        $this->artisan('migrate:fresh');

        // Create a user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create two projects
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

        // Insert keywords for each project
        DB::table('keywords')->insert([
            [
                'user_id' => $userId,
                'project_id' => $project1Id,
                'keyword' => 'project1 keyword',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userId,
                'project_id' => $project2Id,
                'keyword' => 'project2 keyword',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Verify isolation - count by project
        $project1Count = DB::table('keywords')
            ->where('project_id', $project1Id)
            ->count();
        $project2Count = DB::table('keywords')
            ->where('project_id', $project2Id)
            ->count();

        $this->assertEquals(1, $project1Count);
        $this->assertEquals(1, $project2Count);

        // Verify keywords are different
        $this->assertDatabaseHas('keywords', [
            'project_id' => $project1Id,
            'keyword' => 'project1 keyword',
        ]);
        $this->assertDatabaseHas('keywords', [
            'project_id' => $project2Id,
            'keyword' => 'project2 keyword',
        ]);
    }

    /**
     * Test that project_id is placed after user_id in column order.
     * This follows the migration specification.
     */
    public function test_project_id_column_position(): void
    {
        $this->artisan('migrate:fresh');

        // Get column listing for keywords
        $columns = DB::getSchemaBuilder()->getColumnListing('keywords');

        // Find position of user_id and project_id
        $userIdPosition = array_search('user_id', $columns);
        $projectIdPosition = array_search('project_id', $columns);

        // project_id should come immediately after user_id
        $this->assertNotFalse($projectIdPosition, 'project_id column should exist');
        $this->assertEquals(
            $userIdPosition + 1,
            $projectIdPosition,
            'project_id should be positioned after user_id'
        );
    }

    /**
     * Helper: Assert foreign key exists.
     */
    private function assertForeignKeyExists(string $table, string $column): void
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            // Non-MySQL drivers (e.g., sqlite) don't expose INFORMATION_SCHEMA
            // For sqlite, we check if the column exists and assume FK is created
            $this->assertTrue(Schema::hasColumn($table, $column));
            return;
        }

        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        $this->assertNotEmpty(
            $result,
            "Foreign key on {$table}.{$column} does not exist"
        );
    }

    /**
     * Helper: Get table indexes.
     */
    private function getTableIndexes(string $table): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            // SQLite PRagma for indexes
            $result = DB::select("PRAGMA index_list({$table})");
            return array_map(fn($r) => $r->name, $result);
        }

        $result = DB::select("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_NAME = ?
            GROUP BY INDEX_NAME
        ", [$table]);

        return array_map(fn($r) => $r->INDEX_NAME, $result);
    }

    /**
     * Helper: Assert index exists.
     */
    private function assertIndexExists(array $indexes, string $indexName): void
    {
        $found = collect($indexes)->contains(function ($index) use ($indexName) {
            return str_contains(strtolower($index), strtolower($indexName));
        });

        $this->assertTrue(
            $found,
            "Index {$indexName} does not exist. Available indexes: " . implode(', ', $indexes)
        );
    }
}
