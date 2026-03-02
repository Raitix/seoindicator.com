<?php

namespace Tests\Unit\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CrawlerService.
 *
 * @see TASK-22-18
 * @see SPEC-022
 */
class CrawlerServiceTest extends TestCase
{
    use RefreshDatabase, CrawlerTestHelper;

    protected CrawlerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CrawlerService::class);
    }

    // =========================================================================
    // createJob Tests
    // =========================================================================

    /** @test */
    public function create_job_generates_unique_job_id(): void
    {
        $user = \App\Models\User::factory()->create();

        $job1 = $this->service->createJob($user->id, 'https://example.com');
        $job2 = $this->service->createJob($user->id, 'https://example.org');

        $this->assertNotEquals($job1['jobId'], $job2['jobId']);
        $this->assertStringStartsWith('crawl-', $job1['jobId']);
        $this->assertMatchesRegularExpression('/^crawl-[0-9a-f-]{36}$/', $job1['jobId']);
    }

    /** @test */
    public function create_job_sets_default_options(): void
    {
        $user = \App\Models\User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com');

        $this->assertEquals('queued', $job['status']);
        $this->assertEquals(0, $job['depth']);
        $this->assertEquals('https://example.com', $job['url']);
        $this->assertArrayHasKey('estimatedTime', $job);
        $this->assertEquals('Crawl job queued for processing', $job['message']);
    }

    /** @test */
    public function create_job_accepts_custom_options(): void
    {
        $user = \App\Models\User::factory()->create();

        $job = $this->service->createJob($user->id, 'https://example.com', [
            'depth' => 2,
            'options' => [
                'userAgent' => 'CustomBot/1.0',
                'waitTime' => 5000,
            ],
        ]);

        $this->assertEquals(2, $job['depth']);

        // Verify database record
        $crawlJob = CrawlJob::where('job_id', $job['jobId'])->first();
        $this->assertEquals(2, $crawlJob->depth);
        $this->assertEquals([
            'userAgent' => 'CustomBot/1.0',
            'waitTime' => 5000,
        ], $crawlJob->options);
    }

    /** @test */
    public function create_job_calculates_estimated_time(): void
    {
        $user = \App\Models\User::factory()->create();

        $job0 = $this->service->createJob($user->id, 'https://example.com', ['depth' => 0]);
        $job1 = $this->service->createJob($user->id, 'https://example.org', ['depth' => 1]);
        $job2 = $this->service->createJob($user->id, 'https://example.net', ['depth' => 2]);

        $this->assertEquals('30s', $job0['estimatedTime']);
        $this->assertEquals('45s', $job1['estimatedTime']);
        $this->assertEquals('60s', $job2['estimatedTime']);
    }

    /** @test */
    public function create_job_stores_in_database(): void
    {
        $user = \App\Models\User::factory()->create();

        $jobData = $this->service->createJob($user->id, 'https://example.com');

        $this->assertDatabaseHas('crawl_jobs', [
            'job_id' => $jobData['jobId'],
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'queued',
        ]);
    }

    // =========================================================================
    // getJob Tests
    // =========================================================================

    /** @test */
    public function get_job_returns_correct_job(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        $job = $this->service->getJob($createdJob['jobId']);

        $this->assertNotNull($job);
        $this->assertEquals($createdJob['jobId'], $job['jobId']);
        $this->assertEquals('https://example.com', $job['url']);
    }

    /** @test */
    public function get_job_returns_null_for_non_existent(): void
    {
        $job = $this->service->getJob('crawl-nonexistent-uuid');

        $this->assertNull($job);
    }

    /** @test */
    public function get_job_includes_running_job_progress(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to running state
        CrawlJob::where('job_id', $createdJob['jobId'])->update([
            'status' => 'running',
            'pages_crawled' => 5,
        ]);

        $job = $this->service->getJob($createdJob['jobId']);

        $this->assertEquals('running', $job['status']);
        $this->assertEquals(5, $job['pagesCrawled']);
    }

    /** @test */
    public function get_job_includes_completed_job_details(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to completed state
        CrawlJob::where('job_id', $createdJob['jobId'])->update([
            'status' => 'completed',
            'pages_crawled' => 10,
            'total_size' => 50000,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $job = $this->service->getJob($createdJob['jobId']);

        $this->assertEquals('completed', $job['status']);
        $this->assertEquals(10, $job['pagesCrawled']);
        $this->assertEquals(50000, $job['totalSize']);
        $this->assertArrayHasKey('duration', $job);
        $this->assertArrayHasKey('contentUrl', $job);
    }

    /** @test */
    public function get_job_includes_error_for_failed_job(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to failed state
        CrawlJob::where('job_id', $createdJob['jobId'])->update([
            'status' => 'failed',
            'error' => 'Connection timeout',
        ]);

        $job = $this->service->getJob($createdJob['jobId']);

        $this->assertEquals('failed', $job['status']);
        $this->assertEquals('Connection timeout', $job['error']);
    }

    // =========================================================================
    // cancelJob Tests
    // =========================================================================

    /** @test */
    public function cancel_job_works_for_queued_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        $result = $this->service->cancelJob($createdJob['jobId']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('crawl_jobs', [
            'job_id' => $createdJob['jobId'],
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cancel_job_works_for_running_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to running state
        CrawlJob::where('job_id', $createdJob['jobId'])->update(['status' => 'running']);

        $result = $this->service->cancelJob($createdJob['jobId']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('crawl_jobs', [
            'job_id' => $createdJob['jobId'],
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cancel_job_fails_for_completed_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to completed state
        CrawlJob::where('job_id', $createdJob['jobId'])->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $result = $this->service->cancelJob($createdJob['jobId']);

        $this->assertFalse($result);
        $this->assertDatabaseHas('crawl_jobs', [
            'job_id' => $createdJob['jobId'],
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function cancel_job_fails_for_failed_jobs(): void
    {
        $user = \App\Models\User::factory()->create();
        $createdJob = $this->service->createJob($user->id, 'https://example.com');

        // Update to failed state
        CrawlJob::where('job_id', $createdJob['jobId'])->update([
            'status' => 'failed',
            'error' => 'Test error',
        ]);

        $result = $this->service->cancelJob($createdJob['jobId']);

        $this->assertFalse($result);
    }

    /** @test */
    public function cancel_job_returns_null_for_non_existent(): void
    {
        $result = $this->service->cancelJob('crawl-nonexistent-uuid');

        $this->assertNull($result);
    }

    // =========================================================================
    // getJobContent Tests
    // =========================================================================

    /** @test */
    public function get_job_content_returns_null_for_non_existent(): void
    {
        $content = $this->service->getJobContent('crawl-nonexistent-uuid');

        $this->assertNull($content);
    }

    /** @test */
    public function get_job_content_returns_pages(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        CrawlPage::factory()->create([
            'crawl_job_id' => $job->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        CrawlPage::factory()->create([
            'crawl_job_id' => $job->id,
            'url' => 'https://example.com/page',
            'depth' => 1,
        ]);

        $content = $this->service->getJobContent($job->job_id);

        $this->assertNotNull($content);
        $this->assertCount(2, $content['pages']);
        $this->assertEquals(2, $content['summary']['totalPages']);
    }

    /** @test */
    public function get_job_content_includes_summary(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        CrawlPage::factory()->create([
            'crawl_job_id' => $job->id,
            'depth' => 0,
            'size' => 1000,
        ]);

        CrawlPage::factory()->create([
            'crawl_job_id' => $job->id,
            'depth' => 1,
            'size' => 2000,
        ]);

        $content = $this->service->getJobContent($job->job_id);

        $this->assertEquals(3000, $content['summary']['totalSize']);
        $this->assertEquals([0 => 1, 1 => 1], $content['summary']['depthDistribution']);
    }

    // =========================================================================
    // listJobs Tests
    // =========================================================================

    /** @test */
    public function list_jobs_returns_user_jobs(): void
    {
        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();

        CrawlJob::factory()->count(3)->create(['user_id' => $user1->id]);
        CrawlJob::factory()->count(2)->create(['user_id' => $user2->id]);

        $jobs = $this->service->listJobs($user1->id);

        $this->assertCount(3, $jobs->items());
    }

    /** @test */
    public function list_jobs_filters_by_status(): void
    {
        $user = \App\Models\User::factory()->create();

        CrawlJob::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        CrawlJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'queued',
        ]);

        $jobs = $this->service->listJobs($user->id, ['status' => 'completed']);

        $this->assertCount(2, $jobs->items());
    }

    /** @test */
    public function list_jobs_paginates_results(): void
    {
        $user = \App\Models\User::factory()->create();
        CrawlJob::factory()->count(25)->create(['user_id' => $user->id]);

        $page1 = $this->service->listJobs($user->id, ['perPage' => 10, 'page' => 1]);
        $page2 = $this->service->listJobs($user->id, ['perPage' => 10, 'page' => 2]);

        $this->assertCount(10, $page1->items());
        $this->assertCount(10, $page2->items());
        $this->assertEquals(25, $page1->total());
    }

    /** @test */
    public function list_jobs_sorts_by_created_at_desc_by_default(): void
    {
        $user = \App\Models\User::factory()->create();

        $job1 = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $job2 = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $jobs = $this->service->listJobs($user->id);

        // Most recent first
        $this->assertEquals($job2->job_id, $jobs->items()[0]->job_id);
        $this->assertEquals($job1->job_id, $jobs->items()[1]->job_id);
    }

    // =========================================================================
    // Worker Integration Tests
    // =========================================================================

    /** @test */
    public function update_status_sets_running_timestamp(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create(['user_id' => $user->id, 'status' => 'queued']);

        $result = $this->service->updateStatus($job->job_id, 'running');

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function update_status_sets_completion_timestamp(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create(['user_id' => $user->id, 'status' => 'running']);

        $result = $this->service->updateStatus($job->job_id, 'completed');

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function store_page_result_creates_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create(['user_id' => $user->id]);

        $page = $this->service->storePageResult($job->job_id, [
            'url' => 'https://example.com/page',
            'depth' => 1,
            'title' => 'Test Page',
            'html' => '<html><body>Test</body></html>',
            'text' => 'Test content',
            'meta' => ['description' => 'Test'],
            'links' => [],
            'size' => 500,
        ]);

        $this->assertNotNull($page);
        $this->assertEquals('https://example.com/page', $page->url);
        $this->assertEquals(1, $page->depth);
    }

    /** @test */
    public function mark_job_failed_sets_error_and_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create(['user_id' => $user->id, 'status' => 'running']);

        $result = $this->service->markJobFailed($job->job_id, 'Connection timeout');

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Connection timeout', $job->error);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function increment_pages_crawled_increases_count(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'pages_crawled' => 5,
        ]);

        $result = $this->service->incrementPagesCrawled($job->job_id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals(6, $job->pages_crawled);
    }

    /** @test */
    public function add_total_size_increases_total(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'total_size' => 1000,
        ]);

        $result = $this->service->addTotalSize($job->job_id, 500);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals(1500, $job->total_size);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /** @test */
    public function create_job_handles_long_url(): void
    {
        $user = \App\Models\User::factory()->create();
        $longUrl = 'https://example.com/' . str_repeat('a', 400);

        $job = $this->service->createJob($user->id, $longUrl);

        $this->assertEquals($longUrl, $job['url']);
    }

    /** @test */
    public function create_job_handles_unicode_url(): void
    {
        $user = \App\Models\User::factory()->create();
        $unicodeUrl = 'https://example.com/日本語';

        $job = $this->service->createJob($user->id, $unicodeUrl);

        $this->assertEquals($unicodeUrl, $job['url']);
    }

    /** @test */
    public function get_job_content_handles_empty_pages(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $content = $this->service->getJobContent($job->job_id);

        $this->assertNotNull($content);
        $this->assertCount(0, $content['pages']);
        $this->assertEquals(0, $content['summary']['totalPages']);
    }

    /** @test */
    public function mark_job_failed_truncates_long_error(): void
    {
        $user = \App\Models\User::factory()->create();
        $job = CrawlJob::factory()->create(['user_id' => $user->id, 'status' => 'running']);

        $longError = str_repeat('Error: ', 100000); // Very long error message

        $result = $this->service->markJobFailed($job->job_id, $longError);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('failed', $job->status);
        $this->assertLessThanOrEqual(65535, strlen($job->error));
    }
}
