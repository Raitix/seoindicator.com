<?php

namespace App\Exporters;

use App\Services\Tools\ToolResult;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * XLS Exporter for Tool Analysis Results.
 *
 * Generates spreadsheet exports of tool analysis results with:
 * - Header row with 5 columns: Category, Indicator, Value, Status, Recommendation
 * - Bold header formatting
 * - Auto-sized columns
 * - Support for large datasets (100+ indicators)
 * - XSS prevention and formula injection protection
 *
 * TASK-29-2: XlsToolExporter Implementation
 */
class XlsToolExporter implements FromArray, WithHeadings, WithStyles
{
    /**
     * The tool result to export.
     */
    private ?ToolResult $result;

    /**
     * The indicators data extracted from the result.
     */
    private array $indicators = [];

    /**
     * Create a new XLS exporter instance.
     *
     * @param ToolResult|null $result
     */
    public function __construct(?ToolResult $result = null)
    {
        $this->result = $result;
        
        if ($result !== null) {
            $this->extractIndicators($result);
        }
    }

    /**
     * Export the tool result as XLS binary content.
     *
     * @param ToolResult $result
     * @return string Binary XLS content
     */
    public function export(ToolResult $result): string
    {
        $this->result = $result;
        $this->extractIndicators($result);
        
        // Create spreadsheet using PhpSpreadsheet directly
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Apply headings
        $headings = $this->headings();
        foreach ($headings as $col => $heading) {
            $sheet->setCellValue([$col + 1, 1], $heading);
        }
        
        // Apply data
        $data = $this->array();
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }
        
        // Apply styles
        $styles = $this->styles($sheet);
        foreach ($styles as $row => $style) {
            if (isset($style['font']['bold']) && $style['font']['bold']) {
                $sheet->getStyle($row . ':' . $row)->getFont()->setBold(true);
            }
        }
        
        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Write to string
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'xls_export_');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }

    /**
     * Get the data array for the spreadsheet.
     *
     * @return array
     */
    public function array(): array
    {
        $rows = [];
        
        foreach ($this->indicators as $indicator) {
            $rows[] = [
                $this->formatValue($indicator['category'] ?? 'General'),
                $this->formatValue($indicator['name'] ?? ''),
                $this->formatValue($indicator['value'] ?? '-'),
                $this->formatValue($indicator['status'] ?? ''),
                $this->formatValue($indicator['recommendation'] ?? '-'),
            ];
        }
        
        return $rows;
    }

    /**
     * Get the column headings.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Category',
            'Indicator',
            'Value',
            'Status',
            'Recommendation',
        ];
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Extract indicators from the tool result.
     *
     * @param ToolResult $result
     * @return void
     */
    private function extractIndicators(ToolResult $result): void
    {
        $data = $result->data ?? [];
        $this->indicators = $data['indicators'] ?? [];
    }

    /**
     * Format a value for safe spreadsheet use.
     *
     * - Handles null and empty values
     * - Prevents formula injection by prefixing dangerous prefixes
     * - Preserves HTML/script tags as text (safe in spreadsheet context)
     *
     * @param mixed $value
     * @return string
     */
    private function formatValue(mixed $value): string
    {
        // Handle null
        if ($value === null) {
            return '-';
        }
        
        // Convert to string
        $stringValue = (string) $value;
        
        // Handle empty string
        if ($stringValue === '') {
            return '-';
        }
        
        // Prevent formula injection by prefixing dangerous characters
        // Characters that could start a formula: =, +, -, @
        if (preg_match('/^[=+\-@]/', $stringValue)) {
            return "'" . $stringValue;
        }
        
        return $stringValue;
    }
}
