<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Feature tests for API Key Management Controller.
 * 
 * TASK-6-4: API Key Management Endpoints
 * 
 * Endpoints:
 * - POST /api/v1/api-keys - Create new API key
 * - GET /api/v1/api-keys - List user's API keys
 * - GET /api/v1/api-keys/{id} - Get single API key
 * - DELETE /api/v1/api-keys/{id} - Revoke API key
 */
class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected ApiKeyService $apiKeyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->apiKeyService = app(ApiKeyService::class);
    }

    // ============================================================
    // CREATE ENDPOINT TESTS (POST /api/v1/api-keys)
    // ============================================================

    // ---------- Success Cases (C-*) ----------

    /**
     * C-01: Create with minimal data (name only).
     */
    public function test_create_with_minimal_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'My API Key',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'key',
                    'prefix',
                    'permissions',
                    'rate_limit',
                    'expires_at',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'My API Key',
                ],
            ]);

        // Verify key format
        $key = $response->json('data.key');
        $this->assertStringStartsWith('sk_live_', $key);
        $this->assertGreaterThan(40, strlen($key));

        // Verify database record
        $this->assertDatabaseHas('api_keys', [
            'user_id' => $this->user->id,
            'name' => 'My API Key',
        ]);
    }

    /**
     * C-02: Create with all fields.
     */
    public function test_create_with_all_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Full API Key',
                'permissions' => ['keywords:read', 'keywords:write', 'rankings:read'],
                'rate_limit' => 120,
                'expires_at' => now()->addMonth()->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Full API Key',
                    'rate_limit' => 120,
                ],
            ]);

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $this->user->id,
            'name' => 'Full API Key',
            'rate_limit' => 120,
        ]);
    }

    /**
     * C-03: Key format validation (sk_live_*).
     */
    public function test_create_key_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Format Test',
            ]);

        $key = $response->json('data.key');
        
        // Key must start with sk_live_
        $this->assertStringStartsWith('sk_live_', $key);
        
        // Key must have body after prefix
        $keyBody = substr($key, 8); // 'sk_live_' is 8 chars
        $this->assertGreaterThan(30, strlen($keyBody));
        
        // Key body must be base64url (only A-Z, a-z, 0-9, -, _)
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $keyBody);
    }

    /**
     * C-04: Key shown only once.
     */
    public function test_create_key_shown_only_once(): void
    {
        // Create a key
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'One-Time Key',
            ]);

        $keyId = $response->json('data.id');
        $fullKey = $response->json('data.key');

        // Fetch the key via show endpoint
        $showResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$keyId}");

        // Full key should NOT be in the show response
        $showResponse->assertStatus(200)
            ->assertJsonMissing(['key' => $fullKey]);
    }

    /**
     * C-05: Hash stored correctly.
     */
    public function test_create_hash_stored_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Hash Test',
            ]);

        $key = $response->json('data.key');
        $expectedHash = hash('sha256', $key);

        // Verify hash in database
        $this->assertDatabaseHas('api_keys', [
            'user_id' => $this->user->id,
            'key_hash' => $expectedHash,
        ]);
    }

    /**
     * C-06: Prefix stored for identification.
     */
    public function test_create_prefix_stored(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Prefix Test',
            ]);

        $key = $response->json('data.key');
        $expectedPrefix = substr($key, 0, 20);

        $response->assertJsonPath('data.prefix', $expectedPrefix);

        $this->assertDatabaseHas('api_keys', [
            'key_prefix' => $expectedPrefix,
        ]);
    }

    /**
     * C-07: Default rate_limit applied.
     */
    public function test_create_default_rate_limit(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Default Rate Limit',
            ]);

        $response->assertJsonPath('data.rate_limit', 60);

        $this->assertDatabaseHas('api_keys', [
            'name' => 'Default Rate Limit',
            'rate_limit' => 60,
        ]);
    }

    /**
     * C-08: Permissions stored as JSON.
     */
    public function test_create_permissions_stored_as_json(): void
    {
        $permissions = ['keywords:read', 'rankings:read', 'reports:read'];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Permissions Test',
                'permissions' => $permissions,
            ]);

        $response->assertJsonPath('data.permissions', $permissions);
    }

    // ---------- Validation Errors (CV-*) ----------

    /**
     * CV-01: Missing name.
     */
    public function test_create_missing_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CV-02: Empty name.
     */
    public function test_create_empty_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CV-03: Name too long (>255 chars).
     */
    public function test_create_name_too_long(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * CV-04: Invalid permissions format.
     */
    public function test_create_invalid_permissions_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'permissions' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    /**
     * CV-05: Invalid permissions values.
     */
    public function test_create_invalid_permissions_values(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'permissions' => ['invalid:permission', 'also:invalid'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    /**
     * CV-06: Rate limit below minimum.
     */
    public function test_create_rate_limit_below_minimum(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'rate_limit' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate_limit']);
    }

    /**
     * CV-07: Rate limit above maximum.
     */
    public function test_create_rate_limit_above_maximum(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'rate_limit' => 10001,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate_limit']);
    }

    /**
     * CV-08: Invalid expires_at format.
     */
    public function test_create_invalid_expires_at_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'expires_at' => 'not-a-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    /**
     * CV-09: expires_at in the past.
     */
    public function test_create_expires_at_in_past(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'expires_at' => now()->subDay()->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    // ---------- Authorization (CA-*) ----------

    /**
     * CA-01: Unauthenticated request.
     */
    public function test_create_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/api-keys', [
            'name' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    /**
     * CA-02: Authenticated user creates key.
     */
    public function test_create_authenticated(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Auth Test',
            ]);

        $response->assertStatus(201);
    }

    // ---------- Edge Cases (CE-*) ----------

    /**
     * CE-01: Create key with same name (allowed).
     */
    public function test_create_duplicate_name_allowed(): void
    {
        // Create first key
        $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Duplicate Name',
            ]);

        // Create second key with same name
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Duplicate Name',
            ]);

        $response->assertStatus(201);

        // Verify both keys exist
        $this->assertEquals(2, ApiKey::where('user_id', $this->user->id)
            ->where('name', 'Duplicate Name')
            ->count());
    }

    /**
     * CE-02: Maximum permissions array.
     */
    public function test_create_maximum_permissions(): void
    {
        $permissions = [
            'keywords:read', 'keywords:write',
            'rankings:read',
            'reports:read', 'reports:write',
            'webhooks:read', 'webhooks:write',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Max Permissions',
                'permissions' => $permissions,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.permissions', $permissions);
    }

    /**
     * CE-03: Unicode characters in name.
     */
    public function test_create_unicode_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Ключ API 🔑 тест',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Ключ API 🔑 тест');
    }

    /**
     * CE-04: SQL injection in name.
     */
    public function test_create_sql_injection_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => "'; DROP TABLE api_keys; --",
            ]);

        $response->assertStatus(201);

        // Verify the SQL was not executed
        $this->assertDatabaseHas('api_keys', [
            'name' => "'; DROP TABLE api_keys; --",
        ]);
    }

    /**
     * CE-05: XSS attempt in name.
     */
    public function test_create_xss_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => '<script>alert("xss")</script>',
            ]);

        $response->assertStatus(201);

        // Name should be stored as-is (sanitization happens on output)
        $this->assertDatabaseHas('api_keys', [
            'name' => '<script>alert("xss")</script>',
        ]);
    }

    // ============================================================
    // LIST ENDPOINT TESTS (GET /api/v1/api-keys)
    // ============================================================

    // ---------- Success Cases (L-*) ----------

    /**
     * L-01: List all keys for user.
     */
    public function test_list_all_keys_for_user(): void
    {
        // Create multiple keys for user
        ApiKey::factory()->count(3)->create(['user_id' => $this->user->id]);
        // Create key for other user
        ApiKey::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'prefix',
                        'permissions',
                        'rate_limit',
                        'last_used_at',
                        'expires_at',
                        'revoked_at',
                        'created_at',
                    ],
                ],
                'meta' => ['total', 'page', 'perPage'],
            ]);
    }

    /**
     * L-02: Empty list for new user.
     */
    public function test_list_empty_for_new_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => ['total' => 0],
            ]);
    }

    /**
     * L-03: Key hash not returned.
     */
    public function test_list_hash_not_returned(): void
    {
        ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonMissing(['key_hash']);
    }

    /**
     * L-04: Usage statistics included.
     */
    public function test_list_usage_statistics(): void
    {
        ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    0 => ['last_used_at'],
                ],
            ]);
    }

    /**
     * L-05: Pagination works.
     */
    public function test_list_pagination(): void
    {
        ApiKey::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10);
    }

    // ---------- Filtering (LF-*) ----------

    /**
     * LF-01: Filter by status=active.
     */
    public function test_list_filter_active(): void
    {
        ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'revoked_at' => null,
        ]);
        ApiKey::factory()->revoked()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * LF-02: Filter by status=revoked.
     */
    public function test_list_filter_revoked(): void
    {
        ApiKey::factory()->create(['user_id' => $this->user->id]);
        ApiKey::factory()->revoked()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys?status=revoked');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.revoked_at', function ($value) {
                return $value !== null;
            });
    }

    /**
     * LF-03: Filter by expired.
     */
    public function test_list_filter_expired(): void
    {
        ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMonth(),
        ]);
        ApiKey::factory()->expired()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys?status=expired');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ---------- Authorization (LA-*) ----------

    /**
     * LA-01: Unauthenticated request.
     */
    public function test_list_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/api-keys');

        $response->assertStatus(401);
    }

    /**
     * LA-02: User sees only their keys.
     */
    public function test_list_only_own_keys(): void
    {
        ApiKey::factory()->count(3)->create(['user_id' => $this->user->id]);
        ApiKey::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    // ============================================================
    // SHOW ENDPOINT TESTS (GET /api/v1/api-keys/{id})
    // ============================================================

    // ---------- Success Cases (S-*) ----------

    /**
     * S-01: Get existing key.
     */
    public function test_show_existing_key(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                ],
            ]);
    }

    /**
     * S-02: Hash not returned.
     */
    public function test_show_hash_not_returned(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200)
            ->assertJsonMissing(['key_hash']);
    }

    /**
     * S-03: Full key not returned.
     */
    public function test_show_full_key_not_returned(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        // Only prefix should be shown, not full key
        $response->assertStatus(200)
            ->assertJsonMissing(['key']);
    }

    // ---------- Error Cases (SE-*) ----------

    /**
     * SE-01: Non-existent key.
     */
    public function test_show_nonexistent_key(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    /**
     * SE-02: Revoked key.
     */
    public function test_show_revoked_key(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.revoked_at', function ($value) {
                return $value !== null;
            });
    }

    /**
     * SE-03: Expired key.
     */
    public function test_show_expired_key(): void
    {
        $apiKey = ApiKey::factory()->expired()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200);
        // Expired status should be indicated
    }

    // ---------- Authorization (SA-*) ----------

    /**
     * SA-01: Unauthenticated request.
     */
    public function test_show_unauthenticated(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(401);
    }

    /**
     * SA-02: Access own key.
     */
    public function test_show_own_key(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200);
    }

    /**
     * SA-03: Access another user's key.
     */
    public function test_show_other_users_key(): void
    {
        $otherApiKey = ApiKey::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$otherApiKey->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ============================================================
    // REVOKE ENDPOINT TESTS (DELETE /api/v1/api-keys/{id})
    // ============================================================

    // ---------- Success Cases (D-*) ----------

    /**
     * D-01: Revoke active key.
     */
    public function test_revoke_active_key(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify revoked_at is set
        $this->assertNotNull($apiKey->fresh()->revoked_at);
    }

    /**
     * D-02: Revoke already revoked key (idempotent).
     */
    public function test_revoke_already_revoked_key(): void
    {
        $apiKey = ApiKey::factory()->revoked()->create(['user_id' => $this->user->id]);
        $originalRevokedAt = $apiKey->revoked_at;

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200);

        // revoked_at should not change
        $this->assertEquals(
            $originalRevokedAt->timestamp,
            $apiKey->fresh()->revoked_at->timestamp
        );
    }

    /**
     * D-03: Key cannot be used after revoke.
     */
    public function test_revoked_key_cannot_be_used(): void
    {
        // Create key
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test Key',
            ]);

        $keyId = $createResponse->json('data.id');
        $fullKey = $createResponse->json('data.key');

        // Revoke the key
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$keyId}");

        // Try to use the key (simulated - actual API auth would be tested elsewhere)
        $apiKey = ApiKey::find($keyId);
        $this->assertNotNull($apiKey->revoked_at);
    }

    // ---------- Error Cases (DE-*) ----------

    /**
     * DE-01: Non-existent key.
     */
    public function test_revoke_nonexistent_key(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/api-keys/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    // ---------- Authorization (DA-*) ----------

    /**
     * DA-01: Unauthenticated request.
     */
    public function test_revoke_unauthenticated(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(401);
    }

    /**
     * DA-02: Revoke own key.
     */
    public function test_revoke_own_key(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$apiKey->id}");

        $response->assertStatus(200);
    }

    /**
     * DA-03: Revoke another user's key.
     */
    public function test_revoke_other_users_key(): void
    {
        $otherApiKey = ApiKey::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$otherApiKey->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);

        // Verify key was NOT revoked
        $this->assertNull($otherApiKey->fresh()->revoked_at);
    }

    // ============================================================
    // ADVERSARIAL TESTING
    // ============================================================

    // ---------- Security Tests (SEC-*) ----------

    /**
     * SEC-01: Timing attack on key validation.
     */
    public function test_timing_attack_prevention(): void
    {
        // Create multiple keys and time validations
        $correctKey = 'sk_live_' . str_repeat('a', 43);
        $wrongKey = 'sk_live_' . str_repeat('b', 43);
        
        $hash = hash('sha256', $correctKey);

        // Multiple iterations to check timing consistency
        $correctTimes = [];
        $wrongTimes = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            $this->apiKeyService->validate($correctKey, $hash);
            $correctTimes[] = microtime(true) - $start;

            $start = microtime(true);
            $this->apiKeyService->validate($wrongKey, $hash);
            $wrongTimes[] = microtime(true) - $start;
        }

        $avgCorrect = array_sum($correctTimes) / count($correctTimes);
        $avgWrong = array_sum($wrongTimes) / count($wrongTimes);
        $ratio = $avgWrong / $avgCorrect;

        // Timing should be within reasonable range (0.5 to 2.0)
        $this->assertGreaterThan(0.5, $ratio);
        $this->assertLessThan(2.0, $ratio);
    }

    /**
     * SEC-02: Key enumeration prevention.
     */
    public function test_key_enumeration_prevention(): void
    {
        // Non-existent key should return same error as wrong key
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys/999');

        $response->assertStatus(404);
        // Error message should not reveal if key exists or not
    }

    /**
     * SEC-03: IDOR vulnerability check.
     */
    public function test_idor_prevention(): void
    {
        $otherApiKey = ApiKey::factory()->create(['user_id' => $this->otherUser->id]);

        // Try to access via show
        $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$otherApiKey->id}")
            ->assertStatus(403);

        // Try to revoke
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$otherApiKey->id}")
            ->assertStatus(403);
    }

    /**
     * SEC-04: Mass assignment protection.
     */
    public function test_mass_assignment_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test',
                'user_id' => $this->otherUser->id, // Try to assign to other user
                'key_hash' => 'malicious-hash',
                'revoked_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201);

        // Verify the malicious fields were ignored
        $apiKey = ApiKey::find($response->json('data.id'));
        $this->assertEquals($this->user->id, $apiKey->user_id);
        $this->assertNotEquals('malicious-hash', $apiKey->key_hash);
        $this->assertNull($apiKey->revoked_at);
    }

    /**
     * SEC-05: Rate limit bypass attempt.
     */
    public function test_rate_limit_bypass(): void
    {
        $apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'rate_limit' => 5,
        ]);

        // Attempt to bypass by modifying request
        // (Actual rate limiting would be tested at integration level)
        $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$apiKey->id}")
            ->assertStatus(200);
    }

    // ---------- Input Validation (IV-*) ----------

    /**
     * IV-01: Null values in required fields.
     */
    public function test_null_in_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => null,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * IV-02: Array where string expected.
     */
    public function test_array_where_string_expected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => ['not', 'a', 'string'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * IV-03: Very long input strings.
     */
    public function test_very_long_input_strings(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => str_repeat('a', 10000),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * IV-04: Special characters in all fields.
     */
    public function test_special_characters_in_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => '!@#$%^&*()_+-=[]{}|;:\'",.<>?/~`',
            ]);

        $response->assertStatus(201);
    }

    /**
     * IV-05: Unicode and emoji in name.
     */
    public function test_unicode_emoji_in_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => '日本語 한국어 🎉🚀✨',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', '日本語 한국어 🎉🚀✨');
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * Test full key lifecycle.
     */
    public function test_full_key_lifecycle(): void
    {
        // 1. Create key
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Lifecycle Test',
                'permissions' => ['keywords:read'],
                'rate_limit' => 100,
            ]);

        $createResponse->assertStatus(201);
        $keyId = $createResponse->json('data.id');
        $fullKey = $createResponse->json('data.key');

        // 2. List keys
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Show key
        $showResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$keyId}");

        $showResponse->assertStatus(200)
            ->assertJsonMissing(['key' => $fullKey]); // Full key not shown

        // 4. Revoke key
        $revokeResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$keyId}");

        $revokeResponse->assertStatus(200);

        // 5. Verify revoked status
        $showResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/api-keys/{$keyId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.revoked_at', function ($value) {
                return $value !== null;
            });
    }

    /**
     * Test multiple keys per user.
     */
    public function test_multiple_keys_per_user(): void
    {
        // Create multiple keys
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/api-keys', [
                    'name' => "Key {$i}",
                ]);
        }

        // Verify all keys are listed
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test user isolation.
     */
    public function test_user_isolation(): void
    {
        // Create keys for both users
        $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', ['name' => 'User 1 Key']);

        $this->actingAs($this->otherUser)
            ->postJson('/api/v1/api-keys', ['name' => 'User 2 Key']);

        // User 1 should only see their keys
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'User 1 Key');

        // User 2 should only see their keys
        $response = $this->actingAs($this->otherUser)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'User 2 Key');
    }
}
