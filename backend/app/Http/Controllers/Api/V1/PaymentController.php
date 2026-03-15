<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\Gateway\StripeGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Controller for TASK-26-11.
 * 
 * Handles payment method management API endpoints.
 */
class PaymentController extends Controller
{
    protected StripeGatewayService $stripeGateway;

    public function __construct(StripeGatewayService $stripeGateway)
    {
        $this->stripeGateway = $stripeGateway;
    }

    /**
     * List payment methods for the authenticated user.
     *
     * GET /api/v1/payments/methods
     */
    public function listMethods(): JsonResponse
    {
        $user = auth()->user();

        $methods = PaymentMethod::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        $data = $methods->map(function ($method) {
            return [
                'id' => $method->id,
                'provider' => $method->provider,
                'brand' => $method->brand,
                'last_four' => $method->last_four,
                'exp_month' => $method->exp_month,
                'exp_year' => $method->exp_year,
                'is_default' => $method->is_default,
                'created_at' => $method->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Delete a payment method.
     *
     * DELETE /api/v1/payments/methods/{id}
     * 
     * @param string $id Either database ID or Stripe payment method ID (pm_xxx)
     */
    public function deleteMethod(string $id): JsonResponse
    {
        $user = auth()->user();

        // Try to find by Stripe payment method ID first, then by database ID
        $paymentMethod = null;
        
        if (str_starts_with($id, 'pm_')) {
            // Look up by Stripe payment method ID
            $paymentMethod = PaymentMethod::where('stripe_payment_method_id', $id)->first();
            
            // If not in database, check ownership via gateway
            if (!$paymentMethod) {
                $ownerId = $this->stripeGateway->getPaymentMethodOwner($id);
                
                if ($ownerId === null) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'NOT_FOUND',
                            'message' => 'Payment method not found.',
                        ],
                    ], 404);
                }
                
                if ($ownerId !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'FORBIDDEN',
                            'message' => 'Payment method not found.',
                        ],
                    ], 404); // 404 to prevent enumeration
                }
                
                // User owns this payment method, delete it from Stripe
                try {
                    $this->stripeGateway->deletePaymentMethod($id);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment method deleted',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to delete payment method from Stripe', [
                        'payment_method_id' => $id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'DELETE_FAILED',
                            'message' => 'Failed to delete payment method.',
                        ],
                    ], 500);
                }
            }
        } else {
            // Look up by database ID
            $paymentMethod = PaymentMethod::find($id);
        }

        // Check if exists and belongs to user
        if (!$paymentMethod || $paymentMethod->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Payment method not found.',
                ],
            ], 404);
        }

        // Check if this is the only payment method and it's default
        $totalMethods = PaymentMethod::where('user_id', $user->id)->count();
        
        if ($totalMethods === 1 && $paymentMethod->is_default) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_DELETE_LAST_DEFAULT',
                    'message' => 'Cannot delete the only payment method.',
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If it has a Stripe ID, detach from Stripe
            if ($paymentMethod->stripe_payment_method_id) {
                $this->stripeGateway->deletePaymentMethod($paymentMethod->stripe_payment_method_id);
            }

            // If deleting the default, reassign to next method
            if ($paymentMethod->is_default) {
                $nextMethod = PaymentMethod::where('user_id', $user->id)
                    ->where('id', '!=', $paymentMethod->id)
                    ->first();

                if ($nextMethod) {
                    $nextMethod->setAsDefault();
                }
            }

            // Delete the payment method
            $paymentMethod->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete payment method', [
                'payment_method_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Failed to delete payment method.',
                ],
            ], 500);
        }
    }
}
