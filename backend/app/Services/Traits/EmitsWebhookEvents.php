<?php

namespace App\Services\Traits;

use App\Models\Webhook;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Log;

/**
 * Trait for emitting webhook events from services.
 *
 * This trait provides a standardized way to emit webhook events
 * when significant actions occur in the application.
 *
 * @see TASK-6-16
 */
trait EmitsWebhookEvents
{
    /**
     * The webhook dispatcher service instance.
     */
    protected ?WebhookDispatcherService $webhookDispatcher = null;

    /**
     * Set the webhook dispatcher service.
     */
    public function setWebhookDispatcher(WebhookDispatcherService $dispatcher): void
    {
        $this->webhookDispatcher = $dispatcher;
    }

    /**
     * Get the webhook dispatcher service.
     * Always resolves from container to allow mocking in tests.
     */
    protected function getWebhookDispatcher(): WebhookDispatcherService
    {
        // If explicitly set (e.g., for testing), use that
        if ($this->webhookDispatcher !== null) {
            return $this->webhookDispatcher;
        }

        // Always resolve from container to allow mocking
        return app(WebhookDispatcherService::class);
    }

    /**
     * Emit a webhook event to all matching webhooks for a user.
     *
     * @param int $userId User ID who owns the resource
     * @param string $eventType Event type (e.g., 'ranking.changed')
     * @param array $data Event data payload
     * @return int Number of successful dispatches
     */
    protected function emitWebhookEvent(int $userId, string $eventType, array $data): int
    {
        try {
            return $this->getWebhookDispatcher()->dispatchToUserWebhooks($userId, $eventType, $data);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            Log::error('Webhook event emission failed', [
                'user_id' => $userId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Emit a ranking.changed event.
     *
     * @param int $userId User ID
     * @param int $keywordId Keyword ID
     * @param string $keyword Keyword text
     * @param int $siteId Site ID
     * @param int|null $oldPosition Previous position (null if first ranking)
     * @param int|null $newPosition Current position (null if dropped out)
     * @param string|null $urlFound URL found at position
     * @param string $checkedAt Check timestamp (ISO 8601)
     * @param array|null $serpFeatures SERP features data
     * @return int Number of successful dispatches
     */
    public function emitRankingChangedEvent(
        int $userId,
        int $keywordId,
        string $keyword,
        int $siteId,
        ?int $oldPosition,
        ?int $newPosition,
        ?string $urlFound = null,
        string $checkedAt = '',
        ?array $serpFeatures = null
    ): int {
        // Calculate position change
        $change = $this->calculatePositionChange($oldPosition, $newPosition);

        $data = [
            'keyword_id' => $keywordId,
            'keyword' => $keyword,
            'site_id' => $siteId,
            'old_position' => $oldPosition,
            'new_position' => $newPosition,
            'change' => $change,
            'url_found' => $urlFound,
            'checked_at' => $checkedAt ?: now()->toIso8601ZuluString(),
        ];

        if ($serpFeatures !== null) {
            $data['serp_features'] = $serpFeatures;
        }

        return $this->emitWebhookEvent($userId, 'ranking.changed', $data);
    }

    /**
     * Emit a keyword.added event.
     *
     * @param int $userId User ID
     * @param int $keywordId Keyword ID
     * @param string $keyword Keyword text
     * @param int $siteId Site ID
     * @param string|null $targetUrl Target URL
     * @param string $searchEngine Search engine
     * @param string $location Location code
     * @param string $device Device type
     * @param string $status Keyword status
     * @return int Number of successful dispatches
     */
    public function emitKeywordAddedEvent(
        int $userId,
        int $keywordId,
        string $keyword,
        int $siteId,
        ?string $targetUrl = null,
        string $searchEngine = 'google',
        string $location = 'us',
        string $device = 'desktop',
        string $status = 'active'
    ): int {
        $data = [
            'keyword_id' => $keywordId,
            'keyword' => $keyword,
            'site_id' => $siteId,
            'target_url' => $targetUrl,
            'search_engine' => $searchEngine,
            'location' => $location,
            'device' => $device,
            'status' => $status,
        ];

        return $this->emitWebhookEvent($userId, 'keyword.added', $data);
    }

    /**
     * Emit a keyword.removed event.
     *
     * @param int $userId User ID
     * @param int $keywordId Keyword ID
     * @param string $keyword Keyword text
     * @param int $siteId Site ID
     * @return int Number of successful dispatches
     */
    public function emitKeywordRemovedEvent(
        int $userId,
        int $keywordId,
        string $keyword,
        int $siteId
    ): int {
        $data = [
            'keyword_id' => $keywordId,
            'keyword' => $keyword,
            'site_id' => $siteId,
            'removed_at' => now()->toIso8601ZuluString(),
        ];

        return $this->emitWebhookEvent($userId, 'keyword.removed', $data);
    }

    /**
     * Emit a report.completed event.
     *
     * @param int $userId User ID
     * @param int $reportId Report ID
     * @param int $siteId Site ID
     * @param string $type Report type
     * @param string $format Report format
     * @param string $downloadUrl Download URL
     * @param int|null $fileSize File size in bytes
     * @param int|null $scheduledReportId Scheduled report ID if from schedule
     * @param array|null $dateRange Date range ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return int Number of successful dispatches
     */
    public function emitReportCompletedEvent(
        int $userId,
        int $reportId,
        int $siteId,
        string $type,
        string $format,
        string $downloadUrl,
        ?int $fileSize = null,
        ?int $scheduledReportId = null,
        ?array $dateRange = null
    ): int {
        $data = [
            'report_id' => $reportId,
            'site_id' => $siteId,
            'scheduled_report_id' => $scheduledReportId,
            'type' => $type,
            'format' => $format,
            'download_url' => $downloadUrl,
            'file_size' => $fileSize,
            'date_range' => $dateRange,
            'generated_at' => now()->toIso8601ZuluString(),
        ];

        return $this->emitWebhookEvent($userId, 'report.completed', $data);
    }

    /**
     * Emit an alert.triggered event.
     *
     * @param int $userId User ID
     * @param int $alertId Alert ID
     * @param string $alertName Alert name
     * @param int $keywordId Keyword ID
     * @param string $keyword Keyword text
     * @param int $siteId Site ID
     * @param string $condition Condition type
     * @param mixed $threshold Threshold value
     * @param mixed $previousValue Previous value
     * @param mixed $currentValue Current value
     * @return int Number of successful dispatches
     */
    public function emitAlertTriggeredEvent(
        int $userId,
        int $alertId,
        string $alertName,
        int $keywordId,
        string $keyword,
        int $siteId,
        string $condition,
        $threshold,
        $previousValue,
        $currentValue
    ): int {
        $data = [
            'alert_id' => $alertId,
            'alert_name' => $alertName,
            'keyword_id' => $keywordId,
            'keyword' => $keyword,
            'site_id' => $siteId,
            'condition' => $condition,
            'threshold' => $threshold,
            'previous_value' => $previousValue,
            'current_value' => $currentValue,
            'triggered_at' => now()->toIso8601ZuluString(),
        ];

        return $this->emitWebhookEvent($userId, 'alert.triggered', $data);
    }

    /**
     * Calculate the position change.
     *
     * Negative = improved (moved up)
     * Positive = declined (moved down)
     *
     * @param int|null $oldPosition
     * @param int|null $newPosition
     * @return int|null Position change or null if cannot calculate
     */
    public function calculatePositionChange(?int $oldPosition, ?int $newPosition): ?int
    {
        // First ranking (no previous position)
        if ($oldPosition === null) {
            return null;
        }

        // Dropped out of rankings
        if ($newPosition === null) {
            return null;
        }

        // Calculate change: old - new (negative = improvement)
        return $oldPosition - $newPosition;
    }

    /**
     * Check if position has changed.
     */
    protected function hasPositionChanged(?int $oldPosition, ?int $newPosition): bool
    {
        // First ranking
        if ($oldPosition === null && $newPosition !== null) {
            return true;
        }

        // Dropped out of rankings
        if ($oldPosition !== null && $newPosition === null) {
            return true;
        }

        // Position changed
        return $oldPosition !== $newPosition;
    }
}
