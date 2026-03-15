<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Credit Purchase Controller.
 *
 * Handles credit purchase initiation for both Stripe and PayPal.
 *
 * @see TASK-26-26
 * @see FEAT-026
 */
class CreditPurchaseController extends Controller
{
    /**
     * Create a new CreditPurchaseController instance.
     */
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}

    /**
     * Initiate a credit purchase.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:credit_packages,id'],
            'payment_method' => ['required', 'string', 'in:stripe,paypal'],
        ]);

        $user = $request->user();
        $package = CreditPackage::active()->find($validated['package_id']);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'The selected package is not available.',
            ], 422);
        }

        $paymentMethod = $validated['payment_method'];

        try {
            $credits = $package->getEffectiveCreditsAttribute();
            $amount = (float) $package->price_usd;

            if ($paymentMethod === 'stripe') {
                return $this->handleStripePurchase($user, $package, $amount, $credits);
            }

            return $this->handlePayPalPurchase($user, $package, $amount, $credits);
        } catch (\App\Services\Billing\Exceptions\PaymentTimeoutException $e) {
            Log::warning('Credit purchase timeout', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'service_unavailable',
                'message' => $e->getUserMessage(),
            ], 503);
        } catch (\Exception $e) {
            Log::error('Credit purchase failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'purchase_failed',
                'message' => 'Unable to process purchase. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle Stripe-based purchase.
     *
     * @param mixed $user
     * @param CreditPackage $package
     * @param float $amount
     * @param float $credits
     * @return JsonResponse
     */
    protected function handleStripePurchase($user, CreditPackage $package, float $amount, float $credits): JsonResponse
    {
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

        // Create pending transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $credits,
            'balance_after' => 0,
            'type' => CreditTransaction::TYPE_PURCHASE,
            'status' => CreditTransaction::STATUS_PENDING,
            'description' => "Credit purchase: {$package->name}",
            'payment_provider' => 'stripe',
            'payment_id' => $intentData['payment_intent_id'],
            'reference_type' => CreditPackage::class,
            'reference_id' => $package->id,
            'metadata' => [
                'package_name' => $package->name,
                'price_usd' => $amount,
                'credits' => $credits,
            ],
        ]);

        return response()->json([
            'success' => true,
            'payment_intent_id' => $intentData['payment_intent_id'],
            'client_secret' => $intentData['client_secret'],
            'amount' => (int) ($amount * 100),
            'currency' => $intentData['currency'] ?? 'usd',
        ]);
    }

    /**
     * Handle PayPal-based purchase.
     *
     * @param mixed $user
     * @param CreditPackage $package
     * @param float $amount
     * @param float $credits
     * @return JsonResponse
     */
    protected function handlePayPalPurchase($user, CreditPackage $package, float $amount, float $credits): JsonResponse
    {
        $orderData = $this->paymentGateway->createPaymentIntent(
            $user,
            $amount,
            'USD',
            [
                'package_id' => $package->id,
            ]
        );

        // Create pending transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $credits,
            'balance_after' => 0,
            'type' => CreditTransaction::TYPE_PURCHASE,
            'status' => CreditTransaction::STATUS_PENDING,
            'description' => "Credit purchase: {$package->name}",
            'payment_provider' => 'paypal',
            'payment_id' => $orderData['order_id'],
            'reference_type' => CreditPackage::class,
            'reference_id' => $package->id,
            'metadata' => [
                'package_name' => $package->name,
                'price_usd' => $amount,
                'credits' => $credits,
            ],
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $orderData['order_id'],
            'approval_url' => $orderData['approval_url'] ?? null,
            'amount' => (int) ($amount * 100),
            'currency' => 'usd',
        ]);
    }
}
