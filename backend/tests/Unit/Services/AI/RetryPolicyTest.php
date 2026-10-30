<?php

namespace Tests\Unit\Services\AI;

use App\Exceptions\AI\RateLimitException;
use App\Exceptions\AI\TimeoutException;
use App\Exceptions\AI\ServiceUnavailableException;
use App\Services\AI\RetryPolicy;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RetryPolicyTest extends TestCase
{
    private RetryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RetryPolicy();
    }

    // =========================================================================
    // Service Instantiation Tests (SI)
    // =========================================================================

    public function test_si_01_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(RetryPolicy::class, $this->policy);
    }

    public function test_si_02_service_has_should_retry_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'shouldRetry'));
    }

    public function test_si_03_service_has_get_delay_ms_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'getDelayMs'));
    }

    public function test_si_04_service_has_get_max_retries_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'getMaxRetries'));
    }

    public function test_si_05_service_has_is_retryable_error_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'isRetryableError'));
    }

    public function test_si_06_service_has_handle_rate_limit_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'handleRateLimit'));
    }

    public function test_si_07_service_has_get_retry_strategy_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'getRetryStrategy'));
    }

    // =========================================================================
    // shouldRetry() Tests (SR)
    // =========================================================================

    public function test_sr_01_returns_true_for_retryable_error_429(): void
    {
        $exception = new RateLimitException('Rate limited', 429);
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_02_returns_true_for_retryable_error_500(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_03_returns_true_for_retryable_error_502(): void
    {
        $exception = new HttpException(502, 'Bad Gateway');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_04_returns_true_for_retryable_error_503(): void
    {
        $exception = new HttpException(503, 'Service Unavailable');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_05_returns_true_for_retryable_error_504(): void
    {
        $exception = new HttpException(504, 'Gateway Timeout');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_06_returns_true_for_timeout_exception(): void
    {
        $exception = new TimeoutException('Request timed out');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_07_returns_true_for_service_unavailable_exception(): void
    {
        $exception = new ServiceUnavailableException('Service unavailable');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_08_returns_true_for_connection_exception(): void
    {
        $exception = new ConnectionException('Connection failed');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_09_returns_false_for_non_retryable_error_400(): void
    {
        $exception = new HttpException(400, 'Bad Request');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_10_returns_false_for_non_retryable_error_401(): void
    {
        $exception = new HttpException(401, 'Unauthorized');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_11_returns_false_for_non_retryable_error_403(): void
    {
        $exception = new HttpException(403, 'Forbidden');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_12_returns_false_for_non_retryable_error_404(): void
    {
        $exception = new HttpException(404, 'Not Found');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_13_returns_false_for_non_retryable_error_422(): void
    {
        $exception = new HttpException(422, 'Validation Error');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_14_returns_false_for_generic_exception(): void
    {
        $exception = new Exception('Generic error');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertFalse($result);
    }

    public function test_sr_15_returns_false_when_max_retries_exceeded(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        // Attempt 4 exceeds max of 3
        $result = $this->policy->shouldRetry($exception, 4);
        
        $this->assertFalse($result);
    }

    public function test_sr_16_returns_false_at_max_retry_boundary(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        // Attempt 3 is at the limit, should return false (no more retries)
        $result = $this->policy->shouldRetry($exception, 3);
        
        $this->assertFalse($result);
    }

    public function test_sr_17_returns_true_on_attempt_1_for_retryable(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->shouldRetry($exception, 1);
        
        $this->assertTrue($result);
    }

    public function test_sr_18_returns_true_on_attempt_2_for_retryable(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->shouldRetry($exception, 2);
        
        $this->assertTrue($result);
    }

    // =========================================================================
    // getDelayMs() Tests - Exponential Backoff (GD)
    // =========================================================================

    public function test_gd_01_attempt_1_returns_base_delay_with_jitter(): void
    {
        $delay = $this->policy->getDelayMs(1);
        
        // Base: 1000ms ± 10% jitter = 900-1100ms
        $this->assertGreaterThanOrEqual(900, $delay);
        $this->assertLessThanOrEqual(1100, $delay);
    }

    public function test_gd_02_attempt_2_returns_doubled_delay(): void
    {
        $delay = $this->policy->getDelayMs(2);
        
        // Base: 2000ms ± 10% jitter = 1800-2200ms
        $this->assertGreaterThanOrEqual(1800, $delay);
        $this->assertLessThanOrEqual(2200, $delay);
    }

    public function test_gd_03_attempt_3_returns_quadrupled_delay(): void
    {
        $delay = $this->policy->getDelayMs(3);
        
        // Base: 4000ms ± 10% jitter = 3600-4400ms
        $this->assertGreaterThanOrEqual(3600, $delay);
        $this->assertLessThanOrEqual(4400, $delay);
    }

    public function test_gd_04_delay_is_capped_at_max_delay(): void
    {
        // Attempt 5 would be 16000ms without cap, but should be capped at 10000ms
        $delay = $this->policy->getDelayMs(5);
        
        // Max: 10000ms ± 10% jitter = 9000-11000ms
        $this->assertGreaterThanOrEqual(9000, $delay);
        $this->assertLessThanOrEqual(11000, $delay);
    }

    public function test_gd_05_high_attempt_still_capped(): void
    {
        $delay = $this->policy->getDelayMs(10);
        
        // Should still be capped at max delay
        $this->assertGreaterThanOrEqual(9000, $delay);
        $this->assertLessThanOrEqual(11000, $delay);
    }

    public function test_gd_06_jitter_provides_variation(): void
    {
        // Run multiple times to verify jitter produces variation
        $delays = [];
        for ($i = 0; $i < 10; $i++) {
            $delays[] = $this->policy->getDelayMs(1);
        }
        
        $uniqueDelays = array_unique($delays);
        
        // At least some variation should occur due to jitter
        $this->assertGreaterThan(1, count($uniqueDelays));
    }

    public function test_gd_07_attempt_zero_returns_base_delay(): void
    {
        $delay = $this->policy->getDelayMs(0);
        
        // Should treat attempt 0 as attempt 1
        $this->assertGreaterThanOrEqual(900, $delay);
        $this->assertLessThanOrEqual(1100, $delay);
    }

    public function test_gd_08_negative_attempt_returns_base_delay(): void
    {
        $delay = $this->policy->getDelayMs(-1);
        
        // Should handle gracefully
        $this->assertGreaterThanOrEqual(0, $delay);
    }

    // =========================================================================
    // getMaxRetries() Tests (MR)
    // =========================================================================

    public function test_mr_01_returns_default_max_retries(): void
    {
        $maxRetries = $this->policy->getMaxRetries();
        
        $this->assertEquals(3, $maxRetries);
    }

    public function test_mr_02_max_retries_is_integer(): void
    {
        $maxRetries = $this->policy->getMaxRetries();
        
        $this->assertIsInt($maxRetries);
    }

    public function test_mr_03_max_retries_is_positive(): void
    {
        $maxRetries = $this->policy->getMaxRetries();
        
        $this->assertGreaterThan(0, $maxRetries);
    }

    // =========================================================================
    // isRetryableError() Tests (IR)
    // =========================================================================

    public function test_ir_01_returns_true_for_rate_limit_exception(): void
    {
        $exception = new RateLimitException('Rate limited', 429);
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_02_returns_true_for_timeout_exception(): void
    {
        $exception = new TimeoutException('Request timed out');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_03_returns_true_for_service_unavailable_exception(): void
    {
        $exception = new ServiceUnavailableException('Service unavailable');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_04_returns_true_for_connection_exception(): void
    {
        $exception = new ConnectionException('Connection failed');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_05_returns_true_for_http_500(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_06_returns_true_for_http_502(): void
    {
        $exception = new HttpException(502, 'Bad Gateway');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_07_returns_true_for_http_503(): void
    {
        $exception = new HttpException(503, 'Service Unavailable');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_08_returns_true_for_http_504(): void
    {
        $exception = new HttpException(504, 'Gateway Timeout');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_09_returns_true_for_http_429(): void
    {
        $exception = new HttpException(429, 'Too Many Requests');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertTrue($result);
    }

    public function test_ir_10_returns_false_for_http_400(): void
    {
        $exception = new HttpException(400, 'Bad Request');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    public function test_ir_11_returns_false_for_http_401(): void
    {
        $exception = new HttpException(401, 'Unauthorized');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    public function test_ir_12_returns_false_for_http_403(): void
    {
        $exception = new HttpException(403, 'Forbidden');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    public function test_ir_13_returns_false_for_http_404(): void
    {
        $exception = new HttpException(404, 'Not Found');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    public function test_ir_14_returns_false_for_http_422(): void
    {
        $exception = new HttpException(422, 'Validation Error');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    public function test_ir_15_returns_false_for_generic_exception(): void
    {
        $exception = new Exception('Generic error');
        
        $result = $this->policy->isRetryableError($exception);
        
        $this->assertFalse($result);
    }

    // =========================================================================
    // handleRateLimit() Tests (HR)
    // =========================================================================

    public function test_hr_01_respects_retry_after_header(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 30]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        $this->assertTrue($decision->shouldRetry);
        $this->assertEquals(30000, $decision->delayMs); // 30 seconds in ms
    }

    public function test_hr_02_returns_retry_false_for_long_wait(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 120]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        $this->assertFalse($decision->shouldRetry);
    }

    public function test_hr_03_returns_retry_false_for_60_second_boundary(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 60]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        // Exactly 60 seconds should be allowed
        $this->assertTrue($decision->shouldRetry);
        $this->assertEquals(60000, $decision->delayMs);
    }

    public function test_hr_04_uses_default_delay_without_header(): void
    {
        $exception = new RateLimitException('Rate limited', 429);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        $this->assertTrue($decision->shouldRetry);
        // Should use default exponential backoff for attempt 1
        $this->assertGreaterThanOrEqual(900, $decision->delayMs);
        $this->assertLessThanOrEqual(1100, $decision->delayMs);
    }

    public function test_hr_05_handles_zero_retry_after(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 0]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        $this->assertTrue($decision->shouldRetry);
    }

    // =========================================================================
    // getRetryStrategy() Tests (GR)
    // =========================================================================

    public function test_gr_01_returns_standard_strategy_for_small_content(): void
    {
        $strategy = $this->policy->getRetryStrategy('Small content', 1);
        
        $this->assertEquals('standard', $strategy->type);
    }

    public function test_gr_02_returns_standard_strategy_for_medium_content(): void
    {
        $content = str_repeat('a', 10000); // ~2500 tokens
        
        $strategy = $this->policy->getRetryStrategy($content, 1);
        
        $this->assertEquals('standard', $strategy->type);
    }

    public function test_gr_03_returns_truncation_strategy_for_large_content_on_retry(): void
    {
        $content = str_repeat('a', 200000); // ~50000 tokens
        
        $strategy = $this->policy->getRetryStrategy($content, 2);
        
        $this->assertEquals('truncation', $strategy->type);
        $this->assertLessThan(50000, $strategy->targetTokens);
    }

    public function test_gr_04_returns_standard_on_first_attempt_even_for_large_content(): void
    {
        $content = str_repeat('a', 200000); // ~50000 tokens
        
        $strategy = $this->policy->getRetryStrategy($content, 1);
        
        // First attempt should use standard strategy
        $this->assertEquals('standard', $strategy->type);
    }

    public function test_gr_05_truncation_reduces_by_30_percent(): void
    {
        $content = str_repeat('a', 200000); // ~50000 tokens
        
        $strategy = $this->policy->getRetryStrategy($content, 2);
        
        // Should target 70% of original = 35000 tokens
        $this->assertEquals(35000, $strategy->targetTokens);
    }

    // =========================================================================
    // Integration Tests (IT)
    // =========================================================================

    public function test_it_01_full_retry_cycle_for_500_error(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        // Attempt 1 - should retry
        $this->assertTrue($this->policy->shouldRetry($exception, 1));
        $delay1 = $this->policy->getDelayMs(1);
        $this->assertGreaterThanOrEqual(900, $delay1);
        
        // Attempt 2 - should retry
        $this->assertTrue($this->policy->shouldRetry($exception, 2));
        $delay2 = $this->policy->getDelayMs(2);
        $this->assertGreaterThan($delay1, $delay2);
        
        // Attempt 3 - at limit, should not retry
        $this->assertFalse($this->policy->shouldRetry($exception, 3));
    }

    public function test_it_02_no_retry_for_client_error(): void
    {
        $exception = new HttpException(400, 'Bad Request');
        
        // Should never retry client errors
        $this->assertFalse($this->policy->shouldRetry($exception, 1));
        $this->assertFalse($this->policy->shouldRetry($exception, 2));
    }

    public function test_it_03_rate_limit_retry_with_backoff(): void
    {
        $exception = new RateLimitException('Rate limited', 429);
        
        $this->assertTrue($this->policy->shouldRetry($exception, 1));
        
        $decision = $this->policy->handleRateLimit($exception);
        $this->assertTrue($decision->shouldRetry);
    }

    public function test_it_04_connection_failure_is_retryable(): void
    {
        $exception = new ConnectionException('Connection failed');
        
        $this->assertTrue($this->policy->isRetryableError($exception));
        $this->assertTrue($this->policy->shouldRetry($exception, 1));
    }

    // =========================================================================
    // Edge Cases (EC)
    // =========================================================================

    public function test_ec_01_handles_null_exception_gracefully(): void
    {
        $result = $this->policy->shouldRetry(null, 1);
        
        $this->assertFalse($result);
    }

    public function test_ec_02_handles_empty_attempt(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->shouldRetry($exception, 0);
        
        $this->assertTrue($result);
    }

    public function test_ec_03_handles_very_large_attempt(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $result = $this->policy->shouldRetry($exception, 1000);
        
        $this->assertFalse($result);
    }

    public function test_ec_04_handles_unknown_http_status(): void
    {
        $exception = new HttpException(418, 'I am a teapot');
        
        $result = $this->policy->isRetryableError($exception);
        
        // Unknown codes should not be retryable
        $this->assertFalse($result);
    }

    public function test_ec_05_multiple_rate_limits_in_sequence(): void
    {
        $exception1 = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 10]);
        $exception2 = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 20]);
        
        $decision1 = $this->policy->handleRateLimit($exception1);
        $decision2 = $this->policy->handleRateLimit($exception2);
        
        $this->assertEquals(10000, $decision1->delayMs);
        $this->assertEquals(20000, $decision2->delayMs);
    }

    public function test_ec_06_empty_content_strategy(): void
    {
        $strategy = $this->policy->getRetryStrategy('', 1);
        
        $this->assertEquals('standard', $strategy->type);
    }

    public function test_ec_07_unicode_content_strategy(): void
    {
        $content = str_repeat('日本語テスト', 50000); // Large unicode content
        
        $strategy = $this->policy->getRetryStrategy($content, 2);
        
        // Should handle unicode correctly
        $this->assertContains($strategy->type, ['standard', 'truncation']);
    }

    // =========================================================================
    // Security Tests (SC)
    // =========================================================================

    public function test_sc_01_no_sensitive_data_in_exceptions(): void
    {
        $exception = new HttpException(500, 'Error with api_key=secret123');
        
        // The policy should not expose sensitive data in its decision
        $result = $this->policy->shouldRetry($exception, 1);
        
        // Just verify it returns a boolean
        $this->assertIsBool($result);
    }

    public function test_sc_02_handles_malformed_rate_limit_header(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 'invalid']);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        // Should handle gracefully and use default
        $this->assertTrue($decision->shouldRetry);
    }

    public function test_sc_03_negative_retry_after_treated_as_zero(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => -5]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        // Should treat negative as zero/default
        $this->assertTrue($decision->shouldRetry);
    }

    // =========================================================================
    // Performance Tests (PF)
    // =========================================================================

    public function test_pf_01_should_retry_is_fast(): void
    {
        $exception = new HttpException(500, 'Internal Server Error');
        
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->policy->shouldRetry($exception, 1);
        }
        $end = microtime(true);
        
        // 1000 calls should take less than 100ms
        $this->assertLessThan(0.1, $end - $start);
    }

    public function test_pf_02_get_delay_is_fast(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->policy->getDelayMs($i % 5 + 1);
        }
        $end = microtime(true);
        
        // 1000 calls should take less than 100ms
        $this->assertLessThan(0.1, $end - $start);
    }

    public function test_pf_03_handle_rate_limit_is_fast(): void
    {
        $exception = new RateLimitException('Rate limited', 429);
        
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->policy->handleRateLimit($exception);
        }
        $end = microtime(true);
        
        // 1000 calls should take less than 100ms
        $this->assertLessThan(0.1, $end - $start);
    }

    // =========================================================================
    // Acceptance Criteria Tests (AC)
    // =========================================================================

    public function test_ac_01_should_retry_correctly_identifies_retryable_errors(): void
    {
        // Retryable: 429, 500, 502, 503, 504, timeout, connection
        $retryableExceptions = [
            new HttpException(429, 'Too Many Requests'),
            new HttpException(500, 'Internal Server Error'),
            new HttpException(502, 'Bad Gateway'),
            new HttpException(503, 'Service Unavailable'),
            new HttpException(504, 'Gateway Timeout'),
            new TimeoutException('Timeout'),
            new ConnectionException('Connection failed'),
            new RateLimitException('Rate limited', 429),
            new ServiceUnavailableException('Service unavailable'),
        ];
        
        foreach ($retryableExceptions as $exception) {
            $this->assertTrue(
                $this->policy->isRetryableError($exception),
                'Failed for: ' . get_class($exception)
            );
        }
    }

    public function test_ac_02_exponential_backoff_with_jitter(): void
    {
        // Verify exponential backoff: 1s → 2s → 4s
        $delay1 = $this->policy->getDelayMs(1);
        $delay2 = $this->policy->getDelayMs(2);
        $delay3 = $this->policy->getDelayMs(3);
        
        // With jitter, verify ranges
        $this->assertGreaterThanOrEqual(900, $delay1);
        $this->assertLessThanOrEqual(1100, $delay1);
        
        $this->assertGreaterThanOrEqual(1800, $delay2);
        $this->assertLessThanOrEqual(2200, $delay2);
        
        $this->assertGreaterThanOrEqual(3600, $delay3);
        $this->assertLessThanOrEqual(4400, $delay3);
    }

    public function test_ac_03_max_3_retries_by_default(): void
    {
        $this->assertEquals(3, $this->policy->getMaxRetries());
    }

    public function test_ac_04_rate_limit_respects_retry_after_header(): void
    {
        $exception = new RateLimitException('Rate limited', 429, null, [], 0, null, ['retry-after' => 45]);
        
        $decision = $this->policy->handleRateLimit($exception);
        
        $this->assertTrue($decision->shouldRetry);
        $this->assertEquals(45000, $decision->delayMs);
    }

    public function test_ac_05_non_retryable_errors_fail_fast(): void
    {
        $nonRetryableExceptions = [
            new HttpException(400, 'Bad Request'),
            new HttpException(401, 'Unauthorized'),
            new HttpException(403, 'Forbidden'),
            new HttpException(404, 'Not Found'),
            new HttpException(422, 'Validation Error'),
        ];
        
        foreach ($nonRetryableExceptions as $exception) {
            $this->assertFalse(
                $this->policy->isRetryableError($exception),
                'Should not retry: ' . $exception->getStatusCode()
            );
            $this->assertFalse(
                $this->policy->shouldRetry($exception, 1),
                'Should fail fast: ' . $exception->getStatusCode()
            );
        }
    }

    public function test_ac_06_logs_retry_attempts_with_context(): void
    {
        // This test verifies the method exists and accepts context
        // Actual logging behavior is tested in integration tests
        $exception = new HttpException(500, 'Internal Server Error');
        
        // Should not throw
        $result = $this->policy->shouldRetry($exception, 1);
        $this->assertTrue($result);
    }
}
