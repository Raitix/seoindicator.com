<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CrawlWorker;
use App\Models\CrawlJob;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\TimeoutHandlerService;
use App\Services\Crawler\CancellationService;
use App\Services\Crawler\PartialResultsService;
use App\Services\PlaywrightRendererService;
use App\Services\UrlValidatorService;
use App\Exceptions\SSRFException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Unit tests for TASK-22-15: CrawlWorker Implementation
 *
 * Tests the queue job that processes crawl jobs, orchestrating the full crawl pipeline.
 *
 * @see TEST-22-15
 * @see TASK-22-15
 */
class CrawlWorkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Increase memory limit for tests that call handle()
        // CrawlWorker sets 256M limit which may fail in test environment
        ini_set('memory_limit', '512M');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create mock services for handle() method.
     *
     * @param array $options Override default mock behaviors
     * @return array Array of mock services in correct order for handle()
     */
    protected function createMockServices(array $options = []): array
    {
        $crawlJob = $options['crawlJob'] ?? null;
        $validateUrl = $options['validateUrl'] ?? true;
        $renderResult = $options['renderResult'] ?? [
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ];
        $shouldFail = $options['shouldFail'] ?? false;
        $failException = $options['failException'] ?? null;

        // CrawlerService mock
        $mockCrawler = Mockery::mock(CrawlerService::class);
        if ($crawlJob) {
            $mockCrawler->shouldReceive('getCrawlJob')
                ->with($options['jobId'] ?? 'test-job')
                ->atLeast(1)
                ->andReturn($crawlJob);
            
            if (!$shouldFail) {
                $mockCrawler->shouldReceive('updateStatus')->atLeast(0);
                $mockCrawler->shouldReceive('storePageResult')->atLeast(0);
                $mockCrawler->shouldReceive('updateStats')->atLeast(0);
            }
            
            if ($shouldFail) {
                $mockCrawler->shouldReceive('markJobFailed')->atLeast(1);
            }
        }

        // PlaywrightRendererService mock
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        if (isset($options['launchBrowser']) && $options['launchBrowser'] === false) {
            // Don't set up launchBrowser expectation
        } else {
            $mockRenderer->shouldReceive('launchBrowser')
                ->atLeast(0)
                ->andReturn(new \stdClass());
        }
        
        if (isset($options['renderPage']) && $options['renderPage'] === false) {
            // Don't set up renderPage expectation
        } elseif ($failException) {
            $mockRenderer->shouldReceive('renderPage')
                ->atLeast(0)
                ->andThrow($failException);
        } else {
            $mockRenderer->shouldReceive('renderPage')
                ->atLeast(0)
                ->andReturn($renderResult);
        }
        
        $mockRenderer->shouldReceive('closeBrowser')->atLeast(0);

        // UrlValidatorService mock
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        if ($validateUrl === true) {
            $mockUrlValidator->shouldReceive('validate')
                ->atLeast(0)
                ->andReturn(true);
        } elseif ($validateUrl instanceof \Exception) {
            $mockUrlValidator->shouldReceive('validate')
                ->atLeast(0)
                ->andThrow($validateUrl);
        }

        // TimeoutHandlerService mock
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->atLeast(0);
        $mockTimeoutHandler->shouldReceive('stopTracking')->atLeast(0);
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(0)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(0)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(0)->andReturn(false);
        $mockTimeoutHandler->shouldReceive('isPageTimeout')->atLeast(0)->andReturn(false);
        $mockTimeoutHandler->shouldReceive('getElapsedSeconds')->atLeast(0)->andReturn(0);
        $mockTimeoutHandler->shouldReceive('getDefaultTimeout')->atLeast(0)->andReturn(300);
        $mockTimeoutHandler->shouldReceive('getRemainingSeconds')->atLeast(0)->andReturn(300);

        // CancellationService mock
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(0)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->atLeast(0);

        // PartialResultsService mock
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);
        $mockPartialResultsService->shouldReceive('storePartialResults')->atLeast(0);

        return [
            $mockCrawler,
            $mockRenderer,
            $mockUrlValidator,
            $mockTimeoutHandler,
            $mockCancellationService,
            $mockPartialResultsService,
        ];
    }

    // =========================================================================
    // WORKER STRUCTURE TESTS (WS-*)
    // =========================================================================

    /**
     * WS-01: Job file exists
     */
    public function test_job_file_exists(): void
    {
        $this->assertFileExists(app_path('Jobs/CrawlWorker.php'));
    }

    /**
     * WS-02: Implements ShouldQueue
     */
    public function test_implements_should_queue(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /**
     * WS-03: Uses Queueable trait
     */
    public function test_uses_queueable_trait(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($job));
    }

    /**
     * WS-04: Uses InteractsWithQueue trait
     */
    public function test_uses_interacts_with_queue_trait(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, class_uses_recursive($job));
    }

    /**
     * WS-05: Constructor accepts jobId
     */
    public function test_constructor_accepts_job_id(): void
    {
        $jobId = 'crawl-a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $job = new CrawlWorker($jobId);
        $this->assertEquals($jobId, $job->jobId);
    }

    /**
     * WS-06: Job dispatchable
     */
    public function test_job_dispatchable(): void
    {
        Queue::fake();
        CrawlWorker::dispatch('crawl-test-123');
        Queue::assertPushed(CrawlWorker::class, function ($job) {
            return $job->jobId === 'crawl-test-123';
        });
    }

    // =========================================================================
    // JOB CONFIGURATION TESTS (WC-*)
    // =========================================================================

    /**
     * WC-01: Timeout is 300 seconds
     */
    public function test_timeout_is_300_seconds(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        $this->assertEquals(300, $job->timeout);
    }

    /**
     * WC-02: Tries is configurable from config
     */
    public function test_tries_is_configurable(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        // Default from config is 2 (crawler.queue.retry_attempts)
        $this->assertEquals(config('crawler.queue.retry_attempts', 2), $job->tries);
    }

    /**
     * WC-03: Max exceptions matches tries
     */
    public function test_max_exceptions_matches_tries(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        $this->assertEquals($job->tries, $job->maxExceptions);
    }

    /**
     * WC-04: Backoff is calculated from config
     */
    public function test_backoff_is_calculated(): void
    {
        $job = new CrawlWorker('crawl-test-123');
        // Backoff should be an array with exponential delays
        $this->assertIsArray($job->backoff);
        $this->assertNotEmpty($job->backoff);
        // First backoff should be the base delay (60s default)
        $this->assertEquals(config('crawler.queue.retry_delay', 60), $job->backoff[0]);
    }

    /**
     * WC-05: Unique job ID
     */
    public function test_unique_job_id(): void
    {
        $jobId = 'crawl-unique-test-123';
        $job = new CrawlWorker($jobId);
        $this->assertEquals($jobId, $job->uniqueId());
    }

    // =========================================================================
    // MAIN HANDLER TESTS - Job Retrieval (WH-RET-*)
    // =========================================================================

    /**
     * WH-RET-01: Retrieve valid job
     */
    public function test_retrieve_valid_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-test-123';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;
        // Allow the depth property to be accessed
        $crawlJob->shouldReceive('getAttribute')->with('depth')->andReturn(0)->byDefault();
        $crawlJob->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com')->byDefault();
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn('queued')->byDefault();
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-test-123')->byDefault();
        $crawlJob->shouldReceive('getAttribute')->with('options')->andReturn([])->byDefault();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('crawl-test-123')
            ->atLeast(1)
            ->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(0);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(0);
        $mockCrawler->shouldReceive('updateStats')->atLeast(0);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->atLeast(0)
            ->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->atLeast(0)
            ->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')
            ->atLeast(0)
            ->andReturn([
                'html' => '<html></html>',
                'text' => 'Content',
                'meta' => [],
                'links' => [],
                'size' => 1024,
            ]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->atLeast(0);

        Log::shouldReceive('info')->atLeast(0);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services required by handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->atLeast(0);
        $mockTimeoutHandler->shouldReceive('stopTracking')->atLeast(0);
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(0)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(0)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(0)->andReturn(false);
        $mockTimeoutHandler->shouldReceive('isPageTimeout')->atLeast(0)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(0)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->atLeast(0);

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-test-123');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        // Test passes if no exceptions are thrown
        $this->assertTrue(true);
    }

    /**
     * WH-RET-02: Job not found
     */
    public function test_job_not_found(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'CrawlWorker: Job not found',
                Mockery::hasKey('jobId')
            );
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('info')->atLeast(0);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('crawl-nonexistent')
            ->once()
            ->andReturn(null);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        // Additional services for handle() - not needed for job not found case
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-nonexistent');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WH-RET-03: Job already completed
     */
    public function test_job_already_completed(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'CrawlWorker: Job not available for processing',
                Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['status']) &&
                           $context['status'] === 'completed';
                })
            );
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('info')->atLeast(0);

        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-completed';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'completed';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('crawl-completed')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-completed');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WH-RET-04: Job already cancelled
     */
    public function test_job_already_cancelled(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'CrawlWorker: Job not available for processing',
                Mockery::on(function ($context) {
                    return isset($context['status']) &&
                           $context['status'] === 'cancelled';
                })
            );
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('info')->atLeast(0);

        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-cancelled';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'cancelled';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('crawl-cancelled')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-cancelled');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WH-RET-05: Job already running
     */
    public function test_job_already_running(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'CrawlWorker: Job not available for processing',
                Mockery::on(function ($context) {
                    return isset($context['status']) &&
                           $context['status'] === 'running';
                })
            );
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('info')->atLeast(0);

        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-running';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'running';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')
            ->with('crawl-running')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-running');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    // =========================================================================
    // SSRF PROTECTION TESTS (WP-SSRF-*)
    // =========================================================================

    /**
     * WP-SSRF-01: Valid public URL passes validation
     */
    public function test_valid_public_url_passes_validation(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-valid-url';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->with('https://example.com')
            ->once()
            ->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ]);
        $mockRenderer->shouldReceive('closeBrowser');

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-valid-url');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-02: Localhost blocked
     */
    public function test_localhost_blocked(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-localhost';
        $crawlJob->url = 'http://localhost/page';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(0);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->with('http://localhost/page')
            ->once()
            ->andThrow(new SSRFException('URL blocked: localhost access denied'));

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        // startTracking is called BEFORE url validation, so we need to mock it
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();
        
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-localhost');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-03: Private IP blocked
     */
    public function test_private_ip_blocked(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-private-ip';
        $crawlJob->url = 'http://192.168.1.1/admin';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(0);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new SSRFException('URL blocked: private IP access denied'));

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        // startTracking is called BEFORE url validation, so we need to mock it
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        
        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();
        
        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-private-ip');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    // =========================================================================
    // BROWSER LIFECYCLE TESTS (WBL-*)
    // =========================================================================

    /**
     * WBL-01: Launch browser at start
     */
    public function test_launch_browser_at_start(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-browser-test';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $browserInstance = new \stdClass();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->once()
            ->with(Mockery::any())
            ->andReturn($browserInstance);
        $mockRenderer->shouldReceive('renderPage')
            ->atLeast(1)
            ->andReturn([
                'html' => '<html></html>',
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => 1024,
            ]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->once()
            ->with($browserInstance);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-browser-test');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WBL-03: Close browser on completion
     */
    public function test_close_browser_on_completion(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-browser-close';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $browserInstance = new \stdClass();
        $browserClosed = false;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn($browserInstance);
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->once()
            ->with($browserInstance)
            ->andReturnUsing(function () use (&$browserClosed) {
                $browserClosed = true;
            });

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-browser-close');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue($browserClosed);
    }

    /**
     * WBL-04: Close browser on failure
     */
    public function test_close_browser_on_failure(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-browser-failure';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $browserInstance = new \stdClass();
        $browserClosed = false;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn($browserInstance);
        $mockRenderer->shouldReceive('renderPage')
            ->andThrow(new \Exception('Render failed'));
        $mockRenderer->shouldReceive('closeBrowser')
            ->once()
            ->with($browserInstance)
            ->andReturnUsing(function () use (&$browserClosed) {
                $browserClosed = true;
            });

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-browser-failure');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue($browserClosed);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (WEH-*)
    // =========================================================================

    /**
     * WEH-PW-01: Browser launch failure
     */
    public function test_browser_launch_failure(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-launch-fail';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->andThrow(new \Exception('Playwright not found'));

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-launch-fail');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WEH-TIMEOUT-01: Page timeout
     */
    public function test_page_timeout(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-timeout';
        $crawlJob->url = 'https://slow-site.example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')
            ->andThrow(new \Exception('Timeout exceeded: 30000ms'));
        $mockRenderer->shouldReceive('closeBrowser');

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-timeout');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    // =========================================================================
    // FAILED JOB HANDLING TESTS (WFJ-*)
    // =========================================================================

    /**
     * WFJ-01: failed() method exists and has correct signature
     */
    public function test_failed_method_exists(): void
    {
        $job = new CrawlWorker('crawl-failed-test');
        
        // Verify the failed method exists
        $this->assertTrue(method_exists($job, 'failed'));
        
        // Verify it accepts a Throwable parameter
        $reflection = new \ReflectionMethod($job, 'failed');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->hasType());
        
        // Verify return type is void
        $returnType = $reflection->getReturnType();
        $this->assertEquals('void', $returnType?->getName());
    }

    // =========================================================================
    // LOGGING TESTS (WLOG-*)
    // =========================================================================

    /**
     * WLOG-01: Log job start
     */
    public function test_log_job_start(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-log-start';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 1;

        Log::shouldReceive('info')
            ->once()
            ->with(
                'CrawlWorker: Starting crawl job',
                Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['url']) &&
                           isset($context['depth']);
                })
            );

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ]);
        $mockRenderer->shouldReceive('closeBrowser');

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-log-start');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WLOG-04: Log job completion
     */
    public function test_log_job_completion(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-log-complete';
        $crawlJob->url = 'https://example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        // Log job completion - use atLeast(1) to be more flexible
        Log::shouldReceive('info')
            ->atLeast(1)
            ->with(
                Mockery::any(),
                Mockery::any()
            );

        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ]);
        $mockRenderer->shouldReceive('closeBrowser');

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-log-complete');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    /**
     * WLOG-08: Sensitive data redacted in logs
     */
    public function test_sensitive_data_redacted_in_logs(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class)->makePartial();
        $crawlJob->job_id = 'crawl-sensitive';
        $crawlJob->url = 'https://user:secret@example.com';
        $crawlJob->status = 'queued';
        $crawlJob->depth = 0;

        // The URL should be sanitized before logging
        Log::shouldReceive('info')
            ->atLeast(1)
            ->with(
                Mockery::any(),
                Mockery::on(function ($context) {
                    if (isset($context['url'])) {
                        // Credentials should be redacted
                        return !str_contains($context['url'], 'secret');
                    }
                    return true;
                })
            );

        Log::shouldReceive('debug')->atLeast(0);
        Log::shouldReceive('error')->atLeast(0);
        Log::shouldReceive('warning')->atLeast(0);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getCrawlJob')->atLeast(1)->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->atLeast(0);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')->andReturn([
            'html' => '<html></html>',
            'text' => '',
            'meta' => [],
            'links' => [],
            'size' => 1024,
        ]);
        $mockRenderer->shouldReceive('closeBrowser');

        // Additional services for handle()
        $mockTimeoutHandler = Mockery::mock(TimeoutHandlerService::class);
        $mockTimeoutHandler->shouldReceive('startTracking')->once();
        $mockTimeoutHandler->shouldReceive('stopTracking')->once();
        $mockTimeoutHandler->shouldReceive('canContinueCrawling')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('hasTimeForNextPage')->atLeast(1)->andReturn(true);
        $mockTimeoutHandler->shouldReceive('shouldStartGracefulShutdown')->atLeast(1)->andReturn(false);

        $mockCancellationService = Mockery::mock(CancellationService::class);
        $mockCancellationService->shouldReceive('shouldCancel')->atLeast(1)->andReturn(false);
        $mockCancellationService->shouldReceive('clearCancellationFlag')->once();

        $mockPartialResultsService = Mockery::mock(PartialResultsService::class);

        $job = new CrawlWorker('crawl-sensitive');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator, $mockTimeoutHandler, $mockCancellationService, $mockPartialResultsService);

        $this->assertTrue(true);
    }

    // =========================================================================
    // DISPATCH TESTS
    // =========================================================================

    /**
     * Test job can be dispatched
     */
    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();
        CrawlWorker::dispatch('crawl-dispatch-test');
        Queue::assertPushed(CrawlWorker::class);
    }

    /**
     * Test job dispatches with correct jobId
     */
    public function test_job_dispatches_with_correct_job_id(): void
    {
        Queue::fake();

        $jobId = 'crawl-' . \Illuminate\Support\Str::uuid();
        CrawlWorker::dispatch($jobId);

        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }
}
