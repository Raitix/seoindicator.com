<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for the Webhook model.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test webhook belongs to a user.
     */
    public function test_webhook_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $webhook->user->id);
    }

    /**
     * Test webhook has many deliveries.
     */
    public function test_webhook_has_many_deliveries(): void
    {
        $webhook = Webhook::factory()->create();
        WebhookDelivery::factory()->count(3)->create(['webhook_id' => $webhook->id]);

        $this->assertCount(3, $webhook->deliveries);
    }

    /**
     * Test generate secret creates valid format.
     */
    public function test_generate_secret_creates_valid_format(): void
    {
        $secret = Webhook::generateSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertEquals(70, strlen($secret)); // 'whsec_' (6) + 64 hex chars = 70
    }

    /**
     * Test generate secret creates unique values.
     */
    public function test_generate_secret_creates_unique_values(): void
    {
        $secret1 = Webhook::generateSecret();
        $secret2 = Webhook::generateSecret();

        $this->assertNotEquals($secret1, $secret2);
    }

    /**
     * test events are cast to array.
     */
    public function test_events_are_cast_to_array(): void
    {
        $webhook = Webhook::factory()->create([
            'events' => ['ranking.changed', 'alert.triggered'],
        ]);

        $this->assertIsArray($webhook->events);
        $this->assertEquals(['ranking.changed', 'alert.triggered'], $webhook->events);
    }

    /**
     * Test enabled is cast to boolean.
     */
    public function test_enabled_is_cast_to_boolean(): void
    {
        $webhook = Webhook::factory()->create(['enabled' => true]);

        $this->assertIsBool($webhook->enabled);
        $this->assertTrue($webhook->enabled);
    }

    /**
     * Test secret is hidden in serialization.
     */
    public function test_secret_is_hidden_in_serialization(): void
    {
        $webhook = Webhook::factory()->create();
        $array = $webhook->toArray();

        $this->assertArrayNotHasKey('secret', $array);
    }

    /**
     * Test isEnabled returns correct value.
     */
    public function test_is_enabled_returns_correct_value(): void
    {
        $enabledWebhook = Webhook::factory()->create(['enabled' => true]);
        $disabledWebhook = Webhook::factory()->disabled()->create();

        $this->assertTrue($enabledWebhook->isEnabled());
        $this->assertFalse($disabledWebhook->isEnabled());
    }

    /**
     * Test subscribesTo returns correct value.
     */
    public function test_subscribes_to_returns_correct_value(): void
    {
        $webhook = Webhook::factory()->create([
            'events' => ['ranking.changed', 'alert.triggered'],
        ]);

        $this->assertTrue($webhook->subscribesTo('ranking.changed'));
        $this->assertTrue($webhook->subscribesTo('alert.triggered'));
        $this->assertFalse($webhook->subscribesTo('report.completed'));
    }

    /**
     * Test subscribesTo handles empty events array.
     */
    public function test_subscribes_to_handles_empty_events(): void
    {
        $webhook = Webhook::factory()->create(['events' => []]);

        $this->assertFalse($webhook->subscribesTo('ranking.changed'));
    }

    /**
     * Test VALID_EVENTS constant contains expected events.
     */
    public function test_valid_events_constant(): void
    {
        $expectedEvents = [
            'ranking.changed',
            'alert.triggered',
            'report.completed',
            'keyword.added',
            'keyword.removed',
        ];

        $this->assertEquals($expectedEvents, Webhook::VALID_EVENTS);
    }

    /**
     * Test webhook is deleted when user is deleted.
     */
    public function test_webhook_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }
}
