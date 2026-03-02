<?php

namespace App\Services;

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
 * Service for managing user credit balances.
 *
 * Handles all credit operations with transaction safety and audit logging.
 *
 * @see TASK-26-5
 * @see FEAT-026
 */
class CreditService
{
    /**
     * Valid transaction types for adding credits.
     */
    protected const VALID_ADD_TYPES = [
        CreditTransaction::TYPE_PURCHASE,
        CreditTransaction::TYPE_BONUS,
        CreditTransaction::TYPE_REFUND,
    ];

    /**
     * Maximum single transaction amount.
     */
    protected const MAX_AMOUNT = 100000000.00;

    /**
     * Create a new credit service instance.
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
     * Supports idempotency via payment_id.
     *
     * @param User $user
     * @param float $amount
     * @param string $type
     * @param array $metadata
     * @return CreditTransaction
     * @throws InvalidCreditAmountException
     */
    public function addCredits(User $user, float $amount, string $type, array $metadata = []): CreditTransaction
    {
        // Validate amount
        $this->validateAmount($amount);

        // Validate type
        if (!in_array($type, self::VALID_ADD_TYPES)) {
            throw new \InvalidArgumentException("Invalid transaction type: {$type}");
        }

        // Check for idempotency
        $paymentId = $metadata['payment_id'] ?? null;
        $paymentProvider = $metadata['payment_provider'] ?? null;

        if ($paymentId && $paymentProvider) {
            $existing = $this->findTransactionByPaymentId($paymentProvider, $paymentId);
            if ($existing) {
                return $existing;
            }
        }

        return $this->repository->addCredits($user, $amount, array_merge($metadata, [
            'type' => $type,
        ]));
    }

    /**
     * Deduct credits from a user's balance.
     *
     * Creates a transaction record and updates the balance atomically.
     * Throws an exception if insufficient balance.
     *
     * @param User $user
     * @param float $amount
     * @param string $referenceType
     * @param int $referenceId
     * @param array $metadata
     * @return CreditTransaction
     * @throws InsufficientCreditsException
     * @throws InvalidCreditAmountException
     */
    public function deductCredits(
        User $user,
        float $amount,
        string $referenceType,
        int $referenceId,
        array $metadata = []
    ): CreditTransaction {
        // Validate amount
        $this->validateAmount($amount);

        // Check balance before attempting deduction
        if (!$this->hasSufficientBalance($user, $amount)) {
            $currentBalance = $this->getBalance($user);

            Log::error('Insufficient credits for deduction', [
                'user_id' => $user->id,
                'current_balance' => $currentBalance,
                'requested_amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            throw new InsufficientCreditsException($currentBalance, $amount);
        }

        return $this->repository->deductCredits($user, $amount, [
            'type' => CreditTransaction::TYPE_USAGE,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $metadata['description'] ?? 'Credit deduction',
            'metadata' => $metadata['metadata'] ?? null,
        ]);
    }

    /**
     * Check if user has sufficient balance for an amount.
     *
     * Creates a credit record if one doesn't exist.
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        // Ensure credit record exists for the user
        $this->repository->findOrCreateForUser($user);

        // Negative or zero amounts are always "sufficient"
        if ($amount <= 0) {
            return true;
        }

        return $this->repository->hasSufficientBalance($user, $amount);
    }

    /**
     * Get paginated transaction history for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactionHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTransactions($user, $perPage);
    }

    /**
     * Refund credits to a user's balance.
     *
     * @param User $user
     * @param float $amount
     * @param int|null $originalTransactionId
     * @param string $reason
     * @return CreditTransaction
     * @throws InvalidCreditAmountException
     */
    public function refundCredits(
        User $user,
        float $amount,
        ?int $originalTransactionId,
        string $reason
    ): CreditTransaction {
        // Validate amount
        $this->validateAmount($amount);

        $metadata = [
            'reason' => $reason,
        ];

        if ($originalTransactionId) {
            $metadata['original_transaction_id'] = $originalTransactionId;
        }

        return $this->repository->addCredits($user, $amount, [
            'type' => CreditTransaction::TYPE_REFUND,
            'description' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Validate the amount is valid.
     *
     * @param float $amount
     * @throws InvalidCreditAmountException
     */
    protected function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw InvalidCreditAmountException::zeroAmount();
        }

        if ($amount > self::MAX_AMOUNT) {
            throw InvalidCreditAmountException::exceedsMaximum($amount, self::MAX_AMOUNT);
        }
    }

    /**
     * Find an existing transaction by payment provider and ID.
     *
     * Used for idempotency checking.
     *
     * @param string $provider
     * @param string $paymentId
     * @return CreditTransaction|null
     */
    protected function findTransactionByPaymentId(string $provider, string $paymentId): ?CreditTransaction
    {
        return CreditTransaction::where('payment_provider', $provider)
            ->where('payment_id', $paymentId)
            ->first();
    }

    /**
     * Get credit statistics for a user.
     *
     * @param User $user
     * @return array
     */
    public function getStats(User $user): array
    {
        return $this->repository->getStats($user);
    }
}
