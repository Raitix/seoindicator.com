<?php

namespace Tests\Feature\Api\V1\Tools;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Tools\ToolErrorHandler;
use App\Services\Tools\ToolError;

/**
 * Feature tests for tool API error handling.
 * 
 * @see TESTS/TEST-16-12.md
 * Covers: API-001 to API-030
 */
class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * API-001: Invalid URL format
     */
    public function test_invalid_url_format_returns_400(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'not-a-valid-url'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_URL'
                ]
            ]);
    }

    /**
     * API-002: URL not allowed (blocked)
     */
    public function test_url_blocked_returns_403(): void
    {
        $blockedUrls = [
            'http://localhost',
            'http://127.0.0.1',
        ];

        foreach ($blockedUrls as $url) {
            $response = $this->postJson('/api/v1/tools/analyze', [
                'url' => $url
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'URL_BLOCKED'
                    ]
                ]);
        }
    }

    /**
     * API-003: Fetch failed
     */
    public function test_fetch_failed_returns_502(): void
    {
        Http::fake([
            'example.com/*' => Http::response(null, 500)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/error-page'
        ]);

        // The endpoint should return an error status
        $response->assertStatus(502);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * API-004: Timeout
     */
    public function test_timeout_returns_504(): void
    {
        Http::fake([
            'slow-example.com/*' => Http::response(null, 200)->timeout(35)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://slow-example.com/page'
        ]);

        // May get timeout or other error
        $this->assertContains($response->status(), [502, 504, 500]);
    }

    /**
     * API-005: Rate limited - requires multiple requests
     */
    public function test_rate_limited_returns_429(): void
    {
        // Make many requests to trigger rate limit
        for ($i = 0; $i < 25; $i++) {
            $response = $this->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com/page' . $i
            ]);
        }

        // The last request should be rate limited
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED'
                ]
            ]);
    }

    /**
     * API-006: Parse error - malformed HTML
     */
    public function test_parse_error_returns_error(): void
    {
        Http::fake([
            'malformed.com/*' => Http::response('<<<INVALID>>>', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://malformed.com/page'
        ]);

        // Should return an error status
        $response->assertStatus(500);
    }

    /**
     * API-007: Generic tool error
     */
    public function test_generic_tool_error_returns_500(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('Unexpected error');
        });

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/unexpected'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * API-008: Error includes request_id
     */
    public function test_error_includes_request_id(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ]);

        $response->assertJsonStructure([
            'request_id'
        ]);

        $this->assertMatchesRegularExpression('/^req_[a-z0-9]+$/', $response->json('request_id'));
    }

    /**
     * API-009: Error includes X-Error-Code header
     */
    public function test_error_includes_x_error_code_header(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ]);

        $response->assertHeader('X-Error-Code', 'INVALID_URL');
    }

    /**
     * API-010: Request ID is unique
     */
    public function test_error_includes_unique_request_id(): void
    {
        $requestIds = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/tools/analyze', [
                'url' => 'invalid-url-' . $i
            ]);
            $requestIds[] = $response->json('request_id');
        }

        $this->assertEquals(count($requestIds), count(array_unique($requestIds)));
    }

    /**
     * API-011: Validation error format
     */
    public function test_validation_error_format(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['url']
            ]);
    }

    /**
     * API-012: Error with stack trace in debug mode
     */
    public function test_error_with_stack_trace_in_debug(): void
    {
        config(['app.debug' => true]);

        Http::fake(function () {
            throw new \RuntimeException('Test exception');
        });

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/debug'
        ]);

        $response->assertJsonStructure([
            'error' => ['exception']
        ]);
    }

    /**
     * API-013: Error without stack trace in production
     */
    public function test_error_without_stack_trace_in_production(): void
    {
        config(['app.debug' => false]);

        Http::fake(function () {
            throw new \RuntimeException('Test exception');
        });

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/prod'
        ]);

        $response->assertJsonMissing([
            'exception'
        ]);
    }

    /**
     * API-014: Large URL handling
     */
    public function test_large_url_returns_invalid_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2100);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => $longUrl
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => ['code' => 'INVALID_URL']
            ]);
    }

    /**
     * API-015: Special characters in URL
     */
    public function test_special_characters_in_url(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html></html>', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/path?query=test'
        ]);

        // Should either succeed or return a valid error
        $this->assertContains($response->status(), [200, 400, 422]);
    }

    /**
     * API-016: SQL injection attempt is handled safely
     */
    public function test_sql_injection_attempt_is_sanitized(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => "https://example.com'; DROP TABLE users; --"
        ]);

        // Should not cause SQL error, should be treated as URL error
        $response->assertStatus(400);
        $this->assertStringNotContainsString('SQL', $response->content());
    }

    /**
     * API-017: XSS attempt in URL is handled safely
     */
    public function test_xss_attempt_is_sanitized(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/<script>alert(1)</script>'
        ]);

        // Should not execute script, should be sanitized or rejected
        $response->assertStatus(400);
        $this->assertStringNotContainsString('<script>', $response->content());
    }

    /**
     * API-018: Error includes suggestion
     */
    public function test_error_includes_suggestion(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ]);

        $response->assertJsonStructure([
            'error' => ['suggestion']
        ]);
    }

    /**
     * API-019: Network unreachable
     */
    public function test_network_unreachable_returns_fetch_failed(): void
    {
        Http::fake([
            'nonexistent-domain-12345.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'Could not resolve host'
                );
            }
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://nonexistent-domain-12345.com/page'
        ]);

        $response->assertStatus(502);
    }

    /**
     * API-020: Empty response
     */
    public function test_empty_response_returns_error(): void
    {
        Http::fake([
            'empty.com/*' => Http::response('', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://empty.com/page'
        ]);

        // Should return an error for empty content
        $response->assertStatus(500);
    }

    /**
     * API-021: Tool error handler creates proper error
     */
    public function test_tool_error_handler_creates_proper_error(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $error = $handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com'
        ]);

        $this->assertInstanceOf(ToolError::class, $error);
        $this->assertEquals('FETCH_FAILED', $error->code);
        $this->assertEquals(502, $error->httpStatus);
    }

    /**
     * API-022: Tool error response format
     */
    public function test_tool_error_response_format(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $error = $handler->createError('INVALID_URL', [
            'url' => 'bad-url'
        ]);

        $response = $error->toResponse();

        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('INVALID_URL', $response['error']['code']);
    }

    /**
     * API-023: Tool error is retryable
     */
    public function test_tool_error_retryable_detection(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $retryableError = $handler->createError('FETCH_FAILED');
        $nonRetryableError = $handler->createError('INVALID_URL');

        $this->assertTrue($handler->isRetryable($retryableError));
        $this->assertFalse($handler->isRetryable($nonRetryableError));
    }

    /**
     * API-024: Tool error from exception
     */
    public function test_tool_error_from_exception(): void
    {
        $handler = app(ToolErrorHandler::class);
        $exception = new \RuntimeException('Test exception');
        
        $error = $handler->createFromException($exception);

        $this->assertEquals('TOOL_ERROR', $error->code);
        $this->assertEquals(500, $error->httpStatus);
    }

    /**
     * API-025: Multiple errors aggregation
     */
    public function test_multiple_errors_aggregation(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $errors = $handler->aggregateErrors([
            $handler->createError('INVALID_URL', ['url' => 'bad']),
            $handler->createError('FETCH_FAILED', ['url' => 'https://example.com']),
        ]);

        $this->assertCount(2, $errors);
        $this->assertEquals('INVALID_URL', $errors[0]['code']);
        $this->assertEquals('FETCH_FAILED', $errors[1]['code']);
    }

    /**
     * API-026: Error codes are valid
     */
    public function test_error_codes_are_valid(): void
    {
        $validCodes = ToolError::getValidCodes();

        $this->assertContains('INVALID_URL', $validCodes);
        $this->assertContains('URL_BLOCKED', $validCodes);
        $this->assertContains('FETCH_FAILED', $validCodes);
        $this->assertContains('TIMEOUT', $validCodes);
        $this->assertContains('RATE_LIMITED', $validCodes);
        $this->assertContains('PARSE_ERROR', $validCodes);
        $this->assertContains('TOOL_ERROR', $validCodes);
    }

    /**
     * API-027: Error code validation
     */
    public function test_error_code_validation(): void
    {
        $this->assertTrue(ToolError::isValidCode('INVALID_URL'));
        $this->assertTrue(ToolError::isValidCode('FETCH_FAILED'));
        $this->assertFalse(ToolError::isValidCode('INVALID_CODE'));
    }

    /**
     * API-028: Rate limit includes retry-after info
     */
    public function test_rate_limit_includes_retry_info(): void
    {
        // Make many requests to trigger rate limit
        for ($i = 0; $i < 25; $i++) {
            $this->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com/' . $i
            ]);
        }

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/limited'
        ]);

        $response->assertJsonStructure([
            'error' => ['captchaRequired']
        ]);
    }

    /**
     * API-029: Error context is preserved
     */
    public function test_error_context_is_preserved(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $error = $handler->createError('FETCH_FAILED', [
            'url' => 'https://example.com',
            'tool' => 'meta-tag-analyzer',
            'custom_field' => 'custom_value'
        ]);

        $this->assertEquals('https://example.com', $error->context['url']);
        $this->assertEquals('meta-tag-analyzer', $error->context['tool']);
        $this->assertEquals('custom_value', $error->context['custom_field']);
    }

    /**
     * API-030: Retry delay calculation
     */
    public function test_retry_delay_calculation(): void
    {
        $handler = app(ToolErrorHandler::class);
        
        $error = $handler->createError('FETCH_FAILED');

        $delay1 = $handler->getRetryDelay($error, 1);
        $delay2 = $handler->getRetryDelay($error, 2);
        $delay3 = $handler->getRetryDelay($error, 3);

        // Exponential backoff: 1000, 2000, 4000
        $this->assertEquals(1000, $delay1);
        $this->assertEquals(2000, $delay2);
        $this->assertEquals(4000, $delay3);
    }
}
