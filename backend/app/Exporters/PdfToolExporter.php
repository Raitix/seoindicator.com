<?php

namespace App\Exporters;

use App\Services\Tools\ToolResult;

/**
 * PDF Exporter for Tool Analysis Results.
 *
 * Generates multi-page PDF reports for tool analysis results with:
 * - Branded cover page
 * - Summary section with scores
 * - Indicators table grouped by category
 * - Recommendations section
 *
 * TASK-29-1: PdfToolExporter Implementation
 */
class PdfToolExporter
{
    /**
     * Page dimensions (A4 in points).
     */
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 40;
    private const ROWS_PER_PAGE = 25;

    /**
     * Default branding values.
     */
    private const DEFAULT_PRIMARY_COLOR = '#2563eb';
    private const DEFAULT_SECONDARY_COLOR = '#64748b';
    private const DEFAULT_FONT_FAMILY = 'Helvetica';

    /**
     * Font family mapping for PDF (maps friendly names to PDF core fonts).
     */
    private const FONT_MAP = [
        'inter' => 'Helvetica',
        'roboto' => 'Helvetica',
        'open sans' => 'Helvetica',
        'lato' => 'Helvetica',
        'arial' => 'Helvetica',
        'times' => 'Times-Roman',
        'times new roman' => 'Times-Roman',
        'courier' => 'Courier',
        'courier new' => 'Courier',
        'helvetica' => 'Helvetica',
    ];

    /**
     * Status icon mapping for indicators.
     */
    private const STATUS_ICONS = [
        'pass' => 'OK',
        'ok' => 'OK',
        'warning' => 'WARN',
        'fail' => 'FAIL',
        'error' => 'ERR',
    ];

    /**
     * Generate a PDF export of tool analysis results.
     *
     * @param ToolResult $result The tool analysis result
     * @param array|null $branding Optional branding settings
     * @return string PDF content
     */
    public function export(ToolResult $result, ?array $branding = null): string
    {
        $branding = $this->normalizeBranding($branding);
        
        $pages = [];
        
        // Cover page
        $pages = array_merge($pages, $this->renderCoverPage($result, $branding));
        
        // Summary section
        $pages = array_merge($pages, $this->renderSummary($result, $branding));
        
        // Indicators (may span multiple pages)
        $pages = array_merge($pages, $this->renderIndicators($result, $branding));
        
        // Recommendations section
        $pages = array_merge($pages, $this->renderRecommendations($result, $branding));
        
        return $this->assemblePdf($pages, $branding);
    }

    /**
     * Render the cover page.
     *
     * @param ToolResult $result
     * @param array $branding
     * @return array Array of page data
     */
    protected function renderCoverPage(ToolResult $result, array $branding): array
    {
        $lines = [];
        
        // Logo placeholder or company name
        if (!empty($branding['company_name'])) {
            $lines[] = $branding['company_name'];
        } else {
            $lines[] = 'Logo: [placeholder]';
        }
        
        $lines[] = ''; // Spacer
        $lines[] = ''; // Spacer
        
        // Tool name
        $toolName = $result->toolName ?: 'Tool Analysis';
        $lines[] = $toolName;
        $lines[] = '';
        
        // URL analyzed
        $lines[] = 'URL Analyzed:';
        $lines[] = $result->url ?: 'N/A';
        $lines[] = '';
        
        // Analysis date
        $lines[] = 'Analysis Date:';
        $analyzedAt = $result->analyzedAt ?: date('Y-m-d H:i:s');
        $lines[] = $analyzedAt;
        
        // Status indicator
        if (!$result->success) {
            $lines[] = '';
            $lines[] = 'STATUS: ANALYSIS FAILED';
            if ($result->error) {
                $lines[] = 'Error: ' . $result->error;
            }
        }
        
        return $this->createPage($lines, $branding, 'cover');
    }

    /**
     * Render the summary section.
     *
     * @param ToolResult $result
     * @param array $branding
     * @return array Array of page data
     */
    protected function renderSummary(ToolResult $result, array $branding): array
    {
        $data = $result->data ?? [];
        $summary = $data['summary'] ?? [];
        
        $lines = [];
        $lines[] = 'SUMMARY';
        $lines[] = '';
        
        // Overall score
        $score = $summary['score'] ?? 0;
        $lines[] = 'Overall Score: ' . $score . '%';
        $lines[] = '';
        
        // Pass/Warning/Fail counts
        $pass = $summary['pass'] ?? 0;
        $warning = $summary['warning'] ?? 0;
        $fail = $summary['fail'] ?? 0;
        
        $lines[] = 'Passed: ' . $pass;
        $lines[] = 'Warnings: ' . $warning;
        $lines[] = 'Failed: ' . $fail;
        $lines[] = '';
        
        // Quick stats
        $totalIndicators = $pass + $warning + $fail;
        $lines[] = 'Total Indicators: ' . $totalIndicators;
        
        return $this->createPage($lines, $branding, 'summary');
    }

    /**
     * Render the indicators section (may span multiple pages).
     *
     * @param ToolResult $result
     * @param array $branding
     * @return array Array of page data
     */
    protected function renderIndicators(ToolResult $result, array $branding): array
    {
        $data = $result->data ?? [];
        $indicators = $data['indicators'] ?? [];
        
        if (empty($indicators)) {
            return $this->createPage(['INDICATORS', '', 'No indicators to display.'], $branding, 'indicators');
        }
        
        // Group indicators by category
        $grouped = [];
        foreach ($indicators as $indicator) {
            $category = $indicator['category'] ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $indicator;
        }
        
        // Flatten for pagination with category headers
        $rows = [];
        foreach ($grouped as $category => $categoryIndicators) {
            foreach ($categoryIndicators as $indicator) {
                $rows[] = [
                    'category' => $category,
                    'name' => $indicator['name'] ?? 'Unknown',
                    'value' => $indicator['value'] ?? '-',
                    'status' => $indicator['status'] ?? 'unknown',
                ];
            }
        }
        
        // Paginate at 25 rows per page
        $pages = [];
        $chunks = array_chunk($rows, self::ROWS_PER_PAGE);
        
        foreach ($chunks as $index => $chunk) {
            $lines = [];
            if ($index === 0) {
                $lines[] = 'INDICATORS';
            } else {
                $lines[] = 'INDICATORS (continued)';
            }
            $lines[] = '';
            
            // Table header
            $lines[] = str_pad('Category', 20) . str_pad('Indicator', 25) . str_pad('Value', 20) . 'Status';
            $lines[] = str_repeat('-', 75);
            
            $currentCategory = null;
            foreach ($chunk as $row) {
                // Show category only on first occurrence in this chunk
                if ($row['category'] !== $currentCategory) {
                    $currentCategory = $row['category'];
                    $categoryStr = $row['category'];
                } else {
                    $categoryStr = '';
                }
                
                $statusIcon = self::STATUS_ICONS[$row['status']] ?? strtoupper($row['status']);
                $lines[] = str_pad($this->truncate($categoryStr, 18), 20) .
                          str_pad($this->truncate($row['name'], 23), 25) .
                          str_pad($this->truncate((string)$row['value'], 18), 20) .
                          $statusIcon;
            }
            
            $pages = array_merge($pages, $this->createPage($lines, $branding, 'indicators'));
        }
        
        return $pages;
    }

    /**
     * Render the recommendations section.
     *
     * @param ToolResult $result
     * @param array $branding
     * @return array Array of page data
     */
    protected function renderRecommendations(ToolResult $result, array $branding): array
    {
        $data = $result->data ?? [];
        $recommendations = $data['recommendations'] ?? [];
        
        $lines = [];
        $lines[] = 'Recommendations';
        $lines[] = '';
        
        if (empty($recommendations)) {
            $lines[] = 'No recommendations available.';
            return $this->createPage($lines, $branding, 'recommendations');
        }
        
        // Sort by priority (high -> medium -> low)
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            $pa = $priorityOrder[$a['priority'] ?? 'low'] ?? 3;
            $pb = $priorityOrder[$b['priority'] ?? 'low'] ?? 3;
            return $pa <=> $pb;
        });
        
        foreach ($recommendations as $rec) {
            $priority = strtoupper($rec['priority'] ?? 'MEDIUM');
            $message = $rec['message'] ?? '';
            $action = $rec['action'] ?? '';
            
            $lines[] = '[' . $priority . '] ' . $message;
            if ($action) {
                $lines[] = '  Action: ' . $action;
            }
            $lines[] = '';
        }
        
        return $this->createPage($lines, $branding, 'recommendations');
    }

    /**
     * Create page data structure.
     *
     * @param array $lines
     * @param array $branding
     * @param string $section
     * @return array
     */
    protected function createPage(array $lines, array $branding, string $section): array
    {
        return [[
            'lines' => $lines,
            'section' => $section,
            'colors' => [
                'primary' => $branding['primary_color'],
                'secondary' => $branding['secondary_color'],
            ],
            'font' => $branding['font_family'],
        ]];
    }

    /**
     * Assemble the final PDF from pages.
     *
     * @param array $pages
     * @param array $branding
     * @return string PDF content
     */
    protected function assemblePdf(array $pages, array $branding): string
    {
        $pageCount = count($pages);
        $objects = [];
        $offsets = [];
        
        // Map font family to PDF font
        $pdfFont = $this->mapFontFamily($branding['font_family'] ?? self::DEFAULT_FONT_FAMILY);
        
        // Convert colors to RGB
        $primaryRgb = $this->hexToRgb($branding['primary_color'] ?? self::DEFAULT_PRIMARY_COLOR);
        $secondaryRgb = $this->hexToRgb($branding['secondary_color'] ?? self::DEFAULT_SECONDARY_COLOR);
        
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
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /{$pdfFont} >>";
        
        // Build page objects
        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = $pageIds[$i];
            $contentId = $pageId + 1;
            
            $stream = $this->buildContentStream(
                $pages[$i],
                $i + 1,
                $pageCount,
                $primaryRgb,
                $secondaryRgb,
                $pdfFont
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
     * Build content stream for a page.
     *
     * @param array $pageData
     * @param int $pageNum
     * @param int $totalPages
     * @param array $primaryRgb
     * @param array $secondaryRgb
     * @param string $font
     * @return string
     */
    protected function buildContentStream(
        array $pageData,
        int $pageNum,
        int $totalPages,
        array $primaryRgb,
        array $secondaryRgb,
        string $font
    ): string {
        $lines = $pageData['lines'] ?? [];
        $section = $pageData['section'] ?? '';
        $pageColors = $pageData['colors'] ?? [];
        
        $primaryColor = !empty($pageColors['primary']) ? $this->hexToRgb($pageColors['primary']) : $primaryRgb;
        $secondaryColor = !empty($pageColors['secondary']) ? $this->hexToRgb($pageColors['secondary']) : $secondaryRgb;
        
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
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $primaryColor[0]/255, $primaryColor[1]/255, $primaryColor[2]/255);
                $stream .= "/F1 16 Tf\n";
                $stream .= sprintf("%d %d Td\n", self::MARGIN, $y);
                $stream .= '(' . $this->escapePdfText($line) . ") Tj\n";
                $y -= 20;
            } else {
                // Body text - normal font, secondary color
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryColor[0]/255, $secondaryColor[1]/255, $secondaryColor[2]/255);
                $stream .= "/F1 11 Tf\n";
                $stream .= sprintf("%d %d Td\n", self::MARGIN, $y);
                $stream .= '(' . $this->escapePdfText($line) . ") Tj\n";
                $y -= 16;
            }
        }
        
        $stream .= "ET\n";
        
        // Page number
        $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryRgb[0]/255, $secondaryRgb[1]/255, $secondaryRgb[2]/255);
        $stream .= "BT\n/F1 10 Tf\n";
        $stream .= sprintf("%d %d Td\n", self::MARGIN, 30);
        $stream .= '(Page ' . $pageNum . ' of ' . $totalPages . ") Tj\n";
        $stream .= "ET\n";
        
        return $stream;
    }

    /**
     * Normalize branding settings with defaults.
     *
     * @param array|null $branding
     * @return array
     */
    protected function normalizeBranding(?array $branding): array
    {
        $defaults = [
            'primary_color' => self::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => self::DEFAULT_SECONDARY_COLOR,
            'font_family' => self::DEFAULT_FONT_FAMILY,
            'company_name' => null,
            'logo_url' => null,
            'logo_file' => null,
        ];
        
        if (!$branding) {
            return $defaults;
        }
        
        // Map camelCase to snake_case
        $mapped = [];
        $keyMap = [
            'primaryColor' => 'primary_color',
            'secondaryColor' => 'secondary_color',
            'fontFamily' => 'font_family',
            'companyName' => 'company_name',
            'logoUrl' => 'logo_url',
            'logoFile' => 'logo_file',
        ];
        
        foreach ($branding as $key => $value) {
            $normalizedKey = $keyMap[$key] ?? $key;
            $mapped[$normalizedKey] = $value;
        }
        
        return array_merge($defaults, $mapped);
    }

    /**
     * Map font family name to PDF core font.
     *
     * @param string $fontFamily
     * @return string
     */
    protected function mapFontFamily(string $fontFamily): string
    {
        $normalized = strtolower(trim($fontFamily));
        return self::FONT_MAP[$normalized] ?? self::DEFAULT_FONT_FAMILY;
    }

    /**
     * Convert hex color to RGB array.
     *
     * @param string $hex
     * @return array [r, g, b] values 0-255
     */
    protected function hexToRgb(string $hex): array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Validate hex format
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            // Return default color if invalid
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
    protected function escapePdfText(string $text): string
    {
        // Remove HTML/script tags for security
        $text = strip_tags($text);
        
        // Escape PDF special characters
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        
        // Handle non-ASCII characters (basic replacement)
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
    protected function truncate(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}
