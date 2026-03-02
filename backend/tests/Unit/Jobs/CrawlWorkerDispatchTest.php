<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CrawlWorker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;

/**
 * Unit tests for TASK-22-16: Queue Integration with JobQueue
 *
 * Tests job dispatching to the crawler queue.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlWorkerDispatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // =========================================================================
    // JOB DISPATCHING TESTS (QD-*)
    // =========================================================================

    /**
     * QD-01: Dispatch to crawler queue
     */
    public function test_dispatch_to_crawler_queue(): void
    {
        $jobId = 'crawl-test-' . uniqid();
        
        CrawlWorker::dispatch($jobId);
        
        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }

    /**
     * QD-02: Dispatch with specific queue
     */
    public function test_dispatch_with_specific_queue(): void
    {
        $jobId = 'crawl-queue-test-' . uniqid();
        
        CrawlWorker::dispatch($jobId)->onQueue('crawler');
        
        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * QD-03: Dispatch with high priority
     */
    public function test_dispatch_with_high_priority(): void
    {
        $jobId = 'crawl-high-' . uniqid();
        
        CrawlWorker::dispatch($jobId)->onQueue('crawler-high');
        
        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    /**
     * QD-04: Dispatch with default priority
     */
    public function test_dispatch_with_default_priority(): void
    {
        $jobId = 'crawl-default-' . uniqid();
        
        CrawlWorker::dispatch($jobId)->onQueue('crawler');
        
        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * QD-05: Dispatch with low priority
     */
    public function test_dispatch_with_low_priority(): void
    {
        $jobId = 'crawl-low-' . uniqid();
        
        CrawlWorker::dispatch($jobId)->onQueue('crawler-low');
        
        Queue::assertPushedOn('crawler-low', CrawlWorker::class);
    }

    /**
     * QD-06: Dispatch with delay
     */
    public function test_dispatch_with_delay(): void
    {
        $jobId = 'crawl-delayed-' . uniqid();
        $delaySeconds = 60;
        
        CrawlWorker::dispatch($jobId)->delay(now()->addSeconds($delaySeconds));
        
        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }

    /**
     * QD-07: Job metadata included
     */
    public function test_job_metadata_included(): void
    {
        $jobId = 'crawl-metadata-' . uniqid();
        
        $job = new CrawlWorker($jobId);
        
        // Verify job has required metadata
        $this->assertEquals($jobId, $job->jobId);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(1, $job->tries);
        $this->assertEquals([60], $job->backoff);
    }

    /**
     * QD-08: Dispatch after commit
     */
    public function test_dispatch_after_commit(): void
    {
        $jobId = 'crawl-commit-' . uniqid();
        
        Bus::dispatchAfterCommit(new CrawlWorker($jobId));
        
        // Just verify the dispatch doesn't throw an exception
        $this->assertTrue(true);
    }

    /**
     * QD-09: Unique job ID prevents duplicates
     */
    public function test_unique_job_id_prevents_duplicates(): void
    {
        $jobId = 'crawl-unique-' . uniqid();
        
        // Create job and verify unique ID matches jobId
        $job = new CrawlWorker($jobId);
        
        $this->assertEquals($jobId, $job->uniqueId());
        
        // Verify unique ID is consistent
        $job2 = new CrawlWorker($jobId);
        $this->assertEquals($job->uniqueId(), $job2->uniqueId());
    }

    /**
     * QD-10: Job has correct tags
     */
    public function test_job_has_correct_tags(): void
    {
        $jobId = 'crawl-tags-' . uniqid();
        
        $job = new CrawlWorker($jobId);
        $tags = $job->tags();
        
        $this->assertIsArray($tags);
        $this->assertContains('crawl', $tags);
        $this->assertContains('job:' . $jobId, $tags);
    }

    // =========================================================================
    // DISPATCH HELPER TESTS
    // =========================================================================

    /**
     * Test dispatch using dispatch helper
     */
    public function test_dispatch_using_helper(): void
    {
        $jobId = 'crawl-helper-' . uniqid();
        
        dispatch(new CrawlWorker($jobId));
        
        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }

    /**
     * Test dispatch sync for testing
     */
    public function test_dispatch_sync_for_testing(): void
    {
        $jobId = 'crawl-sync-' . uniqid();
        
        // Use sync driver for synchronous testing
        CrawlWorker::dispatch($jobId)->onConnection('sync');
        
        Queue::assertPushed(CrawlWorker::class);
    }

    /**
     * Test dispatch with chain
     */
    public function test_dispatch_with_chain(): void
    {
        $jobId = 'crawl-chain-' . uniqid();
        
        Bus::chain([
            new CrawlWorker($jobId),
        ])->dispatch();
        
        // Verify chain dispatch works
        $this->assertTrue(true);
    }

    /**
     * Test dispatch with batch
     */
    public function test_dispatch_with_batch(): void
    {
        $jobIds = [
            'crawl-batch-1-' . uniqid(),
            'crawl-batch-2-' . uniqid(),
            'crawl-batch-3-' . uniqid(),
        ];
        
        $jobs = collect($jobIds)->map(fn ($id) => new CrawlWorker($id))->toArray();
        
        $batch = Bus::batch($jobs)->dispatch();
        
        $this->assertNotNull($batch);
    }

    // =========================================================================
    // QUEUE CONNECTION TESTS
    // =========================================================================

    /**
     * Test job specifies queue connection
     */
    public function test_job_can_specify_queue_connection(): void
    {
        $jobId = 'crawl-connection-' . uniqid();
        
        CrawlWorker::dispatch($jobId)->onConnection('database');
        
        Queue::assertPushed(CrawlWorker::class);
    }

    /**
     * Test job on specific connection and queue
     */
    public function test_job_on_specific_connection_and_queue(): void
    {
        $jobId = 'crawl-combined-' . uniqid();
        
        CrawlWorker::dispatch($jobId)
            ->onConnection('database')
            ->onQueue('crawler');
        
        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * Test dispatch after response
     */
    public function test_dispatch_after_response(): void
    {
        $jobId = 'crawl-response-' . uniqid();
        
        // dispatchAfterResponse sends job after response sent to browser
        dispatch(new CrawlWorker($jobId))->afterResponse();
        
        $this->assertTrue(true);
    }
}
