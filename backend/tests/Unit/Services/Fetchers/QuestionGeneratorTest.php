<?php

namespace Tests\Unit\Services\Fetchers;

use App\Services\Fetchers\QuestionGenerator;
use Tests\TestCase;

/**
 * Unit tests for the QuestionGenerator service.
 *
 * Tests the question keyword variation generator that creates
 * question-based keywords for SEO discovery.
 *
 * @see TASK-5-5
 * @see TEST-5-13
 * @see SPEC-005
 */
class QuestionGeneratorTest extends TestCase
{
    protected QuestionGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new QuestionGenerator();
    }

    // =========================================================================
    // BASIC GENERATION TESTS
    // =========================================================================

    /**
     * Test generate returns 7 question variations.
     * Q-001
     */
    public function test_generate_returns_seven_questions(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertCount(7, $questions);
    }

    /**
     * Test generate returns array of strings.
     */
    public function test_generate_returns_array_of_strings(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertIsArray($questions);
        foreach ($questions as $question) {
            $this->assertIsString($question);
        }
    }

    // =========================================================================
    // PREFIX TESTS
    // =========================================================================

    /**
     * Test "what is" prefix.
     * Q-002
     */
    public function test_what_is_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('what is seo', $questions);
    }

    /**
     * Test "how to" prefix.
     * Q-003
     */
    public function test_how_to_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('how to seo', $questions);
    }

    /**
     * Test "why" prefix.
     * Q-004
     */
    public function test_why_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('why seo', $questions);
    }

    /**
     * Test "when" prefix.
     * Q-005
     */
    public function test_when_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('when seo', $questions);
    }

    /**
     * Test "where" prefix.
     * Q-006
     */
    public function test_where_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('where seo', $questions);
    }

    /**
     * Test "who" prefix.
     * Q-007
     */
    public function test_who_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('who seo', $questions);
    }

    /**
     * Test "which" prefix.
     * Q-008
     */
    public function test_which_prefix(): void
    {
        $questions = $this->generator->generate('seo');

        $this->assertContains('which seo', $questions);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test empty keyword returns empty array.
     * Q-009
     */
    public function test_empty_keyword_returns_empty_array(): void
    {
        $questions = $this->generator->generate('');

        $this->assertEmpty($questions);
    }

    /**
     * Test multi-word keyword correctly combines.
     * Q-010
     */
    public function test_multi_word_keyword(): void
    {
        $questions = $this->generator->generate('seo tools');

        $this->assertCount(7, $questions);
        $this->assertContains('what is seo tools', $questions);
        $this->assertContains('how to seo tools', $questions);
        $this->assertContains('why seo tools', $questions);
    }

    /**
     * Test case is preserved.
     * Q-011
     */
    public function test_case_preserved(): void
    {
        $questions = $this->generator->generate('SEO Tools');

        // Keyword case should be preserved
        $this->assertContains('what is SEO Tools', $questions);
        $this->assertContains('how to SEO Tools', $questions);
    }

    /**
     * Test whitespace is trimmed.
     * Q-012
     */
    public function test_whitespace_trimmed(): void
    {
        $questions = $this->generator->generate('  seo  ');

        // Should trim whitespace
        $this->assertContains('what is seo', $questions);
        $this->assertNotContains('what is   seo', $questions);
    }

    // =========================================================================
    // SPECIAL CHARACTER TESTS
    // =========================================================================

    /**
     * Test special characters preserved in keyword.
     */
    public function test_special_characters_preserved(): void
    {
        $questions = $this->generator->generate('seo & marketing');

        $this->assertContains('what is seo & marketing', $questions);
    }

    /**
     * Test unicode characters preserved.
     */
    public function test_unicode_characters_preserved(): void
    {
        $questions = $this->generator->generate('seo österreich');

        $this->assertContains('what is seo österreich', $questions);
    }

    // =========================================================================
    // CUSTOM PREFIXES TESTS
    // =========================================================================

    /**
     * Test custom prefixes can be set.
     */
    public function test_custom_prefixes(): void
    {
        $customPrefixes = ['what is', 'how to'];
        $generator = new QuestionGenerator($customPrefixes);

        $questions = $generator->generate('seo');

        $this->assertCount(2, $questions);
        $this->assertContains('what is seo', $questions);
        $this->assertContains('how to seo', $questions);
    }

    /**
     * Test empty custom prefixes returns empty array.
     */
    public function test_empty_custom_prefixes(): void
    {
        $generator = new QuestionGenerator([]);

        $questions = $generator->generate('seo');

        $this->assertEmpty($questions);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /**
     * Test generated questions are unique.
     */
    public function test_generated_questions_are_unique(): void
    {
        $questions = $this->generator->generate('seo');

        $uniqueQuestions = array_unique($questions);
        $this->assertCount(count($questions), $uniqueQuestions);
    }

    /**
     * Test multiple calls return same results.
     */
    public function test_multiple_calls_return_same_results(): void
    {
        $questions1 = $this->generator->generate('seo');
        $questions2 = $this->generator->generate('seo');

        $this->assertEquals($questions1, $questions2);
    }

    /**
     * Test different keywords produce different results.
     */
    public function test_different_keywords_different_results(): void
    {
        $questions1 = $this->generator->generate('seo');
        $questions2 = $this->generator->generate('marketing');

        $this->assertNotEquals($questions1, $questions2);
    }

    // =========================================================================
    // INVOKABLE TEST
    // =========================================================================

    /**
     * Test generator is invokable.
     */
    public function test_generator_is_invokable(): void
    {
        $questions = ($this->generator)('seo');

        $this->assertCount(7, $questions);
    }

    // =========================================================================
    // PREFIX FORMAT TESTS
    // =========================================================================

    /**
     * Test prefixes have correct format (lowercase with space).
     */
    public function test_prefix_format(): void
    {
        $questions = $this->generator->generate('test');

        foreach ($questions as $question) {
            // Each question should start with the prefix
            $this->assertMatchesRegularExpression('/^(what is|how to|why|when|where|who|which) /', $question);
        }
    }

    /**
     * Test all standard prefixes are used.
     */
    public function test_all_standard_prefixes_used(): void
    {
        $questions = $this->generator->generate('test');
        $expectedPrefixes = ['what is', 'how to', 'why', 'when', 'where', 'who', 'which'];

        foreach ($expectedPrefixes as $prefix) {
            $found = false;
            foreach ($questions as $question) {
                if (str_starts_with($question, $prefix . ' ')) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Prefix '{$prefix}' not found in generated questions");
        }
    }

    // =========================================================================
    // NULL AND INVALID INPUT TESTS
    // =========================================================================

    /**
     * Test null keyword returns empty array.
     */
    public function test_null_keyword_returns_empty_array(): void
    {
        $questions = $this->generator->generate(null);

        $this->assertEmpty($questions);
    }

    /**
     * Test whitespace-only keyword returns empty array.
     */
    public function test_whitespace_only_keyword(): void
    {
        $questions = $this->generator->generate('   ');

        $this->assertEmpty($questions);
    }
}
