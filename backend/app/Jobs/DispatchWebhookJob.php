<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Services\WebhookDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for async webhook dispatch.
 *
 * Handles dispatching webhook events asynchronously with retry support.
 */
class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The webhook ID to dispatch to.
     */
    public int $webhookId;

    /**
     * The event payload to send.
     */
    public array $event;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $webhookId, array $event)
    {
        $this->webhookId = $webhookId;
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookDispatcherService $dispatcher): void
    {
        // Find the webhook
        $webhook = Webhook::find($this->webhookId);

        // Handle non-existent or deleted webhook
        if (!$webhook) {
            Log::debug('Webhook not found, skipping dispatch', [
                'webhook_id' => $this->webhookId,
                'event_id' => $this->event['id'] ?? 'unknown',
            ]);
            return;
        }

        // Handle disabled webhook
        if (!$webhook->isEnabled()) {
            Log::debug('Webhook is disabled, skipping dispatch', [
                'webhook_id' => $this->webhookId,
            ]);
            return;
        }

        // Handle unsubscribed event
        $eventType = $this->event['type'] ?? null;
        if ($eventType && !$webhook->subscribesTo($eventType)) {
            Log::debug('Webhook does not subscribe to event type', [
                'webhook_id' => $this->webhookId,
                'event_type' => $eventType,
            ]);
            return;
        }

        // Dispatch the webhook
        $dispatcher->dispatch($webhook, $this->event);
    }

    /**
     * Get the unique job identifier.
     */
    public function uniqueId(): string
    {
        return sprintf(
            'webhook-%d-event-%s',
            $this->webhookId,
            $this->event['id'] ?? uniqid()
        );
    }
}
