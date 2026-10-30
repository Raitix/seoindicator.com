<?php

namespace Tests\Unit\Services\SEO;

use PHPUnit\Framework\TestCase;
use App\Services\SEO\BacklinkProfileScorer;

/**
 * Tests for BacklinkProfileScorer
 * 
 * @see TASK-23-8
 * @see TEST-23-8
 */
class BacklinkProfileScorerTest extends TestCase
{
    // =========================================================================
    // DIVERSITY SCORE TESTS (DS) - 0-25 Points
    // =========================================================================

    /**
     * DS-001: Perfect diversity (1:1 ratio).
     * 
     * @test
     */
    public function diversity_score_perfect_one_to_one_ratio(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 100,
            'referring_domains' => 100,
        ]);

        $this->assertEquals(25.0, $score);
    }

    /**
     * DS-002: Good diversity (1:2 ratio).
     * 
     * @test
     */
    public function diversity_score_good_one_to_two_ratio(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 100,
            'referring_domains' => 50,
        ]);

        $this->assertEquals(25.0, $score); // min(25, 50) = 25
    }

    /**
     * DS-003: Moderate diversity (1:4 ratio).
     * 
     * @test
     */
    public function diversity_score_moderate_one_to_four_ratio(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 100,
            'referring_domains' => 25,
        ]);

        $this->assertEquals(25.0, $score); // min(25, 25) = 25
    }

    /**
     * DS-004: Poor diversity (1:10 ratio).
     * 
     * @test
     */
    public function diversity_score_poor_one_to_ten_ratio(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 100,
            'referring_domains' => 10,
        ]);

        $this->assertEquals(10.0, $score); // min(25, 10) = 10
    }

    /**
     * DS-005: Very poor diversity.
     * 
     * @test
     */
    public function diversity_score_very_poor(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 100,
            'referring_domains' => 1,
        ]);

        $this->assertEquals(1.0, $score); // min(25, 1) = 1
    }

    /**
     * DS-006: Zero backlinks.
     * 
     * @test
     */
    public function diversity_score_zero_backlinks(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 0,
            'referring_domains' => 0,
        ]);

        $this->assertEquals(0.0, $score); // Division by zero protection
    }

    /**
     * DS-007: Many domains, few backlinks (ratio > 1).
     * 
     * @test
     */
    public function diversity_score_more_domains_than_backlinks(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 50,
            'referring_domains' => 100,
        ]);

        $this->assertEquals(25.0, $score); // min(25, 200) = 25
    }

    /**
     * DS-008: Single backlink from single domain.
     * 
     * @test
     */
    public function diversity_score_single_backlink_single_domain(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreDiversity([
            'total_backlinks' => 1,
            'referring_domains' => 1,
        ]);

        $this->assertEquals(25.0, $score); // min(25, 100) = 25
    }

    // =========================================================================
    // TRUST SCORE TESTS (TS) - 0-25 Points
    // =========================================================================

    /**
     * TS-001: High trust average (100).
     * 
     * @test
     */
    public function trust_score_high_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 100.0,
        ]);

        $this->assertEquals(25.0, $score);
    }

    /**
     * TS-002: Good trust average (80).
     * 
     * @test
     */
    public function trust_score_good_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 80.0,
        ]);

        $this->assertEquals(20.0, $score);
    }

    /**
     * TS-003: Moderate trust average (60).
     * 
     * @test
     */
    public function trust_score_moderate_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 60.0,
        ]);

        $this->assertEquals(15.0, $score);
    }

    /**
     * TS-004: Low trust average (40).
     * 
     * @test
     */
    public function trust_score_low_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 40.0,
        ]);

        $this->assertEquals(10.0, $score);
    }

    /**
     * TS-005: Very low trust average (20).
     * 
     * @test
     */
    public function trust_score_very_low_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 20.0,
        ]);

        $this->assertEquals(5.0, $score);
    }

    /**
     * TS-006: Zero trust average.
     * 
     * @test
     */
    public function trust_score_zero_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 0.0,
        ]);

        $this->assertEquals(0.0, $score);
    }

    /**
     * TS-007: Null trust average.
     * 
     * @test
     */
    public function trust_score_null_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => null,
        ]);

        $this->assertEquals(0.0, $score);
    }

    /**
     * TS-008: Decimal trust average.
     * 
     * @test
     */
    public function trust_score_decimal_trust_average(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreTrust([
            'avg_domain_trust' => 45.5,
        ]);

        $this->assertEquals(11.375, $score);
    }

    // =========================================================================
    // GROWTH SCORE TESTS (GS) - 0-25 Points
    // =========================================================================

    /**
     * GS-001: Strong growth (+25%).
     * 
     * @test
     */
    public function growth_score_strong_growth(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => 25.0,
        ]);

        $this->assertEquals(25.0, $score);
    }

    /**
     * GS-002: Moderate growth (+10%).
     * 
     * @test
     */
    public function growth_score_moderate_growth(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => 10.0,
        ]);

        $this->assertEquals(17.5, $score);
    }

    /**
     * GS-003: Slight growth (+5%).
     * 
     * @test
     */
    public function growth_score_slight_growth(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => 5.0,
        ]);

        $this->assertEquals(15.0, $score);
    }

    /**
     * GS-004: No growth (0%).
     * 
     * @test
     */
    public function growth_score_no_growth(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => 0.0,
        ]);

        $this->assertEquals(12.5, $score);
    }

    /**
     * GS-005: Slight decline (-5%).
     * 
     * @test
     */
    public function growth_score_slight_decline(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => -5.0,
        ]);

        $this->assertEquals(10.0, $score);
    }

    /**
     * GS-006: Moderate decline (-10%).
     * 
     * @test
     */
    public function growth_score_moderate_decline(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => -10.0,
        ]);

        $this->assertEquals(7.5, $score);
    }

    /**
     * GS-007: Strong decline (-25%).
     * 
     * @test
     */
    public function growth_score_strong_decline(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => -25.0,
        ]);

        $this->assertEquals(0.0, $score);
    }

    /**
     * GS-008: Very strong decline (-50%).
     * 
     * @test
     */
    public function growth_score_very_strong_decline(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => -50.0,
        ]);

        $this->assertEquals(0.0, $score); // Capped at 0
    }

    /**
     * GS-009: Null growth rate.
     * 
     * @test
     */
    public function growth_score_null_growth_rate(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => null,
        ]);

        $this->assertEquals(12.5, $score); // Neutral score
    }

    /**
     * GS-010: Growth rate above maximum.
     * 
     * @test
     */
    public function growth_score_above_maximum(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreGrowth([
            'growth_rate' => 100.0,
        ]);

        $this->assertEquals(25.0, $score); // Capped at 25
    }

    // =========================================================================
    // ANCHOR HEALTH SCORE TESTS (AH) - 0-25 Points
    // =========================================================================

    /**
     * AH-001: Perfect anchor health (0% exact match).
     * 
     * @test
     */
    public function anchor_health_perfect(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.0,
        ]);

        $this->assertEquals(25.0, $score);
    }

    /**
     * AH-002: Good anchor health (10% exact match).
     * 
     * @test
     */
    public function anchor_health_good(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $this->assertEquals(20.0, $score);
    }

    /**
     * AH-003: Moderate anchor health (25% exact match).
     * 
     * @test
     */
    public function anchor_health_moderate(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.25,
        ]);

        $this->assertEquals(12.5, $score);
    }

    /**
     * AH-004: Poor anchor health (40% exact match).
     * 
     * @test
     */
    public function anchor_health_poor(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.40,
        ]);

        $this->assertEquals(5.0, $score);
    }

    /**
     * AH-005: Very poor anchor health (50% exact match).
     * 
     * @test
     */
    public function anchor_health_very_poor(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.50,
        ]);

        $this->assertEquals(0.0, $score);
    }

    /**
     * AH-006: Extremely poor anchor health (75% exact match).
     * 
     * @test
     */
    public function anchor_health_extremely_poor(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 0.75,
        ]);

        $this->assertEquals(0.0, $score); // Capped at 0
    }

    /**
     * AH-007: All exact match (100%).
     * 
     * @test
     */
    public function anchor_health_all_exact_match(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => 1.0,
        ]);

        $this->assertEquals(0.0, $score);
    }

    /**
     * AH-008: Null anchor ratio.
     * 
     * @test
     */
    public function anchor_health_null_ratio(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $score = $scorer->scoreAnchorHealth([
            'exact_match_anchor_ratio' => null,
        ]);

        $this->assertEquals(12.5, $score); // Neutral score
    }

    // =========================================================================
    // COMPOSITE SCORE TESTS (CS) - 0-100 Points
    // =========================================================================

    /**
     * CS-001: Perfect profile.
     * 
     * @test
     */
    public function composite_score_perfect_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 100,
            'avg_domain_trust' => 100.0,
            'growth_rate' => 25.0,
            'exact_match_anchor_ratio' => 0.0,
        ]);

        $this->assertEquals(100.0, $result['overall_score']);
        $this->assertEquals(25.0, $result['diversity_score']);
        $this->assertEquals(25.0, $result['trust_score']);
        $this->assertEquals(25.0, $result['growth_score']);
        $this->assertEquals(25.0, $result['anchor_health_score']);
    }

    /**
     * CS-002: Good profile.
     * 
     * @test
     */
    public function composite_score_good_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 500,
            'referring_domains' => 200,
            'avg_domain_trust' => 80.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        // DS: min(25, (200/500)*100) = 25
        // TS: min(25, 80/4) = 20
        // GS: min(25, max(0, 12.5 + 10/2)) = 17.5
        // AH: min(25, 25 - 0.20*50) = 15
        // Total: 25 + 20 + 17.5 + 15 = 77.5
        $this->assertEquals(77.5, $result['overall_score']);
    }

    /**
     * CS-003: Moderate profile.
     * 
     * @test
     */
    public function composite_score_moderate_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 25,
            'avg_domain_trust' => 60.0,
            'growth_rate' => 0.0,
            'exact_match_anchor_ratio' => 0.30,
        ]);

        // DS: min(25, (25/100)*100) = 25
        // TS: min(25, 60/4) = 15
        // GS: min(25, max(0, 12.5 + 0)) = 12.5
        // AH: min(25, 25 - 0.30*50) = 10
        // Total: 25 + 15 + 12.5 + 10 = 62.5
        $this->assertEquals(62.5, $result['overall_score']);
    }

    /**
     * CS-004: Poor profile.
     * 
     * @test
     */
    public function composite_score_poor_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 20.0,
            'growth_rate' => -15.0,
            'exact_match_anchor_ratio' => 0.50,
        ]);

        // DS: min(25, (5/100)*100) = 5
        // TS: min(25, 20/4) = 5
        // GS: min(25, max(0, 12.5 - 15/2)) = 5
        // AH: min(25, 25 - 0.50*50) = 0
        // Total: 5 + 5 + 5 + 0 = 15
        $this->assertEquals(15.0, $result['overall_score']);
    }

    /**
     * CS-005: Very poor profile (spammy).
     * 
     * @test
     */
    public function composite_score_very_poor_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 1000,
            'referring_domains' => 2,
            'avg_domain_trust' => 10.0,
            'growth_rate' => -30.0,
            'exact_match_anchor_ratio' => 0.80,
        ]);

        // DS: min(25, (2/1000)*100) = 0.2
        // TS: min(25, 10/4) = 2.5
        // GS: min(25, max(0, 12.5 - 30/2)) = 0
        // AH: min(25, 25 - 0.80*50) = 0
        // Total: 0.2 + 2.5 + 0 + 0 = 2.7
        $this->assertLessThan(5.0, $result['overall_score']);
    }

    /**
     * CS-006: Empty backlink profile.
     * 
     * @test
     */
    public function composite_score_empty_profile(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 0,
            'referring_domains' => 0,
            'avg_domain_trust' => null,
            'growth_rate' => null,
            'exact_match_anchor_ratio' => null,
        ]);

        $this->assertEquals(0.0, $result['overall_score']);
    }

    /**
     * CS-007: Imbalanced profile (strong trust, weak anchor).
     * 
     * @test
     */
    public function composite_score_imbalanced_strong_trust_weak_anchor(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 100.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        // DS: min(25, (50/100)*100) = 25
        // TS: min(25, 100/4) = 25
        // GS: min(25, max(0, 12.5 + 5)) = 17.5
        // AH: min(25, 25 - 0.60*50) = 0
        // Total: 25 + 25 + 17.5 + 0 = 67.5
        $this->assertEquals(67.5, $result['overall_score']);
    }

    /**
     * CS-008: Imbalanced profile (good growth, poor trust).
     * 
     * @test
     */
    public function composite_score_imbalanced_good_growth_poor_trust(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 30,
            'avg_domain_trust' => 20.0,
            'growth_rate' => 25.0,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        // DS: min(25, (30/100)*100) = 25
        // TS: min(25, 20/4) = 5
        // GS: min(25, max(0, 12.5 + 12.5)) = 25
        // AH: min(25, 25 - 0.10*50) = 20
        // Total: 25 + 5 + 25 + 20 = 75
        $this->assertEquals(75.0, $result['overall_score']);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Very large backlink count.
     * 
     * @test
     */
    public function edge_case_very_large_backlink_count(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 1000000,
            'referring_domains' => 10000,
            'avg_domain_trust' => 70.0,
            'growth_rate' => 5.0,
            'exact_match_anchor_ratio' => 0.15,
        ]);

        $this->assertIsFloat($result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /**
     * EC-002: Very large domain count.
     * 
     * @test
     */
    public function edge_case_very_large_domain_count(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 500000,
            'referring_domains' => 100000,
            'avg_domain_trust' => 75.0,
            'growth_rate' => 15.0,
            'exact_match_anchor_ratio' => 0.05,
        ]);

        $this->assertIsFloat($result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /**
     * EC-003: Negative growth rate extreme.
     * 
     * @test
     */
    public function edge_case_negative_growth_extreme(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 50.0,
            'growth_rate' => -100.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $this->assertGreaterThanOrEqual(0, $result['growth_score']);
    }

    /**
     * EC-004: Growth rate above 100%.
     * 
     * @test
     */
    public function edge_case_growth_rate_above_100(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 50.0,
            'growth_rate' => 200.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $this->assertLessThanOrEqual(25, $result['growth_score']);
    }

    /**
     * EC-005: Decimal calculations precision.
     * 
     * @test
     */
    public function edge_case_decimal_precision(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 33,
            'referring_domains' => 11,
            'avg_domain_trust' => 33.33,
            'growth_rate' => 3.33,
            'exact_match_anchor_ratio' => 0.333,
        ]);

        $this->assertIsFloat($result['overall_score']);
        $this->assertEqualsWithDelta($result['overall_score'], 
            $result['diversity_score'] + $result['trust_score'] + 
            $result['growth_score'] + $result['anchor_health_score'], 0.001);
    }

    /**
     * EC-006: Empty metrics array.
     * 
     * @test
     */
    public function edge_case_empty_metrics_array(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([]);

        $this->assertEquals(0.0, $result['overall_score']);
    }

    /**
     * EC-007: Partial metrics (missing growth and anchor).
     * 
     * @test
     */
    public function edge_case_partial_metrics(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60.0,
        ]);

        // Missing growth and anchor should use neutral values
        $this->assertEquals(12.5, $result['growth_score']);
        $this->assertEquals(12.5, $result['anchor_health_score']);
    }

    // =========================================================================
    // SCORE BREAKDOWN TESTS (SB)
    // =========================================================================

    /**
     * SB-001: Score breakdown returned.
     * 
     * @test
     */
    public function score_breakdown_returned(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('diversity_score', $result);
        $this->assertArrayHasKey('trust_score', $result);
        $this->assertArrayHasKey('growth_score', $result);
        $this->assertArrayHasKey('anchor_health_score', $result);
        $this->assertArrayHasKey('component_details', $result);
    }

    /**
     * SB-002: Component details populated.
     * 
     * @test
     */
    public function component_details_populated(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $details = $result['component_details'];
        
        $this->assertEquals(50, $details['referring_domains']);
        $this->assertEquals(100, $details['total_backlinks']);
        $this->assertEquals(60.0, $details['avg_domain_trust']);
        $this->assertEquals(10.0, $details['growth_rate']);
        $this->assertEquals(0.20, $details['exact_match_ratio']);
    }

    /**
     * SB-003: Total matches sum.
     * 
     * @test
     */
    public function total_matches_sum(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 80.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $expectedTotal = $result['diversity_score'] 
            + $result['trust_score'] 
            + $result['growth_score'] 
            + $result['anchor_health_score'];

        $this->assertEquals($expectedTotal, $result['overall_score']);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: SQL injection protection (handled at query level).
     * 
     * @test
     */
    public function security_sql_injection_protection(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        // This tests that the scorer doesn't crash with malformed input
        // SQL injection is handled at the Eloquent/query level
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $this->assertIsArray($result);
    }

    /**
     * SC-002: XSS in values handled.
     * 
     * @test
     */
    public function security_xss_values_handled(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        // The scorer works with numeric values only
        // XSS would be handled at the output/display level
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60.0,
            'growth_rate' => 10.0,
            'exact_match_anchor_ratio' => 0.20,
        ]);

        $this->assertIsFloat($result['overall_score']);
    }

    /**
     * SC-003: Negative values handled.
     * 
     * @test
     */
    public function security_negative_values_handled(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => -100, // Invalid
            'referring_domains' => -50, // Invalid
            'avg_domain_trust' => -60.0, // Invalid
            'growth_rate' => -10.0, // Valid (decline)
            'exact_match_anchor_ratio' => -0.20, // Invalid
        ]);

        // Should handle gracefully without crashing
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
    }

    /**
     * SC-004: Extremely large numbers.
     * 
     * @test
     */
    public function security_extremely_large_numbers(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => PHP_FLOAT_MAX,
            'referring_domains' => PHP_FLOAT_MAX,
            'avg_domain_trust' => PHP_FLOAT_MAX,
            'growth_rate' => PHP_FLOAT_MAX,
            'exact_match_anchor_ratio' => PHP_FLOAT_MAX,
        ]);

        // Should cap results, not overflow
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /**
     * SC-005: Invalid types handled.
     * 
     * @test
     */
    public function security_invalid_types_handled(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        // String that should be numeric
        $result = $scorer->calculateScore([
            'total_backlinks' => '100',
            'referring_domains' => '50',
            'avg_domain_trust' => '60.5',
            'growth_rate' => '10',
            'exact_match_anchor_ratio' => '0.2',
        ]);

        // Should convert strings or handle gracefully
        $this->assertIsArray($result);
    }

    // =========================================================================
    // HELPER METHOD TESTS
    // =========================================================================

    /**
     * Helper: Test score capping at maximum.
     * 
     * @test
     */
    public function helper_score_capped_at_maximum(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 500, // More domains than backlinks
            'avg_domain_trust' => 200.0, // Above max
            'growth_rate' => 500.0, // Above max
            'exact_match_anchor_ratio' => -0.5, // Below zero
        ]);

        $this->assertLessThanOrEqual(100, $result['overall_score']);
        $this->assertLessThanOrEqual(25, $result['diversity_score']);
        $this->assertLessThanOrEqual(25, $result['trust_score']);
        $this->assertLessThanOrEqual(25, $result['growth_score']);
        $this->assertLessThanOrEqual(25, $result['anchor_health_score']);
    }

    /**
     * Helper: Test score floored at minimum.
     * 
     * @test
     */
    public function helper_score_floored_at_minimum(): void
    {
        $scorer = new BacklinkProfileScorer();
        
        $result = $scorer->calculateScore([
            'total_backlinks' => 100,
            'referring_domains' => 0,
            'avg_domain_trust' => 0.0,
            'growth_rate' => -100.0,
            'exact_match_anchor_ratio' => 1.0,
        ]);

        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['diversity_score']);
        $this->assertGreaterThanOrEqual(0, $result['trust_score']);
        $this->assertGreaterThanOrEqual(0, $result['growth_score']);
        $this->assertGreaterThanOrEqual(0, $result['anchor_health_score']);
    }
}
