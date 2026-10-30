<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\GSCConnection;

/**
 * Migration tests for TASK-12-1: Create GSC Connections Table
 *
 * Tests verify:
 * - Table structure matches specification
 * - Foreign key constraints work correctly
 * - Unique constraint prevents duplicates
 * - Column types are correct
 * - Indexes are created
 */
class CreateGSCConnectionsTableTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that the gsc_connections table exists.
     */
    public function test_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('gsc_connections'),
            'gsc_connections table should exist'
        );
    }

    /**
     * Test that all required columns exist.
     */
    public function test_required_columns_exist(): void
    {
        $columns = [
            'id',
            'user_id',
            'site_url',
            'property_url',
            'access_token',
            'refresh_token',
            'token_expires_at',
            'status',
            'last_synced_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('gsc_connections', $column),
                "Column '{$column}' should exist in gsc_connections table"
            );
        }
    }

    /**
     * Test that foreign key to users exists and works.
     */
    public function test_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $connection->user_id);

        // Test cascade delete
        $connectionId = $connection->id;
        $user->delete();

        $this->assertDatabaseMissing('gsc_connections', ['id' => $connectionId]);
    }

    /**
     * Test unique constraint on user_id and property_url.
     */
    public function test_unique_constraint_user_property(): void
    {
        $user = User::factory()->create();
        $propertyUrl = 'https://example.com/';

        // Create first connection
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
        ]);
    }

    /**
     * Test that different users can have the same property_url.
     */
    public function test_different_users_same_property(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $propertyUrl = 'https://shared-property.com/';

        $connection1 = GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'property_url' => $propertyUrl,
        ]);

        $connection2 = GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'property_url' => $propertyUrl,
        ]);

        $this->assertNotEquals($connection1->id, $connection2->id);
        $this->assertEquals($propertyUrl, $connection1->property_url);
        $this->assertEquals($propertyUrl, $connection2->property_url);
    }

    /**
     * Test that indexes are created.
     */
    public function test_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('gsc_connections');

        // Check for user_id index
        $this->assertIndexExists($indexes, 'user_id');

        // Check for status index
        $this->assertIndexExists($indexes, 'status');

        // Check for token_expires_at index
        $this->assertIndexExists($indexes, 'token_expires_at');

        // Check for unique constraint
        $this->assertIndexExists($indexes, 'idx_user_property');
    }

    /**
     * Test that access_token and refresh_token are encrypted.
     */
    public function test_tokens_are_encrypted(): void
    {
        $plainAccessToken = 'plain_access_token_12345';
        $plainRefreshToken = 'plain_refresh_token_67890';

        $connection = GSCConnection::factory()->create([
            'access_token' => $plainAccessToken,
            'refresh_token' => $plainRefreshToken,
        ]);

        // Refresh from database
        $connection->refresh();

        // The retrieved value should match what we set (decrypted)
        $this->assertEquals($plainAccessToken, $connection->access_token);
        $this->assertEquals($plainRefreshToken, $connection->refresh_token);

        // Raw database value should be encrypted (not the plain text)
        $rawConnection = DB::table('gsc_connections')->find($connection->id);
        $this->assertNotEquals($plainAccessToken, $rawConnection->access_token);
        $this->assertNotEquals($plainRefreshToken, $rawConnection->refresh_token);
    }

    /**
     * Test that status enum accepts valid values.
     */
    public function test_status_enum_values(): void
    {
        $validStatuses = ['active', 'expired', 'revoked'];

        foreach ($validStatuses as $status) {
            $connection = GSCConnection::factory()->create(['status' => $status]);
            $this->assertEquals($status, $connection->status);
        }
    }

    /**
     * Test that last_synced_at can be null.
     */
    public function test_last_synced_at_nullable(): void
    {
        $connection = GSCConnection::factory()->create(['last_synced_at' => null]);

        $this->assertNull($connection->last_synced_at);
    }

    /**
     * Test default status is active.
     */
    public function test_default_status_is_active(): void
    {
        $connection = GSCConnection::create([
            'user_id' => User::factory()->create()->id,
            'site_url' => 'example.com',
            'property_url' => 'https://example.com/',
            'access_token' => 'test_token',
            'refresh_token' => 'test_refresh',
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertEquals('active', $connection->status);
    }

    /**
     * Test token_expires_at is cast to datetime.
     */
    public function test_token_expires_at_is_datetime(): void
    {
        $expiresAt = now()->addHours(2);
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => $expiresAt,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $connection->token_expires_at);
        $this->assertTrue($connection->token_expires_at->isSameMinute($expiresAt));
    }

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
}
