<?php

namespace Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Enums\AnnotationCategory;

/**
 * Unit tests for AnnotationCategory enum.
 *
 * Task: TASK-9-2 - Annotation Category Enum
 *
 * @see https://www.php.net/manual/en/language.types.enumerations.php
 */
#[Group('enum')]
#[Group('annotation')]
class AnnotationCategoryTest extends TestCase
{
    // =========================================================================
    // ENUM STRUCTURE TESTS
    // =========================================================================

    #[Test]
    public function enum_exists(): void
    {
        $this->assertTrue(enum_exists(AnnotationCategory::class));
    }

    #[Test]
    public function enum_is_backed_with_string(): void
    {
        $reflection = new \ReflectionEnum(AnnotationCategory::class);
        $this->assertTrue($reflection->isBacked());

        $backingType = $reflection->getBackingType();
        $this->assertNotNull($backingType);
        $this->assertEquals('string', $backingType->getName());
    }

    #[Test]
    public function enum_has_eight_cases(): void
    {
        $cases = AnnotationCategory::cases();
        $this->assertCount(8, $cases);
    }

    // =========================================================================
    // CASE EXISTENCE AND VALUE TESTS
    // =========================================================================

    #[Test]
    public function content_update_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::CONTENT_UPDATE'));
        $this->assertEquals('content_update', AnnotationCategory::CONTENT_UPDATE->value);
    }

    #[Test]
    public function link_building_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::LINK_BUILDING'));
        $this->assertEquals('link_building', AnnotationCategory::LINK_BUILDING->value);
    }

    #[Test]
    public function technical_fix_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::TECHNICAL_FIX'));
        $this->assertEquals('technical_fix', AnnotationCategory::TECHNICAL_FIX->value);
    }

    #[Test]
    public function algorithm_update_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::ALGORITHM_UPDATE'));
        $this->assertEquals('algorithm_update', AnnotationCategory::ALGORITHM_UPDATE->value);
    }

    #[Test]
    public function competitor_action_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::COMPETITOR_ACTION'));
        $this->assertEquals('competitor_action', AnnotationCategory::COMPETITOR_ACTION->value);
    }

    #[Test]
    public function seasonal_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::SEASONAL'));
        $this->assertEquals('seasonal', AnnotationCategory::SEASONAL->value);
    }

    #[Test]
    public function manual_action_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::MANUAL_ACTION'));
        $this->assertEquals('manual_action', AnnotationCategory::MANUAL_ACTION->value);
    }

    #[Test]
    public function other_case_exists(): void
    {
        $this->assertTrue(defined(AnnotationCategory::class . '::OTHER'));
        $this->assertEquals('other', AnnotationCategory::OTHER->value);
    }

    // =========================================================================
    // CASE VALUES DATA PROVIDER
    // =========================================================================

    public static function caseValuesProvider(): array
    {
        return [
            'CONTENT_UPDATE' => [AnnotationCategory::CONTENT_UPDATE, 'content_update'],
            'LINK_BUILDING' => [AnnotationCategory::LINK_BUILDING, 'link_building'],
            'TECHNICAL_FIX' => [AnnotationCategory::TECHNICAL_FIX, 'technical_fix'],
            'ALGORITHM_UPDATE' => [AnnotationCategory::ALGORITHM_UPDATE, 'algorithm_update'],
            'COMPETITOR_ACTION' => [AnnotationCategory::COMPETITOR_ACTION, 'competitor_action'],
            'SEASONAL' => [AnnotationCategory::SEASONAL, 'seasonal'],
            'MANUAL_ACTION' => [AnnotationCategory::MANUAL_ACTION, 'manual_action'],
            'OTHER' => [AnnotationCategory::OTHER, 'other'],
        ];
    }

    #[Test]
    #[DataProvider('caseValuesProvider')]
    public function all_cases_have_correct_string_values(AnnotationCategory $case, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, $case->value);
    }

    // =========================================================================
    // LABEL METHOD TESTS
    // =========================================================================

    public static function labelProvider(): array
    {
        return [
            'CONTENT_UPDATE label' => [AnnotationCategory::CONTENT_UPDATE, 'Content Update'],
            'LINK_BUILDING label' => [AnnotationCategory::LINK_BUILDING, 'Link Building'],
            'TECHNICAL_FIX label' => [AnnotationCategory::TECHNICAL_FIX, 'Technical Fix'],
            'ALGORITHM_UPDATE label' => [AnnotationCategory::ALGORITHM_UPDATE, 'Algorithm Update'],
            'COMPETITOR_ACTION label' => [AnnotationCategory::COMPETITOR_ACTION, 'Competitor Action'],
            'SEASONAL label' => [AnnotationCategory::SEASONAL, 'Seasonal'],
            'MANUAL_ACTION label' => [AnnotationCategory::MANUAL_ACTION, 'Manual Action'],
            'OTHER label' => [AnnotationCategory::OTHER, 'Other'],
        ];
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function label_returns_correct_human_readable_text(AnnotationCategory $case, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $case->label());
    }

    #[Test]
    public function label_method_exists(): void
    {
        $reflection = new \ReflectionEnum(AnnotationCategory::class);
        $this->assertTrue($reflection->hasMethod('label'));

        $method = $reflection->getMethod('label');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }

    #[Test]
    public function all_labels_are_unique(): void
    {
        $labels = array_map(
            fn(AnnotationCategory $case) => $case->label(),
            AnnotationCategory::cases()
        );

        $this->assertEquals($labels, array_unique($labels), 'Each label should be unique');
    }

    #[Test]
    public function all_labels_are_title_case(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $label = $case->label();
            // Each word should start with uppercase
            $words = explode(' ', $label);
            foreach ($words as $word) {
                $this->assertEquals(ucfirst(strtolower($word)), $word, 
                    "Label '{$label}' word '{$word}' should be title case");
            }
        }
    }

    // =========================================================================
    // ICON METHOD TESTS
    // =========================================================================

    public static function iconProvider(): array
    {
        return [
            'CONTENT_UPDATE icon' => [AnnotationCategory::CONTENT_UPDATE, '✏️'],
            'LINK_BUILDING icon' => [AnnotationCategory::LINK_BUILDING, '🔗'],
            'TECHNICAL_FIX icon' => [AnnotationCategory::TECHNICAL_FIX, '🔧'],
            'ALGORITHM_UPDATE icon' => [AnnotationCategory::ALGORITHM_UPDATE, '🤖'],
            'COMPETITOR_ACTION icon' => [AnnotationCategory::COMPETITOR_ACTION, '👀'],
            'SEASONAL icon' => [AnnotationCategory::SEASONAL, '📅'],
            'MANUAL_ACTION icon' => [AnnotationCategory::MANUAL_ACTION, '⚠️'],
            'OTHER icon' => [AnnotationCategory::OTHER, '📝'],
        ];
    }

    #[Test]
    #[DataProvider('iconProvider')]
    public function icon_returns_correct_emoji(AnnotationCategory $case, string $expectedIcon): void
    {
        $this->assertEquals($expectedIcon, $case->icon());
    }

    #[Test]
    public function icon_method_exists(): void
    {
        $reflection = new \ReflectionEnum(AnnotationCategory::class);
        $this->assertTrue($reflection->hasMethod('icon'));

        $method = $reflection->getMethod('icon');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }

    #[Test]
    public function all_icons_are_single_emoji(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $icon = $case->icon();
            // Emoji should be 1-2 characters (some emojis are multi-codepoint)
            $this->assertGreaterThanOrEqual(1, mb_strlen($icon, 'UTF-8'));
            $this->assertLessThanOrEqual(4, mb_strlen($icon, 'UTF-8'), 
                "Icon for {$case->name} should be a single emoji");
        }
    }

    #[Test]
    public function all_icons_are_unique(): void
    {
        $icons = array_map(
            fn(AnnotationCategory $case) => $case->icon(),
            AnnotationCategory::cases()
        );

        $this->assertEquals($icons, array_unique($icons), 'Each icon should be unique');
    }

    // =========================================================================
    // BACKED ENUM METHODS TESTS
    // =========================================================================

    #[Test]
    public function from_returns_correct_case_for_valid_value(): void
    {
        $this->assertSame(AnnotationCategory::CONTENT_UPDATE, AnnotationCategory::from('content_update'));
        $this->assertSame(AnnotationCategory::LINK_BUILDING, AnnotationCategory::from('link_building'));
        $this->assertSame(AnnotationCategory::TECHNICAL_FIX, AnnotationCategory::from('technical_fix'));
        $this->assertSame(AnnotationCategory::ALGORITHM_UPDATE, AnnotationCategory::from('algorithm_update'));
        $this->assertSame(AnnotationCategory::COMPETITOR_ACTION, AnnotationCategory::from('competitor_action'));
        $this->assertSame(AnnotationCategory::SEASONAL, AnnotationCategory::from('seasonal'));
        $this->assertSame(AnnotationCategory::MANUAL_ACTION, AnnotationCategory::from('manual_action'));
        $this->assertSame(AnnotationCategory::OTHER, AnnotationCategory::from('other'));
    }

    #[Test]
    public function from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        AnnotationCategory::from('invalid_category');
    }

    #[Test]
    public function try_from_returns_correct_case_for_valid_value(): void
    {
        $this->assertSame(AnnotationCategory::CONTENT_UPDATE, AnnotationCategory::tryFrom('content_update'));
        $this->assertSame(AnnotationCategory::OTHER, AnnotationCategory::tryFrom('other'));
    }

    #[Test]
    public function try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom('invalid_category'));
        $this->assertNull(AnnotationCategory::tryFrom(''));
        $this->assertNull(AnnotationCategory::tryFrom('CONTENT_UPDATE')); // Case sensitive
    }

    #[Test]
    public function case_values_are_lowercase_snake_case(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $value = $case->value;
            // Should match lowercase_snake_case pattern
            $this->assertMatchesRegularExpression('/^[a-z]+(_[a-z]+)*$/', $value,
                "Value '{$value}' should be lowercase snake_case");
        }
    }

    // =========================================================================
    // CASE SENSITIVITY TESTS
    // =========================================================================

    #[Test]
    public function values_are_case_sensitive(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom('CONTENT_UPDATE'));
        $this->assertNull(AnnotationCategory::tryFrom('Content_Update'));
        $this->assertNull(AnnotationCategory::tryFrom('CONTENTUPDATE'));
    }

    #[Test]
    public function values_do_not_accept_uppercase(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $uppercase = strtoupper($case->value);
            $this->assertNull(AnnotationCategory::tryFrom($uppercase),
                "Value '{$uppercase}' should not match '{$case->value}'");
        }
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    #[Test]
    public function labels_do_not_contain_html_tags(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $label = $case->label();
            $this->assertDoesNotMatchRegularExpression('/<[^>]+>/', $label,
                "Label '{$label}' should not contain HTML tags");
        }
    }

    #[Test]
    public function icons_are_safe_for_html_output(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $icon = $case->icon();
            // Icons should be pure emoji, no scripts or HTML
            $this->assertDoesNotMatchRegularExpression('/<[^>]+>/', $icon);
            $this->assertDoesNotMatchRegularExpression('/javascript:/i', $icon);
            $this->assertDoesNotMatchRegularExpression('/on\w+=/i', $icon);
        }
    }

    #[Test]
    public function values_are_safe_for_database_storage(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $value = $case->value;
            // Values should only contain alphanumeric and underscores
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $value,
                "Value '{$value}' should only contain lowercase letters and underscores");
        }
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    #[Test]
    public function enum_can_be_json_serialized(): void
    {
        $case = AnnotationCategory::CONTENT_UPDATE;
        $json = json_encode($case);
        
        $this->assertJson($json);
        $this->assertEquals('"content_update"', $json);
    }

    #[Test]
    public function enum_can_be_deserialized_from_json(): void
    {
        $json = '"link_building"';
        $decoded = json_decode($json);
        
        $case = AnnotationCategory::from($decoded);
        $this->assertSame(AnnotationCategory::LINK_BUILDING, $case);
    }

    // =========================================================================
    // CONSISTENCY TESTS
    // =========================================================================

    #[Test]
    public function label_matches_case_name_format(): void
    {
        $mappings = [
            'CONTENT_UPDATE' => 'Content Update',
            'LINK_BUILDING' => 'Link Building',
            'TECHNICAL_FIX' => 'Technical Fix',
            'ALGORITHM_UPDATE' => 'Algorithm Update',
            'COMPETITOR_ACTION' => 'Competitor Action',
            'SEASONAL' => 'Seasonal',
            'MANUAL_ACTION' => 'Manual Action',
            'OTHER' => 'Other',
        ];

        foreach (AnnotationCategory::cases() as $case) {
            $this->assertEquals(
                $mappings[$case->name],
                $case->label(),
                "Label for {$case->name} should be '{$mappings[$case->name]}'"
            );
        }
    }

    #[Test]
    public function value_matches_lowercased_name_with_underscores(): void
    {
        foreach (AnnotationCategory::cases() as $case) {
            $expectedValue = strtolower($case->name);
            $this->assertEquals($expectedValue, $case->value,
                "Value should be lowercase version of name");
        }
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    #[Test]
    public function enum_can_be_used_in_match_expression(): void
    {
        $case = AnnotationCategory::ALGORITHM_UPDATE;

        $result = match($case) {
            AnnotationCategory::CONTENT_UPDATE => 'content',
            AnnotationCategory::LINK_BUILDING => 'links',
            AnnotationCategory::TECHNICAL_FIX => 'technical',
            AnnotationCategory::ALGORITHM_UPDATE => 'algorithm',
            AnnotationCategory::COMPETITOR_ACTION => 'competitor',
            AnnotationCategory::SEASONAL => 'seasonal',
            AnnotationCategory::MANUAL_ACTION => 'manual',
            AnnotationCategory::OTHER => 'other',
        };

        $this->assertEquals('algorithm', $result);
    }

    #[Test]
    public function enum_works_with_array_key(): void
    {
        $map = [
            AnnotationCategory::CONTENT_UPDATE->value => 'desc1',
            AnnotationCategory::LINK_BUILDING->value => 'desc2',
        ];

        $this->assertEquals('desc1', $map[AnnotationCategory::CONTENT_UPDATE->value]);
        $this->assertEquals('desc2', $map[AnnotationCategory::LINK_BUILDING->value]);
    }

    #[Test]
    public function cases_returns_all_cases_in_declaration_order(): void
    {
        $cases = AnnotationCategory::cases();
        
        $expectedOrder = [
            'CONTENT_UPDATE',
            'LINK_BUILDING',
            'TECHNICAL_FIX',
            'ALGORITHM_UPDATE',
            'COMPETITOR_ACTION',
            'SEASONAL',
            'MANUAL_ACTION',
            'OTHER',
        ];

        $actualOrder = array_map(fn($case) => $case->name, $cases);
        $this->assertEquals($expectedOrder, $actualOrder);
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    #[Test]
    public function empty_string_is_not_valid(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom(''));
    }

    #[Test]
    public function whitespace_string_is_not_valid(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom(' '));
        $this->assertNull(AnnotationCategory::tryFrom('content_update '));
        $this->assertNull(AnnotationCategory::tryFrom(' content_update'));
    }

    #[Test]
    public function null_is_not_valid(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom(null));
    }

    #[Test]
    public function numeric_strings_are_not_valid(): void
    {
        $this->assertNull(AnnotationCategory::tryFrom('0'));
        $this->assertNull(AnnotationCategory::tryFrom('1'));
        $this->assertNull(AnnotationCategory::tryFrom('123'));
    }

    // =========================================================================
    // ACCEPTANCE CRITERIA TESTS
    // =========================================================================

    #[Test]
    public function ac1_php_enum_defines_categories_matching_specification(): void
    {
        // AC: PHP enum defines categories matching specification.
        $requiredCases = [
            'CONTENT_UPDATE',
            'LINK_BUILDING',
            'TECHNICAL_FIX',
            'ALGORITHM_UPDATE',
            'COMPETITOR_ACTION',
            'SEASONAL',
            'MANUAL_ACTION',
            'OTHER',
        ];

        foreach ($requiredCases as $caseName) {
            $this->assertTrue(
                defined(AnnotationCategory::class . '::' . $caseName),
                "Enum should have case: {$caseName}"
            );
        }
    }

    #[Test]
    public function ac2_category_label_method_supports_dynamic_rendering(): void
    {
        // AC: Category label method supports dynamic rendering.
        $this->assertTrue(method_exists(AnnotationCategory::CONTENT_UPDATE, 'label'));
        
        foreach (AnnotationCategory::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    #[Test]
    public function ac3_category_icon_method_supports_dynamic_rendering(): void
    {
        // AC: Category icon method supports dynamic rendering.
        $this->assertTrue(method_exists(AnnotationCategory::CONTENT_UPDATE, 'icon'));
        
        foreach (AnnotationCategory::cases() as $case) {
            $icon = $case->icon();
            $this->assertIsString($icon);
            $this->assertNotEmpty($icon);
        }
    }
}
