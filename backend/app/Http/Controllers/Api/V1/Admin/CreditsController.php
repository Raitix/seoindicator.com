<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\CreditAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Credits Controller.
 *
 * Handles credit/balance statistics, transactions, and revenue metrics.
 *
 * @see TASK-27-10
 * @see SPEC-027
 */
class CreditsController extends Controller
{
    protected CreditAggregationService $creditService;

    public function __construct(CreditAggregationService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Get credit/balance overview statistics.
     *
     * GET /api/v1/admin/credits/overview
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        try {
            $metrics = $this->creditService->getAllMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
            ], 500);
        }
    }

    /**
     * Get paginated transactions list with filters.
     *
     * GET /api/v1/admin/credits/transactions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'in:purchase,usage,refund,bonus'],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filters = [
            'user_id' => $validated['user_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];

        $perPage = $validated['per_page'] ?? 20;

        $result = $this->creditService->getTransactions($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Get revenue metrics.
     *
     * GET /api/v1/admin/credits/revenue
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revenue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date', 'date_format:Y-m-d'],
        ]);

        $metrics = $this->creditService->getRevenueMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}
