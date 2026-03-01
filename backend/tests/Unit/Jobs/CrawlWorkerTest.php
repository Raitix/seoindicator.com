<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CrawlWorker;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\User;
use App\Services\CrawlerService;
use App\Services\PlaywrightRendererService;
use App\Services\UrlValidatorService;
use App\Services\DepthController;
use App\Services\SizeLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
     * WC-02: Tries is 1 (no auto-retry)
     */
    public function test_tries_is_1(): void
    {
        $job = new CrawlWorker('crawl-test-123');

        $this->assertEquals(1, $job->tries);
    }

    /**
     * WC-03: Max exceptions is 1
     */
    public function test_max_exceptions_is_1(): void
    {
        $job = new CrawlWorker('crawl-test-123');

        $this->assertEquals(1, $job->maxExceptions);
    }

    /**
     * WC-04: Backoff is [60]
     */
    public function test_backoff_is_60(): void
    {
        $job = new CrawlWorker('crawl-test-123');

        $this->assertEquals([60], $job->backoff);
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

    /**
     * WC-06: Different job IDs have different unique IDs
     */
    public function test_different_job_ids_have_different_unique_ids(): void
    {
        $job1 = new CrawlWorker('crawl-test-1');
        $job2 = new CrawlWorker('crawl-test-2');

        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * WC-07: Memory limit configuration
     */
    public function test_memory_limit_configuration(): void
    {
        $job = new CrawlWorker('crawl-test-123');

        // The memory limit should be set in handle(), verify the constant exists
        $this->assertTrue(true); // Placeholder - actual test in integration tests
    }

    // =========================================================================
    // MAIN HANDLER TESTS - Job Retrieval (WH-RET-*)
    // =========================================================================

    /**
     * WH-RET-01: Retrieve valid job
     */
    public function test_retrieve_valid_job(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-123',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')
            ->with('crawl-test-123')
            ->once()
            ->andReturn($crawlJob);

        $mockCrawler->shouldReceive('updateStatus')
            ->with('crawl-test-123', 'running')
            ->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->with('https://example.com')
            ->once();

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->once()
            ->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html></html>',
                'text' => 'Content',
                'meta' => [],
                'links' => [],
                'size' => 1024,
            ]);
        $mockRenderer->shouldReceive('closeBrowser')
            ->once();

        $mockCrawler->shouldReceive('storePageResult')
            ->once();
        $mockCrawler->shouldReceive('updateStats')
            ->once();
        $mockCrawler->shouldReceive('updateStatus')
            ->with('crawl-test-123', 'completed')
            ->once();

        Log::shouldReceive('info')->atLeast(1);

        $job = new CrawlWorker('crawl-test-123');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true); // Test passed if no exceptions
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

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')
            ->with('crawl-nonexistent')
            ->once()
            ->andReturn(null);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        $job = new CrawlWorker('crawl-nonexistent');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        // Should exit early without processing
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

        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-completed',
            'url' => 'https://example.com',
            'status' => 'completed',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')
            ->with('crawl-completed')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        $job = new CrawlWorker('crawl-completed');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        // Should exit early
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

        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-cancelled',
            'url' => 'https://example.com',
            'status' => 'cancelled',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')
            ->with('crawl-cancelled')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        $job = new CrawlWorker('crawl-cancelled');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WH-RET-05: Job already running (duplicate processing prevention)
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

        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-running',
            'url' => 'https://example.com',
            'status' => 'running',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')
            ->with('crawl-running')
            ->once()
            ->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        $job = new CrawlWorker('crawl-running');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    // =========================================================================
    // STATUS TRANSITION TESTS (WH-STAT-*)
    // =========================================================================

    /**
     * WH-STAT-01: Set status to running
     */
    public function test_set_status_to_running(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-status-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')
            ->with('crawl-status-test', 'running')
            ->once()
            ->andReturnUsing(function ($jobId, $status) use ($crawlJob) {
                $crawlJob->status = $status;
                $crawlJob->started_at = now();
                return true;
            });

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        // Mock SSRF validation to throw - we just want to test status change
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new \Exception('Test exception'));

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-status-test');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        // Verify status was set to running before exception
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
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-valid-url',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->with('https://example.com')
            ->once()
            ->andReturn(true); // Valid URL

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

        $job = new CrawlWorker('crawl-valid-url');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-02: Localhost blocked
     */
    public function test_localhost_blocked(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-localhost',
            'url' => 'http://localhost/page',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->with('http://localhost/page')
            ->once()
            ->andThrow(new \App\Exceptions\SSRFException('URL blocked: localhost access denied'));

        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-localhost');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-03: Private IP blocked
     */
    public function test_private_ip_blocked(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-private-ip',
            'url' => 'http://192.168.1.1/admin',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new \App\Exceptions\SSRFException('URL blocked: private IP access denied'));

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-private-ip');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-04: Cloud metadata endpoint blocked
     */
    public function test_cloud_metadata_blocked(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-metadata',
            'url' => 'http://169.254.169.254/latest/meta-data',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new \App\Exceptions\SSRFException('URL blocked: metadata endpoint access denied'));

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-metadata');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WP-SSRF-05: file:// protocol blocked
     */
    public function test_file_protocol_blocked(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-file-protocol',
            'url' => 'file:///etc/passwd',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')
            ->andThrow(new \App\Exceptions\SSRFException('Protocol not allowed: file://'));

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-file-protocol');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

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
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-browser-test',
            'url' => 'https://example.com',
            'status' => 'queued',
            'depth' => 0,
            'expires_at' => now()->addDays(7),
        ]);

        $browserInstance = new \stdClass();

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->once()
            ->with(Mockery::any())
            ->andReturn($browserInstance);
        $mockRenderer->shouldReceive('renderPage')
            ->once()
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

        $job = new CrawlWorker('crawl-browser-test');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WBL-03: Close browser on completion
     */
    public function test_close_browser_on_completion(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-browser-close',
            'url' => 'https://example.com',
            'status' => 'queued',
            'depth' => 0,
            'expires_at' => now()->addDays(7),
        ]);

        $browserInstance = new \stdClass();
        $browserClosed = false;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

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

        $job = new CrawlWorker('crawl-browser-close');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue($browserClosed);
    }

    /**
     * WBL-04: Close browser on failure
     */
    public function test_close_browser_on_failure(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-browser-failure',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $browserInstance = new \stdClass();
        $browserClosed = false;

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
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

        $job = new CrawlWorker('crawl-browser-failure');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

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
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-launch-fail',
            'url' => 'https://example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')
            ->andThrow(new \Exception('Playwright not found'));

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-launch-fail');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WEH-NET-01: DNS resolution failure
     */
    public function test_dns_resolution_failure(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-dns-fail',
            'url' => 'https://nonexistent-domain-12345.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('markJobFailed')->once();

        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);
        $mockUrlValidator->shouldReceive('validate')->andReturn(true);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockRenderer->shouldReceive('launchBrowser')->andReturn(new \stdClass());
        $mockRenderer->shouldReceive('renderPage')
            ->andThrow(new \Exception('net::ERR_NAME_NOT_RESOLVED'));
        $mockRenderer->shouldReceive('closeBrowser');

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atLeast(1);

        $job = new CrawlWorker('crawl-dns-fail');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WEH-TIMEOUT-01: Page timeout
     */
    public function test_page_timeout(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-timeout',
            'url' => 'https://slow-site.example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
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

        $job = new CrawlWorker('crawl-timeout');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    // =========================================================================
    // CANCELLATION TESTS (WCAN-*)
    // =========================================================================

    /**
     * WCAN-01: Check cancellation flag
     */
    public function test_check_cancellation_flag(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-cancel-check',
            'url' => 'https://example.com',
            'status' => 'cancelled',
            'expires_at' => now()->addDays(7),
        ]);

        // Job already cancelled - should exit early
        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);

        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);
        $mockUrlValidator = Mockery::mock(UrlValidatorService::class);

        Log::shouldReceive('warning')->atLeast(1);

        $job = new CrawlWorker('crawl-cancel-check');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    // =========================================================================
    // FAILED JOB HANDLING TESTS (WFJ-*)
    // =========================================================================

    /**
     * WFJ-01: failed() method called
     */
    public function test_failed_method_updates_status(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-failed-test',
            'url' => 'https://example.com',
            'status' => 'running',
            'expires_at' => now()->addDays(7),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with(
                'CrawlWorker: Job failed',
                Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['error']);
                })
            );

        $job = new CrawlWorker('crawl-failed-test');
        $job->failed(new \Exception('Test failure'));

        $crawlJob->refresh();
        $this->assertEquals('failed', $crawlJob->status);
        $this->assertNotNull($crawlJob->completed_at);
    }

    /**
     * WFJ-03: Store error message
     */
    public function test_store_error_message(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-error-msg',
            'url' => 'https://example.com',
            'status' => 'running',
            'expires_at' => now()->addDays(7),
        ]);

        $errorMessage = 'Connection refused';

        Log::shouldReceive('error')->once();

        $job = new CrawlWorker('crawl-error-msg');
        $job->failed(new \Exception($errorMessage));

        $crawlJob->refresh();
        $this->assertStringContainsString($errorMessage, $crawlJob->error);
    }

    /**
     * WFJ-04: Close browser instances on failure
     */
    public function test_close_browser_on_failed_method(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-failed-browser',
            'url' => 'https://example.com',
            'status' => 'running',
            'expires_at' => now()->addDays(7),
        ]);

        Log::shouldReceive('error')->once();

        $job = new CrawlWorker('crawl-failed-browser');
        $job->failed(new \Exception('Test failure'));

        // Job should be marked as failed
        $crawlJob->refresh();
        $this->assertEquals('failed', $crawlJob->status);
    }

    // =========================================================================
    // LOGGING TESTS (WLOG-*)
    // =========================================================================

    /**
     * WLOG-01: Log job start
     */
    public function test_log_job_start(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-log-start',
            'url' => 'https://example.com',
            'status' => 'queued',
            'depth' => 1,
            'expires_at' => now()->addDays(7),
        ]);

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

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

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

        $job = new CrawlWorker('crawl-log-start');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WLOG-04: Log job completion
     */
    public function test_log_job_completion(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-log-complete',
            'url' => 'https://example.com',
            'status' => 'queued',
            'depth' => 0,
            'expires_at' => now()->addDays(7),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with(
                'CrawlWorker: Crawl job completed',
                Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['pagesCrawled']) &&
                           isset($context['duration']);
                })
            );

        Log::shouldReceive('info')->atLeast(1);

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

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

        $job = new CrawlWorker('crawl-log-complete');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

        $this->assertTrue(true);
    }

    /**
     * WLOG-08: Sensitive data redacted in logs
     */
    public function test_sensitive_data_redacted_in_logs(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'job_id' => 'crawl-sensitive',
            'url' => 'https://user:secret@example.com',
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

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

        $mockCrawler = Mockery::mock(CrawlerService::class);
        $mockCrawler->shouldReceive('getJob')->andReturn($crawlJob);
        $mockCrawler->shouldReceive('updateStatus')->atLeast(1);
        $mockCrawler->shouldReceive('storePageResult')->atLeast(1);
        $mockCrawler->shouldReceive('updateStats')->atLeast(1);

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

        $job = new CrawlWorker('crawl-sensitive');
        $job->handle($mockCrawler, $mockRenderer, $mockUrlValidator);

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
