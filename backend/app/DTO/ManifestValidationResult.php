<?php

namespace App\DTO;

/**
 * Data Transfer Object for manifest validation results.
 *
 * Contains the validation status, errors, warnings, and field presence
 * information for a PWA manifest validation operation.
 *
 * @see TASKS/TASK-24-4.md
 * @see TESTS/SPEC-24-4.md
 */
class ManifestValidationResult
{
    /**
     * @param bool $valid Whether the manifest passed validation
     * @param array<ManifestError> $errors Array of validation errors
     * @param array<ManifestError> $warnings Array of validation warnings
     * @param array<string, bool> $fieldPresence Map of field names to presence status
     */
    public function __construct(
        public bool $valid,
        public array $errors = [],
        public array $warnings = [],
        public array $fieldPresence = []
    ) {}

    /**
     * Check if the result has any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if the result has any warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => array_map(fn (ManifestError $e) => $e->toArray(), $this->errors),
            'warnings' => array_map(fn (ManifestError $w) => $w->toArray(), $this->warnings),
            'fieldPresence' => $this->fieldPresence,
        ];
    }
}
