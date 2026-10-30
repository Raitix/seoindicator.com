<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\MetaTagsProcessor;
use App\Services\Analysis\Indicators\IndicatorResult;
use App\Services\URLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MetaTagsProcessor.
 * 
 * @see TASK-19-6
 * @see TEST-19-6
 */
class MetaTagsProcessorTest extends TestCase
{
    private function createProcessor(array $options = []): MetaTagsProcessor
    {
        $urlValidator = $options['urlValidator'] ?? $this->createMock(URLValidationService::class);
        
        $processor = new MetaTagsProcessor($urlValidator);
        
        if (isset($options['httpClient'])) {
            $reflection = new \ReflectionClass($processor);
            $property = $reflection->getProperty('httpClient');
            $property->setAccessible(true);
            $property->setValue($processor, $options['httpClient']);
        }
        
        return $processor;
    }

    // ==================== BASIC TESTS ====================

    public function test_slug_returns_meta_tags(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('meta_tags', $processor->slug());
    }

    public function test_name_returns_human_readable(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('Meta Tags', $processor->name());
    }

    public function test_max_score_is_100(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals(100.0, $processor->maxScore());
    }

    // ==================== TITLE TAG TESTS ====================

    public function test_detects_present_title_tag(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test Page Title</title></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->data['title']['present']);
        $this->assertEquals('Test Page Title', $result->data['title']['text']);
    }

    public function test_handles_missing_title_tag(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertFalse($result->data['title']['present']);
        $this->assertGreaterThanOrEqual(1, count($result->recommendations));
    }

    public function test_scores_optimal_title_length_50_60(): void
    {
        $optimalTitle = str_repeat('a', 55); // 55 chars, within optimal range
        $html = "<!DOCTYPE html><html><head><title>{$optimalTitle}</title></head><body></body></html>";
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals('optimal', $result->data['title']['length_status']);
    }

    public function test_scores_short_title(): void
    {
        $shortTitle = 'Hi'; // 2 chars
        $html = "<!DOCTYPE html><html><head><title>{$shortTitle}</title></head><body></body></html>";
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals('too_short', $result->data['title']['length_status']);
    }

    public function test_scores_long_title(): void
    {
        $longTitle = str_repeat('a', 80); // 80 chars
        $html = "<!DOCTYPE html><html><head><title>{$longTitle}</title></head><body></body></html>";
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals('too_long', $result->data['title']['length_status']);
    }

    // ==================== META DESCRIPTION TESTS ====================

    public function test_detects_meta_description(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="This is a test description for the page."></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertTrue($result->data['description']['present']);
        $this->assertEquals('This is a test description for the page.', $result->data['description']['text']);
    }

    public function test_handles_missing_meta_description(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertFalse($result->data['description']['present']);
    }

    public function test_scores_optimal_description_length(): void
    {
        $optimalDesc = str_repeat('a', 155); // 155 chars, within 150-160 optimal range
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$optimalDesc}\"></head><body></body></html>";
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals('optimal', $result->data['description']['length_status']);
    }

    // ==================== OPEN GRAPH TESTS ====================

    public function test_detects_open_graph_tags(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta property="og:title" content="OG Title">
            <meta property="og:description" content="OG Description">
            <meta property="og:image" content="https://example.com/image.jpg">
        </head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertTrue($result->data['open_graph']['present']);
        $this->assertEquals('OG Title', $result->data['open_graph']['og:title']);
        $this->assertEquals('OG Description', $result->data['open_graph']['og:description']);
        $this->assertEquals('https://example.com/image.jpg', $result->data['open_graph']['og:image']);
    }

    public function test_handles_missing_open_graph_tags(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertFalse($result->data['open_graph']['present']);
    }

    // ==================== TWITTER CARD TESTS ====================

    public function test_detects_twitter_card_tags(): void
    {
        $html = '<!DOCTYPE html><html><head>
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="Twitter Title">
            <meta name="twitter:description" content="Twitter Description">
            <meta name="twitter:image" content="https://example.com/twitter.jpg">
        </head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertTrue($result->data['twitter_cards']['present']);
        $this->assertEquals('summary_large_image', $result->data['twitter_cards']['twitter:card']);
    }

    // ==================== RECOMMENDATION TESTS ====================

    public function test_recommends_adding_missing_title(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $titleRecommendations = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'title')
        );
        $this->assertGreaterThanOrEqual(1, count($titleRecommendations));
    }

    public function test_recommends_adding_missing_description(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $descRecommendations = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'description')
        );
        $this->assertGreaterThanOrEqual(1, count($descRecommendations));
    }

    // ==================== SCORE CALCULATION TESTS ====================

    public function test_score_is_between_0_and_100(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        
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

    public function test_perfect_page_gets_high_score(): void
    {
        $optimalTitle = str_repeat('a', 55);
        $optimalDesc = str_repeat('b', 155);
        $html = "<!DOCTYPE html><html><head>
            <title>{$optimalTitle}</title>
            <meta name=\"description\" content=\"{$optimalDesc}\">
            <meta property=\"og:title\" content=\"OG Title\">
            <meta property=\"og:description\" content=\"OG Desc\">
            <meta property=\"og:image\" content=\"https://example.com/img.jpg\">
            <meta name=\"twitter:card\" content=\"summary\">
            <meta name=\"twitter:title\" content=\"Twitter Title\">
            <meta name=\"twitter:description\" content=\"Twitter Desc\">
            <meta name=\"twitter:image\" content=\"https://example.com/twitter.jpg\">
        </head><body></body></html>";
        
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

    public function test_handles_empty_title_tag(): void
    {
        $html = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertFalse($result->data['title']['present']);
    }

    public function test_returns_failed_result_on_http_error(): void
    {
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(404, [], 'Not Found')]);
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
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        
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
}
