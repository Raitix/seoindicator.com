<?php

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use ReflectionClass;

/**
 * Test suite for AIProviderInterface contract.
 * 
 * AC-1: AIProviderInterface Created
 * 
 * @group ai
 * @group task-21-1
 */
class AIProviderInterfaceTest extends TestCase
{
    /**
     * Path to the interface file (TDD - may not exist yet)
     */
    private const INTERFACE_PATH = 'app/Services/AI/Contracts/AIProviderInterface.php';
    private const INTERFACE_CLASS = 'App\Services\AI\Contracts\AIProviderInterface';

    // =========================================================================
    // IFC-001: Interface file exists at correct path
    // =========================================================================

    #[Test]
    #[TestDox('IFC-001: Interface file exists at correct path')]
    public function interface_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path(self::INTERFACE_PATH),
            'AIProviderInterface should exist at app/Services/AI/Contracts/AIProviderInterface.php'
        );
    }

    // =========================================================================
    // IFC-002: Interface is defined correctly
    // =========================================================================

    #[Test]
    #[TestDox('IFC-002: Interface is defined correctly')]
    public function interface_is_defined_correctly(): void
    {
        $this->assertTrue(
            interface_exists(self::INTERFACE_CLASS),
            'AIProviderInterface should be a valid interface'
        );
    }

    // =========================================================================
    // IFC-003: Interface defines generate method
    // =========================================================================

    #[Test]
    #[TestDox('IFC-003: Interface defines generate method')]
    public function interface_defines_generate_method(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('generate'),
            'Interface must define generate() method'
        );
    }

    // =========================================================================
    // IFC-004: generate method has correct signature
    // =========================================================================

    #[Test]
    #[TestDox('IFC-004: generate method has correct signature')]
    public function generate_method_has_correct_signature(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('generate');
        $parameters = $method->getParameters();
        
        // Should have two parameters: prompt (string) and options (array, optional)
        $this->assertCount(2, $parameters, 'generate() should have 2 parameters');
        
        // First parameter: prompt (string, required)
        $this->assertEquals('prompt', $parameters[0]->getName());
        $this->assertFalse($parameters[0]->isOptional());
        
        // Second parameter: options (array, optional with default [])
        $this->assertEquals('options', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isOptional());
    }

    // =========================================================================
    // IFC-005: generate method returns AIResponse
    // =========================================================================

    #[Test]
    #[TestDox('IFC-005: generate method returns AIResponse')]
    public function generate_method_returns_ai_response(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('generate');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'generate() must have a return type');
        $this->assertEquals('AIResponse', $returnType->getName(), 'generate() must return AIResponse');
    }

    // =========================================================================
    // IFC-006: Interface defines isAvailable method
    // =========================================================================

    #[Test]
    #[TestDox('IFC-006: Interface defines isAvailable method')]
    public function interface_defines_is_available_method(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('isAvailable'),
            'Interface must define isAvailable() method'
        );
    }

    // =========================================================================
    // IFC-007: isAvailable method returns bool
    // =========================================================================

    #[Test]
    #[TestDox('IFC-007: isAvailable method returns bool')]
    public function is_available_method_returns_bool(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('isAvailable');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'isAvailable() must have a return type');
        $this->assertEquals('bool', $returnType->getName(), 'isAvailable() must return bool');
    }

    // =========================================================================
    // IFC-008: Interface defines getName method
    // =========================================================================

    #[Test]
    #[TestDox('IFC-008: Interface defines getName method')]
    public function interface_defines_get_name_method(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('getName'),
            'Interface must define getName() method'
        );
    }

    // =========================================================================
    // IFC-009: getName method returns string
    // =========================================================================

    #[Test]
    #[TestDox('IFC-009: getName method returns string')]
    public function get_name_method_returns_string(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('getName');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'getName() must have a return type');
        $this->assertEquals('string', $returnType->getName(), 'getName() must return string');
    }

    // =========================================================================
    // IFC-010: getName method has no parameters
    // =========================================================================

    #[Test]
    #[TestDox('IFC-010: getName method has no parameters')]
    public function get_name_method_has_no_parameters(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('getName');
        
        $this->assertCount(0, $method->getParameters(), 'getName() should have no parameters');
    }

    // =========================================================================
    // IFC-011: isAvailable method has no parameters
    // =========================================================================

    #[Test]
    #[TestDox('IFC-011: isAvailable method has no parameters')]
    public function is_available_method_has_no_parameters(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $method = $reflection->getMethod('isAvailable');
        
        $this->assertCount(0, $method->getParameters(), 'isAvailable() should have no parameters');
    }

    // =========================================================================
    // IFC-012: All methods have PHPDoc documentation
    // =========================================================================

    #[Test]
    #[TestDox('IFC-012: All methods have PHPDoc documentation')]
    public function all_methods_have_phpdoc_documentation(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        $methods = ['generate', 'isAvailable', 'getName'];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            
            $this->assertNotFalse(
                $docComment,
                "Method {$methodName}() must have PHPDoc documentation"
            );
            $this->assertStringContainsString(
                '@',
                $docComment,
                "Method {$methodName}() PHPDoc should contain annotations"
            );
        }
    }

    // =========================================================================
    // IFC-013: Interface has proper namespace
    // =========================================================================

    #[Test]
    #[TestDox('IFC-013: Interface has proper namespace')]
    public function interface_has_proper_namespace(): void
    {
        $reflection = new ReflectionClass(self::INTERFACE_CLASS);
        
        $this->assertEquals(
            'App\Services\AI\Contracts',
            $reflection->getNamespaceName(),
            'Interface should be in App\Services\AI\Contracts namespace'
        );
    }

    // =========================================================================
    // IFC-014: Mock implementation satisfies contract (integration test)
    // =========================================================================

    #[Test]
    #[TestDox('IFC-014: Mock implementation satisfies contract')]
    public function mock_implementation_satisfies_contract(): void
    {
        // This test verifies that a mock implementing the interface works
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        
        $mockProvider->expects($this->once())
            ->method('getName')
            ->willReturn('test-provider');
        
        $mockProvider->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->assertEquals('test-provider', $mockProvider->getName());
        $this->assertTrue($mockProvider->isAvailable());
    }

    // =========================================================================
    // IFC-015: generate method throws exception on failure (contract behavior)
    // =========================================================================

    #[Test]
    #[TestDox('IFC-015: generate method can throw exception on failure')]
    public function generate_method_can_throw_exception_on_failure(): void
    {
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        
        // Verify the interface allows throwing exceptions
        $mockProvider->method('generate')
            ->willThrowException(new \Exception('Provider unavailable'));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Provider unavailable');
        
        $mockProvider->generate('test prompt');
    }
}
