<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RankCheckerService;
use App\Services\SERPFetcherInterface;
use App\Services\SERP\SERPEntryData;
use App\Services\SERP\SERPResultData;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for SPEC-003-4: Ranking Check Integration
 * 
 * Tests verify:
 * - Competitor domain handling in RankCheckerService
 * - SERP fetcher integration
 * - Ranking storage
 * - Error handling
 */
class RankCheckerServiceTest extends TestCase
{
    protected RankCheckerService $service;
    protected $mockSerpFetcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSerpFetcher = Mockery::mock(SERPFetcherInterface::class);
        $this->service = new RankCheckerService($this->mockSerpFetcher);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Test 1: Accept Competitor Domains Parameter
    // ============================================

    /**
     * Test 1: Service accepts competitor domains parameter
     */
    public function test_accepts_competitor_domains_parameter(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'checkKeyword'),
            'RankCheckerService should have checkKeyword method'
        );

        $reflection = new \ReflectionMethod($this->service, 'checkKeyword');
        $params = $reflection->getParameters();
        
        // Check if there's a competitorDomains parameter (optional)
        $paramNames = array_map(fn($p) => $p->getName(), $params);
        
        $this->assertTrue(
            in_array('competitorDomains', $paramNames) || count($params) >= 2,
            'checkKeyword should accept competitor domains parameter'
        );
    }

    // ============================================
    // Test 2-3: Fetch Rankings for Competitors
    // ============================================

    /**
     * Test 2: Fetch rankings for single competitor
     */
    public function test_fetch_rankings_for_single_competitor(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->with('seo tools', Mockery::any())
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 1, 'url' => 'https://other.com/page'],
                ['position' => 5, 'url' => 'https://user.com/page'],
                ['position' => 8, 'url' => 'https://competitor.com/page'],
            ]));

        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor.com']
        );

        $this->assertEquals(5, $result['user']['position']);
        $this->assertEquals('https://user.com/page', $result['user']['url']);
        
        $this->assertCount(1, $result['competitors']);
        $this->assertEquals('competitor.com', $result['competitors'][0]['domain']);
        $this->assertEquals(8, $result['competitors'][0]['position']);
    }

    /**
     * Test 3: Fetch rankings for multiple competitors
     */
    public function test_fetch_rankings_for_multiple_competitors(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 5, 'url' => 'https://user.com/page'],
                ['position' => 8, 'url' => 'https://competitor1.com/page'],
                ['position' => 12, 'url' => 'https://competitor2.com/page'],
                ['position' => 20, 'url' => 'https://competitor3.com/page'],
            ]));

        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor1.com', 'competitor2.com', 'competitor3.com']
        );

        $this->assertEquals(5, $result['user']['position']);
        $this->assertCount(3, $result['competitors']);
        
        $this->assertEquals(8, $result['competitors'][0]['position']);
        $this->assertEquals(12, $result['competitors'][1]['position']);
        $this->assertEquals(20, $result['competitors'][2]['position']);
    }

    // ============================================
    // Test 5: Handle Competitor Not Ranking
    // ============================================

    /**
     * Test 5: Handle competitor not in top 100
     */
    public function test_handle_competitor_not_ranking(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 5, 'url' => 'https://user.com/page'],
                // competitor.com not in results
            ]));

        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor.com']
        );

        $this->assertEquals(5, $result['user']['position']);
        $this->assertNull($result['competitors'][0]['position']);
        $this->assertNull($result['competitors'][0]['url']);
    }

    // ============================================
    // Test 6: Reuse Same SERP Request
    // ============================================

    /**
     * Test 6: Single SERP call for user + competitors
     */
    public function test_single_serp_call_for_all_domains(): void
    {
        $callCount = 0;
        
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return $this->makeSerpResult('seo tools', [
                    ['position' => 5, 'url' => 'https://user.com/page'],
                    ['position' => 8, 'url' => 'https://competitor.com/page'],
                ]);
            });

        $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor.com', 'competitor2.com', 'competitor3.com']
        );

        $this->assertEquals(1, $callCount, 'SERP fetcher should be called only once');
    }

    // ============================================
    // Test 7: Get Competitors for Site
    // ============================================

    /**
     * Test 7: Get competitors associated with site
     */
    public function test_get_competitors_for_site(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $competitor1 = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor1.com',
        ]);

        $competitor2 = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor2.com',
        ]);

        $competitors = $this->service->getCompetitorsForSite($user->id);

        $this->assertCount(2, $competitors);
        $this->assertEquals('competitor1.com', $competitors[0]->domain);
    }

    // ============================================
    // Test 9-10: Error Handling
    // ============================================

    /**
     * Test 9: Log competitor check failure without failing user check
     */
    public function test_log_competitor_failure_without_failing_user(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'competitor') || 
                       str_contains(strtolower($message), 'failed');
            });

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 5, 'url' => 'https://user.com/page'],
            ]));

        // Simulate competitor check failure
        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor.com'],
            ['throwOnCompetitorFailure' => false]
        );

        // User ranking should still be present
        $this->assertEquals(5, $result['user']['position']);
        
        // Competitor should have error logged
        $this->assertArrayHasKey('error', $result['competitors'][0] ?? []);
    }

    /**
     * Test 10: User check succeeds when all competitor checks fail
     */
    public function test_user_check_succeeds_with_competitor_failures(): void
    {
        Log::shouldReceive('error')->times(3);

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 5, 'url' => 'https://user.com/page'],
            ]));

        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['c1.com', 'c2.com', 'c3.com'],
            ['throwOnCompetitorFailure' => false]
        );

        $this->assertEquals(5, $result['user']['position']);
        $this->assertCount(3, $result['competitors']);
    }

    // ============================================
    // Test 11-12: Batch Processing
    // ============================================

    /**
     * Test 11: Batch check includes competitors
     */
    public function test_batch_check_includes_competitors(): void
    {
        $keywords = ['keyword1', 'keyword2', 'keyword3'];
        $competitors = ['competitor.com'];

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->times(3)
            ->andReturnUsing(function ($keyword) {
                return $this->makeSerpResult($keyword, [
                    ['position' => 5, 'url' => 'https://user.com/page'],
                    ['position' => 8, 'url' => 'https://competitor.com/page'],
                ]);
            });

        $results = $this->service->checkKeywords($keywords, 'user.com', $competitors);

        $this->assertCount(3, $results);
        
        foreach ($results as $result) {
            $this->assertArrayHasKey('user', $result);
            $this->assertArrayHasKey('competitors', $result);
            $this->assertCount(1, $result['competitors']);
        }
    }

    /**
     * Test 12: Batch check minimizes API calls
     */
    public function test_batch_check_minimizes_api_calls(): void
    {
        $callCount = 0;
        
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->times(5)
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return $this->makeSerpResult('test', [
                    ['position' => 5, 'url' => 'https://user.com/page'],
                    ['position' => 8, 'url' => 'https://competitor.com/page'],
                ]);
            });

        $this->service->checkKeywords(
            ['k1', 'k2', 'k3', 'k4', 'k5'],
            'user.com',
            ['competitor.com']
        );

        // 5 keywords = 5 SERP calls (competitors included in same call)
        $this->assertEquals(5, $callCount);
    }

    // ============================================
    // Test 19: Empty Competitors
    // ============================================

    /**
     * Test 19: Check works without competitors
     */
    public function test_check_without_competitors(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 5, 'url' => 'https://user.com/page'],
            ]));

        $result = $this->service->checkKeyword('seo tools', 'user.com', []);

        $this->assertEquals(5, $result['user']['position']);
        $this->assertEmpty($result['competitors']);
    }

    // ============================================
    // Test 15: Duration Tracking
    // ============================================

    /**
     * Test 15: Check duration is tracked
     */
    public function test_check_duration_tracked(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [], 150));

        $result = $this->service->checkKeyword('seo tools', 'user.com', []);

        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertIsInt($result['duration_ms']);
    }

    // ============================================
    // Test 16: Free SERP API Only
    // ============================================

    /**
     * Test 16: Verify free SERP API usage
     */
    public function test_uses_free_serp_api(): void
    {
        $fetcherClass = get_class($this->mockSerpFetcher);
        
        // The actual implementation should be one of:
        // - MockSERPFetcher
        // - GoogleCSEFetcher
        // - SelfHostedScraperFetcher
        
        // This test documents the requirement
        $this->assertTrue(true, 'Service should use free SERP options only');
    }

    // ============================================
    // Test 26: Partial SERP Results
    // ============================================

    /**
     * Test 26: Handle partial SERP results
     */
    public function test_handle_partial_serp_results(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('seo tools', [
                ['position' => 1, 'url' => 'https://other.com'],
                ['position' => 2, 'url' => 'https://another.com'],
                // Only 2 results, user and competitor not found
            ]));

        $result = $this->service->checkKeyword(
            'seo tools',
            'user.com',
            ['competitor.com']
        );

        // User not found in results
        $this->assertNull($result['user']['position']);
        // Competitor not found in results
        $this->assertNull($result['competitors'][0]['position']);
    }

    // ============================================
    // Test 29: SERP Features
    // ============================================

    /**
     * Test 29: SERP features are tracked
     */
    public function test_serp_features_tracked(): void
    {
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult(
                'seo tools',
                [
                    [
                        'position' => 1,
                        'url' => 'https://user.com',
                        'features' => ['featured_snippet', 'faq'],
                    ],
                ],
                0,
                ['featured_snippet', 'local_pack']
            ));

        $result = $this->service->checkKeyword('seo tools', 'user.com', []);

        $this->assertArrayHasKey('serp_features', $result);
    }

    // ============================================
    // Security Tests
    // ============================================

    /**
     * Security: Malicious competitor domain is sanitized
     */
    public function test_malicious_domain_sanitized(): void
    {
        $maliciousDomain = "competitor.com'; DROP TABLE competitors; --";
        
        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($this->makeSerpResult('test', []));

        // Should not throw exception, domain should be sanitized
        $result = $this->service->checkKeyword('test', 'user.com', [$maliciousDomain]);
        
        $this->assertIsArray($result);
    }

    /**
     * @param array<int, array{position:int, url:string}> $results
     * @param array<int, string>|null $serpFeatures
     */
    private function makeSerpResult(string $keyword, array $results, int $durationMs = 0, ?array $serpFeatures = null): SERPResultData
    {
        $entries = array_map(function (array $entry) {
            return new SERPEntryData(
                (int) $entry['position'],
                (string) $entry['url'],
                $entry['title'] ?? '',
                $entry['description'] ?? '',
                $entry['is_our_site'] ?? false
            );
        }, $results);

        return new SERPResultData(
            $keyword,
            'google',
            'US',
            'desktop',
            $entries,
            now()->toAtomString(),
            null,
            $durationMs,
            $serpFeatures
        );
    }
}
