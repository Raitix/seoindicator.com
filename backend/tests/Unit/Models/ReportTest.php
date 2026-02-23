<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Report;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a report with valid data.
     */
    public function test_create_report_with_valid_data(): void
    {
        $site = Site::factory()->create();
        
        $report = Report::create([
            'site_id' => $site->id,
            'name' => 'Monthly SEO Report',
            'type' => 'summary',
            'format' => 'pdf',
            'status' => 'pending',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['overview', 'keywords', 'traffic'],
        ]);
        
        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals('Monthly SEO Report', $report->name);
        $this->assertEquals('pending', $report->status);
        $this->assertIsArray($report->sections);
    }

    /**
     * Test report belongs to site relationship.
     */
    public function test_report_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $report = Report::factory()->create(['site_id' => $site->id]);
        
        $this->assertInstanceOf(Site::class, $report->site);
        $this->assertEquals($site->id, $report->site->id);
    }

    /**
     * Test sections cast to array.
     */
    public function test_sections_cast_to_array(): void
    {
        $report = Report::factory()->create([
            'sections' => ['overview', 'keywords', 'traffic'],
        ]);
        
        $this->assertIsArray($report->sections);
        $this->assertEquals(['overview', 'keywords', 'traffic'], $report->sections);
    }

    /**
     * Test sections can be null.
     */
    public function test_sections_can_be_null(): void
    {
        $report = Report::factory()->create(['sections' => null]);
        
        $this->assertNull($report->sections);
    }

    /**
     * Test date fields are cast to Carbon dates.
     */
    public function test_date_fields_cast_to_carbon(): void
    {
        $report = Report::factory()->create([
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'generated_at' => '2026-02-28 10:30:00',
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->date_range_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->date_range_end);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->generated_at);
    }

    /**
     * Test file_size cast to integer.
     */
    public function test_file_size_cast_to_integer(): void
    {
        $report = Report::factory()->create(['file_size' => 1024000]);
        
        $this->assertIsInt($report->file_size);
        $this->assertEquals(1024000, $report->file_size);
    }

    /**
     * Test status_label accessor for pending status.
     */
    public function test_status_label_accessor_pending(): void
    {
        $report = Report::factory()->pending()->create();
        
        $this->assertEquals('Pending', $report->status_label);
    }

    /**
     * Test status_label accessor for generating status.
     */
    public function test_status_label_accessor_generating(): void
    {
        $report = Report::factory()->generating()->create();
        
        $this->assertEquals('Generating', $report->status_label);
    }

    /**
     * Test status_label accessor for completed status.
     */
    public function test_status_label_accessor_completed(): void
    {
        $report = Report::factory()->completed()->create();
        
        $this->assertEquals('Completed', $report->status_label);
    }

    /**
     * Test status_label accessor for failed status.
     */
    public function test_status_label_accessor_failed(): void
    {
        $report = Report::factory()->failed()->create();
        
        $this->assertEquals('Failed', $report->status_label);
    }

    /**
     * Test status_label accessor for unknown status.
     */
    public function test_status_label_accessor_unknown_status(): void
    {
        $report = Report::factory()->create(['status' => 'unknown']);
        
        $this->assertEquals('Unknown', $report->status_label);
    }

    /**
     * Test status_label accessor for empty status.
     */
    public function test_status_label_accessor_empty_status(): void
    {
        $report = Report::factory()->create(['status' => '']);
        
        $this->assertEquals('', $report->status_label);
    }

    /**
     * Test status constants.
     */
    public function test_status_constants(): void
    {
        $this->assertEquals([
            'pending' => 'Pending',
            'generating' => 'Generating',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ], Report::STATUSES);
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'site_id',
            'name',
            'type',
            'format',
            'status',
            'date_range_start',
            'date_range_end',
            'compare_range_start',
            'compare_range_end',
            'sections',
            'generated_at',
            'file_path',
            'file_size',
            'error',
        ];
        
        $report = new Report();
        $this->assertEquals($fillable, $report->getFillable());
    }

    /**
     * Test compare date ranges can be null.
     */
    public function test_compare_date_ranges_can_be_null(): void
    {
        $report = Report::factory()->create([
            'compare_range_start' => null,
            'compare_range_end' => null,
        ]);
        
        $this->assertNull($report->compare_range_start);
        $this->assertNull($report->compare_range_end);
    }

    /**
     * Test compare date ranges can be set.
     */
    public function test_compare_date_ranges_can_be_set(): void
    {
        $report = Report::factory()->create([
            'compare_range_start' => '2026-01-01',
            'compare_range_end' => '2026-01-31',
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->compare_range_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->compare_range_end);
    }

    /**
     * Test error field can store error message.
     */
    public function test_error_field_can_store_error_message(): void
    {
        $report = Report::factory()->failed()->create([
            'error' => 'Failed to generate report: timeout exceeded',
        ]);
        
        $this->assertEquals('Failed to generate report: timeout exceeded', $report->error);
    }

    /**
     * Test default format is pdf in factory.
     */
    public function test_factory_default_format(): void
    {
        $report = Report::factory()->create();
        
        $this->assertContains($report->format, ['pdf', 'csv']);
    }

    /**
     * Test report can be updated.
     */
    public function test_report_can_be_updated(): void
    {
        $report = Report::factory()->pending()->create();
        
        $report->update([
            'status' => 'completed',
            'generated_at' => now(),
            'file_path' => 'reports/test.pdf',
            'file_size' => 1024,
        ]);
        
        $this->assertEquals('completed', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->generated_at);
        $this->assertEquals('reports/test.pdf', $report->fresh()->file_path);
    }

    /**
     * Test report can be deleted.
     */
    public function test_report_can_be_deleted(): void
    {
        $report = Report::factory()->create();
        
        $report->delete();
        
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /**
     * Test report cascades on site delete.
     */
    public function test_report_cascades_on_site_delete(): void
    {
        $site = Site::factory()->create();
        $report = Report::factory()->create(['site_id' => $site->id]);
        
        $site->delete();
        
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }
}
