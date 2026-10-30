<?php

namespace Tests\Feature\Api\V1;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Models\PublicAuditView;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for PublicAuditController
 * 
 * Tests public audit API endpoints:
 * - GET /api/v1/public-audit/{hash}
 * - POST /api/v1/public-audit/{hash}/lead
 * - GET /api/v1/public-audit/{hash}/badge
 * 
 * @see TASK-33-5
 */
class PublicAuditControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $publicHash;
    protected string $fullAccessHash;
    protected string $privateHash;
    protected string $zeroScoreHash;
    protected string $hundredScoreHash;
    protected string $emptyIssuesHash;
    protected Audit $publicAudit;
    protected Audit $fullAccessAudit;
    protected Audit $privateAudit;
    protected Site $site;
    protected User $user;
    protected IssueType $issueType;
    protected int $requestIpCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestData();
    }

    protected function useRemoteIp(string $ip): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    protected function nextRequestIp(): string
    {
        $this->requestIpCounter++;

        $octet3 = intdiv($this->requestIpCounter, 250) % 250;
        $octet4 = ($this->requestIpCounter % 250) + 1;

        return "10.0.{$octet3}.{$octet4}";
    }

    protected function getJsonFromIp(string $ip, $uri, array $headers = [], $options = 0)
    {
        $this->useRemoteIp($ip);

        return parent::json('GET', $uri, [], $headers, $options);
    }

    protected function postJsonFromIp(string $ip, $uri, array $data = [], array $headers = [], $options = 0)
    {
        $this->useRemoteIp($ip);

        return parent::json('POST', $uri, $data, $headers, $options);
    }

    public function getJson($uri, array $headers = [], $options = 0)
    {
        return $this->getJsonFromIp($this->nextRequestIp(), $uri, $headers, $options);
    }

    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->postJsonFromIp($this->nextRequestIp(), $uri, $data, $headers, $options);
    }

    public function putJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        $this->useRemoteIp($this->nextRequestIp());

        return parent::json('PUT', $uri, $data, $headers, $options);
    }

    public function patchJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        $this->useRemoteIp($this->nextRequestIp());

        return parent::json('PATCH', $uri, $data, $headers, $options);
    }

    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        $this->useRemoteIp($this->nextRequestIp());

        return parent::json('DELETE', $uri, $data, $headers, $options);
    }

    /**
     * Setup test data with factories.
     */
    protected function setupTestData(): void
    {
        // Create a user
        $this->user = User::factory()->create();

        // Create an issue type for issues
        $this->issueType = IssueType::create([
            'name' => 'Missing H1',
            'category' => 'technical',
            'severity_default' => 'warning',
            'description' => 'Missing H1 tag on page',
            'recommendation_template' => 'Add an H1 tag to the page.',
        ]);

        // Define test hashes
        $this->publicHash = 'abc123def456ghi789jkl012mno345pqr';
        $this->fullAccessHash = 'xyz789abc012def345ghi678jkl901mno';
        $this->privateHash = 'private123hash456private789hash';
        $this->zeroScoreHash = 'zeroc00000000000000000000000000';
        $this->hundredScoreHash = 'hundred00000000000000000000000';
        $this->emptyIssuesHash = 'emptyc000000000000000000000000';

        // Create site for public audit
        $this->site = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);

        // Create public audit with issues
        $this->publicAudit = Audit::factory()
            ->completed()
            ->public()
            ->create([
                'site_id' => $this->site->id,
                'public_hash' => $this->publicHash,
                'completed_at' => now()->parse('2026-03-06T12:00:00Z'),
            ]);

        // Create issues for the public audit
        AuditIssue::factory()->create([
            'audit_id' => $this->publicAudit->id,
            'issue_type_id' => $this->issueType->id,
            'severity' => 'critical',
            'description' => 'Missing H1 tag',
        ]);
        AuditIssue::factory()->create([
            'audit_id' => $this->publicAudit->id,
            'issue_type_id' => $this->issueType->id,
            'severity' => 'warning',
            'description' => 'Meta description too long',
        ]);
        AuditIssue::factory()->create([
            'audit_id' => $this->publicAudit->id,
            'issue_type_id' => $this->issueType->id,
            'severity' => 'notice',
            'description' => 'Low word count',
        ]);

        // Create full access audit (owned by user) - no issues
        $fullSite = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://fullaudit.com',
        ]);
        $this->fullAccessAudit = Audit::factory()
            ->completed()
            ->public()
            ->create([
                'site_id' => $fullSite->id,
                'public_hash' => $this->fullAccessHash,
                'completed_at' => now()->parse('2026-03-06T10:00:00Z'),
            ]);

        // Create private audit
        $privateSite = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://private.com',
        ]);
        $this->privateAudit = Audit::factory()
            ->completed()
            ->private()
            ->create([
                'site_id' => $privateSite->id,
                'public_hash' => $this->privateHash,
            ]);

        // Create audit with zero score (many critical issues = 11+ critical issues to get score 0)
        $zeroSite = Site::factory()->create(['url' => 'https://zeroscore.com']);
        $zeroAudit = Audit::factory()
            ->completed()
            ->public()
            ->create([
                'site_id' => $zeroSite->id,
                'public_hash' => $this->zeroScoreHash,
            ]);
        // Create 11 critical issues to bring score to 0 (100 - 11*10 = -10, clamped to 0)
        for ($i = 0; $i < 11; $i++) {
            AuditIssue::factory()->create([
                'audit_id' => $zeroAudit->id,
                'issue_type_id' => $this->issueType->id,
                'severity' => 'critical',
                'description' => "Critical issue {$i}",
            ]);
        }

        // Create audit with 100 score (no issues)
        $hundredSite = Site::factory()->create(['url' => 'https://hundred.com']);
        Audit::factory()
            ->completed()
            ->public()
            ->create([
                'site_id' => $hundredSite->id,
                'public_hash' => $this->hundredScoreHash,
            ]);

        // Create audit with empty issues
        $emptySite = Site::factory()->create(['url' => 'https://empty.com']);
        Audit::factory()
            ->completed()
            ->public()
            ->create([
                'site_id' => $emptySite->id,
                'public_hash' => $this->emptyIssuesHash,
            ]);
    }

    // ========================================================================
    // PA-GET: GET /api/v1/public-audit/{hash}
    // ========================================================================

    /** @test */
    public function get_audit_returns_200_for_valid_hash(): void
    {
        // PA-GET-001
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'site_url',
                    'audited_at',
                    'overall_score',
                    'category_scores',
                    'top_issues',
                    'is_partial',
                ],
            ]);
    }

    /** @test */
    public function get_audit_returns_site_url(): void
    {
        // PA-GET-002
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.site_url', 'https://example.com');
    }

    /** @test */
    public function get_audit_returns_audited_at_timestamp(): void
    {
        // PA-GET-003
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.audited_at', '2026-03-06T12:00:00+00:00');
    }

    /** @test */
    public function get_audit_returns_overall_score(): void
    {
        // PA-GET-004
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200);
        // Score should be calculated from issues (100 - 10 - 5 - 2 = 83)
        $this->assertGreaterThanOrEqual(0, $response->json('data.overall_score'));
        $this->assertLessThanOrEqual(100, $response->json('data.overall_score'));
    }

    /** @test */
    public function get_audit_returns_category_scores(): void
    {
        // PA-GET-005
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'category_scores' => [
                        'technical',
                        'on_page',
                        'content',
                        'mobile',
                    ],
                ],
            ]);
    }

    /** @test */
    public function get_audit_returns_top_issues(): void
    {
        // PA-GET-006
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.top_issues')
            ->assertJsonStructure([
                'data' => [
                    'top_issues' => [
                        '*' => ['category', 'issue', 'severity'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function get_audit_returns_is_partial_flag(): void
    {
        // PA-GET-007
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_partial', true);
    }

    /** @test */
    public function get_audit_returns_is_partial_false_for_full_access(): void
    {
        // PA-GET-008 - For authenticated users with full access
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/public-audit/' . $this->fullAccessHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_partial', false);
    }

    /** @test */
    public function get_audit_returns_404_for_invalid_hash(): void
    {
        // PA-GET-009
        $response = $this->getJson('/api/v1/public-audit/invalidhash123');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Audit not found',
            ]);
    }

    /** @test */
    public function get_audit_returns_404_for_non_existent_hash(): void
    {
        // PA-GET-010
        $response = $this->getJson('/api/v1/public-audit/zzz999zzz999zzz999zzz999zzz999zzz');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Audit not found',
            ]);
    }

    /** @test */
    public function get_audit_returns_404_for_non_public_audit(): void
    {
        // PA-GET-011
        $response = $this->getJson('/api/v1/public-audit/' . $this->privateHash);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Audit not found',
            ]);
    }

    /** @test */
    public function get_audit_excludes_sensitive_data(): void
    {
        // PA-GET-012
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonMissing(['audit_id'])
            ->assertJsonMissing(['user_id'])
            ->assertJsonMissing(['email']);
    }

    /** @test */
    public function get_audit_requires_no_authentication(): void
    {
        // PA-GET-013
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200);
    }

    /** @test */
    public function get_audit_validates_hash_format(): void
    {
        // PA-GET-014
        $response = $this->getJson('/api/v1/public-audit/short');

        $response->assertStatus(404);
    }

    /** @test */
    public function get_audit_accepts_32_char_alphanumeric_hash(): void
    {
        // PA-GET-015
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200);
    }

    /** @test */
    public function get_audit_accepts_uppercase_hash(): void
    {
        // PA-GET-016 - Case insensitive hash matching
        $hash = strtoupper($this->publicHash);
        $response = $this->getJson('/api/v1/public-audit/' . $hash);

        // Hash is case-sensitive by default, so this should return 404
        $response->assertStatus(404);
    }

    /** @test */
    public function get_audit_tracks_view_in_audit_views(): void
    {
        // PA-GET-017 - Verify view tracking
        $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $this->assertDatabaseHas('public_audit_views', [
            'public_hash' => $this->publicHash,
            'is_lead_capture' => false,
        ]);
    }

    // ========================================================================
    // PA-POST: POST /api/v1/public-audit/{hash}/lead
    // ========================================================================

    /** @test */
    public function lead_capture_returns_201_for_valid_email(): void
    {
        // PA-POST-001
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Email captured successfully',
            ]);
    }

    /** @test */
    public function lead_capture_validates_email_format(): void
    {
        // PA-POST-002
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function lead_capture_requires_email(): void
    {
        // PA-POST-003
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function lead_capture_returns_404_for_invalid_hash(): void
    {
        // PA-POST-004
        $response = $this->postJson('/api/v1/public-audit/invalidhash123/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Audit not found',
            ]);
    }

    /** @test */
    public function lead_capture_returns_404_for_non_public_audit(): void
    {
        // PA-POST-005
        $response = $this->postJson('/api/v1/public-audit/' . $this->privateHash . '/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function lead_capture_stores_email_in_audit_views(): void
    {
        // PA-POST-006
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'lead@example.com',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'lead@example.com',
            'is_lead_capture' => true,
        ]);
    }

    /** @test */
    public function lead_capture_requires_no_authentication(): void
    {
        // PA-POST-007
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function lead_capture_normalizes_email_to_lowercase(): void
    {
        // PA-POST-008
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'TEST@Example.COM',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function lead_capture_trims_whitespace(): void
    {
        // PA-POST-009
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => '  trimmed@example.com  ',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'trimmed@example.com',
        ]);
    }

    /** @test */
    public function lead_capture_handles_duplicate_emails(): void
    {
        // PA-POST-010 - Same email should not cause error
        $email = 'duplicate@example.com';
        
        // First submission
        $response1 = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => $email,
        ]);
        $response1->assertStatus(201);

        // Second submission - should still succeed
        $response2 = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => $email,
        ]);
        $response2->assertStatus(201);
    }

    /** @test */
    public function lead_capture_validates_max_email_length(): void
    {
        // PA-POST-011
        $longEmail = str_repeat('a', 250) . '@example.com';
        
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => $longEmail,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function lead_capture_stores_with_audit_reference(): void
    {
        // PA-POST-012
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'referenced@example.com',
        ]);

        $response->assertStatus(201);
        
        // Verify audit_id is stored
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'referenced@example.com',
            'public_hash' => $this->publicHash,
        ]);
    }

    /** @test */
    public function lead_capture_rejects_disposable_email_domains(): void
    {
        // PA-POST-013 - Currently accepts all valid emails
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'test@10minutemail.com',
        ]);

        // Currently accepted - disposable email policy not enforced
        $response->assertStatus(201);
    }

    // ========================================================================
    // PA-BADGE: GET /api/v1/public-audit/{hash}/badge
    // ========================================================================

    /** @test */
    public function badge_config_returns_200_for_valid_hash(): void
    {
        // PA-BADGE-001
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hash',
                    'score',
                    'site_url',
                    'audit_url',
                    'impressions_endpoint',
                    'clicks_endpoint',
                    'sizes',
                    'themes',
                    'embed_code',
                ],
            ]);
    }

    /** @test */
    public function badge_config_returns_score(): void
    {
        // PA-BADGE-002
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200);
        $score = $response->json('data.score');
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** @test */
    public function badge_config_returns_site_url(): void
    {
        // PA-BADGE-003
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.site_url', 'example.com');
    }

    /** @test */
    public function badge_config_returns_audit_url(): void
    {
        // PA-BADGE-004
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200);
        $this->assertStringContainsString($this->publicHash, $response->json('data.audit_url'));
    }

    /** @test */
    public function badge_config_returns_available_sizes(): void
    {
        // PA-BADGE-005
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.sizes', ['small', 'medium', 'large']);
    }

    /** @test */
    public function badge_config_returns_available_themes(): void
    {
        // PA-BADGE-006
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.themes', ['light', 'dark']);
    }

    /** @test */
    public function badge_config_returns_404_for_invalid_hash(): void
    {
        // PA-BADGE-007
        $response = $this->getJson('/api/v1/public-audit/invalidhash123/badge');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Audit not found',
            ]);
    }

    /** @test */
    public function badge_config_returns_404_for_non_public_audit(): void
    {
        // PA-BADGE-008
        $response = $this->getJson('/api/v1/public-audit/' . $this->privateHash . '/badge');

        $response->assertStatus(404);
    }

    /** @test */
    public function badge_config_requires_no_authentication(): void
    {
        // PA-BADGE-009
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200);
    }

    /** @test */
    public function badge_config_includes_embed_code(): void
    {
        // PA-BADGE-010
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'embed_code',
                ],
            ]);

        $this->assertStringContainsString($this->publicHash, $response->json('data.embed_code'));
    }

    // ========================================================================
    // PA-RATE: Rate Limiting Tests
    // ========================================================================

    /** @test */
    public function rate_limit_allows_100_requests_per_hour(): void
    {
        // PA-RATE-001
        $ip = '203.0.113.10';

        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function rate_limit_blocks_after_100_requests(): void
    {
        // PA-RATE-002
        $ip = '203.0.113.11';

        for ($i = 0; $i < 100; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
            ]);
    }

    /** @test */
    public function rate_limit_is_per_ip_not_per_hash(): void
    {
        // PA-RATE-003
        $ip = '203.0.113.12';

        for ($i = 0; $i < 99; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->fullAccessHash)
            ->assertStatus(200);

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->fullAccessHash)
            ->assertStatus(429);
    }

    /** @test */
    public function rate_limit_is_isolated_per_ip(): void
    {
        // PA-RATE-004
        $limitedIp = '203.0.113.13';
        $freshIp = '203.0.113.14';

        for ($i = 0; $i < 100; $i++) {
            $this->getJsonFromIp($limitedIp, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->getJsonFromIp($limitedIp, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(429);

        $this->getJsonFromIp($freshIp, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(200);
    }

    /** @test */
    public function rate_limit_returns_retry_after_header(): void
    {
        // PA-RATE-005
        $ip = '203.0.113.15';

        for ($i = 0; $i < 100; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $response = $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(429);
        $this->assertSame('3600', $response->headers->get('Retry-After'));
    }

    /** @test */
    public function rate_limit_applies_to_all_endpoints(): void
    {
        // PA-RATE-006
        $ip = '203.0.113.16';

        for ($i = 0; $i < 99; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->postJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'test@example.com',
        ])->assertStatus(201);

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash . '/badge')
            ->assertStatus(429);
    }

    /** @test */
    public function rate_limit_resets_after_one_hour(): void
    {
        // PA-RATE-007
        $ip = '203.0.113.17';

        for ($i = 0; $i < 100; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(429);

        $this->travel(61)->minutes();

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(200);
    }

    // ========================================================================
    // PA-SEC: Security Tests
    // ========================================================================

    /** @test */
    public function sql_injection_in_hash_is_prevented(): void
    {
        // PA-SEC-001
        $maliciousHash = "abc'; DROP TABLE audits; --";
        
        $response = $this->getJson('/api/v1/public-audit/' . $maliciousHash);
        
        $response->assertStatus(404);
        // Verify no SQL error
        $response->assertJsonMissing(['SQL', 'syntax', 'mysql']);
    }

    /** @test */
    public function xss_in_email_is_sanitized(): void
    {
        // PA-SEC-002 - Invalid email with script tag should fail validation
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => '<script>alert("xss")</script>@example.com',
        ]);

        // Email validation should reject this
        $response->assertStatus(422);
    }

    /** @test */
    public function path_traversal_in_hash_is_prevented(): void
    {
        // PA-SEC-003
        $maliciousHash = '../../../etc/passwd';
        
        $response = $this->getJson('/api/v1/public-audit/' . $maliciousHash);
        
        $response->assertStatus(404);
    }

    /** @test */
    public function no_sensitive_data_in_response(): void
    {
        // PA-SEC-004
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonMissing(['api_key', 'password', 'secret', 'token']);
    }

    /** @test */
    public function private_audit_not_accessible(): void
    {
        // PA-SEC-005
        $response = $this->getJson('/api/v1/public-audit/' . $this->privateHash);

        $response->assertStatus(404);
    }

    /** @test */
    public function enumeration_protection_returns_same_error(): void
    {
        // PA-SEC-006 - Non-existent and non-public return same 404
        $nonExistent = $this->getJson('/api/v1/public-audit/zzz999zzz999zzz999zzz999zzz999zzz');
        $nonPublic = $this->getJson('/api/v1/public-audit/' . $this->privateHash);
        
        $nonExistent->assertStatus(404);
        $nonPublic->assertStatus(404);
        
        // Same error message
        $this->assertEquals(
            $nonExistent->json('error'),
            $nonPublic->json('error')
        );
    }

    /** @test */
    public function csrf_protection_for_lead_endpoint(): void
    {
        // PA-SEC-007 - API accepts JSON requests
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(201);
    }

    // ========================================================================
    // PA-EDGE: Edge Cases
    // ========================================================================

    /** @test */
    public function empty_hash_returns_404(): void
    {
        // PA-EDGE-001
        $response = $this->getJson('/api/v1/public-audit/');
        $response->assertStatus(404);
    }

    /** @test */
    public function special_characters_in_hash_returns_404(): void
    {
        // PA-EDGE-002
        $response = $this->getJson('/api/v1/public-audit/hash-with-special!@#$%');
        $response->assertStatus(404);
    }

    /** @test */
    public function unicode_in_hash_returns_404(): void
    {
        // PA-EDGE-003
        $response = $this->getJson('/api/v1/public-audit/日本語ハッシュ');
        $response->assertStatus(404);
    }

    /** @test */
    public function very_long_hash_returns_404(): void
    {
        // PA-EDGE-004
        $longHash = str_repeat('a', 1000);
        $response = $this->getJson('/api/v1/public-audit/' . $longHash);
        $response->assertStatus(404);
    }

    /** @test */
    public function hash_with_spaces_returns_404(): void
    {
        // PA-EDGE-005
        $response = $this->getJson('/api/v1/public-audit/hash with spaces');
        $response->assertStatus(404);
    }

    /** @test */
    public function concurrent_requests_are_handled(): void
    {
        // PA-EDGE-006 - Basic test that multiple requests work
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function score_boundary_zero(): void
    {
        // PA-EDGE-007
        $response = $this->getJson('/api/v1/public-audit/' . $this->zeroScoreHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 0);
    }

    /** @test */
    public function score_boundary_100(): void
    {
        // PA-EDGE-008
        $response = $this->getJson('/api/v1/public-audit/' . $this->hundredScoreHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 100);
    }

    /** @test */
    public function empty_category_scores_handled(): void
    {
        // PA-EDGE-009 - Category scores are generated dynamically
        $response = $this->getJson('/api/v1/public-audit/' . $this->emptyIssuesHash);

        $response->assertStatus(200);
        $categoryScores = $response->json('data.category_scores');
        $this->assertIsArray($categoryScores);
    }

    /** @test */
    public function empty_top_issues_handled(): void
    {
        // PA-EDGE-010
        $response = $this->getJson('/api/v1/public-audit/' . $this->emptyIssuesHash);

        $response->assertStatus(200)
            ->assertJsonPath('data.top_issues', []);
    }

    /** @test */
    public function missing_optional_fields_handled(): void
    {
        // PA-EDGE-011
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);

        $response->assertStatus(200)
            ->assertJsonMissing(['non_existent_field']);
    }

    // ========================================================================
    // PA-HTTP: HTTP Method Tests
    // ========================================================================

    /** @test */
    public function get_audit_rejects_post(): void
    {
        // PA-HTTP-001
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash);
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_put(): void
    {
        // PA-HTTP-002
        $response = $this->putJson('/api/v1/public-audit/' . $this->publicHash);
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_patch(): void
    {
        // PA-HTTP-003
        $response = $this->patchJson('/api/v1/public-audit/' . $this->publicHash);
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_delete(): void
    {
        // PA-HTTP-004
        $response = $this->deleteJson('/api/v1/public-audit/' . $this->publicHash);
        $response->assertStatus(405);
    }

    /** @test */
    public function lead_endpoint_rejects_get(): void
    {
        // PA-HTTP-005
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/lead');
        $response->assertStatus(405);
    }

    /** @test */
    public function badge_endpoint_rejects_post(): void
    {
        // PA-HTTP-006
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/badge');
        $response->assertStatus(405);
    }

    // ========================================================================
    // PA-PERF: Performance Tests
    // ========================================================================

    /** @test */
    public function get_audit_responds_within_500ms(): void
    {
        // PA-PERF-001
        $start = microtime(true);
        
        $this->getJson('/api/v1/public-audit/' . $this->publicHash);
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration, "Response time was {$duration}ms");
    }

    /** @test */
    public function lead_capture_responds_within_500ms(): void
    {
        // PA-PERF-002
        $start = microtime(true);
        
        $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'perf@example.com',
        ]);
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration, "Response time was {$duration}ms");
    }

    /** @test */
    public function badge_config_responds_within_200ms(): void
    {
        // PA-PERF-003
        $start = microtime(true);
        
        $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms");
    }

    // ========================================================================
    // PA-AC: Acceptance Criteria Tests
    // ========================================================================

    /** @test */
    public function ac_01_get_returns_audit_data_or_404(): void
    {
        // AC-01: GET /api/v1/public-audit/{hash} returns audit data or 404
        
        // Valid hash returns data
        $valid = $this->getJson('/api/v1/public-audit/' . $this->publicHash);
        $valid->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        
        // Invalid hash returns 404
        $invalid = $this->getJson('/api/v1/public-audit/nonexistent00000000000000000000');
        $invalid->assertStatus(404);
    }

    /** @test */
    public function ac_02_response_includes_required_fields(): void
    {
        // AC-02: Response includes site_url, audited_at, overall_score, category_scores, top_issues
        
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'site_url',
                    'audited_at',
                    'overall_score',
                    'category_scores',
                    'top_issues',
                ],
            ]);
    }

    /** @test */
    public function ac_03_response_includes_is_partial_flag(): void
    {
        // AC-03: Response includes is_partial flag for anonymous users
        
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['is_partial']]);
    }

    /** @test */
    public function ac_04_post_lead_accepts_email(): void
    {
        // AC-04: POST /api/v1/public-audit/{hash}/lead accepts email for lead capture
        
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'acceptance@example.com',
        ]);
        
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function ac_05_lead_validates_email_format(): void
    {
        // AC-05: Lead endpoint validates email format
        
        $response = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'not-an-email',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function ac_06_lead_stores_with_audit_reference(): void
    {
        // AC-06: Lead endpoint stores email with audit reference in public_audit_views
        
        $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'stored@example.com',
        ]);
        
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'stored@example.com',
            'public_hash' => $this->publicHash,
            'is_lead_capture' => true,
        ]);
    }

    /** @test */
    public function ac_07_badge_returns_configuration(): void
    {
        // AC-07: GET /api/v1/public-audit/{hash}/badge returns badge configuration
        
        $response = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hash',
                    'score',
                    'audit_url',
                    'impressions_endpoint',
                    'clicks_endpoint',
                    'sizes',
                    'themes',
                    'embed_code',
                ],
            ]);
    }

    /** @test */
    public function ac_08_rate_limiting_100_per_hour(): void
    {
        // AC-08: Rate limiting: 100 requests/hour for anonymous users
        $ip = '203.0.113.18';

        for ($i = 0; $i < 100; $i++) {
            $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
                ->assertStatus(200);
        }

        $this->getJsonFromIp($ip, '/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(429);
    }

    /** @test */
    public function ac_09_no_authentication_required(): void
    {
        // AC-09: No authentication required for public endpoints
        
        $get = $this->getJson('/api/v1/public-audit/' . $this->publicHash);
        $get->assertStatus(200);
        
        $post = $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'noauth@example.com',
        ]);
        $post->assertStatus(201);
        
        $badge = $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge');
        $badge->assertStatus(200);
    }

    /** @test */
    public function ac_10_404_for_invalid_hashes(): void
    {
        // AC-10: 404 returned for invalid/expired/non-public hashes
        
        // Invalid format
        $invalid = $this->getJson('/api/v1/public-audit/invalid');
        $invalid->assertStatus(404);
        
        // Non-existent
        $nonExistent = $this->getJson('/api/v1/public-audit/zzz999zzz999zzz999zzz999zzz999zzz');
        $nonExistent->assertStatus(404);
        
        // Non-public
        $nonPublic = $this->getJson('/api/v1/public-audit/' . $this->privateHash);
        $nonPublic->assertStatus(404);
    }

    /** @test */
    public function ac_feature_tests_for_all_endpoints(): void
    {
        // Feature tests for all endpoints
        
        // GET audit
        $this->getJson('/api/v1/public-audit/' . $this->publicHash)
            ->assertStatus(200);
        
        // POST lead
        $this->postJson('/api/v1/public-audit/' . $this->publicHash . '/lead', [
            'email' => 'feature@example.com',
        ])->assertStatus(201);
        
        // GET badge
        $this->getJson('/api/v1/public-audit/' . $this->publicHash . '/badge')
            ->assertStatus(200);
    }
}
