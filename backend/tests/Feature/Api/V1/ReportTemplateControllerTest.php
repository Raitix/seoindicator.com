<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\ReportTemplate;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for ReportTemplateController (SPEC-004-10).
 */
class ReportTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::factory()->create();
    }

    // ==================== LIST TESTS (10.1) ====================

    /**
     * Test list templates returns 200 with templates list.
     */
    public function test_list_templates(): void
    {
        ReportTemplate::factory()->count(3)->create(['site_id' => $this->site->id]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/report-templates");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'siteId',
                        'name',
                        'isDefault',
                        'sections',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ]);
    }

    /**
     * Test list templates empty result.
     */
    public function test_list_templates_empty(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/report-templates");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test list templates for invalid site returns 404.
     */
    public function test_list_templates_invalid_site(): void
    {
        $response = $this->getJson('/api/v1/sites/9999/report-templates');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    // ==================== GET SINGLE TESTS (10.2) ====================

    /**
     * Test get template returns 200 with template details.
     */
    public function test_get_template(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => $this->site->id]);

        $response = $this->getJson("/api/v1/report-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.name', $template->name)
            ->assertJsonPath('data.siteId', $this->site->id);
    }

    /**
     * Test get template includes all fields.
     */
    public function test_get_template_includes_all_fields(): void
    {
        $template = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'sections' => ['cover', 'summary', 'keywords'],
            'cover_page_text' => 'Custom cover text',
            'executive_summary' => 'Executive summary content',
            'branding' => [
                'logoUrl' => 'https://example.com/logo.png',
                'primaryColor' => '#FF0000',
            ],
        ]);

        $response = $this->getJson("/api/v1/report-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.sections', ['cover', 'summary', 'keywords'])
            ->assertJsonPath('data.coverPageText', 'Custom cover text')
            ->assertJsonPath('data.executiveSummary', 'Executive summary content')
            ->assertJsonPath('data.branding.logoUrl', 'https://example.com/logo.png')
            ->assertJsonPath('data.branding.primaryColor', '#FF0000');
    }

    /**
     * Test get template invalid ID returns 404.
     */
    public function test_get_template_invalid_id(): void
    {
        $response = $this->getJson('/api/v1/report-templates/9999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    // ==================== CREATE TESTS (10.3) ====================

    /**
     * Test create template returns 201 with created template.
     */
    public function test_create_template(): void
    {
        $data = [
            'name' => 'My Custom Template',
            'sections' => ['cover', 'summary', 'keywords'],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'My Custom Template')
            ->assertJsonPath('data.sections', ['cover', 'summary', 'keywords'])
            ->assertJsonPath('data.isDefault', false);

        $this->assertDatabaseHas('report_templates', [
            'name' => 'My Custom Template',
            'site_id' => $this->site->id,
        ]);
    }

    /**
     * Test create template with all optional fields.
     */
    public function test_create_template_with_all_fields(): void
    {
        $data = [
            'name' => 'Full Template',
            'sections' => ['cover', 'summary', 'keywords', 'trends'],
            'isDefault' => true,
            'coverPageText' => 'Welcome to our report',
            'executiveSummary' => 'This is the summary',
            'branding' => [
                'logoUrl' => 'https://example.com/logo.png',
                'primaryColor' => '#336699',
                'secondaryColor' => '#99CCFF',
                'fontFamily' => 'Arial',
            ],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Full Template')
            ->assertJsonPath('data.isDefault', true)
            ->assertJsonPath('data.branding.primaryColor', '#336699');
    }

    /**
     * Test create template missing name returns validation error.
     */
    public function test_create_template_missing_name(): void
    {
        $data = [
            'sections' => ['cover'],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    /**
     * Test create template missing sections returns validation error.
     */
    public function test_create_template_missing_sections(): void
    {
        $data = [
            'name' => 'Template Without Sections',
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    /**
     * Test create template for invalid site returns 404.
     */
    public function test_create_template_invalid_site(): void
    {
        $data = [
            'name' => 'Test Template',
            'sections' => ['cover'],
        ];

        $response = $this->postJson('/api/v1/sites/9999/report-templates', $data);

        $response->assertStatus(404);
    }

    // ==================== INVALID SECTIONS TESTS (10.6) ====================

    /**
     * Test create template with invalid section returns 422 validation error.
     */
    public function test_create_template_invalid_section(): void
    {
        $data = [
            'name' => 'Invalid Template',
            'sections' => ['cover', 'invalid-section', 'keywords'],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    /**
     * Test create template with empty sections returns validation error.
     */
    public function test_create_template_empty_sections(): void
    {
        $data = [
            'name' => 'Empty Sections Template',
            'sections' => [],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    /**
     * Test update template with invalid section returns 422.
     */
    public function test_update_template_invalid_section(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => $this->site->id]);

        $response = $this->putJson("/api/v1/report-templates/{$template->id}", [
            'sections' => ['cover', 'nonexistent-section'],
        ]);

        $response->assertStatus(422);
    }

    // ==================== UPDATE TESTS (10.4) ====================

    /**
     * Test update template returns 200 with updated template.
     */
    public function test_update_template(): void
    {
        $template = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/v1/report-templates/{$template->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('report_templates', [
            'id' => $template->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test update template sections.
     */
    public function test_update_template_sections(): void
    {
        $template = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'sections' => ['cover'],
        ]);

        $response = $this->putJson("/api/v1/report-templates/{$template->id}", [
            'sections' => ['cover', 'summary', 'keywords', 'trends'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.sections', ['cover', 'summary', 'keywords', 'trends']);
    }

    /**
     * Test update template branding.
     */
    public function test_update_template_branding(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => $this->site->id]);

        $response = $this->putJson("/api/v1/report-templates/{$template->id}", [
            'branding' => [
                'logoUrl' => 'https://example.com/new-logo.png',
                'primaryColor' => '#000000',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.branding.logoUrl', 'https://example.com/new-logo.png');
    }

    /**
     * Test update template invalid ID returns 404.
     */
    public function test_update_template_invalid_id(): void
    {
        $response = $this->putJson('/api/v1/report-templates/9999', [
            'name' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    // ==================== DELETE TESTS (10.5) ====================

    /**
     * Test delete template returns 204.
     */
    public function test_delete_template(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => $this->site->id]);

        $response = $this->deleteJson("/api/v1/report-templates/{$template->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }

    /**
     * Test delete template invalid ID returns 404.
     */
    public function test_delete_template_invalid_id(): void
    {
        $response = $this->deleteJson('/api/v1/report-templates/9999');

        $response->assertStatus(404);
    }

    // ==================== DEFAULT TEMPLATE TESTS (10.8) ====================

    /**
     * Test only one default template per site.
     */
    public function test_only_one_default_template_per_site(): void
    {
        // Create first default template
        $template1 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
            'name' => 'First Default',
        ]);

        // Create second default template
        $template2 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
            'name' => 'Second Default',
        ]);

        // First template should no longer be default
        $this->assertFalse($template1->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_default);
    }

    /**
     * Test setting template as default via update.
     */
    public function test_update_sets_default_unsets_others(): void
    {
        $template1 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
        ]);
        $template2 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => false,
        ]);

        $response = $this->putJson("/api/v1/report-templates/{$template2->id}", [
            'isDefault' => true,
        ]);

        $response->assertStatus(200);
        $this->assertFalse($template1->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_default);
    }

    /**
     * Test default templates in different sites are independent.
     */
    public function test_default_templates_different_sites(): void
    {
        $site2 = Site::factory()->create();

        $template1 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
        ]);
        $template2 = ReportTemplate::factory()->create([
            'site_id' => $site2->id,
            'is_default' => true,
        ]);

        // Both should remain default (different sites)
        $this->assertTrue($template1->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_default);
    }

    // ==================== SECURITY TESTS (10.7) ====================

    /**
     * Test SQL injection protection.
     */
    public function test_sql_injection_protection(): void
    {
        $data = [
            'name' => "'; DROP TABLE report_templates; --",
            'sections' => ['cover'],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        // Should either succeed (sanitized) or fail validation
        $this->assertNotEquals(500, $response->status());
    }

    /**
     * Test XSS protection in template name.
     */
    public function test_xss_protection(): void
    {
        $data = [
            'name' => '<script>alert("xss")</script>',
            'sections' => ['cover'],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        if ($response->status() === 201) {
            $name = $response->json('data.name');
            $this->assertStringNotContainsString('<script>', $name);
        }
    }

    /**
     * Test invalid branding URL validation.
     */
    public function test_invalid_branding_url(): void
    {
        $data = [
            'name' => 'Template with bad branding',
            'sections' => ['cover'],
            'branding' => [
                'logoUrl' => 'not-a-valid-url',
            ],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    /**
     * Test invalid color format validation.
     */
    public function test_invalid_color_format(): void
    {
        $data = [
            'name' => 'Template with bad color',
            'sections' => ['cover'],
            'branding' => [
                'primaryColor' => 'not-a-color',
            ],
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/report-templates", $data);

        $response->assertStatus(422);
    }

    // ==================== EDGE CASES ====================

    /**
     * Test delete default template is allowed.
     */
    public function test_delete_default_template_allowed(): void
    {
        $template = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'is_default' => true,
        ]);

        $response = $this->deleteJson("/api/v1/report-templates/{$template->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }

    /**
     * Test templates are ordered by creation date descending.
     */
    public function test_templates_ordered_by_date(): void
    {
        $template1 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'created_at' => now()->subDay(),
        ]);
        $template2 = ReportTemplate::factory()->create([
            'site_id' => $this->site->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/report-templates");

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();

        // Most recent first
        $this->assertEquals([$template2->id, $template1->id], $ids);
    }
}
