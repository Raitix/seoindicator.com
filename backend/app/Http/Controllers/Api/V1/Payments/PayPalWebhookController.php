<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\CreditService;
use App\Services\Billing\PayPalGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Webhook Controller.
 *
 * Handles incoming PayPal webhook events for payment processing.
 *
 * SECURITY CRITICAL: All webhook requests are verified via PayPal signature
 * before processing. This prevents spoofing and replay attacks.
 *
 * @see TASK-26-14
 * @see FEAT-026
 */
class PayPalWebhookController extends Controller
{
    /**
     * Create a new PayPalWebhookController instance.
     */
    public function __construct(
        protected PayPalGatewayService $payPalGateway,
        protected CreditService $creditService
    ) {}

    /**
     * Handle incoming PayPal webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $payload = $request->getContent();

        // Log webhook attempt
        Log::info('PayPal webhook received', [
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'event_type' => $request->input('event_type'),
        ]);

        // Validate JSON payload first
        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('PayPal webhook invalid JSON payload');
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload',
            ], 400);
        }

        // SECURITY CRITICAL: Verify webhook signature
        if (!$this->payPalGateway->verifyWebhookSignature($request)) {
            Log::warning('PayPal webhook signature verification failed', [
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        try {
            // Process webhook via PayPalGatewayService
            $result = $this->payPalGateway->handleWebhook($request);

            // If capture completed, add credits to user
            if ($result->success && !$result->isDuplicate && !$result->isIgnored) {
                $this->processWebhookResult($result, $event);
            }

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('PayPal webhook processed', [
                'event_id' => $event['id'] ?? 'unknown',
                'event_type' => $event['event_type'] ?? 'unknown',
                'success' => $result->success,
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => $result->success,
                'event_type' => $event['event_type'] ?? null,
                'payment_id' => $result->paymentId,
                'amount' => $result->amount,
                'user_id' => $result->userId,
                'idempotent' => $result->isDuplicate,
                'ignored' => $result->isIgnored,
                'warning' => $result->errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal webhook processing error', [
                'event_id' => $event['id'] ?? 'unknown',
                'event_type' => $event['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Process webhook result to add/deduct credits.
     *
     * @param \App\Services\Billing\DTOs\PaymentResult $result
     * @param array $event
     * @return void
     */
    protected function processWebhookResult($result, array $event): void
    {
        $eventType = $event['event_type'] ?? null;

        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handleCaptureCompleted($result, $event);
                break;

            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handleCaptureRefunded($result, $event);
                break;

            case 'PAYMENT.CAPTURE.DENIED':
                $this->handleCaptureDenied($result, $event);
                break;

            case 'CHECKOUT.ORDER.APPROVED':
                // Order approved - no credit action needed yet
                // Credits added on capture
                break;
        }
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED event.
     *
     * @param \App\Services\Billing\DTOs\PaymentResult $result
     * @param array $event
     * @return void
     */
    protected function handleCaptureCompleted($result, array $event): void
    {
        if (!$result->userId) {
            Log::warning('PayPal capture completed without user_id', [
                'capture_id' => $result->paymentId,
                'custom_id' => $event['resource']['custom_id'] ?? null,
            ]);
            return;
        }

        $user = \App\Models\User::find($result->userId);
        if (!$user) {
            Log::warning('PayPal capture completed - user not found', [
                'user_id' => $result->userId,
                'capture_id' => $result->paymentId,
            ]);
            return;
        }

        // Parse custom_id for package_id
        $customId = $event['resource']['custom_id'] ?? '';
        $packageId = null;
        if (preg_match('/package_([^_]+)/', $customId, $matches)) {
            $packageId = $matches[1];
        }

        // Add credits via CreditService
        DB::transaction(function () use ($user, $result, $event, $packageId) {
            $this->creditService->addCredits(
                $user,
                $result->amount,
                'purchase',
                [
                    'payment_provider' => 'paypal',
                    'payment_id' => $result->paymentId,
                    'description' => 'PayPal credit purchase',
                    'metadata' => [
                        'event_id' => $event['id'] ?? null,
                        'package_id' => $packageId,
                        'currency' => $result->currency,
                    ],
                ]
            );
        });

        Log::info('PayPal credits added', [
            'user_id' => $result->userId,
            'capture_id' => $result->paymentId,
            'amount' => $result->amount,
        ]);
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED event.
     *
     * @param \App\Services\Billing\DTOs\PaymentResult $result
     * @param array $event
     * @return void
     */
    protected function handleCaptureRefunded($result, array $event): void
    {
        // Get refund amount from event
        $refundAmount = (float) ($event['resource']['seller_payable_breakdown']['total_refunded_amount']['value'] ?? $result->amount);
        $captureId = $result->paymentId;

        // Find original transaction
        $originalTransaction = \App\Models\CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $captureId)
            ->first();

        if (!$originalTransaction) {
            Log::warning('PayPal refund - original transaction not found', [
                'capture_id' => $captureId,
            ]);
            return;
        }

        $user = $originalTransaction->user;

        // Check if user has sufficient balance for deduction
        if (!$this->creditService->hasSufficientBalance($user, $refundAmount)) {
            Log::warning('PayPal refund - insufficient balance', [
                'user_id' => $user->id,
                'refund_amount' => $refundAmount,
                'current_balance' => $this->creditService->getBalance($user),
            ]);
            return;
        }

        // Deduct credits via CreditService
        DB::transaction(function () use ($user, $refundAmount, $captureId) {
            $this->creditService->refundCredits(
                $user,
                $refundAmount,
                null,
                "PayPal refund for capture {$captureId}"
            );
        });

        Log::info('PayPal refund credits deducted', [
            'user_id' => $user->id,
            'capture_id' => $captureId,
            'refund_amount' => $refundAmount,
        ]);
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED event.
     *
     * @param \App\Services\Billing\DTOs\PaymentResult $result
     * @param array $event
     * @return void
     */
    protected function handleCaptureDenied($result, array $event): void
    {
        $captureId = $result->paymentId;
        $reason = $event['resource']['status_details']['reason'] ?? 'Unknown';

        // Update any pending transaction to failed
        $transaction = \App\Models\CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $captureId)
            ->first();

        if ($transaction) {
            $transaction->update([
                'status' => \App\Models\CreditTransaction::STATUS_FAILED,
            ]);
        }

        Log::info('PayPal capture denied', [
            'capture_id' => $captureId,
            'reason' => $reason,
        ]);
    }
}
