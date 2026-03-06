<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Credits\TransactionHistoryRequest;
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
     * Create a new controller instance.
     */
    public function __construct(
        protected CreditService $creditService
    ) {}

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
