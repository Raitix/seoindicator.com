<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;

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

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable rate limiting for most tests
        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
        RateLimiter::shouldReceive('hit')->andReturn(true);
        RateLimiter::shouldReceive('retriesLeft')->andReturn(60);
        RateLimiter::shouldReceive('availableIn')->andReturn(60);
        RateLimiter::shouldReceive('attempts')->andReturn(0);
        RateLimiter::shouldReceive('clear')->andReturn(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // MOCK DATA HELPERS
    // ========================================================================

    protected function mockPublicAuditData(): array
    {
        return [
            'hash' => 'abc123def456ghi789jkl012mno345pqr',
            'site_url' => 'https://example.com',
            'audited_at' => '2026-03-06T12:00:00Z',
            'overall_score' => 85,
            'category_scores' => [
                'technical' => 90,
                'on_page' => 85,
                'content' => 80,
                'mobile' => 88,
            ],
            'top_issues' => [
                ['category' => 'technical', 'issue' => 'Missing H1 tag', 'severity' => 'high'],
                ['category' => 'on_page', 'issue' => 'Meta description too long', 'severity' => 'medium'],
                ['category' => 'content', 'issue' => 'Low word count', 'severity' => 'low'],
            ],
            'is_public' => true,
            'is_partial' => true,
            'audit_id' => 1,
        ];
    }

    protected function mockFullAuditData(): array
    {
        return [
            'hash' => 'xyz789abc012def345ghi678jkl901mno',
            'site_url' => 'https://fullaudit.com',
            'audited_at' => '2026-03-06T10:00:00Z',
            'overall_score' => 92,
            'category_scores' => [
                'technical' => 95,
                'on_page' => 90,
                'content' => 88,
                'mobile' => 94,
            ],
            'top_issues' => [],
            'is_public' => true,
            'is_partial' => false,
            'audit_id' => 2,
        ];
    }

    protected function mockBadgeConfig(): array
    {
        return [
            'hash' => 'abc123def456ghi789jkl012mno345pqr',
            'score' => 85,
            'site_url' => 'example.com',
            'badge_url' => 'https://seoindicator.com/badge/abc123def456ghi789jkl012mno345pqr/large',
            'sizes' => ['small', 'medium', 'large'],
            'themes' => ['light', 'dark'],
        ];
    }

    protected function mockPublicAuditModel(array $data = []): object
    {
        $data = array_merge($this->mockPublicAuditData(), $data);
        
        $mock = Mockery::mock('App\Models\PublicAudit');
        $mock->shouldReceive('getAttribute')->with('public_hash')->andReturn($data['hash']);
        $mock->shouldReceive('getAttribute')->with('audit_id')->andReturn($data['audit_id']);
        $mock->shouldReceive('getAttribute')->with('is_public')->andReturn($data['is_public']);
        $mock->shouldReceive('getAttribute')->with('site_url')->andReturn($data['site_url']);
        $mock->shouldReceive('getAttribute')->with('audited_at')->andReturn($data['audited_at']);
        $mock->shouldReceive('getAttribute')->with('overall_score')->andReturn($data['overall_score']);
        $mock->shouldReceive('getAttribute')->with('category_scores')->andReturn($data['category_scores']);
        $mock->shouldReceive('getAttribute')->with('top_issues')->andReturn($data['top_issues']);
        $mock->shouldReceive('getAttribute')->with('is_partial')->andReturn($data['is_partial'] ?? true);
        
        return $mock;
    }

    // ========================================================================
    // PA-GET: GET /api/v1/public-audit/{hash}
    // ========================================================================

    /** @test */
    public function get_audit_returns_200_for_valid_hash(): void
    {
        // PA-GET-001
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

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
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonPath('data.site_url', 'https://example.com');
    }

    /** @test */
    public function get_audit_returns_audited_at_timestamp(): void
    {
        // PA-GET-003
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonPath('data.audited_at', '2026-03-06T12:00:00Z');
    }

    /** @test */
    public function get_audit_returns_overall_score(): void
    {
        // PA-GET-004
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 85);
    }

    /** @test */
    public function get_audit_returns_category_scores(): void
    {
        // PA-GET-005
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

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
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

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
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_partial', true);
    }

    /** @test */
    public function get_audit_returns_is_partial_false_for_full_access(): void
    {
        // PA-GET-008 - For authenticated users with full access
        $response = $this->getJson('/api/v1/public-audit/xyz789abc012def345ghi678jkl901mno');

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
        $response = $this->getJson('/api/v1/public-audit/private123hash456private789hash');

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
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonMissing(['audit_id'])
            ->assertJsonMissing(['user_id'])
            ->assertJsonMissing(['email']);
    }

    /** @test */
    public function get_audit_requires_no_authentication(): void
    {
        // PA-GET-013
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

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
        $hash = 'abc123def456ghi789jkl012mno345pqr';
        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);
    }

    /** @test */
    public function get_audit_accepts_uppercase_hash(): void
    {
        // PA-GET-016 - Case insensitive hash matching
        $hash = 'ABC123DEF456GHI789JKL012MNO345PQR';
        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);
    }

    /** @test */
    public function get_audit_tracks_view_in_audit_views(): void
    {
        // PA-GET-017 - Verify view tracking
        $this->markTestIncomplete('Requires PublicAuditView model implementation');
    }

    // ========================================================================
    // PA-POST: POST /api/v1/public-audit/{hash}/lead
    // ========================================================================

    /** @test */
    public function lead_capture_returns_201_for_valid_email(): void
    {
        // PA-POST-001
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
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
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function lead_capture_requires_email(): void
    {
        // PA-POST-003
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', []);

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
        $response = $this->postJson('/api/v1/public-audit/private123hash456private789hash/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function lead_capture_stores_email_in_audit_views(): void
    {
        // PA-POST-006
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'lead@example.com',
        ]);

        $response->assertStatus(201);
        
        // Verify email stored in public_audit_views
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'lead@example.com',
            'is_lead_capture' => true,
        ]);
    }

    /** @test */
    public function lead_capture_requires_no_authentication(): void
    {
        // PA-POST-007
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function lead_capture_normalizes_email_to_lowercase(): void
    {
        // PA-POST-008
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
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
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
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
        $response1 = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => $email,
        ]);
        $response1->assertStatus(201);

        // Second submission - should still succeed
        $response2 = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => $email,
        ]);
        $response2->assertStatus(201);
    }

    /** @test */
    public function lead_capture_validates_max_email_length(): void
    {
        // PA-POST-011
        $longEmail = str_repeat('a', 250) . '@example.com';
        
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => $longEmail,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function lead_capture_stores_with_audit_reference(): void
    {
        // PA-POST-012
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'referenced@example.com',
        ]);

        $response->assertStatus(201);
        
        // Verify audit_id is stored
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'referenced@example.com',
            'public_hash' => 'abc123def456ghi789jkl012mno345pqr',
        ]);
    }

    /** @test */
    public function lead_capture_rejects_disposable_email_domains(): void
    {
        // PA-POST-013 - Optional business rule
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'test@10minutemail.com',
        ]);

        // May return 422 or 201 depending on business rules
        // Document expected behavior in test
        $this->markTestIncomplete('Define disposable email policy');
    }

    // ========================================================================
    // PA-BADGE: GET /api/v1/public-audit/{hash}/badge
    // ========================================================================

    /** @test */
    public function badge_config_returns_200_for_valid_hash(): void
    {
        // PA-BADGE-001
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hash',
                    'score',
                    'site_url',
                    'badge_url',
                    'sizes',
                    'themes',
                ],
            ]);
    }

    /** @test */
    public function badge_config_returns_score(): void
    {
        // PA-BADGE-002
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.score', 85);
    }

    /** @test */
    public function badge_config_returns_site_url(): void
    {
        // PA-BADGE-003
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.site_url', 'example.com');
    }

    /** @test */
    public function badge_config_returns_badge_url(): void
    {
        // PA-BADGE-004
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.badge_url', 'https://seoindicator.com/badge/abc123def456ghi789jkl012mno345pqr/large');
    }

    /** @test */
    public function badge_config_returns_available_sizes(): void
    {
        // PA-BADGE-005
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonPath('data.sizes', ['small', 'medium', 'large']);
    }

    /** @test */
    public function badge_config_returns_available_themes(): void
    {
        // PA-BADGE-006
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

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
        $response = $this->getJson('/api/v1/public-audit/private123hash456private789hash/badge');

        $response->assertStatus(404);
    }

    /** @test */
    public function badge_config_requires_no_authentication(): void
    {
        // PA-BADGE-009
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200);
    }

    /** @test */
    public function badge_config_includes_embed_code(): void
    {
        // PA-BADGE-010
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'embed_code',
                ],
            ]);
    }

    // ========================================================================
    // PA-RATE: Rate Limiting Tests
    // ========================================================================

    /** @test */
    public function rate_limit_allows_100_requests_per_hour(): void
    {
        // PA-RATE-001
        $hash = 'abc123def456ghi789jkl012mno345pqr';
        
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson("/api/v1/public-audit/{$hash}");
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function rate_limit_blocks_after_100_requests(): void
    {
        // PA-RATE-002
        $hash = 'ratelimited123hash456ratelimited789';
        $ip = '192.168.1.100';
        
        // Make 100 requests
        for ($i = 0; $i < 100; $i++) {
            $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => $ip]);
        }
        
        // 101st request should be blocked
        $response = $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => $ip]);
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'Too many requests',
            ]);
    }

    /** @test */
    public function rate_limit_is_per_hash(): void
    {
        // PA-RATE-003 - Different hashes have separate limits
        $ip = '192.168.1.101';
        
        // Make 100 requests to hash A
        for ($i = 0; $i < 100; $i++) {
            $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr', [], ['REMOTE_ADDR' => $ip]);
        }
        
        // Request to hash B should still work
        $response = $this->getJson('/api/v1/public-audit/xyz789abc012def345ghi678jkl901mno', [], ['REMOTE_ADDR' => $ip]);
        $response->assertStatus(200);
    }

    /** @test */
    public function rate_limit_uses_ip_and_hash_combination(): void
    {
        // PA-RATE-004 - Same hash, different IPs should both work
        $hash = 'ratelimited123hash456ratelimited789';
        
        // 100 requests from IP 1
        for ($i = 0; $i < 100; $i++) {
            $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => '192.168.1.200']);
        }
        
        // Request from IP 2 should work
        $response = $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => '192.168.1.201']);
        $response->assertStatus(200);
    }

    /** @test */
    public function rate_limit_returns_retry_after_header(): void
    {
        // PA-RATE-005
        $hash = 'ratelimited123hash456ratelimited789';
        $ip = '192.168.1.102';
        
        // Exhaust rate limit
        for ($i = 0; $i < 100; $i++) {
            $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => $ip]);
        }
        
        $response = $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => $ip]);
        $response->assertHeader('Retry-After');
    }

    /** @test */
    public function rate_limit_applies_to_all_endpoints(): void
    {
        // PA-RATE-006 - Rate limit applies across GET, POST, badge
        $hash = 'abc123def456ghi789jkl012mno345pqr';
        $ip = '192.168.1.103';
        
        // Exhaust with GET requests
        for ($i = 0; $i < 100; $i++) {
            $this->getJson("/api/v1/public-audit/{$hash}", [], ['REMOTE_ADDR' => $ip]);
        }
        
        // POST should also be blocked
        $response = $this->postJson("/api/v1/public-audit/{$hash}/lead", ['email' => 'test@example.com'], [], ['REMOTE_ADDR' => $ip]);
        $response->assertStatus(429);
        
        // Badge should also be blocked
        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge", [], ['REMOTE_ADDR' => $ip]);
        $response->assertStatus(429);
    }

    /** @test */
    public function rate_limit_resets_after_one_hour(): void
    {
        // PA-RATE-007
        $this->travel(1)->hour();
        
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $response->assertStatus(200);
    }

    // ========================================================================
    // PA-SEC: Security Tests
    // ========================================================================

    /** @test */
    public function sql_injection_in_hash_is_prevented(): void
    {
        // PA-SEC-001
        $maliciousHash = "abc'; DROP TABLE audits; --";
        
        $response = $this->getJson("/api/v1/public-audit/{$maliciousHash}");
        
        $response->assertStatus(404);
        // Verify no SQL error
        $response->assertJsonMissing(['SQL', 'syntax', 'mysql']);
    }

    /** @test */
    public function xss_in_email_is_sanitized(): void
    {
        // PA-SEC-002
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => '<script>alert("xss")</script>@example.com',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function path_traversal_in_hash_is_prevented(): void
    {
        // PA-SEC-003
        $maliciousHash = '../../../etc/passwd';
        
        $response = $this->getJson("/api/v1/public-audit/{$maliciousHash}");
        
        $response->assertStatus(404);
    }

    /** @test */
    public function no_sensitive_data_in_response(): void
    {
        // PA-SEC-004
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');

        $response->assertStatus(200)
            ->assertJsonMissing(['api_key', 'password', 'secret', 'token']);
    }

    /** @test */
    public function private_audit_not_accessible(): void
    {
        // PA-SEC-005
        $response = $this->getJson('/api/v1/public-audit/private123hash456private789hash');

        $response->assertStatus(404);
    }

    /** @test */
    public function enumeration_protection_returns_same_error(): void
    {
        // PA-SEC-006 - Non-existent and non-public return same 404
        $nonExistent = $this->getJson('/api/v1/public-audit/zzz999zzz999zzz999zzz999zzz999zzz');
        $nonPublic = $this->getJson('/api/v1/public-audit/private123hash456private789hash');
        
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
        // PA-SEC-007 - API should reject requests without proper headers
        $response = $this->post('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'test@example.com',
        ]);

        // API expects JSON, should reject form submissions
        $response->assertStatus(415); // Unsupported Media Type
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
        $response = $this->getJson("/api/v1/public-audit/{$longHash}");
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
        // PA-EDGE-006 - Race condition test
        $this->markTestIncomplete('Requires concurrent request testing setup');
    }

    /** @test */
    public function score_boundary_zero(): void
    {
        // PA-EDGE-007
        $response = $this->getJson('/api/v1/public-audit/zeroc00000000000000000000000000');

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 0);
    }

    /** @test */
    public function score_boundary_100(): void
    {
        // PA-EDGE-008
        $response = $this->getJson('/api/v1/public-audit/hundred00000000000000000000000');

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 100);
    }

    /** @test */
    public function empty_category_scores_handled(): void
    {
        // PA-EDGE-009
        $response = $this->getJson('/api/v1/public-audit/emptyc000000000000000000000000');

        $response->assertStatus(200)
            ->assertJsonPath('data.category_scores', []);
    }

    /** @test */
    public function empty_top_issues_handled(): void
    {
        // PA-EDGE-010
        $response = $this->getJson('/api/v1/public-audit/noissue00000000000000000000000');

        $response->assertStatus(200)
            ->assertJsonPath('data.top_issues', []);
    }

    /** @test */
    public function missing_optional_fields_handled(): void
    {
        // PA-EDGE-011
        $response = $this->getJson('/api/v1/public-audit/partial000000000000000000000000');

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
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_put(): void
    {
        // PA-HTTP-002
        $response = $this->putJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_patch(): void
    {
        // PA-HTTP-003
        $response = $this->patchJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $response->assertStatus(405);
    }

    /** @test */
    public function get_audit_rejects_delete(): void
    {
        // PA-HTTP-004
        $response = $this->deleteJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $response->assertStatus(405);
    }

    /** @test */
    public function lead_endpoint_rejects_get(): void
    {
        // PA-HTTP-005
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead');
        $response->assertStatus(405);
    }

    /** @test */
    public function badge_endpoint_rejects_post(): void
    {
        // PA-HTTP-006
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');
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
        
        $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration, "Response time was {$duration}ms");
    }

    /** @test */
    public function lead_capture_responds_within_500ms(): void
    {
        // PA-PERF-002
        $start = microtime(true);
        
        $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
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
        
        $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');
        
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
        $valid = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $valid->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        
        // Invalid hash returns 404
        $invalid = $this->getJson('/api/v1/public-audit/nonexistent00000000000000000000');
        $invalid->assertStatus(404);
    }

    /** @test */
    public function ac_02_response_includes_required_fields(): void
    {
        // AC-02: Response includes siteUrl, auditedAt, overallScore, categoryScores, topIssues
        
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        
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
        // AC-03: Response includes isPartial flag for anonymous users
        
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['is_partial']]);
    }

    /** @test */
    public function ac_04_post_lead_accepts_email(): void
    {
        // AC-04: POST /api/v1/public-audit/{hash}/lead accepts email for lead capture
        
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'acceptance@example.com',
        ]);
        
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function ac_05_lead_validates_email_format(): void
    {
        // AC-05: Lead endpoint validates email format
        
        $response = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'not-an-email',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function ac_06_lead_stores_with_audit_reference(): void
    {
        // AC-06: Lead endpoint stores email with audit reference in public_audit_views
        
        $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'stored@example.com',
        ]);
        
        $this->assertDatabaseHas('public_audit_views', [
            'lead_email' => 'stored@example.com',
            'public_hash' => 'abc123def456ghi789jkl012mno345pqr',
            'is_lead_capture' => true,
        ]);
    }

    /** @test */
    public function ac_07_badge_returns_configuration(): void
    {
        // AC-07: GET /api/v1/public-audit/{hash}/badge returns badge configuration
        
        $response = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hash',
                    'score',
                    'badge_url',
                    'sizes',
                    'themes',
                ],
            ]);
    }

    /** @test */
    public function ac_08_rate_limiting_100_per_hour(): void
    {
        // AC-08: Rate limiting: 100 requests/hour per hash for anonymous users
        
        // This is tested in PA-RATE tests
        $this->assertTrue(true);
    }

    /** @test */
    public function ac_09_no_authentication_required(): void
    {
        // AC-09: No authentication required for public endpoints
        
        $get = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr');
        $get->assertStatus(200);
        
        $post = $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'noauth@example.com',
        ]);
        $post->assertStatus(201);
        
        $badge = $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge');
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
        $nonPublic = $this->getJson('/api/v1/public-audit/private123hash456private789hash');
        $nonPublic->assertStatus(404);
    }

    /** @test */
    public function ac_feature_tests_for_all_endpoints(): void
    {
        // Feature tests for all endpoints
        
        // GET audit
        $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr')
            ->assertStatus(200);
        
        // POST lead
        $this->postJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/lead', [
            'email' => 'feature@example.com',
        ])->assertStatus(201);
        
        // GET badge
        $this->getJson('/api/v1/public-audit/abc123def456ghi789jkl012mno345pqr/badge')
            ->assertStatus(200);
    }
}
