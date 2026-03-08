<?php

namespace App\Services\AIVisibility;

use App\Services\AIVisibility\Contracts\ExporterInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use RuntimeException;

/**
 * XLSX Exporter
 *
 * Handles Excel XLSX export for AI Visibility data.
 * Supports multiple sheets for different data types.
 *
 * @see TASK-34-22
 * @see SPEC-034
 */
class XlsxExporter implements ExporterInterface
{
    /**
     * @var array Column definitions per sheet
     */
    protected array $columns = [];

    /**
     * @var bool Whether streaming is enabled
     */
    protected bool $streamingEnabled = false;

    /**
     * @var int Chunk size for large datasets
     */
    protected int $chunkSize = 1000;

    /**
     * @var Spreadsheet The spreadsheet instance
     */
    protected Spreadsheet $spreadsheet;

    /**
     * Export data to XLSX format.
     *
     * @param Collection $data The data to export
     * @param string $filename The filename (without extension)
     * @param array $options Export options (sheets, formatting, etc.)
     * @return string Binary XLSX content
     */
    public function export(Collection $data, string $filename, array $options = []): string
    {
        $this->applyOptions($options);

        // Create spreadsheet
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0); // Remove default sheet

        // Check if multi-sheet mode
        if (isset($options['sheets']) && is_array($options['sheets'])) {
            $this->exportMultiSheet($options['sheets']);
        } else {
            $this->addSheet($data, $options['sheet_name'] ?? 'Data', $this->columns);
        }

        // Generate output
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');

        // Use temp file for output
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_export_');
        if ($tempPath === false) {
            throw new RuntimeException('Failed to create temporary file for XLSX export');
        }

        $writer->save($tempPath);

        // Read content
        $content = file_get_contents($tempPath);
        unlink($tempPath);

        if ($content === false) {
            throw new RuntimeException('Failed to read XLSX content');
        }

        return $content;
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return 'xlsx';
    }

    /**
     * Get the MIME type.
     */
    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * Set the columns to export.
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Enable streaming for large datasets.
     */
    public function enableStreaming(int $chunkSize = 1000): self
    {
        $this->streamingEnabled = true;
        $this->chunkSize = $chunkSize;
        return $this;
    }

    /**
     * Export multiple sheets.
     *
     * @param array $sheets Array of ['name' => 'SheetName', 'data' => Collection, 'columns' => []]
     */
    protected function exportMultiSheet(array $sheets): void
    {
        foreach ($sheets as $sheetConfig) {
            $name = $sheetConfig['name'] ?? 'Sheet';
            $data = $sheetConfig['data'] ?? collect([]);
            $columns = $sheetConfig['columns'] ?? [];

            $this->addSheet($data, $name, $columns);
        }
    }

    /**
     * Add a sheet to the spreadsheet.
     *
     * @param Collection $data
     * @param string $name
     * @param array $columns
     */
    protected function addSheet(Collection $data, string $name, array $columns): void
    {
        // Create worksheet
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle($this->sanitizeSheetName($name));

        // Set columns if provided
        if (empty($columns) && !empty($this->columns)) {
            $columns = $this->columns;
        }

        // Write headers
        $row = 1;
        if (!empty($columns)) {
            $col = 1;
            foreach ($columns as $label) {
                $sheet->setCellValue([$col, $row], $label);
                $col++;
            }
            $this->formatHeaderRow($sheet, count($columns));
            $row++;
        }

        // Write data
        foreach ($data as $item) {
            $col = 1;
            if (!empty($columns)) {
                foreach (array_keys($columns) as $field) {
                    $value = $this->extractValue($item, $field);
                    $this->setCellValue($sheet, $col, $row, $value);
                    $col++;
                }
            } else {
                // Use all data
                if (is_array($item) || is_object($item)) {
                    $values = is_object($item) && method_exists($item, 'toArray')
                        ? $item->toArray()
                        : (array) $item;
                    foreach ($values as $value) {
                        $this->setCellValue($sheet, $col, $row, $value);
                        $col++;
                    }
                }
            }
            $row++;
        }

        // Auto-size columns
        $this->autoSizeColumns($sheet, count($columns) ?? $col - 1);

        // Freeze header row
        $sheet->freezePane('A2');

        // Add auto-filter
        if ($row > 2) {
            $lastColumn = $this->getColumnLetter(count($columns) ?? $col - 1);
            $sheet->setAutoFilter("A1:{$lastColumn}" . ($row - 1));
        }
    }

    /**
     * Set a cell value with appropriate type.
     *
     * @param mixed $sheet
     * @param int $col
     * @param int $row
     * @param mixed $value
     */
    protected function setCellValue($sheet, int $col, int $row, mixed $value): void
    {
        $cellCoordinate = [$col, $row];

        if ($value === null) {
            $sheet->setCellValue($cellCoordinate, '');
            return;
        }

        // Check for formula injection and escape
        $stringValue = (string) $value;
        if (in_array(substr($stringValue, 0, 1), ['=', '+', '-', '@', "\t", "\r"])) {
            // Escape by prefixing with apostrophe
            $sheet->setCellValueExplicit($cellCoordinate, "'" . $stringValue, DataType::TYPE_STRING);
            return;
        }

        if (is_bool($value)) {
            $sheet->setCellValue($cellCoordinate, $value ? 'Yes' : 'No');
        } elseif (is_int($value) || is_float($value)) {
            $sheet->setCellValue($cellCoordinate, $value);
        } elseif ($value instanceof \DateTimeInterface) {
            $sheet->setCellValue($cellCoordinate, $value->format('Y-m-d H:i:s'));
        } elseif (is_array($value) || is_object($value)) {
            $sheet->setCellValue($cellCoordinate, json_encode($value, JSON_UNESCAPED_UNICODE));
        } else {
            $sheet->setCellValue($cellCoordinate, (string) $value);
        }
    }

    /**
     * Format the header row.
     *
     * @param mixed $sheet
     * @param int $columnCount
     */
    protected function formatHeaderRow($sheet, int $columnCount): void
    {
        $lastColumn = $this->getColumnLetter($columnCount);
        $headerRange = "A1:{$lastColumn}1";

        // Bold
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        // Background color
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Borders
        $sheet->getStyle($headerRange)->getBorders()->applyFromArray([
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
        ]);

        // Alignment
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * Auto-size columns.
     *
     * @param mixed $sheet
     * @param int $columnCount
     */
    protected function autoSizeColumns($sheet, int $columnCount): void
    {
        for ($col = 1; $col <= $columnCount; $col++) {
            $colLetter = $this->getColumnLetter($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Get column letter from index.
     *
     * @param int $index
     * @return string
     */
    protected function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int) ($index / 26);
        }
        return $letter;
    }

    /**
     * Sanitize sheet name.
     *
     * @param string $name
     * @return string
     */
    protected function sanitizeSheetName(string $name): string
    {
        // Remove invalid characters
        $name = str_replace(['\\', '/', '*', '?', ':', '[', ']'], '', $name);
        // Limit to 31 characters
        return mb_substr($name, 0, 31);
    }

    /**
     * Extract a value from a row.
     *
     * @param mixed $row
     * @param string $field
     * @return mixed
     */
    protected function extractValue(mixed $row, string $field): mixed
    {
        if (is_array($row)) {
            return $row[$field] ?? null;
        }

        if (is_object($row)) {
            // Try getter method
            $getter = 'get' . Str::studly($field);
            if (method_exists($row, $getter)) {
                return $row->$getter();
            }

            // Try property access
            if (property_exists($row, $field)) {
                return $row->$field;
            }

            // Try array access
            if ($row instanceof \ArrayAccess) {
                return $row[$field] ?? null;
            }
        }

        return null;
    }

    /**
     * Apply options to the exporter.
     *
     * @param array $options
     */
    protected function applyOptions(array $options): void
    {
        if (isset($options['chunk_size'])) {
            $this->chunkSize = (int) $options['chunk_size'];
            $this->streamingEnabled = true;
        }
    }
}
