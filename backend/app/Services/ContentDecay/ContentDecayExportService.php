<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface;

/**
 * Content Decay Export Service Implementation for SPEC-036.
 *
 * Handles export and AI brief generation:
 * - Queue export (CSV, PDF, XLS)
 * - Recovery report generation
 * - AI refresh brief generation
 */
class ContentDecayExportService implements ContentDecayExportServiceInterface
{
    /**
     * Export the decay queue for a site.
     *
     * @param int $siteId
     * @param string $format
     * @param array $filters
     * @return string
     * @throws \InvalidArgumentException
     */
    public function exportQueue(int $siteId, string $format, array $filters = []): string
    {
        if (!in_array($format, $this->getExportFormats())) {
            throw new \InvalidArgumentException("Invalid export format: {$format}. Supported formats: csv, pdf, xls.");
        }

        $project = ContentDecayProject::where('site_id', $siteId)->first();

        if (!$project) {
            throw new \InvalidArgumentException('No content decay project found for this site.');
        }

        $query = ContentDecayCandidate::where('project_id', $project->id);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('first_detected_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('first_detected_at', '<=', $filters['date_to']);
        }

        $candidates = $query->get();

        // Generate filename
        $filename = $this->getExportFilename($siteId, $format, $filters);

        // In a real implementation, this would generate the actual file
        // For now, we return the path where the file would be stored
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Generate content based on format
        switch ($format) {
            case 'csv':
                $this->generateCsv($candidates, $path);
                break;
            case 'pdf':
                $this->generatePdf($candidates, $path, $filters['recovery_report'] ?? false);
                break;
            case 'xls':
                $this->generateXls($candidates, $path);
                break;
        }

        return $path;
    }

    /**
     * Generate an AI refresh brief for a candidate.
     *
     * @param int $candidateId
     * @return array
     */
    public function generateAiBrief(int $candidateId): array
    {
        $candidate = ContentDecayCandidate::find($candidateId);

        if (!$candidate) {
            throw new \InvalidArgumentException('Candidate not found.');
        }

        // Generate a unique job ID
        $jobId = 'ai_brief_' . $candidateId . '_' . now()->format('YmdHis');

        // In a real implementation, this would dispatch a queue job
        return [
            'job_id' => $jobId,
            'status' => 'queued',
        ];
    }

    /**
     * Get available export formats.
     *
     * @return array
     */
    public function getExportFormats(): array
    {
        return ['csv', 'pdf', 'xls'];
    }

    /**
     * Check if AI brief feature is available.
     *
     * @return bool
     */
    public function isAiBriefAvailable(): bool
    {
        // Check if AI service is configured
        return config('services.openai.api_key') !== null || 
               config('services.anthropic.api_key') !== null;
    }

    /**
     * Get the AI brief for a candidate (if already generated).
     *
     * @param int $candidateId
     * @return array|null
     */
    public function getAiBrief(int $candidateId): ?array
    {
        $candidate = ContentDecayCandidate::find($candidateId);

        if (!$candidate) {
            return null;
        }

        // Check if brief exists in metadata
        $evidence = $candidate->evidence_json ?? [];
        
        return $evidence['ai_brief'] ?? null;
    }

    /**
     * Get export filename for a site.
     *
     * @param int $siteId
     * @param string $format
     * @param array $filters
     * @return string
     */
    public function getExportFilename(int $siteId, string $format, array $filters = []): string
    {
        $prefix = isset($filters['recovery_report']) && $filters['recovery_report'] 
            ? 'recovery_report' 
            : 'content_decay_queue';
        
        $date = now()->format('Y-m-d_His');
        
        $extension = $format;
        
        return "{$prefix}_site_{$siteId}_{$date}.{$extension}";
    }

    /**
     * Generate CSV export.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     */
    private function generateCsv($candidates, string $path): void
    {
        $handle = fopen($path, 'w');
        
        // CSV headers
        fputcsv($handle, [
            'URL',
            'Title',
            'Page Type',
            'Severity',
            'Decay Score',
            'Lost Clicks',
            'Recovery Upside',
            'Status',
            'Primary Reason',
            'First Detected',
            'Last Detected',
        ]);

        foreach ($candidates as $candidate) {
            fputcsv($handle, [
                $candidate->page_url,
                $candidate->page_title,
                $candidate->page_type,
                $candidate->severity,
                $candidate->decay_score,
                $candidate->lost_click_estimate,
                $candidate->recovery_upside_estimate,
                $candidate->status,
                $candidate->primary_reason_code,
                $candidate->first_detected_at?->toDateString(),
                $candidate->last_detected_at?->toDateString(),
            ]);
        }

        fclose($handle);
    }

    /**
     * Generate PDF export.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     * @param bool $isRecoveryReport
     */
    private function generatePdf($candidates, string $path, bool $isRecoveryReport = false): void
    {
        // In a real implementation, this would use a PDF library like DomPDF or TCPDF
        // For now, we create a simple placeholder
        file_put_contents($path, "PDF export placeholder - {$candidates->count()} candidates");
    }

    /**
     * Generate XLS export.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     */
    private function generateXls($candidates, string $path): void
    {
        // In a real implementation, this would use PhpSpreadsheet or Laravel Excel
        // For now, we create a simple placeholder
        file_put_contents($path, "XLS export placeholder - {$candidates->count()} candidates");
    }
}
