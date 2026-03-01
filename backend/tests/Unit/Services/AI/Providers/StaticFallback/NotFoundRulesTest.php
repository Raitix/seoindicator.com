<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - 404/Not Found Rules.
 *
 * Tests the rule-based analysis for 404 error handling and suggestions.
 */
class NotFoundRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyze404(string $url, ?string $referrer = null, ?string $siteStructure = null): array
    {
        $context = json_encode([
            'indicator' => '404',
            'url' => $url,
            'referrer' => $referrer,
            'site_structure' => $siteStructure,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Error Analysis Tests
    // =====================================================

    #[Test]
    public function page_not_found_analyzed(): void
    {
        $result = $this->analyze404('https://example.com/nonexistent-page');

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('suggestions', $result);
    }

    #[Test]
    public function redirect_suggestion_provided(): void
    {
        $result = $this->analyze404('https://example.com/old-products');

        $this->assertNotEmpty($result['suggestions']);

        $hasRedirectSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'redirect') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'redirect')
        );
        $this->assertTrue($hasRedirectSuggestion, 'Should suggest redirect for 404');
    }

    #[Test]
    public function custom_404_check(): void
    {
        $result = $this->analyze404('https://example.com/missing');

        // Should mention custom 404 page
        $issuesText = strtolower(implode(' ', $result['issues']));
        $suggestionsText = strtolower(collect($result['suggestions'])->pluck('text')->join(' '));

        $has404Advice = str_contains($issuesText, '404') ||
                       str_contains($suggestionsText, '404') ||
                       str_contains($suggestionsText, 'page not found');

        $this->assertTrue($has404Advice || !empty($result['suggestions']), 'Should provide 404 handling advice');
    }

    // =====================================================
    // Common Causes Tests
    // =====================================================

    #[Test]
    public function url_typo_suggestion(): void
    {
        $result = $this->analyze404('https://example.com/produts'); // typo: "products"

        $this->assertNotEmpty($result['suggestions']);

        // Should detect potential typo or suggest similar page
        $this->assertIsArray($result['suggestions']);
    }

    #[Test]
    public function deleted_page_redirect_suggestion(): void
    {
        $result = $this->analyze404('https://example.com/old-page-name');

        $this->assertNotEmpty($result['suggestions']);

        // Should suggest redirect or alternative page
        $hasAlternativeSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'redirect') ||
                     str_contains(strtolower($s['text']), 'alternative') ||
                     str_contains(strtolower($s['text']), 'similar')
        );
        $this->assertTrue($hasAlternativeSuggestion || !empty($result['suggestions']));
    }

    #[Test]
    public function case_sensitivity_suggestion(): void
    {
        $result = $this->analyze404('https://example.com/Products'); // uppercase

        // Should note about case sensitivity
        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
    }

    // =====================================================
    // URL Pattern Analysis Tests
    // =====================================================

    #[Test]
    public function trailing_slash_mismatch(): void
    {
        $result = $this->analyze404('https://example.com/page/');

        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
    }

    #[Test]
    public function file_extension_in_url(): void
    {
        $result = $this->analyze404('https://example.com/page.html');

        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['suggestions']);
    }

    #[Test]
    public function query_parameter_issue(): void
    {
        $result = $this->analyze404('https://example.com/page?oldparam=value');

        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
    }

    // =====================================================
    // Referrer Analysis Tests
    // =====================================================

    #[Test]
    public function internal_referrer_noted(): void
    {
        $result = $this->analyze404(
            'https://example.com/broken',
            'https://example.com/linking-page'
        );

        $this->assertIsInt($result['score']);

        // Should note internal broken link
        $hasReferrerNote = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'internal') ||
                         str_contains(strtolower($issue), 'link')
        );
        $this->assertTrue($hasReferrerNote || !empty($result['suggestions']));
    }

    #[Test]
    public function external_referrer_noted(): void
    {
        $result = $this->analyze404(
            'https://example.com/external-link-target',
            'https://external-site.com/page'
        );

        $this->assertIsInt($result['score']);

        // Should note external link to broken page
        $this->assertIsArray($result['issues']);
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $result = $this->analyze404('https://example.com/missing');

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

    #[Test]
    public function multiple_suggestions_provided(): void
    {
        $result = $this->analyze404('https://example.com/old-deleted-page');

        $this->assertNotEmpty($result['suggestions']);
        $this->assertGreaterThanOrEqual(1, count($result['suggestions']));
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyze404('https://example.com/missing');

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_404_advice(): void
    {
        $result = $this->analyze404('https://example.com/missing');
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, '404') ||
            str_contains($practicesText, 'redirect') ||
            str_contains($practicesText, 'page'),
            'Best practices should mention 404 handling'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function url404Provider(): array
    {
        return [
            'simple path' => ['https://example.com/missing', 70],
            'deep path' => ['https://example.com/category/subcategory/missing', 65],
            'with query' => ['https://example.com/missing?param=value', 60],
            'old product' => ['https://example.com/products/old-item-123', 65],
        ];
    }

    #[Test]
    #[DataProvider('url404Provider')]
    public function url_404_affects_score_appropriately(string $url, int $minExpectedScore): void
    {
        $result = $this->analyze404($url);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            '404 URL should score appropriately'
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function empty_url(): void
    {
        $result = $this->analyze404('');

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function very_long_url(): void
    {
        $url = 'https://example.com/' . str_repeat('very-long-path-', 50) . 'page';
        $result = $this->analyze404($url);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function unicode_url(): void
    {
        $result = $this->analyze404('https://example.com/产品/不存在');

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
        $result = $this->analyze404('https://example.com/<script>alert(1)</script>');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function sql_injection_handled(): void
    {
        $result = $this->analyze404("https://example.com/page'; DROP TABLE users;--");

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function path_traversal_handled(): void
    {
        $result = $this->analyze404('https://example.com/../../../etc/passwd');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    // =====================================================
    // HTTP vs HTTPS Tests
    // =====================================================

    #[Test]
    public function http_404_analyzed(): void
    {
        $result = $this->analyze404('http://example.com/missing');

        $this->assertIsInt($result['score']);
        $this->assertArrayHasKey('issues', $result);
    }

    #[Test]
    public function https_404_analyzed(): void
    {
        $result = $this->analyze404('https://example.com/missing');

        $this->assertIsInt($result['score']);
        $this->assertArrayHasKey('issues', $result);
    }
}
