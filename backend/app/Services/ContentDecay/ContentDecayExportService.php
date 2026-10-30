<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
     * Page dimensions (A4 in points) for PDF generation.
     */
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 40;
    private const ROWS_PER_PAGE = 30;

    /**
     * Default branding colors for PDF.
     */
    private const DEFAULT_PRIMARY_COLOR = '#2563eb';
    private const DEFAULT_SECONDARY_COLOR = '#64748b';

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
     * Generate CSV export with all required columns.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     */
    private function generateCsv($candidates, string $path): void
    {
        $handle = fopen($path, 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");
        
        // CSV headers - full list as per acceptance criteria
        fputcsv($handle, [
            'URL',
            'Title',
            'Page Type',
            'Decay Score',
            'Severity',
            'Confidence',
            'Primary Reason',
            'All Reason Codes',
            'Lost Clicks',
            'Recovery Upside',
            'Status',
            'First Detected',
            'Last Detected',
            'Planned Date',
            'Started Date',
            'Refreshed Date',
            'Recovered Date',
        ]);

        foreach ($candidates as $candidate) {
            fputcsv($handle, [
                $candidate->page_url,
                $candidate->page_title,
                $candidate->page_type,
                $candidate->decay_score,
                $candidate->severity,
                $candidate->detection_confidence,
                $candidate->primary_reason_code,
                $this->formatReasonCodes($candidate->reason_codes),
                $candidate->lost_click_estimate,
                $candidate->recovery_upside_estimate,
                $candidate->status,
                $candidate->first_detected_at?->toDateString(),
                $candidate->last_detected_at?->toDateString(),
                $candidate->planned_at?->toDateString(),
                $candidate->started_at?->toDateString(),
                $candidate->refreshed_at?->toDateString(),
                $candidate->recovered_at?->toDateString(),
            ]);
        }

        fclose($handle);
    }

    /**
     * Generate PDF export with summary and detailed sections.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     * @param bool $isRecoveryReport
     */
    private function generatePdf($candidates, string $path, bool $isRecoveryReport = false): void
    {
        $pages = [];
        
        // Summary page
        $pages = array_merge($pages, $this->renderPdfSummaryPage($candidates, $isRecoveryReport));
        
        // Detailed candidate list (may span multiple pages)
        $pages = array_merge($pages, $this->renderPdfCandidatesPages($candidates, $isRecoveryReport));
        
        // Reason code breakdown page
        $pages = array_merge($pages, $this->renderPdfReasonBreakdownPage($candidates));
        
        // Generate PDF content
        $pdfContent = $this->assemblePdf($pages);
        
        file_put_contents($path, $pdfContent);
    }

    /**
     * Generate XLS export using PhpSpreadsheet.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string $path
     */
    private function generateXls($candidates, string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Content Decay Queue');
        
        // Define column headers
        $headers = [
            'URL',
            'Title',
            'Page Type',
            'Decay Score',
            'Severity',
            'Confidence',
            'Primary Reason',
            'All Reason Codes',
            'Lost Clicks',
            'Recovery Upside',
            'Status',
            'First Detected',
            'Last Detected',
            'Planned Date',
            'Started Date',
            'Refreshed Date',
            'Recovered Date',
        ];
        
        // Write headers
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }
        
        // Format header row
        $lastColumn = $this->getColumnLetter(count($headers));
        $headerRange = "A1:{$lastColumn}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($headerRange)->getBorders()->applyFromArray([
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
        ]);
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Write data rows
        $row = 2;
        foreach ($candidates as $candidate) {
            $sheet->setCellValue([1, $row], $candidate->page_url);
            $sheet->setCellValue([2, $row], $candidate->page_title);
            $sheet->setCellValue([3, $row], $candidate->page_type);
            $sheet->setCellValue([4, $row], $candidate->decay_score);
            $sheet->setCellValue([5, $row], $candidate->severity);
            $sheet->setCellValue([6, $row], $candidate->detection_confidence);
            $sheet->setCellValue([7, $row], $candidate->primary_reason_code);
            $sheet->setCellValue([8, $row], $this->formatReasonCodes($candidate->reason_codes));
            $sheet->setCellValue([9, $row], $candidate->lost_click_estimate);
            $sheet->setCellValue([10, $row], $candidate->recovery_upside_estimate);
            $sheet->setCellValue([11, $row], $candidate->status);
            $sheet->setCellValue([12, $row], $candidate->first_detected_at?->toDateString());
            $sheet->setCellValue([13, $row], $candidate->last_detected_at?->toDateString());
            $sheet->setCellValue([14, $row], $candidate->planned_at?->toDateString());
            $sheet->setCellValue([15, $row], $candidate->started_at?->toDateString());
            $sheet->setCellValue([16, $row], $candidate->refreshed_at?->toDateString());
            $sheet->setCellValue([17, $row], $candidate->recovered_at?->toDateString());
            $row++;
        }
        
        // Auto-size columns
        for ($col = 1; $col <= count($headers); $col++) {
            $colLetter = $this->getColumnLetter($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        
        // Freeze header row
        $sheet->freezePane('A2');
        
        // Add auto-filter
        if ($row > 2) {
            $sheet->setAutoFilter("A1:{$lastColumn}" . ($row - 1));
        }
        
        // Write to file
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    /**
     * Format reason codes array as a comma-separated string.
     *
     * @param array|null $reasonCodes
     * @return string
     */
    private function formatReasonCodes(?array $reasonCodes): string
    {
        if (empty($reasonCodes)) {
            return '';
        }
        
        return implode(', ', $reasonCodes);
    }

    /**
     * Get column letter from index (for Excel).
     *
     * @param int $index
     * @return string
     */
    private function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int) ($index / 26);
        }
        return $letter;
    }

    // ==========================================
    // PDF Generation Methods
    // ==========================================

    /**
     * Render PDF summary page.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param bool $isRecoveryReport
     * @return array
     */
    private function renderPdfSummaryPage($candidates, bool $isRecoveryReport): array
    {
        $totalCandidates = $candidates->count();
        $criticalCount = $candidates->where('severity', 'critical')->count();
        $highCount = $candidates->where('severity', 'high')->count();
        $mediumCount = $candidates->where('severity', 'medium')->count();
        $lowCount = $candidates->where('severity', 'low')->count();
        
        $totalLostClicks = $candidates->sum('lost_click_estimate');
        $totalRecoveryUpside = $candidates->sum('recovery_upside_estimate');
        $avgDecayScore = $candidates->avg('decay_score');
        
        $lines = [];
        
        // Title
        $lines[] = $isRecoveryReport ? 'CONTENT DECAY RECOVERY REPORT' : 'CONTENT DECAY QUEUE EXPORT';
        $lines[] = '';
        $lines[] = 'Executive Summary';
        $lines[] = '';
        
        // Totals
        $lines[] = "Total Candidates: {$totalCandidates}";
        $lines[] = '';
        
        // Severity breakdown
        $lines[] = 'Severity Distribution:';
        $lines[] = "  Critical: {$criticalCount}";
        $lines[] = "  High: {$highCount}";
        $lines[] = "  Medium: {$mediumCount}";
        $lines[] = "  Low: {$lowCount}";
        $lines[] = '';
        
        // Impact metrics
        $lines[] = 'Impact Metrics:';
        $lines[] = "  Total Lost Clicks (Est.): " . number_format($totalLostClicks);
        $lines[] = "  Recovery Upside (Est.): " . number_format($totalRecoveryUpside);
        $lines[] = "  Average Decay Score: " . number_format($avgDecayScore, 2);
        $lines[] = '';
        
        // Top 5 priority pages (highest decay score)
        $topPages = $candidates->sortByDesc('decay_score')->take(5);
        if ($topPages->count() > 0) {
            $lines[] = 'Top 5 Priority Pages:';
            $rank = 1;
            foreach ($topPages as $page) {
                $title = $this->truncate($page->page_title ?? $page->page_url, 40);
                $lines[] = "  {$rank}. {$title} (Score: {$page->decay_score})";
                $rank++;
            }
        }
        
        return $this->createPdfPage($lines, 'summary');
    }

    /**
     * Render PDF candidates pages (paginated).
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param bool $isRecoveryReport
     * @return array
     */
    private function renderPdfCandidatesPages($candidates, bool $isRecoveryReport): array
    {
        if ($candidates->isEmpty()) {
            return $this->createPdfPage(['DETAILED QUEUE', '', 'No candidates to display.'], 'candidates');
        }
        
        $pages = [];
        $chunks = $candidates->chunk(self::ROWS_PER_PAGE);
        $chunkIndex = 0;
        
        foreach ($chunks as $chunk) {
            $lines = [];
            
            // Page header
            $lines[] = $chunkIndex === 0 ? 'DETAILED QUEUE' : 'DETAILED QUEUE (continued)';
            $lines[] = '';
            
            // Table header
            $lines[] = str_pad('URL', 40) . str_pad('Score', 8) . str_pad('Severity', 10) . 'Status';
            $lines[] = str_repeat('-', 75);
            
            foreach ($chunk as $candidate) {
                $url = $this->truncate($candidate->page_url, 38);
                $score = number_format($candidate->decay_score, 1);
                $severity = ucfirst($candidate->severity);
                $status = ucfirst(str_replace('_', ' ', $candidate->status));
                
                $lines[] = str_pad($url, 40) . str_pad($score, 8) . str_pad($severity, 10) . $status;
            }
            
            $pages = array_merge($pages, $this->createPdfPage($lines, 'candidates'));
            $chunkIndex++;
        }
        
        return $pages;
    }

    /**
     * Render PDF reason code breakdown page.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @return array
     */
    private function renderPdfReasonBreakdownPage($candidates): array
    {
        $lines = [];
        
        $lines[] = 'REASON CODE BREAKDOWN';
        $lines[] = '';
        
        // Count reason codes
        $reasonCounts = [];
        foreach ($candidates as $candidate) {
            $codes = $candidate->reason_codes ?? [];
            foreach ($codes as $code) {
                if (!isset($reasonCounts[$code])) {
                    $reasonCounts[$code] = 0;
                }
                $reasonCounts[$code]++;
            }
        }
        
        // Sort by count descending
        arsort($reasonCounts);
        
        if (empty($reasonCounts)) {
            $lines[] = 'No reason codes recorded.';
        } else {
            $lines[] = str_pad('Reason Code', 25) . str_pad('Count', 10) . 'Percentage';
            $lines[] = str_repeat('-', 50);
            
            $total = $candidates->count();
            foreach ($reasonCounts as $code => $count) {
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $label = $this->getReasonLabel($code);
                $lines[] = str_pad($label, 25) . str_pad($count, 10) . $percentage . '%';
            }
        }
        
        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'Generated: ' . now()->format('Y-m-d H:i:s');
        
        return $this->createPdfPage($lines, 'breakdown');
    }

    /**
     * Get human-readable label for reason code.
     *
     * @param string $code
     * @return string
     */
    private function getReasonLabel(string $code): string
    {
        $labels = [
            'clicks_down' => 'Clicks Declined',
            'impressions_down' => 'Impressions Declined',
            'ctr_down' => 'CTR Declined',
            'avg_position_down' => 'Position Declined',
            'rank_visibility_down' => 'Rank Visibility Down',
            'ai_visibility_down' => 'AI Visibility Down',
        ];
        
        return $labels[$code] ?? $code;
    }

    /**
     * Create a PDF page data structure.
     *
     * @param array $lines
     * @param string $section
     * @return array
     */
    private function createPdfPage(array $lines, string $section): array
    {
        return [[
            'lines' => $lines,
            'section' => $section,
        ]];
    }

    /**
     * Assemble PDF from pages.
     *
     * @param array $pages
     * @return string
     */
    private function assemblePdf(array $pages): string
    {
        $pageCount = count($pages);
        $objects = [];
        $offsets = [];
        
        $fontObjectId = 3;
        $firstPageObjectId = 4;
        
        // Calculate page object IDs
        $pageIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageIds[] = $firstPageObjectId + ($i * 2);
        }
        
        // Object 1: Catalog
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        
        // Object 2: Pages collection
        $objects[] = "<< /Type /Pages /Kids [" . implode(' ', array_map(fn($id) => $id . ' 0 R', $pageIds)) . "] /Count {$pageCount} >>";
        
        // Object 3: Font
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        
        // Convert colors to RGB
        $primaryRgb = $this->hexToRgb(self::DEFAULT_PRIMARY_COLOR);
        $secondaryRgb = $this->hexToRgb(self::DEFAULT_SECONDARY_COLOR);
        
        // Build page objects
        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = $pageIds[$i];
            $contentId = $pageId + 1;
            
            $stream = $this->buildPdfContentStream(
                $pages[$i],
                $i + 1,
                $pageCount,
                $primaryRgb,
                $secondaryRgb
            );
            
            // Page object
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . "] /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> /Contents {$contentId} 0 R >>";
            
            // Content stream object
            $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
        }
        
        // Build PDF document
        $pdf = "%PDF-1.4\n";
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $objId = $index + 1;
            $pdf .= "{$objId} 0 obj\n{$object}\nendobj\n";
        }
        
        // Cross-reference table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string)$offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        
        // Trailer
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
        
        return $pdf;
    }

    /**
     * Build PDF content stream for a page.
     *
     * @param array $pageData
     * @param int $pageNum
     * @param int $totalPages
     * @param array $primaryRgb
     * @param array $secondaryRgb
     * @return string
     */
    private function buildPdfContentStream(
        array $pageData,
        int $pageNum,
        int $totalPages,
        array $primaryRgb,
        array $secondaryRgb
    ): string {
        $lines = $pageData['lines'] ?? [];
        
        $stream = '';
        
        // Begin text block
        $stream .= "BT\n";
        
        // Start position (margin from top)
        $y = self::PAGE_HEIGHT - self::MARGIN;
        
        foreach ($lines as $index => $line) {
            // Check if we need a new page
            if ($y < self::MARGIN + 30) {
                break;
            }
            
            // First line (title) - larger font, primary color
            if ($index === 0) {
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $primaryRgb[0]/255, $primaryRgb[1]/255, $primaryRgb[2]/255);
                $stream .= "/F1 16 Tf\n";
                $stream .= sprintf("%d %d Td\n", self::MARGIN, $y);
                $stream .= '(' . $this->escapePdfText($line) . ") Tj\n";
                $y -= 20;
            } else {
                // Body text - normal font, secondary color
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryRgb[0]/255, $secondaryRgb[1]/255, $secondaryRgb[2]/255);
                $stream .= "/F1 10 Tf\n";
                $stream .= sprintf("%d %d Td\n", self::MARGIN, $y);
                $stream .= '(' . $this->escapePdfText($line) . ") Tj\n";
                $y -= 14;
            }
        }
        
        $stream .= "ET\n";
        
        // Page number
        $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryRgb[0]/255, $secondaryRgb[1]/255, $secondaryRgb[2]/255);
        $stream .= "BT\n/F1 9 Tf\n";
        $stream .= sprintf("%d %d Td\n", self::MARGIN, 25);
        $stream .= '(Page ' . $pageNum . ' of ' . $totalPages . ") Tj\n";
        $stream .= "ET\n";
        
        return $stream;
    }

    /**
     * Convert hex color to RGB array.
     *
     * @param string $hex
     * @return array [r, g, b] values 0-255
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = ltrim(self::DEFAULT_PRIMARY_COLOR, '#');
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Escape special characters for PDF text.
     *
     * @param string $text
     * @return string
     */
    private function escapePdfText(string $text): string
    {
        $text = strip_tags($text);
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        
        return $text;
    }

    /**
     * Truncate string to max length.
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    private function truncate(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}
