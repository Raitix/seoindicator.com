<?php

namespace App\DTO;

/**
 * Data Transfer Object for manifest validation errors.
 *
 * Represents a single validation error or warning found during
 * PWA manifest validation.
 *
 * @see TASKS/TASK-24-4.md
 * @see TESTS/SPEC-24-4.md
 */
class ManifestError
{
    /**
     * Create a new ManifestError instance.
     *
     * @param string $field The field name that has the error (e.g., 'name', 'icons.0.src')
     * @param string $code The error code (e.g., 'REQUIRED_FIELD_MISSING', 'INVALID_DISPLAY_VALUE')
     * @param string $message Human-readable error message
     * @param string $severity Severity level: 'error' or 'warning'
     */
    public function __construct(
        public string $field,
        public string $code,
        public string $message,
        public string $severity = 'error'
    ) {}

    /**
     * Create an error-level ManifestError.
     *
     * @param string $field
     * @param string $code
     * @param string $message
     * @return self
     */
    public static function error(string $field, string $code, string $message): self
    {
        return new self($field, $code, $message, 'error');
    }

    /**
     * Create a warning-level ManifestError.
     *
     * @param string $field
     * @param string $code
     * @param string $message
     * @return self
     */
    public static function warning(string $field, string $code, string $message): self
    {
        return new self($field, $code, $message, 'warning');
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
        ];
    }
}
