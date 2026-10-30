<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Credits\TransactionHistoryRequest;
use App\Models\Credit;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for credit-related operations.
 *
 * @see TASK-26-7
 * @see FEAT-026
 */
class CreditController extends Controller
{
    /**
     * Low balance threshold in dollars.
     */
    private const LOW_BALANCE_THRESHOLD = 2.00;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Get the current balance for the authenticated user.
     *
     * @return JsonResponse
     */
    public function balance(): JsonResponse
    {
        $user = Auth::user();

        // Get or create credit record
        $credit = Credit::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'total_purchased' => 0.00,
                'total_spent' => 0.00,
            ]
        );

        return response()->json([
            'balance' => (float) $credit->balance,
            'formatted_balance' => '$' . number_format($credit->balance, 2),
            'total_purchased' => (float) $credit->total_purchased,
            'total_spent' => (float) $credit->total_spent,
            'low_balance' => $credit->balance < self::LOW_BALANCE_THRESHOLD,
        ]);
    }

    /**
     * Get paginated transaction history for the authenticated user.
     *
     * @param TransactionHistoryRequest $request
     * @return JsonResponse
     */
    public function transactions(TransactionHistoryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $params = $request->getParams();

        $filters = [
            'type' => $params['type'],
            'from' => $params['from'],
            'to' => $params['to'],
        ];

        $paginator = $this->creditService->getTransactionHistoryWithFilters(
            $user,
            $params['per_page'],
            $filters
        );

        // Transform transactions for API response
        $data = collect($paginator->items())->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
                'type' => $transaction->type,
                'description' => $transaction->description,
                'payment_provider' => $transaction->payment_provider,
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
