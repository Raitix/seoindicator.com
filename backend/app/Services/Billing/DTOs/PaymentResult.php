<?php

namespace App\Services\Billing\DTOs;

/**
 * Payment Result Data Transfer Object
 *
 * Represents the result of a payment operation or webhook processing.
 * Used to standardize payment results across different providers.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class PaymentResult
{
    /**
     * Create a new PaymentResult instance.
     *
     * @param bool $success Whether the payment succeeded
     * @param string|null $paymentId Payment/transaction ID
     * @param float $amount Payment amount (negative for refunds)
     * @param string $currency Currency code
     * @param int|null $userId User ID involved in payment
     * @param string|null $errorCode Error code if failed
     * @param string|null $errorMessage Error message if failed
     * @param array $metadata Additional metadata
     * @param string $type Transaction type (payment, refund, etc.)
     * @param bool $isDuplicate Whether this is a duplicate webhook event
     * @param bool $isIgnored Whether this event was ignored (unknown type)
     * @param bool $requiresCapture Whether payment requires capture (PayPal)
     */
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public float $amount = 0.0,
        public string $currency = 'USD',
        public ?int $userId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
        public string $type = 'payment',
        public bool $isDuplicate = false,
        public bool $isIgnored = false,
        public bool $requiresCapture = false,
    ) {}

    /**
     * Create a successful payment result.
     */
    public static function success(
        string $paymentId,
        float $amount,
        string $currency = 'USD',
        int $userId = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            amount: $amount,
            currency: $currency,
            userId: $userId,
            metadata: $metadata
        );
    }

    /**
     * Create a failed payment result.
     */
    public static function failed(
        string $paymentId,
        float $amount,
        string $errorCode,
        string $errorMessage,
        int $userId = null
    ): self {
        return new self(
            success: false,
            paymentId: $paymentId,
            amount: $amount,
            userId: $userId,
            errorCode: $errorCode,
            errorMessage: $errorMessage
        );
    }

    /**
     * Create a refund result.
     */
    public static function refund(
        string $refundId,
        float $amount,
        int $userId
    ): self {
        return new self(
            success: true,
            paymentId: $refundId,
            amount: -$amount, // Negative for refunds
            userId: $userId,
            type: 'refund'
        );
    }

    /**
     * Create an ignored event result.
     */
    public static function ignored(): self
    {
        return new self(
            success: true,
            isIgnored: true
        );
    }

    /**
     * Create a duplicate event result.
     */
    public static function duplicate(string $paymentId, int $userId): self
    {
        return new self(
            success: true,
            paymentId: $paymentId,
            userId: $userId,
            isDuplicate: true
        );
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_id' => $this->paymentId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'user_id' => $this->userId,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'type' => $this->type,
            'is_duplicate' => $this->isDuplicate,
            'is_ignored' => $this->isIgnored,
        ];
    }
}
