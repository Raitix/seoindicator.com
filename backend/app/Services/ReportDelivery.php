<?php

namespace App\Services;

use App\Mail\ReportReady;
use App\Models\Report;
use App\Models\Site;
use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Report Delivery Service for TASK-4-19.
 *
 * Handles email delivery of generated reports with:
 * - PDF/CSV attachments
 * - File size threshold handling (>10MB uses link only)
 * - Per-recipient delivery logging
 * - Partial failure handling
 * - Report record status updates
 */
class ReportDelivery
{
    protected ReportRepository $reportRepository;

    /**
     * Maximum attachment size in bytes (10MB)
     */
    protected const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;

    /**
     * Create a new ReportDelivery instance.
     */
    public function __construct(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Deliver a report to one or more recipients.
     *
     * @param Report $report The report to deliver
     * @param array|string $recipients Single email or array of emails
     * @param array $options Optional settings (previewUrl, metrics, etc.)
     * @return array Delivery result with status and log
     */
    public function deliver(Report $report, array|string $recipients, array $options = []): array
    {
        // Normalize recipients to array and deduplicate
        $recipients = $this->normalizeRecipients($recipients);

        if (empty($recipients)) {
            return $this->createResult('failed', [], 'No valid recipients provided');
        }

        // Verify report has a file
        if (!$report->file_path || !file_exists($report->file_path)) {
            return $this->handleMissingFile($report, $recipients);
        }

        // Get site information
        $site = $report->site;

        if (!$site) {
            return $this->createResult('failed', [], 'Report has no associated site');
        }

        // Determine if we should attach the file
        $attachFile = $this->shouldAttachFile($report);

        // Build email data
        $emailData = $this->buildEmailData($report, $site, $options, $attachFile);

        // Send to each recipient
        $deliveryLog = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $email) {
            $result = $this->sendToRecipient($email, $report, $emailData, $attachFile);
            $deliveryLog[] = $result;

            if ($result['status'] === 'sent') {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        // Determine overall delivery status
        $deliveryStatus = $this->determineDeliveryStatus($successCount, $failureCount, count($recipients));

        // Update report record
        $this->updateReportDeliveryStatus($report, $deliveryStatus, $deliveryLog);

        return $this->createResult($deliveryStatus, $deliveryLog);
    }

    /**
     * Deliver report to recipients from a scheduled report.
     *
     * @param Report $report The generated report
     * @param \App\Models\ScheduledReport $scheduledReport The scheduled report config
     * @return array Delivery result
     */
    public function deliverFromSchedule(Report $report, \App\Models\ScheduledReport $scheduledReport): array
    {
        return $this->deliver($report, $scheduledReport->recipients ?? []);
    }

    /**
     * Normalize recipients to array and deduplicate.
     *
     * @param array|string $recipients
     * @return array
     */
    protected function normalizeRecipients(array|string $recipients): array
    {
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        // Filter valid emails and deduplicate (case-insensitive)
        $validEmails = [];
        $seen = [];

        foreach ($recipients as $email) {
            $email = trim(strtolower($email));

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($seen[$email])) {
                $validEmails[] = $email;
                $seen[$email] = true;
            }
        }

        return $validEmails;
    }

    /**
     * Check if file should be attached based on size.
     *
     * @param Report $report
     * @return bool
     */
    protected function shouldAttachFile(Report $report): bool
    {
        $fileSize = $report->file_size ?? 0;

        // If file_size is 0, try to get actual file size
        if ($fileSize === 0 && $report->file_path && file_exists($report->file_path)) {
            $fileSize = filesize($report->file_path);
        }

        return $fileSize <= self::MAX_ATTACHMENT_SIZE;
    }

    /**
     * Handle delivery when report file is missing.
     *
     * @param Report $report
     * @param array $recipients
     * @return array
     */
    protected function handleMissingFile(Report $report, array $recipients): array
    {
        $log = array_map(function ($email) {
            return [
                'email' => $email,
                'status' => 'failed',
                'error' => 'Report file not found',
                'timestamp' => now()->toIso8601String(),
            ];
        }, $recipients);

        $this->updateReportDeliveryStatus($report, 'failed', $log);

        return $this->createResult('failed', $log, 'Report file not found');
    }

    /**
     * Build email data for the mailable.
     *
     * @param Report $report
     * @param Site $site
     * @param array $options
     * @param bool $attachFile
     * @return array
     */
    protected function buildEmailData(Report $report, Site $site, array $options, bool $attachFile): array
    {
        $downloadUrl = $options['downloadUrl'] ?? $this->generateDownloadUrl($report);
        $previewUrl = $options['previewUrl'] ?? $this->generatePreviewUrl($report, $site);

        return [
            'reportData' => [
                'id' => $report->id,
                'name' => $report->name,
                'type' => $report->type,
                'format' => $report->format,
                'date_range_start' => $report->date_range_start?->format('Y-m-d'),
                'date_range_end' => $report->date_range_end?->format('Y-m-d'),
                'generated_at' => $report->generated_at?->toIso8601String(),
                'file_path' => $attachFile ? $report->file_path : null,
                'file_name' => $this->generateFileName($report),
            ],
            'siteData' => [
                'id' => $site->id,
                'name' => $site->name ?? parse_url($site->url, PHP_URL_HOST) ?? 'Your Site',
                'url' => $site->url,
            ],
            'metrics' => $options['metrics'] ?? [],
            'downloadUrl' => $downloadUrl,
            'previewUrl' => $previewUrl,
            'unsubscribeUrl' => $options['unsubscribeUrl'] ?? $this->generateUnsubscribeUrl($report),
        ];
    }

    /**
     * Send email to a single recipient.
     *
     * @param string $email
     * @param Report $report
     * @param array $emailData
     * @param bool $attachFile
     * @return array
     */
    protected function sendToRecipient(string $email, Report $report, array $emailData, bool $attachFile): array
    {
        try {
            // Build mailable with or without attachment
            $reportData = $emailData['reportData'];

            // If not attaching file, clear file_path from report data
            if (!$attachFile) {
                $reportData['file_path'] = null;
            }

            $mailable = new ReportReady(
                $reportData,
                $emailData['siteData'],
                $emailData['metrics'],
                $emailData['downloadUrl'],
                $emailData['unsubscribeUrl'],
                $emailData['previewUrl']
            );

            Mail::to($email)->send($mailable);

            Log::info("Report {$report->id} delivered to {$email}", [
                'report_id' => $report->id,
                'recipient' => $email,
                'attached' => $attachFile,
            ]);

            return [
                'email' => $email,
                'status' => 'sent',
                'attached' => $attachFile,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error("Failed to deliver report {$report->id} to {$email}", [
                'report_id' => $report->id,
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'email' => $email,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Determine overall delivery status based on results.
     *
     * @param int $successCount
     * @param int $failureCount
     * @param int $totalCount
     * @return string
     */
    protected function determineDeliveryStatus(int $successCount, int $failureCount, int $totalCount): string
    {
        if ($successCount === 0) {
            return 'failed';
        }

        if ($failureCount > 0) {
            return 'partial';
        }

        return 'sent';
    }

    /**
     * Update report record with delivery status.
     *
     * @param Report $report
     * @param string $status
     * @param array $log
     */
    protected function updateReportDeliveryStatus(Report $report, string $status, array $log): void
    {
        $this->reportRepository->update($report->id, [
            'delivered_at' => now(),
            'delivery_status' => $status,
            'delivery_log' => $log,
        ]);
    }

    /**
     * Generate download URL for the report.
     *
     * @param Report $report
     * @return string
     */
    protected function generateDownloadUrl(Report $report): string
    {
        return url("/api/v1/reports/{$report->id}/download");
    }

    /**
     * Generate preview URL (dashboard) for the report.
     *
     * @param Report $report
     * @param Site $site
     * @return string
     */
    protected function generatePreviewUrl(Report $report, Site $site): string
    {
        return url("/sites/{$site->id}/reports/{$report->id}");
    }

    /**
     * Generate unsubscribe URL for report notifications.
     *
     * @param Report $report
     * @return string
     */
    protected function generateUnsubscribeUrl(Report $report): string
    {
        $token = hash('sha256', $report->id . $report->site_id . config('app.key'));

        return url("/reports/{$report->id}/unsubscribe?token={$token}");
    }

    /**
     * Generate a filename for the report attachment.
     *
     * @param Report $report
     * @return string
     */
    protected function generateFileName(Report $report): string
    {
        $name = Str::slug($report->name ?? 'report');
        $date = $report->generated_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $extension = $report->format ?? 'pdf';

        return "{$name}-{$date}.{$extension}";
    }

    /**
     * Create a result array.
     *
     * @param string $status
     * @param array $log
     * @param string|null $error
     * @return array
     */
    protected function createResult(string $status, array $log, ?string $error = null): array
    {
        return [
            'status' => $status,
            'log' => $log,
            'error' => $error,
        ];
    }
}
