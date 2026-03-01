<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Content size validation rule for crawler API.
 *
 * Validates that content size is within acceptable limits:
 * - Default max: 3MB (3,145,728 bytes)
 * - Must be positive integer
 * - Configurable max limit
 *
 * @see TASK-22-12
 */
class MaxContentSize implements ValidationRule
{
    /**
     * Default maximum size in bytes (3MB).
     */
    protected const DEFAULT_MAX_BYTES = 3145728;

    /**
     * Maximum allowed size in bytes.
     */
    protected int $maxBytes;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $maxBytes = null)
    {
        $this->maxBytes = $maxBytes ?? self::DEFAULT_MAX_BYTES;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Null is not valid
        if ($value === null) {
            $fail('The :attribute must be a numeric value.');
            return;
        }

        // Check if numeric
        if (!is_numeric($value)) {
            $fail('The :attribute must be a numeric value.');
            return;
        }

        // Convert to integer (handles string numbers and floats)
        $intSize = $this->convertToInteger($value);

        // Check if conversion failed (for non-numeric strings)
        if ($intSize === null) {
            $fail('The :attribute must be a valid numeric value.');
            return;
        }

        // Check for negative values
        if ($intSize < 0) {
            $fail('The :attribute must be a positive value.');
            return;
        }

        // Check against max limit
        if ($intSize > $this->maxBytes) {
            $maxMb = round($this->maxBytes / 1048576, 1);
            $fail("The :attribute exceeds the maximum allowed size of {$maxMb} MB.");
            return;
        }
    }

    /**
     * Convert value to integer.
     *
     * Handles:
     * - Integer values
     * - Float values (truncates)
     * - Numeric strings
     * - Scientific notation
     */
    protected function convertToInteger(mixed $value): ?int
    {
        // Already an integer
        if (is_int($value)) {
            return $value;
        }

        // Float - check if it represents a fractional value
        if (is_float($value)) {
            // If the float is larger than max after truncation, fail
            $truncated = (int) $value;
            if ($value > $truncated) {
                // Float with fractional part - this means it's over the integer value
                return (int) ceil($value);
            }
            return $truncated;
        }

        // String
        if (is_string($value)) {
            // Check if it's a numeric string
            if (!is_numeric($value)) {
                return null;
            }

            // Handle scientific notation
            $floatVal = (float) $value;
            return (int) $floatVal;
        }

        // Boolean or other types
        return null;
    }

    /**
     * Get the maximum bytes allowed.
     */
    public function getMaxBytes(): int
    {
        return $this->maxBytes;
    }
}
