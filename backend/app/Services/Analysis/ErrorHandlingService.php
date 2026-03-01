<?php

namespace App\Services\Analysis;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ErrorHandlingService - Comprehensive error handling for SEO analysis.
 *
 * Provides error categorization, user-friendly messages, and recovery
 * mechanisms for the analysis system.
 *
 * @see TASK-19-13
 */
class ErrorHandlingService
{
    // Error categories
    public const CATEGORY_NETWORK = 'network';
    public const CATEGORY_TIMEOUT = 'timeout';
    public const CATEGORY_RATE_LIMIT = 'rate_limit';
    public const CATEGORY_INVALID_URL = 'invalid_url';
    public const CATEGORY_MEMORY = 'memory';
    public const CATEGORY_PARSING = 'parsing';
    public const CATEGORY_SSRF = 'ssrf';
    public const CATEGORY_UNKNOWN = 'unknown';

    // Retry configuration
    public const MAX_RETRIES = 1;
    public const RATE_LIMIT_BACKOFF_SECONDS = 30;
    public const TIMEOUT_BACKOFF_SECONDS = 10;

    /**
     * Categorize an error based on its type and message.
     *
     * @param \Throwable $error
     * @return string Error category constant
     */
    public function categorizeError(\Throwable $error): string
    {
        $message = strtolower($error->getMessage());

        // Check for timeout errors
        if ($this->isTimeoutError($error, $message)) {
            return self::CATEGORY_TIMEOUT;
        }

        // Check for rate limiting
        if ($this->isRateLimitError($error, $message)) {
            return self::CATEGORY_RATE_LIMIT;
        }

        // Check for SSRF/URL validation errors
        if ($this->isSSRFError($error, $message)) {
            return self::CATEGORY_SSRF;
        }

        // Check for invalid URL errors
        if ($this->isInvalidURLError($error, $message)) {
            return self::CATEGORY_INVALID_URL;
        }

        // Check for memory errors
        if ($this->isMemoryError($error, $message)) {
            return self::CATEGORY_MEMORY;
        }

        // Check for network errors
        if ($this->isNetworkError($error, $message)) {
            return self::CATEGORY_NETWORK;
        }

        // Check for parsing errors
        if ($this->isParsingError($error, $message)) {
            return self::CATEGORY_PARSING;
        }

        return self::CATEGORY_UNKNOWN;
    }

    /**
     * Get a user-friendly error message.
     *
     * @param \Throwable $error
     * @param string|null $category Pre-categorized error type
     * @return string User-friendly message
     */
    public function getUserFriendlyMessage(\Throwable $error, ?string $category = null): string
    {
        $category = $category ?? $this->categorizeError($error);

        return match ($category) {
            self::CATEGORY_TIMEOUT => 'The analysis took too long to complete. Please try again.',
            self::CATEGORY_RATE_LIMIT => 'The target website is temporarily limiting requests. Please try again later.',
            self::CATEGORY_INVALID_URL => 'The provided URL is not valid. Please check the URL and try again.',
            self::CATEGORY_SSRF => 'Access to this URL is not allowed for security reasons.',
            self::CATEGORY_MEMORY => 'The analysis ran out of memory. Please try with a simpler page.',
            self::CATEGORY_NETWORK => 'Unable to connect to the website. Please check if the site is accessible.',
            self::CATEGORY_PARSING => 'The page content could not be analyzed. The page may have unusual formatting.',
            default => 'An unexpected error occurred during analysis. Please try again.',
        };
    }

    /**
     * Get error details for logging.
     *
     * @param \Throwable $error
     * @param array $context Additional context (url, indicator, job_id, etc.)
     * @return array Structured error details
     */
    public function getErrorDetails(\Throwable $error, array $context = []): array
    {
        $category = $this->categorizeError($error);

        return [
            'category' => $category,
            'message' => $error->getMessage(),
            'user_message' => $this->getUserFriendlyMessage($error, $category),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $this->getSanitizedTrace($error),
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'should_retry' => $this->shouldRetry($category),
        ];
    }

    /**
     * Log an analysis error with full context.
     *
     * @param \Throwable $error
     * @param array $context Additional context
     * @return void
     */
    public function logError(\Throwable $error, array $context = []): void
    {
        $details = $this->getErrorDetails($error, $context);

        $logContext = [
            'category' => $details['category'],
            'url' => $context['url'] ?? null,
            'job_id' => $context['job_id'] ?? null,
            'indicator_slug' => $context['indicator_slug'] ?? null,
            'user_message' => $details['user_message'],
            'should_retry' => $details['should_retry'],
        ];

        // Use appropriate log level based on category
        if (in_array($details['category'], [self::CATEGORY_NETWORK, self::CATEGORY_TIMEOUT, self::CATEGORY_RATE_LIMIT])) {
            Log::warning('Analysis error: ' . $details['category'], $logContext);
        } else {
            Log::error('Analysis error: ' . $details['category'], array_merge($logContext, [
                'error_message' => $details['message'],
                'file' => $details['file'],
                'line' => $details['line'],
            ]));
        }

        // Log full details to dedicated analysis error channel
        Log::channel('analysis-errors')->error('Analysis error details', $details);
    }

    /**
     * Determine if an error should trigger a retry.
     *
     * @param string $category Error category
     * @return bool
     */
    public function shouldRetry(string $category): bool
    {
        return in_array($category, [
            self::CATEGORY_TIMEOUT,
            self::CATEGORY_RATE_LIMIT,
            self::CATEGORY_NETWORK,
        ]);
    }

    /**
     * Get backoff time in seconds for retry.
     *
     * @param string $category Error category
     * @param int $attempt Current attempt number
     * @return int Seconds to wait before retry
     */
    public function getBackoffSeconds(string $category, int $attempt = 1): int
    {
        return match ($category) {
            self::CATEGORY_RATE_LIMIT => self::RATE_LIMIT_BACKOFF_SECONDS * $attempt,
            self::CATEGORY_TIMEOUT => self::TIMEOUT_BACKOFF_SECONDS * $attempt,
            default => 0,
        };
    }

    /**
     * Handle an indicator failure with proper logging and state updates.
     *
     * @param int $jobId
     * @param string $indicatorSlug
     * @param \Throwable $error
     * @param ProgressService $progressService
     * @param array $context Additional context
     * @return void
     */
    public function handleIndicatorFailure(
        int $jobId,
        string $indicatorSlug,
        \Throwable $error,
        ProgressService $progressService,
        array $context = []
    ): void {
        $category = $this->categorizeError($error);
        $userMessage = $this->getUserFriendlyMessage($error, $category);

        // Log the error
        $this->logError($error, array_merge($context, [
            'job_id' => $jobId,
            'indicator_slug' => $indicatorSlug,
        ]));

        // Mark the indicator as failed
        $progressService->markFailed($jobId, $indicatorSlug, $userMessage);
    }

    /**
     * Check if a job can continue with partial results.
     *
     * @param AnalysisJob $job
     * @return bool
     */
    public function canContinueWithPartialResults(AnalysisJob $job): bool
    {
        // Job can continue if:
        // 1. At least one indicator has completed or failed (not just pending/running)
        // 2. The job hasn't been cancelled
        $processedCount = $job->completed_indicators + $job->failed_indicators;

        return $processedCount > 0 && !$job->isCancelled();
    }

    /**
     * Get a summary of partial job completion.
     *
     * @param AnalysisJob $job
     * @return array Partial completion summary
     */
    public function getPartialCompletionSummary(AnalysisJob $job): array
    {
        $results = $job->results()->get();

        $completed = $results->where('status', AnalysisJobResult::STATUS_COMPLETED);
        $failed = $results->where('status', AnalysisJobResult::STATUS_FAILED);

        $averageScore = $completed->isNotEmpty()
            ? $completed->avg('percentage')
            : null;

        return [
            'total_indicators' => $job->total_indicators,
            'completed_count' => $completed->count(),
            'failed_count' => $failed->count(),
            'average_score' => $averageScore !== null ? round($averageScore, 1) : null,
            'failed_indicators' => $failed->map(fn ($r) => [
                'slug' => $r->indicator_slug,
                'name' => $r->indicator_name,
                'error' => $r->error_message,
            ])->values()->toArray(),
            'is_partial' => $failed->count() > 0,
            'success_rate' => $job->total_indicators > 0
                ? round(($completed->count() / $job->total_indicators) * 100, 1)
                : 0,
        ];
    }

    /**
     * Check if memory usage is approaching the limit.
     *
     * @param int $thresholdPercent Percentage of memory limit (default 85%)
     * @return bool
     */
    public function isMemoryApproachingLimit(int $thresholdPercent = 85): bool
    {
        $usage = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        if ($limit <= 0) {
            return false;
        }

        return ($usage / $limit) * 100 >= $thresholdPercent;
    }

    /**
     * Get current memory usage in bytes.
     *
     * @return int
     */
    public function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }

    /**
     * Get memory limit in bytes.
     *
     * @return int
     */
    public function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        return $this->convertToBytes($limit);
    }

    /**
     * Check if an error is a timeout error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isTimeoutError(\Throwable $error, string $message): bool
    {
        // Guzzle timeout exceptions
        if ($error instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }

        // Check for timeout in message
        $timeoutKeywords = ['timeout', 'timed out', 'time out', 'connection timed out'];
        foreach ($timeoutKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        // Check error class names
        $errorClass = get_class($error);
        if (str_contains($errorClass, 'Timeout') || str_contains($errorClass, 'TimedOut')) {
            return true;
        }

        return false;
    }

    /**
     * Check if an error is a rate limit error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isRateLimitError(\Throwable $error, string $message): bool
    {
        // Check for HTTP 429 in Guzzle exceptions
        if ($error instanceof \GuzzleHttp\Exception\RequestException) {
            $response = $error->getResponse();
            if ($response && $response->getStatusCode() === 429) {
                return true;
            }
        }

        // Check for rate limit keywords
        $rateLimitKeywords = ['429', 'rate limit', 'too many requests', 'rate_limit'];
        foreach ($rateLimitKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an error is an SSRF protection error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isSSRFError(\Throwable $error, string $message): bool
    {
        $ssrfKeywords = ['ssrf', 'blocked for security', 'private ip', 'not allowed for security', 'redirect blocked'];
        foreach ($ssrfKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an error is an invalid URL error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isInvalidURLError(\Throwable $error, string $message): bool
    {
        $urlKeywords = ['invalid url', 'invalid domain', 'url is required', 'malformed url', 'dns resolution failed'];
        foreach ($urlKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        // Check for RuntimeException with URL validation message
        if ($error instanceof RuntimeException) {
            if (str_contains($message, 'url') || str_contains($message, 'domain')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an error is a memory error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isMemoryError(\Throwable $error, string $message): bool
    {
        $memoryKeywords = ['memory', 'out of memory', 'allowed memory', 'exhausted'];
        foreach ($memoryKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        // Check for specific error types
        $errorClass = get_class($error);
        if (str_contains($errorClass, 'Memory')) {
            return true;
        }

        return false;
    }

    /**
     * Check if an error is a network error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isNetworkError(\Throwable $error, string $message): bool
    {
        // Guzzle network exceptions
        if ($error instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }

        if ($error instanceof \GuzzleHttp\Exception\TransferException) {
            return true;
        }

        $networkKeywords = ['connection refused', 'network', 'dns', 'resolve', 'curl error', 'connection reset', 'socket'];
        foreach ($networkKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an error is a parsing error.
     *
     * @param \Throwable $error
     * @param string $message
     * @return bool
     */
    private function isParsingError(\Throwable $error, string $message): bool
    {
        $parsingKeywords = ['invalid html', 'parse error', 'domdocument', 'malformed', 'xml'];
        foreach ($parsingKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        // Check for DOM-related exceptions
        $errorClass = get_class($error);
        if (str_contains($errorClass, 'DOM')) {
            return true;
        }

        return false;
    }

    /**
     * Get a sanitized stack trace for logging.
     *
     * @param \Throwable $error
     * @return array
     */
    private function getSanitizedTrace(\Throwable $error): array
    {
        $trace = $error->getTrace();
        $sanitized = [];

        foreach (array_slice($trace, 0, 10) as $frame) {
            $sanitized[] = [
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '[unknown]',
                'class' => $frame['class'] ?? null,
            ];
        }

        return $sanitized;
    }

    /**
     * Convert PHP memory string to bytes.
     *
     * @param string $memoryString
     * @return int
     */
    private function convertToBytes(string $memoryString): int
    {
        $memoryString = trim($memoryString);
        $last = strtolower($memoryString[strlen($memoryString) - 1]);
        $value = (int) $memoryString;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
