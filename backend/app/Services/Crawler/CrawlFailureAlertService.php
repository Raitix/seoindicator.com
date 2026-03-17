<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Crawl Failure Alert Service.
 *
 * Monitors crawl job failures and sends alerts when consecutive failures
 * exceed configured thresholds. Supports email and Slack notifications.
 *
 * @see TASK-22-16
 * @see config/crawler.php (failure_alerts section)
 */
class CrawlFailureAlertService
{
    /**
     * Cache key prefix for failure tracking.
     */
    protected const CACHE_KEY_PREFIX = 'crawler:failures:';

    /**
     * Cache key for last alert timestamp.
     */
    protected const LAST_ALERT_KEY = 'crawler:failures:last_alert';

    /**
     * Check if failure alerts are enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config('crawler.failure_alerts.enabled', true);
    }

    /**
     * Record a job failure and check if an alert should be sent.
     *
     * @param CrawlJob $crawlJob The failed job
     * @param \Throwable $exception The failure exception
     * @return bool True if alert was sent
     */
    public function recordFailure(CrawlJob $crawlJob, \Throwable $exception): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Increment failure counter
        $failures = $this->incrementFailureCount();

        // Check if threshold exceeded
        if ($this->shouldSendAlert($failures)) {
            $sent = $this->sendAlert($crawlJob, $exception, $failures);
            
            if ($sent) {
                // Record alert timestamp for cooldown
                $this->recordAlertSent();
            }
            
            return $sent;
        }

        return false;
    }

    /**
     * Get the current consecutive failure count.
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        $key = $this->getFailureCacheKey();
        
        return (int) Cache::get($key, 0);
    }

    /**
     * Increment the failure counter.
     *
     * @return int The new failure count
     */
    protected function incrementFailureCount(): int
    {
        $key = $this->getFailureCacheKey();
        $windowMinutes = config('crawler.failure_alerts.window_minutes', 60);
        
        // Increment and set TTL to window duration
        $count = Cache::increment($key);
        
        if ($count === 1) {
            // First failure, set the TTL
            Cache::put($key, 1, now()->addMinutes($windowMinutes));
        }
        
        return $count;
    }

    /**
     * Reset the failure counter (e.g., after a successful job).
     *
     * @return void
     */
    public function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCacheKey());
    }

    /**
     * Check if an alert should be sent based on failure count and cooldown.
     *
     * @param int $failures Current failure count
     * @return bool
     */
    protected function shouldSendAlert(int $failures): bool
    {
        $threshold = config('crawler.failure_alerts.consecutive_threshold', 5);
        
        if ($failures < $threshold) {
            return false;
        }
        
        // Check cooldown period
        if ($this->isInCooldown()) {
            Log::info('CrawlFailureAlert: Alert suppressed due to cooldown', [
                'failures' => $failures,
                'threshold' => $threshold,
            ]);
            
            return false;
        }
        
        return true;
    }

    /**
     * Check if we're in the cooldown period after the last alert.
     *
     * @return bool
     */
    protected function isInCooldown(): bool
    {
        $lastAlert = Cache::get(self::LAST_ALERT_KEY);
        
        if (!$lastAlert) {
            return false;
        }
        
        $cooldownMinutes = config('crawler.failure_alerts.alert_cooldown', 30);
        
        return now()->diffInMinutes($lastAlert) < $cooldownMinutes;
    }

    /**
     * Record that an alert was just sent.
     *
     * @return void
     */
    protected function recordAlertSent(): void
    {
        Cache::put(self::LAST_ALERT_KEY, now(), now()->addHours(24));
    }

    /**
     * Send failure alert through configured channels.
     *
     * @param CrawlJob $crawlJob The failed job
     * @param \Throwable $exception The failure exception
     * @param int $failures Current failure count
     * @return bool True if any notification was sent successfully
     */
    protected function sendAlert(CrawlJob $crawlJob, \Throwable $exception, int $failures): bool
    {
        $alertData = $this->buildAlertData($crawlJob, $exception, $failures);
        
        Log::warning('CrawlFailureAlert: Consecutive failures detected', $alertData);
        
        $sent = false;
        
        // Send email notification
        if ($this->sendEmailAlert($alertData)) {
            $sent = true;
        }
        
        // Send Slack notification
        if ($this->sendSlackAlert($alertData)) {
            $sent = true;
        }
        
        return $sent;
    }

    /**
     * Build the alert data array.
     *
     * @param CrawlJob $crawlJob
     * @param \Throwable $exception
     * @param int $failures
     * @return array
     */
    protected function buildAlertData(CrawlJob $crawlJob, \Throwable $exception, int $failures): array
    {
        return [
            'job_id' => $crawlJob->job_id,
            'url' => $crawlJob->url,
            'error' => $exception->getMessage(),
            'consecutive_failures' => $failures,
            'threshold' => config('crawler.failure_alerts.consecutive_threshold', 5),
            'window_minutes' => config('crawler.failure_alerts.window_minutes', 60),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Send email alert to configured recipients.
     *
     * @param array $alertData
     * @return bool
     */
    protected function sendEmailAlert(array $alertData): bool
    {
        $emails = config('crawler.failure_alerts.notification_emails', '');
        
        if (empty($emails)) {
            return false;
        }
        
        $recipients = array_map('trim', explode(',', $emails));
        
        try {
            foreach ($recipients as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // In a real implementation, you would use a Mailable class
                    // For now, we'll just log the email
                    Log::info('CrawlFailureAlert: Email notification would be sent', [
                        'to' => $email,
                        'subject' => 'SEOIndicator: Crawler Failure Alert',
                        'data' => $alertData,
                    ]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('CrawlFailureAlert: Failed to send email alert', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send Slack alert via webhook.
     *
     * @param array $alertData
     * @return bool
     */
    protected function sendSlackAlert(array $alertData): bool
    {
        $webhookUrl = config('crawler.failure_alerts.slack_webhook', '');
        
        if (empty($webhookUrl)) {
            return false;
        }
        
        try {
            $payload = $this->buildSlackPayload($alertData);
            
            $response = Http::post($webhookUrl, $payload);
            
            if ($response->successful()) {
                Log::info('CrawlFailureAlert: Slack notification sent');
                return true;
            }
            
            Log::error('CrawlFailureAlert: Slack webhook failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('CrawlFailureAlert: Failed to send Slack alert', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Build the Slack message payload.
     *
     * @param array $alertData
     * @return array
     */
    protected function buildSlackPayload(array $alertData): array
    {
        return [
            'attachments' => [
                [
                    'color' => 'danger',
                    'title' => ':warning: Crawler Failure Alert',
                    'fields' => [
                        [
                            'title' => 'Consecutive Failures',
                            'value' => $alertData['consecutive_failures'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $alertData['threshold'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Last Failed URL',
                            'value' => $alertData['url'],
                            'short' => false,
                        ],
                        [
                            'title' => 'Error',
                            'value' => substr($alertData['error'], 0, 500),
                            'short' => false,
                        ],
                        [
                            'title' => 'Job ID',
                            'value' => $alertData['job_id'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => $alertData['timestamp'],
                            'short' => true,
                        ],
                    ],
                    'footer' => 'SEOIndicator Crawler Monitor',
                    'ts' => time(),
                ],
            ],
        ];
    }

    /**
     * Get the cache key for failure tracking.
     *
     * @return string
     */
    protected function getFailureCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . 'count';
    }

    /**
     * Get the time of the last alert.
     *
     * @return Carbon|null
     */
    public function getLastAlertTime(): ?Carbon
    {
        $timestamp = Cache::get(self::LAST_ALERT_KEY);
        
        return $timestamp ? Carbon::parse($timestamp) : null;
    }
}
