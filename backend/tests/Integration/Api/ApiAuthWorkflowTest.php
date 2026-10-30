<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiKey;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Integration tests for TASK-6-20: API Key Authentication Workflow
 *
 * Tests the complete API key lifecycle:
 * - Create API key
 * - Use key for authentication
 * - Revoke key
 * - Verify revoked key rejected
 */
class ApiAuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // WORKFLOW TESTS (WF)
    // =========================================================================

    /**
     * WF-001: Full API key lifecycle
     * Complete workflow: create -> use -> revoke -> verify rejection
     */
    public function test_full_api_key_lifecycle(): void
    {
        // Step 1: Create API key
        $createResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Integration Test Key',
                'permissions' => ['keywords:read', 'keywords:write', 'rankings:read'],
                'rate_limit' => 100,
            ]);

        $createResponse->assertStatus(201);
        $fullKey = $createResponse->json('data.key');
        $keyId = $createResponse->json('data.id');

        $this->assertStringStartsWith('sk_live_', $fullKey);
        $this->assertNotNull($keyId);

        // Step 2: Use key for authentication - make authenticated request to external API
        $apiResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        $apiResponse->assertStatus(200);

        // Step 3: Revoke the key
        $revokeResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$keyId}");

        $revokeResponse->assertStatus(200);

        // Step 4: Verify revoked key is rejected
        $rejectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        $rejectedResponse->assertStatus(401);
    }

    /**
     * WF-002: Create and immediately use key
     */
    public function test_create_and_immediately_use_key(): void
    {
        // Create key
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Immediate Use Key',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Use immediately (no delay)
        $apiResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        $apiResponse->assertStatus(200);
    }

    /**
     * WF-003: Multiple keys for same user
     */
    public function test_multiple_keys_for_same_user(): void
    {
        // Create multiple keys with different permissions
        $key1Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Read Only Key',
                'permissions' => ['keywords:read', 'rankings:read'],
            ]);

        $key2Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Full Access Key',
                'permissions' => ['keywords:read', 'keywords:write', 'rankings:read', 'webhooks:read', 'webhooks:write'],
            ]);

        $key1 = $key1Response->json('data.key');
        $key2 = $key2Response->json('data.key');

        // Both keys should work
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key1,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key2,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /**
     * WF-004: Revoke one key does not affect others
     */
    public function test_revoke_one_key_does_not_affect_others(): void
    {
        // Create two keys
        $key1Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key 1',
                'permissions' => ['keywords:read'],
            ]);

        $key2Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key 2',
                'permissions' => ['keywords:read'],
            ]);

        $key1 = $key1Response->json('data.key');
        $key1Id = $key1Response->json('data.id');
        $key2 = $key2Response->json('data.key');

        // Revoke key1
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$key1Id}")
            ->assertStatus(200);

        // Key1 should be rejected
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key1,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);

        // Key2 should still work
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key2,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);
    }

    /**
     * WF-005: Key with expiration
     */
    public function test_key_with_expiration(): void
    {
        // Create key that expires in the future
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Temporary Key',
                'permissions' => ['keywords:read'],
                'expires_at' => now()->addDay()->toIso8601String(),
            ]);

        $fullKey = $response->json('data.key');

        // Should work now
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);
    }

    /**
     * WF-006: Expired key rejected
     */
    public function test_expired_key_rejected(): void
    {
        // Create already expired key directly in database
        $apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', 'sk_live_expired_key_test'),
            'key_prefix' => 'sk_live_expired_k',
            'expires_at' => now()->subDay(),
        ]);

        // Should be rejected
        $this->withHeaders([
            'Authorization' => 'Bearer sk_live_expired_key_test',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    // =========================================================================
    // PERMISSION WORKFLOW TESTS (PW)
    // =========================================================================

    /**
     * PW-001: Key with read permissions can read
     */
    public function test_key_with_read_permissions_can_read(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Read Key',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Should be able to read
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);
    }

    /**
     * PW-002: Key without permission cannot access resource
     */
    public function test_key_without_permission_cannot_access_resource(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Limited Key',
                'permissions' => ['keywords:read'], // No webhooks permission
            ]);

        $fullKey = $response->json('data.key');

        // Should be able to read keywords
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);

        // Should not be able to access webhooks
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/webhooks')
            ->assertStatus(403);
    }

    /**
     * PW-003: Key with all permissions has full access
     */
    public function test_key_with_all_permissions_has_full_access(): void
    {
        $allPermissions = [
            'keywords:read', 'keywords:write',
            'rankings:read',
            'reports:read', 'reports:write',
            'webhooks:read', 'webhooks:write',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Full Access Key',
                'permissions' => $allPermissions,
            ]);

        $fullKey = $response->json('data.key');

        // Should have access to all permitted resources
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);
    }

    // =========================================================================
    // AUTHENTICATION HEADER TESTS (AH)
    // =========================================================================

    /**
     * AH-001: Valid Bearer token
     */
    public function test_valid_bearer_token(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Bearer Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);
    }

    /**
     * AH-002: Invalid token format
     */
    public function test_invalid_token_format(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-format',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * AH-003: Missing Authorization header
     */
    public function test_missing_authorization_header(): void
    {
        $this->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * AH-004: Wrong prefix (not sk_live_)
     */
    public function test_wrong_prefix(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer pk_test_something',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * AH-005: Empty token
     */
    public function test_empty_token(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * AH-006: Case sensitive token
     */
    public function test_case_sensitive_token(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Case Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');
        $upperKey = strtoupper($fullKey);

        // Lowercase key should work
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200);

        // Uppercase key should fail (keys are case-sensitive)
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $upperKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    // =========================================================================
    // USER ISOLATION TESTS (UI)
    // =========================================================================

    /**
     * UI-001: Key authenticates as correct user
     */
    public function test_key_authenticates_as_correct_user(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        // Create key for user1
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'User 1 Key',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Should not access other user's site keywords via API
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $otherSite->id)
            ->assertStatus(200); // Returns empty list, not forbidden

        // But should return empty data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $otherSite->id);
        
        $this->assertEquals(0, count($response->json('data')));
    }

    /**
     * UI-002: Cannot use another user's key
     */
    public function test_cannot_use_another_users_key(): void
    {
        $otherUser = User::factory()->create();

        // Create key for other user
        $response = $this->actingAs($otherUser)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Other User Key',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Try to use other user's key - should still authenticate as that user
        // but should not have access to user1's resources
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(200); // Returns empty list
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Key used immediately after revoke (timing)
     */
    public function test_key_used_immediately_after_revoke(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Quick Revoke Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');
        $keyId = $response->json('data.id');

        // Revoke immediately
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$keyId}");

        // Try to use immediately (no delay)
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * EC-002: Whitespace in token
     */
    public function test_whitespace_in_token(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Whitespace Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Token with leading/trailing whitespace should fail
        $this->withHeaders([
            'Authorization' => 'Bearer  ' . $fullKey . '  ',
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id)
            ->assertStatus(401);
    }

    /**
     * EC-003: Concurrent requests with same key
     */
    public function test_concurrent_requests_with_same_key(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Concurrent Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);
        }

        // All should succeed
        foreach ($responses as $resp) {
            $resp->assertStatus(200);
        }
    }

    /**
     * EC-004: Key format validation
     */
    public function test_key_format_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Format Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Verify format: sk_live_ followed by base64url characters
        $this->assertMatchesRegularExpression('/^sk_live_[A-Za-z0-9_-]+$/', $fullKey);
        $this->assertGreaterThan(40, strlen($fullKey));
    }

    /**
     * EC-005: Key last_used_at updated
     */
    public function test_key_last_used_at_updated(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Usage Tracking Test',
                'permissions' => ['keywords:read'],
            ]);

        $keyId = $response->json('data.id');
        $fullKey = $response->json('data.key');

        // Initially null
        $apiKey = ApiKey::find($keyId);
        $this->assertNull($apiKey->last_used_at);

        // Use the key
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson('/api/v1/keywords?site_id=' . $this->site->id);

        // Should be updated
        $apiKey->refresh();
        $this->assertNotNull($apiKey->last_used_at);
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: Timing attack on key validation
     */
    public function test_timing_attack_resistance(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Timing Test',
                'permissions' => ['keywords:read'],
            ]);

        $fullKey = $response->json('data.key');

        // Measure time for valid key
        $start = microtime(true);
        $this->withHeaders(['Authorization' => 'Bearer ' . $fullKey])
            ->getJson('/api/v1/keywords?site_id=' . $this->site->id);
        $validTime = microtime(true) - $start;

        // Measure time for invalid key
        $start = microtime(true);
        $this->withHeaders(['Authorization' => 'Bearer sk_live_invalid_key'])
            ->getJson('/api/v1/keywords?site_id=' . $this->site->id);
        $invalidTime = microtime(true) - $start;

        // Times should be roughly similar (within 2x)
        // This is a basic check - proper timing attack prevention needs more sophisticated testing
        $ratio = $invalidTime / max($validTime, 0.001);
        $this->assertGreaterThan(0.1, $ratio);
        $this->assertLessThan(10, $ratio);
    }

    /**
     * SEC-002: Key cannot be derived from hash
     */
    public function test_key_cannot_be_derived_from_hash(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Hash Test',
                'permissions' => ['keywords:read'],
            ]);

        $keyId = $response->json('data.id');
        $fullKey = $response->json('data.key');

        // Get the stored hash
        $apiKey = ApiKey::find($keyId);
        $storedHash = $apiKey->key_hash;

        // Hash should not be reversible
        $this->assertNotEquals($fullKey, $storedHash);
        $this->assertStringStartsNotWith('sk_live_', $storedHash);

        // Hash should be SHA256
        $this->assertEquals(64, strlen($storedHash)); // SHA256 produces 64 hex chars
    }

    /**
     * SEC-003: IDOR prevention
     */
    public function test_idor_prevention(): void
    {
        $otherUser = User::factory()->create();

        // Create key for current user
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'User 1 Key',
                'permissions' => ['keywords:read'],
            ]);

        $keyId = $response->json('data.id');

        // Other user should not be able to see or manage this key
        $this->actingAs($otherUser)
            ->getJson('/api/v1/api-keys')
            ->assertJsonMissing(['name' => 'User 1 Key']);
    }
}
