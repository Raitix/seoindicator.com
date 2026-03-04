<?php

namespace App\Services\Tools;

use App\Exporters\PdfToolExporter;
use App\Exporters\XlsToolExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service for coordinating tool export generation and downloads.
 *
 * TASK-29-3: ToolExportService & Controller
 * SPEC-029: Export Results (PDF/XLS)
 */
class ToolExportService
{
    /**
     * Token length for download URLs.
     */
    private const TOKEN_LENGTH = 64;

    /**
     * Token TTL in seconds (1 hour).
     */
    private const TOKEN_TTL = 3600;

    /**
     * Maximum indicators per export.
     */
    private const MAX_INDICATORS = 500;

    /**
     * Create a new tool export service instance.
     *
     * @param PdfToolExporter $pdfExporter
     * @param XlsToolExporter $xlsExporter
     */
    public function __construct(
        private PdfToolExporter $pdfExporter,
        private XlsToolExporter $xlsExporter
    ) {}

    /**
     * Create an export and return the download URL.
     *
     * @param string $tool Tool identifier (e.g., 'meta-tag-analyzer')
     * @param string $url URL that was analyzed
     * @param string $format Export format ('pdf' or 'xls')
     * @param array $data Analysis data including indicators, summary, recommendations
     * @param int $userId User ID requesting the export
     * @return string Download URL
     * @throws \InvalidArgumentException If format is invalid
     */
    public function createExport(
        string $tool,
        string $url,
        string $format,
        array $data,
        int $userId
    ): string {
        // Validate format
        if (!in_array($format, ['pdf', 'xls'])) {
            throw new \InvalidArgumentException("Invalid format: {$format}. Must be 'pdf' or 'xls'.");
        }

        // Build ToolResult DTO
        $toolResult = $this->buildToolResult($tool, $url, $data);

        // Generate file using appropriate exporter
        $content = $this->generateFile($toolResult, $format);

        // Generate filename and store file
        $filename = $this->generateFilename($tool, $format);
        $filePath = "exports/{$filename}";
        Storage::put($filePath, $content);

        // Generate download token
        $token = $this->generateToken();

        // Cache token data with user ownership
        Cache::put("export:{$token}", [
            'file_path' => $filePath,
            'format' => $format,
            'user_id' => $userId,
            'tool' => $tool,
            'filename' => $filename,
        ], now()->addSeconds(self::TOKEN_TTL));

        // Return download URL
        return "/api/v1/tools/export/download/{$token}";
    }

    /**
     * Download an export by token.
     *
     * @param string $token Download token
     * @param int $userId User ID requesting the download
     * @return Response File download response
     * @throws NotFoundHttpException If token is invalid, expired, or belongs to another user
     */
    public function downloadExport(string $token, int $userId): Response
    {
        // Validate token exists
        $cacheKey = "export:{$token}";
        $tokenData = Cache::get($cacheKey);

        if (!$tokenData) {
            throw new NotFoundHttpException('Export not found.');
        }

        // Validate user ownership
        if ($tokenData['user_id'] !== $userId) {
            throw new NotFoundHttpException('Export not found.');
        }

        $filePath = $tokenData['file_path'];
        $format = $tokenData['format'];
        $filename = $tokenData['filename'];

        // Check file exists
        if (!Storage::exists($filePath)) {
            Cache::forget($cacheKey);
            throw new NotFoundHttpException('Export file not found.');
        }

        // Get file content
        $content = Storage::get($filePath);

        // Determine content type
        $contentType = $this->getContentType($format);

        // Delete file and token (one-time use)
        Storage::delete($filePath);
        Cache::forget($cacheKey);

        // Return download response
        return response($content)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get the PDF exporter instance.
     *
     * @return PdfToolExporter
     */
    public function getPdfExporter(): PdfToolExporter
    {
        return $this->pdfExporter;
    }

    /**
     * Get the XLS exporter instance.
     *
     * @return XlsToolExporter
     */
    public function getXlsExporter(): XlsToolExporter
    {
        return $this->xlsExporter;
    }

    /**
     * Build a ToolResult DTO from export data.
     *
     * @param string $tool
     * @param string $url
     * @param array $data
     * @return ToolResult
     */
    protected function buildToolResult(string $tool, string $url, array $data): ToolResult
    {
        // Ensure indicators are limited
        $indicators = $data['indicators'] ?? [];
        if (count($indicators) > self::MAX_INDICATORS) {
            $indicators = array_slice($indicators, 0, self::MAX_INDICATORS);
        }

        return new ToolResult(
            success: true,
            toolName: $data['toolName'] ?? $this->formatToolName($tool),
            url: $url,
            data: [
                'indicators' => $indicators,
                'summary' => $data['summary'] ?? [],
                'recommendations' => $data['recommendations'] ?? [],
            ],
            analyzedAt: $data['analyzedAt'] ?? now()->toISOString()
        );
    }

    /**
     * Generate file content using the appropriate exporter.
     *
     * @param ToolResult $result
     * @param string $format
     * @return string File content
     */
    protected function generateFile(ToolResult $result, string $format): string
    {
        if ($format === 'pdf') {
            return $this->pdfExporter->export($result);
        }

        return $this->xlsExporter->export($result);
    }

    /**
     * Generate a secure download token.
     *
     * @return string 64-character alphanumeric token
     */
    protected function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Generate a sanitized filename for the export.
     *
     * @param string $tool
     * @param string $format
     * @return string
     */
    protected function generateFilename(string $tool, string $format): string
    {
        // Sanitize tool name for filename
        $sanitizedTool = $this->sanitizeFilename($tool);
        $timestamp = now()->format('Y-m-d-His');
        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';

        return "{$sanitizedTool}-{$timestamp}.{$extension}";
    }

    /**
     * Sanitize a string for use in a filename.
     *
     * @param string $input
     * @return string
     */
    protected function sanitizeFilename(string $input): string
    {
        // Remove path traversal attempts
        $sanitized = str_replace(['../', '..\\', '/', '\\'], '', $input);

        // Remove null bytes
        $sanitized = str_replace("\0", '', $sanitized);

        // Convert to lowercase and replace non-alphanumeric with hyphens
        $sanitized = strtolower($sanitized);
        $sanitized = preg_replace('/[^a-z0-9-]+/', '-', $sanitized);

        // Remove consecutive hyphens
        $sanitized = preg_replace('/-+/', '-', $sanitized);

        // Trim hyphens from ends
        $sanitized = trim($sanitized, '-');

        // Limit length
        if (strlen($sanitized) > 50) {
            $sanitized = substr($sanitized, 0, 50);
        }

        // Ensure not empty
        if (empty($sanitized)) {
            $sanitized = 'export';
        }

        return $sanitized;
    }

    /**
     * Format a tool identifier into a readable name.
     *
     * @param string $tool
     * @return string
     */
    protected function formatToolName(string $tool): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $tool));
    }

    /**
     * Get the content type for the given format.
     *
     * @param string $format
     * @return string
     */
    protected function getContentType(string $format): string
    {
        return match ($format) {
            'pdf' => 'application/pdf',
            'xls' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}
