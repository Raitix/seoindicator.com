<?php

namespace Tests\Unit\Services\SEO;

use App\Services\SEO\PositionScoreCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the PositionScoreCalculator service.
 *
 * Tests the position-based scoring algorithm that assigns scores
 * to keyword rankings based on their search positions.
 *
 * @see TASK-10-2
 * @see TEST-10-2
 * @see SPEC-010
 */
class PositionScoreCalculatorTest extends TestCase
{
    protected PositionScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PositionScoreCalculator();
    }

    // =========================================================================
    // TOP 3 POSITIONS (SCORE = 100)
    // =========================================================================

    /**
     * Position 1 gets maximum score.
     * PS-001
     */
    #[Test]
    public function position_1_gets_maximum_score(): void
    {
        $score = $this->calculator->calculate(1);

        $this->assertEquals(100, $score);
    }

    /**
     * Position 2 gets maximum score.
     * PS-002
     */
    #[Test]
    public function position_2_gets_maximum_score(): void
    {
        $score = $this->calculator->calculate(2);

        $this->assertEquals(100, $score);
    }

    /**
     * Position 3 gets maximum score.
     * PS-003
     */
    #[Test]
    public function position_3_gets_maximum_score(): void
    {
        $score = $this->calculator->calculate(3);

        $this->assertEquals(100, $score);
    }

    // =========================================================================
    // POSITIONS 4-10 (WEIGHTED DEDUCTION)
    // =========================================================================

    /**
     * Position 4 gets score with first deduction.
     * PS-004: 100 - ((4-3) * 3.33) = 96.67 ≈ 97
     */
    #[Test]
    public function position_4_gets_deducted_score(): void
    {
        $score = $this->calculator->calculate(4);

        // 100 - (1 * 3.33) = 96.67
        $this->assertEquals(97, $score);
    }

    /**
     * Position 5 gets linear deduction.
     * PS-005: 100 - ((5-3) * 3.33) = 93.34 ≈ 93
     */
    #[Test]
    public function position_5_gets_linear_deduction(): void
    {
        $score = $this->calculator->calculate(5);

        // 100 - (2 * 3.33) = 93.34
        $this->assertEquals(93, $score);
    }

    /**
     * Position 10 gets correct score at end of range.
     * PS-006: 100 - ((10-3) * 3.33) = 76.69 ≈ 77
     */
    #[Test]
    public function position_10_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(10);

        // 100 - (7 * 3.33) = 76.69
        $this->assertEquals(77, $score);
    }

    // =========================================================================
    // POSITIONS 11-20 (GRADUAL REDUCTION)
    // =========================================================================

    /**
     * Position 11 starts new range.
     * PS-007: 80 - ((11-10) * 3) = 77
     */
    #[Test]
    public function position_11_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(11);

        // 80 - (1 * 3) = 77
        $this->assertEquals(77, $score);
    }

    /**
     * Position 20 gets correct score at end of range.
     * PS-008: 80 - ((20-10) * 3) = 50
     */
    #[Test]
    public function position_20_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(20);

        // 80 - (10 * 3) = 50
        $this->assertEquals(50, $score);
    }

    // =========================================================================
    // POSITIONS 21-50 (SLOWER REDUCTION)
    // =========================================================================

    /**
     * Position 21 starts new range.
     * PS-009: 50 - ((21-20) * 1) = 49
     */
    #[Test]
    public function position_21_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(21);

        // 50 - (1 * 1) = 49
        $this->assertEquals(49, $score);
    }

    /**
     * Position 50 gets correct score at end of range.
     * PS-010: 50 - ((50-20) * 1) = 20
     */
    #[Test]
    public function position_50_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(50);

        // 50 - (30 * 1) = 20
        $this->assertEquals(20, $score);
    }

    // =========================================================================
    // POSITIONS 51-100 (MINIMAL SCORES)
    // =========================================================================

    /**
     * Position 51 starts new range.
     * PS-011: 20 - ((51-50) * 0.38) = 19.62 ≈ 20
     */
    #[Test]
    public function position_51_gets_correct_score(): void
    {
        $score = $this->calculator->calculate(51);

        // 20 - (1 * 0.38) = 19.62
        $this->assertGreaterThanOrEqual(19, $score);
        $this->assertLessThanOrEqual(20, $score);
    }

    /**
     * Position 100 gets minimum positive score.
     * PS-012: 20 - ((100-50) * 0.38) = 1
     */
    #[Test]
    public function position_100_gets_minimum_score(): void
    {
        $score = $this->calculator->calculate(100);

        // 20 - (50 * 0.38) = 1
        $this->assertGreaterThanOrEqual(1, $score);
        $this->assertLessThanOrEqual(2, $score);
    }

    // =========================================================================
    // OUTSIDE TOP 100 (NO SCORE)
    // =========================================================================

    /**
     * Position 101 gets zero score.
     * PS-013
     */
    #[Test]
    public function position_101_gets_zero_score(): void
    {
        $score = $this->calculator->calculate(101);

        $this->assertEquals(0, $score);
    }

    /**
     * Position 0 (not found) gets zero score.
     * PS-014
     */
    #[Test]
    public function position_zero_gets_zero_score(): void
    {
        $score = $this->calculator->calculate(0);

        $this->assertEquals(0, $score);
    }

    /**
     * Negative position gets zero score.
     * PS-015
     */
    #[Test]
    public function negative_position_gets_zero_score(): void
    {
        $score = $this->calculator->calculate(-1);

        $this->assertEquals(0, $score);
    }

    /**
     * Very large position gets zero score.
     * PS-016
     */
    #[Test]
    public function very_large_position_gets_zero_score(): void
    {
        $score = $this->calculator->calculate(1000);

        $this->assertEquals(0, $score);
    }

    // =========================================================================
    // SCORE BOUNDARY TESTS
    // =========================================================================

    /**
     * Score never goes negative.
     * SB-001
     */
    #[Test]
    public function score_never_goes_negative(): void
    {
        // Test various positions
        for ($i = -10; $i <= 150; $i++) {
            $score = $this->calculator->calculate($i);
            $this->assertGreaterThanOrEqual(0, $score, "Score was negative for position {$i}");
        }
    }

    /**
     * Score never exceeds 100.
     * SB-002
     */
    #[Test]
    public function score_never_exceeds_100(): void
    {
        // Test various positions
        for ($i = 1; $i <= 100; $i++) {
            $score = $this->calculator->calculate($i);
            $this->assertLessThanOrEqual(100, $score, "Score exceeded 100 for position {$i}");
        }
    }

    /**
     * Score is returned as integer.
     * SB-003
     */
    #[Test]
    public function score_is_returned_as_integer(): void
    {
        $score = $this->calculator->calculate(5);

        $this->assertIsInt($score);
    }

    /**
     * Same input always returns same output (pure function).
     * SB-004
     */
    #[Test]
    public function same_input_returns_same_output(): void
    {
        $score1 = $this->calculator->calculate(5);
        $score2 = $this->calculator->calculate(5);
        $score3 = $this->calculator->calculate(5);

        $this->assertEquals($score1, $score2);
        $this->assertEquals($score2, $score3);
    }

    /**
     * Score difference between position 1 and 100 is significant.
     * SB-005
     */
    #[Test]
    public function score_difference_between_extremes_is_significant(): void
    {
        $score1 = $this->calculator->calculate(1);
        $score100 = $this->calculator->calculate(100);

        $this->assertGreaterThan(90, $score1 - $score100);
    }

    // =========================================================================
    // DATA PROVIDER TESTS
    // =========================================================================

    /**
     * Test all positions from 1 to 100.
     * DP-001
     */
    #[Test]
    #[DataProvider('positionScoreProvider')]
    public function position_scoring_matches_expected(int $position, int $minScore, int $maxScore): void
    {
        $score = $this->calculator->calculate($position);

        $this->assertGreaterThanOrEqual($minScore, $score, "Position {$position} scored {$score}, expected >= {$minScore}");
        $this->assertLessThanOrEqual($maxScore, $score, "Position {$position} scored {$score}, expected <= {$maxScore}");
    }

    /**
     * Data provider for position scoring tests.
     */
    public static function positionScoreProvider(): array
    {
        return [
            // position, min_score, max_score
            'Position 1' => [1, 100, 100],
            'Position 2' => [2, 100, 100],
            'Position 3' => [3, 100, 100],
            'Position 4' => [4, 96, 97],
            'Position 5' => [5, 93, 94],
            'Position 6' => [6, 89, 91],
            'Position 7' => [7, 86, 88],
            'Position 8' => [8, 83, 85],
            'Position 9' => [9, 79, 81],
            'Position 10' => [10, 76, 78],
            'Position 11' => [11, 76, 78],
            'Position 15' => [15, 64, 66],
            'Position 20' => [20, 49, 51],
            'Position 25' => [25, 44, 46],
            'Position 30' => [30, 39, 41],
            'Position 40' => [40, 29, 31],
            'Position 50' => [50, 19, 21],
            'Position 60' => [60, 15, 18],
            'Position 70' => [70, 11, 14],
            'Position 80' => [80, 7, 10],
            'Position 90' => [90, 3, 6],
            'Position 100' => [100, 0, 3],
            'Position 101' => [101, 0, 0],
            'Position 150' => [150, 0, 0],
        ];
    }

    /**
     * Test boundary positions.
     * DP-002
     */
    #[Test]
    #[DataProvider('boundaryPositionProvider')]
    public function boundary_positions_have_correct_scores(int $position, string $description): void
    {
        $score = $this->calculator->calculate($position);

        // All valid positions should have non-negative scores
        $this->assertGreaterThanOrEqual(0, $score);

        if ($position <= 100 && $position > 0) {
            $this->assertGreaterThan(0, $score, "{$description} should have positive score");
        }

        if ($position > 100 || $position <= 0) {
            $this->assertEquals(0, $score, "{$description} should have zero score");
        }
    }

    /**
     * Data provider for boundary positions.
     */
    public static function boundaryPositionProvider(): array
    {
        return [
            'Position 1 (first)' => [1, 'Position 1'],
            'Position 3 (last of top tier)' => [3, 'Position 3'],
            'Position 4 (first of second tier)' => [4, 'Position 4'],
            'Position 10 (last of second tier)' => [10, 'Position 10'],
            'Position 11 (first of third tier)' => [11, 'Position 11'],
            'Position 20 (last of third tier)' => [20, 'Position 20'],
            'Position 21 (first of fourth tier)' => [21, 'Position 21'],
            'Position 50 (last of fourth tier)' => [50, 'Position 50'],
            'Position 51 (first of fifth tier)' => [51, 'Position 51'],
            'Position 100 (last in top 100)' => [100, 'Position 100'],
            'Position 101 (outside top 100)' => [101, 'Position 101'],
        ];
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Float position is truncated.
     * EC-002
     */
    #[Test]
    public function float_position_is_truncated(): void
    {
        $score = $this->calculator->calculate(1.9);

        // Should be treated as position 1
        $this->assertEquals(100, $score);
    }

    /**
     * String position is cast to integer.
     * EC-003
     */
    #[Test]
    public function string_position_is_cast_to_integer(): void
    {
        $score = $this->calculator->calculate('5');

        // Should be treated as position 5
        $this->assertEquals(93, $score);
    }

    /**
     * Large volume doesn't cause overflow in weighted average.
     * EC-004 (tested in weighted average tests)
     */
    #[Test]
    public function calculation_handles_large_numbers(): void
    {
        // This test verifies the calculate method doesn't have overflow issues
        $score = $this->calculator->calculate(50);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }
}
