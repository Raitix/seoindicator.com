<?php

namespace App\Services;

use App\Models\ScheduledReport;
use App\Repositories\ScheduledReportRepository;
use App\Repositories\ReportRepository;
use App\Services\Traits\EmitsWebhookEvents;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduler service for processing automated reports.
 *
 * Emits report.completed webhook events after successful report generation.
 */
class ReportScheduler
{
    use EmitsWebhookEvents;

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

                // Emit webhook event for report completion
                $this->emitReportCompletedWebhook($generatedReport, $scheduled);
            } catch (Throwable $e) {
                Log::error("Failed to process scheduled report ID {$scheduled->id}. Error: {$e->getMessage()}");
            }
        }
    }

    /**
     * Emit webhook event for completed report.
     */
    protected function emitReportCompletedWebhook(Report $report, ScheduledReport $scheduled): void
    {
        // Get user ID from the site
        $site = $report->site;
        if (!$site || !$site->user_id) {
            Log::warning('Cannot emit report.completed webhook: no user_id for report site', [
                'report_id' => $report->id,
                'site_id' => $report->site_id,
            ]);
            return;
        }

        $downloadUrl = "/api/v1/reports/{$report->id}/download";

        $dateRange = null;
        if ($report->date_range_start && $report->date_range_end) {
            $dateRange = [
                'start' => $report->date_range_start->toDateString(),
                'end' => $report->date_range_end->toDateString(),
            ];
        }

        $this->emitReportCompletedEvent(
            userId: $site->user_id,
            reportId: $report->id,
            siteId: $report->site_id,
            type: $report->type,
            format: $report->format,
            downloadUrl: $downloadUrl,
            fileSize: $report->file_size,
            scheduledReportId: $scheduled->id,
            dateRange: $dateRange
        );
    }
}
