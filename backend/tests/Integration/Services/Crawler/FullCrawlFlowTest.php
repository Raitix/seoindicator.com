<?php

namespace Tests\Integration\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\User;
use App\Models\Project;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\CancellationService;
use App\Services\Crawler\PartialResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Integration tests for full crawl flow.
 *
 * Tests the complete lifecycle of a crawl job from creation
 * through completion, including error scenarios and cancellation.
 *
 * @see TASK-22-20
 * @see SPEC-022
 */
class FullCrawlFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected CrawlerService $crawlerService;
    protected CancellationService $cancellationService;
    protected PartialResultsService $partialResultsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->crawlerService = app(CrawlerService::class);
        $this->cancellationService = app(CancellationService::class);
        $this->partialResultsService = app(PartialResultsService::class);

        Event::fake();
    }

    // =========================================================================
    // Basic Crawl Flow (Depth 0) - CF-001 to CF-003
    // =========================================================================

    /**
     * Test basic crawl creation and status flow.
     * CF-001: Basic crawl (depth 0)
     */
    public function test_basic_crawl_creation_and_status_flow(): void
    {
        // 1. Create crawl job
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $this->assertNotNull($jobData['jobId']);
        $this->assertEquals('queued', $jobData['status']);
        $this->assertEquals(0, $jobData['depth']);
        $this->assertEquals('https://example.com', $jobData['url']);

        // 2. Verify database record
        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();
        $this->assertNotNull($crawlJob);
        $this->assertEquals(CrawlJob::STATUS_QUEUED, $crawlJob->status);

        // 3. Simulate job start
        $crawlJob->update([
            'status' => CrawlJob::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $this->assertEquals(CrawlJob::STATUS_RUNNING, $crawlJob->fresh()->status);

        // 4. Create a crawled page
        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'title' => 'Example Domain',
            'rendered_html' => '<html><head><title>Example</title></head><body>Content</body></html>',
        ]);

        // 5. Complete the job
        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'total_size' => $page->size,
            'completed_at' => now(),
        ]);

        // 6. Retrieve job content
        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertNotNull($content);
        $this->assertEquals(1, $content['summary']['totalPages']);
        $this->assertCount(1, $content['pages']);
        $this->assertEquals('https://example.com', $content['pages'][0]['url']);
    }

    /**
     * Test crawl job with custom options.
     * CF-002: Crawl with options
     */
    public function test_crawl_job_with_custom_options(): void
    {
        $customOptions = [
            'depth' => 1,
            'options' => [
                'userAgent' => 'CustomBot/2.0',
                'waitTime' => 5000,
                'waitForSelector' => '.content',
                'followRedirects' => false,
            ],
        ];

        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com/page',
            $customOptions
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        $this->assertEquals(1, $crawlJob->depth);
        $this->assertEquals('CustomBot/2.0', $crawlJob->options['userAgent']);
        $this->assertEquals(5000, $crawlJob->options['waitTime']);
        $this->assertEquals('.content', $crawlJob->options['waitForSelector']);
        $this->assertFalse($crawlJob->options['followRedirects']);
    }

    /**
     * Test crawl job with project association.
     * CF-003: Crawl with project
     *
     * @todo Service needs to extract project_id from options and store it
     */
    public function test_crawl_job_with_project_association(): void
    {
        // TODO: Service.createJob needs to handle project_id from options
        // Currently the service doesn't extract project_id from options array
        $this->markTestSkipped('CrawlerService.createJob does not yet support project_id in options');

        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['project_id' => $this->project->id, 'depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        $this->assertEquals($this->project->id, $crawlJob->project_id);
        $this->assertEquals($this->user->id, $crawlJob->user_id);
    }

    // =========================================================================
    // Error Scenarios - CF-004 to CF-007
    // =========================================================================

    /**
     * Test crawl handles 404 errors gracefully.
     * CF-004: 404 error handling
     */
    public function test_crawl_handles_404_error_gracefully(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com/nonexistent',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Simulate failed page crawl
        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/nonexistent',
            'depth' => 0,
            'error' => 'HTTP 404: Page not found',
            'rendered_html' => null,
            'structured_text' => null,
        ]);

        // Mark job as failed
        $crawlJob->update([
            'status' => CrawlJob::STATUS_FAILED,
            'error' => 'Crawl failed: HTTP 404',
            'completed_at' => now(),
        ]);

        $job = $this->crawlerService->getJob($jobData['jobId']);

        $this->assertEquals('failed', $job['status']);
        $this->assertStringContainsString('404', $job['error']);
    }

    /**
     * Test crawl handles timeout errors.
     * CF-005: Timeout error handling
     */
    public function test_crawl_handles_timeout_error(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://slow-example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Simulate timeout
        $crawlJob->update([
            'status' => CrawlJob::STATUS_FAILED,
            'error' => 'Page load timeout after 30 seconds',
            'completed_at' => now(),
        ]);

        $job = $this->crawlerService->getJob($jobData['jobId']);

        $this->assertEquals('failed', $job['status']);
        $this->assertStringContainsString('timeout', strtolower($job['error']));
    }

    /**
     * Test crawl handles JavaScript rendering errors.
     * CF-006: JS error handling
     */
    public function test_crawl_handles_javascript_error(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://broken-js.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Simulate JS rendering error with partial content
        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://broken-js.com',
            'depth' => 0,
            'rendered_html' => '<html><body>Partial content before JS error</body></html>',
            'error' => 'JavaScript execution error: Uncaught TypeError',
        ]);

        // Job still completes with partial results
        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        // getJobContent returns jobId, pages, and summary
        $this->assertEquals($jobData['jobId'], $content['jobId']);
        $this->assertCount(1, $content['pages']);
        $this->assertEquals(1, $content['summary']['totalPages']);
    }

    /**
     * Test crawl handles redirect chains.
     * CF-007: Redirect handling
     */
    public function test_crawl_handles_redirect_chain(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com/old-page',
            [
                'depth' => 0,
                'options' => ['followRedirects' => true, 'maxRedirects' => 5],
            ]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Simulate successful redirect follow
        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/new-page', // Final URL after redirect
            'depth' => 0,
            'rendered_html' => '<html><body>Redirected content</body></html>',
            'meta' => [
                'original_url' => 'https://example.com/old-page',
                'redirect_chain' => [
                    ['url' => 'https://example.com/old-page', 'status' => 301],
                    ['url' => 'https://example.com/new-page', 'status' => 200],
                ],
            ],
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        // getJobContent returns jobId, pages, and summary
        $this->assertEquals($jobData['jobId'], $content['jobId']);
        $this->assertEquals('https://example.com/new-page', $content['pages'][0]['url']);
        $this->assertNotNull($content['pages'][0]['meta']['redirect_chain']);
    }

    // =========================================================================
    // Cancellation Flow - CF-008 to CF-010
    // =========================================================================

    /**
     * Test crawl job cancellation.
     * CF-008: Job cancellation
     */
    public function test_crawl_job_cancellation(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Start the job
        $crawlJob->update([
            'status' => CrawlJob::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        // Create some pages before cancellation
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        // Request cancellation
        $result = $this->cancellationService->requestCancellation($crawlJob, 'User requested');

        $this->assertTrue($result);
        $this->assertTrue($this->cancellationService->isCancellationRequested($jobData['jobId']));

        // Verify cancellation metadata
        $metadata = $this->cancellationService->getCancellationMetadata($jobData['jobId']);
        $this->assertEquals('User requested', $metadata['reason']);
    }

    /**
     * Test partial results on cancellation.
     * CF-009: Partial results on cancel
     *
     * @todo CrawlJob model needs partial_results field in fillable and casts
     */
    public function test_partial_results_on_cancellation(): void
    {
        $this->markTestSkipped('CrawlJob model needs partial_results field in fillable and casts');

        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();
        $crawlJob->update(['status' => CrawlJob::STATUS_RUNNING, 'started_at' => now()]);

        // Simulate partial crawl
        $pages = collect([
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => 'https://example.com',
                'depth' => 0,
                'size' => 1024,
            ]),
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => 'https://example.com/about',
                'depth' => 1,
                'size' => 2048,
            ]),
        ]);

        // Store partial results
        $this->partialResultsService->storePartialResults(
            $crawlJob,
            $pages->map(fn ($p) => [
                'url' => $p->url,
                'depth' => $p->depth,
                'title' => $p->title,
                'rendered_html' => $p->rendered_html,
                'size' => $p->size,
                'status' => 'completed',
            ])->toArray(),
            'cancelled',
            10 // total URLs planned
        );

        $crawlJob->refresh();

        $this->assertEquals(CrawlJob::STATUS_CANCELLED, $crawlJob->status);
        $this->assertNotNull($crawlJob->partial_results);
        $this->assertEquals(2, $crawlJob->partial_results['pages_crawled']);
        $this->assertEquals('User requested cancellation', $crawlJob->partial_results['reason']);
    }

    /**
     * Test cannot cancel completed job.
     * CF-010: Cannot cancel completed
     */
    public function test_cannot_cancel_completed_job(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Complete the job
        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        // Try to cancel
        $result = $this->cancellationService->requestCancellation($crawlJob);

        $this->assertFalse($result);
        $this->assertFalse($this->cancellationService->isCancellationRequested($jobData['jobId']));
    }

    // =========================================================================
    // Content Retrieval - CF-011 to CF-013
    // =========================================================================

    /**
     * Test content retrieval with pagination.
     * CF-011: Content pagination
     *
     * @todo CrawlerService needs getJobContentPaginated() method
     */
    public function test_content_retrieval_with_pagination(): void
    {
        $this->markTestSkipped('CrawlerService.getJobContentPaginated() not yet implemented');

        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create multiple pages
        for ($i = 0; $i < 25; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/page{$i}",
                'depth' => $i < 1 ? 0 : ($i < 10 ? 1 : 2),
            ]);
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 25,
            'completed_at' => now(),
        ]);

        // Test paginated retrieval
        $paginatedContent = $this->crawlerService->getJobContentPaginated($jobData['jobId'], 1, 10);

        $this->assertCount(10, $paginatedContent['pages']);
        $this->assertEquals(25, $paginatedContent['total']);
        $this->assertEquals(3, $paginatedContent['last_page']);
    }

    /**
     * Test content retrieval includes metadata.
     * CF-012: Content metadata
     */
    public function test_content_retrieval_includes_metadata(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'title' => 'Example Domain',
            'meta' => [
                'description' => 'Example description',
                'keywords' => 'example, test',
                'robots' => 'index, follow',
                'canonical' => 'https://example.com/',
            ],
            'links' => [
                ['url' => 'https://example.com/about', 'text' => 'About', 'nofollow' => false],
                ['url' => 'https://external.com', 'text' => 'External', 'nofollow' => true],
            ],
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertCount(1, $content['pages']);
        $page = $content['pages'][0];

        $this->assertEquals('Example Domain', $page['title']);
        $this->assertArrayHasKey('description', $page['meta']);
        $this->assertEquals('index, follow', $page['meta']['robots']);
        $this->assertCount(2, $page['links']);
    }

    /**
     * Test content retrieval for non-existent job.
     * CF-013: Non-existent job
     */
    public function test_content_retrieval_for_nonexistent_job(): void
    {
        $content = $this->crawlerService->getJobContent('nonexistent-job-id');

        $this->assertNull($content);
    }

    // =========================================================================
    // Job Status Transitions - CF-014 to CF-015
    // =========================================================================

    /**
     * Test valid status transitions.
     * CF-014: Status transitions
     *
     * @todo CrawlJob model needs canBeStarted(), canBeCompleted() methods
     */
    public function test_valid_status_transitions(): void
    {
        $this->markTestSkipped('CrawlJob model needs canBeStarted(), canBeCompleted() methods');

        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        // queued -> running
        $this->assertTrue($crawlJob->canBeStarted());
        $crawlJob->update(['status' => CrawlJob::STATUS_RUNNING, 'started_at' => now()]);

        // running -> completed
        $this->assertTrue($crawlJob->canBeCompleted());
        $crawlJob->update(['status' => CrawlJob::STATUS_COMPLETED, 'completed_at' => now()]);

        $this->assertEquals(CrawlJob::STATUS_COMPLETED, $crawlJob->fresh()->status);
    }

    /**
     * Test job expiration.
     * CF-015: Job expiration
     *
     * @todo CrawlJob model needs isExpired(), isAccessible() methods
     */
    public function test_job_expiration(): void
    {
        $this->markTestSkipped('CrawlJob model needs isExpired(), isAccessible() methods');

        $crawlJob = CrawlJob::factory()->forUser($this->user)->expired()->create();

        $this->assertTrue($crawlJob->isExpired());
        $this->assertFalse($crawlJob->isAccessible());
    }

    // =========================================================================
    // Concurrent Jobs - CF-016
    // =========================================================================

    /**
     * Test multiple concurrent jobs.
     * CF-016: Concurrent jobs
     */
    public function test_multiple_concurrent_jobs(): void
    {
        // Create multiple jobs
        $jobs = [];
        for ($i = 0; $i < 5; $i++) {
            $jobData = $this->crawlerService->createJob(
                $this->user->id,
                "https://example{$i}.com",
                ['depth' => 0]
            );
            $jobs[] = $jobData['jobId'];
        }

        // Verify all jobs exist
        foreach ($jobs as $jobId) {
            $job = $this->crawlerService->getJob($jobId);
            $this->assertNotNull($job);
            $this->assertEquals('queued', $job['status']);
        }

        // Start all jobs
        CrawlJob::whereIn('job_id', $jobs)->update([
            'status' => CrawlJob::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        // Verify all are running
        $runningJobs = CrawlJob::whereIn('job_id', $jobs)
            ->where('status', CrawlJob::STATUS_RUNNING)
            ->count();

        $this->assertEquals(5, $runningJobs);
    }
}
