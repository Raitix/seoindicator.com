<?php

namespace Tests\Feature\Services;

use App\Models\ScheduledReport;
use App\Services\ReportScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSchedulerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_due_reports_correctly(): void
    {
        // Arrange: Create sample scheduled reports
        ScheduledReport::factory()->count(2)->create(['next_run' => now()->subMinute(), 'enabled' => true]);

        // Act: Run the scheduler
        app(ReportScheduler::class)->processDueReports();

        // Assert: Verify results
        $this->assertDatabaseCount('scheduled_reports', 2);
        $this->assertTrue(ScheduledReport::all()->every(fn ($report) => $report->last_run <= now()));
    }
}