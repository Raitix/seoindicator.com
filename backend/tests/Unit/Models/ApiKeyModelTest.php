<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ApiKey;
use Carbon\Carbon;

/**
 * Model tests for TASK-6-1: API Keys Table
 *
 * Tests verify:
 * - Model behavior and relationships
 * - Status helper methods
 * - Scopes for filtering
 * - Mass assignment protection
 * - Hidden attributes
 */
class ApiKeyModelTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Creation and Mass Assignment Tests
    // ==========================================

    /**
     * Test creating an API key with valid data.
     */
    public function test_create_api_key(): void
    {
        $user = User::factory()->create();
        $keyHash = hash('sha256', 'test_key_123');

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Test API Key',
            'key_hash' => $keyHash,
            'key_prefix' => 'sk_live_test_key',
            'permissions' => ['keywords:read', 'rankings:read'],
            'rate_limit' => 100,
        ]);

        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'name' => 'Test API Key',
            'key_hash' => $keyHash,
        ]);
    }

    /**
     * Test mass assignment fillable fields.
     */
    public function test_mass_assignment_fillable(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'name' => 'Fillable Test',
            'key_hash' => hash('sha256', 'fillable_test'),
            'key_prefix' => 'sk_live_fillable',
            'permissions' => ['keywords:read'],
            'rate_limit' => 200,
            'last_used_at' => now(),
            'expires_at' => now()->addYear(),
            'revoked_at' => null,
        ];

        $apiKey = ApiKey::create($data);

        $this->assertEquals($data['name'], $apiKey->name);
        $this->assertEquals($data['key_hash'], $apiKey->key_hash);
        $this->assertEquals($data['key_prefix'], $apiKey->key_prefix);
        $this->assertEquals($data['permissions'], $apiKey->permissions);
        $this->assertEquals($data['rate_limit'], $apiKey->rate_limit);
    }

    /**
     * Test that key_hash is hidden from JSON serialization.
     */
    public function test_key_hash_hidden_from_json(): void
    {
        $apiKey = ApiKey::factory()->create();
        $json = $apiKey->toArray();

        $this->assertArrayNotHasKey('key_hash', $json);
    }

    /**
     * Test permissions cast to array.
     */
    public function test_permissions_cast_to_array(): void
    {
        $apiKey = ApiKey::factory()->create([
            'permissions' => ['keywords:read', 'keywords:write', 'rankings:read'],
        ]);

        $this->assertIsArray($apiKey->permissions);
        $this->assertCount(3, $apiKey->permissions);
        $this->assertContains('keywords:read', $apiKey->permissions);
    }

    /**
     * Test rate_limit cast to integer.
     */
    public function test_rate_limit_cast_to_integer(): void
    {
        $apiKey = ApiKey::factory()->create(['rate_limit' => 100]);

        $this->assertIsInt($apiKey->rate_limit);
        $this->assertEquals(100, $apiKey->rate_limit);
    }

    /**
     * Test timestamp casts to Carbon instances.
     */
    public function test_timestamp_casts(): void
    {
        $now = now();
        $apiKey = ApiKey::factory()->create([
            'last_used_at' => $now,
            'expires_at' => $now->copy()->addYear(),
            'revoked_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $apiKey->last_used_at);
        $this->assertInstanceOf(Carbon::class, $apiKey->expires_at);
        $this->assertInstanceOf(Carbon::class, $apiKey->revoked_at);
        $this->assertInstanceOf(Carbon::class, $apiKey->created_at);
        $this->assertInstanceOf(Carbon::class, $apiKey->updated_at);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    /**
     * Test user relationship.
     */
    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $apiKey->user);
        $this->assertEquals($user->id, $apiKey->user->id);
    }

    /**
     * Test user hasMany API keys relationship.
     * Note: This requires User model to have apiKeys() relationship defined.
     * The relationship is defined in TASK-6-2, not TASK-6-1.
     */
    // public function test_user_has_many_api_keys(): void
    // {
    //     $user = User::factory()->create();
    //     ApiKey::factory()->count(3)->create(['user_id' => $user->id]);
    //
    //     $this->assertCount(3, $user->apiKeys);
    // }

    // ==========================================
    // Status Helper Method Tests
    // ==========================================

    /**
     * Test isRevoked() returns true when revoked_at is set.
     */
    public function test_is_revoked_true(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertTrue($apiKey->isRevoked());
    }

    /**
     * Test isRevoked() returns false when revoked_at is null.
     */
    public function test_is_revoked_false(): void
    {
        $apiKey = ApiKey::factory()->create(['revoked_at' => null]);

        $this->assertFalse($apiKey->isRevoked());
    }

    /**
     * Test isExpired() returns true when expires_at is in the past.
     */
    public function test_is_expired_true(): void
    {
        $apiKey = ApiKey::factory()->expired()->create();

        $this->assertTrue($apiKey->isExpired());
    }

    /**
     * Test isExpired() returns false when expires_at is in the future.
     */
    public function test_is_expired_false_future(): void
    {
        $apiKey = ApiKey::factory()->create([
            'expires_at' => now()->addYear(),
        ]);

        $this->assertFalse($apiKey->isExpired());
    }

    /**
     * Test isExpired() returns false when expires_at is null.
     */
    public function test_is_expired_false_null(): void
    {
        $apiKey = ApiKey::factory()->create(['expires_at' => null]);

        $this->assertFalse($apiKey->isExpired());
    }

    /**
     * Test isActive() returns true for active key.
     */
    public function test_is_active_true(): void
    {
        $apiKey = ApiKey::factory()->create([
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        $this->assertTrue($apiKey->isActive());
    }

    /**
     * Test isActive() returns false for revoked key.
     */
    public function test_is_active_false_revoked(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertFalse($apiKey->isActive());
    }

    /**
     * Test isActive() returns false for expired key.
     */
    public function test_is_active_false_expired(): void
    {
        $apiKey = ApiKey::factory()->expired()->create();

        $this->assertFalse($apiKey->isActive());
    }

    // ==========================================
    // Action Method Tests
    // ==========================================

    /**
     * Test markAsUsed() updates last_used_at.
     */
    public function test_mark_as_used(): void
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => null]);

        $this->assertNull($apiKey->last_used_at);

        $apiKey->markAsUsed();

        $this->assertNotNull($apiKey->fresh()->last_used_at);
        $this->assertTrue($apiKey->fresh()->last_used_at->isToday());
    }

    /**
     * Test revoke() sets revoked_at.
     */
    public function test_revoke(): void
    {
        $apiKey = ApiKey::factory()->create(['revoked_at' => null]);

        $this->assertNull($apiKey->revoked_at);

        $apiKey->revoke();

        $this->assertNotNull($apiKey->fresh()->revoked_at);
        $this->assertTrue($apiKey->fresh()->isRevoked());
    }

    /**
     * Test revoke() is idempotent (doesn't update if already revoked).
     */
    public function test_revoke_idempotent(): void
    {
        $revokedAt = now()->subDays(5);
        $apiKey = ApiKey::factory()->create(['revoked_at' => $revokedAt]);

        $apiKey->revoke();

        // Should not change the revoked_at timestamp
        $this->assertTrue(
            $apiKey->fresh()->revoked_at->isSameSecond($revokedAt)
        );
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    /**
     * Test scopeActive returns only active keys.
     */
    public function test_scope_active(): void
    {
        $user = User::factory()->create();

        // Create active and inactive keys
        ApiKey::factory()->count(2)->create(['user_id' => $user->id]);
        ApiKey::factory()->revoked()->create(['user_id' => $user->id]);
        ApiKey::factory()->expired()->create(['user_id' => $user->id]);

        $activeKeys = ApiKey::active()->get();

        $this->assertEquals(2, $activeKeys->count());
        foreach ($activeKeys as $key) {
            $this->assertTrue($key->isActive());
        }
    }

    /**
     * Test scopeRevoked returns only revoked keys.
     */
    public function test_scope_revoked(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->create(['user_id' => $user->id]);
        ApiKey::factory()->revoked()->count(2)->create(['user_id' => $user->id]);

        $revokedKeys = ApiKey::revoked()->get();

        $this->assertEquals(2, $revokedKeys->count());
        foreach ($revokedKeys as $key) {
            $this->assertTrue($key->isRevoked());
        }
    }

    /**
     * Test scopeExpired returns only expired keys.
     */
    public function test_scope_expired(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->create(['user_id' => $user->id]);
        ApiKey::factory()->expired()->count(3)->create(['user_id' => $user->id]);

        $expiredKeys = ApiKey::expired()->get();

        $this->assertEquals(3, $expiredKeys->count());
        foreach ($expiredKeys as $key) {
            $this->assertTrue($key->isExpired());
        }
    }

    /**
     * Test scopeByStatus with 'active'.
     */
    public function test_scope_by_status_active(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->count(2)->create(['user_id' => $user->id]);
        ApiKey::factory()->revoked()->create(['user_id' => $user->id]);

        $keys = ApiKey::byStatus('active')->get();

        $this->assertEquals(2, $keys->count());
    }

    /**
     * Test scopeByStatus with 'revoked'.
     */
    public function test_scope_by_status_revoked(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->create(['user_id' => $user->id]);
        ApiKey::factory()->revoked()->count(2)->create(['user_id' => $user->id]);

        $keys = ApiKey::byStatus('revoked')->get();

        $this->assertEquals(2, $keys->count());
    }

    /**
     * Test scopeByStatus with 'expired'.
     */
    public function test_scope_by_status_expired(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->create(['user_id' => $user->id]);
        ApiKey::factory()->expired()->count(3)->create(['user_id' => $user->id]);

        $keys = ApiKey::byStatus('expired')->get();

        $this->assertEquals(3, $keys->count());
    }

    /**
     * Test scopeByStatus with unknown status returns all.
     */
    public function test_scope_by_status_unknown(): void
    {
        $user = User::factory()->create();

        ApiKey::factory()->count(2)->create(['user_id' => $user->id]);
        ApiKey::factory()->revoked()->create(['user_id' => $user->id]);

        $keys = ApiKey::byStatus('unknown')->get();

        $this->assertEquals(3, $keys->count());
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    /**
     * Test empty permissions array.
     */
    public function test_empty_permissions_array(): void
    {
        $apiKey = ApiKey::factory()->create(['permissions' => []]);

        $this->assertEquals([], $apiKey->permissions);
    }

    /**
     * Test null permissions.
     */
    public function test_null_permissions(): void
    {
        $apiKey = ApiKey::factory()->create(['permissions' => null]);

        $this->assertNull($apiKey->permissions);
    }

    /**
     * Test multiple permissions stored correctly.
     */
    public function test_multiple_permissions(): void
    {
        $permissions = ApiKey::VALID_PERMISSIONS;

        $apiKey = ApiKey::factory()->create(['permissions' => $permissions]);

        $this->assertEquals($permissions, $apiKey->permissions);
        $this->assertCount(8, $apiKey->permissions);
    }

    /**
     * Test rate limit boundary minimum.
     */
    public function test_rate_limit_minimum(): void
    {
        $apiKey = ApiKey::factory()->create(['rate_limit' => ApiKey::MIN_RATE_LIMIT]);

        $this->assertEquals(1, $apiKey->rate_limit);
    }

    /**
     * Test rate limit boundary maximum.
     */
    public function test_rate_limit_maximum(): void
    {
        $apiKey = ApiKey::factory()->create(['rate_limit' => ApiKey::MAX_RATE_LIMIT]);

        $this->assertEquals(10000, $apiKey->rate_limit);
    }

    /**
     * Test default rate limit constant.
     */
    public function test_default_rate_limit_constant(): void
    {
        $this->assertEquals(60, ApiKey::DEFAULT_RATE_LIMIT);
    }

    /**
     * Test valid permissions constant.
     */
    public function test_valid_permissions_constant(): void
    {
        $expectedPermissions = [
            'keywords:read',
            'keywords:write',
            'rankings:read',
            'sites:read',
            'reports:read',
            'reports:write',
            'webhooks:read',
            'webhooks:write',
        ];

        $this->assertEquals($expectedPermissions, ApiKey::VALID_PERMISSIONS);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    /**
     * Test cascade delete removes API keys when user is deleted.
     */
    public function test_user_delete_cascades_to_api_keys(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id]);

        $user->delete();

        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    /**
     * Test can create multiple API keys for same user.
     */
    public function test_multiple_keys_for_same_user(): void
    {
        $user = User::factory()->create();

        $keys = ApiKey::factory()->count(5)->forUser($user)->create();

        $this->assertEquals(5, $keys->count());
        // Verify via direct query (not User relationship)
        $this->assertEquals(5, ApiKey::where('user_id', $user->id)->count());
    }

    /**
     * Test factory state active().
     */
    public function test_factory_state_active(): void
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertTrue($apiKey->isActive());
        $this->assertNull($apiKey->revoked_at);
        $this->assertNull($apiKey->expires_at);
    }

    /**
     * Test factory state revoked().
     */
    public function test_factory_state_revoked(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create();

        $this->assertTrue($apiKey->isRevoked());
        $this->assertNotNull($apiKey->revoked_at);
    }

    /**
     * Test factory state expired().
     */
    public function test_factory_state_expired(): void
    {
        $apiKey = ApiKey::factory()->expired()->create();

        $this->assertTrue($apiKey->isExpired());
        $this->assertNotNull($apiKey->expires_at);
    }

    /**
     * Test factory state withPermissions().
     */
    public function test_factory_state_with_permissions(): void
    {
        $customPermissions = ['keywords:read', 'reports:write'];

        $apiKey = ApiKey::factory()->withPermissions($customPermissions)->create();

        $this->assertEquals($customPermissions, $apiKey->permissions);
    }

    /**
     * Test factory state withRateLimit().
     */
    public function test_factory_state_with_rate_limit(): void
    {
        $apiKey = ApiKey::factory()->withRateLimit(500)->create();

        $this->assertEquals(500, $apiKey->rate_limit);
    }

    /**
     * Test factory state forUser().
     */
    public function test_factory_state_for_user(): void
    {
        $user = User::factory()->create();

        $apiKey = ApiKey::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $apiKey->user_id);
    }

    /**
     * Test factory state recentlyUsed().
     */
    public function test_factory_state_recently_used(): void
    {
        $apiKey = ApiKey::factory()->recentlyUsed()->create();

        $this->assertNotNull($apiKey->last_used_at);
        $this->assertTrue($apiKey->last_used_at->isToday());
    }
}
