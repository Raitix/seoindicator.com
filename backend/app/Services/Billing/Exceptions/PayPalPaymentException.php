<?php

namespace App\Services\Billing\Exceptions;

use Exception;

/**
 * PayPal Payment Exception
 *
 * Thrown when a PayPal API call fails with a payment-related error.
 * Contains the PayPal error code for proper error handling.
 *
 * @see TASK-26-12
 * @see FEAT-026
 */
class PayPalPaymentException extends Exception
{
    /**
     * The PayPal error code.
     */
    protected string $paypalCode;

    /**
     * Additional context about the error.
     */
    protected array $context = [];

    /**
     * Create a new PayPalPaymentException instance.
     *
     * @param string $message Error message
     * @param string $paypalCode PayPal error code (e.g., 'DECLINED')
     * @param array $context Additional context
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $paypalCode = '',
        array $context = [],
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->paypalCode = $paypalCode;
        $this->context = $context;
    }

    /**
     * Get the PayPal error code.
     */
    public function getPayPalCode(): string
    {
        return $this->paypalCode;
    }

    /**
     * Get the error context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception for payment declined.
     */
    public static function paymentDeclined(string $message = 'Your PayPal payment was declined.'): self
    {
        return new self($message, 'DECLINED');
    }

    /**
     * Create exception for insufficient funds.
     */
    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds in PayPal account.', 'INSUFFICIENT_FUNDS');
    }

    /**
     * Create exception for expired payment method.
     */
    public static function expiredPaymentMethod(): self
    {
        return new self('Your PayPal payment method has expired.', 'EXPIRED_PAYMENT_METHOD');
    }

    /**
     * Create exception for processing error.
     */
    public static function processingError(string $message = 'PayPal payment processing failed.'): self
    {
        return new self($message, 'PROCESSING_ERROR');
    }

    /**
     * Create exception for API error.
     */
    public static function apiError(string $message = 'PayPal API error.'): self
    {
        return new self($message, 'API_ERROR', [], 502);
    }

    /**
     * Create exception for capture failed.
     */
    public static function captureFailed(string $orderId): self
    {
        return new self(
            'Failed to capture PayPal order.',
            'CAPTURE_FAILED',
            ['order_id' => $orderId]
        );
    }

    /**
     * Create exception for refund failed.
     */
    public static function refundFailed(string $captureId): self
    {
        return new self(
            'Failed to refund PayPal payment.',
            'REFUND_FAILED',
            ['capture_id' => $captureId]
        );
    }

    /**
     * Create exception for order not found.
     */
    public static function orderNotFound(string $orderId): self
    {
        return new self(
            'PayPal order not found.',
            'ORDER_NOT_FOUND',
            ['order_id' => $orderId],
            404
        );
    }

    /**
     * Create exception for customer creation not supported.
     */
    public static function customerNotSupported(): self
    {
        return new self(
            'PayPal does not support customer creation. Use payer_id from approved orders.',
            'CUSTOMER_NOT_SUPPORTED'
        );
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return match ($this->paypalCode) {
            'DECLINED' => 'Your PayPal payment was declined. Please try a different payment method.',
            'INSUFFICIENT_FUNDS' => 'Insufficient funds in your PayPal account.',
            'EXPIRED_PAYMENT_METHOD' => 'Your PayPal payment method has expired. Please update your payment method.',
            'PROCESSING_ERROR' => 'An error occurred while processing your PayPal payment. Please try again.',
            'ORDER_NOT_FOUND' => 'The PayPal order could not be found.',
            'CAPTURE_FAILED' => 'Failed to complete the PayPal payment. Please try again.',
            'REFUND_FAILED' => 'Failed to process the refund. Please contact support.',
            'RATE_LIMIT' => 'Too many requests. Please wait a moment and try again.',
            default => $this->getMessage(),
        };
    }
}
