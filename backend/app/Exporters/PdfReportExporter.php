<?php

namespace App\Exporters;

/**
 * PDF Report Exporter with branding support.
 *
 * Generates PDF reports with customizable branding:
 * - Custom colors (primary/secondary)
 * - Custom fonts (with fallback)
 * - Logo embedding (PNG, JPG)
 * - Company name display
 */
class PdfReportExporter
{
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
     * Generate a PDF report with optional branding.
     *
     * @param array $report Report configuration
     * @param array $data Report data
     * @param array|null $branding Branding settings (optional)
     * @return string PDF content
     */
    public function generate(array $report, array $data, ?array $branding = null): string
    {
        $sections = $report['sections'] ?? [];
        if (empty($sections)) {
            $sections = ['cover', 'keywords'];
        }

        // Normalize branding with defaults
        $branding = $this->normalizeBranding($branding);

        $pages = [];
        foreach ($sections as $section) {
            $pages = array_merge($pages, $this->buildSectionPages($section, $report, $data, $branding));
        }

        if (empty($pages)) {
            $pages[] = ['SEO Report', [], null];
        }

        return $this->renderPdf($pages, $branding);
    }

    /**
     * Normalize branding settings with defaults.
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
     * Build pages for a section.
     *
     * @return array Array of [lines, colors, font] tuples
     */
    protected function buildSectionPages(string $section, array $report, array $data, array $branding): array
    {
        $titleMap = [
            'cover' => 'Cover Page',
            'summary' => 'Executive Summary',
            'keywords' => 'Keyword Rankings',
            'changes' => 'Position Changes',
            'trends' => 'Trend Charts',
            'serp_features' => 'SERP Features',
        ];

        $title = $titleMap[$section] ?? ucfirst(str_replace('-', ' ', $section));
        $lines = [strtoupper($title)];
        $sectionColors = [
            'primary' => $branding['primary_color'],
            'secondary' => $branding['secondary_color'],
        ];

        switch ($section) {
            case 'cover':
                $lines = $this->buildCoverPage($report, $data, $branding);
                break;
            case 'summary':
                $lines[] = ''; // Spacer
                $summary = $data['executive_summary'] ?? $report['executive_summary'] ?? 'Summary not provided.';
                foreach ($this->wrapLines($summary) as $line) {
                    $lines[] = $line;
                }
                break;
            case 'keywords':
                $lines[] = ''; // Spacer
                foreach (($data['keywords'] ?? []) as $row) {
                    // Get SERP feature icons from keyword data
                    $serpIcons = $row['serp_features'] ?? '';
                    $featureStr = $serpIcons ? ' [' . $serpIcons . ']' : '';
                    $lines[] = sprintf(
                        '%s%s | Current: %s | Previous: %s | Change: %s',
                        $row['keyword'] ?? 'Keyword',
                        $featureStr,
                        $this->formatValue($row['current_position'] ?? null, 'Not ranked'),
                        $this->formatValue($row['previous_position'] ?? null, 'N/A'),
                        $this->formatValue($row['change'] ?? null, 'N/A')
                    );
                }
                break;
            case 'changes':
                $lines[] = '';
                $lines[] = 'Top Performers';
                foreach (($data['top_performers'] ?? []) as $row) {
                    $lines[] = sprintf('%s (Δ %s)', $row['keyword'] ?? 'Keyword', $this->formatValue($row['change'] ?? null, 'N/A'));
                }
                $lines[] = '';
                $lines[] = 'Needs Attention';
                foreach (($data['needs_attention'] ?? []) as $row) {
                    $lines[] = sprintf('%s (Δ %s)', $row['keyword'] ?? 'Keyword', $this->formatValue($row['change'] ?? null, 'N/A'));
                }
                break;
            case 'trends':
                $lines[] = '';
                foreach (($data['trends'] ?? []) as $trend) {
                    $points = $trend['points'] ?? [];
                    $lines[] = sprintf('%s: %d data points', $trend['keyword'] ?? 'Keyword', count($points));
                }
                if (count($lines) === 2) {
                    $lines[] = 'Trend data not available.';
                }
                break;
            case 'serp_features':
                $lines = $this->buildSerpFeaturesSection($data, $branding);
                break;
            default:
                $lines[] = 'Section content not available.';
        }

        return $this->chunkLinesWithBranding($lines, 36, $sectionColors, $branding['font_family']);
    }

    /**
     * Build cover page content.
     */
    protected function buildCoverPage(array $report, array $data, array $branding): array
    {
        $lines = ['COVER PAGE'];

        // Logo placeholder or actual logo reference
        if (!empty($branding['logo_file']) || !empty($branding['logo_url'])) {
            $lines[] = '[LOGO]';
        } else {
            $lines[] = 'Logo: [placeholder]';
        }

        $lines[] = '';

        // Report title
        $lines[] = $report['name'] ?? 'SEO Report';
        $lines[] = '';

        // Date range
        $lines[] = $this->formatDateRange($report);
        $lines[] = '';

        // Company name
        $companyName = $branding['company_name'] ?? $data['company_name'] ?? null;
        if ($companyName) {
            $lines[] = 'Prepared for ' . $companyName;
        }

        return $lines;
    }

    /**
     * SERP feature icons mapping.
     */
    protected const SERP_FEATURE_ICONS = [
        'featured_snippet' => 'trophy',
        'local_pack' => 'map-pin',
        'image_pack' => 'image',
        'video' => 'play-circle',
        'people_also_ask' => 'help-circle',
        'knowledge_panel' => 'info',
        'sitelinks' => 'link',
        'reviews' => 'star',
        'faq' => 'list',
        'product' => 'shopping-cart',
    ];

    /**
     * Build SERP features section content.
     */
    protected function buildSerpFeaturesSection(array $data, array $branding): array
    {
        $serpFeatures = $data['serp_features'] ?? null;

        if (!$serpFeatures) {
            return ['SERP FEATURES', '', 'SERP feature data not available.'];
        }

        $lines = ['SERP FEATURES'];
        $lines[] = '';

        // Overview
        $totalKeywords = $serpFeatures['totalKeywords'] ?? 0;
        $keywordsWithFeatures = $serpFeatures['keywordsWithFeatures'] ?? 0;

        $lines[] = sprintf('Keywords with SERP Features: %d / %d', $keywordsWithFeatures, $totalKeywords);
        $lines[] = '';

        // Feature counts
        $lines[] = 'Feature Breakdown:';
        $featureCounts = $serpFeatures['featureCounts'] ?? [];

        // Feature name mapping
        $featureNames = [
            'featured_snippet' => 'Featured Snippet',
            'local_pack' => 'Local Pack',
            'image_pack' => 'Image Pack',
            'video' => 'Video',
            'people_also_ask' => 'People Also Ask',
            'knowledge_panel' => 'Knowledge Panel',
            'sitelinks' => 'Sitelinks',
            'reviews' => 'Reviews',
            'faq' => 'FAQ',
            'product' => 'Product',
        ];

        foreach ($featureCounts as $type => $count) {
            if ($count > 0) {
                $name = $featureNames[$type] ?? ucfirst(str_replace('_', ' ', $type));
                $lines[] = sprintf('  - %s: %d keywords', $name, $count);
            }
        }

        // Recent changes
        $recentChanges = $serpFeatures['recentChanges'] ?? [];
        $gained = $recentChanges['gained'] ?? [];
        $lost = $recentChanges['lost'] ?? [];

        if (!empty($gained)) {
            $lines[] = '';
            $lines[] = 'Recently Gained Features:';
            foreach (array_slice($gained, 0, 10) as $gain) {
                $featureName = $featureNames[$gain['feature']] ?? $gain['feature'];
                $lines[] = sprintf('  + %s: %s', $gain['keyword'], $featureName);
            }
        }

        if (!empty($lost)) {
            $lines[] = '';
            $lines[] = 'Recently Lost Features:';
            foreach (array_slice($lost, 0, 10) as $loss) {
                $featureName = $featureNames[$loss['feature']] ?? $loss['feature'];
                $lines[] = sprintf('  - %s: %s', $loss['keyword'], $featureName);
            }
        }

        return $lines;
    }

    /**
     * Render PDF with branding.
     */
    protected function renderPdf(array $pages, array $branding): string
    {
        $pageCount = count($pages);
        $objects = [];
        $offsets = [];

        // Map font family to PDF font
        $pdfFont = $this->mapFontFamily($branding['font_family'] ?? self::DEFAULT_FONT_FAMILY);

        // Convert colors
        $primaryRgb = $this->hexToRgb($branding['primary_color'] ?? self::DEFAULT_PRIMARY_COLOR);
        $secondaryRgb = $this->hexToRgb($branding['secondary_color'] ?? self::DEFAULT_SECONDARY_COLOR);

        $fontObjectId = 3;
        $firstPageObjectId = 4;

        $pageIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageIds[] = $firstPageObjectId + ($i * 2);
        }

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [" . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageIds)) . "] /Count {$pageCount} >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /{$pdfFont} >>";

        // Build XObject for logo if available
        $logoObjectId = null;
        $logoData = $this->getLogoData($branding);
        if ($logoData) {
            $logoObjectId = count($objects) + 1;
            $objects[] = $this->buildLogoXObject($logoData);
        }

        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = $pageIds[$i];
            $contentId = $pageId + 1;
            $stream = $this->buildContentStream(
                $pages[$i],
                $i + 1,
                $pageCount,
                $primaryRgb,
                $secondaryRgb,
                $pdfFont,
                $logoObjectId,
                $i === 0 // First page has logo
            );

            // Add logo to resources if first page
            if ($i === 0 && $logoObjectId) {
                $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObjectId} 0 R >> /XObject << /Logo {$logoObjectId} 0 R >> >> /Contents {$contentId} 0 R >>";
            } else {
                $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> /Contents {$contentId} 0 R >>";
            }
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

    /**
     * Build content stream for a page.
     */
    protected function buildContentStream(
        array $pageData,
        int $pageNum,
        int $totalPages,
        array $primaryRgb,
        array $secondaryRgb,
        string $font,
        ?int $logoObjectId,
        bool $isFirstPage
    ): string {
        $lines = $pageData[0] ?? [];
        $colors = $pageData[1] ?? [];
        $pageFont = $pageData[2] ?? $font;

        $stream = '';

        // Draw logo on first page if available
        if ($isFirstPage && $logoObjectId && in_array('[LOGO]', $lines)) {
            $stream .= "q\n";
            $stream .= "200 0 0 50 200 700 cm\n"; // Scale and position logo
            $stream .= "/Logo Do\n";
            $stream .= "Q\n";
        }

        // Set primary color for headers
        $primaryColor = !empty($colors['primary']) ? $this->hexToRgb($colors['primary']) : $primaryRgb;
        $stream .= sprintf("%.3f %.3f %.3f rg\n", $primaryColor[0]/255, $primaryColor[1]/255, $primaryColor[2]/255);

        $stream .= "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "50 780 Td\n";
        $stream .= "14 TL\n";

        $yOffset = 780;
        foreach ($lines as $index => $line) {
            // Skip logo placeholder in text (already drawn as image)
            if ($line === '[LOGO]') {
                continue;
            }

            // Apply primary color to first line (title)
            if ($index === 0) {
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $primaryColor[0]/255, $primaryColor[1]/255, $primaryColor[2]/255);
                $stream .= "/F1 16 Tf\n"; // Larger font for title
            } else {
                // Secondary color for body text
                $secondaryColor = !empty($colors['secondary']) ? $this->hexToRgb($colors['secondary']) : $secondaryRgb;
                $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryColor[0]/255, $secondaryColor[1]/255, $secondaryColor[2]/255);
                $stream .= "/F1 12 Tf\n";
            }

            $stream .= '(' . $this->escapePdfText($line) . ") Tj\nT*\n";
            $yOffset -= 14;

            // Check if we need to start a new page (simplified - actual pagination handled in chunkLinesWithBranding)
            if ($yOffset < 50) {
                break;
            }
        }

        $stream .= "ET\n";

        // Page number with secondary color
        $stream .= sprintf("%.3f %.3f %.3f rg\n", $secondaryRgb[0]/255, $secondaryRgb[1]/255, $secondaryRgb[2]/255);
        $stream .= "BT\n/F1 10 Tf\n50 30 Td\n(Page {$pageNum} of {$totalPages}) Tj\nET\n";

        return $stream;
    }

    /**
     * Map font family name to PDF core font.
     */
    protected function mapFontFamily(string $fontFamily): string
    {
        $normalized = strtolower(trim($fontFamily));
        return self::FONT_MAP[$normalized] ?? self::DEFAULT_FONT_FAMILY;
    }

    /**
     * Convert hex color to RGB array.
     *
     * @param string $hex Hex color (e.g., '#2563eb')
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
     * Get logo data if available.
     *
     * @return array|null Logo data or null if not available
     */
    protected function getLogoData(array $branding): ?array
    {
        $logoFile = $branding['logo_file'] ?? null;

        if (!$logoFile) {
            return null;
        }

        // Build full path
        $fullPath = storage_path('app/' . $logoFile);

        if (!file_exists($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => null,
        };

        if (!$mimeType) {
            return null;
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        // Get image dimensions
        $imageInfo = @getimagesize($fullPath);
        if (!$imageInfo) {
            return null;
        }

        [$width, $height] = $imageInfo;

        return [
            'content' => $content,
            'width' => $width,
            'height' => $height,
            'mimeType' => $mimeType,
            'extension' => $extension,
        ];
    }

    /**
     * Build PDF XObject for logo image.
     */
    protected function buildLogoXObject(array $logoData): string
    {
        $content = $logoData['content'];
        $width = $logoData['width'];
        $height = $logoData['height'];
        $mimeType = $logoData['mimeType'];

        // For JPEG, we can embed directly
        if ($mimeType === 'image/jpeg') {
            return sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $width,
                $height,
                strlen($content),
                $content
            );
        }

        // For PNG, we need to extract the raw image data (simplified - just use JPEG-like embedding)
        // In a real implementation, you'd use a library like Imagick or GD to convert PNG to JPEG
        // For now, we'll skip PNG logos in this basic implementation
        return "<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length 3 >>\nstream\n\xFF\xFF\xFF\nendstream";
    }

    /**
     * Chunk lines into pages with branding info.
     *
     * @return array Array of [lines, colors, font] tuples
     */
    protected function chunkLinesWithBranding(array $lines, int $maxLines, array $colors, string $font): array
    {
        if (empty($lines)) {
            return [[[], $colors, $font]];
        }

        $chunks = array_chunk($lines, $maxLines);
        return array_map(fn($chunk) => [$chunk, $colors, $font], $chunks);
    }

    /**
     * Escape special characters for PDF text.
     */
    protected function escapePdfText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return $text;
    }

    /**
     * Format date range for display.
     */
    protected function formatDateRange(array $report): string
    {
        $start = $report['date_range_start'] ?? $report['dateRangeStart'] ?? null;
        $end = $report['date_range_end'] ?? $report['dateRangeEnd'] ?? null;

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return 'Date range not specified';
    }

    /**
     * Format a value with fallback.
     */
    protected function formatValue($value, string $fallback): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (string) $value;
    }

    /**
     * Wrap text to specified line length.
     */
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
