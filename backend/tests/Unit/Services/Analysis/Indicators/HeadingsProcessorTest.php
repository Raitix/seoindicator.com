<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\HeadingsProcessor;
use App\Services\Analysis\Indicators\IndicatorResult;
use App\Services\URLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HeadingsProcessor.
 * 
 * @see TASK-19-6
 * @see TEST-19-6
 */
class HeadingsProcessorTest extends TestCase
{
    private function createProcessor(array $options = []): HeadingsProcessor
    {
        $urlValidator = $options['urlValidator'] ?? $this->createMock(URLValidationService::class);
        
        $processor = new HeadingsProcessor($urlValidator);
        
        if (isset($options['httpClient'])) {
            $reflection = new \ReflectionClass($processor);
            $property = $reflection->getProperty('httpClient');
            $property->setAccessible(true);
            $property->setValue($processor, $options['httpClient']);
        }
        
        return $processor;
    }

    // ==================== BASIC TESTS ====================

    public function test_slug_returns_headings(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('headings', $processor->slug());
    }

    public function test_name_returns_human_readable(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('Headings Structure', $processor->name());
    }

    public function test_max_score_is_100(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals(100.0, $processor->maxScore());
    }

    // ==================== H1 TESTS ====================

    public function test_detects_single_h1(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Main Title</h1></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['h1_analysis']['count']);
        $this->assertContains('Main Title', $result->data['h1_analysis']['texts']);
    }

    public function test_flags_multiple_h1s_as_error(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>First H1</h1><h1>Second H1</h1></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['h1_analysis']['count']);
        
        $h1Recommendations = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'multiple') && str_contains(strtolower($r['message']), 'h1')
        );
        $this->assertGreaterThanOrEqual(1, count($h1Recommendations));
    }

    public function test_handles_missing_h1(): void
    {
        $html = '<!DOCTYPE html><html><body><h2>Subheading</h2></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(0, $result->data['h1_analysis']['count']);
        
        $h1Recommendations = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'missing') && str_contains(strtolower($r['message']), 'h1')
        );
        $this->assertGreaterThanOrEqual(1, count($h1Recommendations));
    }

    public function test_scores_single_h1_as_optimal(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Main Title</h1><h2>Section</h2></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThan(0, $result->data['h1_analysis']['count']);
        $this->assertLessThanOrEqual(1, $result->data['h1_analysis']['count']);
    }

    // ==================== HEADING HIERARCHY TESTS ====================

    public function test_validates_proper_hierarchy(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>Main</h1>
            <h2>Section 1</h2>
            <h3>Subsection 1.1</h3>
            <h2>Section 2</h2>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertTrue($result->data['hierarchy_analysis']['has_proper_hierarchy']);
    }

    public function test_flags_skipped_levels(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>Main</h1>
            <h3>Skipped H2</h3>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertFalse($result->data['hierarchy_analysis']['has_proper_hierarchy']);
        $this->assertNotEmpty($result->data['hierarchy_analysis']['skipped_levels']);
    }

    public function test_detects_h1_to_h3_skip(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>Main</h1>
            <h3>Subsection</h3>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $skippedLevels = $result->data['hierarchy_analysis']['skipped_levels'];
        $this->assertNotEmpty($skippedLevels);
        
        $hasMissingH2 = false;
        foreach ($skippedLevels as $skip) {
            if ($skip['missing'] === 2) {
                $hasMissingH2 = true;
                break;
            }
        }
        $this->assertTrue($hasMissingH2);
    }

    // ==================== HEADING COUNT TESTS ====================

    public function test_counts_all_heading_levels(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>H1</h1>
            <h2>H2 A</h2>
            <h2>H2 B</h2>
            <h3>H3</h3>
            <h4>H4</h4>
            <h5>H5</h5>
            <h6>H6</h6>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['counts']['h1']);
        $this->assertEquals(2, $result->data['counts']['h2']);
        $this->assertEquals(1, $result->data['counts']['h3']);
        $this->assertEquals(1, $result->data['counts']['h4']);
        $this->assertEquals(1, $result->data['counts']['h5']);
        $this->assertEquals(1, $result->data['counts']['h6']);
        $this->assertEquals(7, $result->data['counts']['total']);
    }

    public function test_handles_page_with_no_headings(): void
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

        $this->assertEquals(0, $result->data['counts']['total']);
    }

    // ==================== OUTLINE GENERATION TESTS ====================

    public function test_generates_heading_outline(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>Main</h1>
            <h2>Section 1</h2>
            <h3>Subsection</h3>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertCount(3, $result->data['outline']);
        $this->assertEquals(1, $result->data['outline'][0]['level']);
        $this->assertEquals('Main', $result->data['outline'][0]['text']);
    }

    // ==================== EMPTY HEADING TESTS ====================

    public function test_detects_empty_heading_tags(): void
    {
        $html = '<!DOCTYPE html><html><body><h1></h1><h2>Valid</h2></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThan(0, $result->data['content_analysis']['empty_count']);
    }

    // ==================== SCORE CALCULATION TESTS ====================

    public function test_score_is_between_0_and_100(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        
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

    public function test_perfect_hierarchy_gets_high_score(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <h1>Main Title</h1>
            <h2>Section 1</h2>
            <h3>Subsection 1.1</h3>
            <h3>Subsection 1.2</h3>
            <h2>Section 2</h2>
            <h3>Subsection 2.1</h3>
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
        
        $mock = new MockHandler([new Response(500, [], 'Server Error')]);
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
        $html = '<!DOCTYPE html><html><body><h1>Test</h1></body></html>';
        
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

    // ==================== RECOMMENDATION TESTS ====================

    public function test_recommends_adding_h1_when_missing(): void
    {
        $html = '<!DOCTYPE html><html><body><h2>Subheading</h2></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $h1Recommendations = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'h1')
        );
        $this->assertGreaterThanOrEqual(1, count($h1Recommendations));
    }

    public function test_recommends_fixing_hierarchy(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Main</h1><h3>Skipped</h3></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $hierarchyRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'hierarchy') || 
            str_contains(strtolower($r['message']), 'skipped')
        );
        $this->assertGreaterThanOrEqual(1, count($hierarchyRecs));
    }

    // ==================== H1 LENGTH TESTS ====================

    public function test_flags_very_short_h1(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Hi</h1></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $shortRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'short')
        );
        $this->assertGreaterThanOrEqual(1, count($shortRecs));
    }

    public function test_flags_very_long_h1(): void
    {
        $longTitle = str_repeat('a', 80);
        $html = "<!DOCTYPE html><html><body><h1>{$longTitle}</h1></body></html>";
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $longRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'long')
        );
        $this->assertGreaterThanOrEqual(1, count($longRecs));
    }
}
