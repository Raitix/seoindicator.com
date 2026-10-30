<?php

namespace Tests\Unit\Services\GBP;

use App\Services\GBP\SentimentAnalyzer;
use Tests\TestCase;

/**
 * Unit tests for SentimentAnalyzer service.
 *
 * Tests cover:
 * - AC-1: analyzeSentiment() returns 'positive', 'negative', or 'neutral'
 * - AC-2: Keyword matching (50+ words each category)
 * - AC-3: Negation detection (e.g., "not good" = negative)
 * - AC-4: Mixed sentiment handling
 * - AC-5: Edge cases (empty, unicode, emoji)
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
class SentimentAnalyzerTest extends TestCase
{
    private SentimentAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new SentimentAnalyzer();
    }

    // =================================================================
    // AC-1: Class Structure
    // =================================================================

    /** @test SA-CLS-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(SentimentAnalyzer::class));
    }

    /** @test SA-CLS-002: analyzeSentiment method exists */
    public function test_analyze_sentiment_method_exists(): void
    {
        $this->assertTrue(method_exists($this->analyzer, 'analyzeSentiment'));
    }

    /** @test SA-CLS-003: Returns string type */
    public function test_returns_string_type(): void
    {
        $result = $this->analyzer->analyzeSentiment('test');
        $this->assertIsString($result);
    }

    // =================================================================
    // AC-2: Positive Sentiment Detection
    // =================================================================

    /** @test SA-POS-001: Single positive word - excellent */
    public function test_single_positive_word_excellent(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('excellent'));
    }

    /** @test SA-POS-002: Single positive word - great */
    public function test_single_positive_word_great(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('great'));
    }

    /** @test SA-POS-003: Single positive word - amazing */
    public function test_single_positive_word_amazing(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('amazing'));
    }

    /** @test SA-POS-004: Single positive word - wonderful */
    public function test_single_positive_word_wonderful(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('wonderful'));
    }

    /** @test SA-POS-005: Single positive word - fantastic */
    public function test_single_positive_word_fantastic(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('fantastic'));
    }

    /** @test SA-POS-006: Positive sentence - multiple keywords */
    public function test_positive_sentence_multiple_keywords(): void
    {
        $text = 'This is an excellent product with amazing service!';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-POS-007: Positive sentence - love */
    public function test_positive_sentence_love(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('I love this product!'));
    }

    /** @test SA-POS-008: Positive sentence - recommend */
    public function test_positive_sentence_recommend(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Highly recommend this service!'));
    }

    /** @test SA-POS-009: Positive sentence - satisfied */
    public function test_positive_sentence_satisfied(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Very satisfied with the results.'));
    }

    /** @test SA-POS-010: Positive sentence - impressed */
    public function test_positive_sentence_impressed(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Impressed by the quality!'));
    }

    /** @test SA-POS-011: Case insensitivity - uppercase */
    public function test_case_insensitivity_uppercase(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('EXCELLENT AMAZING FANTASTIC'));
    }

    /** @test SA-POS-012: Case insensitivity - mixed case */
    public function test_case_insensitivity_mixed_case(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('ExCeLlEnT AmAzInG'));
    }

    /** @test SA-POS-013: Positive with punctuation */
    public function test_positive_with_punctuation(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Excellent! Amazing? Fantastic.'));
    }

    /** @test SA-POS-014: 5-star review correlation */
    public function test_five_star_review_correlation(): void
    {
        $text = 'Perfect! Best service ever! Absolutely wonderful experience!';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-POS-015: Professional positive review */
    public function test_professional_positive_review(): void
    {
        $text = 'Professional, helpful, and knowledgeable staff. Fast and efficient service.';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    // =================================================================
    // AC-3: Negative Sentiment Detection
    // =================================================================

    /** @test SA-NEG-001: Single negative word - terrible */
    public function test_single_negative_word_terrible(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('terrible'));
    }

    /** @test SA-NEG-002: Single negative word - bad */
    public function test_single_negative_word_bad(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('bad'));
    }

    /** @test SA-NEG-003: Single negative word - horrible */
    public function test_single_negative_word_horrible(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('horrible'));
    }

    /** @test SA-NEG-004: Single negative word - awful */
    public function test_single_negative_word_awful(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('awful'));
    }

    /** @test SA-NEG-005: Single negative word - disappointed */
    public function test_single_negative_word_disappointed(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('disappointed'));
    }

    /** @test SA-NEG-006: Negative sentence - multiple keywords */
    public function test_negative_sentence_multiple_keywords(): void
    {
        $text = 'Terrible experience, very disappointed and frustrated!';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-NEG-007: Negative sentence - waste */
    public function test_negative_sentence_waste(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Complete waste of money!'));
    }

    /** @test SA-NEG-008: Negative sentence - useless */
    public function test_negative_sentence_useless(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('This product is useless!'));
    }

    /** @test SA-NEG-009: Negative sentence - avoid */
    public function test_negative_sentence_avoid(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Avoid this company at all costs!'));
    }

    /** @test SA-NEG-010: Negative sentence - unprofessional */
    public function test_negative_sentence_unprofessional(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Very unprofessional service.'));
    }

    /** @test SA-NEG-011: 1-star review correlation */
    public function test_one_star_review_correlation(): void
    {
        $text = 'Horrible! Worst experience ever! Complete failure!';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-NEG-012: Negative with slow/unresponsive */
    public function test_negative_with_slow_unresponsive(): void
    {
        $text = 'Slow and unresponsive customer service.';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-NEG-013: Negative with broken/damaged */
    public function test_negative_with_broken_damaged(): void
    {
        $text = 'The product arrived broken and damaged.';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-NEG-014: Negative with regret */
    public function test_negative_with_regret(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('I regret buying this.'));
    }

    /** @test SA-NEG-015: Negative with fraud/scam */
    public function test_negative_with_fraud_scam(): void
    {
        $text = 'This is a scam! Fraudulent company!';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    // =================================================================
    // AC-4: Neutral Sentiment Detection
    // =================================================================

    /** @test SA-NEU-001: Empty text returns neutral */
    public function test_empty_text_returns_neutral(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment(''));
    }

    /** @test SA-NEU-002: Whitespace only returns neutral */
    public function test_whitespace_only_returns_neutral(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment('   '));
    }

    /** @test SA-NEU-003: No sentiment words returns neutral */
    public function test_no_sentiment_words_returns_neutral(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment('The sky is blue.'));
    }

    /** @test SA-NEU-004: Factual statement returns neutral */
    public function test_factual_statement_returns_neutral(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment('The product arrived yesterday.'));
    }

    /** @test SA-NEU-005: Simple question returns neutral */
    public function test_simple_question_returns_neutral(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment('Where is the store located?'));
    }

    /** @test SA-NEU-006: Neutral business hours */
    public function test_neutral_business_hours(): void
    {
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment('Open Monday to Friday 9am to 5pm.'));
    }

    // =================================================================
    // AC-5: Negation Handling
    // =================================================================

    /** @test SA-NEGATION-001: "not good" returns negative */
    public function test_not_good_returns_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('not good'));
    }

    /** @test SA-NEGATION-002: "not bad" returns positive */
    public function test_not_bad_returns_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('not bad'));
    }

    /** @test SA-NEGATION-003: "not happy" returns negative */
    public function test_not_happy_returns_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('I am not happy with this.'));
    }

    /** @test SA-NEGATION-004: "never disappointed" returns positive */
    public function test_never_disappointed_returns_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Never disappointed with their service!'));
    }

    /** @test SA-NEGATION-005: "not terrible" returns positive */
    public function test_not_terrible_returns_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('It was not terrible.'));
    }

    /** @test SA-NEGATION-006: "no issues" returns positive */
    public function test_no_issues_returns_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('No issues at all!'));
    }

    /** @test SA-NEGATION-007: "not excellent" returns negative */
    public function test_not_excellent_returns_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Not excellent, just okay.'));
    }

    /** @test SA-NEGATION-008: "not very good" returns negative */
    public function test_not_very_good_returns_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Not very good at all.'));
    }

    /** @test SA-NEGATION-009: "never had a problem" returns positive */
    public function test_never_had_a_problem_returns_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Never had a problem with them.'));
    }

    /** @test SA-NEGATION-010: Negation with distance */
    public function test_negation_with_distance(): void
    {
        // Negation should work within 1-2 words before sentiment word
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('It was really not good.'));
    }

    // =================================================================
    // AC-6: Mixed Sentiment Handling
    // =================================================================

    /** @test SA-MIX-001: More positive than negative */
    public function test_more_positive_than_negative(): void
    {
        $text = 'Good product but expensive'; // 1 positive, 1 negative
        $result = $this->analyzer->analyzeSentiment($text);
        $this->assertContains($result, ['positive', 'neutral']);
    }

    /** @test SA-MIX-002: More negative than positive */
    public function test_more_negative_than_positive(): void
    {
        $text = 'Nice design but terrible performance and awful support'; // 1 positive, 2 negative
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-MIX-003: Equal positive and negative returns neutral */
    public function test_equal_positive_negative_returns_neutral(): void
    {
        $text = 'Great features but poor support'; // 1 positive, 1 negative
        $this->assertEquals('neutral', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-MIX-004: Mixed with strong positive */
    public function test_mixed_with_strong_positive(): void
    {
        $text = 'Excellent service despite the slow delivery'; // 1 positive (excellent), 1 negative (slow)
        // "excellent" is stronger, should be positive
        $this->assertContains($this->analyzer->analyzeSentiment($text), ['positive', 'neutral']);
    }

    /** @test SA-MIX-005: Mixed with strong negative */
    public function test_mixed_with_strong_negative(): void
    {
        $text = 'Nice people but horrible experience overall'; // 1 positive, 1 negative
        // "horrible" is strong negative
        $this->assertContains($this->analyzer->analyzeSentiment($text), ['negative', 'neutral']);
    }

    // =================================================================
    // AC-7: Edge Cases
    // =================================================================

    /** @test SA-EDGE-001: Very long text */
    public function test_very_long_text(): void
    {
        $text = str_repeat('excellent amazing wonderful fantastic ', 100);
        $result = $this->analyzer->analyzeSentiment($text);
        $this->assertEquals('positive', $result);
    }

    /** @test SA-EDGE-002: Unicode text - Spanish */
    public function test_unicode_text_spanish(): void
    {
        $text = 'Excelente producto! Muy bueno!';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-EDGE-003: Unicode text - Chinese characters */
    public function test_unicode_text_chinese(): void
    {
        // Chinese doesn't have English sentiment words
        $text = '很好的服务'; // "Very good service" in Chinese
        $result = $this->analyzer->analyzeSentiment($text);
        // Should return neutral since no English sentiment words
        $this->assertEquals('neutral', $result);
    }

    /** @test SA-EDGE-004: Emoji handling - positive */
    public function test_emoji_handling_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Great product! 😍👍'));
    }

    /** @test SA-EDGE-005: Emoji handling - negative */
    public function test_emoji_handling_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Terrible service! 😡👎'));
    }

    /** @test SA-EDGE-006: Emoji only */
    public function test_emoji_only(): void
    {
        // No sentiment words, just emoji
        $result = $this->analyzer->analyzeSentiment('😊👍');
        $this->assertEquals('neutral', $result);
    }

    /** @test SA-EDGE-007: Numbers and special characters */
    public function test_numbers_and_special_characters(): void
    {
        $text = '10/10 excellent service! 5 stars! @#\$%';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-EDGE-008: HTML tags stripped */
    public function test_html_tags_in_text(): void
    {
        $text = '<p>Great service!</p> <div>Excellent product!</div>';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-EDGE-009: Multiple exclamation marks */
    public function test_multiple_exclamation_marks(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Amazing!!!'));
    }

    /** @test SA-EDGE-010: All caps positive */
    public function test_all_caps_positive(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('THIS IS ABSOLUTELY FANTASTIC!!!'));
    }

    /** @test SA-EDGE-011: All caps negative */
    public function test_all_caps_negative(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('THIS IS ABSOLUTELY TERRIBLE!!!'));
    }

    // =================================================================
    // AC-8: Real-World Review Samples
    // =================================================================

    /** @test SA-REAL-001: Typical 5-star review */
    public function test_typical_five_star_review(): void
    {
        $text = 'Absolutely love this place! The staff is incredibly friendly and professional. ' .
                'They went above and beyond to help me. Will definitely be coming back!';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-REAL-002: Typical 1-star review */
    public function test_typical_one_star_review(): void
    {
        $text = 'Horrible experience from start to finish. The staff was rude and unprofessional. ' .
                'Waste of time and money. Avoid at all costs!';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-REAL-003: Typical 3-star mixed review */
    public function test_typical_three_star_review(): void
    {
        $text = 'The product itself is okay, nothing special. ' .
                'Delivery was fast but customer service was slow to respond.';
        // 1 neutral-ish positive (fast), 1 negative (slow) - should lean neutral/negative
        $result = $this->analyzer->analyzeSentiment($text);
        $this->assertContains($result, ['neutral', 'negative']);
    }

    /** @test SA-REAL-004: Short positive review */
    public function test_short_positive_review(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('Great service!'));
    }

    /** @test SA-REAL-005: Short negative review */
    public function test_short_negative_review(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('Terrible service.'));
    }

    /** @test SA-REAL-006: Detailed positive review */
    public function test_detailed_positive_review(): void
    {
        $text = 'I have been a customer for 5 years and have always been impressed with their service. ' .
                'The team is knowledgeable, friendly, and helpful. They offer great value and ' .
                'I would highly recommend them to anyone looking for quality work.';
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment($text));
    }

    /** @test SA-REAL-007: Detailed negative review */
    public function test_detailed_negative_review(): void
    {
        $text = 'I regret choosing this company. The service was slow, the staff was rude, ' .
                'and the quality was disappointing. Overpriced for what you get. ' .
                'Save yourself the trouble and go elsewhere.';
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment($text));
    }

    // =================================================================
    // AC-9: Keyword Coverage Tests
    // =================================================================

    /** @test SA-KW-001: Additional positive keywords - superb */
    public function test_positive_keyword_superb(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('superb'));
    }

    /** @test SA-KW-002: Additional positive keywords - outstanding */
    public function test_positive_keyword_outstanding(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('outstanding'));
    }

    /** @test SA-KW-003: Additional positive keywords - brilliant */
    public function test_positive_keyword_brilliant(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('brilliant'));
    }

    /** @test SA-KW-004: Additional positive keywords - perfect */
    public function test_positive_keyword_perfect(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('perfect'));
    }

    /** @test SA-KW-005: Additional positive keywords - reliable */
    public function test_positive_keyword_reliable(): void
    {
        $this->assertEquals('positive', $this->analyzer->analyzeSentiment('reliable'));
    }

    /** @test SA-KW-006: Additional negative keywords - worst */
    public function test_negative_keyword_worst(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('worst'));
    }

    /** @test SA-KW-007: Additional negative keywords - poor */
    public function test_negative_keyword_poor(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('poor'));
    }

    /** @test SA-KW-008: Additional negative keywords - frustrating */
    public function test_negative_keyword_frustrating(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('frustrating'));
    }

    /** @test SA-KW-009: Additional negative keywords - incompetent */
    public function test_negative_keyword_incompetent(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('incompetent'));
    }

    /** @test SA-KW-010: Additional negative keywords - misleading */
    public function test_negative_keyword_misleading(): void
    {
        $this->assertEquals('negative', $this->analyzer->analyzeSentiment('misleading'));
    }

    // =================================================================
    // AC-10: Performance Tests
    // =================================================================

    /** @test SA-PERF-001: Performance - 1000 char text */
    public function test_performance_1000_char_text(): void
    {
        $text = str_repeat('This is excellent and amazing service! ', 25);
        $start = microtime(true);
        $result = $this->analyzer->analyzeSentiment($text);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertEquals('positive', $result);
        $this->assertLessThan(100, $elapsed, 'Analysis should complete in under 100ms');
    }

    /** @test SA-PERF-002: Performance - 5000 char text */
    public function test_performance_5000_char_text(): void
    {
        $text = str_repeat('This is excellent and amazing service! ', 125);
        $start = microtime(true);
        $result = $this->analyzer->analyzeSentiment($text);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertEquals('positive', $result);
        $this->assertLessThan(500, $elapsed, 'Analysis should complete in under 500ms');
    }

    /** @test SA-PERF-003: Multiple calls efficiency */
    public function test_multiple_calls_efficiency(): void
    {
        $texts = [
            'Excellent service!',
            'Terrible experience',
            'Just okay',
            'Love it!',
            'Hate it!',
        ];
        
        $start = microtime(true);
        foreach ($texts as $text) {
            $this->analyzer->analyzeSentiment($text);
        }
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(50, $elapsed, '5 analyses should complete in under 50ms');
    }

    // =================================================================
    // Acceptance Criteria Verification
    // =================================================================

    /** @test AC-001: analyzeSentiment returns valid values only */
    public function test_analyze_sentiment_returns_valid_values_only(): void
    {
        $validValues = ['positive', 'negative', 'neutral'];
        
        $this->assertContains($this->analyzer->analyzeSentiment('excellent'), $validValues);
        $this->assertContains($this->analyzer->analyzeSentiment('terrible'), $validValues);
        $this->assertContains($this->analyzer->analyzeSentiment(''), $validValues);
        $this->assertContains($this->analyzer->analyzeSentiment('random text'), $validValues);
    }

    /** @test AC-002: Keyword matching works for 50+ words each category */
    public function test_keyword_matching_comprehensive(): void
    {
        // Test that at least 50 positive words are recognized
        $positiveWords = [
            'excellent', 'great', 'best', 'amazing', 'wonderful', 'fantastic', 'awesome',
            'superb', 'outstanding', 'perfect', 'love', 'loved', 'happy', 'pleased',
            'satisfied', 'impressed', 'recommend', 'recommended', 'helpful', 'friendly',
            'professional', 'fast', 'quick', 'easy', 'smooth', 'seamless', 'beautiful',
            'clean', 'comfortable', 'reliable', 'trustworthy', 'quality', 'value',
            'worth', 'affordable', 'reasonable', 'efficient', 'effective', 'responsive',
            'attentive', 'knowledgeable', 'skilled', 'expert', 'exceptional', 'brilliant',
            'marvelous', 'terrific', 'splendid', 'delightful', 'superior', 'incredible',
        ];
        
        foreach ($positiveWords as $word) {
            $this->assertEquals(
                'positive',
                $this->analyzer->analyzeSentiment($word),
                "Word '$word' should be detected as positive"
            );
        }
        
        // Test that at least 50 negative words are recognized
        $negativeWords = [
            'terrible', 'bad', 'worst', 'horrible', 'awful', 'poor', 'disappointed',
            'disappointing', 'frustrating', 'waste', 'wasted', 'useless', 'broken',
            'damaged', 'defective', 'slow', 'unresponsive', 'rude', 'unprofessional',
            'overpriced', 'expensive', 'cheap', 'unreliable', 'incompetent', 'unacceptable',
            'annoying', 'irritating', 'misleading', 'dishonest', 'scam', 'fraud', 'fake',
            'lied', 'failed', 'failure', 'regret', 'regretted', 'avoid', 'warned',
            'warning', 'dreadful', 'pathetic', 'mediocre', 'subpar', 'lacking', 'missing',
            'inaccurate', 'wrong', 'horrid', 'atrocious', 'abysmal',
        ];
        
        foreach ($negativeWords as $word) {
            $this->assertEquals(
                'negative',
                $this->analyzer->analyzeSentiment($word),
                "Word '$word' should be detected as negative"
            );
        }
    }
}
