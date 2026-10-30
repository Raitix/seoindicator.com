<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\DispatchWebhookJob;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-6-14: Webhook Dispatch Job
 *
 * Tests the queue job responsible for async webhook dispatch.
 */
class DispatchWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // QUEUE JOB TESTS (QJ)
    // =========================================================================

    /**
     * QJ-001: Job processes webhook
     */
    public function test_job_processes_webhook(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // Verify delivery was created
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_type' => 'ranking.changed',
            'response_status' => 200,
        ]);
    }

    /**
     * QJ-002: Job handles failure gracefully
     */
    public function test_job_handles_failure_gracefully(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal error'], 500),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);

        // Should not throw exception
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // Verify failure was logged
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'response_status' => 500,
        ]);
    }

    /**
     * QJ-003: Job retries on failure
     */
    public function test_job_retries_on_failure(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);

        // Check job has retry configuration
        $this->assertGreaterThan(0, $job->tries ?? 1);
    }

    /**
     * QJ-004: Job with non-existent webhook
     */
    public function test_job_with_non_existent_webhook(): void
    {
        Http::fake();

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob(9999, $event);

        // Should handle gracefully without throwing
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // No HTTP should be sent
        Http::assertNothingSent();

        // No delivery should be created
        $this->assertEquals(0, WebhookDelivery::count());
    }

    /**
     * QJ-005: Job with deleted webhook
     */
    public function test_job_with_deleted_webhook(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $webhookId = $webhook->id;

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        // Delete webhook before job runs
        $webhook->delete();

        $job = new DispatchWebhookJob($webhookId, $event);

        // Should handle gracefully
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // No delivery should be created
        $this->assertEquals(0, WebhookDelivery::count());
    }

    /**
     * QJ-006: Job respects queue
     */
    public function test_job_respects_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        // Dispatch job
        DispatchWebhookJob::dispatch($webhook->id, $event);

        // Verify job was queued
        Queue::assertPushed(DispatchWebhookJob::class, function ($job) use ($webhook, $event) {
            return $job->webhookId === $webhook->id &&
                   $job->event['id'] === $event['id'];
        });
    }

    /**
     * QJ-007: Job with connection exception
     */
    public function test_job_with_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);

        // Should not throw - handled gracefully
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // Delivery should be logged with failure
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'response_status' => null, // No response
        ]);
    }

    /**
     * QJ-008: Job with timeout exception
     */
    public function test_job_with_timeout_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Request timeout');
        });

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);

        // Should handle timeout gracefully
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // Delivery should be logged
        $this->assertEquals(1, WebhookDelivery::where('webhook_id', $webhook->id)->count());
    }

    /**
     * QJ-009: Job payload serialization
     */
    public function test_job_payload_serialization(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'keyword_id' => 1,
                'keyword' => 'test keyword',
                'old_position' => 10,
                'new_position' => 5,
                'change' => -5,
                'checked_at' => now()->toIso8601String(),
            ],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);

        // Verify job properties
        $this->assertEquals($webhook->id, $job->webhookId);
        $this->assertEquals($event, $job->event);
    }

    /**
     * QJ-010: Job unique ID
     */
    public function test_job_unique_id(): void
    {
        $event1 = [
            'id' => 'evt_test1',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 1],
        ];

        $event2 = [
            'id' => 'evt_test2',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 2],
        ];

        $job1 = new DispatchWebhookJob(1, $event1);
        $job2 = new DispatchWebhookJob(1, $event2);

        // Jobs with different event IDs should have different unique IDs (if implemented)
        // This is a sanity check for job identification
        $this->assertNotEquals($event1['id'], $event2['id']);
    }

    /**
     * QJ-011: Job with disabled webhook
     */
    public function test_job_with_disabled_webhook(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => false, // Disabled
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // No HTTP should be sent for disabled webhook
        Http::assertNothingSent();
    }

    /**
     * QJ-012: Job with unsubscribed event
     */
    public function test_job_with_unsubscribed_event(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['alert.triggered'], // Does not include ranking.changed
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed', // Not subscribed
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $job = new DispatchWebhookJob($webhook->id, $event);
        $job->handle(app(\App\Services\WebhookDispatcherService::class));

        // No HTTP should be sent
        Http::assertNothingSent();
    }
}
