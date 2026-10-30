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
 * SERP Feature Alert Email
 *
 * Sent when a SERP feature is gained or lost for a tracked keyword.
 */
class SerpFeatureAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public array $alert
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SEOIndicator] ' . $this->alert['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.serp-feature-alert',
            with: [
                'user' => $this->user,
                'alert' => $this->alert,
                'keyword' => $this->alert['keyword'],
                'featureName' => $this->alert['feature_name'],
                'featureType' => $this->alert['feature_type'],
                'action' => $this->alert['action'],
                'ctrImpact' => $this->alert['ctr_impact'],
                'url' => $this->alert['url'],
                'previousUrl' => $this->alert['previous_url'],
                'detectedAt' => $this->alert['detected_at'],
                'body' => $this->alert['body'],
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
                'dashboardUrl' => $this->generateDashboardUrl(),
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
     * Generate the dashboard URL for the keyword.
     */
    protected function generateDashboardUrl(): string
    {
        return config('app.frontend_url', config('app.url')) . '/keywords/' . $this->alert['keyword_id'];
    }
}
