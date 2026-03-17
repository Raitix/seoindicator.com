<?php

namespace App\Services\Tools;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tool Error Handler Service.
 * 
 * Centralized error handling for SEO tools.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class ToolErrorHandler
{
    /**
     * Blocked URL patterns.
     */
    protected const BLOCKED_PATTERNS = [
        '/^localhost$/i',
        '/^127\./',
        '/^192\.168\./',
        '/^10\./',
        '/^172\.(1[6-9]|2[0-9]|3[01])\./',
        '/^::1$/',
        '/^fc00:/i',
        '/\.local$/i',
        '/\.internal$/i',
        '/\.localhost$/i',
    ];

    /**
     * Maximum URL length.
     */
    protected const MAX_URL_LENGTH = 2048;

    /**
     * Create an error from an exception.
     */
    public function handleException(Throwable $e, array $context = []): ToolError
    {
        $code = $this->mapExceptionToCode($e);
        
        $context['exception_class'] = get_class($e);
        $context['exception_message'] = $e->getMessage();
        
        return ToolError::fromCode($code, $context, $this->getUserMessage($e));
    }

    /**
     * Create an error for invalid URL.
     */
    public function invalidUrl(?string $url, string $reason = 'Invalid URL format'): ToolError
    {
        return ToolError::fromCode(ToolError::INVALID_URL, [
            'url' => $this->sanitizeUrl($url),
            'reason' => $reason,
        ]);
    }

    /**
     * Create an error for blocked URL.
     */
    public function blockedUrl(string $url): ToolError
    {
        return ToolError::fromCode(ToolError::URL_BLOCKED, [
            'url' => $this->sanitizeUrl($url),
            'reason' => 'URL is in blocked list',
        ]);
    }

    /**
     * Create an error for fetch failure.
     */
    public function fetchFailed(string $url, ?string $details = null): ToolError
    {
        return ToolError::fromCode(ToolError::FETCH_FAILED, [
            'url' => $this->sanitizeUrl($url),
            'details' => $details,
        ]);
    }

    /**
     * Create an error for timeout.
     */
    public function timeout(string $url, int $seconds = 30): ToolError
    {
        return ToolError::fromCode(ToolError::TIMEOUT, [
            'url' => $this->sanitizeUrl($url),
            'timeout_seconds' => $seconds,
        ]);
    }

    /**
     * Create an error for rate limiting.
     */
    public function rateLimited(int $retryAfter = 60): ToolError
    {
        return ToolError::fromCode(ToolError::RATE_LIMITED, [
            'retry_after' => $retryAfter,
        ]);
    }

    /**
     * Create an error for parse failure.
     */
    public function parseError(string $url, ?string $details = null): ToolError
    {
        return ToolError::fromCode(ToolError::PARSE_ERROR, [
            'url' => $this->sanitizeUrl($url),
            'details' => $details,
        ]);
    }

    /**
     * Create a generic tool error.
     */
    public function toolError(string $message, array $context = []): ToolError
    {
        return ToolError::fromCode(ToolError::TOOL_ERROR, $context, $message);
    }

    /**
     * Validate URL and return error if invalid.
     */
    public function validateUrl(?string $url): ?ToolError
    {
        // Check for empty URL
        if (empty($url)) {
            return $this->invalidUrl($url, 'URL is required');
        }

        // Check URL length
        if (strlen($url) > self::MAX_URL_LENGTH) {
            return $this->invalidUrl($url, 'URL is too long');
        }

        // Parse URL
        $parsed = parse_url($url);
        
        // Check for valid scheme
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return $this->invalidUrl($url, 'URL must start with http:// or https://');
        }

        // Check for valid host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            return $this->invalidUrl($url, 'URL must have a valid host');
        }

        // Check for blocked URLs
        if ($this->isBlockedUrl($parsed['host'])) {
            return $this->blockedUrl($url);
        }

        return null;
    }

    /**
     * Check if URL is blocked.
     */
    public function isBlockedUrl(string $host): bool
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log error with context.
     */
    public function logError(ToolError $error, ?Request $request = null): void
    {
        $logContext = [
            'error_code' => $error->code,
            'request_id' => $error->requestId,
            'http_status' => $error->httpStatus,
            'retryable' => $error->retryable,
        ];

        // Add request context
        if ($request) {
            $logContext['ip'] = $request->ip();
            $logContext['user_agent'] = $request->userAgent();
            $logContext['url'] = $request->fullUrl();
            $logContext['method'] = $request->method();
        }

        // Add error context (sanitized)
        $logContext['error_context'] = $this->sanitizeContext($error->context);

        // Log at appropriate level
        if ($error->httpStatus >= 500) {
            Log::error('Tool error: ' . $error->message, $logContext);
        } elseif ($error->httpStatus >= 400) {
            Log::warning('Tool error: ' . $error->message, $logContext);
        } else {
            Log::info('Tool error: ' . $error->message, $logContext);
        }
    }

    /**
     * Check if error is retryable.
     */
    public function isRetryable(ToolError $error): bool
    {
        return $error->isRetryable();
    }

    /**
     * Get retry delay.
     */
    public function getRetryDelay(ToolError $error, int $attempt = 1): int
    {
        return $error->getRetryDelay($attempt);
    }

    /**
     * Map exception to error code.
     */
    protected function mapExceptionToCode(Throwable $e): string
    {
        $message = $e->getMessage();
        $class = get_class($e);

        // Connection errors
        if (str_contains($class, 'Connect') || str_contains($message, 'Connection')) {
            return ToolError::FETCH_FAILED;
        }

        // Timeout errors
        if (str_contains($class, 'Timeout') || str_contains($message, 'timeout')) {
            return ToolError::TIMEOUT;
        }

        // DNS errors
        if (str_contains($message, 'DNS') || str_contains($message, 'resolve')) {
            return ToolError::FETCH_FAILED;
        }

        // SSL errors
        if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
            return ToolError::FETCH_FAILED;
        }

        // Parse errors
        if (str_contains($class, 'Parse') || str_contains($message, 'parse')) {
            return ToolError::PARSE_ERROR;
        }

        // Rate limit
        if (str_contains($message, 'rate limit') || str_contains($message, 'Too many')) {
            return ToolError::RATE_LIMITED;
        }

        // Default to tool error
        return ToolError::TOOL_ERROR;
    }

    /**
     * Get user-friendly message from exception.
     */
    protected function getUserMessage(Throwable $e): ?string
    {
        // Return null to use default message
        return null;
    }

    /**
     * Sanitize URL for logging.
     */
    protected function sanitizeUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        // Remove query parameters that might contain sensitive data
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            $sensitiveParams = ['token', 'key', 'secret', 'password', 'auth', 'api_key', 'apikey'];
            foreach ($sensitiveParams as $param) {
                if (isset($params[$param])) {
                    $params[$param] = '[REDACTED]';
                }
            }
            $parsed['query'] = http_build_query($params);
        }

        return $this->buildUrl($parsed);
    }

    /**
     * Sanitize context for logging.
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'auth', 'api_key', 'apikey'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($context[$key])) {
                $context[$key] = '[REDACTED]';
            }
        }

        // Sanitize URL in context
        if (isset($context['url'])) {
            $context['url'] = $this->sanitizeUrl($context['url']);
        }

        return $context;
    }

    /**
     * Build URL from parsed parts.
     */
    protected function buildUrl(array $parts): string
    {
        $url = '';
        
        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }
        
        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':' . $parts['pass'];
            }
            $url .= '@';
        }
        
        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }
        
        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }
        
        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }
        
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }
        
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }
        
        return $url;
    }

    /**
     * Aggregate multiple errors.
     */
    public function aggregateErrors(array $errors): array
    {
        return array_map(fn(ToolError $error) => $error->toArray(), $errors);
    }

    /**
     * Create an error from error code.
     * 
     * @param string $code Error code (INVALID_URL, FETCH_FAILED, etc.)
     * @param array $context Additional context
     * @return ToolError
     */
    public function createError(string $code, array $context = []): ToolError
    {
        $customMessage = $context['message'] ?? null;
        unset($context['message']);
        
        return ToolError::fromCode($code, $context, $customMessage);
    }

    /**
     * Create error from exception.
     * 
     * @param Throwable $e The exception
     * @param array $context Additional context
     * @return ToolError
     */
    public function createFromException(Throwable $e, array $context = []): ToolError
    {
        return $this->handleException($e, $context);
    }

    /**
     * Convert error to response array.
     * 
     * @param ToolError $error
     * @return array
     */
    public function toResponse(ToolError $error): array
    {
        return $error->toResponse();
    }

    /**
     * Create multiple errors from array of error definitions.
     * 
     * @param array $errors Array of ['code' => string, 'context' => array]
     * @return array<ToolError>
     */
    public function createErrors(array $errors): array
    {
        return array_map(function ($errorDef) {
            return $this->createError($errorDef['code'], $errorDef['context'] ?? []);
        }, $errors);
    }
}
