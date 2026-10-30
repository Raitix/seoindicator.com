<?php

namespace App\Services\AIVisibility;

use App\Services\AIVisibility\Contracts\ExporterInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CSV Exporter
 *
 * Handles CSV export for AI Visibility data.
 *
 * @see TASK-34-22
 * @see SPEC-034
 */
class CsvExporter implements ExporterInterface
{
    /**
     * @var array Column definitions
     */
    protected array $columns = [];

    /**
     * @var bool Whether streaming is enabled
     */
    protected bool $streamingEnabled = false;

    /**
     * @var int Chunk size for streaming
     */
    protected int $chunkSize = 1000;

    /**
     * @var string Delimiter character
     */
    protected string $delimiter = ',';

    /**
     * @var string Enclosure character
     */
    protected string $enclosure = '"';

    /**
     * @var string Escape character
     */
    protected string $escape = '\\';

    /**
     * @var bool Include UTF-8 BOM for Excel compatibility
     */
    protected bool $includeBom = true;

    /**
     * Export data to CSV format.
     *
     * @param Collection $data The data to export
     * @param string $filename The filename (without extension)
     * @param array $options Export options
     * @return \Generator|string CSV content
     */
    public function export(Collection $data, string $filename, array $options = []): mixed
    {
        $this->applyOptions($options);

        if ($this->streamingEnabled || $data->count() > 10000) {
            return $this->streamExport($data, $filename);
        }

        return $this->generateCsv($data);
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return 'csv';
    }

    /**
     * Get the MIME type.
     */
    public function getMimeType(): string
    {
        return 'text/csv';
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
     * Generate CSV content.
     *
     * @param Collection $data
     * @return string
     */
    protected function generateCsv(Collection $data): string
    {
        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new RuntimeException('Failed to open temporary stream for CSV export');
        }

        // Add UTF-8 BOM for Excel compatibility
        if ($this->includeBom) {
            fwrite($output, "\xEF\xBB\xBF");
        }

        // Write headers
        if (!empty($this->columns)) {
            $headers = array_values($this->columns);
            fputcsv($output, $headers, $this->delimiter, $this->enclosure, $this->escape);
        }

        // Write data rows
        foreach ($data as $row) {
            $csvRow = $this->formatRow($row);
            fputcsv($output, $csvRow, $this->delimiter, $this->enclosure, $this->escape);
        }

        // Get content
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Stream CSV content as a generator.
     *
     * @param Collection $data
     * @param string $filename
     * @return \Generator
     */
    protected function streamExport(Collection $data, string $filename): \Generator
    {
        // Yield UTF-8 BOM for Excel compatibility
        if ($this->includeBom) {
            yield "\xEF\xBB\xBF";
        }

        // Yield headers
        if (!empty($this->columns)) {
            $headers = array_values($this->columns);
            yield $this->formatCsvRow($headers);
        }

        // Yield data rows in chunks
        $chunkIndex = 0;
        foreach ($data->chunk($this->chunkSize) as $chunk) {
            foreach ($chunk as $row) {
                $csvRow = $this->formatRow($row);
                yield $this->formatCsvRow($csvRow);
            }
            $chunkIndex++;

            // Free memory after each chunk
            if ($chunkIndex % 5 === 0) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Format a data row for CSV.
     *
     * @param mixed $row
     * @return array
     */
    protected function formatRow(mixed $row): array
    {
        $formatted = [];

        if (empty($this->columns)) {
            // No columns defined, use all data
            if (is_object($row)) {
                return method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
            }
            return is_array($row) ? $row : [$row];
        }

        // Use defined columns
        foreach (array_keys($this->columns) as $field) {
            $value = $this->extractValue($row, $field);
            $formatted[] = $this->formatValue($value);
        }

        return $formatted;
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
     * Format a value for CSV.
     *
     * @param mixed $value
     * @return string
     */
    protected function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    /**
     * Format a CSV row string.
     *
     * @param array $fields
     * @return string
     */
    protected function formatCsvRow(array $fields): string
    {
        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new RuntimeException('Failed to format CSV row');
        }

        fputcsv($output, $fields, $this->delimiter, $this->enclosure, $this->escape);
        rewind($output);
        $line = fgets($output);
        fclose($output);

        return $line !== false ? $line : '';
    }

    /**
     * Apply options to the exporter.
     *
     * @param array $options
     */
    protected function applyOptions(array $options): void
    {
        if (isset($options['delimiter'])) {
            $this->delimiter = $options['delimiter'];
        }

        if (isset($options['enclosure'])) {
            $this->enclosure = $options['enclosure'];
        }

        if (isset($options['escape'])) {
            $this->escape = $options['escape'];
        }

        if (isset($options['include_bom'])) {
            $this->includeBom = (bool) $options['include_bom'];
        }

        if (isset($options['chunk_size'])) {
            $this->chunkSize = (int) $options['chunk_size'];
            $this->streamingEnabled = true;
        }
    }
}
