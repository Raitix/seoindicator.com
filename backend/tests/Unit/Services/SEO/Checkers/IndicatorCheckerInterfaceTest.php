<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests for IndicatorCheckerInterface
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class IndicatorCheckerInterfaceTest extends TestCase
{
    /**
     * Test interface defines id() method.
     * 
     * @test
     */
    public function interface_defines_id_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('id'));
        $this->assertTrue($reflection->getMethod('id')->hasReturnType());
        $this->assertEquals('string', (string) $reflection->getMethod('id')->getReturnType());
    }

    /**
     * Test interface defines name() method.
     * 
     * @test
     */
    public function interface_defines_name_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('name'));
        $this->assertTrue($reflection->getMethod('name')->hasReturnType());
        $this->assertEquals('string', (string) $reflection->getMethod('name')->getReturnType());
    }

    /**
     * Test interface defines category() method.
     * 
     * @test
     */
    public function interface_defines_category_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('category'));
        $this->assertTrue($reflection->getMethod('category')->hasReturnType());
        $this->assertEquals('string', (string) $reflection->getMethod('category')->getReturnType());
    }

    /**
     * Test interface defines weight() method.
     * 
     * @test
     */
    public function interface_defines_weight_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('weight'));
        $this->assertTrue($reflection->getMethod('weight')->hasReturnType());
        $this->assertEquals('float', (string) $reflection->getMethod('weight')->getReturnType());
    }

    /**
     * Test interface defines check() method.
     * 
     * @test
     */
    public function interface_defines_check_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('check'));
        $this->assertTrue($reflection->getMethod('check')->hasReturnType());
        $this->assertEquals(IndicatorResult::class, (string) $reflection->getMethod('check')->getReturnType());
    }

    /**
     * Test interface defines getSuggestions() method.
     * 
     * @test
     */
    public function interface_defines_get_suggestions_method(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('getSuggestions'));
        $this->assertTrue($reflection->getMethod('getSuggestions')->hasReturnType());
        $this->assertEquals('array', (string) $reflection->getMethod('getSuggestions')->getReturnType());
    }

    /**
     * Test check() accepts Crawler and URL.
     * 
     * @test
     */
    public function check_method_signature(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        $method = $reflection->getMethod('check');
        $parameters = $method->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('crawler', $parameters[0]->getName());
        $this->assertEquals(Crawler::class, (string) $parameters[0]->getType());
        $this->assertEquals('url', $parameters[1]->getName());
        $this->assertEquals('string', (string) $parameters[1]->getType());
        $this->assertEquals('options', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isOptional());
    }

    /**
     * Test mock implementation works.
     * 
     * @test
     */
    public function mock_implementation_works(): void
    {
        $checker = $this->createMockChecker();

        $this->assertEquals('test_indicator', $checker->id());
        $this->assertEquals('Test Indicator', $checker->name());
        $this->assertEquals('technical', $checker->category());
        $this->assertEquals(0.08, $checker->weight());
    }

    /**
     * Test implementation returns correct result type.
     * 
     * @test
     */
    public function implementation_returns_correct_result_type(): void
    {
        $checker = $this->createMockChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    /**
     * Test implementation returns correct suggestions type.
     * 
     * @test
     */
    public function implementation_returns_correct_suggestions_type(): void
    {
        $checker = $this->createMockChecker();
        $result = $this->createMockResult();
        
        $suggestions = $checker->getSuggestions($result);

        $this->assertIsArray($suggestions);
    }

    /**
     * Test multiple implementations can coexist.
     * 
     * @test
     */
    public function multiple_implementations_can_coexist(): void
    {
        $checker1 = $this->createMockChecker('indicator_1', 0.05);
        $checker2 = $this->createMockChecker('indicator_2', 0.10);

        $this->assertNotEquals($checker1->id(), $checker2->id());
        $this->assertNotEquals($checker1->weight(), $checker2->weight());
    }

    /**
     * Test interface can be type-hinted.
     * 
     * @test
     */
    public function interface_can_be_type_hinted(): void
    {
        $checker = $this->createMockChecker();
        
        // This tests that the interface can be used as a type declaration
        $result = $this->processChecker($checker);

        $this->assertNotNull($result);
    }

    /**
     * Test weight must be declared per implementation.
     * 
     * @test
     */
    public function weight_must_be_declared_per_implementation(): void
    {
        $checker1 = $this->createMockChecker('test_1', 0.05);
        $checker2 = $this->createMockChecker('test_2', 0.10);

        // Different implementations can have different weights
        $this->assertEquals(0.05, $checker1->weight());
        $this->assertEquals(0.10, $checker2->weight());
    }

    /**
     * Test category must be valid.
     * 
     * @test
     */
    public function category_must_be_valid(): void
    {
        $validCategories = ['content', 'technical', 'accessibility', 'performance'];
        
        foreach ($validCategories as $category) {
            $checker = $this->createMockChecker('test', 0.05, $category);
            $this->assertEquals($category, $checker->category());
        }
    }

    /**
     * Test getSuggestions receives IndicatorResult parameter.
     * 
     * @test
     */
    public function get_suggestions_receives_result_parameter(): void
    {
        $reflection = new \ReflectionClass(IndicatorCheckerInterface::class);
        $method = $reflection->getMethod('getSuggestions');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('result', $parameters[0]->getName());
        $this->assertEquals(IndicatorResult::class, (string) $parameters[0]->getType());
    }

    /**
     * Helper: Create mock checker.
     */
    private function createMockChecker(
        string $id = 'test_indicator',
        float $weight = 0.08,
        string $category = 'technical'
    ): IndicatorCheckerInterface {
        return new class($id, $weight, $category) implements IndicatorCheckerInterface {
            private string $id;
            private float $weight;
            private string $category;

            public function __construct(string $id, float $weight, string $category)
            {
                $this->id = $id;
                $this->weight = $weight;
                $this->category = $category;
            }

            public function id(): string { return $this->id; }
            public function name(): string { return 'Test Indicator'; }
            public function category(): string { return $this->category; }
            public function weight(): float { return $this->weight; }
            
            public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return new IndicatorResult(
                    id: $this->id,
                    name: $this->name(),
                    category: $this->category,
                    score: 3,
                    weight: $this->weight,
                    passed: true,
                    details: []
                );
            }

            public function getSuggestions(IndicatorResult $result): array
            {
                return [];
            }
        };
    }

    /**
     * Helper: Create mock result.
     */
    private function createMockResult(): IndicatorResult
    {
        return new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'technical',
            score: 3,
            weight: 0.05,
            passed: true,
            details: []
        );
    }

    /**
     * Helper: Process checker (demonstrates type hinting).
     */
    private function processChecker(IndicatorCheckerInterface $checker): ?IndicatorResult
    {
        return new IndicatorResult(
            id: $checker->id(),
            name: $checker->name(),
            category: $checker->category(),
            score: 3,
            weight: $checker->weight(),
            passed: true,
            details: []
        );
    }
}
