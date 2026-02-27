<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\ApiKey;
use App\Models\Ranking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Feature tests for Rankings REST API Controller.
 * 
 * TASK-6-9: Rankings API Endpoints
 * 
 * Endpoint:
 * - GET /api/v1/keywords/:id/rankings - Get ranking history for a keyword
 * 
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 * Permission: rankings:read
 */
class RankingsApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherUserSite;
    protected Keyword $keyword;
    protected Keyword $otherUserKeyword;
    protected ApiKey $apiKey;
    protected ApiKey $noRankingsApiKey;
    protected ApiKey $otherUserApiKey;
    protected string $keyValue;
    protected string $noRankingsKeyValue;
    protected string $otherUserKeyValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherUserSite = Site::factory()->create(['user_id' => $this->otherUser->id]);

        $this->keyword = Keyword::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'keyword' => 'seo tools',
            'status' => 'active',
        ]);

        $this->otherUserKeyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
            'keyword' => 'other keyword',
            'status' => 'active',
        ]);

        // Create API key with rankings:read permission
        $this->keyValue = 'sk_live_' . Str::random(43);
        $this->apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->keyValue),
            'key_prefix' => substr($this->keyValue, 0, 20),
            'permissions' => ['rankings:read', 'keywords:read'],
            'rate_limit' => 60,
        ]);

        // Create API key without rankings:read permission
        $this->noRankingsKeyValue = 'sk_live_' . Str::random(43);
        $this->noRankingsApiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->noRankingsKeyValue),
            'key_prefix' => substr($this->noRankingsKeyValue, 0, 20),
            'permissions' => ['keywords:read'],
            'rate_limit' => 60,
        ]);

        // Create other user's API key
        $this->otherUserKeyValue = 'sk_live_' . Str::random(43);
        $this->otherUserApiKey = ApiKey::factory()->create([
            'user_id' => $this->otherUser->id,
            'key_hash' => hash('sha256', $this->otherUserKeyValue),
            'key_prefix' => substr($this->otherUserKeyValue, 0, 20),
            'permissions' => ['rankings:read', 'keywords:read'],
            'rate_limit' => 60,
        ]);
    }

    /**
     * Get headers for API key authentication.
     */
    protected function authHeaders(string $key = null): array
    {
        return [
            'Authorization' => 'Bearer ' . ($key ?? $this->keyValue),
        ];
    }

    /**
     * Create ranking entries for a keyword.
     */
    protected function createRankings(Keyword $keyword, int $count, int $startPosition = 5): void
    {
        for ($i = 0; $i < $count; $i++) {
            Ranking::create([
                'keyword_id' => $keyword->id,
                'position' => $startPosition + $i,
                'checked_at' => now()->subDays($count - $i - 1),
            ]);
        }
    }

    // ============================================================
    // SUCCESS CASES (R-*)
    // ============================================================

    /**
     * R-01: Get ranking history for keyword.
     */
    public function test_get_ranking_history(): void
    {
        $this->createRankings($this->keyword, 3);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword_id',
                    'history' => [
                        '*' => ['position', 'checked_at'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword_id' => $this->keyword->id,
                ],
            ])
            ->assertJsonCount(3, 'data.history');
    }

    /**
     * R-02: Empty history (no rankings).
     */
    public function test_empty_history(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword_id' => $this->keyword->id,
                    'history' => [],
                ],
            ]);
    }

    /**
     * R-03: Filter by from date.
     */
    public function test_filter_by_from_date(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-20T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-25T10:00:00Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-24");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.position', 7);
    }

    /**
     * R-04: Filter by to date.
     */
    public function test_filter_by_to_date(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-20T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-25T10:00:00Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?to=2026-02-24");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.position', 5);
    }

    /**
     * R-05: Filter by date range.
     */
    public function test_filter_by_date_range(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 3,
            'checked_at' => '2026-02-15T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-20T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-25T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 9,
            'checked_at' => '2026-03-01T10:00:00Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-18&to=2026-02-26");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.history');
    }

    /**
     * R-06: Limit results.
     */
    public function test_limit_results(): void
    {
        $this->createRankings($this->keyword, 20);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=5");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.history');
    }

    /**
     * R-07: Ordered by checked_at DESC (most recent first).
     */
    public function test_ordered_by_checked_at_desc(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-20T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-25T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 3,
            'checked_at' => '2026-02-22T10:00:00Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200);

        $history = $response->json('data.history');
        $this->assertEquals(7, $history[0]['position']); // Most recent (2026-02-25)
        $this->assertEquals(3, $history[1]['position']); // 2026-02-22
        $this->assertEquals(5, $history[2]['position']); // Oldest (2026-02-20)
    }

    /**
     * R-08: Large dataset.
     */
    public function test_large_dataset(): void
    {
        $this->createRankings($this->keyword, 1000);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=500");

        $response->assertStatus(200)
            ->assertJsonCount(500, 'data.history');
    }

    /**
     * R-09: Single ranking.
     */
    public function test_single_ranking(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.position', 5);
    }

    /**
     * R-10: Default limit applied.
     */
    public function test_default_limit(): void
    {
        $this->createRankings($this->keyword, 150);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data.history'); // Default limit 100
    }

    // ============================================================
    // AUTHENTICATION TESTS (RA-*)
    // ============================================================

    /**
     * RA-01: Valid API key.
     */
    public function test_valid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200);
    }

    /**
     * RA-02: Missing Authorization header.
     */
    public function test_missing_authorization(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED'],
            ]);
    }

    /**
     * RA-03: Invalid API key.
     */
    public function test_invalid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders('sk_live_invalidkey'))
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * RA-04: Revoked API key.
     */
    public function test_revoked_api_key(): void
    {
        $this->apiKey->update(['revoked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * RA-05: Expired API key.
     */
    public function test_expired_api_key(): void
    {
        $this->apiKey->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * RA-06: Wrong permissions (no rankings:read).
     */
    public function test_wrong_permissions(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->noRankingsKeyValue))
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ============================================================
    // AUTHORIZATION TESTS (RZ-*)
    // ============================================================

    /**
     * RZ-01: Own keyword - success.
     */
    public function test_own_keyword(): void
    {
        $this->createRankings($this->keyword, 3);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200);
    }

    /**
     * RZ-02: Other user's keyword - forbidden.
     */
    public function test_other_users_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/rankings");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * RZ-03: Non-existent keyword.
     */
    public function test_nonexistent_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords/999/rankings');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    // ============================================================
    // VALIDATION TESTS (RV-*)
    // ============================================================

    /**
     * RV-02: Invalid from date.
     */
    public function test_invalid_from_date(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=not-a-date");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    /**
     * RV-03: Invalid to date.
     */
    public function test_invalid_to_date(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?to=not-a-date");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    /**
     * RV-04: from > to (invalid range).
     */
    public function test_from_greater_than_to(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-27&to=2026-02-20");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    /**
     * RV-05: Negative limit.
     */
    public function test_negative_limit(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=-5");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    /**
     * RV-06: Limit too large.
     */
    public function test_limit_too_large(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=10000");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    /**
     * RV-07: Limit zero.
     */
    public function test_limit_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=0");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    /**
     * RV-08: Non-numeric limit.
     */
    public function test_non_numeric_limit(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    // ============================================================
    // EDGE CASES (RE-*)
    // ============================================================

    /**
     * RE-01: Position = 0 (not ranked).
     */
    public function test_position_zero(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 0,
            'checked_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonPath('data.history.0.position', 0);
    }

    /**
     * RE-02: Position = 100+ (deep ranking).
     */
    public function test_deep_ranking(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 150,
            'checked_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonPath('data.history.0.position', 150);
    }

    /**
     * RE-03: Same position multiple times.
     */
    public function test_same_position_multiple_times(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now()->subDays(2),
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.history');
    }

    /**
     * RE-04: Very old ranking.
     */
    public function test_very_old_ranking(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2020-01-01T00:00:00Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');
    }

    /**
     * RE-05: Future checked_at (clock skew).
     */
    public function test_future_checked_at(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now()->addDays(7),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');
    }

    /**
     * RE-06: Limit exceeds total.
     */
    public function test_limit_exceeds_total(): void
    {
        $this->createRankings($this->keyword, 5);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=100");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.history');
    }

    /**
     * RE-07: ISO date with timezone.
     */
    public function test_iso_date_with_timezone(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-25T14:00:00+02:00',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-20T10:00:00Z',
        ]);

        // URL encode the + in the timezone offset
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-24T00:00:00%2B02:00");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');
    }

    /**
     * RE-08: ISO date with milliseconds.
     */
    public function test_iso_date_with_milliseconds(): void
    {
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-25T14:00:00.123Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 7,
            'checked_at' => '2026-02-20T10:00:00.456Z',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-24T00:00:00.000Z");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');
    }

    // ============================================================
    // SECURITY TESTS (SEC-*)
    // ============================================================

    /**
     * SEC-01: SQL injection in keyword_id.
     */
    public function test_sql_injection_in_keyword_id(): void
    {
        // Laravel route constraint should handle this - only numeric IDs allowed
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/1; DROP TABLE ranking_history;--/rankings");

        // Should return 404 (route not found due to constraint)
        $response->assertStatus(404);

        // Verify ranking_history table still exists
        $this->assertDatabaseCount('ranking_history', 0);
    }

    /**
     * SEC-02: SQL injection in from parameter.
     */
    public function test_sql_injection_in_from(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from='; DROP TABLE ranking_history;--");

        $response->assertStatus(422);

        // Verify ranking_history table still exists
        $this->assertDatabaseCount('ranking_history', 0);
    }

    /**
     * SEC-03: IDOR via keyword_id.
     */
    public function test_idor_via_keyword_id(): void
    {
        $this->createRankings($this->otherUserKeyword, 3);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/rankings");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * SEC-04: Path traversal in from parameter.
     */
    public function test_path_traversal_in_from(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=../../../etc/passwd");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    // ============================================================
    // RATE LIMITING TESTS (RL-*)
    // ============================================================

    /**
     * RL-01: Within rate limit.
     */
    public function test_within_rate_limit(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');
    }

    /**
     * RL-02: Rate limit exceeded.
     */
    public function test_rate_limit_exceeded(): void
    {
        // Make more requests than the limit
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeaders($this->authHeaders())
                ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");
        }

        // The 61st request should be rate limited
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'RATE_LIMIT_EXCEEDED'],
            ]);
    }

    /**
     * RL-03: Rate limit per API key.
     */
    public function test_rate_limit_per_api_key(): void
    {
        // Use up rate limit for first key
        for ($i = 0; $i < 61; $i++) {
            $this->withHeaders($this->authHeaders())
                ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");
        }

        // First key should be rate limited
        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings")
            ->assertStatus(429);

        // Second key (other user's key) should still work
        $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/rankings")
            ->assertStatus(200);
    }

    /**
     * RL-04: Rate limit headers present.
     */
    public function test_rate_limit_headers(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining');

        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');
        $this->assertLessThanOrEqual(60, $remaining);
        $this->assertGreaterThanOrEqual(0, $remaining);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * Test full ranking history flow.
     */
    public function test_full_ranking_history_flow(): void
    {
        // Create rankings over time
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 20,
            'checked_at' => '2026-02-01T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 15,
            'checked_at' => '2026-02-08T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => '2026-02-15T10:00:00Z',
        ]);
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => '2026-02-22T10:00:00Z',
        ]);

        // Get all rankings
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data.history');

        // Filter by date range (only 2026-02-15 is within 2026-02-10 to 2026-02-20)
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=2026-02-10&to=2026-02-20");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');

        // Limit results
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?limit=2");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.history');
    }

    /**
     * Test user isolation for rankings.
     */
    public function test_user_isolation(): void
    {
        // Create rankings for both users
        $this->createRankings($this->keyword, 3);
        $this->createRankings($this->otherUserKeyword, 5);

        // User 1 should only see their rankings
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.history');

        // User 1 cannot access User 2's rankings
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/rankings");

        $response->assertStatus(403);

        // User 2 can access their own rankings
        $response = $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.history');
    }
}
