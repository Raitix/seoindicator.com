<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use Illuminate\Support\Carbon;

/**
 * Service for handling webhook retry logic.
 *
 * Implements exponential backoff with configurable retry schedule.
 */
class WebhookRetryService
{
    /**
     * Retry schedule in minutes.
     * Key is the attempt number (1-indexed), value is delay in minutes.
     */
    private const RETRY_SCHEDULE = [
        1 => 1,      // 1 minute
        2 => 5,      // 5 minutes
        3 => 15,     // 15 minutes
        4 => 60,     // 1 hour
        5 => 360,    // 6 hours
    ];

    /**
     * Maximum number of retry attempts.
     */
    private const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Calculate the next retry time based on attempt count.
     *
     * @param int $attemptCount Current number of attempts (0-indexed)
     * @return Carbon|null Next retry time, or null if max attempts exceeded
     */
    public function calculateNextRetryAt(int $attemptCount): ?Carbon
    {
        // Next attempt number (1-indexed)
        $nextAttempt = $attemptCount + 1;

        if ($nextAttempt > self::MAX_RETRY_ATTEMPTS) {
            return null;
        }

        $delayMinutes = self::RETRY_SCHEDULE[$nextAttempt] ?? null;

        if ($delayMinutes === null) {
            return null;
        }

        return now()->addMinutes($delayMinutes);
    }

    /**
     * Determine if a response status should trigger a retry.
     *
     * @param int $responseStatus HTTP status code
     * @return bool True if should retry
     */
    public function shouldRetry(int $responseStatus): bool
    {
        // Retry on 5xx server errors
        return $responseStatus >= 500 && $responseStatus < 600;
    }

    /**
     * Determine if an error should trigger a retry.
     *
     * @param \Throwable|null $error The exception that occurred
     * @return bool True if should retry
     */
    public function shouldRetryOnError(?\Throwable $error): bool
    {
        if ($error === null) {
            return false;
        }

        // Retry on connection exceptions (timeouts, DNS failures, etc.)
        if ($error instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        return false;
    }

    /**
     * Get the number of remaining retry attempts.
     *
     * @param int $attemptCount Current number of attempts
     * @return int Number of attempts remaining
     */
    public function getRemainingAttempts(int $attemptCount): int
    {
        return max(0, self::MAX_RETRY_ATTEMPTS - $attemptCount);
    }

    /**
     * Check if a delivery can be retried.
     *
     * @param int $attemptCount Current number of attempts
     * @return bool True if can retry
     */
    public function canRetry(int $attemptCount): bool
    {
        return $attemptCount < self::MAX_RETRY_ATTEMPTS;
    }

    /**
     * Get the retry schedule configuration.
     *
     * @return array<int, int> Array of attempt => minutes
     */
    public function getRetrySchedule(): array
    {
        return self::RETRY_SCHEDULE;
    }

    /**
     * Schedule the next retry for a delivery.
     *
     * @param WebhookDelivery $delivery
     * @return bool True if retry was scheduled, false if max attempts exceeded
     */
    public function scheduleRetry(WebhookDelivery $delivery): bool
    {
        $nextRetryAt = $this->calculateNextRetryAt($delivery->attempt_count);

        if ($nextRetryAt === null) {
            return false;
        }

        $delivery->update([
            'next_retry_at' => $nextRetryAt,
            'attempt_count' => $delivery->attempt_count + 1,
        ]);

        return true;
    }

    /**
     * Mark a delivery as permanently failed.
     *
     * @param WebhookDelivery $delivery
     */
    public function markAsFailed(WebhookDelivery $delivery): void
    {
        $delivery->update([
            'next_retry_at' => null,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Get the maximum number of retry attempts.
     *
     * @return int
     */
    public function getMaxRetryAttempts(): int
    {
        return self::MAX_RETRY_ATTEMPTS;
    }
}
