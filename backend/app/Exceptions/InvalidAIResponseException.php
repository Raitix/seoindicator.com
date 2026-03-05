<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI response cannot be parsed or validated.
 *
 * This exception is thrown when:
 * - No valid JSON found in the AI response
 * - JSON schema validation fails
 * - Required fields are missing
 * - Response format is completely unparseable
 *
 * @see TASK-31-5
 * @see \App\Services\AI\AIAnalysisResponseParser
 */
class InvalidAIResponseException extends Exception
{
    /**
     * The raw response that caused the exception.
     */
    protected ?string $rawResponse;

    /**
     * Validation errors if available.
     *
     * @var array<string>
     */
    protected array $validationErrors = [];

    /**
     * Create a new InvalidAIResponseException instance.
     *
     * @param string $message Exception message
     * @param string|null $rawResponse The raw AI response that failed parsing
     * @param array<string> $validationErrors List of validation errors
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        ?string $rawResponse = null,
        array $validationErrors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->rawResponse = $rawResponse;
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get the raw response that caused the exception.
     *
     * @return string|null
     */
    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    /**
     * Get validation errors.
     *
     * @return array<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Create exception for no JSON found.
     *
     * @param string $rawResponse
     * @return self
     */
    public static function noJsonFound(string $rawResponse): self
    {
        return new self(
            'No valid JSON found in AI response',
            $rawResponse,
            ['No JSON object detected in response']
        );
    }

    /**
     * Create exception for schema validation failure.
     *
     * @param array<string> $errors
     * @param string|null $rawResponse
     * @return self
     */
    public static function schemaValidationFailed(array $errors, ?string $rawResponse = null): self
    {
        return new self(
            'AI response failed schema validation: ' . implode('; ', $errors),
            $rawResponse,
            $errors
        );
    }

    /**
     * Create exception for unparseable response.
     *
     * @param string $rawResponse
     * @param string $reason
     * @return self
     */
    public static function unparseable(string $rawResponse, string $reason = ''): self
    {
        $message = 'AI response is unparseable';
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self(
            $message,
            $rawResponse,
            [$reason ?: 'Unable to parse response']
        );
    }

    /**
     * Create exception for missing required field.
     *
     * @param string $field
     * @param string|null $rawResponse
     * @return self
     */
    public static function missingRequiredField(string $field, ?string $rawResponse = null): self
    {
        return new self(
            "Missing required field in AI response: {$field}",
            $rawResponse,
            ["Missing required field: {$field}"]
        );
    }

    /**
     * Get a context-rich error message for logging.
     *
     * @return string
     */
    public function getContextMessage(): string
    {
        $parts = [$this->getMessage()];

        if ($this->rawResponse) {
            $truncated = strlen($this->rawResponse) > 200
                ? substr($this->rawResponse, 0, 200) . '...'
                : $this->rawResponse;
            $parts[] = "Raw response: {$truncated}";
        }

        if (!empty($this->validationErrors)) {
            $parts[] = 'Validation errors: ' . implode(', ', $this->validationErrors);
        }

        return implode(' | ', $parts);
    }
}
