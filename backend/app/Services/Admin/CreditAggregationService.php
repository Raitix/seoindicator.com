<?php

namespace App\Services\Admin;

use App\Models\Credit;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Cache;

/**
 * Credit Aggregation Service for Admin Dashboard.
 *
 * Aggregates credit balances, spending, and revenue metrics.
 *
 * @see TASK-27-9
 * @see SPEC-027
 */
class CreditAggregationService
{
    /**
     * Low balance threshold (users below this are flagged).
     */
    private const LOW_BALANCE_THRESHOLD = 10.00;

    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Get all aggregated credit metrics.
     *
     * @return array
     */
    public function getAllMetrics(): array
    {
        return [
            'balances' => $this->getBalanceMetrics(),
            'spend' => $this->getSpendMetrics(),
            'revenue' => $this->getRevenueMetrics(),
        ];
    }

    /**
     * Get balance metrics.
     *
     * @return array
     */
    public function getBalanceMetrics(): array
    {
        return Cache::remember('admin.credit.balances', self::CACHE_TTL, function () {
            $credits = Credit::all();

            $totalHeld = $credits->sum('balance');
            $usersWithBalance = $credits->where('balance', '>', 0)->count();
            $lowBalanceCount = $credits->where('balance', '>', 0)
                ->where('balance', '<', self::LOW_BALANCE_THRESHOLD)
                ->count();

            $averagePerUser = $usersWithBalance > 0
                ? round($totalHeld / $usersWithBalance, 2)
                : 0;

            return [
                'totalHeld' => (float) round($totalHeld, 2),
                'averagePerUser' => (float) round($averagePerUser, 2),
                'usersWithBalance' => $usersWithBalance,
                'lowBalanceCount' => $lowBalanceCount,
            ];
        });
    }

    /**
     * Get spend metrics.
     *
     * @return array
     */
    public function getSpendMetrics(): array
    {
        return Cache::remember('admin.credit.spend', self::CACHE_TTL, function () {
            $usageTransactions = CreditTransaction::where('type', 'usage');

            return [
                'total' => round(abs($usageTransactions->sum('amount')), 2),
                'today' => round(abs($usageTransactions->whereDate('created_at', today())->sum('amount')), 2),
                'thisWeek' => round(abs($usageTransactions->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->sum('amount')), 2),
                'thisMonth' => round(abs($usageTransactions->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount')), 2),
            ];
        });
    }

    /**
     * Get revenue metrics.
     *
     * @return array
     */
    public function getRevenueMetrics(): array
    {
        return Cache::remember('admin.credit.revenue', self::CACHE_TTL, function () {
            $purchaseTransactions = CreditTransaction::where('type', 'purchase');

            return [
                'total' => (float) round($purchaseTransactions->sum('amount'), 2),
                'today' => (float) round($purchaseTransactions->whereDate('created_at', today())->sum('amount'), 2),
                'thisWeek' => (float) round($purchaseTransactions->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->sum('amount'), 2),
                'thisMonth' => (float) round($purchaseTransactions->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'), 2),
                'byPackage' => [],
            ];
        });
    }

    /**
     * Get paginated transactions with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getTransactions(array $filters = [], int $perPage = 20): array
    {
        $query = CreditTransaction::query()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Paginate
        $perPage = min(max(1, $perPage), 100);
        $results = $query->paginate($perPage);

        // Transform to exclude PII
        $data = collect($results->items())->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'type' => $transaction->type,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        });

        return [
            'data' => $data,
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ];
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('admin.credit.balances');
        Cache::forget('admin.credit.spend');
        Cache::forget('admin.credit.revenue');
    }
}
