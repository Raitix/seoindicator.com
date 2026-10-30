<?php

namespace App\Services\Billing\Exceptions;

use Exception;

/**
 * Payment Validation Exception
 *
 * Thrown when payment input validation fails.
 * Used for client errors like invalid amount, currency, etc.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class PaymentValidationException extends Exception
{
    /**
     * The validation field that failed.
     */
    protected ?string $field;

    /**
     * Create a new PaymentValidationException instance.
     *
     * @param string $message Error message
     * @param string|null $field The field that failed validation
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?string $field = null,
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->field = $field;
    }

    /**
     * Get the field that failed validation.
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * Create exception for minimum amount violation.
     */
    public static function minimumAmount(float $minimum, float $provided): self
    {
        return new self(
            "Minimum purchase amount is \${$minimum}. You provided \${$provided}.",
            'amount'
        );
    }

    /**
     * Create exception for maximum amount violation.
     */
    public static function maximumAmount(float $maximum, float $provided): self
    {
        return new self(
            "Maximum purchase amount is \${$maximum}. You provided \${$provided}.",
            'amount'
        );
    }

    /**
     * Create exception for invalid currency.
     */
    public static function invalidCurrency(string $currency): self
    {
        return new self(
            "Invalid currency: {$currency}. Supported currencies: USD, EUR, GBP.",
            'currency'
        );
    }

    /**
     * Create exception for invalid package.
     */
    public static function invalidPackage(int $packageId): self
    {
        return new self(
            "Invalid credit package: {$packageId}.",
            'package_id'
        );
    }

    /**
     * Create exception for amount mismatch.
     */
    public static function amountMismatch(float $expected, float $provided): self
    {
        return new self(
            "Amount mismatch. Expected \${$expected}, got \${$provided}.",
            'amount'
        );
    }

    /**
     * Get validation errors array for API response.
     */
    public function getValidationErrors(): array
    {
        return [
            $this->field ?? 'payment' => [$this->getMessage()],
        ];
    }
}
