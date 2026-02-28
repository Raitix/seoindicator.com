<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\ImagesProcessor;
use App\Services\Analysis\Indicators\IndicatorResult;
use App\Services\URLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ImagesProcessor.
 * 
 * @see TASK-19-6
 * @see TEST-19-6
 */
class ImagesProcessorTest extends TestCase
{
    private function createProcessor(array $options = []): ImagesProcessor
    {
        $urlValidator = $options['urlValidator'] ?? $this->createMock(URLValidationService::class);
        
        $processor = new ImagesProcessor($urlValidator);
        
        if (isset($options['httpClient'])) {
            $reflection = new \ReflectionClass($processor);
            $property = $reflection->getProperty('httpClient');
            $property->setAccessible(true);
            $property->setValue($processor, $options['httpClient']);
        }
        
        return $processor;
    }

    // ==================== BASIC TESTS ====================

    public function test_slug_returns_images(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('images', $processor->slug());
    }

    public function test_name_returns_human_readable(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('Images Optimization', $processor->name());
    }

    public function test_max_score_is_100(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals(100.0, $processor->maxScore());
    }

    // ==================== IMAGE COUNT TESTS ====================

    public function test_counts_total_images(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="Image 1">
            <img src="2.jpg" alt="Image 2">
            <img src="3.jpg" alt="Image 3">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(3, $result->data['total_count']);
    }

    public function test_handles_page_with_no_images(): void
    {
        $html = '<!DOCTYPE html><html><body><p>Just text</p></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(0, $result->data['total_count']);
        $this->assertEquals(100.0, $result->score); // Perfect score if no images
    }

    // ==================== ALT TEXT TESTS ====================

    public function test_counts_images_with_alt_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="Alt text">
            <img src="2.jpg" alt="Another alt">
            <img src="3.jpg">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['alt_analysis']['with_alt']);
        $this->assertEquals(1, $result->data['alt_analysis']['without_alt']);
    }

    public function test_calculates_alt_percentage(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="Alt 1">
            <img src="2.jpg" alt="Alt 2">
            <img src="3.jpg" alt="Alt 3">
            <img src="4.jpg">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(75.0, $result->data['alt_analysis']['alt_percentage']);
    }

    public function test_scores_all_images_with_alt(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="Alt 1">
            <img src="2.jpg" alt="Alt 2">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThan(60, $result->score); // Should get max alt score
    }

    public function test_recommends_adding_alt_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg">
            <img src="2.jpg">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $altRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'alt')
        );
        $this->assertGreaterThanOrEqual(1, count($altRecs));
    }

    // ==================== LAZY LOADING TESTS ====================

    public function test_detects_lazy_loading(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="1" loading="lazy">
            <img src="2.jpg" alt="2" loading="lazy">
            <img src="3.jpg" alt="3">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['lazy_loading_analysis']['count']);
    }

    public function test_recommends_lazy_loading(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="1">
            <img src="2.jpg" alt="2">
            <img src="3.jpg" alt="3">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $lazyRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'lazy')
        );
        $this->assertGreaterThanOrEqual(1, count($lazyRecs));
    }

    // ==================== IMAGE FORMAT TESTS ====================

    public function test_detects_modern_formats(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="image.webp" alt="WebP">
            <img src="image.avif" alt="AVIF">
            <img src="image.jpg" alt="JPEG">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['format_analysis']['modern_format_count']);
        $this->assertEquals(1, $result->data['format_analysis']['legacy_format_count']);
    }

    public function test_recommends_modern_formats(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="image.jpg" alt="JPEG">
            <img src="image.png" alt="PNG">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $formatRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'format') || 
            str_contains(strtolower($r['message']), 'webp')
        );
        $this->assertGreaterThanOrEqual(1, count($formatRecs));
    }

    // ==================== DECORATIVE IMAGES TESTS ====================

    public function test_identifies_decorative_images(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="decorative.jpg" alt="">
            <img src="content.jpg" alt="Content image">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['decorative_analysis']['decorative_count']);
    }

    // ==================== SUMMARY TESTS ====================

    public function test_generates_summary(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="Alt 1" loading="lazy">
            <img src="2.jpg" loading="lazy">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['summary']['with_alt']);
        $this->assertEquals(1, $result->data['summary']['without_alt']);
        $this->assertEquals(2, $result->data['summary']['with_lazy_loading']);
    }

    // ==================== SCORE CALCULATION TESTS ====================

    public function test_score_is_between_0_and_100(): void
    {
        $html = '<!DOCTYPE html><html><body><img src="1.jpg"></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThanOrEqual(0, $result->score);
        $this->assertLessThanOrEqual(100, $result->score);
    }

    public function test_perfect_images_get_high_score(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="image.webp" alt="Descriptive alt text" loading="lazy">
            <img src="image2.webp" alt="Another descriptive text" loading="lazy">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThan(80, $result->score);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_returns_failed_result_on_http_error(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(403, [], 'Forbidden')]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertNotNull($result->error);
        $this->assertEquals(0, $result->score);
    }

    public function test_includes_processing_time(): void
    {
        $html = '<!DOCTYPE html><html><body><img src="1.jpg" alt="test"></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertIsInt($result->processingTimeMs);
        $this->assertGreaterThanOrEqual(0, $result->processingTimeMs);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_handles_images_without_src(): void
    {
        $html = '<!DOCTYPE html><html><body><img alt="No src"></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        // Should handle gracefully - images without src are skipped
        $this->assertEquals(0, $result->data['total_count']);
    }

    public function test_detects_short_alt_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="1.jpg" alt="a">
            <img src="2.jpg" alt="Good alt text">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $shortAltRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'short') && str_contains(strtolower($r['message']), 'alt')
        );
        $this->assertGreaterThanOrEqual(1, count($shortAltRecs));
    }

    public function test_stores_image_details(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <img src="test.jpg" alt="Test image" loading="lazy">
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertCount(1, $result->data['images']);
        $this->assertEquals('test.jpg', $result->data['images'][0]['src']);
        $this->assertEquals('Test image', $result->data['images'][0]['alt']);
        $this->assertTrue($result->data['images'][0]['has_alt']);
        $this->assertTrue($result->data['images'][0]['lazy']);
    }
}
