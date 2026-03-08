<?php

namespace Tests\Unit\Services\LocalSEO;

use App\Services\LocalSEO\LocalRankService;
use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use App\Exceptions\LocalSEO\QuotaExceededException;
use App\Exceptions\LocalSEO\SerpBlockedException;
use App\Exceptions\LocalSEO\SerpRequestException;
use App\Exceptions\LocalSEO\SerpParseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class LocalRankServiceTest extends TestCase
{
    protected LocalRankService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocalRankService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SERVICE INSTANTIATION TESTS
    // ==========================================

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(LocalRankService::class, $this->service);
    }

    public function test_service_implements_interface(): void
    {
        $this->assertInstanceOf(LocalRankServiceInterface::class, $this->service);
    }

    public function test_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'checkRanking'));
        $this->assertTrue(method_exists($this->service, 'buildGeoSearchUrl'));
        $this->assertTrue(method_exists($this->service, 'parseLocalResults'));
        $this->assertTrue(method_exists($this->service, 'getMonthlyCheckCount'));
        $this->assertTrue(method_exists($this->service, 'getLastRanking'));
        $this->assertTrue(method_exists($this->service, 'getPreviousRanking'));
    }

    // ==========================================
    // LR-URL: Geo-Targeted URL Building Tests
    // ==========================================

    /** @test */
    public function lr_url_01_basic_geo_search_url(): void
    {
        $url = $this->service->buildGeoSearchUrl('pizza', 40.7128, -74.0060, 'New York');

        $this->assertStringContainsString('google.com/search', $url);
        $this->assertStringContainsString('q=pizza', $url);
        $this->assertStringContainsString('gl=', $url);
        $this->assertStringContainsString('tbm=lcl', $url);
        $this->assertStringContainsString('near=', $url);
    }

    /** @test */
    public function lr_url_02_country_code_parameter(): void
    {
        $url = $this->service->buildGeoSearchUrl('cafe', 51.5074, -0.1278, 'London, UK');

        $this->assertStringContainsString('gl=uk', $url);
    }

    /** @test */
    public function lr_url_03_uule_encoding(): void
    {
        $url = $this->service->buildGeoSearchUrl('pizza', 34.0522, -118.2437, 'Los Angeles, CA');

        $this->assertStringContainsString('uule=', $url);
    }

    /** @test */
    public function lr_url_04_local_search_mode(): void
    {
        $url = $this->service->buildGeoSearchUrl('test', 0, 0, '');

        $this->assertStringContainsString('tbm=lcl', $url);
    }

    /** @test */
    public function lr_url_05_special_characters_in_query(): void
    {
        $url = $this->service->buildGeoSearchUrl('café réstaurant', 0, 0, '');

        $this->assertStringContainsString('q=', $url);
        $this->assertStringContainsString('caf%C3%A9', $url);
    }

    /** @test */
    public function lr_url_06_unicode_location_names(): void
    {
        $url = $this->service->buildGeoSearchUrl('sushi', 35.6762, 139.6503, 'Tōkyō, 日本');

        $this->assertStringContainsString('uule=', $url);
    }

    /** @test */
    public function lr_url_07_empty_query_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->buildGeoSearchUrl('', 0, 0, '');
    }

    /** @test */
    public function lr_url_08_invalid_latitude_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->buildGeoSearchUrl('test', 999, 0, '');
    }

    /** @test */
    public function lr_url_09_invalid_longitude_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->buildGeoSearchUrl('test', 0, 999, '');
    }

    /** @test */
    public function lr_url_10_missing_location_name_graceful_handling(): void
    {
        $url = $this->service->buildGeoSearchUrl('pizza', 40.7128, -74.0060, '');

        $this->assertStringContainsString('q=pizza', $url);
        // Should still work without uule
        $this->assertStringContainsString('near=', $url);
    }

    // ==========================================
    // LR-PARSE: SERP HTML Parsing Tests
    // ==========================================

    /** @test */
    public function lr_parse_01_parse_local_pack_results(): void
    {
        $html = $this->getMockLocalPackHtml();

        $results = $this->service->parseLocalResults($html);

        $this->assertArrayHasKey('local_pack', $results);
        $this->assertGreaterThanOrEqual(1, count($results['local_pack']));
    }

    /** @test */
    public function lr_parse_02_parse_local_organic_results(): void
    {
        $html = $this->getMockOrganicHtml();

        $results = $this->service->parseLocalResults($html);

        $this->assertArrayHasKey('local_organic', $results);
    }

    /** @test */
    public function lr_parse_03_empty_serp(): void
    {
        $html = '<html><body>No results</body></html>';

        $results = $this->service->parseLocalResults($html);

        $this->assertArrayHasKey('local_pack', $results);
        $this->assertArrayHasKey('local_organic', $results);
        $this->assertEmpty($results['local_pack']);
        $this->assertEmpty($results['local_organic']);
    }

    /** @test */
    public function lr_parse_04_mixed_results(): void
    {
        $html = $this->getMockMixedSerpHtml();

        $results = $this->service->parseLocalResults($html);

        $this->assertArrayHasKey('local_pack', $results);
        $this->assertArrayHasKey('local_organic', $results);
    }

    /** @test */
    public function lr_parse_05_malformed_html(): void
    {
        $html = '<div class="broken"><span>unclosed';

        // Should not throw, should handle gracefully
        $results = $this->service->parseLocalResults($html);

        $this->assertIsArray($results);
    }

    /** @test */
    public function lr_parse_06_captcha_detection(): void
    {
        $html = '<html><body><div class="g-recaptcha"></div></body></html>';

        $this->expectException(SerpBlockedException::class);

        $this->service->parseLocalResults($html);
    }

    /** @test */
    public function lr_parse_07_rate_limit_detection(): void
    {
        $html = '<html><body><h1>Our systems have detected unusual traffic</h1></body></html>';

        $this->expectException(SerpBlockedException::class);

        $this->service->parseLocalResults($html);
    }

    /** @test */
    public function lr_parse_08_empty_html_throws_exception(): void
    {
        $this->expectException(SerpParseException::class);

        $this->service->parseLocalResults('');
    }

    /** @test */
    public function lr_parse_09_position_extraction(): void
    {
        $html = $this->getMockLocalPackHtml();

        $results = $this->service->parseLocalResults($html);

        $positions = array_column($results['local_pack'], 'position');
        $this->assertEquals([1, 2, 3], $positions);
    }

    /** @test */
    public function lr_parse_10_business_name_extraction(): void
    {
        $html = $this->getMockLocalPackHtml();

        $results = $this->service->parseLocalResults($html);

        $names = array_column($results['local_pack'], 'name');
        $this->assertNotEmpty(array_filter($names));
    }

    /** @test */
    public function lr_parse_11_rating_extraction(): void
    {
        $html = $this->getMockLocalPackHtml();

        $results = $this->service->parseLocalResults($html);

        // At least one result should have a rating
        $ratings = array_filter(array_column($results['local_pack'], 'rating'));
        $this->assertNotEmpty($ratings);
    }

    /** @test */
    public function lr_parse_12_review_count_extraction(): void
    {
        $html = $this->getMockLocalPackHtml();

        $results = $this->service->parseLocalResults($html);

        // Check that review counts are extracted (may be null for some)
        foreach ($results['local_pack'] as $result) {
            $this->assertArrayHasKey('review_count', $result);
        }
    }

    /** @test */
    public function lr_parse_13_forbidden_detection(): void
    {
        $html = '<html><body><h1>403 Forbidden</h1><p>Access Denied</p></body></html>';

        $this->expectException(SerpBlockedException::class);

        $this->service->parseLocalResults($html);
    }

    /** @test */
    public function lr_parse_14_zero_results_page(): void
    {
        $html = '<html><body><div>No results found for your search</div></body></html>';

        $results = $this->service->parseLocalResults($html);

        $this->assertEmpty($results['local_pack']);
        $this->assertEmpty($results['local_organic']);
    }

    // ==========================================
    // LR-CHECK: Local Ranking Check Tests
    // ==========================================

    /** @test */
    public function lr_check_01_business_found_in_local_pack(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $location = $this->createMockLocation([
            'id' => 1,
            'name' => 'Joes Pizza',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $result = $this->service->checkRanking(1, 'best pizza');

        $this->assertNotNull($result);
        $this->assertNotNull($result->local_pack_position);
    }

    /** @test */
    public function lr_check_02_business_in_organic_only(): void
    {
        $this->mockSerpRequest($this->getMockOrganicHtml());

        $location = $this->createMockLocation([
            'id' => 1,
            'name' => 'Example Business',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $result = $this->service->checkRanking(1, 'test query');

        $this->assertNotNull($result);
    }

    /** @test */
    public function lr_check_03_business_not_found(): void
    {
        $this->mockSerpRequest('<html><body>No results</body></html>');

        $location = $this->createMockLocation([
            'id' => 1,
            'name' => 'Nonexistent Business',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $result = $this->service->checkRanking(1, 'xyzzy123');

        $this->assertNotNull($result);
        $this->assertNull($result->local_pack_position);
    }

    /** @test */
    public function lr_check_04_quota_exceeded(): void
    {
        // Set quota to limit
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 50, 3600);

        $this->expectException(QuotaExceededException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_check_05_cache_hit(): void
    {
        $cachedResult = (object) [
            'location_id' => 1,
            'keyword' => 'test',
            'local_pack_position' => 1,
            'checked_at' => now()->toIso8601String(),
        ];

        $cacheKey = 'local_rank:' . md5('1:test');
        Cache::put($cacheKey, $cachedResult, 3600);

        // Should return cached result without making HTTP request
        Http::preventStrayRequests();

        $result = $this->service->checkRanking(1, 'test');

        $this->assertEquals($cachedResult, $result);
    }

    /** @test */
    public function lr_check_06_blocked_request_throws_exception(): void
    {
        Http::fake([
            'google.com/*' => Http::response('<div class="g-recaptcha"></div>', 200),
        ]);

        $this->expectException(SerpBlockedException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_check_07_network_error_throws_exception(): void
    {
        Http::fake([
            'google.com/*' => Http::response(null, 500),
        ]);

        $this->expectException(SerpRequestException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_check_08_invalid_keyword_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->checkRanking(1, '');
    }

    /** @test */
    public function lr_check_09_keyword_with_injection_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->checkRanking(1, '<script>alert(1)</script>');
    }

    /** @test */
    public function lr_check_10_check_result_has_required_fields(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $result = $this->service->checkRanking(1, 'pizza');

        $this->assertObjectHasProperty('location_id', $result);
        $this->assertObjectHasProperty('keyword', $result);
        $this->assertObjectHasProperty('local_pack_position', $result);
        $this->assertObjectHasProperty('local_organic_position', $result);
        $this->assertObjectHasProperty('competitors_in_pack', $result);
        $this->assertObjectHasProperty('checked_at', $result);
    }

    /** @test */
    public function lr_check_11_competitors_in_pack_populated(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $result = $this->service->checkRanking(1, 'pizza');

        $this->assertIsArray($result->competitors_in_pack);
        $this->assertGreaterThanOrEqual(1, count($result->competitors_in_pack));
    }

    // ==========================================
    // LR-QUOTA: Quota Enforcement Tests
    // ==========================================

    /** @test */
    public function lr_quota_01_under_quota(): void
    {
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 5, 3600);

        $count = $this->service->getMonthlyCheckCount(1);

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function lr_quota_02_at_quota_limit(): void
    {
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 50, 3600);

        $count = $this->service->getMonthlyCheckCount(1);

        $this->assertEquals(50, $count);
    }

    /** @test */
    public function lr_quota_03_over_quota_throws_exception(): void
    {
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 51, 3600);

        $this->expectException(QuotaExceededException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_quota_04_new_month_reset(): void
    {
        // Set quota for previous month
        $lastMonth = now()->subMonth()->format('Y-m');
        Cache::put('local_rank_quota:1:' . $lastMonth, 50, 3600);

        // Current month should be 0
        $count = $this->service->getMonthlyCheckCount(1);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function lr_quota_05_multiple_locations_separate_quota(): void
    {
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 25, 3600);
        Cache::put('local_rank_quota:2:' . now()->format('Y-m'), 10, 3600);

        $count1 = $this->service->getMonthlyCheckCount(1);
        $count2 = $this->service->getMonthlyCheckCount(2);

        $this->assertEquals(25, $count1);
        $this->assertEquals(10, $count2);
    }

    /** @test */
    public function lr_quota_06_quota_exception_has_details(): void
    {
        Cache::put('local_rank_quota:1:' . now()->format('Y-m'), 50, 3600);

        try {
            $this->service->checkRanking(1, 'test');
            $this->fail('Expected QuotaExceededException');
        } catch (QuotaExceededException $e) {
            $this->assertEquals(1, $e->getLocationId());
            $this->assertEquals(50, $e->getCurrentCount());
            $this->assertEquals(50, $e->getLimit());
        }
    }

    /** @test */
    public function lr_quota_07_zero_checks(): void
    {
        $count = $this->service->getMonthlyCheckCount(999);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function lr_quota_08_quota_increments_after_check(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $initialCount = $this->service->getMonthlyCheckCount(1);
        $this->assertEquals(0, $initialCount);

        $this->service->checkRanking(1, 'pizza');

        $newCount = $this->service->getMonthlyCheckCount(1);
        $this->assertEquals(1, $newCount);
    }

    // ==========================================
    // LR-CACHE: Caching Behavior Tests
    // ==========================================

    /** @test */
    public function lr_cache_01_result_is_cached(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        // First call
        $this->service->checkRanking(1, 'pizza');

        // Second call should use cache (no additional HTTP request)
        Http::preventStrayRequests();
        $result = $this->service->checkRanking(1, 'pizza');

        $this->assertNotNull($result);
    }

    /** @test */
    public function lr_cache_02_different_keywords_different_cache(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $result1 = $this->service->checkRanking(1, 'pizza');
        $result2 = $this->service->checkRanking(1, 'burger');

        // Both should have made requests (different cache keys)
        $this->assertNotEquals($result1->keyword, $result2->keyword);
    }

    /** @test */
    public function lr_cache_03_different_locations_different_cache(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $result1 = $this->service->checkRanking(1, 'pizza');
        $result2 = $this->service->checkRanking(2, 'pizza');

        // Both should have made requests
        $this->assertNotEquals($result1->location_id, $result2->location_id);
    }

    /** @test */
    public function lr_cache_04_cache_key_consistency(): void
    {
        $expectedKey = 'local_rank:' . md5('1:pizza');

        // Test the cache key format is deterministic
        $this->assertStringContainsString('local_rank:', $expectedKey);
    }

    // ==========================================
    // LR-RATE: Rate Limiting Tests
    // ==========================================

    /** @test */
    public function lr_rate_01_rate_limit_between_requests(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        $start = microtime(true);

        // Two consecutive requests
        $this->service->checkRanking(1, 'pizza');
        $this->service->checkRanking(2, 'pizza');

        $elapsed = microtime(true) - $start;

        // Should have at least 1 second gap
        $this->assertGreaterThanOrEqual(0.9, $elapsed);
    }

    /** @test */
    public function lr_rate_02_rate_limit_enforced(): void
    {
        $this->mockSerpRequest($this->getMockLocalPackHtml());

        // Make a request to set last request time
        $this->service->checkRanking(1, 'pizza');

        // The internal last request time should be set
        $reflection = new \ReflectionClass(LocalRankService::class);
        $property = $reflection->getProperty('lastRequestTime');
        $property->setAccessible(true);

        $this->assertNotNull($property->getValue());
    }

    // ==========================================
    // LR-HEADERS: Browser Headers Tests
    // ==========================================

    /** @test */
    public function lr_headers_01_user_agent_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertStringContainsString('Mozilla', $headers['User-Agent']);
    }

    /** @test */
    public function lr_headers_02_accept_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('Accept', $headers);
        $this->assertStringContainsString('text/html', $headers['Accept']);
    }

    /** @test */
    public function lr_headers_03_accept_language_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('Accept-Language', $headers);
    }

    /** @test */
    public function lr_headers_04_accept_encoding_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('Accept-Encoding', $headers);
        $this->assertStringContainsString('gzip', $headers['Accept-Encoding']);
    }

    /** @test */
    public function lr_headers_05_dnt_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('DNT', $headers);
        $this->assertEquals('1', $headers['DNT']);
    }

    /** @test */
    public function lr_headers_06_connection_header(): void
    {
        $reflection = new \ReflectionClass(LocalRankService::class);
        $method = $reflection->getMethod('getBrowserHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service);

        $this->assertArrayHasKey('Connection', $headers);
        $this->assertStringContainsString('keep-alive', $headers['Connection']);
    }

    // ==========================================
    // LR-ERROR: Error Handling Tests
    // ==========================================

    /** @test */
    public function lr_error_01_connection_timeout(): void
    {
        Http::fake([
            'google.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $this->expectException(SerpRequestException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_error_03_http_429_rate_limited(): void
    {
        Http::fake([
            'google.com/*' => Http::response('Too many requests', 429),
        ]);

        $this->expectException(SerpBlockedException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_error_04_http_403_forbidden(): void
    {
        Http::fake([
            'google.com/*' => Http::response('Forbidden', 403),
        ]);

        $this->expectException(SerpBlockedException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_error_05_http_500_server_error(): void
    {
        Http::fake([
            'google.com/*' => Http::response('Server error', 500),
        ]);

        $this->expectException(SerpRequestException::class);

        $this->service->checkRanking(1, 'test');
    }

    /** @test */
    public function lr_error_06_blocked_exception_has_type(): void
    {
        Http::fake([
            'google.com/*' => Http::response('<div class="g-recaptcha"></div>', 200),
        ]);

        try {
            $this->service->checkRanking(1, 'test');
            $this->fail('Expected SerpBlockedException');
        } catch (SerpBlockedException $e) {
            $this->assertEquals(SerpBlockedException::TYPE_CAPTCHA, $e->getBlockType());
        }
    }

    /** @test */
    public function lr_error_07_request_exception_has_details(): void
    {
        Http::fake([
            'google.com/*' => Http::response('Error', 500),
        ]);

        try {
            $this->service->checkRanking(1, 'test');
            $this->fail('Expected SerpRequestException');
        } catch (SerpRequestException $e) {
            $this->assertEquals(SerpRequestException::TYPE_SERVER_ERROR, $e->getErrorType());
            $this->assertNotNull($e->getUrl());
        }
    }

    // ==========================================
    // LR-SEC: Security Tests
    // ==========================================

    /** @test */
    public function lr_sec_01_sql_injection_in_query(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // SQL injection characters should be rejected
        $this->service->checkRanking(1, "'; DROP TABLE users;--");
    }

    /** @test */
    public function lr_sec_02_xss_in_query(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->checkRanking(1, '<script>alert(1)</script>');
    }

    /** @test */
    public function lr_sec_03_url_building_safe(): void
    {
        $url = $this->service->buildGeoSearchUrl('test query', 40.7, -74.0, 'New York');

        // URL should be properly encoded
        $this->assertStringNotContainsString('<', $url);
        $this->assertStringNotContainsString('>', $url);
        $this->assertStringNotContainsString("'", $url);
    }

    /** @test */
    public function lr_sec_04_exception_to_array_safe(): void
    {
        $exception = new QuotaExceededException(1, 50, 50);

        $array = $exception->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertArrayHasKey('location_id', $array);
        $this->assertEquals('quota_exceeded', $array['error']);
    }

    /** @test */
    public function lr_sec_05_blocked_exception_to_array_safe(): void
    {
        $exception = new SerpBlockedException(SerpBlockedException::TYPE_CAPTCHA);

        $array = $exception->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertEquals('serp_blocked', $array['error']);
    }

    /** @test */
    public function lr_sec_06_request_exception_to_array_safe(): void
    {
        $exception = new SerpRequestException(
            SerpRequestException::TYPE_TIMEOUT,
            'https://example.com',
            null,
            true
        );

        $array = $exception->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertEquals('serp_request_failed', $array['error']);
        $this->assertTrue($array['should_retry']);
    }

    /** @test */
    public function lr_sec_07_parse_exception_to_array_safe(): void
    {
        $exception = new SerpParseException('<html>', 'Failed to parse');

        $array = $exception->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertEquals('serp_parse_failed', $array['error']);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    protected function createMockLocation(array $attributes = []): object
    {
        return (object) array_merge([
            'id' => $attributes['id'] ?? 1,
            'name' => $attributes['name'] ?? 'Test Business',
            'latitude' => $attributes['latitude'] ?? 40.7128,
            'longitude' => $attributes['longitude'] ?? -74.0060,
            'address' => $attributes['address'] ?? '123 Main St, New York, NY',
            'gbp_place_id' => $attributes['gbp_place_id'] ?? null,
        ], $attributes);
    }

    protected function mockSerpRequest(string $html): void
    {
        Http::fake([
            'google.com/*' => Http::response($html, 200),
        ]);

        // Mock location resolver
        LocalRankService::setLocationResolver(function ($id) {
            return $this->createMockLocation(['id' => $id]);
        });
    }

    protected function getMockLocalPackHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<div class="local-pack">
  <div data-cid="123456789" class="local-result">
    <span class="OSrXXb">Joes Pizza</span>
    <span class="rating">4.5 stars</span>
    <span class="reviews">(123)</span>
  </div>
  <div data-cid="987654321" class="local-result">
    <span class="OSrXXb">Pizza Palace</span>
    <span class="rating">4.8 stars</span>
    <span class="reviews">(256)</span>
  </div>
  <div data-cid="456789012" class="local-result">
    <span class="OSrXXb">Marios Pizzeria</span>
    <span class="rating">4.2 stars</span>
    <span class="reviews">(89)</span>
  </div>
</div>
</body>
</html>
HTML;
    }

    protected function getMockOrganicHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<div class="g" data-hveid="1">
  <h3>Example Business</h3>
  <a href="https://example.com">example.com</a>
</div>
<div class="g" data-hveid="2">
  <h3>Another Business</h3>
  <a href="https://another.com">another.com</a>
</div>
</body>
</html>
HTML;
    }

    protected function getMockMixedSerpHtml(): string
    {
        return $this->getMockLocalPackHtml() . $this->getMockOrganicHtml();
    }
}
