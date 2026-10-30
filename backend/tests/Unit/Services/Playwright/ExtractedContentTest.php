<?php

namespace Tests\Unit\Services\Playwright;

use Tests\TestCase;
use App\Services\Playwright\Data\ExtractedContent;

/**
 * Unit tests for the ExtractedContent DTO.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class ExtractedContentTest extends TestCase
{
    // ==========================================
    // Construction Tests
    // ==========================================

    /**
     * Test ExtractedContent can be created with all fields.
     */
    public function test_extracted_content_can_be_created(): void
    {
        $content = new ExtractedContent(
            html: '<html><body>Test</body></html>',
            title: 'Test Page',
            structuredText: 'Test content here',
            size: 1024
        );

        $this->assertEquals('<html><body>Test</body></html>', $content->html);
        $this->assertEquals('Test Page', $content->title);
        $this->assertEquals('Test content here', $content->structuredText);
        $this->assertEquals(1024, $content->size);
    }

    /**
     * Test ExtractedContent with minimal fields.
     */
    public function test_extracted_content_minimal(): void
    {
        $content = new ExtractedContent(
            html: '<html></html>',
            title: null,
            structuredText: null,
            size: 0
        );

        $this->assertEquals('<html></html>', $content->html);
        $this->assertNull($content->title);
        $this->assertNull($content->structuredText);
    }

    /**
     * Test ExtractedContent toArray method.
     */
    public function test_extracted_content_to_array(): void
    {
        $content = new ExtractedContent(
            html: '<html><body>Test</body></html>',
            title: 'Test',
            structuredText: 'Content',
            size: 100
        );

        $array = $content->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('html', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('structuredText', $array);
        $this->assertArrayHasKey('size', $array);
    }

    /**
     * Test ExtractedContent hasText returns true.
     */
    public function test_extracted_content_has_text_true(): void
    {
        $content = new ExtractedContent(
            html: '<html><body>Test</body></html>',
            title: 'Test',
            structuredText: 'Some text content',
            size: 100
        );

        $this->assertTrue($content->hasText());
    }

    /**
     * Test ExtractedContent hasText returns false.
     */
    public function test_extracted_content_has_text_false(): void
    {
        $content = new ExtractedContent(
            html: '<html></html>',
            title: null,
            structuredText: null,
            size: 0
        );

        $this->assertFalse($content->hasText());
    }

    /**
     * Test ExtractedContent word count.
     */
    public function test_extracted_content_word_count(): void
    {
        $content = new ExtractedContent(
            html: '<html><body>Test</body></html>',
            title: 'Test',
            structuredText: 'This is a test with seven words',
            size: 100
        );

        $this->assertEquals(7, $content->getWordCount());
    }

    /**
     * Test ExtractedContent word count with null text.
     */
    public function test_extracted_content_word_count_null(): void
    {
        $content = new ExtractedContent(
            html: '<html></html>',
            title: null,
            structuredText: null,
            size: 0
        );

        $this->assertEquals(0, $content->getWordCount());
    }

    /**
     * Test ExtractedContent JSON serialization.
     */
    public function test_extracted_content_json_serialization(): void
    {
        $content = new ExtractedContent(
            html: '<html><body>Test</body></html>',
            title: 'Test',
            structuredText: 'Content',
            size: 100
        );

        $json = json_encode($content);
        $decoded = json_decode($json, true);

        $this->assertEquals('Test', $decoded['title']);
        $this->assertEquals('Content', $decoded['structuredText']);
    }

    /**
     * Test ExtractedContent from array factory.
     */
    public function test_extracted_content_from_array(): void
    {
        $data = [
            'html' => '<html><body>Test</body></html>',
            'title' => 'From Array',
            'structuredText' => 'Array content',
            'size' => 500,
        ];

        $content = ExtractedContent::fromArray($data);

        $this->assertEquals('From Array', $content->title);
        $this->assertEquals(500, $content->size);
    }
}
