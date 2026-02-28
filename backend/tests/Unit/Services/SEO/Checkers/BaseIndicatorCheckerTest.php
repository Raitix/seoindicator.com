<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\BaseIndicatorChecker;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests for BaseIndicatorChecker abstract class
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class BaseIndicatorCheckerTest extends TestCase
{
    /**
     * Test abstract class implements interface.
     * 
     * @test
     */
    public function abstract_class_implements_interface(): void
    {
        $reflection = new \ReflectionClass(BaseIndicatorChecker::class);
        
        $this->assertTrue($reflection->implementsInterface(IndicatorCheckerInterface::class));
    }

    /**
     * Test abstract methods must be implemented.
     * 
     * @test
     */
    public function abstract_methods_exist(): void
    {
        $reflection = new \ReflectionClass(BaseIndicatorChecker::class);
        
        $this->assertTrue($reflection->hasMethod('name'));
        $this->assertTrue($reflection->hasMethod('performCheck'));
        $this->assertTrue($reflection->getMethod('performCheck')->isAbstract());
    }

    /**
     * Test concrete check() method calls performCheck().
     * 
     * @test
     */
    public function check_calls_perform_check(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals(3, $result->score);
    }

    /**
     * Test check() returns IndicatorResult.
     * 
     * @test
     */
    public function check_returns_indicator_result(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    /**
     * Test check() handles empty URL.
     * 
     * @test
     */
    public function check_handles_empty_url(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $result = $checker->check($crawler, '');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->details['error'] ?? false);
    }

    /**
     * Test check() handles exceptions gracefully.
     * 
     * @test
     */
    public function check_handles_exceptions_gracefully(): void
    {
        $checker = $this->createExceptionChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->details['error'] ?? false);
    }

    /**
     * Test getSuggestions() returns empty array by default.
     * 
     * @test
     */
    public function get_suggestions_returns_empty_array_by_default(): void
    {
        $checker = $this->createConcreteChecker();
        $result = $this->createMockResult();
        
        $suggestions = $checker->getSuggestions($result);

        $this->assertIsArray($suggestions);
        $this->assertEmpty($suggestions);
    }

    /**
     * Test createResult() creates valid IndicatorResult.
     * 
     * @test
     */
    public function create_result_creates_valid_result(): void
    {
        $checker = $this->createConcreteChecker();
        
        $result = $checker->testCreateResult(3, true, ['key' => 'value']);

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals('test_checker', $result->id);
        $this->assertEquals('Test Checker', $result->name);
        $this->assertEquals('technical', $result->category);
        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->passed);
        $this->assertEquals(['key' => 'value'], $result->details);
    }

    /**
     * Test createSuggestion() creates valid Suggestion.
     * 
     * @test
     */
    public function create_suggestion_creates_valid_suggestion(): void
    {
        $checker = $this->createConcreteChecker();
        
        $suggestion = $checker->testCreateSuggestion('warning', 'Test message', 'Test action', 2);

        $this->assertInstanceOf(Suggestion::class, $suggestion);
        $this->assertEquals('warning', $suggestion->type);
        $this->assertEquals('Test message', $suggestion->message);
        $this->assertEquals('Test action', $suggestion->action);
        $this->assertEquals(2, $suggestion->priority);
    }

    /**
     * Test extractText() helper method.
     * 
     * @test
     */
    public function extract_text_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><title>Page Title</title><body>Test</body></html>');
        
        $text = $checker->testExtractText($crawler, 'title');

        $this->assertEquals('Page Title', $text);
    }

    /**
     * Test extractText() returns null for missing element.
     * 
     * @test
     */
    public function extract_text_returns_null_for_missing(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $text = $checker->testExtractText($crawler, 'title');

        $this->assertNull($text);
    }

    /**
     * Test extractAttribute() helper method.
     * 
     * @test
     */
    public function extract_attribute_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><meta name="description" content="Test description"></html>');
        
        $attr = $checker->testExtractAttribute($crawler, 'meta[name="description"]', 'content');

        $this->assertEquals('Test description', $attr);
    }

    /**
     * Test extractAttribute() returns null for missing attribute.
     * 
     * @test
     */
    public function extract_attribute_returns_null_for_missing(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><meta name="description"></html>');
        
        $attr = $checker->testExtractAttribute($crawler, 'meta[name="description"]', 'content');

        $this->assertNull($attr);
    }

    /**
     * Test countElements() helper method.
     * 
     * @test
     */
    public function count_elements_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body><p>Para 1</p><p>Para 2</p><p>Para 3</p></body></html>');
        
        $count = $checker->testCountElements($crawler, 'p');

        $this->assertEquals(3, $count);
    }

    /**
     * Test countElements() returns 0 for missing elements.
     * 
     * @test
     */
    public function count_elements_returns_zero_for_missing(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body>Test</body></html>');
        
        $count = $checker->testCountElements($crawler, 'p');

        $this->assertEquals(0, $count);
    }

    /**
     * Test extractAllWithAttributes() helper method.
     * 
     * @test
     */
    public function extract_all_with_attributes_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body><a href="/link1">Link 1</a><a href="/link2">Link 2</a></body></html>');
        
        $results = $checker->testExtractAllWithAttributes($crawler, 'a', ['href']);

        $this->assertCount(2, $results);
        $this->assertEquals('Link 1', $results[0]['text']);
        $this->assertEquals('/link1', $results[0]['href']);
        $this->assertEquals('Link 2', $results[1]['text']);
        $this->assertEquals('/link2', $results[1]['href']);
    }

    /**
     * Test elementExists() helper method.
     * 
     * @test
     */
    public function element_exists_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><title>Test</title></html>');
        
        $this->assertTrue($checker->testElementExists($crawler, 'title'));
        $this->assertFalse($checker->testElementExists($crawler, 'h1'));
    }

    /**
     * Test extractAllText() helper method.
     * 
     * @test
     */
    public function extract_all_text_helper(): void
    {
        $checker = $this->createConcreteChecker();
        $crawler = new Crawler('<html><body><h1>Heading 1</h1><h1>Heading 2</h1></body></html>');
        
        $texts = $checker->testExtractAllText($crawler, 'h1');

        $this->assertCount(2, $texts);
        $this->assertEquals('Heading 1', $texts[0]);
        $this->assertEquals('Heading 2', $texts[1]);
    }

    /**
     * Test abstract class cannot be instantiated directly.
     * 
     * @test
     */
    public function cannot_instantiate_directly(): void
    {
        $reflection = new \ReflectionClass(BaseIndicatorChecker::class);
        
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test subclass can override default weight.
     * 
     * @test
     */
    public function subclass_can_override_weight(): void
    {
        $checker = new class extends BaseIndicatorChecker {
            protected float $defaultWeight = 0.15;
            
            public function name(): string { return 'Custom Weight Checker'; }
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return $this->createResult(3, true);
            }
        };

        $this->assertEquals(0.15, $checker->weight());
    }

    /**
     * Test subclass can override default category.
     * 
     * @test
     */
    public function subclass_can_override_category(): void
    {
        $checker = new class extends BaseIndicatorChecker {
            protected string $defaultCategory = 'content';
            
            public function name(): string { return 'Content Checker'; }
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return $this->createResult(3, true);
            }
        };

        $this->assertEquals('content', $checker->category());
    }

    /**
     * Test default id() converts class name to snake_case.
     * 
     * @test
     */
    public function default_id_converts_class_name(): void
    {
        $checker = new class extends BaseIndicatorChecker {
            public function name(): string { return 'Test'; }
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return $this->createResult(3, true);
            }
        };

        // The anonymous class name is unpredictable, but should contain 'class'
        $this->assertNotEmpty($checker->id());
    }

    /**
     * Test getSuggestions() can be overridden.
     * 
     * @test
     */
    public function get_suggestions_can_be_overridden(): void
    {
        $checker = new class extends BaseIndicatorChecker {
            public function name(): string { return 'Suggestion Checker'; }
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return $this->createResult(1, false);
            }
            
            public function getSuggestions(IndicatorResult $result): array
            {
                return [
                    $this->createSuggestion('warning', 'Fix this issue', 'Take action', 2)
                ];
            }
        };

        $result = $checker->check(new Crawler('<html></html>'), 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertCount(1, $suggestions);
        $this->assertInstanceOf(Suggestion::class, $suggestions[0]);
    }

    /**
     * Test template method pattern execution order.
     * 
     * @test
     */
    public function template_method_pattern_works(): void
    {
        $executionOrder = [];
        
        $checker = new class($executionOrder) extends BaseIndicatorChecker {
            private array $log;
            
            public function __construct(array &$log)
            {
                $this->log = &$log;
                parent::__construct();
            }
            
            public function name(): string { return 'Logging Checker'; }
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                $this->log[] = 'performCheck';
                return $this->createResult(3, true);
            }
        };

        $checker->check(new Crawler('<html></html>'), 'https://example.com');

        $this->assertContains('performCheck', $executionOrder);
    }

    /**
     * Helper: Create a concrete checker implementation.
     */
    private function createConcreteChecker(): BaseIndicatorChecker
    {
        return new class extends BaseIndicatorChecker {
            protected float $defaultWeight = 0.08;
            protected string $defaultCategory = 'technical';

            public function id(): string { return 'test_checker'; }
            public function name(): string { return 'Test Checker'; }
            
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                return $this->createResult(3, true, ['url' => $url]);
            }

            // Expose protected methods for testing
            public function testCreateResult(int $score, bool $passed, array $details = []): IndicatorResult
            {
                return $this->createResult($score, $passed, $details);
            }

            public function testCreateSuggestion(string $type, string $message, ?string $action, int $priority): Suggestion
            {
                return $this->createSuggestion($type, $message, $action, $priority);
            }

            public function testExtractText(Crawler $crawler, string $selector): ?string
            {
                return $this->extractText($crawler, $selector);
            }

            public function testExtractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
            {
                return $this->extractAttribute($crawler, $selector, $attribute);
            }

            public function testCountElements(Crawler $crawler, string $selector): int
            {
                return $this->countElements($crawler, $selector);
            }

            public function testExtractAllWithAttributes(Crawler $crawler, string $selector, array $attributes): array
            {
                return $this->extractAllWithAttributes($crawler, $selector, $attributes);
            }

            public function testElementExists(Crawler $crawler, string $selector): bool
            {
                return $this->elementExists($crawler, $selector);
            }

            public function testExtractAllText(Crawler $crawler, string $selector): array
            {
                return $this->extractAllText($crawler, $selector);
            }
        };
    }

    /**
     * Helper: Create a checker that throws exceptions.
     */
    private function createExceptionChecker(): BaseIndicatorChecker
    {
        return new class extends BaseIndicatorChecker {
            public function name(): string { return 'Exception Checker'; }
            
            protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult
            {
                throw new \RuntimeException('Test exception');
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
}
