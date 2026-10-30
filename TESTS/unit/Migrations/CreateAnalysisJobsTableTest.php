<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateAnalysisJobsTableTest extends TestCase
{
    use RefreshDatabase;

    protected const JOBS_TABLE = 'analysis_jobs';
    protected const RESULTS_TABLE = 'analysis_job_results';

    // =========================================================================
    // 1. SCHEMA STRUCTURE TESTS
    // =========================================================================

    /** @test */
    public function analysis_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable(self::JOBS_TABLE));
    }

    /** @test */
    public function analysis_job_results_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable(self::RESULTS_TABLE));
    }

    /** @test */
    public function analysis_jobs_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'id'));
    }

    /** @test */
    public function analysis_jobs_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'user_id'));
    }

    /** @test */
    public function analysis_jobs_has_site_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'site_id'));
    }

    /** @test */
    public function analysis_jobs_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'status'));
    }

    /** @test */
    public function analysis_jobs_has_total_indicators_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'total_indicators'));
    }

    /** @test */
    public function analysis_jobs_has_completed_indicators_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'completed_indicators'));
    }

    /** @test */
    public function analysis_jobs_has_failed_indicators_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'failed_indicators'));
    }

    /** @test */
    public function analysis_jobs_has_progress_percent_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'progress_percent'));
    }

    /** @test */
    public function analysis_jobs_has_started_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'started_at'));
    }

    /** @test */
    public function analysis_jobs_has_completed_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'completed_at'));
    }

    /** @test */
    public function analysis_jobs_has_cancelled_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'cancelled_at'));
    }

    /** @test */
    public function analysis_jobs_has_error_message_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'error_message'));
    }

    /** @test */
    public function analysis_jobs_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'created_at'));
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'updated_at'));
    }

    /** @test */
    public function analysis_job_results_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'id'));
    }

    /** @test */
    public function analysis_job_results_has_job_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'job_id'));
    }

    /** @test */
    public function analysis_job_results_has_indicator_slug_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'indicator_slug'));
    }

    /** @test */
    public function analysis_job_results_has_indicator_name_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'indicator_name'));
    }

    /** @test */
    public function analysis_job_results_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'status'));
    }

    /** @test */
    public function analysis_job_results_has_score_columns(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'score'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'max_score'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'percentage'));
    }

    /** @test */
    public function analysis_job_results_has_json_columns(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'data'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'recommendations'));
    }

    /** @test */
    public function analysis_job_results_has_error_message_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'error_message'));
    }

    /** @test */
    public function analysis_job_results_has_time_columns(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'started_at'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'completed_at'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'processing_time_ms'));
    }

    /** @test */
    public function analysis_job_results_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'created_at'));
        $this->assertTrue(Schema::hasColumn(self::RESULTS_TABLE, 'updated_at'));
    }

    // =========================================================================
    // 2. FOREIGN KEY CONSTRAINTS
    // =========================================================================

    /** @test */
    public function analysis_jobs_user_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::JOBS_TABLE, 'user_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on user_id should exist');
    }

    /** @test */
    public function analysis_jobs_site_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::JOBS_TABLE, 'site_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on site_id should exist');
    }

    /** @test */
    public function analysis_job_results_job_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::RESULTS_TABLE, 'job_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on job_id should exist');
    }

    /** @test */
    public function deleting_user_cascades_to_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $job = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->delete();

        $this->assertDatabaseMissing(self::JOBS_TABLE, ['id' => $job]);
    }

    /** @test */
    public function deleting_site_cascades_to_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $job = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $site->delete();

        $this->assertDatabaseMissing(self::JOBS_TABLE, ['id' => $job]);
    }

    /** @test */
    public function deleting_job_cascades_to_results(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $jobId = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resultId = DB::table(self::RESULTS_TABLE)->insertGetId([
            'job_id' => $jobId,
            'indicator_slug' => 'test_indicator',
            'indicator_name' => 'Test Indicator',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(self::JOBS_TABLE)->where('id', $jobId)->delete();

        $this->assertDatabaseMissing(self::RESULTS_TABLE, ['id' => $resultId]);
    }

    // =========================================================================
    // 3. INDEXES
    // =========================================================================

    /** @test */
    public function analysis_jobs_has_index_on_user_id(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'user_id');
        $this->assertNotEmpty($indexes, 'Index on user_id should exist');
    }

    /** @test */
    public function analysis_jobs_has_index_on_site_id(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'site_id');
        $this->assertNotEmpty($indexes, 'Index on site_id should exist');
    }

    /** @test */
    public function analysis_jobs_has_index_on_status(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'status');
        $this->assertNotEmpty($indexes, 'Index on status should exist');
    }

    /** @test */
    public function analysis_jobs_has_index_on_created_at(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'created_at');
        $this->assertNotEmpty($indexes, 'Index on created_at should exist');
    }

    /** @test */
    public function analysis_job_results_has_index_on_job_id(): void
    {
        $indexes = $this->getIndexes(self::RESULTS_TABLE, 'job_id');
        $this->assertNotEmpty($indexes, 'Index on job_id should exist');
    }

    /** @test */
    public function analysis_job_results_has_index_on_status(): void
    {
        $indexes = $this->getIndexes(self::RESULTS_TABLE, 'status');
        $this->assertNotEmpty($indexes, 'Index on status should exist');
    }

    /** @test */
    public function analysis_job_results_has_unique_constraint_on_job_and_indicator(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $jobId = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $jobId,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempting to insert duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $jobId,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags Duplicate',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function same_indicator_slug_allowed_for_different_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);

        $job1Id = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job2Id = DB::table(self::JOBS_TABLE)->insertGetId([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $job1Id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $job2Id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(2, DB::table(self::RESULTS_TABLE)->where('indicator_slug', 'meta_tags')->count());
    }

    // =========================================================================
    // 4. ENUM VALIDATION
    // =========================================================================

    /** @test */
    public function analysis_jobs_status_accepts_pending(): void
    {
        $job = $this->createJob(['status' => 'pending']);
        $this->assertEquals('pending', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function analysis_jobs_status_accepts_running(): void
    {
        $job = $this->createJob(['status' => 'running']);
        $this->assertEquals('running', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function analysis_jobs_status_accepts_completed(): void
    {
        $job = $this->createJob(['status' => 'completed']);
        $this->assertEquals('completed', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function analysis_jobs_status_accepts_cancelled(): void
    {
        $job = $this->createJob(['status' => 'cancelled']);
        $this->assertEquals('cancelled', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function analysis_jobs_status_accepts_failed(): void
    {
        $job = $this->createJob(['status' => 'failed']);
        $this->assertEquals('failed', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function analysis_jobs_status_rejects_invalid_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->createJob(['status' => 'invalid_status']);
    }

    /** @test */
    public function analysis_job_results_status_accepts_pending(): void
    {
        $job = $this->createJob();
        $result = $this->createResult($job->id, ['status' => 'pending']);
        $this->assertEquals('pending', DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('status'));
    }

    /** @test */
    public function analysis_job_results_status_accepts_running(): void
    {
        $job = $this->createJob();
        $result = $this->createResult($job->id, ['status' => 'running']);
        $this->assertEquals('running', DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('status'));
    }

    /** @test */
    public function analysis_job_results_status_accepts_completed(): void
    {
        $job = $this->createJob();
        $result = $this->createResult($job->id, ['status' => 'completed']);
        $this->assertEquals('completed', DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('status'));
    }

    /** @test */
    public function analysis_job_results_status_accepts_failed(): void
    {
        $job = $this->createJob();
        $result = $this->createResult($job->id, ['status' => 'failed']);
        $this->assertEquals('failed', DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('status'));
    }

    /** @test */
    public function analysis_job_results_status_accepts_skipped(): void
    {
        $job = $this->createJob();
        $result = $this->createResult($job->id, ['status' => 'skipped']);
        $this->assertEquals('skipped', DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('status'));
    }

    /** @test */
    public function analysis_job_results_status_rejects_invalid_value(): void
    {
        $job = $this->createJob();
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->createResult($job->id, ['status' => 'invalid_status']);
    }

    // =========================================================================
    // 5. ROLLBACK TESTS
    // =========================================================================

    /** @test */
    public function migration_can_be_rolled_back(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/' . $this->getMigrationFileName(),
        ]);

        $this->assertFalse(Schema::hasTable(self::RESULTS_TABLE));
        $this->assertFalse(Schema::hasTable(self::JOBS_TABLE));
    }

    /** @test */
    public function migration_can_be_re_run_after_rollback(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/' . $this->getMigrationFileName(),
        ]);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/' . $this->getMigrationFileName(),
        ]);

        $this->assertTrue(Schema::hasTable(self::JOBS_TABLE));
        $this->assertTrue(Schema::hasTable(self::RESULTS_TABLE));
    }

    // =========================================================================
    // 6. DATA INTEGRITY TESTS
    // =========================================================================

    /** @test */
    public function data_column_stores_valid_json(): void
    {
        $job = $this->createJob();
        $testData = ['key' => 'value', 'nested' => ['item' => 1]];

        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'completed',
            'data' => json_encode($testData),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retrieved = DB::table(self::RESULTS_TABLE)
            ->where('job_id', $job->id)
            ->where('indicator_slug', 'test')
            ->value('data');

        $this->assertEquals($testData, json_decode($retrieved, true));
    }

    /** @test */
    public function data_column_stores_null(): void
    {
        $job = $this->createJob();

        $resultId = DB::table(self::RESULTS_TABLE)->insertGetId([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'pending',
            'data' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table(self::RESULTS_TABLE)->where('id', $resultId)->value('data'));
    }

    /** @test */
    public function recommendations_column_stores_valid_json(): void
    {
        $job = $this->createJob();
        $testRecommendations = [
            ['priority' => 'high', 'message' => 'Fix meta description'],
            ['priority' => 'medium', 'message' => 'Add alt tags'],
        ];

        DB::table(self::RESULTS_TABLE)->insert([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'completed',
            'recommendations' => json_encode($testRecommendations),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retrieved = DB::table(self::RESULTS_TABLE)
            ->where('job_id', $job->id)
            ->where('indicator_slug', 'test')
            ->value('recommendations');

        $this->assertEquals($testRecommendations, json_decode($retrieved, true));
    }

    /** @test */
    public function recommendations_column_stores_null(): void
    {
        $job = $this->createJob();

        $resultId = DB::table(self::RESULTS_TABLE)->insertGetId([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'pending',
            'recommendations' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table(self::RESULTS_TABLE)->where('id', $resultId)->value('recommendations'));
    }

    /** @test */
    public function score_column_preserves_decimal_precision(): void
    {
        $job = $this->createJob();

        $resultId = DB::table(self::RESULTS_TABLE)->insertGetId([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'completed',
            'score' => 85.50,
            'max_score' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = DB::table(self::RESULTS_TABLE)->where('id', $resultId)->first();

        $this->assertEquals('85.50', $result->score);
        $this->assertEquals('100.00', $result->max_score);
    }

    // =========================================================================
    // 7. EDGE CASES
    // =========================================================================

    /** @test */
    public function nullable_columns_accept_null(): void
    {
        $job = $this->createJob([
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'error_message' => null,
        ]);

        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('started_at'));
    }

    /** @test */
    public function status_defaults_to_pending(): void
    {
        $job = $this->createJob();

        $this->assertEquals('pending', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function total_indicators_defaults_to_zero(): void
    {
        $job = $this->createJob();

        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('total_indicators'));
    }

    /** @test */
    public function completed_indicators_defaults_to_zero(): void
    {
        $job = $this->createJob();

        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('completed_indicators'));
    }

    /** @test */
    public function failed_indicators_defaults_to_zero(): void
    {
        $job = $this->createJob();

        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('failed_indicators'));
    }

    /** @test */
    public function progress_percent_defaults_to_zero(): void
    {
        $job = $this->createJob();

        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('progress_percent'));
    }

    /** @test */
    public function progress_percent_accepts_boundary_values(): void
    {
        $job0 = $this->createJob(['progress_percent' => 0]);
        $job100 = $this->createJob(['progress_percent' => 100]);

        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job0->id)->value('progress_percent'));
        $this->assertEquals(100, DB::table(self::JOBS_TABLE)->where('id', $job100->id)->value('progress_percent'));
    }

    /** @test */
    public function percentage_accepts_boundary_values(): void
    {
        $job = $this->createJob();

        $result0 = $this->createResult($job->id, ['percentage' => 0]);
        $result100 = $this->createResult($job->id, ['indicator_slug' => 'test2', 'percentage' => 100]);

        $this->assertEquals(0, DB::table(self::RESULTS_TABLE)->where('id', $result0->id)->value('percentage'));
        $this->assertEquals(100, DB::table(self::RESULTS_TABLE)->where('id', $result100->id)->value('percentage'));
    }

    /** @test */
    public function percentage_accepts_null(): void
    {
        $job = $this->createJob();

        $result = $this->createResult($job->id, ['percentage' => null]);

        $this->assertNull(DB::table(self::RESULTS_TABLE)->where('id', $result->id)->value('percentage'));
    }

    // =========================================================================
    // 8. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function indicator_slug_with_sql_characters_handled_safely(): void
    {
        $job = $this->createJob();

        $result = $this->createResult($job->id, [
            'indicator_slug' => "test'; DROP TABLE users; --",
        ]);

        $retrievedSlug = DB::table(self::RESULTS_TABLE)
            ->where('id', $result->id)
            ->value('indicator_slug');

        $this->assertEquals("test'; DROP TABLE users; --", $retrievedSlug);
        $this->assertTrue(Schema::hasTable('users'), 'Users table should still exist');
    }

    /** @test */
    public function error_message_with_sql_characters_handled_safely(): void
    {
        $job = $this->createJob();

        $result = $this->createResult($job->id, [
            'error_message' => "Error: '; DELETE FROM users; --",
        ]);

        $retrievedError = DB::table(self::RESULTS_TABLE)
            ->where('id', $result->id)
            ->value('error_message');

        $this->assertStringContainsString("DELETE FROM users", $retrievedError);
        $this->assertTrue(Schema::hasTable('users'), 'Users table should still exist');
    }

    /** @test */
    public function indicator_name_with_script_tags_stored_safely(): void
    {
        $job = $this->createJob();

        $result = $this->createResult($job->id, [
            'indicator_name' => '<script>alert("xss")</script>Meta Tags',
        ]);

        $retrievedName = DB::table(self::RESULTS_TABLE)
            ->where('id', $result->id)
            ->value('indicator_name');

        $this->assertStringContainsString('<script>', $retrievedName);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createJob(array $overrides = []): object
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);

        $data = array_merge([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $jobId = DB::table(self::JOBS_TABLE)->insertGetId($data);

        return (object) ['id' => $jobId, 'user_id' => $user->id, 'site_id' => $site->id];
    }

    protected function createResult(int $jobId, array $overrides = []): object
    {
        $data = array_merge([
            'job_id' => $jobId,
            'indicator_slug' => 'test_indicator',
            'indicator_name' => 'Test Indicator',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $resultId = DB::table(self::RESULTS_TABLE)->insertGetId($data);

        return (object) ['id' => $resultId];
    }

    protected function getForeignKeys(string $table, string $column): array
    {
        $conn = DB::getConnection()->getName();
        
        if (DB::getDriverName() === 'mysql') {
            return DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $column]);
        }

        // SQLite fallback
        $sql = DB::select("PRAGMA foreign_key_list($table)");
        return array_filter($sql, fn($fk) => $fk->from === $column);
    }

    protected function getIndexes(string $table, string $column): array
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
            ", [$table, $column]);
        }

        // SQLite fallback
        $sql = DB::select("PRAGMA index_list($table)");
        return $sql;
    }

    protected function getMigrationFileName(): string
    {
        $files = glob(database_path('migrations/*analysis_jobs*'));
        return $files ? basename($files[0]) : '';
    }
}
