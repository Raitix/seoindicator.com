<?php

namespace Tests\Unit\Services\Playwright;

use Tests\TestCase;
use App\Services\Playwright\Data\RenderResult;

/**
 * Unit tests for the RenderResult DTO.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class RenderResultTest extends TestCase
{
    // ==========================================
    // Construction Tests
    // ==========================================

    /**
     * Test RenderResult can be created with all fields.
     */
    public function test_render_result_can_be_created(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com/old',
            title: 'Test Page',
            renderedHtml: '<html><body>Test</body></html>',
            structuredText: 'Test content',
            meta: ['description' => 'Test'],
            links: [['url' => 'https://example.com/page']],
            size: 1024,
            loadTime: 500,
            statusCode: 200,
            error: null
        );

        $this->assertEquals('https://example.com', $result->url);
        $this->assertEquals('https://example.com/old', $result->originalUrl);
        $this->assertEquals('Test Page', $result->title);
        $this->assertEquals('<html><body>Test</body></html>', $result->renderedHtml);
        $this->assertEquals('Test content', $result->structuredText);
        $this->assertEquals(['description' => 'Test'], $result->meta);
        $this->assertCount(1, $result->links);
        $this->assertEquals(1024, $result->size);
        $this->assertEquals(500, $result->loadTime);
        $this->assertEquals(200, $result->statusCode);
        $this->assertNull($result->error);
    }

    /**
     * Test RenderResult with error.
     */
    public function test_render_result_with_error(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: null,
            renderedHtml: null,
            structuredText: null,
            meta: [],
            links: [],
            size: 0,
            loadTime: 0,
            statusCode: 0,
            error: 'Timeout waiting for page'
        );

        $this->assertNotNull($result->error);
        $this->assertEquals('Timeout waiting for page', $result->error);
    }

    /**
     * Test RenderResult toArray method.
     */
    public function test_render_result_to_array(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: 'Test',
            renderedHtml: '<html></html>',
            structuredText: 'Test',
            meta: ['key' => 'value'],
            links: [],
            size: 100,
            loadTime: 50,
            statusCode: 200,
            error: null
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('originalUrl', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('renderedHtml', $array);
        $this->assertArrayHasKey('structuredText', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('links', $array);
        $this->assertArrayHasKey('size', $array);
        $this->assertArrayHasKey('loadTime', $array);
        $this->assertArrayHasKey('statusCode', $array);
        $this->assertArrayHasKey('error', $array);
    }

    /**
     * Test RenderResult isSuccess returns true when no error.
     */
    public function test_render_result_is_success_true(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: 'Test',
            renderedHtml: '<html></html>',
            structuredText: 'Test',
            meta: [],
            links: [],
            size: 100,
            loadTime: 50,
            statusCode: 200,
            error: null
        );

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test RenderResult isSuccess returns false when error.
     */
    public function test_render_result_is_success_false(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: null,
            renderedHtml: null,
            structuredText: null,
            meta: [],
            links: [],
            size: 0,
            loadTime: 0,
            statusCode: 0,
            error: 'Failed'
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * Test RenderResult size formatting.
     */
    public function test_render_result_size_formatting(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: 'Test',
            renderedHtml: str_repeat('a', 2048),
            structuredText: 'Test',
            meta: [],
            links: [],
            size: 2048,
            loadTime: 50,
            statusCode: 200,
            error: null
        );

        $this->assertEquals('2 KB', $result->getFormattedSize());
    }

    /**
     * Test RenderResult load time formatting.
     */
    public function test_render_result_load_time_formatting(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: 'Test',
            renderedHtml: '<html></html>',
            structuredText: 'Test',
            meta: [],
            links: [],
            size: 100,
            loadTime: 1500,
            statusCode: 200,
            error: null
        );

        $this->assertEquals('1.5s', $result->getFormattedLoadTime());
    }

    /**
     * Test RenderResult JSON serialization.
     */
    public function test_render_result_json_serialization(): void
    {
        $result = new RenderResult(
            url: 'https://example.com',
            originalUrl: 'https://example.com',
            title: 'Test',
            renderedHtml: '<html></html>',
            structuredText: 'Test',
            meta: ['key' => 'value'],
            links: [['url' => 'https://example.com/page']],
            size: 100,
            loadTime: 50,
            statusCode: 200,
            error: null
        );

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals('https://example.com', $decoded['url']);
        $this->assertEquals('Test', $decoded['title']);
    }
}
