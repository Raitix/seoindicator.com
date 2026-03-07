<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\User;
use App\Services\WebhookRetryService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-6-15: Webhook Retry Logic
 *
 * Tests the WebhookRetryService responsible for:
 * - Calculating retry intervals with exponential backoff
 * - Determining if errors should trigger retry
 * - Managing retry attempt limits
 */
class WebhookRetryServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebhookRetryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WebhookRetryService::class);
    }

    // =========================================================================
    // RETRY SCHEDULE CALCULATION TESTS (RL-001 to RL-007)
    // =========================================================================

    /**
     * RL-001: Calculate next retry time for 1st attempt
     * Expected: 1 minute from now
     */
    public function test_calculate_next_retry_for_first_attempt(): void
    {
        $nextRetry = $this->service->calculateNextRetryAt(0);

        $this->assertNotNull($nextRetry);
        $expected = now()->addMinute();
        $this->assertTrue(
            $nextRetry->diffInSeconds($expected) < 10,
            'First retry should be scheduled approximately 1 minute from now'
        );
    }

    /**
     * RL-002: Calculate next retry time for 2nd attempt
     * Expected: 5 minutes from now
     */
    public function test_calculate_next_retry_for_second_attempt(): void
    {
        $nextRetry = $this->service->calculateNextRetryAt(1);

        $this->assertNotNull($nextRetry);
        $expected = now()->addMinutes(5);
        $this->assertTrue(
            $nextRetry->diffInSeconds($expected) < 10,
            'Second retry should be scheduled approximately 5 minutes from now'
        );
    }

    /**
     * RL-003: Calculate next retry time for 3rd attempt
     * Expected: 15 minutes from now
     */
    public function test_calculate_next_retry_for_third_attempt(): void
    {
        $nextRetry = $this->service->calculateNextRetryAt(2);

        $this->assertNotNull($nextRetry);
        $expected = now()->addMinutes(15);
        $this->assertTrue(
            $nextRetry->diffInSeconds($expected) < 10,
            'Third retry should be scheduled approximately 15 minutes from now'
        );
    }

    /**
     * RL-004: Calculate next retry time for 4th attempt
     * Expected: 1 hour from now
     */
    public function test_calculate_next_retry_for_fourth_attempt(): void
    {
        $nextRetry = $this->service->calculateNextRetryAt(3);

        $this->assertNotNull($nextRetry);
        $expected = now()->addHour();
        $this->assertTrue(
            $nextRetry->diffInSeconds($expected) < 10,
            'Fourth retry should be scheduled approximately 1 hour from now'
        );
    }

    /**
     * RL-005: Calculate next retry time for 5th attempt
     * Expected: 6 hours from now
     */
    public function test_calculate_next_retry_for_fifth_attempt(): void
    {
        $nextRetry = $this->service->calculateNextRetryAt(4);

        $this->assertNotNull($nextRetry);
        $expected = now()->addHours(6);
        $this->assertTrue(
            $nextRetry->diffInSeconds($expected) < 10,
            'Fifth retry should be scheduled approximately 6 hours from now'
        );
    }

    /**
     * RL-006: Exceed max retry attempts (6th)
     * Expected: Returns null, no more retries
     */
    public function test_exceed_max_retry_attempts_returns_null(): void
    {
        // After 5 retries, no more should be allowed
        $nextRetry = $this->service->calculateNextRetryAt(5);

        $this->assertNull($nextRetry, 'Should return null when max retries exceeded');

        // Also test with higher attempt counts
        $this->assertNull($this->service->calculateNextRetryAt(6));
        $this->assertNull($this->service->calculateNextRetryAt(10));
    }

    /**
     * RL-007: Get retry schedule
     * Expected: Returns array of intervals
     */
    public function test_get_retry_schedule(): void
    {
        $schedule = $this->service->getRetrySchedule();

        $this->assertIsArray($schedule);
        $this->assertCount(5, $schedule, 'Should have 5 retry intervals');

        $expected = [
            1 => 1,    // 1 minute
            2 => 5,    // 5 minutes
            3 => 15,   // 15 minutes
            4 => 60,   // 1 hour
            5 => 360,  // 6 hours
        ];

        $this->assertEquals($expected, $schedule);
    }

    // =========================================================================
    // RETRY ON HTTP STATUS TESTS (RL-008 to RL-016)
    // =========================================================================

    /**
     * RL-008: Should retry on 500 error
     */
    public function test_should_retry_on_500_error(): void
    {
        $this->assertTrue($this->service->shouldRetry(500));
    }

    /**
     * RL-009: Should retry on 502 error
     */
    public function test_should_retry_on_502_error(): void
    {
        $this->assertTrue($this->service->shouldRetry(502));
    }

    /**
     * RL-010: Should retry on 503 error
     */
    public function test_should_retry_on_503_error(): void
    {
        $this->assertTrue($this->service->shouldRetry(503));
    }

    /**
     * RL-011: Should retry on 504 error
     */
    public function test_should_retry_on_504_error(): void
    {
        $this->assertTrue($this->service->shouldRetry(504));
    }

    /**
     * RL-012: Should NOT retry on 400 error
     */
    public function test_should_not_retry_on_400_error(): void
    {
        $this->assertFalse($this->service->shouldRetry(400));
    }

    /**
     * RL-013: Should NOT retry on 401 error
     */
    public function test_should_not_retry_on_401_error(): void
    {
        $this->assertFalse($this->service->shouldRetry(401));
    }

    /**
     * RL-014: Should NOT retry on 403 error
     */
    public function test_should_not_retry_on_403_error(): void
    {
        $this->assertFalse($this->service->shouldRetry(403));
    }

    /**
     * RL-015: Should NOT retry on 404 error
     */
    public function test_should_not_retry_on_404_error(): void
    {
        $this->assertFalse($this->service->shouldRetry(404));
    }

    /**
     * RL-016: Should NOT retry on 422 error
     */
    public function test_should_not_retry_on_422_error(): void
    {
        $this->assertFalse($this->service->shouldRetry(422));
    }

    /**
     * Additional: Should NOT retry on any 4xx error
     */
    public function test_should_not_retry_on_any_4xx_error(): void
    {
        foreach ([400, 401, 403, 404, 405, 409, 410, 422, 429, 451] as $status) {
            $this->assertFalse(
                $this->service->shouldRetry($status),
                "Should not retry on {$status} error"
            );
        }
    }

    /**
     * Additional: Should retry on any 5xx error
     */
    public function test_should_retry_on_any_5xx_error(): void
    {
        foreach ([500, 501, 502, 503, 504, 505, 507, 508, 510] as $status) {
            $this->assertTrue(
                $this->service->shouldRetry($status),
                "Should retry on {$status} error"
            );
        }
    }

    // =========================================================================
    // RETRY ON EXCEPTION TESTS (RL-017 to RL-019)
    // =========================================================================

    /**
     * RL-017: Should retry on connection timeout
     */
    public function test_should_retry_on_connection_timeout(): void
    {
        $exception = new \Illuminate\Http\Client\ConnectionException('Connection timed out');

        $this->assertTrue($this->service->shouldRetryOnError($exception));
    }

    /**
     * RL-018: Should retry on connection refused
     */
    public function test_should_retry_on_connection_refused(): void
    {
        $exception = new \Illuminate\Http\Client\ConnectionException('Connection refused');

        $this->assertTrue($this->service->shouldRetryOnError($exception));
    }

    /**
     * RL-019: Should retry on DNS resolution failure
     */
    public function test_should_retry_on_dns_failure(): void
    {
        $exception = new \Illuminate\Http\Client\ConnectionException('DNS resolution failed');

        $this->assertTrue($this->service->shouldRetryOnError($exception));
    }

    /**
     * Additional: Should retry on any ConnectionException
     */
    public function test_should_retry_on_any_connection_exception(): void
    {
        $exception = new \Illuminate\Http\Client\ConnectionException('Generic connection error');

        $this->assertTrue($this->service->shouldRetryOnError($exception));
    }

    /**
     * Additional: Should not retry on null exception
     */
    public function test_should_not_retry_on_null_exception(): void
    {
        $this->assertFalse($this->service->shouldRetryOnError(null));
    }

    // =========================================================================
    // REMAINING ATTEMPTS TESTS (RL-020 to RL-021)
    // =========================================================================

    /**
     * RL-020: Get remaining retry attempts
     */
    public function test_get_remaining_retry_attempts(): void
    {
        $this->assertEquals(5, $this->service->getRemainingAttempts(0));
        $this->assertEquals(4, $this->service->getRemainingAttempts(1));
        $this->assertEquals(3, $this->service->getRemainingAttempts(2));
        $this->assertEquals(2, $this->service->getRemainingAttempts(3));
        $this->assertEquals(1, $this->service->getRemainingAttempts(4));
        $this->assertEquals(0, $this->service->getRemainingAttempts(5));
        $this->assertEquals(0, $this->service->getRemainingAttempts(10));
    }

    /**
     * RL-021: Can retry check with attempt count
     */
    public function test_can_retry_check(): void
    {
        $this->assertTrue($this->service->canRetry(0));
        $this->assertTrue($this->service->canRetry(1));
        $this->assertTrue($this->service->canRetry(2));
        $this->assertTrue($this->service->canRetry(3));
        $this->assertTrue($this->service->canRetry(4));
        $this->assertFalse($this->service->canRetry(5));
        $this->assertFalse($this->service->canRetry(6));
    }

    // =========================================================================
    // INTEGRATION TESTS WITH DELIVERY MODEL
    // =========================================================================

    /**
     * Test schedule next retry on delivery
     */
    public function test_schedule_next_retry_on_delivery(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 0,
            'response_status' => 500,
        ]);

        $this->service->scheduleRetry($delivery);

        $delivery->refresh();

        $this->assertNotNull($delivery->next_retry_at);
        $this->assertEquals(1, $delivery->attempt_count);
    }

    /**
     * Test schedule retry exhausts attempts
     */
    public function test_schedule_retry_exhausts_attempts(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 5,
            'response_status' => 500,
        ]);

        $result = $this->service->scheduleRetry($delivery);

        $this->assertFalse($result, 'Should return false when no more retries');

        $delivery->refresh();
        $this->assertNull($delivery->next_retry_at, 'next_retry_at should remain null');
    }

    /**
     * Test mark delivery as failed
     */
    public function test_mark_delivery_as_failed(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 5,
            'response_status' => 500,
            'next_retry_at' => now()->addMinutes(5),
        ]);

        $this->service->markAsFailed($delivery);

        $delivery->refresh();

        $this->assertNull($delivery->next_retry_at);
        $this->assertNotNull($delivery->delivered_at);
    }
}
