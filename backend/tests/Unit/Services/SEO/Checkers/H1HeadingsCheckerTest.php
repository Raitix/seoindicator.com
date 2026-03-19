<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\BaseIndicatorChecker;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Unit tests for H1HeadingsChecker.
 *
 * @see TASK-18-14
 * @see TEST-18-14
 */
class H1HeadingsCheckerTest extends TestCase
{
    /**
     * Path to the H1HeadingsChecker class.
     */
    private const CHECKER_CLASS = 'App\Services\SEO\Checkers\H1HeadingsChecker';

    /**
     * Create a Crawler from HTML string.
     */
    private function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    // =========================================================================
    // HE-001: Service Instantiation
    // =========================================================================

    #[Test]
    public function he001_1_class_exists(): void
    {
        $this->assertTrue(class_exists(self::CHECKER_CLASS), 'H1HeadingsChecker class should exist');
    }

    #[Test]
    public function he001_2_implements_interface(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function he001_3_extends_base_class(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertInstanceOf(BaseIndicatorChecker::class, $checker);
    }

    // =========================================================================
    // IC-001: Indicator Metadata
    // =========================================================================

    #[Test]
    public function ic001_1_id_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('h1_headings', $checker->id());
    }

    #[Test]
    public function ic001_2_name_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('H1 Headings', $checker->name());
    }

    #[Test]
    public function ic001_3_category_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('content', $checker->category());
    }

    #[Test]
    public function ic001_4_weight_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals(0.08, $checker->weight());
    }

    // =========================================================================
    // HE-01/HE-02: H1 Existence Tests
    // =========================================================================

    #[Test]
    public function he01_1_single_h1_present(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Main Title</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['exists'] ?? false);
        $this->assertEquals(1, $result->details['count'] ?? 0);
    }

    #[Test]
    public function he02_1_no_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h2>Subtitle</h2><p>Content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['exists'] ?? true);
        $this->assertEquals(0, $result->details['count'] ?? -1);
    }

    #[Test]
    public function he03_1_empty_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1></h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['exists'] ?? false);
        $this->assertTrue($result->details['empty'] ?? false);
    }

    #[Test]
    public function he04_1_h1_with_image(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1><img src="logo.png" alt="Company Logo"></h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['exists'] ?? false);
        // Should extract alt text or handle gracefully
        $this->assertArrayHasKey('headings', $result->details);
    }

    // =========================================================================
    // HC-01-HC-04: H1 Count Tests
    // =========================================================================

    #[Test]
    public function hc01_1_single_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->details['count'] ?? 0);
        $this->assertTrue($result->details['isSingle'] ?? false);
    }

    #[Test]
    public function hc02_1_two_h1s(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>First</h1><h1>Second</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->details['count'] ?? 0);
        $this->assertFalse($result->details['isSingle'] ?? true);
    }

    #[Test]
    public function hc03_1_multiple_h1s(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>One</h1><h1>Two</h1><h1>Three</h1><h1>Four</h1><h1>Five</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(5, $result->details['count'] ?? 0);
        $this->assertFalse($result->details['isSingle'] ?? true);
    }

    #[Test]
    public function hc04_1_no_h1_count(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><p>Content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(0, $result->details['count'] ?? -1);
        $this->assertFalse($result->details['isSingle'] ?? true);
    }

    // =========================================================================
    // HK-01-HK-04: Keywords Tests
    // =========================================================================

    #[Test]
    public function hk01_1_primary_keyword(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>SEO Services</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertTrue($result->details['hasKeywords'] ?? false);
    }

    #[Test]
    public function hk02_1_multiple_keywords(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Best SEO Services London</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'London']]);

        $this->assertTrue($result->details['hasKeywords'] ?? false);
        $keywordsFound = $result->details['keywords_found'] ?? [];
        $this->assertContains('SEO', $keywordsFound);
        $this->assertContains('London', $keywordsFound);
    }

    #[Test]
    public function hk03_1_no_keywords(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Welcome</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertFalse($result->details['hasKeywords'] ?? true);
    }

    #[Test]
    public function hk04_1_keywords_in_nested_elements(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1><span>SEO</span> Services</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertTrue($result->details['hasKeywords'] ?? false);
    }

    // =========================================================================
    // HL-01-HL-05: Length Tests
    // =========================================================================

    #[Test]
    public function hl01_1_too_short(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Hi</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->details['length'] ?? 0);
        $this->assertFalse($result->details['isOptimalLength'] ?? true);
    }

    #[Test]
    public function hl02_1_optimal_length(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 38 characters - within 20-70 range
        $h1 = 'Professional SEO Services in London UK';
        $html = "<!DOCTYPE html><html><body><h1>{$h1}</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(38, $result->details['length'] ?? 0);
        $this->assertTrue($result->details['isOptimalLength'] ?? false);
    }

    #[Test]
    public function hl03_1_too_long(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 80+ characters
        $h1 = 'This is a very long heading that exceeds the recommended length for SEO best practices';
        $html = "<!DOCTYPE html><html><body><h1>{$h1}</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertGreaterThan(70, $result->details['length'] ?? 0);
        $this->assertFalse($result->details['isOptimalLength'] ?? true);
    }

    #[Test]
    public function hl04_1_exact_20_chars(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Exactly 20 characters
        $h1 = 'SEO Services London'; // 19 chars + 1 = 20
        $this->assertEquals(19, strlen($h1), 'Pre-adjustment check');
        $h1 = 'SEO Services London!'; // 20 chars
        $this->assertEquals(20, strlen($h1), 'H1 should be exactly 20 chars');
        $html = "<!DOCTYPE html><html><body><h1>{$h1}</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(20, $result->details['length'] ?? 0);
        $this->assertTrue($result->details['isOptimalLength'] ?? false);
    }

    #[Test]
    public function hl05_1_exact_70_chars(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Exactly 70 characters
        $h1 = 'Professional SEO Services for Digital Marketing Success in London UK';
        // Adjust to exactly 70
        $h1 = str_pad($h1, 70, '!');
        $this->assertEquals(70, strlen($h1), 'H1 should be exactly 70 chars');
        $html = "<!DOCTYPE html><html><body><h1>{$h1}</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(70, $result->details['length'] ?? 0);
        $this->assertTrue($result->details['isOptimalLength'] ?? false);
    }

    // =========================================================================
    // SC-01-SC-04: Scoring Tests
    // =========================================================================

    #[Test]
    public function sc01_1_perfect_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Professional SEO Services in London UK</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->passed);
    }

    #[Test]
    public function sc02_1_good_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Single H1 but without keywords
        $html = '<!DOCTYPE html><html><body><h1>Welcome to Our Website</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertGreaterThanOrEqual(2, $result->score);
    }

    #[Test]
    public function sc03_1_poor_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Missing H1
        $html = '<!DOCTYPE html><html><body><p>Content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals(1, $result->score);
        $this->assertFalse($result->passed);
    }

    #[Test]
    public function sc04_1_too_many_h1s(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>One</h1><h1>Two</h1><h1>Three</h1><h1>Four</h1><h1>Five</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    // =========================================================================
    // SG-01-SG-05: Suggestion Generation Tests
    // =========================================================================

    #[Test]
    public function sg01_1_no_h1_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><p>Content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $hasAddSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'add') ||
            str_contains(strtolower($s->message), 'missing') ||
            str_contains(strtolower($s->message), 'h1')
        );
        $this->assertTrue($hasAddSuggestion, 'Should suggest adding H1');
    }

    #[Test]
    public function sg02_1_multiple_h1s_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>First</h1><h1>Second</h1><h1>Third</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasSingleSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'single') ||
            str_contains(strtolower($s->message), 'multiple') ||
            str_contains(strtolower($s->message), 'consolidate')
        );
        $this->assertTrue($hasSingleSuggestion, 'Should suggest using single H1');
    }

    #[Test]
    public function sg03_1_too_short_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Hi</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasLengthSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'short') ||
            str_contains(strtolower($s->message), 'longer') ||
            str_contains(strtolower($s->message), 'length')
        );
        $this->assertTrue($hasLengthSuggestion, 'Should suggest extending H1');
    }

    #[Test]
    public function sg04_1_no_keywords_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Welcome to Our Website</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);
        $suggestions = $checker->getSuggestions($result);

        $hasKeywordSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'keyword')
        );
        $this->assertTrue($hasKeywordSuggestion, 'Should suggest adding keywords');
    }

    #[Test]
    public function sg05_1_too_long_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $longH1 = str_repeat('SEO Services ', 10); // 130+ chars
        $html = "<!DOCTYPE html><html><body><h1>{$longH1}</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasLengthSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'long') ||
            str_contains(strtolower($s->message), 'shorter')
        );
        $this->assertTrue($hasLengthSuggestion, 'Should suggest shortening H1');
    }

    // =========================================================================
    // EC-01-EC-05: Edge Case Tests
    // =========================================================================

    #[Test]
    public function ec01_1_unicode_in_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>日本語タイトル</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        $this->assertStringContainsString('日本語', $result->details['headings'][0] ?? '');
    }

    #[Test]
    public function ec02_1_html_in_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1><strong>Bold</strong> Title</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        // Text should be extracted (HTML stripped)
        $this->assertStringContainsString('Bold', $result->details['headings'][0] ?? '');
        $this->assertStringContainsString('Title', $result->details['headings'][0] ?? '');
    }

    #[Test]
    public function ec03_1_nested_h1s(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Invalid HTML but should handle gracefully
        $html = '<!DOCTYPE html><html><body><h1>Outer<h1>Inner</h1></h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should count both (browser behavior)
        $this->assertGreaterThanOrEqual(1, $result->details['count'] ?? 0);
    }

    #[Test]
    public function ec04_1_hidden_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1 style="display:none">Hidden</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Hidden H1 should still be detected
        $this->assertTrue($result->details['exists'] ?? false);
        $this->assertEquals(1, $result->details['count'] ?? 0);
    }

    #[Test]
    public function ec05_1_h1_in_header_footer(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><header><h1>Logo</h1></header><main><h1>Main Title</h1></main><footer><h1>Footer</h1></footer></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // All H1s should be counted
        $this->assertEquals(3, $result->details['count'] ?? 0);
    }

    // =========================================================================
    // Details Structure Tests
    // =========================================================================

    #[Test]
    public function details_has_headings_array(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test Title</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        $this->assertIsArray($result->details['headings']);
        $this->assertContains('Test Title', $result->details['headings']);
    }

    #[Test]
    public function details_has_exists(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('exists', $result->details);
        $this->assertIsBool($result->details['exists']);
    }

    #[Test]
    public function details_has_count(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('count', $result->details);
        $this->assertIsInt($result->details['count']);
    }

    #[Test]
    public function details_has_is_single(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('isSingle', $result->details);
        $this->assertIsBool($result->details['isSingle']);
    }

    #[Test]
    public function details_has_length(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('length', $result->details);
        $this->assertIsInt($result->details['length']);
    }

    #[Test]
    public function details_has_is_optimal_length(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('isOptimalLength', $result->details);
        $this->assertIsBool($result->details['isOptimalLength']);
    }

    #[Test]
    public function details_has_has_keywords(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertArrayHasKey('hasKeywords', $result->details);
        $this->assertIsBool($result->details['hasKeywords']);
    }

    // =========================================================================
    // Score Calculation Data Provider Tests
    // =========================================================================

    public static function scoreCalculationProvider(): array
    {
        return [
            'no h1' => ['<body><p>Content</p></body>', 1, false],
            'empty h1' => ['<body><h1></h1></body>', 1, false],
            'short h1 no keywords' => ['<body><h1>Hi</h1></body>', 1, false],
            'optimal h1 no keywords' => ['<body><h1>Professional SEO Services in London UK</h1></body>', 2, true],
            'optimal h1 with keywords' => ['<body><h1>Professional SEO Services in London UK</h1></body>', 3, true],
            'multiple h1s' => ['<body><h1>First</h1><h1>Second</h1></body>', 1, false],
            'too long h1' => ['<body><h1>This is a very long heading that exceeds the recommended maximum length</h1></body>', 2, false],
        ];
    }

    #[DataProvider('scoreCalculationProvider')]
    #[Test]
    public function score_calculation_scenarios(string $bodyHtml, int $expectedMinScore, bool $expectedPassedWithKeywords): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = "<!DOCTYPE html><html>{$bodyHtml}</html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertGreaterThanOrEqual($expectedMinScore, $result->score, "Score should be at least {$expectedMinScore}");
    }

    // =========================================================================
    // Integration with Base Class Tests
    // =========================================================================

    #[Test]
    public function error_handling_malformed_html(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<malformed>html';
        $crawler = $this->createCrawler($html);

        // Should not throw exception
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function result_has_correct_id(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('h1_headings', $result->id);
    }

    #[Test]
    public function result_has_correct_name(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('H1 Headings', $result->name);
    }

    #[Test]
    public function result_has_correct_category(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('content', $result->category);
    }

    #[Test]
    public function result_has_correct_weight(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(0.08, $result->weight);
    }

    #[Test]
    public function suggestions_are_suggestion_objects(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceOf(Suggestion::class, $suggestion);
        }
    }

    #[Test]
    public function no_keywords_option_handled(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Test Page</h1></body></html>';
        $crawler = $this->createCrawler($html);

        // Should work without keywords option
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('hasKeywords', $result->details);
        $this->assertIsBool($result->details['hasKeywords']);
    }

    // =========================================================================
    // Additional Edge Cases
    // =========================================================================

    #[Test]
    public function whitespace_only_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>   </h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['exists'] ?? false);
        $this->assertTrue($result->details['empty'] ?? false);
    }

    #[Test]
    public function h1_with_newlines(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = "<!DOCTYPE html><html><body><h1>Line1\nLine2</h1></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        $this->assertNotEmpty($result->details['headings'][0] ?? '');
    }

    #[Test]
    public function h1_with_html_entities(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Tom &amp; Jerry</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        // HTML entities should be decoded
        $this->assertStringContainsString('Tom', $result->details['headings'][0] ?? '');
    }

    #[Test]
    public function multiple_h1s_headings_array(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>First</h1><h1>Second</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertCount(2, $result->details['headings'] ?? []);
        $this->assertContains('First', $result->details['headings'] ?? []);
        $this->assertContains('Second', $result->details['headings'] ?? []);
    }

    #[Test]
    public function emoji_in_h1(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><body><h1>Best Site 🚀 2024</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('headings', $result->details);
        $this->assertStringContainsString('🚀', $result->details['headings'][0] ?? '');
    }
}
