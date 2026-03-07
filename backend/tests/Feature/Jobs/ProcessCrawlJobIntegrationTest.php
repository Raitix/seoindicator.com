<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCrawlJob;
use App\Models\Audit;
use App\Models\Site;
use App\Models\User;
use App\Services\Audit\SiteCrawler;
use App\Services\Audit\IssueDetector;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use App\Events\AuditStarted;
use App\Events\AuditProgress;
use App\Events\AuditCompleted;
use App\Events\AuditFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ProcessCrawlJobIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Category: Integration Tests (INT)
    // =========================================================================

    /** @test */
    public function int_001_full_happy_path(): void
    {
        Event::fake([AuditStarted::class, AuditCompleted::class]);
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $crawlResult = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 150.0,
            html: '<html><head><title>Test Page</title></head><body><h1>Heading</h1></body></html>',
            links: ['internal' => ['/about'], 'external' => []],
            metadata: ['title' => 'Test Page', 'h1' => 'Heading'],
            errorMessage: null,
            headers: ['content-type' => 'text/html'],
            depth: 0,
            redirectChain: []
        );
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')
            ->with($audit->id, 'https://example.com')
            ->once();
        $mockCrawler->shouldReceive('processQueue')
            ->once()
            ->andReturn(new \ArrayObject([$crawlResult]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')
            ->once()
            ->andReturn([]);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        // Verify status progression
        $audit->refresh();
        $this->assertEquals('completed', $audit->status);
        $this->assertEquals(1, $audit->pages_crawled);
        $this->assertEquals(0, $audit->issues_found);
        
        // Verify events dispatched
        Event::assertDispatched(AuditStarted::class);
        Event::assertDispatched(AuditCompleted::class);
    }

    /** @test */
    public function int_002_full_error_path(): void
    {
        Event::fake([AuditStarted::class, AuditFailed::class]);
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')
            ->andThrow(new \Exception('Crawler initialization failed'));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        
        try {
            $job->handle($mockCrawler, $mockDetector);
        } catch (\Exception $e) {
            // Expected
        }
        
        $job->failed(new \Exception('Crawler failed'));
        
        $audit->refresh();
        $this->assertEquals('failed', $audit->status);
        $this->assertNotNull($audit->error_message);
        
        Event::assertDispatched(AuditFailed::class);
    }

    /** @test */
    public function int_003_partial_failure_recovery(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        // Create some successful results and one failed
        $successResult1 = new CrawlResult(
            url: 'https://example.com/page/1',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: '<html><body>Page 1</body></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 1,
            redirectChain: []
        );
        
        $failedResult = new CrawlResult(
            url: 'https://example.com/page/2',
            statusCode: 500,
            responseTimeMs: 0,
            html: null,
            links: [],
            metadata: [],
            errorMessage: 'Internal server error',
            headers: [],
            depth: 1,
            redirectChain: []
        );
        
        $successResult2 = new CrawlResult(
            url: 'https://example.com/page/3',
            statusCode: 200,
            responseTimeMs: 120.0,
            html: '<html><body>Page 3</body></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 1,
            redirectChain: []
        );
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$successResult1, $failedResult, $successResult2]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')
            ->andReturn([]);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        $audit->refresh();
        $this->assertEquals('completed', $audit->status);
        $this->assertEquals(2, $audit->pages_crawled); // Only successful pages
    }

    /** @test */
    public function int_004_resume_from_interruption(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'running',
            'pages_crawled' => 50,
            'issues_found' => 10,
        ]);
        
        // Simulate cached progress from previous run
        $cachedProgress = [
            'audit_id' => $audit->id,
            'pages_crawled' => 50,
            'issues_found' => 10,
            'current_url' => 'https://example.com/page/50',
            'started_at' => now()->subHour()->toISOString(),
            'last_updated' => now()->subMinutes(5)->toISOString(),
        ];
        
        Cache::shouldReceive('get')
            ->with("audit:progress:{$audit->id}")
            ->andReturn($cachedProgress);
        
        // Additional pages to crawl after resume
        $additionalResult = new CrawlResult(
            url: 'https://example.com/page/51',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: '<html><body>Page 51</body></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 1,
            redirectChain: []
        );
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$additionalResult]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')->andReturn([]);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        $audit->refresh();
        // Should have resumed and added one more page
        $this->assertEquals(51, $audit->pages_crawled);
    }

    /** @test */
    public function int_005_concurrent_audit_isolation(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        
        $audit1 = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $audit2 = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        // Different crawl results for each audit
        $result1 = new CrawlResult(
            url: 'https://site1.com/',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: '<html>Site 1</html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        $result2 = new CrawlResult(
            url: 'https://site2.com/',
            statusCode: 200,
            responseTimeMs: 150.0,
            html: '<html>Site 2</html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        // Mock crawler for first audit
        $mockCrawler1 = Mockery::mock(SiteCrawler::class);
        $mockCrawler1->shouldReceive('startCrawl')
            ->with($audit1->id, 'https://site1.com')
            ->once();
        $mockCrawler1->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$result1]));
        
        // Mock crawler for second audit
        $mockCrawler2 = Mockery::mock(SiteCrawler::class);
        $mockCrawler2->shouldReceive('startCrawl')
            ->with($audit2->id, 'https://site2.com')
            ->once();
        $mockCrawler2->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$result2]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')->andReturn([]);
        
        // Process first audit
        $job1 = new ProcessCrawlJob($audit1->id, 'https://site1.com');
        $job1->handle($mockCrawler1, $mockDetector);
        
        // Process second audit
        $job2 = new ProcessCrawlJob($audit2->id, 'https://site2.com');
        $job2->handle($mockCrawler2, $mockDetector);
        
        // Verify each audit has its own isolated results
        $audit1->refresh();
        $audit2->refresh();
        
        $this->assertEquals('completed', $audit1->status);
        $this->assertEquals('completed', $audit2->status);
        $this->assertEquals(1, $audit1->pages_crawled);
        $this->assertEquals(1, $audit2->pages_crawled);
    }

    /** @test */
    public function int_006_event_listener_triggered(): void
    {
        $eventReceived = false;
        
        Event::listen(AuditCompleted::class, function ($event) use (&$eventReceived) {
            $eventReceived = true;
        });
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')->andReturn(new \ArrayObject([]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        $this->assertTrue($eventReceived);
    }

    /** @test */
    public function int_007_queue_worker_processes_job(): void
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        // Dispatch the job
        ProcessCrawlJob::dispatch($audit->id, 'https://example.com');
        
        // Verify job was queued
        Queue::assertPushed(ProcessCrawlJob::class, function ($job) use ($audit) {
            return $job->auditId === $audit->id;
        });
    }

    /** @test */
    public function int_008_issues_stored_in_database(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $crawlResult = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: '<html><head></head><body></body></html>',
            links: [],
            metadata: ['title' => ''],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        $issueFinding = new IssueFinding(
            issueType: 'missing_page_title',
            severity: 'critical',
            description: 'Missing page title',
            recommendation: 'Add a descriptive title tag',
            context: ['element' => 'head', 'url' => 'https://example.com/']
        );
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$crawlResult]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')
            ->andReturn([$issueFinding]);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        $audit->refresh();
        $this->assertEquals(1, $audit->issues_found);
        
        // Verify issue was stored (if AuditIssue model exists)
        $this->assertDatabaseHas('audit_issues', [
            'audit_id' => $audit->id,
            'url' => 'https://example.com/',
        ]);
    }

    /** @test */
    public function int_009_progress_event_dispatched_every_10_pages(): void
    {
        Event::fake([AuditProgress::class]);
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        // Create 25 crawl results
        $crawlResults = [];
        for ($i = 1; $i <= 25; $i++) {
            $crawlResults[] = new CrawlResult(
                url: "https://example.com/page/{$i}",
                statusCode: 200,
                responseTimeMs: 100.0,
                html: "<html><body>Page {$i}</body></html>",
                links: [],
                metadata: [],
                errorMessage: null,
                headers: [],
                depth: 1,
                redirectChain: []
            );
        }
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject($crawlResults));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')->andReturn([]);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        // Should dispatch at page 10 and 20 (2 times)
        Event::assertDispatchedTimes(AuditProgress::class, 2);
    }

    /** @test */
    public function int_010_multiple_issue_types_detected(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $audit = Audit::factory()->create([
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
        
        $crawlResult = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: '<html><head></head><body></body></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        $issues = [
            new IssueFinding(
                issueType: 'missing_page_title',
                severity: 'critical',
                description: 'Missing page title',
                recommendation: 'Add title',
                context: ['url' => 'https://example.com/']
            ),
            new IssueFinding(
                issueType: 'missing_meta_description',
                severity: 'warning',
                description: 'Missing meta description',
                recommendation: 'Add meta description',
                context: ['url' => 'https://example.com/']
            ),
            new IssueFinding(
                issueType: 'no_h1_heading',
                severity: 'notice',
                description: 'No H1 heading',
                recommendation: 'Add H1',
                context: ['url' => 'https://example.com/']
            ),
        ];
        
        $mockCrawler = Mockery::mock(SiteCrawler::class);
        $mockCrawler->shouldReceive('startCrawl')->once();
        $mockCrawler->shouldReceive('processQueue')
            ->andReturn(new \ArrayObject([$crawlResult]));
        
        $mockDetector = Mockery::mock(IssueDetector::class);
        $mockDetector->shouldReceive('detect')
            ->andReturn($issues);
        
        $this->app->instance(SiteCrawler::class, $mockCrawler);
        $this->app->instance(IssueDetector::class, $mockDetector);
        
        $job = new ProcessCrawlJob($audit->id, 'https://example.com');
        $job->handle($mockCrawler, $mockDetector);
        
        $audit->refresh();
        $this->assertEquals(3, $audit->issues_found);
    }
}
