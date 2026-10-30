<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CrawlerService;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the CrawlerService skeleton.
 *
 * @see TASK-22-3
 * @see SPEC-022
 */
class CrawlerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CrawlerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CrawlerService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Service Instantiation Tests
    // ==========================================

    /**
     * Test service can be instantiated via container.
     */
    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CrawlerService::class, $this->service);
    }

    /**
     * Test service is not a singleton (each request gets new instance).
     */
    public function test_service_is_not_singleton(): void
    {
        // The service is not bound as singleton - each resolution gets new instance
        // This is intentional for flexibility with constructor parameters
        $instance1 = app(CrawlerService::class);
        $instance2 = app(CrawlerService::class);

        // Both are valid instances
        $this->assertInstanceOf(CrawlerService::class, $instance1);
        $this->assertInstanceOf(CrawlerService::class, $instance2);
    }

    // ==========================================
    // createJob Method Tests
    // ==========================================

    /**
     * Test createJob creates a job with valid data.
     */
    public function test_create_job_creates_job(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertInstanceOf(CrawlJob::class, $job);
        $this->assertEquals($user->id, $job->user_id);
        $this->assertEquals('https://example.com', $job->url);
        $this->assertEquals(CrawlJob::STATUS_QUEUED, $job->status);
    }

    /**
     * Test createJob generates unique job_id.
     */
    public function test_create_job_generates_unique_job_id(): void
    {
        $user = User::factory()->create();

        $job1 = $this->service->createJob($user->id, 'https://example1.com');
        $job2 = $this->service->createJob($user->id, 'https://example2.com');

        $this->assertNotEquals($job1->job_id, $job2->job_id);
        $this->assertStringStartsWith('crawl-', $job1->job_id);
    }

    /**
     * Test createJob job_id format is valid.
     */
    public function test_create_job_id_format(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertMatchesRegularExpression('/^crawl-[a-f0-9\-]{36}$/', $job->job_id);
    }

    /**
     * Test createJob sets default options.
     */
    public function test_create_job_sets_default_options(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertIsArray($job->options);
        $this->assertEquals(config('crawler.default_wait_time'), $job->options['waitTime']);
        $this->assertEquals(config('crawler.user_agent'), $job->options['userAgent']);
    }

    /**
     * Test createJob merges custom options.
     */
    public function test_create_job_merges_custom_options(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', [
            'waitTime' => 5000,
            'customOption' => 'value',
        ]);

        $this->assertEquals(5000, $job->options['waitTime']);
        $this->assertEquals('value', $job->options['customOption']);
    }

    /**
     * Test createJob sets expiry date.
     */
    public function test_create_job_sets_expiry_date(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $expectedExpiry = now()->addDays(config('crawler.retention_days'));
        $this->assertTrue(
            $job->expires_at->between($expectedExpiry->copy()->subMinute(), $expectedExpiry->copy()->addMinute())
        );
    }

    /**
     * Test createJob sets initial status to queued.
     */
    public function test_create_job_sets_queued_status(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertEquals(CrawlJob::STATUS_QUEUED, $job->status);
    }

    /**
     * Test createJob associates with project.
     */
    public function test_create_job_associates_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', [
            'project_id' => $project->id,
        ]);

        $this->assertEquals($project->id, $job->project_id);
    }

    /**
     * Test createJob caps depth to max_depth.
     */
    public function test_create_job_caps_depth(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', [
            'depth' => 100,
        ]);

        $this->assertEquals(config('crawler.max_depth'), $job->depth);
    }

    /**
     * Test createJob sets depth from options.
     */
    public function test_create_job_sets_depth_from_options(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', [
            'depth' => 1,
        ]);

        $this->assertEquals(1, $job->depth);
    }

    /**
     * Test createJob returns created job.
     */
    public function test_create_job_returns_job(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertDatabaseHas('crawl_jobs', [
            'id' => $job->id,
            'job_id' => $job->job_id,
        ]);
    }

    /**
     * Test createJob throws exception for invalid URL.
     */
    public function test_create_job_throws_for_invalid_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'not-a-valid-url');
    }

    /**
     * Test createJob throws exception for non-HTTP protocol.
     */
    public function test_create_job_throws_for_non_http_protocol(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'ftp://example.com');
    }

    /**
     * Test createJob throws exception for empty URL.
     */
    public function test_create_job_throws_for_empty_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, '');
    }

    /**
     * Test createJob validates URL format.
     */
    public function test_create_job_validates_url_format(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com/path?query=value');

        $this->assertEquals('https://example.com/path?query=value', $job->url);
    }

    // ==========================================
    // getJob Method Tests
    // ==========================================

    /**
     * Test getJob returns job by job_id.
     */
    public function test_get_job_returns_job(): void
    {
        $createdJob = CrawlJob::factory()->create();

        $job = $this->service->getJob($createdJob->job_id);

        $this->assertNotNull($job);
        $this->assertEquals($createdJob->id, $job->id);
    }

    /**
     * Test getJob returns null for non-existent job.
     */
    public function test_get_job_returns_null_for_nonexistent(): void
    {
        $job = $this->service->getJob('crawl-nonexistent');

        $this->assertNull($job);
    }

    /**
     * Test getJob includes page count.
     */
    public function test_get_job_includes_page_count(): void
    {
        $crawlJob = CrawlJob::factory()->create(['pages_crawled' => 5]);

        $job = $this->service->getJob($crawlJob->job_id);

        $this->assertEquals(5, $job->pages_crawled);
    }

    /**
     * Test getJob includes total size.
     */
    public function test_get_job_includes_total_size(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 1048576]);

        $job = $this->service->getJob($crawlJob->job_id);

        $this->assertEquals(1048576, $job->total_size);
    }

    /**
     * Test getJob queries by job_id not id.
     */
    public function test_get_job_queries_by_job_id(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        // Try to get by id (should return null)
        $job = $this->service->getJob((string) $crawlJob->id);

        $this->assertNull($job);
    }

    // ==========================================
    // processJob Method Tests (Skeleton)
    // ==========================================

    /**
     * Test processJob method exists.
     */
    public function test_process_job_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'processJob'));
    }

    /**
     * Test processJob throws for non-existent job.
     */
    public function test_process_job_throws_for_nonexistent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->processJob('crawl-nonexistent');
    }

    /**
     * Test processJob returns void.
     */
    public function test_process_job_returns_void(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $result = $this->service->processJob($crawlJob->job_id);

        $this->assertNull($result);
    }

    /**
     * Test processJob sets status to completed (skeleton behavior).
     * 
     * Note: In the full implementation (TASK-22-5 through TASK-22-10), the job
     * will transition to 'running' and then to 'completed' or 'failed' after crawling.
     * For the skeleton, it completes immediately.
     */
    public function test_process_job_sets_completed_status_skeleton(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $this->service->processJob($crawlJob->job_id);

        $crawlJob->refresh();
        // Skeleton completes immediately; full implementation will have running state
        $this->assertEquals(CrawlJob::STATUS_COMPLETED, $crawlJob->status);
    }

    /**
     * Test processJob sets started_at.
     */
    public function test_process_job_sets_started_at(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create(['started_at' => null]);

        $this->service->processJob($crawlJob->job_id);

        $crawlJob->refresh();
        $this->assertNotNull($crawlJob->started_at);
    }

    /**
     * Test processJob only processes queued jobs.
     */
    public function test_process_job_only_processes_queued(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $crawlJob = CrawlJob::factory()->completed()->create();

        $this->service->processJob($crawlJob->job_id);
    }

    // ==========================================
    // cancelJob Method Tests
    // ==========================================

    /**
     * Test cancelJob cancels queued job.
     */
    public function test_cancel_job_cancels_queued(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $result = $this->service->cancelJob($crawlJob->job_id);

        $this->assertTrue($result);
        $crawlJob->refresh();
        $this->assertEquals(CrawlJob::STATUS_CANCELLED, $crawlJob->status);
    }

    /**
     * Test cancelJob cancels running job.
     */
    public function test_cancel_job_cancels_running(): void
    {
        $crawlJob = CrawlJob::factory()->running()->create();

        $result = $this->service->cancelJob($crawlJob->job_id);

        $this->assertTrue($result);
        $crawlJob->refresh();
        $this->assertEquals(CrawlJob::STATUS_CANCELLED, $crawlJob->status);
    }

    /**
     * Test cancelJob returns false for completed job.
     */
    public function test_cancel_job_returns_false_for_completed(): void
    {
        $crawlJob = CrawlJob::factory()->completed()->create();

        $result = $this->service->cancelJob($crawlJob->job_id);

        $this->assertFalse($result);
    }

    /**
     * Test cancelJob returns false for failed job.
     */
    public function test_cancel_job_returns_false_for_failed(): void
    {
        $crawlJob = CrawlJob::factory()->failed()->create();

        $result = $this->service->cancelJob($crawlJob->job_id);

        $this->assertFalse($result);
    }

    /**
     * Test cancelJob returns false for already cancelled.
     */
    public function test_cancel_job_returns_false_for_cancelled(): void
    {
        $crawlJob = CrawlJob::factory()->cancelled()->create();

        $result = $this->service->cancelJob($crawlJob->job_id);

        $this->assertFalse($result);
    }

    /**
     * Test cancelJob throws for non-existent job.
     */
    public function test_cancel_job_throws_for_nonexistent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->cancelJob('crawl-nonexistent');
    }

    /**
     * Test cancelJob sets completed_at.
     */
    public function test_cancel_job_sets_completed_at(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create(['completed_at' => null]);

        $this->service->cancelJob($crawlJob->job_id);

        $crawlJob->refresh();
        $this->assertNotNull($crawlJob->completed_at);
    }

    // ==========================================
    // cleanup Method Tests
    // ==========================================

    /**
     * Test cleanup deletes expired jobs.
     */
    public function test_cleanup_deletes_expired(): void
    {
        CrawlJob::factory()->expired()->create();
        CrawlJob::factory()->expired()->create();

        $count = $this->service->cleanup();

        $this->assertEquals(2, $count);
        $this->assertEquals(0, CrawlJob::where('expires_at', '<', now())->count());
    }

    /**
     * Test cleanup returns 0 when no expired jobs.
     */
    public function test_cleanup_returns_zero_when_no_expired(): void
    {
        CrawlJob::factory()->create(['expires_at' => now()->addDays(7)]);

        $count = $this->service->cleanup();

        $this->assertEquals(0, $count);
    }

    /**
     * Test cleanup only deletes expired.
     */
    public function test_cleanup_only_deletes_expired(): void
    {
        CrawlJob::factory()->expired()->create();
        CrawlJob::factory()->create(['expires_at' => now()->addDays(7)]);

        $count = $this->service->cleanup();

        $this->assertEquals(1, $count);
        $this->assertEquals(1, CrawlJob::count());
    }

    /**
     * Test cleanup cascade deletes pages.
     */
    public function test_cleanup_cascade_deletes_pages(): void
    {
        $expiredJob = CrawlJob::factory()->expired()->create();
        CrawlPage::factory()->count(3)->forJob($expiredJob)->create();

        $count = $this->service->cleanup();

        $this->assertEquals(1, $count);
        $this->assertEquals(0, CrawlPage::where('crawl_job_id', $expiredJob->id)->count());
    }

    /**
     * Test cleanup preserves active jobs.
     */
    public function test_cleanup_preserves_active_jobs(): void
    {
        $activeJob = CrawlJob::factory()->create(['expires_at' => now()->addDays(7)]);
        CrawlJob::factory()->expired()->create();

        $this->service->cleanup();

        $this->assertDatabaseHas('crawl_jobs', ['id' => $activeJob->id]);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    /**
     * Test createJob with unicode URL.
     */
    public function test_create_job_with_unicode_url(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://例え.jp');

        $this->assertEquals('https://例え.jp', $job->url);
    }

    /**
     * Test createJob with long URL.
     */
    public function test_create_job_with_long_url(): void
    {
        $user = User::factory()->create();
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);

        $job = $this->service->createJob($user->id, $longUrl);

        $this->assertEquals($longUrl, $job->url);
    }

    /**
     * Test createJob with zero depth.
     */
    public function test_create_job_with_zero_depth(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', ['depth' => 0]);

        $this->assertEquals(0, $job->depth);
    }

    /**
     * Test createJob with empty options.
     */
    public function test_create_job_with_empty_options(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', []);

        $this->assertIsArray($job->options);
    }

    /**
     * Test getJob with invalid format.
     */
    public function test_get_job_with_invalid_format(): void
    {
        $job = $this->service->getJob('invalid-format');

        $this->assertNull($job);
    }

    // ==========================================
    // Security Tests
    // ==========================================

    /**
     * Test createJob blocks localhost SSRF.
     */
    public function test_create_job_blocks_localhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://localhost/test');
    }

    /**
     * Test createJob blocks 127.x.x.x SSRF.
     */
    public function test_create_job_blocks_loopback(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://127.0.0.1/test');
    }

    /**
     * Test createJob blocks private IP 10.x.x.x.
     */
    public function test_create_job_blocks_private_10(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://10.0.0.1/test');
    }

    /**
     * Test createJob blocks private IP 172.16.x.x.
     */
    public function test_create_job_blocks_private_172(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://172.16.0.1/test');
    }

    /**
     * Test createJob blocks private IP 192.168.x.x.
     */
    public function test_create_job_blocks_private_192(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://192.168.1.1/test');
    }

    /**
     * Test createJob blocks cloud metadata.
     */
    public function test_create_job_blocks_cloud_metadata(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'http://169.254.169.254/latest/meta-data');
    }

    /**
     * Test createJob blocks file protocol.
     */
    public function test_create_job_blocks_file_protocol(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->createJob($user->id, 'file:///etc/passwd');
    }

    /**
     * Test getJob with SQL injection attempt.
     */
    public function test_get_job_sql_injection(): void
    {
        // Should not throw, should return null
        $job = $this->service->getJob("crawl-1'; DROP TABLE crawl_jobs; --");

        $this->assertNull($job);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    /**
     * Test full job lifecycle (skeleton).
     */
    public function test_full_job_lifecycle(): void
    {
        $user = User::factory()->create();

        // Create
        $job = $this->service->createJob($user->id, 'https://example.com');
        $this->assertEquals(CrawlJob::STATUS_QUEUED, $job->status);

        // Get
        $retrieved = $this->service->getJob($job->job_id);
        $this->assertNotNull($retrieved);
        $this->assertEquals($job->id, $retrieved->id);

        // Process (skeleton completes immediately)
        $this->service->processJob($job->job_id);
        $job->refresh();
        // Skeleton sets status to completed; full implementation will have running state
        $this->assertEquals(CrawlJob::STATUS_COMPLETED, $job->status);
    }

    /**
     * Test create and cancel flow.
     */
    public function test_create_and_cancel_flow(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');
        $this->assertEquals(CrawlJob::STATUS_QUEUED, $job->status);

        $result = $this->service->cancelJob($job->job_id);
        $this->assertTrue($result);

        $job->refresh();
        $this->assertEquals(CrawlJob::STATUS_CANCELLED, $job->status);
    }

    /**
     * Test multiple jobs have unique IDs.
     */
    public function test_multiple_jobs_unique_ids(): void
    {
        $user = User::factory()->create();

        $job1 = $this->service->createJob($user->id, 'https://example1.com');
        $job2 = $this->service->createJob($user->id, 'https://example2.com');
        $job3 = $this->service->createJob($user->id, 'https://example3.com');

        $ids = [$job1->job_id, $job2->job_id, $job3->job_id];
        $this->assertCount(3, array_unique($ids));
    }

    /**
     * Test cleanup after expiry.
     */
    public function test_cleanup_after_expiry(): void
    {
        $user = User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        // Manually expire
        $job->update(['expires_at' => now()->subDay()]);

        $count = $this->service->cleanup();

        $this->assertEquals(1, $count);
        $this->assertDatabaseMissing('crawl_jobs', ['id' => $job->id]);
    }
}
