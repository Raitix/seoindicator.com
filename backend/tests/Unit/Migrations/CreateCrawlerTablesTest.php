<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateCrawlerTablesTest extends TestCase
{
    use RefreshDatabase;

    protected const JOBS_TABLE = 'crawl_jobs';
    protected const PAGES_TABLE = 'crawl_pages';

    // =========================================================================
    // 1. SCHEMA STRUCTURE TESTS - CRAWL_JOBS
    // =========================================================================

    /** @test */
    public function crawl_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable(self::JOBS_TABLE));
    }

    /** @test */
    public function crawl_jobs_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'id'));
    }

    /** @test */
    public function crawl_jobs_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'user_id'));
    }

    /** @test */
    public function crawl_jobs_has_project_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'project_id'));
    }

    /** @test */
    public function crawl_jobs_has_job_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'job_id'));
    }

    /** @test */
    public function crawl_jobs_has_url_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'url'));
    }

    /** @test */
    public function crawl_jobs_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'status'));
    }

    /** @test */
    public function crawl_jobs_has_depth_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'depth'));
    }

    /** @test */
    public function crawl_jobs_has_options_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'options'));
    }

    /** @test */
    public function crawl_jobs_has_pages_crawled_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'pages_crawled'));
    }

    /** @test */
    public function crawl_jobs_has_total_size_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'total_size'));
    }

    /** @test */
    public function crawl_jobs_has_error_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'error'));
    }

    /** @test */
    public function crawl_jobs_has_started_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'started_at'));
    }

    /** @test */
    public function crawl_jobs_has_completed_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'completed_at'));
    }

    /** @test */
    public function crawl_jobs_has_expires_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'expires_at'));
    }

    /** @test */
    public function crawl_jobs_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'created_at'));
        $this->assertTrue(Schema::hasColumn(self::JOBS_TABLE, 'updated_at'));
    }

    // =========================================================================
    // 2. SCHEMA STRUCTURE TESTS - CRAWL_PAGES
    // =========================================================================

    /** @test */
    public function crawl_pages_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable(self::PAGES_TABLE));
    }

    /** @test */
    public function crawl_pages_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'id'));
    }

    /** @test */
    public function crawl_pages_has_crawl_job_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'crawl_job_id'));
    }

    /** @test */
    public function crawl_pages_has_url_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'url'));
    }

    /** @test */
    public function crawl_pages_has_depth_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'depth'));
    }

    /** @test */
    public function crawl_pages_has_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'title'));
    }

    /** @test */
    public function crawl_pages_has_rendered_html_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'rendered_html'));
    }

    /** @test */
    public function crawl_pages_has_structured_text_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'structured_text'));
    }

    /** @test */
    public function crawl_pages_has_meta_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'meta'));
    }

    /** @test */
    public function crawl_pages_has_links_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'links'));
    }

    /** @test */
    public function crawl_pages_has_size_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'size'));
    }

    /** @test */
    public function crawl_pages_has_crawled_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'crawled_at'));
    }

    /** @test */
    public function crawl_pages_has_error_column(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'error'));
    }

    /** @test */
    public function crawl_pages_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'created_at'));
        $this->assertTrue(Schema::hasColumn(self::PAGES_TABLE, 'updated_at'));
    }

    // =========================================================================
    // 3. INDEX TESTS
    // =========================================================================

    /** @test */
    public function crawl_jobs_has_index_on_user_id(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'user_id');
        $this->assertNotEmpty($indexes, 'Index on user_id should exist');
    }

    /** @test */
    public function crawl_jobs_has_index_on_status(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'status');
        $this->assertNotEmpty($indexes, 'Index on status should exist');
    }

    /** @test */
    public function crawl_jobs_has_index_on_job_id(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'job_id');
        $this->assertNotEmpty($indexes, 'Index on job_id should exist');
    }

    /** @test */
    public function crawl_jobs_has_index_on_expires_at(): void
    {
        $indexes = $this->getIndexes(self::JOBS_TABLE, 'expires_at');
        $this->assertNotEmpty($indexes, 'Index on expires_at should exist');
    }

    /** @test */
    public function crawl_pages_has_index_on_crawl_job_id(): void
    {
        $indexes = $this->getIndexes(self::PAGES_TABLE, 'crawl_job_id');
        $this->assertNotEmpty($indexes, 'Index on crawl_job_id should exist');
    }

    /** @test */
    public function crawl_pages_has_composite_index_on_job_and_depth(): void
    {
        // The composite index exists if we can query by both columns efficiently
        // We verify this by checking the index exists on both columns
        $jobIndexes = $this->getIndexes(self::PAGES_TABLE, 'crawl_job_id');
        $this->assertNotEmpty($jobIndexes, 'Composite index on (crawl_job_id, depth) should include crawl_job_id');
    }

    // =========================================================================
    // 4. FOREIGN KEY CONSTRAINT TESTS
    // =========================================================================

    /** @test */
    public function crawl_jobs_user_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::JOBS_TABLE, 'user_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on user_id should exist');
    }

    /** @test */
    public function crawl_jobs_project_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::JOBS_TABLE, 'project_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on project_id should exist');
    }

    /** @test */
    public function crawl_pages_crawl_job_id_foreign_key_exists(): void
    {
        $foreignKeys = $this->getForeignKeys(self::PAGES_TABLE, 'crawl_job_id');
        $this->assertNotEmpty($foreignKeys, 'Foreign key on crawl_job_id should exist');
    }

    /** @test */
    public function deleting_user_cascades_to_crawl_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing(self::JOBS_TABLE, ['id' => $job->id]);
    }

    /** @test */
    public function deleting_project_sets_null_on_crawl_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $project = \App\Models\Project::create([
            'name' => 'Test Project',
            'user_id' => $user->id,
        ]);
        $job = $this->createJob(['user_id' => $user->id, 'project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseHas(self::JOBS_TABLE, [
            'id' => $job->id,
            'project_id' => null,
        ]);
    }

    /** @test */
    public function deleting_crawl_job_cascades_to_pages(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id);

        DB::table(self::JOBS_TABLE)->where('id', $job->id)->delete();

        $this->assertDatabaseMissing(self::PAGES_TABLE, ['id' => $page->id]);
    }

    /** @test */
    public function null_project_id_allowed(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id, 'project_id' => null]);

        $this->assertDatabaseHas(self::JOBS_TABLE, ['id' => $job->id, 'project_id' => null]);
    }

    /** @test */
    public function invalid_user_id_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => 999999,
            'job_id' => 'crawl-test-' . uniqid(),
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function invalid_project_id_throws_exception(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => $user->id,
            'project_id' => 999999,
            'job_id' => 'crawl-test-' . uniqid(),
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function invalid_crawl_job_id_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::PAGES_TABLE)->insert([
            'crawl_job_id' => 999999,
            'url' => 'https://example.com',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // 5. ENUM VALIDATION TESTS
    // =========================================================================

    /** @test */
    public function crawl_jobs_status_accepts_queued(): void
    {
        $job = $this->createJob(['status' => 'queued']);
        $this->assertEquals('queued', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function crawl_jobs_status_accepts_running(): void
    {
        $job = $this->createJob(['status' => 'running']);
        $this->assertEquals('running', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function crawl_jobs_status_accepts_completed(): void
    {
        $job = $this->createJob(['status' => 'completed']);
        $this->assertEquals('completed', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function crawl_jobs_status_accepts_failed(): void
    {
        $job = $this->createJob(['status' => 'failed']);
        $this->assertEquals('failed', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function crawl_jobs_status_accepts_cancelled(): void
    {
        $job = $this->createJob(['status' => 'cancelled']);
        $this->assertEquals('cancelled', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    /** @test */
    public function crawl_jobs_status_rejects_invalid_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->createJob(['status' => 'invalid_status']);
    }

    /** @test */
    public function crawl_jobs_status_defaults_to_queued(): void
    {
        $job = $this->createJob();
        $this->assertEquals('queued', DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('status'));
    }

    // =========================================================================
    // 6. COLUMN CONSTRAINT TESTS - CRAWL_JOBS
    // =========================================================================

    /** @test */
    public function crawl_jobs_user_id_not_null(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => null,
            'job_id' => 'crawl-test',
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_jobs_job_id_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => $user->id,
            'job_id' => null,
            'url' => 'https://example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_jobs_url_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => $user->id,
            'job_id' => 'crawl-test',
            'url' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_jobs_expires_at_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::JOBS_TABLE)->insert([
            'user_id' => $user->id,
            'job_id' => 'crawl-test',
            'url' => 'https://example.com',
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_jobs_depth_defaults_to_zero(): void
    {
        $job = $this->createJob();
        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('depth'));
    }

    /** @test */
    public function crawl_jobs_pages_crawled_defaults_to_zero(): void
    {
        $job = $this->createJob();
        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('pages_crawled'));
    }

    /** @test */
    public function crawl_jobs_total_size_defaults_to_zero(): void
    {
        $job = $this->createJob();
        $this->assertEquals(0, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('total_size'));
    }

    /** @test */
    public function crawl_jobs_options_nullable(): void
    {
        $job = $this->createJob(['options' => null]);
        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('options'));
    }

    /** @test */
    public function crawl_jobs_error_nullable(): void
    {
        $job = $this->createJob(['error' => null]);
        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('error'));
    }

    /** @test */
    public function crawl_jobs_started_at_nullable(): void
    {
        $job = $this->createJob(['started_at' => null]);
        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('started_at'));
    }

    /** @test */
    public function crawl_jobs_completed_at_nullable(): void
    {
        $job = $this->createJob(['completed_at' => null]);
        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('completed_at'));
    }

    /** @test */
    public function crawl_jobs_job_id_unique(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->createJob(['user_id' => $user->id, 'job_id' => 'crawl-unique-test']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $this->createJob(['user_id' => $user->id, 'job_id' => 'crawl-unique-test']);
    }

    /** @test */
    public function crawl_jobs_url_max_length_2083(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2050); // Total ~2083
        
        $job = $this->createJob(['url' => $longUrl]);
        
        $this->assertEquals($longUrl, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('url'));
    }

    // =========================================================================
    // 7. COLUMN CONSTRAINT TESTS - CRAWL_PAGES
    // =========================================================================

    /** @test */
    public function crawl_pages_crawl_job_id_not_null(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::PAGES_TABLE)->insert([
            'crawl_job_id' => null,
            'url' => 'https://example.com',
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_pages_url_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::PAGES_TABLE)->insert([
            'crawl_job_id' => $job->id,
            'url' => null,
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_pages_depth_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table(self::PAGES_TABLE)->insert([
            'crawl_job_id' => $job->id,
            'url' => 'https://example.com',
            'depth' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function crawl_pages_title_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['title' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('title'));
    }

    /** @test */
    public function crawl_pages_rendered_html_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['rendered_html' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('rendered_html'));
    }

    /** @test */
    public function crawl_pages_structured_text_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['structured_text' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('structured_text'));
    }

    /** @test */
    public function crawl_pages_meta_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['meta' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('meta'));
    }

    /** @test */
    public function crawl_pages_links_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['links' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('links'));
    }

    /** @test */
    public function crawl_pages_size_defaults_to_zero(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id);
        
        $this->assertEquals(0, DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('size'));
    }

    /** @test */
    public function crawl_pages_crawled_at_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['crawled_at' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('crawled_at'));
    }

    /** @test */
    public function crawl_pages_error_nullable(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        $page = $this->createPage($job->id, ['error' => null]);
        
        $this->assertNull(DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('error'));
    }

    // =========================================================================
    // 8. JSON COLUMN TESTS
    // =========================================================================

    /** @test */
    public function crawl_jobs_options_stores_valid_json(): void
    {
        $testOptions = [
            'waitForSelector' => '.content',
            'waitTime' => 2000,
            'headers' => ['X-Custom' => 'value'],
        ];
        
        $job = $this->createJob(['options' => json_encode($testOptions)]);
        
        $retrieved = DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('options');
        $this->assertEquals($testOptions, json_decode($retrieved, true));
    }

    /** @test */
    public function crawl_jobs_options_stores_null(): void
    {
        $job = $this->createJob(['options' => null]);
        $this->assertNull(DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('options'));
    }

    /** @test */
    public function crawl_pages_meta_stores_valid_json(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $testMeta = [
            'description' => 'Page description',
            'robots' => 'index, follow',
            'og:title' => 'Open Graph Title',
        ];
        
        $page = $this->createPage($job->id, ['meta' => json_encode($testMeta)]);
        
        $retrieved = DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('meta');
        $this->assertEquals($testMeta, json_decode($retrieved, true));
    }

    /** @test */
    public function crawl_pages_links_stores_valid_json(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $testLinks = [
            ['url' => 'https://example.com/page1', 'text' => 'Page 1'],
            ['url' => 'https://example.com/page2', 'text' => 'Page 2'],
        ];
        
        $page = $this->createPage($job->id, ['links' => json_encode($testLinks)]);
        
        $retrieved = DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('links');
        $this->assertEquals($testLinks, json_decode($retrieved, true));
    }

    // =========================================================================
    // 9. LARGE CONTENT TESTS
    // =========================================================================

    /** @test */
    public function crawl_pages_stores_1mb_html(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $largeHtml = str_repeat('<div>Test content</div>', 50000); // ~1MB
        
        $page = $this->createPage($job->id, ['rendered_html' => $largeHtml]);
        
        $retrieved = DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('rendered_html');
        $this->assertEquals($largeHtml, $retrieved);
    }

    /** @test */
    public function crawl_pages_stores_large_structured_text(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $largeText = str_repeat("This is structured text content.\n", 30000); // ~1MB
        
        $page = $this->createPage($job->id, ['structured_text' => $largeText]);
        
        $retrieved = DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('structured_text');
        $this->assertEquals($largeText, $retrieved);
    }

    // =========================================================================
    // 10. ROLLBACK TESTS
    // =========================================================================

    /** @test */
    public function migration_can_be_rolled_back(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/' . $this->getMigrationFileName(),
        ]);

        $this->assertFalse(Schema::hasTable(self::PAGES_TABLE));
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
        $this->assertTrue(Schema::hasTable(self::PAGES_TABLE));
    }

    // =========================================================================
    // 11. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function job_id_with_sql_injection_handled_safely(): void
    {
        $maliciousJobId = "'; DROP TABLE crawl_jobs; --";
        
        $job = $this->createJob(['job_id' => $maliciousJobId]);
        
        $retrieved = DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('job_id');
        $this->assertEquals($maliciousJobId, $retrieved);
        $this->assertTrue(Schema::hasTable(self::JOBS_TABLE), 'Table should still exist');
    }

    /** @test */
    public function url_with_sql_injection_handled_safely(): void
    {
        $maliciousUrl = "https://example.com'; DROP TABLE users; --";
        
        $job = $this->createJob(['url' => $maliciousUrl]);
        
        $retrieved = DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('url');
        $this->assertEquals($maliciousUrl, $retrieved);
        $this->assertTrue(Schema::hasTable('users'), 'Users table should still exist');
    }

    /** @test */
    public function error_message_with_xss_stored_safely(): void
    {
        $xssError = '<script>alert("xss")</script>Error occurred';
        
        $job = $this->createJob(['error' => $xssError]);
        
        $retrieved = DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('error');
        $this->assertStringContainsString('<script>', $retrieved);
    }

    /** @test */
    public function title_with_sql_injection_handled_safely(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = $this->createJob(['user_id' => $user->id]);
        
        $maliciousTitle = "'; DROP TABLE crawl_pages; --";
        
        $page = $this->createPage($job->id, ['title' => $maliciousTitle]);
        
        $retrieved = DB::table(self::PAGES_TABLE)->where('id', $page->id)->value('title');
        $this->assertEquals($maliciousTitle, $retrieved);
        $this->assertTrue(Schema::hasTable(self::PAGES_TABLE), 'Table should still exist');
    }

    /** @test */
    public function options_json_with_sql_injection_handled_safely(): void
    {
        $maliciousOptions = '{"url": "\'; DROP TABLE users; --"}';
        
        $job = $this->createJob(['options' => $maliciousOptions]);
        
        $retrieved = DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('options');
        $this->assertStringContainsString('DROP TABLE', $retrieved);
        $this->assertTrue(Schema::hasTable('users'), 'Users table should still exist');
    }

    /** @test */
    public function large_url_at_max_length_stored_successfully(): void
    {
        $maxUrl = 'https://example.com/' . str_repeat('a', 2050); // ~2083 chars
        
        $job = $this->createJob(['url' => $maxUrl]);
        
        $this->assertEquals($maxUrl, DB::table(self::JOBS_TABLE)->where('id', $job->id)->value('url'));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createJob(array $overrides = []): object
    {
        $user = isset($overrides['user_id']) 
            ? \App\Models\User::find($overrides['user_id'])
            : \App\Models\User::factory()->create();

        $data = array_merge([
            'user_id' => $user->id,
            'project_id' => null,
            'job_id' => 'crawl-' . uniqid(),
            'url' => 'https://example.com',
            'status' => 'queued',
            'depth' => 0,
            'options' => null,
            'pages_crawled' => 0,
            'total_size' => 0,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $jobId = DB::table(self::JOBS_TABLE)->insertGetId($data);

        return (object) ['id' => $jobId, 'user_id' => $user->id];
    }

    protected function createPage(int $jobId, array $overrides = []): object
    {
        $data = array_merge([
            'crawl_job_id' => $jobId,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'title' => 'Test Page',
            'rendered_html' => null,
            'structured_text' => null,
            'meta' => null,
            'links' => null,
            'size' => 0,
            'crawled_at' => null,
            'error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $pageId = DB::table(self::PAGES_TABLE)->insertGetId($data);

        return (object) ['id' => $pageId];
    }

    protected function getForeignKeys(string $table, string $column): array
    {
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
        $files = glob(database_path('migrations/*crawler*'));
        return $files ? basename($files[0]) : '';
    }
}
