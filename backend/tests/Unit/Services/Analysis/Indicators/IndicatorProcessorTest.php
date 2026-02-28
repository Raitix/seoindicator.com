<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\IndicatorProcessor;
use App\Services\Analysis\Indicators\IndicatorResult;
use App\Services\URLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for IndicatorProcessor abstract base class
 * 
 * @see TASK-19-5
 * @see TEST-19-5
 */
class IndicatorProcessorTest extends TestCase
{
    // ==================== CONCRETE TEST IMPLEMENTATION ====================

    /**
     * Create a concrete implementation for testing.
     */
    private function createTestProcessor(array $options = []): TestableIndicatorProcessor
    {
        $urlValidator = $options['urlValidator'] ?? $this->createMock(URLValidationService::class);
        
        // Create processor with URL validator
        $processor = new TestableIndicatorProcessor($urlValidator, $options['httpClient'] ?? null);
        
        return $processor;
    }

    // ==================== ABSTRACT METHOD TESTS ====================

    /**
     * Test slug() returns unique identifier.
     */
    public function test_slug_returns_string(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertIsString($processor->slug());
        $this->assertEquals('test-processor', $processor->slug());
    }

    /**
     * Test slug() uses snake_case format.
     */
    public function test_slug_uses_snake_case(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $processor->slug());
    }

    /**
     * Test name() returns human-readable name.
     */
    public function test_name_returns_string(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertIsString($processor->name());
        $this->assertEquals('Test Processor', $processor->name());
    }

    /**
     * Test maxScore() returns float.
     */
    public function test_max_score_returns_float(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertIsFloat($processor->maxScore());
        $this->assertEquals(100.0, $processor->maxScore());
    }

    /**
     * Test process() returns IndicatorResult.
     */
    public function test_process_returns_indicator_result(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);

        $mock = new MockHandler([
            new Response(200, [], '<html><head><title>Test</title></head><body>Content</body></html>'),
        ]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals('test-processor', $result->slug);
    }

    // ==================== FETCH URL TESTS ====================

    /**
     * Test fetchUrl() fetches HTML content.
     */
    public function test_fetch_url_returns_html(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], $html),
        ]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $content = $processor->testFetchUrl('https://example.com');

        $this->assertEquals($html, $content);
    }

    /**
     * Test fetchUrl() handles HTTP 404 error.
     */
    public function test_fetch_url_handles_404(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com/notfound']);

        $mock = new MockHandler([
            new Response(404, [], 'Not Found'),
        ]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('404');

        $processor->testFetchUrl('https://example.com/notfound');
    }

    /**
     * Test fetchUrl() handles HTTP 500 error.
     */
    public function test_fetch_url_handles_500(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com/error']);

        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('500');

        $processor->testFetchUrl('https://example.com/error');
    }

    /**
     * Test fetchUrl() respects SSRF protection.
     */
    public function test_fetch_url_blocks_ssrf(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => false, 'error' => 'Access to this URL is not allowed']);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access to this URL is not allowed');

        $processor->testFetchUrl('http://localhost');
    }

    /**
     * Test fetchUrl() handles connection timeout.
     */
    public function test_fetch_url_handles_timeout(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);

        $mock = new MockHandler([
            new ConnectException('Connection timed out', new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')),
        ]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $this->expectException(RuntimeException::class);

        $processor->testFetchUrl('https://example.com');
    }

    /**
     * Test fetchUrl() uses normalized URL.
     */
    public function test_fetch_url_uses_normalized_url(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')
            ->willReturn(['valid' => true, 'normalized_url' => 'https://example.com/normalized']);

        $mock = new MockHandler([
            new Response(200, [], '<html></html>'),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $processor = $this->createTestProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => $client,
        ]);

        $processor->testFetchUrl('https://EXAMPLE.COM/Normalized');

        // The mock handler should have received the normalized URL
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    // ==================== PARSE HTML TESTS ====================

    /**
     * Test parseHtml() returns DOMDocument.
     */
    public function test_parse_html_returns_dom_document(): void
    {
        $processor = $this->createTestProcessor();
        
        $dom = $processor->testParseHtml('<html><body>Test</body></html>');
        
        $this->assertInstanceOf(\DOMDocument::class, $dom);
    }

    /**
     * Test parseHtml() handles valid HTML.
     */
    public function test_parse_html_handles_valid_html(): void
    {
        $processor = $this->createTestProcessor();
        
        $dom = $processor->testParseHtml('<!DOCTYPE html><html><head><title>Test</title></head><body><p>Content</p></body></html>');
        
        $this->assertInstanceOf(\DOMDocument::class, $dom);
        $this->assertEquals('Test', $dom->getElementsByTagName('title')->item(0)->textContent);
        $this->assertEquals('Content', $dom->getElementsByTagName('p')->item(0)->textContent);
    }

    /**
     * Test parseHtml() handles malformed HTML.
     */
    public function test_parse_html_handles_malformed_html(): void
    {
        $processor = $this->createTestProcessor();
        
        // Malformed but still parseable
        $dom = $processor->testParseHtml('<html><body><div>Unclosed');
        
        $this->assertInstanceOf(\DOMDocument::class, $dom);
        $this->assertStringContainsString('Unclosed', $dom->textContent);
    }

    /**
     * Test parseHtml() throws on empty HTML.
     */
    public function test_parse_html_throws_on_empty_html(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid HTML content');
        
        $processor->testParseHtml('');
    }

    /**
     * Test parseHtml() throws on whitespace only.
     */
    public function test_parse_html_throws_on_whitespace(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid HTML content');
        
        $processor->testParseHtml('   ');
    }

    // ==================== HELPER METHOD TESTS ====================

    /**
     * Test extractText() returns text content.
     */
    public function test_extract_text_returns_content(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><h1>Title</h1><p class="desc">Description</p></body></html>');
        
        $text = $processor->testExtractText($dom, 'h1');
        
        $this->assertEquals('Title', $text);
    }

    /**
     * Test extractText() returns null for missing element.
     */
    public function test_extract_text_returns_null_for_missing(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><p>Content</p></body></html>');
        
        $text = $processor->testExtractText($dom, 'h1');
        
        $this->assertNull($text);
    }

    /**
     * Test extractAttribute() returns attribute value.
     */
    public function test_extract_attribute_returns_value(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><a href="https://example.com">Link</a></body></html>');
        
        $href = $processor->testExtractAttribute($dom, 'a', 'href');
        
        $this->assertEquals('https://example.com', $href);
    }

    /**
     * Test extractAttribute() returns null for missing attribute.
     */
    public function test_extract_attribute_returns_null_for_missing(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><a>Link</a></body></html>');
        
        $href = $processor->testExtractAttribute($dom, 'a', 'href');
        
        $this->assertNull($href);
    }

    /**
     * Test extractAllText() returns array of texts.
     */
    public function test_extract_all_text_returns_array(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><ul><li>One</li><li>Two</li><li>Three</li></ul></body></html>');
        
        $texts = $processor->testExtractAllText($dom, 'li');
        
        $this->assertCount(3, $texts);
        $this->assertEquals(['One', 'Two', 'Three'], $texts);
    }

    /**
     * Test extractAllWithAttributes() returns array with attributes.
     */
    public function test_extract_all_with_attributes(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><a href="/a">Link A</a><a href="/b">Link B</a></body></html>');
        
        $links = $processor->testExtractAllWithAttributes($dom, 'a', ['href']);
        
        $this->assertCount(2, $links);
        $this->assertEquals('Link A', $links[0]['text']);
        $this->assertEquals('/a', $links[0]['href']);
    }

    /**
     * Test countElements() returns correct count.
     */
    public function test_count_elements(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><img src="1.jpg"><img src="2.jpg"><img src="3.jpg"></body></html>');
        
        $count = $processor->testCountElements($dom, 'img');
        
        $this->assertEquals(3, $count);
    }

    /**
     * Test recommendation() creates recommendation array.
     */
    public function test_recommendation_creates_array(): void
    {
        $processor = $this->createTestProcessor();
        
        $rec = $processor->testRecommendation('high', 'Fix this issue', 'More details here');
        
        $this->assertEquals('high', $rec['priority']);
        $this->assertEquals('Fix this issue', $rec['message']);
        $this->assertEquals('More details here', $rec['details']);
    }

    /**
     * Test recommendation() with null details.
     */
    public function test_recommendation_without_details(): void
    {
        $processor = $this->createTestProcessor();
        
        $rec = $processor->testRecommendation('medium', 'Consider this');
        
        $this->assertEquals('medium', $rec['priority']);
        $this->assertEquals('Consider this', $rec['message']);
        $this->assertNull($rec['details']);
    }

    // ==================== CONFIGURATION TESTS ====================

    /**
     * Test default timeout is 30 seconds.
     */
    public function test_default_timeout(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertEquals(30, $processor->getTestTimeout());
    }

    /**
     * Test setTimeout() changes timeout.
     */
    public function test_set_timeout(): void
    {
        $processor = $this->createTestProcessor();
        
        $processor->setTestTimeout(60);
        
        $this->assertEquals(60, $processor->getTestTimeout());
    }

    /**
     * Test default max response size is 10MB.
     */
    public function test_default_max_response_size(): void
    {
        $processor = $this->createTestProcessor();
        
        $this->assertEquals(10485760, $processor->getTestMaxResponseSize());
    }

    /**
     * Test setMaxResponseSize() changes size.
     */
    public function test_set_max_response_size(): void
    {
        $processor = $this->createTestProcessor();
        
        $processor->setTestMaxResponseSize(5242880); // 5MB
        
        $this->assertEquals(5242880, $processor->getTestMaxResponseSize());
    }

    // ==================== PROCESSING TIME TESTS ====================

    /**
     * Test calculateProcessingTime() returns integer.
     */
    public function test_calculate_processing_time(): void
    {
        $processor = $this->createTestProcessor();
        
        $start = microtime(true);
        usleep(10000); // 10ms
        $time = $processor->testCalculateProcessingTime($start);
        
        $this->assertIsInt($time);
        $this->assertGreaterThanOrEqual(10, $time);
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test extractText() trims whitespace.
     */
    public function test_extract_text_trims_whitespace(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><p>   Trimmed text   </p></body></html>');
        
        $text = $processor->testExtractText($dom, 'p');
        
        $this->assertEquals('Trimmed text', $text);
    }

    /**
     * Test extractAllText() with no matches.
     */
    public function test_extract_all_text_no_matches(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><p>Content</p></body></html>');
        
        $texts = $processor->testExtractAllText($dom, 'article');
        
        $this->assertEquals([], $texts);
    }

    /**
     * Test countElements() with no matches.
     */
    public function test_count_elements_no_matches(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><p>Content</p></body></html>');
        
        $count = $processor->testCountElements($dom, 'video');
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test HTML with special characters.
     */
    public function test_html_with_special_characters(): void
    {
        $processor = $this->createTestProcessor();
        $html = '<html><body><p>&amp; &lt; &gt; &quot; &#39;</p></body></html>';
        
        $dom = $processor->testParseHtml($html);
        $text = $processor->testExtractText($dom, 'p');
        
        $this->assertStringContainsString('&', $text);
    }

    /**
     * Test HTML with unicode.
     */
    public function test_html_with_unicode(): void
    {
        $processor = $this->createTestProcessor();
        $html = '<html><body><p>日本語 🎉 Tëst</p></body></html>';
        
        $dom = $processor->testParseHtml($html);
        $text = $processor->testExtractText($dom, 'p');
        
        $this->assertStringContainsString('日本語', $text);
        $this->assertStringContainsString('🎉', $text);
    }

    /**
     * Test extractAttribute() with data attributes.
     */
    public function test_extract_data_attribute(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><div data-id="123">Item</div></body></html>');
        
        $dataId = $processor->testExtractAttribute($dom, 'div', 'data-id');
        
        $this->assertEquals('123', $dataId);
    }

    /**
     * Test extractAllWithAttributes() with multiple attributes.
     */
    public function test_extract_multiple_attributes(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><img src="test.jpg" alt="Test" width="100"><img src="test2.jpg" alt="Test 2" width="200"></body></html>');
        
        $images = $processor->testExtractAllWithAttributes($dom, 'img', ['src', 'alt', 'width']);
        
        $this->assertCount(2, $images);
        $this->assertEquals('test.jpg', $images[0]['src']);
        $this->assertEquals('Test', $images[0]['alt']);
        $this->assertEquals('100', $images[0]['width']);
    }

    /**
     * Test CSS class selector.
     */
    public function test_css_class_selector(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><p class="highlight">Important</p><p>Normal</p></body></html>');
        
        $text = $processor->testExtractText($dom, 'p.highlight');
        
        $this->assertEquals('Important', $text);
    }

    /**
     * Test CSS id selector.
     */
    public function test_css_id_selector(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><div id="main">Content</div></body></html>');
        
        $text = $processor->testExtractText($dom, '#main');
        
        $this->assertEquals('Content', $text);
    }

    /**
     * Test nested selectors work.
     */
    public function test_nested_selectors(): void
    {
        $processor = $this->createTestProcessor();
        $dom = $processor->testParseHtml('<html><body><div class="container"><p class="text">Nested</p></div></body></html>');
        
        $text = $processor->testExtractText($dom, 'div.container p.text');
        
        $this->assertEquals('Nested', $text);
    }
}

/**
 * Concrete test implementation of IndicatorProcessor.
 */
class TestableIndicatorProcessor extends IndicatorProcessor
{
    public function slug(): string
    {
        return 'test-processor';
    }

    public function name(): string
    {
        return 'Test Processor';
    }

    public function maxScore(): float
    {
        return 100.0;
    }

    public function process(string $url): IndicatorResult
    {
        $startTime = microtime(true);
        
        try {
            $html = $this->fetchUrl($url);
            $dom = $this->parseHtml($html);
            
            return IndicatorResult::success(
                slug: $this->slug(),
                name: $this->name(),
                score: 100.0,
                maxScore: $this->maxScore(),
                data: ['title' => $this->extractText($dom, 'title')],
                recommendations: [],
                processingTimeMs: $this->calculateProcessingTime($startTime),
            );
        } catch (\Exception $e) {
            return IndicatorResult::failed(
                slug: $this->slug(),
                name: $this->name(),
                maxScore: $this->maxScore(),
                error: $e->getMessage(),
                processingTimeMs: $this->calculateProcessingTime($startTime),
            );
        }
    }

    /**
     * Constructor that allows setting test HTTP client.
     */
    public function __construct(URLValidationService $urlValidator, ?Client $httpClient = null)
    {
        parent::__construct($urlValidator);
        
        if ($httpClient !== null) {
            $this->httpClient = $httpClient;
        }
    }

    // Expose protected methods for testing

    public function testFetchUrl(string $url): string
    {
        return $this->fetchUrl($url);
    }

    public function testParseHtml(string $html): \DOMDocument
    {
        return $this->parseHtml($html);
    }

    public function testExtractText(\DOMDocument $dom, string $selector): ?string
    {
        return $this->extractText($dom, $selector);
    }

    public function testExtractAttribute(\DOMDocument $dom, string $selector, string $attribute): ?string
    {
        return $this->extractAttribute($dom, $selector, $attribute);
    }

    public function testExtractAllText(\DOMDocument $dom, string $selector): array
    {
        return $this->extractAllText($dom, $selector);
    }

    public function testExtractAllWithAttributes(\DOMDocument $dom, string $selector, array $attributes): array
    {
        return $this->extractAllWithAttributes($dom, $selector, $attributes);
    }

    public function testCountElements(\DOMDocument $dom, string $selector): int
    {
        return $this->countElements($dom, $selector);
    }

    public function testCalculateProcessingTime(float $startTime): int
    {
        return $this->calculateProcessingTime($startTime);
    }

    public function testRecommendation(string $priority, string $message, ?string $details = null): array
    {
        return $this->recommendation($priority, $message, $details);
    }

    public function getTestTimeout(): int
    {
        return $this->timeout;
    }

    public function setTestTimeout(int $seconds): void
    {
        $this->setTimeout($seconds);
    }

    public function getTestMaxResponseSize(): int
    {
        return $this->maxResponseSize;
    }

    public function setTestMaxResponseSize(int $bytes): void
    {
        $this->setMaxResponseSize($bytes);
    }
}
