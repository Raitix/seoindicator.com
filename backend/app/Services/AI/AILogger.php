<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * AI Logger Service (TASK-21-7).
 *
 * Provides comprehensive logging for AI suggestion operations,
 * including request/response tracking, error logging, and debug mode support.
 *
 * @see TASK-21-7
 * @see SPEC-021
 */
class AILogger
{
    /**
     * The log channel to use for AI operations.
     */
    protected string $channel = 'ai';

    /**
     * Log an AI request.
     *
     * @param array<string, mixed> $context Request context data
     */
    public function logRequest(array $context): void
    {
        $sanitizedContext = $this->sanitizeContext($context);

        Log::channel($this->channel)->info('AI Request', array_merge($sanitizedContext, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log an AI response.
     *
     * @param array<string, mixed> $response Response data
     */
    public function logResponse(array $response): void
    {
        $sanitizedResponse = $this->sanitizeContext($response);

        Log::channel($this->channel)->info('AI Response', array_merge($sanitizedResponse, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log an AI error.
     *
     * @param array<string, mixed> $error Error context data
     */
    public function logError(array $error): void
    {
        $sanitizedError = $this->sanitizeContext($error);

        // Truncate stack trace if present
        if (isset($sanitizedError['stack_trace']) && strlen($sanitizedError['stack_trace']) > 5000) {
            $sanitizedError['stack_trace'] = substr($sanitizedError['stack_trace'], 0, 5000) . '... [truncated]';
        }

        Log::channel($this->channel)->error('AI Error', array_merge($sanitizedError, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log a debug message.
     *
     * @param string $message Debug message
     * @param array<string, mixed> $data Additional data
     */
    public function logDebug(string $message, array $data = []): void
    {
        if (!config('ai.debug', false)) {
            return;
        }

        $sanitizedData = $this->sanitizeContext($data);

        Log::channel($this->channel)->debug($message, array_merge($sanitizedData, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Sanitize context data to remove sensitive information.
     *
     * @param array<string, mixed> $context Context data to sanitize
     * @return array<string, mixed> Sanitized context
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'api_key',
            'apiKey',
            'password',
            'secret',
            'token',
            'authorization',
            'Bearer',
            'X-Api-Key',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($context[$key])) {
                $context[$key] = '***REDACTED***';
            }
        }

        // Check nested arrays for sensitive data
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }

        return $context;
    }
}
