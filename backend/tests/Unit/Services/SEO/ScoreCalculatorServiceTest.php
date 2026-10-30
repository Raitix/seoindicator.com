<?php

namespace Tests\Unit\Services\SEO;

use PHPUnit\Framework\TestCase;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\ScoreCalculatorService;
use App\Services\SEO\ScoreResult;

/**
 * Tests for ScoreCalculatorService
 * 
 * @see TASK-18-7
 * @see TEST-18-7
 */
class ScoreCalculatorServiceTest extends TestCase
{
    // =========================================================================
    // SCORE CALCULATION TESTS
    // =========================================================================

    /**
     * SC-01: Calculate overall score with all indicators.
     * 
     * @test
     */
    public function calculate_overall_score_with_all_indicators(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('meta_description', 'content', 2, 0.08),
            $this->createIndicator('h1_headings', 'content', 3, 0.08),
            $this->createIndicator('url', 'technical', 3, 0.08),
            $this->createIndicator('canonical', 'technical', 2, 0.06),
        ];

        $result = $service->calculate($indicators);

        $this->assertInstanceOf(ScoreResult::class, $result);
        $this->assertIsInt($result->overallScore);
        $this->assertGreaterThanOrEqual(0, $result->overallScore);
        $this->assertLessThanOrEqual(100, $result->overallScore);
    }

    /**
     * SC-02: Calculate score returns 100 for all excellent indicators.
     * 
     * @test
     */
    public function calculate_score_100_for_all_excellent(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('meta_description', 'content', 3, 0.08),
            $this->createIndicator('h1_headings', 'content', 3, 0.08),
            $this->createIndicator('url', 'technical', 3, 0.08),
            $this->createIndicator('canonical', 'technical', 3, 0.06),
        ];

        $result = $service->calculate($indicators);

        // All 3-star scores should yield 100
        $this->assertEquals(100, $result->overallScore);
    }

    /**
     * SC-03: Calculate score returns 33 for all poor indicators.
     * 
     * @test
     */
    public function calculate_score_33_for_all_poor(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 1, 0.10),
            $this->createIndicator('meta_description', 'content', 1, 0.08),
            $this->createIndicator('h1_headings', 'content', 1, 0.08),
        ];

        $result = $service->calculate($indicators);

        // All 1-star scores should yield ~33 (33.33)
        $this->assertEquals(33, $result->overallScore);
    }

    /**
     * SC-04: Calculate score returns 67 for all good indicators.
     * 
     * @test
     */
    public function calculate_score_67_for_all_good(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 2, 0.10),
            $this->createIndicator('meta_description', 'content', 2, 0.08),
            $this->createIndicator('h1_headings', 'content', 2, 0.08),
        ];

        $result = $service->calculate($indicators);

        // All 2-star scores should yield ~67 (66.67)
        $this->assertEquals(67, $result->overallScore);
    }

    /**
     * SC-05: Weighted average calculation is correct.
     * 
     * @test
     */
    public function weighted_average_calculation_is_correct(): void
    {
        $service = new ScoreCalculatorService();
        
        // Mix of scores: 3, 2, 1 with different weights
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10), // 3 * 0.10 = 0.30
            $this->createIndicator('meta_description', 'content', 2, 0.08), // 2 * 0.08 = 0.16
            $this->createIndicator('h1_headings', 'content', 1, 0.08), // 1 * 0.08 = 0.08
        ];
        // Total weighted = 0.54, Total weight = 0.26
        // Average = 0.54 / 0.26 = 2.0769
        // Score = 2.0769 / 3 * 100 = 69.23 ≈ 69

        $result = $service->calculate($indicators);

        $this->assertEquals(69, $result->overallScore);
    }

    // =========================================================================
    // CATEGORY SCORE TESTS
    // =========================================================================

    /**
     * CS-01: Calculate category scores.
     * 
     * @test
     */
    public function calculate_category_scores(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('meta_description', 'content', 2, 0.08),
            $this->createIndicator('url', 'technical', 3, 0.08),
            $this->createIndicator('canonical', 'technical', 2, 0.06),
            $this->createIndicator('image_alt', 'accessibility', 3, 0.05),
            $this->createIndicator('page_speed', 'performance', 2, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertIsArray($result->categoryScores);
        $this->assertArrayHasKey('content', $result->categoryScores);
        $this->assertArrayHasKey('technical', $result->categoryScores);
        $this->assertArrayHasKey('accessibility', $result->categoryScores);
        $this->assertArrayHasKey('performance', $result->categoryScores);
    }

    /**
     * CS-02: Content category score calculation.
     * 
     * @test
     */
    public function content_category_score_calculation(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('meta_description', 'content', 3, 0.08),
        ];

        $result = $service->calculate($indicators);

        $this->assertEquals(100, $result->categoryScores['content']);
    }

    /**
     * CS-03: Technical category score calculation.
     * 
     * @test
     */
    public function technical_category_score_calculation(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('url', 'technical', 2, 0.08),
            $this->createIndicator('canonical', 'technical', 2, 0.06),
        ];

        $result = $service->calculate($indicators);

        $this->assertEquals(67, $result->categoryScores['technical']);
    }

    /**
     * CS-04: Missing category returns null.
     * 
     * @test
     */
    public function missing_category_returns_null(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertNull($result->categoryScores['technical'] ?? null);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * EC-01: Empty indicators array.
     * 
     * @test
     */
    public function empty_indicators_array(): void
    {
        $service = new ScoreCalculatorService();
        
        $result = $service->calculate([]);

        $this->assertEquals(0, $result->overallScore);
        $this->assertEmpty($result->categoryScores);
    }

    /**
     * EC-02: Single indicator.
     * 
     * @test
     */
    public function single_indicator(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertEquals(100, $result->overallScore);
        $this->assertEquals(100, $result->categoryScores['content']);
    }

    /**
     * EC-03: Exclude N/A (score 0) indicators from calculation.
     * 
     * @test
     */
    public function exclude_na_indicators_from_calculation(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('image_alt', 'accessibility', 0, 0.05), // N/A - no images
        ];

        $result = $service->calculate($indicators);

        // Only the title_tag should be counted
        $this->assertEquals(100, $result->overallScore);
        // Accessibility category should be excluded (all N/A)
        $this->assertNull($result->categoryScores['accessibility'] ?? null);
    }

    /**
     * EC-04: All indicators are N/A.
     * 
     * @test
     */
    public function all_indicators_are_na(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('image_alt', 'accessibility', 0, 0.05),
            $this->createIndicator('aria', 'accessibility', 0, 0.04),
        ];

        $result = $service->calculate($indicators);

        $this->assertEquals(0, $result->overallScore);
    }

    /**
     * EC-05: Handle failed indicators (passed = false).
     * 
     * @test
     */
    public function handle_failed_indicators(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10, true),
            $this->createIndicator('meta_description', 'content', 1, 0.08, false), // Failed
        ];

        $result = $service->calculate($indicators);

        // Both should be included in calculation regardless of passed status
        $this->assertLessThan(100, $result->overallScore);
    }

    /**
     * EC-06: Large number of indicators.
     * 
     * @test
     */
    public function large_number_of_indicators(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [];
        $categories = ['content', 'technical', 'accessibility', 'performance'];
        
        for ($i = 0; $i < 50; $i++) {
            $indicators[] = $this->createIndicator(
                "indicator_$i",
                $categories[$i % 4],
                rand(1, 3),
                0.02
            );
        }

        $result = $service->calculate($indicators);

        $this->assertGreaterThanOrEqual(0, $result->overallScore);
        $this->assertLessThanOrEqual(100, $result->overallScore);
    }

    // =========================================================================
    // SCORE BREAKDOWN TESTS
    // =========================================================================

    /**
     * SB-01: Score breakdown includes all indicators.
     * 
     * @test
     */
    public function score_breakdown_includes_all_indicators(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('meta_description', 'content', 2, 0.08),
        ];

        $result = $service->calculate($indicators);

        $this->assertIsArray($result->breakdown);
        $this->assertCount(2, $result->breakdown);
    }

    /**
     * SB-02: Breakdown contains weighted contribution.
     * 
     * @test
     */
    public function breakdown_contains_weighted_contribution(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertArrayHasKey('title_tag', $result->breakdown);
        $this->assertEquals(3, $result->breakdown['title_tag']['score']);
        $this->assertEquals(0.10, $result->breakdown['title_tag']['weight']);
    }

    // =========================================================================
    // SUMMARY TESTS
    // =========================================================================

    /**
     * SM-01: Summary counts passed/warning/failed.
     * 
     * @test
     */
    public function summary_counts_passed_warning_failed(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10, true),
            $this->createIndicator('meta_description', 'content', 2, 0.08, false), // Warning
            $this->createIndicator('h1_headings', 'content', 1, 0.08, false), // Failed
        ];

        $result = $service->calculate($indicators);

        $this->assertIsArray($result->summary);
        $this->assertEquals(1, $result->summary['passed']);
        $this->assertEquals(1, $result->summary['warnings']);
        $this->assertEquals(1, $result->summary['failed']);
    }

    /**
     * SM-02: Summary with all passed.
     * 
     * @test
     */
    public function summary_with_all_passed(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10, true),
            $this->createIndicator('meta_description', 'content', 3, 0.08, true),
        ];

        $result = $service->calculate($indicators);

        $this->assertEquals(2, $result->summary['passed']);
        $this->assertEquals(0, $result->summary['warnings']);
        $this->assertEquals(0, $result->summary['failed']);
    }

    /**
     * SM-03: N/A indicators excluded from summary.
     * 
     * @test
     */
    public function na_indicators_excluded_from_summary(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10, true),
            $this->createIndicator('image_alt', 'accessibility', 0, 0.05), // N/A
        ];

        $result = $service->calculate($indicators);

        // Only title_tag counts
        $this->assertEquals(1, $result->summary['passed']);
    }

    // =========================================================================
    // VALIDATION TESTS
    // =========================================================================

    /**
     * VL-01: Invalid indicator throws exception.
     * 
     * @test
     */
    public function invalid_indicator_throws_exception(): void
    {
        $service = new ScoreCalculatorService();
        
        $this->expectException(\InvalidArgumentException::class);

        // Pass invalid object
        $service->calculate([new \stdClass()]);
    }

    /**
     * VL-02: Weights are normalized when indicators excluded.
     * 
     * @test
     */
    public function weights_are_normalized_when_indicators_excluded(): void
    {
        $service = new ScoreCalculatorService();
        
        // Total weight = 0.18, but N/A indicator weight 0.05 excluded
        // Effective weight = 0.10
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
            $this->createIndicator('image_alt', 'accessibility', 0, 0.05), // N/A
        ];

        $result = $service->calculate($indicators);

        // Should be 100 since only the 3-score indicator is counted
        $this->assertEquals(100, $result->overallScore);
    }

    // =========================================================================
    // RESULT OBJECT TESTS
    // =========================================================================

    /**
     * RO-01: ScoreResult has all required properties.
     * 
     * @test
     */
    public function score_result_has_all_required_properties(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertObjectHasProperty('overallScore', $result);
        $this->assertObjectHasProperty('categoryScores', $result);
        $this->assertObjectHasProperty('breakdown', $result);
        $this->assertObjectHasProperty('summary', $result);
    }

    /**
     * RO-02: ScoreResult toArray method.
     * 
     * @test
     */
    public function score_result_to_array_method(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('overallScore', $array);
        $this->assertArrayHasKey('categoryScores', $array);
        $this->assertArrayHasKey('breakdown', $array);
        $this->assertArrayHasKey('summary', $array);
    }

    /**
     * RO-03: ScoreResult toJson method.
     * 
     * @test
     */
    public function score_result_to_json_method(): void
    {
        $service = new ScoreCalculatorService();
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.10),
        ];

        $result = $service->calculate($indicators);
        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(100, $decoded['overallScore']);
    }

    // =========================================================================
    // CONFIGURATION TESTS
    // =========================================================================

    /**
     * CF-01: Custom weights configuration.
     * 
     * @test
     */
    public function custom_weights_configuration(): void
    {
        $customWeights = [
            'title_tag' => 0.20, // Double the default
            'meta_description' => 0.10,
        ];

        $service = new ScoreCalculatorService($customWeights);
        
        $indicators = [
            $this->createIndicator('title_tag', 'content', 3, 0.20),
            $this->createIndicator('meta_description', 'content', 2, 0.10),
        ];

        $result = $service->calculate($indicators);

        $this->assertIsInt($result->overallScore);
    }

    /**
     * CF-02: Missing weight uses default.
     * 
     * @test
     */
    public function missing_weight_uses_default(): void
    {
        $service = new ScoreCalculatorService();
        
        // Indicator not in default weights
        $indicators = [
            $this->createIndicator('custom_indicator', 'content', 3, 0.05),
        ];

        $result = $service->calculate($indicators);

        // Should still work with the weight from the indicator
        $this->assertEquals(100, $result->overallScore);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create an IndicatorResult for testing.
     */
    private function createIndicator(
        string $id,
        string $category,
        int $score,
        float $weight,
        bool $passed = true
    ): IndicatorResult {
        return new IndicatorResult(
            id: $id,
            name: ucfirst(str_replace('_', ' ', $id)),
            category: $category,
            score: $score,
            weight: $weight,
            passed: $passed,
            details: []
        );
    }
}
