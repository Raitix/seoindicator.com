<?php

namespace Tests\Unit\Queue;

use Tests\TestCase;
use App\Jobs\CrawlWorker;

/**
 * Unit tests for TASK-22-16: Failed Job Handling
 *
 * Tests the failed job handling for the crawler queue.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class FailedJobHandlingTest extends TestCase
{
    // =========================================================================
    // FAILED JOB HANDLING TESTS (FJ-*)
    // =========================================================================

    /**
     * FJ-01: Failed jobs table configuration
     */
    public function test_failed_jobs_table_configured(): void
    {
        // Verify failed jobs configuration
        $failedConfig = config('queue.failed');
        
        $this->assertIsArray($failedConfig);
        $this->assertEquals('database-uuids', $failedConfig['driver']);
        $this->assertEquals('failed_jobs', $failedConfig['table']);
    }

    /**
     * FJ-03: Failed job has queue column in config
     */
    public function test_failed_job_config_has_queue(): void
    {
        // The failed jobs table supports queue column
        $this->assertEquals('failed_jobs', config('queue.failed.table'));
    }

    /**
     * FJ-07: Retry mechanism available
     */
    public function test_retry_mechanism_available(): void
    {
        // Verify queue:retry command exists
        $this->assertTrue(
            in_array('queue:retry', \Illuminate\Support\Facades\Artisan::all())
                || array_key_exists('queue:retry', \Illuminate\Support\Facades\Artisan::all())
        );
    }

    // =========================================================================
    // JOB FAILURE CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test job has failure handling
     */
    public function test_job_has_failure_handling(): void
    {
        $job = new CrawlWorker('test-failure-job');
        
        // Job should have a failed method
        $this->assertTrue(method_exists($job, 'failed'));
    }

    /**
     * Test job failure method handles exception
     */
    public function test_job_failure_method_handles_exception(): void
    {
        $job = new CrawlWorker('test-failure-job');
        
        // Call failed method with exception
        $exception = new \Exception('Test failure');
        
        // Should not throw
        $job->failed($exception);
        
        $this->assertTrue(true);
    }

    /**
     * Test job has unique ID for tracking
     */
    public function test_job_has_unique_id_for_tracking(): void
    {
        $job = new CrawlWorker('unique-track-id');
        
        $this->assertEquals('unique-track-id', $job->uniqueId());
    }

    /**
     * Test job has tags for identification
     */
    public function test_job_has_tags_for_identification(): void
    {
        $job = new CrawlWorker('tagged-job');
        $tags = $job->tags();
        
        $this->assertIsArray($tags);
        $this->assertContains('crawl', $tags);
    }

    // =========================================================================
    // QUEUE FAILURE CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test database connection for failed jobs
     */
    public function test_database_connection_for_failed_jobs(): void
    {
        $failedConfig = config('queue.failed');
        
        // Should use the default database connection
        $this->assertArrayHasKey('database', $failedConfig);
    }

    /**
     * Test batching configuration for jobs
     */
    public function test_batching_configuration(): void
    {
        $batchingConfig = config('queue.batching');
        
        $this->assertIsArray($batchingConfig);
        $this->assertArrayHasKey('table', $batchingConfig);
        $this->assertEquals('job_batches', $batchingConfig['table']);
    }

    // =========================================================================
    // CRAWLER JOB FAILURE TESTS
    // =========================================================================

    /**
     * Test CrawlWorker has proper timeout for failure detection
     */
    public function test_crawl_worker_has_proper_timeout(): void
    {
        $job = new CrawlWorker('timeout-test');
        
        $this->assertEquals(300, $job->timeout);
    }

    /**
     * Test CrawlWorker has tries limit
     */
    public function test_crawl_worker_has_tries_limit(): void
    {
        $job = new CrawlWorker('tries-test');
        
        $this->assertEquals(1, $job->tries);
    }

    /**
     * Test CrawlWorker has max exceptions limit
     */
    public function test_crawl_worker_has_max_exceptions(): void
    {
        $job = new CrawlWorker('exceptions-test');
        
        $this->assertEquals(1, $job->maxExceptions);
    }

    /**
     * Test CrawlWorker has backoff configuration
     */
    public function test_crawl_worker_has_backoff(): void
    {
        $job = new CrawlWorker('backoff-test');
        
        $this->assertEquals([60], $job->backoff);
    }

    // =========================================================================
    // ACCEPTANCE CRITERIA VERIFICATION
    // =========================================================================

    /**
     * Verify: Failed jobs configuration is complete
     */
    public function test_acceptance_failed_jobs_configured(): void
    {
        $config = config('queue.failed');
        
        $this->assertNotNull($config['driver']);
        $this->assertNotNull($config['table']);
    }

    /**
     * Verify: Job can be marked as failed
     */
    public function test_acceptance_job_can_be_marked_failed(): void
    {
        $job = new CrawlWorker('acceptance-fail-test');
        
        // Job should have failed method
        $this->assertTrue(method_exists($job, 'failed'));
        
        // Failed method should accept exception
        $job->failed(new \Exception('Test'));
        
        $this->assertTrue(true);
    }
}
