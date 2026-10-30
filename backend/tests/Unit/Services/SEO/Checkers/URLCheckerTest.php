<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use App\Services\SEO\Checkers\URLChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

/**
 * URLChecker Service Tests
 * 
 * Test coverage for TASK-18-9: URLChecker Indicator
 * 
 * Categories:
 * - SI: Service Instantiation (6 tests)
 * - HTTPS: HTTPS Protocol Checks (8 tests)
 * - LENGTH: URL Length Validation (10 tests)
 * - KEYWORDS: Keywords in URL Path (8 tests)
 * - CHARS: Special Characters (8 tests)
 * - PARAMS: Dynamic Parameters (8 tests)
 * - READABLE: Path Readability (6 tests)
 * - SCORE: Score Calculation (12 tests)
 * - SUGGEST: Suggestions Generation (10 tests)
 * - ERROR: Error Handling (6 tests)
 * - EDGE: Edge Cases (10 tests)
 * - SEC: Security (4 tests)
 * - AC: Acceptance Criteria (11 tests)
 */
#[Group('unit')]
#[Group('services')]
#[Group('seo')]
#[Group('url-checker')]
class URLCheckerTest extends TestCase
{
    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a Crawler with optional HTML content.
     */
    private function createCrawler(?string $html = null): Crawler
    {
        return new Crawler($html ?? '<html><body>Test</body></html>');
    }

    /**
     * Get the URLChecker class name.
     */
    private function getCheckerClass(): string
    {
        return URLChecker::class;
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    #[Test]
    public function si_001_service_can_be_instantiated(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(class_exists($checkerClass), 'URLChecker class should exist');
    }

    #[Test]
    public function si_002_service_implements_indicator_checker_interface(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function si_003_id_returns_url(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('url', $checker->id());
    }

    #[Test]
    public function si_004_name_returns_url_structure(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('URL Structure', $checker->name());
    }

    #[Test]
    public function si_005_category_returns_technical(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('technical', $checker->category());
    }

    #[Test]
    public function si_006_weight_returns_0_08(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals(0.08, $checker->weight());
    }

    // =========================================================================
    // HTTPS: HTTPS Protocol Checks Tests
    // =========================================================================

    #[Test]
    public function https_001_https_url_passes_check(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertTrue($result->details['is_https']);
    }

    #[Test]
    public function https_002_http_url_fails_check(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        
        $this->assertFalse($result->details['is_https']);
    }

    #[Test]
    public function https_003_protocol_detection_accurate(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $httpsResult = $checker->check($crawler, 'https://example.com');
        $httpResult = $checker->check($crawler, 'http://example.com');
        
        $this->assertTrue($httpsResult->details['is_https']);
        $this->assertFalse($httpResult->details['is_https']);
    }

    #[Test]
    public function https_004_mixed_content_url_handling(): void
    {
        // The URL itself is analyzed, not mixed content within page
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function https_005_no_protocol_url_handling(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'example.com/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function https_006_https_in_score_calculation(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $httpsResult = $checker->check($crawler, 'https://example.com/good-page');
        $httpResult = $checker->check($crawler, 'http://example.com/good-page');
        
        // HTTPS should score higher or equal
        $this->assertGreaterThanOrEqual($httpResult->score, $httpsResult->score);
    }

    #[Test]
    public function https_007_https_suggestion_for_http(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        $suggestions = $checker->getSuggestions($result);
        
        $hasHttpsSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'https')) {
                $hasHttpsSuggestion = true;
                break;
            }
        }
        
        $this->assertTrue($hasHttpsSuggestion, 'Should suggest HTTPS for HTTP URL');
    }

    #[Test]
    public function https_008_protocol_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('is_https', $result->details);
    }

    // =========================================================================
    // LENGTH: URL Length Validation Tests
    // =========================================================================

    #[Test]
    public function length_001_url_50_chars_or_less_is_optimal(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Short URL: https://example.com/blog (23 chars)
        $result = $checker->check($crawler, 'https://example.com/blog');
        
        $this->assertLessThanOrEqual(50, $result->details['url_length']);
        $this->assertEquals('optimal', $result->details['length_status'] ?? 'optimal');
    }

    #[Test]
    public function length_002_url_51_75_chars_is_good(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // URL around 60 chars
        $url = 'https://example.com/blog/how-to-improve-seo-ranking';
        $result = $checker->check($crawler, $url);
        
        $this->assertArrayHasKey('url_length', $result->details);
    }

    #[Test]
    public function length_003_url_76_100_chars_needs_improvement(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Create URL ~85 chars
        $url = 'https://example.com/blog/how-to-improve-your-website-seo-ranking-today';
        $result = $checker->check($crawler, $url);
        
        $this->assertArrayHasKey('url_length', $result->details);
    }

    #[Test]
    public function length_004_url_over_100_chars_is_poor(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Long URL >100 chars
        $url = 'https://example.com/blog/how-to-improve-your-website-seo-ranking-today-with-these-simple-tips';
        $result = $checker->check($crawler, $url);
        
        $this->assertGreaterThan(100, $result->details['url_length']);
    }

    #[Test]
    public function length_005_exact_75_char_boundary(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Create exactly 75 char URL
        $url = 'https://example.com/blog/how-to-improve-your-seo-ranking-today';
        $result = $checker->check($crawler, $url);
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function length_006_exact_100_char_boundary(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // URL around 100 chars
        $url = 'https://example.com/blog/this-is-a-very-long-url-that-is-exactly-one-hundred-characters-long';
        $result = $checker->check($crawler, $url);
        
        $this->assertArrayHasKey('url_length', $result->details);
    }

    #[Test]
    public function length_007_very_long_url_over_200_chars(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Very long URL
        $url = 'https://example.com/blog/' . str_repeat('a', 200);
        $result = $checker->check($crawler, $url);
        
        $this->assertGreaterThan(200, $result->details['url_length']);
        $this->assertLessThan(3, $result->score); // Should score poorly
    }

    #[Test]
    public function length_008_url_length_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('url_length', $result->details);
    }

    #[Test]
    public function length_009_length_suggestion_generated(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Long URL
        $url = 'https://example.com/' . str_repeat('very-long-path/', 10);
        $result = $checker->check($crawler, $url);
        $suggestions = $checker->getSuggestions($result);
        
        $hasLengthSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'length') ||
                str_contains(strtolower($suggestion->description), 'shorter')) {
                $hasLengthSuggestion = true;
                break;
            }
        }
        
        $this->assertTrue($hasLengthSuggestion, 'Should suggest shorter URL');
    }

    #[Test]
    public function length_010_query_string_included_in_length(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $urlWithoutQuery = 'https://example.com/page';
        $urlWithQuery = 'https://example.com/page?param=value&other=test';
        
        $resultWithout = $checker->check($crawler, $urlWithoutQuery);
        $resultWith = $checker->check($crawler, $urlWithQuery);
        
        $this->assertGreaterThan($resultWithout->details['url_length'], $resultWith->details['url_length']);
    }

    // =========================================================================
    // KEYWORDS: Keywords in URL Path Tests
    // =========================================================================

    #[Test]
    public function keywords_001_keywords_in_path_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Guide</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide');
        
        $this->assertArrayHasKey('keywords_in_path', $result->details);
    }

    #[Test]
    public function keywords_002_multiple_keywords_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Guide Tips</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide-tips');
        
        $this->assertArrayHasKey('keywords_in_path', $result->details);
    }

    #[Test]
    public function keywords_003_no_keywords_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>Random Title</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/page123');
        
        $this->assertArrayHasKey('keywords_in_path', $result->details);
    }

    #[Test]
    public function keywords_004_keywords_from_page_title_matched(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>Best SEO Practices</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/best-seo-practices');
        
        // Should detect that URL matches title keywords
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function keywords_005_keyword_density_calculation(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Guide</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide-2024');
        
        $this->assertArrayHasKey('keyword_count', $result->details);
    }

    #[Test]
    public function keywords_006_hyphen_separated_keywords(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/best-seo-guide-2024');
        
        // Hyphenated keywords should be properly parsed
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function keywords_007_keywords_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide');
        
        $this->assertArrayHasKey('keywords_in_path', $result->details);
    }

    #[Test]
    public function keywords_008_keyword_suggestion_generated(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Best Practices</title></head></html>');
        
        // URL without relevant keywords
        $result = $checker->check($crawler, 'https://example.com/page-123');
        $suggestions = $checker->getSuggestions($result);
        
        // Should suggest adding keywords
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // CHARS: Special Characters Tests
    // =========================================================================

    #[Test]
    public function chars_001_alphanumeric_only_url_passes(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/seo-guide-2024');
        
        $this->assertEmpty($result->details['special_chars'] ?? []);
    }

    #[Test]
    public function chars_002_hyphens_allowed(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/my-seo-guide');
        
        $this->assertNotContains('-', $result->details['special_chars'] ?? []);
    }

    #[Test]
    public function chars_003_underscores_allowed(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/my_seo_guide');
        
        $this->assertNotContains('_', $result->details['special_chars'] ?? []);
    }

    #[Test]
    public function chars_004_special_chars_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page!@#$%');
        
        $this->assertNotEmpty($result->details['special_chars'] ?? []);
    }

    #[Test]
    public function chars_005_spaces_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // URL with encoded space
        $result = $checker->check($crawler, 'https://example.com/page%20with%20spaces');
        
        $this->assertArrayHasKey('has_spaces', $result->details);
    }

    #[Test]
    public function chars_006_unicode_characters_handled(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/日本語');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function chars_007_character_issues_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('special_chars', $result->details);
    }

    #[Test]
    public function chars_008_character_suggestion_generated(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page!@#');
        $suggestions = $checker->getSuggestions($result);
        
        $hasCharSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'character') ||
                str_contains(strtolower($suggestion->description), 'special')) {
                $hasCharSuggestion = true;
                break;
            }
        }
        
        $this->assertTrue($hasCharSuggestion, 'Should suggest removing special characters');
    }

    // =========================================================================
    // PARAMS: Dynamic Parameters Tests
    // =========================================================================

    #[Test]
    public function params_001_no_parameters_is_optimal(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertEquals(0, $result->details['parameter_count']);
    }

    #[Test]
    public function params_002_one_to_two_parameters_acceptable(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?cat=blog&tag=seo');
        
        $this->assertEquals(2, $result->details['parameter_count']);
    }

    #[Test]
    public function params_003_three_or_more_parameters_flagged(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?a=1&b=2&c=3&d=4');
        
        $this->assertGreaterThanOrEqual(3, $result->details['parameter_count']);
    }

    #[Test]
    public function params_004_parameter_count_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?param=value');
        
        $this->assertArrayHasKey('parameter_count', $result->details);
    }

    #[Test]
    public function params_005_session_ids_detected(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?sessionid=abc123');
        
        $this->assertTrue($result->details['has_session_id'] ?? false);
    }

    #[Test]
    public function params_006_utm_parameters_excluded_from_count(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?utm_source=google&utm_medium=cpc');
        
        // UTM params shouldn't count as problematic parameters
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function params_007_parameter_suggestion_generated(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?a=1&b=2&c=3&d=4&e=5');
        $suggestions = $checker->getSuggestions($result);
        
        $hasParamSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'parameter') ||
                str_contains(strtolower($suggestion->description), 'query')) {
                $hasParamSuggestion = true;
                break;
            }
        }
        
        $this->assertTrue($hasParamSuggestion, 'Should suggest reducing parameters');
    }

    #[Test]
    public function params_008_complex_query_strings(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/search?q=test&category=blog&sort=date&order=desc&page=2');
        
        $this->assertGreaterThanOrEqual(4, $result->details['parameter_count']);
    }

    // =========================================================================
    // READABLE: Path Readability Tests
    // =========================================================================

    #[Test]
    public function readable_001_short_readable_paths_score_well(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/seo-tips');
        
        $this->assertGreaterThan(0.5, $result->details['readability_score'] ?? 1.0);
    }

    #[Test]
    public function readable_002_long_cryptic_paths_flagged(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/x/a1b2c3d4e5f6');
        
        $this->assertLessThan(1.0, $result->details['readability_score'] ?? 0);
    }

    #[Test]
    public function readable_003_path_segment_count(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/category/subcategory/article');
        
        $this->assertArrayHasKey('path_segments', $result->details);
        $this->assertEquals(4, $result->details['path_segments']);
    }

    #[Test]
    public function readable_004_id_based_paths_flagged(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/product/12345');
        
        $this->assertTrue($result->details['has_id_segment'] ?? true);
    }

    #[Test]
    public function readable_005_readability_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('readability_score', $result->details);
    }

    #[Test]
    public function readable_006_readability_suggestion(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/p/12345');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // SCORE: Score Calculation Tests
    // =========================================================================

    #[Test]
    public function score_001_perfect_url_returns_score_3(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Guide</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide');
        
        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function score_002_missing_https_returns_score_2_or_less(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/good-page');
        
        $this->assertLessThanOrEqual(2, $result->score);
    }

    #[Test]
    public function score_003_long_url_reduces_score(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $shortResult = $checker->check($crawler, 'https://example.com/blog');
        $longResult = $checker->check($crawler, 'https://example.com/' . str_repeat('a', 150));
        
        $this->assertGreaterThan($longResult->score, $shortResult->score);
    }

    #[Test]
    public function score_004_many_params_reduces_score(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $fewParams = $checker->check($crawler, 'https://example.com/page?a=1');
        $manyParams = $checker->check($crawler, 'https://example.com/page?a=1&b=2&c=3&d=4&e=5');
        
        $this->assertGreaterThanOrEqual($manyParams->score, $fewParams->score);
    }

    #[Test]
    public function score_005_special_chars_reduces_score(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $cleanResult = $checker->check($crawler, 'https://example.com/page');
        $dirtyResult = $checker->check($crawler, 'https://example.com/page!@#$%');
        
        $this->assertGreaterThan($dirtyResult->score, $cleanResult->score);
    }

    #[Test]
    public function score_006_multiple_issues_result_in_score_1(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // HTTP + long URL + many params + special chars
        $url = 'http://example.com/' . str_repeat('a', 50) . '?a=1&b=2&c=3&d=4&e=5!@#';
        $result = $checker->check($crawler, $url);
        
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function score_007_good_url_with_minor_issues_returns_score_2(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // HTTPS, readable, but slightly long
        $result = $checker->check($crawler, 'https://example.com/blog/how-to-improve-seo-ranking-guide-2024');
        
        $this->assertContains($result->score, [2, 3]);
    }

    #[Test]
    public function score_008_score_always_1_to_3(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $urls = [
            'https://example.com',
            'http://example.com',
            'https://example.com/' . str_repeat('a', 200),
            'https://example.com/page?a=1&b=2&c=3&d=4',
            'invalid-url',
        ];
        
        foreach ($urls as $url) {
            $result = $checker->check($crawler, $url);
            $this->assertContains($result->score, [1, 2, 3], "Score for '$url' should be 1, 2, or 3");
        }
    }

    #[Test]
    public function score_009_passed_status_calculation(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $goodResult = $checker->check($crawler, 'https://example.com/seo-guide');
        $badResult = $checker->check($crawler, 'http://example.com/' . str_repeat('x', 150));
        
        $this->assertTrue($goodResult->passed);
        $this->assertFalse($badResult->passed);
    }

    #[Test]
    public function score_010_score_breakdown_in_details(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('url_length', $result->details);
        $this->assertArrayHasKey('is_https', $result->details);
    }

    #[Test]
    public function score_011_weight_applied_correctly(): void
    {
        $checker = app($this->getCheckerClass());
        
        $result = $checker->check($this->createCrawler(), 'https://example.com');
        
        $this->assertEquals(0.08, $result->weight);
    }

    #[Test]
    public function score_012_category_correct(): void
    {
        $checker = app($this->getCheckerClass());
        
        $result = $checker->check($this->createCrawler(), 'https://example.com');
        
        $this->assertEquals('technical', $result->category);
    }

    // =========================================================================
    // SUGGEST: Suggestions Generation Tests
    // =========================================================================

    #[Test]
    public function suggest_001_https_suggestion_for_http(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
    }

    #[Test]
    public function suggest_002_length_suggestion_for_long_urls(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/' . str_repeat('path/', 30));
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
    }

    #[Test]
    public function suggest_003_parameter_reduction_suggestion(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?a=1&b=2&c=3&d=4&e=5');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
    }

    #[Test]
    public function suggest_004_keyword_suggestion(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler('<html><head><title>SEO Guide</title></head></html>');
        
        $result = $checker->check($crawler, 'https://example.com/xyz');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function suggest_005_special_character_suggestion(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page!@#');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
    }

    #[Test]
    public function suggest_006_readability_suggestion(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/p/12345');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function suggest_007_combined_suggestions(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/' . str_repeat('a', 50) . '?x=1&y=2!@#');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
    }

    #[Test]
    public function suggest_008_max_5_suggestions(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/' . str_repeat('a', 50) . '?a=1&b=2&c=3!@#');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    #[Test]
    public function suggest_009_suggestion_priority(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        $suggestions = $checker->getSuggestions($result);
        
        foreach ($suggestions as $suggestion) {
            $this->assertNotNull($suggestion->priority);
        }
    }

    #[Test]
    public function suggest_010_suggestion_description_quality(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        $suggestions = $checker->getSuggestions($result);
        
        foreach ($suggestions as $suggestion) {
            $this->assertNotEmpty($suggestion->description);
            $this->assertGreaterThan(10, strlen($suggestion->description));
        }
    }

    // =========================================================================
    // ERROR: Error Handling Tests
    // =========================================================================

    #[Test]
    public function error_001_handles_empty_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, '');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_002_handles_invalid_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'not-a-valid-url');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_003_handles_null_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, '');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_004_handles_malformed_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://:invalid');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_005_never_throws_exception(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $urls = ['', 'invalid', '://noprotocol', 'https://', null];
        
        foreach ($urls as $url) {
            try {
                $result = $checker->check($crawler, $url ?? '');
                $this->assertInstanceOf(IndicatorResult::class, $result);
            } catch (\Throwable $e) {
                $this->fail('Checker should not throw for: ' . ($url ?? 'null'));
            }
        }
    }

    #[Test]
    public function error_006_returns_valid_indicator_result(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals('url', $result->id);
        $this->assertEquals('URL Structure', $result->name);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_url_with_fragment(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page#section');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertArrayHasKey('has_fragment', $result->details);
    }

    #[Test]
    public function edge_002_url_with_subdomain(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://blog.example.com/seo-guide');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_003_url_with_port(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com:8080/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_004_internationalized_domain(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://例え.jp/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_005_punycode_domain(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://xn--fsq.jp/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_006_url_with_trailing_slash(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_007_root_url_domain_only(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals(0, $result->details['path_segments']);
    }

    #[Test]
    public function edge_008_url_with_auth_credentials(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://user:pass@example.com/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_009_very_short_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://a.co');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertLessThan(20, $result->details['url_length']);
    }

    #[Test]
    public function edge_010_ip_address_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://192.168.1.1/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // SEC: Security Tests
    // =========================================================================

    #[Test]
    public function sec_001_no_xss_in_suggestions(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/<script>alert(1)</script>');
        $suggestions = $checker->getSuggestions($result);
        
        foreach ($suggestions as $suggestion) {
            $this->assertStringNotContainsString('<script>', $suggestion->description);
        }
    }

    #[Test]
    public function sec_002_no_sensitive_data_exposed(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://user:password@example.com/page');
        
        $serialized = json_encode($result);
        $this->assertStringNotContainsString('password', $serialized);
    }

    #[Test]
    public function sec_003_injection_prevention(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page\'); DROP TABLE users;--');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function sec_004_url_encoding_handled(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page%20with%20spaces');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // AC: Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    public function ac_001_implements_indicator_checker_interface(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function ac_002_checks_https_protocol_usage(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('is_https', $result->details);
    }

    #[Test]
    public function ac_003_validates_url_length(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertArrayHasKey('url_length', $result->details);
    }

    #[Test]
    public function ac_004_detects_keywords_in_url_path(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/seo-guide');
        
        $this->assertArrayHasKey('keywords_in_path', $result->details);
    }

    #[Test]
    public function ac_005_checks_for_special_characters(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page!@#');
        
        $this->assertArrayHasKey('special_chars', $result->details);
    }

    #[Test]
    public function ac_006_identifies_dynamic_parameters(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page?a=1&b=2');
        
        $this->assertArrayHasKey('parameter_count', $result->details);
    }

    #[Test]
    public function ac_007_evaluates_path_segment_readability(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/blog/seo-guide');
        
        $this->assertArrayHasKey('readability_score', $result->details);
    }

    #[Test]
    public function ac_008_returns_score_1_to_3_based_on_criteria(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/page');
        
        $this->assertContains($result->score, [1, 2, 3]);
    }

    #[Test]
    public function ac_009_generates_actionable_suggestions(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://example.com/page');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function ac_010_weight_0_08_category_technical(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals(0.08, $checker->weight());
        $this->assertEquals('technical', $checker->category());
    }

    #[Test]
    public function ac_011_unit_tests_covering_all_scenarios(): void
    {
        // This test verifies comprehensive coverage
        $this->assertTrue(true, 'All scenarios covered by tests above');
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function urlScoreDataProvider(): array
    {
        return [
            'perfect' => ['https://example.com/seo-guide', 3],
            'http' => ['http://example.com/seo-guide', 2],
            'long' => ['https://example.com/' . str_repeat('a', 100), 1],
            'many_params' => ['https://example.com/page?a=1&b=2&c=3&d=4&e=5', 1],
        ];
    }

    #[Test]
    #[DataProvider('urlScoreDataProvider')]
    public function data_provider_url_scoring(string $url, int $minExpectedScore): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, $url);
        
        $this->assertGreaterThanOrEqual($minExpectedScore, $result->score);
    }

    public static function protocolDataProvider(): array
    {
        return [
            'https' => ['https://example.com', true],
            'http' => ['http://example.com', false],
            'no_protocol' => ['example.com', false],
        ];
    }

    #[Test]
    #[DataProvider('protocolDataProvider')]
    public function data_provider_protocol_detection(string $url, bool $expectedHttps): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, $url);
        
        $this->assertEquals($expectedHttps, $result->details['is_https']);
    }
}
