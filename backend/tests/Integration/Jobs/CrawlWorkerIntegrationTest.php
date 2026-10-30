<?php

namespace Tests\Integration\Jobs;

use App\Jobs\CrawlWorker;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\User;
use App\Models\Project;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\CancellationService;
use App\Services\Crawler\PartialResultsService;
use App\Services\Crawler\TimeoutHandlerService;
use App\Services\PlaywrightRendererService;
use App\Services\UrlValidatorService;
use App\Exceptions\SSRFException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for CrawlWorker job processing.
 *
 * Tests queue processing, error handling, cancellation,
 * timeout handling, and partial results storage.
 *
 * @see TASK-22-20
 * @see TASK-22-15
 * @see TASK-22-17
 * @see SPEC-022
 */
class CrawlWorkerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Queue Processing - CW-001 to CW-005
    // =========================================================================

    /**
     * Test job is queued with correct parameters.
     * CW-001: Job queue creation
     */
    public function test_job_is_queued_with_correct_parameters(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'url' => 'https://example.com',
            'depth' => 1,
        ]);

        $job = new CrawlWorker($crawlJob->job_id);

        $this->assertEquals($crawlJob->job_id, $job->jobId);
        $this->assertEquals('crawler', $job->queue);
    }

    /**
     * Test job unique ID.
     * CW-002: Job unique ID
     */
    public function test_job_unique_id(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $job = new CrawlWorker($crawlJob->job_id);

        $this->assertEquals($crawlJob->job_id, $job->uniqueId());
    }

    /**
     * Test job tags include crawl and job ID.
     * CW-003: Job tags
     */
    public function test_job_tags_include_crawl_and_job_id(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $job = new CrawlWorker($crawlJob->job_id);
        $tags = $job->tags();

        $this->assertContains('crawl', $tags);
        $this->assertContains('job:' . $crawlJob->job_id, $tags);
    }

    /**
     * Test job timeout configuration.
     * CW-004: Job timeout config
     */
    public function test_job_timeout_configuration(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $job = new CrawlWorker($crawlJob->job_id);

        $this->assertGreaterThanOrEqual(60, $job->timeout);
        $this->assertEquals(1, $job->tries);
    }

    /**
     * Test job handles non-existent crawl job.
     * CW-005: Non-existent job handling
     */
    public function test_job_handles_non_existent_crawl_job(): void
    {
        $job = new CrawlWorker('nonexistent-job-id');

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('nonexistent-job-id')
            ->andReturn(null);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        // Should not throw, just return early
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }

    // =========================================================================
    // Job Processing - CW-006 to CW-011
    // =========================================================================

    /**
     * Test job updates status to running on start.
     * CW-006: Status update to running
     */
    public function test_job_updates_status_to_running_on_start(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with($crawlJob->job_id)
            ->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')
            ->with($crawlJob->job_id, CrawlJob::STATUS_RUNNING)
            ->once();

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => 'Content',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');
        $mockCrawler->shouldReceive('updateStatus')
            ->with($crawlJob->job_id, CrawlJob::STATUS_COMPLETED)
            ->once();

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job processes single page at depth 0.
     * CW-007: Single page processing
     */
    public function test_job_processes_single_page_at_depth_0(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0,
            'url' => 'https://example.com',
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')->once();
        $mockCrawler->shouldReceive('updateStats');
        $mockCrawler->shouldReceive('markJobFailed');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html><body>Content</body></html>',
            'text' => 'Content',
            'meta' => ['title' => 'Example'],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job marks completion after processing.
     * CW-008: Job completion
     */
    public function test_job_marks_completion_after_processing(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')
            ->with($crawlJob->job_id, CrawlJob::STATUS_RUNNING);
        $mockCrawler->shouldReceive('updateStatus')
            ->with($crawlJob->job_id, CrawlJob::STATUS_COMPLETED);
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 100,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job already in non-queued status is skipped.
     * CW-009: Skip non-queued jobs
     */
    public function test_job_already_in_non_queued_status_is_skipped(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->create([
            'status' => CrawlJob::STATUS_RUNNING,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        // Should NOT call updateStatus or process
        $mockCrawler->shouldNotReceive('updateStatus');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job stores page results correctly.
     * CW-010: Page result storage
     */
    public function test_job_stores_page_results_correctly(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0,
            'url' => 'https://example.com',
        ]);

        $pageResult = [
            'url' => 'https://example.com',
            'depth' => 0,
            'title' => 'Example Domain',
            'html' => '<html><body>Content</body></html>',
            'text' => 'Content',
            'meta' => ['title' => 'Example Domain'],
            'links' => [],
            'size' => 5000,
        ];

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')
            ->once()
            ->withArgs(function ($jobId, $data) use ($pageResult) {
                return $data['url'] === $pageResult['url'];
            });
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn($pageResult);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job updates statistics during processing.
     * CW-011: Stats update during processing
     */
    public function test_job_updates_statistics_during_processing(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats')
            ->once()
            ->withArgs(function ($jobId, $stats) {
                return isset($stats['pages_crawled']) && isset($stats['total_size']);
            });

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    // =========================================================================
    // Error Handling - CW-012 to CW-017
    // =========================================================================

    /**
     * Test job handles SSRF exception.
     * CW-012: SSRF exception handling
     */
    public function test_job_handles_ssrf_exception(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'url' => 'http://localhost',
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('markJobFailed')
            ->once()
            ->withArgs(function ($jobId, $error) {
                return str_contains($error, 'SSRF') || str_contains($error, 'blocked');
            });

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new SSRFException('URL blocked: localhost'));

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job handles generic exception.
     * CW-013: Generic exception handling
     */
    public function test_job_handles_generic_exception(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('markJobFailed')
            ->once();

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->andThrow(new \Exception('Browser failed to launch'));
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job handles page render failure.
     * CW-014: Page render failure
     */
    public function test_job_handles_page_render_failure(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')
            ->once()
            ->withArgs(function ($jobId, $data) {
                return isset($data['error']);
            });
        $mockCrawler->shouldReceive('updateStatus')
            ->with($crawlJob->job_id, CrawlJob::STATUS_COMPLETED);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')
            ->andThrow(new \Exception('Page timeout'));

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job failed method updates database.
     * CW-015: Failed method updates DB
     */
    public function test_job_failed_method_updates_database(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $job = new CrawlWorker($crawlJob->job_id);
        $job->failed(new \Exception('Test failure'));

        $crawlJob->refresh();

        $this->assertEquals(CrawlJob::STATUS_FAILED, $crawlJob->status);
        $this->assertNotNull($crawlJob->completed_at);
        $this->assertStringContainsString('Test failure', $crawlJob->error);
    }

    /**
     * Test job handles failed job with non-existent record.
     * CW-016: Failed job no record
     */
    public function test_job_handles_failed_job_with_non_existent_record(): void
    {
        $job = new CrawlWorker('nonexistent-job-id');

        // Should not throw
        $job->failed(new \Exception('Test failure'));

        $this->assertNull(CrawlJob::where('job_id', 'nonexistent-job-id')->first());
    }

    /**
     * Test browser cleanup on error.
     * CW-017: Browser cleanup on error
     */
    public function test_browser_cleanup_on_error(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('markJobFailed');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->once(); // Verify cleanup happens
        $mockRenderer->shouldReceive('renderPage')
            ->andThrow(new \Exception('Render failed'));

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    // =========================================================================
    // Timeout Handling - CW-018 to CW-022
    // =========================================================================

    /**
     * Test job respects timeout limit.
     * CW-018: Timeout limit respect
     */
    public function test_job_respects_timeout_limit(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 2, // Would normally crawl many pages
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [['url' => 'https://example.com/page2', 'internal' => true]],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        // First call returns true (start), second returns false (timeout)
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')
            ->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')
            ->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job stores partial results on timeout.
     * CW-019: Partial results on timeout
     */
    public function test_job_stores_partial_results_on_timeout(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 2,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);
        // Should be called when timeout occurs with partial results
        $mockPartialResults->shouldReceive('storePartialResults')
            ->once();

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job handles graceful shutdown signal.
     * CW-020: Graceful shutdown
     */
    public function test_job_handles_graceful_shutdown_signal(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(true);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test timeout handler is started and stopped.
     * CW-021: Timeout handler lifecycle
     */
    public function test_timeout_handler_is_started_and_stopped(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('markJobFailed');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->andThrow(new \Exception('Test'));
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')
            ->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')
            ->once();

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job with custom timeout options.
     * CW-022: Custom timeout options
     */
    public function test_job_with_custom_timeout_options(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'options' => [
                'timeout' => 600,
            ],
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')
            ->once()
            ->withArgs(function ($jobId, $options) {
                return ($options['timeout'] ?? 0) === 600;
            });
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    // =========================================================================
    // Cancellation - CW-023 to CW-027
    // =========================================================================

    /**
     * Test job respects cancellation request.
     * CW-023: Cancellation request respect
     */
    public function test_job_respects_cancellation_request(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 2,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [['url' => 'https://example.com/page2', 'internal' => true]],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        // First call returns false (not cancelled), second returns true (cancelled)
        $mockCancellation->shouldReceive('shouldCancel')
            ->andReturn(false, true);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);
        $mockPartialResults->shouldReceive('storePartialResults')
            ->once();

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test job stores partial results on cancellation.
     * CW-024: Partial results on cancel
     */
    public function test_job_stores_partial_results_on_cancellation(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 1,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(true);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);
        $mockPartialResults->shouldReceive('storePartialResults')
            ->once()
            ->withArgs(function ($job, $pages, $reason) {
                return $reason === 'cancelled';
            });

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test cancellation clears flag after completion.
     * CW-025: Cancellation flag cleared
     */
    public function test_cancellation_flag_cleared_after_completion(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('markJobFailed');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->andThrow(new \Exception('Test'));
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('clearCancellationFlag')
            ->once();

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test early cancellation before processing.
     * CW-026: Early cancellation
     */
    public function test_early_cancellation_before_processing(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(true);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);
        $mockPartialResults->shouldReceive('storePartialResults');

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test cancellation resources released.
     * CW-027: Cancellation resources released
     */
    public function test_cancellation_resources_released(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->once(); // Browser must be closed
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking')
            ->once(); // Timeout must be stopped
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(true);
        $mockCancellation->shouldReceive('clearCancellationFlag')
            ->once(); // Flag must be cleared

        $mockPartialResults = Mockery::mock(PartialResultsService::class);
        $mockPartialResults->shouldReceive('storePartialResults');

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    // =========================================================================
    // Multi-Depth Processing - CW-028 to CW-031
    // =========================================================================

    /**
     * Test depth 1 processes linked pages.
     * CW-028: Depth 1 linked pages
     */
    public function test_depth_1_processes_linked_pages(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 1,
            'url' => 'https://example.com',
        ]);

        $callCount = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult');
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'html' => '<html></html>',
                        'text' => '',
                        'meta' => [],
                        'links' => [
                            ['url' => 'https://example.com/page2', 'internal' => true],
                        ],
                        'size' => 1000,
                    ];
                }
                return [
                    'html' => '<html></html>',
                    'text' => '',
                    'meta' => [],
                    'links' => [],
                    'size' => 500,
                ];
            });

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test external links are not followed.
     * CW-029: External links not followed
     */
    public function test_external_links_are_not_followed(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 1,
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')
            ->once(); // Only main page
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [
                ['url' => 'https://external.com/page', 'internal' => false],
            ],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test duplicate URLs are skipped.
     * CW-030: Duplicate URLs skipped
     */
    public function test_duplicate_urls_are_skipped(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 1,
            'url' => 'https://example.com',
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')
            ->once(); // Only one call for main page
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [
                ['url' => 'https://example.com', 'internal' => true], // Same as main URL
            ],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(true, false);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }

    /**
     * Test depth limit is enforced.
     * CW-031: Depth limit enforced
     */
    public function test_depth_limit_is_enforced(): void
    {
        $crawlJob = CrawlJob::factory()->forUser($this->user)->queued()->create([
            'depth' => 0, // Only main page
            'url' => 'https://example.com',
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus');
        $mockCrawler->shouldReceive('storePageResult')
            ->once(); // Only one page despite links
        $mockCrawler->shouldReceive('updateStats');

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn((object)['connected' => true]);
        $mockRenderer->shouldReceive('closeBrowser');
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [
                ['url' => 'https://example.com/page2', 'internal' => true],
            ],
            'size' => 1000,
        ]);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate');

        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking');
        $mockTimeoutHandler->shouldReceive('stopTracking');
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->andReturn(false);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->andReturn(false);

        $mockCancellation = Mockery::mock(CancellationService::class);
        $mockCancellation->shouldReceive('shouldCancel')->andReturn(false);
        $mockCancellation->shouldReceive('clearCancellationFlag');

        $mockPartialResults = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker($crawlJob->job_id);
        $job->handle(
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellation,
            $mockPartialResults
        );
    }
}
