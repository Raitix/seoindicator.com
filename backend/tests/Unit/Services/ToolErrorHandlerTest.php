<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Tools\ToolErrorHandler;
use App\Services\Tools\ToolError;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Unit tests for ToolErrorHandler service.
 * 
 * @see TESTS/TEST-16-12.md
 * Covers: EH-001 to EH-025
 */
class ToolErrorHandlerTest extends TestCase
{
    private ToolErrorHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ToolErrorHandler();
    }

    /**
     * EH-001: Create error with INVALID_URL code
     */
    public function test_create_error_with_invalid_url_code(): void
    {
        $error = $this->handler->createError('INVALID_URL', [
            'url' => 'not-a-valid-url'
        ]);

        $this->assertInstanceOf(ToolError::class, $error);
        $this->assertEquals('INVALID_URL', $error->code);
        $this->assertEquals(400, $error->httpStatus);
        $this->assertStringContainsString('Invalid URL', $error->message);
    }

    /**
     * EH-002: Create error with URL_BLOCKED code
     */
    public function test_create_error_with_url_blocked_code(): void
    {
        $error = $this->handler->createError('URL_BLOCKED', [
            'url' => 'http://localhost'
        ]);

        $this->assertEquals('URL_BLOCKED', $error->code);
        $this->assertEquals(403, $error->httpStatus);
        $this->assertStringContainsString('not allowed', $error->message);
    }

    /**
     * EH-003: Create error with FETCH_FAILED code
     */
    public function test_create_error_with_fetch_failed_code(): void
    {
        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com',
            'details' => 'Server returned 500'
        ]);

        $this->assertEquals('FETCH_FAILED', $error->code);
        $this->assertEquals(502, $error->httpStatus);
        $this->assertStringContainsString('Could not fetch', $error->message);
    }

    /**
     * EH-004: Create error with TIMEOUT code
     */
    public function test_create_error_with_timeout_code(): void
    {
        $error = $this->handler->createError('TIMEOUT', [
            'url' => 'https://slow-example.com'
        ]);

        $this->assertEquals('TIMEOUT', $error->code);
        $this->assertEquals(504, $error->httpStatus);
        $this->assertStringContainsString('timed out', strtolower($error->message));
    }

    /**
     * EH-005: Create error with RATE_LIMITED code
     */
    public function test_create_error_with_rate_limited_code(): void
    {
        $error = $this->handler->createError('RATE_LIMITED', [
            'retry_after' => 60
        ]);

        $this->assertEquals('RATE_LIMITED', $error->code);
        $this->assertEquals(429, $error->httpStatus);
        $this->assertStringContainsString('Too many requests', $error->message);
    }

    /**
     * EH-006: Create error with PARSE_ERROR code
     */
    public function test_create_error_with_parse_error_code(): void
    {
        $error = $this->handler->createError('PARSE_ERROR', [
            'url' => 'https://example.com',
            'details' => 'Invalid HTML structure'
        ]);

        $this->assertEquals('PARSE_ERROR', $error->code);
        $this->assertEquals(500, $error->httpStatus);
        $this->assertStringContainsString('Could not parse', $error->message);
    }

    /**
     * EH-007: Create error with TOOL_ERROR code
     */
    public function test_create_error_with_tool_error_code(): void
    {
        $error = $this->handler->createError('TOOL_ERROR', [
            'tool' => 'meta-tag-analyzer',
            'details' => 'Unexpected error occurred'
        ]);

        $this->assertEquals('TOOL_ERROR', $error->code);
        $this->assertEquals(500, $error->httpStatus);
        $this->assertStringContainsString('Analysis failed', $error->message);
    }

    /**
     * EH-008: Error includes suggestion
     */
    public function test_error_includes_suggestion(): void
    {
        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com'
        ]);

        $this->assertNotEmpty($error->suggestion);
        $this->assertIsString($error->suggestion);
    }

    /**
     * EH-009: Error includes details
     */
    public function test_error_includes_details(): void
    {
        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com',
            'details' => 'Connection refused'
        ]);

        $this->assertNotEmpty($error->details);
        $this->assertEquals('Connection refused', $error->details);
    }

    /**
     * EH-010: Error logging with context via logError method
     */
    public function test_error_logging_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Tool error') &&
                    isset($context['error_code']) &&
                    $context['error_code'] === 'FETCH_FAILED';
            });

        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com'
        ]);
        
        $this->handler->logError($error);
    }

    /**
     * EH-011: Is retryable - transient failure
     */
    public function test_is_retryable_transient_failure(): void
    {
        $error = $this->handler->createError('FETCH_FAILED');
        $this->assertTrue($this->handler->isRetryable($error));
    }

    /**
     * EH-012: Is retryable - permanent failure
     */
    public function test_is_retryable_permanent_failure(): void
    {
        $error = $this->handler->createError('INVALID_URL');
        $this->assertFalse($this->handler->isRetryable($error));
    }

    /**
     * EH-013: Is retryable - rate limited
     */
    public function test_is_retryable_rate_limited(): void
    {
        $error = $this->handler->createError('RATE_LIMITED');
        $this->assertTrue($this->handler->isRetryable($error));
    }

    /**
     * EH-014: Get retry delay
     */
    public function test_get_retry_delay(): void
    {
        $error = $this->handler->createError('FETCH_FAILED');
        $delay = $this->handler->getRetryDelay($error, 1);

        $this->assertEquals(1000, $delay);
    }

    /**
     * EH-015: Get retry delay with exponential backoff
     */
    public function test_get_retry_delay_with_exponential_backoff(): void
    {
        $error = $this->handler->createError('FETCH_FAILED');

        $delay1 = $this->handler->getRetryDelay($error, 1);
        $delay2 = $this->handler->getRetryDelay($error, 2);
        $delay3 = $this->handler->getRetryDelay($error, 3);

        $this->assertEquals(1000, $delay1);
        $this->assertEquals(2000, $delay2);
        $this->assertEquals(4000, $delay3);
    }

    /**
     * EH-016: Handle unknown error
     */
    public function test_handle_unknown_error(): void
    {
        $exception = new Exception('Unknown error occurred');
        $error = $this->handler->createFromException($exception);

        $this->assertEquals('TOOL_ERROR', $error->code);
        $this->assertEquals(500, $error->httpStatus);
    }

    /**
     * EH-017: Handle malformed URL
     */
    public function test_handle_malformed_url(): void
    {
        $error = $this->handler->createError('INVALID_URL', [
            'url' => 'not a url'
        ]);

        $this->assertEquals('INVALID_URL', $error->code);
        $this->assertStringContainsString('Invalid URL', $error->message);
    }

    /**
     * EH-018: Handle empty URL
     */
    public function test_handle_empty_url(): void
    {
        $error = $this->handler->createError('INVALID_URL', [
            'url' => ''
        ]);

        $this->assertEquals('INVALID_URL', $error->code);
    }

    /**
     * EH-019: Handle null URL
     */
    public function test_handle_null_url(): void
    {
        $error = $this->handler->createError('INVALID_URL', [
            'url' => null
        ]);

        $this->assertEquals('INVALID_URL', $error->code);
    }

    /**
     * EH-020: Error response format
     */
    public function test_error_response_format(): void
    {
        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com'
        ]);

        $response = $this->handler->toResponse($error);

        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('code', $response['error']);
        $this->assertArrayHasKey('message', $response['error']);
    }

    /**
     * EH-021: Custom error message
     */
    public function test_custom_error_message(): void
    {
        $customMessage = 'Custom error message for testing';
        $error = $this->handler->createError('FETCH_FAILED', [
            'message' => $customMessage
        ]);

        $this->assertEquals($customMessage, $error->message);
    }

    /**
     * EH-022: Error context includes URL
     */
    public function test_error_context_includes_url(): void
    {
        $url = 'https://example.com/page?query=value';
        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => $url
        ]);

        $this->assertEquals($url, $error->context['url']);
    }

    /**
     * EH-023: Error context includes tool name
     */
    public function test_error_context_includes_tool_name(): void
    {
        $error = $this->handler->createError('FETCH_FAILED', [
            'tool' => 'meta-tag-analyzer'
        ]);

        $this->assertEquals('meta-tag-analyzer', $error->context['tool']);
    }

    /**
     * EH-024: Sensitive data redaction in log
     */
    public function test_sensitive_data_redaction_in_log(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                // Check that sensitive data is redacted in the logged context
                if (isset($context['error_context']['url'])) {
                    return !str_contains($context['error_context']['url'], 'api_key=secret') &&
                           !str_contains($context['error_context']['url'], 'password=secret123');
                }
                return true;
            });

        $error = $this->handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com?api_key=secret&password=secret123'
        ]);
        
        $this->handler->logError($error);
    }

    /**
     * EH-025: Multiple errors aggregation
     */
    public function test_multiple_errors_aggregation(): void
    {
        $errors = $this->handler->createErrors([
            ['code' => 'INVALID_URL', 'context' => ['url' => 'bad']],
            ['code' => 'FETCH_FAILED', 'context' => ['url' => 'https://example.com']],
        ]);

        $this->assertCount(2, $errors);
        $this->assertEquals('INVALID_URL', $errors[0]->code);
        $this->assertEquals('FETCH_FAILED', $errors[1]->code);
    }
}
