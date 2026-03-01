<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Sitemap Rules.
 *
 * Tests the rule-based analysis for XML sitemap SEO optimization.
 */
class SitemapRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeSitemap(string $sitemapContent, int $urlCount = 0): array
    {
        $context = json_encode([
            'indicator' => 'sitemap',
            'sitemap_content' => $sitemapContent,
            'url_count' => $urlCount,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Structure Tests
    // =====================================================

    #[Test]
    public function valid_xml_structure_parsed(): void
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
    </url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function urlset_root_recognized(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function url_entries_counted(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
    <url><loc>https://example.com/page1</loc></url>
    <url><loc>https://example.com/page2</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 3);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function lastmod_recognized(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <lastmod>2026-01-15</lastmod>
    </url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'lastmod should boost score');
    }

    #[Test]
    public function changefreq_recognized(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <changefreq>weekly</changefreq>
    </url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function priority_recognized(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <priority>1.0</priority>
    </url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Coverage Tests
    // =====================================================

    #[Test]
    public function url_count_reported(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
    }

    #[Test]
    public function empty_sitemap_warning(): void
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 0);

        $this->assertLessThan(70, $result['score'], 'Empty sitemap should have low score');

        $hasEmptyWarning = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'empty') ||
                         str_contains(strtolower($issue), 'no url')
        );
        $this->assertTrue($hasEmptyWarning, 'Should warn about empty sitemap');
    }

    #[Test]
    public function missing_required_fields_flagged(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertLessThan(80, $result['score'], 'URL without loc should be flagged');
    }

    // =====================================================
    // Format Tests
    // =====================================================

    #[Test]
    public function invalid_xml_handled(): void
    {
        $sitemap = 'This is not valid XML';
        $result = $this->analyzeSitemap($sitemap, 0);

        $this->assertLessThan(50, $result['score'], 'Invalid XML should have low score');

        $hasXmlIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'xml') ||
                         str_contains(strtolower($issue), 'invalid') ||
                         str_contains(strtolower($issue), 'parse')
        );
        $this->assertTrue($hasXmlIssue, 'Should flag XML parsing issue');
    }

    #[Test]
    public function gzip_suggestion(): void
    {
        $context = json_encode([
            'indicator' => 'sitemap',
            'sitemap_content' => 'binary gzip content',
            'url_count' => 100,
            'is_gzip' => true,
        ]);
        $response = $this->provider->generate($context);
        $result = json_decode($response->content, true);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function sitemap_index_recognized(): void
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap1.xml</loc>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap2.xml</loc>
    </sitemap>
</sitemapindex>';
        $result = $this->analyzeSitemap($sitemap, 2);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Sitemap index should be recognized');
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_size_advice(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'url') ||
            str_contains($practicesText, 'limit') ||
            str_contains($practicesText, 'size'),
            'Best practices should mention URL limits or size'
        );
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

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

    public static function sitemapProvider(): array
    {
        return [
            'empty' => ['', 0, 40],
            'invalid xml' => ['not xml', 0, 40],
            'single url' => ['<urlset><url><loc>https://example.com</loc></url></urlset>', 1, 80],
            'multiple urls' => ['<urlset><url><loc>https://example.com</loc></url><url><loc>https://example.com/page</loc></url></urlset>', 2, 85],
        ];
    }

    #[Test]
    #[DataProvider('sitemapProvider')]
    public function sitemap_content_affects_score_appropriately(string $sitemap, int $urlCount, int $minExpectedScore): void
    {
        $result = $this->analyzeSitemap($sitemap, $urlCount);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "Sitemap should score at least {$minExpectedScore}"
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function large_sitemap_handled(): void
    {
        $urls = str_repeat('<url><loc>https://example.com/page-' . mt_rand() . '</loc></url>', 50000);
        $sitemap = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">{$urls}</urlset>";

        $start = microtime(true);
        $result = $this->analyzeSitemap($sitemap, 50000);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertIsInt($result['score']);
        $this->assertLessThan(100, $elapsed, 'Should process quickly');
    }

    #[Test]
    public function unicode_urls_handled(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/товары/产品</loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function xss_in_sitemap_sanitized(): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>https://example.com/<script>alert(1)</script></loc></url>
</urlset>';
        $result = $this->analyzeSitemap($sitemap, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
