<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Feature tests for Sites REST API Controller.
 * 
 * TASK-6-10: Sites API Endpoints
 * 
 * Endpoint:
 * - GET /api/v1/sites - List sites for authenticated user
 * 
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 * Permission: sites:read
 */
class SitesApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherUserSite;
    protected ApiKey $apiKey;
    protected ApiKey $noSitesReadApiKey;
    protected ApiKey $otherUserApiKey;
    protected string $keyValue;
    protected string $noSitesReadKeyValue;
    protected string $otherUserKeyValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherUserSite = Site::factory()->create(['user_id' => $this->otherUser->id]);

        // Create API key with sites:read permission
        $this->keyValue = 'sk_live_' . Str::random(43);
        $this->apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->keyValue),
            'key_prefix' => substr($this->keyValue, 0, 20),
            'permissions' => ['sites:read', 'keywords:read'],
            'rate_limit' => 60,
        ]);

        // Create API key without sites:read permission
        $this->noSitesReadKeyValue = 'sk_live_' . Str::random(43);
        $this->noSitesReadApiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->noSitesReadKeyValue),
            'key_prefix' => substr($this->noSitesReadKeyValue, 0, 20),
            'permissions' => ['keywords:read'],
            'rate_limit' => 60,
        ]);

        // Create other user's API key
        $this->otherUserKeyValue = 'sk_live_' . Str::random(43);
        $this->otherUserApiKey = ApiKey::factory()->create([
            'user_id' => $this->otherUser->id,
            'key_hash' => hash('sha256', $this->otherUserKeyValue),
            'key_prefix' => substr($this->otherUserKeyValue, 0, 20),
            'permissions' => ['sites:read', 'keywords:read'],
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

    // ============================================================
    // SUCCESS CASES (S-*)
    // ============================================================

    /**
     * S-01: List all sites for user.
     */
    public function test_list_all_sites_for_user(): void
    {
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'url',
                        'name',
                        'keyword_count',
                        'created_at',
                    ],
                ],
                'meta' => ['total', 'page', 'per_page', 'total_pages'],
            ])
            ->assertJson([
                'success' => true,
                'meta' => ['total' => 4], // 3 created + 1 from setUp
            ]);
    }

    /**
     * S-02: Empty list (no sites).
     */
    public function test_empty_list(): void
    {
        // Delete the site created in setUp
        Site::where('user_id', $this->user->id)->delete();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'page' => 1,
                    'per_page' => 20,
                    'total_pages' => 0,
                ],
            ]);
    }

    /**
     * S-03: Single site with keywords.
     */
    public function test_single_site_with_keywords(): void
    {
        Keyword::factory()->count(50)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.keyword_count', 50);
    }

    /**
     * S-04: Multiple sites.
     */
    public function test_multiple_sites(): void
    {
        Site::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3); // 2 created + 1 from setUp
    }

    /**
     * S-05: Keyword count = 0.
     */
    public function test_keyword_count_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.keyword_count', 0);
    }

    /**
     * S-06: Pagination default.
     */
    public function test_pagination_default(): void
    {
        Site::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    /**
     * S-07: Pagination custom page.
     */
    public function test_pagination_custom_page(): void
    {
        Site::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 10);
    }

    /**
     * S-08: Pagination meta.
     */
    public function test_pagination_meta(): void
    {
        Site::factory()->count(45)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=2&per_page=20');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 46) // 45 + 1 from setUp
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total_pages', 3);
    }

    /**
     * S-09: Pagination max per_page.
     */
    public function test_pagination_max_per_page(): void
    {
        Site::factory()->count(150)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?per_page=200');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.per_page', 100);
    }

    /**
     * S-10: Site fields included.
     */
    public function test_site_fields_included(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'url',
                        'name',
                        'keyword_count',
                        'created_at',
                    ],
                ],
            ]);
    }

    // ============================================================
    // AUTHENTICATION TESTS (A-*)
    // ============================================================

    /**
     * A-01: Valid API key.
     */
    public function test_valid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);
    }

    /**
     * A-02: Missing Authorization header.
     */
    public function test_missing_authorization(): void
    {
        $response = $this->getJson('/api/v1/sites');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED'],
            ]);
    }

    /**
     * A-03: Invalid API key.
     */
    public function test_invalid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders('sk_live_invalidkey'))
            ->getJson('/api/v1/sites');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * A-04: Revoked API key.
     */
    public function test_revoked_api_key(): void
    {
        $this->apiKey->update(['revoked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * A-05: Expired API key.
     */
    public function test_expired_api_key(): void
    {
        $this->apiKey->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * A-06: Wrong permissions (no sites:read).
     */
    public function test_wrong_permissions(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->noSitesReadKeyValue))
            ->getJson('/api/v1/sites');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ============================================================
    // AUTHORIZATION TESTS (Z-*)
    // ============================================================

    /**
     * Z-01: Own sites - success.
     */
    public function test_own_sites(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);
    }

    /**
     * Z-02: User isolation - each user sees only their sites.
     */
    public function test_user_isolation(): void
    {
        // Create sites for both users
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);
        Site::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        // User 1 should only see their sites
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 4); // 3 + 1 from setUp

        // User 2 should only see their sites
        $response = $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 6); // 5 + 1 from setUp
    }

    /**
     * Z-03: Cross-user access prevented.
     */
    public function test_cross_user_access_prevented(): void
    {
        // Create sites for other user
        Site::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        // User 1 should see 0 of User 2's sites
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1); // Only their own site from setUp
    }

    // ============================================================
    // VALIDATION TESTS (V-*)
    // ============================================================

    /**
     * V-01: Invalid page (negative).
     */
    public function test_invalid_page_negative(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=-1');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * V-02: Invalid page (zero).
     */
    public function test_invalid_page_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * V-03: Invalid page (non-numeric).
     */
    public function test_invalid_page_non_numeric(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * V-04: Invalid per_page (negative).
     */
    public function test_invalid_per_page_negative(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?per_page=-5');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * V-05: Invalid per_page (zero).
     */
    public function test_invalid_per_page_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * V-06: Invalid per_page (non-numeric).
     */
    public function test_invalid_per_page_non_numeric(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?per_page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * V-07: per_page exceeds max - capped to 100.
     */
    public function test_per_page_exceeds_max(): void
    {
        Site::factory()->count(150)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?per_page=500');

        // Should be capped, not rejected
        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 100);
    }

    // ============================================================
    // EDGE CASES (E-*)
    // ============================================================

    /**
     * E-01: Site with many keywords.
     */
    public function test_site_with_many_keywords(): void
    {
        Keyword::factory()->count(500)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.keyword_count', 500);
    }

    /**
     * E-02: Site with paused keywords - count includes all non-deleted.
     */
    public function test_site_with_paused_keywords(): void
    {
        Keyword::factory()->count(30)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Keyword::factory()->count(20)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'paused',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.keyword_count', 50);
    }

    /**
     * E-03: Site with deleted keywords - count excludes deleted.
     */
    public function test_site_with_deleted_keywords(): void
    {
        Keyword::factory()->count(30)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Keyword::factory()->count(20)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'deleted',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.keyword_count', 30);
    }

    /**
     * E-04: Large page number - empty result.
     */
    public function test_large_page_number(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites?page=9999');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * E-05: Created_at ISO format.
     */
    public function test_created_at_iso_format(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);

        $createdAt = $response->json('data.0.created_at');
        // Should be ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $createdAt
        );
    }

    /**
     * E-06: Site without name.
     */
    public function test_site_without_name(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'name' => null,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);

        // Find the site in the response
        $siteData = collect($response->json('data'))->firstWhere('id', $site->id);
        $this->assertNull($siteData['name']);
    }

    /**
     * E-07: Site with long URL.
     */
    public function test_site_with_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2050);
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => $longUrl,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);

        $siteData = collect($response->json('data'))->firstWhere('id', $site->id);
        $this->assertEquals($longUrl, $siteData['url']);
    }

    /**
     * E-08: Site with special characters in name.
     */
    public function test_site_with_special_characters_in_name(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'name' => '日本語 테스트 العربية 🚀 Test <script>',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);

        $siteData = collect($response->json('data'))->firstWhere('id', $site->id);
        $this->assertEquals('日本語 테스트 العربية 🚀 Test <script>', $siteData['name']);
    }

    // ============================================================
    // SECURITY TESTS (SEC-*)
    // ============================================================

    /**
     * SEC-01: SQL injection in page parameter.
     */
    public function test_sql_injection_in_page(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/sites?page=1; DROP TABLE sites;--");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);

        // Verify sites table still exists
        $this->assertDatabaseCount('sites', 2);
    }

    /**
     * SEC-02: SQL injection in per_page parameter.
     */
    public function test_sql_injection_in_per_page(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/sites?per_page=1; SELECT * FROM users;--");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * SEC-03: IDOR prevention.
     */
    public function test_idor_prevention(): void
    {
        // Create sites for both users
        Site::factory()->count(5)->create(['user_id' => $this->user->id]);
        Site::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        // User 1 should not see User 2's sites
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $siteIds = collect($response->json('data'))->pluck('id');
        
        // Verify all returned sites belong to user 1
        foreach ($siteIds as $siteId) {
            $site = Site::find($siteId);
            $this->assertEquals($this->user->id, $site->user_id);
        }
    }

    /**
     * SEC-04: XSS in site name - should be encoded in JSON response.
     */
    public function test_xss_in_site_name(): void
    {
        $xssName = '<script>alert("xss")</script>Test Site';
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'name' => $xssName,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200);

        $siteData = collect($response->json('data'))->firstWhere('id', $site->id);
        // JSON encoding should prevent XSS - the string is preserved as-is
        $this->assertEquals($xssName, $siteData['name']);
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
            ->getJson('/api/v1/sites');

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
                ->getJson('/api/v1/sites');
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
                ->getJson('/api/v1/sites');
        }

        // First key should be rate limited
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites')
            ->assertStatus(429);

        // Second key (other user's key) should still work
        $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson('/api/v1/sites')
            ->assertStatus(200);
    }

    /**
     * RL-04: Rate limit headers present.
     */
    public function test_rate_limit_headers(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

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
     * INT-01: Full sites list flow.
     */
    public function test_full_sites_list_flow(): void
    {
        // Create multiple sites with varying keyword counts
        $site1 = Site::factory()->create(['user_id' => $this->user->id]);
        $site2 = Site::factory()->create(['user_id' => $this->user->id]);
        $site3 = Site::factory()->create(['user_id' => $this->user->id]);

        // Add keywords to sites
        Keyword::factory()->count(10)->create([
            'site_id' => $site1->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Keyword::factory()->count(20)->create([
            'site_id' => $site2->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        // site3 has no keywords

        // Get sites list
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 4); // 3 new + 1 from setUp

        // Verify keyword counts
        $data = collect($response->json('data'));
        
        $site1Data = $data->firstWhere('id', $site1->id);
        $this->assertEquals(10, $site1Data['keyword_count']);

        $site2Data = $data->firstWhere('id', $site2->id);
        $this->assertEquals(20, $site2Data['keyword_count']);

        $site3Data = $data->firstWhere('id', $site3->id);
        $this->assertEquals(0, $site3Data['keyword_count']);
    }

    /**
     * INT-02: User isolation flow.
     */
    public function test_user_isolation_flow(): void
    {
        // Create sites for both users
        $user1Sites = Site::factory()->count(3)->create(['user_id' => $this->user->id]);
        $user2Sites = Site::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        // Add keywords to some sites
        Keyword::factory()->count(10)->create([
            'site_id' => $user1Sites->first()->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Keyword::factory()->count(15)->create([
            'site_id' => $user2Sites->first()->id,
            'user_id' => $this->otherUser->id,
            'status' => 'active',
        ]);

        // User 1 sees only their sites
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 4); // 3 + 1 from setUp

        // Verify all sites belong to user 1
        $siteIds = collect($response->json('data'))->pluck('id');
        foreach ($siteIds as $siteId) {
            $this->assertDatabaseHas('sites', [
                'id' => $siteId,
                'user_id' => $this->user->id,
            ]);
        }

        // User 2 sees only their sites
        $response = $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson('/api/v1/sites');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 6); // 5 + 1 from setUp

        // Verify all sites belong to user 2
        $siteIds = collect($response->json('data'))->pluck('id');
        foreach ($siteIds as $siteId) {
            $this->assertDatabaseHas('sites', [
                'id' => $siteId,
                'user_id' => $this->otherUser->id,
            ]);
        }
    }
}
