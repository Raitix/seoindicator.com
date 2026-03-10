<?php

namespace App\Services\ContentDecay\Contracts;

/**
 * Interface for Content Decay Export Service.
 *
 * Used for mocking in tests. Handles export and AI brief generation:
 * - Queue export (CSV, PDF, XLS)
 * - Recovery report generation
 * - AI refresh brief generation
 */
interface ContentDecayExportServiceInterface
{
    /**
     * Export the decay queue for a site.
     *
     * @param int $siteId
     * @param string $format Export format ('csv', 'pdf', 'xls')
     * @param array $filters {
     *     @type string $status Filter by status
     *     @type string $severity Filter by severity
     *     @type string $date_from Filter by detection date range
     *     @type string $date_to Filter by detection date range
     *     @type bool $recovery_report Generate recovery report instead
     * }
     * @return string File path or download URL
     * @throws \InvalidArgumentException If format is invalid
     */
    public function exportQueue(int $siteId, string $format, array $filters = []): string;

    /**
     * Generate an AI refresh brief for a candidate.
     *
     * @param int $candidateId
     * @return array {
     *     @type string $job_id The queued job ID
     *     @type string $status 'queued'
     * }
     */
    public function generateAiBrief(int $candidateId): array;

    /**
     * Get available export formats.
     *
     * @return array ['csv', 'pdf', 'xls']
     */
    public function getExportFormats(): array;

    /**
     * Check if AI brief feature is available.
     *
     * @return bool
     */
    public function isAiBriefAvailable(): bool;

    /**
     * Get the AI brief for a candidate (if already generated).
     *
     * @param int $candidateId
     * @return array|null The brief data or null if not generated
     */
    public function getAiBrief(int $candidateId): ?array;

    /**
     * Get export filename for a site.
     *
     * @param int $siteId
     * @param string $format
     * @param array $filters
     * @return string Filename with extension
     */
    public function getExportFilename(int $siteId, string $format, array $filters = []): string;
}
