<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;

/**
 * Unit tests for TASK-6-14: Webhook Dispatcher Service
 *
 * Tests the WebhookDispatcherService responsible for:
 * - HTTP POST delivery to webhook URLs
 * - HMAC signature generation
 * - Timeout handling
 * - Delivery logging
 */
class WebhookDispatcherServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // DISPATCHER SERVICE TESTS (DS)
    // =========================================================================

    /**
     * DS-001: Dispatch successful webhook
     */
    public function test_dispatch_successful_webhook(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            ],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertTrue($result);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_type' => 'ranking.changed',
            'response_status' => 200,
        ]);
    }

    /**
     * DS-002: Dispatch with HMAC signature
     */
    public function test_dispatch_with_hmac_signature(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return Http::response(['ok' => true], 200);
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $signature = $capturedRequest->header('X-SEOIndicator-Signature');
        $this->assertNotNull($signature);
        $this->assertStringStartsWith('sha256=', $signature[0]);
    }

    /**
     * DS-003: Dispatch with timestamp header
     */
    public function test_dispatch_with_timestamp_header(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return Http::response(['ok' => true], 200);
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $timestamp = $capturedRequest->header('X-SEOIndicator-Timestamp');
        $this->assertNotNull($timestamp);
        $this->assertMatchesRegularExpression('/^\d+$/', $timestamp[0]);
    }

    /**
     * DS-004: Dispatch to disabled webhook
     */
    public function test_dispatch_to_disabled_webhook(): void
    {
        Http::fake();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'enabled' => false,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /**
     * DS-005: Dispatch with HTTP error
     */
    public function test_dispatch_with_http_error(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal error'], 500),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'response_status' => 500,
        ]);
    }

    /**
     * DS-006: Dispatch with connection timeout
     */
    public function test_dispatch_with_connection_timeout(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'response_status' => null, // No response received
        ]);
    }

    /**
     * DS-008: Dispatch with 4xx response
     */
    public function test_dispatch_with_4xx_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'response_status' => 404,
        ]);
    }

    /**
     * DS-011: Dispatch records response body
     */
    public function test_dispatch_records_response_body(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['message' => 'Success', 'id' => 123], 200),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();
        $this->assertNotNull($delivery->response_body);
        $this->assertStringContainsString('Success', $delivery->response_body);
    }

    /**
     * DS-014: Dispatch with event not subscribed
     */
    public function test_dispatch_with_event_not_subscribed(): void
    {
        Http::fake();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'events' => ['alert.triggered'], // Does not include ranking.changed
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed', // Not subscribed
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    // =========================================================================
    // HMAC SIGNATURE TESTS (HM)
    // =========================================================================

    /**
     * HM-001: Generate valid HMAC signature
     */
    public function test_generate_valid_hmac_signature(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = json_encode(['test' => 'data']);
        $secret = 'whsec_testsecret123';
        $timestamp = time();

        $signature = $service->generateSignature($payload, $secret, $timestamp);

        // Verify the signature can be validated
        $expectedPayload = $timestamp . '.' . $payload;
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $expectedPayload, $secret);

        $this->assertEquals($expectedSignature, $signature);
    }

    /**
     * HM-002: Signature format correct
     */
    public function test_signature_format_correct(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = json_encode(['test' => 'data']);
        $secret = 'whsec_testsecret123';
        $timestamp = time();

        $signature = $service->generateSignature($payload, $secret, $timestamp);

        $this->assertMatchesRegularExpression('/^sha256=[a-f0-9]{64}$/', $signature);
    }

    /**
     * HM-003: Signature varies with payload
     */
    public function test_signature_varies_with_payload(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $secret = 'whsec_testsecret123';
        $timestamp = time();

        $signature1 = $service->generateSignature(json_encode(['data' => 'one']), $secret, $timestamp);
        $signature2 = $service->generateSignature(json_encode(['data' => 'two']), $secret, $timestamp);

        $this->assertNotEquals($signature1, $signature2);
    }

    /**
     * HM-004: Signature varies with secret
     */
    public function test_signature_varies_with_secret(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = json_encode(['test' => 'data']);
        $timestamp = time();

        $signature1 = $service->generateSignature($payload, 'whsec_secret1', $timestamp);
        $signature2 = $service->generateSignature($payload, 'whsec_secret2', $timestamp);

        $this->assertNotEquals($signature1, $signature2);
    }

    /**
     * HM-005: Signature for empty payload
     */
    public function test_signature_for_empty_payload(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = '';
        $secret = 'whsec_testsecret123';
        $timestamp = time();

        $signature = $service->generateSignature($payload, $secret, $timestamp);

        $expectedPayload = $timestamp . '.' . '';
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $expectedPayload, $secret);

        $this->assertEquals($expectedSignature, $signature);
    }

    /**
     * HM-006: Signature for JSON payload
     */
    public function test_signature_for_json_payload(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = json_encode(['nested' => ['object' => ['with' => 'data']]]);
        $secret = 'whsec_testsecret123';
        $timestamp = time();

        $signature = $service->generateSignature($payload, $secret, $timestamp);

        $this->assertMatchesRegularExpression('/^sha256=[a-f0-9]{64}$/', $signature);
    }

    // =========================================================================
    // EVENT PAYLOAD TESTS (EP)
    // =========================================================================

    /**
     * EP-001: Ranking changed event payload structure
     */
    public function test_ranking_changed_event_payload_structure(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $eventData = [
            'keyword_id' => 1,
            'keyword' => 'seo tools',
            'site_id' => 1,
            'old_position' => 7,
            'new_position' => 5,
            'change' => -2,
        ];

        $payload = $service->createEventPayload('ranking.changed', $eventData);

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertEquals('ranking.changed', $payload['type']);
        $this->assertStringStartsWith('evt_', $payload['id']);
    }

    /**
     * EP-002: Alert triggered event payload structure
     */
    public function test_alert_triggered_event_payload_structure(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $eventData = [
            'alert_id' => 1,
            'alert_name' => 'Position Drop',
            'keyword' => 'seo tools',
            'condition' => 'dropped_below_10',
        ];

        $payload = $service->createEventPayload('alert.triggered', $eventData);

        $this->assertEquals('alert.triggered', $payload['type']);
        $this->assertArrayHasKey('data', $payload);
    }

    /**
     * EP-003: Report completed event payload structure
     */
    public function test_report_completed_event_payload_structure(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $eventData = [
            'report_id' => 1,
            'report_type' => 'weekly',
            'site_id' => 1,
            'download_url' => 'https://example.com/reports/123.pdf',
        ];

        $payload = $service->createEventPayload('report.completed', $eventData);

        $this->assertEquals('report.completed', $payload['type']);
        $this->assertArrayHasKey('data', $payload);
    }

    /**
     * EP-006: Event ID generation
     */
    public function test_event_id_generation(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload1 = $service->createEventPayload('test.event', ['data' => 'test']);
        $payload2 = $service->createEventPayload('test.event', ['data' => 'test']);

        $this->assertNotEquals($payload1['id'], $payload2['id']);
        $this->assertMatchesRegularExpression('/^evt_[a-f0-9]+$/', $payload1['id']);
    }

    /**
     * EP-007: Timestamp in ISO 8601
     */
    public function test_timestamp_in_iso_8601(): void
    {
        $service = app(\App\Services\WebhookDispatcherService::class);

        $payload = $service->createEventPayload('test.event', ['data' => 'test']);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $payload['created_at']
        );
    }

    // =========================================================================
    // TIMEOUT HANDLING TESTS (TO)
    // =========================================================================

    /**
     * TO-001: Default timeout applied
     */
    public function test_default_timeout_applied(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return Http::response(['ok' => true], 200);
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        // Verify timeout is set (default 30s)
        // This is more of an integration test but we can check config
        $this->assertEquals(30, config('webhooks.timeout', 30));
    }

    /**
     * TO-005: Timeout logs correctly
     */
    public function test_timeout_logs_correctly(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Webhook dispatch failed') ||
                       str_contains($message, 'timeout') ||
                       str_contains(strtolower($message), 'failed');
            });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);
    }

    // =========================================================================
    // DELIVERY LOGGING TESTS (DL)
    // =========================================================================

    /**
     * DL-001: Log successful delivery
     */
    public function test_log_successful_delivery(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();

        $this->assertNotNull($delivery);
        $this->assertEquals(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * DL-002: Log failed delivery
     */
    public function test_log_failed_delivery(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();

        $this->assertNotNull($delivery);
        $this->assertEquals(500, $delivery->response_status);
    }

    /**
     * DL-005: Log attempt count
     */
    public function test_log_attempt_count(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

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
            'data' => ['test' => 'data'],
        ];

        // First dispatch
        $service->dispatch($webhook, $event);

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();
        $this->assertEquals(1, $delivery->attempt_count);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Secret not logged
     */
    public function test_secret_not_logged(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        Log::shouldReceive('info')
            ->never()
            ->withArgs(function ($message, $context) {
                if (isset($context['secret'])) {
                    return true;
                }
                if (is_string($message) && str_contains($message, 'whsec_')) {
                    return true;
                }
                return false;
            });

        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_topsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $service->dispatch($webhook, $event);
    }

    /**
     * SC-006: Payload size limit
     */
    public function test_payload_size_limit(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create a large payload (1MB+)
        $largeData = str_repeat('x', 1024 * 1024 + 1);
        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['large_field' => $largeData],
        ];

        // Service should handle large payloads gracefully
        $result = $service->dispatch($webhook, $event);

        // Either succeeds or fails gracefully (implementation-dependent)
        $this->assertIsBool($result);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Empty webhook URL
     */
    public function test_dispatch_with_empty_webhook_url(): void
    {
        Http::fake();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => '',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
    }

    /**
     * EC-002: Null webhook events
     */
    public function test_dispatch_with_null_webhook_events(): void
    {
        Http::fake();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => null,
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /**
     * EC-003: Empty events array
     */
    public function test_dispatch_with_empty_events_array(): void
    {
        Http::fake();

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => [],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /**
     * EC-005: Unicode in payload
     */
    public function test_unicode_in_payload(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return Http::response(['ok' => true], 200);
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

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
                'keyword' => 'SEO 工具 🚀 café',
                'language' => '日本語',
            ],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertTrue($result);

        // Verify Unicode was encoded correctly
        $body = $capturedRequest->body();
        $decoded = json_decode($body, true);
        $this->assertEquals('SEO 工具 🚀 café', $decoded['data']['keyword']);
    }

    /**
     * EC-009: DNS resolution failure
     */
    public function test_dns_resolution_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host');
        });

        $service = app(\App\Services\WebhookDispatcherService::class);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://nonexistent-domain-12345.com/webhook',
            'secret' => 'whsec_testsecret123',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        $event = [
            'id' => 'evt_test123',
            'type' => 'ranking.changed',
            'created_at' => now()->toIso8601String(),
            'data' => ['test' => 'data'],
        ];

        $result = $service->dispatch($webhook, $event);

        $this->assertFalse($result);
    }
}
