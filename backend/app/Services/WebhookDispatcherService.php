<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Dispatcher Service for TASK-6-14.
 *
 * Handles HTTP POST delivery to webhook URLs with HMAC signatures,
 * timeout handling, and delivery logging.
 */
class WebhookDispatcherService
{
    /**
     * Default timeout in seconds for webhook requests.
     */
    protected int $timeout = 30;

    /**
     * Maximum payload size in bytes (1MB).
     */
    protected int $maxPayloadSize = 1048576;

    /**
     * Maximum response body storage size in bytes (64KB).
     */
    protected int $maxResponseBodySize = 65535;

    /**
     * Dispatch a webhook event.
     *
     * @param Webhook $webhook The webhook to dispatch to
     * @param array $event The event payload
     * @return bool True if dispatch was successful
     */
    public function dispatch(Webhook $webhook, array $event): bool
    {
        // Check if webhook is enabled
        if (!$webhook->isEnabled()) {
            Log::debug('Webhook disabled, skipping dispatch', ['webhook_id' => $webhook->id]);
            return false;
        }

        // Check if webhook subscribes to this event type
        $eventType = $event['type'] ?? null;
        if (!$eventType || !$webhook->subscribesTo($eventType)) {
            Log::debug('Webhook does not subscribe to event type', [
                'webhook_id' => $webhook->id,
                'event_type' => $eventType,
            ]);
            return false;
        }

        // Validate webhook URL
        if (empty($webhook->url)) {
            Log::warning('Webhook has empty URL', ['webhook_id' => $webhook->id]);
            return false;
        }

        // Check payload size
        $payload = json_encode($event);
        if (strlen($payload) > $this->maxPayloadSize) {
            Log::warning('Webhook payload exceeds maximum size', [
                'webhook_id' => $webhook->id,
                'payload_size' => strlen($payload),
            ]);
            // Still proceed but log the warning
        }

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $event,
            'attempt_count' => 1,
        ]);

        try {
            $timestamp = time();
            $signature = $this->generateSignature($payload, $webhook->secret, $timestamp);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-SEOIndicator-Signature' => $signature,
                    'X-SEOIndicator-Timestamp' => (string) $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $event);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Limit response body storage
            if (strlen($responseBody) > 10000) {
                $responseBody = substr($responseBody, 0, 10000) . '... [truncated]';
            }

            $delivery->update([
                'response_status' => $statusCode,
                'response_body' => $responseBody,
                'delivered_at' => now(),
            ]);

            $success = $statusCode >= 200 && $statusCode < 300;

            if ($success) {
                Log::info('Webhook dispatched successfully', [
                    'webhook_id' => $webhook->id,
                    'event_type' => $eventType,
                    'status' => $statusCode,
                ]);
            } else {
                Log::warning('Webhook dispatch returned error status', [
                    'webhook_id' => $webhook->id,
                    'event_type' => $eventType,
                    'status' => $statusCode,
                ]);
            }

            return $success;
        } catch (ConnectionException $e) {
            Log::error('Webhook dispatch failed: connection error', [
                'webhook_id' => $webhook->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            $delivery->update([
                'response_status' => null,
                'response_body' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Webhook dispatch failed: unexpected error', [
                'webhook_id' => $webhook->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            $delivery->update([
                'response_status' => null,
                'response_body' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Redeliver an existing webhook delivery (for retries).
     *
     * Updates the existing delivery record instead of creating a new one.
     * Increments attempt_count and updates response details.
     *
     * @param WebhookDelivery $delivery The delivery to retry
     * @return bool True if delivery was successful (2xx response)
     */
    public function redeliver(WebhookDelivery $delivery): bool
    {
        $webhook = $delivery->webhook;

        // Check if webhook exists and is enabled
        if (!$webhook || !$webhook->isEnabled()) {
            Log::debug('Webhook not available for redelivery', [
                'delivery_id' => $delivery->id,
                'webhook_id' => $delivery->webhook_id,
            ]);
            return false;
        }

        // Validate webhook URL
        if (empty($webhook->url)) {
            Log::warning('Webhook has empty URL for redelivery', [
                'delivery_id' => $delivery->id,
                'webhook_id' => $webhook->id,
            ]);
            return false;
        }

        // Get the event payload from the delivery
        $event = $delivery->payload;
        $payload = json_encode($event);

        // Increment attempt count for this retry
        $newAttemptCount = $delivery->attempt_count + 1;

        try {
            $timestamp = time();
            $signature = $this->generateSignature($payload, $webhook->secret, $timestamp);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-SEOIndicator-Signature' => $signature,
                    'X-SEOIndicator-Timestamp' => (string) $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $event);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Limit response body storage
            if (strlen($responseBody) > $this->maxResponseBodySize) {
                $responseBody = substr($responseBody, 0, $this->maxResponseBodySize);
            }

            // Update delivery with results
            $delivery->update([
                'attempt_count' => $newAttemptCount,
                'response_status' => $statusCode,
                'response_body' => $responseBody,
                'delivered_at' => now(),
            ]);

            $success = $statusCode >= 200 && $statusCode < 300;

            if ($success) {
                Log::info('Webhook redelivery successful', [
                    'delivery_id' => $delivery->id,
                    'webhook_id' => $webhook->id,
                    'attempt' => $newAttemptCount,
                    'status' => $statusCode,
                ]);
            } else {
                Log::warning('Webhook redelivery returned error status', [
                    'delivery_id' => $delivery->id,
                    'webhook_id' => $webhook->id,
                    'attempt' => $newAttemptCount,
                    'status' => $statusCode,
                ]);
            }

            return $success;
        } catch (ConnectionException $e) {
            Log::error('Webhook redelivery failed: connection error', [
                'delivery_id' => $delivery->id,
                'webhook_id' => $webhook->id,
                'attempt' => $newAttemptCount,
                'error' => $e->getMessage(),
            ]);

            // Update delivery with error info
            $delivery->update([
                'attempt_count' => $newAttemptCount,
                'response_status' => null,
                'response_body' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to let caller handle retry logic
        } catch (\Exception $e) {
            Log::error('Webhook redelivery failed: unexpected error', [
                'delivery_id' => $delivery->id,
                'webhook_id' => $webhook->id,
                'attempt' => $newAttemptCount,
                'error' => $e->getMessage(),
            ]);

            // Update delivery with error info
            $delivery->update([
                'attempt_count' => $newAttemptCount,
                'response_status' => null,
                'response_body' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to let caller handle retry logic
        }
    }

    /**
     * Generate HMAC signature for webhook payload.
     *
     * @param string $payload JSON-encoded payload
     * @param string $secret Webhook secret
     * @param int $timestamp Unix timestamp
     * @return string Signature in format "sha256=<hex>"
     */
    public function generateSignature(string $payload, string $secret, int $timestamp): string
    {
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return 'sha256=' . $signature;
    }

    /**
     * Create a standardized event payload.
     *
     * @param string $type Event type (e.g., 'ranking.changed')
     * @param array $data Event data
     * @return array Complete event payload
     */
    public function createEventPayload(string $type, array $data): array
    {
        return [
            'id' => $this->generateEventId(),
            'type' => $type,
            'created_at' => now()->toIso8601ZuluString(),
            'data' => $data,
        ];
    }

    /**
     * Generate a unique event ID.
     *
     * @return string Event ID in format "evt_<24 hex chars>"
     */
    protected function generateEventId(): string
    {
        return 'evt_' . bin2hex(random_bytes(12));
    }

    /**
     * Dispatch an event to all webhooks for a user.
     *
     * @param int $userId User ID
     * @param string $eventType Event type
     * @param array $eventData Event data
     * @return int Number of successful dispatches
     */
    public function dispatchToUserWebhooks(int $userId, string $eventType, array $eventData): int
    {
        $event = $this->createEventPayload($eventType, $eventData);

        $webhooks = Webhook::where('user_id', $userId)
            ->where('enabled', true)
            ->whereJsonContains('events', $eventType)
            ->get();

        $successCount = 0;

        foreach ($webhooks as $webhook) {
            if ($this->dispatch($webhook, $event)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}
