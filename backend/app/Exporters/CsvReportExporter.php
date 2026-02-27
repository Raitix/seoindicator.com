<?php

namespace App\Exporters;

/**
 * CSV exporter for SEO reports.
 */
class CsvReportExporter
{
    /**
     * SERP feature icons mapping for CSV output.
     */
    protected const SERP_FEATURE_ICONS = [
        'featured_snippet' => '🏆',
        'local_pack' => '📍',
        'image_pack' => '🖼️',
        'video' => '🎬',
        'people_also_ask' => '❓',
        'knowledge_panel' => '📋',
        'sitelinks' => '🔗',
        'reviews' => '⭐',
        'faq' => '📝',
        'product' => '🛒',
    ];

    /**
     * Generate CSV content from report data.
     */
    public function generate(array $data, bool $includeBom = true): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($includeBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        $header = [
            'Keyword',
            'Current Position',
            'Previous Position',
            'Change',
            'Search Engine',
            'Location',
            'Last Checked',
            'SERP Features',
        ];

        fputcsv($handle, $header);

        foreach (($data['keywords'] ?? []) as $row) {
            fputcsv($handle, [
                (string) ($row['keyword'] ?? ''),
                $this->formatCurrentPosition($row['current_position'] ?? null),
                $this->formatPreviousPosition($row['previous_position'] ?? null),
                $this->formatChange($row['change'] ?? null),
                (string) ($row['search_engine'] ?? ''),
                (string) ($row['location'] ?? ''),
                (string) ($row['last_checked_at'] ?? ''),
                (string) ($row['serp_features'] ?? ''),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function formatCurrentPosition($value): string
    {
        if ($value === null || $value === '') {
            return 'Not ranked';
        }

        return (string) $value;
    }

    private function formatPreviousPosition($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return (string) $value;
    }

    private function formatChange($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return (string) $value;
    }

    /**
     * Format SERP features for CSV output.
     *
     * @param array $features Array of feature types
     * @return string Icon string
     */
    public function formatSerpFeatures(array $features): string
    {
        $icons = [];
        foreach ($features as $type) {
            $icons[] = self::SERP_FEATURE_ICONS[$type] ?? '';
        }
        return implode('', $icons);
    }
}
