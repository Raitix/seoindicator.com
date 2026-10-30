<?php

namespace App\Services;

use App\Models\User;
use App\Models\Keyword;
use App\Models\AlertSettings;
use App\Models\Notification;
use App\Mail\RankingAlertEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * NotificationDispatcher Service
 *
 * Handles the delivery of alert notifications via configured channels.
 * Supports email and in-app notifications with quiet hours and digest modes.
 */
class NotificationDispatcher
{
    /**
     * Dispatch notifications for triggered events.
     *
     * @param User $user The user to notify
     * @param Keyword $keyword The keyword that triggered the alert
     * @param array $events Array of triggered events from AlertEvaluator
     * @return array Dispatch results
     */
    public function dispatch(User $user, Keyword $keyword, array $events): array
    {
        if (empty($events)) {
            return ['sent' => false, 'reason' => 'no_events'];
        }

        // Get user's alert settings
        $alertSettings = AlertSettings::firstOrCreate(
            ['user_id' => $user->id],
            AlertSettings::getDefaults()
        );

        // Get notification channels
        $channels = $this->getNotificationChannels($keyword, $alertSettings);

        $results = [
            'sent' => true,
            'channels' => [],
            'events_count' => count($events),
        ];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $results['channels']['email'] = $this->sendEmailNotification(
                        $user,
                        $keyword,
                        $events,
                        $alertSettings
                    );
                    break;

                case 'in_app':
                    $results['channels']['in_app'] = $this->createInAppNotification(
                        $user,
                        $keyword,
                        $events
                    );
                    break;
            }
        }

        Log::info('NotificationDispatcher: Notifications dispatched', [
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
            'events_count' => count($events),
            'channels' => $channels,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Send email notification.
     *
     * @param User $user
     * @param Keyword $keyword
     * @param array $events
     * @param AlertSettings $alertSettings
     * @return array
     */
    protected function sendEmailNotification(
        User $user,
        Keyword $keyword,
        array $events,
        AlertSettings $alertSettings
    ): array {
        // Check if in quiet hours
        if ($alertSettings->isInQuietHours()) {
            Log::info('NotificationDispatcher: Skipping email - in quiet hours', [
                'user_id' => $user->id,
            ]);

            // Queue for later if digest mode
            if ($alertSettings->digest_mode !== 'instant') {
                $this->queueForDigest($user, $keyword, $events, $alertSettings->digest_mode);
            }

            return [
                'sent' => false,
                'reason' => 'quiet_hours',
                'queued_for_digest' => $alertSettings->digest_mode !== 'instant',
            ];
        }

        // Handle digest mode
        if ($alertSettings->digest_mode !== 'instant') {
            $this->queueForDigest($user, $keyword, $events, $alertSettings->digest_mode);

            return [
                'sent' => false,
                'reason' => 'digest_mode',
                'digest_mode' => $alertSettings->digest_mode,
            ];
        }

        // Send immediately
        try {
            Mail::to($user->email)
                ->queue(new RankingAlertEmail($user, $keyword, $events));

            Log::info('NotificationDispatcher: Email queued', [
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'email' => $user->email,
            ]);

            return [
                'sent' => true,
                'queued_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('NotificationDispatcher: Email failed', [
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'reason' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create in-app notification.
     *
     * @param User $user
     * @param Keyword $keyword
     * @param array $events
     * @return array
     */
    protected function createInAppNotification(
        User $user,
        Keyword $keyword,
        array $events
    ): array {
        try {
            // Create one notification per event
            $notifications = [];

            foreach ($events as $event) {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => $event['event'],
                    'keyword_id' => $keyword->id,
                    'data' => [
                        'keyword' => $keyword->keyword,
                        'old_position' => $event['old_position'] ?? null,
                        'new_position' => $event['new_position'] ?? null,
                        'change' => $event['change'] ?? null,
                        'feature_type' => $event['feature_type'] ?? null,
                        'threshold' => $event['threshold'] ?? null,
                    ],
                ]);

                $notifications[] = $notification->id;
            }

            Log::info('NotificationDispatcher: In-app notifications created', [
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'notification_ids' => $notifications,
            ]);

            return [
                'sent' => true,
                'notification_ids' => $notifications,
            ];
        } catch (\Exception $e) {
            Log::error('NotificationDispatcher: In-app notification failed', [
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'reason' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get notification channels to use.
     *
     * @param Keyword $keyword
     * @param AlertSettings $alertSettings
     * @return array
     */
    protected function getNotificationChannels(Keyword $keyword, AlertSettings $alertSettings): array
    {
        // Check for keyword-specific channel override
        $keywordSettings = $keyword->alertSettings ?? null;

        if ($keywordSettings && !empty($keywordSettings->notification_channels)) {
            return $keywordSettings->notification_channels;
        }

        // Default channels
        return ['email', 'in_app'];
    }

    /**
     * Queue notification for digest delivery.
     *
     * @param User $user
     * @param Keyword $keyword
     * @param array $events
     * @param string $digestMode 'daily' or 'weekly'
     */
    protected function queueForDigest(User $user, Keyword $keyword, array $events, string $digestMode): void
    {
        // For MVP, we'll log this. A full implementation would queue to a digest table.
        Log::info('NotificationDispatcher: Queued for digest', [
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
            'digest_mode' => $digestMode,
            'events_count' => count($events),
        ]);

        // TODO: Implement digest queue table for Phase 2
        // This would store pending notifications for batch processing
    }

    /**
     * Get unread notification count for a user.
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param User $user
     * @return int Number of notifications marked
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
