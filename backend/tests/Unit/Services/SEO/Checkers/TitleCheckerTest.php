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
 * Unit tests for TitleChecker.
 *
 * @see TASK-18-10
 * @see TEST-TASK-18-10
 */
class TitleCheckerTest extends TestCase
{
    /**
     * Path to the TitleChecker class.
     */
    private const CHECKER_CLASS = 'App\Services\SEO\Checkers\TitleChecker';

    /**
     * Create a Crawler from HTML string.
     */
    private function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    // =========================================================================
    // TC-001: Service Instantiation
    // =========================================================================

    #[Test]
    public function tc001_1_class_exists(): void
    {
        $this->assertTrue(class_exists(self::CHECKER_CLASS), 'TitleChecker class should exist');
    }

    #[Test]
    public function tc001_2_implements_interface(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function tc001_3_extends_base_class(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertInstanceOf(BaseIndicatorChecker::class, $checker);
    }

    // =========================================================================
    // TC-002: Indicator Metadata
    // =========================================================================

    #[Test]
    public function tc002_1_id_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('title_tag', $checker->id());
    }

    #[Test]
    public function tc002_2_name_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('Title Tag', $checker->name());
    }

    #[Test]
    public function tc002_3_category_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals('content', $checker->category());
    }

    #[Test]
    public function tc002_4_weight_is_correct(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $this->assertEquals(0.10, $checker->weight());
    }

    // =========================================================================
    // TC-003: Title Existence Check
    // =========================================================================

    #[Test]
    public function tc003_1_title_exists(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test Page</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->passed);
        $this->assertGreaterThanOrEqual(1, $result->score);
    }

    #[Test]
    public function tc003_2_title_missing(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(0, $result->score);
    }

    #[Test]
    public function tc003_3_empty_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(0, $result->score);
    }

    #[Test]
    public function tc003_4_whitespace_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>   </title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(0, $result->score);
    }

    // =========================================================================
    // TC-004: Title Length Validation
    // =========================================================================

    #[Test]
    public function tc004_1_optimal_length_50_to_60(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 55 characters - optimal range 50-60
        $title = 'Best SEO Tools for Digital Marketing Success 2024';
        $this->assertEquals(49, strlen($title), 'Title should be 49 chars for this test');
        // Use a proper 55-char title
        $title = 'Best SEO Tools for Digital Marketing Success Today';
        $this->assertEquals(53, strlen($title), 'Title should be 53 chars');
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
        $this->assertEquals('optimal', $result->details['length_status'] ?? null);
    }

    #[Test]
    public function tc004_2_good_length_30_to_49(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 40 characters
        $title = 'SEO Tools for Marketing Success';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertGreaterThanOrEqual(2, $result->score);
    }

    #[Test]
    public function tc004_3_too_short_under_30(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 20 characters
        $title = 'SEO Tools Guide';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertLessThanOrEqual(1, $result->score);
        $this->assertEquals('short', $result->details['length_status'] ?? null);
    }

    #[Test]
    public function tc004_4_too_long_over_60(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 80 characters
        $title = 'Best SEO Tools for Digital Marketing Success and Growth in 2024 - Complete Guide';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertLessThanOrEqual(2, $result->score);
        $this->assertEquals('long', $result->details['length_status'] ?? null);
    }

    #[Test]
    public function tc004_5_very_short_under_10(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 4 characters
        $title = 'Home';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function tc004_6_very_long_over_100(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 120+ characters
        $title = 'Best SEO Tools for Digital Marketing Success and Growth in 2024 - Complete Guide for Beginners and Professionals';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function tc004_7_exactly_50_chars(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Exactly 50 characters
        $title = 'Best SEO Tools for Digital Marketing Success 2024'; // 50 chars
        $this->assertEquals(50, strlen($title), 'Title should be exactly 50 chars');
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function tc004_8_exactly_60_chars(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // Exactly 60 characters
        $title = 'Best SEO Tools for Digital Marketing Success 2024 Guide'; // 60 chars
        $this->assertEquals(60, strlen($title), 'Title should be exactly 60 chars');
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
    }

    // =========================================================================
    // TC-005: Keyword Detection
    // =========================================================================

    #[Test]
    public function tc005_1_keyword_in_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Best SEO Tools Guide</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertContains('SEO', $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function tc005_2_multiple_keywords(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>SEO Guide 2024</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'Guide']]);

        $this->assertContains('SEO', $result->details['keywords_found'] ?? []);
        $this->assertContains('Guide', $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function tc005_3_no_keyword_match(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Welcome to Our Site</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEmpty($result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function tc005_4_case_insensitive_match(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>SEO GUIDE</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['seo']]);

        $keywordsFound = $result->details['keywords_found'] ?? [];
        $this->assertTrue(
            in_array('seo', array_map('strtolower', $keywordsFound)) || in_array('SEO', $keywordsFound),
            'Keyword should be found case-insensitively'
        );
    }

    #[Test]
    public function tc005_5_partial_word_match(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>SEOptimization Guide</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        // Partial match should be detected
        $keywordsFound = $result->details['keywords_found'] ?? [];
        $this->assertNotEmpty($keywordsFound, 'Partial keyword match should be found');
    }

    // =========================================================================
    // TC-006: Multiple Title Tags
    // =========================================================================

    #[Test]
    public function tc006_1_first_title_used(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>First</title><title>Second</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('First', $result->details['title'] ?? null);
    }

    #[Test]
    public function tc006_2_multiple_with_warning(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>First</title><title>Second</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['multiple_titles'] ?? false);
    }

    // =========================================================================
    // TC-007: Details Structure
    // =========================================================================

    #[Test]
    public function tc007_1_has_title_value(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test Title</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('title', $result->details);
        $this->assertEquals('Test Title', $result->details['title']);
    }

    #[Test]
    public function tc007_2_has_length(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('length', $result->details);
        $this->assertEquals(4, $result->details['length']);
    }

    #[Test]
    public function tc007_3_has_keywords_found(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>SEO Guide</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertArrayHasKey('keywords_found', $result->details);
        $this->assertIsArray($result->details['keywords_found']);
    }

    #[Test]
    public function tc007_4_has_length_status(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test Page Title</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('length_status', $result->details);
        $this->assertContains($result->details['length_status'], ['optimal', 'short', 'long', 'good']);
    }

    // =========================================================================
    // TC-008: Suggestions Generation
    // =========================================================================

    #[Test]
    public function tc008_1_missing_title_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $hasMissingTitleSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'missing') ||
            str_contains(strtolower($s->message), 'no title')
        );
        $this->assertTrue($hasMissingTitleSuggestion, 'Should have suggestion about missing title');
    }

    #[Test]
    public function tc008_2_too_short_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Hi</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasLengthSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'short') ||
            str_contains(strtolower($s->message), 'longer') ||
            str_contains(strtolower($s->message), 'length')
        );
        $this->assertTrue($hasLengthSuggestion, 'Should have suggestion about title length');
    }

    #[Test]
    public function tc008_3_too_long_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $longTitle = str_repeat('SEO Tools ', 15); // 100+ chars
        $html = "<!DOCTYPE html><html><head><title>{$longTitle}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasLengthSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'long') ||
            str_contains(strtolower($s->message), 'shorter')
        );
        $this->assertTrue($hasLengthSuggestion, 'Should have suggestion about title being too long');
    }

    #[Test]
    public function tc008_4_optimal_no_error_suggestions(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        // 55 characters - optimal
        $title = 'Best SEO Tools for Digital Marketing Success 2024';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);
        $suggestions = $checker->getSuggestions($result);

        // Optimal with keywords should have no error suggestions
        $errorSuggestions = collect($suggestions)->filter(fn ($s) => $s->isError());
        $this->assertCount(0, $errorSuggestions, 'Optimal title should have no error suggestions');
    }

    #[Test]
    public function tc008_5_no_keywords_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Welcome to Our Website</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);
        $suggestions = $checker->getSuggestions($result);

        $hasKeywordSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'keyword')
        );
        $this->assertTrue($hasKeywordSuggestion, 'Should have suggestion about adding keywords');
    }

    #[Test]
    public function tc008_6_multiple_titles_suggestion(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>First</title><title>Second</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $hasMultipleSuggestion = collect($suggestions)->contains(fn ($s) =>
            str_contains(strtolower($s->message), 'multiple') ||
            str_contains(strtolower($s->message), 'duplicate')
        );
        $this->assertTrue($hasMultipleSuggestion, 'Should have warning about multiple titles');
    }

    // =========================================================================
    // TC-009: Edge Cases
    // =========================================================================

    #[Test]
    public function tc009_2_empty_html(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<html></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(0, $result->score);
    }

    #[Test]
    public function tc009_3_unicode_in_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>中文标题 测试</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('title', $result->details);
        $this->assertEquals('中文标题 测试', $result->details['title']);
    }

    #[Test]
    public function tc009_4_emoji_in_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Best Site 🚀 2024</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('title', $result->details);
        $this->assertStringContainsString('🚀', $result->details['title']);
    }

    #[Test]
    public function tc009_5_html_entities(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Tom &amp; Jerry Show</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('title', $result->details);
        // HTML entities should be decoded
        $this->assertEquals('Tom & Jerry Show', $result->details['title']);
    }

    #[Test]
    public function tc009_6_newlines_in_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = "<!DOCTYPE html><html><head><title>Line1\nLine2</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('title', $result->details);
        // Newlines should be normalized or preserved
        $this->assertNotEmpty($result->details['title']);
    }

    #[Test]
    public function tc009_7_very_long_title(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $longTitle = str_repeat('a', 500);
        $html = "<!DOCTYPE html><html><head><title>{$longTitle}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should handle gracefully without error
        $this->assertArrayHasKey('title', $result->details);
        $this->assertEquals(500, $result->details['length']);
    }

    // =========================================================================
    // TC-010: Score Calculation
    // =========================================================================

    public static function scoreCalculationProvider(): array
    {
        return [
            'missing title' => ['', 0, false],
            'empty title' => ['   ', 0, false],
            'short no keywords' => ['Home', 1, false],
            'short with keywords' => ['SEO Tools', 1, false],
            'good length' => ['Best SEO Tools for Marketing Success', 2, true],
            'optimal with keywords' => ['Best SEO Tools for Digital Marketing Success 2024', 3, true],
            'long title' => ['Best SEO Tools for Digital Marketing Success and Growth in 2024 Complete Guide', 2, false],
            'very long title' => [str_repeat('SEO Tools ', 20), 1, false],
        ];
    }

    #[DataProvider('scoreCalculationProvider')]
    #[Test]
    public function tc010_score_calculation(string $title, int $expectedScore, bool $expectedPassed): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals($expectedScore, $result->score, "Score mismatch for title: {$title}");
        $this->assertEquals($expectedPassed, $result->passed, "Passed status mismatch for title: {$title}");
    }

    // =========================================================================
    // TC-011: Integration with Base Class
    // =========================================================================

    #[Test]
    public function tc011_1_error_handling(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<malformed>html';
        $crawler = $this->createCrawler($html);

        // Should not throw exception
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function tc011_3_default_values(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);

        $this->assertEquals('title_tag', $checker->id());
        $this->assertEquals('Title Tag', $checker->name());
        $this->assertEquals('content', $checker->category());
        $this->assertEquals(0.10, $checker->weight());
    }

    // =========================================================================
    // Additional Tests
    // =========================================================================

    #[Test]
    public function result_has_correct_id(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('title_tag', $result->id);
    }

    #[Test]
    public function result_has_correct_name(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('Title Tag', $result->name);
    }

    #[Test]
    public function result_has_correct_category(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('content', $result->category);
    }

    #[Test]
    public function result_has_correct_weight(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(0.10, $result->weight);
    }

    #[Test]
    public function suggestions_are_suggestion_objects(): void
    {
        $checker = $this->app->make(self::CHECKER_CLASS);
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
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
        $html = '<!DOCTYPE html><html><head><title>Test Page</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        // Should work without keywords option
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('keywords_found', $result->details);
        $this->assertIsArray($result->details['keywords_found']);
    }
}
