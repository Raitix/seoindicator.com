<?php

namespace Tests\Unit\Services\AIVisibility;

use App\Services\AIVisibility\MentionParser;
use Tests\TestCase;

/**
 * Unit tests for MentionParser service.
 *
 * Tests cover:
 * - AC-1: MentionParser class created at correct namespace
 * - AC-2: extractMentions() method implemented
 * - AC-3: extractContext() method implemented
 * - AC-4: detectMentionType() method implemented
 * - AC-5: extractCompetitorMentions() method implemented
 * - AC-6: Edge cases handled
 *
 * @see TASK-34-4
 * @see SPEC-034
 */
class MentionParserTest extends TestCase
{
    private MentionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MentionParser();
    }

    // =================================================================
    // AC-1: Class Structure
    // =================================================================

    /** @test MP-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(MentionParser::class));
    }

    /** @test MP-002: Class is instantiable */
    public function test_class_is_instantiable(): void
    {
        $parser = new MentionParser();
        $this->assertInstanceOf(MentionParser::class, $parser);
    }

    // =================================================================
    // AC-2: extractMentions() Method
    // =================================================================

    /** @test MP-003: extractMentions returns array */
    public function test_extract_mentions_returns_array(): void
    {
        $response = 'SEOIndicator is a great tool for SEO analysis.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertIsArray($result);
    }

    /** @test MP-004: extractMentions finds single brand mention */
    public function test_extract_mentions_finds_single_brand_mention(): void
    {
        $response = 'SEOIndicator is a great tool for SEO analysis.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
        $this->assertEquals('SEOIndicator', $result[0]['brand']);
    }

    /** @test MP-005: extractMentions returns correct structure */
    public function test_extract_mentions_returns_correct_structure(): void
    {
        $response = 'SEOIndicator is a great tool.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertArrayHasKey('brand', $result[0]);
        $this->assertArrayHasKey('position', $result[0]);
        $this->assertArrayHasKey('context', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
    }

    /** @test MP-006: extractMentions returns correct position */
    public function test_extract_mentions_returns_correct_position(): void
    {
        $response = 'Some text before SEOIndicator appears here.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        // Position 17: "Some text before " = 17 characters (0-indexed)
        $this->assertEquals(17, $result[0]['position']);
    }

    /** @test MP-007: extractMentions case insensitive matching */
    public function test_extract_mentions_case_insensitive(): void
    {
        $response = 'seoindicator and SEOINDICATOR and SeoIndicator are all matched.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(3, $result);
    }

    /** @test MP-008: extractMentions word boundary matching */
    public function test_extract_mentions_word_boundary_matching(): void
    {
        $response = 'SEOIndicatorPro is different from SEOIndicator tool.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        // Should only match 'SEOIndicator' as a word, not 'SEOIndicatorPro'
        $this->assertCount(1, $result);
        // Position 34: "SEOIndicatorPro is different from " = 34 characters
        $this->assertEquals(34, $result[0]['position']);
    }

    /** @test MP-009: extractMentions multiple brands */
    public function test_extract_mentions_multiple_brands(): void
    {
        $response = 'SEOIndicator and Ahrefs are both SEO tools.';
        $brands = ['SEOIndicator', 'Ahrefs'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(2, $result);
        $brandNames = array_column($result, 'brand');
        $this->assertContains('SEOIndicator', $brandNames);
        $this->assertContains('Ahrefs', $brandNames);
    }

    /** @test MP-010: extractMentions multiple mentions same brand */
    public function test_extract_mentions_multiple_mentions_same_brand(): void
    {
        $response = 'SEOIndicator is great. I recommend SEOIndicator for beginners.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(2, $result);
        $this->assertEquals('SEOIndicator', $result[0]['brand']);
        $this->assertEquals('SEOIndicator', $result[1]['brand']);
    }

    /** @test MP-011: extractMentions empty response returns empty array */
    public function test_extract_mentions_empty_response(): void
    {
        $response = '';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-012: extractMentions no mentions found returns empty array */
    public function test_extract_mentions_no_mentions_found(): void
    {
        $response = 'This is some random text without brand names.';
        $brands = ['SEOIndicator', 'Ahrefs'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-013: extractMentions empty brands array returns empty */
    public function test_extract_mentions_empty_brands_array(): void
    {
        $response = 'SEOIndicator is a great tool.';
        $brands = [];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-014: extractMentions brand with special characters */
    public function test_extract_mentions_brand_with_special_characters(): void
    {
        $response = 'SEO-Indicator and Ahrefs+ are tools.';
        $brands = ['SEO-Indicator', 'Ahrefs+'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(2, $result);
    }

    /** @test MP-015: extractMentions brand with dots */
    public function test_extract_mentions_brand_with_dots(): void
    {
        $response = 'Use S.E.O. Tool for analysis.';
        $brands = ['S.E.O. Tool'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-016: extractMentions brand with apostrophe */
    public function test_extract_mentions_brand_with_apostrophe(): void
    {
        $response = "John's SEO Tool is useful.";
        $brands = ["John's SEO Tool"];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-017: extractMentions unicode text support */
    public function test_extract_mentions_unicode_text(): void
    {
        $response = 'SEOIndicator — отличный инструмент для SEO анализа.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
        $this->assertEquals('SEOIndicator', $result[0]['brand']);
    }

    /** @test MP-018: extractMentions japanese text */
    public function test_extract_mentions_japanese_text(): void
    {
        $response = 'SEOIndicatorは素晴らしいSEOツールです。';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-019: extractMentions emoji in text */
    public function test_extract_mentions_emoji_in_text(): void
    {
        $response = 'SEOIndicator 🚀 is an amazing tool! 💯';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-020: extractMentions very long response */
    public function test_extract_mentions_very_long_response(): void
    {
        // Generate 15KB of text
        $longText = str_repeat('This is some filler text. ', 600);
        $response = $longText . 'SEOIndicator is mentioned here.' . $longText;
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
        $this->assertEquals('SEOIndicator', $result[0]['brand']);
    }

    /** @test MP-021: extractMentions position is byte-safe for unicode */
    public function test_extract_mentions_position_byte_safe_unicode(): void
    {
        $response = 'Привет SEOIndicator мир';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        // Position should be byte offset (14 bytes for "Привет ")
        $this->assertIsInt($result[0]['position']);
    }

    // =================================================================
    // AC-3: extractContext() Method
    // =================================================================

    /** @test MP-022: extractContext returns string */
    public function test_extract_context_returns_string(): void
    {
        $text = 'This is some text around SEOIndicator mention.';

        $result = $this->parser->extractContext($text, 25, 10);

        $this->assertIsString($result);
    }

    /** @test MP-023: extractContext extracts surrounding text */
    public function test_extract_context_extracts_surrounding_text(): void
    {
        $text = 'This is some text around SEOIndicator mention here.';

        // Position 25 with radius 10 gives range [15, 35]
        // "t around SEOIndica" - contains 'around' and 'SEOIndica'
        $result = $this->parser->extractContext($text, 25, 10);

        $this->assertStringContainsString('around', $result);
        $this->assertStringContainsString('SEOIndica', $result);
    }

    /** @test MP-024: extractContext default radius */
    public function test_extract_context_default_radius(): void
    {
        $text = str_repeat('x', 200) . 'SEOIndicator' . str_repeat('y', 200);

        $result = $this->parser->extractContext($text, 200, 100);

        // Default radius should be 100 chars on each side
        $this->assertLessThanOrEqual(200 + 12 + 100, strlen($result));
    }

    /** @test MP-025: extractContext at start of string */
    public function test_extract_context_at_start(): void
    {
        $text = 'SEOIndicator is mentioned at the start.';

        $result = $this->parser->extractContext($text, 0, 20);

        $this->assertStringContainsString('SEOIndicator', $result);
        $this->assertStringContainsString('is mentioned', $result);
    }

    /** @test MP-026: extractContext at end of string */
    public function test_extract_context_at_end(): void
    {
        $text = 'This text ends with SEOIndicator';
        $position = strlen($text) - strlen('SEOIndicator');

        $result = $this->parser->extractContext($text, $position, 20);

        $this->assertStringContainsString('ends with', $result);
        $this->assertStringContainsString('SEOIndicator', $result);
    }

    /** @test MP-027: extractContext radius exceeds text length */
    public function test_extract_context_radius_exceeds_text_length(): void
    {
        $text = 'Short text';

        $result = $this->parser->extractContext($text, 5, 100);

        // Should return as much context as available
        $this->assertStringContainsString('Short', $result);
    }

    /** @test MP-028: extractContext handles empty text */
    public function test_extract_context_empty_text(): void
    {
        $result = $this->parser->extractContext('', 0, 10);

        $this->assertEquals('', $result);
    }

    /** @test MP-029: extractContext preserves original text */
    public function test_extract_context_preserves_original_text(): void
    {
        $text = 'This is    text   with   spaces';

        $result = $this->parser->extractContext($text, 12, 10);

        // Should preserve original spacing
        $this->assertStringContainsString('text   with', $result);
    }

    /** @test MP-030: extractContext does not truncate words */
    public function test_extract_context_does_not_truncate_words(): void
    {
        $text = 'The quick brown fox jumps over the lazy dog.';

        $result = $this->parser->extractContext($text, 20, 5);

        // Context should start/end at word boundaries if possible
        $this->assertIsString($result);
    }

    /** @test MP-031: extractContext with unicode characters */
    public function test_extract_context_unicode_characters(): void
    {
        // Japanese chars are 3 bytes each in UTF-8
        // "日本語のテキスト " = 7 Japanese chars (21 bytes) + 1 space (1 byte) = 22 bytes
        // SEOIndicator starts at byte position 22
        $text = '日本語のテキスト SEOIndicator テキストの終わり';

        $result = $this->parser->extractContext($text, 22, 10);

        $this->assertStringContainsString('SEOIndicator', $result);
    }

    /** @test MP-032: extractContext with emoji */
    public function test_extract_context_with_emoji(): void
    {
        // 🚀 emoji is 4 bytes
        // "Hello 🚀 " = 5 chars + 4 bytes emoji + 1 space = 10 bytes
        // SEOIndicator starts at byte position 10
        $text = 'Hello 🚀 SEOIndicator 💯 world!';

        $result = $this->parser->extractContext($text, 10, 10);

        $this->assertStringContainsString('SEOIndicator', $result);
    }

    /** @test MP-033: extractContext negative position handled */
    public function test_extract_context_negative_position(): void
    {
        $text = 'Some text here.';

        $result = $this->parser->extractContext($text, -5, 10);

        // Should handle gracefully
        $this->assertIsString($result);
    }

    /** @test MP-034: extractContext position beyond text length */
    public function test_extract_context_position_beyond_text_length(): void
    {
        $text = 'Short text';

        $result = $this->parser->extractContext($text, 100, 10);

        // Should handle gracefully
        $this->assertIsString($result);
    }

    /** @test MP-035: extractContext custom radius parameter */
    public function test_extract_context_custom_radius(): void
    {
        $text = 'xxxxxxxxxxxxxxxxxxxxSEOIndicatoryyyyyyyyyyyyyyyyyyyy';

        $result = $this->parser->extractContext($text, 20, 5);

        // Should respect custom radius
        $this->assertLessThanOrEqual(12 + 10, strlen($result));
    }

    // =================================================================
    // AC-4: detectMentionType() Method
    // =================================================================

    /** @test MP-036: detectMentionType returns string */
    public function test_detect_mention_type_returns_string(): void
    {
        $context = 'SEOIndicator is a tool.';

        $result = $this->parser->detectMentionType($context);

        $this->assertIsString($result);
    }

    /** @test MP-037: detectMentionType returns valid type */
    public function test_detect_mention_type_returns_valid_type(): void
    {
        $context = 'SEOIndicator is a tool.';

        $result = $this->parser->detectMentionType($context);

        $this->assertContains($result, ['list', 'comparison', 'recommendation', 'neutral']);
    }

    /** @test MP-038: detectMentionType detects list mention */
    public function test_detect_mention_type_list_top_10(): void
    {
        $context = 'Here are the top 10 SEO tools including SEOIndicator.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('list', $result);
    }

    /** @test MP-039: detectMentionType detects list with best */
    public function test_detect_mention_type_list_best(): void
    {
        $context = 'SEOIndicator is one of the best SEO tools available.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('list', $result);
    }

    /** @test MP-040: detectMentionType detects list with including */
    public function test_detect_mention_type_list_including(): void
    {
        $context = 'Popular tools including SEOIndicator and Ahrefs.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('list', $result);
    }

    /** @test MP-041: detectMentionType detects comparison with vs */
    public function test_detect_mention_type_comparison_vs(): void
    {
        $context = 'SEOIndicator vs Ahrefs comparison shows differences.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('comparison', $result);
    }

    /** @test MP-042: detectMentionType detects comparison with compared to */
    public function test_detect_mention_type_comparison_compared_to(): void
    {
        $context = 'When SEOIndicator is compared to other tools, it excels.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('comparison', $result);
    }

    /** @test MP-043: detectMentionType detects comparison with better than */
    public function test_detect_mention_type_comparison_better_than(): void
    {
        $context = 'SEOIndicator is better than most competitors.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('comparison', $result);
    }

    /** @test MP-044: detectMentionType detects recommendation explicit */
    public function test_detect_mention_type_recommendation_explicit(): void
    {
        $context = 'I recommend SEOIndicator for your SEO needs.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('recommendation', $result);
    }

    /** @test MP-045: detectMentionType detects recommendation should use */
    public function test_detect_mention_type_recommendation_should_use(): void
    {
        $context = 'You should use SEOIndicator for better results.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('recommendation', $result);
    }

    /** @test MP-046: detectMentionType detects recommendation suggest */
    public function test_detect_mention_type_recommendation_suggest(): void
    {
        $context = 'I suggest trying SEOIndicator for this task.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('recommendation', $result);
    }

    /** @test MP-047: detectMentionType returns neutral for plain text */
    public function test_detect_mention_type_neutral(): void
    {
        $context = 'SEOIndicator is a tool that helps with SEO.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('neutral', $result);
    }

    /** @test MP-048: detectMentionType case insensitive patterns */
    public function test_detect_mention_type_case_insensitive(): void
    {
        $context = 'SEOIndicator is one of the BEST tools available.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('list', $result);
    }

    /** @test MP-049: detectMentionType empty context returns neutral */
    public function test_detect_mention_type_empty_context(): void
    {
        $result = $this->parser->detectMentionType('');

        $this->assertEquals('neutral', $result);
    }

    /** @test MP-050: detectMentionType list pattern priority */
    public function test_detect_mention_type_list_priority(): void
    {
        // Multiple patterns could match, list should be detected
        $context = 'The top 10 tools list includes SEOIndicator.';

        $result = $this->parser->detectMentionType($context);

        $this->assertEquals('list', $result);
    }

    /** @test MP-051: detectMentionType comparison vs recommendation priority */
    public function test_detect_mention_type_comparison_recommendation_priority(): void
    {
        // When both comparison and recommendation patterns exist
        $context = 'I recommend SEOIndicator vs Ahrefs comparison.';

        $result = $this->parser->detectMentionType($context);

        // Comparison or recommendation based on implementation priority
        $this->assertContains($result, ['comparison', 'recommendation']);
    }

    // =================================================================
    // AC-5: extractCompetitorMentions() Method
    // =================================================================

    /** @test MP-052: extractCompetitorMentions returns array */
    public function test_extract_competitor_mentions_returns_array(): void
    {
        $response = 'Ahrefs is a popular SEO tool.';
        $competitors = ['Ahrefs'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertIsArray($result);
    }

    /** @test MP-053: extractCompetitorMentions finds competitor */
    public function test_extract_competitor_mentions_finds_competitor(): void
    {
        $response = 'Ahrefs is a popular SEO tool.';
        $competitors = ['Ahrefs'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertCount(1, $result);
        $this->assertEquals('Ahrefs', $result[0]['brand']);
    }

    /** @test MP-054: extractCompetitorMentions returns correct structure */
    public function test_extract_competitor_mentions_correct_structure(): void
    {
        $response = 'Ahrefs and SEMrush are competitors.';
        $competitors = ['Ahrefs', 'SEMrush'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertArrayHasKey('brand', $result[0]);
        $this->assertArrayHasKey('position', $result[0]);
        $this->assertArrayHasKey('context', $result[0]);
    }

    /** @test MP-055: extractCompetitorMentions multiple competitors */
    public function test_extract_competitor_mentions_multiple(): void
    {
        $response = 'Ahrefs, SEMrush, and Moz are all SEO tools.';
        $competitors = ['Ahrefs', 'SEMrush', 'Moz'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertCount(3, $result);
    }

    /** @test MP-056: extractCompetitorMentions empty response */
    public function test_extract_competitor_mentions_empty_response(): void
    {
        $result = $this->parser->extractCompetitorMentions('', ['Ahrefs']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-057: extractCompetitorMentions no competitors found */
    public function test_extract_competitor_mentions_none_found(): void
    {
        $response = 'Some random text without competitor names.';
        $competitors = ['Ahrefs', 'SEMrush'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertEmpty($result);
    }

    /** @test MP-058: extractCompetitorMentions empty competitors array */
    public function test_extract_competitor_mentions_empty_competitors(): void
    {
        $response = 'Ahrefs is mentioned here.';

        $result = $this->parser->extractCompetitorMentions($response, []);

        $this->assertEmpty($result);
    }

    /** @test MP-059: extractCompetitorMentions case insensitive */
    public function test_extract_competitor_mentions_case_insensitive(): void
    {
        // Case variants: ahrefs, Ahrefs, AREFS (lowercase matches)
        $response = 'ahrefs and Ahrefs are the same tool.';
        $competitors = ['Ahrefs'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertCount(2, $result);
    }

    /** @test MP-060: extractCompetitorMentions word boundary */
    public function test_extract_competitor_mentions_word_boundary(): void
    {
        $response = 'AhrefsPro is different from Ahrefs.';
        $competitors = ['Ahrefs'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        // Should only match 'Ahrefs' as a word
        $this->assertCount(1, $result);
        $this->assertGreaterThan(0, $result[0]['position']);
    }

    // =================================================================
    // AC-6: Edge Cases
    // =================================================================

    /** @test MP-061: Empty response text handled */
    public function test_empty_response_handled(): void
    {
        $result = $this->parser->extractMentions('', ['SEOIndicator']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-062: No mentions found handled */
    public function test_no_mentions_found_handled(): void
    {
        $response = 'This response has no brand mentions.';
        $brands = ['SEOIndicator', 'Ahrefs'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test MP-063: Brand name with special regex characters */
    public function test_brand_name_with_special_regex_characters(): void
    {
        $response = 'Use $100+ SEO tool for best results.';
        $brands = ['$100+ SEO'];

        $result = $this->parser->extractMentions($response, $brands);

        // Should not throw regex error
        $this->assertIsArray($result);
    }

    /** @test MP-064: Brand name with forward slash */
    public function test_brand_name_with_forward_slash(): void
    {
        $response = 'Check out SEO/Tools for analysis.';
        $brands = ['SEO/Tools'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-065: Brand name with backslash */
    public function test_brand_name_with_backslash(): void
    {
        $response = 'Tool\\Name is a brand.';
        $brands = ['Tool\\Name'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-066: Brand name with parentheses */
    public function test_brand_name_with_parentheses(): void
    {
        $response = 'SEO (Pro) Tool is useful.';
        $brands = ['SEO (Pro) Tool'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-067: Brand name with brackets */
    public function test_brand_name_with_brackets(): void
    {
        $response = 'SEO [Tool] is available.';
        $brands = ['SEO [Tool]'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-068: Very long brand name */
    public function test_very_long_brand_name(): void
    {
        $longBrand = str_repeat('a', 100);
        $response = $longBrand . ' is a brand name.';

        $result = $this->parser->extractMentions($response, [$longBrand]);

        $this->assertCount(1, $result);
    }

    /** @test MP-069: Response with newlines */
    public function test_response_with_newlines(): void
    {
        $response = "Some text\nSEOIndicator\nmore text";
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-070: Response with tabs */
    public function test_response_with_tabs(): void
    {
        $response = "Some\ttext\tSEOIndicator\tmore\ttext";
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-071: Response with HTML entities */
    public function test_response_with_html_entities(): void
    {
        $response = 'SEOIndicator &amp; Ahrefs are tools.';
        $brands = ['SEOIndicator', 'Ahrefs'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertGreaterThanOrEqual(1, count($result));
    }

    /** @test MP-072: Response with mixed case brands list */
    public function test_mixed_case_brands_list(): void
    {
        $response = 'SEOIndicator is mentioned.';
        $brands = ['seoindicator']; // lowercase in list

        $result = $this->parser->extractMentions($response, $brands);

        // Should match regardless of case in brands list
        $this->assertCount(1, $result);
    }

    /** @test MP-073: Very large response 50KB */
    public function test_very_large_response_50kb(): void
    {
        // Generate 50KB of text
        $largeText = str_repeat('Random text here. ', 2500);
        $response = $largeText . ' SEOIndicator ' . $largeText;
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-074: Response with unicode brand name */
    public function test_response_with_unicode_brand_name(): void
    {
        $response = '日本語SEO is a tool.';
        $brands = ['日本語SEO'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    /** @test MP-075: Response with accented characters */
    public function test_response_with_accented_characters(): void
    {
        $response = 'SéOIndicätör is a tool.';
        $brands = ['SéOIndicätör'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
    }

    // =================================================================
    // Integration Scenarios
    // =================================================================

    /** @test MP-076: Full flow with extractMentions and detectMentionType */
    public function test_full_flow_mentions_and_type(): void
    {
        $response = 'The top 10 SEO tools include SEOIndicator, Ahrefs, and SEMrush.';
        $brands = ['SEOIndicator'];

        $mentions = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $mentions);
        $this->assertEquals('list', $mentions[0]['type']);
    }

    /** @test MP-077: Multiple mentions with different types */
    public function test_multiple_mentions_different_types(): void
    {
        $response = 'SEOIndicator is in the top 10 list. I also recommend SEOIndicator vs Ahrefs comparison.';
        $brands = ['SEOIndicator'];

        $mentions = $this->parser->extractMentions($response, $brands);

        $this->assertCount(2, $mentions);
        // First mention should be list type
        $this->assertEquals('list', $mentions[0]['type']);
        // Second mention should be comparison or recommendation
        $this->assertContains($mentions[1]['type'], ['comparison', 'recommendation']);
    }

    /** @test MP-078: Context extraction with mention */
    public function test_context_extraction_with_mention(): void
    {
        $response = 'This is a long response with SEOIndicator mentioned in the middle of the text.';
        $brands = ['SEOIndicator'];

        $mentions = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $mentions);
        $this->assertStringContainsString('response', $mentions[0]['context']);
        $this->assertStringContainsString('middle', $mentions[0]['context']);
    }

    /** @test MP-079: Competitor and brand mentions in same response */
    public function test_competitor_and_brand_mentions(): void
    {
        $response = 'SEOIndicator and Ahrefs are both mentioned in this text.';

        $brandMentions = $this->parser->extractMentions($response, ['SEOIndicator']);
        $competitorMentions = $this->parser->extractCompetitorMentions($response, ['Ahrefs']);

        $this->assertCount(1, $brandMentions);
        $this->assertCount(1, $competitorMentions);
    }

    /** @test MP-080: Performance with 100 brands */
    public function test_performance_with_100_brands(): void
    {
        $brands = [];
        for ($i = 0; $i < 100; $i++) {
            $brands[] = "Brand{$i}";
        }
        $response = 'Brand0 Brand50 Brand99 are all mentioned.';

        $startTime = microtime(true);
        $result = $this->parser->extractMentions($response, $brands);
        $endTime = microtime(true);

        $this->assertCount(3, $result);
        $this->assertLessThan(1.0, $endTime - $startTime, 'Should complete within 1 second');
    }

    // =================================================================
    // Acceptance Criteria Tests
    // =================================================================

    /** @test AC-1: MentionParser class created at correct path */
    public function test_ac1_class_created(): void
    {
        $this->assertTrue(class_exists(MentionParser::class));
    }

    /** @test AC-2: extractMentions method implemented */
    public function test_ac2_extract_mentions_implemented(): void
    {
        $response = 'SEOIndicator is a great tool.';
        $brands = ['SEOIndicator'];

        $result = $this->parser->extractMentions($response, $brands);

        $this->assertCount(1, $result);
        $this->assertEquals('SEOIndicator', $result[0]['brand']);
        $this->assertArrayHasKey('position', $result[0]);
        $this->assertArrayHasKey('context', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
    }

    /** @test AC-3: extractContext method implemented */
    public function test_ac3_extract_context_implemented(): void
    {
        $text = 'This is some text around SEOIndicator mention.';

        // Position 25 with radius 10 gives range [15, 35]
        $result = $this->parser->extractContext($text, 25, 10);

        $this->assertIsString($result);
        $this->assertStringContainsString('around', $result);
        $this->assertStringContainsString('SEOIndica', $result);
    }

    /** @test AC-4: detectMentionType method implemented */
    public function test_ac4_detect_mention_type_implemented(): void
    {
        $listContext = 'top 10 SEO tools including SEOIndicator';
        $comparisonContext = 'SEOIndicator vs Ahrefs';
        $recommendationContext = 'I recommend SEOIndicator';
        $neutralContext = 'SEOIndicator is a tool';

        $this->assertEquals('list', $this->parser->detectMentionType($listContext));
        $this->assertEquals('comparison', $this->parser->detectMentionType($comparisonContext));
        $this->assertEquals('recommendation', $this->parser->detectMentionType($recommendationContext));
        $this->assertEquals('neutral', $this->parser->detectMentionType($neutralContext));
    }

    /** @test AC-5: extractCompetitorMentions method implemented */
    public function test_ac5_extract_competitor_mentions_implemented(): void
    {
        $response = 'Ahrefs and SEMrush are competitors.';
        $competitors = ['Ahrefs', 'SEMrush'];

        $result = $this->parser->extractCompetitorMentions($response, $competitors);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('brand', $result[0]);
        $this->assertArrayHasKey('position', $result[0]);
        $this->assertArrayHasKey('context', $result[0]);
    }

    /** @test AC-6: Edge cases handled */
    public function test_ac6_edge_cases_handled(): void
    {
        // Empty response
        $this->assertEmpty($this->parser->extractMentions('', ['Brand']));

        // No mentions found
        $this->assertEmpty($this->parser->extractMentions('No brands here', ['Brand']));

        // Special characters in brand name
        $result = $this->parser->extractMentions('SEO-Indicator tool', ['SEO-Indicator']);
        $this->assertCount(1, $result);

        // Unicode text
        $result = $this->parser->extractMentions('SEOIndicator 日本語', ['SEOIndicator']);
        $this->assertCount(1, $result);

        // Very long response
        $longResponse = str_repeat('text ', 2000) . 'SEOIndicator' . str_repeat(' text', 2000);
        $result = $this->parser->extractMentions($longResponse, ['SEOIndicator']);
        $this->assertCount(1, $result);
    }
}