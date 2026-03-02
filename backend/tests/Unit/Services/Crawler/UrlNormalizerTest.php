<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\UrlNormalizer;
use Mockery;

/**
 * Unit tests for the UrlNormalizer.
 *
 * @see TASK-22-8
 * @see SPEC-022
 */
class UrlNormalizerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Basic Normalization Tests (UN-001)
    // ==========================================

    /**
     * Test normalize lowercase host.
     * UN-001-01: Lowercase host
     */
    public function test_normalize_lowercase_host(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('HTTPS://EXAMPLE.COM/page');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test normalize preserves path case.
     * UN-001-02: Preserve path case
     */
    public function test_normalize_preserves_path_case(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/Path/To/Page');

        $this->assertEquals('https://example.com/Path/To/Page', $result);
    }

    /**
     * Test normalize empty string.
     * UN-001-03: Empty string
     */
    public function test_normalize_empty_string(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('');

        $this->assertEquals('', $result);
    }

    /**
     * Test normalize null.
     * UN-001-04: Null input
     */
    public function test_normalize_null(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize(null);

        $this->assertEquals('', $result);
    }

    // ==========================================
    // Port Normalization Tests (UN-002)
    // ==========================================

    /**
     * Test remove default port 80 for HTTP.
     * UN-002-01: Remove port 80
     */
    public function test_normalize_remove_port_80(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('http://example.com:80/page');

        $this->assertEquals('http://example.com/page', $result);
    }

    /**
     * Test remove default port 443 for HTTPS.
     * UN-002-02: Remove port 443
     */
    public function test_normalize_remove_port_443(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com:443/page');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test keep custom port.
     * UN-002-03: Keep custom port
     */
    public function test_normalize_keep_custom_port(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com:8080/page');

        $this->assertEquals('https://example.com:8080/page', $result);
    }

    /**
     * Test keep port 80 for HTTPS (non-default).
     * UN-002-04: HTTPS on port 80
     */
    public function test_normalize_https_on_port_80(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com:80/page');

        $this->assertEquals('https://example.com:80/page', $result);
    }

    /**
     * Test keep port 443 for HTTP (non-default).
     * UN-002-05: HTTP on port 443
     */
    public function test_normalize_http_on_port_443(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('http://example.com:443/page');

        $this->assertEquals('http://example.com:443/page', $result);
    }

    // ==========================================
    // Trailing Slash Tests (UN-003)
    // ==========================================

    /**
     * Test remove trailing slash from path.
     * UN-003-01: Remove trailing slash
     */
    public function test_normalize_remove_trailing_slash(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page/');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test keep trailing slash when configured.
     * UN-003-02: Keep trailing slash (config)
     */
    public function test_normalize_keep_trailing_slash(): void
    {
        $normalizer = new UrlNormalizer(['keepTrailingSlash' => true]);

        $result = $normalizer->normalize('https://example.com/page/');

        $this->assertEquals('https://example.com/page/', $result);
    }

    /**
     * Test root URL keeps single slash.
     * UN-003-03: Root URL
     */
    public function test_normalize_root_url(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test remove multiple trailing slashes.
     * UN-003-04: Multiple trailing slashes
     */
    public function test_normalize_multiple_trailing_slashes(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page///');

        $this->assertEquals('https://example.com/page', $result);
    }

    // ==========================================
    // Tracking Parameter Tests (UN-004)
    // ==========================================

    /**
     * Test remove utm_source.
     * UN-004-01: Remove utm_source
     */
    public function test_normalize_remove_utm_source(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_source=google');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove utm_medium.
     * UN-004-02: Remove utm_medium
     */
    public function test_normalize_remove_utm_medium(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_medium=cpc');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove utm_campaign.
     * UN-004-03: Remove utm_campaign
     */
    public function test_normalize_remove_utm_campaign(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_campaign=spring');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove all utm_* params.
     * UN-004-04: Remove all utm params
     */
    public function test_normalize_remove_all_utm_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_source=google&utm_medium=cpc&utm_campaign=spring');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove fbclid.
     * UN-004-05: Remove fbclid
     */
    public function test_normalize_remove_fbclid(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?fbclid=abc123');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove gclid.
     * UN-004-06: Remove gclid
     */
    public function test_normalize_remove_gclid(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?gclid=xyz789');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove msclkid.
     * UN-004-07: Remove msclkid
     */
    public function test_normalize_remove_msclkid(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?msclkid=def456');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test keep other params while removing tracking.
     * UN-004-08: Keep other params
     */
    public function test_normalize_keep_other_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_source=google&page=1');

        $this->assertEquals('https://example.com/page?page=1', $result);
    }

    /**
     * Test remove multiple tracking params with other params.
     * UN-004-09: Mixed params
     */
    public function test_normalize_mixed_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?utm_source=google&fbclid=abc&page=1&gclid=xyz&sort=date');

        $this->assertEquals('https://example.com/page?page=1&sort=date', $result);
    }

    /**
     * Test disable tracking param removal.
     * UN-004-10: Disable tracking removal
     */
    public function test_normalize_disable_tracking_removal(): void
    {
        $normalizer = new UrlNormalizer(['removeTrackingParams' => false]);

        $result = $normalizer->normalize('https://example.com/page?utm_source=google');

        $this->assertEquals('https://example.com/page?utm_source=google', $result);
    }

    // ==========================================
    // Query Parameter Sorting Tests (UN-005)
    // ==========================================

    /**
     * Test sort query parameters.
     * UN-005-01: Sort params
     */
    public function test_normalize_sort_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?b=2&a=1&c=3');

        $this->assertEquals('https://example.com/page?a=1&b=2&c=3', $result);
    }

    /**
     * Test sort params with same first letter.
     * UN-005-02: Sort similar params
     */
    public function test_normalize_sort_similar_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?zebra=zoo&apple=ant&zoo=zebra');

        $this->assertEquals('https://example.com/page?apple=ant&zebra=zoo&zoo=zebra', $result);
    }

    /**
     * Test disable query sorting.
     * UN-005-03: Disable sorting
     */
    public function test_normalize_disable_sorting(): void
    {
        $normalizer = new UrlNormalizer(['sortQueryParams' => false]);

        $result = $normalizer->normalize('https://example.com/page?b=2&a=1');

        $this->assertEquals('https://example.com/page?b=2&a=1', $result);
    }

    // ==========================================
    // Fragment Tests (UN-006)
    // ==========================================

    /**
     * Test remove fragment.
     * UN-006-01: Remove fragment
     */
    public function test_normalize_remove_fragment(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page#section');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove fragment with query.
     * UN-006-02: Fragment with query
     */
    public function test_normalize_remove_fragment_with_query(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?a=1#section');

        $this->assertEquals('https://example.com/page?a=1', $result);
    }

    /**
     * Test keep fragment when configured.
     * UN-006-03: Keep fragment (config)
     */
    public function test_normalize_keep_fragment(): void
    {
        $normalizer = new UrlNormalizer(['keepFragment' => true]);

        $result = $normalizer->normalize('https://example.com/page#section');

        $this->assertEquals('https://example.com/page#section', $result);
    }

    // ==========================================
    // Encoding Tests (UN-007)
    // ==========================================

    /**
     * Test decode and re-encode percent-encoded characters.
     * UN-007-01: Re-encode
     */
    public function test_normalize_re_encode(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page%20with%20spaces');

        $this->assertEquals('https://example.com/page%20with%20spaces', $result);
    }

    /**
     * Test uppercase percent encoding normalized to uppercase.
     * UN-007-02: Uppercase encoding
     */
    public function test_normalize_uppercase_encoding(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page%2Fwith%2Fslashes');

        $this->assertEquals('https://example.com/page%2Fwith%2Fslashes', $result);
    }

    /**
     * Test already decoded characters in path.
     * UN-007-03: Decoded characters
     */
    public function test_normalize_decoded_characters(): void
    {
        $normalizer = new UrlNormalizer();

        // Unicode characters should be percent-encoded
        $result = $normalizer->normalize('https://example.com/日本語');

        $this->assertStringContainsString('example.com', $result);
    }

    // ==========================================
    // IDN (Internationalized Domain Names) Tests (UN-008)
    // ==========================================

    /**
     * Test IDN domain converted to punycode.
     * UN-008-01: IDN to punycode
     */
    public function test_normalize_idn_to_punycode(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://例え.jp/page');

        $this->assertStringContainsString('xn--', $result);
    }

    /**
     * Test IDN domain with unicode path.
     * UN-008-02: IDN with unicode path
     */
    public function test_normalize_idn_with_unicode_path(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://例え.jp/パス');

        $this->assertStringContainsString('xn--', $result);
    }

    /**
     * Test already punycode domain.
     * UN-008-03: Already punycode
     */
    public function test_normalize_already_punycode(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://xn--r8jz45g.jp/page');

        $this->assertEquals('https://xn--r8jz45g.jp/page', $result);
    }

    // ==========================================
    // IPv6 Tests (UN-009)
    // ==========================================

    /**
     * Test IPv6 URL.
     * UN-009-01: IPv6 URL
     */
    public function test_normalize_ipv6_url(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://[::1]/page');

        $this->assertEquals('https://[::1]/page', $result);
    }

    /**
     * Test IPv6 with port.
     * UN-009-02: IPv6 with port
     */
    public function test_normalize_ipv6_with_port(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://[2001:db8::1]:8080/page');

        $this->assertEquals('https://[2001:db8::1]:8080/page', $result);
    }

    /**
     * Test IPv6 remove default port 443.
     * UN-009-03: IPv6 remove port 443
     */
    public function test_normalize_ipv6_remove_port_443(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://[2001:db8::1]:443/page');

        $this->assertEquals('https://[2001:db8::1]/page', $result);
    }

    // ==========================================
    // Edge Cases Tests (UN-010)
    // ==========================================

    /**
     * Test empty query string.
     * UN-010-01: Empty query string
     */
    public function test_normalize_empty_query(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page?');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test empty fragment.
     * UN-010-02: Empty fragment
     */
    public function test_normalize_empty_fragment(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com/page#');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test double slash in path.
     * UN-010-03: Double slash in path
     */
    public function test_normalize_double_slash_in_path(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com//page');

        $this->assertEquals('https://example.com//page', $result);
    }

    /**
     * Test URL with only host.
     * UN-010-04: URL with only host
     */
    public function test_normalize_url_only_host(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test URL with username only.
     * UN-010-05: URL with username
     */
    public function test_normalize_url_with_username(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://user@example.com/page');

        $this->assertEquals('https://user@example.com/page', $result);
    }

    /**
     * Test URL with username and password.
     * UN-010-06: URL with username and password
     */
    public function test_normalize_url_with_credentials(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalize('https://user:pass@example.com/page');

        $this->assertEquals('https://user:pass@example.com/page', $result);
    }

    // ==========================================
    // Comparison Tests (UN-011)
    // ==========================================

    /**
     * Test same URL normalizes identically.
     * UN-011-01: Same URL
     */
    public function test_normalize_same_url_identical(): void
    {
        $normalizer = new UrlNormalizer();

        $result1 = $normalizer->normalize('https://example.com/page');
        $result2 = $normalizer->normalize('https://example.com/page');

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test equivalent URLs normalize identically.
     * UN-011-02: Equivalent URLs
     */
    public function test_normalize_equivalent_urls_identical(): void
    {
        $normalizer = new UrlNormalizer();

        $result1 = $normalizer->normalize('HTTPS://EXAMPLE.COM/page/');
        $result2 = $normalizer->normalize('https://example.com/page');

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test different URLs stay different.
     * UN-011-03: Different URLs
     */
    public function test_normalize_different_urls_different(): void
    {
        $normalizer = new UrlNormalizer();

        $result1 = $normalizer->normalize('https://example.com/page1');
        $result2 = $normalizer->normalize('https://example.com/page2');

        $this->assertNotEquals($result1, $result2);
    }

    // ==========================================
    // Helper Method Tests (UN-012)
    // ==========================================

    /**
     * Test getHost method.
     * UN-012-01: getHost
     */
    public function test_get_host(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->getHost('https://example.com/page');

        $this->assertEquals('example.com', $result);
    }

    /**
     * Test getPath method.
     * UN-012-02: getPath
     */
    public function test_get_path(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->getPath('https://example.com/page/sub');

        $this->assertEquals('/page/sub', $result);
    }

    /**
     * Test getQueryParams method.
     * UN-012-03: getQueryParams
     */
    public function test_get_query_params(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->getQueryParams('https://example.com/page?a=1&b=2');

        $this->assertEquals(['a' => '1', 'b' => '2'], $result);
    }

    /**
     * Test isSameOrigin method.
     * UN-012-04: isSameOrigin
     */
    public function test_is_same_origin(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertTrue($normalizer->isSameOrigin(
            'https://example.com/page1',
            'https://example.com/page2'
        ));

        $this->assertFalse($normalizer->isSameOrigin(
            'https://example.com/page1',
            'https://other.com/page2'
        ));
    }
}
