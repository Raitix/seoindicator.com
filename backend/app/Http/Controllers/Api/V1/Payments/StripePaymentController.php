<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\CreateStripeIntentRequest;
use App\Models\CreditTransaction;
use App\Models\CreditPackage;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stripe Payment Controller.
 *
 * Handles Stripe payment intent creation for credit purchases.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class StripePaymentController extends Controller
{
    /**
     * Create a new StripePaymentController instance.
     */
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}

    /**
     * Create a Stripe payment intent for credit purchase.
     *
     * @param CreateStripeIntentRequest $request
     * @return JsonResponse
     */
    public function createIntent(CreateStripeIntentRequest $request): JsonResponse
    {
        $user = $request->user();
        $package = $request->getPackage();

        // Package should always exist due to validation, but be defensive
        if (!$package) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PACKAGE_NOT_FOUND',
                    'message' => 'The selected package could not be found.',
                ],
            ], 422);
        }

        // Check if package is active (extra safety check)
        if (!$package->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PACKAGE_INACTIVE',
                    'message' => 'The selected package is not available for purchase.',
                ],
            ], 400);
        }

        try {
            // Calculate credits including bonus
            $credits = $package->getEffectiveCreditsAttribute();
            $amount = (float) $package->price_usd;

            // Generate idempotency key for retry safety
            $idempotencyKey = $request->header('Idempotency-Key') ?? 'stripe_' . Str::uuid()->toString();

            // Create Stripe payment intent via gateway
            $intentData = $this->paymentGateway->createPaymentIntent(
                $user,
                $amount,
                'USD',
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'credits' => $credits,
                ]
            );

            // Create pending transaction record
            $this->createPendingTransaction(
                $user->id,
                $intentData['payment_intent_id'],
                $amount,
                $credits,
                $package->id,
                $package->name
            );

            Log::info('Stripe payment intent created', [
                'user_id' => $user->id,
                'payment_intent_id' => $intentData['payment_intent_id'],
                'package_id' => $package->id,
                'amount' => $amount,
                'credits' => $credits,
            ]);

            return response()->json([
                'client_secret' => $intentData['client_secret'],
                'payment_intent_id' => $intentData['payment_intent_id'],
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => $intentData['currency'] ?? 'usd',
            ]);
        } catch (\App\Services\Billing\Exceptions\PaymentValidationException $e) {
            Log::warning('Stripe payment intent validation error', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\App\Services\Billing\Exceptions\StripePaymentException $e) {
            Log::error('Stripe payment intent creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);

            return response()->json([
                'error' => 'payment_failed',
                'code' => $e->getStripeCode(),
                'message' => $e->getMessage(),
            ], 400);
        } catch (\App\Services\Billing\Exceptions\PaymentTimeoutException $e) {
            Log::error('Stripe payment timeout', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'service_unavailable',
                'message' => $e->getMessage(),
            ], 503);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating Stripe payment intent', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    /**
     * Create a pending transaction record.
     *
     * @param int $userId
     * @param string $paymentIntentId Stripe payment intent ID
     * @param float $amount Payment amount
     * @param float $credits Credits to be added
     * @param int $packageId
     * @param string $packageName
     * @return CreditTransaction
     */
    protected function createPendingTransaction(
        int $userId,
        string $paymentIntentId,
        float $amount,
        float $credits,
        int $packageId,
        string $packageName
    ): CreditTransaction {
        return CreditTransaction::create([
            'user_id' => $userId,
            'amount' => $credits, // Credits to be added
            'balance_after' => 0, // Will be updated on webhook
            'type' => CreditTransaction::TYPE_PURCHASE,
            'status' => CreditTransaction::STATUS_PENDING,
            'description' => "Credit purchase: {$packageName}",
            'payment_provider' => 'stripe',
            'payment_id' => $paymentIntentId,
            'reference_type' => CreditPackage::class,
            'reference_id' => $packageId,
            'metadata' => [
                'package_name' => $packageName,
                'price_usd' => $amount,
                'credits' => $credits,
            ],
        ]);
    }
}
