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
 * Feature tests for Keywords REST API Controller.
 * 
 * TASK-6-8: Keywords API Endpoints
 * 
 * Endpoints:
 * - GET /api/v1/keywords - List keywords
 * - POST /api/v1/keywords - Create keyword
 * - GET /api/v1/keywords/{id} - Get single keyword
 * - PUT /api/v1/keywords/{id} - Update keyword
 * - DELETE /api/v1/keywords/{id} - Delete keyword
 * 
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 */
class KeywordsApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherUserSite;
    protected ApiKey $apiKey;
    protected ApiKey $readOnlyApiKey;
    protected ApiKey $otherUserApiKey;
    protected string $keyValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherUserSite = Site::factory()->create(['user_id' => $this->otherUser->id]);

        // Create API key with full permissions
        $this->keyValue = 'sk_live_' . Str::random(43);
        $this->apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->keyValue),
            'key_prefix' => substr($this->keyValue, 0, 20),
            'permissions' => ['keywords:read', 'keywords:write'],
            'rate_limit' => 60,
        ]);

        // Create read-only API key
        $readOnlyKeyValue = 'sk_live_' . Str::random(43);
        $this->readOnlyApiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $readOnlyKeyValue),
            'key_prefix' => substr($readOnlyKeyValue, 0, 20),
            'permissions' => ['keywords:read'],
            'rate_limit' => 60,
        ]);
        $this->readOnlyKeyValue = $readOnlyKeyValue;

        // Create other user's API key
        $otherKeyValue = 'sk_live_' . Str::random(43);
        $this->otherUserApiKey = ApiKey::factory()->create([
            'user_id' => $this->otherUser->id,
            'key_hash' => hash('sha256', $otherKeyValue),
            'key_prefix' => substr($otherKeyValue, 0, 20),
            'permissions' => ['keywords:read', 'keywords:write'],
            'rate_limit' => 60,
        ]);
        $this->otherUserKeyValue = $otherKeyValue;
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
    // LIST ENDPOINT TESTS (GET /api/v1/keywords)
    // ============================================================

    // ---------- Success Cases (L-*) ----------

    /**
     * L-01: List all keywords for user.
     */
    public function test_list_all_keywords_for_user(): void
    {
        Keyword::factory()->count(5)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'site_id',
                        'target_url',
                        'search_engine',
                        'location',
                        'device',
                        'status',
                    ],
                ],
                'meta' => ['total', 'page', 'per_page', 'total_pages'],
            ])
            ->assertJson([
                'success' => true,
                'meta' => ['total' => 5],
            ]);
    }

    /**
     * L-02: Filter by site_id.
     */
    public function test_list_filter_by_site_id(): void
    {
        $site2 = Site::factory()->create(['user_id' => $this->user->id]);
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Keyword::factory()->count(2)->create(['site_id' => $site2->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords?site_id={$this->site->id}");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * L-03: Filter by status=active.
     */
    public function test_list_filter_by_status_active(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'paused']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * L-04: Filter by status=paused.
     */
    public function test_list_filter_by_status_paused(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'paused']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?status=paused');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * L-05: Filter by status=all.
     */
    public function test_list_filter_by_status_all(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'paused']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?status=all');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 5);
    }

    /**
     * L-06: Pagination default.
     */
    public function test_list_pagination_default(): void
    {
        Keyword::factory()->count(25)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    /**
     * L-07: Pagination custom per_page.
     */
    public function test_list_pagination_custom_per_page(): void
    {
        Keyword::factory()->count(25)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    /**
     * L-08: Pagination max per_page.
     */
    public function test_list_pagination_max_per_page(): void
    {
        Keyword::factory()->count(150)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?per_page=200');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.per_page', 100);
    }

    /**
     * L-09: Pagination meta.
     */
    public function test_list_pagination_meta(): void
    {
        Keyword::factory()->count(45)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords?page=2&per_page=20');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 45)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total_pages', 3);
    }

    /**
     * L-10: Empty list.
     */
    public function test_list_empty(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * L-11: Include ranking data.
     */
    public function test_list_include_ranking_data(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.current_position', 5);
    }

    /**
     * L-12: Position change calculation.
     */
    public function test_list_position_change_calculation(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 7, 'checked_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.current_position', 5)
            ->assertJsonPath('data.0.previous_position', 7)
            ->assertJsonPath('data.0.position_change', -2); // Improved by 2
    }

    // ---------- Authentication (LA-*) ----------

    /**
     * LA-01: Valid API key.
     */
    public function test_list_valid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200);
    }

    /**
     * LA-02: Missing Authorization header.
     */
    public function test_list_missing_auth(): void
    {
        $response = $this->getJson('/api/v1/keywords');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED'],
            ]);
    }

    /**
     * LA-03: Invalid API key.
     */
    public function test_list_invalid_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders('sk_live_invalidkey'))
            ->getJson('/api/v1/keywords');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * LA-04: Revoked API key.
     */
    public function test_list_revoked_api_key(): void
    {
        $this->apiKey->update(['revoked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * LA-05: Expired API key.
     */
    public function test_list_expired_api_key(): void
    {
        $this->apiKey->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'INVALID_API_KEY'],
            ]);
    }

    /**
     * LA-06: Wrong permissions (no keywords:read).
     */
    public function test_list_wrong_permissions(): void
    {
        $this->apiKey->update(['permissions' => ['rankings:read']]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ---------- Authorization (LZ-*) ----------

    /**
     * LZ-01: User can only see their keywords.
     */
    public function test_list_only_own_keywords(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Keyword::factory()->count(5)->create(['site_id' => $this->otherUserSite->id, 'user_id' => $this->otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * LZ-03: site_id filter respects ownership.
     */
    public function test_list_site_filter_respects_ownership(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Keyword::factory()->count(5)->create(['site_id' => $this->otherUserSite->id, 'user_id' => $this->otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords?site_id={$this->otherUserSite->id}");

        // Should return empty because user doesn't own the site
        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    }

    // ============================================================
    // CREATE ENDPOINT TESTS (POST /api/v1/keywords)
    // ============================================================

    // ---------- Success Cases (C-*) ----------

    /**
     * C-01: Create with minimal data.
     */
    public function test_create_minimal_data(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'seo tools',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword' => 'seo tools',
                    'site_id' => $this->site->id,
                ],
            ]);

        $this->assertDatabaseHas('keywords', [
            'keyword' => 'seo tools',
            'site_id' => $this->site->id,
        ]);
    }

    /**
     * C-02: Create with all fields.
     */
    public function test_create_all_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'seo tools',
                'target_url' => 'https://example.com/seo-tools',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword' => 'seo tools',
                    'target_url' => 'https://example.com/seo-tools',
                    'search_engine' => 'google',
                    'location' => 'us',
                    'device' => 'desktop',
                ],
            ]);
    }

    /**
     * C-03: Default values applied.
     */
    public function test_create_default_values(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'test keyword',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.search_engine', 'google')
            ->assertJsonPath('data.location', 'us')
            ->assertJsonPath('data.device', 'desktop');
    }

    /**
     * C-04: Status defaults to active.
     */
    public function test_create_status_defaults_to_active(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'test keyword',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    /**
     * C-06: Keyword sanitized.
     */
    public function test_create_keyword_sanitized(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => '<script>alert("xss")</script>seo tools',
            ]);

        $response->assertStatus(201);

        // strip_tags should remove the script tag
        $this->assertDatabaseHas('keywords', [
            'keyword' => 'seo tools',
        ]);
    }

    // ---------- Validation Errors (CV-*) ----------

    /**
     * CV-01: Missing site_id.
     */
    public function test_create_missing_site_id(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'keyword' => 'test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * CV-02: Missing keyword.
     */
    public function test_create_missing_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }

    /**
     * CV-03: Empty keyword.
     */
    public function test_create_empty_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }

    /**
     * CV-04: Keyword too long.
     */
    public function test_create_keyword_too_long(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }

    /**
     * CV-05: Invalid site_id (non-existent).
     */
    public function test_create_invalid_site_id(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => 99999,
                'keyword' => 'test',
            ]);

        $response->assertStatus(422);
    }

    /**
     * CV-06: Invalid search_engine.
     */
    public function test_create_invalid_search_engine(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'test',
                'search_engine' => 'bing',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['search_engine']);
    }

    /**
     * CV-10: Duplicate keyword.
     */
    public function test_create_duplicate_keyword(): void
    {
        Keyword::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'keyword' => 'seo tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'seo tools',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'KEYWORD_EXISTS'],
            ]);
    }

    // ---------- Business Rules (CB-*) ----------

    /**
     * CB-01: Keyword limit exceeded.
     */
    public function test_create_keyword_limit_exceeded(): void
    {
        // Create 500 keywords (max limit)
        Keyword::factory()->count(500)->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'new keyword',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'KEYWORD_LIMIT_EXCEEDED'],
            ]);
    }

    /**
     * CB-02: Site ownership check.
     */
    public function test_create_site_ownership_check(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->otherUserSite->id,
                'keyword' => 'test keyword',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ---------- Authentication (CA-*) ----------

    /**
     * CA-02: API key with only keywords:read.
     */
    public function test_create_read_only_api_key(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->readOnlyKeyValue))
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'test',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * CA-03: Missing Authorization.
     */
    public function test_create_missing_auth(): void
    {
        $response = $this->postJson('/api/v1/keywords', [
            'site_id' => $this->site->id,
            'keyword' => 'test',
        ]);

        $response->assertStatus(401);
    }

    // ============================================================
    // SHOW ENDPOINT TESTS (GET /api/v1/keywords/{id})
    // ============================================================

    // ---------- Success Cases (S-*) ----------

    /**
     * S-01: Get existing keyword.
     */
    public function test_show_existing_keyword(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                ],
            ]);
    }

    /**
     * S-02: Include current position.
     */
    public function test_show_include_current_position(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.current_position', 5);
    }

    /**
     * S-03: Include previous position.
     */
    public function test_show_include_previous_position(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 7, 'checked_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.previous_position', 7);
    }

    /**
     * S-04: Include position change.
     */
    public function test_show_include_position_change(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 3, 'checked_at' => now()]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 10, 'checked_at' => now()->subDay()]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.position_change', -7); // Improved by 7
    }

    // ---------- Error Cases (SE-*) ----------

    /**
     * SE-01: Non-existent keyword.
     */
    public function test_show_nonexistent_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    /**
     * SE-02: Deleted keyword.
     */
    public function test_show_deleted_keyword(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'deleted',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(404);
    }

    // ---------- Authorization (SZ-*) ----------

    /**
     * SZ-02: Another user's keyword.
     */
    public function test_show_other_users_keyword(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    // ============================================================
    // UPDATE ENDPOINT TESTS (PUT /api/v1/keywords/{id})
    // ============================================================

    // ---------- Success Cases (U-*) ----------

    /**
     * U-01: Update target_url.
     */
    public function test_update_target_url(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'target_url' => 'https://example.com/new-url',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.target_url', 'https://example.com/new-url');
    }

    /**
     * U-02: Update status to paused.
     */
    public function test_update_status_to_paused(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'active']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'status' => 'paused',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');
    }

    /**
     * U-03: Update status to active.
     */
    public function test_update_status_to_active(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id, 'status' => 'paused']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'status' => 'active',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    /**
     * U-04: Update multiple fields.
     */
    public function test_update_multiple_fields(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'target_url' => 'https://example.com/new-url',
                'status' => 'paused',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.target_url', 'https://example.com/new-url')
            ->assertJsonPath('data.status', 'paused');
    }

    // ---------- Validation Errors (UV-*) ----------

    /**
     * UV-01: Invalid target_url.
     */
    public function test_update_invalid_target_url(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'target_url' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_url']);
    }

    /**
     * UV-02: Invalid status.
     */
    public function test_update_invalid_status(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'status' => 'invalid-status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * UV-03: No fields provided.
     */
    public function test_update_no_fields(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", []);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    // ---------- Error Cases (UE-*) ----------

    /**
     * UE-01: Non-existent keyword.
     */
    public function test_update_nonexistent_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/v1/keywords/999', ['status' => 'paused']);

        $response->assertStatus(404);
    }

    // ---------- Authorization (UZ-*) ----------

    /**
     * UZ-02: Another user's keyword.
     */
    public function test_update_other_users_keyword(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keyword->id}", ['status' => 'paused']);

        $response->assertStatus(403);
    }

    /**
     * UA-02: Read-only API key.
     */
    public function test_update_read_only_api_key(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders($this->readOnlyKeyValue))
            ->putJson("/api/v1/keywords/{$keyword->id}", ['status' => 'paused']);

        $response->assertStatus(403);
    }

    // ============================================================
    // DELETE ENDPOINT TESTS (DELETE /api/v1/keywords/{id})
    // ============================================================

    // ---------- Success Cases (D-*) ----------

    /**
     * D-01: Delete keyword.
     */
    public function test_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(204);

        // Verify soft delete (status = deleted)
        $this->assertEquals('deleted', $keyword->fresh()->status);
    }

    /**
     * D-03: keepHistory=true preserves rankings.
     */
    public function test_delete_keep_history(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$keyword->id}?keep_history=true");

        $response->assertStatus(204);

        // Rankings should still exist
        $this->assertDatabaseHas('rankings', ['keyword_id' => $keyword->id]);
    }

    /**
     * D-04: keepHistory=false deletes rankings.
     */
    public function test_delete_remove_history(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
        Ranking::create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$keyword->id}?keep_history=false");

        $response->assertStatus(204);

        // Rankings should be deleted
        $this->assertDatabaseMissing('rankings', ['keyword_id' => $keyword->id]);
    }

    // ---------- Error Cases (DE-*) ----------

    /**
     * DE-01: Non-existent keyword.
     */
    public function test_delete_nonexistent_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/v1/keywords/999');

        $response->assertStatus(404);
    }

    // ---------- Authorization (DZ-*) ----------

    /**
     * DZ-02: Another user's keyword.
     */
    public function test_delete_other_users_keyword(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(403);
    }

    /**
     * DA-02: Read-only API key.
     */
    public function test_delete_read_only_api_key(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders($this->readOnlyKeyValue))
            ->deleteJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(403);
    }

    // ============================================================
    // ADVERSARIAL TESTING
    // ============================================================

    // ---------- Security Tests (SEC-*) ----------

    /**
     * SEC-01: SQL injection in keyword.
     */
    public function test_sql_injection_in_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => "'; DROP TABLE keywords; --",
            ]);

        $response->assertStatus(201);

        // Verify SQL was not executed
        $this->assertDatabaseHas('keywords', [
            'keyword' => "'; DROP TABLE keywords; --",
        ]);
    }

    /**
     * SEC-03: IDOR via keyword ID.
     */
    public function test_idor_via_keyword_id(): void
    {
        $otherKeyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
        ]);

        // Try to access via show
        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$otherKeyword->id}")
            ->assertStatus(403);

        // Try to update
        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$otherKeyword->id}", ['status' => 'paused'])
            ->assertStatus(403);

        // Try to delete
        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$otherKeyword->id}")
            ->assertStatus(403);
    }

    /**
     * SEC-04: Mass assignment protection.
     */
    public function test_mass_assignment_protection(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'test',
                'user_id' => $this->otherUser->id, // Try to assign to other user
                'status' => 'deleted', // Try to set status directly
            ]);

        $response->assertStatus(201);

        // Verify user_id and status were not changed
        $keyword = Keyword::where('keyword', 'test')->first();
        $this->assertEquals($this->user->id, $keyword->user_id);
        $this->assertEquals('active', $keyword->status); // Default status
    }

    // ---------- Input Validation (IV-*) ----------

    /**
     * IV-04: Special characters in keyword.
     */
    public function test_special_characters_in_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => '!@#$%^&*()_+-=[]{}|;:\'",.<>?/~`',
            ]);

        $response->assertStatus(201);
    }

    /**
     * IV-05: Unicode in keyword.
     */
    public function test_unicode_in_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => '日本語 한국어 العربية',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', '日本語 한국어 العربية');
    }

    /**
     * IV-06: Emoji in keyword.
     */
    public function test_emoji_in_keyword(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => '🎉🚀✨ SEO tools 🎯',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', '🎉🚀✨ SEO tools 🎯');
    }

    // ============================================================
    // RATE LIMITING TESTS
    // ============================================================

    /**
     * LR-01: Within rate limit.
     */
    public function test_rate_limit_within_limit(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');
    }

    /**
     * LR-02: Rate limit exceeded.
     */
    public function test_rate_limit_exceeded(): void
    {
        // Make more requests than the limit
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeaders($this->authHeaders())
                ->getJson('/api/v1/keywords');
        }

        // The 61st request should be rate limited
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'RATE_LIMIT_EXCEEDED'],
            ]);
    }

    /**
     * LR-04: Rate limit per API key.
     */
    public function test_rate_limit_per_api_key(): void
    {
        // Use up rate limit for first key
        for ($i = 0; $i < 61; $i++) {
            $this->withHeaders($this->authHeaders())
                ->getJson('/api/v1/keywords');
        }

        // First key should be rate limited
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords')
            ->assertStatus(429);

        // Second key should still work
        $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson('/api/v1/keywords')
            ->assertStatus(200);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * Test full keyword lifecycle.
     */
    public function test_full_keyword_lifecycle(): void
    {
        // 1. Create keyword
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'lifecycle test',
                'target_url' => 'https://example.com/lifecycle',
            ]);

        $createResponse->assertStatus(201);
        $keywordId = $createResponse->json('data.id');

        // 2. List keywords
        $listResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $listResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        // 3. Show keyword
        $showResponse = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keywordId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.keyword', 'lifecycle test');

        // 4. Update keyword
        $updateResponse = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/keywords/{$keywordId}", [
                'status' => 'paused',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');

        // 5. Delete keyword
        $deleteResponse = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/keywords/{$keywordId}");

        $deleteResponse->assertStatus(204);

        // 6. Verify keyword is deleted
        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/keywords/{$keywordId}")
            ->assertStatus(404);
    }

    /**
     * Test user isolation.
     */
    public function test_user_isolation(): void
    {
        // Create keyword for user 1
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->site->id,
                'keyword' => 'user 1 keyword',
            ]);

        // Create keyword for user 2
        $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->postJson('/api/v1/keywords', [
                'site_id' => $this->otherUserSite->id,
                'keyword' => 'user 2 keyword',
            ]);

        // User 1 should only see their keywords
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        // User 2 should only see their keywords
        $response = $this->withHeaders($this->authHeaders($this->otherUserKeyValue))
            ->getJson('/api/v1/keywords');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }
}
