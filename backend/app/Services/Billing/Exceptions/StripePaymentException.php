<?php

namespace App\Services\Billing\Exceptions;

use Exception;

/**
 * Stripe Payment Exception
 *
 * Thrown when a Stripe API call fails with a payment-related error.
 * Contains the Stripe error code for proper error handling.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class StripePaymentException extends Exception
{
    /**
     * The Stripe error code.
     */
    protected string $stripeCode;

    /**
     * Additional context about the error.
     */
    protected array $context = [];

    /**
     * Create a new StripePaymentException instance.
     *
     * @param string $message Error message
     * @param string $stripeCode Stripe error code (e.g., 'card_declined')
     * @param array $context Additional context
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $stripeCode = '',
        array $context = [],
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->stripeCode = $stripeCode;
        $this->context = $context;
    }

    /**
     * Get the Stripe error code.
     */
    public function getStripeCode(): string
    {
        return $this->stripeCode;
    }

    /**
     * Get the error context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception for card declined.
     */
    public static function cardDeclined(string $message = 'Your card was declined.'): self
    {
        return new self($message, 'card_declined');
    }

    /**
     * Create exception for insufficient funds.
     */
    public static function insufficientFunds(): self
    {
        return new self('Your card has insufficient funds.', 'insufficient_funds');
    }

    /**
     * Create exception for expired card.
     */
    public static function expiredCard(): self
    {
        return new self('Your card has expired.', 'expired_card');
    }

    /**
     * Create exception for processing error.
     */
    public static function processingError(string $message = 'Payment processing failed.'): self
    {
        return new self($message, 'processing_error');
    }

    /**
     * Create exception for API error.
     */
    public static function apiError(string $message = 'Stripe API error.'): self
    {
        return new self($message, 'api_error', [], 502);
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return match ($this->stripeCode) {
            'card_declined' => 'Your card was declined. Please try a different payment method.',
            'insufficient_funds' => 'Your card has insufficient funds.',
            'expired_card' => 'Your card has expired. Please update your payment method.',
            'incorrect_cvc' => 'Your card\'s security code is incorrect.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'rate_limit' => 'Too many requests. Please wait a moment and try again.',
            default => $this->getMessage(),
        };
    }
}
