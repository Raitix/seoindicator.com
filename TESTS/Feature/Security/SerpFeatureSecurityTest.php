<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SerpFeatureSecurityTest - Security tests for SERP Features API
 * 
 * Tests authentication and authorization for SERP Features endpoints.
 * Addresses OWASP A01:2021 (Broken Access Control) and A05:2021 (Security Misconfiguration).
 * 
 * @see SPEC-SEC-7-10
 * @see TEST-SEC-7-10
 */
class SerpFeatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a user with a site for testing.
     */
    private function createUserWithSite(): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        return ['user' => $user, 'site' => $site];
    }

    /**
     * Create a keyword for a given site.
     */
    private function createKeywordForSite(Site $site): Keyword
    {
        return Keyword::factory()->create(['site_id' => $site->id]);
    }

    // =========================================================================
    // AUTH-001: Unauthenticated keyword features
    // =========================================================================
    
    public function test_unauthenticated_keyword_features_returns_401(): void
    {
        $response = $this->getJson('/api/v1/keywords/1/serp-features');

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // AUTH-002: Unauthenticated site features
    // =========================================================================
    
    public function test_unauthenticated_site_features_returns_401(): void
    {
        $response = $this->getJson('/api/v1/sites/1/serp-features');

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // AUTH-003: Unauthenticated summary
    // =========================================================================
    
    public function test_unauthenticated_summary_returns_401(): void
    {
        $response = $this->getJson('/api/v1/sites/1/serp-features/summary');

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // IDOR-001: User accessing another's keyword features
    // =========================================================================
    
    public function test_user_cannot_access_other_users_keyword_features(): void
    {
        // Owner creates site and keyword
        $owner = User::factory()->create();
        $ownerSite = Site::factory()->create(['user_id' => $owner->id]);
        $keyword = Keyword::factory()->create(['site_id' => $ownerSite->id]);

        // Attacker tries to access
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        $response->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // IDOR-002: User accessing another's site features
    // =========================================================================
    
    public function test_user_cannot_access_other_users_site_features(): void
    {
        // Owner creates site
        $owner = User::factory()->create();
        $ownerSite = Site::factory()->create(['user_id' => $owner->id]);

        // Attacker tries to access
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/sites/{$ownerSite->id}/serp-features");

        $response->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // IDOR-003: User accessing another's summary
    // =========================================================================
    
    public function test_user_cannot_access_other_users_site_summary(): void
    {
        // Owner creates site
        $owner = User::factory()->create();
        $ownerSite = Site::factory()->create(['user_id' => $owner->id]);

        // Attacker tries to access
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/sites/{$ownerSite->id}/serp-features/summary");

        $response->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // AUTHZ-001: User accessing own keyword features
    // =========================================================================
    
    public function test_user_can_access_own_keyword_features(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();
        $keyword = $this->createKeywordForSite($site);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHZ-002: User accessing own site features
    // =========================================================================
    
    public function test_user_can_access_own_site_features(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features");

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHZ-003: User accessing own summary
    // =========================================================================
    
    public function test_user_can_access_own_site_summary(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features/summary");

        $response->assertStatus(200);
    }

    // =========================================================================
    // NF-001: Non-existent keyword
    // =========================================================================
    
    public function test_non_existent_keyword_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/keywords/99999/serp-features');

        $response->assertStatus(404);
    }

    // =========================================================================
    // NF-002: Non-existent site
    // =========================================================================
    
    public function test_non_existent_site_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/99999/serp-features');

        $response->assertStatus(404);
    }

    // =========================================================================
    // NF-003: Non-existent site for summary
    // =========================================================================
    
    public function test_non_existent_site_summary_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/99999/serp-features/summary');

        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-001: SQL injection attempt in keyword ID
    // =========================================================================
    
    public function test_sql_injection_in_keyword_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/keywords/1' OR '1'='1/serp-features");

        // Route regex [0-9]+ should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-002: SQL injection attempt in site ID
    // =========================================================================
    
    public function test_sql_injection_in_site_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/1' OR '1'='1/serp-features");

        // Route regex [0-9]+ should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-003: Path traversal attempt in site ID
    // =========================================================================
    
    public function test_path_traversal_in_site_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/../etc/passwd/serp-features');

        // Route regex [0-9]+ should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-004: XSS attempt in ID
    // =========================================================================
    
    public function test_xss_in_site_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/<script>alert(1)</script>/serp-features');

        // Route regex [0-9]+ should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-005: Negative ID
    // =========================================================================
    
    public function test_negative_site_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/-1/serp-features');

        // Route regex [0-9]+ does not match negative numbers
        $response->assertStatus(404);
    }

    // =========================================================================
    // INJ-006: Float ID
    // =========================================================================
    
    public function test_float_site_id_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/1.5/serp-features');

        // Route regex [0-9]+ does not match floats
        $response->assertStatus(404);
    }

    // =========================================================================
    // EDGE-001: Keyword with deleted/non-existent site
    // =========================================================================
    
    public function test_keyword_without_site_returns_403(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $keyword = Keyword::factory()->create(['site_id' => $otherSite->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        // User doesn't own the site that owns the keyword
        $response->assertStatus(403);
    }

    // =========================================================================
    // EDGE-002: Site with no SERP features (empty data)
    // =========================================================================
    
    public function test_site_with_no_serp_features_returns_empty_data(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
        // Empty data is valid - site just has no SERP features tracked yet
    }

    // =========================================================================
    // EDGE-003: Large siteId value
    // =========================================================================
    
    public function test_large_site_id_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/' . PHP_INT_MAX . '/serp-features');

        $response->assertStatus(404);
    }

    // =========================================================================
    // EDGE-004: Summary endpoint with empty data
    // =========================================================================
    
    public function test_summary_with_no_serp_features_returns_empty(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features/summary");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // EDGE-005: Multiple attempts from same user (no rate limiting on these endpoints)
    // =========================================================================
    
    public function test_multiple_requests_from_same_user_succeed(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        // Make multiple requests - should all succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)
                ->getJson("/api/v1/sites/{$site->id}/serp-features");

            $response->assertStatus(200);
        }
    }

    // =========================================================================
    // Additional: Verify error message format for 403
    // =========================================================================
    
    public function test_403_error_message_does_not_leak_information(): void
    {
        $owner = User::factory()->create();
        $ownerSite = Site::factory()->create(['user_id' => $owner->id]);
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/sites/{$ownerSite->id}/serp-features");

        $response->assertStatus(403);

        // Error message should be generic, not reveal resource exists
        $message = $response->json('message');
        $this->assertStringNotContainsString('owner', $message);
        $this->assertStringNotContainsString($owner->email, $message);
        $this->assertStringNotContainsString($ownerSite->domain, $message);
    }

    // =========================================================================
    // Additional: Verify response structure for successful requests
    // =========================================================================
    
    public function test_successful_response_has_expected_structure(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithSite();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    // Data structure depends on implementation
                    // At minimum, should have a data key
                ]
            ]);
    }
}
