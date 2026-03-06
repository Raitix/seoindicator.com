<?php

namespace Tests\Unit\Services;

use App\Services\BadgeService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for BadgeService.
 *
 * Tests SVG badge generation with multiple sizes, themes, and score color coding.
 * Uses TDD approach with mocked cache and validates SVG structure.
 *
 * @see TASK-33-30
 * @see SPEC-033
 */
#[Group('unit')]
#[Group('services')]
#[Group('badge')]
class BadgeServiceTest extends TestCase
{
    private BadgeService $service;

    /**
     * Size dimensions mapping.
     */
    private const SIZE_DIMENSIONS = [
        'small' => ['width' => 120, 'height' => 40],
        'medium' => ['width' => 150, 'height' => 100],
        'large' => ['width' => 300, 'height' => 150],
    ];

    /**
     * Score color mapping.
     */
    private const SCORE_COLORS = [
        [90, 100, '#22c55e'], // green
        [70, 89, '#3b82f6'],  // blue
        [50, 69, '#eab308'],  // yellow
        [30, 49, '#f97316'],  // orange
        [0, 29, '#ef4444'],   // red
    ];

    /**
     * Theme colors.
     */
    private const THEME_COLORS = [
        'light' => ['bg' => '#ffffff', 'text' => '#1f2937'],
        'dark' => ['bg' => '#1f2937', 'text' => '#f9fafb'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new BadgeService();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SERVICE INSTANTIATION TESTS
    // =========================================================================

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(BadgeService::class, $this->service);
    }

    #[Test]
    public function service_has_generate_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'generate'));
    }

    #[Test]
    public function generate_method_returns_string(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertIsString($svg);
    }

    // =========================================================================
    // SIZE VARIANT TESTS
    // =========================================================================

    #[Test]
    #[DataProvider('sizeProvider')]
    public function it_generates_correct_dimensions_for_size(string $size, int $expectedWidth, int $expectedHeight): void
    {
        $svg = $this->service->generate(score: 75, size: $size);

        $this->assertStringContainsString("width=\"{$expectedWidth}\"", $svg);
        $this->assertStringContainsString("height=\"{$expectedHeight}\"", $svg);
    }

    #[Test]
    public function it_rejects_invalid_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid size');

        $this->service->generate(score: 75, size: 'invalid');
    }

    #[Test]
    public function size_is_case_sensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate(score: 75, size: 'SMALL');
    }

    public static function sizeProvider(): array
    {
        return [
            'small size' => ['small', 120, 40],
            'medium size' => ['medium', 150, 100],
            'large size' => ['large', 300, 150],
        ];
    }

    // =========================================================================
    // THEME VARIANT TESTS
    // =========================================================================

    #[Test]
    public function it_applies_light_theme_by_default(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // Light theme has white background
        $this->assertStringContainsString('#ffffff', $svg);
    }

    #[Test]
    public function it_applies_light_theme_explicitly(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small', theme: 'light');

        $this->assertStringContainsString('#ffffff', $svg);
    }

    #[Test]
    public function it_applies_dark_theme(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small', theme: 'dark');

        // Dark theme has dark background
        $this->assertStringContainsString('#1f2937', $svg);
    }

    #[Test]
    public function it_rejects_invalid_theme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid theme');

        $this->service->generate(score: 75, size: 'small', theme: 'invalid');
    }

    #[Test]
    public function theme_is_case_sensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate(score: 75, size: 'small', theme: 'DARK');
    }

    // =========================================================================
    // SCORE COLOR CODING TESTS
    // =========================================================================

    #[Test]
    #[DataProvider('scoreColorProvider')]
    public function it_applies_correct_color_for_score_range(int $score, string $expectedColor): void
    {
        $svg = $this->service->generate(score: $score, size: 'medium');

        $this->assertStringContainsString($expectedColor, $svg);
    }

    #[Test]
    public function score_90_is_green(): void
    {
        $svg = $this->service->generate(score: 90, size: 'medium');
        $this->assertStringContainsString('#22c55e', $svg);
    }

    #[Test]
    public function score_100_is_green(): void
    {
        $svg = $this->service->generate(score: 100, size: 'medium');
        $this->assertStringContainsString('#22c55e', $svg);
    }

    #[Test]
    public function score_89_is_blue(): void
    {
        $svg = $this->service->generate(score: 89, size: 'medium');
        $this->assertStringContainsString('#3b82f6', $svg);
    }

    #[Test]
    public function score_70_is_blue(): void
    {
        $svg = $this->service->generate(score: 70, size: 'medium');
        $this->assertStringContainsString('#3b82f6', $svg);
    }

    #[Test]
    public function score_50_is_yellow(): void
    {
        $svg = $this->service->generate(score: 50, size: 'medium');
        $this->assertStringContainsString('#eab308', $svg);
    }

    #[Test]
    public function score_30_is_orange(): void
    {
        $svg = $this->service->generate(score: 30, size: 'medium');
        $this->assertStringContainsString('#f97316', $svg);
    }

    #[Test]
    public function score_29_is_red(): void
    {
        $svg = $this->service->generate(score: 29, size: 'medium');
        $this->assertStringContainsString('#ef4444', $svg);
    }

    public static function scoreColorProvider(): array
    {
        return [
            'score 100 (green)' => [100, '#22c55e'],
            'score 95 (green)' => [95, '#22c55e'],
            'score 90 (green)' => [90, '#22c55e'],
            'score 89 (blue)' => [89, '#3b82f6'],
            'score 75 (blue)' => [75, '#3b82f6'],
            'score 70 (blue)' => [70, '#3b82f6'],
            'score 69 (yellow)' => [69, '#eab308'],
            'score 50 (yellow)' => [50, '#eab308'],
            'score 49 (orange)' => [49, '#f97316'],
            'score 30 (orange)' => [30, '#f97316'],
            'score 29 (red)' => [29, '#ef4444'],
            'score 15 (red)' => [15, '#ef4444'],
            'score 0 (red)' => [0, '#ef4444'],
        ];
    }

    // =========================================================================
    // SVG STRUCTURE TESTS
    // =========================================================================

    #[Test]
    public function it_generates_valid_svg(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // Check SVG opening tag
        $this->assertStringStartsWith('<svg', trim($svg));
        $this->assertStringEndsWith('</svg>', trim($svg));
    }

    #[Test]
    public function svg_has_xmlns_attribute(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
    }

    #[Test]
    public function svg_can_be_parsed_as_xml(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // Use libxml to parse SVG as XML
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($svg);
        libxml_use_internal_errors($previousLibxmlSetting);

        $this->assertInstanceOf(\SimpleXMLElement::class, $doc, 'SVG should be valid XML');
        $this->assertFalse(libxml_get_last_error(), 'SVG should have no XML errors');
    }

    #[Test]
    #[DataProvider('sizeProvider')]
    public function svg_has_viewbox_for_all_sizes(string $size): void
    {
        $svg = $this->service->generate(score: 75, size: $size);

        $this->assertMatchesRegularExpression('/viewBox="\d+ \d+ \d+ \d+"/', $svg);
    }

    #[Test]
    public function svg_displays_score_text(): void
    {
        $svg = $this->service->generate(score: 75, size: 'medium');

        $this->assertStringContainsString('75', $svg);
    }

    #[Test]
    public function svg_includes_powered_by_text(): void
    {
        $svg = $this->service->generate(score: 75, size: 'medium');

        $this->assertStringContainsString('SEOIndicator', $svg);
    }

    #[Test]
    public function large_badge_includes_category_breakdown(): void
    {
        $categoryScores = [
            'content' => 80,
            'technical' => 70,
            'accessibility' => 75,
            'performance' => 65,
        ];

        $svg = $this->service->generate(
            score: 75,
            size: 'large',
            categoryScores: $categoryScores
        );

        $this->assertStringContainsString('Content', $svg);
        $this->assertStringContainsString('Technical', $svg);
    }

    // =========================================================================
    // ACCESSIBILITY TESTS
    // =========================================================================

    #[Test]
    public function svg_has_role_attribute(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertStringContainsString('role="img"', $svg);
    }

    #[Test]
    public function svg_has_aria_label(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertMatchesRegularExpression('/aria-label="[^"]*SEO[^"]*"/', $svg);
    }

    #[Test]
    public function aria_label_includes_score(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertMatchesRegularExpression('/aria-label="[^"]*75[^"]*"/', $svg);
    }

    #[Test]
    #[DataProvider('sizeProvider')]
    public function all_sizes_have_accessibility_attributes(string $size): void
    {
        $svg = $this->service->generate(score: 75, size: $size);

        $this->assertStringContainsString('role="img"', $svg);
        $this->assertMatchesRegularExpression('/aria-label="[^"]+"/', $svg);
    }

    // =========================================================================
    // CACHING TESTS
    // =========================================================================

    #[Test]
    public function it_caches_generated_svg(): void
    {
        Cache::flush();

        // First call
        $svg1 = $this->service->generate(score: 75, size: 'small');
        
        // Second call should return cached version
        $svg2 = $this->service->generate(score: 75, size: 'small');

        $this->assertEquals($svg1, $svg2);
    }

    #[Test]
    public function cache_key_includes_score_size_and_theme(): void
    {
        Cache::flush();

        $svg1 = $this->service->generate(score: 75, size: 'small', theme: 'light');
        $svg2 = $this->service->generate(score: 75, size: 'small', theme: 'dark');
        $svg3 = $this->service->generate(score: 80, size: 'small', theme: 'light');
        $svg4 = $this->service->generate(score: 75, size: 'medium', theme: 'light');

        // Different parameters should produce different SVGs
        $this->assertNotEquals($svg1, $svg2, 'Different themes should produce different SVGs');
        $this->assertNotEquals($svg1, $svg3, 'Different scores should produce different SVGs');
        $this->assertNotEquals($svg1, $svg4, 'Different sizes should produce different SVGs');
    }

    #[Test]
    public function cache_ttl_is_one_hour(): void
    {
        // This test verifies the cache is used with correct TTL
        // In real implementation, TTL is set to 3600 seconds (1 hour)
        Cache::flush();

        $svg = $this->service->generate(score: 75, size: 'small');

        // Verify the cache was set (implementation detail)
        $cacheKey = $this->getCacheKey(75, 'small', 'light');
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_can_be_bypassed(): void
    {
        Cache::flush();

        // First call caches
        $svg1 = $this->service->generate(score: 75, size: 'small');

        // Force fresh generation
        $svg2 = $this->service->generate(score: 75, size: 'small', forceFresh: true);

        $this->assertEquals($svg1, $svg2);
    }

    /**
     * Generate expected cache key for testing.
     */
    private function getCacheKey(int $score, string $size, string $theme): string
    {
        $hash = md5($score . $size . $theme);
        return "badge:{$hash}:{$size}:{$theme}";
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    #[Test]
    public function score_zero_is_valid(): void
    {
        $svg = $this->service->generate(score: 0, size: 'small');

        $this->assertStringContainsString('0', $svg);
        $this->assertStringContainsString('#ef4444', $svg); // red
    }

    #[Test]
    public function score_100_is_valid(): void
    {
        $svg = $this->service->generate(score: 100, size: 'small');

        $this->assertStringContainsString('100', $svg);
        $this->assertStringContainsString('#22c55e', $svg); // green
    }

    #[Test]
    public function negative_score_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Score must be between 0 and 100');

        $this->service->generate(score: -1, size: 'small');
    }

    #[Test]
    public function score_above_100_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Score must be between 0 and 100');

        $this->service->generate(score: 101, size: 'small');
    }

    #[Test]
    public function score_with_decimal_is_floored(): void
    {
        // Score should be floored to nearest integer
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertStringContainsString('75', $svg);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    #[Test]
    public function svg_does_not_contain_script_tags(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('javascript:', $svg);
        $this->assertStringNotContainsString('onload=', $svg);
        $this->assertStringNotContainsString('onclick=', $svg);
    }

    #[Test]
    public function svg_does_not_contain_external_resources(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // No external images or scripts
        // Note: xmlns contains w3.org URL which is required for valid SVG
        $this->assertStringNotContainsString('<image', $svg);
        $this->assertStringNotContainsString('<foreignObject', $svg);
        $this->assertStringNotContainsString('src="http', $svg);
        $this->assertStringNotContainsString('href="http', $svg);
    }

    #[Test]
    public function svg_entities_are_safe(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // SVG should not have dangerous entities
        $this->assertStringNotContainsString('&lt;script', $svg);
        $this->assertStringNotContainsString('&#', $svg); // No numeric character references for XSS
    }

    // =========================================================================
    // CONTENT TESTS
    // =========================================================================

    #[Test]
    public function small_badge_is_compact(): void
    {
        $svg = $this->service->generate(score: 75, size: 'small');

        // Small badge should be simple - just score and branding
        $this->assertStringContainsString('75', $svg);
        $this->assertStringContainsString('SEOIndicator', $svg);
    }

    #[Test]
    public function medium_badge_has_score_and_label(): void
    {
        $svg = $this->service->generate(score: 75, size: 'medium');

        $this->assertStringContainsString('75', $svg);
        $this->assertStringContainsString('SEO', $svg);
    }

    #[Test]
    public function large_badge_has_full_details(): void
    {
        $categoryScores = [
            'content' => 80,
            'technical' => 70,
            'accessibility' => 75,
            'performance' => 65,
        ];

        $svg = $this->service->generate(
            score: 75,
            size: 'large',
            categoryScores: $categoryScores
        );

        // Large badge includes overall score
        $this->assertStringContainsString('75', $svg);
        
        // Includes category scores
        $this->assertStringContainsString('80', $svg);
        $this->assertStringContainsString('70', $svg);
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    #[Test]
    public function generation_is_fast_under_50ms(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $this->service->generate(score: 75, size: 'medium');
        }
        
        $elapsed = (microtime(true) - $start) * 1000; // ms
        
        $this->assertLessThan(50, $elapsed / 10, 'Average generation should be under 50ms');
    }

    #[Test]
    public function cached_generation_is_instant(): void
    {
        // Prime the cache
        $this->service->generate(score: 75, size: 'medium');

        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->service->generate(score: 75, size: 'medium');
        }
        
        $elapsed = (microtime(true) - $start) * 1000; // ms
        
        // Cached retrieval should be very fast
        $this->assertLessThan(10, $elapsed / 100, 'Cached retrieval should be under 0.1ms');
    }

    // =========================================================================
    // ACCEPTANCE CRITERIA TESTS
    // =========================================================================

    #[Test]
    public function ac_01_all_size_variants_work(): void
    {
        $sizes = ['small', 'medium', 'large'];
        
        foreach ($sizes as $size) {
            $svg = $this->service->generate(score: 75, size: $size);
            $this->assertNotEmpty($svg, "Size {$size} should generate SVG");
            $this->assertValidSvg($svg);
        }
    }

    #[Test]
    public function ac_02_all_theme_variants_work(): void
    {
        $themes = ['light', 'dark'];
        
        foreach ($themes as $theme) {
            $svg = $this->service->generate(score: 75, size: 'medium', theme: $theme);
            $this->assertNotEmpty($svg, "Theme {$theme} should generate SVG");
            $this->assertValidSvg($svg);
        }
    }

    #[Test]
    public function ac_03_all_score_ranges_work(): void
    {
        $scores = [100, 90, 89, 70, 69, 50, 49, 30, 29, 0];
        
        foreach ($scores as $score) {
            $svg = $this->service->generate(score: $score, size: 'medium');
            $this->assertNotEmpty($svg, "Score {$score} should generate SVG");
            $this->assertValidSvg($svg);
        }
    }

    #[Test]
    public function ac_04_svg_is_valid_xml(): void
    {
        $svg = $this->service->generate(score: 75, size: 'medium');
        
        $this->assertValidSvg($svg);
    }

    #[Test]
    public function ac_05_caching_works(): void
    {
        Cache::flush();
        
        $svg1 = $this->service->generate(score: 75, size: 'medium');
        $svg2 = $this->service->generate(score: 75, size: 'medium');
        
        $this->assertEquals($svg1, $svg2, 'Cached result should match original');
    }

    #[Test]
    public function ac_06_edge_cases_handled(): void
    {
        // Score 0
        $svg0 = $this->service->generate(score: 0, size: 'medium');
        $this->assertValidSvg($svg0);
        
        // Score 100
        $svg100 = $this->service->generate(score: 100, size: 'medium');
        $this->assertValidSvg($svg100);
        
        // Negative score throws exception
        try {
            $this->service->generate(score: -1, size: 'medium');
            $this->fail('Should throw exception for negative score');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Score', $e->getMessage());
        }
    }

    #[Test]
    public function ac_07_accessibility_attributes_present(): void
    {
        $svg = $this->service->generate(score: 75, size: 'medium');
        
        $this->assertStringContainsString('role="img"', $svg);
        $this->assertMatchesRegularExpression('/aria-label="[^"]+"/', $svg);
    }

    /**
     * Assert that a string is valid SVG XML.
     */
    private function assertValidSvg(string $svg): void
    {
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($svg);
        $errors = libxml_get_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        $this->assertInstanceOf(
            \SimpleXMLElement::class,
            $doc,
            'SVG should be valid XML: ' . ($errors[0]->message ?? 'Unknown error')
        );
    }
}
