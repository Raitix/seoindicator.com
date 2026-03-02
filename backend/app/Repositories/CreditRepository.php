<?php

namespace App\Repositories;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Credit and CreditTransaction models.
 *
 * Handles credit balance operations with transaction safety.
 *
 * @see TASK-26-2
 * @see FEAT-026
 */
class CreditRepository
{
    /**
     * Find or create a credit record for a user.
     *
     * @param User $user
     * @return Credit
     */
    public function findOrCreateForUser(User $user): Credit
    {
        return Credit::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_purchased' => 0,
                'total_spent' => 0,
            ]
        );
    }

    /**
     * Get the current balance for a user.
     *
     * Creates a credit record if one doesn't exist.
     *
     * @param User $user
     * @return float
     */
    public function getBalance(User $user): float
    {
        $credit = $this->findOrCreateForUser($user);
        return (float) $credit->balance;
    }

    /**
     * Get paginated transactions for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return CreditTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add credits to a user's balance.
     *
     * Creates a transaction record and updates the balance atomically.
     *
     * @param User $user
     * @param float $amount
     * @param array $data Transaction data (type, description, metadata, etc.)
     * @return CreditTransaction
     */
    public function addCredits(User $user, float $amount, array $data): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $data) {
            $credit = $this->findOrCreateForUser($user);
            $credit = $credit->lockForUpdate()->fresh();

            $balanceAfter = $credit->balance + $amount;

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'type' => $data['type'] ?? CreditTransaction::TYPE_PURCHASE,
                'description' => $data['description'] ?? 'Credit addition',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'payment_provider' => $data['payment_provider'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Update credit balance
            $credit->balance = $balanceAfter;
            
            // Update totals based on type
            if ($amount > 0) {
                if (in_array($data['type'] ?? '', [CreditTransaction::TYPE_PURCHASE, CreditTransaction::TYPE_BONUS])) {
                    $credit->total_purchased += $amount;
                }
            }

            $credit->save();

            return $transaction;
        });
    }

    /**
     * Deduct credits from a user's balance.
     *
     * Creates a transaction record and updates the balance atomically.
     * Throws an exception if insufficient balance.
     *
     * @param User $user
     * @param float $amount
     * @param array $data Transaction data (type, description, metadata, etc.)
     * @return CreditTransaction
     * @throws \Exception If insufficient balance
     */
    public function deductCredits(User $user, float $amount, array $data): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $data) {
            $credit = $this->findOrCreateForUser($user);
            $credit = $credit->lockForUpdate()->fresh();

            if (!$credit->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient credit balance');
            }

            $balanceAfter = $credit->balance - $amount;

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => -$amount, // Negative for deduction
                'balance_after' => $balanceAfter,
                'type' => $data['type'] ?? CreditTransaction::TYPE_USAGE,
                'description' => $data['description'] ?? 'Credit deduction',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'payment_provider' => $data['payment_provider'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Update credit balance
            $credit->balance = $balanceAfter;
            $credit->total_spent += $amount;
            $credit->save();

            return $transaction;
        });
    }

    /**
     * Get credit statistics for a user.
     *
     * @param User $user
     * @return array
     */
    public function getStats(User $user): array
    {
        $credit = $this->findOrCreateForUser($user);

        return [
            'balance' => (float) $credit->balance,
            'total_purchased' => (float) $credit->total_purchased,
            'total_spent' => (float) $credit->total_spent,
            'transaction_count' => CreditTransaction::where('user_id', $user->id)->count(),
        ];
    }

    /**
     * Check if user has sufficient balance for an operation.
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        $credit = $this->findOrCreateForUser($user);
        return $credit->hasSufficientBalance($amount);
    }
}
