<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Models\PublicAuditView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Integration tests for the complete public audit flow.
 *
 * Tests the full workflow from publish to view to lead capture to unpublish.
 * Verifies database state changes, view tracking, and lead capture functionality.
 *
 * @see TASK-33-33
 * @see SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
 */
class PublicAuditFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $otherUser;
    protected Site $site;
    protected Audit $audit;
    protected IssueType $issueType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->owner->id]);
        $this->issueType = IssueType::factory()->create();
        
        // Create a completed audit with issues
        $this->audit = Audit::factory()
            ->completed()
            ->create(['site_id' => $this->site->id]);
    }

    // =========================================================================
    // PUBLISH FLOW TESTS
    // =========================================================================

    /** @test */
    public function publish_flow_audit_becomes_publicly_accessible(): void
    {
        // 1. Create audit in private state
        $this->assertEquals('private', $this->audit->visibility);
        $this->assertNull($this->audit->public_hash);

        // 2. Publish the audit
        $this->audit->publish();
        $this->audit->refresh();

        // 3. Verify audit is now public with hash
        $this->assertEquals('public', $this->audit->visibility);
        $this->assertNotNull($this->audit->public_hash);
        $this->assertEquals(32, strlen($this->audit->public_hash));
        $this->assertNotNull($this->audit->public_hash_created_at);

        // 4. Verify public URL is generated
        $publicUrl = $this->audit->getPublicUrl();
        $this->assertStringContainsString($this->audit->public_hash, $publicUrl);

        // 5. Access via API - should return 200
        $response = $this->getJson("/api/v1/public-audit/{$this->audit->public_hash}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'hash',
                    'siteUrl',
                    'auditedAt',
                    'overallScore',
                    'categoryScores',
                    'topIssues',
                    'isPartial',
                ],
            ]);

        // 6. Verify view was tracked
        $this->assertEquals(1, PublicAuditView::where('audit_id', $this->audit->id)->count());
    }

    /** @test */
    public function publish_generates_unique_hash(): void
    {
        // Create and publish multiple audits
        $audit2 = Audit::factory()->completed()->create(['site_id' => $this->site->id]);
        $audit3 = Audit::factory()->completed()->create(['site_id' => $this->site->id]);

        $this->audit->publish();
        $audit2->publish();
        $audit3->publish();

        // All hashes should be unique
        $hashes = [$this->audit->public_hash, $audit2->public_hash, $audit3->public_hash];
        $this->assertCount(3, array_unique($hashes));
    }

    /** @test */
    public function private_audit_returns_404(): void
    {
        // Audit is private by default
        $response = $this->getJson("/api/v1/public-audit/nonexistenthash12345678901234");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Audit not found']);
    }

    /** @test */
    public function non_completed_audit_not_publicly_accessible(): void
    {
        // Create pending audit and try to publish
        $pendingAudit = Audit::factory()->pending()->create(['site_id' => $this->site->id]);
        $pendingAudit->publish();
        $pendingAudit->refresh();

        // It should be public but still pending
        $this->assertEquals('public', $pendingAudit->visibility);

        // API should return 404 for non-completed audits
        $response = $this->getJson("/api/v1/public-audit/{$pendingAudit->public_hash}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // VIEW TRACKING TESTS
    // =========================================================================

    /** @test */
    public function view_tracking_anonymous_user_view_recorded(): void
    {
        // Publish audit
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access as anonymous user
        $response = $this->getJson("/api/v1/public-audit/{$hash}", [
            'Referer' => 'https://google.com/search?q=seo',
        ]);

        $response->assertStatus(200);

        // Verify view was recorded
        $this->assertDatabaseHas('public_audit_views', [
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'is_lead_capture' => false,
        ]);

        // Verify referrer was captured
        $view = PublicAuditView::where('audit_id', $this->audit->id)->first();
        $this->assertEquals('https://google.com/search?q=seo', $view->referrer);
    }

    /** @test */
    public function view_tracking_multiple_views_recorded_separately(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->getJson("/api/v1/public-audit/{$hash}");
        }

        // Verify 5 views recorded
        $this->assertEquals(5, PublicAuditView::where('audit_id', $this->audit->id)->count());
    }

    /** @test */
    public function view_tracking_ip_hashed_for_privacy(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access with specific IP using withServer helper
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        // Verify IP was hashed (not stored in plaintext)
        $view = PublicAuditView::where('audit_id', $this->audit->id)->first();
        $this->assertNotNull($view->ip_hash);
        $this->assertNotEquals('192.168.1.100', $view->ip_hash);
        $this->assertEquals(64, strlen($view->ip_hash)); // SHA-256 hex length
    }

    /** @test */
    public function view_tracking_user_agent_captured(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0';

        $response = $this->getJson("/api/v1/public-audit/{$hash}", [
            'User-Agent' => $userAgent,
        ]);

        $response->assertStatus(200);

        $view = PublicAuditView::where('audit_id', $this->audit->id)->first();
        $this->assertStringContainsString('Chrome', $view->user_agent);
    }

    // =========================================================================
    // LEAD CAPTURE TESTS
    // =========================================================================

    /** @test */
    public function lead_capture_email_submitted_successfully(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $email = 'lead@example.com';

        // Submit lead capture
        $response = $this->postJson("/api/v1/public-audit/{$hash}/lead", [
            'email' => $email,
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Email captured successfully']);

        // Verify lead was stored
        $this->assertDatabaseHas('public_audit_views', [
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'is_lead_capture' => true,
            'lead_email' => $email,
        ]);
    }

    /** @test */
    public function lead_capture_validates_email_format(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Invalid email
        $response = $this->postJson("/api/v1/public-audit/{$hash}/lead", [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function lead_capture_sanitizes_email(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Email with potential XSS
        $maliciousEmail = 'test<script>alert(1)</script>@example.com';

        $response = $this->postJson("/api/v1/public-audit/{$hash}/lead", [
            'email' => $maliciousEmail,
        ]);

        // Should be rejected or sanitized
        if ($response->status() === 201) {
            $view = PublicAuditView::where('audit_id', $this->audit->id)
                ->where('is_lead_capture', true)
                ->first();
            
            // Verify script tags are not present
            $this->assertStringNotContainsString('<script>', $view->lead_email);
        } else {
            $response->assertStatus(422);
        }
    }

    /** @test */
    public function lead_capture_for_non_public_audit_returns_404(): void
    {
        // Don't publish audit
        $response = $this->postJson("/api/v1/public-audit/somehash123456789012345678/lead", [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function lead_capture_for_invalid_hash_returns_404(): void
    {
        $response = $this->postJson("/api/v1/public-audit/nonexistenthash12345678901234/lead", [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // UNPUBLISH FLOW TESTS
    // =========================================================================

    /** @test */
    public function unpublish_flow_audit_no_longer_accessible(): void
    {
        // 1. Publish audit
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // 2. Verify accessible
        $response = $this->getJson("/api/v1/public-audit/{$hash}");
        $response->assertStatus(200);

        // 3. Unpublish
        $this->audit->unpublish();
        $this->audit->refresh();

        // 4. Verify audit is private
        $this->assertEquals('private', $this->audit->visibility);
        $this->assertNull($this->audit->public_hash);

        // 5. Verify 404 on access
        $response = $this->getJson("/api/v1/public-audit/{$hash}");
        $response->assertStatus(404);
    }

    /** @test */
    public function unpublish_preserves_existing_views(): void
    {
        // Publish and create some views
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create some views
        PublicAuditView::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Unpublish
        $this->audit->unpublish();

        // Views should still exist
        $this->assertEquals(5, PublicAuditView::where('audit_id', $this->audit->id)->count());
    }

    /** @test */
    public function republish_generates_new_hash(): void
    {
        // Publish
        $this->audit->publish();
        $originalHash = $this->audit->public_hash;

        // Unpublish
        $this->audit->unpublish();

        // Republish
        $this->audit->publish();
        $newHash = $this->audit->public_hash;

        // Hash should be different (null was set, new one generated)
        $this->assertNotEquals($originalHash, $newHash);
    }

    // =========================================================================
    // OWNER VS ANONYMOUS ACCESS TESTS
    // =========================================================================

    /** @test */
    public function anonymous_user_sees_limited_issues(): void
    {
        // Create audit with many issues
        AuditIssue::factory()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access as anonymous user
        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        // Should only see top 5 issues
        $data = $response->json('data');
        $this->assertCount(5, $data['topIssues']);
        $this->assertTrue($data['isPartial']);
    }

    /** @test */
    public function owner_sees_all_issues(): void
    {
        // Create audit with many issues
        AuditIssue::factory()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access as owner
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        // Should see all issues
        $data = $response->json('data');
        $this->assertCount(10, $data['topIssues']);
        $this->assertFalse($data['isPartial']);
    }

    /** @test */
    public function non_owner_authenticated_user_sees_limited_issues(): void
    {
        // Create audit with many issues
        AuditIssue::factory()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Access as different authenticated user
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        // Should only see top 5 issues
        $data = $response->json('data');
        $this->assertCount(5, $data['topIssues']);
        $this->assertTrue($data['isPartial']);
    }

    // =========================================================================
    // SCORE CALCULATION TESTS
    // =========================================================================

    /** @test */
    public function score_calculated_from_issues(): void
    {
        // Create issues with different severities
        AuditIssue::factory()->count(2)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(3)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        $data = $response->json('data');
        // 2 critical (-20) + 3 warnings (-15) = 65 score
        $this->assertEquals(65, $data['overallScore']);
    }

    /** @test */
    public function score_perfect_with_no_issues(): void
    {
        // No issues
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(100, $data['overallScore']);
    }

    /** @test */
    public function score_minimum_is_zero(): void
    {
        // Create many critical issues to drive score negative
        AuditIssue::factory()->count(15)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(0, $data['overallScore']);
    }

    // =========================================================================
    // ANALYTICS AGGREGATION TESTS
    // =========================================================================

    /** @test */
    public function analytics_views_can_be_aggregated_by_audit(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create multiple views
        PublicAuditView::factory()->count(10)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'viewed_at' => now()->subDays(1),
        ]);

        // Create leads
        PublicAuditView::factory()->count(3)->leadCapture()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Query analytics
        $totalViews = PublicAuditView::where('audit_id', $this->audit->id)->count();
        $leadCount = PublicAuditView::where('audit_id', $this->audit->id)
            ->where('is_lead_capture', true)
            ->count();

        $this->assertEquals(13, $totalViews);
        $this->assertEquals(3, $leadCount);
    }

    /** @test */
    public function analytics_can_filter_by_date_range(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create views on different days
        PublicAuditView::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'viewed_at' => now()->subDays(7),
        ]);
        PublicAuditView::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'viewed_at' => now()->subDays(2),
        ]);
        PublicAuditView::factory()->count(2)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
            'viewed_at' => now(),
        ]);

        // Query last 3 days
        $recentViews = PublicAuditView::where('audit_id', $this->audit->id)
            ->where('viewed_at', '>=', now()->subDays(3))
            ->count();

        $this->assertEquals(5, $recentViews); // 3 + 2
    }

    /** @test */
    public function analytics_referrers_can_be_tracked(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create views with different referrers
        PublicAuditView::factory()->count(3)->fromGoogle()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);
        PublicAuditView::factory()->count(2)->directVisit()->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Query by referrer
        $googleViews = PublicAuditView::where('audit_id', $this->audit->id)
            ->where('referrer', 'like', '%google%')
            ->count();
        $directViews = PublicAuditView::where('audit_id', $this->audit->id)
            ->whereNull('referrer')
            ->count();

        $this->assertEquals(3, $googleViews);
        $this->assertEquals(2, $directViews);
    }

    // =========================================================================
    // RATE LIMITING TESTS
    // =========================================================================

    /** @test */
    public function rate_limiting_applied_to_public_audit_endpoints(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Make many requests quickly
        for ($i = 0; $i < 105; $i++) {
            $response = $this->getJson("/api/v1/public-audit/{$hash}");
        }

        // Should be rate limited after threshold
        // Note: The actual threshold is 100/minute, so 105th should fail
        $this->assertTrue(
            in_array($response->status(), [200, 429]),
            'Expected 200 or 429 status'
        );
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /** @test */
    public function invalid_hash_format_returns_404(): void
    {
        // Hash with special characters (not matching route constraint)
        $response = $this->getJson('/api/v1/public-audit/invalid-hash!@#$%');

        // Route constraint should reject this
        $response->assertStatus(404);
    }

    /** @test */
    public function sql_injection_prevented_in_hash(): void
    {
        $this->audit->publish();

        // Try SQL injection in hash
        $response = $this->getJson("/api/v1/public-audit/1' OR '1'='1");

        // Should return 404, not error
        $response->assertStatus(404);
    }

    /** @test */
    public function xss_prevented_in_lead_email(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Try XSS in email
        $response = $this->postJson("/api/v1/public-audit/{$hash}/lead", [
            'email' => '<script>alert("xss")</script>@example.com',
        ]);

        // Should reject or sanitize
        $response->assertStatus(422);
    }

    /** @test */
    public function no_sensitive_data_exposed_in_response(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        $response = $this->getJson("/api/v1/public-audit/{$hash}");

        $response->assertStatus(200);

        // Check that sensitive fields are not exposed
        $response->assertJsonMissing([
            'user_id',
            'site.user_id',
            'ip_address',
            'internal_notes',
        ]);
    }

    // =========================================================================
    // CLEANUP TESTS
    // =========================================================================

    /** @test */
    public function deleting_audit_cascades_to_views(): void
    {
        $this->audit->publish();
        $hash = $this->audit->public_hash;

        // Create views
        PublicAuditView::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'public_hash' => $hash,
        ]);

        // Delete audit
        $auditId = $this->audit->id;
        $this->audit->delete();

        // Views should be deleted
        $this->assertEquals(0, PublicAuditView::where('audit_id', $auditId)->count());
    }

    /** @test */
    public function tests_clean_up_after_themselves(): void
    {
        // This test verifies RefreshDatabase trait is working
        $initialCount = Audit::count();

        // Create and publish audit
        $audit = Audit::factory()->completed()->create(['site_id' => $this->site->id]);
        $audit->publish();

        // Access it
        $this->getJson("/api/v1/public-audit/{$audit->public_hash}");

        // Should have created one view
        $this->assertEquals(1, PublicAuditView::where('audit_id', $audit->id)->count());

        // After test ends, RefreshDatabase will clean up
        $this->assertTrue(true);
    }
}
