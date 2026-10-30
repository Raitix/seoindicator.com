<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for report delivery notifications.
 * 
 * Supports:
 * - HTML and text multipart emails
 * - PDF/CSV attachments
 * - Key metrics preview
 * - Unsubscribe links
 * - Dark mode compatible styling
 */
class ReportReady extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Report data
     */
    public array $reportData;

    /**
     * Site information
     */
    public array $siteData;

    /**
     * Key metrics for preview
     */
    public array $metrics;

    /**
     * Download URL for the report
     */
    public string $downloadUrl;

    /**
     * Preview URL (dashboard)
     */
    public ?string $previewUrl;

    /**
     * Unsubscribe URL
     */
    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        array $reportData,
        array $siteData,
        array $metrics,
        string $downloadUrl,
        string $unsubscribeUrl,
        ?string $previewUrl = null
    ) {
        $this->reportData = $reportData;
        $this->siteData = $siteData;
        $this->metrics = $metrics;
        $this->downloadUrl = $downloadUrl;
        $this->previewUrl = $previewUrl;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $reportName = $this->reportData['name'] ?? 'SEO Report';
        $siteName = $this->siteData['name'] ?? 'Your Site';

        return new Envelope(
            subject: "{$reportName} is ready - {$siteName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report-ready',
            text: 'emails.report-ready-text',
            with: [
                'reportName' => $this->reportData['name'] ?? 'SEO Report',
                'reportType' => $this->reportData['type'] ?? 'weekly',
                'reportFormat' => $this->reportData['format'] ?? 'pdf',
                'dateRangeStart' => $this->reportData['date_range_start'] ?? null,
                'dateRangeEnd' => $this->reportData['date_range_end'] ?? null,
                'siteName' => $this->siteData['name'] ?? 'Your Site',
                'siteUrl' => $this->siteData['url'] ?? null,
                'metrics' => $this->metrics,
                'downloadUrl' => $this->downloadUrl,
                'previewUrl' => $this->previewUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'generatedAt' => $this->reportData['generated_at'] ?? now()->toIso8601String(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Add report file as attachment if path is provided
        if (!empty($this->reportData['file_path'])) {
            $filePath = $this->reportData['file_path'];
            $fileName = $this->reportData['file_name'] ?? $this->generateFileName();

            if (file_exists($filePath)) {
                $attachments[] = Attachment::fromPath($filePath)
                    ->as($fileName)
                    ->withMime($this->getMimeType($this->reportData['format'] ?? 'pdf'));
            }
        }

        return $attachments;
    }

    /**
     * Generate a filename for the report
     */
    protected function generateFileName(): string
    {
        $reportName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->reportData['name'] ?? 'report');
        $reportName = preg_replace('/-+/', '-', $reportName);
        $date = date('Y-m-d');
        $extension = $this->reportData['format'] ?? 'pdf';

        return strtolower("{$reportName}-{$date}.{$extension}");
    }

    /**
     * Get MIME type for report format
     */
    protected function getMimeType(string $format): string
    {
        return match ($format) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }

    /**
     * Build the view data for rendering
     */
    public function build(): static
    {
        return $this->view('emails.report-ready')
            ->text('emails.report-ready-text')
            ->with([
                'reportName' => $this->reportData['name'] ?? 'SEO Report',
                'reportType' => $this->reportData['type'] ?? 'weekly',
                'reportFormat' => $this->reportData['format'] ?? 'pdf',
                'dateRangeStart' => $this->reportData['date_range_start'] ?? null,
                'dateRangeEnd' => $this->reportData['date_range_end'] ?? null,
                'siteName' => $this->siteData['name'] ?? 'Your Site',
                'siteUrl' => $this->siteData['url'] ?? null,
                'metrics' => $this->metrics,
                'downloadUrl' => $this->downloadUrl,
                'previewUrl' => $this->previewUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'generatedAt' => $this->reportData['generated_at'] ?? now()->toIso8601String(),
            ]);
    }
}
