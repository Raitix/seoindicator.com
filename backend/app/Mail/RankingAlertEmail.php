<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Keyword;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Ranking Alert Email
 *
 * Sent when a keyword ranking changes significantly.
 */
class RankingAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Keyword $keyword,
        public array $events
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $eventType = $this->getPrimaryEventType();
        $keywordName = $this->keyword->keyword;

        return new Envelope(
            subject: "[SEOIndicator] Alert: \"{$keywordName}\" {$eventType}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ranking-alert',
            with: [
                'user' => $this->user,
                'keyword' => $this->keyword,
                'events' => $this->events,
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
     * Get the primary event type for the subject.
     */
    protected function getPrimaryEventType(): string
    {
        if (empty($this->events)) {
            return 'ranking change';
        }

        $eventLabels = [
            'position_drop' => 'position dropped',
            'position_gain' => 'position improved',
            'entered_top_10' => 'entered top 10',
            'left_top_10' => 'left top 10',
            'entered_top_3' => 'entered top 3',
            'left_top_3' => 'left top 3',
            'serp_feature_gained' => 'gained SERP feature',
            'serp_feature_lost' => 'lost SERP feature',
        ];

        $primaryEvent = $this->events[0]['event'] ?? 'ranking change';

        return $eventLabels[$primaryEvent] ?? 'ranking change';
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
        return config('app.frontend_url', config('app.url')) . '/keywords/' . $this->keyword->id;
    }
}
