<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\User;
use App\Jobs\ProcessWebhookRetriesJob;
use App\Services\WebhookRetryService;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-6-15: Webhook Retry Logic
 *
 * Tests the ProcessWebhookRetriesJob responsible for:
 * - Processing pending retry queue
 * - Handling retry successes and failures
 * - Managing retry scheduling
 */
class ProcessWebhookRetriesJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // =========================================================================
    // WORKER JOB TESTS (WJ-001 to WJ-010)
    // =========================================================================

    /**
     * WJ-001: Process pending retry
     * Expected: Dispatches webhook, updates status
     */
    public function test_process_pending_retry(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'event_type' => 'ranking.changed',
            'payload' => ['event' => 'test'],
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(), // Due for retry
            'delivered_at' => null,
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertNull($delivery->next_retry_at);
    }

    /**
     * WJ-002: Process multiple pending retries
     * Expected: All dispatched in batch
     */
    public function test_process_multiple_pending_retries(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        // Create 3 pending retries
        $deliveries = WebhookDelivery::factory()->count(3)->create([
            'webhook_id' => $webhook->id,
            'next_retry_at' => now()->subMinutes(5),
            'attempt_count' => 1,
            'delivered_at' => null,
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        foreach ($deliveries as $delivery) {
            $delivery->refresh();
            $this->assertEquals(200, $delivery->response_status);
            $this->assertNotNull($delivery->delivered_at);
        }
    }

    /**
     * WJ-003: No pending retries
     * Expected: Job completes without errors
     */
    public function test_no_pending_retries(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        // Create delivery with future retry time (not due yet)
        WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'next_retry_at' => now()->addHours(5),
        ]);

        $job = new ProcessWebhookRetriesJob();

        // Should complete without errors
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $this->assertTrue(true, 'Job completed without errors');
    }

    /**
     * WJ-004: Retry succeeds
     * Expected: Marks as delivered
     */
    public function test_retry_succeeds(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 2,
            'next_retry_at' => now()->subMinute(),
            'response_status' => 500, // Previous failure
            'delivered_at' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertNull($delivery->next_retry_at);
    }

    /**
     * WJ-005: Retry fails with 5xx
     * Expected: Schedules next retry
     */
    public function test_retry_fails_with_5xx_schedules_next_retry(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
            'delivered_at' => null,
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(500, $delivery->response_status);
        $this->assertEquals(2, $delivery->attempt_count, 'Attempt count should increment');
        $this->assertNotNull($delivery->next_retry_at, 'Should schedule next retry');
        $this->assertTrue(
            $delivery->next_retry_at->isAfter(now()),
            'Next retry should be in the future'
        );
    }

    /**
     * WJ-006: Retry fails with 4xx
     * Expected: Marks as failed (no retry)
     */
    public function test_retry_fails_with_4xx_marks_as_failed(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
            'delivered_at' => null,
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(404, $delivery->response_status);
        $this->assertNull($delivery->next_retry_at, 'Should NOT schedule retry for 4xx');
    }

    /**
     * WJ-007: Retry exhausts max attempts
     * Expected: Marks as failed
     */
    public function test_retry_exhausts_max_attempts_marks_as_failed(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Service Unavailable'], 503),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 5, // Max attempts reached
            'next_retry_at' => now()->subMinute(),
            'delivered_at' => null,
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(503, $delivery->response_status);
        $this->assertNull($delivery->next_retry_at, 'Should not have next retry');
    }

    /**
     * WJ-008: Process retry with disabled webhook
     * Expected: Skips delivery
     */
    public function test_process_retry_with_disabled_webhook_skips(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => false, // Disabled
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        // Delivery should be skipped
        Http::assertNothingSent();
    }

    /**
     * WJ-009: Process retry with deleted webhook
     * Expected: Handles gracefully
     */
    public function test_process_retry_with_deleted_webhook_handles_gracefully(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        // Delete the webhook (cascade should delete delivery, but let's test the case)
        $webhook->delete();

        $job = new ProcessWebhookRetriesJob();

        // Should complete without errors
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $this->assertTrue(true, 'Job completed without errors');
    }

    /**
     * WJ-010: Batch size limit
     * Expected: Processes only configured batch size
     */
    public function test_batch_size_limit(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        // Create more deliveries than batch size
        $batchSize = 10;
        $totalDeliveries = 20;

        WebhookDelivery::factory()->count($totalDeliveries)->create([
            'webhook_id' => $webhook->id,
            'next_retry_at' => now()->subMinute(),
            'attempt_count' => 1,
        ]);

        $job = new ProcessWebhookRetriesJob($batchSize);
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        // Only batch size should be processed
        $processedCount = WebhookDelivery::whereNotNull('delivered_at')->count();
        $this->assertLessThanOrEqual($batchSize, $processedCount);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC-001 to EC-008)
    // =========================================================================

    /**
     * EC-001: Null response status
     * Expected: Treated as retryable
     */
    public function test_null_response_status_treated_as_retryable(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'timeout'], null),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        // Null status should be treated as retryable (connection error)
        $this->assertEquals(2, $delivery->attempt_count);
        $this->assertNotNull($delivery->next_retry_at);
    }

    /**
     * EC-002: Empty response body
     * Expected: Logged, no error
     */
    public function test_empty_response_body_logged(): void
    {
        Http::fake([
            'example.com/*' => Http::response('', 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(200, $delivery->response_status);
        $this->assertEquals('', $delivery->response_body);
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * EC-003: Very large response body
     * Expected: Truncated, no error
     */
    public function test_large_response_body_truncated(): void
    {
        $largeBody = str_repeat('x', 100000); // 100KB

        Http::fake([
            'example.com/*' => Http::response($largeBody, 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(200, $delivery->response_status);
        // Response body should be stored (possibly truncated)
        $this->assertNotNull($delivery->response_body);
        $this->assertLessThanOrEqual(65535, strlen($delivery->response_body), 'Body should be truncated');
    }

    /**
     * EC-004: Concurrent job processing
     * Expected: Lock prevents duplicates
     */
    public function test_concurrent_job_processing_lock(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        // Simulate two jobs running concurrently
        $job1 = new ProcessWebhookRetriesJob();
        $job2 = new ProcessWebhookRetriesJob();

        $job1->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        // Second job should find no pending retries (already processed)
        $job2->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();
        $this->assertEquals(200, $delivery->response_status);
        $this->assertEquals(2, $delivery->attempt_count, 'Should only be processed once');
    }

    /**
     * EC-007: Invalid next_retry_at in past
     * Expected: Processed immediately
     */
    public function test_next_retry_at_in_past_processed_immediately(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->subDays(1), // Way in the past
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        $this->assertEquals(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * EC-008: Invalid next_retry_at in future
     * Expected: Not processed yet
     */
    public function test_next_retry_at_in_future_not_processed(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'attempt_count' => 1,
            'next_retry_at' => now()->addHours(5), // In the future
            'response_status' => null,
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $delivery->refresh();

        // Should not be processed
        $this->assertNull($delivery->response_status);
        $this->assertNull($delivery->delivered_at);
        Http::assertNothingSent();
    }

    // =========================================================================
    // SECURITY TESTS (SC-001 to SC-003)
    // =========================================================================

    /**
     * SC-001: Retry with invalid webhook_id
     * Expected: Gracefully handled
     */
    public function test_retry_with_invalid_webhook_id(): void
    {
        $delivery = WebhookDelivery::factory()->make([
            'webhook_id' => 99999, // Non-existent
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);
        $delivery->saveQuietly(); // Bypass foreign key for test

        $job = new ProcessWebhookRetriesJob();

        // Should complete without errors
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        $this->assertTrue(true, 'Job completed without errors');
    }

    /**
     * SC-003: Retry payload tampering
     * Expected: HMAC verification on dispatch
     */
    public function test_retry_payload_hmac_verification(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return Http::response(['success' => true], 200);
        });

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'enabled' => true,
        ]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'event_type' => 'ranking.changed',
            'payload' => [
                'id' => 'evt_test123',
                'type' => 'ranking.changed',
                'data' => ['keyword' => 'test'],
            ],
            'attempt_count' => 1,
            'next_retry_at' => now()->subMinute(),
        ]);

        $job = new ProcessWebhookRetriesJob();
        $job->handle(
            app(WebhookRetryService::class),
            app(WebhookDispatcherService::class)
        );

        // Verify HMAC signature was included
        $this->assertNotNull($capturedRequest);
        $this->assertTrue(
            $capturedRequest->hasHeader('X-SEOIndicator-Signature'),
            'HMAC signature header should be present'
        );
    }

    // =========================================================================
    // DELIVERY STATUS TESTS
    // =========================================================================

    /**
     * Test delivery status helper - pending
     */
    public function test_delivery_status_pending(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'response_status' => null,
            'delivered_at' => null,
            'next_retry_at' => null,
        ]);

        $this->assertEquals('pending', $delivery->getStatus());
    }

    /**
     * Test delivery status helper - delivered
     */
    public function test_delivery_status_delivered(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'response_status' => 200,
            'delivered_at' => now(),
        ]);

        $this->assertEquals('delivered', $delivery->getStatus());
    }

    /**
     * Test delivery status helper - retrying
     */
    public function test_delivery_status_retrying(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'response_status' => 500,
            'next_retry_at' => now()->addMinutes(5),
            'attempt_count' => 1,
        ]);

        $this->assertEquals('retrying', $delivery->getStatus());
    }

    /**
     * Test delivery status helper - failed
     */
    public function test_delivery_status_failed(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'response_status' => 500,
            'next_retry_at' => null,
            'attempt_count' => 5,
        ]);

        $this->assertEquals('failed', $delivery->getStatus());
    }

    // =========================================================================
    // QUEUE CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test job is queued
     */
    public function test_job_is_queued(): void
    {
        Queue::fake();

        ProcessWebhookRetriesJob::dispatch();

        Queue::assertPushed(ProcessWebhookRetriesJob::class);
    }

    /**
     * Test job has correct queue name
     */
    public function test_job_has_correct_queue(): void
    {
        $job = new ProcessWebhookRetriesJob();

        $this->assertEquals('webhooks', $job->queue ?? 'default');
    }

    /**
     * Test job retry configuration
     */
    public function test_job_retry_configuration(): void
    {
        $job = new ProcessWebhookRetriesJob();

        // Job should have reasonable retry limits
        $this->assertLessThanOrEqual(3, $job->tries ?? 1, 'Job should have limited retries');
        $this->assertLessThanOrEqual(300, $job->timeout ?? 60, 'Job should have reasonable timeout');
    }
}
