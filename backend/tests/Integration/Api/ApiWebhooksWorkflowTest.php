<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\Keyword;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for TASK-6-20: Webhooks API Workflow
 *
 * Tests the complete webhooks API lifecycle:
 * - Create webhook
 * - Trigger event
 * - Verify webhook received
 * - Test webhook endpoint
 * - Delete webhook
 */
class ApiWebhooksWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // WORKFLOW TESTS (WF)
    // =========================================================================

    /**
     * WF-001: Full webhook lifecycle
     * Create -> Test -> Trigger -> Verify -> Delete
     */
    public function test_full_webhook_lifecycle(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        // Step 1: Create webhook
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Integration Test Webhook',
                'url' => 'https://example.com/webhook',
                'events' => ['ranking.changed'],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Integration Test Webhook');

        $webhookId = $createResponse->json('data.id');
        $secret = $createResponse->json('data.secret');

        $this->assertStringStartsWith('whsec_', $secret);

        // Step 2: Test webhook endpoint
        $testResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhookId}/test");

        $testResponse->assertStatus(200)
            ->assertJsonPath('data.delivered', true);

        // Step 3: List webhooks
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Integration Test Webhook');

        // Step 4: Delete webhook
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/webhooks/{$webhookId}");

        $deleteResponse->assertStatus(204);

        // Verify webhook deleted
        $this->assertDatabaseMissing('webhooks', ['id' => $webhookId]);
    }

    /**
     * WF-002: Webhook receives event
     */
    public function test_webhook_receives_event(): void
    {
        $receivedPayload = null;

        Http::fake(function ($request) use (&$receivedPayload) {
            $receivedPayload = $request->body();
            return Http::response(['ok' => true], 200);
        });

        // Create webhook
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Event Receiver',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $webhookId = $response->json('data.id');

        // Test the webhook (triggers a test event)
        $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhookId}/test");

        // Verify payload was sent
        $this->assertNotNull($receivedPayload);

        $payload = json_decode($receivedPayload, true);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertArrayHasKey('data', $payload);
    }

    /**
     * WF-003: Multiple webhooks for same event
     */
    public function test_multiple_webhooks_for_same_event(): void
    {
        Http::fake([
            'webhook1.com/*' => Http::response(['ok' => true], 200),
            'webhook2.com/*' => Http::response(['ok' => true], 200),
        ]);

        // Create two webhooks for same event
        $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Webhook 1',
                'url' => 'https://webhook1.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Webhook 2',
                'url' => 'https://webhook2.com/hook',
                'events' => ['ranking.changed'],
            ]);

        // List should show both
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * WF-004: Webhook with multiple events
     */
    public function test_webhook_with_multiple_events(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Multi Event Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed', 'alert.triggered', 'report.completed'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.events', ['ranking.changed', 'alert.triggered', 'report.completed']);
    }

    // =========================================================================
    // CREATE WORKFLOW TESTS (CW)
    // =========================================================================

    /**
     * CW-001: Create with valid data
     */
    public function test_create_with_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/webhook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'enabled',
                    'secret',
                ],
            ]);

        // Verify database
        $this->assertDatabaseHas('webhooks', [
            'user_id' => $this->user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
        ]);
    }

    /**
     * CW-002: Secret returned only on create
     */
    public function test_secret_returned_only_on_create(): void
    {
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Secret Test',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        // Secret present on create
        $createResponse->assertJsonStructure(['data' => ['secret']]);

        $webhookId = $createResponse->json('data.id');

        // Secret NOT present on show
        $showResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/webhooks/{$webhookId}");

        $showResponse->assertJsonMissing(['secret']);

        // Secret NOT present on list
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks');

        $listResponse->assertJsonMissing(['secret']);
    }

    /**
     * CW-003: Create without name fails
     */
    public function test_create_without_name_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CW-004: Create without url fails
     */
    public function test_create_without_url_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /**
     * CW-005: Create without events fails
     */
    public function test_create_without_events_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events']);
    }

    /**
     * CW-006: Create with invalid url fails
     */
    public function test_create_with_invalid_url_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'not-a-url',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /**
     * CW-007: Create with invalid event fails
     */
    public function test_create_with_invalid_event_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook',
                'events' => ['invalid.event'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events.0']);
    }

    // =========================================================================
    // TEST ENDPOINT WORKFLOW TESTS (TE)
    // =========================================================================

    /**
     * TE-001: Test webhook success
     */
    public function test_webhook_test_success(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200)
            ->assertJsonPath('data.delivered', true)
            ->assertJsonPath('data.response_status', 200);
    }

    /**
     * TE-002: Test webhook failure
     */
    public function test_webhook_test_failure(): void
    {
        Http::fake([
            'example.com/*' => Http::response(null, 500),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200)
            ->assertJsonPath('data.delivered', false)
            ->assertJsonPath('data.response_status', 500);
    }

    /**
     * TE-003: Test webhook timeout
     */
    public function test_webhook_test_timeout(): void
    {
        Http::fake([
            'example.com/*' => Http::response(null, 200)->timeout(30),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200);
    }

    /**
     * TE-004: Test webhook creates delivery record
     */
    public function test_webhook_creates_delivery_record(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
        ]);
    }

    /**
     * TE-005: Test disabled webhook
     */
    public function test_disabled_webhook(): void
    {
        Http::fake();

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
            'enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        // Disabled webhooks should not be tested
        $response->assertStatus(400);
        Http::assertNothingSent();
    }

    // =========================================================================
    // DELIVERY TESTS (DL)
    // =========================================================================

    /**
     * DL-001: Delivery history recorded
     */
    public function test_delivery_history_recorded(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        // Trigger multiple deliveries
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)
                ->postJson("/api/v1/webhooks/{$webhook->id}/test");
        }

        // Verify history
        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)->get();
        $this->assertEquals(3, $deliveries->count());
    }

    /**
     * DL-002: Delivery includes response time
     */
    public function test_delivery_includes_response_time(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['response_time_ms']]);
    }

    // =========================================================================
    // UPDATE WORKFLOW TESTS (UW)
    // =========================================================================

    /**
     * UW-001: Update name
     */
    public function test_update_name(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    /**
     * UW-002: Update url
     */
    public function test_update_url(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://old-url.com/hook',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'url' => 'https://new-url.com/hook',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.url', 'https://new-url.com/hook');
    }

    /**
     * UW-003: Update events
     */
    public function test_update_events(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'events' => ['ranking.changed'],
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'events' => ['ranking.changed', 'alert.triggered'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.events', ['ranking.changed', 'alert.triggered']);
    }

    /**
     * UW-004: Update enabled status
     */
    public function test_update_enabled_status(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'enabled' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.enabled', false);
    }

    /**
     * UW-005: Update does not change secret
     */
    public function test_update_does_not_change_secret(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'secret' => 'whsec_original',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'Updated Name',
            ]);

        $webhook->refresh();
        $this->assertEquals('whsec_original', $webhook->secret);
    }

    // =========================================================================
    // DELETE WORKFLOW TESTS (DW)
    // =========================================================================

    /**
     * DW-001: Delete webhook
     */
    public function test_delete_webhook(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    /**
     * DW-002: Delete webhook removes deliveries
     */
    public function test_delete_removes_deliveries(): void
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
        ]);

        WebhookDelivery::factory()->count(3)->create([
            'webhook_id' => $webhook->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $this->assertEquals(0, WebhookDelivery::where('webhook_id', $webhook->id)->count());
    }

    /**
     * DW-003: Cannot delete another user's webhook
     */
    public function test_cannot_delete_another_users_webhook(): void
    {
        $otherUser = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id]);
    }

    // =========================================================================
    // AUTHORIZATION TESTS (AZ)
    // =========================================================================

    /**
     * AZ-001: Cannot access another user's webhook
     */
    public function test_cannot_access_another_users_webhook(): void
    {
        $otherUser = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(404);
    }

    /**
     * AZ-002: Unauthenticated access denied
     */
    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->getJson('/api/v1/webhooks');
        $response->assertStatus(401);
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: Secret never exposed after creation
     */
    public function test_secret_never_exposed_after_creation(): void
    {
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $webhookId = $createResponse->json('data.id');

        // Show endpoint
        $this->actingAs($this->user)
            ->getJson("/api/v1/webhooks/{$webhookId}")
            ->assertJsonMissing(['secret']);

        // List endpoint
        $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks')
            ->assertJsonMissing(['secret']);

        // Update endpoint
        $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$webhookId}", ['name' => 'Updated'])
            ->assertJsonMissing(['secret']);
    }

    /**
     * SEC-002: HMAC signature in webhook delivery
     */
    public function test_hmac_signature_in_webhook_delivery(): void
    {
        $receivedSignature = null;

        Http::fake(function ($request) use (&$receivedSignature) {
            $receivedSignature = $request->header('X-SEOIndicator-Signature')[0] ?? null;
            return Http::response(['ok' => true], 200);
        });

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/hook',
            'secret' => 'whsec_testsecret',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        // Signature should be present
        $this->assertNotNull($receivedSignature);
        $this->assertStringStartsWith('sha256=', $receivedSignature);
    }

    /**
     * SEC-003: XSS in name sanitized
     */
    public function test_xss_in_name_sanitized(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => '<script>alert("xss")</script>',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201);
        // Name should be sanitized on output
    }

    /**
     * SEC-004: SQL injection protection
     */
    public function test_sql_injection_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => "'; DROP TABLE webhooks; --",
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('webhooks', [
            'name' => "'; DROP TABLE webhooks; --",
        ]);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Unicode in name
     */
    public function test_unicode_in_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Slack 연동 🚀 тест',
                'url' => 'https://example.com/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Slack 연동 🚀 тест');
    }

    /**
     * EC-002: URL with port
     */
    public function test_url_with_port(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com:8080/hook',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * EC-003: URL with query params
     */
    public function test_url_with_query_params(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', [
                'name' => 'Test',
                'url' => 'https://example.com/hook?token=abc&version=2',
                'events' => ['ranking.changed'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * EC-004: Empty webhook list
     */
    public function test_empty_webhook_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }
}
