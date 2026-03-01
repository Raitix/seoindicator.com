<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Links Rules.
 *
 * Tests the rule-based analysis for internal and external link SEO optimization.
 */
class LinksRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeLinks(array $links, string $baseUrl = 'https://example.com'): array
    {
        $context = json_encode([
            'indicator' => 'links',
            'links' => $links,
            'base_url' => $baseUrl,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Internal Links Tests
    // =====================================================

    #[Test]
    public function adequate_internal_links_scores_well(): void
    {
        $links = [];
        for ($i = 1; $i <= 10; $i++) {
            $links[] = ['url' => "https://example.com/page{$i}", 'type' => 'internal', 'status' => 200];
        }
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Adequate internal links should score well');
    }

    #[Test]
    public function no_internal_links_flagged(): void
    {
        $links = [];
        $result = $this->analyzeLinks($links);

        $this->assertLessThan(60, $result['score'], 'No links should have low score');

        $hasNoLinksIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'no link') ||
                         str_contains(strtolower($issue), 'missing') ||
                         str_contains(strtolower($issue), 'add')
        );
        $this->assertTrue($hasNoLinksIssue, 'Should flag missing links');
    }

    #[Test]
    public function too_many_internal_warning(): void
    {
        $links = [];
        for ($i = 1; $i <= 150; $i++) {
            $links[] = ['url' => "https://example.com/page{$i}", 'type' => 'internal', 'status' => 200];
        }
        $result = $this->analyzeLinks($links);

        $hasTooManyIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'many') ||
                         str_contains(strtolower($issue), 'dilut')
        );
        $this->assertTrue($hasTooManyIssue, 'Should warn about too many links');
    }

    // =====================================================
    // External Links Tests
    // =====================================================

    #[Test]
    public function nofollow_on_external_good(): void
    {
        $links = [
            ['url' => 'https://external.com', 'type' => 'external', 'rel' => 'nofollow', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Properly attributed external link');
    }

    #[Test]
    public function missing_noflag_suggestion(): void
    {
        $links = [
            ['url' => 'https://external.com', 'type' => 'external', 'rel' => null, 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $hasNofollowSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'nofollow') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'nofollow')
        );
        $this->assertTrue($hasNofollowSuggestion, 'Should suggest nofollow for external links');
    }

    #[Test]
    public function noopener_for_target_blank_good(): void
    {
        $links = [
            ['url' => 'https://external.com', 'type' => 'external', 'target' => '_blank', 'rel' => 'noopener', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'noopener with target blank is secure');
    }

    #[Test]
    public function missing_noopener_security_issue(): void
    {
        $links = [
            ['url' => 'https://external.com', 'type' => 'external', 'target' => '_blank', 'rel' => null, 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $hasSecurityIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'noopener') ||
                         str_contains(strtolower($issue), 'security')
        );
        $this->assertTrue($hasSecurityIssue, 'Should flag missing noopener');
    }

    // =====================================================
    // Broken Links Tests
    // =====================================================

    #[Test]
    public function broken_link_flagged(): void
    {
        $links = [
            ['url' => 'https://example.com/working', 'type' => 'internal', 'status' => 200],
            ['url' => 'https://example.com/broken', 'type' => 'internal', 'status' => 404],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertLessThan(80, $result['score'], 'Broken link should have penalty');

        $hasBrokenIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'broken') ||
                         str_contains(strtolower($issue), '404')
        );
        $this->assertTrue($hasBrokenIssue, 'Should flag broken link');
    }

    #[Test]
    public function multiple_broken_links_severity_increased(): void
    {
        $links = [
            ['url' => 'https://example.com/broken1', 'type' => 'internal', 'status' => 404],
            ['url' => 'https://example.com/broken2', 'type' => 'internal', 'status' => 404],
            ['url' => 'https://example.com/broken3', 'type' => 'internal', 'status' => 404],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertLessThan(60, $result['score'], 'Multiple broken links should have significant penalty');
    }

    // =====================================================
    // Anchor Text Tests
    // =====================================================

    #[Test]
    public function descriptive_anchor_text_good(): void
    {
        $links = [
            ['url' => 'https://example.com/widgets', 'type' => 'internal', 'anchor' => 'Best widgets 2026', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(85, $result['score']);
    }

    #[Test]
    public function generic_anchor_flagged(): void
    {
        $links = [
            ['url' => 'https://example.com/page', 'type' => 'internal', 'anchor' => 'click here', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $hasGenericAnchorIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'anchor') ||
                         str_contains(strtolower($issue), 'click here')
        );
        $this->assertTrue($hasGenericAnchorIssue, 'Should flag generic anchor text');
    }

    // =====================================================
    // Link Balance Tests
    // =====================================================

    #[Test]
    public function good_internal_external_balance(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'type' => 'internal', 'status' => 200],
            ['url' => 'https://example.com/page2', 'type' => 'internal', 'status' => 200],
            ['url' => 'https://external.com', 'type' => 'external', 'rel' => 'nofollow', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function too_many_external_warning(): void
    {
        $links = [];
        for ($i = 1; $i <= 20; $i++) {
            $links[] = ['url' => "https://external{$i}.com", 'type' => 'external', 'rel' => 'nofollow', 'status' => 200];
        }
        $result = $this->analyzeLinks($links);

        $hasExternalWarning = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'external')
        );
        $this->assertTrue($hasExternalWarning, 'Should warn about too many external links');
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_for_missing_links(): void
    {
        $result = $this->analyzeLinks([]);

        $this->assertNotEmpty($result['suggestions']);

        $hasAddSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'link') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'link')
        );
        $this->assertTrue($hasAddSuggestion, 'Should suggest adding links');
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $links = [['url' => 'https://example.com', 'type' => 'internal', 'status' => 200]];
        $result = $this->analyzeLinks($links);

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
        $links = [['url' => 'https://example.com', 'type' => 'internal', 'status' => 200]];
        $result = $this->analyzeLinks($links);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_link_advice(): void
    {
        $links = [['url' => 'https://example.com', 'type' => 'internal', 'status' => 200]];
        $result = $this->analyzeLinks($links);
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'link') ||
            str_contains($practicesText, 'internal') ||
            str_contains($practicesText, 'anchor'),
            'Best practices should mention link optimization'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function linksProvider(): array
    {
        return [
            'no links' => [[], 50],
            'one internal' => [[['url' => 'https://example.com/page', 'type' => 'internal', 'status' => 200]], 65],
            'adequate internal' => [
                array_fill(0, 10, ['url' => 'https://example.com/page', 'type' => 'internal', 'status' => 200]),
                85
            ],
            'broken link' => [[['url' => 'https://example.com/404', 'type' => 'internal', 'status' => 404]], 55],
        ];
    }

    #[Test]
    #[DataProvider('linksProvider')]
    public function links_affect_score_appropriately(array $links, int $minExpectedScore): void
    {
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            'Links should score appropriately'
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function many_links_handled(): void
    {
        $links = [];
        for ($i = 0; $i < 200; $i++) {
            $links[] = ['url' => "https://example.com/page{$i}", 'type' => 'internal', 'status' => 200];
        }
        $result = $this->analyzeLinks($links);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function mixed_link_types(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'type' => 'internal', 'status' => 200],
            ['url' => 'https://example.com/page2', 'type' => 'internal', 'status' => 200],
            ['url' => 'https://external.com', 'type' => 'external', 'rel' => 'nofollow noopener', 'target' => '_blank', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function javascript_url_handled(): void
    {
        $links = [
            ['url' => 'javascript:alert(1)', 'type' => 'other', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function xss_in_anchor_handled(): void
    {
        $links = [
            ['url' => 'https://example.com', 'type' => 'internal', 'anchor' => '<script>alert(1)</script>', 'status' => 200],
        ];
        $result = $this->analyzeLinks($links);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
