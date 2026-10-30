<?php

namespace App\Services;

use App\Services\Tools\RetryMechanism as BaseRetryMechanism;
use App\Services\ToolError;
use Closure;

/**
 * Retry Mechanism - Alias with test-expected methods.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class RetryMechanism extends BaseRetryMechanism
{
    /**
     * Last delay used.
     */
    protected int $lastDelay = 0;

    /**
     * Context for logging.
     */
    protected array $context = [];

    /**
     * Cancel callback.
     */
    protected ?Closure $cancelCallback = null;

    /**
     * Whether cancelled.
     */
    protected bool $cancelled = false;

    /**
     * Result of last successful operation.
     */
    protected mixed $result = null;

    /**
     * Create with config (test-expected static method).
     */
    public static function withConfig(array $config): self
    {
        return new self($config);
    }

    /**
     * Execute with options array (test-expected signature).
     */
    public function execute(Closure $operation, ?array $options = [], ?Closure $shouldRetry = null): mixed
    {
        // Apply options
        if (isset($options['maxAttempts'])) {
            $this->maxAttempts = $options['maxAttempts'];
        }
        if (isset($options['delay'])) {
            $this->baseDelay = $options['delay'];
        }

        $this->reset();
        $this->cancelled = false;
        $this->result = null;

        while ($this->attempt <= $this->maxAttempts) {
            // Check for cancellation
            if ($this->cancelled) {
                if ($this->cancelCallback) {
                    ($this->cancelCallback)();
                }
                throw new \RuntimeException('Retry cancelled');
            }

            try {
                $result = $operation($this->attempt);
                $this->result = $result;

                // Success - call callback and return
                if ($this->onSuccess) {
                    ($this->onSuccess)($result, $this->attempt);
                }

                return $result;

            } catch (ToolError $e) {
                $this->lastError = $e;
            } catch (\App\Exceptions\NonRetryableException $e) {
                throw $e;
            } catch (\Exception $e) {
                // Wrap in ToolError
                $handler = app(ToolErrorHandler::class);
                $this->lastError = $handler->handleException($e, $this->context);
            }

            // Check if we should retry
            if (!$this->shouldRetryWithError($this->lastError, $shouldRetry)) {
                break;
            }

            $this->attempt++;
            $this->isRetrying = true;

            // Calculate and store delay
            $this->lastDelay = $this->calculateDelay($this->attempt);

            // Call retry callback
            if ($this->onRetry) {
                ($this->onRetry)($this->lastError, $this->attempt);
            }

            // Wait before next attempt
            usleep($this->lastDelay * 1000);
        }

        // All retries failed
        if ($this->onFailure) {
            ($this->onFailure)($this->lastError, $this->attempt);
        }

        throw $this->lastError ?? new \RuntimeException('Retry mechanism failed');
    }

    /**
     * Check if should retry.
     */
    protected function shouldRetryWithError(ToolError $error, ?Closure $customCheck = null): bool
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
     * Calculate delay for attempt (test-expected method).
     */
    public function calculateDelay(int $attempt): int
    {
        // Use baseDelay from parent, with exponential backoff
        $multiplier = $this->enableJitter ? 2 : 1;
        $delay = $this->baseDelay * pow($multiplier, $attempt - 1);

        // Add jitter if enabled
        if ($this->enableJitter) {
            $jitterFactor = $this->context['jitterFactor'] ?? 0.1;
            $jitter = (int)($delay * $jitterFactor);
            $delay += rand(-$jitter, $jitter);
        }

        return (int) $delay;
    }

    /**
     * Get last delay used (test-expected method).
     */
    public function getLastDelay(): int
    {
        return $this->lastDelay;
    }

    /**
     * Get delay for specific error (test-expected method).
     */
    public function getDelayForError(ToolError $error, int $attempt): int
    {
        // If error has retry-after, use it
        if (isset($error->context['retry_after'])) {
            return $error->context['retry_after'] * 1000;
        }

        if (isset($error->retryAfter)) {
            return $error->retryAfter * 1000;
        }

        return $this->calculateDelay($attempt);
    }

    /**
     * Set context (test-expected method).
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Set cancel callback (test-expected method).
     */
    public function onCancel(Closure $callback): self
    {
        $this->cancelCallback = $callback;
        return $this;
    }

    /**
     * Cancel the retry operation.
     */
    public function cancel(): void
    {
        $this->cancelled = true;
    }

    /**
     * Get current state (test-expected method).
     */
    public function getState(): array
    {
        return [
            'totalAttempts' => $this->attempt,
            'isRetrying' => $this->isRetrying,
            'result' => $this->result,
            'lastError' => $this->lastError,
            'cancelled' => $this->cancelled,
        ];
    }

    /**
     * Get current attempt (test-expected alias).
     */
    public function getCurrentAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Reset state.
     */
    public function reset(): void
    {
        parent::reset();
        $this->lastDelay = 0;
        $this->result = null;
        $this->cancelled = false;
    }
}
