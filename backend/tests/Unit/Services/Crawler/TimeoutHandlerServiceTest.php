<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\TimeoutHandlerService;
use Carbon\Carbon;
use Mockery;

/**
 * Unit tests for TASK-22-17: Timeout Handler Service
 *
 * Tests timeout detection, tracking, and graceful shutdown initiation
 *
 * @see TESTS/TASK-22-17.md
 * @see TASK-22-17
 */
class TimeoutHandlerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // TC-02: TIMEOUT HANDLER SERVICE TESTS
    // =========================================================================

    /**
     * TC-02-01: Service instantiation
     */
    public function test_service_instantiation(): void
    {
        $service = app(TimeoutHandlerService::class);
        $this->assertInstanceOf(TimeoutHandlerService::class, $service);
    }

    /**
     * TC-02-02: Start time recorded when tracking starts
     */
    public function test_start_time_recorded_when_tracking_starts(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService();
        $service->startTracking('crawl-test-123');

        $startTime = $service->getStartTime('crawl-test-123');

        $this->assertInstanceOf(Carbon::class, $startTime);
        $this->assertEquals('2026-03-02 10:00:00', $startTime->toDateTimeString());
    }

    /**
     * TC-02-03: Elapsed time calculated correctly
     */
    public function test_elapsed_time_calculated_correctly(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService();
        $service->startTracking('crawl-test-123');

        // Advance time by 10 seconds
        Carbon::setTestNow('2026-03-02 10:00:10');

        $elapsed = $service->getElapsedSeconds('crawl-test-123');

        $this->assertEquals(10, $elapsed);
    }

    /**
     * TC-02-04: Remaining time calculated correctly
     */
    public function test_remaining_time_calculated_correctly(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300); // 5 minute timeout
        $service->startTracking('crawl-test-123');

        // Advance time by 100 seconds
        Carbon::setTestNow('2026-03-02 10:01:40');

        $remaining = $service->getRemainingSeconds('crawl-test-123');

        $this->assertEquals(200, $remaining);
    }

    /**
     * TC-02-05: Approaching timeout detection when remaining < 60s
     */
    public function test_approaching_timeout_detection(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        // Advance to 250 seconds (50 remaining)
        Carbon::setTestNow('2026-03-02 10:04:10');

        $this->assertTrue($service->isApproachingTimeout('crawl-test-123', 60));
    }

    /**
     * TC-02-06: Timeout exceeded detection
     */
    public function test_timeout_exceeded_detection(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        // Advance past timeout
        Carbon::setTestNow('2026-03-02 10:06:00'); // 360 seconds

        $this->assertTrue($service->isTimeoutExceeded('crawl-test-123'));
    }

    /**
     * TC-02-07: Grace period start detection
     */
    public function test_grace_period_start_detection(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300, 30); // 5 min timeout, 30s grace
        $service->startTracking('crawl-test-123');

        // Advance to 275 seconds (25 remaining, less than 30s grace period)
        Carbon::setTestNow('2026-03-02 10:04:35');

        $this->assertTrue($service->shouldStartGracefulShutdown('crawl-test-123'));
    }

    /**
     * TC-02-08: Can continue crawling when time remaining
     */
    public function test_can_continue_crawling_when_time_remaining(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        // Advance 250 seconds (50 remaining)
        Carbon::setTestNow('2026-03-02 10:04:10');

        $this->assertTrue($service->canContinueCrawling('crawl-test-123'));
    }

    /**
     * TC-02-09: Cannot continue when timeout exceeded
     */
    public function test_cannot_continue_when_timeout_exceeded(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        // Advance past timeout
        Carbon::setTestNow('2026-03-02 10:06:00');

        $this->assertFalse($service->canContinueCrawling('crawl-test-123'));
    }

    /**
     * TC-02-10: Timeout event logged with context
     */
    public function test_timeout_event_logged_with_context(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->with(
                'Crawler: Job timeout exceeded',
                \Mockery::on(function ($context) {
                    return isset($context['jobId']) &&
                           isset($context['elapsed']) &&
                           isset($context['timeout']);
                })
            );

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        Carbon::setTestNow('2026-03-02 10:06:00');

        $service->logTimeoutEvent('crawl-test-123');
    }

    /**
     * TC-02-11: Custom timeout via options
     */
    public function test_custom_timeout_via_options(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123', ['timeout' => 120]);

        // Advance 110 seconds (still under custom 120s timeout)
        Carbon::setTestNow('2026-03-02 10:01:50');

        $this->assertTrue($service->canContinueCrawling('crawl-test-123'));

        // Advance to 130 seconds (past custom 120s timeout)
        Carbon::setTestNow('2026-03-02 10:02:10');

        $this->assertFalse($service->canContinueCrawling('crawl-test-123'));
    }

    /**
     * TC-02-12: Multiple jobs tracked independently
     */
    public function test_multiple_jobs_tracked_independently(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-job-a');
        $service->startTracking('crawl-job-b');

        // Advance 100 seconds for job A
        Carbon::setTestNow('2026-03-02 10:01:40');

        $elapsedA = $service->getElapsedSeconds('crawl-job-a');
        $elapsedB = $service->getElapsedSeconds('crawl-job-b');

        $this->assertEquals(100, $elapsedA);
        $this->assertEquals(100, $elapsedB);

        // Stop tracking job A
        $service->stopTracking('crawl-job-a');

        // Advance another 100 seconds
        Carbon::setTestNow('2026-03-02 10:03:20');

        // Job B should have 200 elapsed
        $this->assertEquals(200, $service->getElapsedSeconds('crawl-job-b'));

        // Job A should not have tracking data
        $this->assertNull($service->getStartTime('crawl-job-a'));
    }

    /**
     * TC-02-13: Clear tracking on completion
     */
    public function test_clear_tracking_on_completion(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        $service->stopTracking('crawl-test-123');

        $this->assertNull($service->getStartTime('crawl-test-123'));
        $this->assertEquals(0, $service->getElapsedSeconds('crawl-test-123'));
    }

    /**
     * TC-02-14: Get timeout context for logging
     */
    public function test_get_timeout_context_for_logging(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300, 30);
        $service->startTracking('crawl-test-123');

        Carbon::setTestNow('2026-03-02 10:02:30'); // 150 seconds elapsed

        $context = $service->getTimeoutContext('crawl-test-123');

        $this->assertIsArray($context);
        $this->assertArrayHasKey('jobId', $context);
        $this->assertArrayHasKey('elapsed', $context);
        $this->assertArrayHasKey('remaining', $context);
        $this->assertArrayHasKey('timeout', $context);
        $this->assertArrayHasKey('grace_period', $context);

        $this->assertEquals(150, $context['elapsed']);
        $this->assertEquals(150, $context['remaining']);
    }

    /**
     * TC-02-15: Page timeout validation
     */
    public function test_page_timeout_validation(): void
    {
        $service = new TimeoutHandlerService(300, 30, 30); // job, grace, page timeout

        // Page took 25 seconds (under limit)
        $this->assertFalse($service->isPageTimeout(25));

        // Page took 35 seconds (over limit)
        $this->assertTrue($service->isPageTimeout(35));

        // Page took exactly 30 seconds (at limit)
        $this->assertFalse($service->isPageTimeout(30));
    }

    /**
     * TC-02-16: Get time until grace period starts
     */
    public function test_get_time_until_grace_period_starts(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300, 30);
        $service->startTracking('crawl-test-123');

        // 270 seconds until grace (300 - 30)
        // At start, 270 seconds until grace
        $this->assertEquals(270, $service->getSecondsUntilGrace('crawl-test-123'));

        // Advance 100 seconds
        Carbon::setTestNow('2026-03-02 10:01:40');

        $this->assertEquals(170, $service->getSecondsUntilGrace('crawl-test-123'));
    }

    /**
     * TC-02-17: Check if within safe time for next page
     */
    public function test_check_if_within_safe_time_for_next_page(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300, 30, 30); // 5 min job, 30s grace, 30s page
        $service->startTracking('crawl-test-123');

        // At 200 seconds, we have 100 seconds remaining
        // Grace starts at 270 seconds, so we have 70 seconds of safe time
        // Enough for a 30-second page operation
        Carbon::setTestNow('2026-03-02 10:03:20');

        $this->assertTrue($service->hasTimeForNextPage('crawl-test-123'));
    }

    /**
     * TC-02-18: Not enough time for next page
     */
    public function test_not_enough_time_for_next_page(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300, 30, 30);
        $service->startTracking('crawl-test-123');

        // At 280 seconds, we only have 20 seconds until timeout
        // Grace period is about to start, not enough for a 30s page
        Carbon::setTestNow('2026-03-02 10:04:40');

        $this->assertFalse($service->hasTimeForNextPage('crawl-test-123'));
    }

    /**
     * TC-02-19: Get formatted duration string
     */
    public function test_get_formatted_duration_string(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');

        Carbon::setTestNow('2026-03-02 10:02:30'); // 150 seconds

        $formatted = $service->getFormattedDuration('crawl-test-123');

        $this->assertEquals('2m 30s', $formatted);
    }

    /**
     * TC-02-20: Zero elapsed when not tracking
     */
    public function test_zero_elapsed_when_not_tracking(): void
    {
        $service = new TimeoutHandlerService(300);

        $elapsed = $service->getElapsedSeconds('nonexistent-job');

        $this->assertEquals(0, $elapsed);
    }

    /**
     * TC-02-21: Full timeout remaining when not tracking
     */
    public function test_full_timeout_remaining_when_not_tracking(): void
    {
        $service = new TimeoutHandlerService(300);

        $remaining = $service->getRemainingSeconds('nonexistent-job');

        $this->assertEquals(300, $remaining);
    }

    /**
     * TC-02-22: Timeout from config when not specified
     */
    public function test_timeout_from_config_when_not_specified(): void
    {
        $service = app(TimeoutHandlerService::class);

        $timeout = $service->getDefaultTimeout();

        $this->assertEquals(config('crawler.timeouts.job'), $timeout);
    }

    /**
     * TC-02-23: Grace period from config when not specified
     */
    public function test_grace_period_from_config_when_not_specified(): void
    {
        $service = app(TimeoutHandlerService::class);

        $grace = $service->getDefaultGracePeriod();

        $this->assertEquals(config('crawler.timeouts.grace_period'), $grace);
    }

    /**
     * TC-02-24: Page timeout from config when not specified
     */
    public function test_page_timeout_from_config_when_not_specified(): void
    {
        $service = app(TimeoutHandlerService::class);

        $pageTimeout = $service->getDefaultPageTimeout();

        $this->assertEquals(config('crawler.timeouts.page'), $pageTimeout);
    }

    /**
     * TC-02-25: Tracking does not persist after stop
     */
    public function test_tracking_does_not_persist_after_stop(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');

        $service = new TimeoutHandlerService(300);
        $service->startTracking('crawl-test-123');
        $service->stopTracking('crawl-test-123');

        // Start new tracking
        Carbon::setTestNow('2026-03-02 10:05:00');
        $service->startTracking('crawl-test-123');

        $startTime = $service->getStartTime('crawl-test-123');
        $this->assertEquals('2026-03-02 10:05:00', $startTime->toDateTimeString());
    }
}
