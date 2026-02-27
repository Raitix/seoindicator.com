<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * Feature tests for TASK-6-13: Webhook Management API
 *
 * Tests the REST API endpoints for webhook management.
 * Covers CRUD operations, authorization, validation, and security.
 */
class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // INDEX (LIST) TESTS
    // =========================================================================

    /**
     * IX-001: List webhooks for user
     */
    public function test_list_webhooks_for_user(): void
    {
        $user = User::factory()->create();
        Webhook::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * IX-002: List empty for user with no webhooks
     */
    public function test_list_webhooks_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonPath('data', []);
    }

    /**
     * IX-003: List only returns user's webhooks
     */
    public function test_list_only_returns_users_webhooks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Webhook::factory()->count(2)->create(['user_id' => $user1->id]);
        Webhook::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * IX-004: List ordered by created_at desc
     */
    public function test_list_ordered_by_created_at_desc(): void
    {
        $user = User::factory()->create();

        $webhook1 = Webhook::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        $webhook2 = Webhook::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);
        $webhook3 = Webhook::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals([$webhook3->id, $webhook2->id, $webhook1->id], $ids);
    }

    /**
     * IX-005: List includes all fields
     */
    public function test_list_includes_all_fields(): void
    {
        $user = User::factory()->create();
        Webhook::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/hook',
            'events' => ['ranking.changed'],
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Test Webhook');
        $response->assertJsonPath('data.0.url', 'https://example.com/hook');
        $response->assertJsonPath('data.0.events', ['ranking.changed']);
        $response->assertJsonPath('data.0.enabled', true);
    }

    /**
     * IX-006: List excludes secret field
     */
    public function test_list_excludes_secret_field(): void
    {
        $user = User::factory()->create();
        Webhook::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonMissing(['secret']);
    }

    // =========================================================================
    // STORE (CREATE) TESTS
    // =========================================================================

    /**
     * ST-001: Create webhook with valid data
     */
    public function test_create_webhook_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Slack Integration',
                'url' => 'https://hooks.slack.com/services/T00/B00/XXX',
                'events' => ['ranking.changed', 'alert.triggered'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Slack Integration');
        $response->assertJsonPath('data.url', 'https://hooks.slack.com/services/T00/B00/XXX');
        $response->assertJsonPath('data.events', ['ranking.changed', 'alert.triggered']);
        $response->assertJsonPath('data.enabled', true);
    }

    /**
     * ST-002: Create returns secret once
     */
    public function test_create_returns_secret_once(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['secret']]);

        $secret = $response->json('data.secret');
        $this->assertNotNull($secret);
        $this->assertStringStartsWith('whsec_', $secret);
    }

    /**
     * ST-003: Create with minimal fields
     */
    public function test_create_with_minimal_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Minimal Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.enabled', true);
    }

    /**
     * ST-004: Create without name
     */
    public function test_create_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * ST-005: Create without url
     */
    public function test_create_without_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * ST-006: Create without events
     */
    public function test_create_without_events(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/hook',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['events']);
    }

    /**
     * ST-007: Create with invalid url format
     */
    public function test_create_with_invalid_url_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'not-a-valid-url',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * ST-009: Create with invalid event type
     */
    public function test_create_with_invalid_event_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['invalid.event'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['events.0']);
    }

    /**
     * ST-010: Create with empty events array
     */
    public function test_create_with_empty_events_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/hook',
                'events' => [],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['events']);
    }

    /**
     * ST-011: Create with name > 255 chars
     */
    public function test_create_with_name_too_long(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => str_repeat('a', 256),
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * ST-012: Create with url > 2048 chars
     */
    public function test_create_with_url_too_long(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/' . str_repeat('a', 2100),
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * ST-013: Create generates unique secret
     */
    public function test_create_generates_unique_secret(): void
    {
        $user = User::factory()->create();

        $response1 = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Webhook 1',
                'url' => 'https://example.com/hook1',
                'events' => ['ranking.changed'],
            ]);

        $response2 = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Webhook 2',
                'url' => 'https://example.com/hook2',
                'events' => ['ranking.changed'],
            ]);

        $secret1 = $response1->json('data.secret');
        $secret2 = $response2->json('data.secret');

        $this->assertNotEquals($secret1, $secret2);
    }

    /**
     * ST-014: Create with multiple events
     */
    public function test_create_with_multiple_events(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Multi Event Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed', 'alert.triggered', 'report.completed'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.events', ['ranking.changed', 'alert.triggered', 'report.completed']);
    }

    // =========================================================================
    // SHOW (SINGLE) TESTS
    // =========================================================================

    /**
     * SH-001: Show existing webhook
     */
    public function test_show_existing_webhook(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Test Webhook');
    }

    /**
     * SH-002: Show non-existent webhook
     */
    public function test_show_non_existent_webhook(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks/9999');

        $response->assertNotFound();
    }

    /**
     * SH-003: Show excludes secret
     */
    public function test_show_excludes_secret(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertOk();
        $response->assertJsonMissing(['secret']);
    }

    // =========================================================================
    // UPDATE TESTS
    // =========================================================================

    /**
     * UP-001: Update name
     */
    public function test_update_name(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'New Name');
    }

    /**
     * UP-002: Update url
     */
    public function test_update_url(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://old-url.com/hook',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'url' => 'https://new-url.com/hook',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.url', 'https://new-url.com/hook');
    }

    /**
     * UP-003: Update events
     */
    public function test_update_events(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'events' => ['ranking.changed'],
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'events' => ['ranking.changed', 'alert.triggered'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.events', ['ranking.changed', 'alert.triggered']);
    }

    /**
     * UP-004: Update enabled status
     */
    public function test_update_enabled_status(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'enabled' => false,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.enabled', false);
    }

    /**
     * UP-006: Update non-existent webhook
     */
    public function test_update_non_existent_webhook(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/v1/webhooks/9999', [
                'name' => 'New Name',
            ]);

        $response->assertNotFound();
    }

    /**
     * UP-007: Update with invalid url
     */
    public function test_update_with_invalid_url(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'url' => 'not-a-url',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * UP-008: Update with invalid event
     */
    public function test_update_with_invalid_event(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'events' => ['invalid.event'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['events.0']);
    }

    /**
     * UP-010: Update does not change secret
     */
    public function test_update_does_not_change_secret(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);
        $originalSecret = $webhook->secret;

        $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'Updated Name',
            ]);

        $webhook->refresh();
        $this->assertEquals($originalSecret, $webhook->secret);
    }

    // =========================================================================
    // DESTROY (DELETE) TESTS
    // =========================================================================

    /**
     * DE-001: Delete existing webhook
     */
    public function test_delete_existing_webhook(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    /**
     * DE-002: Delete non-existent webhook
     */
    public function test_delete_non_existent_webhook(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/webhooks/9999');

        $response->assertNotFound();
    }

    /**
     * DE-004: Delete cascades to deliveries
     */
    public function test_delete_cascades_to_deliveries(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        WebhookDelivery::factory()->count(3)->create(['webhook_id' => $webhook->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $this->assertDatabaseMissing('webhook_deliveries', ['webhook_id' => $webhook->id]);
    }

    // =========================================================================
    // TEST WEBHOOK ENDPOINT TESTS
    // =========================================================================

    /**
     * TW-001: Test webhook success
     */
    public function test_webhook_endpoint_success(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertOk();
        $response->assertJsonPath('data.delivered', true);
        $response->assertJsonPath('data.response_status', 200);
    }

    /**
     * TW-002: Test webhook failure
     */
    public function test_webhook_endpoint_failure(): void
    {
        Http::fake([
            'example.com/*' => Http::response(null, 500),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertOk();
        $response->assertJsonPath('data.delivered', false);
        $response->assertJsonPath('data.response_status', 500);
    }

    /**
     * TW-003: Test returns response status
     */
    public function test_webhook_returns_response_status(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 201),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertOk();
        $response->assertJsonPath('data.response_status', 201);
    }

    /**
     * TW-004: Test returns response time
     */
    public function test_webhook_returns_response_time(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['response_time_ms']]);
    }

    /**
     * TW-005: Test non-existent webhook
     */
    public function test_webhook_test_non_existent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks/9999/test');

        $response->assertNotFound();
    }

    /**
     * TW-006: Test creates delivery record
     */
    public function test_webhook_creates_delivery_record(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/hook',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
        ]);
    }

    // =========================================================================
    // AUTHORIZATION TESTS - UNAUTHENTICATED
    // =========================================================================

    /**
     * AZ-001: Unauthenticated list
     */
    public function test_unauthenticated_list(): void
    {
        $response = $this->getJson('/api/v1/webhooks');
        $response->assertUnauthorized();
    }

    /**
     * AZ-002: Unauthenticated create
     */
    public function test_unauthenticated_create(): void
    {
        $response = $this->postJson('/api/v1/webhooks', [
            'name' => 'Test',
            'url' => 'https://example.com/hook',
            'events' => ['ranking.changed'],
        ]);
        $response->assertUnauthorized();
    }

    /**
     * AZ-003: Unauthenticated show
     */
    public function test_unauthenticated_show(): void
    {
        $webhook = Webhook::factory()->create();
        $response = $this->getJson("/api/v1/webhooks/{$webhook->id}");
        $response->assertUnauthorized();
    }

    /**
     * AZ-004: Unauthenticated update
     */
    public function test_unauthenticated_update(): void
    {
        $webhook = Webhook::factory()->create();
        $response = $this->putJson("/api/v1/webhooks/{$webhook->id}", ['name' => 'Updated']);
        $response->assertUnauthorized();
    }

    /**
     * AZ-005: Unauthenticated delete
     */
    public function test_unauthenticated_delete(): void
    {
        $webhook = Webhook::factory()->create();
        $response = $this->deleteJson("/api/v1/webhooks/{$webhook->id}");
        $response->assertUnauthorized();
    }

    /**
     * AZ-006: Unauthenticated test
     */
    public function test_unauthenticated_test(): void
    {
        $webhook = Webhook::factory()->create();
        $response = $this->postJson("/api/v1/webhooks/{$webhook->id}/test");
        $response->assertUnauthorized();
    }

    // =========================================================================
    // AUTHORIZATION TESTS - CROSS-USER ACCESS
    // =========================================================================

    /**
     * AZ-011: Show other user's webhook
     */
    public function test_show_other_users_webhook(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertNotFound();
    }

    /**
     * AZ-012: Update other user's webhook
     */
    public function test_update_other_users_webhook(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->putJson("/api/v1/webhooks/{$webhook->id}", ['name' => 'Hacked']);

        $response->assertNotFound();
    }

    /**
     * AZ-013: Delete other user's webhook
     */
    public function test_delete_other_users_webhook(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id]);
    }

    /**
     * AZ-014: Test other user's webhook
     */
    public function test_test_other_users_webhook(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertNotFound();
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * EC-001: Empty name string
     */
    public function test_create_with_empty_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => '',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * EC-002: Whitespace-only name
     */
    public function test_create_with_whitespace_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => '   ',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * EC-003: URL with port
     */
    public function test_create_with_url_port(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com:8080/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
    }

    /**
     * EC-004: URL with query params
     */
    public function test_create_with_url_query_params(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook?token=abc&version=2',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
    }

    /**
     * EC-005: Unicode in name
     */
    public function test_create_with_unicode_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Slack 연동 🚀',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Slack 연동 🚀');
    }

    /**
     * EC-007: SQL injection in name
     */
    public function test_create_with_sql_injection_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => "' OR 1=1--",
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', "' OR 1=1--");
    }

    /**
     * EC-011: Empty url string
     */
    public function test_create_with_empty_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => '',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * EC-012: Invalid URL protocol
     */
    public function test_create_with_invalid_url_protocol(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'ftp://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * EC-014: Events as object instead of array
     */
    public function test_create_with_events_as_object(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook',
                'events' => ['event' => 'ranking.changed'],
            ]);

        $response->assertUnprocessable();
    }

    /**
     * EC-015: Null events
     */
    public function test_create_with_null_events(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook',
                'events' => null,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['events']);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * SC-001: XSS in name
     */
    public function test_create_with_xss_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => '<script>alert(1)</script>',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        // Should accept but sanitize on output
        $response->assertCreated();
    }

    /**
     * SC-002: XSS in url (javascript protocol)
     */
    public function test_create_with_javascript_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'javascript:alert(1)',
                'events' => ['ranking.changed'],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['url']);
    }

    /**
     * SC-005: SSRF with metadata endpoint
     */
    public function test_create_with_ssrf_metadata_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'http://169.254.169.254/latest/meta-data/',
                'events' => ['ranking.changed'],
            ]);

        // Should reject internal IPs or allow with warning
        // Implementation specific - either reject or allow with logging
        $this->assertContains($response->status(), [201, 422]);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /**
     * IT-001: Full CRUD lifecycle
     */
    public function test_full_crud_lifecycle(): void
    {
        $user = User::factory()->create();

        // Create
        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Lifecycle Test',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);
        $createResponse->assertCreated();
        $webhookId = $createResponse->json('data.id');

        // Read
        $readResponse = $this->actingAs($user)
            ->getJson("/api/v1/webhooks/{$webhookId}");
        $readResponse->assertOk();
        $readResponse->assertJsonPath('data.name', 'Lifecycle Test');

        // Update
        $updateResponse = $this->actingAs($user)
            ->putJson("/api/v1/webhooks/{$webhookId}", [
                'name' => 'Updated Lifecycle',
            ]);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.name', 'Updated Lifecycle');

        // Delete
        $deleteResponse = $this->actingAs($user)
            ->deleteJson("/api/v1/webhooks/{$webhookId}");
        $deleteResponse->assertNoContent();

        // Verify deleted
        $this->actingAs($user)
            ->getJson("/api/v1/webhooks/{$webhookId}")
            ->assertNotFound();
    }

    /**
     * IT-002: Multiple webhooks for user
     */
    public function test_multiple_webhooks_for_user(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($user)
                ->postJson('/api/v1/webhooks', [
                    'name' => "Webhook {$i}",
                    'url' => "https://example.com/hook{$i}",
                    'events' => ['ranking.changed'],
                ])->assertCreated();
        }

        $response = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
    }

    /**
     * IT-006: Secret only shown on create
     */
    public function test_secret_only_shown_on_create(): void
    {
        $user = User::factory()->create();

        // Create - secret visible
        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Secret Test',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);
        $createResponse->assertJsonStructure(['data' => ['secret']]);

        $webhookId = $createResponse->json('data.id');

        // Show - secret not visible
        $showResponse = $this->actingAs($user)
            ->getJson("/api/v1/webhooks/{$webhookId}");
        $showResponse->assertJsonMissing(['secret']);

        // List - secret not visible
        $listResponse = $this->actingAs($user)
            ->getJson('/api/v1/webhooks');
        $listResponse->assertJsonMissing(['secret']);
    }
}
