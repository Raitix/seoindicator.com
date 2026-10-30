<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a user has insufficient credits for an operation.
 *
 * @see TASK-26-5
 * @see FEAT-026
 */
class InsufficientCreditsException extends Exception
{
    /**
     * The user's current balance.
     */
    protected float $currentBalance;

    /**
     * The requested amount.
     */
    protected float $requestedAmount;

    /**
     * Create a new insufficient credits exception.
     *
     * @param float $currentBalance
     * @param float $requestedAmount
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        float $currentBalance = 0.0,
        float $requestedAmount = 0.0,
        string $message = 'Insufficient credit balance',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->currentBalance = $currentBalance;
        $this->requestedAmount = $requestedAmount;

        $message = "Insufficient credit balance. Current: {$currentBalance}, Required: {$requestedAmount}";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the current balance.
     */
    public function getCurrentBalance(): float
    {
        return $this->currentBalance;
    }

    /**
     * Get the requested amount.
     */
    public function getRequestedAmount(): float
    {
        return $this->requestedAmount;
    }

    /**
     * Get the shortfall amount.
     */
    public function getShortfall(): float
    {
        return max(0, $this->requestedAmount - $this->currentBalance);
    }

    /**
     * Get context for logging.
     */
    public function getContext(): array
    {
        return [
            'current_balance' => $this->currentBalance,
            'requested_amount' => $this->requestedAmount,
            'shortfall' => $this->getShortfall(),
        ];
    }
}
