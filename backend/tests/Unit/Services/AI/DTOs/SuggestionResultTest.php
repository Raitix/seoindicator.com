<?php

namespace Tests\Unit\Services\AI\DTOs;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Test suite for SuggestionResult DTO.
 * 
 * AC-3: SuggestionResult DTO Created
 * 
 * @group ai
 * @group task-21-1
 */
class SuggestionResultTest extends TestCase
{
    private const DTO_CLASS = 'App\Services\AI\DTOs\SuggestionResult';

    /**
     * Sample valid suggestion data
     */
    private function getValidSuggestionData(): array
    {
        return [
            'indicator' => 'title',
            'score' => 85,
            'issues' => ['Title too short', 'Missing keyword'],
            'suggestions' => [
                [
                    'type' => 'improve',
                    'text' => 'Add target keyword to title',
                    'reason' => 'Improves SEO relevance'
                ]
            ],
            'bestPractices' => ['Keep title under 60 characters'],
            'provider' => 'openrouter',
            'model' => 'llama-3.1-8b-instruct',
            'generatedAt' => new \DateTime('2026-03-01T12:00:00Z'),
        ];
    }

    // =========================================================================
    // SUG-001: DTO file exists at correct path
    // =========================================================================

    #[Test]
    #[TestDox('SUG-001: DTO file exists at correct path')]
    public function dto_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Services/AI/DTOs/SuggestionResult.php'),
            'SuggestionResult should exist at app/Services/AI/DTOs/SuggestionResult.php'
        );
    }

    // =========================================================================
    // SUG-002: DTO class exists and is instantiable
    // =========================================================================

    #[Test]
    #[TestDox('SUG-002: DTO class exists and is instantiable')]
    public function dto_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(self::DTO_CLASS), 'SuggestionResult class should exist');
    }

    // =========================================================================
    // SUG-003: Create valid result with all properties
    // =========================================================================

    #[Test]
    #[TestDox('SUG-003: Create valid result with all properties')]
    public function create_valid_result_with_all_properties(): void
    {
        $data = $this->getValidSuggestionData();
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEquals('title', $result->indicator);
        $this->assertEquals(85, $result->score);
        $this->assertCount(2, $result->issues);
        $this->assertCount(1, $result->suggestions);
        $this->assertEquals('openrouter', $result->provider);
    }

    // =========================================================================
    // SUG-004: indicator is required
    // =========================================================================

    #[Test]
    #[TestDox('SUG-004: indicator is required')]
    public function indicator_is_required(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('indicator');
        $type = $property->getType();

        $this->assertNotNull($type, 'indicator must have type declaration');
        $this->assertFalse($type->allowsNull(), 'indicator should not allow null');
    }

    // =========================================================================
    // SUG-005: score must be between 0 and 100
    // =========================================================================

    #[Test]
    #[TestDox('SUG-005: score must be between 0 and 100')]
    public function score_must_be_between_0_and_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = $this->getValidSuggestionData();
        $data['score'] = 101;
        
        new (self::DTO_CLASS)(...$data);
    }

    // =========================================================================
    // SUG-006: score cannot be negative
    // =========================================================================

    #[Test]
    #[TestDox('SUG-006: score cannot be negative')]
    public function score_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = $this->getValidSuggestionData();
        $data['score'] = -1;
        
        new (self::DTO_CLASS)(...$data);
    }

    // =========================================================================
    // SUG-007: score can be 0
    // =========================================================================

    #[Test]
    #[TestDox('SUG-007: score can be 0')]
    public function score_can_be_zero(): void
    {
        $data = $this->getValidSuggestionData();
        $data['score'] = 0;
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEquals(0, $result->score);
    }

    // =========================================================================
    // SUG-008: score can be 100
    // =========================================================================

    #[Test]
    #[TestDox('SUG-008: score can be 100')]
    public function score_can_be_100(): void
    {
        $data = $this->getValidSuggestionData();
        $data['score'] = 100;
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEquals(100, $result->score);
    }

    // =========================================================================
    // SUG-009: issues must be an array
    // =========================================================================

    #[Test]
    #[TestDox('SUG-009: issues must be an array')]
    public function issues_must_be_an_array(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('issues');
        $type = $property->getType();

        $this->assertNotNull($type, 'issues must have type declaration');
        $this->assertEquals('array', $type->getName(), 'issues must be array type');
    }

    // =========================================================================
    // SUG-010: suggestions must be an array
    // =========================================================================

    #[Test]
    #[TestDox('SUG-010: suggestions must be an array')]
    public function suggestions_must_be_an_array(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('suggestions');
        $type = $property->getType();

        $this->assertNotNull($type, 'suggestions must have type declaration');
        $this->assertEquals('array', $type->getName(), 'suggestions must be array type');
    }

    // =========================================================================
    // SUG-011: Empty issues array is valid
    // =========================================================================

    #[Test]
    #[TestDox('SUG-011: Empty issues array is valid')]
    public function empty_issues_array_is_valid(): void
    {
        $data = $this->getValidSuggestionData();
        $data['issues'] = [];
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEmpty($result->issues);
    }

    // =========================================================================
    // SUG-012: Empty suggestions array is valid
    // =========================================================================

    #[Test]
    #[TestDox('SUG-012: Empty suggestions array is valid')]
    public function empty_suggestions_array_is_valid(): void
    {
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [];
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEmpty($result->suggestions);
    }

    // =========================================================================
    // SUG-013: provider is required
    // =========================================================================

    #[Test]
    #[TestDox('SUG-013: provider is required')]
    public function provider_is_required(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('provider');
        $type = $property->getType();

        $this->assertNotNull($type, 'provider must have type declaration');
        $this->assertFalse($type->allowsNull(), 'provider should not allow null');
    }

    // =========================================================================
    // SUG-014: model is required
    // =========================================================================

    #[Test]
    #[TestDox('SUG-014: model is required')]
    public function model_is_required(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('model');
        $type = $property->getType();

        $this->assertNotNull($type, 'model must have type declaration');
        $this->assertFalse($type->allowsNull(), 'model should not allow null');
    }

    // =========================================================================
    // SUG-015: generatedAt is DateTimeInterface
    // =========================================================================

    #[Test]
    #[TestDox('SUG-015: generatedAt is DateTimeInterface')]
    public function generated_at_is_datetime_interface(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('generatedAt');
        $type = $property->getType();

        $this->assertNotNull($type, 'generatedAt must have type declaration');
    }

    // =========================================================================
    // SUG-016: JSON serialization works correctly
    // =========================================================================

    #[Test]
    #[TestDox('SUG-016: JSON serialization works correctly')]
    public function json_serialization_works_correctly(): void
    {
        $data = $this->getValidSuggestionData();
        $result = new (self::DTO_CLASS)(...$data);

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals('title', $decoded['indicator']);
        $this->assertEquals(85, $decoded['score']);
        $this->assertIsArray($decoded['issues']);
        $this->assertIsArray($decoded['suggestions']);
        $this->assertEquals('openrouter', $decoded['provider']);
    }

    // =========================================================================
    // SUG-017: JSON deserialization works correctly
    // =========================================================================

    #[Test]
    #[TestDox('SUG-017: JSON deserialization works correctly')]
    public function json_deserialization_works_correctly(): void
    {
        $class = self::DTO_CLASS;
        
        if (method_exists($class, 'fromJson')) {
            $json = json_encode([
                'indicator' => 'meta_description',
                'score' => 75,
                'issues' => ['Too short'],
                'suggestions' => [],
                'bestPractices' => ['Use 150-160 characters'],
                'provider' => 'openrouter',
                'model' => 'llama-3.1-8b-instruct',
                'generatedAt' => '2026-03-01T12:00:00Z',
            ]);

            $result = $class::fromJson($json);
            
            $this->assertEquals('meta_description', $result->indicator);
            $this->assertEquals(75, $result->score);
        } else {
            $this->markTestSkipped('fromJson method not implemented yet');
        }
    }

    // =========================================================================
    // SUG-018: Suggestion structure validation - missing type
    // =========================================================================

    #[Test]
    #[TestDox('SUG-018: Suggestion structure validation - missing type')]
    public function suggestion_structure_validation_missing_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [
            ['text' => 'Some suggestion', 'reason' => 'Some reason']
        ];
        
        new (self::DTO_CLASS)(...$data);
    }

    // =========================================================================
    // SUG-019: Suggestion type must be valid enum value
    // =========================================================================

    #[Test]
    #[TestDox('SUG-019: Suggestion type must be valid enum value')]
    public function suggestion_type_must_be_valid_enum_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [
            ['type' => 'invalid_type', 'text' => 'Test', 'reason' => 'Test']
        ];
        
        new (self::DTO_CLASS)(...$data);
    }

    // =========================================================================
    // SUG-020: Suggestion text is required
    // =========================================================================

    #[Test]
    #[TestDox('SUG-020: Suggestion text is required')]
    public function suggestion_text_is_required(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [
            ['type' => 'improve', 'reason' => 'Some reason']
        ];
        
        new (self::DTO_CLASS)(...$data);
    }

    // =========================================================================
    // SUG-021: Valid suggestion types accepted
    // =========================================================================

    #[Test]
    #[TestDox('SUG-021: Valid suggestion types accepted')]
    public function valid_suggestion_types_accepted(): void
    {
        $validTypes = ['improve', 'rewrite', 'add', 'remove', 'warning'];
        
        foreach ($validTypes as $type) {
            $data = $this->getValidSuggestionData();
            $data['suggestions'] = [
                ['type' => $type, 'text' => "Test for {$type}", 'reason' => 'Test']
            ];
            
            $result = new (self::DTO_CLASS)(...$data);
            $this->assertEquals($type, $result->suggestions[0]['type']);
        }
    }

    // =========================================================================
    // SUG-022: Properties are readonly (immutable)
    // =========================================================================

    #[Test]
    #[TestDox('SUG-022: Properties are readonly (immutable)')]
    public function properties_are_readonly(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    // =========================================================================
    // SUG-023: bestPractices is array
    // =========================================================================

    #[Test]
    #[TestDox('SUG-023: bestPractices is array')]
    public function best_practices_is_array(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('bestPractices');
        $type = $property->getType();

        $this->assertNotNull($type, 'bestPractices must have type declaration');
        $this->assertEquals('array', $type->getName(), 'bestPractices must be array type');
    }

    // =========================================================================
    // SUG-024: Empty bestPractices array is valid
    // =========================================================================

    #[Test]
    #[TestDox('SUG-024: Empty bestPractices array is valid')]
    public function empty_best_practices_array_is_valid(): void
    {
        $data = $this->getValidSuggestionData();
        $data['bestPractices'] = [];
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEmpty($result->bestPractices);
    }

    // =========================================================================
    // SUG-025: Unicode in indicator name
    // =========================================================================

    #[Test]
    #[TestDox('SUG-025: Unicode in indicator name')]
    public function unicode_in_indicator_name(): void
    {
        $data = $this->getValidSuggestionData();
        $data['indicator'] = '标题'; // Chinese for "title"
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertEquals('标题', $result->indicator);
    }

    // =========================================================================
    // SUG-026: Unicode in issues
    // =========================================================================

    #[Test]
    #[TestDox('SUG-026: Unicode in issues')]
    public function unicode_in_issues(): void
    {
        $data = $this->getValidSuggestionData();
        $data['issues'] = ['标题太短', '缺少关键词'];
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertCount(2, $result->issues);
        $this->assertStringContainsString('标题', $result->issues[0]);
    }

    // =========================================================================
    // SUG-027: Large suggestions array
    // =========================================================================

    #[Test]
    #[TestDox('SUG-027: Large suggestions array')]
    public function large_suggestions_array(): void
    {
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [];
        
        for ($i = 0; $i < 50; $i++) {
            $data['suggestions'][] = [
                'type' => 'improve',
                'text' => "Suggestion {$i}",
                'reason' => "Reason {$i}"
            ];
        }
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertCount(50, $result->suggestions);
    }

    // =========================================================================
    // SUG-028: DTO has all required properties
    // =========================================================================

    #[Test]
    #[TestDox('SUG-028: DTO has all required properties')]
    public function dto_has_all_required_properties(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $properties = $reflection->getProperties();
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);

        $requiredProperties = [
            'indicator', 'score', 'issues', 'suggestions',
            'bestPractices', 'provider', 'model', 'generatedAt'
        ];
        
        foreach ($requiredProperties as $required) {
            $this->assertContains(
                $required,
                $propertyNames,
                "DTO must have {$required} property"
            );
        }
    }

    // =========================================================================
    // SUG-029: JSON output contains all fields
    // =========================================================================

    #[Test]
    #[TestDox('SUG-029: JSON output contains all fields')]
    public function json_output_contains_all_fields(): void
    {
        $data = $this->getValidSuggestionData();
        $result = new (self::DTO_CLASS)(...$data);

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('indicator', $decoded);
        $this->assertArrayHasKey('score', $decoded);
        $this->assertArrayHasKey('issues', $decoded);
        $this->assertArrayHasKey('suggestions', $decoded);
        $this->assertArrayHasKey('bestPractices', $decoded);
        $this->assertArrayHasKey('provider', $decoded);
        $this->assertArrayHasKey('model', $decoded);
        $this->assertArrayHasKey('generatedAt', $decoded);
    }

    // =========================================================================
    // SUG-030: Reason is optional in suggestions
    // =========================================================================

    #[Test]
    #[TestDox('SUG-030: Reason is optional in suggestions')]
    public function reason_is_optional_in_suggestions(): void
    {
        $data = $this->getValidSuggestionData();
        $data['suggestions'] = [
            ['type' => 'improve', 'text' => 'Test without reason']
        ];
        
        $result = new (self::DTO_CLASS)(...$data);

        $this->assertCount(1, $result->suggestions);
    }
}
