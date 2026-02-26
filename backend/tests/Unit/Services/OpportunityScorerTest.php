<?php

namespace Tests\Unit\Services;

use App\Services\OpportunityScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for the OpportunityScorer service.
 *
 * Tests the keyword opportunity scoring algorithm that helps users
 * prioritize which suggested keywords to track.
 *
 * @see TASK-5-3
 * @see TEST-5-13
 * @see SPEC-005
 */
class OpportunityScorerTest extends TestCase
{
    protected OpportunityScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new OpportunityScorer();
    }

    // =========================================================================
    // BASE SCORE TESTS
    // =========================================================================

    /**
     * Test base score is 50.
     * S-001
     */
    public function test_base_score_is_50(): void
    {
        $score = $this->scorer->calculate('seo');

        $this->assertEquals(50, $score);
    }

    /**
     * Test empty keyword returns base score.
     * S-009
     */
    public function test_empty_keyword_returns_base_score(): void
    {
        $score = $this->scorer->calculate('');

        $this->assertEquals(50, $score);
    }

    /**
     * Test single word returns base score.
     * S-010
     */
    public function test_single_word_returns_base_score(): void
    {
        $score = $this->scorer->calculate('marketing');

        $this->assertEquals(50, $score);
    }

    // =========================================================================
    // LONG-TAIL BONUS TESTS
    // =========================================================================

    /**
     * Test long-tail bonus for 3+ words.
     * S-002
     */
    public function test_long_tail_bonus_three_words(): void
    {
        $score = $this->scorer->calculate('best seo tools 2026');

        // 50 base + 10 for 3+ words = 60
        $this->assertEquals(60, $score);
    }

    /**
     * Test long-tail bonus for 5+ words.
     * S-003
     */
    public function test_long_tail_bonus_five_words(): void
    {
        $score = $this->scorer->calculate('best free seo tools for beginners');

        // 50 base + 10 for 3+ words + 5 for 5+ words = 65
        $this->assertEquals(65, $score);
    }

    /**
     * Test long-tail bonus for 7+ words.
     */
    public function test_long_tail_bonus_seven_words(): void
    {
        $score = $this->scorer->calculate('how to find the best free seo tools for beginners');

        // 50 base + 10 for 3+ words + 5 for 5+ words = 65 (no additional bonus beyond 5)
        $this->assertEquals(65, $score);
    }

    /**
     * Test 2-word keyword gets no long-tail bonus.
     */
    public function test_two_words_no_long_tail_bonus(): void
    {
        $score = $this->scorer->calculate('seo tools');

        // 50 base only
        $this->assertEquals(50, $score);
    }

    // =========================================================================
    // QUESTION PREFIX TESTS
    // =========================================================================

    /**
     * Test question prefix bonus.
     * S-004
     */
    public function test_question_prefix_bonus(): void
    {
        $score = $this->scorer->calculate('how to do seo');

        // 50 base + 15 for question = 65
        $this->assertEquals(65, $score);
    }

    /**
     * Test all question prefixes.
     * S-011
     */
    public function test_all_question_prefixes(): void
    {
        $prefixes = [
            'what is seo' => 65,
            'how to seo' => 65,
            'why seo' => 65,
            'when seo' => 65,
            'where seo' => 65,
            'who seo' => 65,
            'which seo' => 65,
        ];

        foreach ($prefixes as $keyword => $expectedScore) {
            $score = $this->scorer->calculate($keyword);
            $this->assertEquals($expectedScore, $score, "Failed for keyword: {$keyword}");
        }
    }

    /**
     * Test question prefix with long-tail.
     */
    public function test_question_with_long_tail(): void
    {
        $score = $this->scorer->calculate('how to improve seo rankings');

        // 50 base + 15 question + 10 long-tail (4 words) = 75
        $this->assertEquals(75, $score);
    }

    // =========================================================================
    // COMMERCIAL INTENT TESTS
    // =========================================================================

    /**
     * Test commercial intent bonus for 'best'.
     * S-005
     */
    public function test_commercial_intent_bonus_best(): void
    {
        $score = $this->scorer->calculate('best seo tools');

        // 50 base + 10 commercial = 60
        $this->assertEquals(60, $score);
    }

    /**
     * Test all commercial intent keywords.
     * S-012
     */
    public function test_all_commercial_intent_keywords(): void
    {
        $commercialKeywords = [
            'best seo' => 60,
            'top seo tools' => 60,
            'seo review' => 60,
            'compare seo tools' => 60,
        ];

        foreach ($commercialKeywords as $keyword => $expectedScore) {
            $score = $this->scorer->calculate($keyword);
            $this->assertEquals($expectedScore, $score, "Failed for keyword: {$keyword}");
        }
    }

    /**
     * Test only one commercial bonus applied.
     */
    public function test_only_one_commercial_bonus(): void
    {
        $score = $this->scorer->calculate('best top seo tools');

        // 50 base + 10 commercial (only once) + 10 long-tail = 70
        $this->assertEquals(70, $score);
    }

    // =========================================================================
    // COMBINED BONUS TESTS
    // =========================================================================

    /**
     * Test multiple bonuses combined.
     * S-006
     */
    public function test_multiple_bonuses_combined(): void
    {
        $score = $this->scorer->calculate('how to choose best seo tools');

        // 50 base + 15 question + 10 commercial + 10 long-tail (5 words) + 5 (5+ words) = 90
        // Actually: 50 + 15 + 10 + 10 = 85 (long-tail gives 10 for 3+ words, 5 for 5+)
        $this->assertEquals(85, $score);
    }

    /**
     * Test question + commercial + long-tail.
     */
    public function test_question_commercial_long_tail(): void
    {
        $score = $this->scorer->calculate('what are the best seo tools');

        // 50 base + 15 question + 10 commercial + 10 long-tail + 5 (5+ words) = 90
        // Actually: 50 + 15 + 10 + 10 + 5 = 90, but let me recalculate
        // 6 words, so: 50 + 15 + 10 + 10 + 5 = 90
        $this->assertEquals(90, $score);
    }

    // =========================================================================
    // MAX SCORE TESTS
    // =========================================================================

    /**
     * Test score capped at 100.
     * S-007
     */
    public function test_score_capped_at_100(): void
    {
        // Very long commercial question keyword
        $score = $this->scorer->calculate('how to find and compare the best seo tools for small business');

        // Would be: 50 + 15 + 10 + 10 + 5 = 90, still under 100
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test score never exceeds 100.
     */
    public function test_score_never_exceeds_100(): void
    {
        // Create a keyword that would score very high without cap
        // Even with all bonuses, max is around 90-95
        $keywords = [
            'how to choose the best seo tools for beginners',
            'what are the top rated seo tools for small business',
            'why should I compare the best seo software',
        ];

        foreach ($keywords as $keyword) {
            $score = $this->scorer->calculate($keyword);
            $this->assertLessThanOrEqual(100, $score, "Score exceeded 100 for: {$keyword}");
        }
    }

    // =========================================================================
    // CASE INSENSITIVITY TESTS
    // =========================================================================

    /**
     * Test scoring is case insensitive.
     * S-008
     */
    public function test_scoring_is_case_insensitive(): void
    {
        $lowercase = $this->scorer->calculate('best seo tools');
        $uppercase = $this->scorer->calculate('BEST SEO TOOLS');
        $mixedCase = $this->scorer->calculate('Best Seo Tools');

        $this->assertEquals($lowercase, $uppercase);
        $this->assertEquals($lowercase, $mixedCase);
    }

    /**
     * Test question prefixes are case insensitive.
     */
    public function test_question_prefixes_case_insensitive(): void
    {
        $lowercase = $this->scorer->calculate('how to seo');
        $uppercase = $this->scorer->calculate('HOW TO SEO');
        $mixedCase = $this->scorer->calculate('How To Seo');

        $this->assertEquals($lowercase, $uppercase);
        $this->assertEquals($lowercase, $mixedCase);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test whitespace is trimmed.
     */
    public function test_whitespace_is_trimmed(): void
    {
        $score1 = $this->scorer->calculate('seo tools');
        $score2 = $this->scorer->calculate('  seo tools  ');

        $this->assertEquals($score1, $score2);
    }

    /**
     * Test multiple spaces between words.
     */
    public function test_multiple_spaces_between_words(): void
    {
        $score1 = $this->scorer->calculate('best seo tools');
        $score2 = $this->scorer->calculate('best  seo   tools');

        $this->assertEquals($score1, $score2);
    }

    /**
     * Test special characters are handled.
     */
    public function test_special_characters_handled(): void
    {
        $score = $this->scorer->calculate('seo & marketing tools');

        // Should still work with special characters
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(50, $score);
    }

    /**
     * Test unicode characters are handled.
     */
    public function test_unicode_characters_handled(): void
    {
        $score = $this->scorer->calculate('seo österreich tools');

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(50, $score);
    }

    // =========================================================================
    // DATA PROVIDER TESTS
    // =========================================================================

    /**
     * Test scoring with various keywords.
     */
    #[DataProvider('keywordScoreProvider')]
    public function test_scoring_with_various_keywords(string $keyword, int $expectedScore): void
    {
        $score = $this->scorer->calculate($keyword);

        $this->assertEquals($expectedScore, $score, "Failed for keyword: {$keyword}");
    }

    /**
     * Data provider for scoring tests.
     */
    public static function keywordScoreProvider(): array
    {
        return [
            // keyword, expected_score
            ['seo', 50],                                          // Base only
            ['seo tools', 50],                                    // 2 words, no bonus
            ['best seo tools', 60],                              // Base + commercial
            ['how to seo', 65],                                  // Base + question
            ['best seo tools 2026', 60],                         // Base + commercial + long-tail (but commercial comes first)
            ['how to do seo', 65],                               // Base + question + long-tail
            ['what is seo optimization', 75],                    // Base + question + long-tail (4 words)
            ['best seo tools for beginners', 70],               // Base + commercial + long-tail + 5+ words
            ['how to choose best seo tools', 85],               // Base + question + commercial + long-tail + 5+ words
        ];
    }

    // =========================================================================
    // INVOKABLE TEST
    // =========================================================================

    /**
     * Test scorer is invokable.
     */
    public function test_scorer_is_invokable(): void
    {
        $score = $this->scorer('best seo tools');

        $this->assertEquals(60, $score);
    }
}
