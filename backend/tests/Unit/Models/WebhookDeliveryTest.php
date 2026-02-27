<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for the WebhookDelivery model.
 */
class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test delivery belongs to a webhook.
     */
    public function test_delivery_belongs_to_webhook(): void
    {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);

        $this->assertEquals($webhook->id, $delivery->webhook->id);
    }

    /**
     * Test payload is cast to array.
     */
    public function test_payload_is_cast_to_array(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'payload' => ['event' => 'test', 'data' => ['id' => 1]],
        ]);

        $this->assertIsArray($delivery->payload);
        $this->assertEquals('test', $delivery->payload['event']);
    }

    /**
     * Test delivered_at is cast to datetime.
     */
    public function test_delivered_at_is_cast_to_datetime(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'delivered_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $delivery->delivered_at);
    }

    /**
     * Test next_retry_at is cast to datetime.
     */
    public function test_next_retry_at_is_cast_to_datetime(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'next_retry_at' => now()->addMinutes(5),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $delivery->next_retry_at);
    }

    /**
     * Test wasSuccessful returns true for 2xx status.
     */
    public function test_was_successful_returns_true_for_2xx(): void
    {
        foreach ([200, 201, 204, 299] as $status) {
            $delivery = WebhookDelivery::factory()->create([
                'response_status' => $status,
            ]);

            $this->assertTrue($delivery->wasSuccessful(), "Expected true for status {$status}");
        }
    }

    /**
     * Test wasSuccessful returns false for non-2xx status.
     */
    public function test_was_successful_returns_false_for_non_2xx(): void
    {
        foreach ([400, 404, 500, 502, 503] as $status) {
            $delivery = WebhookDelivery::factory()->create([
                'response_status' => $status,
            ]);

            $this->assertFalse($delivery->wasSuccessful(), "Expected false for status {$status}");
        }
    }

    /**
     * Test wasSuccessful returns false for null status.
     */
    public function test_was_successful_returns_false_for_null_status(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'response_status' => null,
        ]);

        $this->assertFalse($delivery->wasSuccessful());
    }

    /**
     * Test markDelivered updates status and body.
     */
    public function test_mark_delivered_updates_status_and_body(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'response_status' => null,
            'response_body' => null,
            'delivered_at' => null,
        ]);

        $delivery->markDelivered(200, '{"success": true}');

        $this->assertEquals(200, $delivery->response_status);
        $this->assertEquals('{"success": true}', $delivery->response_body);
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * Test delivery is deleted when webhook is deleted.
     */
    public function test_delivery_deleted_when_webhook_deleted(): void
    {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);

        $webhook->delete();

        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $delivery->id]);
    }

    /**
     * Test successful factory state.
     */
    public function test_successful_factory_state(): void
    {
        $delivery = WebhookDelivery::factory()->successful()->create();

        $this->assertGreaterThanOrEqual(200, $delivery->response_status);
        $this->assertLessThan(300, $delivery->response_status);
    }

    /**
     * Test failed factory state.
     */
    public function test_failed_factory_state(): void
    {
        $delivery = WebhookDelivery::factory()->failed()->create();

        $this->assertGreaterThanOrEqual(400, $delivery->response_status);
        $this->assertNotNull($delivery->next_retry_at);
    }
}
