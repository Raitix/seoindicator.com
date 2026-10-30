<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IndicatorResult DTO
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class IndicatorResultTest extends TestCase
{
    /**
     * Test create with all fields.
     * 
     * @test
     */
    public function create_with_all_fields(): void
    {
        $result = new IndicatorResult(
            id: 'url',
            name: 'URL Structure',
            category: 'technical',
            score: 3,
            weight: 0.08,
            passed: true,
            details: ['urlLength' => 35]
        );

        $this->assertEquals('url', $result->id);
        $this->assertEquals('URL Structure', $result->name);
        $this->assertEquals('technical', $result->category);
        $this->assertEquals(3, $result->score);
        $this->assertEquals(0.08, $result->weight);
        $this->assertTrue($result->passed);
        $this->assertEquals(['urlLength' => 35], $result->details);
    }

    /**
     * Test DTO is immutable.
     * 
     * @test
     */
    public function is_immutable(): void
    {
        $result = new IndicatorResult(
            id: 'url',
            name: 'URL Structure',
            category: 'technical',
            score: 3,
            weight: 0.08,
            passed: true,
            details: []
        );

        // Try to modify a property (should fail with readonly)
        $this->expectException(\Error::class);
        $result->score = 1;
    }

    /**
     * Test getScore() returns int 1-3.
     * 
     * @test
     */
    public function get_score_returns_int(): void
    {
        $result = $this->createDefaultResult();

        $this->assertIsInt($result->score);
        $this->assertGreaterThanOrEqual(0, $result->score);
        $this->assertLessThanOrEqual(3, $result->score);
    }

    /**
     * Test getWeight() returns float.
     * 
     * @test
     */
    public function get_weight_returns_float(): void
    {
        $result = $this->createDefaultResult();

        $this->assertIsFloat($result->weight);
        $this->assertGreaterThanOrEqual(0.0, $result->weight);
        $this->assertLessThanOrEqual(1.0, $result->weight);
    }

    /**
     * Test toArray() serializes correctly.
     * 
     * @test
     */
    public function to_array_serializes_correctly(): void
    {
        $result = new IndicatorResult(
            id: 'url',
            name: 'URL Structure',
            category: 'technical',
            score: 3,
            weight: 0.08,
            passed: true,
            details: ['urlLength' => 35]
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('score', $array);
        $this->assertArrayHasKey('weight', $array);
        $this->assertArrayHasKey('passed', $array);
        $this->assertArrayHasKey('details', $array);
    }

    /**
     * Test toJson() serializes correctly.
     * 
     * @test
     */
    public function to_json_serializes_correctly(): void
    {
        $result = $this->createDefaultResult();

        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($result->id, $decoded['id']);
    }

    /**
     * Test fromArray() creates DTO.
     * 
     * @test
     */
    public function from_array_creates_dto(): void
    {
        $data = [
            'id' => 'url',
            'name' => 'URL Structure',
            'category' => 'technical',
            'score' => 3,
            'weight' => 0.08,
            'passed' => true,
            'details' => [],
        ];

        $result = IndicatorResult::fromArray($data);

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals('url', $result->id);
    }

    /**
     * Test score 3 = Excellent.
     * 
     * @test
     */
    public function score_3_is_excellent(): void
    {
        $result = $this->createDefaultResult(score: 3);

        $this->assertTrue($result->isExcellent());
        $this->assertFalse($result->isGood());
        $this->assertFalse($result->isPoor());
    }

    /**
     * Test score 2 = Good.
     * 
     * @test
     */
    public function score_2_is_good(): void
    {
        $result = $this->createDefaultResult(score: 2);

        $this->assertTrue($result->isGood());
        $this->assertFalse($result->isExcellent());
        $this->assertFalse($result->isPoor());
    }

    /**
     * Test score 1 = Poor.
     * 
     * @test
     */
    public function score_1_is_poor(): void
    {
        $result = $this->createDefaultResult(score: 1);

        $this->assertTrue($result->isPoor());
        $this->assertFalse($result->isExcellent());
        $this->assertFalse($result->isGood());
    }

    /**
     * Test score 0 = N/A.
     * 
     * @test
     */
    public function score_0_is_not_applicable(): void
    {
        $result = $this->createDefaultResult(score: 0);

        $this->assertTrue($result->isNotApplicable());
    }

    /**
     * Test invalid score throws exception.
     * 
     * @test
     */
    public function invalid_score_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: 4,
            weight: 0.05,
            passed: true,
            details: []
        );
    }

    /**
     * Test negative score throws exception.
     * 
     * @test
     */
    public function negative_score_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: -1,
            weight: 0.05,
            passed: true,
            details: []
        );
    }

    /**
     * Test empty details allowed.
     * 
     * @test
     */
    public function empty_details_allowed(): void
    {
        $result = new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: 3,
            weight: 0.05,
            passed: true,
            details: []
        );

        $this->assertIsArray($result->details);
        $this->assertEmpty($result->details);
    }

    /**
     * Test large details array.
     * 
     * @test
     */
    public function large_details_array(): void
    {
        $details = [];
        for ($i = 0; $i < 100; $i++) {
            $details["item_$i"] = "value_$i";
        }

        $result = new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: 3,
            weight: 0.05,
            passed: true,
            details: $details
        );

        $this->assertCount(100, $result->details);
    }

    /**
     * Test weight outside range throws exception.
     * 
     * @test
     */
    public function weight_outside_range_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: 3,
            weight: 1.5,
            passed: true,
            details: []
        );
    }

    /**
     * Test getScoreLabel() returns human-readable label.
     * 
     * @test
     */
    public function get_score_label(): void
    {
        $excellent = $this->createDefaultResult(score: 3);
        $good = $this->createDefaultResult(score: 2);
        $poor = $this->createDefaultResult(score: 1);
        $na = $this->createDefaultResult(score: 0);

        $this->assertEquals('Excellent', $excellent->getScoreLabel());
        $this->assertEquals('Good', $good->getScoreLabel());
        $this->assertEquals('Poor', $poor->getScoreLabel());
        $this->assertEquals('N/A', $na->getScoreLabel());
    }

    /**
     * Helper: Create default result.
     */
    private function createDefaultResult(int $score = 3): IndicatorResult
    {
        return new IndicatorResult(
            id: 'test',
            name: 'Test Indicator',
            category: 'technical',
            score: $score,
            weight: 0.05,
            passed: true,
            details: []
        );
    }
}
