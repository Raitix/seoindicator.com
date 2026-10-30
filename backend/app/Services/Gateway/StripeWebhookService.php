<?php

namespace App\Services\Gateway;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event as StripeEvent;
use Stripe\PaymentIntent;
use Stripe\Charge;
use Exception;

/**
 * Stripe Webhook Service for TASK-26-10.
 * 
 * Processes Stripe webhook events for payment confirmation,
 * failures, and refunds.
 * 
 * @see TASK-26-10
 * @see FEAT-026
 */
class StripeWebhookService
{
    /**
     * Supported webhook event types.
     */
    public const SUPPORTED_EVENTS = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'charge.refunded',
    ];

    /**
     * Create a new webhook service instance.
     */
    public function __construct(
        protected CreditService $creditService,
        protected StripeSignatureVerifier $signatureVerifier
    ) {}

    /**
     * Process a webhook event payload.
     *
     * @param string $payload
     * @return array{success: bool, event_type: string|null, message: string}
     */
    public function processWebhook(string $payload): array
    {
        try {
            // Validate payload is valid JSON first
            $decoded = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in Stripe webhook payload', [
                    'json_error' => json_last_error_msg(),
                ]);
                
                return [
                    'success' => false,
                    'event_type' => null,
                    'message' => 'Failed to process webhook: Invalid JSON payload',
                ];
            }
            
            if (!is_array($decoded) || empty($decoded)) {
                return [
                    'success' => false,
                    'event_type' => null,
                    'message' => 'Failed to process webhook: Empty or invalid payload',
                ];
            }

            // Parse the event
            $event = StripeEvent::constructFrom($decoded);

            Log::info('Processing Stripe webhook event', [
                'event_id' => $event->id ?? 'unknown',
                'event_type' => $event->type ?? 'unknown',
            ]);

            // Check if we support this event type
            if (!in_array($event->type, self::SUPPORTED_EVENTS)) {
                Log::debug('Unsupported Stripe webhook event type', [
                    'event_type' => $event->type,
                    'event_id' => $event->id ?? 'unknown',
                ]);

                return [
                    'success' => true, // Return success for idempotency
                    'event_type' => $event->type,
                    'message' => 'Event type not supported, acknowledged',
                ];
            }

            // Route to appropriate handler
            return match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($event),
                'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
                'charge.refunded' => $this->handleChargeRefunded($event),
                default => [
                    'success' => true,
                    'event_type' => $event->type,
                    'message' => 'Event acknowledged',
                ],
            };
        } catch (Exception $e) {
            Log::error('Failed to process Stripe webhook', [
                'error' => $e->getMessage(),
                'payload_preview' => substr($payload, 0, 500),
            ]);

            return [
                'success' => false,
                'event_type' => null,
                'message' => 'Failed to process webhook: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment_intent.succeeded event.
     *
     * @param StripeEvent $event
     * @return array{success: bool, event_type: string, message: string}
     */
    protected function handlePaymentSucceeded(StripeEvent $event): array
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $paymentIntentId = $paymentIntent->id;
        $amount = $paymentIntent->amount / 100; // Convert from cents
        $currency = strtoupper($paymentIntent->currency);
        $metadata = $paymentIntent->metadata ? $paymentIntent->metadata->toArray() : [];

        Log::info('Processing successful payment', [
            'payment_intent_id' => $paymentIntentId,
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
        ]);

        // Get user ID from metadata
        $userId = $metadata['user_id'] ?? null;

        if (!$userId) {
            Log::error('Payment succeeded but no user_id in metadata', [
                'payment_intent_id' => $paymentIntentId,
                'metadata' => $metadata,
            ]);

            return [
                'success' => false,
                'event_type' => $event->type,
                'message' => 'No user_id in payment metadata',
            ];
        }

        // Find user
        $user = User::find($userId);

        if (!$user) {
            Log::error('Payment succeeded but user not found', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'event_type' => $event->type,
                'message' => 'User not found',
            ];
        }

        // Check for idempotency - already processed?
        $existingTransaction = CreditTransaction::where('payment_provider', 'stripe')
            ->where('payment_id', $paymentIntentId)
            ->first();

        if ($existingTransaction) {
            Log::info('Payment already processed (idempotent)', [
                'payment_intent_id' => $paymentIntentId,
                'transaction_id' => $existingTransaction->id,
            ]);

            return [
                'success' => true,
                'event_type' => $event->type,
                'message' => 'Payment already processed',
            ];
        }

        // Add credits to user balance
        try {
            DB::transaction(function () use ($user, $amount, $paymentIntentId, $metadata) {
                $this->creditService->addCredits($user, $amount, CreditTransaction::TYPE_PURCHASE, [
                    'payment_provider' => 'stripe',
                    'payment_id' => $paymentIntentId,
                    'package_id' => $metadata['package_id'] ?? null,
                    'currency' => $metadata['currency'] ?? 'USD',
                    'metadata' => $metadata,
                ]);
            });

            Log::info('Credits added successfully', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'event_type' => $event->type,
                'message' => 'Credits added successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to add credits for payment', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'event_type' => $event->type,
                'message' => 'Failed to add credits: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment_intent.payment_failed event.
     *
     * @param StripeEvent $event
     * @return array{success: bool, event_type: string, message: string}
     */
    protected function handlePaymentFailed(StripeEvent $event): array
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $paymentIntentId = $paymentIntent->id;
        $lastPaymentError = $paymentIntent->last_payment_error;
        $metadata = $paymentIntent->metadata->toArray();

        $errorMessage = $lastPaymentError->message ?? 'Unknown error';
        $errorCode = $lastPaymentError->code ?? null;
        $userId = $metadata['user_id'] ?? null;

        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);

        // Create a failed transaction record for audit purposes
        if ($userId) {
            try {
                CreditTransaction::create([
                    'user_id' => $userId,
                    'amount' => 0, // No amount for failed payments
                    'balance_after' => 0,
                    'type' => CreditTransaction::TYPE_PURCHASE,
                    'status' => CreditTransaction::STATUS_FAILED,
                    'description' => "Payment failed: {$errorMessage}",
                    'payment_provider' => 'stripe',
                    'payment_id' => $paymentIntentId,
                    'metadata' => array_merge($metadata, [
                        'error_message' => $errorMessage,
                        'error_code' => $errorCode,
                    ]),
                    'created_at' => now(),
                ]);
            } catch (Exception $e) {
                Log::error('Failed to record failed payment transaction', [
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => true,
            'event_type' => $event->type,
            'message' => 'Payment failure recorded',
        ];
    }

    /**
     * Handle charge.refunded event.
     *
     * @param StripeEvent $event
     * @return array{success: bool, event_type: string, message: string}
     */
    protected function handleChargeRefunded(StripeEvent $event): array
    {
        /** @var Charge $charge */
        $charge = $event->data->object;

        $chargeId = $charge->id;
        $refundedAmount = $charge->amount_refunded / 100; // Convert from cents
        $paymentIntentId = $charge->payment_intent;
        $metadata = $charge->metadata->toArray();

        Log::info('Processing refund', [
            'charge_id' => $chargeId,
            'payment_intent_id' => $paymentIntentId,
            'refunded_amount' => $refundedAmount,
        ]);

        // Find the original transaction
        $originalTransaction = null;
        if ($paymentIntentId) {
            $originalTransaction = CreditTransaction::where('payment_provider', 'stripe')
                ->where('payment_id', $paymentIntentId)
                ->first();
        }

        if (!$originalTransaction) {
            Log::warning('Refund received but no original transaction found', [
                'charge_id' => $chargeId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            // Still return success - we don't want Stripe to retry
            return [
                'success' => true,
                'event_type' => $event->type,
                'message' => 'No original transaction found, acknowledged',
            ];
        }

        $userId = $originalTransaction->user_id;
        $user = User::find($userId);

        if (!$user) {
            Log::error('Refund received but user not found', [
                'charge_id' => $chargeId,
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'event_type' => $event->type,
                'message' => 'User not found',
            ];
        }

        // Check if refund was already processed (idempotency)
        $existingRefund = CreditTransaction::where('payment_provider', 'stripe')
            ->where('payment_id', $chargeId)
            ->where('type', CreditTransaction::TYPE_REFUND)
            ->first();

        if ($existingRefund) {
            Log::info('Refund already processed (idempotent)', [
                'charge_id' => $chargeId,
                'transaction_id' => $existingRefund->id,
            ]);

            return [
                'success' => true,
                'event_type' => $event->type,
                'message' => 'Refund already processed',
            ];
        }

        // Process the refund (deduct credits)
        try {
            DB::transaction(function () use ($user, $refundedAmount, $chargeId, $originalTransaction) {
                $this->creditService->refundCredits(
                    $user,
                    $refundedAmount,
                    $originalTransaction->id,
                    "Refund for payment {$originalTransaction->payment_id}"
                );

                // Update the refund transaction with charge ID
                CreditTransaction::where('payment_provider', 'stripe')
                    ->where('type', CreditTransaction::TYPE_REFUND)
                    ->where('user_id', $user->id)
                    ->where('reference_id', $originalTransaction->id)
                    ->latest('created_at')
                    ->first()
                    ?->update([
                        'payment_provider' => 'stripe',
                        'payment_id' => $chargeId,
                    ]);
            });

            Log::info('Refund processed successfully', [
                'charge_id' => $chargeId,
                'user_id' => $userId,
                'refunded_amount' => $refundedAmount,
            ]);

            return [
                'success' => true,
                'event_type' => $event->type,
                'message' => 'Refund processed successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to process refund', [
                'charge_id' => $chargeId,
                'user_id' => $userId,
                'refunded_amount' => $refundedAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'event_type' => $event->type,
                'message' => 'Failed to process refund: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if an event type is supported.
     *
     * @param string $eventType
     * @return bool
     */
    public function isSupportedEvent(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS);
    }

    /**
     * Get list of supported event types.
     *
     * @return array<string>
     */
    public function getSupportedEvents(): array
    {
        return self::SUPPORTED_EVENTS;
    }
}
