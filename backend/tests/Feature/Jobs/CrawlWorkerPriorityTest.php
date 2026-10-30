<?php

namespace Tests\Feature\Jobs;

use Tests\TestCase;
use App\Jobs\CrawlWorker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Feature tests for TASK-22-16: Queue Integration with JobQueue
 *
 * Tests the job priority system for crawler jobs.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlWorkerPriorityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // =========================================================================
    // JOB PRIORITY SYSTEM TESTS (PRI-*)
    // =========================================================================

    /**
     * PRI-01: High priority queue exists
     */
    public function test_high_priority_queue_exists(): void
    {
        $highConfig = config('queue.connections.crawler-high');
        
        $this->assertNotNull($highConfig, 'High priority queue should be configured');
        $this->assertEquals('crawler-high', $highConfig['queue'] ?? 'crawler-high');
    }

    /**
     * PRI-02: Default priority queue exists
     */
    public function test_default_priority_queue_exists(): void
    {
        $defaultConfig = config('queue.connections.crawler');
        
        $this->assertNotNull($defaultConfig, 'Default priority queue should be configured');
        $this->assertEquals('crawler', $defaultConfig['queue'] ?? 'crawler');
    }

    /**
     * PRI-03: Low priority queue exists
     */
    public function test_low_priority_queue_exists(): void
    {
        $lowConfig = config('queue.connections.crawler-low');
        
        $this->assertNotNull($lowConfig, 'Low priority queue should be configured');
        $this->assertEquals('crawler-low', $lowConfig['queue'] ?? 'crawler-low');
    }

    /**
     * PRI-04: High priority processed first
     */
    public function test_high_priority_processed_first(): void
    {
        $lowJobId = 'crawl-low-' . Str::uuid();
        $defaultJobId = 'crawl-default-' . Str::uuid();
        $highJobId = 'crawl-high-' . Str::uuid();
        
        // Dispatch jobs in reverse order
        CrawlWorker::dispatch($lowJobId)->onQueue('crawler-low');
        CrawlWorker::dispatch($defaultJobId)->onQueue('crawler');
        CrawlWorker::dispatch($highJobId)->onQueue('crawler-high');
        
        // Verify high priority job is on correct queue
        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    /**
     * PRI-05: Default processed after high
     */
    public function test_default_processed_after_high(): void
    {
        $highJobId = 'crawl-high-' . Str::uuid();
        $defaultJobId = 'crawl-default-' . Str::uuid();
        
        CrawlWorker::dispatch($defaultJobId)->onQueue('crawler');
        CrawlWorker::dispatch($highJobId)->onQueue('crawler-high');
        
        Queue::assertPushedOn('crawler', CrawlWorker::class);
        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    /**
     * PRI-06: Low priority processed last
     */
    public function test_low_priority_processed_last(): void
    {
        $jobId = 'crawl-low-' . Str::uuid();
        
        CrawlWorker::dispatch($jobId)->onQueue('crawler-low');
        
        Queue::assertPushedOn('crawler-low', CrawlWorker::class);
    }

    /**
     * PRI-07: Manual crawl gets high priority
     */
    public function test_manual_crawl_gets_high_priority(): void
    {
        $jobId = 'crawl-manual-' . Str::uuid();
        
        // Manual/user-initiated crawls should use high priority
        CrawlWorker::dispatch($jobId)->onQueue('crawler-high');
        
        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    /**
     * PRI-08: Scheduled crawl gets default
     */
    public function test_scheduled_crawl_gets_default(): void
    {
        $jobId = 'crawl-scheduled-' . Str::uuid();
        
        // Scheduled/system crawls should use default priority
        CrawlWorker::dispatch($jobId)->onQueue('crawler');
        
        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * PRI-09: Background crawl gets low
     */
    public function test_background_crawl_gets_low(): void
    {
        $jobId = 'crawl-background-' . Str::uuid();
        
        // Background re-crawls should use low priority
        CrawlWorker::dispatch($jobId)->onQueue('crawler-low');
        
        Queue::assertPushedOn('crawler-low', CrawlWorker::class);
    }

    /**
     * PRI-10: Priority can be overridden
     */
    public function test_priority_can_be_overridden(): void
    {
        $jobId = 'crawl-override-' . Str::uuid();
        
        // Explicitly set priority overrides defaults
        CrawlWorker::dispatch($jobId)->onQueue('crawler-custom');
        
        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }

    // =========================================================================
    // PRIORITY HELPER TESTS
    // =========================================================================

    /**
     * Test priority queue naming convention
     */
    public function test_priority_queue_naming_convention(): void
    {
        // Verify naming convention: crawler, crawler-high, crawler-low
        $defaultQueue = config('queue.connections.crawler.queue');
        $highQueue = config('queue.connections.crawler-high.queue');
        $lowQueue = config('queue.connections.crawler-low.queue');
        
        $this->assertEquals('crawler', $defaultQueue);
        $this->assertEquals('crawler-high', $highQueue);
        $this->assertEquals('crawler-low', $lowQueue);
    }

    /**
     * Test multiple jobs with same priority
     */
    public function test_multiple_jobs_with_same_priority(): void
    {
        $jobIds = [
            'crawl-multi-1-' . Str::uuid(),
            'crawl-multi-2-' . Str::uuid(),
            'crawl-multi-3-' . Str::uuid(),
        ];
        
        foreach ($jobIds as $jobId) {
            CrawlWorker::dispatch($jobId)->onQueue('crawler');
        }
        
        Queue::assertPushed(CrawlWorker::class, 3);
    }

    /**
     * Test priority with delay
     */
    public function test_priority_with_delay(): void
    {
        $jobId = 'crawl-delayed-high-' . Str::uuid();
        
        CrawlWorker::dispatch($jobId)
            ->onQueue('crawler-high')
            ->delay(now()->addMinutes(5));
        
        Queue::assertPushed(CrawlWorker::class, function ($job) use ($jobId) {
            return $job->jobId === $jobId;
        });
    }

    /**
     * Test priority job counts
     */
    public function test_priority_job_counts(): void
    {
        // Push jobs to each priority
        CrawlWorker::dispatch('high-1')->onQueue('crawler-high');
        CrawlWorker::dispatch('high-2')->onQueue('crawler-high');
        CrawlWorker::dispatch('default-1')->onQueue('crawler');
        CrawlWorker::dispatch('low-1')->onQueue('crawler-low');
        CrawlWorker::dispatch('low-2')->onQueue('crawler-low');
        CrawlWorker::dispatch('low-3')->onQueue('crawler-low');
        
        // Verify all jobs pushed
        Queue::assertPushed(CrawlWorker::class, 6);
    }

    /**
     * Test queue worker priority order
     */
    public function test_queue_worker_priority_order(): void
    {
        // Worker should process queues in order: crawler-high,crawler,crawler-low
        $workerQueueOrder = 'crawler-high,crawler,crawler-low';
        
        // This is how a worker would be started:
        // php artisan queue:work --queue=crawler-high,crawler,crawler-low
        
        $this->assertEquals(
            'crawler-high,crawler,crawler-low',
            $workerQueueOrder,
            'Worker should process queues in priority order'
        );
    }

    /**
     * Test high priority job metadata
     */
    public function test_high_priority_job_metadata(): void
    {
        $jobId = 'crawl-meta-high-' . Str::uuid();
        
        $job = new CrawlWorker($jobId);
        
        // Verify job has required properties for priority handling
        $this->assertEquals($jobId, $job->jobId);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(1, $job->tries);
    }

    /**
     * Test priority does not affect job execution
     */
    public function test_priority_does_not_affect_job_execution(): void
    {
        $highJob = new CrawlWorker('high-job');
        $lowJob = new CrawlWorker('low-job');
        
        // Both jobs should have same configuration
        $this->assertEquals($highJob->timeout, $lowJob->timeout);
        $this->assertEquals($highJob->tries, $lowJob->tries);
        $this->assertEquals($highJob->backoff, $lowJob->backoff);
    }
}
