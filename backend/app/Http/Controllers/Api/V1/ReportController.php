<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Exporters\CsvReportExporter;
use App\Exporters\PdfReportExporter;
use App\Models\Report;
use App\Models\Site;
use App\Repositories\ReportRepository;
use App\Repositories\ReportBrandingRepository;
use App\Services\ReportDataAggregator;
use App\Services\Traits\EmitsWebhookEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Report Controller for SPEC-004-7, SPEC-004-8, SPEC-004-9.
 * 
 * TASK-4-22: All endpoints require authentication and verify user ownership
 * of the site associated with the report.
 * 
 * TASK-4-16: PDF generation includes branding (colors, fonts, logo, company name).
 * 
 * TASK-6-16: Emits report.completed webhook events after successful generation.
 */
class ReportController extends Controller
{
    use EmitsWebhookEvents;

    protected ReportRepository $reportRepository;
    protected ReportBrandingRepository $brandingRepository;
    protected ReportDataAggregator $dataAggregator;
    protected CsvReportExporter $csvExporter;
    protected PdfReportExporter $pdfExporter;

    public function __construct(
        ReportRepository $reportRepository,
        ReportBrandingRepository $brandingRepository,
        ReportDataAggregator $dataAggregator,
        CsvReportExporter $csvExporter,
        PdfReportExporter $pdfExporter
    ) {
        $this->reportRepository = $reportRepository;
        $this->brandingRepository = $brandingRepository;
        $this->dataAggregator = $dataAggregator;
        $this->csvExporter = $csvExporter;
        $this->pdfExporter = $pdfExporter;
    }

    /**
     * List reports for a site (SPEC-004-7).
     */
    public function index(Request $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        // Authorization check: user must own the site
        $user = $request->user();
        if (!$user || $site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $status = $request->query('status');
        $type = $request->query('type');
        $perPage = min((int) $request->query('perPage', 20), 100);
        $page = max(1, (int) $request->query('page', 1));

        $query = Report::where('site_id', $siteId);

        if ($status && in_array($status, ['pending', 'generating', 'completed', 'failed'])) {
            $query->where('status', $status);
        }

        if ($type && in_array($type, ['daily', 'weekly', 'monthly', 'custom', 'one-time'])) {
            $query->where('type', $type);
        }

        $reports = $query->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $reports->map(fn (Report $report) => $this->formatReport($report)),
            'meta' => [
                'total' => $reports->total(),
                'page' => $reports->currentPage(),
                'perPage' => $reports->perPage(),
            ],
        ]);
    }

    /**
     * Get a single report (SPEC-004-7).
     */
    public function show(Request $request, int $reportId): JsonResponse
    {
        $report = $this->reportRepository->find($reportId);
        if (!$report) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Report not found.',
                ],
            ], 404);
        }

        // Authorization check: user must own the site associated with the report
        $user = $request->user();
        if (!$user || !$report->site || $report->site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this report.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatReport($report),
        ]);
    }

    /**
     * Delete a report (SPEC-004-7).
     */
    public function destroy(Request $request, int $reportId): Response|JsonResponse
    {
        $report = $this->reportRepository->find($reportId);
        if (!$report) {
            return response()->noContent(404);
        }

        // Authorization check: user must own the site associated with the report
        $user = $request->user();
        if (!$user || !$report->site || $report->site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this report.',
                ],
            ], 403);
        }

        // Delete associated file
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        $this->reportRepository->delete($reportId);

        return response()->noContent();
    }

    /**
     * Generate a new report (SPEC-004-8).
     */
    public function generate(Request $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        // Authorization check: user must own the site
        $user = $request->user();
        if (!$user || $site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly,custom,one-time',
            'format' => 'required|in:pdf,csv',
            'dateRange' => 'required|array',
            'dateRange.start' => 'required|date',
            'dateRange.end' => 'required|date|after_or_equal:dateRange.start',
            'compareRange' => 'nullable|array',
            'compareRange.start' => 'nullable|date',
            'compareRange.end' => 'nullable|date|after_or_equal:compareRange.start',
            'sections' => 'nullable|array',
            'sections.*' => 'string|in:cover,summary,keywords,changes,top-performers,needs-attention,trends,competitors,serp-features',
        ]);

        // Create report record
        $report = $this->reportRepository->create([
            'site_id' => $siteId,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'format' => $validated['format'],
            'status' => 'generating',
            'date_range_start' => $validated['dateRange']['start'],
            'date_range_end' => $validated['dateRange']['end'],
            'compare_range_start' => $validated['compareRange']['start'] ?? null,
            'compare_range_end' => $validated['compareRange']['end'] ?? null,
            'sections' => $validated['sections'] ?? ['cover', 'keywords'],
        ]);

        try {
            // Aggregate data
            $data = $this->dataAggregator->aggregate(
                $siteId,
                $validated['dateRange'],
                $validated['compareRange'] ?? null
            );

            // Generate file
            $filename = $this->generateFilename($report, $validated['format']);
            $filePath = "reports/{$filename}";

            if ($validated['format'] === 'csv') {
                $content = $this->csvExporter->generate($data);
            } else {
                // Fetch branding for the site (TASK-4-16)
                $branding = $this->brandingRepository->findBySite($siteId);
                $brandingData = $branding ? [
                    'primaryColor' => $branding->primary_color,
                    'secondaryColor' => $branding->secondary_color,
                    'fontFamily' => $branding->font_family,
                    'companyName' => $branding->company_name,
                    'logoFile' => $branding->logo_file,
                    'logoUrl' => $branding->logo_url,
                ] : null;

                $content = $this->pdfExporter->generate([
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
            $this->emitReportWebhook($report, $site->user_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'reportId' => $report->id,
                    'status' => 'completed',
                    'downloadUrl' => "/api/v1/reports/{$report->id}/download",
                    'generatedAt' => $report->generated_at?->toISOString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GENERATION_FAILED',
                    'message' => 'Report generation failed: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Download a report (SPEC-004-9).
     * 
     * TASK-4-22: Authorization check ensures users can only download
     * reports for sites they own.
     */
    public function download(Request $request, int $reportId): Response|JsonResponse
    {
        $report = $this->reportRepository->find($reportId);
        if (!$report) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Report not found.',
                ],
            ], 404);
        }

        // Authorization check: user must own the site associated with the report
        $user = $request->user();
        if (!$user || !$report->site || $report->site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this report.',
                ],
            ], 403);
        }

        if ($report->status !== 'completed') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REPORT_NOT_READY',
                    'message' => 'Report is not ready for download.',
                ],
            ], 400);
        }

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'Report file not found.',
                ],
            ], 404);
        }

        $filename = $this->generateFilename($report, $report->format);
        $mimeType = $report->format === 'pdf' ? 'application/pdf' : 'text/csv';

        return response(Storage::get($report->file_path))
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Format report for API response.
     */
    protected function formatReport(Report $report): array
    {
        return [
            'id' => $report->id,
            'name' => $report->name,
            'type' => $report->type,
            'format' => $report->format,
            'status' => $report->status,
            'statusLabel' => $report->status_label,
            'dateRange' => [
                'start' => $report->date_range_start?->toDateString(),
                'end' => $report->date_range_end?->toDateString(),
            ],
            'compareRange' => $report->compare_range_start ? [
                'start' => $report->compare_range_start?->toDateString(),
                'end' => $report->compare_range_end?->toDateString(),
            ] : null,
            'sections' => $report->sections,
            'generatedAt' => $report->generated_at?->toISOString(),
            'downloadUrl' => $report->status === 'completed' 
                ? "/api/v1/reports/{$report->id}/download" 
                : null,
            'fileSize' => $report->file_size,
            'error' => $report->error,
            'createdAt' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * Generate a filename for the report.
     */
    protected function generateFilename(Report $report, string $format): string
    {
        $slug = Str::slug($report->name);
        $date = $report->date_range_end?->format('Y-m-d') ?? now()->format('Y-m-d');
        $extension = $format === 'pdf' ? 'pdf' : 'csv';

        return "{$slug}-{$date}.{$extension}";
    }

    /**
     * Emit webhook event for completed report.
     */
    protected function emitReportWebhook(Report $report, int $userId): void
    {
        $downloadUrl = "/api/v1/reports/{$report->id}/download";

        $dateRange = null;
        if ($report->date_range_start && $report->date_range_end) {
            $dateRange = [
                'start' => $report->date_range_start->toDateString(),
                'end' => $report->date_range_end->toDateString(),
            ];
        }

        $this->emitReportCompletedEvent(
            userId: $userId,
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
}
