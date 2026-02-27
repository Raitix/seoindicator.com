<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\ToolLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the LeadController Admin API.
 *
 * Tests admin endpoints for viewing, exporting, and managing leads.
 *
 * @see TASK-16-6
 * @see TEST-16-6
 * @see FEAT-016
 */
class LeadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::factory()->create(['is_admin' => true]);

        // Create regular user
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    // =========================================================================
    // LIST LEADS - AUTHORIZATION TESTS
    // =========================================================================

    /**
     * Test list leads requires authentication.
     * AUTH-002 / LIST-009
     */
    public function test_list_leads_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/leads');

        $response->assertStatus(401);
    }

    /**
     * Test list leads requires admin role.
     * AUTH-002 / LIST-010
     */
    public function test_list_leads_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/leads');

        $response->assertStatus(403);
    }

    /**
     * Test admin can list leads.
     * AUTH-001 / LIST-011
     */
    public function test_admin_can_list_leads(): void
    {
        ToolLead::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // =========================================================================
    // LIST LEADS - FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test list leads returns paginated results.
     * LIST-001
     */
    public function test_list_leads_returns_paginated_results(): void
    {
        ToolLead::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?perPage=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.perPage', 10);
    }

    /**
     * Test list leads filter by tool.
     * LIST-002
     */
    public function test_list_leads_filter_by_tool(): void
    {
        ToolLead::factory()->count(5)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(3)->keywordDensity()->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?tool=meta-tag-analyzer');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test list leads filter by email.
     * LIST-003
     */
    public function test_list_leads_filter_by_email(): void
    {
        ToolLead::factory()->create(['email' => 'findme@example.com']);
        ToolLead::factory()->create(['email' => 'other@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?email=findme');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'findme@example.com');
    }

    /**
     * Test list leads filter by date range.
     * LIST-004
     */
    public function test_list_leads_filter_by_date_range(): void
    {
        ToolLead::factory()->create(['created_at' => '2026-02-15']);
        ToolLead::factory()->create(['created_at' => '2026-02-20']);
        ToolLead::factory()->create(['created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?from=2026-02-01&to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test list leads pagination.
     * LIST-005
     */
    public function test_list_leads_pagination(): void
    {
        ToolLead::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?page=2&perPage=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2);
    }

    /**
     * Test list leads sort by date.
     * LIST-006
     */
    public function test_list_leads_sort_by_date(): void
    {
        ToolLead::factory()->create(['email' => 'old@example.com', 'created_at' => '2026-02-01']);
        ToolLead::factory()->create(['email' => 'new@example.com', 'created_at' => '2026-02-27']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?sort=created_at&order=desc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.email', 'new@example.com');
    }

    /**
     * Test list leads include soft deleted.
     * LIST-007
     */
    public function test_list_leads_include_soft_deleted(): void
    {
        ToolLead::factory()->create(['email' => 'active@example.com']);
        ToolLead::factory()->softDeleted()->create(['email' => 'deleted@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?withTrashed=true');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test list leads only soft deleted.
     * LIST-008
     */
    public function test_list_leads_only_soft_deleted(): void
    {
        ToolLead::factory()->create(['email' => 'active@example.com']);
        ToolLead::factory()->softDeleted()->create(['email' => 'deleted@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?onlyTrashed=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'deleted@example.com');
    }

    /**
     * Test list leads empty result.
     * LIST-012
     */
    public function test_list_leads_empty_result(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // EXPORT CSV - AUTHORIZATION TESTS
    // =========================================================================

    /**
     * Test export CSV requires authentication.
     * AUTH-003 / CSV-007
     */
    public function test_export_csv_requires_authentication(): void
    {
        $response = $this->get('/api/v1/admin/leads/export');

        $response->assertStatus(401);
    }

    /**
     * Test export CSV requires admin role.
     * AUTH-004 / CSV-008
     */
    public function test_export_csv_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/api/v1/admin/leads/export');

        $response->assertStatus(403);
    }

    // =========================================================================
    // EXPORT CSV - FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test export CSV returns CSV download.
     * CSV-001
     */
    public function test_export_csv_returns_csv_download(): void
    {
        ToolLead::factory()->create(['email' => 'export@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->get('/api/v1/admin/leads/export');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test export CSV with filter.
     * CSV-002
     */
    public function test_export_csv_with_filter(): void
    {
        ToolLead::factory()->create(['email' => 'include@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'exclude@example.com', 'tool' => 'keyword-density']);

        $response = $this->actingAs($this->adminUser)
            ->get('/api/v1/admin/leads/export?tool=meta-tag-analyzer');

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('include@example.com', $content);
        $this->assertStringNotContainsString('exclude@example.com', $content);
    }

    /**
     * Test export CSV has correct headers.
     * CSV-003
     */
    public function test_export_csv_has_correct_headers(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/api/v1/admin/leads/export');

        $response->assertHeader('content-disposition', 'attachment; filename=leads.csv');
    }

    /**
     * Test export CSV valid format.
     * CSV-004
     */
    public function test_export_csv_valid_format(): void
    {
        ToolLead::factory()->create([
            'email' => 'test@example.com',
            'tool' => 'meta-tag-analyzer',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/api/v1/admin/leads/export');

        $content = $response->streamedContent();
        $this->assertStringContainsString('id,email,tool,url,ip_address,user_agent,created_at', $content);
        $this->assertStringContainsString('test@example.com', $content);
    }

    /**
     * Test export CSV with date filter.
     * CSV-006
     */
    public function test_export_csv_with_date_filter(): void
    {
        ToolLead::factory()->create(['email' => 'feb@example.com', 'created_at' => '2026-02-15']);
        ToolLead::factory()->create(['email' => 'mar@example.com', 'created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->get('/api/v1/admin/leads/export?from=2026-02-01&to=2026-02-28');

        $content = $response->streamedContent();
        $this->assertStringContainsString('feb@example.com', $content);
        $this->assertStringNotContainsString('mar@example.com', $content);
    }

    // =========================================================================
    // STATISTICS - AUTHORIZATION TESTS
    // =========================================================================

    /**
     * Test statistics requires authentication.
     * AUTH-005 / STATS-009
     */
    public function test_statistics_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(401);
    }

    /**
     * Test statistics requires admin role.
     * AUTH-006 / STATS-010
     */
    public function test_statistics_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(403);
    }

    // =========================================================================
    // STATISTICS - FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test statistics returns total count.
     * STATS-001
     */
    public function test_statistics_returns_total_count(): void
    {
        ToolLead::factory()->count(10)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 10);
    }

    /**
     * Test statistics returns thisWeek count.
     * STATS-002
     */
    public function test_statistics_returns_this_week_count(): void
    {
        ToolLead::factory()->create(['created_at' => now()->subDays(3)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.thisWeek', 1);
    }

    /**
     * Test statistics returns thisMonth count.
     * STATS-003
     */
    public function test_statistics_returns_this_month_count(): void
    {
        ToolLead::factory()->create(['created_at' => now()->subDays(15)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(45)]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.thisMonth', 1);
    }

    /**
     * Test statistics returns byTool breakdown.
     * STATS-004
     */
    public function test_statistics_returns_by_tool_breakdown(): void
    {
        ToolLead::factory()->count(5)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(3)->keywordDensity()->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.byTool.meta-tag-analyzer', 5)
            ->assertJsonPath('data.byTool.keyword-density', 3);
    }

    /**
     * Test statistics with date filter.
     * STATS-006
     */
    public function test_statistics_with_date_filter(): void
    {
        ToolLead::factory()->create(['created_at' => '2026-02-15']);
        ToolLead::factory()->create(['created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats?from=2026-02-01&to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    /**
     * Test statistics excludes soft deleted by default.
     * STATS-007
     */
    public function test_statistics_excludes_soft_deleted(): void
    {
        ToolLead::factory()->create();
        ToolLead::factory()->softDeleted()->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    /**
     * Test statistics includes soft deleted when requested.
     * STATS-008
     */
    public function test_statistics_includes_soft_deleted_when_requested(): void
    {
        ToolLead::factory()->create();
        ToolLead::factory()->softDeleted()->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads/stats?withTrashed=true');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    // =========================================================================
    // DELETE LEAD - AUTHORIZATION TESTS
    // =========================================================================

    /**
     * Test delete requires authentication.
     * AUTH-007
     */
    public function test_delete_requires_authentication(): void
    {
        $lead = ToolLead::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/leads/{$lead->id}");

        $response->assertStatus(401);
    }

    /**
     * Test delete requires admin role.
     * AUTH-008
     */
    public function test_delete_requires_admin_role(): void
    {
        $lead = ToolLead::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/v1/admin/leads/{$lead->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // DELETE LEAD - FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test delete soft deletes lead.
     * GDPR-001
     */
    public function test_delete_soft_deletes_lead(): void
    {
        $lead = ToolLead::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/admin/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertSoftDeleted('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test restore endpoint.
     * GDPR-002
     */
    public function test_restore_endpoint(): void
    {
        $lead = ToolLead::factory()->softDeleted()->create();

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/admin/leads/{$lead->id}/restore");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertNull($lead->fresh()->deleted_at);
    }

    /**
     * Test force delete with parameter.
     * GDPR-003
     */
    public function test_force_delete_with_parameter(): void
    {
        $lead = ToolLead::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/admin/leads/{$lead->id}?force=true");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test delete returns 404 for non-existent lead.
     */
    public function test_delete_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/leads/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test SQL injection in email filter.
     * EC-004
     */
    public function test_sql_injection_in_email_filter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/leads?email='; DROP TABLE tool_leads; --");

        // Should not cause an error, just return empty result
        $response->assertStatus(200);
    }

    /**
     * Test XSS in email filter.
     * EC-005
     */
    public function test_xss_in_email_filter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?email=<script>alert(1)</script>');

        $response->assertStatus(200);
    }

    /**
     * Test invalid tool filter.
     * EC-007
     */
    public function test_invalid_tool_filter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?tool=invalid-tool-name');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test perPage maximum limit.
     */
    public function test_perpage_maximum_limit(): void
    {
        ToolLead::factory()->count(150)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?perPage=200');

        // Should cap at 100
        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 100);
    }

    /**
     * Test invalid sort field.
     */
    public function test_invalid_sort_field(): void
    {
        ToolLead::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/leads?sort=invalid_field');

        // Should default to created_at or return error
        $response->assertStatus(200);
    }

    /**
     * Test concurrent delete requests.
     * EC-011
     */
    public function test_concurrent_delete_requests(): void
    {
        $lead = ToolLead::factory()->create();

        // Simulate concurrent requests
        $response1 = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/admin/leads/{$lead->id}");

        // Second delete should return 404 (already deleted)
        $response2 = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/admin/leads/{$lead->id}");

        $response1->assertStatus(200);
        $response2->assertStatus(404);
    }
}
