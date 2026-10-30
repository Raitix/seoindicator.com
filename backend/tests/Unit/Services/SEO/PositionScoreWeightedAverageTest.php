<?php

namespace Tests\Unit\Services\SEO;

use App\Services\SEO\PositionScoreCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for PositionScoreCalculator weighted average functionality.
 *
 * Tests the weighted average calculation that incorporates
 * search volume data to compute aggregate visibility scores.
 *
 * @see TASK-10-2
 * @see TEST-10-2
 * @see SPEC-010
 */
class PositionScoreWeightedAverageTest extends TestCase
{
    protected PositionScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PositionScoreCalculator();
    }

    // =========================================================================
    // BASIC WEIGHTED AVERAGE TESTS
    // =========================================================================

    /**
     * Single keyword returns its own score.
     * WA-001
     */
    #[Test]
    public function single_keyword_returns_its_score(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 100],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        $this->assertEquals(100.00, $average);
    }

    /**
     * Two keywords with equal volumes average correctly.
     * WA-002
     */
    #[Test]
    public function two_keywords_equal_volumes_average_correctly(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 100], // Score 100
            (object) ['current_position' => 10, 'search_volume' => 100], // Score ~77
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // (100 * 100 + 77 * 100) / 200 = 88.5
        $this->assertGreaterThan(88, $average);
        $this->assertLessThan(89, $average);
    }

    /**
     * Volume-weighted average favors high-volume keywords.
     * WA-003
     */
    #[Test]
    public function volume_weighted_average_favors_high_volume(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 1000], // Score 100, weight 1000
            (object) ['current_position' => 10, 'search_volume' => 100], // Score ~77, weight 100
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // (100 * 1000 + 77 * 100) / 1100 = 97.9
        $this->assertGreaterThan(97, $average);
        $this->assertLessThan(99, $average);
    }

    // =========================================================================
    // ZERO AND NULL VOLUME TESTS
    // =========================================================================

    /**
     * Zero volume keyword handled gracefully.
     * WA-004
     */
    #[Test]
    public function zero_volume_keyword_handled_gracefully(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 0],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Zero volume should either return 0 or be treated as weight 1
        $this->assertGreaterThanOrEqual(0, $average);
        $this->assertLessThanOrEqual(100, $average);
    }

    /**
     * Null volume defaults to weight of 1.
     * WA-005
     */
    #[Test]
    public function null_volume_defaults_to_weight_one(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => null],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Null volume should be treated as 1, so score is 100
        $this->assertEquals(100.00, $average);
    }

    // =========================================================================
    // EMPTY AND EDGE CASE TESTS
    // =========================================================================

    /**
     * Empty collection returns zero.
     * WA-006
     */
    #[Test]
    public function empty_collection_returns_zero(): void
    {
        $keywords = collect([]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        $this->assertEquals(0.00, $average);
    }

    /**
     * All positions outside top 100 return zero.
     * WA-007
     */
    #[Test]
    public function all_positions_outside_top_100_return_zero(): void
    {
        $keywords = collect([
            (object) ['current_position' => 101, 'search_volume' => 100],
            (object) ['current_position' => 150, 'search_volume' => 100],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        $this->assertEquals(0.00, $average);
    }

    /**
     * Mixed valid and invalid positions only count valid.
     * WA-008
     */
    #[Test]
    public function mixed_valid_invalid_positions(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 100], // Score 100
            (object) ['current_position' => 101, 'search_volume' => 100], // Score 0 (excluded or 0)
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Only position 1 contributes: (100 * 100) / 100 = 100
        // Or if both are counted: (100 * 100 + 0 * 100) / 200 = 50
        // The implementation should handle this consistently
        $this->assertGreaterThanOrEqual(50, $average);
        $this->assertLessThanOrEqual(100, $average);
    }

    // =========================================================================
    // MULTI-KEYWORD TESTS
    // =========================================================================

    /**
     * Three keywords average correctly.
     * WA-009
     */
    #[Test]
    public function three_keywords_average_correctly(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 100],  // Score 100
            (object) ['current_position' => 5, 'search_volume' => 200],  // Score 93
            (object) ['current_position' => 10, 'search_volume' => 100], // Score 77
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // (100 * 100 + 93 * 200 + 77 * 100) / 400 = 90.75
        $this->assertGreaterThan(89, $average);
        $this->assertLessThan(92, $average);
    }

    /**
     * Very high volume dominates average.
     * WA-010
     */
    #[Test]
    public function very_high_volume_dominates_average(): void
    {
        $keywords = collect([
            (object) ['current_position' => 5, 'search_volume' => 1000000], // Score 93
            (object) ['current_position' => 50, 'search_volume' => 1],     // Score 20
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // (93 * 1000000 + 20 * 1) / 1000001 ≈ 93
        $this->assertGreaterThan(92, $average);
        $this->assertLessThan(94, $average);
    }

    /**
     * Same positions twice average correctly.
     * WA-011
     */
    #[Test]
    public function same_positions_twice_average_correctly(): void
    {
        $keywords = collect([
            (object) ['current_position' => 5, 'search_volume' => 100], // Score 93
            (object) ['current_position' => 5, 'search_volume' => 100], // Score 93
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // (93 * 100 + 93 * 100) / 200 = 93
        $this->assertGreaterThan(92, $average);
        $this->assertLessThan(94, $average);
    }

    /**
     * Float precision is correct to 2 decimal places.
     * WA-012
     */
    #[Test]
    public function float_precision_is_correct(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 3],
            (object) ['current_position' => 2, 'search_volume' => 7],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Should be exactly 100.00 since both are position 1-3
        $this->assertEquals(100.00, $average);

        // Verify it's a float with proper precision
        $this->assertIsFloat($average);
    }

    // =========================================================================
    // DATA PROVIDER TESTS
    // =========================================================================

    /**
     * Test various volume combinations.
     * DP-003
     */
    #[Test]
    #[DataProvider('volumeCombinationProvider')]
    public function volume_combinations_calculate_correctly(
        array $keywordsData,
        float $minExpected,
        float $maxExpected
    ): void {
        $keywords = collect(array_map(function ($item) {
            return (object) $item;
        }, $keywordsData));

        $average = $this->calculator->calculateWeightedAverage($keywords);

        $this->assertGreaterThanOrEqual($minExpected, $average);
        $this->assertLessThanOrEqual($maxExpected, $average);
    }

    /**
     * Data provider for volume combination tests.
     */
    public static function volumeCombinationProvider(): array
    {
        return [
            'All top 3 positions' => [
                [
                    ['current_position' => 1, 'search_volume' => 100],
                    ['current_position' => 2, 'search_volume' => 100],
                    ['current_position' => 3, 'search_volume' => 100],
                ],
                99.0, 100.0, // All should be 100
            ],
            'Mixed positions with volumes' => [
                [
                    ['current_position' => 1, 'search_volume' => 500],
                    ['current_position' => 10, 'search_volume' => 200],
                    ['current_position' => 20, 'search_volume' => 100],
                ],
                80.0, 95.0, // High volume position 1 should dominate
            ],
            'Low positions with high volumes' => [
                [
                    ['current_position' => 40, 'search_volume' => 1000],
                    ['current_position' => 50, 'search_volume' => 1000],
                ],
                20.0, 35.0, // Both low positions
            ],
            'Single keyword position 1' => [
                [
                    ['current_position' => 1, 'search_volume' => 1000],
                ],
                99.0, 100.0,
            ],
            'Null volumes default to 1' => [
                [
                    ['current_position' => 1, 'search_volume' => null],
                    ['current_position' => 10, 'search_volume' => null],
                ],
                88.0, 89.0,
            ],
        ];
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Very large volume doesn't cause overflow.
     * EC-004
     */
    #[Test]
    public function very_large_volume_no_overflow(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => PHP_INT_MAX],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Should not throw overflow error
        $this->assertIsFloat($average);
        $this->assertEquals(100.00, $average);
    }

    /**
     * Negative volume handled gracefully.
     * EC-005
     */
    #[Test]
    public function negative_volume_handled_gracefully(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => -100],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Should handle negative volume without error
        $this->assertGreaterThanOrEqual(0, $average);
        $this->assertLessThanOrEqual(100, $average);
    }

    /**
     * Collection with array access works.
     * EC-006
     */
    #[Test]
    public function collection_with_arrays_works(): void
    {
        // Test with array-style access instead of objects
        $keywords = collect([
            ['current_position' => 1, 'search_volume' => 100],
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Implementation should handle both object and array access
        $this->assertGreaterThanOrEqual(0, $average);
        $this->assertLessThanOrEqual(100, $average);
    }

    /**
     * Missing search_volume property defaults to 1.
     * EC-007
     */
    #[Test]
    public function missing_search_volume_defaults_to_one(): void
    {
        $keywords = collect([
            (object) ['current_position' => 1], // No search_volume property
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Should default to volume 1
        $this->assertEquals(100.00, $average);
    }

    /**
     * Missing current_position returns 0 contribution.
     * EC-008
     */
    #[Test]
    public function missing_current_position_handled(): void
    {
        $keywords = collect([
            (object) ['search_volume' => 100], // No current_position
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Missing position should result in 0 or be handled gracefully
        $this->assertGreaterThanOrEqual(0, $average);
        $this->assertLessThanOrEqual(100, $average);
    }

    // =========================================================================
    // INTEGRATION-STYLE TESTS
    // =========================================================================

    /**
     * Full workflow: calculate individual scores then weighted average.
     */
    #[Test]
    public function full_workflow_integration(): void
    {
        // Create a realistic keyword collection
        $keywords = collect([
            (object) ['current_position' => 1, 'search_volume' => 5000],  // Branded, high volume
            (object) ['current_position' => 5, 'search_volume' => 2000],  // Target keyword
            (object) ['current_position' => 12, 'search_volume' => 3000], // Growing keyword
            (object) ['current_position' => 25, 'search_volume' => 1500], // Long-tail
            (object) ['current_position' => 45, 'search_volume' => 800],  // Niche
        ]);

        $average = $this->calculator->calculateWeightedAverage($keywords);

        // Verify result is reasonable
        $this->assertGreaterThan(50, $average, 'Average should be above 50 for mixed positions');
        $this->assertLessThan(100, $average, 'Average should be below 100');

        // Verify individual scores contribute correctly
        $score1 = $this->calculator->calculate(1);
        $score5 = $this->calculator->calculate(5);
        $score12 = $this->calculator->calculate(12);

        // Position 1 should contribute more than position 5
        $this->assertEquals(100, $score1);
        $this->assertLessThan($score1, $score5);
        $this->assertLessThan($score5, $score12);
    }

    /**
     * Invokable class test.
     */
    #[Test]
    public function calculator_is_invokable_for_position(): void
    {
        $calculator = $this->calculator;
        $score = $calculator(5);

        $this->assertIsInt($score);
        $this->assertGreaterThan(90, $score);
    }
}
