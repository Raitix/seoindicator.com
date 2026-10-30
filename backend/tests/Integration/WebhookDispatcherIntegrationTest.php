<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\Keyword;
use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for TASK-6-14: Webhook Dispatcher Service
 *
 * Tests the end-to-end webhook dispatch flow including:
 * - Event triggering
 * - Multiple webhook delivery
 * - Delivery history
 * - Full lifecycle tests
 */
class WebhookDispatcherIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // INTEGRATION TESTS (IT)
    // =========================================================================

    /**
     * IT-001: Full dispatch flow
     */
    public function test_full_dispatch_flow(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Trigger a ranking changed event
        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'keyword_id' => 1,
                'keyword' => 'seo tools',
                'site_id' => $site->id,
                'old_position' => 10,
                'new_position' => 5,
                'change' => -5,
            ],
        ];

        $result = $service->dispatch($webhook, $event);

        // Verify dispatch succeeded
        $this->assertTrue($result);

        // Verify delivery was logged
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_type' => 'ranking.changed',
            'response_status' => 200,
        ]);

        // Verify HTTP was called with correct data
        Http::assertSent(function ($request) use ($webhook) {
            return $request->url() === $webhook->url &&
                   $request->hasHeader('X-SEOIndicator-Signature') &&
                   $request->hasHeader('X-SEOIndicator-Timestamp');
        });
    }

    /**
     * IT-002: Multiple webhooks for same event
     */
    public function test_multiple_webhooks_for_same_event(): void
    {
        Http::fake([
            'webhook1.com/*' => Http::response(['ok' => true], 200),
            'webhook2.com/*' => Http::response(['ok' => true], 200),
            'webhook3.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        // Create multiple webhooks for the same event
        $webhook1 = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://webhook1.com/hook',
            'secret' => 'whsec_secret1',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $webhook2 = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://webhook2.com/hook',
            'secret' => 'whsec_secret2',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $webhook3 = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://webhook3.com/hook',
            'secret' => 'whsec_secret3',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_multi',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        // Dispatch to all webhooks
        foreach ([$webhook1, $webhook2, $webhook3] as $webhook) {
            $service->dispatch($webhook, $event);
        }

        // Verify all webhooks received the event
        Http::assertSentCount(3);

        // Verify all deliveries logged
        $this->assertEquals(3, WebhookDelivery::count());
    }

    /**
     * IT-003: Event filtering
     */
    public function test_event_filtering(): void
    {
        Http::fake();

        $user = User::factory()->create();

        // Webhook only subscribes to ranking.changed
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'], // Only this event
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        // Try to dispatch a different event
        $event = [
            'id' => 'evt_test',
            'type' => 'alert.triggered', // Not subscribed
            'created_at' => now()->toIso8601String(),
            'data' => ['alert' => 'test'],
        ];

        $result = $service->dispatch($webhook, $event);

        // Should not dispatch
        $this->assertFalse($result);
        Http::assertNothingSent();

        // Now dispatch the subscribed event
        $event = [
            'id' => 'evt_test2',
            'type' => 'ranking.changed', // Subscribed
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $result = $service->dispatch($webhook, $event);

        $this->assertTrue($result);
        Http::assertSentCount(1);
    }

    /**
     * IT-005: Delivery history
     */
    public function test_delivery_history(): void
    {
        Http::fake([
            'example.com/*' => Http::sequence()
                ->push(['ok' => true], 200)
                ->push(['error' => 'fail'], 500)
                ->push(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed', 'alert.triggered'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        // Dispatch multiple events
        for ($i = 1; $i <= 3; $i++) {
            $event = [
                'id' => "evt_test_{$i}",
                'type' => 'ranking.changed',
                'created_at' => now()->toIso8601String(),
                'data' => ['iteration' => $i],
            ];

            $service->dispatch($webhook, $event);
        }

        // Verify delivery history
        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderBy('created_at')
            ->get();

        $this->assertEquals(3, $deliveries->count());
        $this->assertEquals(200, $deliveries[0]->response_status);
        $this->assertEquals(500, $deliveries[1]->response_status);
        $this->assertEquals(200, $deliveries[2]->response_status);
    }

    /**
     * IT-006: Disabled webhook skip
     */
    public function test_disabled_webhook_skip(): void
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

        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_test',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        Http::assertNothingSent();

        // No delivery should be logged for disabled webhooks
        $this->assertEquals(0, WebhookDelivery::count());
    }

    /**
     * IT-007: Response body stored correctly
     */
    public function test_response_body_stored_correctly(): void
    {
        $responseBody = json_encode([
            'status' => 'success',
            'processed_at' => now()->toIso8601String(),
            'webhook_id' => 'internal_123',
        ]);

        Http::fake([
            'example.com/*' => Http::response($responseBody, 200),
        ]);

        $user = User::factory()->create();

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_test',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();

        $this->assertNotNull($delivery->response_body);
        $this->assertStringContainsString('success', $delivery->response_body);
        $this->assertStringContainsString('internal_123', $delivery->response_body);
    }

    /**
     * IT-008: HMAC signature verification flow
     */
    public function test_hmac_signature_verification_flow(): void
    {
        $receivedSignature = null;
        $receivedPayload = null;
        $receivedTimestamp = null;

        Http::fake(function ($request) use (&$receivedSignature, &$receivedPayload, &$receivedTimestamp) {
            $receivedSignature = $request->header('X-SEOIndicator-Signature')[0] ?? null;
            $receivedTimestamp = $request->header('X-SEOIndicator-Timestamp')[0] ?? null;
            $receivedPayload = $request->body();
            return Http::response(['ok' => true], 200);
        });

        $user = User::factory()->create();
        $secret = 'whsec_testsecret123';

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => $secret,
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_test',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['keyword' => 'test'],
        ];

        $service->dispatch($webhook, $event);

        // Verify signature on receiver side
        $this->assertNotNull($receivedSignature);
        $this->assertNotNull($receivedTimestamp);
        $this->assertNotNull($receivedPayload);

        // Calculate expected signature
        $signaturePayload = $receivedTimestamp . '.' . $receivedPayload;
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $signaturePayload, $secret);

        $this->assertEquals($expectedSignature, $receivedSignature);
    }

    /**
     * IT-009: Cross-user isolation
     */
    public function test_cross_user_isolation(): void
    {
        Http::fake([
            'user1.com/*' => Http::response(['ok' => true], 200),
            'user2.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $webhook1 = Webhook::factory()->create([
            'user_id' => $user1->id,
            'url' => 'https://user1.com/webhook',
            'secret' => 'whsec_user1secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $webhook2 = Webhook::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://user2.com/webhook',
            'secret' => 'whsec_user2secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        $event = [
            'id' => 'evt_test',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        // Dispatch for user1's webhook
        $service->dispatch($webhook1, $event);

        // Verify user1's delivery logged, not user2's
        $this->assertEquals(1, WebhookDelivery::where('webhook_id', $webhook1->id)->count());
        $this->assertEquals(0, WebhookDelivery::where('webhook_id', $webhook2->id)->count());
    }

    /**
     * IT-010: All valid event types dispatch correctly
     */
    public function test_all_valid_event_types_dispatch_correctly(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => Webhook::VALID_EVENTS, // All events
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        foreach (Webhook::VALID_EVENTS as $eventType) {
            $event = [
                'id' => 'evt_' . $eventType,
                'type' => $eventType,
                'created_at' => now()->toIso8601String(),
                'data' => ['event_type' => $eventType],
            ];

            $result = $service->dispatch($webhook, $event);
            $this->assertTrue($result, "Failed to dispatch event: {$eventType}");
        }

        // Verify all events were dispatched
        $this->assertEquals(count(Webhook::VALID_EVENTS), WebhookDelivery::count());
    }

    /**
     * IT-011: Large payload handling
     */
    public function test_large_payload_handling(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        // Create a moderately large payload (10KB)
        $largeData = str_repeat('x', 10240);

        $event = [
            'id' => 'evt_large',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'keyword' => $largeData,
                'metadata' => [
                    'field1' => $largeData,
                    'field2' => $largeData,
                ],
            ],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertTrue($result);

        // Verify the delivery was logged with the large payload
        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();
        $this->assertNotNull($delivery);
        $this->assertGreaterThan(30000, strlen(json_encode($delivery->payload)));
    }

    /**
     * IT-012: Concurrent dispatch safety
     */
    public function test_concurrent_dispatch_safety(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        // Dispatch multiple events "concurrently" (simulated)
        $dispatchCount = 10;
        $results = [];

        for ($i = 0; $i < $dispatchCount; $i++) {
            $event = [
                'id' => 'evt_concurrent_' . $i,
                'type' => 'ranking.changed',
                'created_at' => now()->toIso8601String(),
                'data' => ['index' => $i],
            ];

            $results[] = $service->dispatch($webhook, $event);
        }

        // All should succeed
        foreach ($results as $result) {
            $this->assertTrue($result);
        }

        // All should be logged
        $this->assertEquals($dispatchCount, WebhookDelivery::count());

        // Each should have unique event data
        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)->get();
        $indices = $deliveries->pluck('payload.data.index')->unique();
        $this->assertEquals($dispatchCount, $indices->count());
    }
}
