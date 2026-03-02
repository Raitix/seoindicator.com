<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\ReportBranding;
use App\Models\ScheduledReport;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive integration tests for Reports API (SPEC-004).
 * 
 * TASK-4-21: Integration tests covering all Reports API endpoints
 * including CRUD, generation, templates, scheduling, and branding.
 */
class Spec00421Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherSite;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        
        Storage::fake('local');
        Storage::fake('public');
    }

    // ============================================================
    // REPORTS CRUD API WORKFLOW
    // ============================================================

    /**
     * Test complete report lifecycle: create, read, delete.
     */
    public function test_report_crud_lifecycle(): void
    {
        // Create report
        $createData = [
            'name' => 'Integration Test Report',
            'type' => 'weekly',
            'format' => 'pdf',
            'dateRange' => [
                'start' => '2026-02-01',
                'end' => '2026-02-28',
            ],
        ];

        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", $createData);

        $createResponse->assertStatus(201)
            ->assertJson(['success' => true]);

        $reportId = $createResponse->json('data.reportId');

        // Read report
        $readResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$reportId}");

        $readResponse->assertStatus(200)
            ->assertJsonPath('data.id', $reportId)
            ->assertJsonPath('data.name', 'Integration Test Report');

        // List reports
        $listResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/reports");

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reportId);

        // Delete report
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/reports/{$reportId}");

        $deleteResponse->assertStatus(204);

        // Verify deletion
        $this->assertDatabaseMissing('reports', ['id' => $reportId]);
    }

    /**
     * Test list API filters correctly by status.
     */
    public function test_report_list_filters_by_status(): void
    {
        Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'name' => 'Completed Report',
        ]);
        Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'pending',
            'name' => 'Pending Report',
        ]);
        Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'failed',
            'name' => 'Failed Report',
        ]);

        // Filter by completed
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/reports?status=completed");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'completed');

        // Filter by pending
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/reports?status=pending");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    // ============================================================
    // REPORT GENERATION
    // ============================================================

    /**
     * Test generate report with valid template.
     */
    public function test_generate_report_with_template(): void
    {
        $template = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'sections' => ['cover', 'summary', 'keywords'],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Templated Report',
                'type' => 'weekly',
                'format' => 'pdf',
                'templateId' => $template->id,
                'dateRange' => [
                    'start' => '2026-02-01',
                    'end' => '2026-02-28',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test generate report with invalid template returns error.
     */
    public function test_generate_report_with_invalid_template(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Bad Template Report',
                'type' => 'summary',
                'format' => 'pdf',
                'templateId' => 999, // Non-existent
                'dateRange' => [
                    'start' => '2026-02-01',
                    'end' => '2026-02-28',
                ],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test generate report validates required fields.
     */
    public function test_generate_report_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", []);

        $response->assertStatus(422);
    }

    /**
     * Test generate report validates date range.
     */
    public function test_generate_report_validates_date_range(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Invalid Date Range',
                'type' => 'weekly',
                'format' => 'pdf',
                'dateRange' => [
                    'start' => '2026-02-28',
                    'end' => '2026-02-01', // End before start
                ],
            ]);

        $response->assertStatus(422);
    }

    // ============================================================
    // REPORT DOWNLOAD
    // ============================================================

    /**
     * Test download generated report.
     */
    public function test_download_report(): void
    {
        Storage::put('reports/test-report.pdf', 'PDF content');
        
        $report = Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'completed',
            'format' => 'pdf',
            'file_path' => 'reports/test-report.pdf',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$report->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test download non-existent report returns error.
     */
    public function test_download_nonexistent_report(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reports/999/download');

        $response->assertStatus(404);
    }

    /**
     * Test download pending report returns error.
     */
    public function test_download_pending_report(): void
    {
        $report = Report::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$report->id}/download");

        $response->assertStatus(400);
    }

    // ============================================================
    // TEMPLATE MANAGEMENT API
    // ============================================================

    /**
     * Test template CRUD lifecycle.
     */
    public function test_template_crud_lifecycle(): void
    {
        // Create template
        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/report-templates", [
                'name' => 'Test Template',
                'sections' => ['cover', 'summary', 'keywords'],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Template');

        $templateId = $createResponse->json('data.id');

        // Update template
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/v1/report-templates/{$templateId}", [
                'name' => 'Updated Template',
                'sections' => ['cover', 'summary', 'keywords', 'trends'],
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Template');

        // List templates
        $listResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/report-templates");

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Delete template
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/report-templates/{$templateId}");

        $deleteResponse->assertStatus(204);
        $this->assertDatabaseMissing('report_templates', ['id' => $templateId]);
    }

    /**
     * Test template validation - invalid sections.
     */
    public function test_template_invalid_sections(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/report-templates", [
                'name' => 'Invalid Template',
                'sections' => ['cover', 'invalid-section'],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test only one default template per site.
     */
    public function test_single_default_template_per_site(): void
    {
        $template1 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
        ]);

        $template2 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
        ]);

        // First template should no longer be default
        $this->assertFalse($template1->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_default);
    }

    // ============================================================
    // AUTHENTICATION / AUTHORIZATION
    // ============================================================

    /**
     * Test unauthenticated access to protected endpoints.
     */
    public function test_unauthenticated_access(): void
    {
        // Reports endpoints
        $this->getJson("/api/v1/sites/{$this->site->id}/reports")->assertStatus(401);
        $this->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [])->assertStatus(401);
        
        $report = Report::factory()->create(['site_id' => $this->site->id]);
        $this->getJson("/api/v1/reports/{$report->id}")->assertStatus(401);
        $this->deleteJson("/api/v1/reports/{$report->id}")->assertStatus(401);
    }

    /**
     * Test user cannot access another user's reports.
     */
    public function test_cross_user_report_access_denied(): void
    {
        $otherReport = Report::factory()->create(['site_id' => $this->otherSite->id]);

        // Cannot view
        $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$otherReport->id}")
            ->assertStatus(403);

        // Cannot delete
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/reports/{$otherReport->id}")
            ->assertStatus(403);

        // Cannot download
        Storage::put('reports/other.pdf', 'content');
        $otherReport->update([
            'status' => 'completed',
            'file_path' => 'reports/other.pdf',
        ]);
        
        $this->actingAs($this->user)
            ->getJson("/api/v1/reports/{$otherReport->id}/download")
            ->assertStatus(403);
    }

    /**
     * Test user cannot generate report for another user's site.
     */
    public function test_cross_user_report_generation_denied(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/reports/generate", [
                'name' => 'Unauthorized Report',
                'type' => 'weekly',
                'format' => 'pdf',
                'dateRange' => ['start' => '2026-02-01', 'end' => '2026-02-28'],
            ]);

        $response->assertStatus(403);
    }

    // ============================================================
    // SCHEDULED REPORTS
    // ============================================================

    /**
     * Test scheduled report CRUD lifecycle.
     */
    public function test_scheduled_report_crud_lifecycle(): void
    {
        // Create scheduled report
        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/scheduled-reports", [
                'name' => 'Weekly Schedule',
                'frequency' => 'weekly',
                'format' => 'pdf',
                'recipients' => ['user@example.com'],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Weekly Schedule');

        $scheduledId = $createResponse->json('data.id');

        // List scheduled reports
        $listResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/scheduled-reports");

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Update
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/v1/scheduled-reports/{$scheduledId}", [
                'name' => 'Updated Schedule',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Schedule');

        // Pause
        $pauseResponse = $this->actingAs($this->user)
            ->patchJson("/api/v1/scheduled-reports/{$scheduledId}/pause");
        $pauseResponse->assertStatus(200)
            ->assertJsonPath('data.enabled', false);

        // Resume
        $resumeResponse = $this->actingAs($this->user)
            ->patchJson("/api/v1/scheduled-reports/{$scheduledId}/resume");
        $resumeResponse->assertStatus(200)
            ->assertJsonPath('data.enabled', true);

        // Delete
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/scheduled-reports/{$scheduledId}");
        $deleteResponse->assertStatus(204);
    }

    /**
     * Test scheduled report frequency validation.
     */
    public function test_scheduled_report_frequency_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/scheduled-reports", [
                'name' => 'Invalid Frequency',
                'frequency' => 'hourly', // Invalid
                'format' => 'pdf',
                'recipients' => ['user@example.com'],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test scheduled report with template.
     */
    public function test_scheduled_report_with_template(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => $this->site->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/scheduled-reports", [
                'name' => 'Scheduled with Template',
                'templateId' => $template->id,
                'frequency' => 'weekly',
                'format' => 'pdf',
                'recipients' => ['user@example.com'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.templateId', $template->id);
    }

    // ============================================================
    // BRANDING API
    // ============================================================

    /**
     * Test branding save and retrieve.
     */
    public function test_branding_save_and_retrieve(): void
    {
        // Get default branding
        $getResponse = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

        $getResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Save branding
        $putResponse = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'primaryColor' => '#FF0000',
            'secondaryColor' => '#00FF00',
            'fontFamily' => 'Arial',
            'companyName' => 'Test Company',
            'companyWebsite' => 'https://test.com',
        ]);

        $putResponse->assertStatus(200)
            ->assertJsonPath('data.primaryColor', '#FF0000')
            ->assertJsonPath('data.companyName', 'Test Company');

        // Retrieve saved branding
        $getResponse2 = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

        $getResponse2->assertStatus(200)
            ->assertJsonPath('data.primaryColor', '#FF0000');
    }

    /**
     * Test branding validation - invalid color.
     */
    public function test_branding_invalid_color(): void
    {
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'primaryColor' => 'not-a-color',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test branding validation - invalid URL.
     */
    public function test_branding_invalid_url(): void
    {
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'companyWebsite' => 'not-a-url',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test minimal branding requirements.
     */
    public function test_minimal_branding_requirements(): void
    {
        // Default branding should be available
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'siteId',
                    'primaryColor',
                    'secondaryColor',
                    'fontFamily',
                ],
            ]);
    }

    // ============================================================
    // LOGO UPLOAD
    // ============================================================

    /**
     * Test upload valid logo file.
     */
    public function test_upload_valid_logo(): void
    {
        Storage::fake('public');

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 100, 100),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['logoUrl', 'logoFile'],
            ]);
    }

    /**
     * Test upload unsupported file format returns error.
     */
    public function test_upload_unsupported_format(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test upload file too large returns error.
     */
    public function test_upload_file_too_large(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png')->size(5000), // 5MB
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test delete logo.
     */
    public function test_delete_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/logos/test.png', 'test');

        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'logo_file' => 'uploads/logos/test.png',
        ]);

        $response = $this->deleteJson("/api/v1/sites/{$this->site->id}/branding/logo");

        $response->assertStatus(204);
        Storage::disk('public')->assertMissing('uploads/logos/test.png');
    }

    // ============================================================
    // INTEGRATION WORKFLOW TESTS
    // ============================================================

    /**
     * Test full report generation workflow with template and branding.
     */
    public function test_full_report_generation_workflow(): void
    {
        // Setup branding
        $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'primaryColor' => '#336699',
            'companyName' => 'ACME Corp',
        ]);

        // Create template
        $templateResponse = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", [
            'name' => 'Standard Report',
            'sections' => ['cover', 'summary', 'keywords', 'trends'],
            'isDefault' => true,
        ]);
        $templateId = $templateResponse->json('data.id');

        // Generate report using template
        $reportResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/reports/generate", [
                'name' => 'Q1 Report',
                'type' => 'quarterly',
                'format' => 'pdf',
                'templateId' => $templateId,
                'dateRange' => [
                    'start' => '2026-01-01',
                    'end' => '2026-03-31',
                ],
            ]);

        $reportResponse->assertStatus(201);
        $reportId = $reportResponse->json('data.id');

        // Create scheduled report
        $scheduleResponse = $this->postJson("/api/v1/sites/{$this->site->id}/scheduled-reports", [
            'name' => 'Weekly Reports',
            'templateId' => $templateId,
            'frequency' => 'weekly',
            'format' => 'pdf',
            'recipients' => ['stakeholder@example.com'],
        ]);

        $scheduleResponse->assertStatus(201);

        // Verify all components exist
        $this->assertDatabaseHas('report_templates', ['id' => $templateId]);
        $this->assertDatabaseHas('reports', ['id' => $reportId]);
        $this->assertDatabaseHas('scheduled_reports', ['template_id' => $templateId]);
    }

    /**
     * Test multiple sites have isolated data.
     */
    public function test_site_data_isolation(): void
    {
        // Create data for site 1
        $template1 = ReportTemplate::factory()->create(['site_id' => $this->site->id]);
        $report1 = Report::factory()->create(['site_id' => $this->site->id]);
        $scheduled1 = ScheduledReport::factory()->create(['site_id' => $this->site->id]);

        // Create data for site 2
        $template2 = ReportTemplate::factory()->create(['site_id' => $this->otherSite->id]);
        $report2 = Report::factory()->create(['site_id' => $this->otherSite->id]);
        $scheduled2 = ScheduledReport::factory()->create(['site_id' => $this->otherSite->id]);

        // Verify site 1 user sees only their data
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/reports");
        $response->assertJsonCount(1, 'data');

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/report-templates");
        $response->assertJsonCount(1, 'data');

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/scheduled-reports");
        $response->assertJsonCount(1, 'data');
    }
}
