<?php

namespace Tests\Unit\Services\SEO;

use PHPUnit\Framework\TestCase;
use App\Services\SEO\ScoreResult;

/**
 * Tests for ScoreResult DTO
 * 
 * @see TASK-18-7
 * @see TEST-18-7
 */
class ScoreResultTest extends TestCase
{
    // =========================================================================
    // CREATION TESTS
    // =========================================================================

    /**
     * SR-01: Create with all fields.
     * 
     * @test
     */
    public function create_with_all_fields(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [
                'content' => 82,
                'technical' => 75,
                'accessibility' => 70,
                'performance' => 85,
            ],
            breakdown: [
                'title_tag' => ['score' => 3, 'weight' => 0.10],
            ],
            summary: [
                'passed' => 12,
                'warnings' => 3,
                'failed' => 2,
            ]
        );

        $this->assertEquals(78, $result->overallScore);
        $this->assertEquals(82, $result->categoryScores['content']);
        $this->assertEquals(12, $result->summary['passed']);
    }

    /**
     * SR-02: DTO is immutable.
     * 
     * @test
     */
    public function is_immutable(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->expectException(\Error::class);
        $result->overallScore = 100;
    }

    /**
     * SR-03: Overall score must be 0-100.
     * 
     * @test
     */
    public function overall_score_must_be_0_to_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ScoreResult(
            overallScore: 150,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );
    }

    /**
     * SR-04: Negative overall score throws exception.
     * 
     * @test
     */
    public function negative_overall_score_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ScoreResult(
            overallScore: -10,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    /**
     * SR-05: toArray serializes correctly.
     * 
     * @test
     */
    public function to_array_serializes_correctly(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: ['content' => 82],
            breakdown: ['title_tag' => ['score' => 3, 'weight' => 0.10]],
            summary: ['passed' => 1, 'warnings' => 0, 'failed' => 0]
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('overallScore', $array);
        $this->assertArrayHasKey('categoryScores', $array);
        $this->assertArrayHasKey('breakdown', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertEquals(78, $array['overallScore']);
    }

    /**
     * SR-06: toJson serializes correctly.
     * 
     * @test
     */
    public function to_json_serializes_correctly(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: ['content' => 82],
            breakdown: [],
            summary: ['passed' => 1, 'warnings' => 0, 'failed' => 0]
        );

        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(78, $decoded['overallScore']);
    }

    /**
     * SR-07: fromArray creates DTO.
     * 
     * @test
     */
    public function from_array_creates_dto(): void
    {
        $data = [
            'overallScore' => 78,
            'categoryScores' => ['content' => 82],
            'breakdown' => [],
            'summary' => ['passed' => 1, 'warnings' => 0, 'failed' => 0],
        ];

        $result = ScoreResult::fromArray($data);

        $this->assertInstanceOf(ScoreResult::class, $result);
        $this->assertEquals(78, $result->overallScore);
    }

    // =========================================================================
    // SCORE RANGE METHODS
    // =========================================================================

    /**
     * SR-08: isGoodScore returns true for 80+.
     * 
     * @test
     */
    public function is_good_score_returns_true_for_80_plus(): void
    {
        $result = new ScoreResult(
            overallScore: 85,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isGoodScore());
        $this->assertFalse($result->isModerateScore());
        $this->assertFalse($result->isPoorScore());
    }

    /**
     * SR-09: isModerateScore returns true for 60-79.
     * 
     * @test
     */
    public function is_moderate_score_returns_true_for_60_to_79(): void
    {
        $result = new ScoreResult(
            overallScore: 70,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isModerateScore());
        $this->assertFalse($result->isGoodScore());
        $this->assertFalse($result->isPoorScore());
    }

    /**
     * SR-10: isPoorScore returns true for 0-59.
     * 
     * @test
     */
    public function is_poor_score_returns_true_for_0_to_59(): void
    {
        $result = new ScoreResult(
            overallScore: 45,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isPoorScore());
        $this->assertFalse($result->isGoodScore());
        $this->assertFalse($result->isModerateScore());
    }

    /**
     * SR-11: getScoreLabel returns correct label.
     * 
     * @test
     */
    public function get_score_label_returns_correct_label(): void
    {
        $good = new ScoreResult(85, [], [], ['passed' => 0, 'warnings' => 0, 'failed' => 0]);
        $moderate = new ScoreResult(70, [], [], ['passed' => 0, 'warnings' => 0, 'failed' => 0]);
        $poor = new ScoreResult(45, [], [], ['passed' => 0, 'warnings' => 0, 'failed' => 0]);

        $this->assertEquals('Good', $good->getScoreLabel());
        $this->assertEquals('Moderate', $moderate->getScoreLabel());
        $this->assertEquals('Poor', $poor->getScoreLabel());
    }

    // =========================================================================
    // CATEGORY HELPERS
    // =========================================================================

    /**
     * SR-12: getCategoryScore returns category score.
     * 
     * @test
     */
    public function get_category_score_returns_category_score(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [
                'content' => 82,
                'technical' => 75,
            ],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertEquals(82, $result->getCategoryScore('content'));
        $this->assertEquals(75, $result->getCategoryScore('technical'));
    }

    /**
     * SR-13: getCategoryScore returns null for missing category.
     * 
     * @test
     */
    public function get_category_score_returns_null_for_missing(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: ['content' => 82],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertNull($result->getCategoryScore('nonexistent'));
    }

    /**
     * SR-14: getWorstCategory returns lowest scoring category.
     * 
     * @test
     */
    public function get_worst_category_returns_lowest(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [
                'content' => 82,
                'technical' => 45,
                'accessibility' => 70,
            ],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertEquals('technical', $result->getWorstCategory());
    }

    /**
     * SR-15: getBestCategory returns highest scoring category.
     * 
     * @test
     */
    public function get_best_category_returns_highest(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [
                'content' => 95,
                'technical' => 45,
                'accessibility' => 70,
            ],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertEquals('content', $result->getBestCategory());
    }

    /**
     * SR-16: getWorstCategory returns null with no categories.
     * 
     * @test
     */
    public function get_worst_category_returns_null_with_no_categories(): void
    {
        $result = new ScoreResult(
            overallScore: 0,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertNull($result->getWorstCategory());
    }

    // =========================================================================
    // SUMMARY HELPERS
    // =========================================================================

    /**
     * SR-17: getTotalIndicators returns sum of summary.
     * 
     * @test
     */
    public function get_total_indicators_returns_sum(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 12, 'warnings' => 3, 'failed' => 2]
        );

        $this->assertEquals(17, $result->getTotalIndicators());
    }

    /**
     * SR-18: getPassRate returns percentage.
     * 
     * @test
     */
    public function get_pass_rate_returns_percentage(): void
    {
        $result = new ScoreResult(
            overallScore: 78,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 12, 'warnings' => 3, 'failed' => 2]
        );

        // 12 passed out of 17 total = 70.59%
        $this->assertEqualsWithDelta(70.59, $result->getPassRate(), 0.01);
    }

    /**
     * SR-19: getPassRate returns 0 with no indicators.
     * 
     * @test
     */
    public function get_pass_rate_returns_0_with_no_indicators(): void
    {
        $result = new ScoreResult(
            overallScore: 0,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertEquals(0, $result->getPassRate());
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    /**
     * SR-20: Zero score is valid.
     * 
     * @test
     */
    public function zero_score_is_valid(): void
    {
        $result = new ScoreResult(
            overallScore: 0,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 5]
        );

        $this->assertEquals(0, $result->overallScore);
        $this->assertTrue($result->isPoorScore());
    }

    /**
     * SR-21: 100 score is valid.
     * 
     * @test
     */
    public function hundred_score_is_valid(): void
    {
        $result = new ScoreResult(
            overallScore: 100,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 17, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertEquals(100, $result->overallScore);
        $this->assertTrue($result->isGoodScore());
    }

    /**
     * SR-22: Boundary score 80 is good.
     * 
     * @test
     */
    public function boundary_score_80_is_good(): void
    {
        $result = new ScoreResult(
            overallScore: 80,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isGoodScore());
    }

    /**
     * SR-23: Boundary score 59 is poor.
     * 
     * @test
     */
    public function boundary_score_59_is_poor(): void
    {
        $result = new ScoreResult(
            overallScore: 59,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isPoorScore());
    }

    /**
     * SR-24: Boundary score 60 is moderate.
     * 
     * @test
     */
    public function boundary_score_60_is_moderate(): void
    {
        $result = new ScoreResult(
            overallScore: 60,
            categoryScores: [],
            breakdown: [],
            summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
        );

        $this->assertTrue($result->isModerateScore());
    }
}
