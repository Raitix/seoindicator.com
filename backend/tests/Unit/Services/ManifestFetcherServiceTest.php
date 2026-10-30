<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ManifestFetcherService;
use App\Services\URLValidationService;
use App\Services\HtmlFetcherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for ManifestFetcherService.
 * 
 * Tests manifest fetching, parsing, and SSRF protection.
 * 
 * @see TESTS/SPEC-24-3.md
 * @see TASKS/TASK-24-3.md
 */
class ManifestFetcherServiceTest extends TestCase
{
    private ManifestFetcherService $service;
    private URLValidationService $urlValidationService;
    private HtmlFetcherService $htmlFetcherService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlValidationService = Mockery::mock(URLValidationService::class);
        $this->htmlFetcherService = Mockery::mock(HtmlFetcherService::class);
        
        $this->service = new ManifestFetcherService(
            $this->urlValidationService,
            $this->htmlFetcherService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Category 1: fetch() Method Tests (MF-001 to MF-010)
    // =========================================================================

    /** @test MF-001: Fetch Manifest Successfully */
    public function test_fetch_manifest_successfully(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $manifestContent = json_encode([
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
        ]);

        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        // Mock URL validation
        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        // Mock HTML fetch
        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        // Mock manifest HTTP fetch
        Http::fake([
            $manifestUrl => Http::response($manifestContent, 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['manifest']);
        $this->assertEquals('Example App', $result['manifest']['name']);
        $this->assertEquals($manifestUrl, $result['manifest_url']);
        $this->assertNull($result['error']);
    }

    /** @test MF-002: No Manifest Link in HTML */
    public function test_fetch_no_manifest_link_in_html(): void
    {
        $siteUrl = 'https://example.com';
        $htmlContent = '<!DOCTYPE html><html><head><title>No Manifest</title></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertNull($result['manifest']);
        $this->assertNull($result['manifest_url']);
        $this->assertEquals('MANIFEST_NOT_FOUND', $result['error_code']);
        $this->assertStringContainsString('No manifest link', $result['error']);
    }

    /** @test MF-003: Invalid JSON Response */
    public function test_fetch_invalid_json_response(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response('{ invalid json', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertNull($result['manifest']);
        $this->assertEquals($manifestUrl, $result['manifest_url']);
        $this->assertEquals('INVALID_JSON', $result['error_code']);
    }

    /** @test MF-004: Manifest Fetch Timeout */
    public function test_fetch_manifest_timeout(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        // Simulate connection exception (timeout)
        Http::fake(function ($request) use ($manifestUrl) {
            if ($request->url() === $manifestUrl) {
                throw new \Illuminate\Http\Client\ConnectionException('Request timed out');
            }
            return Http::response('{}');
        });

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('TIMEOUT', $result['error_code']);
    }

    /** @test MF-005: Manifest Size Limit (10KB max) */
    public function test_fetch_manifest_size_limit(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        // Create a manifest larger than 10KB
        $largeManifest = json_encode([
            'name' => str_repeat('x', 11000), // > 10KB
        ]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response($largeManifest, 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('MANIFEST_TOO_LARGE', $result['error_code']);
    }

    /** @test MF-006: HTML Fetch Failure */
    public function test_fetch_html_fetch_failure(): void
    {
        $siteUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Failed to fetch HTML',
                'error_code' => 'CONNECTION_FAILED',
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('HTML_FETCH_FAILED', $result['error_code']);
    }

    /** @test MF-007: Null URL Input */
    public function test_fetch_null_url(): void
    {
        $result = $this->service->fetch(null);

        $this->assertFalse($result['success']);
        $this->assertNull($result['manifest']);
        $this->assertEquals('INVALID_URL', $result['error_code']);
        $this->assertStringContainsString('URL is required', $result['error']);
    }

    /** @test MF-008: Empty URL Input */
    public function test_fetch_empty_url(): void
    {
        $result = $this->service->fetch('');

        $this->assertFalse($result['success']);
        $this->assertNull($result['manifest']);
        $this->assertEquals('INVALID_URL', $result['error_code']);
    }

    /** @test MF-009: Manifest HTTP Error */
    public function test_fetch_manifest_http_error(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response('Forbidden', 403),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('MANIFEST_FETCH_FAILED', $result['error_code']);
    }

    /** @test MF-010: Complete Manifest With Icons */
    public function test_fetch_complete_manifest_with_icons(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $manifestContent = json_encode([
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'icons' => [
                ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ]);

        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response($manifestContent, 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['manifest']);
        $this->assertCount(2, $result['manifest']['icons']);
        $this->assertEquals('/icons/192.png', $result['manifest']['icons'][0]['src']);
    }

    // =========================================================================
    // Category 2: extractManifestUrl() Method Tests (EM-001 to EM-010)
    // =========================================================================

    /** @test EM-001: Standard Manifest Link */
    public function test_extract_manifest_url_standard_link(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EM-002: Manifest Link With Alternate Order */
    public function test_extract_manifest_url_alternate_order(): void
    {
        $html = '<!DOCTYPE html><html><head><link href="/manifest.json" rel="manifest"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EM-003: Manifest Link With Additional Attributes */
    public function test_extract_manifest_url_with_additional_attributes(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json" crossorigin="use-credentials"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EM-004: No Manifest Link */
    public function test_extract_manifest_url_no_link(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title><meta charset="utf-8"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertNull($result);
    }

    /** @test EM-005: Multiple Link Tags - Pick Manifest */
    public function test_extract_manifest_url_multiple_links(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="stylesheet" href="/style.css"><link rel="manifest" href="/manifest.json"><link rel="icon" href="/favicon.ico"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EM-006: Absolute Manifest URL */
    public function test_extract_manifest_url_absolute(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="https://cdn.example.com/manifest.json"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('https://cdn.example.com/manifest.json', $result);
    }

    /** @test EM-007: Protocol-Relative URL */
    public function test_extract_manifest_url_protocol_relative(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="//cdn.example.com/manifest.json"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('//cdn.example.com/manifest.json', $result);
    }

    /** @test EM-008: Query Parameters in URL */
    public function test_extract_manifest_url_with_query(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json?v=1.2.3"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json?v=1.2.3', $result);
    }

    /** @test EM-010: Empty href Attribute */
    public function test_extract_manifest_url_empty_href(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href=""></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertNull($result);
    }

    // =========================================================================
    // Category 3: resolveManifestUrl() Method Tests (RM-001 to RM-008)
    // =========================================================================

    /** @test RM-001: Relative Path */
    public function test_resolve_manifest_url_relative_path(): void
    {
        $result = $this->service->resolveManifestUrl('/manifest.json', 'https://example.com');

        $this->assertEquals('https://example.com/manifest.json', $result);
    }

    /** @test RM-002: Relative Path With Subdirectory */
    public function test_resolve_manifest_url_relative_with_subdirectory(): void
    {
        $result = $this->service->resolveManifestUrl('manifest.json', 'https://example.com/app/');

        $this->assertEquals('https://example.com/app/manifest.json', $result);
    }

    /** @test RM-003: Absolute URL */
    public function test_resolve_manifest_url_absolute(): void
    {
        $result = $this->service->resolveManifestUrl('https://cdn.example.com/pwa/manifest.json', 'https://example.com');

        $this->assertEquals('https://cdn.example.com/pwa/manifest.json', $result);
    }

    /** @test RM-004: Protocol-Relative URL */
    public function test_resolve_manifest_url_protocol_relative(): void
    {
        $result = $this->service->resolveManifestUrl('//cdn.example.com/manifest.json', 'https://example.com');

        $this->assertEquals('https://cdn.example.com/manifest.json', $result);
    }

    /** @test RM-005: Port in Base URL */
    public function test_resolve_manifest_url_with_port(): void
    {
        $result = $this->service->resolveManifestUrl('/manifest.json', 'https://example.com:8443');

        $this->assertEquals('https://example.com:8443/manifest.json', $result);
    }

    /** @test RM-006: Query String in Base URL */
    public function test_resolve_manifest_url_query_in_base(): void
    {
        $result = $this->service->resolveManifestUrl('/manifest.json', 'https://example.com/page?query=value');

        $this->assertEquals('https://example.com/manifest.json', $result);
    }

    /** @test RM-007: Fragment in Base URL */
    public function test_resolve_manifest_url_fragment_in_base(): void
    {
        $result = $this->service->resolveManifestUrl('/manifest.json', 'https://example.com/page#section');

        $this->assertEquals('https://example.com/manifest.json', $result);
    }

    /** @test RM-008: Null Manifest URL */
    public function test_resolve_manifest_url_null(): void
    {
        $result = $this->service->resolveManifestUrl(null, 'https://example.com');

        $this->assertNull($result);
    }

    // =========================================================================
    // Category 5: SSRF Protection Tests (SSRF-001 to SSRF-007)
    // =========================================================================

    /** @test SSRF-001: Localhost Blocked */
    public function test_ssrf_localhost_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'http://localhost/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="http://localhost/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this host is blocked for security reasons.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['error_code']);
    }

    /** @test SSRF-002: Private IP Blocked */
    public function test_ssrf_private_ip_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'http://192.168.1.1/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="http://192.168.1.1/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['error_code']);
    }

    /** @test SSRF-003: Cloud Metadata IP Blocked */
    public function test_ssrf_cloud_metadata_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'http://169.254.169.254/latest/meta-data/';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="http://169.254.169.254/latest/meta-data/"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['error_code']);
    }

    /** @test SSRF-004: Decimal IP Notation Blocked */
    public function test_ssrf_decimal_ip_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'http://2130706433/manifest.json'; // 127.0.0.1 in decimal
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="http://2130706433/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is blocked for security reasons.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['error_code']);
    }

    /** @test SSRF-005: Hex IP Notation Blocked */
    public function test_ssrf_hex_ip_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'http://0x7f000001/manifest.json'; // 127.0.0.1 in hex
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="http://0x7f000001/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is blocked for security reasons.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['error_code']);
    }

    /** @test SSRF-007: File Protocol Blocked */
    public function test_ssrf_file_protocol_blocked(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'file:///etc/passwd';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="file:///etc/passwd"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Invalid protocol. Only HTTP and HTTPS are allowed.',
            ]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertContains($result['error_code'], ['BLOCKED_URL', 'BLOCKED_PROTOCOL', 'INVALID_URL']);
    }

    // =========================================================================
    // Category 6: Manifest Content Validation Tests (MCV-001 to MCV-006)
    // =========================================================================

    /** @test MCV-001: Empty Manifest Object */
    public function test_manifest_validation_empty_object(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response('{}', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['manifest']);
    }

    /** @test MCV-002: Manifest With Only Name */
    public function test_manifest_validation_minimal(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response('{"name": "App"}', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(['name' => 'App'], $result['manifest']);
    }

    /** @test MCV-003: Unicode in Manifest */
    public function test_manifest_validation_unicode(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        $manifestContent = json_encode([
            'name' => '示例应用',
            'description' => '日本語の説明',
        ]);

        Http::fake([
            $manifestUrl => Http::response($manifestContent, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals('示例应用', $result['manifest']['name']);
        $this->assertEquals('日本語の説明', $result['manifest']['description']);
    }

    // =========================================================================
    // Category 7: Edge Cases Tests (EC-001 to EC-008)
    // =========================================================================

    /** @test EC-001: Self-Closing Link Tag */
    public function test_edge_case_self_closing_tag(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json" /></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EC-002: Multiple Manifest Links */
    public function test_edge_case_multiple_manifest_links(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest-default.json"><link rel="manifest" href="/manifest-alt.json"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        // Should return the first manifest link
        $this->assertEquals('/manifest-default.json', $result);
    }

    /** @test EC-003: Whitespace in href */
    public function test_edge_case_whitespace_in_href(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="  /manifest.json  "></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        // Should be trimmed
        $this->assertEquals('/manifest.json', $result);
    }

    /** @test EC-004: HTML Entities in href */
    public function test_edge_case_html_entities_in_href(): void
    {
        $html = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json?app=test&amp;v=1"></head><body></body></html>';
        $baseUrl = 'https://example.com';

        $result = $this->service->extractManifestUrl($html, $baseUrl);

        // HTML entities should be decoded
        $this->assertEquals('/manifest.json?app=test&v=1', $result);
    }

    /** @test EC-005: BOM in Manifest JSON */
    public function test_edge_case_bom_in_manifest(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        // UTF-8 BOM + JSON
        $bomManifest = "\xEF\xBB\xBF" . '{"name": "App"}';

        Http::fake([
            $manifestUrl => Http::response($bomManifest, 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(['name' => 'App'], $result['manifest']);
    }

    /** @test EC-006: JSONP Response */
    public function test_edge_case_jsonp_response(): void
    {
        $siteUrl = 'https://example.com';
        $manifestUrl = 'https://example.com/manifest.json';
        $htmlContent = '<!DOCTYPE html><html><head><link rel="manifest" href="/manifest.json"></head><body></body></html>';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($siteUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $siteUrl]);

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($manifestUrl)
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => $manifestUrl]);

        $this->htmlFetcherService
            ->shouldReceive('fetch')
            ->with($siteUrl, Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'content' => $htmlContent,
                'finalUrl' => $siteUrl,
                'statusCode' => 200,
            ]);

        Http::fake([
            $manifestUrl => Http::response('callback({"name": "App"});', 200, [
                'Content-Type' => 'application/javascript',
            ]),
        ]);

        $result = $this->service->fetch($siteUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_JSON', $result['error_code']);
    }
}
