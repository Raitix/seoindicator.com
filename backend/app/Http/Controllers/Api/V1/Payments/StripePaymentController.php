<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\CreateStripeIntentRequest;
use App\Models\CreditTransaction;
use App\Models\CreditPackage;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Controller.
 *
 * Handles Stripe payment intent creation for credit purchases.
 *
 * @see TASK-26-9
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
        $returnUrl = $request->validated('return_url');

        // Package should always exist due to validation, but be defensive
        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid package selected',
                'errors' => [
                    'package_id' => ['Invalid package selected'],
                ],
            ], 422);
        }

        // Check if package is active (extra safety check)
        if (!$package->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid package selected',
            ], 400);
        }

        // Calculate credits including bonus
        $credits = $package->getEffectiveCreditsAttribute();
        $amount = (float) $package->price_usd;

        // Check for existing pending transaction (idempotency)
        // Check both reference_id and metadata.package_id for backwards compatibility
        $existingTransaction = CreditTransaction::where('user_id', $user->id)
            ->where('payment_provider', 'stripe')
            ->where('status', CreditTransaction::STATUS_PENDING)
            ->where(function ($query) use ($package) {
                $query->where(function ($q) use ($package) {
                    $q->where('reference_type', CreditPackage::class)
                      ->where('reference_id', $package->id);
                })->orWhere(function ($q) use ($package) {
                    $q->whereJsonContains('metadata->package_id', $package->id);
                });
            })
            ->first();

        if ($existingTransaction && $existingTransaction->payment_id) {
            // Return existing intent instead of creating a new one
            Log::info('Returning existing Stripe payment intent', [
                'user_id' => $user->id,
                'payment_intent_id' => $existingTransaction->payment_id,
                'package_id' => $package->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $existingTransaction->payment_id . '_secret_existing',
                    'payment_intent_id' => $existingTransaction->payment_id,
                    'amount' => number_format((float) ($existingTransaction->metadata['price_usd'] ?? $amount), 2, '.', ''),
                    'credits' => number_format((float) ($existingTransaction->metadata['credits'] ?? $credits), 2, '.', ''),
                    'package_name' => $existingTransaction->metadata['package_name'] ?? $package->name,
                ],
            ]);
        }

        try {
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
                $package->name,
                $returnUrl
            );

            Log::info('Stripe payment intent created', [
                'user_id' => $user->id,
                'payment_intent_id' => $intentData['payment_intent_id'],
                'package_id' => $package->id,
                'amount' => $amount,
                'credits' => $credits,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $intentData['client_secret'],
                    'payment_intent_id' => $intentData['payment_intent_id'],
                    'amount' => number_format($amount, 2, '.', ''),
                    'credits' => number_format($credits, 2, '.', ''),
                    'package_name' => $package->name,
                ],
            ]);
        } catch (\App\Services\Billing\Exceptions\PaymentValidationException $e) {
            Log::warning('Stripe payment intent validation error', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment could not be initiated',
            ], 400);
        } catch (\App\Services\Billing\Exceptions\StripePaymentException $e) {
            Log::error('Stripe payment intent creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment could not be initiated',
            ], 500);
        } catch (\App\Services\Billing\Exceptions\PaymentTimeoutException $e) {
            Log::error('Stripe payment timeout', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment could not be initiated',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating Stripe payment intent', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment could not be initiated',
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
     * @param string|null $returnUrl
     * @return CreditTransaction
     */
    protected function createPendingTransaction(
        int $userId,
        string $paymentIntentId,
        float $amount,
        float $credits,
        int $packageId,
        string $packageName,
        ?string $returnUrl = null
    ): CreditTransaction {
        $metadata = [
            'package_name' => $packageName,
            'price_usd' => $amount,
            'credits' => $credits,
            'package_id' => $packageId,
        ];

        if ($returnUrl) {
            $metadata['return_url'] = $returnUrl;
        }

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
            'metadata' => $metadata,
        ]);
    }
}
