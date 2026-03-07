<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookRetryService;
use App\Services\WebhookDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to process pending webhook retries.
 *
 * This job is run periodically to retry failed webhook deliveries.
 */
class ProcessWebhookRetriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of deliveries to process per run.
     */
    protected int $batchSize;

    /**
     * Create a new job instance.
     *
     * @param int $batchSize Maximum deliveries to process
     */
    public function __construct(int $batchSize = 100)
    {
        $this->batchSize = $batchSize;
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     *
     * @param WebhookRetryService $retryService
     * @param WebhookDispatcherService $dispatcher
     */
    public function handle(
        WebhookRetryService $retryService,
        WebhookDispatcherService $dispatcher
    ): void {
        // Get deliveries pending retry
        $pendingDeliveries = WebhookDelivery::query()
            ->with('webhook')
            ->pendingRetry()
            ->limit($this->batchSize)
            ->get();

        Log::info('Processing webhook retries', [
            'count' => $pendingDeliveries->count(),
        ]);

        foreach ($pendingDeliveries as $delivery) {
            $this->processDelivery($delivery, $retryService, $dispatcher);
        }
    }

    /**
     * Process a single delivery.
     *
     * @param WebhookDelivery $delivery
     * @param WebhookRetryService $retryService
     * @param WebhookDispatcherService $dispatcher
     */
    protected function processDelivery(
        WebhookDelivery $delivery,
        WebhookRetryService $retryService,
        WebhookDispatcherService $dispatcher
    ): void {
        // Check if webhook exists and is enabled
        $webhook = $delivery->webhook;

        if (!$webhook || !$webhook->enabled) {
            // Skip if webhook deleted or disabled
            $delivery->update(['next_retry_at' => null]);
            return;
        }

        // Build event payload from delivery
        $event = [
            'id' => $delivery->id,
            'type' => $delivery->event_type,
            'created_at' => $delivery->created_at->toIso8601String(),
            'data' => $delivery->payload['data'] ?? $delivery->payload,
        ];

        try {
            $success = $dispatcher->dispatch($webhook, $event);

            if ($success) {
                // Delivery successful
                $delivery->update([
                    'next_retry_at' => null,
                ]);
                Log::info('Webhook retry successful', [
                    'delivery_id' => $delivery->id,
                    'attempt' => $delivery->attempt_count,
                ]);
            } else {
                $this->handleRetryFailure($delivery, $retryService);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook retry failed with exception', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            // Treat exceptions as retryable
            $this->handleRetryFailure($delivery, $retryService, $e);
        }
    }

    /**
     * Handle a failed retry attempt.
     *
     * @param WebhookDelivery $delivery
     * @param WebhookRetryService $retryService
     * @param \Throwable|null $error
     */
    protected function handleRetryFailure(
        WebhookDelivery $delivery,
        WebhookRetryService $retryService,
        ?\Throwable $error = null
    ): void {
        // Check if we should retry based on response status
        $shouldRetry = false;

        if ($delivery->response_status !== null) {
            $shouldRetry = $retryService->shouldRetry($delivery->response_status);
        } elseif ($error !== null) {
            $shouldRetry = $retryService->shouldRetryOnError($error);
        } else {
            // Null response status - treat as connection error
            $shouldRetry = true;
        }

        if ($shouldRetry && $retryService->canRetry($delivery->attempt_count)) {
            // Schedule next retry
            $nextRetryAt = $retryService->calculateNextRetryAt($delivery->attempt_count);

            if ($nextRetryAt !== null) {
                $delivery->update([
                    'next_retry_at' => $nextRetryAt,
                    'attempt_count' => $delivery->attempt_count + 1,
                ]);

                Log::info('Webhook retry scheduled', [
                    'delivery_id' => $delivery->id,
                    'attempt' => $delivery->attempt_count,
                    'next_retry_at' => $nextRetryAt->toIso8601String(),
                ]);
            } else {
                // Max retries exceeded
                $retryService->markAsFailed($delivery);
                Log::warning('Webhook retry exhausted', [
                    'delivery_id' => $delivery->id,
                    'attempts' => $delivery->attempt_count,
                ]);
            }
        } else {
            // Not retryable or max attempts exceeded
            $retryService->markAsFailed($delivery);
            Log::warning('Webhook marked as failed', [
                'delivery_id' => $delivery->id,
                'status' => $delivery->response_status,
            ]);
        }
    }

    /**
     * Get the number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;
}
