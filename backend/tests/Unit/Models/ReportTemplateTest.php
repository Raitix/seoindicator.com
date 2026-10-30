<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ReportTemplate;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTemplateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a report template with valid data.
     */
    public function test_create_report_template_with_valid_data(): void
    {
        $site = Site::factory()->create();
        
        $template = ReportTemplate::create([
            'site_id' => $site->id,
            'name' => 'Custom Template',
            'is_default' => true,
            'sections' => ['overview', 'keywords'],
            'branding' => ['color' => '#000000'],
            'cover_page_text' => 'Welcome',
            'executive_summary' => 'Summary content',
        ]);
        
        $this->assertInstanceOf(ReportTemplate::class, $template);
        $this->assertEquals('Custom Template', $template->name);
        $this->assertTrue($template->is_default);
        $this->assertIsArray($template->sections);
        $this->assertIsArray($template->branding);
    }

    /**
     * Test report template belongs to site relationship.
     */
    public function test_report_template_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $template = ReportTemplate::factory()->create(['site_id' => $site->id]);
        
        $this->assertInstanceOf(Site::class, $template->site);
        $this->assertEquals($site->id, $template->site->id);
    }

    /**
     * Test sections and branding cast to array.
     */
    public function test_json_fields_cast_to_array(): void
    {
        $template = ReportTemplate::factory()->create([
            'sections' => ['a', 'b'],
            'branding' => ['logo' => 'test.png'],
        ]);
        
        $this->assertIsArray($template->sections);
        $this->assertIsArray($template->branding);
        $this->assertEquals(['a', 'b'], $template->sections);
        $this->assertEquals(['logo' => 'test.png'], $template->branding);
    }

    /**
     * Test makeDefault method sets default and unsets others for same site.
     */
    public function test_make_default_sets_correct_default(): void
    {
        $site = Site::factory()->create();
        $template1 = ReportTemplate::factory()->create(['site_id' => $site->id, 'is_default' => true]);
        $template2 = ReportTemplate::factory()->create(['site_id' => $site->id, 'is_default' => false]);
        
        $template2->makeDefault();
        
        $this->assertTrue($template2->fresh()->is_default);
        $this->assertFalse($template1->fresh()->is_default);
    }

    /**
     * Test saving a new template as default unsets others for same site.
     */
    public function test_saving_default_unsets_other_defaults(): void
    {
        $site = Site::factory()->create();
        $template1 = ReportTemplate::factory()->create(['site_id' => $site->id, 'is_default' => true]);
        
        $template2 = ReportTemplate::factory()->create(['site_id' => $site->id, 'is_default' => true]);
        
        $this->assertTrue($template2->is_default);
        $this->assertFalse($template1->fresh()->is_default);
    }

    /**
     * Test global templates (site_id is null).
     */
    public function test_global_template_allowed(): void
    {
        $template = ReportTemplate::factory()->create(['site_id' => null]);
        $this->assertNull($template->site_id);
    }
}
