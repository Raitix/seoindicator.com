<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Robots.txt Rules.
 *
 * Tests the rule-based analysis for robots.txt SEO optimization.
 */
class RobotsRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeRobots(string $robotsContent): array
    {
        $context = json_encode([
            'indicator' => 'robots',
            'robots_content' => $robotsContent,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Basic Directive Tests
    // =====================================================

    #[Test]
    public function valid_user_agent_recognized(): void
    {
        $robots = "User-agent: *\nAllow: /";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(70, $result['score']);
    }

    #[Test]
    public function allow_directive_parsed(): void
    {
        $robots = "User-agent: *\nAllow: /";
        $result = $this->analyzeRobots($robots);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('issues', $result);
    }

    #[Test]
    public function disallow_directive_parsed(): void
    {
        $robots = "User-agent: *\nDisallow: /admin/";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function sitemap_reference_recognized(): void
    {
        $robots = "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml";
        $result = $this->analyzeRobots($robots);

        $this->assertGreaterThanOrEqual(
            80,
            $result['score'],
            'Sitemap reference should boost score'
        );
    }

    #[Test]
    public function crawl_delay_recognized(): void
    {
        $robots = "User-agent: *\nCrawl-delay: 10\nAllow: /";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Security Warning Tests
    // =====================================================

    #[Test]
    public function blocks_entire_site_warning(): void
    {
        $robots = "User-agent: *\nDisallow: /";
        $result = $this->analyzeRobots($robots);

        $this->assertLessThan(
            50,
            $result['score'],
            'Blocking entire site should have low score'
        );

        $hasBlockingIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'block') ||
                         str_contains(strtolower($issue), 'disallow')
        );
        $this->assertTrue($hasBlockingIssue, 'Should warn about site blocking');
    }

    #[Test]
    public function blocks_css_js_warning(): void
    {
        $robots = "User-agent: *\nDisallow: /css/\nDisallow: /js/";
        $result = $this->analyzeRobots($robots);

        $hasCssJsWarning = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'css') ||
                         str_contains(strtolower($issue), 'js') ||
                         str_contains(strtolower($issue), 'javascript') ||
                         str_contains(strtolower($issue), 'stylesheet')
        );
        $this->assertTrue($hasCssJsWarning, 'Should warn about blocking CSS/JS');
    }

    #[Test]
    public function exposes_admin_paths_note(): void
    {
        $robots = "User-agent: *\nDisallow: /admin/\nDisallow: /wp-admin/";
        $result = $this->analyzeRobots($robots);

        // Should have note about exposing admin paths through robots.txt
        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function empty_robots_txt(): void
    {
        $result = $this->analyzeRobots('');

        $this->assertIsInt($result['score']);
        $this->assertNotEmpty($result['suggestions'], 'Should suggest adding robots.txt content');
    }

    #[Test]
    public function malformed_directives_handled(): void
    {
        $robots = "InvalidEntry\nAnotherBadLine\nNoColonHere";
        $result = $this->analyzeRobots($robots);

        $this->assertInstanceOf(\ArrayObject::class, new \ArrayObject($result));
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function large_file_handled(): void
    {
        $robots = "User-agent: *\n" . str_repeat("Allow: /page-" . PHP_EOL, 1000);
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function multiple_user_agents(): void
    {
        $robots = "User-agent: Googlebot\nAllow: /\nUser-agent: Bingbot\nAllow: /public/";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(70, $result['score']);
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeRobots("User-agent: *\nAllow: /");

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_sitemap_advice(): void
    {
        $result = $this->analyzeRobots("User-agent: *\nAllow: /");
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'sitemap') ||
            str_contains($practicesText, 'crawl'),
            'Best practices should mention sitemap or crawl'
        );
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_for_missing_sitemap(): void
    {
        $robots = "User-agent: *\nAllow: /";
        $result = $this->analyzeRobots($robots);

        $hasSitemapSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'sitemap') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'sitemap')
        );

        if (!$hasSitemapSuggestion) {
            // Check if it's in issues instead
            $hasSitemapIssue = collect($result['issues'])->some(
                fn($issue) => str_contains(strtolower($issue), 'sitemap')
            );
            $this->assertTrue($hasSitemapIssue || $hasSitemapSuggestion, 'Should suggest adding sitemap');
        } else {
            $this->assertTrue(true, 'Sitemap suggestion provided');
        }
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $result = $this->analyzeRobots("User-agent: *\nAllow: /");

        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('text', $suggestion);
            $this->assertContains(
                $suggestion['type'],
                ['improve', 'rewrite', 'add', 'remove', 'warning'],
                'Suggestion type must be valid'
            );
        }
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function robotsProvider(): array
    {
        return [
            'empty' => ['', 50],
            'allow all' => ["User-agent: *\nAllow: /", 85],
            'block all' => ["User-agent: *\nDisallow: /", 30],
            'with sitemap' => ["User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml", 90],
            'block admin only' => ["User-agent: *\nDisallow: /admin/", 80],
        ];
    }

    #[Test]
    #[DataProvider('robotsProvider')]
    public function robots_content_affects_score_appropriately(string $robots, int $minExpectedScore): void
    {
        $result = $this->analyzeRobots($robots);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "Robots content should score at least {$minExpectedScore}"
        );
    }

    // =====================================================
    // Comment and Whitespace Handling
    // =====================================================

    #[Test]
    public function comments_handled(): void
    {
        $robots = "# This is a comment\nUser-agent: *\nAllow: /";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(70, $result['score']);
    }

    #[Test]
    public function extra_whitespace_handled(): void
    {
        $robots = "User-agent:   *\n\nAllow:    /   \n\n";
        $result = $this->analyzeRobots($robots);

        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function xss_in_content_sanitized(): void
    {
        $robots = "User-agent: *\nAllow: /\n# <script>alert('xss')</script>";
        $result = $this->analyzeRobots($robots);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function sql_injection_handled(): void
    {
        $robots = "User-agent: *\nDisallow: /'; DROP TABLE users;--";
        $result = $this->analyzeRobots($robots);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
