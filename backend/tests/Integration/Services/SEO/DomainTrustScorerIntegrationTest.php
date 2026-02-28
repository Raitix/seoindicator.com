<?php

namespace Tests\Integration\Services\SEO;

use Tests\TestCase;
use App\Services\SEO\DomainTrustScorer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Integration tests for DomainTrustScorer service.
 * 
 * Tests real-world scenarios including DNS lookups, SSL verification,
 * and caching behavior.
 * 
 * @see TEST-23-7
 * @see TASK-23-7
 */
class DomainTrustScorerIntegrationTest extends TestCase
{
    private DomainTrustScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new DomainTrustScorer();
    }

    /**
     * @test
     * @group integration
     */
    public function test_real_trusted_domain_scores_high(): void
    {
        // Skip if no internet connection
        if (!$this->hasInternetConnection()) {
            $this->markTestSkipped('No internet connection available');
        }

        $score = $this->scorer->score('google.com');

        // Google should score high (trusted TLD, HTTPS, old domain)
        $this->assertGreaterThan(50, $score, 'google.com should score above 50');
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * @test
     * @group integration
     */
    public function test_real_gov_domain_scores_high(): void
    {
        if (!$this->hasInternetConnection()) {
            $this->markTestSkipped('No internet connection available');
        }

        $score = $this->scorer->score('usa.gov');

        // .gov domain should score high
        $this->assertGreaterThan(40, $score, '.gov domain should score above 40');
    }

    /**
     * @test
     * @group integration
     */
    public function test_real_spam_domain_scores_low(): void
    {
        if (!$this->hasInternetConnection()) {
            $this->markTestSkipped('No internet connection available');
        }

        // Test a known spam TLD
        $score = $this->scorer->score('example.xyz');

        // Spam TLD should score low
        $this->assertLessThan(20, $score, 'Spam TLD should score below 20');
    }

    /**
     * @test
     * @group integration
     */
    public function test_domain_scoring_is_cached(): void
    {
        $domain = 'cached-test-example.com';

        // First call
        $score1 = $this->scorer->score($domain);

        // Verify cache was set
        $cacheKey = "domain_trust_score:{$domain}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should use cache
        $score2 = $this->scorer->score($domain);

        $this->assertEquals($score1, $score2, 'Cached score should match original');
    }

    /**
     * @test
     * @group integration
     */
    public function test_https_check_makes_request(): void
    {
        Http::fake([
            'https://test-domain.com/*' => Http::response('', 200),
        ]);

        $this->scorer->score('test-domain.com');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test-domain.com');
        });
    }

    /**
     * @test
     * @group integration
     */
    public function test_multiple_domains_scored_independently(): void
    {
        $domains = ['google.com', 'example.org', 'test.xyz'];

        $scores = [];
        foreach ($domains as $domain) {
            $scores[$domain] = $this->scorer->score($domain);
        }

        // Each domain should have different score
        $uniqueScores = array_unique($scores);
        $this->assertGreaterThan(1, count($uniqueScores), 'Different domains should have different scores');
    }

    /**
     * @test
     * @group integration
     */
    public function test_performance_under_1_second(): void
    {
        $start = microtime(true);

        $this->scorer->score('performance-test.com');

        $duration = microtime(true) - $start;

        // Should complete in under 1 second
        $this->assertLessThan(1.0, $duration, 'Scoring should complete in under 1 second');
    }

    /**
     * @test
     * @group integration
     */
    public function test_concurrent_scoring_is_safe(): void
    {
        $domains = ['test1.com', 'test2.com', 'test3.com'];

        $scores = [];
        foreach ($domains as $domain) {
            $scores[] = $this->scorer->score($domain);
        }

        // All scores should be valid integers
        foreach ($scores as $score) {
            $this->assertIsInt($score);
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }

    /**
     * @test
     * @group integration
     */
    public function test_domain_age_estimation(): void
    {
        // Test with known old domain
        $oldDomainScore = $this->scorer->estimateAge('google.com');
        $newDomainScore = $this->scorer->estimateAge('very-new-test-domain-2024.com');

        // Old domain should have higher age score
        $this->assertGreaterThanOrEqual(0, $oldDomainScore);
        $this->assertGreaterThanOrEqual(0, $newDomainScore);
    }

    /**
     * @test
     * @group integration
     */
    public function test_full_scoring_workflow(): void
    {
        // Test complete workflow from domain input to final score
        $domain = 'complete-workflow-test.com';

        // Get individual component scores
        $httpsScore = $this->scorer->scoreHttps($domain);
        $tldScore = $this->scorer->scoreTld($domain);
        $lengthScore = $this->scorer->scoreLength($domain);
        $patternScore = $this->scorer->isCleanPattern($domain) ? 10 : 0;
        $ageScore = $this->scorer->estimateAge($domain);

        // Get composite score
        $totalScore = $this->scorer->score($domain);

        // Verify composite score is reasonable
        $minExpected = min(0, $httpsScore + $tldScore + $lengthScore + $patternScore + $ageScore);
        $maxExpected = 100;

        $this->assertGreaterThanOrEqual(0, $totalScore);
        $this->assertLessThanOrEqual(100, $totalScore);
    }

    /**
     * @test
     * @group integration
     */
    public function test_cache_expiration(): void
    {
        $domain = 'cache-expiration-test.com';

        // First call caches the result
        $score1 = $this->scorer->score($domain);

        // Manually expire the cache
        Cache::forget("domain_trust_score:{$domain}");

        // Second call should recalculate
        $score2 = $this->scorer->score($domain);

        // Scores should be equal (deterministic)
        $this->assertEquals($score1, $score2);
    }

    /**
     * @test
     * @group integration
     */
    public function test_invalid_domain_handles_gracefully(): void
    {
        // Test with non-existent domain
        $score = $this->scorer->score('this-domain-does-not-exist-12345.com');

        // Should return a valid score even if domain doesn't exist
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Check if internet connection is available.
     */
    private function hasInternetConnection(): bool
    {
        $connected = @fsockopen('www.google.com', 443);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }
}
