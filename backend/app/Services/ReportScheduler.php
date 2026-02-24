<?php

namespace App\Services;

use App\Models\ScheduledReport;
use App\Repositories\ScheduledReportRepository;
use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduler service for processing automated reports.
 */
class ReportScheduler
{
    protected ScheduledReportRepository $scheduledReportRepository;
    protected ReportRepository $reportRepository;

    public function __construct(
        ScheduledReportRepository $scheduledReportRepository,
        ReportRepository $reportRepository
    ) {
        $this->scheduledReportRepository = $scheduledReportRepository;
        $this->reportRepository = $reportRepository;
    }

    /**
     * Start the scheduler (placeholder for timed job setup or process).
     */
    public function start(): void
    {
        Log::info('Scheduler initialized. Ready to process due reports.');
    }

    /**
     * Process all scheduled reports due for execution.
     */
    public function processDueReports(): void
    {
        $dueReports = $this->scheduledReportRepository->findDue();

        foreach ($dueReports as $scheduled) {
            try {
                Log::info("Processing scheduled report ID {$scheduled->id}");

                $generatedReport = $this->reportRepository->generateFromSchedule($scheduled);

                $this->scheduledReportRepository->markRun($scheduled->id);

                Log::info("Report ID {$generatedReport->id} generated successfully.");
            } catch (Throwable $e) {
                Log::error("Failed to process scheduled report ID {$scheduled->id}. Error: {$e->getMessage()}");
            }
        }
    }
}