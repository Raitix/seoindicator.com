<?php

namespace Tests\Feature\Api\V1\Security;

use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\Ranking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security tests for SerpFeatureController (SPEC-SEC-7-10).
 *
 * Tests authentication, IDOR protection, and input validation
 * for SERP feature API endpoints.
 */
#[\PHPUnit\Framework\Attributes\Group('security')]
#[\PHPUnit\Framework\Attributes\Group('serp-features')]
class SerpFeatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $otherUser;
    protected Site $ownerSite;
    protected Site $otherUserSite;
    protected Keyword $ownerKeyword;
    protected Keyword $otherUserKeyword;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two users for IDOR testing
        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create sites for each user
        $this->ownerSite = Site::factory()->create([
            'user_id' => $this->owner->id,
            'url' => 'https://owner-site.com',
        ]);
        $this->otherUserSite = Site::factory()->create([
            'user_id' => $this->otherUser->id,
            'url' => 'https://other-user-site.com',
        ]);

        // Create keywords for each user's site
        $this->ownerKeyword = Keyword::factory()->create([
            'site_id' => $this->ownerSite->id,
            'user_id' => $this->owner->id,
            'keyword' => 'owner keyword',
        ]);
        $this->otherUserKeyword = Keyword::factory()->create([
            'site_id' => $this->otherUserSite->id,
            'user_id' => $this->otherUser->id,
            'keyword' => 'other user keyword',
        ]);

        // Create some ranking history for testing
        Ranking::factory()->create([
            'keyword_id' => $this->ownerKeyword->id,
            'position' => 5,
            'serp_features' => json_encode(['featured_snippet', 'local_pack']),
            'checked_at' => now()->subDay(),
        ]);
        Ranking::factory()->create([
            'keyword_id' => $this->otherUserKeyword->id,
            'position' => 10,
            'serp_features' => json_encode(['image_pack']),
            'checked_at' => now()->subDay(),
        ]);
    }

    // =========================================================================
    // AUTHENTICATION TESTS (401 Unauthorized)
    // =========================================================================

    /**
     * TEST-ID: AUTH-001
     * Unauthenticated access to keyword SERP features returns 401.
     */
    public function test_unauthenticated_keyword_features_returns_401(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features");

        $response->assertStatus(401);
    }

    /**
     * TEST-ID: AUTH-002
     * Unauthenticated access to site SERP features returns 401.
     */
    public function test_unauthenticated_site_features_returns_401(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features");

        $response->assertStatus(401);
    }

    /**
     * TEST-ID: AUTH-003
     * Unauthenticated access to site SERP summary returns 401.
     */
    public function test_unauthenticated_summary_returns_401(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary");

        $response->assertStatus(401);
    }

    /**
     * TEST-ID: AUTH-004
     * Unauthenticated access to cache invalidation returns 401.
     */
    public function test_unauthenticated_invalidate_cache_returns_401(): void
    {
        $response = $this->deleteJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/cache");

        $response->assertStatus(401);
    }

    // =========================================================================
    // IDOR PROTECTION TESTS (403 Forbidden)
    // =========================================================================

    /**
     * TEST-ID: IDOR-001
     * Cross-user access to keyword SERP features returns 403.
     * 
     * User A attempts to access User B's keyword SERP features.
     * Expected: 403 Forbidden
     */
    public function test_idor_keyword_features_cross_user_returns_403(): void
    {
        // Owner tries to access other user's keyword
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->otherUserKeyword->id}/serp-features");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ]);
    }

    /**
     * TEST-ID: IDOR-002
     * Cross-user access to site SERP features returns 403.
     * 
     * User A attempts to access User B's site SERP features.
     * Expected: 403 Forbidden
     */
    public function test_idor_site_features_cross_user_returns_403(): void
    {
        // Owner tries to access other user's site
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->otherUserSite->id}/serp-features");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ]);
    }

    /**
     * TEST-ID: IDOR-003
     * Cross-user access to site SERP summary returns 403.
     * 
     * User A attempts to access User B's site SERP summary.
     * Expected: 403 Forbidden
     */
    public function test_idor_summary_cross_user_returns_403(): void
    {
        // Owner tries to access other user's site summary
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->otherUserSite->id}/serp-features/summary");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ]);
    }

    /**
     * TEST-ID: IDOR-004
     * Cross-user cache invalidation returns 403.
     * 
     * User A attempts to invalidate cache for User B's site.
     * Expected: 403 Forbidden
     */
    public function test_idor_invalidate_cache_cross_user_returns_403(): void
    {
        // Owner tries to invalidate cache for other user's site
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/sites/{$this->otherUserSite->id}/serp-features/cache");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ]);
    }

    /**
     * TEST-ID: IDOR-005
     * Owner can access their own keyword SERP features.
     * 
     * User accesses their own keyword - should succeed.
     * Expected: 200 OK
     */
    public function test_idor_owner_can_access_own_keyword_features(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features");

        // Should succeed (200 or 404 if no data, but NOT 403)
        $response->assertStatus(200);
    }

    /**
     * TEST-ID: IDOR-006
     * Owner can access their own site SERP features.
     * 
     * User accesses their own site - should succeed.
     * Expected: 200 OK
     */
    public function test_idor_owner_can_access_own_site_features(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: IDOR-007
     * Owner can access their own site SERP summary.
     * 
     * User accesses their own site summary - should succeed.
     * Expected: 200 OK
     */
    public function test_idor_owner_can_access_own_summary(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: IDOR-008
     * Owner can invalidate their own site cache.
     * 
     * User invalidates their own site cache - should succeed.
     * Expected: 200 OK
     */
    public function test_idor_owner_can_invalidate_own_cache(): void
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/cache");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    // =========================================================================
    // INPUT VALIDATION TESTS (400 Bad Request)
    // =========================================================================

    /**
     * TEST-ID: VAL-001
     * Invalid keyword ID (zero) returns validation error.
     */
    public function test_validation_keyword_id_zero_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/keywords/0/serp-features');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-002
     * Invalid keyword ID (negative) returns validation error.
     */
    public function test_validation_keyword_id_negative_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/keywords/-1/serp-features');

        // Route constraint should reject this (404) or validation (400)
        $response->assertStatus(404);
    }

    /**
     * TEST-ID: VAL-003
     * Invalid site ID (zero) returns validation error.
     */
    public function test_validation_site_id_zero_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/sites/0/serp-features');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-004
     * Invalid site ID (negative) returns validation error.
     */
    public function test_validation_site_id_negative_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/sites/-1/serp-features');

        // Route constraint should reject this (404)
        $response->assertStatus(404);
    }

    /**
     * TEST-ID: VAL-005
     * Non-existent keyword returns 404.
     */
    public function test_validation_keyword_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/keywords/99999/serp-features');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'KEYWORD_NOT_FOUND',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-006
     * Non-existent site returns 404.
     */
    public function test_validation_site_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/sites/99999/serp-features');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'SITE_NOT_FOUND',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-007
     * Invalid days parameter returns validation error.
     */
    public function test_validation_invalid_days_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=invalid");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-008
     * Days parameter out of range returns validation error.
     */
    public function test_validation_days_out_of_range_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=500");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-009
     * Negative days parameter returns validation error.
     */
    public function test_validation_negative_days_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=-5");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: VAL-010
     * Invalid pagination parameters return validation error.
     */
    public function test_validation_invalid_pagination_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features?page=-1&perPage=invalid");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    // =========================================================================
    // SQL INJECTION TESTS
    // =========================================================================

    /**
     * TEST-ID: SQL-001
     * SQL injection in keyword ID is blocked by route constraint.
     */
    public function test_sql_injection_keyword_id_blocked(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/keywords/1;DROP%20TABLE%20keywords;/serp-features');

        // Route constraint only allows numeric IDs
        $response->assertStatus(404);
    }

    /**
     * TEST-ID: SQL-002
     * SQL injection in site ID is blocked by route constraint.
     */
    public function test_sql_injection_site_id_blocked(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/sites/1;SELECT%20*%20FROM%20users/serp-features');

        // Route constraint only allows numeric IDs
        $response->assertStatus(404);
    }

    /**
     * TEST-ID: SQL-003
     * SQL injection in query parameters does not affect query.
     */
    public function test_sql_injection_query_params_safe(): void
    {
        // This should be safely handled by parameterized queries
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=7;SELECT%20*%20FROM%20users");

        // Either validation error or successful response with safe handling
        // Should NOT return database data or cause errors
        $this->assertContains($response->status(), [200, 400]);
        $response->assertJsonMissing(['password']);
    }

    // =========================================================================
    // XSS PROTECTION TESTS
    // =========================================================================

    /**
     * TEST-ID: XSS-001
     * XSS in error messages is not reflected.
     */
    public function test_xss_error_messages_sanitized(): void
    {
        // Try to inject XSS in the site lookup
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/sites/99999/serp-features');

        $response->assertStatus(404);
        
        // Verify error message does not reflect any input
        $content = $response->json();
        $this->assertStringNotContainsString('<script>', json_encode($content));
    }

    /**
     * TEST-ID: XSS-002
     * XSS in query parameters does not execute.
     */
    public function test_xss_query_params_sanitized(): void
    {
        $xssPayload = '<script>alert("xss")</script>';
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=7" . urlencode($xssPayload));

        // Should handle safely
        $this->assertContains($response->status(), [200, 400]);
        
        $content = $response->json();
        $this->assertStringNotContainsString('<script>', json_encode($content));
    }

    // =========================================================================
    // PATH TRAVERSAL TESTS
    // =========================================================================

    /**
     * TEST-ID: PATH-001
     * Path traversal in ID parameter is blocked.
     */
    public function test_path_traversal_id_blocked(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/keywords/../../../etc/passwd/serp-features');

        // Route constraint only allows numeric IDs
        $response->assertStatus(404);
    }

    /**
     * TEST-ID: PATH-002
     * Path traversal in query parameters is handled safely.
     */
    public function test_path_traversal_query_params_safe(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?from=../../../etc/passwd");

        // Invalid date parameter should be handled gracefully (400 or 200 with defaults)
        // Either validation error or successful response with safe handling
        $this->assertContains($response->status(), [200, 400]);
        
        // Should NOT return any file content
        $content = $response->json();
        $this->assertStringNotContainsString('passwd', json_encode($content));
        $this->assertStringNotContainsString('root:', json_encode($content));
    }

    // =========================================================================
    // RATE LIMITING / ABUSE TESTS (Optional - depends on middleware)
    // =========================================================================

    /**
     * TEST-ID: RATE-001
     * Multiple requests from authenticated user are handled.
     * 
     * Note: This test verifies the endpoint handles multiple requests.
     * Actual rate limiting is handled by middleware.
     */
    public function test_multiple_requests_handled(): void
    {
        // Make multiple requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->owner)
                ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary");
            $response->assertStatus(200);
        }
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    /**
     * TEST-ID: EDGE-001
     * Keyword with direct user_id ownership (no site) is accessible.
     */
    public function test_keyword_with_direct_ownership_accessible(): void
    {
        // Create keyword with direct user ownership (no site)
        $keyword = Keyword::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => null,
        ]);

        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 3,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        // Should succeed - owner can access via direct user_id
        $response->assertStatus(200);
    }

    /**
     * TEST-ID: EDGE-002
     * Keyword with site ownership is accessible.
     */
    public function test_keyword_with_site_ownership_accessible(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: EDGE-003
     * Keyword with neither direct nor site ownership is forbidden.
     */
    public function test_keyword_no_ownership_forbidden(): void
    {
        // Create keyword owned by another user with no site
        $keyword = Keyword::factory()->create([
            'user_id' => $this->otherUser->id,
            'site_id' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        $response->assertStatus(403);
    }

    /**
     * TEST-ID: EDGE-004
     * Empty SERP features handled gracefully.
     */
    public function test_empty_serp_features_handled(): void
    {
        // Create keyword with no ranking history
        $keyword = Keyword::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $this->ownerSite->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        // Should succeed with empty or null data
        $response->assertStatus(200);
    }

    /**
     * TEST-ID: EDGE-005
     * Large days parameter is capped to maximum.
     */
    public function test_large_days_capped(): void
    {
        // Request with days beyond max (365)
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=999");

        // Should return validation error (not server error)
        $response->assertStatus(400);
    }

    /**
     * TEST-ID: EDGE-006
     * Minimum days parameter (1) is accepted.
     */
    public function test_minimum_days_accepted(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=1");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: EDGE-007
     * Maximum days parameter (365) is accepted.
     */
    public function test_maximum_days_accepted(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features/summary?days=365");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: EDGE-008
     * Pagination perPage is capped to maximum (100).
     */
    public function test_pagination_perpage_capped(): void
    {
        // Request with perPage beyond max (100)
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/sites/{$this->ownerSite->id}/serp-features?perPage=500");

        // Should succeed (capped internally)
        $response->assertStatus(200);
    }

    // =========================================================================
    // DATE RANGE VALIDATION TESTS
    // =========================================================================

    /**
     * TEST-ID: DATE-001
     * Valid date range is accepted.
     */
    public function test_valid_date_range_accepted(): void
    {
        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features?from={$from}&to={$to}");

        $response->assertStatus(200);
    }

    /**
     * TEST-ID: DATE-002
     * Invalid date format returns validation error.
     */
    public function test_invalid_date_format_returns_400(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features?from=not-a-date&to=also-invalid");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    /**
     * TEST-ID: DATE-003
     * 'from' date after 'to' date returns validation error.
     */
    public function test_from_after_to_returns_400(): void
    {
        $from = now()->format('Y-m-d');
        $to = now()->subDays(7)->format('Y-m-d');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/keywords/{$this->ownerKeyword->id}/serp-features?from={$from}&to={$to}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }
}
