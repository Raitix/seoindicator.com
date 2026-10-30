<?php

namespace Tests\Unit\Services\Audit\DTOs;

use App\Services\Audit\DTOs\IssueFinding;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit')]
#[Group('services')]
#[Group('audit')]
#[Group('dtos')]
class IssueFindingTest extends TestCase
{
    // =========================================================================
    // DTO: DTO Structure Tests
    // =========================================================================

    #[Test]
    public function dto_001_create_with_all_fields(): void
    {
        $finding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Page is missing a title tag',
            recommendation: 'Add a descriptive title tag',
            context: ['url' => 'https://example.com', 'line' => 5]
        );
        
        $this->assertEquals('missing_title_tag', $finding->issueType);
        $this->assertEquals('critical', $finding->severity);
        $this->assertEquals('Page is missing a title tag', $finding->description);
        $this->assertEquals('Add a descriptive title tag', $finding->recommendation);
        $this->assertEquals(['url' => 'https://example.com', 'line' => 5], $finding->context);
    }

    #[Test]
    public function dto_002_create_with_minimal_fields(): void
    {
        $finding = new IssueFinding(
            issueType: 'test_issue',
            severity: 'notice',
            description: 'Test description',
            recommendation: 'Test recommendation'
        );
        
        $this->assertEquals('test_issue', $finding->issueType);
        $this->assertEquals('notice', $finding->severity);
        $this->assertEquals('Test description', $finding->description);
        $this->assertEquals('Test recommendation', $finding->recommendation);
        $this->assertEquals([], $finding->context);
    }

    #[Test]
    public function dto_007_context_defaults_to_empty_array(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec'
        );
        
        $this->assertEquals([], $finding->context);
    }

    // =========================================================================
    // VAL: Validation Tests
    // =========================================================================

    #[Test]
    public function val_001_invalid_severity_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('severity');
        
        new IssueFinding(
            issueType: 'test',
            severity: 'invalid_severity',
            description: 'Desc',
            recommendation: 'Rec'
        );
    }

    #[Test]
    public function val_002_empty_issue_type_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('issueType');
        
        new IssueFinding(
            issueType: '',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec'
        );
    }

    #[Test]
    public function val_003_empty_description_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('description');
        
        new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: '',
            recommendation: 'Rec'
        );
    }

    #[Test]
    public function val_004_empty_recommendation_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('recommendation');
        
        new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: ''
        );
    }

    #[Test]
    public function val_005_invalid_context_type_throws(): void
    {
        $this->expectException(\TypeError::class);
        
        new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: 'not an array'
        );
    }

    #[Test]
    public function val_006_very_long_description_truncated(): void
    {
        $longDesc = str_repeat('a', 1500);
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: $longDesc,
            recommendation: 'Rec'
        );
        
        $this->assertLessThanOrEqual(1000, strlen($finding->description));
    }

    #[Test]
    public function val_007_very_long_recommendation_truncated(): void
    {
        $longRec = str_repeat('b', 600);
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: $longRec
        );
        
        $this->assertLessThanOrEqual(500, strlen($finding->recommendation));
    }

    #[Test]
    public function val_008_context_with_nested_objects(): void
    {
        $nestedContext = [
            'level1' => [
                'level2' => [
                    'level3' => 'value'
                ]
            ]
        ];
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: $nestedContext
        );
        
        $this->assertEquals($nestedContext, $finding->context);
    }

    #[Test]
    public function val_009_context_with_special_characters(): void
    {
        $context = [
            'special' => '<script>alert("xss")</script>',
            'unicode' => '日本語',
            'emoji' => '😀🎉'
        ];
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: $context
        );
        
        $this->assertEquals($context, $finding->context);
    }

    #[Test]
    public function val_010_from_array_factory_method(): void
    {
        $data = [
            'issueType' => 'missing_title_tag',
            'severity' => 'critical',
            'description' => 'Missing title',
            'recommendation' => 'Add title',
            'context' => ['url' => 'https://example.com']
        ];
        
        $finding = IssueFinding::fromArray($data);
        
        $this->assertEquals('missing_title_tag', $finding->issueType);
        $this->assertEquals('critical', $finding->severity);
        $this->assertEquals('Missing title', $finding->description);
        $this->assertEquals('Add title', $finding->recommendation);
        $this->assertEquals(['url' => 'https://example.com'], $finding->context);
    }

    // =========================================================================
    // ACC: Accessor Tests
    // =========================================================================

    #[Test]
    public function acc_001_get_issue_type_returns_string(): void
    {
        $finding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Desc',
            recommendation: 'Rec'
        );
        
        $this->assertEquals('missing_title_tag', $finding->issueType);
        $this->assertIsString($finding->issueType);
    }

    #[Test]
    public function acc_002_get_severity_returns_string(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec'
        );
        
        $this->assertEquals('warning', $finding->severity);
        $this->assertIsString($finding->severity);
    }

    #[Test]
    public function acc_003_get_description_returns_string(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Test Description',
            recommendation: 'Rec'
        );
        
        $this->assertEquals('Test Description', $finding->description);
        $this->assertIsString($finding->description);
    }

    #[Test]
    public function acc_004_get_recommendation_returns_string(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Test Recommendation'
        );
        
        $this->assertEquals('Test Recommendation', $finding->recommendation);
        $this->assertIsString($finding->recommendation);
    }

    #[Test]
    public function acc_005_get_context_returns_array(): void
    {
        $context = ['key' => 'value', 'nested' => ['a' => 1]];
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: $context
        );
        
        $this->assertEquals($context, $finding->context);
        $this->assertIsArray($finding->context);
    }

    #[Test]
    public function acc_006_get_context_value_with_key(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['url' => 'https://example.com', 'line' => 42]
        );
        
        $this->assertEquals('https://example.com', $finding->getContextValue('url'));
        $this->assertEquals(42, $finding->getContextValue('line'));
        $this->assertNull($finding->getContextValue('nonexistent'));
    }

    #[Test]
    public function acc_007_has_context_returns_boolean(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['exists' => 'value']
        );
        
        $this->assertTrue($finding->hasContext('exists'));
        $this->assertFalse($finding->hasContext('missing'));
    }

    #[Test]
    public function acc_008_to_array_returns_all_data(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $array = $finding->toArray();
        
        $this->assertEquals([
            'issueType' => 'test',
            'severity' => 'warning',
            'description' => 'Desc',
            'recommendation' => 'Rec',
            'context' => ['key' => 'value']
        ], $array);
    }

    #[Test]
    public function acc_009_json_serialize_returns_array(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $json = json_encode($finding);
        $decoded = json_decode($json, true);
        
        $this->assertEquals('test', $decoded['issueType']);
        $this->assertEquals('warning', $decoded['severity']);
    }

    #[Test]
    public function acc_010_to_string_returns_description(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Test Issue Description',
            recommendation: 'Rec'
        );
        
        $this->assertEquals('Test Issue Description', (string) $finding);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_context_with_null_value(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['nullable' => null]
        );
        
        $this->assertNull($finding->getContextValue('nullable'));
    }

    #[Test]
    public function edge_002_context_with_boolean(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['flag' => true, 'other' => false]
        );
        
        $this->assertTrue($finding->getContextValue('flag'));
        $this->assertFalse($finding->getContextValue('other'));
    }

    #[Test]
    public function edge_003_context_with_numeric(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['count' => 42, 'price' => 19.99]
        );
        
        $this->assertEquals(42, $finding->getContextValue('count'));
        $this->assertEquals(19.99, $finding->getContextValue('price'));
    }

    #[Test]
    public function edge_004_unicode_in_description(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: '日本語の説明 - العربية - 中文',
            recommendation: 'Rec'
        );
        
        $this->assertEquals('日本語の説明 - العربية - 中文', $finding->description);
    }

    #[Test]
    public function edge_005_emoji_in_recommendation(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Fix this! 🎉 ✅ 🚀'
        );
        
        $this->assertEquals('Fix this! 🎉 ✅ 🚀', $finding->recommendation);
    }

    #[Test]
    public function edge_006_html_in_description(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: '<script>alert("xss")</script>Normal text',
            recommendation: 'Rec'
        );
        
        // Should be stored as-is, escaping happens at output
        $this->assertStringContainsString('<script>', $finding->description);
    }

    #[Test]
    public function edge_007_very_large_context_truncated(): void
    {
        $largeContext = [
            'data' => str_repeat('x', 15000)
        ];
        
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: $largeContext
        );
        
        // Context should be truncated to 10KB
        $serialized = json_encode($finding->context);
        $this->assertLessThanOrEqual(10240, strlen($serialized));
    }

    #[Test]
    public function edge_009_clone_creates_new_instance(): void
    {
        $original = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $clone = clone $original;
        
        $this->assertEquals($original->issueType, $clone->issueType);
        $this->assertNotSame($original, $clone);
    }

    #[Test]
    public function edge_010_equals_comparison(): void
    {
        $finding1 = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $finding2 = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $finding3 = new IssueFinding(
            issueType: 'different',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec'
        );
        
        $this->assertTrue($finding1->equals($finding2));
        $this->assertFalse($finding1->equals($finding3));
    }

    // =========================================================================
    // SER: Serialization Tests
    // =========================================================================

    #[Test]
    public function ser_001_json_encode_decode(): void
    {
        $original = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $json = json_encode($original);
        $decoded = json_decode($json, true);
        $restored = IssueFinding::fromArray($decoded);
        
        $this->assertTrue($original->equals($restored));
    }

    #[Test]
    public function ser_002_to_array_then_from_array(): void
    {
        $original = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $array = $original->toArray();
        $restored = IssueFinding::fromArray($array);
        
        $this->assertTrue($original->equals($restored));
    }

    #[Test]
    public function ser_003_serialization_for_cache(): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $serialized = serialize($finding);
        $unserialized = unserialize($serialized);
        
        $this->assertTrue($finding->equals($unserialized));
    }

    #[Test]
    public function ser_004_deserialization_from_cache(): void
    {
        $original = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        // Simulate cache storage
        $cached = serialize($original);
        $restored = unserialize($cached);
        
        $this->assertInstanceOf(IssueFinding::class, $restored);
        $this->assertEquals('test', $restored->issueType);
    }

    #[Test]
    public function ser_005_database_storage_format(): void
    {
        $finding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Page is missing title',
            recommendation: 'Add a title tag',
            context: ['url' => 'https://example.com', 'line' => 5]
        );
        
        $dbFormat = $finding->toArray();
        
        // Should be JSON-compatible
        $json = json_encode($dbFormat);
        $this->assertJson($json);
        
        // Should have all required fields
        $this->assertArrayHasKey('issueType', $dbFormat);
        $this->assertArrayHasKey('severity', $dbFormat);
        $this->assertArrayHasKey('description', $dbFormat);
        $this->assertArrayHasKey('recommendation', $dbFormat);
        $this->assertArrayHasKey('context', $dbFormat);
    }

    // =========================================================================
    // Data Providers
    // =========================================================================

    public static function severityProvider(): array
    {
        return [
            'critical' => ['critical'],
            'warning' => ['warning'],
            'notice' => ['notice'],
        ];
    }

    #[Test]
    #[DataProvider('severityProvider')]
    public function all_valid_severities_accepted(string $severity): void
    {
        $finding = new IssueFinding(
            issueType: 'test',
            severity: $severity,
            description: 'Desc',
            recommendation: 'Rec'
        );
        
        $this->assertEquals($severity, $finding->severity);
    }
}
