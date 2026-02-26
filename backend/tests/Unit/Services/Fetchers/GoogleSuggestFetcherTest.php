<?php

namespace Tests\Unit\Services\Fetchers;

use App\Services\Fetchers\GoogleSuggestFetcher;
use App\Services\Fetchers\Exceptions\FetchException;
use App\Services\Fetchers\Exceptions\NetworkException;
use App\Services\Fetchers\Exceptions\RateLimitException;
use App\Services\Fetchers\Exceptions\TimeoutException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit tests for the GoogleSuggestFetcher service.
 *
 * Tests the Google Suggest API integration with mocked HTTP responses.
 *
 * @see TASK-5-4
 * @see TEST-5-13
 * @see SPEC-005
 */
class GoogleSuggestFetcherTest extends TestCase
{
    protected GoogleSuggestFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetcher = new GoogleSuggestFetcher();
    }

    // =========================================================================
    // SUCCESSFUL FETCH TESTS
    // =========================================================================

    /**
     * Test fetch returns array of suggestion strings.
     * F-001
     */
    public function test_fetch_returns_suggestions(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                '["seo tools",["best seo tools","free seo tools","seo tools online","seo tools for wordpress"]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('seo tools');

        $this->assertIsArray($suggestions);
        $this->assertCount(4, $suggestions);
        $this->assertContains('best seo tools', $suggestions);
        $this->assertContains('free seo tools', $suggestions);
    }

    /**
     * Test JSON response is correctly parsed.
     * F-002
     */
    public function test_parse_json_response(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                '["seo",["seo optimizer","seo analytics","seo audit"]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('seo');

        $this->assertEquals(['seo optimizer', 'seo analytics', 'seo audit'], $suggestions);
    }

    /**
     * Test fetch with valid response structure.
     */
    public function test_fetch_with_valid_response_structure(): void
    {
        Http::fake([
            '*' => Http::response(
                '["original query",["suggestion 1","suggestion 2"]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('test');

        $this->assertEquals(['suggestion 1', 'suggestion 2'], $suggestions);
    }

    // =========================================================================
    // EMPTY RESPONSE TESTS
    // =========================================================================

    /**
     * Test empty response returns empty array.
     * F-003
     */
    public function test_empty_response_returns_empty_array(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                '["seo",[]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('seo');

        $this->assertIsArray($suggestions);
        $this->assertEmpty($suggestions);
    }

    /**
     * Test malformed response returns empty array.
     */
    public function test_malformed_response_returns_empty_array(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                'invalid json',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('test');

        $this->assertIsArray($suggestions);
        $this->assertEmpty($suggestions);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * Test rate limit exception on 429.
     * F-004
     */
    public function test_rate_limit_exception(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('Rate limited', 429),
        ]);

        $this->expectException(RateLimitException::class);

        $this->fetcher->fetch('seo tools');
    }

    /**
     * Test fetch exception on server error.
     * F-005
     */
    public function test_server_error_exception(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('Server error', 500),
        ]);

        $this->expectException(FetchException::class);

        $this->fetcher->fetch('seo tools');
    }

    /**
     * Test network exception on connection failure.
     * F-006
     */
    public function test_network_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
        });

        $this->expectException(NetworkException::class);

        $this->fetcher->fetch('seo tools');
    }

    /**
     * Test timeout exception.
     * F-012
     */
    public function test_timeout_exception(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response()->timeout(0.001),
        ]);

        $this->expectException(NetworkException::class);

        $this->fetcher->fetch('seo tools');
    }

    /**
     * Test 404 returns empty array (not an error).
     */
    public function test_404_returns_empty_array(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('Not found', 404),
        ]);

        $suggestions = $this->fetcher->fetch('seo tools');

        $this->assertEmpty($suggestions);
    }

    // =========================================================================
    // URL ENCODING TESTS
    // =========================================================================

    /**
     * Test URL encoding of keyword.
     * F-007
     */
    public function test_url_encoding(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["seo tools 2026",["suggestion"]]', 200),
        ]);

        $this->fetcher->fetch('seo tools 2026');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), urlencode('seo tools 2026'));
        });
    }

    /**
     * Test special characters are URL encoded.
     */
    public function test_special_characters_url_encoded(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",["suggestion"]]', 200),
        ]);

        $this->fetcher->fetch('seo & marketing');

        Http::assertSent(function ($request) {
            $url = $request->url();
            return str_contains($url, urlencode('&'));
        });
    }

    // =========================================================================
    // UNICODE TESTS
    // =========================================================================

    /**
     * Test Unicode characters handled correctly.
     * F-011
     */
    public function test_unicode_characters(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                '["seo österreich",["suggestion 1","suggestion 2"]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('seo österreich');

        $this->assertCount(2, $suggestions);
    }

    // =========================================================================
    // CACHING TESTS
    // =========================================================================

    /**
     * Test same query returns cached result.
     * F-008
     */
    public function test_caching_same_query(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["seo",["cached"]]', 200),
        ]);

        // First call
        $suggestions1 = $this->fetcher->fetch('seo');
        
        // Second call (should use cache)
        $suggestions2 = $this->fetcher->fetch('seo');

        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
        
        $this->assertEquals($suggestions1, $suggestions2);
    }

    /**
     * Test cache expiration.
     * F-009
     */
    public function test_cache_expiration(): void
    {
        $fetcher = new GoogleSuggestFetcher(ttl: 1); // 1 second TTL

        Http::fake([
            'suggestqueries.google.com/*' => Http::sequence()
                ->push('["seo",["first"]]', 200)
                ->push('["seo",["second"]]', 200),
        ]);

        // First call
        $suggestions1 = $fetcher->fetch('seo');

        // Wait for cache to expire
        sleep(2);

        // Second call (should make fresh request)
        $suggestions2 = $fetcher->fetch('seo');

        // Should make two HTTP requests
        Http::assertSentCount(2);
        
        $this->assertNotEquals($suggestions1, $suggestions2);
    }

    /**
     * Test different queries bypass cache.
     */
    public function test_different_queries_bypass_cache(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",["suggestion"]]', 200),
        ]);

        $this->fetcher->fetch('seo');
        $this->fetcher->fetch('marketing');

        Http::assertSentCount(2);
    }

    // =========================================================================
    // SPECIAL CHARACTERS TESTS
    // =========================================================================

    /**
     * Test special characters in keyword.
     * F-010
     */
    public function test_special_characters_in_keyword(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",["suggestion"]]', 200),
        ]);

        $suggestions = $this->fetcher->fetch('seo & marketing <script>');

        $this->assertIsArray($suggestions);
    }

    /**
     * Test newlines in keyword are handled.
     */
    public function test_newlines_in_keyword(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",["suggestion"]]', 200),
        ]);

        $suggestions = $this->fetcher->fetch("seo\ntools");

        $this->assertIsArray($suggestions);
    }

    // =========================================================================
    // TIMEOUT CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test timeout is configurable.
     */
    public function test_timeout_is_configurable(): void
    {
        $fetcher = new GoogleSuggestFetcher(timeout: 5);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",["suggestion"]]', 200),
        ]);

        $suggestions = $fetcher->fetch('test');

        $this->assertNotEmpty($suggestions);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test empty keyword returns empty array.
     */
    public function test_empty_keyword_returns_empty_array(): void
    {
        $suggestions = $this->fetcher->fetch('');

        $this->assertEmpty($suggestions);
    }

    /**
     * Test very long keyword is handled.
     */
    public function test_very_long_keyword(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test",[]]', 200),
        ]);

        $longKeyword = str_repeat('seo ', 100);
        $suggestions = $this->fetcher->fetch($longKeyword);

        $this->assertIsArray($suggestions);
    }

    /**
     * Test whitespace is trimmed.
     */
    public function test_whitespace_trimmed(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["seo",["suggestion"]]', 200),
        ]);

        $this->fetcher->fetch('  seo  ');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'seo');
        });
    }

    // =========================================================================
    // RESPONSE FORMAT TESTS
    // =========================================================================

    /**
     * Test response with numeric suggestions.
     */
    public function test_response_with_numeric_suggestions(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response(
                '["test",["suggestion 1","suggestion 2",123,"suggestion 4"]]',
                200
            ),
        ]);

        $suggestions = $this->fetcher->fetch('test');

        // Should filter out non-string values
        $this->assertCount(3, $suggestions);
        foreach ($suggestions as $suggestion) {
            $this->assertIsString($suggestion);
        }
    }

    /**
     * Test response with missing second element.
     */
    public function test_response_with_missing_suggestions_array(): void
    {
        Http::fake([
            'suggestqueries.google.com/*' => Http::response('["test"]', 200),
        ]);

        $suggestions = $this->fetcher->fetch('test');

        $this->assertEmpty($suggestions);
    }
}
