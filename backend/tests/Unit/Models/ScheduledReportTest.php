<?php

namespace Tests\Unit\Models;

use App\Models\ScheduledReport;
use App\Models\Site;
use App\Models\ReportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for ScheduledReport model.
 */
class ScheduledReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a scheduled report with valid data.
     */
    public function test_create_scheduled_report_with_valid_data(): void
    {
        $site = Site::factory()->create();

        $scheduled = ScheduledReport::create([
            'site_id' => $site->id,
            'name' => 'Weekly SEO Report',
            'frequency' => 'weekly',
            'format' => 'pdf',
            'recipients' => ['admin@example.com', 'manager@example.com'],
            'enabled' => true,
            'next_run' => now()->addDay(),
        ]);

        $this->assertInstanceOf(ScheduledReport::class, $scheduled);
        $this->assertEquals('Weekly SEO Report', $scheduled->name);
        $this->assertEquals('weekly', $scheduled->frequency);
        $this->assertTrue($scheduled->enabled);
    }

    /**
     * Test scheduled report belongs to site relationship.
     */
    public function test_scheduled_report_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $scheduled = ScheduledReport::factory()->create(['site_id' => $site->id]);

        $this->assertInstanceOf(Site::class, $scheduled->site);
        $this->assertEquals($site->id, $scheduled->site->id);
    }

    /**
     * Test scheduled report belongs to template relationship.
     */
    public function test_scheduled_report_belongs_to_template(): void
    {
        $site = Site::factory()->create();
        $template = ReportTemplate::factory()->create(['site_id' => $site->id]);
        $scheduled = ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'template_id' => $template->id,
        ]);

        $this->assertInstanceOf(ReportTemplate::class, $scheduled->template);
        $this->assertEquals($template->id, $scheduled->template->id);
    }

    /**
     * Test template relationship can be null.
     */
    public function test_template_can_be_null(): void
    {
        $scheduled = ScheduledReport::factory()->create(['template_id' => null]);

        $this->assertNull($scheduled->template);
    }

    /**
     * Test recipients cast to array.
     */
    public function test_recipients_cast_to_array(): void
    {
        $recipients = ['user1@example.com', 'user2@example.com'];

        $scheduled = ScheduledReport::factory()->create([
            'recipients' => $recipients,
        ]);

        $this->assertIsArray($scheduled->recipients);
        $this->assertEquals($recipients, $scheduled->recipients);
    }

    /**
     * Test enabled cast to boolean.
     */
    public function test_enabled_cast_to_boolean(): void
    {
        $scheduled = ScheduledReport::factory()->create(['enabled' => true]);

        $this->assertIsBool($scheduled->enabled);
        $this->assertTrue($scheduled->enabled);
    }

    /**
     * Test calculateNextRun for daily frequency.
     */
    public function test_calculate_next_run_daily(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'daily']);

        $nextRun = $scheduled->calculateNextRun();

        $this->assertTrue($nextRun->isTomorrow());
        $this->assertEquals(9, $nextRun->hour);
    }

    /**
     * Test calculateNextRun for weekly frequency.
     */
    public function test_calculate_next_run_weekly(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'weekly']);

        $nextRun = $scheduled->calculateNextRun();

        $this->assertTrue($nextRun->isMonday());
        $this->assertEquals(9, $nextRun->hour);
    }

    /**
     * Test calculateNextRun for biweekly frequency.
     */
    public function test_calculate_next_run_biweekly(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'biweekly']);

        $nextRun = $scheduled->calculateNextRun();

        $this->assertTrue($nextRun->gt(now()->addWeek()));
        $this->assertEquals(9, $nextRun->hour);
    }

    /**
     * Test calculateNextRun for monthly frequency.
     */
    public function test_calculate_next_run_monthly(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'monthly']);

        $nextRun = $scheduled->calculateNextRun();

        $this->assertEquals(1, $nextRun->day);
        $this->assertEquals(9, $nextRun->hour);
    }

    /**
     * Test calculateNextRun for unknown frequency defaults to weekly.
     */
    public function test_calculate_next_run_unknown_frequency(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'unknown']);

        $nextRun = $scheduled->calculateNextRun();

        // Should default to weekly-ish behavior
        $this->assertNotNull($nextRun);
        $this->assertTrue($nextRun->isFuture());
    }

    /**
     * Test frequency label accessor.
     */
    public function test_frequency_label_accessor(): void
    {
        $frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
        ];

        foreach ($frequencies as $frequency => $label) {
            $scheduled = ScheduledReport::factory()->create(['frequency' => $frequency]);
            $this->assertEquals($label, $scheduled->frequency_label);
        }
    }

    /**
     * Test frequency label for unknown frequency.
     */
    public function test_frequency_label_unknown(): void
    {
        $scheduled = ScheduledReport::factory()->create(['frequency' => 'custom']);

        $this->assertEquals('Custom', $scheduled->frequency_label);
    }

    /**
     * Test frequency constants.
     */
    public function test_frequency_constants(): void
    {
        $this->assertEquals([
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
        ], ScheduledReport::FREQUENCIES);
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'site_id',
            'template_id',
            'name',
            'frequency',
            'format',
            'recipients',
            'enabled',
            'last_run',
            'next_run',
        ];

        $scheduled = new ScheduledReport();
        $this->assertEquals($fillable, $scheduled->getFillable());
    }

    /**
     * Test scheduled report can be updated.
     */
    public function test_scheduled_report_can_be_updated(): void
    {
        $scheduled = ScheduledReport::factory()->create(['enabled' => true]);

        $scheduled->update([
            'enabled' => false,
            'name' => 'Updated Name',
        ]);

        $this->assertFalse($scheduled->fresh()->enabled);
        $this->assertEquals('Updated Name', $scheduled->fresh()->name);
    }

    /**
     * Test scheduled report can be deleted.
     */
    public function test_scheduled_report_can_be_deleted(): void
    {
        $scheduled = ScheduledReport::factory()->create();

        $scheduled->delete();

        $this->assertDatabaseMissing('scheduled_reports', ['id' => $scheduled->id]);
    }

    /**
     * Test last_run and next_run are datetime cast.
     */
    public function test_datetime_casts(): void
    {
        $scheduled = ScheduledReport::factory()->create([
            'last_run' => now(),
            'next_run' => now()->addDay(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $scheduled->last_run);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $scheduled->next_run);
    }

    /**
     * Test last_run can be null.
     */
    public function test_last_run_can_be_null(): void
    {
        $scheduled = ScheduledReport::factory()->create(['last_run' => null]);

        $this->assertNull($scheduled->last_run);
    }
}
