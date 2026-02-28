<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorCategory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IndicatorCategory enum
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class IndicatorCategoryTest extends TestCase
{
    /**
     * Test enum has CONTENT case.
     * 
     * @test
     */
    public function enum_has_content_case(): void
    {
        $this->assertEquals('content', IndicatorCategory::CONTENT->value);
    }

    /**
     * Test enum has TECHNICAL case.
     * 
     * @test
     */
    public function enum_has_technical_case(): void
    {
        $this->assertEquals('technical', IndicatorCategory::TECHNICAL->value);
    }

    /**
     * Test enum has ACCESSIBILITY case.
     * 
     * @test
     */
    public function enum_has_accessibility_case(): void
    {
        $this->assertEquals('accessibility', IndicatorCategory::ACCESSIBILITY->value);
    }

    /**
     * Test enum has PERFORMANCE case.
     * 
     * @test
     */
    public function enum_has_performance_case(): void
    {
        $this->assertEquals('performance', IndicatorCategory::PERFORMANCE->value);
    }

    /**
     * Test fromValue() creates correct enum.
     * 
     * @test
     */
    public function from_value_creates_correct_enum(): void
    {
        $this->assertEquals(IndicatorCategory::CONTENT, IndicatorCategory::fromValue('content'));
        $this->assertEquals(IndicatorCategory::TECHNICAL, IndicatorCategory::fromValue('technical'));
        $this->assertEquals(IndicatorCategory::ACCESSIBILITY, IndicatorCategory::fromValue('accessibility'));
        $this->assertEquals(IndicatorCategory::PERFORMANCE, IndicatorCategory::fromValue('performance'));
    }

    /**
     * Test tryFromValue() returns null for invalid.
     * 
     * @test
     */
    public function try_from_value_returns_null_for_invalid(): void
    {
        $this->assertNull(IndicatorCategory::tryFromValue('invalid'));
        $this->assertNull(IndicatorCategory::tryFromValue(''));
        $this->assertNull(IndicatorCategory::tryFromValue('CONTENT'));
    }

    /**
     * Test fromValue() throws for invalid.
     * 
     * @test
     */
    public function from_value_throws_for_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IndicatorCategory::fromValue('invalid_category');
    }

    /**
     * Test values() returns all categories.
     * 
     * @test
     */
    public function values_returns_all_categories(): void
    {
        $values = IndicatorCategory::values();

        $this->assertIsArray($values);
        $this->assertCount(4, $values);
        $this->assertContains('content', $values);
        $this->assertContains('technical', $values);
        $this->assertContains('accessibility', $values);
        $this->assertContains('performance', $values);
    }

    /**
     * Test label() returns human-readable.
     * 
     * @test
     */
    public function label_returns_human_readable(): void
    {
        $this->assertEquals('Content', IndicatorCategory::CONTENT->label());
        $this->assertEquals('Technical SEO', IndicatorCategory::TECHNICAL->label());
        $this->assertEquals('Accessibility', IndicatorCategory::ACCESSIBILITY->label());
        $this->assertEquals('Performance', IndicatorCategory::PERFORMANCE->label());
    }

    /**
     * Test description() returns meaningful text.
     * 
     * @test
     */
    public function description_returns_meaningful_text(): void
    {
        $this->assertStringContainsString('title', IndicatorCategory::CONTENT->description());
        $this->assertStringContainsString('URL', IndicatorCategory::TECHNICAL->description());
        $this->assertStringContainsString('alt', IndicatorCategory::ACCESSIBILITY->description());
        $this->assertStringContainsString('speed', IndicatorCategory::PERFORMANCE->description());
    }

    /**
     * Test icon() returns valid icon names.
     * 
     * @test
     */
    public function icon_returns_valid_names(): void
    {
        $icons = [
            IndicatorCategory::CONTENT->icon(),
            IndicatorCategory::TECHNICAL->icon(),
            IndicatorCategory::ACCESSIBILITY->icon(),
            IndicatorCategory::PERFORMANCE->icon(),
        ];

        foreach ($icons as $icon) {
            $this->assertIsString($icon);
            $this->assertNotEmpty($icon);
        }
    }

    /**
     * Test color() returns valid colors.
     * 
     * @test
     */
    public function color_returns_valid_colors(): void
    {
        $colors = [
            IndicatorCategory::CONTENT->color(),
            IndicatorCategory::TECHNICAL->color(),
            IndicatorCategory::ACCESSIBILITY->color(),
            IndicatorCategory::PERFORMANCE->color(),
        ];

        foreach ($colors as $color) {
            $this->assertIsString($color);
            $this->assertNotEmpty($color);
        }
    }

    /**
     * Test enum is backed by string.
     * 
     * @test
     */
    public function enum_is_backed_by_string(): void
    {
        $reflection = new \ReflectionClass(IndicatorCategory::class);
        
        $this->assertTrue($reflection->isEnum());
        $this->assertTrue(method_exists(IndicatorCategory::class, 'from'));
        $this->assertTrue(method_exists(IndicatorCategory::class, 'tryFrom'));
    }

    /**
     * Test all cases have unique values.
     * 
     * @test
     */
    public function all_cases_have_unique_values(): void
    {
        $values = array_map(fn($case) => $case->value, IndicatorCategory::cases());
        
        $this->assertEquals(count($values), count(array_unique($values)));
    }

    /**
     * Test total of 4 categories.
     * 
     * @test
     */
    public function total_of_four_categories(): void
    {
        $this->assertCount(4, IndicatorCategory::cases());
    }

    /**
     * Test can iterate over all cases.
     * 
     * @test
     */
    public function can_iterate_over_all_cases(): void
    {
        $count = 0;
        foreach (IndicatorCategory::cases() as $case) {
            $this->assertInstanceOf(IndicatorCategory::class, $case);
            $count++;
        }
        
        $this->assertEquals(4, $count);
    }

    /**
     * Test toArray() returns correct structure.
     * 
     * @test
     */
    public function to_array_returns_correct_structure(): void
    {
        $array = IndicatorCategory::toArray();

        $this->assertIsArray($array);
        $this->assertCount(4, $array);
        $this->assertEquals('Content', $array['content']);
        $this->assertEquals('Technical SEO', $array['technical']);
    }

    /**
     * Test enum values are lowercase.
     * 
     * @test
     */
    public function enum_values_are_lowercase(): void
    {
        foreach (IndicatorCategory::cases() as $case) {
            $this->assertEquals(strtolower($case->value), $case->value);
        }
    }

    /**
     * Test tryFrom() behavior (native enum method).
     * 
     * @test
     */
    public function try_from_behavior(): void
    {
        $this->assertEquals(IndicatorCategory::CONTENT, IndicatorCategory::tryFrom('content'));
        $this->assertNull(IndicatorCategory::tryFrom('invalid'));
    }

    /**
     * Test from() behavior (native enum method).
     * 
     * @test
     */
    public function from_behavior(): void
    {
        $this->assertEquals(IndicatorCategory::CONTENT, IndicatorCategory::from('content'));
        
        $this->expectException(\ValueError::class);
        IndicatorCategory::from('invalid');
    }
}
