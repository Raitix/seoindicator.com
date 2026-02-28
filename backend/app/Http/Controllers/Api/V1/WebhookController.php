<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookRequest;
use App\Http\Requests\Api\V1\UpdateWebhookRequest;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Webhook Management Controller for TASK-6-13.
 *
 * Endpoints:
 * - GET /api/v1/webhooks - List user's webhooks
 * - POST /api/v1/webhooks - Create new webhook
 * - GET /api/v1/webhooks/{id} - Get single webhook
 * - PUT /api/v1/webhooks/{id} - Update webhook
 * - DELETE /api/v1/webhooks/{id} - Delete webhook
 * - POST /api/v1/webhooks/{id}/test - Test webhook delivery
 */
class WebhookController extends Controller
{
    /**
     * List all webhooks for the authenticated user.
     *
     * Returns webhooks ordered by created_at descending.
     * The secret field is never included in the response.
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $webhooks->map(fn ($webhook) => $this->formatWebhook($webhook)),
        ]);
    }

    /**
     * Create a new webhook.
     *
     * The secret is generated and returned ONLY on creation.
     * Store it securely - it cannot be retrieved again.
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $secret = Webhook::generateSecret();

        $webhook = Webhook::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'secret' => $secret,
            'events' => $request->input('events'),
            'enabled' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $this->formatWebhook($webhook),
                ['secret' => $secret] // Secret shown only on creation
            ),
        ], 201);
    }

    /**
     * Get a single webhook.
     *
     * The secret is NOT included in the response.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->find($id);

        if (!$webhook) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Webhook not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatWebhook($webhook),
        ]);
    }

    /**
     * Update a webhook.
     *
     * Supports partial updates. The secret cannot be changed.
     */
    public function update(UpdateWebhookRequest $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->find($id);

        if (!$webhook) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Webhook not found.',
                ],
            ], 404);
        }

        $webhook->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $this->formatWebhook($webhook->fresh()),
        ]);
    }

    /**
     * Delete a webhook.
     *
     * This will cascade delete all associated deliveries.
     */
    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->find($id);

        if (!$webhook) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Webhook not found.',
                ],
            ], 404);
        }

        $webhook->delete();

        return response()->noContent();
    }

    /**
     * Test a webhook by sending a test payload.
     *
     * Creates a delivery record with the result.
     * Does not affect the webhook's enabled status.
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->find($id);

        if (!$webhook) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Webhook not found.',
                ],
            ], 404);
        }

        $startTime = microtime(true);
        $delivered = false;
        $responseStatus = null;
        $responseBody = null;

        try {
            $response = Http::timeout(10)
                ->post($webhook->url, [
                    'test' => true,
                    'webhook_id' => $webhook->id,
                    'timestamp' => now()->toIso8601String(),
                ]);

            $responseStatus = $response->status();
            $responseBody = $response->body();
            $delivered = $response->successful();
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();
        }

        $responseTime = round((microtime(true) - $startTime) * 1000);

        // Create delivery record
        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => 'test',
            'payload' => [
                'test' => true,
                'webhook_id' => $webhook->id,
                'timestamp' => now()->toIso8601String(),
            ],
            'response_status' => $responseStatus,
            'response_body' => $responseBody ? substr($responseBody, 0, 65535) : null,
            'attempt_count' => 1,
            'delivered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'delivered' => $delivered,
                'response_status' => $responseStatus,
                'response_time_ms' => (int) $responseTime,
            ],
        ]);
    }

    /**
     * Format webhook for API response.
     *
     * Excludes the secret field.
     */
    protected function formatWebhook(Webhook $webhook): array
    {
        return [
            'id' => $webhook->id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'enabled' => $webhook->enabled,
            'created_at' => $webhook->created_at->toIso8601String(),
        ];
    }
}
