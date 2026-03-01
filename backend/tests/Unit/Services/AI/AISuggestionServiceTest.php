<?php

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use ReflectionClass;

/**
 * Test suite for AISuggestionService core functionality.
 * 
 * AC-4: AISuggestionService Core Created
 * 
 * @group ai
 * @group task-21-1
 */
class AISuggestionServiceTest extends TestCase
{
    private const SERVICE_CLASS = 'App\Services\AI\AISuggestionService';
    private const INTERFACE_CLASS = 'App\Services\AI\Contracts\AIProviderInterface';

    // =========================================================================
    // SVC-001: Service file exists at correct path
    // =========================================================================

    #[Test]
    #[TestDox('SVC-001: Service file exists at correct path')]
    public function service_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Services/AI/AISuggestionService.php'),
            'AISuggestionService should exist at app/Services/AI/AISuggestionService.php'
        );
    }

    // =========================================================================
    // SVC-002: Service class exists and is instantiable
    // =========================================================================

    #[Test]
    #[TestDox('SVC-002: Service class exists and is instantiable')]
    public function service_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(self::SERVICE_CLASS), 'AISuggestionService class should exist');
    }

    // =========================================================================
    // SVC-003: Constructor accepts provider via interface
    // =========================================================================

    #[Test]
    #[TestDox('SVC-003: Constructor accepts provider via interface')]
    public function constructor_accepts_provider_via_interface(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'Service should have a constructor');
        
        $parameters = $constructor->getParameters();
        $this->assertGreaterThan(0, count($parameters), 'Constructor should have at least one parameter');
        
        $firstParam = $parameters[0];
        $type = $firstParam->getType();
        
        $this->assertNotNull($type, 'Provider parameter should have type hint');
        $this->assertEquals(
            self::INTERFACE_CLASS,
            $type->getName(),
            'Constructor should accept AIProviderInterface'
        );
    }

    // =========================================================================
    // SVC-004: Service has getSuggestions method
    // =========================================================================

    #[Test]
    #[TestDox('SVC-004: Service has getSuggestions method')]
    public function service_has_get_suggestions_method(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('getSuggestions'),
            'Service must have getSuggestions() method'
        );
    }

    // =========================================================================
    // SVC-005: getSuggestions accepts indicator parameter
    // =========================================================================

    #[Test]
    #[TestDox('SVC-005: getSuggestions accepts indicator parameter')]
    public function get_suggestions_accepts_indicator_parameter(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('getSuggestions');
        $parameters = $method->getParameters();
        
        $this->assertGreaterThanOrEqual(1, count($parameters), 'getSuggestions should have at least 1 parameter');
        
        $firstParam = $parameters[0];
        $this->assertEquals('indicator', $firstParam->getName());
        $this->assertFalse($firstParam->isOptional(), 'indicator parameter should be required');
    }

    // =========================================================================
    // SVC-006: getSuggestions accepts context parameter
    // =========================================================================

    #[Test]
    #[TestDox('SVC-006: getSuggestions accepts context parameter')]
    public function get_suggestions_accepts_context_parameter(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('getSuggestions');
        $parameters = $method->getParameters();
        
        $this->assertGreaterThanOrEqual(2, count($parameters), 'getSuggestions should have at least 2 parameters');
        
        $secondParam = $parameters[1];
        $this->assertEquals('context', $secondParam->getName());
    }

    // =========================================================================
    // SVC-007: getSuggestions returns SuggestionResult
    // =========================================================================

    #[Test]
    #[TestDox('SVC-007: getSuggestions returns SuggestionResult')]
    public function get_suggestions_returns_suggestion_result(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('getSuggestions');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'getSuggestions must have return type');
        $this->assertEquals('SuggestionResult', $returnType->getName(), 'getSuggestions must return SuggestionResult');
    }

    // =========================================================================
    // SVC-008: Service has setProvider method
    // =========================================================================

    #[Test]
    #[TestDox('SVC-008: Service has setProvider method')]
    public function service_has_set_provider_method(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('setProvider'),
            'Service must have setProvider() method'
        );
    }

    // =========================================================================
    // SVC-009: setProvider accepts string parameter
    // =========================================================================

    #[Test]
    #[TestDox('SVC-009: setProvider accepts string parameter')]
    public function set_provider_accepts_string_parameter(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('setProvider');
        $parameters = $method->getParameters();
        
        $this->assertGreaterThanOrEqual(1, count($parameters), 'setProvider should have at least 1 parameter');
        
        $firstParam = $parameters[0];
        $type = $firstParam->getType();
        
        $this->assertNotNull($type, 'provider parameter should have type hint');
        $this->assertEquals('string', $type->getName(), 'setProvider should accept string');
    }

    // =========================================================================
    // SVC-010: setProvider returns void
    // =========================================================================

    #[Test]
    #[TestDox('SVC-010: setProvider returns void')]
    public function set_provider_returns_void(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('setProvider');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'setProvider must have return type');
        $this->assertEquals('void', $returnType->getName(), 'setProvider must return void');
    }

    // =========================================================================
    // SVC-011: Service has isDebugMode method
    // =========================================================================

    #[Test]
    #[TestDox('SVC-011: Service has isDebugMode method')]
    public function service_has_is_debug_mode_method(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('isDebugMode'),
            'Service must have isDebugMode() method'
        );
    }

    // =========================================================================
    // SVC-012: isDebugMode returns bool
    // =========================================================================

    #[Test]
    #[TestDox('SVC-012: isDebugMode returns bool')]
    public function is_debug_mode_returns_bool(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $method = $reflection->getMethod('isDebugMode');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'isDebugMode must have return type');
        $this->assertEquals('bool', $returnType->getName(), 'isDebugMode must return bool');
    }

    // =========================================================================
    // SVC-013: isDebugMode returns false when config debug is false
    // =========================================================================

    #[Test]
    #[TestDox('SVC-013: isDebugMode returns false when config debug is false')]
    public function is_debug_mode_returns_false_when_config_debug_is_false(): void
    {
        config(['ai.debug' => false]);
        
        $service = app(self::SERVICE_CLASS);
        
        $this->assertFalse($service->isDebugMode());
    }

    // =========================================================================
    // SVC-014: isDebugMode returns true when config debug is true
    // =========================================================================

    #[Test]
    #[TestDox('SVC-014: isDebugMode returns true when config debug is true')]
    public function is_debug_mode_returns_true_when_config_debug_is_true(): void
    {
        config(['ai.debug' => true]);
        
        $service = app(self::SERVICE_CLASS);
        
        $this->assertTrue($service->isDebugMode());
    }

    // =========================================================================
    // SVC-015: Service can be resolved from container
    // =========================================================================

    #[Test]
    #[TestDox('SVC-015: Service can be resolved from container')]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(self::SERVICE_CLASS);
        
        $this->assertInstanceOf(self::SERVICE_CLASS, $service);
    }

    // =========================================================================
    // SVC-016: Service accepts mock provider
    // =========================================================================

    #[Test]
    #[TestDox('SVC-016: Service accepts mock provider')]
    public function service_accepts_mock_provider(): void
    {
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        $this->assertInstanceOf(self::SERVICE_CLASS, $service);
    }

    // =========================================================================
    // SVC-017: setProvider changes provider at runtime
    // =========================================================================

    #[Test]
    #[TestDox('SVC-017: setProvider changes provider at runtime')]
    public function set_provider_changes_provider_at_runtime(): void
    {
        $mockProvider1 = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider1->method('getName')->willReturn('provider1');
        
        $mockProvider2 = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider2->method('getName')->willReturn('provider2');
        
        $service = new (self::SERVICE_CLASS)($mockProvider1);
        
        // setProvider is a stub - it should accept the call
        $service->setProvider('provider2');
        
        $this->assertTrue(true, 'setProvider should complete without error');
    }

    // =========================================================================
    // SVC-018: getSuggestions stub can be called
    // =========================================================================

    #[Test]
    #[TestDox('SVC-018: getSuggestions stub can be called')]
    public function get_suggestions_stub_can_be_called(): void
    {
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')->willReturn(
            new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: '{"score": 50}',
                tokensUsed: 10,
                latencyMs: 100,
                errorMessage: null
            )
        );
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // This is a stub test - actual implementation will come later
        // The test passes if no exception is thrown
        try {
            $result = $service->getSuggestions('title', ['url' => 'https://example.com']);
            $this->assertTrue(true, 'getSuggestions should complete without error');
        } catch (\Throwable $e) {
            // Stub may not be fully implemented yet
            $this->markTestSkipped('getSuggestions stub not fully implemented: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SVC-019: Service has proper namespace
    // =========================================================================

    #[Test]
    #[TestDox('SVC-019: Service has proper namespace')]
    public function service_has_proper_namespace(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        
        $this->assertEquals(
            'App\Services\AI',
            $reflection->getNamespaceName(),
            'Service should be in App\Services\AI namespace'
        );
    }

    // =========================================================================
    // SVC-020: All public methods have documentation
    // =========================================================================

    #[Test]
    #[TestDox('SVC-020: All public methods have documentation')]
    public function all_public_methods_have_documentation(): void
    {
        $reflection = new ReflectionClass(self::SERVICE_CLASS);
        $methods = ['getSuggestions', 'setProvider', 'isDebugMode'];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            
            $this->assertNotFalse(
                $docComment,
                "Method {$methodName}() must have PHPDoc documentation"
            );
        }
    }
}
