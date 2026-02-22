<?php

namespace App\Exporters;

class PdfReportExporter
{
    /**
     * Generate a basic PDF report.
     */
    public function generate(array $report, array $data): string
    {
        $sections = $report['sections'] ?? [];
        if (empty($sections)) {
            $sections = ['cover', 'keywords'];
        }

        $pages = [];
        foreach ($sections as $section) {
            $pages = array_merge($pages, $this->buildSectionPages($section, $report, $data));
        }

        if (empty($pages)) {
            $pages[] = ['SEO Report'];
        }

        return $this->renderPdf($pages);
    }

    protected function buildSectionPages(string $section, array $report, array $data): array
    {
        $titleMap = [
            'cover' => 'Cover Page',
            'summary' => 'Executive Summary',
            'keywords' => 'Keyword Rankings',
            'changes' => 'Position Changes',
            'trends' => 'Trend Charts',
        ];

        $title = $titleMap[$section] ?? ucfirst(str_replace('-', ' ', $section));
        $lines = [strtoupper($title)];

        switch ($section) {
            case 'cover':
                $lines[] = $report['name'] ?? 'SEO Report';
                $lines[] = $this->formatDateRange($report);
                $lines[] = 'Logo: [placeholder]';
                if (!empty($data['company_name'])) {
                    $lines[] = 'Prepared for ' . $data['company_name'];
                }
                break;
            case 'summary':
                $summary = $data['executive_summary'] ?? $report['executive_summary'] ?? 'Summary not provided.';
                foreach ($this->wrapLines($summary) as $line) {
                    $lines[] = $line;
                }
                break;
            case 'keywords':
                foreach (($data['keywords'] ?? []) as $row) {
                    $lines[] = sprintf(
                        '%s | Current: %s | Previous: %s | Change: %s',
                        $row['keyword'] ?? 'Keyword',
                        $this->formatValue($row['current_position'] ?? null, 'Not ranked'),
                        $this->formatValue($row['previous_position'] ?? null, 'N/A'),
                        $this->formatValue($row['change'] ?? null, 'N/A')
                    );
                }
                break;
            case 'changes':
                $lines[] = 'Top Performers';
                foreach (($data['top_performers'] ?? []) as $row) {
                    $lines[] = sprintf('%s (Δ %s)', $row['keyword'] ?? 'Keyword', $this->formatValue($row['change'] ?? null, 'N/A'));
                }
                $lines[] = 'Needs Attention';
                foreach (($data['needs_attention'] ?? []) as $row) {
                    $lines[] = sprintf('%s (Δ %s)', $row['keyword'] ?? 'Keyword', $this->formatValue($row['change'] ?? null, 'N/A'));
                }
                break;
            case 'trends':
                foreach (($data['trends'] ?? []) as $trend) {
                    $points = $trend['points'] ?? [];
                    $lines[] = sprintf('%s: %d data points', $trend['keyword'] ?? 'Keyword', count($points));
                }
                if (count($lines) === 1) {
                    $lines[] = 'Trend data not available.';
                }
                break;
            default:
                $lines[] = 'Section content not available.';
        }

        return $this->chunkLines($lines, 38);
    }

    protected function renderPdf(array $pages): string
    {
        $pageCount = count($pages);
        $objects = [];
        $offsets = [];

        $fontObjectId = 3;
        $firstPageObjectId = 4;

        $pageIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageIds[] = $firstPageObjectId + ($i * 2);
        }

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [" . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageIds)) . "] /Count {$pageCount} >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = $pageIds[$i];
            $contentId = $pageId + 1;
            $stream = $this->buildContentStream($pages[$i], $i + 1, $pageCount);
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $objId = $index + 1;
            $pdf .= "{$objId} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function buildContentStream(array $lines, int $page, int $totalPages): string
    {
        $stream = "BT\n/F1 12 Tf\n50 780 Td\n14 TL\n";
        foreach ($lines as $line) {
            $stream .= '(' . $this->escapePdfText($line) . ") Tj\nT*\n";
        }
        $stream .= "ET\n";
        $stream .= "BT\n/F1 10 Tf\n50 30 Td\n(Page {$page} of {$totalPages}) Tj\nET\n";

        return $stream;
    }

    protected function escapePdfText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return $text;
    }

    protected function chunkLines(array $lines, int $maxLines): array
    {
        if (empty($lines)) {
            return [[]];
        }

        $chunks = array_chunk($lines, $maxLines);
        return $chunks;
    }

    protected function formatDateRange(array $report): string
    {
        $start = $report['date_range_start'] ?? $report['dateRangeStart'] ?? null;
        $end = $report['date_range_end'] ?? $report['dateRangeEnd'] ?? null;

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return 'Date range not specified';
    }

    protected function formatValue($value, string $fallback): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (string) $value;
    }

    protected function wrapLines(string $text, int $maxLength = 90): array
    {
        $words = preg_split('/\s+/', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $next = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($next) > $maxLength) {
                $lines[] = $current;
                $current = $word;
                continue;
            }
            $current = $next;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
