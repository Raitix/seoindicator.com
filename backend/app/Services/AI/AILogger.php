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
     * Keys that should be redacted for security.
     */
    protected array $sensitiveKeys = [
        'api_key',
        'apiKey',
        'password',
        'secret',
        'token',
        'authorization',
        'Bearer',
        'X-Api-Key',
    ];

    /**
     * PII keys that should be completely removed from logs.
     */
    protected array $piiKeys = [
        'user_email',
        'user_name',
        'user_id',
        'ip_address',
    ];

    /**
     * Log an AI request.
     *
     * @param string $indicator The SEO indicator being analyzed
     * @param array<string, mixed> $context Request context data
     * @param string $provider The AI provider being used
     */
    public function logRequest(string $indicator, array $context, string $provider): void
    {
        $sanitizedContext = $this->sanitizeContext($context);

        // Anonymize URLs in context
        $sanitizedContext = $this->anonymizeUrls($sanitizedContext);

        // Generate request hash for tracking
        $requestHash = $this->generateRequestHash($indicator, $context);

        Log::channel($this->channel)->info('AI Request', array_merge($sanitizedContext, [
            'indicator' => $indicator,
            'provider' => $provider,
            'request_hash' => $requestHash,
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log an AI response.
     *
     * @param string $indicator The SEO indicator that was analyzed
     * @param array<string, mixed> $response Response data
     * @param int $latencyMs Response latency in milliseconds
     */
    public function logResponse(string $indicator, array $response, int $latencyMs): void
    {
        $sanitizedResponse = $this->sanitizeContext($response);

        // Include all sanitized response data, plus standard fields
        $logData = array_merge($sanitizedResponse, [
            'indicator' => $indicator,
            'latency_ms' => $latencyMs,
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::channel($this->channel)->info('AI Response', $logData);
    }

    /**
     * Log an AI error.
     *
     * @param string $indicator The SEO indicator that was being analyzed
     * @param string $error Error message
     * @param array<string, mixed> $context Additional error context
     */
    public function logError(string $indicator, string $error, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);

        // Truncate stack trace if present (max 5000 chars total including suffix)
        if (isset($sanitizedContext['stack_trace']) && strlen($sanitizedContext['stack_trace']) > 5000) {
            $truncationSuffix = '... [truncated]';
            $sanitizedContext['stack_trace'] = substr($sanitizedContext['stack_trace'], 0, 5000 - strlen($truncationSuffix)) . $truncationSuffix;
        }

        Log::channel($this->channel)->error('AI Error', array_merge($sanitizedContext, [
            'indicator' => $indicator,
            'error' => $error,
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
     * Generate a SHA-256 hash for request tracking.
     *
     * @param string $indicator
     * @param array<string, mixed> $context
     * @return string 64-character hex string
     */
    protected function generateRequestHash(string $indicator, array $context): string
    {
        $hashData = $indicator . json_encode($context, JSON_UNESCAPED_SLASHES);
        return hash('sha256', $hashData);
    }

    /**
     * Anonymize URLs in context data.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function anonymizeUrls(array $context): array
    {
        foreach ($context as $key => $value) {
            if ($key === 'url' && is_string($value)) {
                // Replace URL with anonymized version
                $context[$key] = $this->anonymizeUrl($value);
            } elseif (is_array($value)) {
                $context[$key] = $this->anonymizeUrls($value);
            }
        }

        return $context;
    }

    /**
     * Anonymize a single URL by hashing the path.
     *
     * @param string $url
     * @return string
     */
    protected function anonymizeUrl(string $url): string
    {
        // Parse the URL
        $parsed = parse_url($url);
        
        if ($parsed === false) {
            return '[invalid-url]';
        }

        // Extract domain (keep it for context)
        $domain = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'example.com');
        
        // Hash the path and query for anonymization
        $pathAndQuery = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        $pathHash = substr(hash('sha256', $pathAndQuery), 0, 8);

        return $domain . '/[anon:' . $pathHash . ']';
    }

    /**
     * Sanitize context data to remove sensitive information and PII.
     *
     * @param array<string, mixed> $context Context data to sanitize
     * @return array<string, mixed> Sanitized context
     */
    protected function sanitizeContext(array $context): array
    {
        // Remove PII keys entirely
        foreach ($this->piiKeys as $key) {
            unset($context[$key]);
        }

        // Redact sensitive keys
        foreach ($this->sensitiveKeys as $key) {
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
