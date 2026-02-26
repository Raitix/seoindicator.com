<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Services\URLValidationService;
use App\Services\HtmlParserService;
use App\Services\RateLimitService;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * API Proxy Controller Tests for TASK-16-3.
 * 
 * Tests cover:
 * - Unit tests: Controller logic, URL validation, HTML parsing, rate limiting
 * - Integration tests: Endpoint behavior, external fetch handling
 * - Security tests: SSRF prevention, rate limiting, input validation
 */
class APIProxyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $analyzeEndpoint = '/api/v1/tools/analyze';
    protected string $captchaConfigEndpoint = '/api/v1/tools/captcha-config';
    protected string $rateLimitStatusEndpoint = '/api/v1/tools/rate-limit-status';

    // ==================== URL VALIDATION TESTS ====================

    /**
     * UT-001: Valid URL request
     */
    public function test_valid_url_request(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<!DOCTYPE html><html><head><title>Test Page</title></head><body>Content</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'url',
                    'finalUrl',
                    'statusCode',
                    'title',
                ],
            ]);
    }

    /**
     * UT-002: Invalid URL format
     */
    public function test_invalid_url_format(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'not-a-url',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-003: Empty URL request
     */
    public function test_empty_url_request(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * UT-004: Missing URL field
     */
    public function test_missing_url_field(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * UT-005: URL with query params
     */
    public function test_url_with_query_params(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Query Test</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com?query=1',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * UT-006: URL with fragment
     */
    public function test_url_with_fragment(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Fragment Test</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com#section',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * UT-007: HTTP URL (non-HTTPS)
     */
    public function test_http_url_request(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>HTTP Test</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * UT-009: localhost URL blocked
     */
    public function test_localhost_url_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://localhost',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-010: Private IP URL blocked
     */
    public function test_private_ip_url_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://192.168.1.1',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-011: Internal IP blocked
     */
    public function test_internal_ip_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://10.0.0.1',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-012: 127.0.0.1 blocked
     */
    public function test_loopback_ip_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://127.0.0.1',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-013: file:// protocol blocked
     */
    public function test_file_protocol_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'file:///etc/passwd',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-014: ftp:// protocol blocked
     */
    public function test_ftp_protocol_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'ftp://example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-015: javascript: protocol blocked
     */
    public function test_javascript_protocol_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'javascript:alert(1)',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-016: data: protocol blocked
     */
    public function test_data_protocol_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN_URL');
    }

    /**
     * UT-017: URL max length exceeded
     */
    public function test_url_max_length_exceeded(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 3000);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => $longUrl,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    // ==================== SSRF PREVENTION TESTS ====================

    /**
     * ST-016: localhost blocked
     */
    public function test_ssrf_localhost_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://localhost',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-017: 127.0.0.1 blocked
     */
    public function test_ssrf_127_0_0_1_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://127.0.0.1',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-018: 127.0.0.1 with port blocked
     */
    public function test_ssrf_127_0_0_1_with_port_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://127.0.0.1:8080',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-019: 0.0.0.0 blocked
     */
    public function test_ssrf_0_0_0_0_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://0.0.0.0',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-020: 10.x.x.x blocked
     */
    public function test_ssrf_10_range_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://10.0.0.1',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-021: 172.16.x.x blocked
     */
    public function test_ssrf_172_range_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://172.16.0.1',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-022: 192.168.x.x blocked
     */
    public function test_ssrf_192_168_range_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://192.168.1.1',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-023: 169.254.x.x blocked
     */
    public function test_ssrf_link_local_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://169.254.1.1',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-025: IPv6 localhost blocked
     */
    public function test_ssrf_ipv6_localhost_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://[::1]',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-032: Cloud metadata IP blocked
     */
    public function test_ssrf_cloud_metadata_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://169.254.169.254',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-033: AWS metadata blocked
     */
    public function test_ssrf_aws_metadata_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://169.254.169.254/latest/meta-data/',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ST-034: GCP metadata blocked
     */
    public function test_ssrf_gcp_metadata_blocked(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'http://metadata.google.internal',
        ]);

        $response->assertStatus(403);
    }

    // ==================== RATE LIMITING TESTS ====================

    /**
     * ST-001: First request allowed
     */
    public function test_rate_limit_first_request_allowed(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Test</title></head><body></body></html>', 200),
        ]);

        // Clear any existing rate limit
        Cache::flush();

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '3')
            ->assertHeader('X-RateLimit-Remaining', '2');
    }

    /**
     * ST-004: Fourth request blocked (rate limit exceeded)
     */
    public function test_rate_limit_fourth_request_blocked(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Test</title></head><body></body></html>', 200),
        ]);

        // Clear and make 3 requests
        Cache::flush();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson($this->analyzeEndpoint, [
                'url' => 'https://example.com/page' . $i,
            ]);
        }

        // 4th request should be blocked
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED')
            ->assertJsonPath('error.captchaRequired', true);
    }

    /**
     * Rate limit status endpoint
     */
    public function test_rate_limit_status_endpoint(): void
    {
        Cache::flush();

        $response = $this->getJson($this->rateLimitStatusEndpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'limit',
                    'remaining',
                    'current',
                ],
            ]);
    }

    // ==================== HTML PARSER TESTS ====================

    /**
     * UT-021: Extract title tag
     */
    public function test_parser_extract_title(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Test Page Title</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Page Title');
    }

    /**
     * UT-022: Extract meta description
     */
    public function test_parser_extract_description(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><meta name="description" content="Test description"></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Test description');
    }

    /**
     * UT-024: Extract Open Graph title
     */
    public function test_parser_extract_og_title(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><meta property="og:title" content="OG Title"></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.ogTitle', 'OG Title');
    }

    /**
     * UT-028: Extract Twitter Card type
     */
    public function test_parser_extract_twitter_card(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><meta name="twitter:card" content="summary_large_image"></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.twitterCard', 'summary_large_image');
    }

    /**
     * UT-032: Extract all headings
     */
    public function test_parser_extract_headings(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><body><h1>Main Title</h1><h2>Subtitle</h2><h3>Section</h3></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200);
        
        $headings = $response->json('data.headings');
        $this->assertCount(3, $headings);
        $this->assertEquals('h1', $headings[0]['tag']);
        $this->assertEquals('Main Title', $headings[0]['text']);
    }

    /**
     * UT-035: Calculate word count
     */
    public function test_parser_calculate_word_count(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><body><p>This is a test paragraph with eight words total here.</p></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200);
        $wordCount = $response->json('data.wordCount');
        $this->assertGreaterThanOrEqual(8, $wordCount);
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * UT-018: Request timeout handling
     */
    public function test_timeout_handling(): void
    {
        Http::fake([
            'slow.example.com/*' => Http::response('', 200)->delay(35000), // 35 seconds, exceeds 30s timeout
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://slow.example.com',
        ]);

        $response->assertStatus(504)
            ->assertJsonPath('error.code', 'CONNECTION_ERROR');
    }

    /**
     * UT-019: Connection refused handling
     */
    public function test_connection_refused_handling(): void
    {
        Http::fake([
            'unreachable.example.com/*' => Http::response(null, 0), // Simulate connection failure
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://unreachable.example.com',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('success', false);
    }

    // ==================== CAPTCHA CONFIG TESTS ====================

    /**
     * CAPTCHA config endpoint returns valid structure
     */
    public function test_captcha_config_endpoint(): void
    {
        $response = $this->getJson($this->captchaConfigEndpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'driver',
                    'siteKey',
                    'isRequired',
                ],
            ]);
    }

    // ==================== INTEGRATION TESTS ====================

    /**
     * IT-001: Successful fetch and parse
     */
    public function test_integration_successful_fetch_and_parse(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Example Domain</title>
    <meta name="description" content="This domain is for use in illustrative examples">
    <meta property="og:title" content="Example OG">
    <meta name="twitter:card" content="summary">
</head>
<body>
    <h1>Example Domain</h1>
    <p>This domain is for use in illustrative examples in documents.</p>
    <a href="https://www.iana.org/domains/example">More information...</a>
</body>
</html>
HTML;

        Http::fake([
            'example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Example Domain')
            ->assertJsonPath('data.description', 'This domain is for use in illustrative examples')
            ->assertJsonPath('data.ogTitle', 'Example OG')
            ->assertJsonPath('data.twitterCard', 'summary')
            ->assertJsonPath('data.statusCode', 200);
    }

    /**
     * IT-002: Fetch with redirects
     */
    public function test_integration_fetch_with_redirects(): void
    {
        Http::fake([
            'redirect.example.com' => Http::response('', 301, ['Location' => 'https://final.example.com']),
            'final.example.com' => Http::response('<html><head><title>Final Page</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://redirect.example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * IT-010: Binary file rejection
     */
    public function test_integration_binary_file_rejection(): void
    {
        Http::fake([
            'example.com/*' => Http::response('PDF content here', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com/document.pdf',
        ]);

        $response->assertStatus(415)
            ->assertJsonPath('error.code', 'INVALID_CONTENT_TYPE');
    }

    // ==================== INPUT VALIDATION TESTS ====================

    /**
     * ST-036: SQL injection in URL (should be handled safely)
     */
    public function test_sql_injection_in_url(): void
    {
        // URL should be passed as-is to the target, not executed as SQL
        Http::fake([
            '*' => Http::response('<html><head><title>Test</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com?id=1\' OR \'1\'=\'1',
        ]);

        // Should not cause SQL error, just normal fetch
        $response->assertStatus(200);
    }

    /**
     * ST-038: CRLF injection in URL
     */
    public function test_crlf_injection_in_url(): void
    {
        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => "https://example.com\r\nX-Injected: true",
        ]);

        // Should be rejected or sanitized
        $response->assertStatus(403);
    }

    /**
     * ST-040: Unicode normalization
     */
    public function test_unicode_normalization(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head><title>Unicode Test</title></head><body></body></html>', 200),
        ]);

        $response = $this->postJson($this->analyzeEndpoint, [
            'url' => 'https://example.com/‑test', // Non-breaking hyphen
        ]);

        $response->assertStatus(200);
    }
}
