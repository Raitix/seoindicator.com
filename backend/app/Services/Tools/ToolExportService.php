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
 * Service for coordinating tool result exports.
 *
 * Handles PDF and XLS export generation with token-based download system.
 *
 * TASK-29-3: ToolExportService & Controller
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
     * @param PdfToolExporter $pdfExporter
     * @param XlsToolExporter $xlsExporter
     */
    public function __construct(
        private PdfToolExporter $pdfExporter,
        private XlsToolExporter $xlsExporter
    ) {}

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
     * Create an export and return the download URL.
     *
     * @param string $tool Tool name (e.g., 'meta-tag-analyzer')
     * @param string $url URL that was analyzed
     * @param string $format Export format ('pdf' or 'xls')
     * @param array $data Tool result data
     * @param int $userId User ID who created the export
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
            throw new \InvalidArgumentException("Invalid export format: {$format}");
        }

        // Build ToolResult DTO from data
        $toolResult = $this->buildToolResult($tool, $url, $data);

        // Generate file content
        $fileContent = $this->generateFileContent($toolResult, $format);

        // Generate unique filename
        $filename = $this->generateFilename($tool, $format);
        $filePath = 'exports/' . $filename;

        // Store file
        Storage::disk('local')->put($filePath, $fileContent);

        // Generate download token
        $token = $this->generateToken();

        // Cache token data
        Cache::put("export:{$token}", [
            'file' => $filePath,
            'format' => $format,
            'tool' => $tool,
            'url' => $url,
            'user_id' => $userId,
        ], now()->addSeconds(self::TOKEN_TTL));

        // Return download URL
        return "/api/v1/tools/export/download/{$token}";
    }

    /**
     * Download an export by token.
     *
     * @param string $token Download token
     * @param int $userId User ID requesting the download
     * @return Response
     * @throws NotFoundHttpException If token is invalid, expired, or user mismatch
     */
    public function downloadExport(string $token, int $userId): Response
    {
        // Get cached token data
        $tokenData = Cache::get("export:{$token}");

        if (!$tokenData) {
            throw new NotFoundHttpException('Invalid or expired download token.');
        }

        // Verify user ownership
        if ($tokenData['user_id'] !== $userId) {
            throw new NotFoundHttpException('Invalid or expired download token.');
        }

        $filePath = $tokenData['file'];
        $format = $tokenData['format'];
        $tool = $tokenData['tool'];

        // Check if file exists
        if (!Storage::disk('local')->exists($filePath)) {
            Cache::forget("export:{$token}");
            throw new NotFoundHttpException('Export file not found.');
        }

        // Get file content
        $content = Storage::disk('local')->get($filePath);

        // Determine content type and extension
        $contentType = $format === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';

        // Generate filename for download
        $downloadFilename = $this->sanitizeFilename($tool) . '.' . $extension;

        // Delete file and token
        Storage::disk('local')->delete($filePath);
        Cache::forget("export:{$token}");

        // Return response
        return response($content)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'attachment; filename="' . $downloadFilename . '"');
    }

    /**
     * Build a ToolResult DTO from data.
     *
     * @param string $tool
     * @param string $url
     * @param array $data
     * @return ToolResult
     */
    private function buildToolResult(string $tool, string $url, array $data): ToolResult
    {
        // If data is already a ToolResult, return it
        if ($data instanceof ToolResult) {
            return $data;
        }

        // Build ToolResult from array data
        return ToolResult::ok(
            toolName: $data['toolName'] ?? $tool,
            url: $url,
            data: $data
        );
    }

    /**
     * Generate file content using the appropriate exporter.
     *
     * @param ToolResult $result
     * @param string $format
     * @return string
     */
    private function generateFileContent(ToolResult $result, string $format): string
    {
        if ($format === 'pdf') {
            return $this->pdfExporter->export($result);
        }

        // XLS format
        return $this->xlsExporter->export($result);
    }

    /**
     * Generate a unique filename for the export.
     *
     * @param string $tool
     * @param string $format
     * @return string
     */
    private function generateFilename(string $tool, string $format): string
    {
        $timestamp = date('YmdHis');
        $random = Str::random(8);
        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
        $sanitizedTool = $this->sanitizeFilename($tool);

        return "{$sanitizedTool}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Generate a secure download token.
     *
     * @return string
     */
    private function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Sanitize a string for use in a filename.
     *
     * @param string $name
     * @return string
     */
    private function sanitizeFilename(string $name): string
    {
        // Remove path traversal attempts
        $name = str_replace(['../', '..\\', '/', '\\'], '', $name);

        // Remove script tags and HTML
        $name = strip_tags($name);

        // Replace non-alphanumeric characters with dashes
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);

        // Remove multiple consecutive dashes
        $name = preg_replace('/-+/', '-', $name);

        // Trim dashes from ends
        $name = trim($name, '-');

        // Return lowercase
        return strtolower($name ?: 'export');
    }
}
