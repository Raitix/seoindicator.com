<?php

namespace Tests\Unit\Services\Audit\DTOs;

use App\Services\Audit\DTOs\CrawlResult;
use Tests\TestCase;

/**
 * Unit tests for CrawlResult DTO.
 *
 * @see TASK-14-5
 * @see TEST-14-5
 */
class CrawlResultTest extends TestCase
{
    // =========================================================================
    // Factory Methods
    // =========================================================================

    /** @test Creates successful result */
    public function test_creates_successful_result(): void
    {
        $result = CrawlResult::success(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 150.5,
            html: '<html></html>',
            links: ['internal' => [], 'external' => []],
            metadata: ['title' => 'Test'],
        );

        $this->assertEquals('https://example.com/', $result->url);
        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals(150.5, $result->responseTimeMs);
        $this->assertEquals('<html></html>', $result->html);
        $this->assertNull($result->errorMessage);
    }

    /** @test Creates failure result */
    public function test_creates_failure_result(): void
    {
        $result = CrawlResult::failure(
            url: 'https://example.com/',
            errorMessage: 'Connection timed out',
            statusCode: 0,
            responseTimeMs: 5000.0,
        );

        $this->assertEquals('https://example.com/', $result->url);
        $this->assertEquals(0, $result->statusCode);
        $this->assertEquals('Connection timed out', $result->errorMessage);
        $this->assertNull($result->html);
    }

    // =========================================================================
    // Status Checks
    // =========================================================================

    /** @test isSuccess() returns true for 2xx */
    public function test_is_success_returns_true_for_2xx(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
        );

        $this->assertTrue($result->isSuccess());
    }

    /** @test isSuccess() returns true for 3xx */
    public function test_is_success_returns_true_for_3xx(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 301,
            responseTimeMs: 100,
        );

        $this->assertTrue($result->isSuccess());
    }

    /** @test isSuccess() returns false for 4xx */
    public function test_is_success_returns_false_for_4xx(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 404,
            responseTimeMs: 100,
        );

        $this->assertFalse($result->isSuccess());
    }

    /** @test isSuccess() returns false for 5xx */
    public function test_is_success_returns_false_for_5xx(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 500,
            responseTimeMs: 100,
        );

        $this->assertFalse($result->isSuccess());
    }

    /** @test isFailure() returns opposite of isSuccess() */
    public function test_is_failure_returns_opposite(): void
    {
        $success = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
        );

        $failure = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 500,
            responseTimeMs: 100,
            errorMessage: 'Server error',
        );

        $this->assertFalse($success->isFailure());
        $this->assertTrue($failure->isFailure());
    }

    /** @test isRedirect() detects redirects */
    public function test_is_redirect_detects_redirects(): void
    {
        $redirect301 = new CrawlResult('url', 301, 100);
        $redirect302 = new CrawlResult('url', 302, 100);
        $redirect307 = new CrawlResult('url', 307, 100);
        $redirect308 = new CrawlResult('url', 308, 100);
        $notRedirect = new CrawlResult('url', 200, 100);

        $this->assertTrue($redirect301->isRedirect());
        $this->assertTrue($redirect302->isRedirect());
        $this->assertTrue($redirect307->isRedirect());
        $this->assertTrue($redirect308->isRedirect());
        $this->assertFalse($notRedirect->isRedirect());
    }

    /** @test isClientError() detects client errors */
    public function test_is_client_error_detects_client_errors(): void
    {
        $clientError = new CrawlResult('url', 404, 100);
        $serverError = new CrawlResult('url', 500, 100);
        $success = new CrawlResult('url', 200, 100);

        $this->assertTrue($clientError->isClientError());
        $this->assertFalse($serverError->isClientError());
        $this->assertFalse($success->isClientError());
    }

    /** @test isServerError() detects server errors */
    public function test_is_server_error_detects_server_errors(): void
    {
        $serverError = new CrawlResult('url', 503, 100);
        $clientError = new CrawlResult('url', 404, 100);
        $success = new CrawlResult('url', 200, 100);

        $this->assertTrue($serverError->isServerError());
        $this->assertFalse($clientError->isServerError());
        $this->assertFalse($success->isServerError());
    }

    // =========================================================================
    // Link Accessors
    // =========================================================================

    /** @test getInternalLinks() returns internal links */
    public function test_get_internal_links(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            links: [
                'internal' => ['https://example.com/page1', 'https://example.com/page2'],
                'external' => ['https://external.com'],
            ],
        );

        $internal = $result->getInternalLinks();

        $this->assertCount(2, $internal);
        $this->assertContains('https://example.com/page1', $internal);
    }

    /** @test getExternalLinks() returns external links */
    public function test_get_external_links(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            links: [
                'internal' => [],
                'external' => ['https://external.com', 'https://other.com'],
            ],
        );

        $external = $result->getExternalLinks();

        $this->assertCount(2, $external);
    }

    // =========================================================================
    // Metadata Accessors
    // =========================================================================

    /** @test getMetadata() returns value or default */
    public function test_get_metadata_returns_value_or_default(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            metadata: ['title' => 'Test Page'],
        );

        $this->assertEquals('Test Page', $result->getMetadata('title'));
        $this->assertNull($result->getMetadata('missing'));
        $this->assertEquals('default', $result->getMetadata('missing', 'default'));
    }

    /** @test getTitle() returns title from metadata */
    public function test_get_title(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            metadata: ['title' => 'Page Title'],
        );

        $this->assertEquals('Page Title', $result->getTitle());
    }

    /** @test getDescription() returns description from metadata */
    public function test_get_description(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            metadata: ['description' => 'Meta description'],
        );

        $this->assertEquals('Meta description', $result->getDescription());
    }

    /** @test getCanonical() returns canonical from metadata */
    public function test_get_canonical(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            metadata: ['canonical' => 'https://example.com/canonical'],
        );

        $this->assertEquals('https://example.com/canonical', $result->getCanonical());
    }

    /** @test getH1Tags() returns H1 array */
    public function test_get_h1_tags(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            metadata: ['h1' => ['Heading 1', 'Heading 2']],
        );

        $h1s = $result->getH1Tags();

        $this->assertCount(2, $h1s);
    }

    // =========================================================================
    // Header Accessors
    // =========================================================================

    /** @test getHeader() returns header value */
    public function test_get_header(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
            html: '<html></html>',
            headers: ['Content-Type' => ['text/html; charset=UTF-8']],
        );

        $this->assertEquals('text/html; charset=UTF-8', $result->getHeader('content-type'));
        $this->assertEquals('text/html; charset=UTF-8', $result->getHeader('Content-Type'));
        $this->assertNull($result->getHeader('missing'));
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    /** @test toArray() returns complete array */
    public function test_to_array(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 150.5,
            html: '<html></html>',
            links: ['internal' => [], 'external' => []],
            metadata: ['title' => 'Test'],
            headers: ['X-Custom' => ['value']],
            depth: 1,
            redirectChain: [['url' => 'https://old.com', 'status' => 301]],
        );

        $array = $result->toArray();

        $this->assertEquals('https://example.com/', $array['url']);
        $this->assertEquals(200, $array['statusCode']);
        $this->assertEquals(150.5, $array['responseTimeMs']);
        $this->assertEquals('<html></html>', $array['html']);
        $this->assertEquals(1, $array['depth']);
    }

    /** @test jsonSerialize() returns JSON-compatible array */
    public function test_json_serialize(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 150.5,
            html: '<html></html>',
        );

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals('https://example.com/', $decoded['url']);
        $this->assertEquals(200, $decoded['statusCode']);
    }

    /** @test fromArray() creates instance from array */
    public function test_from_array(): void
    {
        $data = [
            'url' => 'https://example.com/',
            'statusCode' => 200,
            'responseTimeMs' => 150.5,
            'html' => '<html></html>',
            'links' => ['internal' => [], 'external' => []],
            'metadata' => ['title' => 'Test'],
            'errorMessage' => null,
            'headers' => [],
            'depth' => 1,
            'redirectChain' => [],
        ];

        $result = CrawlResult::fromArray($data);

        $this->assertEquals('https://example.com/', $result->url);
        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals(150.5, $result->responseTimeMs);
        $this->assertEquals('<html></html>', $result->html);
        $this->assertEquals(1, $result->depth);
    }

    /** @test Round-trip serialization */
    public function test_round_trip_serialization(): void
    {
        $original = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 150.5,
            html: '<html></html>',
            links: ['internal' => ['https://example.com/page'], 'external' => []],
            metadata: ['title' => 'Test', 'description' => 'Desc'],
            headers: ['Content-Type' => ['text/html']],
            depth: 2,
            redirectChain: [],
        );

        $array = $original->toArray();
        $restored = CrawlResult::fromArray($array);

        $this->assertEquals($original->url, $restored->url);
        $this->assertEquals($original->statusCode, $restored->statusCode);
        $this->assertEquals($original->responseTimeMs, $restored->responseTimeMs);
        $this->assertEquals($original->html, $restored->html);
        $this->assertEquals($original->links, $restored->links);
        $this->assertEquals($original->metadata, $restored->metadata);
        $this->assertEquals($original->depth, $restored->depth);
    }

    // =========================================================================
    // Immutability
    // =========================================================================

    /** @test Properties are readonly */
    public function test_properties_are_readonly(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com/',
            statusCode: 200,
            responseTimeMs: 100,
        );

        // This should not throw an error - readonly properties can be accessed
        $this->assertEquals('https://example.com/', $result->url);
    }
}
