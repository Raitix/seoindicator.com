<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Repositories\CreditRepository;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Capture Controller.
 *
 * Handles PayPal Smart Button order capture for credit purchases.
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
        protected CreditService $creditService,
        protected CreditRepository $creditRepository
    ) {}

    /**
     * Capture an approved PayPal order and credit the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $orderId = $request->input('order_id');

        Log::info('PayPal capture initiated', [
            'user_id' => $user->id,
            'order_id' => $orderId,
        ]);

        try {
            return DB::transaction(function () use ($user, $orderId) {
                // Find pending transaction with this order_id
                $transaction = CreditTransaction::where('payment_provider', 'paypal')
                    ->where('payment_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                // Order not found
                if (!$transaction) {
                    Log::warning('PayPal capture: Order not found', [
                        'user_id' => $user->id,
                        'order_id' => $orderId,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'ORDER_NOT_FOUND',
                            'message' => 'Order not found.',
                        ],
                    ], 404);
                }

                // Verify ownership (IDOR prevention)
                if ($transaction->user_id !== $user->id) {
                    Log::warning('PayPal capture: Unauthorized access attempt', [
                        'user_id' => $user->id,
                        'transaction_user_id' => $transaction->user_id,
                        'order_id' => $orderId,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'UNAUTHORIZED',
                            'message' => 'Unauthorized.',
                        ],
                    ], 401);
                }

                // Check order state
                $orderStatus = $transaction->metadata['status'] ?? 'unknown';

                // Already captured
                if ($orderStatus === 'completed') {
                    Log::info('PayPal capture: Order already captured', [
                        'user_id' => $user->id,
                        'order_id' => $orderId,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'ORDER_ALREADY_CAPTURED',
                            'message' => 'Order already captured.',
                        ],
                    ], 400);
                }

                // Order not approved yet
                if ($orderStatus !== 'approved') {
                    Log::warning('PayPal capture: Order not approved', [
                        'user_id' => $user->id,
                        'order_id' => $orderId,
                        'status' => $orderStatus,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'ORDER_NOT_APPROVED',
                            'message' => "Order not approved. Current status: {$orderStatus}",
                        ],
                    ], 400);
                }

                // Check if order expired (3 hour window)
                $createdAt = $transaction->created_at;
                if ($createdAt && $createdAt->diffInHours(now()) >= 3) {
                    Log::warning('PayPal capture: Order expired', [
                        'user_id' => $user->id,
                        'order_id' => $orderId,
                        'created_at' => $createdAt->toIso8601String(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'ORDER_EXPIRED',
                            'message' => 'Order expired.',
                        ],
                    ], 400);
                }

                // Capture payment via PayPal gateway
                $captureSuccess = $this->paymentGateway->capturePayment($orderId);

                if (!$captureSuccess) {
                    Log::error('PayPal capture: Gateway capture failed', [
                        'user_id' => $user->id,
                        'order_id' => $orderId,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'CAPTURE_FAILED',
                            'message' => 'Payment capture failed.',
                        ],
                    ], 500);
                }

                // Get credits amount from transaction
                $credits = (float) $transaction->amount;

                // Get or create credit record for user
                $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$credit) {
                    $credit = Credit::create([
                        'user_id' => $user->id,
                        'balance' => 0,
                        'total_purchased' => 0,
                        'total_spent' => 0,
                    ]);
                }

                // Update balance
                $newBalance = (float) $credit->balance + $credits;
                $credit->balance = $newBalance;
                $credit->total_purchased = (float) $credit->total_purchased + $credits;
                $credit->save();

                // Update transaction metadata
                $transaction->update([
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'status' => 'completed',
                        'captured_at' => now()->toIso8601String(),
                    ]),
                    'balance_after' => $newBalance,
                ]);

                Log::info('PayPal capture successful', [
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'credits' => $credits,
                    'new_balance' => $newBalance,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => (float) ($transaction->metadata['price_usd'] ?? $credits),
                        'credits' => $credits,
                        'new_balance' => $newBalance,
                    ],
                ]);
            }, 3); // 3 retries for deadlock
        } catch (\Exception $e) {
            Log::error('PayPal capture error', [
                'user_id' => $user->id,
                'order_id' => $orderId,
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
}
