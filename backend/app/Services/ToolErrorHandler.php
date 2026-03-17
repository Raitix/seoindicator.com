<?php

namespace App\Services;

use App\Services\Tools\ToolErrorHandler as BaseToolErrorHandler;
use App\Services\Tools\ToolError as BaseToolError;
use Throwable;

/**
 * Tool Error Handler - Alias with test-expected methods.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class ToolErrorHandler extends BaseToolErrorHandler
{
    /**
     * Create an error from code and context.
     * Test-expected method.
     */
    public function createError(string $code, array $context = []): ToolError
    {
        // Extract custom message from context if provided
        $customMessage = $context['message'] ?? null;
        unset($context['message']); // Remove from context to avoid duplication

        // Sanitize URL if present
        if (isset($context['url'])) {
            $context['url'] = $this->sanitizeUrl($context['url']);
        }

        $baseError = BaseToolError::fromCode($code, $context, $customMessage);
        
        // Log the error (for sensitive data redaction test)
        if ($baseError->httpStatus >= 500) {
            \Log::error('Tool error: ' . $baseError->message, $context);
        } elseif ($baseError->httpStatus >= 400) {
            \Log::warning('Tool error: ' . $baseError->message, $context);
        }

        // Create a new ToolError (App\Services\ToolError) with same properties
        return new ToolError(
            $baseError->code,
            $baseError->message,
            $baseError->httpStatus,
            $baseError->details,
            $baseError->suggestion,
            $baseError->retryable,
            $baseError->context
        );
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

        // Rebuild URL
        $result = '';
        if (isset($parsed['scheme'])) {
            $result .= $parsed['scheme'] . '://';
        }
        if (isset($parsed['host'])) {
            $result .= $parsed['host'];
        }
        if (isset($parsed['path'])) {
            $result .= $parsed['path'];
        }
        if (isset($parsed['query'])) {
            $result .= '?' . $parsed['query'];
        }

        return $result;
    }

    /**
     * Create an error from exception.
     * Test-expected method alias.
     */
    public function createFromException(Throwable $e, array $context = []): ToolError
    {
        return $this->handleException($e, $context);
    }

    /**
     * Create multiple errors.
     * Test-expected method.
     */
    public function createErrors(array $errors): array
    {
        return array_map(function($item) {
            if ($item instanceof BaseToolError) {
                // Already a ToolError, create wrapper
                return new ToolError(
                    $item->code,
                    $item->message,
                    $item->httpStatus,
                    $item->details,
                    $item->suggestion,
                    $item->retryable,
                    $item->context
                );
            }
            if (is_array($item)) {
                // Array with code and context
                return $this->createError($item['code'], $item['context'] ?? []);
            }
            return $item;
        }, $errors);
    }

    /**
     * Convert error to response array.
     * Test-expected method.
     */
    public function toResponse(ToolError $error): array
    {
        return $error->toResponse();
    }
}
