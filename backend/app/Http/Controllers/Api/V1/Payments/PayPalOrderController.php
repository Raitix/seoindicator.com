<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\CreatePayPalOrderRequest;
use App\Models\CreditTransaction;
use App\Models\CreditPackage;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Order Controller.
 *
 * Handles PayPal order creation for credit purchases.
 *
 * @see TASK-26-13
 * @see FEAT-026
 */
class PayPalOrderController extends Controller
{
    /**
     * Create a new PayPalOrderController instance.
     */
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}

    /**
     * Create a PayPal order for credit purchase.
     *
     * @param CreatePayPalOrderRequest $request
     * @return JsonResponse
     */
    public function store(CreatePayPalOrderRequest $request): JsonResponse
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

            // Create PayPal order via gateway
            $orderData = $this->paymentGateway->createPaymentIntent(
                $user,
                $amount,
                'USD',
                [
                    'package_id' => $package->id,
                    'return_url' => $request->return_url,
                    'cancel_url' => $request->cancel_url,
                ]
            );

            // Create pending transaction record
            $transaction = $this->createPendingTransaction(
                $user->id,
                $orderData['order_id'],
                $amount,
                $credits,
                $package->id,
                $package->name
            );

            Log::info('PayPal order created', [
                'user_id' => $user->id,
                'order_id' => $orderData['order_id'],
                'package_id' => $package->id,
                'amount' => $amount,
                'credits' => $credits,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $orderData['order_id'],
                    'approval_url' => $orderData['approval_url'],
                    'amount' => $amount,
                    'credits' => $credits,
                    'package_name' => $package->name,
                ],
            ]);
        } catch (\App\Services\Billing\Exceptions\PaymentTimeoutException $e) {
            Log::warning('PayPal order timeout', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'service_unavailable',
                'message' => $e->getUserMessage(),
            ], 503);
        } catch (\App\Services\Billing\Exceptions\PaymentValidationException $e) {
            Log::warning('PayPal order validation error', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        } catch (\App\Services\Billing\Exceptions\PayPalPaymentException $e) {
            Log::error('PayPal order creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYPAL_ERROR',
                    'message' => 'Failed to create PayPal order. Please try again.',
                ],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating PayPal order', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An unexpected error occurred. Please try again.',
                ],
            ], 500);
        }
    }

    /**
     * Create a pending transaction record.
     *
     * @param int $userId
     * @param string $orderId PayPal order ID
     * @param float $amount Payment amount
     * @param float $credits Credits to be added
     * @param int $packageId
     * @param string $packageName
     * @return CreditTransaction
     */
    protected function createPendingTransaction(
        int $userId,
        string $orderId,
        float $amount,
        float $credits,
        int $packageId,
        string $packageName
    ): CreditTransaction {
        return CreditTransaction::create([
            'user_id' => $userId,
            'amount' => $credits, // Credits to be added
            'balance_after' => 0, // Will be updated on capture
            'type' => CreditTransaction::TYPE_PURCHASE,
            'status' => CreditTransaction::STATUS_PENDING,
            'description' => "Credit purchase: {$packageName}",
            'payment_provider' => 'paypal',
            'payment_id' => $orderId,
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
