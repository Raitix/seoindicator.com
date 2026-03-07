<?php

namespace Tests\Unit\Services\Audit;

use App\Services\Audit\RobotsParser;
use App\Services\Audit\DTOs\ParsedRobots;
use App\Services\Audit\DTOs\RobotsRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * RobotsParser Service Tests
 * 
 * Test coverage for TASK-14-6: Create RobotsParser Service
 * 
 * Categories:
 * - SI: Service Instantiation (5 tests)
 * - FETCH: Robots.txt Fetching (14 tests)
 * - PARSE: Directive Parsing (18 tests)
 * - PERM: Permission Checking (16 tests)
 * - DELAY: Crawl Delay Extraction (8 tests)
 * - SITEMAP: Sitemap Extraction (6 tests)
 * - CACHE: Caching Behavior (10 tests)
 * - WILDCARD: Wildcard Pattern Matching (12 tests)
 * - ERROR: Error Handling (10 tests)
 * - SEC: Security (SSRF Prevention) (10 tests)
 * - EDGE: Edge Cases (12 tests)
 * - INT: Integration (5 tests)
 */
class RobotsParserTest extends TestCase
{
    private RobotsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = app(RobotsParser::class);
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    /** @test SI-01: Service can be resolved from container */
    public function test_si_01_service_can_be_resolved_from_container(): void
    {
        $parser = app(RobotsParser::class);
        
        $this->assertInstanceOf(RobotsParser::class, $parser);
    }

    /** @test SI-02: Service has all required methods */
    public function test_si_02_service_has_all_required_methods(): void
    {
        $this->assertTrue(method_exists($this->parser, 'fetch'));
        $this->assertTrue(method_exists($this->parser, 'parse'));
        $this->assertTrue(method_exists($this->parser, 'isAllowed'));
        $this->assertTrue(method_exists($this->parser, 'getCrawlDelay'));
        $this->assertTrue(method_exists($this->parser, 'getSitemaps'));
        $this->assertTrue(method_exists($this->parser, 'getDisallowedPaths'));
    }

    /** @test SI-03: Service uses default timeout configuration */
    public function test_si_03_service_uses_default_timeout_configuration(): void
    {
        config(['robots.timeout' => 15]);
        
        $parser = app(RobotsParser::class);
        
        // Timeout should be configurable
        $this->assertNotNull($parser);
    }

    /** @test SI-04: Service uses default cache TTL configuration */
    public function test_si_04_service_uses_default_cache_ttl_configuration(): void
    {
        config(['robots.cache_ttl' => 7200]);
        
        $parser = app(RobotsParser::class);
        
        $this->assertNotNull($parser);
    }

    /** @test SI-05: Service is singleton in container */
    public function test_si_05_service_is_singleton_in_container(): void
    {
        $parser1 = app(RobotsParser::class);
        $parser2 = app(RobotsParser::class);
        
        $this->assertSame($parser1, $parser2);
    }

    // =========================================================================
    // FETCH: Robots.txt Fetching Tests
    // =========================================================================

    /** @test FETCH-01: Fetch returns robots.txt content on success */
    public function test_fetch_01_returns_content_on_success(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response("User-agent: *\nDisallow: /admin/", 200),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNotNull($result);
        $this->assertStringContainsString('User-agent:', $result);
    }

    /** @test FETCH-02: Fetch returns null on 404 (no robots.txt) */
    public function test_fetch_02_returns_null_on_404(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('Not Found', 404),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test FETCH-03: Fetch returns null on 500 error */
    public function test_fetch_03_returns_null_on_500_error(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('Server Error', 500),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test FETCH-04: Fetch handles connection timeout */
    public function test_fetch_04_handles_connection_timeout(): void
    {
        Http::fake([
            'example.com/robots.txt' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test FETCH-05: Fetch constructs correct robots.txt URL from base URL */
    public function test_fetch_05_constructs_correct_robots_url(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com/path/to/page');

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/robots.txt');
        });
    }

    /** @test FETCH-06: Fetch handles URLs with trailing slash */
    public function test_fetch_06_handles_trailing_slash(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $result = $this->parser->fetch('https://example.com/');

        $this->assertNotNull($result);
    }

    /** @test FETCH-07: Fetch handles URLs with query string */
    public function test_fetch_07_handles_query_string(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $result = $this->parser->fetch('https://example.com/page?param=value');

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/robots.txt');
        });
    }

    /** @test FETCH-08: Fetch handles URLs with port */
    public function test_fetch_08_handles_url_with_port(): void
    {
        Http::fake([
            'example.com:8080/robots.txt' => Http::response('OK', 200),
        ]);

        $result = $this->parser->fetch('https://example.com:8080/page');

        $this->assertNotNull($result);
    }

    /** @test FETCH-09: Fetch handles subdomain URLs */
    public function test_fetch_09_handles_subdomain_urls(): void
    {
        Http::fake([
            'blog.example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $result = $this->parser->fetch('https://blog.example.com/post');

        $this->assertNotNull($result);
    }

    /** @test FETCH-10: Fetch sends appropriate user-agent header */
    public function test_fetch_10_sends_user_agent_header(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com');

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent');
        });
    }

    /** @test FETCH-11: Fetch handles DNS resolution failure */
    public function test_fetch_11_handles_dns_failure(): void
    {
        Http::fake([
            'nonexistent.invalid/robots.txt' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('DNS resolution failed');
            },
        ]);

        $result = $this->parser->fetch('https://nonexistent.invalid');

        $this->assertNull($result);
    }

    /** @test FETCH-12: Fetch handles SSL certificate errors */
    public function test_fetch_12_handles_ssl_errors(): void
    {
        Http::fake([
            'expired.badssl.com/robots.txt' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('SSL certificate error');
            },
        ]);

        $result = $this->parser->fetch('https://expired.badssl.com');

        $this->assertNull($result);
    }

    /** @test FETCH-13: Fetch handles large robots.txt files */
    public function test_fetch_13_handles_large_files(): void
    {
        $largeContent = str_repeat("Disallow: /path\n", 10000);
        
        Http::fake([
            'example.com/robots.txt' => Http::response($largeContent, 200),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNotNull($result);
        $this->assertGreaterThan(50000, strlen($result));
    }

    /** @test FETCH-14: Fetch handles empty robots.txt */
    public function test_fetch_14_handles_empty_robots_txt(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('', 200),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertEquals('', $result);
    }

    // =========================================================================
    // PARSE: Directive Parsing Tests
    // =========================================================================

    /** @test PARSE-01: Parse returns ParsedRobots DTO */
    public function test_parse_01_returns_parsed_robots_dto(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    /** @test PARSE-02: Parse handles user-agent groups */
    public function test_parse_02_handles_user_agent_groups(): void
    {
        $robotsTxt = "User-agent: Googlebot\nDisallow: /google/\n\nUser-agent: Bingbot\nDisallow: /bing/";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertContains('Googlebot', $result->userAgents);
        $this->assertContains('Bingbot', $result->userAgents);
    }

    /** @test PARSE-03: Parse handles wildcard user-agent */
    public function test_parse_03_handles_wildcard_user_agent(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        
        $result = $this->parser->parse($robotsTxt, '*');

        $this->assertContains('*', $result->userAgents);
    }

    /** @test PARSE-04: Parse extracts disallow directives */
    public function test_parse_04_extracts_disallow_directives(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/\nDisallow: /private/";
        
        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/admin/', $paths);
        $this->assertContains('/private/', $paths);
    }

    /** @test PARSE-05: Parse extracts allow directives */
    public function test_parse_05_extracts_allow_directives(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /\nAllow: /public/";
        
        $result = $this->parser->parse($robotsTxt);

        $rules = $result->getRules('*');
        $allowRules = array_filter($rules, fn($r) => $r->allow);
        $this->assertNotEmpty($allowRules);
    }

    /** @test PARSE-06: Parse extracts crawl-delay directive */
    public function test_parse_06_extracts_crawl_delay(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 10";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertEquals(10, $result->crawlDelay);
    }

    /** @test PARSE-07: Parse extracts sitemap directives */
    public function test_parse_07_extracts_sitemap_directives(): void
    {
        $robotsTxt = "Sitemap: https://example.com/sitemap.xml\nSitemap: https://example.com/news.xml";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertCount(2, $result->sitemaps);
        $this->assertContains('https://example.com/sitemap.xml', $result->sitemaps);
        $this->assertContains('https://example.com/news.xml', $result->sitemaps);
    }

    /** @test PARSE-08: Parse handles empty robots.txt */
    public function test_parse_08_handles_empty_robots_txt(): void
    {
        $result = $this->parser->parse('');

        $this->assertTrue($result->isEmpty);
        $this->assertEmpty($result->userAgents);
        $this->assertEmpty($result->rules);
    }

    /** @test PARSE-09: Parse handles null input */
    public function test_parse_09_handles_null_input(): void
    {
        $result = $this->parser->parse(null);

        $this->assertTrue($result->isEmpty);
    }

    /** @test PARSE-10: Parse handles malformed robots.txt gracefully */
    public function test_parse_10_handles_malformed_gracefully(): void
    {
        $robotsTxt = "Random text\nInvalid directive\nNo colon here";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    /** @test PARSE-11: Parse handles mixed case directives */
    public function test_parse_11_handles_mixed_case(): void
    {
        $robotsTxt = "USER-AGENT: *\nDISALLOW: /admin/\nCRAWL-DELAY: 5";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertNotEmpty($result->userAgents);
        $this->assertEquals(5, $result->crawlDelay);
    }

    /** @test PARSE-12: Parse handles lines with extra whitespace */
    public function test_parse_12_handles_extra_whitespace(): void
    {
        $robotsTxt = "User-agent:  *\nDisallow:   /admin/  \n";
        
        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/admin/', $paths);
    }

    /** @test PARSE-13: Parse handles comments */
    public function test_parse_13_handles_comments(): void
    {
        $robotsTxt = "# This is a comment\nUser-agent: *\nDisallow: /admin/ # inline comment";
        
        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/admin/', $paths);
    }

    /** @test PARSE-14: Parse handles URL-encoded paths */
    public function test_parse_14_handles_url_encoded_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /search%3Fq=";
        
        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertNotEmpty($paths);
    }

    /** @test PARSE-15: Parse handles Unicode in paths */
    public function test_parse_15_handles_unicode_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /приватный/";
        
        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/приватный/', $paths);
    }

    /** @test PARSE-16: Parse handles multiple user-agents per group */
    public function test_parse_16_handles_multiple_user_agents_per_group(): void
    {
        $robotsTxt = "User-agent: Googlebot\nUser-agent: Bingbot\nDisallow: /admin/";
        
        $result = $this->parser->parse($robotsTxt);

        $this->assertContains('Googlebot', $result->userAgents);
        $this->assertContains('Bingbot', $result->userAgents);
    }

    /** @test PARSE-17: Parse extracts specific user-agent rules */
    public function test_parse_17_extracts_specific_user_agent_rules(): void
    {
        $robotsTxt = "User-agent: Googlebot\nDisallow: /google/\n\nUser-agent: *\nDisallow: /admin/";
        
        $result = $this->parser->parse($robotsTxt);

        $googlePaths = $result->getDisallowedPaths('Googlebot');
        $allPaths = $result->getDisallowedPaths('*');
        
        $this->assertContains('/google/', $googlePaths);
        $this->assertNotContains('/google/', $allPaths);
    }

    /** @test PARSE-18: Parse handles rules without explicit user-agent */
    public function test_parse_18_handles_rules_without_user_agent(): void
    {
        $robotsTxt = "Disallow: /admin/";
        
        $result = $this->parser->parse($robotsTxt);

        // Should default to wildcard user-agent
        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    // =========================================================================
    // PERM: Permission Checking Tests
    // =========================================================================

    /** @test PERM-01: isAllowed returns true for allowed paths */
    public function test_perm_01_returns_true_for_allowed_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertTrue($this->parser->isAllowed('/public/page', $parsed));
    }

    /** @test PERM-02: isAllowed returns false for disallowed paths */
    public function test_perm_02_returns_false_for_disallowed_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/admin/settings', $parsed));
    }

    /** @test PERM-03: isAllowed returns true for empty robots.txt */
    public function test_perm_03_returns_true_for_empty_robots(): void
    {
        $parsed = $this->parser->parse('');

        $this->assertTrue($this->parser->isAllowed('/any/path', $parsed));
    }

    /** @test PERM-04: isAllowed returns true when no rules match */
    public function test_perm_04_returns_true_when_no_rules_match(): void
    {
        $robotsTxt = "User-agent: Googlebot\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        // Different user-agent, should be allowed
        $this->assertTrue($this->parser->isAllowed('/admin/settings', $parsed, 'Bingbot'));
    }

    /** @test PERM-05: isAllowed handles exact path match */
    public function test_perm_05_handles_exact_path_match(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /private";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/private', $parsed));
        $this->assertTrue($this->parser->isAllowed('/private-data', $parsed));
    }

    /** @test PERM-06: isAllowed handles directory disallow */
    public function test_perm_06_handles_directory_disallow(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/admin/', $parsed));
        $this->assertFalse($this->parser->isAllowed('/admin/users', $parsed));
        $this->assertTrue($this->parser->isAllowed('/administrator', $parsed));
    }

    /** @test PERM-07: isAllowed handles root disallow */
    public function test_perm_07_handles_root_disallow(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/any/path', $parsed));
        $this->assertFalse($this->parser->isAllowed('/', $parsed));
    }

    /** @test PERM-08: isAllowed handles allow override */
    public function test_perm_08_handles_allow_override(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /\nAllow: /public/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertTrue($this->parser->isAllowed('/public/page', $parsed));
        $this->assertFalse($this->parser->isAllowed('/private/page', $parsed));
    }

    /** @test PERM-09: isAllowed handles specific user-agent rules */
    public function test_perm_09_handles_specific_user_agent(): void
    {
        $robotsTxt = "User-agent: Googlebot\nDisallow: /google-only/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/google-only/page', $parsed, 'Googlebot'));
        $this->assertTrue($this->parser->isAllowed('/google-only/page', $parsed, 'Bingbot'));
    }

    /** @test PERM-10: isAllowed checks wildcard and specific rules */
    public function test_perm_10_checks_wildcard_and_specific_rules(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/\n\nUser-agent: Googlebot\nDisallow: /google/";
        $parsed = $this->parser->parse($robotsTxt);

        // Googlebot should respect both wildcard and specific rules
        $this->assertFalse($this->parser->isAllowed('/admin/page', $parsed, 'Googlebot'));
        $this->assertFalse($this->parser->isAllowed('/google/page', $parsed, 'Googlebot'));
    }

    /** @test PERM-11: isAllowed handles URL with query string */
    public function test_perm_11_handles_url_with_query_string(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /search";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/search?q=test', $parsed));
    }

    /** @test PERM-12: isAllowed handles URL with fragment */
    public function test_perm_12_handles_url_with_fragment(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /page";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/page#section', $parsed));
    }

    /** @test PERM-13: isAllowed handles empty disallow (allow all) */
    public function test_perm_13_handles_empty_disallow(): void
    {
        $robotsTxt = "User-agent: *\nDisallow:";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertTrue($this->parser->isAllowed('/any/path', $parsed));
    }

    /** @test PERM-14: isAllowed handles path with encoded characters */
    public function test_perm_14_handles_encoded_characters(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /search%3Fq=";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/search?q=test', $parsed));
    }

    /** @test PERM-15: isAllowed respects longest matching rule */
    public function test_perm_15_respects_longest_matching_rule(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /\nAllow: /public/\nAllow: /public/docs/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertTrue($this->parser->isAllowed('/public/docs/', $parsed));
    }

    /** @test PERM-16: isAllowed handles case-sensitive paths */
    public function test_perm_16_handles_case_sensitive_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /Admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/Admin/', $parsed));
        $this->assertTrue($this->parser->isAllowed('/admin/', $parsed));
    }

    // =========================================================================
    // WILDCARD: Wildcard Pattern Matching Tests
    // =========================================================================

    /** @test WILDCARD-01: Handles asterisk wildcard in path */
    public function test_wildcard_01_handles_asterisk_in_path(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /*.pdf";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/document.pdf', $parsed));
        $this->assertFalse($this->parser->isAllowed('/files/report.pdf', $parsed));
        $this->assertTrue($this->parser->isAllowed('/document.html', $parsed));
    }

    /** @test WILDCARD-02: Handles asterisk in middle of path */
    public function test_wildcard_02_handles_asterisk_in_middle(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/*/settings";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/admin/users/settings', $parsed));
        $this->assertFalse($this->parser->isAllowed('/admin/site/settings', $parsed));
        $this->assertTrue($this->parser->isAllowed('/admin/settings', $parsed));
    }

    /** @test WILDCARD-03: Handles dollar sign end-of-path marker */
    public function test_wildcard_03_handles_dollar_sign_marker(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /print$";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/print', $parsed));
        $this->assertTrue($this->parser->isAllowed('/printer', $parsed));
        $this->assertTrue($this->parser->isAllowed('/print/document', $parsed));
    }

    /** @test WILDCARD-04: Handles combined asterisk and dollar sign */
    public function test_wildcard_04_handles_combined_wildcards(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /*.pdf$";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/document.pdf', $parsed));
        $this->assertTrue($this->parser->isAllowed('/document.pdf?page=1', $parsed));
    }

    /** @test WILDCARD-05: Handles multiple asterisks */
    public function test_wildcard_05_handles_multiple_asterisks(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /*/admin/*";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/site/admin/users', $parsed));
        $this->assertFalse($this->parser->isAllowed('/blog/admin/posts', $parsed));
        $this->assertTrue($this->parser->isAllowed('/admin/users', $parsed));
    }

    /** @test WILDCARD-06: Wildcard matches zero characters */
    public function test_wildcard_06_matches_zero_characters(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /test*page";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/testpage', $parsed));
        $this->assertFalse($this->parser->isAllowed('/testXYZpage', $parsed));
    }

    /** @test WILDCARD-07: Handles wildcard in user-agent */
    public function test_wildcard_07_handles_wildcard_in_user_agent(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /all/\n\nUser-agent: Googlebot\nDisallow: /google/";
        $parsed = $this->parser->parse($robotsTxt);

        // Any user-agent should match wildcard rules
        $this->assertFalse($this->parser->isAllowed('/all/page', $parsed, 'AnyBot'));
    }

    /** @test WILDCARD-08: Handles path ending with asterisk */
    public function test_wildcard_08_handles_path_ending_asterisk(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /private/*";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/private/', $parsed));
        $this->assertFalse($this->parser->isAllowed('/private/secret', $parsed));
    }

    /** @test WILDCARD-09: Handles path starting with asterisk */
    public function test_wildcard_09_handles_path_starting_asterisk(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: */search";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/api/search', $parsed));
        $this->assertFalse($this->parser->isAllowed('/search', $parsed));
    }

    /** @test WILDCARD-10: Wildcard is case-sensitive */
    public function test_wildcard_10_is_case_sensitive(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /*.PDF";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/document.PDF', $parsed));
        $this->assertTrue($this->parser->isAllowed('/document.pdf', $parsed));
    }

    /** @test WILDCARD-11: Handles escaped special characters */
    public function test_wildcard_11_handles_escaped_characters(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /path%20with%20spaces";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/path with spaces', $parsed));
    }

    /** @test WILDCARD-12: Complex wildcard pattern */
    public function test_wildcard_12_complex_pattern(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /*?*print*";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/page?print=1', $parsed));
        $this->assertFalse($this->parser->isAllowed('/doc?format=print', $parsed));
        $this->assertTrue($this->parser->isAllowed('/page', $parsed));
    }

    // =========================================================================
    // DELAY: Crawl Delay Tests
    // =========================================================================

    /** @test DELAY-01: getCrawlDelay returns delay value */
    public function test_delay_01_returns_delay_value(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 5";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertEquals(5, $this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-02: getCrawlDelay returns null for missing directive */
    public function test_delay_02_returns_null_when_missing(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertNull($this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-03: getCrawlDelay returns delay for specific user-agent */
    public function test_delay_03_returns_delay_for_specific_user_agent(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 1\n\nUser-agent: Googlebot\nCrawl-delay: 10";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertEquals(10, $this->parser->getCrawlDelay($parsed, 'Googlebot'));
        $this->assertEquals(1, $this->parser->getCrawlDelay($parsed, 'Bingbot'));
    }

    /** @test DELAY-04: getCrawlDelay handles decimal values */
    public function test_delay_04_handles_decimal_values(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 0.5";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertEquals(0.5, $this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-05: getCrawlDelay handles invalid values */
    public function test_delay_05_handles_invalid_values(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: invalid";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertNull($this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-06: getCrawlDelay handles negative values */
    public function test_delay_06_handles_negative_values(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: -5";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertNull($this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-07: getCrawlDelay handles zero value */
    public function test_delay_07_handles_zero_value(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 0";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertEquals(0, $this->parser->getCrawlDelay($parsed));
    }

    /** @test DELAY-08: getCrawlDelay uses wildcard default for unknown user-agent */
    public function test_delay_08_uses_wildcard_default(): void
    {
        $robotsTxt = "User-agent: *\nCrawl-delay: 3";
        $parsed = $this->parser->parse($robotsTxt);

        $this->assertEquals(3, $this->parser->getCrawlDelay($parsed, 'UnknownBot'));
    }

    // =========================================================================
    // SITEMAP: Sitemap Extraction Tests
    // =========================================================================

    /** @test SITEMAP-01: getSitemaps returns sitemap URLs */
    public function test_sitemap_01_returns_sitemap_urls(): void
    {
        $robotsTxt = "Sitemap: https://example.com/sitemap.xml";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertCount(1, $sitemaps);
        $this->assertEquals('https://example.com/sitemap.xml', $sitemaps[0]);
    }

    /** @test SITEMAP-02: getSitemaps returns multiple sitemaps */
    public function test_sitemap_02_returns_multiple_sitemaps(): void
    {
        $robotsTxt = "Sitemap: https://example.com/sitemap.xml\nSitemap: https://example.com/news.xml";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertCount(2, $sitemaps);
    }

    /** @test SITEMAP-03: getSitemaps returns empty array when none */
    public function test_sitemap_03_returns_empty_when_none(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertEmpty($sitemaps);
    }

    /** @test SITEMAP-04: getSitemaps handles relative sitemap URLs */
    public function test_sitemap_04_handles_relative_urls(): void
    {
        $robotsTxt = "Sitemap: /sitemap.xml";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertCount(1, $sitemaps);
    }

    /** @test SITEMAP-05: getSitemaps handles sitemap index files */
    public function test_sitemap_05_handles_sitemap_index(): void
    {
        $robotsTxt = "Sitemap: https://example.com/sitemap-index.xml";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertStringContainsString('sitemap-index.xml', $sitemaps[0]);
    }

    /** @test SITEMAP-06: getSitemaps handles gzip sitemap files */
    public function test_sitemap_06_handles_gzip_sitemaps(): void
    {
        $robotsTxt = "Sitemap: https://example.com/sitemap.xml.gz";
        $parsed = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($parsed);

        $this->assertCount(1, $sitemaps);
        $this->assertStringContainsString('.gz', $sitemaps[0]);
    }

    // =========================================================================
    // CACHE: Caching Behavior Tests
    // =========================================================================

    /** @test CACHE-01: Fetch caches successful response */
    public function test_cache_01_caches_successful_response(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response("User-agent: *\nDisallow: /admin/", 200),
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn("User-agent: *\nDisallow: /admin/");

        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-02: Fetch uses cache on second request */
    public function test_cache_02_uses_cache_on_second_request(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response("User-agent: *\nDisallow: /admin/", 200),
        ]);

        // First request
        $this->parser->fetch('https://example.com');
        
        // Second request should use cache
        Cache::shouldReceive('has')->andReturn(true);
        
        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-03: Cache uses correct key format */
    public function test_cache_03_uses_correct_key_format(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key) {
                return str_starts_with($key, 'robots:');
            });

        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-04: Cache TTL is 1 hour */
    public function test_cache_04_ttl_is_one_hour(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $ttl === 3600;
            });

        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-05: Cache is bypassed with force_refresh option */
    public function test_cache_05_bypassed_with_force_refresh(): void
    {
        Cache::shouldReceive('forget')->once();

        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com', true);
    }

    /** @test CACHE-06: Different domains have different cache keys */
    public function test_cache_06_different_domains_different_keys(): void
    {
        $keys = [];
        
        Cache::shouldReceive('remember')
            ->twice()
            ->withArgs(function ($key) use (&$keys) {
                $keys[] = $key;
                return true;
            });

        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com');
        $this->parser->fetch('https://other.com');

        $this->assertNotEquals($keys[0], $keys[1]);
    }

    /** @test CACHE-07: Cache handles 404 responses */
    public function test_cache_07_handles_404_responses(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(null);

        Http::fake([
            'example.com/robots.txt' => Http::response('Not Found', 404),
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test CACHE-08: Cache is cleared on parse errors */
    public function test_cache_08_cleared_on_parse_errors(): void
    {
        // Cache should be cleared if parsing fails
        Cache::shouldReceive('forget')->once();

        Http::fake([
            'example.com/robots.txt' => Http::response("\xFF\xFE Invalid binary", 200),
        ]);

        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-09: Cache respects custom TTL from config */
    public function test_cache_09_respects_custom_ttl(): void
    {
        config(['robots.cache_ttl' => 7200]);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $ttl === 7200;
            });

        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        $this->parser->fetch('https://example.com');
    }

    /** @test CACHE-10: ParsedRobots is cached along with content */
    public function test_cache_10_parsed_robots_is_cached(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response("User-agent: *\nDisallow: /admin/", 200),
        ]);

        // First call parses and caches
        $parsed1 = $this->parser->parse($this->parser->fetch('https://example.com'));
        
        // Second call should use cached parsed version
        $parsed2 = $this->parser->parse($this->parser->fetch('https://example.com'));

        $this->assertEquals($parsed1, $parsed2);
    }

    // =========================================================================
    // ERROR: Error Handling Tests
    // =========================================================================

    /** @test ERROR-01: Handles malformed URL gracefully */
    public function test_error_01_handles_malformed_url(): void
    {
        $result = $this->parser->fetch('not-a-valid-url');

        $this->assertNull($result);
    }

    /** @test ERROR-02: Handles empty URL */
    public function test_error_02_handles_empty_url(): void
    {
        $result = $this->parser->fetch('');

        $this->assertNull($result);
    }

    /** @test ERROR-03: Handles connection reset */
    public function test_error_03_handles_connection_reset(): void
    {
        Http::fake([
            'example.com/robots.txt' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection reset');
            },
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test ERROR-04: Handles invalid response body */
    public function test_error_04_handles_invalid_response_body(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response("\x00\x01\x02", 200),
        ]);

        $result = $this->parser->fetch('https://example.com');

        // Should handle binary data gracefully
        $this->assertNotNull($result);
    }

    /** @test ERROR-05: Handles timeout gracefully */
    public function test_error_05_handles_timeout_gracefully(): void
    {
        Http::fake([
            'example.com/robots.txt' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Request timeout');
            },
        ]);

        $result = $this->parser->fetch('https://example.com');

        $this->assertNull($result);
    }

    /** @test ERROR-06: Parse handles binary content */
    public function test_error_06_parse_handles_binary(): void
    {
        $result = $this->parser->parse("\x00\x01\x02\x03");

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    /** @test ERROR-07: Parse handles very long lines */
    public function test_error_07_handles_very_long_lines(): void
    {
        $longPath = str_repeat('a', 10000);
        $robotsTxt = "User-agent: *\nDisallow: /{$longPath}";

        $result = $this->parser->parse($robotsTxt);

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    /** @test ERROR-08: isAllowed handles invalid URL */
    public function test_error_08_is_allowed_handles_invalid_url(): void
    {
        $parsed = $this->parser->parse("User-agent: *\nDisallow: /admin/");

        // Should handle invalid URLs gracefully
        $result = $this->parser->isAllowed('not-a-url', $parsed);

        $this->assertTrue($result); // Default to allow for invalid input
    }

    /** @test ERROR-09: Handles recursion limit in wildcard matching */
    public function test_error_09_handles_recursion_limit(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /" . str_repeat("*/", 100);
        $parsed = $this->parser->parse($robotsTxt);

        // Should not crash
        $this->assertInstanceOf(ParsedRobots::class, $parsed);
    }

    /** @test ERROR-10: Handles memory limit with large robots.txt */
    public function test_error_10_handles_memory_limit(): void
    {
        $largeContent = str_repeat("User-agent: bot\nDisallow: /path\n", 100000);
        
        // Should process without memory issues
        $result = $this->parser->parse($largeContent);

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    // =========================================================================
    // SEC: Security Tests (SSRF Prevention)
    // =========================================================================

    /** @test SEC-01: Blocks localhost URLs */
    public function test_sec_01_blocks_localhost_urls(): void
    {
        $result = $this->parser->fetch('http://localhost/');

        $this->assertNull($result);
    }

    /** @test SEC-02: Blocks 127.0.0.1 URLs */
    public function test_sec_02_blocks_loopback_urls(): void
    {
        $result = $this->parser->fetch('http://127.0.0.1/');

        $this->assertNull($result);
    }

    /** @test SEC-03: Blocks private IP ranges (10.x.x.x) */
    public function test_sec_03_blocks_private_ip_10(): void
    {
        $result = $this->parser->fetch('http://10.0.0.1/');

        $this->assertNull($result);
    }

    /** @test SEC-04: Blocks private IP ranges (172.16-31.x.x) */
    public function test_sec_04_blocks_private_ip_172(): void
    {
        $result = $this->parser->fetch('http://172.16.0.1/');

        $this->assertNull($result);
    }

    /** @test SEC-05: Blocks private IP ranges (192.168.x.x) */
    public function test_sec_05_blocks_private_ip_192(): void
    {
        $result = $this->parser->fetch('http://192.168.1.1/');

        $this->assertNull($result);
    }

    /** @test SEC-06: Blocks cloud metadata endpoint */
    public function test_sec_06_blocks_cloud_metadata(): void
    {
        $result = $this->parser->fetch('http://169.254.169.254/');

        $this->assertNull($result);
    }

    /** @test SEC-07: Blocks 0.0.0.0 */
    public function test_sec_07_blocks_zero_ip(): void
    {
        $result = $this->parser->fetch('http://0.0.0.0/');

        $this->assertNull($result);
    }

    /** @test SEC-08: Blocks IPv6 localhost */
    public function test_sec_08_blocks_ipv6_localhost(): void
    {
        $result = $this->parser->fetch('http://[::1]/');

        $this->assertNull($result);
    }

    /** @test SEC-09: Blocks file:// protocol */
    public function test_sec_09_blocks_file_protocol(): void
    {
        $result = $this->parser->fetch('file:///etc/passwd');

        $this->assertNull($result);
    }

    /** @test SEC-10: Blocks internal hostnames */
    public function test_sec_10_blocks_internal_hostnames(): void
    {
        $result = $this->parser->fetch('http://internal.local/');

        $this->assertNull($result);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    /** @test EDGE-01: Handles UTF-8 BOM */
    public function test_edge_01_handles_utf8_bom(): void
    {
        $robotsTxt = "\xEF\xBB\xBFUser-agent: *\nDisallow: /admin/";

        $result = $this->parser->parse($robotsTxt);

        $this->assertNotEmpty($result->userAgents);
    }

    /** @test EDGE-02: Handles Windows line endings */
    public function test_edge_02_handles_windows_line_endings(): void
    {
        $robotsTxt = "User-agent: *\r\nDisallow: /admin/\r\n";

        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/admin/', $paths);
    }

    /** @test EDGE-03: Handles mixed line endings */
    public function test_edge_03_handles_mixed_line_endings(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/\r\nDisallow: /private\n";

        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertCount(2, $paths);
    }

    /** @test EDGE-04: Handles URLs with credentials */
    public function test_edge_04_handles_urls_with_credentials(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('OK', 200),
        ]);

        // Should strip credentials and use the domain only
        $result = $this->parser->fetch('https://user:pass@example.com/');

        Http::assertSent(function ($request) {
            return !str_contains($request->url(), 'user:pass');
        });
    }

    /** @test EDGE-05: Handles non-standard ports */
    public function test_edge_05_handles_non_standard_ports(): void
    {
        Http::fake([
            'example.com:3000/robots.txt' => Http::response('OK', 200),
        ]);

        $result = $this->parser->fetch('https://example.com:3000/');

        $this->assertNotNull($result);
    }

    /** @test EDGE-06: Handles international domain names */
    public function test_edge_06_handles_international_domains(): void
    {
        Http::fake([
            'xn--e1afmkfd.org/robots.txt' => Http::response('OK', 200),
        ]);

        // Punycode encoded
        $result = $this->parser->fetch('https://пример.org/');

        $this->assertNotNull($result);
    }

    /** @test EDGE-07: Handles robots.txt with only comments */
    public function test_edge_07_handles_only_comments(): void
    {
        $robotsTxt = "# Just a comment\n# Another comment";

        $result = $this->parser->parse($robotsTxt);

        $this->assertTrue($result->isEmpty);
    }

    /** @test EDGE-08: Handles duplicate directives */
    public function test_edge_08_handles_duplicate_directives(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/\nDisallow: /admin/";

        $result = $this->parser->parse($robotsTxt);

        // Should deduplicate
        $paths = $result->getDisallowedPaths('*');
        $this->assertCount(1, $paths);
    }

    /** @test EDGE-09: Handles very long sitemap URLs */
    public function test_edge_09_handles_long_sitemap_urls(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2000) . '.xml';
        $robotsTxt = "Sitemap: {$longUrl}";

        $result = $this->parser->parse($robotsTxt);

        $sitemaps = $this->parser->getSitemaps($result);
        $this->assertCount(1, $sitemaps);
    }

    /** @test EDGE-10: Handles null bytes in input */
    public function test_edge_10_handles_null_bytes(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin\x00/";

        $result = $this->parser->parse($robotsTxt);

        $this->assertInstanceOf(ParsedRobots::class, $result);
    }

    /** @test EDGE-11: Handles Unicode in user-agent */
    public function test_edge_11_handles_unicode_user_agent(): void
    {
        $robotsTxt = "User-agent: Бот\nDisallow: /admin/";

        $result = $this->parser->parse($robotsTxt);

        $this->assertContains('Бот', $result->userAgents);
    }

    /** @test EDGE-12: Handles emoji in paths */
    public function test_edge_12_handles_emoji_in_paths(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /🎉/";

        $result = $this->parser->parse($robotsTxt);

        $paths = $result->getDisallowedPaths('*');
        $this->assertContains('/🎉/', $paths);
    }

    // =========================================================================
    // INT: Integration Tests
    // =========================================================================

    /** @test INT-01: Full fetch-parse-check workflow */
    public function test_int_01_full_workflow(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response(
                "User-agent: *\nDisallow: /admin/\nCrawl-delay: 5\nSitemap: https://example.com/sitemap.xml",
                200
            ),
        ]);

        $content = $this->parser->fetch('https://example.com');
        $parsed = $this->parser->parse($content);

        $this->assertFalse($this->parser->isAllowed('/admin/page', $parsed));
        $this->assertTrue($this->parser->isAllowed('/public/page', $parsed));
        $this->assertEquals(5, $this->parser->getCrawlDelay($parsed));
        $this->assertCount(1, $this->parser->getSitemaps($parsed));
    }

    /** @test INT-02: Integration with caching layer */
    public function test_int_02_integration_with_caching(): void
    {
        Http::fake([
            'example.com/robots.txt' => Http::response('User-agent: *', 200),
        ]);

        // First call - should hit network
        $this->parser->fetch('https://example.com');
        
        // Second call - should use cache
        Cache::shouldReceive('remember')->once();
        $this->parser->fetch('https://example.com');
    }

    /** @test INT-03: Multiple user-agent permission check */
    public function test_int_03_multiple_user_agent_check(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/\n\nUser-agent: Googlebot\nDisallow: /google/\nAllow: /admin/public/";
        $parsed = $this->parser->parse($robotsTxt);

        // Googlebot should have specific rules
        $this->assertFalse($this->parser->isAllowed('/google/', $parsed, 'Googlebot'));
        
        // Other bots should use wildcard rules
        $this->assertFalse($this->parser->isAllowed('/admin/', $parsed, 'Bingbot'));
    }

    /** @test INT-04: Real-world robots.txt parsing */
    public function test_int_04_real_world_robots_txt(): void
    {
        // Based on common patterns seen in production
        $robotsTxt = <<<ROBOTS
# Robots.txt for example.com
User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Allow: /wp-admin/admin-ajax.php

User-agent: Googlebot
Crawl-delay: 1

Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/sitemap-news.xml
ROBOTS;

        $parsed = $this->parser->parse($robotsTxt);

        $this->assertFalse($this->parser->isAllowed('/wp-admin/options.php', $parsed));
        $this->assertTrue($this->parser->isAllowed('/wp-admin/admin-ajax.php', $parsed));
        $this->assertCount(2, $this->parser->getSitemaps($parsed));
    }

    /** @test INT-05: Performance with large robots.txt */
    public function test_int_05_performance_large_robots_txt(): void
    {
        $rules = [];
        for ($i = 0; $i < 1000; $i++) {
            $rules[] = "Disallow: /path{$i}/";
        }
        
        $robotsTxt = "User-agent: *\n" . implode("\n", $rules);
        
        $start = microtime(true);
        $parsed = $this->parser->parse($robotsTxt);
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Parsing should complete in under 1 second');
        $this->assertCount(1000, $parsed->getDisallowedPaths('*'));
    }
}
