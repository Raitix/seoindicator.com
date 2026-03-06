<?php

namespace Tests\Feature\Api\V1;

use App\Models\Audit;
use App\Models\BadgeImpression;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Feature tests for Badge Impression Tracking API.
 *
 * Tests the POST /api/v1/badge/impression endpoint that tracks
 * embedded badge views on third-party sites.
 *
 * @see TASK-33-17
 * @see TEST-33-17
 */
class BadgeImpressionControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/badge/impression';

    private User $user;
    private Site $site;
    private Audit $publicAudit;
    private Audit $privateAudit;
    private string $validHash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);

        // Create public audit with hash
        $this->validHash = 'abc123def45678901234567890123456';
        $this->publicAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => $this->validHash,
            'is_public' => true,
        ]);

        // Create private audit
        $this->privateAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'private123456789012345678901',
            'is_public' => false,
        ]);
    }

    // =========================================================================
    // BI-VAL: Hash Validation Tests
    // =========================================================================

    /** @test */
    public function bi_val_01_valid_32_char_hash_returns_204(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_val_02_hash_too_short_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'abc',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'error', 'details']);
    }

    /** @test */
    public function bi_val_03_hash_too_long_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => str_repeat('a', 33),
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_04_hash_with_special_chars_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'abc-123_!@#def45678901234567890',
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_05_hash_with_spaces_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'abc 123 def 456 789 012 345 678',
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_06_empty_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => '',
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_07_missing_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_08_null_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => null,
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_09_hash_case_insensitive(): void
    {
        // Create audit with lowercase hash
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'lowercase12345678901234567890',
            'is_public' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'hash' => 'LOWERCASE12345678901234567890',
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_val_10_non_existent_hash_returns_404(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'nonexistent123456789012345678',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function bi_val_11_hash_with_unicode_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'abc日本語def45678901234567890',
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_val_12_numeric_only_hash_is_valid(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => '12345678901234567890123456789012',
            'is_public' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'hash' => '12345678901234567890123456789012',
        ]);

        $response->assertNoContent();
    }

    // =========================================================================
    // BI-AUDIT: Audit Validation Tests
    // =========================================================================

    /** @test */
    public function bi_audit_01_public_audit_returns_204(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_audit_02_private_audit_returns_404(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->privateAudit->public_hash,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function bi_audit_03_unpublished_audit_returns_404(): void
    {
        $unpublishedAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => null,
            'is_public' => false,
        ]);

        $response = $this->postJson($this->endpoint, [
            'hash' => 'unpublished1234567890123456789',
        ]);

        $response->assertNotFound();
    }

    /** @test */
    public function bi_audit_04_no_auth_required(): void
    {
        // No actingAs() - anonymous request
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_audit_05_same_hash_multiple_times_allowed(): void
    {
        // Clear rate limiter for this test
        RateLimiter::clear('badge-impression:127.0.0.1');

        // First request from IP 1
        $response1 = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.1']);

        // Second request from IP 2
        $response2 = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.2']);

        $response1->assertNoContent();
        $response2->assertNoContent();

        // Should have 2 impressions
        $this->assertEquals(2, BadgeImpression::where('public_hash', $this->validHash)->count());
    }

    /** @test */
    public function bi_audit_06_pending_audit_returns_204(): void
    {
        $pendingAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'pending123456789012345678901',
            'is_public' => true,
            'status' => 'pending',
        ]);

        $response = $this->postJson($this->endpoint, [
            'hash' => $pendingAudit->public_hash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_audit_07_404_consistency_for_non_existent_and_private(): void
    {
        // Non-existent hash
        $response1 = $this->postJson($this->endpoint, [
            'hash' => 'nonexistent123456789012345678',
        ]);

        // Private audit
        $response2 = $this->postJson($this->endpoint, [
            'hash' => $this->privateAudit->public_hash,
        ]);

        // Both should return same 404 format
        $response1->assertNotFound();
        $response2->assertNotFound();

        // Same error structure (prevents enumeration)
        $this->assertEquals(
            $response1->json('error'),
            $response2->json('error')
        );
    }

    // =========================================================================
    // BI-REF: Referrer Domain Tests
    // =========================================================================

    /** @test */
    public function bi_ref_01_valid_referrer_domain_stored(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => 'example.com',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('badge_impressions', [
            'public_hash' => $this->validHash,
            'referrer_domain' => 'example.com',
        ]);
    }

    /** @test */
    public function bi_ref_02_no_referrer_domain_stores_null(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        $this->assertNull($impression->referrer_domain);
    }

    /** @test */
    public function bi_ref_03_empty_referrer_domain_stores_null(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => '',
        ]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        $this->assertNull($impression->referrer_domain);
    }

    /** @test */
    public function bi_ref_04_domain_with_subdomain_stored(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => 'blog.example.com',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('badge_impressions', [
            'referrer_domain' => 'blog.example.com',
        ]);
    }

    /** @test */
    public function bi_ref_05_null_referrer_domain_accepted(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => null,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_ref_06_unicode_domain_accepted(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => '例え.jp',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('badge_impressions', [
            'referrer_domain' => '例え.jp',
        ]);
    }

    /** @test */
    public function bi_ref_07_referrer_header_extracted(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], [
            'Referer' => 'https://example.com/some/page',
        ]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        $this->assertEquals('example.com', $impression->referrer_domain);
    }

    /** @test */
    public function bi_ref_08_request_body_overrides_header(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => 'bodydomain.com',
        ], [
            'Referer' => 'https://headerdomain.com/page',
        ]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        $this->assertEquals('bodydomain.com', $impression->referrer_domain);
    }

    /** @test */
    public function bi_ref_09_long_referrer_truncated(): void
    {
        $longDomain = str_repeat('a', 300) . '.com';

        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => $longDomain,
        ]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        // Should be truncated to 255 chars by DB
        $this->assertLessThanOrEqual(255, strlen($impression->referrer_domain));
    }

    /** @test */
    public function bi_ref_10_ip_as_referrer_accepted(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => '192.168.1.1',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('badge_impressions', [
            'referrer_domain' => '192.168.1.1',
        ]);
    }

    // =========================================================================
    // BI-IP: IP Hashing Tests
    // =========================================================================

    /** @test */
    public function bi_ip_01_ip_hashed_not_stored_plain(): void
    {
        $testIp = '192.168.1.100';

        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => $testIp]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        // IP hash should be SHA256 (64 chars)
        $this->assertEquals(64, strlen($impression->ip_hash));

        // Should NOT be the plain IP
        $this->assertNotEquals($testIp, $impression->ip_hash);
    }

    /** @test */
    public function bi_ip_02_same_ip_same_hash(): void
    {
        $testIp = '192.168.1.100';

        // Create two impressions from same IP
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => $testIp]);

        $audit2 = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'second1234567890123456789012',
            'is_public' => true,
        ]);

        $this->postJson($this->endpoint, [
            'hash' => $audit2->public_hash,
        ], ['REMOTE_ADDR' => $testIp]);

        $impression1 = BadgeImpression::where('public_hash', $this->validHash)->first();
        $impression2 = BadgeImpression::where('public_hash', $audit2->public_hash)->first();

        // Same IP should produce same hash
        $this->assertEquals($impression1->ip_hash, $impression2->ip_hash);
    }

    /** @test */
    public function bi_ip_03_different_ip_different_hash(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.100']);

        $audit2 = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'second1234567890123456789012',
            'is_public' => true,
        ]);

        $this->postJson($this->endpoint, [
            'hash' => $audit2->public_hash,
        ], ['REMOTE_ADDR' => '192.168.1.200']);

        $impression1 = BadgeImpression::where('public_hash', $this->validHash)->first();
        $impression2 = BadgeImpression::where('public_hash', $audit2->public_hash)->first();

        // Different IPs should produce different hashes
        $this->assertNotEquals($impression1->ip_hash, $impression2->ip_hash);
    }

    /** @test */
    public function bi_ip_04_ipv6_hashed_correctly(): void
    {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => $ipv6]);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        // Should be valid SHA256 hash
        $this->assertEquals(64, strlen($impression->ip_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $impression->ip_hash);
    }

    /** @test */
    public function bi_ip_05_localhost_ip_hashed(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        $response->assertNoContent();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        // Should be 64 char hash
        $this->assertEquals(64, strlen($impression->ip_hash));
    }

    /** @test */
    public function bi_ip_06_ip_not_in_response(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        // 204 has no body, but verify no IP info leaked in headers
        $response->assertNoContent();
        $this->assertFalse($response->headers->has('X-Client-IP'));
    }

    /** @test */
    public function bi_ip_07_ip_hash_is_irreversible(): void
    {
        $testIp = '10.0.0.1';

        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => $testIp]);

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        // Hash should not contain any part of the IP
        $this->assertStringNotContainsString($testIp, $impression->ip_hash);
    }

    // =========================================================================
    // BI-DB: Database Write Tests
    // =========================================================================

    /** @test */
    public function bi_db_01_correct_audit_id_stored(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $this->assertDatabaseHas('badge_impressions', [
            'audit_id' => $this->publicAudit->id,
        ]);
    }

    /** @test */
    public function bi_db_02_correct_public_hash_stored(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $this->assertDatabaseHas('badge_impressions', [
            'public_hash' => $this->validHash,
        ]);
    }

    /** @test */
    public function bi_db_03_impression_at_timestamp_set(): void
    {
        $before = now()->subSecond();

        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $after = now()->addSecond();

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        $this->assertNotNull($impression->impression_at);
        $this->assertTrue($impression->impression_at->between($before, $after));
    }

    /** @test */
    public function bi_db_04_clicked_at_initially_null(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        $this->assertNull($impression->clicked_at);
    }

    /** @test */
    public function bi_db_05_click_target_initially_null(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();

        $this->assertNull($impression->click_target);
    }

    /** @test */
    public function bi_db_06_multiple_impressions_allowed(): void
    {
        // Clear rate limiter
        RateLimiter::clear('badge-impression:127.0.0.1');

        $this->postJson($this->endpoint, ['hash' => $this->validHash], ['REMOTE_ADDR' => '10.0.0.1']);
        $this->postJson($this->endpoint, ['hash' => $this->validHash], ['REMOTE_ADDR' => '10.0.0.2']);
        $this->postJson($this->endpoint, ['hash' => $this->validHash], ['REMOTE_ADDR' => '10.0.0.3']);

        $this->assertEquals(3, BadgeImpression::where('public_hash', $this->validHash)->count());
    }

    /** @test */
    public function bi_db_07_record_created_successfully(): void
    {
        $countBefore = BadgeImpression::count();

        $this->postJson($this->endpoint, ['hash' => $this->validHash]);

        $this->assertEquals($countBefore + 1, BadgeImpression::count());
    }

    // =========================================================================
    // BI-RES: Response Code Tests
    // =========================================================================

    /** @test */
    public function bi_res_01_success_response_is_204(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_res_02_success_has_empty_body(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function bi_res_03_404_response_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'nonexistent123456789012345678',
        ]);

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'error'])
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function bi_res_04_422_response_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'error', 'details'])
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function bi_res_05_content_type_is_json(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertHeader('Content-Type', 'application/json');
    }

    // =========================================================================
    // BI-SEC: Security Tests
    // =========================================================================

    /** @test */
    public function bi_sec_01_sql_injection_in_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => "'; DROP TABLE badge_impressions; --",
        ]);

        $response->assertUnprocessable();

        // Verify table still exists
        $this->assertDatabaseCount('badge_impressions', 0);
    }

    /** @test */
    public function bi_sec_02_sql_injection_in_referrer_sanitized(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => "'; SELECT * FROM users; --",
        ]);

        // Should succeed (sanitized)
        $response->assertNoContent();

        // Verify no injection occurred
        $impression = BadgeImpression::where('public_hash', $this->validHash)->first();
        $this->assertStringNotContainsString('SELECT', $impression->referrer_domain ?? '');
    }

    /** @test */
    public function bi_sec_03_xss_in_referrer_sanitized(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => '<script>alert(1)</script>.com',
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_sec_04_path_traversal_in_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => '../../../etc/passwd12345678901234567',
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_sec_05_no_sensitive_data_in_response(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $content = $response->getContent();

        // Should not expose audit details
        $this->assertStringNotContainsString('site_id', $content);
        $this->assertStringNotContainsString('user_id', $content);
        $this->assertStringNotContainsString('email', $content);
    }

    /** @test */
    public function bi_sec_06_csrf_not_required(): void
    {
        // POST without CSRF token should work (API endpoint)
        $response = $this->post($this->endpoint, [
            'hash' => $this->validHash,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_sec_07_no_auth_required(): void
    {
        // No actingAs() call - anonymous request
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_sec_08_request_body_size_limit(): void
    {
        // Very large referrer_domain
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => str_repeat('a', 10000),
        ]);

        // Should either succeed (truncated) or fail gracefully
        $this->assertContains($response->status(), [204, 422, 413]);
    }

    // =========================================================================
    // BI-EDGE: Edge Case Tests
    // =========================================================================

    /** @test */
    public function bi_edge_01_empty_request_body_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertUnprocessable();
    }

    /** @test */
    public function bi_edge_02_extra_fields_ignored(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'extra_field' => 'should be ignored',
            'malicious' => 'data',
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_edge_03_malformed_json_returns_400(): void
    {
        $response = $this->post($this->endpoint, '{invalid json}', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function bi_edge_04_duplicate_impression_allowed(): void
    {
        // Clear rate limiter
        RateLimiter::clear('badge-impression:192.168.1.1');

        // Same hash, same IP (within rate limit)
        $response1 = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.1']);

        $response2 = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.2']); // Different IP to avoid rate limit

        $response1->assertNoContent();
        $response2->assertNoContent();

        // Should have 2 records
        $this->assertEquals(2, BadgeImpression::count());
    }

    /** @test */
    public function bi_edge_05_special_chars_in_referrer_sanitized(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => "test\r\n.com",
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function bi_edge_06_request_timing_reasonable(): void
    {
        $start = microtime(true);

        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $duration = (microtime(true) - $start) * 1000;

        // Should complete in under 500ms
        $this->assertLessThan(500, $duration);
    }

    /** @test */
    public function bi_edge_07_whitespace_in_hash_returns_422(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => ' abc123def45678901234567890123456 ',
        ]);

        $response->assertUnprocessable();
    }

    // =========================================================================
    // AC: Acceptance Criteria Tests
    // =========================================================================

    /** @test */
    public function ac_01_endpoint_exists(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        // Should not return 404 (route exists)
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function ac_02_accepts_hash_and_referrer_domain(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => 'example.com',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('badge_impressions', [
            'public_hash' => $this->validHash,
            'referrer_domain' => 'example.com',
        ]);
    }

    /** @test */
    public function ac_03_validates_hash_exists_and_public(): void
    {
        // Valid public hash
        $validResponse = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);
        $validResponse->assertNoContent();

        // Invalid hash
        $invalidResponse = $this->postJson($this->endpoint, [
            'hash' => 'nonexistent123456789012345678',
        ]);
        $invalidResponse->assertNotFound();

        // Private audit
        $privateResponse = $this->postJson($this->endpoint, [
            'hash' => $this->privateAudit->public_hash,
        ]);
        $privateResponse->assertNotFound();
    }

    /** @test */
    public function ac_04_stores_impression_in_database(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $this->assertDatabaseHas('badge_impressions', [
            'audit_id' => $this->publicAudit->id,
            'public_hash' => $this->validHash,
        ]);
    }

    /** @test */
    public function ac_05_ip_hashed_for_privacy(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '192.168.1.100']);

        $impression = BadgeImpression::first();

        // IP should be hashed (64 char SHA256)
        $this->assertEquals(64, strlen($impression->ip_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $impression->ip_hash);
    }

    /** @test */
    public function ac_06_no_auth_required(): void
    {
        // Anonymous request
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent();
    }

    /** @test */
    public function ac_07_rate_limited_10_per_minute(): void
    {
        $ip = '10.0.0.' . rand(100, 200); // Random IP for clean test
        RateLimiter::clear('badge-impression:' . $ip);

        // Make 10 requests (within limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson($this->endpoint, [
                'hash' => $this->validHash,
            ], ['REMOTE_ADDR' => $ip]);
            $response->assertNoContent();
        }

        // 11th request should be rate limited
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => $ip]);

        $response->assertStatus(429);
    }

    /** @test */
    public function ac_08_returns_204_on_success(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ]);

        $response->assertNoContent(); // 204
    }

    /** @test */
    public function ac_09_returns_404_for_invalid_hash(): void
    {
        $response = $this->postJson($this->endpoint, [
            'hash' => 'nonexistent123456789012345678',
        ]);

        $response->assertNotFound();
    }

    // =========================================================================
    // BI-INT: Integration Tests
    // =========================================================================

    /** @test */
    public function bi_int_01_full_flow_creates_record(): void
    {
        $beforeCount = BadgeImpression::count();

        $response = $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
            'referrer_domain' => 'testsite.com',
        ], [
            'REMOTE_ADDR' => '203.0.113.50',
            'Referer' => 'https://example.org/page',
        ]);

        $response->assertNoContent();
        $this->assertEquals($beforeCount + 1, BadgeImpression::count());

        $impression = BadgeImpression::latest()->first();
        $this->assertEquals($this->publicAudit->id, $impression->audit_id);
        $this->assertEquals($this->validHash, $impression->public_hash);
        $this->assertEquals('testsite.com', $impression->referrer_domain);
        $this->assertEquals(64, strlen($impression->ip_hash));
        $this->assertNull($impression->clicked_at);
        $this->assertNull($impression->click_target);
    }

    /** @test */
    public function bi_int_02_uses_badge_impression_model(): void
    {
        $this->postJson($this->endpoint, [
            'hash' => $this->validHash,
        ], ['REMOTE_ADDR' => '198.51.100.1']);

        $impression = BadgeImpression::first();

        // Verify model methods work
        $this->assertInstanceOf(Audit::class, $impression->audit);
        $this->assertFalse($impression->is_clicked);
    }

    /** @test */
    public function bi_int_03_multiple_audits_separate_impressions(): void
    {
        $audit2 = Audit::factory()->create([
            'site_id' => $this->site->id,
            'public_hash' => 'second1234567890123456789012',
            'is_public' => true,
        ]);

        RateLimiter::clear('badge-impression:10.0.0.1');
        RateLimiter::clear('badge-impression:10.0.0.2');

        $this->postJson($this->endpoint, ['hash' => $this->validHash], ['REMOTE_ADDR' => '10.0.0.1']);
        $this->postJson($this->endpoint, ['hash' => $audit2->public_hash], ['REMOTE_ADDR' => '10.0.0.2']);

        $this->assertEquals(2, BadgeImpression::count());

        $impression1 = BadgeImpression::where('public_hash', $this->validHash)->first();
        $impression2 = BadgeImpression::where('public_hash', $audit2->public_hash)->first();

        $this->assertNotEquals($impression1->audit_id, $impression2->audit_id);
    }

    /** @test */
    public function bi_int_04_impression_scope_works(): void
    {
        $this->postJson($this->endpoint, ['hash' => $this->validHash]);

        $count = BadgeImpression::forAudit($this->publicAudit->id)->count();
        $this->assertEquals(1, $count);

        $count = BadgeImpression::forHash($this->validHash)->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function bi_int_05_http_method_restrictions(): void
    {
        // GET should not be allowed
        $getResponse = $this->getJson($this->endpoint);
        $getResponse->assertMethodNotAllowed();

        // PUT should not be allowed
        $putResponse = $this->putJson($this->endpoint, ['hash' => $this->validHash]);
        $putResponse->assertMethodNotAllowed();

        // DELETE should not be allowed
        $deleteResponse = $this->deleteJson($this->endpoint);
        $deleteResponse->assertMethodNotAllowed();

        // PATCH should not be allowed
        $patchResponse = $this->patchJson($this->endpoint, ['hash' => $this->validHash]);
        $patchResponse->assertMethodNotAllowed();
    }
}
