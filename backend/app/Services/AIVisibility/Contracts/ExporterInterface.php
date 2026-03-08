<?php

namespace App\Services\AIVisibility\Contracts;

use Illuminate\Support\Collection;

/**
 * Exporter Interface
 *
 * Contract for data exporters (CSV, XLSX).
 *
 * @see TASK-34-22
 * @see SPEC-034
 */
interface ExporterInterface
{
    /**
     * Export data to the specified format.
     *
     * @param Collection $data The data to export
     * @param string $filename The filename (without extension)
     * @param array $options Export options (columns, formatting, etc.)
     * @return mixed The exported content (stream, string, or file path)
     */
    public function export(Collection $data, string $filename, array $options = []): mixed;

    /**
     * Get the file extension for this exporter.
     *
     * @return string
     */
    public function getExtension(): string;

    /**
     * Get the MIME type for this exporter.
     *
     * @return string
     */
    public function getMimeType(): string;

    /**
     * Set the columns to export.
     *
     * @param array $columns Column definitions ['field' => 'Label']
     * @return self
     */
    public function setColumns(array $columns): self;

    /**
     * Enable streaming for large datasets.
     *
     * @param int $chunkSize Number of rows per chunk
     * @return self
     */
    public function enableStreaming(int $chunkSize = 1000): self;
}
