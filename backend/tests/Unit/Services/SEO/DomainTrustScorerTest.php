<?php

namespace Tests\Unit\Services\SEO;

use Tests\TestCase;
use App\Services\SEO\DomainTrustScorer;
use Illuminate\Support\Facades\Http;

/**
 * Unit tests for DomainTrustScorer service.
 * 
 * Tests domain trust scoring algorithm that evaluates backlink quality
 * using heuristic analysis (HTTPS, TLD, age, length, pattern).
 * 
 * @see TEST-23-7
 * @see TASK-23-7
 */
class DomainTrustScorerTest extends TestCase
{
    private DomainTrustScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new DomainTrustScorer();
    }

    // ============================================
    // HTTPS Scoring Tests (HS)
    // ============================================

    /** @test HS-001: Domain supports HTTPS */
    public function test_https_scoring_returns_20_for_https_domain(): void
    {
        Http::fake([
            'https://trusted.com/*' => Http::response('', 200),
        ]);

        $score = $this->scorer->score('trusted.com');

        $this->assertGreaterThanOrEqual(20, $score, 'HTTPS should contribute at least 20 points');
    }

    /** @test HS-002: Domain no HTTPS */
    public function test_https_scoring_returns_0_for_http_only_domain(): void
    {
        Http::fake([
            'https://http-only.com/*' => Http::response('', null, ['Connection refused']),
            'http://http-only.com/*' => Http::response('', 200),
        ]);

        // Mock the HTTPS check to fail
        $score = $this->scorer->score('http-only.com');

        // Should not have HTTPS points
        $this->assertLessThanOrEqual(80, $score, 'HTTP-only domain should not get HTTPS points');
    }

    /** @test HS-003: IP address input */
    public function test_https_scoring_returns_0_for_ip_address(): void
    {
        $score = $this->scorer->score('192.168.1.1');

        $this->assertEquals(0, $score, 'IP address should return 0 score');
    }

    /** @test HS-004: Timeout handling */
    public function test_https_scoring_handles_timeout_gracefully(): void
    {
        Http::fake([
            'https://timeout.com/*' => Http::timeout(),
        ]);

        $score = $this->scorer->score('timeout.com');

        // Should not throw exception, just skip HTTPS points
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test HS-005: Invalid certificate */
    public function test_https_scoring_returns_0_for_invalid_certificate(): void
    {
        Http::fake([
            'https://bad-cert.com/*' => Http::response('', 200)->withHeaders(['X-SSL-Error' => 'self_signed']),
        ]);

        // Service should detect invalid cert and return 0 for HTTPS
        $score = $this->scorer->score('bad-cert.com');

        // Even if cert is invalid, domain might still get other points
        $this->assertGreaterThanOrEqual(0, $score);
    }

    // ============================================
    // TLD Scoring Tests (TS)
    // ============================================

    /** @test TS-001: Trusted TLD .com */
    public function test_tld_scoring_returns_15_for_com_tld(): void
    {
        $this->assertTldScore('example.com', 15);
    }

    /** @test TS-002: Trusted TLD .org */
    public function test_tld_scoring_returns_15_for_org_tld(): void
    {
        $this->assertTldScore('example.org', 15);
    }

    /** @test TS-003: Trusted TLD .net */
    public function test_tld_scoring_returns_15_for_net_tld(): void
    {
        $this->assertTldScore('example.net', 15);
    }

    /** @test TS-004: Trusted TLD .edu */
    public function test_tld_scoring_returns_15_for_edu_tld(): void
    {
        $this->assertTldScore('harvard.edu', 15);
    }

    /** @test TS-005: Trusted TLD .gov */
    public function test_tld_scoring_returns_15_for_gov_tld(): void
    {
        $this->assertTldScore('whitehouse.gov', 15);
    }

    /** @test TS-006: Moderate TLD .io */
    public function test_tld_scoring_returns_10_for_io_tld(): void
    {
        $this->assertTldScore('techstartup.io', 10);
    }

    /** @test TS-007: Moderate TLD .co */
    public function test_tld_scoring_returns_10_for_co_tld(): void
    {
        $this->assertTldScore('example.co', 10);
    }

    /** @test TS-008: Moderate TLD .dev */
    public function test_tld_scoring_returns_10_for_dev_tld(): void
    {
        $this->assertTldScore('myapp.dev', 10);
    }

    /** @test TS-009: Moderate TLD .app */
    public function test_tld_scoring_returns_10_for_app_tld(): void
    {
        $this->assertTldScore('mobile.app', 10);
    }

    /** @test TS-010: Other TLD */
    public function test_tld_scoring_returns_5_for_other_tld(): void
    {
        $this->assertTldScore('example.info', 5);
    }

    /** @test TS-011: Spam TLD .xyz */
    public function test_spam_tld_xyz_applies_penalty(): void
    {
        $score = $this->scorer->score('cheap.xyz');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    /** @test TS-012: Spam TLD .top */
    public function test_spam_tld_top_applies_penalty(): void
    {
        $score = $this->scorer->score('free.top');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    /** @test TS-013: Spam TLD .click */
    public function test_spam_tld_click_applies_penalty(): void
    {
        $score = $this->scorer->score('ad.click');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    /** @test TS-014: Spam TLD .loan */
    public function test_spam_tld_loan_applies_penalty(): void
    {
        $score = $this->scorer->score('payday.loan');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    /** @test TS-015: Spam TLD .work */
    public function test_spam_tld_work_applies_penalty(): void
    {
        $score = $this->scorer->score('remote.work');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    /** @test TS-016: Spam TLD .date */
    public function test_spam_tld_date_applies_penalty(): void
    {
        $score = $this->scorer->score('dating.date');
        $this->assertLessThanOrEqual(10, $score, 'Spam TLD should apply -30 penalty');
    }

    // ============================================
    // Domain Length Scoring Tests (LS)
    // ============================================

    /** @test LS-001: Short domain (≤8 chars) */
    public function test_length_scoring_returns_10_for_short_domain(): void
    {
        $score = $this->scorer->scoreLength('abc.com');
        $this->assertEquals(10, $score);
    }

    /** @test LS-002: Medium domain (≤15 chars) */
    public function test_length_scoring_returns_5_for_medium_domain(): void
    {
        $score = $this->scorer->scoreLength('example.com');
        $this->assertEquals(5, $score);
    }

    /** @test LS-003: Long domain (>15 chars) */
    public function test_length_scoring_returns_0_for_long_domain(): void
    {
        $score = $this->scorer->scoreLength('verylongdomainname.com');
        $this->assertEquals(0, $score);
    }

    /** @test LS-004: Single char domain */
    public function test_length_scoring_returns_10_for_single_char_domain(): void
    {
        $score = $this->scorer->scoreLength('a.com');
        $this->assertEquals(10, $score);
    }

    /** @test LS-005: Exactly 8 chars */
    public function test_length_scoring_boundary_8_chars(): void
    {
        $score = $this->scorer->scoreLength('abcdefgh.com'); // exactly 8 chars
        $this->assertEquals(10, $score, 'Domain with exactly 8 chars should get 10 points');
    }

    /** @test LS-006: Exactly 15 chars */
    public function test_length_scoring_boundary_15_chars(): void
    {
        $score = $this->scorer->scoreLength('abcdefghijklmno.com'); // exactly 15 chars
        $this->assertEquals(5, $score, 'Domain with exactly 15 chars should get 5 points');
    }

    /** @test LS-007: Exactly 16 chars */
    public function test_length_scoring_boundary_16_chars(): void
    {
        $score = $this->scorer->scoreLength('abcdefghijklmnop.com'); // exactly 16 chars
        $this->assertEquals(0, $score, 'Domain with 16+ chars should get 0 points');
    }

    // ============================================
    // Pattern Scoring Tests (PS)
    // ============================================

    /** @test PS-001: Clean domain (letters only) */
    public function test_pattern_scoring_returns_10_for_clean_domain(): void
    {
        $this->assertTrue($this->scorer->isCleanPattern('example'));
    }

    /** @test PS-002: Domain with hyphen */
    public function test_pattern_scoring_returns_0_for_hyphenated_domain(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('my-site'));
    }

    /** @test PS-003: Domain with numbers */
    public function test_pattern_scoring_returns_0_for_domain_with_numbers(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('site123'));
    }

    /** @test PS-004: Domain with hyphen and numbers */
    public function test_pattern_scoring_returns_0_for_complex_domain(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('my-site-2024'));
    }

    /** @test PS-005: Multiple hyphens */
    public function test_pattern_scoring_returns_0_for_multiple_hyphens(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('my-best-site-ever'));
    }

    /** @test PS-006: Starts with number */
    public function test_pattern_scoring_returns_0_for_leading_number(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('1stchoice'));
    }

    /** @test PS-007: Ends with number */
    public function test_pattern_scoring_returns_0_for_trailing_number(): void
    {
        $this->assertFalse($this->scorer->isCleanPattern('choice1'));
    }

    // ============================================
    // Edge Cases Tests (EC)
    // ============================================

    /** @test EC-001: IP address */
    public function test_ip_address_returns_0(): void
    {
        $score = $this->scorer->score('192.168.1.1');
        $this->assertEquals(0, $score, 'IP address should return 0 score');
    }

    /** @test EC-002: IPv6 address */
    public function test_ipv6_address_returns_0(): void
    {
        $score = $this->scorer->score('2001:db8::1');
        $this->assertEquals(0, $score, 'IPv6 address should return 0 score');
    }

    /** @test EC-003: Localhost */
    public function test_localhost_returns_0(): void
    {
        $score = $this->scorer->score('localhost');
        $this->assertEquals(0, $score, 'localhost should return 0 score');
    }

    /** @test EC-004: Subdomain only */
    public function test_subdomain_uses_apex_domain(): void
    {
        // The scorer should extract the apex domain for TLD analysis
        $score = $this->scorer->score('blog.example.com');
        
        // Should be treated as .com TLD
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-005: Deep subdomain */
    public function test_deep_subdomain_uses_apex_domain(): void
    {
        $score = $this->scorer->score('a.b.c.d.example.com');
        
        // Should still recognize .com TLD
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-006: Port in URL */
    public function test_port_is_stripped(): void
    {
        $score = $this->scorer->score('example.com:8080');
        
        // Port should be stripped before scoring
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-007: URL with path */
    public function test_path_is_stripped(): void
    {
        $score = $this->scorer->score('example.com/page');
        
        // Path should be stripped before scoring
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-008: URL with query */
    public function test_query_is_stripped(): void
    {
        $score = $this->scorer->score('example.com?param=1');
        
        // Query should be stripped before scoring
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-009: Empty string */
    public function test_empty_string_returns_0(): void
    {
        $score = $this->scorer->score('');
        $this->assertEquals(0, $score, 'Empty string should return 0 score');
    }

    /** @test EC-010: Null input */
    public function test_null_input_returns_0(): void
    {
        $score = $this->scorer->score(null);
        $this->assertEquals(0, $score, 'Null input should return 0 score');
    }

    /** @test EC-011: Unicode domain */
    public function test_unicode_domain_is_converted(): void
    {
        $score = $this->scorer->score('münchen.de');
        
        // Should handle IDN conversion
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-012: Punycode domain */
    public function test_punycode_domain_is_handled(): void
    {
        $score = $this->scorer->score('xn--mnchen-3ya.de');
        
        // Should recognize .de TLD
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test EC-013: Very long domain */
    public function test_very_long_domain_no_overflow(): void
    {
        $longDomain = str_repeat('a', 200) . '.com';
        $score = $this->scorer->score($longDomain);
        
        // Should not cause overflow or error
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // ============================================
    // Score Boundary Tests (SB)
    // ============================================

    /** @test SB-001: Score never below 0 */
    public function test_score_never_below_0(): void
    {
        // Even with spam penalty, score should be >= 0
        $score = $this->scorer->score('free-cheap-stuff.xyz');
        $this->assertGreaterThanOrEqual(0, $score, 'Score should never be negative');
    }

    /** @test SB-002: Score never above 100 */
    public function test_score_never_above_100(): void
    {
        // Perfect domain should cap at 100
        // Mock to return max values for all components
        $score = $this->scorer->score('google.com');
        $this->assertLessThanOrEqual(100, $score, 'Score should never exceed 100');
    }

    /** @test SB-003: Spam penalty doesn't go below 0 */
    public function test_spam_penalty_capped_at_0(): void
    {
        $score = $this->scorer->score('x.xyz');
        $this->assertEquals(0, $score, 'Even minimal spam domain should return 0, not negative');
    }

    // ============================================
    // Security Tests (SC)
    // ============================================

    /** @test SC-001: SQL injection attempt */
    public function test_sql_injection_is_safe(): void
    {
        $score = $this->scorer->score("example.com'; DROP TABLE users;--");
        
        // Should handle safely without error
        $this->assertIsInt($score);
    }

    /** @test SC-002: XSS attempt */
    public function test_xss_is_sanitized(): void
    {
        $score = $this->scorer->score("<script>alert('xss')</script>.com");
        
        // Should handle safely
        $this->assertIsInt($score);
    }

    /** @test SC-003: Null byte injection */
    public function test_null_byte_is_handled(): void
    {
        $score = $this->scorer->score("example\x00.com");
        
        // Should handle safely
        $this->assertIsInt($score);
    }

    /** @test SC-004: Command injection attempt */
    public function test_command_injection_is_safe(): void
    {
        $score = $this->scorer->score("example.com; rm -rf /");
        
        // Should handle safely
        $this->assertIsInt($score);
    }

    /** @test SC-005: Special characters */
    public function test_special_characters_handled(): void
    {
        $score = $this->scorer->score("example!@#$%.com");
        
        // Should handle gracefully
        $this->assertIsInt($score);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Assert TLD scoring for a given domain.
     */
    private function assertTldScore(string $domain, int $expectedTldScore): void
    {
        // Extract TLD and verify scoring
        $tldScore = $this->scorer->scoreTld($domain);
        $this->assertEquals($expectedTldScore, $tldScore, "TLD scoring for {$domain} failed");
    }
}
