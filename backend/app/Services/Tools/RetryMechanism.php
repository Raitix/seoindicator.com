<?php

namespace App\Services\Tools;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Retry Mechanism Service.
 * 
 * Handles retry logic with exponential backoff.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class RetryMechanism
{
    /**
     * Default maximum retry attempts.
     */
    protected const DEFAULT_MAX_ATTEMPTS = 2;

    /**
     * Default base delay in milliseconds.
     */
    protected const DEFAULT_BASE_DELAY = 1000;

    /**
     * Maximum jitter in milliseconds.
     */
    protected const MAX_JITTER = 200;

    /**
     * Current retry state.
     */
    protected int $attempt = 0;
    protected bool $isRetrying = false;
    protected ?ToolError $lastError = null;
    protected ?int $lastDelay = null;
    protected mixed $lastResult = null;
    protected array $context = [];
    protected bool $cancelled = false;

    /**
     * Configuration.
     */
    protected int $maxAttempts;
    protected int $baseDelay;
    protected bool $enableJitter;
    protected ?Closure $onRetry = null;
    protected ?Closure $onSuccess = null;
    protected ?Closure $onFailure = null;
    protected ?Closure $onCancelCallback = null;
    protected ?Closure $onBeforeRetryCallback = null;

    public function __construct(array $config = [])
    {
        $this->maxAttempts = $config['max_attempts'] ?? self::DEFAULT_MAX_ATTEMPTS;
        $this->baseDelay = $config['base_delay'] ?? self::DEFAULT_BASE_DELAY;
        $this->enableJitter = $config['jitter'] ?? true;
    }

    /**
     * Execute an operation with retry logic.
     */
    public function execute(Closure $operation, ?Closure $shouldRetry = null): mixed
    {
        $this->reset();

        while ($this->attempt <= $this->maxAttempts) {
            try {
                $result = $operation($this->attempt);

                // Success - call callback and return
                $this->lastResult = $result;
                $this->isRetrying = false; // Reset retrying flag on success
                if ($this->onSuccess) {
                    ($this->onSuccess)($result, $this->attempt);
                }

                return $result;

            } catch (ToolError $e) {
                $this->lastError = $e;
            } catch (\Exception $e) {
                // Wrap in ToolError
                $handler = app(ToolErrorHandler::class);
                $this->lastError = $handler->handleException($e);
            }

            // Check if we should retry
            if (!$this->shouldRetry($this->lastError, $shouldRetry)) {
                break;
            }

            // Check if cancelled
            if ($this->cancelled) {
                break;
            }

            $this->attempt++;
            $this->isRetrying = true;

            // Call retry callback
            if ($this->onRetry) {
                ($this->onRetry)($this->lastError, $this->attempt);
            }

            // Call before retry callback
            if ($this->onBeforeRetryCallback) {
                ($this->onBeforeRetryCallback)($this->attempt);
            }

            // Wait before next attempt
            $this->lastDelay = $this->getDelay();
            usleep($this->lastDelay * 1000);
        }

        // All retries failed
        if ($this->onFailure) {
            ($this->onFailure)($this->lastError, $this->attempt);
        }

        // Convert ToolError to a throwable exception
        if ($this->lastError instanceof ToolError) {
            throw new \RuntimeException(
                $this->lastError->message . ' (Code: ' . $this->lastError->code . ')',
                $this->lastError->httpStatus
            );
        }

        throw new \RuntimeException('Retry mechanism failed');
    }

    /**
     * Check if should retry.
     */
    protected function shouldRetry(ToolError $error, ?Closure $customCheck = null): bool
    {
        // Check if max attempts reached
        if ($this->attempt >= $this->maxAttempts) {
            return false;
        }

        // Check if error is retryable
        if (!$error->isRetryable()) {
            return false;
        }

        // Use custom check if provided
        if ($customCheck) {
            return $customCheck($error, $this->attempt);
        }

        return true;
    }

    /**
     * Get delay for next retry.
     */
    public function getDelay(): int
    {
        // Exponential backoff: base * 2^attempt
        $delay = $this->baseDelay * pow(2, $this->attempt);

        // Add jitter to prevent thundering herd
        if ($this->enableJitter) {
            $delay += rand(0, self::MAX_JITTER);
        }

        return (int) $delay;
    }

    /**
     * Get current attempt number.
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Check if currently retrying.
     */
    public function isRetrying(): bool
    {
        return $this->isRetrying;
    }

    /**
     * Get last error.
     */
    public function getLastError(): ?ToolError
    {
        return $this->lastError;
    }

    /**
     * Set callback for retry events.
     */
    public function onRetry(Closure $callback): self
    {
        $this->onRetry = $callback;
        return $this;
    }

    /**
     * Set callback for success events.
     */
    public function onSuccess(Closure $callback): self
    {
        $this->onSuccess = $callback;
        return $this;
    }

    /**
     * Set callback for failure events.
     */
    public function onFailure(Closure $callback): self
    {
        $this->onFailure = $callback;
        return $this;
    }

    /**
     * Reset retry state.
     */
    public function reset(): void
    {
        $this->attempt = 0;
        $this->isRetrying = false;
        $this->lastError = null;
        $this->lastDelay = null;
        $this->lastResult = null;
        $this->cancelled = false;
    }

    /**
     * Create with custom config.
     */
    public static function withConfig(array $config): self
    {
        return new self($config);
    }

    /**
     * Create for rate-limited requests.
     */
    public static function forRateLimit(int $retryAfter = 60): self
    {
        return new self([
            'max_attempts' => 1,
            'base_delay' => $retryAfter * 1000,
            'jitter' => false,
        ]);
    }

    /**
     * Get the last delay used.
     * 
     * @return int Delay in milliseconds
     */
    public function getLastDelay(): int
    {
        return $this->lastDelay ?? 0;
    }

    /**
     * Calculate delay for a specific attempt.
     * 
     * @param int $attempt Attempt number
     * @return int Delay in milliseconds
     */
    public function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelay * pow(2, $attempt - 1);

        if ($this->enableJitter) {
            $delay += rand(0, self::MAX_JITTER);
        }

        return (int) $delay;
    }

    /**
     * Set execution context.
     * 
     * @param array $context Context data
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get execution context.
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context ?? [];
    }

    /**
     * Set cancellation callback.
     * 
     * @param Closure $callback
     * @return self
     */
    public function onCancel(Closure $callback): self
    {
        $this->onCancelCallback = $callback;
        return $this;
    }

    /**
     * Cancel the retry operation.
     */
    public function cancel(): void
    {
        $this->cancelled = true;
        if ($this->onCancelCallback) {
            ($this->onCancelCallback)();
        }
    }

    /**
     * Get delay for a specific error and attempt.
     * 
     * @param ToolError $error
     * @param int $attempt
     * @return int Delay in milliseconds
     */
    public function getDelayForError(ToolError $error, int $attempt): int
    {
        // For rate-limited errors, use retry-after header
        if ($error->code === ToolError::RATE_LIMITED && isset($error->context['retry_after'])) {
            return $error->context['retry_after'] * 1000;
        }

        return $this->calculateDelay($attempt);
    }

    /**
     * Get current retry state.
     * 
     * @return array
     */
    public function getState(): array
    {
        return [
            'totalAttempts' => $this->attempt,
            'isRetrying' => $this->isRetrying,
            'result' => $this->lastResult ?? null,
            'lastError' => $this->lastError?->toArray(),
        ];
    }

    /**
     * Get current attempt number.
     * 
     * @return int
     */
    public function getCurrentAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Set callback for before retry.
     * 
     * @param Closure $callback
     * @return self
     */
    public function onBeforeRetry(Closure $callback): self
    {
        $this->onBeforeRetryCallback = $callback;
        return $this;
    }
}
