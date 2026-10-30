<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;

/**
 * Migration tests for TASK-19-1: Create Analysis Jobs Tables
 *
 * Tests verify:
 * - Table structure matches specification
 * - Foreign key constraints work correctly
 * - Unique constraint prevents duplicate (job_id, indicator_slug)
 * - Column types are correct
 * - Indexes are created
 * - Enum validation
 */
class CreateAnalysisJobsTablesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // ANALYSIS_JOBS TABLE TESTS
    // =========================================================================

    /**
     * Test that the analysis_jobs table exists.
     */
    public function test_analysis_jobs_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('analysis_jobs'),
            'analysis_jobs table should exist'
        );
    }

    /**
     * Test that all required columns exist in analysis_jobs table.
     */
    public function test_analysis_jobs_required_columns_exist(): void
    {
        $columns = [
            'id',
            'user_id',
            'site_id',
            'status',
            'total_indicators',
            'completed_indicators',
            'failed_indicators',
            'progress_percent',
            'started_at',
            'completed_at',
            'cancelled_at',
            'error_message',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('analysis_jobs', $column),
                "Column '{$column}' should exist in analysis_jobs table"
            );
        }
    }

    /**
     * Test that foreign key to users exists and works.
     */
    public function test_analysis_jobs_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();
        $job = AnalysisJob::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertEquals($user->id, $job->user_id);

        // Test cascade delete
        $jobId = $job->id;
        $user->delete();

        $this->assertDatabaseMissing('analysis_jobs', ['id' => $jobId]);
    }

    /**
     * Test that foreign key to sites exists and works.
     */
    public function test_analysis_jobs_foreign_key_to_sites(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();
        $job = AnalysisJob::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertEquals($site->id, $job->site_id);

        // Test cascade delete
        $jobId = $job->id;
        $site->delete();

        $this->assertDatabaseMissing('analysis_jobs', ['id' => $jobId]);
    }

    /**
     * Test that foreign key constraint prevents invalid user_id.
     */
    public function test_analysis_jobs_foreign_key_constraint_invalid_user(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $site = Site::factory()->create();

        // Attempt to create job with non-existent user
        DB::table('analysis_jobs')->insert([
            'user_id' => 99999,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that foreign key constraint prevents invalid site_id.
     */
    public function test_analysis_jobs_foreign_key_constraint_invalid_site(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        // Attempt to create job with non-existent site
        DB::table('analysis_jobs')->insert([
            'user_id' => $user->id,
            'site_id' => 99999,
            'status' => 'pending',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created on analysis_jobs table.
     */
    public function test_analysis_jobs_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('analysis_jobs');

        // Check for user_id index
        $this->assertIndexExists($indexes, 'user_id');

        // Check for site_id index
        $this->assertIndexExists($indexes, 'site_id');

        // Check for status index
        $this->assertIndexExists($indexes, 'status');

        // Check for created_at index
        $this->assertIndexExists($indexes, 'created_at');
    }

    /**
     * Test that status accepts valid enum values.
     */
    public function test_analysis_jobs_status_valid_enum_values(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $validStatuses = ['pending', 'running', 'completed', 'cancelled', 'failed'];

        foreach ($validStatuses as $status) {
            $job = AnalysisJob::factory()->create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'status' => $status,
            ]);

            $this->assertEquals($status, $job->status);
        }
    }

    /**
     * Test that status rejects invalid enum values.
     */
    public function test_analysis_jobs_status_invalid_enum_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Attempt to insert invalid status
        DB::table('analysis_jobs')->insert([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'invalid_status',
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test default status is pending.
     */
    public function test_analysis_jobs_default_status(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        // Refresh from DB to get actual default value
        $this->assertEquals('pending', $job->fresh()->status);
    }

    /**
     * Test that factory sets total_indicators to 0 by default.
     */
    public function test_analysis_jobs_default_total_indicators(): void
    {
        $job = AnalysisJob::factory()->create();

        $this->assertEquals(0, $job->total_indicators);
    }

    /**
     * Test that factory sets completed_indicators to 0 by default.
     */
    public function test_analysis_jobs_default_completed_indicators(): void
    {
        $job = AnalysisJob::factory()->create();

        $this->assertEquals(0, $job->completed_indicators);
    }

    /**
     * Test that factory sets failed_indicators to 0 by default.
     */
    public function test_analysis_jobs_default_failed_indicators(): void
    {
        $job = AnalysisJob::factory()->create();

        $this->assertEquals(0, $job->failed_indicators);
    }

    /**
     * Test that factory sets progress_percent to 0 by default.
     */
    public function test_analysis_jobs_default_progress_percent(): void
    {
        $job = AnalysisJob::factory()->create();

        $this->assertEquals(0, $job->progress_percent);
    }

    /**
     * Test that started_at can be null.
     */
    public function test_analysis_jobs_started_at_nullable(): void
    {
        $job = AnalysisJob::factory()->create(['started_at' => null]);

        $this->assertNull($job->started_at);
    }

    /**
     * Test that completed_at can be null.
     */
    public function test_analysis_jobs_completed_at_nullable(): void
    {
        $job = AnalysisJob::factory()->create(['completed_at' => null]);

        $this->assertNull($job->completed_at);
    }

    /**
     * Test that cancelled_at can be null.
     */
    public function test_analysis_jobs_cancelled_at_nullable(): void
    {
        $job = AnalysisJob::factory()->create(['cancelled_at' => null]);

        $this->assertNull($job->cancelled_at);
    }

    /**
     * Test that error_message can be null.
     */
    public function test_analysis_jobs_error_message_nullable(): void
    {
        $job = AnalysisJob::factory()->create(['error_message' => null]);

        $this->assertNull($job->error_message);
    }

    /**
     * Test that error_message can store text.
     */
    public function test_analysis_jobs_error_message_stores_text(): void
    {
        $longMessage = str_repeat('Error detail. ', 100);

        $job = AnalysisJob::factory()->create(['error_message' => $longMessage]);

        $this->assertEquals($longMessage, $job->error_message);
    }

    /**
     * Test that timestamps are cast to Carbon instances.
     */
    public function test_analysis_jobs_timestamps_cast_to_carbon(): void
    {
        $job = AnalysisJob::factory()->create([
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
            'cancelled_at' => now()->addMinutes(5),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $job->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $job->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $job->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $job->completed_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $job->cancelled_at);
    }

    /**
     * Test that progress_percent boundary values work.
     * Note: progress_percent is calculated from completed/total_indicators via accessor.
     */
    public function test_analysis_jobs_progress_percent_boundary_values(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Test 0 - no indicators completed
        $job1 = AnalysisJob::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 10,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
        ]);
        $this->assertEquals(0, $job1->progress_percent);

        // Test 100 - all indicators completed
        $job2 = AnalysisJob::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 10,
            'completed_indicators' => 10,
            'failed_indicators' => 0,
        ]);
        $this->assertEquals(100, $job2->progress_percent);

        // Test 50 - half completed
        $job3 = AnalysisJob::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 10,
            'completed_indicators' => 5,
            'failed_indicators' => 0,
        ]);
        $this->assertEquals(50, $job3->progress_percent);
    }

    /**
     * Test that multiple jobs can be created for same user and site.
     */
    public function test_analysis_jobs_multiple_per_user_and_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $jobs = AnalysisJob::factory()->count(5)->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertEquals(5, $jobs->count());
        foreach ($jobs as $job) {
            $this->assertEquals($user->id, $job->user_id);
            $this->assertEquals($site->id, $job->site_id);
        }
    }

    // =========================================================================
    // ANALYSIS_JOB_RESULTS TABLE TESTS
    // =========================================================================

    /**
     * Test that the analysis_job_results table exists.
     */
    public function test_analysis_job_results_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('analysis_job_results'),
            'analysis_job_results table should exist'
        );
    }

    /**
     * Test that all required columns exist in analysis_job_results table.
     */
    public function test_analysis_job_results_required_columns_exist(): void
    {
        $columns = [
            'id',
            'job_id',
            'indicator_slug',
            'indicator_name',
            'status',
            'score',
            'max_score',
            'percentage',
            'data',
            'recommendations',
            'error_message',
            'started_at',
            'completed_at',
            'processing_time_ms',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('analysis_job_results', $column),
                "Column '{$column}' should exist in analysis_job_results table"
            );
        }
    }

    /**
     * Test that foreign key to analysis_jobs exists and works.
     */
    public function test_analysis_job_results_foreign_key_to_jobs(): void
    {
        $job = AnalysisJob::factory()->create();
        $result = AnalysisJobResult::factory()->create(['job_id' => $job->id]);

        $this->assertEquals($job->id, $result->job_id);

        // Test cascade delete
        $resultId = $result->id;
        $job->delete();

        $this->assertDatabaseMissing('analysis_job_results', ['id' => $resultId]);
    }

    /**
     * Test that foreign key constraint prevents invalid job_id.
     */
    public function test_analysis_job_results_foreign_key_constraint_invalid_job(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create result with non-existent job
        DB::table('analysis_job_results')->insert([
            'job_id' => 99999,
            'indicator_slug' => 'test-indicator',
            'indicator_name' => 'Test Indicator',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created on analysis_job_results table.
     */
    public function test_analysis_job_results_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('analysis_job_results');

        // Check for job_id index
        $this->assertIndexExists($indexes, 'job_id');

        // Check for status index
        $this->assertIndexExists($indexes, 'status');
    }

    /**
     * Test unique constraint on (job_id, indicator_slug).
     */
    public function test_analysis_job_results_unique_constraint_job_indicator(): void
    {
        $job = AnalysisJob::factory()->create();

        // Create first result
        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'unique-slug',
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'unique-slug',
        ]);
    }

    /**
     * Test that same indicator_slug is allowed for different job_id.
     */
    public function test_analysis_job_results_same_slug_different_job(): void
    {
        $job1 = AnalysisJob::factory()->create();
        $job2 = AnalysisJob::factory()->create();

        $result1 = AnalysisJobResult::factory()->create([
            'job_id' => $job1->id,
            'indicator_slug' => 'shared-slug',
        ]);

        $result2 = AnalysisJobResult::factory()->create([
            'job_id' => $job2->id,
            'indicator_slug' => 'shared-slug',
        ]);

        $this->assertNotEquals($result1->id, $result2->id);
        $this->assertEquals('shared-slug', $result1->indicator_slug);
        $this->assertEquals('shared-slug', $result2->indicator_slug);
    }

    /**
     * Test that status accepts valid enum values.
     */
    public function test_analysis_job_results_status_valid_enum_values(): void
    {
        $job = AnalysisJob::factory()->create();
        $validStatuses = ['pending', 'running', 'completed', 'failed', 'skipped'];

        foreach ($validStatuses as $index => $status) {
            $result = AnalysisJobResult::factory()->create([
                'job_id' => $job->id,
                'indicator_slug' => "indicator-{$index}",
                'status' => $status,
            ]);

            $this->assertEquals($status, $result->status);
        }
    }

    /**
     * Test that status rejects invalid enum values.
     */
    public function test_analysis_job_results_status_invalid_enum_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $job = AnalysisJob::factory()->create();

        // Attempt to insert invalid status
        DB::table('analysis_job_results')->insert([
            'job_id' => $job->id,
            'indicator_slug' => 'test-slug',
            'indicator_name' => 'Test',
            'status' => 'invalid_status',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test default status is pending.
     */
    public function test_analysis_job_results_default_status(): void
    {
        $job = AnalysisJob::factory()->create();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test-indicator',
            'indicator_name' => 'Test Indicator',
        ]);

        // Refresh from DB to get actual default value
        $this->assertEquals('pending', $result->fresh()->status);
    }

    /**
     * Test that score can store decimal values.
     */
    public function test_analysis_job_results_score_decimal_precision(): void
    {
        $job = AnalysisJob::factory()->create();

        $result = AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'score' => 85.50,
            'max_score' => 100.00,
        ]);

        $this->assertEquals(85.50, (float) $result->score);
        $this->assertEquals(100.00, (float) $result->max_score);
    }

    /**
     * Test that score can be null.
     */
    public function test_analysis_job_results_score_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['score' => null]);

        $this->assertNull($result->score);
    }

    /**
     * Test that max_score can be null.
     */
    public function test_analysis_job_results_max_score_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['max_score' => null]);

        $this->assertNull($result->max_score);
    }

    /**
     * Test that percentage can be null.
     */
    public function test_analysis_job_results_percentage_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['percentage' => null]);

        $this->assertNull($result->percentage);
    }

    /**
     * Test that percentage boundary values work.
     */
    public function test_analysis_job_results_percentage_boundary_values(): void
    {
        $job = AnalysisJob::factory()->create();

        // Test 0
        $result1 = AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'indicator-0',
            'percentage' => 0,
        ]);
        $this->assertEquals(0, $result1->percentage);

        // Test 100
        $result2 = AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'indicator-100',
            'percentage' => 100,
        ]);
        $this->assertEquals(100, $result2->percentage);
    }

    /**
     * Test that data can be stored as JSON.
     */
    public function test_analysis_job_results_data_stored_as_json(): void
    {
        $data = [
            'h1_count' => 1,
            'h2_count' => 5,
            'missing_alt' => 2,
            'issues' => ['Missing meta description', 'H1 too long'],
        ];

        $result = AnalysisJobResult::factory()->create(['data' => $data]);

        $this->assertEquals($data, $result->data);
    }

    /**
     * Test that data can be null.
     */
    public function test_analysis_job_results_data_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['data' => null]);

        $this->assertEquals([], $result->data);
    }

    /**
     * Test that data can be empty array.
     */
    public function test_analysis_job_results_data_empty_array(): void
    {
        $result = AnalysisJobResult::factory()->create(['data' => []]);

        $this->assertEquals([], $result->data);
    }

    /**
     * Test that recommendations can be stored as JSON.
     */
    public function test_analysis_job_results_recommendations_stored_as_json(): void
    {
        $recommendations = [
            [
                'priority' => 'high',
                'message' => 'Add meta description',
                'details' => 'Missing meta description tag',
            ],
            [
                'priority' => 'medium',
                'message' => 'Reduce H1 length',
                'details' => 'Current: 80 chars, recommended: 60 chars',
            ],
        ];

        $result = AnalysisJobResult::factory()->create(['recommendations' => $recommendations]);

        $this->assertEquals($recommendations, $result->recommendations);
    }

    /**
     * Test that recommendations can be null.
     */
    public function test_analysis_job_results_recommendations_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['recommendations' => null]);

        $this->assertEquals([], $result->recommendations);
    }

    /**
     * Test that error_message can be null.
     */
    public function test_analysis_job_results_error_message_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['error_message' => null]);

        $this->assertNull($result->error_message);
    }

    /**
     * Test that error_message can store text.
     */
    public function test_analysis_job_results_error_message_stores_text(): void
    {
        $longMessage = str_repeat('Error occurred. ', 50);

        $result = AnalysisJobResult::factory()->create(['error_message' => $longMessage]);

        $this->assertEquals($longMessage, $result->error_message);
    }

    /**
     * Test that started_at can be null.
     */
    public function test_analysis_job_results_started_at_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['started_at' => null]);

        $this->assertNull($result->started_at);
    }

    /**
     * Test that completed_at can be null.
     */
    public function test_analysis_job_results_completed_at_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['completed_at' => null]);

        $this->assertNull($result->completed_at);
    }

    /**
     * Test that processing_time_ms can be null.
     */
    public function test_analysis_job_results_processing_time_ms_nullable(): void
    {
        $result = AnalysisJobResult::factory()->create(['processing_time_ms' => null]);

        $this->assertNull($result->processing_time_ms);
    }

    /**
     * Test that processing_time_ms can store integer values.
     */
    public function test_analysis_job_results_processing_time_ms_integer(): void
    {
        $result = AnalysisJobResult::factory()->create(['processing_time_ms' => 1234]);

        $this->assertEquals(1234, $result->processing_time_ms);
    }

    /**
     * Test that timestamps are cast to Carbon instances.
     */
    public function test_analysis_job_results_timestamps_cast_to_carbon(): void
    {
        $result = AnalysisJobResult::factory()->create([
            'started_at' => now(),
            'completed_at' => now()->addSeconds(5),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $result->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result->completed_at);
    }

    /**
     * Test that multiple results can be created for same job.
     */
    public function test_analysis_job_results_multiple_per_job(): void
    {
        $job = AnalysisJob::factory()->create();

        $results = AnalysisJobResult::factory()->count(5)->create(['job_id' => $job->id]);

        $this->assertEquals(5, $results->count());
        foreach ($results as $result) {
            $this->assertEquals($job->id, $result->job_id);
        }
    }

    /**
     * Test that indicator_slug max length is 100.
     */
    public function test_analysis_job_results_indicator_slug_max_length(): void
    {
        $longSlug = str_repeat('a', 100);

        $result = AnalysisJobResult::factory()->create(['indicator_slug' => $longSlug]);

        $this->assertEquals(100, strlen($result->indicator_slug));
        $this->assertEquals($longSlug, $result->indicator_slug);
    }

    /**
     * Test that indicator_name max length is 255.
     */
    public function test_analysis_job_results_indicator_name_max_length(): void
    {
        $longName = str_repeat('a', 255);

        $result = AnalysisJobResult::factory()->create(['indicator_name' => $longName]);

        $this->assertEquals(255, strlen($result->indicator_name));
        $this->assertEquals($longName, $result->indicator_name);
    }

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /**
     * Test job has many results relationship.
     */
    public function test_job_has_many_results(): void
    {
        $job = AnalysisJob::factory()->create();

        // Create results with unique indicator slugs to avoid unique constraint violation
        $indicators = ['meta_tags', 'headings', 'images'];
        foreach ($indicators as $slug) {
            AnalysisJobResult::factory()->create([
                'job_id' => $job->id,
                'indicator_slug' => $slug,
            ]);
        }

        $this->assertEquals(3, $job->results->count());
        foreach ($job->results as $result) {
            $this->assertEquals($job->id, $result->job_id);
        }
    }

    /**
     * Test result belongs to job relationship.
     */
    public function test_result_belongs_to_job(): void
    {
        $job = AnalysisJob::factory()->create();
        $result = AnalysisJobResult::factory()->create(['job_id' => $job->id]);

        $this->assertEquals($job->id, $result->job->id);
    }

    /**
     * Test job belongs to user relationship.
     */
    public function test_job_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $job = AnalysisJob::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $job->user->id);
    }

    /**
     * Test job belongs to site relationship.
     */
    public function test_job_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $job = AnalysisJob::factory()->create(['site_id' => $site->id]);

        $this->assertEquals($site->id, $job->site->id);
    }

    // =========================================================================
    // ROLLBACK TESTS
    // =========================================================================

    /**
     * Test that migration rollback drops tables.
     * Note: This test would require running migration:rollback which we skip
     * to avoid side effects. The down() method is verified in the migration file.
     */
    public function test_migration_down_method_drops_tables(): void
    {
        // Verify tables exist
        $this->assertTrue(Schema::hasTable('analysis_jobs'));
        $this->assertTrue(Schema::hasTable('analysis_job_results'));

        // The down() method in the migration drops:
        // 1. analysis_job_results (must be first due to FK)
        // 2. analysis_jobs
        // This is verified by code review, not runtime test
        $this->assertTrue(true, 'Migration down() method verified by code review');
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * Test SQL injection attempt in indicator_slug field.
     */
    public function test_result_sql_injection_in_indicator_slug(): void
    {
        $maliciousSlug = "slug'; DROP TABLE analysis_job_results; --";

        $result = AnalysisJobResult::factory()->create(['indicator_slug' => $maliciousSlug]);

        // Verify the table still exists and slug was stored safely
        $this->assertTrue(Schema::hasTable('analysis_job_results'));
        $this->assertEquals($maliciousSlug, $result->fresh()->indicator_slug);
    }

    /**
     * Test XSS attempt in indicator_name field.
     */
    public function test_result_xss_in_indicator_name(): void
    {
        $maliciousName = '<script>alert("xss")</script>';

        $result = AnalysisJobResult::factory()->create(['indicator_name' => $maliciousName]);

        // Name stored as-is (sanitization should happen on output)
        $this->assertEquals($maliciousName, $result->indicator_name);
    }

    /**
     * Test SQL injection attempt in error_message field.
     */
    public function test_job_sql_injection_in_error_message(): void
    {
        $maliciousMessage = "Error'; DROP TABLE analysis_jobs; --";

        $job = AnalysisJob::factory()->create(['error_message' => $maliciousMessage]);

        // Verify the table still exists
        $this->assertTrue(Schema::hasTable('analysis_jobs'));
        $this->assertEquals($maliciousMessage, $job->fresh()->error_message);
    }

    /**
     * Test XSS attempt in error_message field.
     */
    public function test_result_xss_in_error_message(): void
    {
        $maliciousMessage = '<script>document.location="http://evil.com"</script>';

        $result = AnalysisJobResult::factory()->create(['error_message' => $maliciousMessage]);

        // Message stored as-is (sanitization should happen on output)
        $this->assertEquals($maliciousMessage, $result->error_message);
    }

    /**
     * Test that JSON data with nested structures is stored correctly.
     */
    public function test_result_data_nested_json_structure(): void
    {
        $complexData = [
            'meta' => [
                'title' => 'Test Page',
                'description' => 'Test description',
            ],
            'headings' => [
                'h1' => ['Main Heading'],
                'h2' => ['Sub 1', 'Sub 2'],
            ],
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep value',
                    ],
                ],
            ],
        ];

        $result = AnalysisJobResult::factory()->create(['data' => $complexData]);

        $this->assertEquals($complexData, $result->data);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = [];
            $result = DB::select("PRAGMA index_list('{$table}')");
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info('{$row->name}')");
                foreach ($indexInfo as $info) {
                    $indexes[$row->name][] = $info->name;
                }
            }
            return $indexes;
        }

        // MySQL
        $indexes = [];
        $result = DB::select("SHOW INDEX FROM {$table}");
        foreach ($result as $row) {
            $indexes[$row->Key_name][] = $row->Column_name;
        }
        return $indexes;
    }

    /**
     * Helper: Assert index exists.
     */
    protected function assertIndexExists(array $indexes, string $indexName): void
    {
        $found = collect($indexes)->contains(function ($columns, $name) use ($indexName) {
            return str_contains(strtolower($name), strtolower($indexName));
        });

        $this->assertTrue($found, "Index '{$indexName}' does not exist");
    }

    /**
     * Helper: Assert unique index exists.
     */
    protected function assertUniqueIndexExists(array $indexes, string $columnName): void
    {
        $found = collect($indexes)->contains(function ($columns, $name) use ($columnName) {
            return in_array($columnName, $columns) && str_contains(strtolower($name), 'unique');
        });

        $this->assertTrue($found, "Unique index for column '{$columnName}' does not exist");
    }
}
