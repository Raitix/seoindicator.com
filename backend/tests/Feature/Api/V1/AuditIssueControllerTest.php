<?php

namespace Tests\Feature\Api\V1;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TASK-14-14: AuditIssueController (Issue Management)
 *
 * Tests the REST API endpoints for listing audit issues and updating their status.
 * Covers filtering, pagination, authorization, and bulk operations.
 *
 * @see TASK-14-14
 * @see \App\Http\Controllers\Api\V1\AuditIssueController
 */
class AuditIssueControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherSite;
    protected Audit $audit;
    protected Audit $otherAudit;
    protected Audit $pendingAudit;
    protected IssueType $issueType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create sites owned by users
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);

        // Create completed audits
        $this->audit = Audit::factory()->completed()->create(['site_id' => $this->site->id]);
        $this->otherAudit = Audit::factory()->completed()->create(['site_id' => $this->otherSite->id]);

        // Create pending audit for authorization testing
        $this->pendingAudit = Audit::factory()->pending()->create(['site_id' => $this->site->id]);

        // Create issue type
        $this->issueType = IssueType::factory()->create([
            'category' => 'meta',
            'name' => 'Missing Title Tag',
        ]);
    }

    // =========================================================================
    // INDEX Tests - List Issues for Audit
    // =========================================================================

    /**
     * Test 1: List issues for audit - success
     */
    public function test_list_issues_for_audit(): void
    {
        AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test 2: List issues - empty list
     */
    public function test_list_issues_empty(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data', []);
    }

    /**
     * Test 3: List issues with pagination
     */
    public function test_list_issues_with_pagination(): void
    {
        AuditIssue::factory()->count(50)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?per_page=25");

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 50);
        $response->assertJsonCount(25, 'data');
    }

    /**
     * Test 4: List issues - filter by severity (critical)
     */
    public function test_list_issues_filter_by_severity_critical(): void
    {
        AuditIssue::factory()->count(3)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(2)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?severity=critical");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test 5: List issues - filter by severity (warning)
     */
    public function test_list_issues_filter_by_severity_warning(): void
    {
        AuditIssue::factory()->count(3)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(5)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?severity=warning");

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test 6: List issues - filter by severity (notice)
     */
    public function test_list_issues_filter_by_severity_notice(): void
    {
        AuditIssue::factory()->count(4)->notice()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(2)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?severity=notice");

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    /**
     * Test 7: List issues - filter by status (open)
     */
    public function test_list_issues_filter_by_status_open(): void
    {
        AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);
        AuditIssue::factory()->count(3)->fixed()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?status=open");

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test 8: List issues - filter by status (fixed)
     */
    public function test_list_issues_filter_by_status_fixed(): void
    {
        AuditIssue::factory()->count(3)->fixed()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?status=fixed");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test 9: List issues - filter by status (ignored)
     */
    public function test_list_issues_filter_by_status_ignored(): void
    {
        AuditIssue::factory()->count(2)->ignored()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?status=ignored");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test 10: List issues - filter by category
     */
    public function test_list_issues_filter_by_category(): void
    {
        $metaIssueType = IssueType::factory()->create(['category' => 'meta']);
        $contentIssueType = IssueType::factory()->create(['category' => 'content']);

        AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $metaIssueType->id,
        ]);
        AuditIssue::factory()->count(2)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $contentIssueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?category=meta");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test 11: List issues - search by URL
     */
    public function test_list_issues_search_by_url(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'url' => 'https://example.com/products',
        ]);
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'url' => 'https://example.com/about',
        ]);
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'url' => 'https://example.com/contact',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?search=products");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.url', 'https://example.com/products');
    }

    /**
     * Test 12: List issues - search by description
     */
    public function test_list_issues_search_by_description(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'description' => 'This page has a missing meta description tag',
        ]);
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'description' => 'The title tag is too long',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?search=meta");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test 13: List issues - sorting by created_at
     */
    public function test_list_issues_sorting_by_created_at(): void
    {
        $issue1 = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'created_at' => now()->subDays(2),
        ]);
        $issue2 = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'created_at' => now()->subDay(),
        ]);
        $issue3 = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?sort_by=created_at&sort_order=desc");

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $issue3->id);
        $response->assertJsonPath('data.1.id', $issue2->id);
        $response->assertJsonPath('data.2.id', $issue1->id);
    }

    /**
     * Test 14: List issues - sorting by severity
     */
    public function test_list_issues_sorting_by_severity(): void
    {
        AuditIssue::factory()->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->notice()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?sort_by=severity&sort_order=asc");

        $response->assertOk();
        // Critical should come first when sorting ascending (critical=1, warning=2, notice=3)
        $response->assertJsonPath('data.0.severity', 'critical');
    }

    /**
     * Test 15: List issues - includes severity counts in meta
     */
    public function test_list_issues_includes_severity_counts(): void
    {
        AuditIssue::factory()->count(10)->critical()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(25)->warning()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(15)->notice()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonPath('meta.severity_counts.critical', 10);
        $response->assertJsonPath('meta.severity_counts.warning', 25);
        $response->assertJsonPath('meta.severity_counts.notice', 15);
    }

    /**
     * Test 16: List issues - includes status counts in meta
     */
    public function test_list_issues_includes_status_counts(): void
    {
        AuditIssue::factory()->count(45)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);
        AuditIssue::factory()->count(3)->fixed()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->count(2)->ignored()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonPath('meta.status_counts.open', 45);
        $response->assertJsonPath('meta.status_counts.fixed', 3);
        $response->assertJsonPath('meta.status_counts.ignored', 2);
    }

    /**
     * Test 17: List issues - response structure
     */
    public function test_list_issues_response_structure(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'url',
                    'issue_type' => [
                        'id',
                        'name',
                        'category',
                    ],
                    'severity',
                    'description',
                    'recommendation',
                    'status',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'severity_counts',
                'status_counts',
            ],
        ]);
    }

    /**
     * Test 18: List issues - authorization denied for other user's audit
     */
    public function test_list_issues_authorization_denied_for_other_user(): void
    {
        AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->otherAudit->id}/issues");

        $response->assertStatus(403);
    }

    /**
     * Test 19: List issues - audit not found
     */
    public function test_list_issues_audit_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/audits/99999/issues');

        $response->assertStatus(404);
    }

    // =========================================================================
    // SHOW Tests - Get Single Issue
    // =========================================================================

    /**
     * Test 20: Get single issue - success
     */
    public function test_get_single_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $issue->id);
        $response->assertJsonPath('data.url', $issue->url);
        $response->assertJsonPath('data.severity', $issue->severity);
        $response->assertJsonPath('data.status', $issue->status);
    }

    /**
     * Test 21: Get single issue - includes issue_type details
     */
    public function test_get_single_issue_includes_issue_type(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('data.issue_type.id', $this->issueType->id);
        $response->assertJsonPath('data.issue_type.name', $this->issueType->name);
        $response->assertJsonPath('data.issue_type.category', $this->issueType->category);
        $response->assertJsonPath('data.issue_type.category_label', 'SEO Meta');
    }

    /**
     * Test 22: Get single issue - includes audit info
     */
    public function test_get_single_issue_includes_audit_info(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('data.audit.id', $this->audit->id);
        $response->assertJsonPath('data.audit.status', 'completed');
        $response->assertJsonPath('data.audit.pages_crawled', $this->audit->pages_crawled);
    }

    /**
     * Test 23: Get single issue - response structure
     */
    public function test_get_single_issue_response_structure(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'url',
                'issue_type' => [
                    'id',
                    'name',
                    'category',
                    'category_label',
                ],
                'severity',
                'description',
                'recommendation',
                'status',
                'created_at',
                'audit' => [
                    'id',
                    'status',
                    'pages_crawled',
                ],
            ],
        ]);
    }

    /**
     * Test 24: Get single issue - authorization denied
     */
    public function test_get_single_issue_authorization_denied(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 25: Get single issue - not found
     */
    public function test_get_single_issue_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issues/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // UPDATE Tests - Update Issue Status
    // =========================================================================

    /**
     * Test 26: Update issue status to fixed - success
     */
    public function test_update_issue_status_to_fixed(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'fixed',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $issue->id);
        $response->assertJsonPath('data.status', 'fixed');

        $this->assertDatabaseHas('audit_issues', [
            'id' => $issue->id,
            'status' => 'fixed',
        ]);
    }

    /**
     * Test 27: Update issue status to ignored - success
     */
    public function test_update_issue_status_to_ignored(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'ignored',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'ignored');

        $this->assertDatabaseHas('audit_issues', [
            'id' => $issue->id,
            'status' => 'ignored',
        ]);
    }

    /**
     * Test 28: Update issue status back to open - success
     */
    public function test_update_issue_status_to_open(): void
    {
        $issue = AuditIssue::factory()->fixed()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'open',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('audit_issues', [
            'id' => $issue->id,
            'status' => 'open',
        ]);
    }

    /**
     * Test 29: Update issue - response includes updated_at
     */
    public function test_update_issue_response_includes_updated_at(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'fixed',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'status',
                'updated_at',
            ],
        ]);
    }

    /**
     * Test 30: Update issue - validation error invalid status
     */
    public function test_update_issue_validation_error_invalid_status(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 31: Update issue - validation error missing status
     */
    public function test_update_issue_validation_error_missing_status(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", []);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 32: Update issue - authorization denied
     */
    public function test_update_issue_authorization_denied(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'fixed',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test 33: Update issue - not found
     */
    public function test_update_issue_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/issues/99999', [
                'status' => 'fixed',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Test 34: Update issue - denied for non-completed audit
     */
    public function test_update_issue_denied_for_non_completed_audit(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->pendingAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'fixed',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // BULK UPDATE Tests
    // =========================================================================

    /**
     * Test 35: Bulk update issues to fixed - success
     */
    public function test_bulk_update_issues_to_fixed(): void
    {
        $issues = AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'fixed',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.updated_count', 5);
        $response->assertJsonPath('data.message', '5 issues marked as fixed');

        foreach ($issueIds as $id) {
            $this->assertDatabaseHas('audit_issues', [
                'id' => $id,
                'status' => 'fixed',
            ]);
        }
    }

    /**
     * Test 36: Bulk update issues to ignored - success
     */
    public function test_bulk_update_issues_to_ignored(): void
    {
        $issues = AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'ignored',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.updated_count', 3);
        $response->assertJsonPath('data.message', '3 issues marked as ignored');

        foreach ($issueIds as $id) {
            $this->assertDatabaseHas('audit_issues', [
                'id' => $id,
                'status' => 'ignored',
            ]);
        }
    }

    /**
     * Test 37: Bulk update issues - partial update (some already fixed)
     */
    public function test_bulk_update_issues_partial_update(): void
    {
        $openIssues = AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);
        $fixedIssues = AuditIssue::factory()->count(2)->fixed()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $issueIds = $openIssues->merge($fixedIssues)->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'fixed',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.updated_count', 5);
    }

    /**
     * Test 38: Bulk update issues - validation error empty array
     */
    public function test_bulk_update_issues_validation_error_empty_array(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [],
                'status' => 'fixed',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 39: Bulk update issues - validation error max 100 items
     */
    public function test_bulk_update_issues_validation_error_max_items(): void
    {
        $issueIds = range(1, 101); // 101 items

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'fixed',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 40: Bulk update issues - validation error invalid status
     */
    public function test_bulk_update_issues_validation_error_invalid_status(): void
    {
        $issues = AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 41: Bulk update issues - validation error non-existent issue
     */
    public function test_bulk_update_issues_validation_error_non_existent_issue(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [1, 2, 99999],
                'status' => 'fixed',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test 42: Bulk update issues - authorization denied
     */
    public function test_bulk_update_issues_authorization_denied(): void
    {
        $issues = AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->otherAudit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'fixed',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test 43: Bulk update issues - denied for non-completed audit
     */
    public function test_bulk_update_issues_denied_for_non_completed_audit(): void
    {
        $issues = AuditIssue::factory()->count(3)->create([
            'audit_id' => $this->pendingAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->pendingAudit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'fixed',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test 44: Bulk update issues - ignores issues from other audits
     */
    public function test_bulk_update_issues_ignores_issues_from_other_audits(): void
    {
        $myIssue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        $otherIssue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [$myIssue->id, $otherIssue->id],
                'status' => 'fixed',
            ]);

        // Should only update issues belonging to this audit
        $response->assertOk();
        $response->assertJsonPath('data.updated_count', 1);

        $this->assertDatabaseHas('audit_issues', [
            'id' => $myIssue->id,
            'status' => 'fixed',
        ]);

        // Other issue should remain unchanged
        $this->assertDatabaseHas('audit_issues', [
            'id' => $otherIssue->id,
            'status' => 'open',
        ]);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    /**
     * Test 45: Unauthenticated access denied - list issues
     */
    public function test_unauthenticated_list_issues(): void
    {
        $response = $this->getJson("/api/v1/audits/{$this->audit->id}/issues");
        $response->assertStatus(401);
    }

    /**
     * Test 46: Unauthenticated access denied - get single issue
     */
    public function test_unauthenticated_get_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->getJson("/api/v1/issues/{$issue->id}");
        $response->assertStatus(401);
    }

    /**
     * Test 47: Unauthenticated access denied - update issue
     */
    public function test_unauthenticated_update_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->patchJson("/api/v1/issues/{$issue->id}", [
            'status' => 'fixed',
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test 48: Unauthenticated access denied - bulk update
     */
    public function test_unauthenticated_bulk_update(): void
    {
        $response = $this->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
            'issue_ids' => [1, 2, 3],
            'status' => 'fixed',
        ]);
        $response->assertStatus(401);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /**
     * Test 49: SQL injection protection in search
     */
    public function test_sql_injection_protection_in_search(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?search='; DROP TABLE audit_issues; --");

        // Should not cause an error
        $response->assertOk();
    }

    /**
     * Test 50: XSS protection in search
     */
    public function test_xss_protection_in_search(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?search=<script>alert(1)</script>");

        $response->assertOk();
    }

    /**
     * Test 51: IDOR protection - cannot access other user's issue
     */
    public function test_idor_protection_get_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 52: IDOR protection - cannot update other user's issue
     */
    public function test_idor_protection_update_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", [
                'status' => 'fixed',
            ]);

        $response->assertStatus(403);

        // Verify issue was NOT updated
        $this->assertDatabaseHas('audit_issues', [
            'id' => $issue->id,
            'status' => 'open',
        ]);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test 53: List issues - large page number returns empty
     */
    public function test_list_issues_large_page_number(): void
    {
        AuditIssue::factory()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?page=999");

        $response->assertOk();
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.current_page', 999);
    }

    /**
     * Test 54: List issues - per_page boundary validation
     */
    public function test_list_issues_per_page_boundary(): void
    {
        AuditIssue::factory()->count(150)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        // Test per_page=100 (max allowed)
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?per_page=100");

        $response->assertOk();
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test 55: Bulk update - single item in array
     */
    public function test_bulk_update_single_item(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [$issue->id],
                'status' => 'fixed',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.updated_count', 1);
        $response->assertJsonPath('data.message', '1 issue marked as fixed');
    }

    /**
     * Test 56: Bulk update - exactly 100 items (boundary)
     */
    public function test_bulk_update_exactly_100_items(): void
    {
        $issues = AuditIssue::factory()->count(100)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $issueIds = $issues->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issueIds,
                'status' => 'ignored',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.updated_count', 100);
    }

    /**
     * Test 57: Unicode in search query
     */
    public function test_unicode_in_search_query(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'description' => '日本語の説明文',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?search=日本語");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test 58: Very long URL in issue
     */
    public function test_very_long_url_in_issue(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 500);

        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'url' => $longUrl,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('data.url', $longUrl);
    }

    // =========================================================================
    // HTTP Method Tests
    // =========================================================================

    /**
     * Test 59: Wrong HTTP method for list issues
     */
    public function test_wrong_method_list_issues(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/audits/{$this->audit->id}/issues");
        $response->assertStatus(405);
    }

    /**
     * Test 60: Wrong HTTP method for update issue
     */
    public function test_wrong_method_update_issue(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/issues/{$issue->id}", ['status' => 'fixed']);
        $response->assertStatus(405);
    }

    /**
     * Test 61: Wrong HTTP method for bulk update
     */
    public function test_wrong_method_bulk_update(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [1],
                'status' => 'fixed',
            ]);
        $response->assertStatus(405);
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    /**
     * Test AC-01: AuditIssueController created with all endpoints
     */
    public function test_ac_all_endpoints_exist(): void
    {
        AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        // List endpoint
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");
        $response->assertOk();

        // Get single issue endpoint
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");
        $response->assertOk();

        // Update issue endpoint
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", ['status' => 'fixed']);
        $response->assertOk();

        // Bulk update endpoint
        $issues = AuditIssue::factory()->count(2)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issues->pluck('id')->toArray(),
                'status' => 'fixed',
            ]);
        $response->assertOk();
    }

    /**
     * Test AC-02: List endpoint with filtering and pagination
     */
    public function test_ac_list_with_filtering_and_pagination(): void
    {
        AuditIssue::factory()->critical()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);
        AuditIssue::factory()->warning()->count(10)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues?severity=critical&per_page=5");

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.total', 10);
    }

    /**
     * Test AC-03: Severity/status counts in meta response
     */
    public function test_ac_counts_in_meta(): void
    {
        AuditIssue::factory()->critical()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);
        AuditIssue::factory()->fixed()->count(3)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->audit->id}/issues");

        $response->assertOk();
        $response->assertJsonPath('meta.severity_counts.critical', 5);
        $response->assertJsonPath('meta.status_counts.open', 5);
        $response->assertJsonPath('meta.status_counts.fixed', 3);
    }

    /**
     * Test AC-04: Single issue detail endpoint
     */
    public function test_ac_single_issue_detail(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $issue->id);
        $response->assertJsonPath('data.issue_type.id', $this->issueType->id);
        $response->assertJsonPath('data.audit.id', $this->audit->id);
    }

    /**
     * Test AC-05: Status update endpoint working
     */
    public function test_ac_status_update(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", ['status' => 'fixed']);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'fixed');

        $this->assertDatabaseHas('audit_issues', [
            'id' => $issue->id,
            'status' => 'fixed',
        ]);
    }

    /**
     * Test AC-06: Bulk update endpoint working
     */
    public function test_ac_bulk_update(): void
    {
        $issues = AuditIssue::factory()->count(5)->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => $issues->pluck('id')->toArray(),
                'status' => 'ignored',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.updated_count', 5);
    }

    /**
     * Test AC-07: Authorization checks implemented
     */
    public function test_ac_authorization_checks(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->otherAudit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        // List - denied
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/audits/{$this->otherAudit->id}/issues");
        $response->assertStatus(403);

        // Get - denied
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issues/{$issue->id}");
        $response->assertStatus(403);

        // Update - denied
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", ['status' => 'fixed']);
        $response->assertStatus(403);

        // Bulk update - denied
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->otherAudit->id}/issues/bulk", [
                'issue_ids' => [$issue->id],
                'status' => 'fixed',
            ]);
        $response->assertStatus(403);
    }

    /**
     * Test AC-08: Request validation
     */
    public function test_ac_request_validation(): void
    {
        $issue = AuditIssue::factory()->create([
            'audit_id' => $this->audit->id,
            'issue_type_id' => $this->issueType->id,
        ]);

        // Invalid status
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/issues/{$issue->id}", ['status' => 'invalid']);
        $response->assertStatus(422);

        // Empty bulk update
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/audits/{$this->audit->id}/issues/bulk", [
                'issue_ids' => [],
                'status' => 'fixed',
            ]);
        $response->assertStatus(422);
    }
}
