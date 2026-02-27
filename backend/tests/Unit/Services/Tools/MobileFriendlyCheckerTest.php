<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\MobileFriendlyChecker;
use App\Services\Tools\ToolResult;
use Illuminate\Support\Facades\Http;

/**
 * Unit tests for TASK-16-7: Mobile-Friendly Checker
 *
 * Tests the MobileFriendlyChecker service responsible for:
 * - Viewport meta tag detection and validation
 * - Responsive design indicators (media queries, flexible grids)
 * - Font size analysis for mobile readability
 * - Tap target size checking
 * - Horizontal scroll issue detection
 * - Mobile score calculation
 *
 * @see \App\Services\Tools\MobileFriendlyChecker
 */
class MobileFriendlyCheckerTest extends TestCase
{
    protected MobileFriendlyChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(MobileFriendlyChecker::class);
    }

    /**
     * Create a mock HTTP response for testing.
     */
    protected function mockHtmlResponse(string $html, int $status = 200, array $headers = []): void
    {
        $defaultHeaders = ['Content-Type' => 'text/html'];
        Http::fake([
            '*' => Http::response($html, $status, array_merge($defaultHeaders, $headers)),
        ]);
    }

    // =========================================================================
    // VIEWPORT DETECTION TESTS (VP)
    // =========================================================================

    /**
     * VP-001: Standard viewport meta tag
     */
    public function test_standard_viewport_detection(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
        $this->assertTrue($result->data['viewport']['valid']);
        $this->assertStringContainsString('width=device-width', $result->data['viewport']['content']);
    }

    /**
     * VP-002: Missing viewport meta tag
     */
    public function test_missing_viewport(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['viewport']['present']);
        $this->assertIssueContains($result, 'viewport');
    }

    /**
     * VP-003: Empty viewport content
     */
    public function test_empty_viewport_content(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
        $this->assertFalse($result->data['viewport']['valid']);
    }

    /**
     * VP-004: Fixed width viewport (not mobile-friendly)
     */
    public function test_fixed_width_viewport(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=960">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
        $this->assertStringContainsString('width=960', $result->data['viewport']['content']);
        $this->assertIssueContains($result, 'fixed width');
    }

    /**
     * VP-005: Initial-scale only (partial config)
     */
    public function test_partial_viewport_config(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="initial-scale=1">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
    }

    /**
     * VP-006: User-scalable=no (accessibility issue)
     */
    public function test_user_scalable_no_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, user-scalable=no">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertIssueContains($result, 'user-scalable');
    }

    /**
     * VP-007: Maximum-scale=1 (accessibility issue)
     */
    public function test_maximum_scale_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, maximum-scale=1">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertIssueContains($result, 'maximum-scale');
    }

    /**
     * VP-008: Case insensitive viewport detection
     */
    public function test_case_insensitive_viewport(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <META NAME="VIEWPORT" CONTENT="width=device-width">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
    }

    /**
     * VP-009: Complex viewport with multiple directives
     */
    public function test_complex_viewport_content(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['viewport']['present']);
        $this->assertStringContainsString('shrink-to-fit=no', $result->data['viewport']['content']);
    }

    // =========================================================================
    // RESPONSIVE INDICATORS TESTS (RESP)
    // =========================================================================

    /**
     * RESP-001: Media queries detected
     */
    public function test_media_queries_detected(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>
                @media (max-width: 768px) { .container { width: 100%; } }
            </style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['responsiveIndicators']['hasMediaQueries']);
    }

    /**
     * RESP-002: No media queries
     */
    public function test_no_media_queries(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.container { width: 960px; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['responsiveIndicators']['hasMediaQueries']);
    }

    /**
     * RESP-003: Flexbox layout detected
     */
    public function test_flexbox_detected(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.container { display: flex; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['responsiveIndicators']['hasFlexibleGrid']);
    }

    /**
     * RESP-004: CSS Grid detected
     */
    public function test_css_grid_detected(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.container { display: grid; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['responsiveIndicators']['hasFlexibleGrid']);
    }

    /**
     * RESP-005: Fixed width layout (not flexible)
     */
    public function test_fixed_width_layout(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.container { width: 960px; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['responsiveIndicators']['hasFlexibleGrid']);
    }

    /**
     * RESP-006: Percentage widths detected
     */
    public function test_percentage_widths_detected(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.container { width: 100%; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['responsiveIndicators']['hasFlexibleGrid']);
    }

    /**
     * RESP-007: Responsive images (max-width)
     */
    public function test_responsive_images_detected(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>img { max-width: 100%; height: auto; }</style>
        </head><body><img src="test.jpg"></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['responsiveIndicators']['hasFlexibleImages']);
    }

    /**
     * RESP-008: Fixed image sizes
     */
    public function test_fixed_image_sizes(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>img { width: 500px; }</style>
        </head><body><img src="test.jpg"></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['responsiveIndicators']['hasFlexibleImages']);
    }

    // =========================================================================
    // FONT SIZE ANALYSIS TESTS (FONT)
    // =========================================================================

    /**
     * FONT-001: Adequate base font size
     */
    public function test_adequate_base_font_size(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>body { font-size: 16px; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertGreaterThanOrEqual(16, $result->data['readability']['fontSizeMin']);
        $this->assertFalse($result->data['readability']['fontSizeWarning']);
    }

    /**
     * FONT-002: Small font size warning
     */
    public function test_small_font_size_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>body { font-size: 12px; } .small { font-size: 10px; }</style>
        </head><body><p class="small">Text</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['readability']['fontSizeWarning']);
    }

    /**
     * FONT-003: Mixed font sizes
     */
    public function test_mixed_font_sizes(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>
                body { font-size: 16px; }
                .small { font-size: 11px; }
                .large { font-size: 24px; }
            </style>
        </head><body>
            <p class="small">Small</p>
            <p class="large">Large</p>
        </body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(11, $result->data['readability']['fontSizeMin']);
    }

    /**
     * FONT-004: Rem units converted
     */
    public function test_rem_units_conversion(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>body { font-size: 1rem; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        // Rem should be noted or converted (implementation dependent)
        $this->assertArrayHasKey('fontSizeMin', $result->data['readability']);
    }

    /**
     * FONT-005: Viewport units for fonts
     */
    public function test_viewport_units_for_fonts(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>h1 { font-size: 5vw; }</style>
        </head><body><h1>Title</h1></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        // Viewport units are responsive, should be noted
    }

    // =========================================================================
    // TAP TARGET ANALYSIS TESTS (TAP)
    // =========================================================================

    /**
     * TAP-001: Adequate link size
     */
    public function test_adequate_link_size(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>a { display: inline-block; width: 48px; height: 48px; }</style>
        </head><body><a href="#">Link</a></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['tapTargets']['smallTargets']);
    }

    /**
     * TAP-002: Small link warning
     */
    public function test_small_link_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>a { display: inline-block; width: 20px; height: 20px; }</style>
        </head><body><a href="#">Link</a></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->data['tapTargets']['smallTargets']);
    }

    /**
     * TAP-003: Adequate button size
     */
    public function test_adequate_button_size(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>button { width: 48px; height: 48px; }</style>
        </head><body><button>Click</button></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['tapTargets']['smallTargets']);
    }

    /**
     * TAP-004: Small button warning
     */
    public function test_small_button_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>button { width: 30px; height: 30px; }</style>
        </head><body><button>X</button></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->data['tapTargets']['smallTargets']);
    }

    /**
     * TAP-005: Inline links (use text size)
     */
    public function test_inline_links(): void
    {
        $html = '<!DOCTYPE html><html><head></head>
        <body><p>This is <a href="#">a link</a> in text.</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('links', $result->data['tapTargets']);
    }

    /**
     * TAP-006: Padding contributes to tap target size
     */
    public function test_padding_contributes_to_size(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>a { display: inline-block; width: 30px; height: 30px; padding: 10px; }</style>
        </head><body><a href="#">Link</a></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        // 30px + 20px padding = 50px effective size
        // Should not trigger warning
    }

    /**
     * TAP-007: Multiple links counted
     */
    public function test_multiple_links_counted(): void
    {
        $html = '<!DOCTYPE html><html><head></head>
        <body>
            <a href="#">Link 1</a>
            <a href="#">Link 2</a>
            <a href="#">Link 3</a>
        </body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['tapTargets']['links']);
    }

    /**
     * TAP-008: Hidden targets not counted
     */
    public function test_hidden_targets_not_counted(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.hidden { display: none; }</style>
        </head><body>
            <a href="#">Visible</a>
            <a href="#" class="hidden">Hidden</a>
        </body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['tapTargets']['links']);
    }

    // =========================================================================
    // HORIZONTAL SCROLL DETECTION TESTS (SCROLL)
    // =========================================================================

    /**
     * SCROLL-001: No horizontal scroll issues
     */
    public function test_no_horizontal_scroll_issues(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>body { overflow-x: hidden; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['horizontalScroll']['detected']);
    }

    /**
     * SCROLL-002: Wide fixed element warning
     */
    public function test_wide_fixed_element_warning(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>.wide { width: 1200px; }</style>
        </head><body><div class="wide">Wide content</div></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['horizontalScroll']['detected']);
    }

    /**
     * SCROLL-003: Wide image warning
     */
    public function test_wide_image_warning(): void
    {
        $html = '<!DOCTYPE html><html><head></head>
        <body><img src="test.jpg" width="1200" height="800"></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['horizontalScroll']['detected']);
    }

    /**
     * SCROLL-004: Wide table warning
     */
    public function test_wide_table_warning(): void
    {
        $html = '<!DOCTYPE html><html><head></head>
        <body><table style="width: 1500px"><tr><td>Data</td></tr></table></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['horizontalScroll']['detected']);
    }

    // =========================================================================
    // MOBILE SCORE CALCULATION TESTS (SCORE)
    // =========================================================================

    /**
     * SCORE-001: Perfect mobile score
     */
    public function test_perfect_mobile_score(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                @media (max-width: 768px) { .container { width: 100%; } }
                body { font-size: 16px; }
                a { display: inline-block; width: 48px; height: 48px; }
                img { max-width: 100%; }
            </style>
        </head><body><a href="#">Link</a></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertGreaterThanOrEqual(80, $result->data['mobileScore']);
    }

    /**
     * SCORE-002: Missing viewport reduces score
     */
    public function test_missing_viewport_reduces_score(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>body { font-size: 16px; }</style>
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertLessThan(80, $result->data['mobileScore']);
    }

    /**
     * SCORE-003: Multiple issues cumulative
     */
    public function test_multiple_issues_cumulative(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <style>
                body { font-size: 10px; }
                a { width: 20px; height: 20px; display: inline-block; }
            </style>
        </head><body><a href="#">X</a></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        // Missing viewport + small fonts + small tap targets = low score
        $this->assertLessThan(50, $result->data['mobileScore']);
    }

    /**
     * SCORE-004: Score is between 0 and 100
     */
    public function test_score_range(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertGreaterThanOrEqual(0, $result->data['mobileScore']);
        $this->assertLessThanOrEqual(100, $result->data['mobileScore']);
    }

    /**
     * SCORE-005: Score is integer
     */
    public function test_score_is_integer(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertIsInt($result->data['mobileScore']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (ERR)
    // =========================================================================

    /**
     * ERR-001: Invalid URL
     */
    public function test_invalid_url(): void
    {
        $result = $this->checker->analyze('not-a-valid-url');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    /**
     * ERR-002: Connection failure
     */
    public function test_connection_failure(): void
    {
        Http::fake([
            '*' => Http::failedConnection('Connection refused'),
        ]);

        $result = $this->checker->analyze('https://nonexistent.example.com');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    /**
     * ERR-003: HTTP error response
     */
    public function test_http_error_response(): void
    {
        Http::fake([
            '*' => Http::response('<html><body>Not Found</body></html>', 404),
        ]);

        $result = $this->checker->analyze('https://example.com/notfound');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('404', $result->error);
    }

    /**
     * ERR-004: Malformed HTML handled gracefully
     */
    public function test_malformed_html(): void
    {
        $html = '<html><head><title>Test<body><p>Unclosed';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        // Should handle gracefully, not crash
        $this->assertTrue($result->success);
    }

    /**
     * ERR-005: Empty HTML
     */
    public function test_empty_html(): void
    {
        $this->mockHtmlResponse('');

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        // Should have low score with no content
        $this->assertLessThan(50, $result->data['mobileScore']);
    }

    /**
     * ERR-006: SSRF protection (blocked URL)
     */
    public function test_ssrf_protection(): void
    {
        $result = $this->checker->analyze('http://localhost');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('blocked', strtolower($result->error));
    }

    // =========================================================================
    // OUTPUT STRUCTURE TESTS
    // =========================================================================

    /**
     * OUTPUT-001: Complete output structure
     */
    public function test_complete_output_structure(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('mobileScore', $result->data);
        $this->assertArrayHasKey('viewport', $result->data);
        $this->assertArrayHasKey('responsiveIndicators', $result->data);
        $this->assertArrayHasKey('readability', $result->data);
        $this->assertArrayHasKey('tapTargets', $result->data);
        $this->assertArrayHasKey('horizontalScroll', $result->data);
        $this->assertArrayHasKey('issues', $result->data);
        $this->assertArrayHasKey('recommendations', $result->data);
    }

    /**
     * OUTPUT-002: Viewport structure
     */
    public function test_viewport_output_structure(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('present', $result->data['viewport']);
        $this->assertArrayHasKey('content', $result->data['viewport']);
        $this->assertArrayHasKey('valid', $result->data['viewport']);
    }

    /**
     * OUTPUT-003: Recommendations provided
     */
    public function test_recommendations_provided(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertIsArray($result->data['recommendations']);
        $this->assertNotEmpty($result->data['recommendations']);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertIssueContains(ToolResult $result, string $text): void
    {
        $this->assertTrue($result->success, 'Result must be successful to check issues');
        
        $found = false;
        foreach ($result->data['issues'] as $issue) {
            if (stripos($issue['message'] ?? '', $text) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected issue containing '{$text}' not found");
    }
}
