<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\DTOs\PaymentResult;
use App\Services\Billing\Exceptions\StripePaymentException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use App\Services\Billing\Exceptions\PaymentValidationException;
use App\Services\Billing\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

/**
 * Stripe Gateway Service
 *
 * Handles all Stripe payment operations including:
 * - Payment intent creation
 * - Payment capture
 * - Refund processing
 * - Webhook handling
 * - Customer management
 *
 * SECURITY CRITICAL: All webhook operations verify signatures.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class StripeGatewayService implements PaymentGatewayInterface
{
    /**
     * Minimum purchase amount in dollars.
     */
    public const MINIMUM_AMOUNT = 5.00;

    /**
     * Maximum purchase amount in dollars.
     */
    public const MAXIMUM_AMOUNT = 999999.99;

    /**
     * Zero-decimal currencies (no cents).
     */
    public const ZERO_DECIMAL_CURRENCIES = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    /**
     * Stripe client instance.
     */
    protected StripeClient $stripe;

    /**
     * Webhook secret for signature verification.
     */
    protected string $webhookSecret;

    /**
     * Create a new StripeGatewayService instance.
     *
     * @param StripeClient|null $stripe Optional Stripe client for testing
     * @throws \RuntimeException If Stripe configuration is missing
     */
    public function __construct(?StripeClient $stripe = null)
    {
        $secret = Config::get('services.stripe.secret');
        $this->webhookSecret = Config::get('services.stripe.webhook_secret');

        if (empty($secret)) {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        $this->stripe = $stripe ?? new StripeClient([
            'api_key' => $secret,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentIntent(
        User $user,
        float $amount,
        string $currency = 'USD',
        array $metadata = []
    ): array {
        // Validate minimum amount
        if ($amount < self::MINIMUM_AMOUNT) {
            throw PaymentValidationException::minimumAmount(self::MINIMUM_AMOUNT, $amount);
        }

        // Validate maximum amount
        if ($amount > self::MAXIMUM_AMOUNT) {
            throw PaymentValidationException::maximumAmount(self::MAXIMUM_AMOUNT, $amount);
        }

        try {
            // Get or create customer
            $customerId = $this->getCustomer($user);
            if (!$customerId) {
                $customerId = $this->createCustomer($user);
            }

            // Convert amount to cents (handle zero-decimal currencies)
            $amountInCents = $this->convertToStripeAmount($amount, $currency);

            // Generate idempotency key
            $idempotencyKey = 'pi_' . Str::uuid()->toString();

            // Build payment intent parameters
            $params = [
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'customer' => $customerId,
                'metadata' => array_merge([
                    'user_id' => (string) $user->id,
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            // Create payment intent with idempotency key
            $paymentIntent = $this->stripe->paymentIntents->create(
                $params,
                ['idempotency_key' => $idempotencyKey]
            );

            Log::info('Stripe payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);

            // Handle timeout/network errors
            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'connection')) {
                throw new PaymentTimeoutException('Payment service unavailable. Please try again.');
            }

            throw new StripePaymentException(
                $e->getMessage(),
                $e->getStripeCode() ?? 'unknown',
                ['user_id' => $user->id]
            );
        } catch (\Exception $e) {
            Log::error('Payment intent creation error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Handle network timeout
            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Connection')) {
                throw new PaymentTimeoutException('Payment service unavailable. Please try again.');
            }

            throw new StripePaymentException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function capturePayment(string $paymentIntentId, array $options = []): bool
    {
        try {
            $params = [];
            if (isset($options['amount_to_capture'])) {
                $params['amount_to_capture'] = $options['amount_to_capture'];
            }

            $paymentIntent = $this->stripe->paymentIntents->capture(
                $paymentIntentId,
                $params
            );

            Log::info('Stripe payment captured', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntent->status,
            ]);

            return $paymentIntent->status === 'succeeded';
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment capture failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Payment capture error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $params = [
                'payment_intent' => $paymentIntentId,
            ];

            // Handle partial refund
            if ($amount !== null) {
                // Get currency from payment intent to convert correctly
                $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
                $currency = strtoupper($paymentIntent->currency);
                $params['amount'] = $this->convertToStripeAmount($amount, $currency);
            }

            $idempotencyKey = 're_' . Str::uuid()->toString();

            $refund = $this->stripe->refunds->create(
                $params,
                ['idempotency_key' => $idempotencyKey]
            );

            Log::info('Stripe refund processed', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $refund->amount,
            ]);

            return [
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
                'status' => $refund->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw new StripePaymentException(
                $e->getMessage(),
                $e->getStripeCode() ?? 'refund_failed'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(Request $request): PaymentResult
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            throw InvalidWebhookSignatureException::missingSignature();
        }

        try {
            // Verify webhook signature - SECURITY CRITICAL
            $event = $this->stripe->webhooks->constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Invalid webhook signature', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        } catch (\Exception $e) {
            Log::error('Webhook parsing failed', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidWebhookSignatureException('Invalid webhook payload.');
        }

        Log::info('Stripe webhook received', [
            'event_id' => $event->id,
            'event_type' => $event->type,
        ]);

        // Handle different event types
        return match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
            'charge.refunded' => $this->handleChargeRefunded($event),
            default => $this->handleUnknownEvent($event),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomer(User $user): ?string
    {
        // Check if user has a stored Stripe customer ID
        // This would typically be stored in a payment_methods or user metadata table
        // For now, return null to trigger customer creation
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomer(User $user): string
    {
        try {
            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ]);

            Log::info('Stripe customer created', [
                'customer_id' => $customer->id,
                'user_id' => $user->id,
            ]);

            // Store customer ID for future use
            // This would typically be saved to a payment_methods table

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new StripePaymentException(
                'Failed to create payment customer.',
                $e->getStripeCode() ?? 'customer_creation_failed'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethods(User $user): array
    {
        $customerId = $this->getCustomer($user);

        if (!$customerId) {
            return [];
        }

        try {
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            return array_map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'type' => $pm->type,
                    'card' => $pm->card ? [
                        'brand' => $pm->card->brand,
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year,
                    ] : null,
                ];
            }, $paymentMethods->data);
        } catch (ApiErrorException $e) {
            Log::error('Failed to get payment methods', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        try {
            $this->stripe->paymentMethods->detach($paymentMethodId);

            Log::info('Payment method deleted', [
                'payment_method_id' => $paymentMethodId,
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to delete payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodOwner(string $paymentMethodId): ?int
    {
        // This would query the payment_methods table to find the owner
        // For now, return null
        return null;
    }

    /**
     * Handle payment_intent.succeeded event.
     */
    protected function handlePaymentSucceeded($event): PaymentResult
    {
        $paymentIntent = $event->data->object;
        $userId = $paymentIntent->metadata->user_id ?? null;

        // Check for duplicate (idempotency)
        $existingTransaction = CreditTransaction::where('payment_provider', 'stripe')
            ->where('payment_id', $paymentIntent->id)
            ->first();

        if ($existingTransaction) {
            Log::info('Duplicate webhook event detected', [
                'event_id' => $event->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return PaymentResult::duplicate($paymentIntent->id, (int) $userId);
        }

        return PaymentResult::success(
            paymentId: $paymentIntent->id,
            amount: $this->convertFromStripeAmount($paymentIntent->amount, $paymentIntent->currency),
            currency: strtoupper($paymentIntent->currency),
            userId: $userId ? (int) $userId : null,
            metadata: (array) $paymentIntent->metadata
        );
    }

    /**
     * Handle payment_intent.payment_failed event.
     */
    protected function handlePaymentFailed($event): PaymentResult
    {
        $paymentIntent = $event->data->object;
        $userId = $paymentIntent->metadata->user_id ?? null;

        $errorCode = $paymentIntent->last_payment_error->code ?? 'unknown';
        $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';

        return PaymentResult::failed(
            paymentId: $paymentIntent->id,
            amount: $this->convertFromStripeAmount($paymentIntent->amount, $paymentIntent->currency),
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            userId: $userId ? (int) $userId : null
        );
    }

    /**
     * Handle charge.refunded event.
     */
    protected function handleChargeRefunded($event): PaymentResult
    {
        $charge = $event->data->object;
        $userId = $charge->metadata->user_id ?? null;
        $refundId = $charge->refunds->data[0]->id ?? null;
        $refundAmount = $charge->refunds->data[0]->amount ?? $charge->amount_refunded;

        return PaymentResult::refund(
            refundId: $refundId ?? $charge->id,
            amount: $this->convertFromStripeAmount($refundAmount, $charge->currency),
            userId: $userId ? (int) $userId : null
        );
    }

    /**
     * Handle unknown event type.
     */
    protected function handleUnknownEvent($event): PaymentResult
    {
        Log::info('Unhandled webhook event type', [
            'event_id' => $event->id,
            'event_type' => $event->type,
        ]);

        return PaymentResult::ignored();
    }

    /**
     * Convert amount to Stripe's smallest currency unit.
     */
    protected function convertToStripeAmount(float $amount, string $currency): int
    {
        $currency = strtoupper($currency);

        // Zero-decimal currencies don't need conversion
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES)) {
            return (int) round($amount);
        }

        // Convert to cents
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from Stripe's smallest currency unit.
     */
    protected function convertFromStripeAmount(int $amount, string $currency): float
    {
        $currency = strtoupper($currency);

        // Zero-decimal currencies don't need conversion
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES)) {
            return (float) $amount;
        }

        // Convert from cents
        return $amount / 100;
    }
}
