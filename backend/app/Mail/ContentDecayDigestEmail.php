<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Content Decay Digest Email
 *
 * Sent weekly/monthly to summarize:
 * - Newly detected decay candidates
 * - Recovered pages
 * - Summary statistics and recommendations
 *
 * @see TASK-36-7
 * @see SPEC-036
 */
class ContentDecayDigestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public array $digest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $siteName = $this->getSiteName();
        $frequency = $this->digest['frequency'] ?? 'weekly';
        $frequencyLabel = $frequency === 'monthly' ? 'Monthly' : 'Weekly';

        $newCount = $this->digest['summary']['total_new'] ?? 0;
        $recoveredCount = $this->digest['summary']['total_recovered'] ?? 0;

        $subject = "[SEOIndicator] {$frequencyLabel} Content Health Report for {$siteName}";

        if ($newCount > 0 || $recoveredCount > 0) {
            $parts = [];
            if ($newCount > 0) {
                $parts[] = "{$newCount} new";
            }
            if ($recoveredCount > 0) {
                $parts[] = "{$recoveredCount} recovered";
            }
            $subject .= " - " . implode(', ', $parts);
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-decay-digest',
            with: [
                'user' => $this->user,
                'digest' => $this->digest,
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
                'dashboardUrl' => $this->generateDashboardUrl(),
                'siteName' => $this->getSiteName(),
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
        return [];
    }

    /**
     * Get the site name for the email.
     */
    protected function getSiteName(): string
    {
        return $this->digest['site_name'] ?? 'Your Site';
    }

    /**
     * Generate a signed unsubscribe URL.
     */
    protected function generateUnsubscribeUrl(): string
    {
        return URL::signedRoute(
            'alerts.unsubscribe',
            ['user' => $this->user->id],
            expiration: now()->addDays(30)
        );
    }

    /**
     * Generate the dashboard URL for content decay.
     */
    protected function generateDashboardUrl(): string
    {
        $projectId = $this->digest['project_id'] ?? null;
        $baseUrl = config('app.frontend_url', config('app.url'));
        
        if ($projectId) {
            return "{$baseUrl}/content-decay/projects/{$projectId}";
        }

        return "{$baseUrl}/content-decay";
    }
}
