<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Project;

/**
 * Migration tests for TASK-22-1: Database Migrations for Crawler
 *
 * Tests verify:
 * - crawl_jobs and crawl_pages table structures match specification
 * - Foreign key constraints work correctly
 * - Indexes are created for performance
 * - Enum validation for status field
 * - JSON columns store and retrieve data correctly
 * - MEDIUMTEXT columns handle large content
 * - Security: SQL injection prevention
 */
class CreateCrawlerTablesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // CRAWL_JOBS TABLE TESTS
    // =========================================================================

    /**
     * Test that the crawl_jobs table exists.
     * @test
     */
    public function test_crawl_jobs_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('crawl_jobs'),
            'crawl_jobs table should exist'
        );
    }

    /**
     * Test that all required columns exist in crawl_jobs table.
     * @test
     */
    public function test_crawl_jobs_required_columns_exist(): void
    {
        $columns = [
            'id',
            'user_id',
            'project_id',
            'job_id',
            'url',
            'status',
            'depth',
            'options',
            'pages_crawled',
            'total_size',
            'error',
            'started_at',
            'completed_at',
            'expires_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('crawl_jobs', $column),
                "Column '{$column}' should exist in crawl_jobs table"
            );
        }
    }

    /**
     * Test that foreign key to users exists and works.
     * @test
     */
    public function test_crawl_jobs_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-' . \Str::uuid()->toString(),
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('crawl_jobs', ['id' => $jobId]);

        // Test cascade delete
        $user->delete();

        $this->assertDatabaseMissing('crawl_jobs', ['id' => $jobId]);
    }

    /**
     * Test that foreign key to projects uses SET NULL on delete.
     * @test
     */
    public function test_crawl_jobs_foreign_key_to_projects_set_null(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'job_id' => 'crawl-' . \Str::uuid()->toString(),
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals($project->id, DB::table('crawl_jobs')->where('id', $jobId)->first()->project_id);

        // Delete project - should SET NULL
        $project->delete();

        $job = DB::table('crawl_jobs')->where('id', $jobId)->first();
        $this->assertNull($job->project_id);
    }

    /**
     * Test that foreign key constraint prevents invalid user_id.
     * @test
     */
    public function test_crawl_jobs_foreign_key_constraint_invalid_user(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_jobs')->insert([
            'user_id' => 99999,
            'project_id' => null,
            'job_id' => 'crawl-test-123',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that foreign key constraint prevents invalid project_id.
     * @test
     */
    public function test_crawl_jobs_foreign_key_constraint_invalid_project(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => 99999,
            'job_id' => 'crawl-test-456',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test unique constraint on job_id.
     * @test
     */
    public function test_crawl_jobs_unique_job_id(): void
    {
        $user = User::factory()->create();
        $jobId = 'crawl-unique-test-123';

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => $jobId,
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempt duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => $jobId,
            'url' => 'https://example.org',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created on crawl_jobs table.
     * @test
     */
    public function test_crawl_jobs_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('crawl_jobs');

        // Check for user_id index
        $this->assertIndexExists($indexes, 'user_id');

        // Check for status index
        $this->assertIndexExists($indexes, 'status');

        // Check for job_id index
        $this->assertIndexExists($indexes, 'job_id');

        // Check for expires_at index
        $this->assertIndexExists($indexes, 'expires_at');
    }

    /**
     * Test that status accepts valid enum values.
     * @test
     */
    public function test_crawl_jobs_status_valid_enum_values(): void
    {
        $user = User::factory()->create();
        $validStatuses = ['queued', 'running', 'completed', 'failed', 'cancelled'];

        foreach ($validStatuses as $index => $status) {
            DB::table('crawl_jobs')->insert([
                'user_id' => $user->id,
                'project_id' => null,
                'job_id' => "crawl-status-{$index}",
                'url' => 'https://example.com',
                'status' => $status,
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertEquals(5, DB::table('crawl_jobs')->where('user_id', $user->id)->count());
    }

    /**
     * Test that status rejects invalid enum values.
     * @test
     */
    public function test_crawl_jobs_status_invalid_enum_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-invalid-status',
            'url' => 'https://example.com',
            'status' => 'invalid_status',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test default status is queued.
     * @test
     */
    public function test_crawl_jobs_default_status(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-default-status',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals('queued', $job->status);
    }

    /**
     * Test default depth is 0.
     * @test
     */
    public function test_crawl_jobs_default_depth(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-default-depth',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals(0, $job->depth);
    }

    /**
     * Test default pages_crawled is 0.
     * @test
     */
    public function test_crawl_jobs_default_pages_crawled(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-default-pages',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals(0, $job->pages_crawled);
    }

    /**
     * Test default total_size is 0.
     * @test
     */
    public function test_crawl_jobs_default_total_size(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-default-size',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals(0, $job->total_size);
    }

    /**
     * Test that options can be stored as JSON.
     * @test
     */
    public function test_crawl_jobs_options_stored_as_json(): void
    {
        $user = User::factory()->create();
        $options = [
            'waitForSelector' => '.content',
            'waitTime' => 2000,
            'userAgent' => 'SEOIndicator/1.0 Crawler',
            'includeImages' => false,
        ];

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-json-options',
            'url' => 'https://example.com',
            'options' => json_encode($options),
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($options, json_decode($job->options, true));
    }

    /**
     * Test that options can be null.
     * @test
     */
    public function test_crawl_jobs_options_nullable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-options',
            'url' => 'https://example.com',
            'options' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertNull($job->options);
    }

    /**
     * Test that error can be null.
     * @test
     */
    public function test_crawl_jobs_error_nullable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-error',
            'url' => 'https://example.com',
            'error' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertNull($job->error);
    }

    /**
     * Test that error can store long text.
     * @test
     */
    public function test_crawl_jobs_error_stores_long_text(): void
    {
        $user = User::factory()->create();
        $longError = str_repeat('Error detail. ', 100);

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-long-error',
            'url' => 'https://example.com',
            'error' => $longError,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($longError, $job->error);
    }

    /**
     * Test that started_at can be null.
     * @test
     */
    public function test_crawl_jobs_started_at_nullable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-started',
            'url' => 'https://example.com',
            'started_at' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertNull($job->started_at);
    }

    /**
     * Test that completed_at can be null.
     * @test
     */
    public function test_crawl_jobs_completed_at_nullable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-completed',
            'url' => 'https://example.com',
            'completed_at' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertNull($job->completed_at);
    }

    /**
     * Test that expires_at cannot be null.
     * @test
     */
    public function test_crawl_jobs_expires_at_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-expires',
            'url' => 'https://example.com',
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that URL max length is 2083 characters.
     * @test
     */
    public function test_crawl_jobs_url_max_length(): void
    {
        $user = User::factory()->create();
        $maxUrl = 'https://example.com/' . str_repeat('a', 2060); // Total ~2083

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-max-url',
            'url' => $maxUrl,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($maxUrl, $job->url);
    }

    /**
     * Test that project_id can be null.
     * @test
     */
    public function test_crawl_jobs_project_id_nullable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-no-project',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('crawl_jobs', ['id' => $id, 'project_id' => null]);
    }

    /**
     * Test that user_id cannot be null.
     * @test
     */
    public function test_crawl_jobs_user_id_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_jobs')->insert([
            'user_id' => null,
            'project_id' => null,
            'job_id' => 'crawl-null-user',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that url cannot be null.
     * @test
     */
    public function test_crawl_jobs_url_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-null-url',
            'url' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that job_id cannot be null.
     * @test
     */
    public function test_crawl_jobs_job_id_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();

        DB::table('crawl_jobs')->insert([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => null,
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // CRAWL_PAGES TABLE TESTS
    // =========================================================================

    /**
     * Test that the crawl_pages table exists.
     * @test
     */
    public function test_crawl_pages_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('crawl_pages'),
            'crawl_pages table should exist'
        );
    }

    /**
     * Test that all required columns exist in crawl_pages table.
     * @test
     */
    public function test_crawl_pages_required_columns_exist(): void
    {
        $columns = [
            'id',
            'crawl_job_id',
            'url',
            'depth',
            'title',
            'rendered_html',
            'structured_text',
            'meta',
            'links',
            'size',
            'crawled_at',
            'error',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('crawl_pages', $column),
                "Column '{$column}' should exist in crawl_pages table"
            );
        }
    }

    /**
     * Test that foreign key to crawl_jobs exists and works.
     * @test
     */
    public function test_crawl_pages_foreign_key_to_crawl_jobs(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-fk-test-job',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageId = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page1',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('crawl_pages', ['id' => $pageId]);

        // Test cascade delete
        DB::table('crawl_jobs')->where('id', $jobId)->delete();

        $this->assertDatabaseMissing('crawl_pages', ['id' => $pageId]);
    }

    /**
     * Test that foreign key constraint prevents invalid crawl_job_id.
     * @test
     */
    public function test_crawl_pages_foreign_key_constraint_invalid_job(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_pages')->insert([
            'crawl_job_id' => 99999,
            'url' => 'https://example.com',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created on crawl_pages table.
     * @test
     */
    public function test_crawl_pages_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('crawl_pages');

        // Check for crawl_job_id index (index name contains 'job_id')
        // Note: Migration uses custom index name 'idx_crawl_pages_job_id'
        $this->assertIndexExists($indexes, 'job_id');

        // Check for composite index (crawl_job_id, depth)
        $this->assertCompositeIndexExists($indexes, ['crawl_job_id', 'depth']);
    }

    /**
     * Test that crawl_job_id cannot be null.
     * @test
     */
    public function test_crawl_pages_crawl_job_id_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_pages')->insert([
            'crawl_job_id' => null,
            'url' => 'https://example.com',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that url cannot be null.
     * @test
     */
    public function test_crawl_pages_url_required(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-url-required-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_pages')->insert([
            'crawl_job_id' => $jobId,
            'url' => null,
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that depth cannot be null.
     * @test
     */
    public function test_crawl_pages_depth_required(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-depth-required-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('crawl_pages')->insert([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com',
            'depth' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test default size is 0.
     * @test
     */
    public function test_crawl_pages_default_size(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-default-size-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals(0, $page->size);
    }

    /**
     * Test that title can be null.
     * @test
     */
    public function test_crawl_pages_title_nullable(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-title-null-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'title' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertNull($page->title);
    }

    /**
     * Test that rendered_html can be null.
     * @test
     */
    public function test_crawl_pages_rendered_html_nullable(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-html-null-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'rendered_html' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertNull($page->rendered_html);
    }

    /**
     * Test that structured_text can be null.
     * @test
     */
    public function test_crawl_pages_structured_text_nullable(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-text-null-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'structured_text' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertNull($page->structured_text);
    }

    /**
     * Test that meta can be stored as JSON.
     * @test
     */
    public function test_crawl_pages_meta_stored_as_json(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-meta-json-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meta = [
            'description' => 'Page description',
            'robots' => 'index, follow',
            'canonical' => 'https://example.com/page',
        ];

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'meta' => json_encode($meta),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals($meta, json_decode($page->meta, true));
    }

    /**
     * Test that links can be stored as JSON.
     * @test
     */
    public function test_crawl_pages_links_stored_as_json(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-links-json-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $links = [
            ['href' => 'https://example.com/about', 'text' => 'About', 'internal' => true],
            ['href' => 'https://external.com', 'text' => 'External', 'internal' => false],
        ];

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'links' => json_encode($links),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals($links, json_decode($page->links, true));
    }

    /**
     * Test that crawled_at can be null.
     * @test
     */
    public function test_crawl_pages_crawled_at_nullable(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-crawled-null-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'crawled_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertNull($page->crawled_at);
    }

    /**
     * Test that error can be null.
     * @test
     */
    public function test_crawl_pages_error_nullable(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-error-null-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertNull($page->error);
    }

    /**
     * Test storing large HTML content (MEDIUMTEXT ~16MB).
     * @test
     */
    public function test_crawl_pages_large_html_content(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-large-html-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generate > 1MB HTML content (29 chars per paragraph, need ~34500 for 1MB)
        $largeHtml = '<html><body>' . str_repeat('<p>Test content paragraph.</p>', 35000) . '</body></html>';

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/large-page',
            'depth' => 0,
            'rendered_html' => $largeHtml,
            'size' => strlen($largeHtml),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals($largeHtml, $page->rendered_html);
        $this->assertGreaterThan(1000000, strlen($page->rendered_html));
    }

    /**
     * Test storing large structured text.
     * @test
     */
    public function test_crawl_pages_large_structured_text(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-large-text-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Large text content
        $largeText = str_repeat("This is structured text content.\n", 50000);

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/text-page',
            'depth' => 0,
            'structured_text' => $largeText,
            'size' => strlen($largeText),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals($largeText, $page->structured_text);
    }

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /**
     * Test job has many pages relationship.
     * @test
     */
    public function test_job_has_many_pages(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-relationship-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'pages_crawled' => 3,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create multiple pages
        for ($i = 0; $i < 3; $i++) {
            DB::table('crawl_pages')->insert([
                'crawl_job_id' => $jobId,
                'url' => "https://example.com/page{$i}",
                'depth' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pagesCount = DB::table('crawl_pages')->where('crawl_job_id', $jobId)->count();
        $this->assertEquals(3, $pagesCount);
    }

    /**
     * Test user has many jobs relationship.
     * @test
     */
    public function test_user_has_many_jobs(): void
    {
        $user = User::factory()->create();

        // Create multiple jobs for user
        for ($i = 0; $i < 3; $i++) {
            DB::table('crawl_jobs')->insert([
                'user_id' => $user->id,
                'project_id' => null,
                'job_id' => "crawl-user-rel-{$i}",
                'url' => "https://example{$i}.com",
                'status' => 'queued',
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $jobsCount = DB::table('crawl_jobs')->where('user_id', $user->id)->count();
        $this->assertEquals(3, $jobsCount);
    }

    /**
     * Test project has many jobs relationship.
     * @test
     */
    public function test_project_has_many_jobs(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create multiple jobs for project
        for ($i = 0; $i < 2; $i++) {
            DB::table('crawl_jobs')->insert([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'job_id' => "crawl-project-rel-{$i}",
                'url' => "https://example{$i}.com",
                'status' => 'queued',
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $jobsCount = DB::table('crawl_jobs')->where('project_id', $project->id)->count();
        $this->assertEquals(2, $jobsCount);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * Test SQL injection attempt in job_id field.
     * @test
     */
    public function test_sql_injection_in_job_id(): void
    {
        $user = User::factory()->create();
        $maliciousJobId = "crawl-'; DROP TABLE crawl_jobs; --";

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => $maliciousJobId,
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify table still exists and job was stored safely
        $this->assertTrue(Schema::hasTable('crawl_jobs'));
        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($maliciousJobId, $job->job_id);
    }

    /**
     * Test SQL injection attempt in url field.
     * @test
     */
    public function test_sql_injection_in_url(): void
    {
        $user = User::factory()->create();
        $maliciousUrl = "https://example.com'; DROP TABLE crawl_jobs; --";

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-sql-injection-url',
            'url' => $maliciousUrl,
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify table still exists
        $this->assertTrue(Schema::hasTable('crawl_jobs'));
        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($maliciousUrl, $job->url);
    }

    /**
     * Test XSS attempt in error field.
     * @test
     */
    public function test_xss_in_error_message(): void
    {
        $user = User::factory()->create();
        $maliciousError = '<script>alert("xss")</script>';

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-xss-error',
            'url' => 'https://example.com',
            'status' => 'failed',
            'error' => $maliciousError,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        // Stored as-is (should be escaped on output)
        $this->assertEquals($maliciousError, $job->error);
    }

    /**
     * Test SQL injection attempt in JSON options field.
     * @test
     */
    public function test_sql_injection_in_json_options(): void
    {
        $user = User::factory()->create();
        $maliciousOptions = json_encode([
            'waitForSelector' => "'; DROP TABLE crawl_jobs; --",
            'waitTime' => 2000,
        ]);

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-sql-json',
            'url' => 'https://example.com',
            'options' => $maliciousOptions,
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify table still exists
        $this->assertTrue(Schema::hasTable('crawl_jobs'));
        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $options = json_decode($job->options, true);
        $this->assertEquals("'; DROP TABLE crawl_jobs; --", $options['waitForSelector']);
    }

    /**
     * Test SQL injection in page title.
     * @test
     */
    public function test_sql_injection_in_page_title(): void
    {
        $user = User::factory()->create();
        
        $jobId = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-sql-title',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maliciousTitle = "Page Title'; DROP TABLE crawl_pages; --";

        $id = DB::table('crawl_pages')->insertGetId([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'title' => $maliciousTitle,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify table still exists
        $this->assertTrue(Schema::hasTable('crawl_pages'));
        $page = DB::table('crawl_pages')->where('id', $id)->first();
        $this->assertEquals($maliciousTitle, $page->title);
    }

    /**
     * Test large URL at max length (2083).
     * @test
     */
    public function test_large_url_handling(): void
    {
        $user = User::factory()->create();
        
        // Create URL at max length (2083 is HTTP spec max)
        $baseUrl = 'https://example.com/path?query=';
        $padding = str_repeat('a', 2083 - strlen($baseUrl));
        $maxUrl = $baseUrl . $padding;

        $id = DB::table('crawl_jobs')->insertGetId([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-max-url-length',
            'url' => $maxUrl,
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('crawl_jobs')->where('id', $id)->first();
        $this->assertEquals($maxUrl, $job->url);
        $this->assertEquals(2083, strlen($job->url));
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
     * Helper: Assert composite index exists.
     */
    protected function assertCompositeIndexExists(array $indexes, array $columns): void
    {
        $found = collect($indexes)->contains(function ($indexColumns, $name) use ($columns) {
            return count(array_intersect($indexColumns, $columns)) === count($columns);
        });

        $columnsStr = implode(', ', $columns);
        $this->assertTrue($found, "Composite index for columns '{$columnsStr}' does not exist");
    }
}
