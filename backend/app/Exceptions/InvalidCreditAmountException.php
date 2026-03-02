<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid credit amount is provided.
 *
 * @see TASK-26-5
 * @see FEAT-026
 */
class InvalidCreditAmountException extends Exception
{
    /**
     * The invalid amount.
     */
    protected float $amount;

    /**
     * The reason for invalidity.
     */
    protected string $reason;

    /**
     * Create a new invalid credit amount exception.
     *
     * @param float $amount
     * @param string $reason
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        float $amount = 0.0,
        string $reason = 'Invalid credit amount',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->amount = $amount;
        $this->reason = $reason;

        $message = "Invalid credit amount: {$amount}. {$reason}";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the invalid amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the reason for invalidity.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Create exception for zero amount.
     */
    public static function zeroAmount(): self
    {
        return new self(0.0, 'Amount must be greater than zero');
    }

    /**
     * Create exception for negative amount.
     */
    public static function negativeAmount(float $amount): self
    {
        return new self($amount, 'Amount cannot be negative');
    }

    /**
     * Create exception for amount exceeding maximum.
     */
    public static function exceedsMaximum(float $amount, float $max): self
    {
        return new self($amount, "Amount exceeds maximum allowed value of {$max}");
    }

    /**
     * Get context for logging.
     */
    public function getContext(): array
    {
        return [
            'amount' => $this->amount,
            'reason' => $this->reason,
        ];
    }
}
