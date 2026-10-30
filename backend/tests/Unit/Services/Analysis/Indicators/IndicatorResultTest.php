<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\IndicatorResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IndicatorResult DTO
 * 
 * @see TASK-19-5
 * @see TEST-19-5
 */
class IndicatorResultTest extends TestCase
{
    // ==================== STRUCTURE TESTS ====================

    /**
     * Test create with all fields.
     */
    public function test_create_with_all_fields(): void
    {
        $result = new IndicatorResult(
            slug: 'title-tag',
            name: 'Title Tag Analysis',
            score: 85.5,
            maxScore: 100.0,
            percentage: 86,
            data: ['title' => 'Test Page', 'length' => 9],
            recommendations: [['priority' => 'high', 'message' => 'Add keywords']],
            processingTimeMs: 150,
            error: null,
        );

        $this->assertEquals('title-tag', $result->slug);
        $this->assertEquals('Title Tag Analysis', $result->name);
        $this->assertEquals(85.5, $result->score);
        $this->assertEquals(100.0, $result->maxScore);
        $this->assertEquals(86, $result->percentage);
        $this->assertEquals(['title' => 'Test Page', 'length' => 9], $result->data);
        $this->assertCount(1, $result->recommendations);
        $this->assertEquals(150, $result->processingTimeMs);
        $this->assertNull($result->error);
    }

    /**
     * Test readonly properties - immutable DTO.
     */
    public function test_is_immutable(): void
    {
        $result = $this->createDefaultResult();

        $this->expectException(\Error::class);
        $result->score = 50.0;
    }

    /**
     * Test percentage is integer 0-100.
     */
    public function test_percentage_is_integer(): void
    {
        $result = $this->createDefaultResult();

        $this->assertIsInt($result->percentage);
        $this->assertGreaterThanOrEqual(0, $result->percentage);
        $this->assertLessThanOrEqual(100, $result->percentage);
    }

    /**
     * Test processingTimeMs is integer.
     */
    public function test_processing_time_is_integer(): void
    {
        $result = $this->createDefaultResult();

        $this->assertIsInt($result->processingTimeMs);
        $this->assertGreaterThanOrEqual(0, $result->processingTimeMs);
    }

    // ==================== SUCCESS FACTORY TESTS ====================

    /**
     * Test success factory method.
     */
    public function test_success_factory_creates_result(): void
    {
        $result = IndicatorResult::success(
            slug: 'meta-description',
            name: 'Meta Description',
            score: 75.0,
            maxScore: 100.0,
            data: ['description' => 'Test'],
            recommendations: [],
            processingTimeMs: 200,
        );

        $this->assertEquals('meta-description', $result->slug);
        $this->assertEquals('Meta Description', $result->name);
        $this->assertEquals(75.0, $result->score);
        $this->assertEquals(100.0, $result->maxScore);
        $this->assertEquals(75, $result->percentage);
        $this->assertNull($result->error);
    }

    /**
     * Test success factory calculates percentage correctly.
     */
    public function test_success_factory_calculates_percentage(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 50.0,
            maxScore: 100.0,
        );

        $this->assertEquals(50, $result->percentage);
    }

    /**
     * Test success factory with custom maxScore.
     */
    public function test_success_factory_custom_max_score(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 15.0,
            maxScore: 20.0,
        );

        $this->assertEquals(15.0, $result->score);
        $this->assertEquals(20.0, $result->maxScore);
        $this->assertEquals(75, $result->percentage); // 15/20 = 75%
    }

    /**
     * Test success factory with zero maxScore.
     */
    public function test_success_factory_zero_max_score(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 0.0,
            maxScore: 0.0,
        );

        $this->assertEquals(0, $result->percentage);
    }

    /**
     * Test success factory percentage rounding.
     */
    public function test_success_factory_percentage_rounding(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 33.33,
            maxScore: 100.0,
        );

        $this->assertEquals(33, $result->percentage); // rounded
    }

    // ==================== FAILED FACTORY TESTS ====================

    /**
     * Test failed factory method.
     */
    public function test_failed_factory_creates_result(): void
    {
        $result = IndicatorResult::failed(
            slug: 'broken-links',
            name: 'Broken Links Check',
            maxScore: 100.0,
            error: 'Connection timeout',
            processingTimeMs: 30000,
        );

        $this->assertEquals('broken-links', $result->slug);
        $this->assertEquals('Broken Links Check', $result->name);
        $this->assertEquals(0.0, $result->score);
        $this->assertEquals(100.0, $result->maxScore);
        $this->assertEquals(0, $result->percentage);
        $this->assertEquals('Connection timeout', $result->error);
    }

    /**
     * Test failed factory adds error recommendation.
     */
    public function test_failed_factory_adds_error_recommendation(): void
    {
        $result = IndicatorResult::failed(
            slug: 'test',
            name: 'Test',
            maxScore: 100.0,
            error: 'Something went wrong',
        );

        $this->assertCount(1, $result->recommendations);
        $this->assertEquals('high', $result->recommendations[0]['priority']);
        $this->assertEquals('Analysis failed', $result->recommendations[0]['message']);
        $this->assertEquals('Something went wrong', $result->recommendations[0]['details']);
    }

    /**
     * Test failed factory has empty data.
     */
    public function test_failed_factory_has_empty_data(): void
    {
        $result = IndicatorResult::failed(
            slug: 'test',
            name: 'Test',
            maxScore: 100.0,
            error: 'Error',
        );

        $this->assertEquals([], $result->data);
    }

    // ==================== TO ARRAY TESTS ====================

    /**
     * Test toArray serializes correctly.
     */
    public function test_to_array_serializes_correctly(): void
    {
        $result = new IndicatorResult(
            slug: 'title-tag',
            name: 'Title Tag',
            score: 90.0,
            maxScore: 100.0,
            percentage: 90,
            data: ['title' => 'Test'],
            recommendations: [],
            processingTimeMs: 100,
            error: null,
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('score', $array);
        $this->assertArrayHasKey('max_score', $array);
        $this->assertArrayHasKey('percentage', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('recommendations', $array);
        $this->assertArrayHasKey('processing_time_ms', $array);
        $this->assertArrayHasKey('error', $array);
    }

    /**
     * Test toArray uses snake_case keys.
     */
    public function test_to_array_uses_snake_case(): void
    {
        $result = $this->createDefaultResult();
        $array = $result->toArray();

        $this->assertArrayHasKey('max_score', $array);
        $this->assertArrayHasKey('processing_time_ms', $array);
    }

    /**
     * Test toArray rounds score to 2 decimals.
     */
    public function test_to_array_rounds_score(): void
    {
        $result = new IndicatorResult(
            slug: 'test',
            name: 'Test',
            score: 85.666666,
            maxScore: 100.0,
            percentage: 86,
            data: [],
            recommendations: [],
            processingTimeMs: 0,
        );

        $array = $result->toArray();

        $this->assertEquals(85.67, $array['score']);
        $this->assertEquals(100.0, $array['max_score']);
    }

    /**
     * Test toArray includes error when present.
     */
    public function test_to_array_includes_error(): void
    {
        $result = IndicatorResult::failed(
            slug: 'test',
            name: 'Test',
            maxScore: 100.0,
            error: 'Test error',
        );

        $array = $result->toArray();

        $this->assertEquals('Test error', $array['error']);
    }

    /**
     * Test toArray has null error when successful.
     */
    public function test_to_array_null_error_on_success(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 100.0,
            maxScore: 100.0,
        );

        $array = $result->toArray();

        $this->assertNull($array['error']);
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test empty data array allowed.
     */
    public function test_empty_data_allowed(): void
    {
        $result = new IndicatorResult(
            slug: 'test',
            name: 'Test',
            score: 50.0,
            maxScore: 100.0,
            percentage: 50,
            data: [],
            recommendations: [],
            processingTimeMs: 0,
        );

        $this->assertEquals([], $result->data);
    }

    /**
     * Test large data array.
     */
    public function test_large_data_array(): void
    {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data["item_$i"] = "value_$i";
        }

        $result = new IndicatorResult(
            slug: 'test',
            name: 'Test',
            score: 50.0,
            maxScore: 100.0,
            percentage: 50,
            data: $data,
            recommendations: [],
            processingTimeMs: 0,
        );

        $this->assertCount(100, $result->data);
    }

    /**
     * Test nested data array.
     */
    public function test_nested_data_array(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'value',
                ],
            ],
        ];

        $result = new IndicatorResult(
            slug: 'test',
            name: 'Test',
            score: 50.0,
            maxScore: 100.0,
            percentage: 50,
            data: $data,
            recommendations: [],
            processingTimeMs: 0,
        );

        $this->assertEquals('value', $result->data['level1']['level2']['level3']);
    }

    /**
     * Test zero score is valid.
     */
    public function test_zero_score_valid(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 0.0,
            maxScore: 100.0,
        );

        $this->assertEquals(0.0, $result->score);
        $this->assertEquals(0, $result->percentage);
    }

    /**
     * Test max score is valid.
     */
    public function test_max_score_valid(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 100.0,
            maxScore: 100.0,
        );

        $this->assertEquals(100.0, $result->score);
        $this->assertEquals(100, $result->percentage);
    }

    /**
     * Test multiple recommendations.
     */
    public function test_multiple_recommendations(): void
    {
        $recommendations = [
            ['priority' => 'high', 'message' => 'Critical issue'],
            ['priority' => 'medium', 'message' => 'Suggested improvement'],
            ['priority' => 'low', 'message' => 'Minor tip'],
        ];

        $result = new IndicatorResult(
            slug: 'test',
            name: 'Test',
            score: 50.0,
            maxScore: 100.0,
            percentage: 50,
            data: [],
            recommendations: $recommendations,
            processingTimeMs: 0,
        );

        $this->assertCount(3, $result->recommendations);
    }

    /**
     * Test zero processing time.
     */
    public function test_zero_processing_time(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 100.0,
            maxScore: 100.0,
            processingTimeMs: 0,
        );

        $this->assertEquals(0, $result->processingTimeMs);
    }

    /**
     * Test large processing time.
     */
    public function test_large_processing_time(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Test',
            score: 100.0,
            maxScore: 100.0,
            processingTimeMs: 30000, // 30 seconds
        );

        $this->assertEquals(30000, $result->processingTimeMs);
    }

    /**
     * Test special characters in error message.
     */
    public function test_special_characters_in_error(): void
    {
        $result = IndicatorResult::failed(
            slug: 'test',
            name: 'Test',
            maxScore: 100.0,
            error: 'Error with <script>alert("xss")</script> & "quotes"',
        );

        $this->assertStringContainsString('<script>', $result->error);
        $this->assertStringContainsString('&', $result->error);
    }

    /**
     * Test unicode in name and data.
     */
    public function test_unicode_support(): void
    {
        $result = IndicatorResult::success(
            slug: 'test',
            name: 'Tëst Änälÿsïs 日本語',
            score: 100.0,
            maxScore: 100.0,
            data: ['emoji' => '🎉', 'chinese' => '中文'],
        );

        $this->assertEquals('Tëst Änälÿsïs 日本語', $result->name);
        $this->assertEquals('🎉', $result->data['emoji']);
        $this->assertEquals('中文', $result->data['chinese']);
    }

    /**
     * Helper: Create default result.
     */
    private function createDefaultResult(): IndicatorResult
    {
        return new IndicatorResult(
            slug: 'test-indicator',
            name: 'Test Indicator',
            score: 50.0,
            maxScore: 100.0,
            percentage: 50,
            data: [],
            recommendations: [],
            processingTimeMs: 100,
        );
    }
}
