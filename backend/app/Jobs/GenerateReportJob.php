<?php

namespace App\Jobs;

use App\Models\Report;
use App\Repositories\ReportRepository;
use App\Repositories\ReportBrandingRepository;
use App\Services\ReportDataAggregator;
use App\Services\Traits\EmitsWebhookEvents;
use App\Exporters\CsvReportExporter;
use App\Exporters\PdfReportExporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Job for asynchronous report generation.
 * 
 * Used by TASK-6-11 (Reports API Endpoint).
 * Dispatched when a report generation request is made via the external API.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, EmitsWebhookEvents;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $reportId,
        public string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ReportRepository $reportRepository,
        ReportBrandingRepository $brandingRepository,
        ReportDataAggregator $dataAggregator,
        CsvReportExporter $csvExporter,
        PdfReportExporter $pdfExporter
    ): void {
        $report = Report::find($this->reportId);

        if (!$report) {
            return;
        }

        // Update status to generating
        $report->update(['status' => 'generating']);

        try {
            $site = $report->site;
            if (!$site) {
                throw new \RuntimeException('Site not found for report.');
            }

            // Aggregate data
            $dateRange = null;
            if ($report->date_range_start && $report->date_range_end) {
                $dateRange = [
                    'start' => $report->date_range_start->toDateString(),
                    'end' => $report->date_range_end->toDateString(),
                ];
            }

            $data = $this->aggregateReportData($dataAggregator, $report, $dateRange);

            // Generate file
            $filename = $this->generateFilename($report);
            $filePath = "reports/{$filename}";

            if ($report->format === 'csv') {
                $content = $csvExporter->generate($data);
            } else {
                // Fetch branding for the site
                $branding = $brandingRepository->findBySite($report->site_id);
                $brandingData = $branding ? [
                    'primaryColor' => $branding->primary_color,
                    'secondaryColor' => $branding->secondary_color,
                    'fontFamily' => $branding->font_family,
                    'companyName' => $branding->company_name,
                    'logoFile' => $branding->logo_file,
                    'logoUrl' => $branding->logo_url,
                ] : null;

                $content = $pdfExporter->generate([
                    'name' => $report->name,
                    'date_range_start' => $report->date_range_start?->toDateString(),
                    'date_range_end' => $report->date_range_end?->toDateString(),
                    'sections' => $report->sections,
                ], $data, $brandingData);
            }

            Storage::put($filePath, $content);

            // Update report record
            $report->update([
                'status' => 'completed',
                'generated_at' => now(),
                'file_path' => $filePath,
                'file_size' => Storage::size($filePath),
            ]);

            $report->refresh();

            // Emit webhook event for report completion
            $this->emitReportWebhook($report);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Aggregate report data.
     */
    protected function aggregateReportData(
        ReportDataAggregator $dataAggregator,
        Report $report,
        ?array $dateRange
    ): array {
        if (!$dateRange) {
            // Use default date range based on report type
            $dateRange = $this->getDefaultDateRange($report->type);
        }

        return $dataAggregator->aggregate(
            $report->site_id,
            $dateRange
        );
    }

    /**
     * Get default date range for report type.
     */
    protected function getDefaultDateRange(string $type): array
    {
        $end = now()->toDateString();
        $start = match ($type) {
            'daily' => now()->subDay()->toDateString(),
            'weekly' => now()->subWeek()->toDateString(),
            'monthly' => now()->subMonth()->toDateString(),
            default => now()->subWeek()->toDateString(),
        };

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Generate a filename for the report.
     */
    protected function generateFilename(Report $report): string
    {
        $slug = Str::slug($report->name);
        $date = $report->date_range_end?->format('Y-m-d') ?? now()->format('Y-m-d');
        $extension = $report->format === 'pdf' ? 'pdf' : 'csv';

        return "{$slug}-{$date}.{$extension}";
    }

    /**
     * Emit webhook event for completed report.
     */
    protected function emitReportWebhook(Report $report): void
    {
        if (!$report->site) {
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
            userId: $report->site->user_id,
            reportId: $report->id,
            siteId: $report->site_id,
            type: $report->type,
            format: $report->format,
            downloadUrl: $downloadUrl,
            fileSize: $report->file_size,
            scheduledReportId: null,
            dateRange: $dateRange
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $report = Report::find($this->reportId);

        if ($report) {
            $report->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
