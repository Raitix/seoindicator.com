<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\BaseIndicatorChecker;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Unit tests for MetaDescriptionChecker.
 *
 * Tests the SEO indicator checker for meta description analysis.
 * Covers existence, length validation, keyword detection, and suggestions.
 *
 * @see TASK-18-11
 * @see TEST-18-11
 */
#[Group('seo')]
#[Group('indicators')]
class MetaDescriptionCheckerTest extends TestCase
{
    /**
     * The checker class being tested.
     */
    protected string $checkerClass = 'App\Services\SEO\Checkers\MetaDescriptionChecker';

    /**
     * Create a Crawler from HTML string.
     */
    protected function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    /**
     * Create the checker instance.
     */
    protected function createChecker(): object
    {
        $this->assertClassExists($this->checkerClass, 'MetaDescriptionChecker class should exist');
        return new $this->checkerClass();
    }

    /**
     * Assert that a class exists, with a helpful message for TDD.
     */
    protected function assertClassExists(string $className, string $message = ''): void
    {
        $this->assertTrue(
            class_exists($className),
            $message ?: "Class {$className} should exist. Developer needs to implement this class."
        );
    }

    // =========================================================================
    // MD-001: Checker Identity Tests
    // =========================================================================

    #[Test]
    public function md_001a_id_returns_correct_identifier(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('meta_description', $checker->id());
    }

    #[Test]
    public function md_001b_name_returns_human_readable_name(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('Meta Description', $checker->name());
    }

    #[Test]
    public function md_001c_category_returns_content(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('content', $checker->category());
    }

    #[Test]
    public function md_001d_weight_returns_0_08(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals(0.08, $checker->weight());
    }

    #[Test]
    public function md_001e_implements_indicator_checker_interface(): void
    {
        $checker = $this->createChecker();
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function md_001f_extends_base_indicator_checker(): void
    {
        $checker = $this->createChecker();
        $this->assertInstanceOf(BaseIndicatorChecker::class, $checker);
    }

    // =========================================================================
    // MD-002: Meta Description Existence Tests
    // =========================================================================

    #[Test]
    public function md_002a_description_present_returns_passed(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="A valid description text here."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->passed);
        $this->assertGreaterThanOrEqual(2, $result->score);
    }

    #[Test]
    public function md_002b_description_missing_returns_failed(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertFalse($result->passed);
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_002c_description_empty_content_returns_failed(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content=""></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_002d_description_whitespace_only_returns_failed(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="   "></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_002e_multiple_descriptions_uses_first(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="First description here"><meta name="description" content="Second description here"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertGreaterThanOrEqual(2, $result->score);
        $this->assertEquals('First description here', $result->details['content'] ?? null);
        // Should note duplicate warning
        $this->assertArrayHasKey('duplicate_count', $result->details);
    }

    #[Test]
    public function md_002f_description_outside_head_still_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body><meta name="description" content="Test description"></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should still detect it (graceful handling)
        $this->assertGreaterThanOrEqual(1, $result->score);
    }

    #[Test]
    public function md_002g_case_insensitive_name_attribute(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta NAME="DESCRIPTION" content="Test description"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertGreaterThanOrEqual(2, $result->score);
    }

    #[Test]
    public function md_002h_self_closing_and_explicit_both_handled(): void
    {
        $checker = $this->createChecker();
        $html1 = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $html2 = '<!DOCTYPE html><html><head><meta name="description" content="Test"></meta></head><body></body></html>';

        $crawler1 = $this->createCrawler($html1);
        $crawler2 = $this->createCrawler($html2);

        $result1 = $checker->check($crawler1, 'https://example.com');
        $result2 = $checker->check($crawler2, 'https://example.com');

        $this->assertEquals($result1->score, $result2->score);
    }

    // =========================================================================
    // MD-003: Length Validation - Optimal Range Tests
    // =========================================================================

    #[Test]
    public function md_003a_exactly_150_chars_is_excellent(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 150);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->details['is_optimal_length'] ?? false);
    }

    #[Test]
    public function md_003b_exactly_160_chars_is_excellent(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 160);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->details['is_optimal_length'] ?? false);
    }

    #[Test]
    public function md_003c_mid_range_155_chars_is_excellent(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 155);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function md_003d_just_below_optimal_149_is_good(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 149);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
        $this->assertFalse($result->details['is_optimal_length'] ?? true);
    }

    #[Test]
    public function md_003e_just_above_optimal_161_is_good(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 161);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
        $this->assertFalse($result->details['is_optimal_length'] ?? true);
    }

    #[Test]
    public function md_003f_minimum_acceptable_120_is_good(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 120);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
        $this->assertTrue($result->details['is_acceptable_length'] ?? false);
    }

    #[Test]
    public function md_003g_maximum_acceptable_170_is_good(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 170);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
        $this->assertTrue($result->details['is_acceptable_length'] ?? false);
    }

    #[Test]
    public function md_003h_perfect_length_with_keywords_is_excellent(): void
    {
        $checker = $this->createChecker();
        $description = 'Discover the best SEO analysis tools for your website optimization needs. Start your free trial today!';
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'tools']]);

        $this->assertEquals(3, $result->score);
    }

    // =========================================================================
    // MD-004: Length Validation - Poor Range Tests
    // =========================================================================

    #[Test]
    public function md_004a_too_short_50_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 50);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004b_too_short_100_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 100);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004c_very_short_10_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 10);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004d_single_char_is_poor(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="a"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004e_too_long_171_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 171);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004f_too_long_200_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 200);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004g_very_long_300_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 300);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_004h_extremely_long_500_chars_is_poor(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 500);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    // =========================================================================
    // MD-005: Keyword Detection Tests
    // =========================================================================

    #[Test]
    public function md_005a_keywords_present_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Best SEO tools for your website"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'tools']]);

        $this->assertContains('SEO', $result->details['keywords_found'] ?? []);
        $this->assertContains('tools', $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005b_keywords_absent_not_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Welcome to our website"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'marketing']]);

        $this->assertEmpty($result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005c_partial_keyword_match(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="SEO analysis tools"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO marketing']]);

        // Should detect partial match (SEO present)
        $this->assertGreaterThanOrEqual(1, count($result->details['keywords_found'] ?? []));
    }

    #[Test]
    public function md_005d_case_insensitive_match(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Best seo tools"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertNotEmpty($result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005e_multiple_keywords_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="SEO tools and marketing tips"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'marketing', 'tools']]);

        $this->assertCount(3, $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005f_keyword_in_middle_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Find the best SEO analysis here"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertContains('SEO', $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005g_no_keywords_option_default_handling(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Any description text"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should handle gracefully when no keywords provided
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_005h_empty_keywords_array(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="SEO description"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => []]);

        // Should handle empty keywords array
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_005i_unicode_keywords(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Best café résumé tools"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['café', 'résumé']]);

        $this->assertCount(2, $result->details['keywords_found'] ?? []);
    }

    #[Test]
    public function md_005j_special_chars_in_keywords(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="C++ programming guide"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['C++']]);

        $this->assertContains('C++', $result->details['keywords_found'] ?? []);
    }

    // =========================================================================
    // MD-006: Result Structure Tests
    // =========================================================================

    #[Test]
    public function md_006a_result_is_indicator_result(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_006b_details_contain_length(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test description"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('char_count', $result->details);
    }

    #[Test]
    public function md_006c_details_contain_exists_flag(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('exists', $result->details);
        $this->assertTrue($result->details['exists']);
    }

    #[Test]
    public function md_006d_details_contain_optimal_flag(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 155);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('is_optimal_length', $result->details);
    }

    #[Test]
    public function md_006e_details_contain_char_count(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(4, $result->details['char_count']);
    }

    #[Test]
    public function md_006f_details_contain_keyword_info(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="SEO tools"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertArrayHasKey('keywords_found', $result->details);
    }

    // =========================================================================
    // MD-007: Suggestions Generation Tests
    // =========================================================================

    #[Test]
    public function md_007a_missing_description_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $this->assertStringContainsStringIgnoringCase('add', $suggestions[0]->message);
    }

    #[Test]
    public function md_007b_too_short_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Short"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'length') !== false || stripos($suggestion->message, 'short') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest lengthening description');
    }

    #[Test]
    public function md_007c_too_long_suggestion(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 200);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'shorten') !== false || stripos($suggestion->message, 'long') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest shortening description');
    }

    #[Test]
    public function md_007d_no_keywords_suggestion(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 155);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'marketing']]);
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'keyword') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest including keywords');
    }

    #[Test]
    public function md_007e_multiple_descriptions_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="First"><meta name="description" content="Second"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'one') !== false || stripos($suggestion->message, 'multiple') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest using only one description');
    }

    #[Test]
    public function md_007f_optimal_description_no_suggestions(): void
    {
        $checker = $this->createChecker();
        $description = 'Discover the best SEO tools for your website optimization needs.';
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'tools']]);
        $suggestions = $checker->getSuggestions($result);

        // Optimal description might have no suggestions or just info suggestions
        $warningOrError = array_filter($suggestions, fn($s) => $s->type !== 'info');
        $this->assertEmpty($warningOrError);
    }

    #[Test]
    public function md_007g_auto_generated_pattern_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="This is a post excerpt that was automatically generated..."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'custom') !== false || stripos($suggestion->message, 'auto') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest customizing auto-generated description');
    }

    #[Test]
    public function md_007h_suggestions_have_correct_type(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceOf(Suggestion::class, $suggestion);
            $this->assertContains($suggestion->type, ['warning', 'error', 'info']);
        }
    }

    #[Test]
    public function md_007i_suggestions_have_priority(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        foreach ($suggestions as $suggestion) {
            $this->assertGreaterThanOrEqual(1, $suggestion->priority);
            $this->assertLessThanOrEqual(3, $suggestion->priority);
        }
    }

    #[Test]
    public function md_007j_duplicate_title_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><title>SEO Tools</title><meta name="description" content="SEO Tools"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        // Should suggest unique description
        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'unique') !== false || stripos($suggestion->message, 'different') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest unique description from title');
    }

    // =========================================================================
    // MD-008: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function md_008a_null_url_returns_error(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, '');

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('error', $result->details);
    }

    #[Test]
    public function md_008b_empty_url_returns_error(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, '');

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function md_008c_malformed_html_handled_gracefully(): void
    {
        $checker = $this->createChecker();
        $html = '<html><head><meta name="description" content="Test"';
        $crawler = $this->createCrawler($html);

        // Should not throw exception
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_008d_unicode_content_handled(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Best café résumé tools 日本語"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertStringContainsString('café', $result->details['content'] ?? '');
    }

    #[Test]
    public function md_008e_html_entities_handled(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test &amp; more &lt;stuff&gt;"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        // Should count decoded or raw length
        $this->assertArrayHasKey('char_count', $result->details);
    }

    #[Test]
    public function md_008f_very_long_url_still_processes(): void
    {
        $checker = $this->createChecker();
        $url = 'https://example.com/' . str_repeat('a', 2000);
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test description"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, $url);

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_008g_script_in_description_sanitized(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="<script>alert(1)</script>Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should not execute script, should handle gracefully
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertStringNotContainsString('<script>', $result->details['content'] ?? '');
    }

    #[Test]
    public function md_008h_newlines_in_description_normalized(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Line1
Line2
Line3"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function md_008i_multiple_spaces_normalized(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test     multiple     spaces"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        // Content might be normalized
    }

    #[Test]
    public function md_008j_empty_html_document(): void
    {
        $checker = $this->createChecker();
        $html = '<html></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->passed);
        $this->assertEquals(1, $result->score);
    }

    // =========================================================================
    // MD-009: Auto-Generated Detection Tests
    // =========================================================================

    #[Test]
    public function md_009a_wordpress_auto_excerpt_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="This is a post excerpt from WordPress that was automatically generated..."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['is_auto_generated'] ?? false);
    }

    #[Test]
    public function md_009b_generic_placeholder_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="This is the description for page..."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['is_auto_generated'] ?? false);
    }

    #[Test]
    public function md_009c_truncated_content_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore..."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Trailing ... suggests truncation
        $this->assertTrue($result->details['is_auto_generated'] ?? false);
    }

    #[Test]
    public function md_009d_custom_written_not_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Discover our award-winning SEO tools trusted by 10,000+ marketers worldwide."></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['is_auto_generated'] ?? true);
    }

    #[Test]
    public function md_009e_numeric_template_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Page 1 of 10 results"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['is_auto_generated'] ?? false);
    }

    #[Test]
    public function md_009f_date_template_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Posted on March 19, 2026"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['is_auto_generated'] ?? false);
    }

    // =========================================================================
    // MD-010: Integration with Base Class Tests
    // =========================================================================

    #[Test]
    public function md_010a_extends_base_indicator_checker(): void
    {
        $checker = $this->createChecker();
        $this->assertInstanceOf(BaseIndicatorChecker::class, $checker);
    }

    #[Test]
    public function md_010b_uses_create_result_helper(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Result should have all required fields from createResult()
        $this->assertEquals('meta_description', $result->id);
        $this->assertEquals('Meta Description', $result->name);
        $this->assertEquals('content', $result->category);
        $this->assertEquals(0.08, $result->weight);
    }

    #[Test]
    public function md_010c_uses_create_error_result_on_error(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, '');

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('error', $result->details);
    }

    #[Test]
    public function md_010d_uses_extract_attribute_for_content(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Extracted content"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('Extracted content', $result->details['content']);
    }

    #[Test]
    public function md_010e_error_logging_on_failure(): void
    {
        $checker = $this->createChecker();

        // Create a malformed HTML that might cause issues
        $html = '';
        $crawler = $this->createCrawler($html);

        // Should not throw, should log and return error result
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // MD-011: Score Calculation Edge Cases Tests
    // =========================================================================

    #[Test]
    public function md_011a_all_optimal_returns_3(): void
    {
        $checker = $this->createChecker();
        $description = 'Discover the best SEO analysis tools for your website optimization needs today.';
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO', 'tools']]);

        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function md_011b_good_length_no_keywords_returns_2(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 155);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['missing', 'keywords']]);

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function md_011c_short_but_has_keywords_returns_1(): void
    {
        $checker = $this->createChecker();
        $description = 'SEO tools';
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        // Too short, even with keywords
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_011d_long_but_has_keywords_returns_1(): void
    {
        $checker = $this->createChecker();
        $description = 'SEO tools ' . str_repeat('a', 175);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        // Too long, even with keywords
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function md_011e_optimal_length_no_keywords_returns_2(): void
    {
        $checker = $this->createChecker();
        $description = str_repeat('a', 155);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function md_011f_just_above_optimal_with_keywords_returns_2(): void
    {
        $checker = $this->createChecker();
        $description = 'SEO ' . str_repeat('a', 161);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function md_011g_just_below_optimal_with_keywords_returns_2(): void
    {
        $checker = $this->createChecker();
        $description = 'SEO ' . str_repeat('a', 144);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function md_011h_boundary_120_with_keywords_returns_2(): void
    {
        $checker = $this->createChecker();
        $description = 'SEO ' . str_repeat('a', 116);
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com', ['keywords' => ['SEO']]);

        $this->assertEquals(2, $result->score);
    }
}
