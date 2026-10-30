<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * Migration tests for TASK-6-12: Create Webhooks Tables
 *
 * Tests verify:
 * - Table structure matches specification
 * - Foreign key constraints work correctly
 * - Unique constraint prevents duplicate secret
 * - Column types are correct
 * - Indexes are created
 */
class CreateWebhooksTablesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // WEBHOOKS TABLE TESTS
    // =========================================================================

    /**
     * Test that the webhooks table exists.
     */
    public function test_webhooks_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('webhooks'),
            'webhooks table should exist'
        );
    }

    /**
     * Test that all required columns exist in webhooks table.
     */
    public function test_webhooks_required_columns_exist(): void
    {
        $columns = [
            'id',
            'user_id',
            'name',
            'url',
            'secret',
            'events',
            'enabled',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('webhooks', $column),
                "Column '{$column}' should exist in webhooks table"
            );
        }
    }

    /**
     * Test that foreign key to users exists and works.
     */
    public function test_webhooks_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $webhook->user_id);

        // Test cascade delete
        $webhookId = $webhook->id;
        $user->delete();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhookId]);
    }

    /**
     * Test that foreign key constraint prevents invalid user_id.
     */
    public function test_webhooks_foreign_key_constraint_invalid_user(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create webhook with non-existent user
        DB::table('webhooks')->insert([
            'user_id' => 99999,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test_secret',
            'events' => json_encode(['ranking.changed']),
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test unique constraint on secret.
     */
    public function test_webhooks_unique_constraint_secret(): void
    {
        $user = User::factory()->create();
        $secret = Webhook::generateSecret();

        // Create first webhook
        Webhook::factory()->create([
            'user_id' => $user->id,
            'secret' => $secret,
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        Webhook::factory()->create([
            'user_id' => $user->id,
            'secret' => $secret,
        ]);
    }

    /**
     * Test that different secret values can be inserted.
     */
    public function test_webhooks_different_secret_allowed(): void
    {
        $user = User::factory()->create();

        $webhook1 = Webhook::factory()->create([
            'user_id' => $user->id,
            'secret' => Webhook::generateSecret(),
        ]);

        $webhook2 = Webhook::factory()->create([
            'user_id' => $user->id,
            'secret' => Webhook::generateSecret(),
        ]);

        $this->assertNotEquals($webhook1->id, $webhook2->id);
        $this->assertNotEquals($webhook1->secret, $webhook2->secret);
    }

    /**
     * Test that indexes are created on webhooks table.
     */
    public function test_webhooks_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('webhooks');

        // Check for user_id index
        $this->assertIndexExists($indexes, 'user_id');

        // Check for enabled index
        $this->assertIndexExists($indexes, 'enabled');

        // Check for unique index on secret
        $this->assertUniqueIndexExists($indexes, 'secret');
    }

    /**
     * Test that events can be stored as JSON.
     */
    public function test_webhooks_events_stored_as_json(): void
    {
        $events = ['ranking.changed', 'alert.triggered', 'report.completed'];

        $webhook = Webhook::factory()->create([
            'events' => $events,
        ]);

        $this->assertEquals($events, $webhook->events);
    }

    /**
     * Test that events can be empty array.
     */
    public function test_webhooks_events_empty_array(): void
    {
        $webhook = Webhook::factory()->create(['events' => []]);

        $this->assertEquals([], $webhook->events);
    }

    /**
     * Test default enabled is true.
     */
    public function test_webhooks_default_enabled(): void
    {
        $webhook = Webhook::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => Webhook::generateSecret(),
            'events' => ['ranking.changed'],
        ]);

        // Refresh from DB to get actual default value
        $this->assertTrue($webhook->fresh()->enabled);
    }

    /**
     * Test that enabled can be set to false.
     */
    public function test_webhooks_enabled_can_be_false(): void
    {
        $webhook = Webhook::factory()->create(['enabled' => false]);

        $this->assertFalse($webhook->enabled);
    }

    /**
     * Test that timestamps are cast to Carbon instances.
     */
    public function test_webhooks_timestamps_cast_to_carbon(): void
    {
        $webhook = Webhook::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $webhook->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $webhook->updated_at);
    }

    /**
     * Test that multiple webhooks can be created for the same user.
     */
    public function test_webhooks_multiple_per_user(): void
    {
        $user = User::factory()->create();

        $webhooks = Webhook::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertEquals(5, $webhooks->count());
        foreach ($webhooks as $webhook) {
            $this->assertEquals($user->id, $webhook->user_id);
        }
    }

    /**
     * Test that name can be 255 characters.
     */
    public function test_webhooks_name_max_length(): void
    {
        $longName = str_repeat('a', 255);

        $webhook = Webhook::factory()->create(['name' => $longName]);

        $this->assertEquals(255, strlen($webhook->name));
        $this->assertEquals($longName, $webhook->name);
    }

    /**
     * Test that url can be up to 2048 characters.
     */
    public function test_webhooks_url_max_length(): void
    {
        $baseUrl = 'https://example.com/webhook/';
        $longUrl = $baseUrl . str_repeat('a', 2048 - strlen($baseUrl));

        $webhook = Webhook::factory()->create(['url' => $longUrl]);

        $this->assertEquals($longUrl, $webhook->url);
    }

    /**
     * Test that secret has correct format (whsec_ prefix).
     */
    public function test_webhooks_secret_format(): void
    {
        $secret = Webhook::generateSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertEquals(70, strlen($secret)); // whsec_ (6) + 64 hex chars
    }

    /**
     * Test that secret is hidden from serialization.
     */
    public function test_webhooks_secret_hidden_from_serialization(): void
    {
        $webhook = Webhook::factory()->create();
        $array = $webhook->toArray();

        $this->assertArrayNotHasKey('secret', $array);
    }

    // =========================================================================
    // WEBHOOK_DELIVERIES TABLE TESTS
    // =========================================================================

    /**
     * Test that the webhook_deliveries table exists.
     */
    public function test_webhook_deliveries_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('webhook_deliveries'),
            'webhook_deliveries table should exist'
        );
    }

    /**
     * Test that all required columns exist in webhook_deliveries table.
     */
    public function test_webhook_deliveries_required_columns_exist(): void
    {
        $columns = [
            'id',
            'webhook_id',
            'event_type',
            'payload',
            'response_status',
            'response_body',
            'attempt_count',
            'delivered_at',
            'next_retry_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('webhook_deliveries', $column),
                "Column '{$column}' should exist in webhook_deliveries table"
            );
        }
    }

    /**
     * Test that foreign key to webhooks exists and works.
     */
    public function test_webhook_deliveries_foreign_key_to_webhooks(): void
    {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);

        $this->assertEquals($webhook->id, $delivery->webhook_id);

        // Test cascade delete
        $deliveryId = $delivery->id;
        $webhook->delete();

        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $deliveryId]);
    }

    /**
     * Test that foreign key constraint prevents invalid webhook_id.
     */
    public function test_webhook_deliveries_foreign_key_constraint_invalid_webhook(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create delivery with non-existent webhook
        DB::table('webhook_deliveries')->insert([
            'webhook_id' => 99999,
            'event_type' => 'ranking.changed',
            'payload' => json_encode(['test' => true]),
            'attempt_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created on webhook_deliveries table.
     */
    public function test_webhook_deliveries_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('webhook_deliveries');

        // Check for webhook_id index
        $this->assertIndexExists($indexes, 'webhook_id');

        // Check for next_retry_at index
        $this->assertIndexExists($indexes, 'next_retry_at');

        // Check for response_status index
        $this->assertIndexExists($indexes, 'response_status');
    }

    /**
     * Test that payload can be stored as JSON.
     */
    public function test_webhook_deliveries_payload_stored_as_json(): void
    {
        $payload = [
            'event' => 'ranking.changed',
            'keyword_id' => 123,
            'old_position' => 10,
            'new_position' => 5,
        ];

        $delivery = WebhookDelivery::factory()->create([
            'payload' => $payload,
        ]);

        $this->assertEquals($payload, $delivery->payload);
    }

    /**
     * Test that default attempt_count is 1.
     */
    public function test_webhook_deliveries_default_attempt_count(): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => Webhook::factory()->create()->id,
            'event_type' => 'ranking.changed',
            'payload' => ['test' => true],
        ]);

        // Refresh from DB to get actual default value
        $this->assertEquals(1, $delivery->fresh()->attempt_count);
    }

    /**
     * Test that response_status can be null.
     */
    public function test_webhook_deliveries_response_status_nullable(): void
    {
        $delivery = WebhookDelivery::factory()->create(['response_status' => null]);

        $this->assertNull($delivery->response_status);
    }

    /**
     * Test that response_body can be null.
     */
    public function test_webhook_deliveries_response_body_nullable(): void
    {
        $delivery = WebhookDelivery::factory()->create(['response_body' => null]);

        $this->assertNull($delivery->response_body);
    }

    /**
     * Test that delivered_at can be null.
     */
    public function test_webhook_deliveries_delivered_at_nullable(): void
    {
        $delivery = WebhookDelivery::factory()->create(['delivered_at' => null]);

        $this->assertNull($delivery->delivered_at);
    }

    /**
     * Test that next_retry_at can be null.
     */
    public function test_webhook_deliveries_next_retry_at_nullable(): void
    {
        $delivery = WebhookDelivery::factory()->create(['next_retry_at' => null]);

        $this->assertNull($delivery->next_retry_at);
    }

    /**
     * Test that timestamps are cast to Carbon instances.
     */
    public function test_webhook_deliveries_timestamps_cast_to_carbon(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'delivered_at' => now(),
            'next_retry_at' => now()->addMinutes(5),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $delivery->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $delivery->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $delivery->delivered_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $delivery->next_retry_at);
    }

    /**
     * Test that multiple deliveries can be created for the same webhook.
     */
    public function test_webhook_deliveries_multiple_per_webhook(): void
    {
        $webhook = Webhook::factory()->create();

        $deliveries = WebhookDelivery::factory()->count(5)->create(['webhook_id' => $webhook->id]);

        $this->assertEquals(5, $deliveries->count());
        foreach ($deliveries as $delivery) {
            $this->assertEquals($webhook->id, $delivery->webhook_id);
        }
    }

    /**
     * Test that response_status can store HTTP status codes.
     */
    public function test_webhook_deliveries_response_status_http_codes(): void
    {
        $webhook = Webhook::factory()->create();

        // Test various HTTP status codes
        $statuses = [200, 201, 204, 400, 401, 403, 404, 500, 502, 503];

        foreach ($statuses as $status) {
            $delivery = WebhookDelivery::factory()->create([
                'webhook_id' => $webhook->id,
                'response_status' => $status,
            ]);

            $this->assertEquals($status, $delivery->response_status);
        }
    }

    /**
     * Test that attempt_count can be incremented.
     */
    public function test_webhook_deliveries_attempt_count_increment(): void
    {
        $delivery = WebhookDelivery::factory()->create(['attempt_count' => 1]);

        $delivery->update(['attempt_count' => 2]);

        $this->assertEquals(2, $delivery->fresh()->attempt_count);
    }

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /**
     * Test webhook has many deliveries relationship.
     */
    public function test_webhook_has_many_deliveries(): void
    {
        $webhook = Webhook::factory()->create();
        $deliveries = WebhookDelivery::factory()->count(3)->create(['webhook_id' => $webhook->id]);

        $this->assertEquals(3, $webhook->deliveries->count());
        foreach ($deliveries as $delivery) {
            $this->assertEquals($webhook->id, $delivery->webhook_id);
        }
    }

    /**
     * Test delivery belongs to webhook relationship.
     */
    public function test_delivery_belongs_to_webhook(): void
    {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);

        $this->assertEquals($webhook->id, $delivery->webhook->id);
    }

    /**
     * Test webhook belongs to user relationship.
     */
    public function test_webhook_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $webhook = Webhook::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $webhook->user->id);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * Test SQL injection attempt in webhook name field.
     */
    public function test_webhook_sql_injection_in_name(): void
    {
        $maliciousName = "Test'; DROP TABLE webhooks; --";

        $webhook = Webhook::factory()->create(['name' => $maliciousName]);

        // Verify the table still exists and name was stored safely
        $this->assertTrue(Schema::hasTable('webhooks'));
        $this->assertEquals($maliciousName, $webhook->fresh()->name);
    }

    /**
     * Test XSS attempt in webhook name field.
     */
    public function test_webhook_xss_in_name(): void
    {
        $maliciousName = '<script>alert("xss")</script>';

        $webhook = Webhook::factory()->create(['name' => $maliciousName]);

        // Name stored as-is (sanitization should happen on output)
        $this->assertEquals($maliciousName, $webhook->name);
    }

    /**
     * Test SQL injection attempt in delivery response_body field.
     */
    public function test_delivery_sql_injection_in_response_body(): void
    {
        $maliciousBody = "OK'; DROP TABLE webhook_deliveries; --";

        $delivery = WebhookDelivery::factory()->create(['response_body' => $maliciousBody]);

        // Verify the table still exists
        $this->assertTrue(Schema::hasTable('webhook_deliveries'));
        $this->assertEquals($maliciousBody, $delivery->fresh()->response_body);
    }

    /**
     * Test that valid event types are enforced in model.
     */
    public function test_webhook_valid_event_types(): void
    {
        $validEvents = Webhook::VALID_EVENTS;

        $this->assertContains('ranking.changed', $validEvents);
        $this->assertContains('alert.triggered', $validEvents);
        $this->assertContains('report.completed', $validEvents);
        $this->assertContains('keyword.added', $validEvents);
        $this->assertContains('keyword.removed', $validEvents);
    }

    /**
     * Test that webhook can check if it subscribes to an event.
     */
    public function test_webhook_subscribes_to_event(): void
    {
        $webhook = Webhook::factory()->create([
            'events' => ['ranking.changed', 'alert.triggered'],
        ]);

        $this->assertTrue($webhook->subscribesTo('ranking.changed'));
        $this->assertTrue($webhook->subscribesTo('alert.triggered'));
        $this->assertFalse($webhook->subscribesTo('report.completed'));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = [];
            $result = DB::select("PRAGMA index_list('{$table}')");
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info('{$row->name}')");
                foreach ($indexInfo as $info) {
                    $indexes[$row->name][] = $info->name;
                }
            }
            return $indexes;
        }

        // MySQL
        $indexes = [];
        $result = DB::select("SHOW INDEX FROM {$table}");
        foreach ($result as $row) {
            $indexes[$row->Key_name][] = $row->Column_name;
        }
        return $indexes;
    }

    /**
     * Helper: Assert index exists.
     */
    protected function assertIndexExists(array $indexes, string $indexName): void
    {
        $found = collect($indexes)->contains(function ($columns, $name) use ($indexName) {
            return str_contains(strtolower($name), strtolower($indexName));
        });

        $this->assertTrue($found, "Index '{$indexName}' does not exist");
    }

    /**
     * Helper: Assert unique index exists.
     */
    protected function assertUniqueIndexExists(array $indexes, string $columnName): void
    {
        $found = collect($indexes)->contains(function ($columns, $name) use ($columnName) {
            return in_array($columnName, $columns) && str_contains(strtolower($name), 'unique');
        });

        $this->assertTrue($found, "Unique index for column '{$columnName}' does not exist");
    }
}
