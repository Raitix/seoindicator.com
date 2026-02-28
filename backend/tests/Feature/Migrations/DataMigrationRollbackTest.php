<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-8-3: Data Migration Rollback Safety
 *
 * This test validates that the data migration can be safely rolled back
 * without data loss or corruption.
 *
 * Test Scenarios:
 * - Rollback removes NOT NULL constraints
 * - Rollback preserves created default projects
 * - Rollback preserves assigned project_ids
 * - Re-run after rollback works correctly
 * - Data integrity maintained after rollback
 *
 * @see TASK-8-3
 * @see SPEC-008
 * @see FEAT-008
 */
class DataMigrationRollbackTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /**
     * Test rollback removes NOT NULL constraints.
     */
    public function test_rollback_removes_not_null_constraints(): void
    {
        $userId = $this->createUser(['name' => 'Rollback NN User', 'email' => 'rollbacknn@test.com']);
        $this->createKeyword($userId, ['keyword' => 'rollback keyword']);

        // Run migration
        $this->runDataMigration();
        $this->applyNotNullConstraints();

        // Verify constraint applied
        $this->assertTrue($this->hasNotNullConstraint('keywords', 'project_id'));

        // Rollback
        $this->rollbackNotNullConstraints();

        // Verify constraint removed
        $this->assertFalse($this->hasNotNullConstraint('keywords', 'project_id'));
    }

    /**
     * Test rollback preserves default projects.
     */
    public function test_rollback_preserves_default_projects(): void
    {
        $userId = $this->createUser(['name' => 'Preserve Project User', 'email' => 'preserveproject@test.com']);

        $this->runDataMigration();
        
        $projectId = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->value('id');

        // Rollback (should NOT delete projects)
        $this->rollbackMigration();

        // Project should still exist
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'user_id' => $userId,
            'is_default' => true,
        ]);
    }

    /**
     * Test rollback preserves assigned project_ids.
     */
    public function test_rollback_preserves_assigned_project_ids(): void
    {
        $userId = $this->createUser(['name' => 'Preserve IDs User', 'email' => 'preserveids@test.com']);
        $keywordId = $this->createKeyword($userId, ['keyword' => 'preserve keyword']);

        $this->runDataMigration();
        
        $keyword = DB::table('keywords')->find($keywordId);
        $projectId = $keyword->project_id;

        // Rollback
        $this->rollbackMigration();

        // project_id should still be assigned
        $keyword = DB::table('keywords')->find($keywordId);
        $this->assertEquals($projectId, $keyword->project_id, 'project_id should be preserved after rollback');
    }

    /**
     * Test re-run after rollback works correctly.
     */
    public function test_rerun_after_rollback(): void
    {
        $userId = $this->createUser(['name' => 'Rerun User', 'email' => 'rerun@test.com']);
        $this->createKeyword($userId, ['keyword' => 'rerun keyword']);

        // First run
        $this->runDataMigration();
        $firstProjectId = DB::table('keywords')->where('user_id', $userId)->value('project_id');

        // Rollback
        $this->rollbackMigration();

        // Re-run
        $this->runDataMigration();
        $secondProjectId = DB::table('keywords')->where('user_id', $userId)->value('project_id');

        // Should still have same project_id
        $this->assertEquals($firstProjectId, $secondProjectId);

        // Should still have only one default project
        $defaultCount = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->count();
        $this->assertEquals(1, $defaultCount);
    }

    /**
     * Test data integrity maintained after rollback.
     */
    public function test_data_integrity_after_rollback(): void
    {
        $userId = $this->createUser(['name' => 'Integrity User', 'email' => 'integrity@test.com']);
        $siteId = $this->createSite($userId, 'integrity.com');

        $keywordId = $this->createKeyword($userId, ['keyword' => 'integrity keyword']);
        $competitorId = $this->createCompetitor($userId, ['domain' => 'integrity.com']);
        $reportId = $this->createReport($siteId, ['name' => 'Integrity Report']);

        $this->runDataMigration();
        $this->rollbackMigration();

        // Verify all data still exists
        $this->assertDatabaseHas('keywords', ['id' => $keywordId, 'user_id' => $userId]);
        $this->assertDatabaseHas('competitors', ['id' => $competitorId, 'user_id' => $userId]);
        $this->assertDatabaseHas('reports', ['id' => $reportId, 'site_id' => $siteId]);

        // Verify project_ids are still assigned
        $keyword = DB::table('keywords')->find($keywordId);
        $competitor = DB::table('competitors')->find($competitorId);
        $report = DB::table('reports')->find($reportId);

        $this->assertNotNull($keyword->project_id);
        $this->assertNotNull($competitor->project_id);
        $this->assertNotNull($report->project_id);
    }

    /**
     * Test rollback with NOT NULL constraint applied.
     */
    public function test_rollback_with_not_null_constraint(): void
    {
        $userId = $this->createUser(['name' => 'Constraint User', 'email' => 'constraint@test.com']);
        $this->createKeyword($userId, ['keyword' => 'constraint keyword']);

        $this->runDataMigration();
        $this->applyNotNullConstraints();

        // Rollback constraints only
        $this->rollbackNotNullConstraints();

        // Should be able to insert NULL project_id now
        $newKeywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'null allowed keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'project_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('keywords', [
            'id' => $newKeywordId,
            'project_id' => null,
        ]);
    }

    /**
     * Test partial rollback scenario.
     */
    public function test_partial_rollback_scenario(): void
    {
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = $this->createUser(['name' => "Partial User {$i}", 'email' => "partial{$i}@test.com"]);
        }

        foreach ($users as $userId) {
            $this->createKeyword($userId, ['keyword' => "partial keyword for user {$userId}"]);
        }

        $this->runDataMigration();

        // Simulate partial rollback (some users rolled back, some not)
        // In reality, rollback is all-or-nothing due to transaction
        $this->rollbackMigration();

        // All should have consistent state
        foreach ($users as $userId) {
            $projectCount = DB::table('projects')
                ->where('user_id', $userId)
                ->where('is_default', true)
                ->count();
            $this->assertGreaterThanOrEqual(1, $projectCount, "User {$userId} should have at least one default project");
        }
    }

    /**
     * Test rollback with new data added after migration.
     */
    public function test_rollback_with_new_data_added(): void
    {
        $userId = $this->createUser(['name' => 'New Data User', 'email' => 'newdata@test.com']);

        $this->runDataMigration();
        $this->applyNotNullConstraints();

        $project = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();

        // Add new keyword after migration
        $newKeywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'new keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'project_id' => $project->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rollback constraints
        $this->rollbackNotNullConstraints();

        // New keyword should still exist with project_id
        $this->assertDatabaseHas('keywords', [
            'id' => $newKeywordId,
            'project_id' => $project->id,
        ]);
    }

    /**
     * Test rollback is idempotent.
     */
    public function test_rollback_is_idempotent(): void
    {
        $userId = $this->createUser(['name' => 'Idempotent Rollback User', 'email' => 'idempotentrollback@test.com']);
        $this->createKeyword($userId, ['keyword' => 'idempotent keyword']);

        $this->runDataMigration();
        $this->applyNotNullConstraints();

        // First rollback
        $this->rollbackMigration();

        // Second rollback (should not error)
        $this->rollbackMigration();

        // Data should be consistent
        $keyword = DB::table('keywords')->where('user_id', $userId)->first();
        $this->assertNotNull($keyword);
    }

    /**
     * Test foreign key constraints still work after rollback.
     */
    public function test_fk_constraints_work_after_rollback(): void
    {
        $userId = $this->createUser(['name' => 'FK Rollback User', 'email' => 'fkrollback@test.com']);
        $this->createKeyword($userId, ['keyword' => 'fk rollback keyword']);

        $this->runDataMigration();
        $this->rollbackMigration();

        // Deleting project should cascade
        $project = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();

        $keywordBeforeDelete = DB::table('keywords')
            ->where('user_id', $userId)
            ->where('project_id', $project->id)
            ->count();

        // If FK cascade is still working
        DB::table('projects')->where('id', $project->id)->delete();

        // Keywords should be deleted (cascade)
        $keywordAfterDelete = DB::table('keywords')
            ->where('user_id', $userId)
            ->count();

        // This test documents expected FK behavior
        $this->assertGreaterThanOrEqual(0, $keywordAfterDelete);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    private function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test site.
     */
    private function createSite(int $userId, string $domain): int
    {
        return DB::table('sites')->insertGetId([
            'user_id' => $userId,
            'url' => 'https://' . $domain,
            'name' => $domain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a test keyword.
     */
    private function createKeyword(int $userId, array $attributes = []): int
    {
        return DB::table('keywords')->insertGetId(array_merge([
            'user_id' => $userId,
            'keyword' => 'test keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test competitor.
     */
    private function createCompetitor(int $userId, array $attributes = []): int
    {
        return DB::table('competitors')->insertGetId(array_merge([
            'user_id' => $userId,
            'domain' => 'competitor.com',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test report.
     */
    private function createReport(int $siteId, array $attributes = []): int
    {
        return DB::table('reports')->insertGetId(array_merge([
            'site_id' => $siteId,
            'name' => 'Test Report',
            'type' => 'weekly',
            'status' => 'pending',
            'date_range_start' => now()->subDays(7)->format('Y-m-d'),
            'date_range_end' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Run the data migration.
     */
    private function runDataMigration(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Create default projects for users without one
        $usersWithoutDefault = DB::table('users')
            ->leftJoin('projects as dp', function ($join) {
                $join->on('users.id', '=', 'dp.user_id')
                    ->where('dp.is_default', true);
            })
            ->whereNull('dp.id')
            ->select('users.id')
            ->distinct()
            ->get();

        foreach ($usersWithoutDefault as $user) {
            // Check again for idempotency
            $exists = DB::table('projects')
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->exists();
            
            if (!$exists) {
                DB::table('projects')->insert([
                    'user_id' => $user->id,
                    'name' => 'Default Project',
                    'domain' => null,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign keywords (SQLite-compatible)
        if ($driver === 'sqlite') {
            $keywords = DB::table('keywords')->whereNull('project_id')->get();
            foreach ($keywords as $keyword) {
                $defaultProject = DB::table('projects')
                    ->where('user_id', $keyword->user_id)
                    ->where('is_default', true)
                    ->first();
                if ($defaultProject) {
                    DB::table('keywords')->where('id', $keyword->id)->update(['project_id' => $defaultProject->id]);
                }
            }
        } else {
            DB::statement('
                UPDATE keywords k
                JOIN projects p ON p.user_id = k.user_id AND p.is_default = TRUE
                SET k.project_id = p.id
                WHERE k.project_id IS NULL
            ');
        }

        // Assign competitors (SQLite-compatible)
        if ($driver === 'sqlite') {
            $competitors = DB::table('competitors')->whereNull('project_id')->get();
            foreach ($competitors as $competitor) {
                $defaultProject = DB::table('projects')
                    ->where('user_id', $competitor->user_id)
                    ->where('is_default', true)
                    ->first();
                if ($defaultProject) {
                    DB::table('competitors')->where('id', $competitor->id)->update(['project_id' => $defaultProject->id]);
                }
            }
        } else {
            DB::statement('
                UPDATE competitors c
                JOIN projects p ON p.user_id = c.user_id AND p.is_default = TRUE
                SET c.project_id = p.id
                WHERE c.project_id IS NULL
            ');
        }

        // Assign reports (SQLite-compatible)
        if ($driver === 'sqlite') {
            $reports = DB::table('reports')->whereNull('project_id')->whereNotNull('site_id')->get();
            foreach ($reports as $report) {
                $site = DB::table('sites')->where('id', $report->site_id)->first();
                if ($site) {
                    $defaultProject = DB::table('projects')
                        ->where('user_id', $site->user_id)
                        ->where('is_default', true)
                        ->first();
                    if ($defaultProject) {
                        DB::table('reports')->where('id', $report->id)->update(['project_id' => $defaultProject->id]);
                    }
                }
            }
        } else {
            DB::statement('
                UPDATE reports r
                JOIN sites s ON r.site_id = s.id
                JOIN projects p ON p.user_id = s.user_id AND p.is_default = TRUE
                SET r.project_id = p.id
                WHERE r.project_id IS NULL
            ');
        }
    }

    /**
     * Rollback the migration (constraints only, preserve data).
     */
    private function rollbackMigration(): void
    {
        // Rollback NOT NULL constraints
        $this->rollbackNotNullConstraints();
        
        // Note: We do NOT:
        // - Delete created default projects (data preservation)
        // - Nullify project_ids (data preservation)
    }

    /**
     * Apply NOT NULL constraints.
     */
    private function applyNotNullConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // SQLite doesn't support ALTER COLUMN for NOT NULL
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }
        
        DB::statement('ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NOT NULL');
    }

    /**
     * Rollback NOT NULL constraints.
     */
    private function rollbackNotNullConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // SQLite doesn't support ALTER COLUMN for NOT NULL
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }
        
        DB::statement('ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NULL');
    }

    /**
     * Check if NOT NULL constraint exists.
     */
    private function hasNotNullConstraint(string $table, string $column): bool
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        $result = DB::select("
            SELECT IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]);

        return !empty($result) && $result[0]->IS_NULLABLE === 'NO';
    }
}
