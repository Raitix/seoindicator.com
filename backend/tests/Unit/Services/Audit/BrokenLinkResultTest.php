<?php

namespace Tests\Unit\Services\Audit;

use PHPUnit\Framework\TestCase;
use App\Services\Audit\BrokenLinkResult;

/**
 * Unit tests for BrokenLinkResult DTO
 *
 * Tests the data transfer object representing a single link check result.
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class BrokenLinkResultTest extends TestCase
{
    // =========================================================================
    // DTO CREATION TESTS
    // =========================================================================

    /**
     * Test DTO can be created with all properties.
     * 
     * @test
     */
    public function dto_can_be_created_with_all_properties(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/page',
            sourceUrl: 'https://example.com/source',
            statusCode: 200,
            type: 'valid',
            redirectUrl: null,
            responseTimeMs: 150,
            linkText: 'Click Here',
            elementType: 'a',
            errorMessage: null,
            surroundingText: 'Some context text'
        );

        $this->assertEquals('https://example.com/page', $result->url);
        $this->assertEquals('https://example.com/source', $result->sourceUrl);
        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals('valid', $result->type);
        $this->assertNull($result->redirectUrl);
        $this->assertEquals(150, $result->responseTimeMs);
        $this->assertEquals('Click Here', $result->linkText);
        $this->assertEquals('a', $result->elementType);
        $this->assertNull($result->errorMessage);
        $this->assertEquals('Some context text', $result->surroundingText);
    }

    /**
     * Test DTO can be created with minimal properties.
     * 
     * @test
     */
    public function dto_can_be_created_with_minimal_properties(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/page',
            sourceUrl: 'https://example.com/source',
            statusCode: 404,
            type: 'broken'
        );

        $this->assertEquals('https://example.com/page', $result->url);
        $this->assertEquals(404, $result->statusCode);
        $this->assertEquals('broken', $result->type);
        $this->assertNull($result->redirectUrl);
        $this->assertNull($result->linkText);
        $this->assertEquals('a', $result->elementType); // Default element type is 'a'
    }

    /**
     * Test DTO with redirect information.
     * 
     * @test
     */
    public function dto_with_redirect_information(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/old',
            sourceUrl: 'https://example.com/source',
            statusCode: 301,
            type: 'redirect',
            redirectUrl: 'https://example.com/new'
        );

        $this->assertEquals('redirect', $result->type);
        $this->assertEquals('https://example.com/new', $result->redirectUrl);
    }

    /**
     * Test DTO with error information.
     * 
     * @test
     */
    public function dto_with_error_information(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/error',
            sourceUrl: 'https://example.com/source',
            statusCode: 500,
            type: 'error',
            errorMessage: 'Internal Server Error'
        );

        $this->assertEquals('error', $result->type);
        $this->assertEquals('Internal Server Error', $result->errorMessage);
    }

    /**
     * Test DTO with timeout.
     * 
     * @test
     */
    public function dto_with_timeout(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://slow-example.com/page',
            sourceUrl: 'https://example.com/source',
            statusCode: 0,
            type: 'timeout',
            errorMessage: 'Connection timed out after 10s'
        );

        $this->assertEquals('timeout', $result->type);
        $this->assertEquals(0, $result->statusCode);
    }

    // =========================================================================
    // TYPE VALIDATION TESTS
    // =========================================================================

    /**
     * Test valid type.
     * 
     * @test
     */
    public function valid_type(): void
    {
        $result = $this->createResult(['type' => 'valid']);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * Test broken type.
     * 
     * @test
     */
    public function broken_type(): void
    {
        $result = $this->createResult(['type' => 'broken']);
        $this->assertEquals('broken', $result->type);
    }

    /**
     * Test redirect type.
     * 
     * @test
     */
    public function redirect_type(): void
    {
        $result = $this->createResult(['type' => 'redirect']);
        $this->assertEquals('redirect', $result->type);
    }

    /**
     * Test timeout type.
     * 
     * @test
     */
    public function timeout_type(): void
    {
        $result = $this->createResult(['type' => 'timeout']);
        $this->assertEquals('timeout', $result->type);
    }

    /**
     * Test error type.
     * 
     * @test
     */
    public function error_type(): void
    {
        $result = $this->createResult(['type' => 'error']);
        $this->assertEquals('error', $result->type);
    }

    /**
     * Test access_denied type.
     * 
     * @test
     */
    public function access_denied_type(): void
    {
        $result = $this->createResult(['type' => 'access_denied']);
        $this->assertEquals('access_denied', $result->type);
    }

    /**
     * Test auth_required type.
     * 
     * @test
     */
    public function auth_required_type(): void
    {
        $result = $this->createResult(['type' => 'auth_required']);
        $this->assertEquals('auth_required', $result->type);
    }

    /**
     * Test gone type.
     * 
     * @test
     */
    public function gone_type(): void
    {
        $result = $this->createResult(['type' => 'gone']);
        $this->assertEquals('gone', $result->type);
    }

    /**
     * Test rate_limited type.
     * 
     * @test
     */
    public function rate_limited_type(): void
    {
        $result = $this->createResult(['type' => 'rate_limited']);
        $this->assertEquals('rate_limited', $result->type);
    }

    // =========================================================================
    // ELEMENT TYPE TESTS
    // =========================================================================

    /**
     * Test anchor element type.
     * 
     * @test
     */
    public function anchor_element_type(): void
    {
        $result = $this->createResult(['elementType' => 'a']);
        $this->assertEquals('a', $result->elementType);
    }

    /**
     * Test image element type.
     * 
     * @test
     */
    public function image_element_type(): void
    {
        $result = $this->createResult(['elementType' => 'img']);
        $this->assertEquals('img', $result->elementType);
    }

    /**
     * Test link element type.
     * 
     * @test
     */
    public function link_element_type(): void
    {
        $result = $this->createResult(['elementType' => 'link']);
        $this->assertEquals('link', $result->elementType);
    }

    /**
     * Test script element type.
     * 
     * @test
     */
    public function script_element_type(): void
    {
        $result = $this->createResult(['elementType' => 'script']);
        $this->assertEquals('script', $result->elementType);
    }

    /**
     * Test iframe element type.
     * 
     * @test
     */
    public function iframe_element_type(): void
    {
        $result = $this->createResult(['elementType' => 'iframe']);
        $this->assertEquals('iframe', $result->elementType);
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    /**
     * Test toArray serialization.
     * 
     * @test
     */
    public function to_array_serialization(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/page',
            sourceUrl: 'https://example.com/source',
            statusCode: 200,
            type: 'valid',
            responseTimeMs: 150,
            linkText: 'Click Here',
            elementType: 'a'
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('https://example.com/page', $array['url']);
        $this->assertEquals('https://example.com/source', $array['sourceUrl']);
        $this->assertEquals(200, $array['statusCode']);
        $this->assertEquals('valid', $array['type']);
        $this->assertEquals(150, $array['responseTimeMs']);
        $this->assertEquals('Click Here', $array['linkText']);
        $this->assertEquals('a', $array['elementType']);
    }

    /**
     * Test JSON serialization.
     * 
     * @test
     */
    public function json_serialization(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/page',
            sourceUrl: 'https://example.com/source',
            statusCode: 404,
            type: 'broken'
        );

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertEquals('https://example.com/page', $decoded['url']);
        $this->assertEquals(404, $decoded['statusCode']);
        $this->assertEquals('broken', $decoded['type']);
    }

    /**
     * Test JSON serialization with redirect.
     * 
     * @test
     */
    public function json_serialization_with_redirect(): void
    {
        $result = new BrokenLinkResult(
            url: 'https://example.com/old',
            sourceUrl: 'https://example.com/source',
            statusCode: 301,
            type: 'redirect',
            redirectUrl: 'https://example.com/new'
        );

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals('https://example.com/new', $decoded['redirectUrl']);
    }

    // =========================================================================
    // FACTORY METHOD TESTS
    // =========================================================================

    /**
     * Test creating valid result.
     * 
     * @test
     */
    public function creating_valid_result(): void
    {
        $result = BrokenLinkResult::valid(
            url: 'https://example.com/page',
            sourceUrl: 'https://example.com/source',
            responseTimeMs: 100
        );

        $this->assertEquals('valid', $result->type);
        $this->assertEquals(200, $result->statusCode);
    }

    /**
     * Test creating broken result.
     * 
     * @test
     */
    public function creating_broken_result(): void
    {
        $result = BrokenLinkResult::broken(
            url: 'https://example.com/notfound',
            sourceUrl: 'https://example.com/source',
            statusCode: 404
        );

        $this->assertEquals('broken', $result->type);
        $this->assertEquals(404, $result->statusCode);
    }

    /**
     * Test creating redirect result.
     * 
     * @test
     */
    public function creating_redirect_result(): void
    {
        $result = BrokenLinkResult::redirect(
            url: 'https://example.com/old',
            sourceUrl: 'https://example.com/source',
            statusCode: 301,
            redirectUrl: 'https://example.com/new'
        );

        $this->assertEquals('redirect', $result->type);
        $this->assertEquals(301, $result->statusCode);
        $this->assertEquals('https://example.com/new', $result->redirectUrl);
    }

    /**
     * Test creating timeout result.
     * 
     * @test
     */
    public function creating_timeout_result(): void
    {
        $result = BrokenLinkResult::timeout(
            url: 'https://slow-example.com/page',
            sourceUrl: 'https://example.com/source',
            errorMessage: 'Connection timed out'
        );

        $this->assertEquals('timeout', $result->type);
        $this->assertEquals(0, $result->statusCode);
        $this->assertEquals('Connection timed out', $result->errorMessage);
    }

    /**
     * Test creating error result.
     * 
     * @test
     */
    public function creating_error_result(): void
    {
        $result = BrokenLinkResult::error(
            url: 'https://example.com/error',
            sourceUrl: 'https://example.com/source',
            statusCode: 500,
            errorMessage: 'Internal Server Error'
        );

        $this->assertEquals('error', $result->type);
        $this->assertEquals(500, $result->statusCode);
        $this->assertEquals('Internal Server Error', $result->errorMessage);
    }

    /**
     * Test creating blocked result.
     * 
     * @test
     */
    public function creating_blocked_result(): void
    {
        $result = BrokenLinkResult::blocked(
            url: 'http://localhost/admin',
            sourceUrl: 'https://example.com/source',
            reason: 'SSRF protection'
        );

        $this->assertEquals('blocked', $result->type);
        $this->assertEquals('SSRF protection', $result->errorMessage);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a result with custom properties.
     */
    private function createResult(array $overrides = []): BrokenLinkResult
    {
        return new BrokenLinkResult(
            url: $overrides['url'] ?? 'https://example.com/page',
            sourceUrl: $overrides['sourceUrl'] ?? 'https://example.com/source',
            statusCode: $overrides['statusCode'] ?? 200,
            type: $overrides['type'] ?? 'valid',
            redirectUrl: $overrides['redirectUrl'] ?? null,
            responseTimeMs: $overrides['responseTimeMs'] ?? 100,
            linkText: $overrides['linkText'] ?? null,
            elementType: $overrides['elementType'] ?? 'a',
            errorMessage: $overrides['errorMessage'] ?? null,
            surroundingText: $overrides['surroundingText'] ?? null
        );
    }
}
