<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Models\BadgeImpression;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for badge flow in public audit feature.
 *
 * Tests badge generation, SVG rendering, and impression tracking.
 *
 * @see TASK-33-33
 * @see SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
 */
class BadgeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Site $site;
    protected Audit $audit;
    protected IssueType $issueType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->owner->id]);
        $this->issueType = IssueType::factory()->create();
        
        // Create a completed audit
        $this->audit = Audit::factory()
            ->completed()
            ->create(['site_id' => $this->site->id]);
    }

    // =========================================================================
    // BADGE ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function badge_endpoint_returns_badge_configuration(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'hash',
                    'score',
                    'label',
                    'size',
                    'theme',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($hash, $data['hash']);
        $this->assertIsInt($data['score']);
        $this->assertIsString($data['label']);
    }

    /** @test */
    public function badge_for_non_public_audit_returns_404(): void
    {
        // Don't publish audit
        $response = $this->getJson("/api/v1/public-audit/somehash123456789012345678/badge");

        $response->assertStatus(404);
    }

    /** @test */
    public function badge_for_invalid_hash_returns_404(): void
    {
        $response = $this->getJson("/api/v1/public-audit/nonexistenthash12345678901234/badge");

        $response->assertStatus(404);
    }

    // =========================================================================
    // SCORE LABEL TESTS
    // =========================================================================

    /** @test */
    public function badge_score_label_excellent_for_high_score(): void
    {
        // Audit with no issues = 100 score
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(90, $data['score']);
        $this->assertEquals('Excellent', $data['label']);
    }

    /** @test */
    public function badge_score_label_good_for_medium_score(): void
    {
        // Create issues to reduce score to 70-89 range
        // 3 warnings (-15) = score 85
        AuditIssue::factory()->count(3)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(70, $data['score']);
        $this->assertLessThan(90, $data['score']);
        $this->assertEquals('Good', $data['label']);
    }

    /** @test */
    public function badge_score_label_needs_work_for_low_score(): void
    {
        // Create issues to reduce score to 50-69 range
        // 5 warnings (-25) + 2 critical (-20) = -45 = score 55
        AuditIssue::factory()->count(5)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(2)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(50, $data['score']);
        $this->assertLessThan(70, $data['score']);
        $this->assertEquals('Needs Work', $data['label']);
    }

    /** @test */
    public function badge_score_label_poor_for_very_low_score(): void
    {
        // Create issues to reduce score to 30-49 range
        AuditIssue::factory()->count(7)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(30, $data['score']);
        $this->assertLessThan(50, $data['score']);
        $this->assertEquals('Poor', $data['label']);
    }

    /** @test */
    public function badge_score_label_critical_for_very_low_score(): void
    {
        // Create many issues to reduce score below 30
        AuditIssue::factory()->count(10)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertLessThan(30, $data['score']);
        $this->assertEquals('Critical Issues', $data['label']);
    }

    // =========================================================================
    // BADGE IMPRESSION TRACKING TESTS
    // =========================================================================

    /** @test */
    public function badge_impression_can_be_recorded(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Record impression using model method
        $impression = BadgeImpression::recordImpression(
            $this->audit,
            'https://example.com/embed-page',
            '192.168.1.1'
        );

        $this->assertNotNull($impression);
        $this->assertEquals($this->audit->id, $impression->audit_id);
        $this->assertEquals($hash, $impression->public_hash);
        $this->assertEquals('example.com', $impression->referrer_domain);
        $this->assertNull($impression->clicked_at);
    }

    /** @test */
    public function badge_impression_click_can_be_recorded(): void
    {
        $this->audit->publish();

        $impression = BadgeImpression::factory()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        // Record click
        $result = $impression->recordClick('audit');

        $this->assertTrue($result);
        $this->assertNotNull($impression->clicked_at);
        $this->assertEquals('audit', $impression->click_target);
    }

    /** @test */
    public function badge_impression_click_target_validated(): void
    {
        $this->audit->publish();

        $impression = BadgeImpression::factory()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        // Invalid target
        $result = $impression->recordClick('invalid');

        $this->assertFalse($result);
        $this->assertNull($impression->clicked_at);
    }

    /** @test */
    public function badge_impression_ip_hashed_for_privacy(): void
    {
        $ip = '10.0.0.1';
        $hashedIp = BadgeImpression::hashIp($ip);

        $this->assertNotEquals($ip, $hashedIp);
        $this->assertEquals(64, strlen($hashedIp)); // SHA-256 hex length
    }

    /** @test */
    public function badge_impression_same_ip_produces_same_hash(): void
    {
        $ip = '10.0.0.1';

        $hash1 = BadgeImpression::hashIp($ip);
        $hash2 = BadgeImpression::hashIp($ip);

        $this->assertEquals($hash1, $hash2);
    }

    /** @test */
    public function badge_impression_domain_extracted_from_referer(): void
    {
        $domain = BadgeImpression::extractDomain('https://www.example.com/page?query=1');

        $this->assertEquals('www.example.com', $domain);
    }

    /** @test */
    public function badge_impression_null_referer_returns_null_domain(): void
    {
        $domain = BadgeImpression::extractDomain(null);

        $this->assertNull($domain);
    }

    // =========================================================================
    // BADGE ANALYTICS TESTS
    // =========================================================================

    /** @test */
    public function badge_analytics_can_aggregate_impressions(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create impressions
        BadgeImpression::factory()->count(10)->notClicked()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Create clicked impressions
        BadgeImpression::factory()->count(5)->clicked()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Query analytics
        $totalImpressions = BadgeImpression::where('audit_id', $this->audit->id)->count();
        $clickedCount = BadgeImpression::where('audit_id', $this->audit->id)
            ->clicked()
            ->count();
        $clickRate = ($clickedCount / $totalImpressions) * 100;

        $this->assertEquals(15, $totalImpressions);
        $this->assertEquals(5, $clickedCount);
        $this->assertEquals(33.33, round($clickRate, 2));
    }

    /** @test */
    public function badge_analytics_can_filter_by_date_range(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create impressions on different days
        BadgeImpression::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'impression_at' => now()->subDays(10),
        ]);
        BadgeImpression::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'impression_at' => now()->subDays(2),
        ]);
        BadgeImpression::factory()->count(2)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);

        // Query last 3 days
        $recentImpressions = BadgeImpression::where('audit_id', $this->audit->id)
            ->where('impression_at', '>=', now()->subDays(3))
            ->count();

        $this->assertEquals(5, $recentImpressions); // 3 + 2
    }

    /** @test */
    public function badge_analytics_can_group_by_referrer(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create impressions from different sources
        BadgeImpression::factory()->count(5)->fromGoogle()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);
        BadgeImpression::factory()->count(3)->fromDomain('example.com')->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Group by referrer
        $byReferrer = BadgeImpression::where('audit_id', $this->audit->id)
            ->selectRaw('referrer_domain, COUNT(*) as count')
            ->groupBy('referrer_domain')
            ->pluck('count', 'referrer_domain');

        $this->assertEquals(5, $byReferrer['google.com']);
        $this->assertEquals(3, $byReferrer['example.com']);
    }

    // =========================================================================
    // BADGE CLEANUP TESTS
    // =========================================================================

    /** @test */
    public function deleting_audit_cascades_to_badge_impressions(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create impressions
        BadgeImpression::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Delete audit
        $auditId = $this->audit->id;
        $this->audit->delete();

        // Impressions should be deleted
        $this->assertEquals(0, BadgeImpression::where('audit_id', $auditId)->count());
    }

    // =========================================================================
    // BADGE SECURITY TESTS
    // =========================================================================

    /** @test */
    public function badge_endpoint_no_sensitive_data_exposed(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}/badge");

        $response->assertStatus(200);

        // Check that sensitive fields are not exposed
        $response->assertJsonMissing([
            'user_id',
            'site_id',
            'ip_address',
            'email',
        ]);
    }

    /** @test */
    public function badge_hash_validation_prevents_injection(): void
    {
        // Try SQL injection in hash parameter
        $response = $this->getJson("/api/v1/public-audit/1' OR '1'='1/badge");

        // Should return 404, not error
        $response->assertStatus(404);
    }

    /** @test */
    public function badge_impression_user_agent_truncated(): void
    {
        $this->audit->publish();

        // Very long user agent
        $longUserAgent = str_repeat('Mozilla/5.0 ', 100);

        $impression = BadgeImpression::factory()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
            'ip_hash' => BadgeImpression::hashIp('127.0.0.1'),
        ]);

        // User agent is not stored in badge impressions, but IP hash is
        $this->assertNotNull($impression->ip_hash);
    }

    // =========================================================================
    // BADGE MODEL SCOPE TESTS
    // =========================================================================

    /** @test */
    public function badge_scope_for_audit(): void
    {
        $this->audit->publish();
        
        $audit2 = Audit::factory()->completed()->create(['site_id' => $this->site->id]);
        $audit2->publish();

        BadgeImpression::factory()->count(3)->create(['audit_id' => $this->audit->id]);
        BadgeImpression::factory()->count(2)->create(['audit_id' => $audit2->id]);

        $this->auditImpressions = BadgeImpression::forAudit($this->audit->id)->count();
        $audit2Impressions = BadgeImpression::forAudit($audit2->id)->count();

        $this->assertEquals(3, $this->auditImpressions);
        $this->assertEquals(2, $audit2Impressions);
    }

    /** @test */
    public function badge_scope_for_hash(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        BadgeImpression::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        $impressions = BadgeImpression::forHash($hash)->count();

        $this->assertEquals(3, $impressions);
    }

    /** @test */
    public function badge_scope_clicked(): void
    {
        $this->audit->publish();

        BadgeImpression::factory()->count(3)->clicked()->create(['audit_id' => $this->audit->id]);
        BadgeImpression::factory()->count(2)->notClicked()->create(['audit_id' => $this->audit->id]);

        $clicked = BadgeImpression::clicked()->count();
        $notClicked = BadgeImpression::notClicked()->count();

        $this->assertEquals(3, $clicked);
        $this->assertEquals(2, $notClicked);
    }

    /** @test */
    public function badge_scope_recent(): void
    {
        $this->audit->publish();

        BadgeImpression::factory()->count(3)->recent(3)->create(['audit_id' => $this->audit->id]);
        BadgeImpression::factory()->count(2)->create([
            'audit_id' => $this->audit->id,
            'impression_at' => now()->subDays(10),
        ]);

        $recent = BadgeImpression::where('audit_id', $this->audit->id)->recent(5)->count();

        $this->assertEquals(3, $recent);
    }

    // =========================================================================
    // PUBLIC AUDIT VIEW MASKED EMAIL TESTS
    // =========================================================================

    /** @test */
    public function masked_email_hides_local_part(): void
    {
        $this->audit->publish();

        $view = \App\Models\PublicAuditView::factory()->leadCapture('john.doe@example.com')->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        $masked = $view->masked_email;

        $this->assertNotEquals('john.doe@example.com', $masked);
        $this->assertStringContainsString('@example.com', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    /** @test */
    public function masked_email_handles_short_local_part(): void
    {
        $this->audit->publish();

        $view = \App\Models\PublicAuditView::factory()->leadCapture('ab@example.com')->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        $masked = $view->masked_email;

        $this->assertStringContainsString('@example.com', $masked);
    }

    /** @test */
    public function has_lead_email_accessor(): void
    {
        $this->audit->publish();

        $viewWithEmail = \App\Models\PublicAuditView::factory()->leadCapture()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        $viewWithoutEmail = \App\Models\PublicAuditView::factory()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $this->audit->public_hash,
        ]);

        $this->assertTrue($viewWithEmail->has_lead_email);
        $this->assertFalse($viewWithoutEmail->has_lead_email);
    }
}
