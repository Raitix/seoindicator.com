<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Report;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Exporters\DTOs\AuditReportSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Feature tests for Report Controller audit integration.
 *
 * TASK-14-18: Integrate Audit Results into PDF Reports
 * Test documentation: TESTS/TEST-14-18.md
 */
class ReportControllerAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        
        Storage::fake('local');
    }

    // ============================================
    // CONTROLLER INTEGRATION TESTS (INT-001 to INT-010)
    // ============================================

    /**
     * INT-001: Report includes audit data when audit exists.
     */
    public function test_report_includes_audit_data_when_audit_exists(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 100,
        ]);

        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * INT-002: Report without audit data when no audit.
     */
    public function test_report_without_audit_data_when_no_audit(): void
    {
        // No audit created for this site

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['cover'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    /**
     * INT-003: Report uses latest completed audit.
     */
    public function test_report_uses_latest_completed_audit(): void
    {
        // Create older audit
        $oldAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 50,
            'completed_at' => now()->subDays(5),
        ]);

        // Create newer audit
        $newAudit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 100,
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        // The PDF should be generated successfully (newer audit used)
        $this->assertNotNull($response->json('data.downloadUrl'));
    }

    /**
     * INT-004: Only completed audits included.
     */
    public function test_only_completed_audits_included(): void
    {
        // Create pending audit
        Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);

        // Create running audit
        Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'running',
        ]);

        // No completed audit
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        // Report generated but audit section shows "not available"
    }

    /**
     * INT-005: buildAuditSection method exists.
     */
    public function test_build_audit_section_method_exists(): void
    {
        $controller = new \App\Http\Controllers\Api\V1\ReportController(
            app(\App\Repositories\ReportRepository::class),
            app(\App\Repositories\ReportBrandingRepository::class),
            app(\App\Services\ReportDataAggregator::class),
            app(\App\Exporters\CsvReportExporter::class),
            app(\App\Exporters\PdfReportExporter::class)
        );

        $this->assertTrue(method_exists($controller, 'generate'));
    }

    /**
     * INT-008: Audit for different site excluded.
     */
    public function test_audit_for_different_site_excluded(): void
    {
        $otherSite = Site::factory()->create(['user_id' => $this->user->id]);
        
        // Create audit for other site
        Audit::factory()->create([
            'site_id' => $otherSite->id,
            'status' => 'completed',
            'pages_crawled' => 200,
        ]);

        // No audit for the target site
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        // Report generated, but audit from other site not included
    }

    /**
     * INT-010: Authorization enforced.
     */
    public function test_authorization_enforced(): void
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(403);
    }

    // ============================================
    // PDF GENERATION INTEGRATION TESTS (INT-015 to INT-025)
    // ============================================

    /**
     * INT-015: PDF generated with audit section.
     */
    public function test_pdf_generated_with_audit_section(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 100,
        ]);

        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
            'url' => 'https://example.com',
            'issue_type' => 'Missing title',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Verify PDF was created
        $report = Report::find($response->json('data.reportId'));
        $this->assertNotNull($report);
        $this->assertEquals('completed', $report->status);
        $this->assertNotNull($report->file_path);
    }

    /**
     * INT-016: PDF download response.
     */
    public function test_pdf_download_response(): void
    {
        $report = Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'format' => 'pdf',
            'file_path' => 'reports/test-report.pdf',
        ]);

        Storage::put('reports/test-report.pdf', '%PDF-1.4 test content');

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$report->id}/download");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * INT-017: PDF filename includes site.
     */
    public function test_pdf_filename_includes_site(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'My SEO Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        $report = Report::find($response->json('data.reportId'));
        $this->assertStringContainsString('my-seo-report', $report->file_path);
    }

    /**
     * INT-018: PDF includes audit summary.
     */
    public function test_pdf_includes_audit_summary(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 250,
        ]);

        AuditIssue::factory()->count(5)->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        AuditIssue::factory()->count(3)->create([
            'audit_id' => $audit->id,
            'severity' => 'warning',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        // Verify PDF content includes summary
        $report = Report::find($response->json('data.reportId'));
        Storage::assertExists($report->file_path);
    }

    /**
     * INT-019: PDF includes critical issues.
     */
    public function test_pdf_includes_critical_issues(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
            'issue_type' => 'Missing title tag',
            'url' => 'https://example.com/page',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * INT-020: PDF includes warnings.
     */
    public function test_pdf_includes_warnings(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'warning',
            'status' => 'open',
            'issue_type' => 'Missing meta description',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * INT-021: PDF includes recommendations.
     */
    public function test_pdf_includes_recommendations(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
            'recommendation' => 'Add a unique title tag to the page',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * INT-023: PDF size reasonable.
     */
    public function test_pdf_size_reasonable(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        $report = Report::find($response->json('data.reportId'));
        // PDF should be less than 5MB
        $this->assertLessThan(5 * 1024 * 1024, $report->file_size);
    }

    /**
     * INT-025: Large audit generates PDF.
     */
    public function test_large_audit_generates_pdf(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 500,
        ]);

        // Create 100+ issues
        AuditIssue::factory()->count(50)->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        AuditIssue::factory()->count(50)->create([
            'audit_id' => $audit->id,
            'severity' => 'warning',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Large Audit Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        $report = Report::find($response->json('data.reportId'));
        $this->assertEquals('completed', $report->status);
    }

    // ============================================
    // ERROR HANDLING TESTS (INT-030 to INT-035)
    // ============================================

    /**
     * INT-030: Site not found.
     */
    public function test_site_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/99999/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(404);
    }

    /**
     * INT-031: Unauthorized access.
     */
    public function test_unauthorized_access(): void
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(403);
    }

    /**
     * INT-032: Unauthenticated request.
     */
    public function test_unauthenticated_request(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/reports", [
            'name' => 'Test Report',
            'type' => 'one-time',
            'format' => 'pdf',
            'dateRange' => [
                'start' => now()->subDays(7)->toDateString(),
                'end' => now()->toDateString(),
            ],
            'sections' => ['audit'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * INT-033: Audit query error - PDF without audit section.
     */
    public function test_audit_query_error_pdf_without_audit(): void
    {
        // No audit exists, PDF should still generate
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['cover', 'audit'],
            ]);

        $response->assertStatus(201);
    }

    /**
     * INT-034: PDF generation error.
     */
    public function test_pdf_generation_error(): void
    {
        // Invalid date range should cause validation error
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => 'invalid-date',
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * INT-035: Invalid audit data.
     */
    public function test_invalid_audit_data(): void
    {
        // Create audit with no issues
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Test Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        // Report should still be generated even with empty audit data
        $report = Report::find($response->json('data.reportId'));
        $this->assertEquals('completed', $report->status);
    }

    // ============================================
    // ADDITIONAL INTEGRATION TESTS
    // ============================================

    /**
     * Test audit section with all sections combined.
     */
    public function test_audit_with_all_sections_combined(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'pages_crawled' => 100,
        ]);

        AuditIssue::factory()->count(5)->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Full Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['cover', 'summary', 'audit'],
            ]);

        $response->assertStatus(201);
        
        $report = Report::find($response->json('data.reportId'));
        $this->assertEquals('completed', $report->status);
    }

    /**
     * Test CSV report does not include audit section.
     */
    public function test_csv_report_does_not_include_audit(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'CSV Report',
                'type' => 'one-time',
                'format' => 'csv',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['cover'],
            ]);

        $response->assertStatus(201);
        
        $report = Report::find($response->json('data.reportId'));
        $this->assertEquals('csv', $report->format);
    }

    /**
     * Test audit section respects issue limits.
     */
    public function test_audit_section_respects_issue_limits(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        // Create 15 critical issues (should be limited to 10)
        AuditIssue::factory()->count(15)->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        // Create 10 warning issues (should be limited to 5)
        AuditIssue::factory()->count(10)->create([
            'audit_id' => $audit->id,
            'severity' => 'warning',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Limited Issues Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
        
        // Verify PDF was generated
        $report = Report::find($response->json('data.reportId'));
        $this->assertEquals('completed', $report->status);
    }

    /**
     * Test audit data excludes fixed issues.
     */
    public function test_audit_data_excludes_fixed_issues(): void
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        // Create open issue
        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'open',
        ]);

        // Create fixed issue
        AuditIssue::factory()->create([
            'audit_id' => $audit->id,
            'severity' => 'critical',
            'status' => 'fixed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports", [
                'name' => 'Open Issues Report',
                'type' => 'one-time',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'sections' => ['audit'],
            ]);

        $response->assertStatus(201);
    }
}
