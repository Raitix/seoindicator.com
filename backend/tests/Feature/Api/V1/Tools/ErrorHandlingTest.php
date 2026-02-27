<?php

namespace Tests\Feature\Api\V1\Tools;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        // Set up any required test data
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
            'file:///etc/passwd',
            'http://internal.company.local'
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

        $response->assertStatus(502)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED'
                ]
            ]);
    }

    /**
     * API-004: Timeout
     */
    public function test_timeout_returns_504(): void
    {
        Http::fake([
            'slow-example.com/*' => Http::response(null, 200)->delay(35000) // 35 seconds
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://slow-example.com/page'
        ]);

        $response->assertStatus(504)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TIMEOUT'
                ]
            ]);
    }

    /**
     * API-005: Rate limited
     */
    public function test_rate_limited_returns_429(): void
    {
        // Make requests up to the limit
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com/page' . $i
            ]);
        }

        // The 16th request should be rate limited
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED'
                ]
            ]);
    }

    /**
     * API-006: Parse error
     */
    public function test_parse_error_returns_500(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><body>Invalid malformed HTML<<<', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/malformed'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'PARSE_ERROR'
                ]
            ]);
    }

    /**
     * API-007: Generic tool error
     */
    public function test_generic_tool_error_returns_500(): void
    {
        // Force an unexpected exception
        Http::fake(function () {
            throw new \RuntimeException('Unexpected error');
        });

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/unexpected'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TOOL_ERROR'
                ]
            ]);
    }

    /**
     * API-008: Error includes Retry-After header
     */
    public function test_rate_limited_includes_retry_after_header(): void
    {
        // Exhaust rate limit
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/api/v1/tools/analyze', ['url' => 'https://example.com/' . $i]);
        }

        $response = $this->postJson('/api/v1/tools/analyze', ['url' => 'https://example.com/limited']);

        $response->assertHeader('Retry-After');
        $this->assertIsNumeric($response->headers->get('Retry-After'));
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
     * API-010: Request ID in error response
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
     * API-011: Error logged with request ID
     */
    public function test_error_logged_with_request_id(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['request_id']) &&
                    preg_match('/^req_[a-z0-9]+$/', $context['request_id']);
            });

        $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ]);
    }

    /**
     * API-012: Concurrent error handling
     */
    public function test_concurrent_errors_have_unique_request_ids(): void
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
     * API-013: Validation error format
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
     * API-014: Error with stack trace in debug mode
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
            'exception'
        ]);
    }

    /**
     * API-015: Error without stack trace in production
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
     * API-016: CORS error handling
     */
    public function test_cors_headers_on_error(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ], [
            'Origin' => 'https://seoindicator.com'
        ]);

        $response->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * API-017: OPTIONS preflight error
     */
    public function test_options_preflight_returns_200(): void
    {
        $response = $this->options('/api/v1/tools/analyze', [], [
            'Origin' => 'https://seoindicator.com',
            'Access-Control-Request-Method' => 'POST'
        ]);

        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Methods');
    }

    /**
     * API-018: Large URL handling
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
     * API-019: Special characters in URL
     */
    public function test_special_characters_in_url(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html></html>', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/path?query=café&name=日本語'
        ]);

        // Should either succeed with encoding or return INVALID_URL
        $this->assertContains($response->status(), [200, 400]);
    }

    /**
     * API-020: SQL injection attempt
     */
    public function test_sql_injection_attempt_is_sanitized(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => "https://example.com'; DROP TABLE users; --"
        ]);

        // Should not cause SQL error, should be treated as invalid URL
        $response->assertStatus(400);
        $this->assertStringNotContainsString('SQL', $response->content());
    }

    /**
     * API-021: XSS attempt in URL
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
     * API-022: Error with tool context
     */
    public function test_error_includes_tool_context(): void
    {
        $response = $this->postJson('/api/v1/tools/meta-tags/analyze', [
            'url' => 'invalid-url'
        ]);

        $response->assertJsonStructure([
            'error' => ['tool']
        ]);
    }

    /**
     * API-023: Partial success response
     */
    public function test_partial_success_with_multiple_tools(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><title>Test</title></html>', 200),
            'blocked.com/*' => Http::response(null, 403)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze-batch', [
            'urls' => [
                ['url' => 'https://example.com/page1', 'tool' => 'meta-tags'],
                ['url' => 'https://blocked.com/page2', 'tool' => 'meta-tags']
            ]
        ]);

        $response->assertJsonStructure([
            'results' => [
                '*' => ['url', 'success', 'error']
            ]
        ]);
    }

    /**
     * API-024: Error recovery suggestion
     */
    public function test_error_includes_suggestion(): void
    {
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com/nonexistent'
        ]);

        if ($response->status() >= 400) {
            $response->assertJsonStructure([
                'error' => ['suggestion']
            ]);
        }
    }

    /**
     * API-025: Transient vs permanent error distinction
     */
    public function test_transient_vs_permanent_error_suggestions(): void
    {
        // Transient error (FETCH_FAILED) should suggest retry
        Http::fake(['transient.com/*' => Http::response(null, 502)]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://transient.com/page'
        ]);

        if ($response->json('error.code') === 'FETCH_FAILED') {
            $this->assertStringContainsStringIgnoringCase('try', 
                $response->json('error.suggestion'));
        }

        // Permanent error (INVALID_URL) should not suggest retry
        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'invalid-url'
        ]);

        $this->assertStringContainsStringIgnoringCase('format', 
            $response->json('error.suggestion'));
    }

    /**
     * API-026: Network unreachable
     */
    public function test_network_unreachable_returns_fetch_failed(): void
    {
        Http::fake([
            'nonexistent-domain-12345.com/*' => function () {
                throw new \GuzzleHttp\Exception\ConnectException(
                    'Could not resolve host',
                    new \GuzzleHttp\Psr7\Request('GET', 'test')
                );
            }
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://nonexistent-domain-12345.com/page'
        ]);

        $response->assertStatus(502)
            ->assertJson([
                'error' => ['code' => 'FETCH_FAILED']
            ]);
    }

    /**
     * API-027: SSL certificate error
     */
    public function test_ssl_error_returns_fetch_failed(): void
    {
        Http::fake([
            'expired-ssl.com/*' => function () {
                throw new \GuzzleHttp\Exception\RequestException(
                    'SSL certificate problem',
                    new \GuzzleHttp\Psr7\Request('GET', 'test')
                );
            }
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://expired-ssl.com/page'
        ]);

        $response->assertStatus(502)
            ->assertJson([
                'error' => ['code' => 'FETCH_FAILED']
            ]);

        $this->assertStringContainsStringIgnoringCase('ssl', 
            $response->json('error.details'));
    }

    /**
     * API-028: Connection refused
     */
    public function test_connection_refused_returns_fetch_failed(): void
    {
        Http::fake([
            'offline-server.com/*' => function () {
                throw new \GuzzleHttp\Exception\ConnectException(
                    'Connection refused',
                    new \GuzzleHttp\Psr7\Request('GET', 'test')
                );
            }
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://offline-server.com/page'
        ]);

        $response->assertStatus(502);
    }

    /**
     * API-029: Malformed response
     */
    public function test_malformed_response_returns_parse_error(): void
    {
        Http::fake([
            'malformed.com/*' => Http::response('<<<INVALID>>>', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://malformed.com/page'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => ['code' => 'PARSE_ERROR']
            ]);
    }

    /**
     * API-030: Empty response
     */
    public function test_empty_response_returns_parse_error(): void
    {
        Http::fake([
            'empty.com/*' => Http::response('', 200)
        ]);

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://empty.com/page'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => ['code' => 'PARSE_ERROR']
            ]);
    }
}
