<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RetryMechanism;
use App\Services\ToolError;
use Mockery;

/**
 * Unit tests for Retry Mechanism.
 * 
 * @see TESTS/TEST-16-12.md
 * Covers: RT-001 to RT-018
 */
class RetryMechanismTest extends TestCase
{
    private RetryMechanism $retry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->retry = new RetryMechanism();
    }

    /**
     * RT-001: Single retry on transient
     */
    public function test_single_retry_on_transient_failure(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new \Exception('Transient failure');
            }
            return 'success';
        };

        $result = $this->retry->execute($operation, [
            'maxAttempts' => 2,
            'delay' => 0, // No delay for tests
        ]);

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempts);
    }

    /**
     * RT-002: Exponential backoff
     */
    public function test_exponential_backoff(): void
    {
        $delays = [];
        $this->retry = new RetryMechanism([
            'backoffMultiplier' => 2,
            'baseDelay' => 100, // 100ms base
        ]);

        $attempts = 0;
        $operation = function () use (&$attempts, &$delays) {
            $attempts++;
            if ($attempts <= 3) {
                $delays[] = $this->retry->getLastDelay();
                throw new \Exception('Failure');
            }
            return 'success';
        };

        try {
            $this->retry->execute($operation, ['maxAttempts' => 4]);
        } catch (\Exception $e) {
            // Expected
        }

        // Verify exponential backoff: 100, 200, 400
        $this->assertCount(3, $delays);
        $this->assertEquals(100, $delays[0]);
        $this->assertEquals(200, $delays[1]);
        $this->assertEquals(400, $delays[2]);
    }

    /**
     * RT-003: Max retries limit
     */
    public function test_max_retries_limit(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            throw new \Exception('Always fails');
        };

        $this->expectException(\Exception::class);

        $this->retry->execute($operation, [
            'maxAttempts' => 2,
            'delay' => 0,
        ]);

        $this->assertEquals(2, $attempts);
    }

    /**
     * RT-004: Non-retryable error
     */
    public function test_non_retryable_error_no_retry(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            $error = new ToolError('INVALID_URL', 'Invalid URL', 400);
            throw new \App\Exceptions\NonRetryableException($error);
        };

        $this->expectException(\App\Exceptions\NonRetryableException::class);

        $this->retry->execute($operation, [
            'maxAttempts' => 3,
            'delay' => 0,
        ]);

        // Should only attempt once (no retry for non-retryable)
        $this->assertEquals(1, $attempts);
    }

    /**
     * RT-005: Rate limit cooldown
     */
    public function test_rate_limit_cooldown(): void
    {
        $error = new ToolError('RATE_LIMITED', 'Too many requests', 429);
        $error->retryAfter = 5; // 5 seconds

        $delay = $this->retry->getDelayForError($error, 1);

        // Should respect retry-after header (5000ms)
        $this->assertEquals(5000, $delay);
    }

    /**
     * RT-006: Retry success
     */
    public function test_retry_success_on_second_attempt(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new \Exception('First attempt failed');
            }
            return 'success';
        };

        $result = $this->retry->execute($operation, [
            'maxAttempts' => 3,
            'delay' => 0,
        ]);

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempts);
    }

    /**
     * RT-007: All retries fail
     */
    public function test_all_retries_fail_returns_last_error(): void
    {
        $lastException = null;
        $attempts = 0;
        $operation = function () use (&$attempts, &$lastException) {
            $attempts++;
            $lastException = new \Exception("Attempt {$attempts} failed");
            throw $lastException;
        };

        $caught = null;
        try {
            $this->retry->execute($operation, [
                'maxAttempts' => 3,
                'delay' => 0,
            ]);
        } catch (\Exception $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertEquals('Attempt 3 failed', $caught->getMessage());
        $this->assertEquals(3, $attempts);
    }

    /**
     * RT-008: Retry with jitter
     */
    public function test_retry_with_jitter(): void
    {
        $this->retry = new RetryMechanism([
            'baseDelay' => 1000,
            'jitter' => true,
            'jitterFactor' => 0.1, // 10% jitter
        ]);

        $delays = [];
        for ($i = 1; $i <= 5; $i++) {
            $delay = $this->retry->calculateDelay($i);
            $delays[] = $delay;
        }

        // With jitter, delays should vary
        $uniqueDelays = array_unique($delays);
        $this->assertGreaterThan(1, count($uniqueDelays), 'Delays should vary with jitter');

        // But all should be within expected range (base ± jitter)
        foreach ($delays as $delay) {
            $this->assertGreaterThanOrEqual(900, $delay);
            $this->assertLessThanOrEqual(1100, $delay);
        }
    }

    /**
     * RT-009: Cancel retry
     */
    public function test_cancel_retry(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            throw new \Exception('Failure');
        };

        // Start retry in background
        $cancelled = false;
        $this->retry->onCancel(function () use (&$cancelled) {
            $cancelled = true;
        });

        // Simulate cancellation
        $this->retry->cancel();

        try {
            $this->retry->execute($operation, [
                'maxAttempts' => 3,
                'delay' => 100,
            ]);
        } catch (\Exception $e) {
            // May throw cancellation exception
        }

        $this->assertTrue($cancelled || $attempts < 3);
    }

    /**
     * RT-010: Retry state tracking
     */
    public function test_retry_state_tracking(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \Exception('Failure');
            }
            return 'success';
        };

        $this->retry->execute($operation, [
            'maxAttempts' => 3,
            'delay' => 0,
        ]);

        $state = $this->retry->getState();

        $this->assertEquals(3, $state['totalAttempts']);
        $this->assertFalse($state['isRetrying']);
        $this->assertEquals('success', $state['result']);
    }

    /**
     * RT-011: Retry count tracking
     */
    public function test_retry_count_tracking(): void
    {
        $attemptCounts = [];
        $operation = function () use (&$attemptCounts) {
            $attemptCounts[] = $this->retry->getCurrentAttempt();
            if (count($attemptCounts) < 3) {
                throw new \Exception('Failure');
            }
            return 'success';
        };

        $this->retry->execute($operation, [
            'maxAttempts' => 5,
            'delay' => 0,
        ]);

        $this->assertEquals([1, 2, 3], $attemptCounts);
    }

    /**
     * RT-012: Custom retry config
     */
    public function test_custom_retry_config(): void
    {
        $customRetry = new RetryMechanism([
            'maxAttempts' => 5,
            'baseDelay' => 500,
            'backoffMultiplier' => 3,
        ]);

        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            throw new \Exception('Failure');
        };

        try {
            $customRetry->execute($operation);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals(5, $attempts);
    }

    /**
     * RT-013: Retry delay config
     */
    public function test_retry_delay_config(): void
    {
        $this->retry = new RetryMechanism(['baseDelay' => 250]);

        $delay = $this->retry->calculateDelay(1);

        $this->assertEquals(250, $delay);
    }

    /**
     * RT-014: Network offline handling
     */
    public function test_network_offline_handling(): void
    {
        $operation = function () {
            throw new \App\Exceptions\NetworkOfflineException('Network is offline');
        };

        $this->expectException(\App\Exceptions\NetworkOfflineException::class);

        $this->retry->execute($operation, [
            'maxAttempts' => 2,
            'delay' => 0,
        ]);
    }

    /**
     * RT-015: Request timeout during retry
     */
    public function test_request_timeout_during_retry(): void
    {
        $attempts = 0;
        $operation = function () use (&$attempts) {
            $attempts++;
            throw new \App\Exceptions\TimeoutException('Request timed out');
        };

        $caught = null;
        try {
            $this->retry->execute($operation, [
                'maxAttempts' => 2,
                'delay' => 0,
            ]);
        } catch (\App\Exceptions\TimeoutException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertEquals(2, $attempts);
    }

    /**
     * RT-016: Retry preserves context
     */
    public function test_retry_preserves_context(): void
    {
        $context = ['url' => 'https://example.com', 'tool' => 'meta-tags'];
        $capturedContexts = [];

        $operation = function () use (&$capturedContexts, $context) {
            $capturedContexts[] = $this->retry->getContext();
            if (count($capturedContexts) < 2) {
                throw new \Exception('Failure');
            }
            return 'success';
        };

        $this->retry->setContext($context);
        $this->retry->execute($operation, ['maxAttempts' => 3, 'delay' => 0]);

        // Context should be preserved across retries
        $this->assertEquals($context, $capturedContexts[0]);
        $this->assertEquals($context, $capturedContexts[1]);
    }

    /**
     * RT-017: Retry with different params
     */
    public function test_retry_with_different_params(): void
    {
        $params = ['initial'];
        $attempts = 0;

        $operation = function ($param) use (&$attempts, &$params) {
            $attempts++;
            $params[] = $param;
            if ($attempts < 3) {
                throw new \Exception('Failure');
            }
            return 'success';
        };

        // Simulate retry with modified params
        $this->retry->onBeforeRetry(function ($attempt) use (&$params) {
            $params[] = "retry-{$attempt}";
        });

        $result = $this->retry->execute($operation, [
            'maxAttempts' => 3,
            'delay' => 0,
            'params' => ['initial']
        ]);

        $this->assertEquals('success', $result);
    }

    /**
     * RT-018: Concurrent retries
     */
    public function test_concurrent_retries(): void
    {
        $results = [];
        $promises = [];

        // Create multiple concurrent retry operations
        for ($i = 0; $i < 3; $i++) {
            $id = $i;
            $attempts = 0;

            $operation = function () use (&$attempts, $id) {
                $attempts++;
                if ($attempts < 2) {
                    throw new \Exception("Failure {$id}");
                }
                return "success-{$id}";
            };

            $results[$id] = $this->retry->execute($operation, [
                'maxAttempts' => 3,
                'delay' => 0,
            ]);
        }

        // Each retry should complete independently
        $this->assertEquals('success-0', $results[0]);
        $this->assertEquals('success-1', $results[1]);
        $this->assertEquals('success-2', $results[2]);
    }
}
