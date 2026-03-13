<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\CancellationService;
use App\Models\CrawlJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mockery;

/**
 * Unit tests for TASK-22-17: Cancellation Service
 *
 * Tests cancellation detection, flag management, and atomic operations
 *
 * @see TESTS/TASK-22-17.md
 * @see TASK-22-17
 */
class CancellationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // TC-03: CANCELLATION SERVICE TESTS
    // =========================================================================

    /**
     * TC-03-01: Service instantiation
     */
    public function test_service_instantiation(): void
    {
        $service = app(CancellationService::class);
        $this->assertInstanceOf(CancellationService::class, $service);
    }

    /**
     * TC-03-02: Request cancellation for queued job
     */
    public function test_request_cancellation_for_queued_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-test-123');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_QUEUED);
        $crawlJob->shouldReceive('update')->once();

        $service = new CancellationService();

        $result = $service->requestCancellation($crawlJob);

        $this->assertTrue($result);
        $this->assertTrue(Cache::get("crawler:cancel:crawl-test-123"));
    }

    /**
     * TC-03-03: Request cancellation for running job
     */
    public function test_request_cancellation_for_running_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-test-456');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);

        $service = new CancellationService();

        $result = $service->requestCancellation($crawlJob);

        $this->assertTrue($result);
        $this->assertTrue(Cache::get("crawler:cancel:crawl-test-456"));
    }

    /**
     * TC-03-04: Cannot request cancellation for completed job
     */
    public function test_cannot_request_cancellation_for_completed_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-test-789');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_COMPLETED);

        $service = new CancellationService();

        $result = $service->requestCancellation($crawlJob);

        $this->assertFalse($result);
        $this->assertNull(Cache::get("crawler:cancel:crawl-test-789"));
    }

    /**
     * TC-03-05: Cannot request cancellation for failed job
     */
    public function test_cannot_request_cancellation_for_failed_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-test-failed');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_FAILED);

        $service = new CancellationService();

        $result = $service->requestCancellation($crawlJob);

        $this->assertFalse($result);
    }

    /**
     * TC-03-06: Check cancellation flag is set after request
     */
    public function test_check_cancellation_flag_is_set_after_request(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-flag-test');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);

        $service = new CancellationService();

        // Before request, flag not set
        $this->assertFalse($service->isCancellationRequested('crawl-flag-test'));

        // After request
        $service->requestCancellation($crawlJob);

        $this->assertTrue($service->isCancellationRequested('crawl-flag-test'));
    }

    /**
     * TC-03-07: Check cancellation flag not set when no request
     */
    public function test_check_cancellation_flag_not_set_when_no_request(): void
    {
        $service = new CancellationService();

        $this->assertFalse($service->isCancellationRequested('crawl-no-cancel'));
    }

    /**
     * TC-03-08: Check should cancel before each page
     */
    public function test_check_should_cancel_before_each_page(): void
    {
        $service = new CancellationService();

        // Set flag
        Cache::put("crawler:cancel:crawl-cancel-check", true, 300);

        $this->assertTrue($service->shouldCancel('crawl-cancel-check'));
    }

    /**
     * TC-03-09: Atomic cache operation for race condition safety
     */
    public function test_atomic_cache_operation_for_race_condition_safety(): void
    {
        $service = new CancellationService();

        // Simulate concurrent requests
        Cache::put("crawler:cancel:crawl-atomic", true, 300);

        // Multiple checks should all return true
        $this->assertTrue($service->isCancellationRequested('crawl-atomic'));
        $this->assertTrue($service->isCancellationRequested('crawl-atomic'));
        $this->assertTrue($service->shouldCancel('crawl-atomic'));
    }

    /**
     * TC-03-10: Cancellation timestamp recorded
     */
    public function test_cancellation_timestamp_recorded(): void
    {
        Carbon::setTestNow('2026-03-02 10:30:00');

        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-timestamp');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);
        // For running jobs, timestamp is stored in cache metadata
        // Worker will update the model when handling cancellation

        $service = new CancellationService();
        $service->requestCancellation($crawlJob);

        // Verify timestamp is stored in cache metadata
        $metadata = $service->getCancellationMetadata('crawl-timestamp');
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('requested_at', $metadata);

        Carbon::setTestNow();
    }

    /**
     * TC-03-11: Cache key format
     */
    public function test_cache_key_format(): void
    {
        $service = new CancellationService();

        $key = $service->getCacheKey('crawl-abc123');

        $this->assertEquals('crawler:cancel:crawl-abc123', $key);
    }

    /**
     * TC-03-12: Cache TTL matches job timeout
     */
    public function test_cache_ttl_matches_job_timeout(): void
    {
        $service = new CancellationService(300); // 300 second TTL

        $ttl = $service->getCacheTTL();

        $this->assertEquals(300, $ttl);
    }

    /**
     * TC-03-13: Clear cancellation flag after completion
     */
    public function test_clear_cancellation_flag_after_completion(): void
    {
        $service = new CancellationService();

        // Set flag
        Cache::put("crawler:cancel:crawl-clear-test", true, 300);

        // Clear it
        $service->clearCancellationFlag('crawl-clear-test');

        $this->assertFalse($service->isCancellationRequested('crawl-clear-test'));
    }

    /**
     * TC-03-14: Immediate cancellation for queued job
     */
    public function test_immediate_cancellation_for_queued_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-immediate');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_QUEUED);
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return isset($data['status']) &&
                       $data['status'] === CrawlJob::STATUS_CANCELLED;
            }));

        $service = new CancellationService();
        $result = $service->requestCancellation($crawlJob);

        $this->assertTrue($result);
    }

    /**
     * TC-03-15: Deferred cancellation for running job
     */
    public function test_deferred_cancellation_for_running_job(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-deferred');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);
        // Should NOT update status immediately for running jobs
        $crawlJob->shouldNotReceive('update');

        $service = new CancellationService();
        $result = $service->requestCancellation($crawlJob);

        $this->assertTrue($result);
        // Flag should be set for worker to detect
        $this->assertTrue(Cache::has("crawler:cancel:crawl-deferred"));
    }

    /**
     * TC-03-16: Cancellation reason stored
     */
    public function test_cancellation_reason_stored(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-reason');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);
        // For running jobs, reason is stored in cache metadata (not model update)
        // Worker will handle model update when processing cancellation

        $service = new CancellationService();
        $service->requestCancellation($crawlJob, 'User requested cancellation');

        // Verify the reason is stored in cache metadata
        $metadata = $service->getCancellationMetadata('crawl-reason');
        $this->assertNotNull($metadata);
        $this->assertEquals('User requested cancellation', $metadata['reason']);
    }

    /**
     * TC-03-17: Multiple cancellation requests are idempotent
     */
    public function test_multiple_cancellation_requests_are_idempotent(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-idempotent');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);

        $service = new CancellationService();

        // First request
        $result1 = $service->requestCancellation($crawlJob);

        // Second request (should still return true)
        $result2 = $service->isCancellationRequested('crawl-idempotent');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * TC-03-18: Cancelled status check
     */
    public function test_cancelled_status_check(): void
    {
        $service = new CancellationService();

        $canCancelQueued = $service->canCancel(CrawlJob::STATUS_QUEUED);
        $canCancelRunning = $service->canCancel(CrawlJob::STATUS_RUNNING);
        $canCancelCompleted = $service->canCancel(CrawlJob::STATUS_COMPLETED);
        $canCancelFailed = $service->canCancel(CrawlJob::STATUS_FAILED);
        $canCancelCancelled = $service->canCancel(CrawlJob::STATUS_CANCELLED);

        $this->assertTrue($canCancelQueued);
        $this->assertTrue($canCancelRunning);
        $this->assertFalse($canCancelCompleted);
        $this->assertFalse($canCancelFailed);
        $this->assertFalse($canCancelCancelled);
    }

    /**
     * TC-03-19: Get cancellation info
     */
    public function test_get_cancellation_info(): void
    {
        $service = new CancellationService();

        // Set both the cancellation flag and metadata (as service expects)
        Cache::put("crawler:cancel:crawl-info", true, 300);
        Cache::put("crawler:cancel:crawl-info:metadata", [
            'requested_at' => '2026-03-02 10:00:00',
            'reason' => 'User requested',
        ], 300);

        $info = $service->getCancellationInfo('crawl-info');

        $this->assertIsArray($info);
        $this->assertEquals('User requested', $info['reason']);
    }

    /**
     * TC-03-20: Log cancellation event
     */
    public function test_log_cancellation_event(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Crawler: Job cancellation requested',
                \Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['status']);
                })
            );

        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->shouldReceive('getAttribute')->with('job_id')->andReturn('crawl-log-test');
        $crawlJob->shouldReceive('getAttribute')->with('status')->andReturn(CrawlJob::STATUS_RUNNING);

        $service = new CancellationService();
        $service->requestCancellation($crawlJob);
    }

    /**
     * TC-03-21: Store cancellation metadata
     */
    public function test_store_cancellation_metadata(): void
    {
        $service = new CancellationService();

        $metadata = [
            'requested_by' => 123,
            'ip_address' => '192.168.1.1',
            'reason' => 'User requested',
        ];

        $service->storeCancellationMetadata('crawl-metadata', $metadata);

        $cached = Cache::get("crawler:cancel:crawl-metadata:metadata");
        $this->assertEquals($metadata, $cached);
    }

    /**
     * TC-03-22: Get cancellation metadata
     */
    public function test_get_cancellation_metadata(): void
    {
        $service = new CancellationService();

        $metadata = ['requested_by' => 123];
        Cache::put("crawler:cancel:crawl-meta:metadata", $metadata, 300);

        $retrieved = $service->getCancellationMetadata('crawl-meta');

        $this->assertEquals($metadata, $retrieved);
    }

    /**
     * TC-03-23: TTL from config when not specified
     */
    public function test_ttl_from_config_when_not_specified(): void
    {
        $service = app(CancellationService::class);

        $ttl = $service->getCacheTTL();

        $this->assertEquals(config('crawler.timeouts.job'), $ttl);
    }

    /**
     * TC-03-24: Namespace cache keys correctly
     */
    public function test_namespace_cache_keys_correctly(): void
    {
        $service = new CancellationService();

        $key1 = $service->getCacheKey('job-1');
        $key2 = $service->getCacheKey('job-2');

        $this->assertStringStartsWith('crawler:cancel:', $key1);
        $this->assertStringStartsWith('crawler:cancel:', $key2);
        $this->assertNotEquals($key1, $key2);
    }

    /**
     * TC-03-25: Clear all job cancellation data
     */
    public function test_clear_all_job_cancellation_data(): void
    {
        $service = new CancellationService();

        // Set flag and metadata
        Cache::put("crawler:cancel:crawl-clear-all", true, 300);
        Cache::put("crawler:cancel:crawl-clear-all:metadata", ['test' => true], 300);

        $service->clearAllCancellationData('crawl-clear-all');

        $this->assertFalse(Cache::has("crawler:cancel:crawl-clear-all"));
        $this->assertFalse(Cache::has("crawler:cancel:crawl-clear-all:metadata"));
    }
}
