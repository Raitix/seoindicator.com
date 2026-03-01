<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Custom validation rule for max context length.
 *
 * Validates that the total character count of all values
 * in an array does not exceed the maximum.
 */
class MaxContextLength implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param int $maxLength Maximum total characters allowed
     */
    public function __construct(
        protected int $maxLength = 5000
    ) {}

    /**
     * Run the validation rule.
     *
     * @param string $attribute The attribute being validated
     * @param mixed $value The value to validate
     * @param Closure $fail The failure callback
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        $totalChars = $this->calculateTotalChars($value);

        if ($totalChars > $this->maxLength) {
            $fail("The total size of context data exceeds the maximum of {$this->maxLength} characters.");
        }
    }

    /**
     * Calculate the total character count of all values in the array.
     *
     * @param array $value
     * @return int
     */
    protected function calculateTotalChars(array $value): int
    {
        $total = 0;

        foreach ($value as $item) {
            if (is_string($item)) {
                $total += strlen($item);
            } elseif (is_array($item)) {
                $total += $this->calculateTotalChars($item);
            } elseif (is_null($item) || is_bool($item)) {
                // Skip null and boolean values
                continue;
            } else {
                // Convert other types to string for counting
                $total += strlen((string) $item);
            }
        }

        return $total;
    }
}
