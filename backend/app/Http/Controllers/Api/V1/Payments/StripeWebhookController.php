<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Services\Gateway\StripeSignatureVerifier;
use App\Services\Gateway\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Webhook Controller for TASK-26-10.
 * 
 * Receives and processes Stripe webhook events.
 * NO authentication required - security via signature verification.
 * 
 * @see TASK-26-10
 * @see FEAT-026
 */
class StripeWebhookController extends Controller
{
    /**
     * Create a new webhook controller instance.
     */
    public function __construct(
        protected StripeSignatureVerifier $signatureVerifier,
        protected StripeWebhookService $webhookService
    ) {}

    /**
     * Handle incoming Stripe webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('Stripe webhook received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_length' => strlen($request->getContent()),
        ]);

        // Verify the webhook signature
        $verification = $this->signatureVerifier->verify($request);

        if (!$verification['valid']) {
            Log::warning('Stripe webhook signature verification failed', [
                'ip' => $request->ip(),
                'error' => $verification['error'],
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        // Process the webhook
        $result = $this->webhookService->processWebhook($verification['payload']);

        if (!$result['success']) {
            Log::error('Stripe webhook processing failed', [
                'event_type' => $result['event_type'],
                'message' => $result['message'],
            ]);

            // Return 500 for processing errors (Stripe will retry)
            return response()->json([
                'error' => 'Processing failed',
                'message' => $result['message'],
            ], 500);
        }

        Log::info('Stripe webhook processed successfully', [
            'event_type' => $result['event_type'],
            'message' => $result['message'],
        ]);

        // Return 200 for success (acknowledges to Stripe)
        return response()->json([
            'received' => true,
            'event_type' => $result['event_type'],
        ], 200);
    }
}
