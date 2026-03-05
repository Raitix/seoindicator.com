<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\CapturePayPalOrderRequest;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Capture Controller.
 *
 * Handles PayPal order capture for credit purchases.
 * Called by frontend after user approves payment in PayPal popup.
 *
 * @see TASK-26-15
 * @see FEAT-026
 */
class PayPalCaptureController extends Controller
{
    /**
     * Create a new PayPalCaptureController instance.
     */
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected CreditService $creditService
    ) {}

    /**
     * Capture an approved PayPal order.
     *
     * @param CapturePayPalOrderRequest $request
     * @return JsonResponse
     */
    public function store(CapturePayPalOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $orderId = $request->validated('order_id');

        // Find the pending transaction
        $transaction = CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $orderId)
            ->first();

        // Transaction not found
        if (!$transaction) {
            Log::warning('PayPal capture: Order not found', [
                'order_id' => $orderId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Verify ownership (IDOR prevention)
        if ($transaction->user_id !== $user->id) {
            Log::warning('PayPal capture: Unauthorized order access attempt', [
                'order_id' => $orderId,
                'transaction_user_id' => $transaction->user_id,
                'request_user_id' => $user->id,
            ]);

            // Return 401 to not leak order existence
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check transaction status
        if ($transaction->status !== CreditTransaction::STATUS_PENDING) {
            $statusMessage = match ($transaction->status) {
                CreditTransaction::STATUS_COMPLETED => 'Order already captured',
                CreditTransaction::STATUS_CANCELLED => 'Order cancelled',
                CreditTransaction::STATUS_FAILED => 'Order failed',
                default => "Order cannot be captured (status: {$transaction->status})",
            };

            Log::info('PayPal capture: Invalid transaction status', [
                'order_id' => $orderId,
                'status' => $transaction->status,
            ]);

            return response()->json([
                'success' => false,
                'message' => $statusMessage,
            ], 400);
        }

        // Check for order state in metadata
        $orderState = $transaction->metadata['status'] ?? 'unknown';
        if (!in_array($orderState, ['approved', 'pending', 'unknown'])) {
            $statusMessage = match ($orderState) {
                'created' => 'Order not approved',
                'completed' => 'Order already captured',
                'cancelled' => 'Order cancelled',
                'expired' => 'Order expired',
                'denied' => 'Order denied',
                default => "Invalid order state: {$orderState}",
            };

            Log::info('PayPal capture: Invalid order state', [
                'order_id' => $orderId,
                'order_state' => $orderState,
            ]);

            return response()->json([
                'success' => false,
                'message' => $statusMessage,
            ], 400);
        }

        // Check for order expiration (3 hour window)
        $createdAt = $transaction->created_at;
        if ($createdAt && $createdAt->diffInHours(now()) > 3) {
            Log::info('PayPal capture: Order expired', [
                'order_id' => $orderId,
                'created_at' => $createdAt,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order expired',
            ], 400);
        }

        try {
            // Use database transaction for atomicity
            $result = DB::transaction(function () use ($transaction, $user, $orderId) {
                // Lock the transaction for update (prevent concurrent captures)
                $lockedTransaction = CreditTransaction::where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                // Double-check status after lock
                if ($lockedTransaction->status !== CreditTransaction::STATUS_PENDING) {
                    throw new \RuntimeException('Order already captured');
                }

                // Capture via PayPal gateway
                $captureSuccess = $this->paymentGateway->capturePayment($orderId);

                if (!$captureSuccess) {
                    throw new \RuntimeException('PayPal capture failed');
                }

                // Get credit amount from transaction
                $credits = (float) $lockedTransaction->amount;

                // Add credits to user
                $this->creditService->addCredits(
                    $user,
                    $credits,
                    CreditTransaction::TYPE_PURCHASE,
                    [
                        'payment_provider' => 'paypal',
                        'payment_id' => $orderId,
                        'description' => $lockedTransaction->description,
                        'reference_type' => $lockedTransaction->reference_type,
                        'reference_id' => $lockedTransaction->reference_id,
                    ]
                );

                // Update transaction status
                $lockedTransaction->status = CreditTransaction::STATUS_COMPLETED;
                $lockedTransaction->metadata = array_merge(
                    $lockedTransaction->metadata ?? [],
                    ['status' => 'completed', 'captured_at' => now()->toIso8601String()]
                );
                $lockedTransaction->save();

                // Get new balance
                $newBalance = $this->creditService->getBalance($user);

                // Get the price from metadata
                $amount = (float) ($lockedTransaction->metadata['price_usd'] ?? $lockedTransaction->amount);

                return [
                    'transaction_id' => $lockedTransaction->id,
                    'amount' => $amount,
                    'credits' => $credits,
                    'new_balance' => $newBalance,
                ];
            }, 3); // 3 retries for deadlock

            Log::info('PayPal capture successful', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'credits' => $result['credits'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();

            Log::error('PayPal capture failed', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $errorMessage,
            ]);

            // Check for specific error types
            if (str_contains($errorMessage, 'already captured')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already captured',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
            ], 500);
        } catch (\Exception $e) {
            Log::error('PayPal capture unexpected error', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
