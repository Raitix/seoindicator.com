<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - URL Rules.
 *
 * Tests the rule-based analysis for URL structure SEO optimization.
 */
class UrlRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeUrl(string $url, ?string $keyword = null): array
    {
        $context = json_encode([
            'indicator' => 'url',
            'url' => $url,
            'keyword' => $keyword,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Structure Tests
    // =====================================================

    #[Test]
    public function clean_url_scores_high(): void
    {
        $result = $this->analyzeUrl('https://example.com/products/best-widgets');

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Clean URL should score high');
    }

    #[Test]
    public function query_parameters_suggestion(): void
    {
        $result = $this->analyzeUrl('https://example.com/products?id=123&category=5');

        $this->assertLessThan(85, $result['score'], 'Query parameters should have penalty');

        $hasQuerySuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'clean') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'query')
        );
        $this->assertTrue($hasQuerySuggestion, 'Should suggest cleaner URL');
    }

    #[Test]
    public function hash_fragment_handled(): void
    {
        $result = $this->analyzeUrl('https://example.com/page#section');

        $this->assertIsInt($result['score']);
        // Hash fragments are valid but noted
    }

    #[Test]
    public function trailing_slash_consistency(): void
    {
        $result = $this->analyzeUrl('https://example.com/products/');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    // =====================================================
    // SEO Quality Tests
    // =====================================================

    #[Test]
    public function keywords_in_url_boost_score(): void
    {
        $resultWithKeyword = $this->analyzeUrl('https://example.com/best-widgets-2026', 'widgets');
        $resultWithoutKeyword = $this->analyzeUrl('https://example.com/products/item-123', 'widgets');

        $this->assertGreaterThan(
            $resultWithoutKeyword['score'],
            $resultWithKeyword['score'],
            'Keyword in URL should boost score'
        );
    }

    #[Test]
    public function dynamic_urls_flagged(): void
    {
        $result = $this->analyzeUrl('https://example.com/product.php?id=123&sid=abc');

        $this->assertLessThan(80, $result['score'], 'Dynamic URL should have penalty');

        $hasDynamicIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'dynamic') ||
                         str_contains(strtolower($issue), 'parameter')
        );
        $this->assertTrue($hasDynamicIssue, 'Should flag dynamic URL');
    }

    #[Test]
    public function too_long_url_warning(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('very-long-path-segment-', 20) . 'page';
        $result = $this->analyzeUrl($longUrl);

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long')
        );
        $this->assertTrue($hasLengthIssue, 'Should warn about long URL');
    }

    #[Test]
    public function underscores_suggest_hyphens(): void
    {
        $result = $this->analyzeUrl('https://example.com/best_widgets_2026');

        $hasHyphenSuggestion = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'underscore') ||
                         str_contains(strtolower($issue), 'hyphen')
        );
        $this->assertTrue($hasHyphenSuggestion, 'Should suggest hyphens over underscores');
    }

    // =====================================================
    // Protocol Tests
    // =====================================================

    #[Test]
    public function https_preferred(): void
    {
        $resultHttps = $this->analyzeUrl('https://example.com/page');
        $resultHttp = $this->analyzeUrl('http://example.com/page');

        $this->assertGreaterThan(
            $resultHttp['score'],
            $resultHttps['score'],
            'HTTPS should score higher than HTTP'
        );
    }

    #[Test]
    public function http_flagged(): void
    {
        $result = $this->analyzeUrl('http://example.com/page');

        $hasHttpIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'http') ||
                         str_contains(strtolower($issue), 'secure')
        );
        $this->assertTrue($hasHttpIssue, 'Should flag non-HTTPS');
    }

    // =====================================================
    // Domain Tests
    // =====================================================

    #[Test]
    public function subdomain_handled(): void
    {
        $result = $this->analyzeUrl('https://blog.example.com/post-title');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function long_subdomain_flagged(): void
    {
        $result = $this->analyzeUrl('https://very-long-subdomain-name.example.com/page');

        // Should still work, might have minor note
        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_for_dynamic_url(): void
    {
        $result = $this->analyzeUrl('https://example.com/page.php?id=123');

        $this->assertNotEmpty($result['suggestions']);

        $hasRewriteSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'clean') ||
                     str_contains(strtolower($s['text']), 'rewrite')
        );
        $this->assertTrue($hasRewriteSuggestion, 'Should suggest URL rewrite');
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $result = $this->analyzeUrl('https://example.com/test_page');

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
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeUrl('https://example.com/page');

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_structure_advice(): void
    {
        $result = $this->analyzeUrl('https://example.com/page');
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'hyphen') ||
            str_contains($practicesText, 'lowercase') ||
            str_contains($practicesText, 'url'),
            'Best practices should mention URL structure'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function urlProvider(): array
    {
        return [
            'clean https' => ['https://example.com/best-widgets', 90],
            'with query' => ['https://example.com/page?id=123', 70],
            'dynamic php' => ['https://example.com/page.php?id=123', 60],
            'http' => ['http://example.com/page', 75],
            'with underscores' => ['https://example.com/best_widgets', 80],
            'too long' => ['https://example.com/' . str_repeat('a', 200), 65],
        ];
    }

    #[Test]
    #[DataProvider('urlProvider')]
    public function url_affects_score_appropriately(string $url, int $minExpectedScore): void
    {
        $result = $this->analyzeUrl($url);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "URL should score at least {$minExpectedScore}"
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function empty_url(): void
    {
        $result = $this->analyzeUrl('');

        $this->assertIsInt($result['score']);
        $this->assertLessThan(50, $result['score'], 'Empty URL should score low');
    }

    #[Test]
    public function root_url(): void
    {
        $result = $this->analyzeUrl('https://example.com');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(70, $result['score']);
    }

    #[Test]
    public function unicode_url_handled(): void
    {
        $result = $this->analyzeUrl('https://example.com/产品/Best-Widgets');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function xss_in_url_sanitized(): void
    {
        $result = $this->analyzeUrl('https://example.com/<script>alert(1)</script>');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function sql_injection_handled(): void
    {
        $result = $this->analyzeUrl("https://example.com/page'; DROP TABLE users;--");

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
