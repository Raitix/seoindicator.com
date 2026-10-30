<?php

namespace Tests\Unit\Services\Analysis\Indicators;

use App\Services\Analysis\Indicators\LinksProcessor;
use App\Services\Analysis\Indicators\IndicatorResult;
use App\Services\URLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LinksProcessor.
 * 
 * @see TASK-19-6
 * @see TEST-19-6
 */
class LinksProcessorTest extends TestCase
{
    private function createProcessor(array $options = []): LinksProcessor
    {
        $urlValidator = $options['urlValidator'] ?? $this->createMock(URLValidationService::class);
        
        $processor = new LinksProcessor($urlValidator);
        
        if (isset($options['httpClient'])) {
            $reflection = new \ReflectionClass($processor);
            $property = $reflection->getProperty('httpClient');
            $property->setAccessible(true);
            $property->setValue($processor, $options['httpClient']);
        }
        
        return $processor;
    }

    // ==================== BASIC TESTS ====================

    public function test_slug_returns_links(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('links', $processor->slug());
    }

    public function test_name_returns_human_readable(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals('Links Analysis', $processor->name());
    }

    public function test_max_score_is_100(): void
    {
        $processor = $this->createProcessor();
        
        $this->assertEquals(100.0, $processor->maxScore());
    }

    // ==================== LINK COUNT TESTS ====================

    public function test_counts_total_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Link 1</a>
            <a href="/page2">Link 2</a>
            <a href="https://external.com">External</a>
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

    public function test_handles_page_with_no_links(): void
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
    }

    // ==================== INTERNAL VS EXTERNAL TESTS ====================

    public function test_distinguishes_internal_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Internal 1</a>
            <a href="/page2">Internal 2</a>
            <a href="https://example.com/page3">Also Internal</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(3, $result->data['internal']['count']);
        $this->assertEquals(0, $result->data['external']['count']);
    }

    public function test_distinguishes_external_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Internal</a>
            <a href="https://external.com">External 1</a>
            <a href="https://other.com">External 2</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['internal']['count']);
        $this->assertEquals(2, $result->data['external']['count']);
    }

    // ==================== NOFOLLOW TESTS ====================

    public function test_detects_nofollow_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://external.com" rel="nofollow">NoFollow</a>
            <a href="https://other.com">Regular</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['external']['nofollow_count']);
    }

    public function test_detects_sponsored_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://sponsor.com" rel="sponsored">Sponsored</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['external']['sponsored_count']);
    }

    public function test_detects_ugc_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://user.com" rel="ugc">UGC</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['external']['ugc_count']);
    }

    // ==================== TARGET BLANK TESTS ====================

    public function test_detects_target_blank(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://external.com" target="_blank">Opens New Tab</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['external']['opens_new_tab_count']);
    }

    public function test_flags_missing_noopener(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://external.com" target="_blank">Missing Noopener</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['external']['missing_noopener_count']);
        
        $noopenerRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'noopener')
        );
        $this->assertGreaterThanOrEqual(1, count($noopenerRecs));
    }

    public function test_accepts_link_with_noopener(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://external.com" target="_blank" rel="noopener">Safe Link</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(0, $result->data['external']['missing_noopener_count']);
    }

    // ==================== LINK TEXT TESTS ====================

    public function test_detects_generic_link_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Click here</a>
            <a href="/page2">Read more</a>
            <a href="/page3">Proper description</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['internal']['generic_text_count']);
        $this->assertEquals(1, $result->data['internal']['descriptive_text_count']);
    }

    public function test_recommends_descriptive_link_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Click here</a>
            <a href="/page2">Read more</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $genericRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'generic') || 
            str_contains(strtolower($r['message']), 'descriptive')
        );
        $this->assertGreaterThanOrEqual(1, count($genericRecs));
    }

    // ==================== LINKS TO SKIP TESTS ====================

    public function test_skips_anchor_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="#section">Jump to section</a>
            <a href="/page">Real link</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['total_count']);
    }

    public function test_skips_javascript_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="javascript:void(0)">JS Link</a>
            <a href="/page">Real link</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['total_count']);
    }

    public function test_skips_mailto_links(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="mailto:test@example.com">Email</a>
            <a href="/page">Real link</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['total_count']);
    }

    // ==================== SUMMARY TESTS ====================

    public function test_generates_summary(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Internal 1</a>
            <a href="/page2">Internal 2</a>
            <a href="https://external.com" rel="nofollow">External</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(2, $result->data['summary']['internal_count']);
        $this->assertEquals(1, $result->data['summary']['external_count']);
        $this->assertEquals(1, $result->data['summary']['nofollow_count']);
    }

    // ==================== RECOMMENDATION TESTS ====================

    public function test_recommends_adding_internal_links(): void
    {
        $html = '<!DOCTYPE html><html><body><p>No links</p></body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $linkRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'internal') || 
            str_contains(strtolower($r['message']), 'link')
        );
        $this->assertGreaterThanOrEqual(1, count($linkRecs));
    }

    // ==================== SCORE CALCULATION TESTS ====================

    public function test_score_is_between_0_and_100(): void
    {
        $html = '<!DOCTYPE html><html><body><a href="/page">Link</a></body></html>';
        
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

    public function test_good_links_get_high_score(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">Learn about our services</a>
            <a href="/page2">Contact our team</a>
            <a href="/page3">View our portfolio</a>
            <a href="https://partner.com" rel="noopener nofollow" target="_blank">Partner site</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertGreaterThan(70, $result->score);
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
        $html = '<!DOCTYPE html><html><body><a href="/page">Link</a></body></html>';
        
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

    // ==================== LINK TEXT LENGTH TESTS ====================

    public function test_analyzes_link_text_length(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page1">A</a>
            <a href="/page2">This is a good link text</a>
            <a href="/page3">This is a very long link text that exceeds the recommended sixty characters limit</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $this->assertEquals(1, $result->data['link_text_analysis']['short_text_count']);
        $this->assertEquals(1, $result->data['link_text_analysis']['optimal_text_count']);
        $this->assertEquals(1, $result->data['link_text_analysis']['long_text_count']);
    }

    public function test_recommends_fixing_short_link_text(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/page">a</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        $shortTextRecs = array_filter($result->recommendations, fn($r) => 
            str_contains(strtolower($r['message']), 'short') && str_contains(strtolower($r['message']), 'anchor')
        );
        $this->assertGreaterThanOrEqual(1, count($shortTextRecs));
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_handles_links_without_href(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a>No href</a>
            <a href="/page">Valid</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        // Links without href should be skipped
        $this->assertEquals(1, $result->data['total_count']);
    }

    public function test_case_insensitive_host_comparison(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="https://EXAMPLE.COM/page">Same Domain Different Case</a>
        </body></html>';
        
        $urlValidator = $this->createMock(URLValidationService::class);
        $urlValidator->method('validate')->willReturn(['valid' => true, 'normalized_url' => 'https://example.com']);
        
        $mock = new MockHandler([new Response(200, [], $html)]);
        $processor = $this->createProcessor([
            'urlValidator' => $urlValidator,
            'httpClient' => new Client(['handler' => HandlerStack::create($mock)]),
        ]);

        $result = $processor->process('https://example.com');

        // Should be treated as internal (same domain, different case)
        $this->assertEquals(1, $result->data['internal']['count']);
    }
}
