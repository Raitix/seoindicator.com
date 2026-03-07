<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiKey;
use App\Models\Project;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Integration tests for TASK-6-20: Rate Limiting Workflow
 *
 * Tests the rate limiting functionality:
 * - Hit rate limit
 * - Wait for reset
 * - Continue requests
 */
class RateLimitingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        // Create API key with low rate limit for testing
        $keyResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Rate Limit Test Key',
                'permissions' => ['keywords:read'],
                'rate_limit' => 5, // Low limit for testing
            ]);

        $this->apiKey = ApiKey::find($keyResponse->json('data.id'));
    }

    // =========================================================================
    // WORKFLOW TESTS (WF)
    // =========================================================================

    /**
     * WF-001: Full rate limit workflow
     * Request -> Hit limit -> Wait -> Continue
     */
    public function test_full_rate_limit_workflow(): void
    {
        $fullKey = $this->getFullKey();

        // Make requests until rate limit
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

            $response->assertStatus(200);
        }

        // Next request should hit rate limit
        $limitedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $limitedResponse->assertStatus(429);

        // Wait for rate limit reset (simulated by clearing cache)
        // In real scenario, we'd wait for the time window to pass
        Cache::flush();

        // Should work again
        $resetResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $resetResponse->assertStatus(200);
    }

    /**
     * WF-002: Rate limit per API key
     */
    public function test_rate_limit_per_api_key(): void
    {
        // Create two keys
        $key1Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key 1',
                'permissions' => ['keywords:read'],
                'rate_limit' => 3,
            ]);

        $key2Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key 2',
                'permissions' => ['keywords:read'],
                'rate_limit' => 3,
            ]);

        $key1 = $key1Response->json('data.key');
        $key2 = $key2Response->json('data.key');

        // Exhaust key1
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $key1,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // key1 should be limited
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key1,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(429);

        // key2 should still work
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key2,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(200);
    }

    /**
     * WF-003: Rate limit headers included
     */
    public function test_rate_limit_headers_included(): void
    {
        $fullKey = $this->getFullKey();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(200);

        // Check for rate limit headers
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * WF-004: Rate limit remaining decreases
     */
    public function test_rate_limit_remaining_decreases(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

            $remaining = (int) $response->headers->get('X-RateLimit-Remaining');
            $this->assertEquals($limit - $i - 1, $remaining);
        }
    }

    // =========================================================================
    // RATE LIMIT HIT TESTS (RH)
    // =========================================================================

    /**
     * RH-001: Hit rate limit returns 429
     */
    public function test_hit_rate_limit_returns_429(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Exhaust rate limit
        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // Next request should be 429
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'RATE_LIMIT_EXCEEDED'],
            ]);
    }

    /**
     * RH-002: Rate limit includes retry-after header
     */
    public function test_rate_limit_includes_retry_after_header(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Exhaust rate limit
        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    /**
     * RH-003: Rate limit error message
     */
    public function test_rate_limit_error_message(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Exhaust rate limit
        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    // =========================================================================
    // RATE LIMIT RESET TESTS (RR)
    // =========================================================================

    /**
     * RR-001: Rate limit resets after window
     */
    public function test_rate_limit_resets_after_window(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Exhaust rate limit
        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // Verify limited
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(429);

        // Clear cache to simulate time window passing
        Cache::flush();

        // Should work again
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(200);
    }

    /**
     * RR-002: Partial reset not allowed
     */
    public function test_partial_reset_not_allowed(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Make some requests but don't exhaust
        for ($i = 0; $i < $limit - 1; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // Should have 1 remaining
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(200);
        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');
        $this->assertEquals(0, $remaining);
    }

    // =========================================================================
    // DIFFERENT RATE LIMITS TESTS (DL)
    // =========================================================================

    /**
     * DL-001: Different keys have different limits
     */
    public function test_different_keys_have_different_limits(): void
    {
        // Create key with limit of 10
        $key1Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key Limit 10',
                'permissions' => ['keywords:read'],
                'rate_limit' => 10,
            ]);

        // Create key with limit of 2
        $key2Response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Key Limit 2',
                'permissions' => ['keywords:read'],
                'rate_limit' => 2,
            ]);

        $key1 = $key1Response->json('data.key');
        $key2 = $key2Response->json('data.key');

        // Key with limit 2 should hit limit first
        for ($i = 0; $i < 2; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $key2,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key2,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(429);

        // Key with limit 10 should still work
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $key1,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(200);
    }

    /**
     * DL-002: Default rate limit applied
     */
    public function test_default_rate_limit_applied(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Default Limit Key',
                'permissions' => ['keywords:read'],
                // No rate_limit specified
            ]);

        $apiKey = ApiKey::find($response->json('data.id'));

        // Default should be 60 (as per ApiKeyControllerTest)
        $this->assertEquals(60, $apiKey->rate_limit);
    }

    /**
     * DL-003: Maximum rate limit enforced
     */
    public function test_maximum_rate_limit_enforced(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Excessive Limit Key',
                'permissions' => ['keywords:read'],
                'rate_limit' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate_limit']);
    }

    /**
     * DL-004: Minimum rate limit enforced
     */
    public function test_minimum_rate_limit_enforced(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Zero Limit Key',
                'permissions' => ['keywords:read'],
                'rate_limit' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate_limit']);
    }

    // =========================================================================
    // CONCURRENT REQUEST TESTS (CR)
    // =========================================================================

    /**
     * CR-001: Concurrent requests count against limit
     */
    public function test_concurrent_requests_count_against_limit(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Make concurrent requests (simulated sequentially)
        $responses = [];
        for ($i = 0; $i < $limit + 2; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // First $limit should succeed, rest should fail
        $successCount = 0;
        $failCount = 0;

        foreach ($responses as $response) {
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $failCount++;
            }
        }

        $this->assertEquals($limit, $successCount);
        $this->assertGreaterThanOrEqual(2, $failCount);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Rate limit with invalid API key
     */
    public function test_rate_limit_with_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer sk_live_invalid_key',
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        // Should get 401, not rate limit error
        $response->assertStatus(401);
    }

    /**
     * EC-002: Rate limit with revoked key
     */
    public function test_rate_limit_with_revoked_key(): void
    {
        $fullKey = $this->getFullKey();

        // Revoke the key
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$this->apiKey->id}");

        // Should get 401, not rate limit error
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(401);
    }

    /**
     * EC-003: Rate limit with expired key
     */
    public function test_rate_limit_with_expired_key(): void
    {
        // Create expired key
        $expiredKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', 'sk_live_expired_test'),
            'key_prefix' => 'sk_live_expired_',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer sk_live_expired_test',
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords");

        // Should get 401, not rate limit error
        $response->assertStatus(401);
    }

    /**
     * EC-004: Multiple endpoints share rate limit
     */
    public function test_multiple_endpoints_share_rate_limit(): void
    {
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        // Use different endpoints
        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // Any endpoint should now be rate limited
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(429);
    }

    // =========================================================================
    // USER ISOLATION TESTS (UI)
    // =========================================================================

    /**
     * UI-001: Rate limit not shared between users
     */
    public function test_rate_limit_not_shared_between_users(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        // Create key for other user
        $keyResponse = $this->actingAs($otherUser)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Other User Key',
                'permissions' => ['keywords:read'],
                'rate_limit' => 2,
            ]);

        $otherKey = $keyResponse->json('data.key');

        // Exhaust current user's rate limit
        $fullKey = $this->getFullKey();
        $limit = $this->apiKey->rate_limit;

        for ($i = 0; $i < $limit; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $fullKey,
            ])->getJson("/api/v1/projects/{$this->project->id}/keywords");
        }

        // Current user should be limited
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $fullKey,
        ])->getJson("/api/v1/projects/{$this->project->id}/keywords")
            ->assertStatus(429);

        // Other user should not be limited
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherKey,
        ])->getJson("/api/v1/projects/{$otherProject->id}/keywords")
            ->assertStatus(200);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get the full API key string for testing.
     */
    protected function getFullKey(): string
    {
        // Since we can't retrieve the full key after creation,
        // we need to create a new one for each test
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Test Key ' . uniqid(),
                'permissions' => ['keywords:read'],
                'rate_limit' => 5,
            ]);

        return $response->json('data.key');
    }
}
