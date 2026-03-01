<?php

namespace Tests\Unit\Services\AI\DTOs;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Test suite for AIResponse DTO.
 * 
 * AC-2: AIResponse DTO Created
 * 
 * @group ai
 * @group task-21-1
 */
class AIResponseTest extends TestCase
{
    private const DTO_CLASS = 'App\Services\AI\DTOs\AIResponse';

    // =========================================================================
    // RES-001: DTO file exists at correct path
    // =========================================================================

    #[Test]
    #[TestDox('RES-001: DTO file exists at correct path')]
    public function dto_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Services/AI/DTOs/AIResponse.php'),
            'AIResponse should exist at app/Services/AI/DTOs/AIResponse.php'
        );
    }

    // =========================================================================
    // RES-002: DTO class exists and is instantiable
    // =========================================================================

    #[Test]
    #[TestDox('RES-002: DTO class exists and is instantiable')]
    public function dto_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(self::DTO_CLASS), 'AIResponse class should exist');
    }

    // =========================================================================
    // RES-003: Create success response with all properties
    // =========================================================================

    #[Test]
    #[TestDox('RES-003: Create success response with all properties')]
    public function create_success_response_with_all_properties(): void
    {
        $response = new (self::DTO_CLASS)(
            success: true,
            content: '{"score": 85}',
            tokensUsed: 150,
            latencyMs: 1250,
            errorMessage: null
        );

        $this->assertTrue($response->success);
        $this->assertEquals('{"score": 85}', $response->content);
        $this->assertEquals(150, $response->tokensUsed);
        $this->assertEquals(1250, $response->latencyMs);
        $this->assertNull($response->errorMessage);
    }

    // =========================================================================
    // RES-004: Create error response
    // =========================================================================

    #[Test]
    #[TestDox('RES-004: Create error response')]
    public function create_error_response(): void
    {
        $response = new (self::DTO_CLASS)(
            success: false,
            content: null,
            tokensUsed: null,
            latencyMs: 30000,
            errorMessage: 'Connection timeout'
        );

        $this->assertFalse($response->success);
        $this->assertNull($response->content);
        $this->assertEquals('Connection timeout', $response->errorMessage);
    }

    // =========================================================================
    // RES-005: Properties are readonly (immutable)
    // =========================================================================

    #[Test]
    #[TestDox('RES-005: Properties are readonly (immutable)')]
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
    // RES-006: Factory method success() creates valid response
    // =========================================================================

    #[Test]
    #[TestDox('RES-006: Factory method success() creates valid response')]
    public function factory_method_success_creates_valid_response(): void
    {
        $class = self::DTO_CLASS;
        
        $response = $class::success(
            content: '{"score": 90}',
            tokensUsed: 200,
            latencyMs: 1500
        );

        $this->assertTrue($response->success);
        $this->assertEquals('{"score": 90}', $response->content);
        $this->assertEquals(200, $response->tokensUsed);
        $this->assertEquals(1500, $response->latencyMs);
        $this->assertNull($response->errorMessage);
    }

    // =========================================================================
    // RES-007: Factory method error() creates valid response
    // =========================================================================

    #[Test]
    #[TestDox('RES-007: Factory method error() creates valid response')]
    public function factory_method_error_creates_valid_response(): void
    {
        $class = self::DTO_CLASS;
        
        $response = $class::error('API rate limit exceeded');

        $this->assertFalse($response->success);
        $this->assertEquals('API rate limit exceeded', $response->errorMessage);
        $this->assertNull($response->content);
        $this->assertNull($response->tokensUsed);
    }

    // =========================================================================
    // RES-008: JSON serialization works correctly
    // =========================================================================

    #[Test]
    #[TestDox('RES-008: JSON serialization works correctly')]
    public function json_serialization_works_correctly(): void
    {
        $response = new (self::DTO_CLASS)(
            success: true,
            content: '{"test": "data"}',
            tokensUsed: 100,
            latencyMs: 500,
            errorMessage: null
        );

        $json = json_encode($response);
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['success']);
        $this->assertEquals('{"test": "data"}', $decoded['content']);
        $this->assertEquals(100, $decoded['tokensUsed']);
        $this->assertEquals(500, $decoded['latencyMs']);
    }

    // =========================================================================
    // RES-009: JSON deserialization works correctly
    // =========================================================================

    #[Test]
    #[TestDox('RES-009: JSON deserialization works correctly')]
    public function json_deserialization_works_correctly(): void
    {
        $class = self::DTO_CLASS;
        
        $json = json_encode([
            'success' => true,
            'content' => '{"key": "value"}',
            'tokensUsed' => 75,
            'latencyMs' => 300,
            'errorMessage' => null
        ]);

        if (method_exists($class, 'fromJson')) {
            $response = $class::fromJson($json);
            
            $this->assertTrue($response->success);
            $this->assertEquals('{"key": "value"}', $response->content);
        } else {
            $this->markTestSkipped('fromJson method not implemented yet');
        }
    }

    // =========================================================================
    // RES-010: tokensUsed must be non-negative
    // =========================================================================

    #[Test]
    #[TestDox('RES-010: tokensUsed must be non-negative')]
    public function tokens_used_must_be_non_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new (self::DTO_CLASS)(
            success: true,
            content: 'test',
            tokensUsed: -1,
            latencyMs: 100,
            errorMessage: null
        );
    }

    // =========================================================================
    // RES-011: latencyMs must be non-negative
    // =========================================================================

    #[Test]
    #[TestDox('RES-011: latencyMs must be non-negative')]
    public function latency_ms_must_be_non_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new (self::DTO_CLASS)(
            success: true,
            content: 'test',
            tokensUsed: 100,
            latencyMs: -1,
            errorMessage: null
        );
    }

    // =========================================================================
    // RES-012: Nullable properties accept null
    // =========================================================================

    #[Test]
    #[TestDox('RES-012: Nullable properties accept null')]
    public function nullable_properties_accept_null(): void
    {
        $response = new (self::DTO_CLASS)(
            success: true,
            content: 'test',
            tokensUsed: null,
            latencyMs: null,
            errorMessage: null
        );

        $this->assertNull($response->tokensUsed);
        $this->assertNull($response->latencyMs);
    }

    // =========================================================================
    // RES-013: Success response with minimal data
    // =========================================================================

    #[Test]
    #[TestDox('RES-013: Success response with minimal data')]
    public function success_response_with_minimal_data(): void
    {
        $class = self::DTO_CLASS;
        
        $response = $class::success('simple content');

        $this->assertTrue($response->success);
        $this->assertEquals('simple content', $response->content);
    }

    // =========================================================================
    // RES-014: Error response with latency tracking
    // =========================================================================

    #[Test]
    #[TestDox('RES-014: Error response with latency tracking')]
    public function error_response_with_latency_tracking(): void
    {
        $class = self::DTO_CLASS;
        
        if (method_exists($class, 'error')) {
            $reflection = new \ReflectionMethod($class, 'error');
            $params = $reflection->getParameters();
            
            if (count($params) >= 2) {
                $response = $class::error('Error message', 5000);
                $this->assertEquals(5000, $response->latencyMs);
            } else {
                // Basic error method
                $response = $class::error('Error message');
                $this->assertFalse($response->success);
            }
        }
    }

    // =========================================================================
    // RES-015: Large content handling
    // =========================================================================

    #[Test]
    #[TestDox('RES-015: Large content handling')]
    public function large_content_handling(): void
    {
        $largeContent = str_repeat('x', 100000);
        
        $response = new (self::DTO_CLASS)(
            success: true,
            content: $largeContent,
            tokensUsed: 10000,
            latencyMs: 5000,
            errorMessage: null
        );

        $this->assertEquals($largeContent, $response->content);
    }

    // =========================================================================
    // RES-016: Unicode content handling
    // =========================================================================

    #[Test]
    #[TestDox('RES-016: Unicode content handling')]
    public function unicode_content_handling(): void
    {
        $unicodeContent = json_encode(['title' => '中文标题', 'emoji' => '😀🎉']);
        
        $response = new (self::DTO_CLASS)(
            success: true,
            content: $unicodeContent,
            tokensUsed: 50,
            latencyMs: 100,
            errorMessage: null
        );

        $this->assertStringContainsString('\u4e2d\u6587\u6807\u9898', $response->content);
    }

    // =========================================================================
    // RES-017: DTO has all required properties
    // =========================================================================

    #[Test]
    #[TestDox('RES-017: DTO has all required properties')]
    public function dto_has_all_required_properties(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $properties = $reflection->getProperties();
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);

        $requiredProperties = ['success', 'content', 'tokensUsed', 'latencyMs', 'errorMessage'];
        
        foreach ($requiredProperties as $required) {
            $this->assertContains(
                $required,
                $propertyNames,
                "DTO must have {$required} property"
            );
        }
    }

    // =========================================================================
    // RES-018: success property is boolean
    // =========================================================================

    #[Test]
    #[TestDox('RES-018: success property is boolean')]
    public function success_property_is_boolean(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('success');
        $type = $property->getType();

        $this->assertNotNull($type, 'success property must have type declaration');
        $this->assertEquals('bool', $type->getName(), 'success property must be bool');
    }

    // =========================================================================
    // RES-019: content property is nullable string
    // =========================================================================

    #[Test]
    #[TestDox('RES-019: content property is nullable string')]
    public function content_property_is_nullable_string(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('content');
        $type = $property->getType();

        $this->assertNotNull($type, 'content property must have type declaration');
        $this->assertTrue($type->allowsNull(), 'content property should allow null');
    }

    // =========================================================================
    // RES-020: errorMessage property is nullable string
    // =========================================================================

    #[Test]
    #[TestDox('RES-020: errorMessage property is nullable string')]
    public function error_message_property_is_nullable_string(): void
    {
        $reflection = new \ReflectionClass(self::DTO_CLASS);
        $property = $reflection->getProperty('errorMessage');
        $type = $property->getType();

        $this->assertNotNull($type, 'errorMessage property must have type declaration');
        $this->assertTrue($type->allowsNull(), 'errorMessage property should allow null');
    }
}
