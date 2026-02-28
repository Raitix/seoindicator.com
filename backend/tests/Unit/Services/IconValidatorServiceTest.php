<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\IconValidatorService;
use App\Services\URLValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for IconValidatorService.
 * 
 * Tests icon validation, accessibility checks, and SSRF protection.
 * 
 * @see TESTS/SPEC-24-5.md
 * @see TASKS/TASK-24-5.md
 */
class IconValidatorServiceTest extends TestCase
{
    private IconValidatorService $service;
    private URLValidationService $urlValidationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlValidationService = Mockery::mock(URLValidationService::class);
        $this->service = new IconValidatorService($this->urlValidationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Category 1: validateIcons() Method Tests (IV-001 to IV-012)
    // =========================================================================

    /** @test IV-001: Validate Icons Successfully */
    public function test_validate_icons_successfully(): void
    {
        $icons = [
            ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/icons/512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/icons/180.png', 'sizes' => '180x180', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com';

        // Mock URL validation for each icon
        foreach (['192', '512', '180'] as $size) {
            $this->urlValidationService
                ->shouldReceive('validate')
                ->with("https://example.com/icons/{$size}.png")
                ->once()
                ->andReturn(['valid' => true, 'normalized_url' => "https://example.com/icons/{$size}.png"]);
        }

        // Mock HTTP HEAD requests
        Http::fake([
            'https://example.com/icons/*' => Http::response('', 200, ['Content-Type' => 'image/png']),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['valid']);
        $this->assertTrue($result['has_192x192']);
        $this->assertTrue($result['has_512x512']);
        $this->assertTrue($result['has_180x180']);
        $this->assertCount(3, $result['icons']);
        $this->assertEmpty($result['errors']);
    }

    /** @test IV-002: Missing Required Icon Sizes */
    public function test_validate_icons_missing_required_sizes(): void
    {
        $icons = [
            ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://example.com/icons/192.png']);

        Http::fake([
            'https://example.com/icons/192.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['valid']);
        $this->assertTrue($result['has_192x192']);
        $this->assertFalse($result['has_512x512']); // Missing!
        $this->assertFalse($result['has_180x180']);
    }

    /** @test IV-003: Empty Icons Array */
    public function test_validate_icons_empty_array(): void
    {
        $result = $this->service->validateIcons([], 'https://example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['valid']);
        $this->assertFalse($result['has_192x192']);
        $this->assertFalse($result['has_512x512']);
        $this->assertFalse($result['has_180x180']);
        $this->assertEmpty($result['icons']);
        $this->assertEmpty($result['errors']);
    }

    /** @test IV-004: Null Icons Input */
    public function test_validate_icons_null_input(): void
    {
        $result = $this->service->validateIcons(null, 'https://example.com');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_INPUT', $result['error_code']);
        $this->assertStringContainsString('Icons array is required', $result['error']);
    }

    /** @test IV-005: Null Base URL */
    public function test_validate_icons_null_base_url(): void
    {
        $icons = [['src' => '/icon.png', 'sizes' => '192x192']];

        $result = $this->service->validateIcons($icons, null);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_INPUT', $result['error_code']);
        $this->assertStringContainsString('Base URL is required', $result['error']);
    }

    /** @test IV-006: Inaccessible Icon URL */
    public function test_validate_icons_inaccessible(): void
    {
        $icons = [
            ['src' => '/icons/missing.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://example.com/icons/missing.png']);

        Http::fake([
            'https://example.com/icons/missing.png' => Http::response('', 404),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']); // Partial success
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['valid']);
        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test IV-007: Icon Timeout */
    public function test_validate_icons_timeout(): void
    {
        $icons = [
            ['src' => '/icons/slow.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://example.com/icons/slow.png']);

        Http::fake(function ($request) {
            if ($request->url() === 'https://example.com/icons/slow.png') {
                throw new \Illuminate\Http\Client\ConnectionException('Request timed out');
            }
            return Http::response('');
        });

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['valid']);
        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertStringContainsString('timeout', strtolower($result['icons'][0]['error']));
    }

    /** @test IV-008: Concurrent Validation */
    public function test_validate_icons_concurrent(): void
    {
        // Create 10 icons (should validate in batches of 5)
        $icons = [];
        for ($i = 1; $i <= 10; $i++) {
            $icons[] = ['src' => "/icons/icon{$i}.png", 'sizes' => '192x192', 'type' => 'image/png'];
        }
        $baseUrl = 'https://example.com';

        // Mock URL validation for all icons
        $this->urlValidationService
            ->shouldReceive('validate')
            ->times(10)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icons/*' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(10, $result['valid']);
    }

    /** @test IV-009: Relative URL Resolution */
    public function test_validate_icons_relative_url(): void
    {
        $icons = [
            ['src' => '/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com/pwa/';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://example.com/icons/192.png')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://example.com/icons/192.png']);

        Http::fake([
            'https://example.com/icons/192.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://example.com/icons/192.png', $result['icons'][0]['resolved_url']);
    }

    /** @test IV-010: Absolute Icon URL */
    public function test_validate_icons_absolute_url(): void
    {
        $icons = [
            ['src' => 'https://cdn.example.com/icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://cdn.example.com/icons/192.png')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://cdn.example.com/icons/192.png']);

        Http::fake([
            'https://cdn.example.com/icons/192.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://cdn.example.com/icons/192.png', $result['icons'][0]['resolved_url']);
    }

    /** @test IV-011: Mixed Required and Recommended Sizes */
    public function test_validate_icons_mixed_sizes(): void
    {
        $icons = [
            ['src' => '/192.png', 'sizes' => '192x192'],
            ['src' => '/180.png', 'sizes' => '180x180'], // iOS recommended
            ['src' => '/256.png', 'sizes' => '256x256'], // Other size
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->times(3)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/*.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['has_192x192']);
        $this->assertFalse($result['has_512x512']);
        $this->assertTrue($result['has_180x180']);
    }

    /** @test IV-012: Icon With "any" Size */
    public function test_validate_icons_any_size(): void
    {
        $icons = [
            ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.svg' => Http::response('', 200, ['Content-Type' => 'image/svg+xml']),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertEquals('any', $result['icons'][0]['sizes']);
        $this->assertTrue($result['icons'][0]['is_svg'] ?? false);
    }

    // =========================================================================
    // Category 2: validateIcon() Method Tests (VI-001 to VI-008)
    // =========================================================================

    /** @test VI-001: Valid Single Icon */
    public function test_validate_icon_valid(): void
    {
        $icon = ['src' => '/icon.png', 'sizes' => '192x192', 'type' => 'image/png'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://example.com/icon.png']);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertEquals('/icon.png', $result['src']);
        $this->assertEquals('https://example.com/icon.png', $result['resolved_url']);
        $this->assertTrue($result['accessible']);
        $this->assertEquals('192x192', $result['sizes']);
        $this->assertEquals(192, $result['width']);
        $this->assertEquals(192, $result['height']);
    }

    /** @test VI-002: Invalid Size String */
    public function test_validate_icon_invalid_size(): void
    {
        $icon = ['src' => '/icon.png', 'sizes' => 'invalid', 'type' => 'image/png'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
        $this->assertEquals('invalid', $result['sizes']);
        $this->assertNull($result['width']);
        $this->assertNull($result['height']);
    }

    /** @test VI-003: Missing src Attribute */
    public function test_validate_icon_missing_src(): void
    {
        $icon = ['sizes' => '192x192', 'type' => 'image/png'];
        $baseUrl = 'https://example.com';

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertFalse($result['accessible']);
        $this->assertStringContainsString('missing src', strtolower($result['error']));
    }

    /** @test VI-004: Maskable Icon Purpose */
    public function test_validate_icon_maskable(): void
    {
        $icon = [
            'src' => '/maskable.png',
            'sizes' => '512x512',
            'purpose' => 'maskable any',
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/maskable.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
        $this->assertEquals('maskable any', $result['purpose']);
        $this->assertTrue($result['is_maskable']);
    }

    /** @test VI-005: Icon With Query Parameters */
    public function test_validate_icon_query_params(): void
    {
        $icon = ['src' => '/icon.png?v=1.2.3', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://example.com/icon.png?v=1.2.3')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png?v=1.2.3' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertEquals('https://example.com/icon.png?v=1.2.3', $result['resolved_url']);
    }

    /** @test VI-006: Protocol-Relative URL */
    public function test_validate_icon_protocol_relative(): void
    {
        $icon = ['src' => '//cdn.example.com/icon.png', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://cdn.example.com/icon.png')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://cdn.example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertEquals('https://cdn.example.com/icon.png', $result['resolved_url']);
    }

    /** @test VI-007: Multiple Sizes in String */
    public function test_validate_icon_multiple_sizes(): void
    {
        $icon = ['src' => '/icon.png', 'sizes' => '72x72 96x96 128x128 192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertEquals('72x72 96x96 128x128 192x192', $result['sizes']);
        $this->assertCount(4, $result['parsed_sizes']);
        $this->assertTrue($result['has_192x192']);
    }

    /** @test VI-008: Data URI Icon */
    public function test_validate_icon_data_uri(): void
    {
        $icon = ['src' => 'data:image/png;base64,ABC123==', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']); // Data URIs are inherently accessible
        $this->assertTrue($result['is_data_uri']);
    }

    // =========================================================================
    // Category 3: checkIconAccessible() Method Tests (CI-001 to CI-010)
    // =========================================================================

    /** @test CI-001: Accessible Icon (HEAD 200) */
    public function test_check_icon_accessible_200(): void
    {
        $url = 'https://example.com/icon.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with($url)
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 200),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertTrue($result);
    }

    /** @test CI-002: Inaccessible Icon (HEAD 404) */
    public function test_check_icon_accessible_404(): void
    {
        $url = 'https://example.com/missing.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 404),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertFalse($result);
    }

    /** @test CI-003: Forbidden Icon (HEAD 403) */
    public function test_check_icon_accessible_403(): void
    {
        $url = 'https://example.com/protected.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 403),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertFalse($result);
    }

    /** @test CI-004: Server Error (HEAD 500) */
    public function test_check_icon_accessible_500(): void
    {
        $url = 'https://example.com/error.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 500),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertFalse($result);
    }

    /** @test CI-005: Timeout Handling */
    public function test_check_icon_accessible_timeout(): void
    {
        $url = 'https://example.com/slow.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake(function ($request) use ($url) {
            if ($request->url() === $url) {
                throw new \Illuminate\Http\Client\ConnectionException('Request timed out');
            }
            return Http::response('');
        });

        $result = $this->service->checkIconAccessible($url);

        $this->assertFalse($result);
    }

    /** @test CI-006: Redirect Follow */
    public function test_check_icon_accessible_redirect(): void
    {
        $url = 'https://example.com/icon'; // Redirects to /icon.png

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 302, ['Location' => 'https://example.com/icon.png']),
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertTrue($result);
    }

    /** @test CI-007: Invalid URL */
    public function test_check_icon_accessible_invalid_url(): void
    {
        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('not-a-valid-url')
            ->once()
            ->andReturn(['valid' => false, 'error' => 'Invalid URL format']);

        $result = $this->service->checkIconAccessible('not-a-valid-url');

        $this->assertFalse($result);
    }

    /** @test CI-008: Null URL */
    public function test_check_icon_accessible_null(): void
    {
        $result = $this->service->checkIconAccessible(null);

        $this->assertFalse($result);
    }

    /** @test CI-009: Content-Type Validation */
    public function test_check_icon_accessible_wrong_content_type(): void
    {
        $url = 'https://example.com/icon.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 200, ['Content-Type' => 'text/html']),
        ]);

        $result = $this->service->validateIcon(['src' => $url, 'sizes' => '192x192'], 'https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('text/html', $result['content_type']);
        $this->assertArrayHasKey('warning', $result);
    }

    /** @test CI-010: File Size Header */
    public function test_check_icon_accessible_large_file(): void
    {
        $url = 'https://example.com/large.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 200, ['Content-Length' => '50000000']),
        ]);

        $result = $this->service->validateIcon(['src' => $url, 'sizes' => '192x192'], 'https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals(50000000, $result['content_length']);
        $this->assertArrayHasKey('warning', $result);
    }

    // =========================================================================
    // Category 4: SSRF Protection Tests (SSRF-001 to SSRF-008)
    // =========================================================================

    /** @test SSRF-001: Localhost Icon URL Blocked */
    public function test_ssrf_localhost_blocked(): void
    {
        $icons = [
            ['src' => 'http://localhost/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('http://localhost/icon.png')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this host is blocked for security reasons.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertEquals('BLOCKED_URL', $result['icons'][0]['error']);
    }

    /** @test SSRF-002: Private IP Icon URL Blocked */
    public function test_ssrf_private_ip_blocked(): void
    {
        $icons = [
            ['src' => 'http://192.168.1.1/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertEquals('BLOCKED_URL', $result['icons'][0]['error']);
    }

    /** @test SSRF-003: Cloud Metadata Icon URL Blocked */
    public function test_ssrf_cloud_metadata_blocked(): void
    {
        $icons = [
            ['src' => 'http://169.254.169.254/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertEquals('BLOCKED_URL', $result['icons'][0]['error']);
    }

    /** @test SSRF-004: Decimal IP Icon URL Blocked */
    public function test_ssrf_decimal_ip_blocked(): void
    {
        $icons = [
            ['src' => 'http://2130706433/icon.png', 'sizes' => '192x192'], // 127.0.0.1
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is blocked for security reasons.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertEquals('BLOCKED_URL', $result['icons'][0]['error']);
    }

    /** @test SSRF-005: Hex IP Icon URL Blocked */
    public function test_ssrf_hex_ip_blocked(): void
    {
        $icons = [
            ['src' => 'http://0x7f000001/icon.png', 'sizes' => '192x192'], // 127.0.0.1
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is blocked for security reasons.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertEquals('BLOCKED_URL', $result['icons'][0]['error']);
    }

    /** @test SSRF-006: File Protocol Blocked */
    public function test_ssrf_file_protocol_blocked(): void
    {
        $icons = [
            ['src' => 'file:///etc/passwd', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Invalid protocol. Only HTTP and HTTPS are allowed.',
            ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertFalse($result['icons'][0]['accessible']);
        $this->assertContains($result['icons'][0]['error'], ['BLOCKED_URL', 'BLOCKED_PROTOCOL', 'INVALID_URL']);
    }

    /** @test SSRF-007: Redirect to Private IP Blocked */
    public function test_ssrf_redirect_to_private_blocked(): void
    {
        $url = 'https://public.com/icon.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->urlValidationService
            ->shouldReceive('validateRedirect')
            ->with('http://192.168.1.1/icon.png')
            ->once()
            ->andReturn([
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ]);

        Http::fake([
            $url => Http::response('', 302, ['Location' => 'http://192.168.1.1/icon.png']),
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertFalse($result);
    }

    /** @test SSRF-008: Cross-Origin CDN Allowed */
    public function test_ssrf_cross_origin_cdn_allowed(): void
    {
        $icons = [
            ['src' => 'https://cdn.cloudflare.com/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://cdn.cloudflare.com/icon.png')
            ->once()
            ->andReturn(['valid' => true, 'normalized_url' => 'https://cdn.cloudflare.com/icon.png']);

        Http::fake([
            'https://cdn.cloudflare.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['icons'][0]['accessible']);
    }

    // =========================================================================
    // Category 5: Size Parsing Tests (SP-001 to SP-008)
    // =========================================================================

    /** @test SP-001: Standard Size Format */
    public function test_parse_size_standard(): void
    {
        $result = $this->service->parseSize('192x192');

        $this->assertEquals(192, $result['width']);
        $this->assertEquals(192, $result['height']);
    }

    /** @test SP-002: "any" Size */
    public function test_parse_size_any(): void
    {
        $result = $this->service->parseSize('any');

        $this->assertNull($result['width']);
        $this->assertNull($result['height']);
        $this->assertTrue($result['is_svg']);
    }

    /** @test SP-003: Multiple Sizes */
    public function test_parse_size_multiple(): void
    {
        $result = $this->service->parseSize('72x72 96x96 128x128');

        $this->assertCount(3, $result['sizes']);
        $this->assertEquals(['width' => 72, 'height' => 72], $result['sizes'][0]);
        $this->assertEquals(['width' => 96, 'height' => 96], $result['sizes'][1]);
        $this->assertEquals(['width' => 128, 'height' => 128], $result['sizes'][2]);
    }

    /** @test SP-004: Invalid Size String */
    public function test_parse_size_invalid(): void
    {
        $result = $this->service->parseSize('invalid');

        $this->assertNull($result['width']);
        $this->assertNull($result['height']);
    }

    /** @test SP-005: Empty Size String */
    public function test_parse_size_empty(): void
    {
        $result = $this->service->parseSize('');

        $this->assertNull($result['width']);
        $this->assertNull($result['height']);
    }

    /** @test SP-006: Non-Square Size */
    public function test_parse_size_non_square(): void
    {
        $result = $this->service->parseSize('512x256');

        $this->assertEquals(512, $result['width']);
        $this->assertEquals(256, $result['height']);
    }

    /** @test SP-007: Case Sensitivity */
    public function test_parse_size_uppercase(): void
    {
        $result = $this->service->parseSize('192X192');

        $this->assertEquals(192, $result['width']);
        $this->assertEquals(192, $result['height']);
    }

    /** @test SP-008: Whitespace Handling */
    public function test_parse_size_whitespace(): void
    {
        $result = $this->service->parseSize(' 192 x 192 ');

        $this->assertEquals(192, $result['width']);
        $this->assertEquals(192, $result['height']);
    }

    // =========================================================================
    // Category 6: URL Resolution Tests (UR-001 to UR-008)
    // =========================================================================

    /** @test UR-001: Relative Path Resolution */
    public function test_resolve_icon_url_relative(): void
    {
        $result = $this->service->resolveIconUrl('/icons/192.png', 'https://example.com');

        $this->assertEquals('https://example.com/icons/192.png', $result);
    }

    /** @test UR-002: Relative Path With Subdirectory */
    public function test_resolve_icon_url_subdirectory(): void
    {
        $result = $this->service->resolveIconUrl('icon.png', 'https://example.com/pwa/');

        $this->assertEquals('https://example.com/pwa/icon.png', $result);
    }

    /** @test UR-003: Absolute URL Unchanged */
    public function test_resolve_icon_url_absolute(): void
    {
        $result = $this->service->resolveIconUrl('https://cdn.example.com/icon.png', 'https://example.com');

        $this->assertEquals('https://cdn.example.com/icon.png', $result);
    }

    /** @test UR-004: Protocol-Relative URL */
    public function test_resolve_icon_url_protocol_relative(): void
    {
        $result = $this->service->resolveIconUrl('//cdn.example.com/icon.png', 'https://example.com');

        $this->assertEquals('https://cdn.example.com/icon.png', $result);
    }

    /** @test UR-005: Query Parameters Preserved */
    public function test_resolve_icon_url_query(): void
    {
        $result = $this->service->resolveIconUrl('/icon.png?v=1.2.3', 'https://example.com');

        $this->assertEquals('https://example.com/icon.png?v=1.2.3', $result);
    }

    /** @test UR-006: Fragment Preserved */
    public function test_resolve_icon_url_fragment(): void
    {
        $result = $this->service->resolveIconUrl('/icon.svg#icon-id', 'https://example.com');

        $this->assertEquals('https://example.com/icon.svg#icon-id', $result);
    }

    /** @test UR-007: Port in Base URL */
    public function test_resolve_icon_url_port(): void
    {
        $result = $this->service->resolveIconUrl('/icon.png', 'https://example.com:8443');

        $this->assertEquals('https://example.com:8443/icon.png', $result);
    }

    /** @test UR-008: Data URI Pass-Through */
    public function test_resolve_icon_url_data_uri(): void
    {
        $dataUri = 'data:image/png;base64,ABC123==';
        $result = $this->service->resolveIconUrl($dataUri, 'https://example.com');

        $this->assertEquals($dataUri, $result);
    }

    // =========================================================================
    // Category 7: Result DTO Tests (RD-001 to RD-006)
    // =========================================================================

    /** @test RD-001: Complete Result Structure */
    public function test_result_structure(): void
    {
        $icons = [
            ['src' => '/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('has_192x192', $result);
        $this->assertArrayHasKey('has_512x512', $result);
        $this->assertArrayHasKey('has_180x180', $result);
        $this->assertArrayHasKey('icons', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /** @test RD-002: Total vs Valid Count */
    public function test_result_count_accuracy(): void
    {
        $icons = [
            ['src' => '/valid.png', 'sizes' => '192x192'],
            ['src' => '/invalid.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/valid.png' => Http::response('', 200),
            'https://example.com/invalid.png' => Http::response('', 404),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['valid']);
    }

    /** @test RD-003: Errors Array Population */
    public function test_result_errors_array(): void
    {
        $icons = [
            ['src' => '/error1.png', 'sizes' => '192x192'],
            ['src' => '/error2.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/error1.png' => Http::response('', 404),
            'https://example.com/error2.png' => Http::response('', 500),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertCount(2, $result['errors']);
    }

    /** @test RD-004: Required Size Flags Accuracy */
    public function test_result_size_flags(): void
    {
        $icons = [
            ['src' => '/192.png', 'sizes' => '192x192'],
            ['src' => '/180.png', 'sizes' => '180x180'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/*.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['has_192x192']);
        $this->assertFalse($result['has_512x512']);
        $this->assertTrue($result['has_180x180']);
    }

    /** @test RD-005: Warnings for Non-Critical Issues */
    public function test_result_warnings(): void
    {
        $icons = [
            ['src' => '/icon.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200, ['Content-Type' => 'text/html']),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['warnings'] ?? []);
    }

    /** @test RD-006: Success Flag Logic */
    public function test_result_success_partial(): void
    {
        $icons = [
            ['src' => '/valid.png', 'sizes' => '192x192'],
            ['src' => '/invalid.png', 'sizes' => '192x192'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/valid.png' => Http::response('', 200),
            'https://example.com/invalid.png' => Http::response('', 404),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        // Partial success - some icons valid
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['valid']);
    }

    // =========================================================================
    // Category 8: Edge Cases Tests (EC-001 to EC-010)
    // =========================================================================

    /** @test EC-001: Empty src Attribute */
    public function test_edge_case_empty_src(): void
    {
        $icon = ['src' => '', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertFalse($result['accessible']);
        $this->assertStringContainsString('missing src', strtolower($result['error']));
    }

    /** @test EC-002: Very Long Icon URL */
    public function test_edge_case_long_url(): void
    {
        $longPath = str_repeat('/subdir', 300);
        $icon = ['src' => $longPath . '/icon.png', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'error' => 'URL is too long']);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertFalse($result['accessible']);
    }

    /** @test EC-003: Unicode in Icon URL */
    public function test_edge_case_unicode_url(): void
    {
        $icon = ['src' => '/アイコン.png', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
    }

    /** @test EC-004: Special Characters in URL */
    public function test_edge_case_special_chars(): void
    {
        $icon = ['src' => '/icon (1).png', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
    }

    /** @test EC-005: Missing sizes Attribute */
    public function test_edge_case_missing_sizes(): void
    {
        $icon = ['src' => '/icon.png', 'type' => 'image/png'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
        $this->assertArrayHasKey('warning', $result);
    }

    /** @test EC-006: Missing type Attribute */
    public function test_edge_case_missing_type(): void
    {
        $icon = ['src' => '/icon.png', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertTrue($result['accessible']);
    }

    /** @test EC-007: Icon Index in Result */
    public function test_edge_case_icon_index(): void
    {
        $icons = [
            ['src' => '/icon1.png', 'sizes' => '192x192'],
            ['src' => '/icon2.png', 'sizes' => '512x512'],
            ['src' => '/icon3.png', 'sizes' => '180x180'],
        ];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->times(3)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon*.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        // Icons should be in original order
        $this->assertEquals('/icon1.png', $result['icons'][0]['src']);
        $this->assertEquals('/icon2.png', $result['icons'][1]['src']);
        $this->assertEquals('/icon3.png', $result['icons'][2]['src']);
    }

    /** @test EC-008: Concurrent Request Limits */
    public function test_edge_case_concurrent_limit(): void
    {
        // Create 20 icons - should batch into groups of 5
        $icons = [];
        for ($i = 1; $i <= 20; $i++) {
            $icons[] = ['src' => "/icon{$i}.png", 'sizes' => '192x192'];
        }
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->times(20)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/icon*.png' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcons($icons, $baseUrl);

        $this->assertEquals(20, $result['total']);
        $this->assertEquals(20, $result['valid']);
    }

    /** @test EC-009: Head Request Not Supported */
    public function test_edge_case_head_not_supported(): void
    {
        $url = 'https://example.com/icon.png';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // HEAD returns 405, should fallback to GET
        Http::fake([
            $url => Http::sequence()
                ->push('', 405) // HEAD not allowed
                ->push('', 200), // GET works
        ]);

        $result = $this->service->checkIconAccessible($url);

        $this->assertTrue($result);
    }

    /** @test EC-010: Self-Referencing URL */
    public function test_edge_case_self_referencing(): void
    {
        $icon = ['src' => '/', 'sizes' => '192x192'];
        $baseUrl = 'https://example.com';

        $this->urlValidationService
            ->shouldReceive('validate')
            ->with('https://example.com/')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com/' => Http::response('', 200),
        ]);

        $result = $this->service->validateIcon($icon, $baseUrl);

        $this->assertEquals('https://example.com/', $result['resolved_url']);
    }
}
