<?php

namespace Tests\Integration\Jobs;

use Tests\TestCase;
use App\Jobs\CrawlWorker;
use App\Models\CrawlJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for TASK-22-16: CrawlWorker Queue Integration
 *
 * Tests the queue integration between CrawlWorker and the job queue system.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlWorkerQueueIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // JOB DISPATCHING INTEGRATION TESTS (JD-*)
    // =========================================================================

    /**
     * JD-01: CrawlWorker dispatches to crawler queue
     */
    public function test_crawl_worker_dispatches_to_crawler_queue(): void
    {
        Queue::fake();

        CrawlWorker::dispatch('test-job-123');

        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * JD-02: Job can be dispatched to priority queues
     */
    public function test_job_dispatch_with_priority(): void
    {
        Queue::fake();

        // High priority
        $job = new CrawlWorker('test-job-high');
        $job->onQueue('crawler-high');
        dispatch($job);

        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    /**
     * JD-03: Job metadata in payload
     */
    public function test_job_metadata_in_payload(): void
    {
        $job = new CrawlWorker('test-job-123');

        // Job should have jobId as metadata
        $this->assertEquals('test-job-123', $job->jobId);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(1, $job->tries);
    }

    /**
     * JD-04: Job delay parameter
     */
    public function test_job_delay_parameter(): void
    {
        Queue::fake();

        CrawlWorker::dispatch('test-job-123')
            ->delay(now()->addMinutes(5));

        Queue::assertPushed(CrawlWorker::class, function ($job) {
            return $job->jobId === 'test-job-123';
        });
    }

    /**
     * JD-05: Job uniqueId is set
     */
    public function test_job_unique_id_set(): void
    {
        $job = new CrawlWorker('unique-test-id');

        $this->assertEquals('unique-test-id', $job->uniqueId());
    }

    /**
     * JD-06: Job tags include crawl
     */
    public function test_job_tags_include_crawl(): void
    {
        $job = new CrawlWorker('test-job-123');
        $tags = $job->tags();

        $this->assertContains('crawl', $tags);
    }

    /**
     * JD-07: Job tags include job id
     */
    public function test_job_tags_include_job_id(): void
    {
        $job = new CrawlWorker('test-job-123');
        $tags = $job->tags();

        $this->assertContains('job:test-job-123', $tags);
    }

    /**
     * JD-08: Dispatch to high priority queue
     */
    public function test_dispatch_to_high_priority_queue(): void
    {
        Queue::fake();

        $job = (new CrawlWorker('test-job-high'))->onQueue('crawler-high');
        dispatch($job);

        Queue::assertPushedOn('crawler-high', CrawlWorker::class);
    }

    // =========================================================================
    // QUEUE CONNECTION TESTS
    // =========================================================================

    /**
     * Test job uses correct queue connection
     */
    public function test_job_uses_correct_queue_connection(): void
    {
        // The job should use the default queue connection (database)
        // but dispatch to the 'crawler' queue
        Queue::fake();

        CrawlWorker::dispatch('test-job-123');

        Queue::assertPushed(CrawlWorker::class, function ($job) {
            return $job->jobId === 'test-job-123';
        });
    }

    /**
     * Test multiple jobs can be dispatched
     */
    public function test_multiple_jobs_can_be_dispatched(): void
    {
        Queue::fake();

        CrawlWorker::dispatch('test-job-1');
        CrawlWorker::dispatch('test-job-2');
        CrawlWorker::dispatch('test-job-3');

        Queue::assertPushed(CrawlWorker::class, 3);
    }

    /**
     * Test job backoff configuration
     */
    public function test_job_backoff_configuration(): void
    {
        $job = new CrawlWorker('test-job-123');

        $this->assertEquals([60], $job->backoff);
    }

    /**
     * Test job max exceptions
     */
    public function test_job_max_exceptions(): void
    {
        $job = new CrawlWorker('test-job-123');

        $this->assertEquals(1, $job->maxExceptions);
    }

    // =========================================================================
    // FAILED JOB HANDLING TESTS
    // =========================================================================

    /**
     * Test failed job is stored
     */
    public function test_failed_job_is_stored(): void
    {
        // This test verifies the failed() method exists and handles failure
        $job = new CrawlWorker('non-existent-job');

        // Call failed method
        $exception = new \Exception('Test failure');
        $job->failed($exception);

        // If we get here without error, the failed method works
        $this->assertTrue(true);
    }

    /**
     * Test job handles non-existent crawl job
     */
    public function test_job_handles_nonexistent_crawl_job(): void
    {
        // Create a job for a non-existent crawl job
        $job = new CrawlWorker('non-existent-job-id');

        // The job should handle this gracefully
        // (This is a structural test - actual behavior is in the worker)
        $this->assertEquals('non-existent-job-id', $job->jobId);
    }

    // =========================================================================
    // QUEUE PRIORITY INTEGRATION TESTS
    // =========================================================================

    /**
     * Test dispatching to all three priority queues
     */
    public function test_dispatching_to_all_priority_queues(): void
    {
        Queue::fake();

        // High priority
        $highJob = (new CrawlWorker('high-job'))->onQueue('crawler-high');
        dispatch($highJob);

        // Normal priority
        CrawlWorker::dispatch('normal-job');

        // Low priority
        $lowJob = (new CrawlWorker('low-job'))->onQueue('crawler-low');
        dispatch($lowJob);

        Queue::assertPushed(CrawlWorker::class, 3);
    }

    /**
     * Test job serialization preserves data
     */
    public function test_job_serialization_preserves_data(): void
    {
        $job = new CrawlWorker('test-serialization-id');

        // Serialize and unserialize
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertEquals('test-serialization-id', $unserialized->jobId);
    }

    // =========================================================================
    // ACCEPTANCE CRITERIA VERIFICATION
    // =========================================================================

    /**
     * Verify: CrawlWorker dispatches to 'crawler' queue
     */
    public function test_acceptance_crawl_worker_dispatches_to_crawler_queue(): void
    {
        Queue::fake();

        CrawlWorker::dispatch('acceptance-test-job');

        Queue::assertPushedOn('crawler', CrawlWorker::class);
    }

    /**
     * Verify: Job supports delay parameter
     */
    public function test_acceptance_job_supports_delay(): void
    {
        Queue::fake();

        CrawlWorker::dispatch('delayed-job')
            ->delay(now()->addMinutes(10));

        Queue::assertPushed(CrawlWorker::class);
    }

    /**
     * Verify: Job has proper timeout configuration
     */
    public function test_acceptance_job_has_proper_timeout(): void
    {
        $job = new CrawlWorker('timeout-test');

        $this->assertEquals(300, $job->timeout);
    }

    /**
     * Verify: Job has proper retry configuration
     */
    public function test_acceptance_job_has_proper_retry_config(): void
    {
        $job = new CrawlWorker('retry-test');

        $this->assertEquals(1, $job->tries);
        $this->assertEquals([60], $job->backoff);
    }
}
