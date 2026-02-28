<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use App\Services\SEO\SerpFeatureScoreCalculator;

/**
 * Unit tests for SerpFeatureScoreCalculator.
 * 
 * Tests SERP feature bonus calculation including:
 * - Feature-specific bonus points
 * - Per-keyword bonus cap at 50
 * - Score normalization to 0-100 range
 * - Edge cases and security considerations
 * 
 * @see TASK-10-3
 */
class SerpFeatureScoreCalculatorTest extends TestCase
{
    private SerpFeatureScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SerpFeatureScoreCalculator();
    }

    /**
     * Create a mock keyword object.
     */
    private function createKeyword(array $serpFeatures = []): object
    {
        return new class($serpFeatures) {
            public array $serp_features;
            public function __construct(array $serpFeatures)
            {
                $this->serp_features = $serpFeatures;
            }
        };
    }

    // ============================================
    // TC-001: Single Keyword with Single SERP Feature
    // ============================================

    #[Test] public function single_keyword_with_featured_snippet(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 20 points * 2 = 40
        $this->assertEquals(40.0, $score);
    }

    #[Test] public function single_keyword_with_local_pack(): void
    {
        $keywords = collect([
            $this->createKeyword(['local_pack']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 15 points * 2 = 30
        $this->assertEquals(30.0, $score);
    }

    #[Test] public function single_keyword_with_sitelinks(): void
    {
        $keywords = collect([
            $this->createKeyword(['sitelinks']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 10 points * 2 = 20
        $this->assertEquals(20.0, $score);
    }

    #[Test] public function single_keyword_with_reviews(): void
    {
        $keywords = collect([
            $this->createKeyword(['reviews']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 5 points * 2 = 10
        $this->assertEquals(10.0, $score);
    }

    #[Test] public function single_keyword_with_video(): void
    {
        $keywords = collect([
            $this->createKeyword(['video']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 5 points * 2 = 10
        $this->assertEquals(10.0, $score);
    }

    #[Test] public function single_keyword_with_image_pack(): void
    {
        $keywords = collect([
            $this->createKeyword(['image_pack']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 5 points * 2 = 10
        $this->assertEquals(10.0, $score);
    }

    #[Test] public function single_keyword_with_people_also_ask(): void
    {
        $keywords = collect([
            $this->createKeyword(['people_also_ask']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // 3 points * 2 = 6
        $this->assertEquals(6.0, $score);
    }

    // ============================================
    // TC-002: Single Keyword with Multiple SERP Features
    // ============================================

    #[Test] public function single_keyword_with_two_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'local_pack']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // (20 + 15) * 2 = 70
        $this->assertEquals(70.0, $score);
    }

    #[Test] public function single_keyword_with_three_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // (20 + 15 + 10) * 2 = 90
        $this->assertEquals(90.0, $score);
    }

    #[Test] public function single_keyword_with_all_features(): void
    {
        $keywords = collect([
            $this->createKeyword([
                'featured_snippet',
                'local_pack',
                'sitelinks',
                'reviews',
                'video',
                'image_pack',
                'people_also_ask',
            ]),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Raw sum: 20+15+10+5+5+5+3 = 63, capped at 50, then *2 = 100 (capped at 100)
        $this->assertEquals(100.0, $score);
    }

    #[Test] public function bonus_cap_at_fifty_per_keyword(): void
    {
        // featured_snippet(20) + local_pack(15) + sitelinks(10) + reviews(5) + video(5) = 55
        // Should be capped at 50, then *2 = 100 (capped at 100)
        $keywords = collect([
            $this->createKeyword([
                'featured_snippet',
                'local_pack',
                'sitelinks',
                'reviews',
                'video',
            ]),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Raw: 55, capped: 50, *2 = 100
        $this->assertEquals(100.0, $score);
    }

    #[Test] public function bonus_cap_exactly_at_fifty(): void
    {
        // featured_snippet(20) + local_pack(15) + sitelinks(10) + video(5) = 50
        $keywords = collect([
            $this->createKeyword([
                'featured_snippet',
                'local_pack',
                'sitelinks',
                'video',
            ]),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Exactly 50 * 2 = 100
        $this->assertEquals(100.0, $score);
    }

    // ============================================
    // TC-003: Multiple Keywords with SERP Features
    // ============================================

    #[Test] public function two_keywords_with_same_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']),
            $this->createKeyword(['featured_snippet']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Each: 20, Total: 40, Avg: 20, *2 = 40
        $this->assertEquals(40.0, $score);
    }

    #[Test] public function two_keywords_with_mixed_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']), // 20
            $this->createKeyword(['local_pack']),       // 15
        ]);

        $score = $this->calculator->calculate($keywords);

        // Total: 35, Avg: 17.5, *2 = 35
        $this->assertEquals(35.0, $score);
    }

    #[Test] public function keywords_with_and_without_features_mixed(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']), // 20
            $this->createKeyword([]),                   // 0 (excluded from avg)
            $this->createKeyword(['local_pack']),       // 15
        ]);

        $score = $this->calculator->calculate($keywords);

        // Total: 35, Keywords with features: 2, Avg: 17.5, *2 = 35
        $this->assertEquals(35.0, $score);
    }

    #[Test] public function one_keyword_with_features_many_without(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']),
            $this->createKeyword([]),
            $this->createKeyword([]),
            $this->createKeyword([]),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Only 1 keyword with features: 20 * 2 = 40
        $this->assertEquals(40.0, $score);
    }

    // ============================================
    // TC-004: Edge Cases
    // ============================================

    #[Test] public function empty_collection(): void
    {
        $keywords = collect([]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function keywords_with_no_features(): void
    {
        $keywords = collect([
            $this->createKeyword([]),
            $this->createKeyword([]),
            $this->createKeyword([]),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function keywords_with_null_features(): void
    {
        $keyword = new class {
            public ?array $serp_features = null;
        };

        $keywords = collect([$keyword]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function unknown_serp_feature(): void
    {
        $keywords = collect([
            $this->createKeyword(['unknown_feature']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Unknown features contribute 0 points
        $this->assertEquals(0.0, $score);
    }

    #[Test] public function empty_string_feature(): void
    {
        $keywords = collect([
            $this->createKeyword(['']),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function mixed_known_and_unknown_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'unknown_feature', 'local_pack']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Only known features: 20 + 15 = 35, *2 = 70
        $this->assertEquals(70.0, $score);
    }

    // ============================================
    // TC-005: Score Normalization
    // ============================================

    #[Test] public function score_never_exceeds_100(): void
    {
        // Multiple keywords all with max features
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks', 'reviews', 'video']),
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks', 'reviews', 'video']),
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks', 'reviews', 'video']),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertLessThanOrEqual(100.0, $score);
        $this->assertEquals(100.0, $score);
    }

    #[Test] public function low_bonus_normalized_correctly(): void
    {
        $keywords = collect([
            $this->createKeyword(['people_also_ask']), // 3 points
        ]);

        $score = $this->calculator->calculate($keywords);

        // 3 * 2 = 6
        $this->assertEquals(6.0, $score);
    }

    #[Test] public function fractional_scores_from_averaging(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']), // 20
            $this->createKeyword(['sitelinks']),        // 10
        ]);

        $score = $this->calculator->calculate($keywords);

        // Total: 30, Avg: 15, *2 = 30
        $this->assertEquals(30.0, $score);
    }

    // ============================================
    // TC-006: Security & Adversarial Tests
    // ============================================

    #[Test] public function sql_injection_in_feature_name(): void
    {
        $keywords = collect([
            $this->createKeyword(["'; DROP TABLE keywords; --"]),
        ]);

        $score = $this->calculator->calculate($keywords);

        // SQL injection string is not a valid feature, returns 0
        $this->assertEquals(0.0, $score);
    }

    #[Test] public function xss_in_feature_name(): void
    {
        $keywords = collect([
            $this->createKeyword(['<script>alert(1)</script>']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // XSS string is not a valid feature, returns 0
        $this->assertEquals(0.0, $score);
    }

    #[Test] public function feature_name_with_special_characters(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet\'; DELETE FROM users;--']),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function case_sensitivity(): void
    {
        $keywords = collect([
            $this->createKeyword(['FEATURED_SNIPPET']), // Uppercase
            $this->createKeyword(['Featured_Snippet']), // Mixed case
            $this->createKeyword(['featured_snippet']), // Correct case
        ]);

        $score = $this->calculator->calculate($keywords);

        // Only lowercase 'featured_snippet' matches
        // Total: 20, Avg: 20, *2 = 40
        $this->assertEquals(40.0, $score);
    }

    #[Test] public function large_collection_performance(): void
    {
        // Create 10,000 keywords with random features
        $keywords = collect();
        $features = ['featured_snippet', 'local_pack', 'sitelinks', 'reviews', 'video', 'image_pack', 'people_also_ask'];
        
        for ($i = 0; $i < 10000; $i++) {
            $numFeatures = random_int(0, 3);
            $keywordFeatures = [];
            for ($j = 0; $j < $numFeatures; $j++) {
                $keywordFeatures[] = $features[random_int(0, count($features) - 1)];
            }
            $keywords->push($this->createKeyword($keywordFeatures));
        }

        $startTime = microtime(true);
        $score = $this->calculator->calculate($keywords);
        $duration = microtime(true) - $startTime;

        // Should complete in less than 1 second
        $this->assertLessThan(1.0, $duration, 'Calculation should complete in under 1 second for 10,000 keywords');
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(100.0, $score);
    }

    // ============================================
    // Additional Edge Cases
    // ============================================

    #[Test] public function score_is_always_float(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet']),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertIsFloat($score);
    }

    #[Test] public function score_zero_for_collection_without_features(): void
    {
        $keywords = collect([
            $this->createKeyword([]),
            $this->createKeyword([]),
        ]);

        $score = $this->calculator->calculate($keywords);

        $this->assertEquals(0.0, $score);
    }

    #[Test] public function duplicate_features_in_array(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'featured_snippet', 'featured_snippet']),
        ]);

        $score = $this->calculator->calculate($keywords);

        // Each occurrence adds points: 20 + 20 + 20 = 60, capped at 50, *2 = 100
        $this->assertEquals(100.0, $score);
    }

    #[Test] public function multiple_keywords_with_same_high_value_features(): void
    {
        $keywords = collect([
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks']), // 45
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks']), // 45
            $this->createKeyword(['featured_snippet', 'local_pack', 'sitelinks']), // 45
        ]);

        $score = $this->calculator->calculate($keywords);

        // Total: 135, Avg: 45, *2 = 90
        $this->assertEquals(90.0, $score);
    }

    #[Test] public function feature_order_does_not_matter(): void
    {
        $keywords1 = collect([
            $this->createKeyword(['featured_snippet', 'local_pack']),
        ]);

        $keywords2 = collect([
            $this->createKeyword(['local_pack', 'featured_snippet']),
        ]);

        $score1 = $this->calculator->calculate($keywords1);
        $score2 = $this->calculator->calculate($keywords2);

        $this->assertEquals($score1, $score2);
    }

    #[Test] public function all_seven_feature_types_individually(): void
    {
        $expectedScores = [
            'featured_snippet' => 40.0,
            'local_pack' => 30.0,
            'sitelinks' => 20.0,
            'reviews' => 10.0,
            'video' => 10.0,
            'image_pack' => 10.0,
            'people_also_ask' => 6.0,
        ];

        foreach ($expectedScores as $feature => $expectedScore) {
            $keywords = collect([
                $this->createKeyword([$feature]),
            ]);

            $score = $this->calculator->calculate($keywords);

            $this->assertEquals(
                $expectedScore, 
                $score, 
                "Feature '{$feature}' should produce score {$expectedScore}"
            );
        }
    }

    // ============================================
    // Additional API Tests
    // ============================================

    #[Test] public function get_feature_bonus_returns_correct_values(): void
    {
        $this->assertEquals(20, $this->calculator->getFeatureBonus('featured_snippet'));
        $this->assertEquals(15, $this->calculator->getFeatureBonus('local_pack'));
        $this->assertEquals(10, $this->calculator->getFeatureBonus('sitelinks'));
        $this->assertEquals(5, $this->calculator->getFeatureBonus('reviews'));
        $this->assertEquals(5, $this->calculator->getFeatureBonus('video'));
        $this->assertEquals(5, $this->calculator->getFeatureBonus('image_pack'));
        $this->assertEquals(3, $this->calculator->getFeatureBonus('people_also_ask'));
    }

    #[Test] public function get_feature_bonus_returns_zero_for_unknown(): void
    {
        $this->assertEquals(0, $this->calculator->getFeatureBonus('unknown_feature'));
        $this->assertEquals(0, $this->calculator->getFeatureBonus(''));
    }

    #[Test] public function get_all_feature_bonuses_returns_complete_map(): void
    {
        $bonuses = $this->calculator->getAllFeatureBonuses();

        $this->assertCount(7, $bonuses);
        $this->assertArrayHasKey('featured_snippet', $bonuses);
        $this->assertArrayHasKey('local_pack', $bonuses);
        $this->assertArrayHasKey('sitelinks', $bonuses);
        $this->assertArrayHasKey('reviews', $bonuses);
        $this->assertArrayHasKey('video', $bonuses);
        $this->assertArrayHasKey('image_pack', $bonuses);
        $this->assertArrayHasKey('people_also_ask', $bonuses);
    }
}
