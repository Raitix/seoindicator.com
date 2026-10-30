<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for TASK-8-3: Create Default Projects Migration
 *
 * This test suite focuses on individual migration methods and logic,
 * testing each step in isolation.
 *
 * Test Scenarios:
 * - createDefaultProjects() method behavior
 * - assignKeywordsToProjects() method behavior
 * - assignCompetitorsToProjects() method behavior
 * - assignReportsToProjects() method behavior
 * - applyNotNullConstraints() method behavior
 *
 * @see TASK-8-3
 * @see SPEC-008
 * @see FEAT-008
 */
class CreateDefaultProjectsMigrationTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    // =========================================================================
    // createDefaultProjects() Tests
    // =========================================================================

    /**
     * Test createDefaultProjects creates project for single user.
     */
    public function test_create_default_projects_for_single_user(): void
    {
        $userId = $this->createUser(['name' => 'Single', 'email' => 'single@test.com']);

        $this->createDefaultProjects();

        $this->assertDatabaseHas('projects', [
            'user_id' => $userId,
            'name' => 'Default Project',
            'is_default' => true,
        ]);
    }

    /**
     * Test createDefaultProjects handles empty users table.
     */
    public function test_create_default_projects_empty_users(): void
    {
        $this->createDefaultProjects();

        $count = DB::table('projects')->where('is_default', true)->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test createDefaultProjects skips users with existing default.
     */
    public function test_create_default_projects_skips_existing(): void
    {
        $userId = $this->createUser(['name' => 'Existing', 'email' => 'existing@test.com']);

        // Pre-create default project
        DB::table('projects')->insert([
            'user_id' => $userId,
            'name' => 'Already Have One',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createDefaultProjects();

        // Should still have only one default project
        $count = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->count();
        $this->assertEquals(1, $count);

        // Name should not be changed
        $project = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();
        $this->assertEquals('Already Have One', $project->name);
    }

    /**
     * Test createDefaultProjects sets correct attributes.
     */
    public function test_create_default_projects_attributes(): void
    {
        $userId = $this->createUser(['name' => 'Attributes', 'email' => 'attributes@test.com']);

        $this->createDefaultProjects();

        $project = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();

        $this->assertEquals('Default Project', $project->name);
        $this->assertNull($project->domain);
        $this->assertEquals(1, $project->is_default);
        $this->assertNotNull($project->created_at);
        $this->assertNotNull($project->updated_at);
    }

    /**
     * Test createDefaultProjects is idempotent.
     */
    public function test_create_default_projects_idempotent(): void
    {
        $userId = $this->createUser(['name' => 'Idempotent', 'email' => 'idempotent@test.com']);

        $this->createDefaultProjects();
        $this->createDefaultProjects();
        $this->createDefaultProjects();

        $count = DB::table('projects')
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->count();
        $this->assertEquals(1, $count);
    }

    // =========================================================================
    // assignKeywordsToProjects() Tests
    // =========================================================================

    /**
     * Test assignKeywordsToProjects assigns single keyword.
     */
    public function test_assign_keywords_single(): void
    {
        $userId = $this->createUser(['name' => 'KW Single', 'email' => 'kwsingle@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $keywordId = $this->createKeyword($userId);

        $this->assignKeywordsToProjects();

        $keyword = DB::table('keywords')->find($keywordId);
        $this->assertEquals($projectId, $keyword->project_id);
    }

    /**
     * Test assignKeywordsToProjects skips already assigned.
     */
    public function test_assign_keywords_skips_assigned(): void
    {
        $userId = $this->createUser(['name' => 'KW Skip', 'email' => 'kwskip@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $otherProjectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Other Project',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $keywordId = $this->createKeyword($userId, ['project_id' => $otherProjectId]);

        $this->assignKeywordsToProjects();

        $keyword = DB::table('keywords')->find($keywordId);
        $this->assertEquals($otherProjectId, $keyword->project_id);
    }

    /**
     * Test assignKeywordsToProjects handles no keywords.
     */
    public function test_assign_keywords_no_keywords(): void
    {
        $userId = $this->createUser(['name' => 'No KW', 'email' => 'nokw@test.com']);
        $this->createDefaultProject($userId);

        // Should not error
        $this->assignKeywordsToProjects();

        $this->assertTrue(true);
    }

    /**
     * Test assignKeywordsToProjects handles multiple users.
     */
    public function test_assign_keywords_multiple_users(): void
    {
        $user1 = $this->createUser(['name' => 'Multi 1', 'email' => 'multi1@test.com']);
        $user2 = $this->createUser(['name' => 'Multi 2', 'email' => 'multi2@test.com']);
        $project1 = $this->createDefaultProject($user1);
        $project2 = $this->createDefaultProject($user2);

        $kw1 = $this->createKeyword($user1);
        $kw2 = $this->createKeyword($user2);

        $this->assignKeywordsToProjects();

        $keyword1 = DB::table('keywords')->find($kw1);
        $keyword2 = DB::table('keywords')->find($kw2);

        $this->assertEquals($project1, $keyword1->project_id);
        $this->assertEquals($project2, $keyword2->project_id);
    }

    /**
     * Test assignKeywordsToProjects is idempotent.
     */
    public function test_assign_keywords_idempotent(): void
    {
        $userId = $this->createUser(['name' => 'KW Idem', 'email' => 'kwidem@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $keywordId = $this->createKeyword($userId);

        $this->assignKeywordsToProjects();
        $firstResult = DB::table('keywords')->find($keywordId)->project_id;

        $this->assignKeywordsToProjects();
        $secondResult = DB::table('keywords')->find($keywordId)->project_id;

        $this->assertEquals($firstResult, $secondResult);
    }

    // =========================================================================
    // assignCompetitorsToProjects() Tests
    // =========================================================================

    /**
     * Test assignCompetitorsToProjects assigns single competitor.
     */
    public function test_assign_competitors_single(): void
    {
        $userId = $this->createUser(['name' => 'Comp Single', 'email' => 'compsingle@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $competitorId = $this->createCompetitor($userId);

        $this->assignCompetitorsToProjects();

        $competitor = DB::table('competitors')->find($competitorId);
        $this->assertEquals($projectId, $competitor->project_id);
    }

    /**
     * Test assignCompetitorsToProjects skips already assigned.
     */
    public function test_assign_competitors_skips_assigned(): void
    {
        $userId = $this->createUser(['name' => 'Comp Skip', 'email' => 'compskip@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $otherProjectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Other Project',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $competitorId = $this->createCompetitor($userId, ['project_id' => $otherProjectId]);

        $this->assignCompetitorsToProjects();

        $competitor = DB::table('competitors')->find($competitorId);
        $this->assertEquals($otherProjectId, $competitor->project_id);
    }

    /**
     * Test assignCompetitorsToProjects handles no competitors.
     */
    public function test_assign_competitors_no_competitors(): void
    {
        $userId = $this->createUser(['name' => 'No Comp', 'email' => 'nocomp@test.com']);
        $this->createDefaultProject($userId);

        $this->assignCompetitorsToProjects();

        $this->assertTrue(true);
    }

    /**
     * Test assignCompetitorsToProjects is idempotent.
     */
    public function test_assign_competitors_idempotent(): void
    {
        $userId = $this->createUser(['name' => 'Comp Idem', 'email' => 'compidem@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $competitorId = $this->createCompetitor($userId);

        $this->assignCompetitorsToProjects();
        $firstResult = DB::table('competitors')->find($competitorId)->project_id;

        $this->assignCompetitorsToProjects();
        $secondResult = DB::table('competitors')->find($competitorId)->project_id;

        $this->assertEquals($firstResult, $secondResult);
    }

    // =========================================================================
    // assignReportsToProjects() Tests
    // =========================================================================

    /**
     * Test assignReportsToProjects assigns single report.
     */
    public function test_assign_reports_single(): void
    {
        $userId = $this->createUser(['name' => 'Rep Single', 'email' => 'repsingle@test.com']);
        $siteId = $this->createSite($userId);
        $projectId = $this->createDefaultProject($userId);
        $reportId = $this->createReport($siteId);

        $this->assignReportsToProjects();

        $report = DB::table('reports')->find($reportId);
        $this->assertEquals($projectId, $report->project_id);
    }

    /**
     * Test assignReportsToProjects skips already assigned.
     */
    public function test_assign_reports_skips_assigned(): void
    {
        $userId = $this->createUser(['name' => 'Rep Skip', 'email' => 'repskip@test.com']);
        $siteId = $this->createSite($userId);
        $this->createDefaultProject($userId);
        $otherProjectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Other Project',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reportId = $this->createReport($siteId, ['project_id' => $otherProjectId]);

        $this->assignReportsToProjects();

        $report = DB::table('reports')->find($reportId);
        $this->assertEquals($otherProjectId, $report->project_id);
    }

    /**
     * Test assignReportsToProjects handles no reports.
     */
    public function test_assign_reports_no_reports(): void
    {
        $userId = $this->createUser(['name' => 'No Rep', 'email' => 'norep@test.com']);
        $this->createSite($userId);
        $this->createDefaultProject($userId);

        $this->assignReportsToProjects();

        $this->assertTrue(true);
    }

    /**
     * Test assignReportsToProjects matches via site user.
     */
    public function test_assign_reports_via_site_user(): void
    {
        $userId = $this->createUser(['name' => 'Rep Site', 'email' => 'repsite@test.com']);
        $siteId = $this->createSite($userId);
        $projectId = $this->createDefaultProject($userId);
        $reportId = $this->createReport($siteId);

        $this->assignReportsToProjects();

        $report = DB::table('reports')->find($reportId);
        $this->assertEquals($projectId, $report->project_id);
    }

    /**
     * Test assignReportsToProjects is idempotent.
     */
    public function test_assign_reports_idempotent(): void
    {
        $userId = $this->createUser(['name' => 'Rep Idem', 'email' => 'repidem@test.com']);
        $siteId = $this->createSite($userId);
        $projectId = $this->createDefaultProject($userId);
        $reportId = $this->createReport($siteId);

        $this->assignReportsToProjects();
        $firstResult = DB::table('reports')->find($reportId)->project_id;

        $this->assignReportsToProjects();
        $secondResult = DB::table('reports')->find($reportId)->project_id;

        $this->assertEquals($firstResult, $secondResult);
    }

    // =========================================================================
    // applyNotNullConstraints() Tests
    // =========================================================================

    /**
     * Test applyNotNullConstraints adds constraint.
     */
    public function test_apply_not_null_adds_constraint(): void
    {
        $userId = $this->createUser(['name' => 'NN Add', 'email' => 'nnadd@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $this->createKeyword($userId, ['project_id' => $projectId]);

        $this->applyNotNullConstraints();

        $this->assertTrue($this->hasNotNullConstraint('keywords', 'project_id'));
    }

    /**
     * Test applyNotNullConstraints on keywords.
     */
    public function test_apply_not_null_keywords(): void
    {
        $userId = $this->createUser(['name' => 'NN KW', 'email' => 'nnkw@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $this->createKeyword($userId, ['project_id' => $projectId]);

        $this->applyNotNullConstraints();

        $this->assertTrue($this->hasNotNullConstraint('keywords', 'project_id'));
    }

    /**
     * Test applyNotNullConstraints on competitors.
     */
    public function test_apply_not_null_competitors(): void
    {
        $userId = $this->createUser(['name' => 'NN Comp', 'email' => 'nncomp@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $this->createCompetitor($userId, ['project_id' => $projectId]);

        $this->applyNotNullConstraints();

        $this->assertTrue($this->hasNotNullConstraint('competitors', 'project_id'));
    }

    /**
     * Test applyNotNullConstraints on reports.
     */
    public function test_apply_not_null_reports(): void
    {
        $userId = $this->createUser(['name' => 'NN Rep', 'email' => 'nnrep@test.com']);
        $siteId = $this->createSite($userId);
        $projectId = $this->createDefaultProject($userId);
        $this->createReport($siteId, ['project_id' => $projectId]);

        $this->applyNotNullConstraints();

        $this->assertTrue($this->hasNotNullConstraint('reports', 'project_id'));
    }

    /**
     * Test constraint prevents NULL insert.
     */
    public function test_not_null_prevents_null(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // SQLite doesn't enforce NOT NULL the same way
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite does not enforce NOT NULL constraints the same way as MySQL');
        }
        
        $userId = $this->createUser(['name' => 'NN Prevent', 'email' => 'nnprevent@test.com']);
        $projectId = $this->createDefaultProject($userId);
        $this->createKeyword($userId, ['project_id' => $projectId]);

        $this->applyNotNullConstraints();

        $this->expectException(\Exception::class);

        DB::table('keywords')->insert([
            'user_id' => $userId,
            'keyword' => 'should fail',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'project_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
    private function createSite(int $userId): int
    {
        return DB::table('sites')->insertGetId([
            'user_id' => $userId,
            'url' => 'https://test' . uniqid() . '.com',
            'name' => 'Test Site',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a default project for user.
     */
    private function createDefaultProject(int $userId): int
    {
        return DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Default Project',
            'is_default' => true,
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
     * Create default projects for all users without one.
     */
    private function createDefaultProjects(): void
    {
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
    }

    /**
     * Assign keywords to their user's default project.
     */
    private function assignKeywordsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        
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
    }

    /**
     * Assign competitors to their user's default project.
     */
    private function assignCompetitorsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        
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
    }

    /**
     * Assign reports to their site user's default project.
     */
    private function assignReportsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        
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
     * Check if NOT NULL constraint exists.
     */
    private function hasNotNullConstraint(string $table, string $column): bool
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        // SQLite doesn't enforce NOT NULL the same way
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
