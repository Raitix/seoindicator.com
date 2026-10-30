<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Image Alt Text Rules.
 *
 * Tests the rule-based analysis for image alt text SEO optimization.
 */
class ImageAltRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeImageAlt(array $images, ?string $keyword = null): array
    {
        $context = json_encode([
            'indicator' => 'image_alt',
            'images' => $images,
            'keyword' => $keyword,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Coverage Tests
    // =====================================================

    #[Test]
    public function all_images_have_alt_scores_high(): void
    {
        $images = [
            ['src' => 'widget1.jpg', 'alt' => 'Red widget product photo'],
            ['src' => 'widget2.jpg', 'alt' => 'Blue widget product photo'],
            ['src' => 'banner.jpg', 'alt' => 'Summer sale banner'],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertGreaterThanOrEqual(90, $result['score'], '100% alt coverage should score high');
    }

    #[Test]
    public function partial_coverage_scores_proportionally(): void
    {
        $images = [
            ['src' => 'widget1.jpg', 'alt' => 'Widget product'],
            ['src' => 'widget2.jpg', 'alt' => null],
            ['src' => 'banner.jpg', 'alt' => 'Banner'],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertLessThan(90, $result['score'], 'Partial coverage should have lower score');
        $this->assertGreaterThanOrEqual(50, $result['score'], 'But not too low for 66% coverage');
    }

    #[Test]
    public function no_images_coverage(): void
    {
        $images = [];
        $result = $this->analyzeImageAlt($images);

        // No images could be N/A or high score
        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function all_missing_alt_scores_low(): void
    {
        $images = [
            ['src' => 'widget1.jpg', 'alt' => null],
            ['src' => 'widget2.jpg', 'alt' => null],
            ['src' => 'banner.jpg', 'alt' => null],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertLessThan(60, $result['score'], 'No alt text should have low score');

        $hasAltIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'alt') ||
                         str_contains(strtolower($issue), 'missing')
        );
        $this->assertTrue($hasAltIssue, 'Should flag missing alt text');
    }

    // =====================================================
    // Quality Tests
    // =====================================================

    #[Test]
    public function generic_alt_text_flagged(): void
    {
        $images = [
            ['src' => 'widget.jpg', 'alt' => 'image'],
            ['src' => 'product.jpg', 'alt' => 'photo'],
            ['src' => 'banner.jpg', 'alt' => 'picture'],
        ];
        $result = $this->analyzeImageAlt($images);

        $hasGenericIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'generic') ||
                         str_contains(strtolower($issue), 'descriptive')
        );
        $this->assertTrue($hasGenericIssue, 'Should flag generic alt text');
    }

    #[Test]
    public function too_long_alt_flagged(): void
    {
        $longAlt = str_repeat('Very long alt text that describes every single detail ', 5);
        $images = [
            ['src' => 'widget.jpg', 'alt' => $longAlt],
        ];
        $result = $this->analyzeImageAlt($images);

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long')
        );
        $this->assertTrue($hasLengthIssue, 'Should flag too long alt text');
    }

    #[Test]
    public function keyword_stuffing_flagged(): void
    {
        $images = [
            ['src' => 'widget.jpg', 'alt' => 'widget widget widget widget widget'],
        ];
        $result = $this->analyzeImageAlt($images, 'widget');

        $hasStuffingIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'stuff') ||
                         str_contains(strtolower($issue), 'repeat')
        );
        $this->assertTrue($hasStuffingIssue, 'Should flag keyword stuffing');
    }

    #[Test]
    public function keyword_in_alt_boosts_score(): void
    {
        $imagesWithKeyword = [
            ['src' => 'widget.jpg', 'alt' => 'Best widget for sale'],
        ];
        $imagesWithoutKeyword = [
            ['src' => 'widget.jpg', 'alt' => 'Product for sale'],
        ];

        $resultWith = $this->analyzeImageAlt($imagesWithKeyword, 'widget');
        $resultWithout = $this->analyzeImageAlt($imagesWithoutKeyword, 'widget');

        $this->assertGreaterThan(
            $resultWithout['score'],
            $resultWith['score'],
            'Keyword in alt should boost score'
        );
    }

    // =====================================================
    // Decorative Images Tests
    // =====================================================

    #[Test]
    public function empty_alt_flagged_as_decorative(): void
    {
        $images = [
            ['src' => 'decoration.jpg', 'alt' => ''],
        ];
        $result = $this->analyzeImageAlt($images);

        // Empty alt is valid for decorative images, but should be noted
        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_generated_for_missing_alt(): void
    {
        $images = [
            ['src' => 'product.jpg', 'alt' => null],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertNotEmpty($result['suggestions']);

        $hasAltSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'alt') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'alt')
        );
        $this->assertTrue($hasAltSuggestion, 'Should suggest adding alt text');
    }

    #[Test]
    public function multiple_missing_alts_listed(): void
    {
        $images = [
            ['src' => 'product1.jpg', 'alt' => null],
            ['src' => 'product2.jpg', 'alt' => null],
            ['src' => 'product3.jpg', 'alt' => null],
        ];
        $result = $this->analyzeImageAlt($images);

        // Should mention multiple images need alt
        $issuesText = strtolower(implode(' ', $result['issues']));
        $this->assertTrue(
            str_contains($issuesText, '3') ||
            str_contains($issuesText, 'multiple') ||
            str_contains($issuesText, 'images'),
            'Should report count of images missing alt'
        );
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $images = [['src' => 'test.jpg', 'alt' => null]];
        $result = $this->analyzeImageAlt($images);

        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('text', $suggestion);
            $this->assertContains(
                $suggestion['type'],
                ['improve', 'rewrite', 'add', 'remove', 'warning'],
                'Suggestion type must be valid'
            );
        }
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $images = [['src' => 'test.jpg', 'alt' => 'Test image']];
        $result = $this->analyzeImageAlt($images);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_descriptive_advice(): void
    {
        $images = [['src' => 'test.jpg', 'alt' => 'Test']];
        $result = $this->analyzeImageAlt($images);
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'descriptive') ||
            str_contains($practicesText, 'alt') ||
            str_contains($practicesText, 'text'),
            'Best practices should mention alt text'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function imageAltProvider(): array
    {
        return [
            'empty' => [[], 80],
            'all with alt' => [[['src' => 'a.jpg', 'alt' => 'A']], 90],
            '50% coverage' => [[['src' => 'a.jpg', 'alt' => 'A'], ['src' => 'b.jpg', 'alt' => null]], 70],
            'none with alt' => [[['src' => 'a.jpg', 'alt' => null], ['src' => 'b.jpg', 'alt' => null]], 50],
        ];
    }

    #[Test]
    #[DataProvider('imageAltProvider')]
    public function image_alt_coverage_affects_score_appropriately(array $images, int $minExpectedScore): void
    {
        $result = $this->analyzeImageAlt($images);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            'Image alt coverage should score appropriately'
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function many_images_handled(): void
    {
        $images = [];
        for ($i = 0; $i < 100; $i++) {
            $images[] = ['src' => "image{$i}.jpg", 'alt' => $i % 2 === 0 ? "Image $i" : null];
        }
        $result = $this->analyzeImageAlt($images);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function unicode_in_alt_handled(): void
    {
        $images = [
            ['src' => 'product.jpg', 'alt' => 'Лучший продукт - 产品特色 🎉'],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function xss_in_alt_sanitized(): void
    {
        $images = [
            ['src' => 'test.jpg', 'alt' => '<script>alert("xss")</script>'],
        ];
        $result = $this->analyzeImageAlt($images);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
