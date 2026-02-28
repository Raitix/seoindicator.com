<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorScore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IndicatorScore enum
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class IndicatorScoreTest extends TestCase
{
    /**
     * Test NOT_APPLICABLE = 0.
     * 
     * @test
     */
    public function not_applicable_is_zero(): void
    {
        $this->assertEquals(0, IndicatorScore::NOT_APPLICABLE->value);
    }

    /**
     * Test POOR = 1.
     * 
     * @test
     */
    public function poor_is_one(): void
    {
        $this->assertEquals(1, IndicatorScore::POOR->value);
    }

    /**
     * Test GOOD = 2.
     * 
     * @test
     */
    public function good_is_two(): void
    {
        $this->assertEquals(2, IndicatorScore::GOOD->value);
    }

    /**
     * Test EXCELLENT = 3.
     * 
     * @test
     */
    public function excellent_is_three(): void
    {
        $this->assertEquals(3, IndicatorScore::EXCELLENT->value);
    }

    /**
     * Test isValid(0) returns true.
     * 
     * @test
     */
    public function is_valid_zero_returns_true(): void
    {
        $this->assertTrue(IndicatorScore::isValid(0));
    }

    /**
     * Test isValid(1) returns true.
     * 
     * @test
     */
    public function is_valid_one_returns_true(): void
    {
        $this->assertTrue(IndicatorScore::isValid(1));
    }

    /**
     * Test isValid(2) returns true.
     * 
     * @test
     */
    public function is_valid_two_returns_true(): void
    {
        $this->assertTrue(IndicatorScore::isValid(2));
    }

    /**
     * Test isValid(3) returns true.
     * 
     * @test
     */
    public function is_valid_three_returns_true(): void
    {
        $this->assertTrue(IndicatorScore::isValid(3));
    }

    /**
     * Test isValid(4) returns false.
     * 
     * @test
     */
    public function is_valid_four_returns_false(): void
    {
        $this->assertFalse(IndicatorScore::isValid(4));
    }

    /**
     * Test isValid(-1) returns false.
     * 
     * @test
     */
    public function is_valid_negative_returns_false(): void
    {
        $this->assertFalse(IndicatorScore::isValid(-1));
    }

    /**
     * Test getLabel(0) returns "N/A".
     * 
     * @test
     */
    public function get_label_zero(): void
    {
        $this->assertEquals('N/A', IndicatorScore::getLabel(0));
    }

    /**
     * Test getLabel(1) returns "Poor".
     * 
     * @test
     */
    public function get_label_one(): void
    {
        $this->assertEquals('Poor', IndicatorScore::getLabel(1));
    }

    /**
     * Test getLabel(2) returns "Good".
     * 
     * @test
     */
    public function get_label_two(): void
    {
        $this->assertEquals('Good', IndicatorScore::getLabel(2));
    }

    /**
     * Test getLabel(3) returns "Excellent".
     * 
     * @test
     */
    public function get_label_three(): void
    {
        $this->assertEquals('Excellent', IndicatorScore::getLabel(3));
    }

    /**
     * Test getLabel() throws for invalid.
     * 
     * @test
     */
    public function get_label_throws_for_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IndicatorScore::getLabel(4);
    }

    /**
     * Test label() method on enum cases.
     * 
     * @test
     */
    public function label_method(): void
    {
        $this->assertEquals('N/A', IndicatorScore::NOT_APPLICABLE->label());
        $this->assertEquals('Poor', IndicatorScore::POOR->label());
        $this->assertEquals('Good', IndicatorScore::GOOD->label());
        $this->assertEquals('Excellent', IndicatorScore::EXCELLENT->label());
    }

    /**
     * Test description() method.
     * 
     * @test
     */
    public function description_method(): void
    {
        $this->assertStringContainsString('not applicable', IndicatorScore::NOT_APPLICABLE->description());
        $this->assertStringContainsString('immediate attention', IndicatorScore::POOR->description());
        $this->assertStringContainsString('room for improvement', IndicatorScore::GOOD->description());
        $this->assertStringContainsString('All criteria met', IndicatorScore::EXCELLENT->description());
    }

    /**
     * Test color() method.
     * 
     * @test
     */
    public function color_method(): void
    {
        $this->assertEquals('gray', IndicatorScore::NOT_APPLICABLE->color());
        $this->assertEquals('red', IndicatorScore::POOR->color());
        $this->assertEquals('yellow', IndicatorScore::GOOD->color());
        $this->assertEquals('green', IndicatorScore::EXCELLENT->color());
    }

    /**
     * Test stars() method.
     * 
     * @test
     */
    public function stars_method(): void
    {
        $this->assertEquals(0, IndicatorScore::NOT_APPLICABLE->stars());
        $this->assertEquals(1, IndicatorScore::POOR->stars());
        $this->assertEquals(2, IndicatorScore::GOOD->stars());
        $this->assertEquals(3, IndicatorScore::EXCELLENT->stars());
    }

    /**
     * Test isPassing() method.
     * 
     * @test
     */
    public function is_passing_method(): void
    {
        $this->assertFalse(IndicatorScore::NOT_APPLICABLE->isPassing());
        $this->assertFalse(IndicatorScore::POOR->isPassing());
        $this->assertTrue(IndicatorScore::GOOD->isPassing());
        $this->assertTrue(IndicatorScore::EXCELLENT->isPassing());
    }

    /**
     * Test tryFromInt() method.
     * 
     * @test
     */
    public function try_from_int_method(): void
    {
        $this->assertEquals(IndicatorScore::EXCELLENT, IndicatorScore::tryFromInt(3));
        $this->assertNull(IndicatorScore::tryFromInt(4));
        $this->assertNull(IndicatorScore::tryFromInt(-1));
    }

    /**
     * Test fromInt() method.
     * 
     * @test
     */
    public function from_int_method(): void
    {
        $this->assertEquals(IndicatorScore::EXCELLENT, IndicatorScore::fromInt(3));
        
        $this->expectException(\InvalidArgumentException::class);
        IndicatorScore::fromInt(4);
    }

    /**
     * Test values() method.
     * 
     * @test
     */
    public function values_method(): void
    {
        $values = IndicatorScore::values();

        $this->assertIsArray($values);
        $this->assertCount(4, $values);
        $this->assertEquals([0, 1, 2, 3], $values);
    }

    /**
     * Test compare() method.
     * 
     * @test
     */
    public function compare_method(): void
    {
        $this->assertEquals(-1, IndicatorScore::POOR->compare(IndicatorScore::GOOD));
        $this->assertEquals(0, IndicatorScore::GOOD->compare(IndicatorScore::GOOD));
        $this->assertEquals(1, IndicatorScore::EXCELLENT->compare(IndicatorScore::POOR));
    }

    /**
     * Test isBetterThan() method.
     * 
     * @test
     */
    public function is_better_than_method(): void
    {
        $this->assertTrue(IndicatorScore::EXCELLENT->isBetterThan(IndicatorScore::GOOD));
        $this->assertTrue(IndicatorScore::GOOD->isBetterThan(IndicatorScore::POOR));
        $this->assertFalse(IndicatorScore::POOR->isBetterThan(IndicatorScore::GOOD));
    }

    /**
     * Test isWorseThan() method.
     * 
     * @test
     */
    public function is_worse_than_method(): void
    {
        $this->assertTrue(IndicatorScore::POOR->isWorseThan(IndicatorScore::GOOD));
        $this->assertTrue(IndicatorScore::GOOD->isWorseThan(IndicatorScore::EXCELLENT));
        $this->assertFalse(IndicatorScore::EXCELLENT->isWorseThan(IndicatorScore::GOOD));
    }

    /**
     * Test enum is backed by int.
     * 
     * @test
     */
    public function enum_is_backed_by_int(): void
    {
        $reflection = new \ReflectionClass(IndicatorScore::class);
        
        $this->assertTrue($reflection->isEnum());
        $this->assertEquals('int', (string) $reflection->getMethod('from')->getReturnType());
    }

    /**
     * Test all cases have unique values.
     * 
     * @test
     */
    public function all_cases_have_unique_values(): void
    {
        $values = array_map(fn($case) => $case->value, IndicatorScore::cases());
        
        $this->assertEquals(count($values), count(array_unique($values)));
    }

    /**
     * Test can iterate over all cases.
     * 
     * @test
     */
    public function can_iterate_over_all_cases(): void
    {
        $count = 0;
        foreach (IndicatorScore::cases() as $case) {
            $this->assertInstanceOf(IndicatorScore::class, $case);
            $count++;
        }
        
        $this->assertEquals(4, $count);
    }
}
