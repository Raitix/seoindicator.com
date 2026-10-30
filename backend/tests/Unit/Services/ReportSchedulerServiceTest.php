<?php

namespace Tests\Unit\Services;

use App\Models\ScheduledReport;
use App\Models\Site;
use App\Repositories\ScheduledReportRepository;
use App\Services\ReportScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Comprehensive unit tests for ReportScheduler service.
 */
class ReportSchedulerServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScheduledReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ScheduledReportRepository();
    }

    /**
     * Test scheduler can be instantiated.
     */
    public function test_scheduler_can_be_instantiated(): void
    {
        $scheduler = app(ReportScheduler::class);

        $this->assertInstanceOf(ReportScheduler::class, $scheduler);
    }

    /**
     * Test scheduler start logs initialization.
     */
    public function test_scheduler_start_logs_initialization(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Scheduler initialized. Ready to process due reports.');

        $scheduler = app(ReportScheduler::class);
        $scheduler->start();
    }

    /**
     * Test processDueReports with no due reports.
     */
    public function test_process_due_reports_with_none_due(): void
    {
        $site = Site::factory()->create();

        // Create reports not due yet
        ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => true,
            'next_run' => now()->addDay(),
        ]);

        $scheduler = app(ReportScheduler::class);
        $scheduler->processDueReports();

        // Should not throw, and reports should remain unchanged
        $this->assertDatabaseCount('scheduled_reports', 1);
    }

    /**
     * Test processDueReports with disabled reports.
     */
    public function test_process_due_reports_skips_disabled(): void
    {
        $site = Site::factory()->create();

        ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => false,
            'next_run' => now()->subMinute(),
        ]);

        $scheduler = app(ReportScheduler::class);
        $scheduler->processDueReports();

        // Disabled reports should not be processed
        $report = ScheduledReport::first();
        $this->assertNull($report->last_run);
    }

    /**
     * Test processDueReports processes multiple due reports.
     */
    public function test_process_due_reports_handles_multiple(): void
    {
        $site = Site::factory()->create();

        ScheduledReport::factory()->count(3)->create([
            'site_id' => $site->id,
            'enabled' => true,
            'next_run' => now()->subMinute(),
        ]);

        $scheduler = app(ReportScheduler::class);
        $scheduler->processDueReports();

        // All due reports should have last_run set
        $reports = ScheduledReport::all();
        foreach ($reports as $report) {
            $this->assertNotNull($report->last_run);
        }
    }

    /**
     * Test findDue returns only enabled and overdue reports.
     */
    public function test_find_due_returns_correct_reports(): void
    {
        $site = Site::factory()->create();

        // Due report (enabled, overdue)
        ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => true,
            'next_run' => now()->subHour(),
        ]);

        // Not due (enabled, future)
        ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => true,
            'next_run' => now()->addDay(),
        ]);

        // Disabled (overdue, but disabled)
        ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => false,
            'next_run' => now()->subHour(),
        ]);

        $due = $this->repository->findDue();

        $this->assertCount(1, $due);
    }

    /**
     * Test markRun updates timestamps correctly.
     */
    public function test_mark_run_updates_timestamps(): void
    {
        $site = Site::factory()->create();

        $scheduled = ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'frequency' => 'daily',
            'next_run' => now()->subMinute(),
            'last_run' => null,
        ]);

        $this->repository->markRun($scheduled->id);

        $scheduled->refresh();

        $this->assertNotNull($scheduled->last_run);
        $this->assertNotNull($scheduled->next_run);
        $this->assertTrue($scheduled->next_run->isFuture());
    }

    /**
     * Test repository CRUD operations.
     */
    public function test_repository_crud_operations(): void
    {
        $site = Site::factory()->create();

        // Create
        $scheduled = $this->repository->create([
            'site_id' => $site->id,
            'name' => 'Test Schedule',
            'frequency' => 'weekly',
            'format' => 'pdf',
            'recipients' => ['test@example.com'],
            'enabled' => true,
            'next_run' => now()->addDay(),
        ]);

        $this->assertInstanceOf(ScheduledReport::class, $scheduled);
        $this->assertEquals('Test Schedule', $scheduled->name);

        // Find
        $found = $this->repository->find($scheduled->id);
        $this->assertEquals($scheduled->id, $found->id);

        // Update
        $updated = $this->repository->update($scheduled->id, ['name' => 'Updated Name']);
        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $scheduled->fresh()->name);

        // Delete
        $deleted = $this->repository->delete($scheduled->id);
        $this->assertTrue($deleted);
        $this->assertNull($this->repository->find($scheduled->id));
    }

    /**
     * Test findBySite returns correct reports.
     */
    public function test_find_by_site_returns_correct(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        ScheduledReport::factory()->count(3)->create(['site_id' => $site1->id]);
        ScheduledReport::factory()->count(2)->create(['site_id' => $site2->id]);

        $site1Reports = $this->repository->findBySite($site1->id);
        $site2Reports = $this->repository->findBySite($site2->id);

        $this->assertCount(3, $site1Reports);
        $this->assertCount(2, $site2Reports);
    }

    /**
     * Test markRun returns false for invalid ID.
     */
    public function test_mark_run_returns_false_for_invalid(): void
    {
        $result = $this->repository->markRun(9999);

        $this->assertFalse($result);
    }

    /**
     * Test update returns false for invalid ID.
     */
    public function test_update_returns_false_for_invalid(): void
    {
        $result = $this->repository->update(9999, ['name' => 'test']);

        $this->assertFalse($result);
    }

    /**
     * Test delete returns false for invalid ID.
     */
    public function test_delete_returns_false_for_invalid(): void
    {
        $result = $this->repository->delete(9999);

        $this->assertFalse($result);
    }

    /**
     * Test frequency variations.
     */
    public function test_frequency_variations(): void
    {
        $site = Site::factory()->create();
        $frequencies = ['daily', 'weekly', 'biweekly', 'monthly'];

        foreach ($frequencies as $frequency) {
            $scheduled = ScheduledReport::factory()->create([
                'site_id' => $site->id,
                'frequency' => $frequency,
            ]);

            $this->assertEquals($frequency, $scheduled->frequency);
        }
    }

    /**
     * Test recipients are stored as array.
     */
    public function test_recipients_stored_as_array(): void
    {
        $site = Site::factory()->create();
        $recipients = ['user1@example.com', 'user2@example.com'];

        $scheduled = ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'recipients' => $recipients,
        ]);

        $this->assertEquals($recipients, $scheduled->recipients);
    }

    /**
     * Test enabled toggle.
     */
    public function test_enabled_toggle(): void
    {
        $site = Site::factory()->create();

        $scheduled = ScheduledReport::factory()->create([
            'site_id' => $site->id,
            'enabled' => true,
        ]);

        $this->assertTrue($scheduled->enabled);

        $scheduled->update(['enabled' => false]);

        $this->assertFalse($scheduled->fresh()->enabled);
    }
}
