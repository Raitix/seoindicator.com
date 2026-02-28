<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ApiKey;

/**
 * Migration tests for TASK-6-1: Create API Keys Table
 *
 * Tests verify:
 * - Table structure matches specification
 * - Foreign key constraints work correctly
 * - Unique constraint prevents duplicate key_hash
 * - Column types are correct
 * - Indexes are created
 */
class CreateApiKeysTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the api_keys table exists.
     */
    public function test_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('api_keys'),
            'api_keys table should exist'
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
            'name',
            'key_hash',
            'key_prefix',
            'permissions',
            'rate_limit',
            'last_used_at',
            'expires_at',
            'revoked_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('api_keys', $column),
                "Column '{$column}' should exist in api_keys table"
            );
        }
    }

    /**
     * Test that foreign key to users exists and works.
     */
    public function test_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $apiKey->user_id);

        // Test cascade delete
        $apiKeyId = $apiKey->id;
        $user->delete();

        $this->assertDatabaseMissing('api_keys', ['id' => $apiKeyId]);
    }

    /**
     * Test that foreign key constraint prevents invalid user_id.
     */
    public function test_foreign_key_constraint_invalid_user(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create API key with non-existent user
        DB::table('api_keys')->insert([
            'user_id' => 99999,
            'name' => 'Test Key',
            'key_hash' => hash('sha256', 'test_key'),
            'key_prefix' => 'sk_live_test_key',
            'rate_limit' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test unique constraint on key_hash.
     */
    public function test_unique_constraint_key_hash(): void
    {
        $user = User::factory()->create();
        $keyHash = hash('sha256', 'test_unique_key');

        // Create first API key
        ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_hash' => $keyHash,
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_hash' => $keyHash,
        ]);
    }

    /**
     * Test that different key_hash values can be inserted.
     */
    public function test_different_key_hash_allowed(): void
    {
        $user = User::factory()->create();

        $key1 = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', 'key1'),
        ]);

        $key2 = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', 'key2'),
        ]);

        $this->assertNotEquals($key1->id, $key2->id);
        $this->assertNotEquals($key1->key_hash, $key2->key_hash);
    }

    /**
     * Test that key_prefix is not unique (multiple keys can have same prefix).
     */
    public function test_key_prefix_not_unique(): void
    {
        $user = User::factory()->create();
        $prefix = 'sk_live_same_prefix';

        $key1 = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', 'key1_prefix_test'),
        ]);

        $key2 = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', 'key2_prefix_test'),
        ]);

        $this->assertEquals($prefix, $key1->key_prefix);
        $this->assertEquals($prefix, $key2->key_prefix);
    }

    /**
     * Test that indexes are created.
     */
    public function test_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('api_keys');

        // Check for user_id index
        $this->assertIndexExists($indexes, 'user_id');

        // Check for key_prefix index
        $this->assertIndexExists($indexes, 'key_prefix');

        // Check for revoked_at index
        $this->assertIndexExists($indexes, 'revoked_at');

        // Check for unique index on key_hash
        $this->assertUniqueIndexExists($indexes, 'key_hash');
    }

    /**
     * Test that permissions can be stored as JSON.
     */
    public function test_permissions_stored_as_json(): void
    {
        $permissions = ['keywords:read', 'keywords:write', 'rankings:read'];

        $apiKey = ApiKey::factory()->create([
            'permissions' => $permissions,
        ]);

        $this->assertEquals($permissions, $apiKey->permissions);
    }

    /**
     * Test that permissions can be null.
     */
    public function test_permissions_nullable(): void
    {
        $apiKey = ApiKey::factory()->create(['permissions' => null]);

        $this->assertNull($apiKey->permissions);
    }

    /**
     * Test that permissions can be empty array.
     */
    public function test_permissions_empty_array(): void
    {
        $apiKey = ApiKey::factory()->create(['permissions' => []]);

        $this->assertEquals([], $apiKey->permissions);
    }

    /**
     * Test default rate_limit is 60.
     */
    public function test_default_rate_limit(): void
    {
        $apiKey = ApiKey::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Test Key',
            'key_hash' => hash('sha256', 'test_default_rate'),
            'key_prefix' => 'sk_live_default',
        ]);

        // Refresh from DB to get actual default value
        $this->assertEquals(60, $apiKey->fresh()->rate_limit);
    }

    /**
     * Test that rate_limit can be set to boundary values.
     */
    public function test_rate_limit_boundary_values(): void
    {
        // Minimum rate limit
        $key1 = ApiKey::factory()->create([
            'rate_limit' => 1,
            'key_hash' => hash('sha256', 'rate_min'),
            'key_prefix' => 'sk_live_min_rate',
        ]);
        $this->assertEquals(1, $key1->rate_limit);

        // High rate limit
        $key2 = ApiKey::factory()->create([
            'rate_limit' => 10000,
            'key_hash' => hash('sha256', 'rate_max'),
            'key_prefix' => 'sk_live_max_rate',
        ]);
        $this->assertEquals(10000, $key2->rate_limit);
    }

    /**
     * Test that last_used_at can be null.
     */
    public function test_last_used_at_nullable(): void
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => null]);

        $this->assertNull($apiKey->last_used_at);
    }

    /**
     * Test that expires_at can be null.
     */
    public function test_expires_at_nullable(): void
    {
        $apiKey = ApiKey::factory()->create(['expires_at' => null]);

        $this->assertNull($apiKey->expires_at);
    }

    /**
     * Test that revoked_at can be null.
     */
    public function test_revoked_at_nullable(): void
    {
        $apiKey = ApiKey::factory()->create(['revoked_at' => null]);

        $this->assertNull($apiKey->revoked_at);
    }

    /**
     * Test that timestamps are cast to Carbon instances.
     */
    public function test_timestamps_cast_to_carbon(): void
    {
        $apiKey = ApiKey::factory()->create([
            'last_used_at' => now(),
            'expires_at' => now()->addYear(),
            'revoked_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $apiKey->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $apiKey->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $apiKey->last_used_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $apiKey->expires_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $apiKey->revoked_at);
    }

    /**
     * Test that multiple API keys can be created for the same user.
     */
    public function test_multiple_keys_per_user(): void
    {
        $user = User::factory()->create();

        $keys = ApiKey::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertEquals(5, $keys->count());
        foreach ($keys as $key) {
            $this->assertEquals($user->id, $key->user_id);
        }
    }

    /**
     * Test that name can be 255 characters.
     */
    public function test_name_max_length(): void
    {
        $longName = str_repeat('a', 255);

        $apiKey = ApiKey::factory()->create(['name' => $longName]);

        $this->assertEquals(255, strlen($apiKey->name));
        $this->assertEquals($longName, $apiKey->name);
    }

    /**
     * Test that key_hash is exactly 64 characters (SHA-256).
     */
    public function test_key_hash_length(): void
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertEquals(64, strlen($apiKey->key_hash));
    }

    /**
     * Test that key_prefix is at most 20 characters.
     */
    public function test_key_prefix_max_length(): void
    {
        $prefix = str_repeat('a', 20);

        $apiKey = ApiKey::factory()->create(['key_prefix' => $prefix]);

        $this->assertEquals(20, strlen($apiKey->key_prefix));
    }

    /**
     * Test SQL injection attempt in name field.
     */
    public function test_sql_injection_in_name(): void
    {
        $maliciousName = "Test'; DROP TABLE api_keys; --";

        $apiKey = ApiKey::factory()->create(['name' => $maliciousName]);

        // Verify the table still exists and name was stored safely
        $this->assertTrue(Schema::hasTable('api_keys'));
        $this->assertEquals($maliciousName, $apiKey->fresh()->name);
    }

    /**
     * Test XSS attempt in name field.
     */
    public function test_xss_in_name(): void
    {
        $maliciousName = '<script>alert("xss")</script>';

        $apiKey = ApiKey::factory()->create(['name' => $maliciousName]);

        // Name stored as-is (sanitization should happen on output)
        $this->assertEquals($maliciousName, $apiKey->name);
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
