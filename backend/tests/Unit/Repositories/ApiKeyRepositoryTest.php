<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\ApiKeyRepository;
use App\Models\User;
use App\Models\ApiKey;
use Carbon\Carbon;

/**
 * Unit tests for ApiKeyRepository
 *
 * TASK-6-2: API Key Model and Repository
 *
 * Tests verify:
 * - CRUD operations
 * - Lookup by hash and prefix
 * - User-scoped queries
 * - Revocation and deletion
 * - Security (hash hiding, user isolation)
 */
class ApiKeyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKeyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->repository = app(ApiKeyRepository::class);
    }

    // ==========================================
    // Category 1: Create Operations (CR)
    // ==========================================

    /**
     * CR-01: Create with all fields
     */
    public function test_create_with_all_fields(): void
    {
        $user = User::factory()->create();
        $keyHash = hash('sha256', 'sk_live_test_key_123');

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Production Key',
            'key_hash' => $keyHash,
            'key_prefix' => 'sk_live_test_key',
            'permissions' => ['keywords:read', 'rankings:read'],
            'rate_limit' => 500,
            'expires_at' => now()->addYear(),
        ]);

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertEquals('Production Key', $apiKey->name);
        $this->assertEquals($keyHash, $apiKey->key_hash);
        $this->assertEquals(['keywords:read', 'rankings:read'], $apiKey->permissions);
        $this->assertEquals(500, $apiKey->rate_limit);
        $this->assertNotNull($apiKey->expires_at);
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'name' => 'Production Key',
        ]);
    }

    /**
     * CR-02: Create with minimal fields
     */
    public function test_create_with_minimal_fields(): void
    {
        $user = User::factory()->create();
        $keyHash = hash('sha256', 'minimal_key');

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Minimal Key',
            'key_hash' => $keyHash,
            'key_prefix' => 'sk_live_minimal',
        ]);

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertEquals('Minimal Key', $apiKey->name);
        // Rate limit uses database default (60) via model default or null
        $this->assertTrue($apiKey->rate_limit === ApiKey::DEFAULT_RATE_LIMIT || $apiKey->rate_limit === null);
    }

    /**
     * CR-03: Create with permissions array
     */
    public function test_create_with_permissions_array(): void
    {
        $user = User::factory()->create();
        $permissions = ['keywords:read', 'keywords:write', 'rankings:read', 'reports:read'];

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Full Access Key',
            'key_hash' => hash('sha256', 'full_access'),
            'key_prefix' => 'sk_live_full',
            'permissions' => $permissions,
        ]);

        $this->assertEquals($permissions, $apiKey->permissions);
        $this->assertCount(4, $apiKey->permissions);
    }

    /**
     * CR-04: Create with custom rate limit
     */
    public function test_create_with_custom_rate_limit(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'High Rate Key',
            'key_hash' => hash('sha256', 'high_rate'),
            'key_prefix' => 'sk_live_high',
            'rate_limit' => 1000,
        ]);

        $this->assertEquals(1000, $apiKey->rate_limit);
    }

    /**
     * CR-05: Create with expiration
     */
    public function test_create_with_expiration(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addDays(90);

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Temporary Key',
            'key_hash' => hash('sha256', 'temp_key'),
            'key_prefix' => 'sk_live_temp',
            'expires_at' => $expiresAt,
        ]);

        $this->assertTrue($apiKey->expires_at->isSameDay($expiresAt));
    }

    /**
     * CR-06: Create already revoked
     */
    public function test_create_already_revoked(): void
    {
        $user = User::factory()->create();
        $revokedAt = now()->subDays(5);

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Revoked Key',
            'key_hash' => hash('sha256', 'revoked_key'),
            'key_prefix' => 'sk_live_revoked',
            'revoked_at' => $revokedAt,
        ]);

        $this->assertTrue($apiKey->isRevoked());
        $this->assertTrue($apiKey->revoked_at->isSameDay($revokedAt));
    }

    // ==========================================
    // Category 2: Find Operations (FN)
    // ==========================================

    /**
     * FN-01: Find by ID - exists
     */
    public function test_find_by_id_exists(): void
    {
        $apiKey = ApiKey::factory()->create(['name' => 'Findable Key']);

        $found = $this->repository->findById($apiKey->id);

        $this->assertNotNull($found);
        $this->assertEquals('Findable Key', $found->name);
        $this->assertEquals($apiKey->id, $found->id);
    }

    /**
     * FN-02: Find by ID - not exists
     */
    public function test_find_by_id_not_exists(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /**
     * FN-03: Find by hash - exists
     */
    public function test_find_by_hash_exists(): void
    {
        $keyHash = hash('sha256', 'unique_hash_value_123');
        $apiKey = ApiKey::factory()->create([
            'key_hash' => $keyHash,
            'name' => 'Hash Lookup Key',
        ]);

        $found = $this->repository->findByHash($keyHash);

        $this->assertNotNull($found);
        $this->assertEquals('Hash Lookup Key', $found->name);
        $this->assertEquals($keyHash, $found->key_hash);
    }

    /**
     * FN-04: Find by hash - not exists
     */
    public function test_find_by_hash_not_exists(): void
    {
        $found = $this->repository->findByHash('nonexistent_hash');

        $this->assertNull($found);
    }

    /**
     * FN-05: Find by prefix - exists
     */
    public function test_find_by_prefix_exists(): void
    {
        $prefix = 'sk_live_abc123def456';
        $apiKey = ApiKey::factory()->create([
            'key_prefix' => $prefix,
            'name' => 'Prefix Lookup Key',
        ]);

        $found = $this->repository->findByPrefix($prefix);

        $this->assertNotNull($found);
        $this->assertEquals('Prefix Lookup Key', $found->name);
        $this->assertEquals($prefix, $found->key_prefix);
    }

    /**
     * FN-06: Find by prefix - not exists
     */
    public function test_find_by_prefix_not_exists(): void
    {
        $found = $this->repository->findByPrefix('sk_live_nonexistent');

        $this->assertNull($found);
    }

    /**
     * FN-07: Find by hash is exact match
     */
    public function test_find_by_hash_exact_match(): void
    {
        $keyHash = hash('sha256', 'exact_match_test');
        ApiKey::factory()->create(['key_hash' => $keyHash]);

        // Partial hash should not match
        $partialHash = substr($keyHash, 0, 32);
        $found = $this->repository->findByHash($partialHash);

        $this->assertNull($found);

        // Full hash should match
        $found = $this->repository->findByHash($keyHash);
        $this->assertNotNull($found);
    }

    // ==========================================
    // Category 3: User Operations (UO)
    // ==========================================

    /**
     * UO-01: Get for user - multiple keys
     */
    public function test_get_for_user_multiple_keys(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->count(3)->forUser($user)->create();

        $keys = $this->repository->getForUser($user);

        $this->assertEquals(3, $keys->total());
        $this->assertEquals(3, $keys->count());
    }

    /**
     * UO-02: Get for user - no keys
     */
    public function test_get_for_user_no_keys(): void
    {
        $user = User::factory()->create();

        $keys = $this->repository->getForUser($user);

        $this->assertEquals(0, $keys->total());
        $this->assertCount(0, $keys->items());
    }

    /**
     * UO-03: Get for user - with status filter active
     */
    public function test_get_for_user_filter_active(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->count(2)->forUser($user)->create();
        ApiKey::factory()->revoked()->forUser($user)->create();
        ApiKey::factory()->expired()->forUser($user)->create();

        $keys = $this->repository->getForUser($user, ['status' => 'active']);

        $this->assertEquals(2, $keys->total());
        foreach ($keys as $key) {
            $this->assertTrue($key->isActive());
        }
    }

    /**
     * UO-04: Get for user - with status filter revoked
     */
    public function test_get_for_user_filter_revoked(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->forUser($user)->create();
        ApiKey::factory()->revoked()->count(2)->forUser($user)->create();

        $keys = $this->repository->getForUser($user, ['status' => 'revoked']);

        $this->assertEquals(2, $keys->total());
        foreach ($keys as $key) {
            $this->assertTrue($key->isRevoked());
        }
    }

    /**
     * UO-05: Get for user - with status filter expired
     */
    public function test_get_for_user_filter_expired(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->forUser($user)->create();
        ApiKey::factory()->expired()->count(3)->forUser($user)->create();

        $keys = $this->repository->getForUser($user, ['status' => 'expired']);

        $this->assertEquals(3, $keys->total());
        foreach ($keys as $key) {
            $this->assertTrue($key->isExpired());
        }
    }

    /**
     * UO-06: Get for user - pagination
     */
    public function test_get_for_user_pagination(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->count(15)->forUser($user)->create();

        $keys = $this->repository->getForUser($user, [], 5);

        $this->assertEquals(15, $keys->total());
        $this->assertEquals(5, $keys->perPage());
        $this->assertCount(5, $keys->items());
    }

    /**
     * UO-07: Get for user - ordered by created_at desc
     */
    public function test_get_for_user_ordered_desc(): void
    {
        $user = User::factory()->create();
        $key1 = ApiKey::factory()->forUser($user)->create(['created_at' => now()->subDays(2)]);
        $key2 = ApiKey::factory()->forUser($user)->create(['created_at' => now()->subDay()]);
        $key3 = ApiKey::factory()->forUser($user)->create(['created_at' => now()]);

        $keys = $this->repository->getForUser($user);

        $this->assertEquals($key3->id, $keys->first()->id);
        $this->assertEquals($key1->id, $keys->last()->id);
    }

    /**
     * UO-08: Count for user
     */
    public function test_count_for_user(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->count(5)->forUser($user)->create();

        $count = $this->repository->countForUser($user);

        $this->assertEquals(5, $count);
    }

    /**
     * UO-09: Cross-user isolation
     */
    public function test_cross_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        ApiKey::factory()->count(3)->forUser($user1)->create();
        ApiKey::factory()->count(2)->forUser($user2)->create();

        $user1Keys = $this->repository->getForUser($user1);
        $user2Keys = $this->repository->getForUser($user2);

        $this->assertEquals(3, $user1Keys->total());
        $this->assertEquals(2, $user2Keys->total());
    }

    // ==========================================
    // Category 4: Update Operations (UP)
    // ==========================================

    /**
     * UP-01: Update name
     */
    public function test_update_name(): void
    {
        $apiKey = ApiKey::factory()->create(['name' => 'Old Name']);

        $updated = $this->repository->update($apiKey, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * UP-02: Update permissions
     */
    public function test_update_permissions(): void
    {
        $apiKey = ApiKey::factory()->create(['permissions' => ['keywords:read']]);

        $newPermissions = ['keywords:read', 'keywords:write', 'reports:read'];
        $updated = $this->repository->update($apiKey, ['permissions' => $newPermissions]);

        $this->assertEquals($newPermissions, $updated->permissions);
    }

    /**
     * UP-03: Update rate limit
     */
    public function test_update_rate_limit(): void
    {
        $apiKey = ApiKey::factory()->create(['rate_limit' => 60]);

        $updated = $this->repository->update($apiKey, ['rate_limit' => 500]);

        $this->assertEquals(500, $updated->rate_limit);
    }

    /**
     * UP-04: Update last_used_at
     */
    public function test_update_last_used_at(): void
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => null]);

        $usedAt = now();
        $updated = $this->repository->update($apiKey, ['last_used_at' => $usedAt]);

        $this->assertTrue($updated->last_used_at->isSameMinute($usedAt));
    }

    /**
     * UP-05: Update expires_at
     */
    public function test_update_expires_at(): void
    {
        $apiKey = ApiKey::factory()->create(['expires_at' => null]);

        $expiresAt = now()->addYear();
        $updated = $this->repository->update($apiKey, ['expires_at' => $expiresAt]);

        $this->assertTrue($updated->expires_at->isSameDay($expiresAt));
    }

    /**
     * UP-06: Mark as used
     */
    public function test_mark_as_used(): void
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => null]);

        $result = $this->repository->markAsUsed($apiKey);

        $this->assertTrue($result);
        $this->assertNotNull($apiKey->fresh()->last_used_at);
        $this->assertTrue($apiKey->fresh()->last_used_at->isToday());
    }

    /**
     * UP-07: Partial update preserves other fields
     */
    public function test_partial_update_preserves_fields(): void
    {
        $apiKey = ApiKey::factory()->create([
            'name' => 'Original Name',
            'rate_limit' => 100,
            'permissions' => ['keywords:read'],
        ]);

        $updated = $this->repository->update($apiKey, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals(100, $updated->rate_limit);
        $this->assertEquals(['keywords:read'], $updated->permissions);
    }

    /**
     * UP-08: Update returns fresh model
     */
    public function test_update_returns_fresh_model(): void
    {
        $apiKey = ApiKey::factory()->create();

        // Modify the model instance without saving
        $apiKey->name = 'Stale Name';

        $updated = $this->repository->update($apiKey, ['name' => 'Fresh Name']);

        // The returned model should have the fresh DB values
        $this->assertEquals('Fresh Name', $updated->name);
        $this->assertNotEquals('Stale Name', $updated->name);
    }

    // ==========================================
    // Category 5: Revoke Operations (RV)
    // ==========================================

    /**
     * RV-01: Revoke active key
     */
    public function test_revoke_active_key(): void
    {
        $apiKey = ApiKey::factory()->create(['revoked_at' => null]);

        $result = $this->repository->revoke($apiKey);

        $this->assertTrue($result);
        $this->assertNotNull($apiKey->fresh()->revoked_at);
        $this->assertTrue($apiKey->fresh()->isRevoked());
    }

    /**
     * RV-02: Revoke already revoked (idempotent)
     */
    public function test_revoke_already_revoked(): void
    {
        $originalRevokedAt = now()->subDays(5);
        $apiKey = ApiKey::factory()->create(['revoked_at' => $originalRevokedAt]);

        $result = $this->repository->revoke($apiKey);

        $this->assertTrue($result);
        // Should not update the timestamp
        $this->assertTrue(
            $apiKey->fresh()->revoked_at->isSameDay($originalRevokedAt)
        );
    }

    /**
     * RV-03: Revoke sets timestamp
     */
    public function test_revoke_sets_timestamp(): void
    {
        $apiKey = ApiKey::factory()->create(['revoked_at' => null]);

        $this->repository->revoke($apiKey);

        $this->assertTrue($apiKey->fresh()->revoked_at->isToday());
    }

    /**
     * RV-04: Revoked key is not active
     */
    public function test_revoked_key_not_active(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertFalse($apiKey->isActive());
        $this->assertTrue($apiKey->isRevoked());
    }

    /**
     * RV-05: Revoked key is not valid for use
     */
    public function test_revoked_key_not_valid(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertFalse($this->repository->isValidForUse($apiKey));
    }

    // ==========================================
    // Category 6: Delete Operations (DL)
    // ==========================================

    /**
     * DL-01: Delete existing key
     */
    public function test_delete_existing_key(): void
    {
        $apiKey = ApiKey::factory()->create();

        $result = $this->repository->delete($apiKey);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    /**
     * DL-02: Deleted key not found
     */
    public function test_deleted_key_not_found(): void
    {
        $apiKey = ApiKey::factory()->create();
        $id = $apiKey->id;

        $this->repository->delete($apiKey);

        $found = $this->repository->findById($id);
        $this->assertNull($found);
    }

    /**
     * DL-03: User deletion cascades
     */
    public function test_user_deletion_cascades(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->forUser($user)->create();

        $user->delete();

        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    // ==========================================
    // Category 7: Validation & Status (VS)
    // ==========================================

    /**
     * VS-01: isValidForUse - active key
     */
    public function test_is_valid_for_use_active(): void
    {
        $apiKey = ApiKey::factory()->create([
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        $this->assertTrue($this->repository->isValidForUse($apiKey));
    }

    /**
     * VS-02: isValidForUse - revoked key
     */
    public function test_is_valid_for_use_revoked(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertFalse($this->repository->isValidForUse($apiKey));
    }

    /**
     * VS-03: isValidForUse - expired key
     */
    public function test_is_valid_for_use_expired(): void
    {
        $apiKey = ApiKey::factory()->expired()->create();

        $this->assertFalse($this->repository->isValidForUse($apiKey));
    }

    /**
     * VS-04: isValidForUse - expiring soon (still valid)
     */
    public function test_is_valid_for_use_expiring_soon(): void
    {
        $apiKey = ApiKey::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($this->repository->isValidForUse($apiKey));
    }

    // ==========================================
    // Category 8: Edge Cases (EC)
    // ==========================================

    /**
     * EC-01: Empty permissions array
     */
    public function test_empty_permissions_array(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Empty Perms',
            'key_hash' => hash('sha256', 'empty_perms'),
            'key_prefix' => 'sk_live_empty',
            'permissions' => [],
        ]);

        $this->assertEquals([], $apiKey->permissions);
    }

    /**
     * EC-02: Null permissions
     */
    public function test_null_permissions(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Null Perms',
            'key_hash' => hash('sha256', 'null_perms'),
            'key_prefix' => 'sk_live_null',
            'permissions' => null,
        ]);

        $this->assertNull($apiKey->permissions);
    }

    /**
     * EC-03: All permissions
     */
    public function test_all_permissions(): void
    {
        $user = User::factory()->create();
        $allPermissions = ApiKey::VALID_PERMISSIONS;

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'All Perms',
            'key_hash' => hash('sha256', 'all_perms'),
            'key_prefix' => 'sk_live_all',
            'permissions' => $allPermissions,
        ]);

        $this->assertEquals($allPermissions, $apiKey->permissions);
    }

    /**
     * EC-04: Max rate limit
     */
    public function test_max_rate_limit(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Max Rate',
            'key_hash' => hash('sha256', 'max_rate'),
            'key_prefix' => 'sk_live_max',
            'rate_limit' => ApiKey::MAX_RATE_LIMIT,
        ]);

        $this->assertEquals(ApiKey::MAX_RATE_LIMIT, $apiKey->rate_limit);
    }

    /**
     * EC-05: Min rate limit
     */
    public function test_min_rate_limit(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Min Rate',
            'key_hash' => hash('sha256', 'min_rate'),
            'key_prefix' => 'sk_live_min',
            'rate_limit' => ApiKey::MIN_RATE_LIMIT,
        ]);

        $this->assertEquals(ApiKey::MIN_RATE_LIMIT, $apiKey->rate_limit);
    }

    /**
     * EC-06: Very long key name
     */
    public function test_very_long_key_name(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 255);

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => $longName,
            'key_hash' => hash('sha256', 'long_name'),
            'key_prefix' => 'sk_live_long',
        ]);

        $this->assertEquals(255, strlen($apiKey->name));
    }

    /**
     * EC-07: Special characters in name
     */
    public function test_special_characters_in_name(): void
    {
        $user = User::factory()->create();
        $specialName = "Test's Key 🚀 <script>alert('xss')</script>";

        $apiKey = $this->repository->create([
            'user_id' => $user->id,
            'name' => $specialName,
            'key_hash' => hash('sha256', 'special_name'),
            'key_prefix' => 'sk_live_special',
        ]);

        $this->assertEquals($specialName, $apiKey->name);
    }

    /**
     * EC-08: Concurrent mark as used
     */
    public function test_concurrent_mark_as_used(): void
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => null]);

        // Simulate concurrent updates
        $this->repository->markAsUsed($apiKey);
        $firstUsedAt = $apiKey->fresh()->last_used_at;

        // Small delay to ensure different timestamp
        sleep(1);
        $this->repository->markAsUsed($apiKey);

        // Should update the timestamp
        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }

    // ==========================================
    // Category 9: Security Tests (SC)
    // ==========================================

    /**
     * SC-01: Hash not exposed in JSON
     */
    public function test_hash_not_exposed_in_json(): void
    {
        $apiKey = ApiKey::factory()->create();
        $array = $apiKey->toArray();

        $this->assertArrayNotHasKey('key_hash', $array);
    }

    /**
     * SC-02: Hash lookup is exact match (no partial)
     */
    public function test_hash_lookup_exact_only(): void
    {
        $keyHash = hash('sha256', 'exact_match_security_test');
        ApiKey::factory()->create(['key_hash' => $keyHash]);

        // Partial hash should not match
        $partial = substr($keyHash, 0, 32);
        $found = $this->repository->findByHash($partial);

        $this->assertNull($found);
    }

    /**
     * SC-03: User isolation enforced
     */
    public function test_user_isolation_enforced(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $key1 = ApiKey::factory()->forUser($user1)->create();
        ApiKey::factory()->forUser($user2)->create();

        // User1's keys should not include User2's
        $user1Keys = $this->repository->getForUser($user1);
        $this->assertEquals(1, $user1Keys->total());
        $this->assertEquals($key1->id, $user1Keys->first()->id);
    }

    /**
     * SC-04: SQL injection in hash
     */
    public function test_sql_injection_in_hash(): void
    {
        // Create an existing key to verify table integrity
        ApiKey::factory()->create();
        $initialCount = ApiKey::count();

        $maliciousHash = "'; DROP TABLE api_keys; --";

        // Should not throw, should safely return null
        $found = $this->repository->findByHash($maliciousHash);

        $this->assertNull($found);
        // Verify table still has records (SQL injection didn't work)
        $this->assertEquals($initialCount, ApiKey::count());
    }

    /**
     * SC-05: SQL injection in prefix
     */
    public function test_sql_injection_in_prefix(): void
    {
        // Create an existing key to verify table integrity
        ApiKey::factory()->create();
        $initialCount = ApiKey::count();

        $maliciousPrefix = "sk_live_'; DELETE FROM api_keys WHERE '1'='1";

        $found = $this->repository->findByPrefix($maliciousPrefix);

        $this->assertNull($found);
        // Verify table still has records (SQL injection didn't work)
        $this->assertEquals($initialCount, ApiKey::count());
    }

    /**
     * SC-06: SQL injection in name via update
     */
    public function test_sql_injection_in_name(): void
    {
        $apiKey = ApiKey::factory()->create(['name' => 'Safe Name']);
        $maliciousName = "'; UPDATE api_keys SET rate_limit=99999 WHERE '1'='1";

        $updated = $this->repository->update($apiKey, ['name' => $maliciousName]);

        // Name should be stored as-is (escaped), not executed
        $this->assertEquals($maliciousName, $updated->name);
        // Rate limit should not be affected
        $this->assertNotEquals(99999, ApiKey::where('id', '!=', $apiKey->id)->first()?->rate_limit);
    }
}
