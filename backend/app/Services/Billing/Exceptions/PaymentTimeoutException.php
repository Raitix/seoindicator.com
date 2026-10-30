<?php

namespace App\Services\Billing\Exceptions;

use Exception;

/**
 * Payment Timeout Exception
 *
 * Thrown when a payment request times out due to network issues.
 * Indicates a temporary problem that may be retried.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class PaymentTimeoutException extends Exception
{
    /**
     * Create a new PaymentTimeoutException instance.
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Payment service unavailable. Please try again.',
        int $code = 503,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get user-friendly message.
     */
    public function getUserMessage(): string
    {
        return 'The payment service is temporarily unavailable. Please wait a moment and try again.';
    }

    /**
     * Whether the operation can be retried.
     */
    public function canRetry(): bool
    {
        return true;
    }

    /**
     * Suggested retry delay in seconds.
     */
    public function getSuggestedRetryDelay(): int
    {
        return 5;
    }
}
