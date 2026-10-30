<?php

namespace App\Services\Billing;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Repositories\CreditRepository;
use App\Exceptions\InsufficientCreditsException;
use App\Exceptions\InvalidCreditAmountException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CreditService - Core service for credit balance management.
 *
 * Handles all credit operations with transaction safety and audit logging.
 * This is the foundation service for the billing system.
 *
 * @see TASK-26-5
 * @see FEAT-026
 */
class CreditService
{
    /**
     * Valid transaction types.
     */
    public const VALID_TYPES = [
        CreditTransaction::TYPE_PURCHASE,
        CreditTransaction::TYPE_USAGE,
        CreditTransaction::TYPE_REFUND,
        CreditTransaction::TYPE_BONUS,
    ];

    /**
     * Maximum allowed credit amount.
     */
    public const MAX_AMOUNT = 99999999.99;

    /**
     * Create a new CreditService instance.
     */
    public function __construct(
        protected CreditRepository $repository
    ) {}

    /**
     * Get the current credit balance for a user.
     *
     * Creates a credit record if one doesn't exist.
     *
     * @param User $user
     * @return float
     */
    public function getBalance(User $user): float
    {
        return $this->repository->getBalance($user);
    }

    /**
     * Add credits to a user's balance.
     *
     * Creates a transaction record and updates the balance atomically.
     * Supports idempotency for payment webhooks via payment_id.
     *
     * @param User $user
     * @param float $amount Amount to add (must be positive)
     * @param string $type Transaction type: purchase, bonus
     * @param array $metadata Additional transaction data
     * @return CreditTransaction
     * @throws InvalidCreditAmountException If amount is invalid
     * @throws \InvalidArgumentException If type is invalid
     */
    public function addCredits(User $user, float $amount, string $type, array $metadata = []): CreditTransaction
    {
        // Validate amount
        if ($amount <= 0) {
            throw InvalidCreditAmountException::zeroAmount();
        }

        if ($amount > self::MAX_AMOUNT) {
            throw InvalidCreditAmountException::exceedsMaximum($amount, self::MAX_AMOUNT);
        }

        // Validate type (only purchase and bonus allowed for adding)
        if (!in_array($type, [CreditTransaction::TYPE_PURCHASE, CreditTransaction::TYPE_BONUS])) {
            throw new \InvalidArgumentException("Invalid transaction type: {$type}. Only 'purchase' and 'bonus' are allowed for adding credits.");
        }

        // Check for idempotency (same payment_id)
        $paymentProvider = $metadata['payment_provider'] ?? null;
        $paymentId = $metadata['payment_id'] ?? null;

        if ($paymentProvider && $paymentId) {
            $existingTransaction = CreditTransaction::where('payment_provider', $paymentProvider)
                ->where('payment_id', $paymentId)
                ->first();

            if ($existingTransaction) {
                // Return existing transaction (idempotent)
                return $existingTransaction;
            }
        }

        // Build transaction data
        $data = [
            'type' => $type,
            'description' => $metadata['description'] ?? "Credit {$type}",
            'payment_provider' => $paymentProvider,
            'payment_id' => $paymentId,
            'metadata' => $metadata['metadata'] ?? null,
            'reference_type' => $metadata['reference_type'] ?? null,
            'reference_id' => $metadata['reference_id'] ?? null,
        ];

        return $this->repository->addCredits($user, $amount, $data);
    }

    /**
     * Deduct credits from a user's balance.
     *
     * Creates a transaction record and updates the balance atomically.
     * Throws an exception if insufficient balance.
     *
     * @param User $user
     * @param float $amount Amount to deduct (must be positive)
     * @param string $referenceType The type of entity causing the deduction
     * @param int $referenceId The ID of the entity causing the deduction
     * @return CreditTransaction
     * @throws InvalidCreditAmountException If amount is invalid
     * @throws InsufficientCreditsException If insufficient balance
     */
    public function deductCredits(User $user, float $amount, string $referenceType, int $referenceId): CreditTransaction
    {
        // Validate amount
        if ($amount <= 0) {
            throw InvalidCreditAmountException::zeroAmount();
        }

        if ($amount > self::MAX_AMOUNT) {
            throw InvalidCreditAmountException::exceedsMaximum($amount, self::MAX_AMOUNT);
        }

        try {
            return DB::transaction(function () use ($user, $amount, $referenceType, $referenceId) {
                // Lock the credit record
                $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$credit) {
                    $credit = Credit::create([
                        'user_id' => $user->id,
                        'balance' => 0,
                        'total_purchased' => 0,
                        'total_spent' => 0,
                    ]);
                }

                // Check sufficient balance
                if (!$credit->hasSufficientBalance($amount)) {
                    throw new InsufficientCreditsException(
                        (float) $credit->balance,
                        $amount
                    );
                }

                $balanceAfter = (float) $credit->balance - $amount;

                // Create transaction record
                $transaction = CreditTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$amount, // Negative for deduction
                    'balance_after' => $balanceAfter,
                    'type' => CreditTransaction::TYPE_USAGE,
                    'description' => "Credit usage for {$referenceType} #{$referenceId}",
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);

                // Update credit balance
                $credit->balance = $balanceAfter;
                $credit->total_spent = (float) $credit->total_spent + $amount;
                $credit->save();

                return $transaction;
            }, 3); // 3 retries for deadlock
        } catch (InsufficientCreditsException $e) {
            // Log the error
            Log::error('Insufficient credits for deduction', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'current_balance' => $e->getCurrentBalance(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a user has sufficient balance for an amount.
     *
     * Creates a credit record if one doesn't exist (for consistent behavior with getBalance).
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        // Ensure credit record exists (matches getBalance behavior)
        $this->repository->findOrCreateForUser($user);
        
        return $this->repository->hasSufficientBalance($user, $amount);
    }

    /**
     * Get paginated transaction history for a user.
     *
     * @param User $user
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getTransactionHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTransactions($user, $perPage);
    }

    /**
     * Refund credits to a user's balance.
     *
     * Creates a refund transaction and updates the balance.
     *
     * @param User $user
     * @param float $amount Amount to refund (must be positive)
     * @param int|null $originalTransactionId Optional original transaction reference
     * @param string $reason Reason for the refund
     * @return CreditTransaction
     * @throws InvalidCreditAmountException If amount is invalid
     */
    public function refundCredits(User $user, float $amount, ?int $originalTransactionId, string $reason): CreditTransaction
    {
        // Validate amount
        if ($amount <= 0) {
            throw InvalidCreditAmountException::zeroAmount();
        }

        if ($amount > self::MAX_AMOUNT) {
            throw InvalidCreditAmountException::exceedsMaximum($amount, self::MAX_AMOUNT);
        }

        return DB::transaction(function () use ($user, $amount, $originalTransactionId, $reason) {
            // Lock the credit record
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$credit) {
                $credit = Credit::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'total_purchased' => 0,
                    'total_spent' => 0,
                ]);
            }

            $balanceAfter = (float) $credit->balance + $amount;

            // Build description
            $description = $reason;
            if ($originalTransactionId) {
                $description .= " (Refund for transaction #{$originalTransactionId})";
            }

            // Create refund transaction
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'type' => CreditTransaction::TYPE_REFUND,
                'description' => $description,
                'reference_type' => $originalTransactionId ? CreditTransaction::class : null,
                'reference_id' => $originalTransactionId,
            ]);

            // Update credit balance (refunds don't count toward total_purchased)
            $credit->balance = $balanceAfter;
            $credit->save();

            return $transaction;
        }, 3); // 3 retries for deadlock
    }
}
