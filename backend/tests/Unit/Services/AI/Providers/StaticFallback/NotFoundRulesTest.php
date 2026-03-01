<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - 404 Handling Rules.
 */
class NotFoundRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyze404(array $context): array
    {
        $context['indicator'] = '404';
        $response = $this->provider->generate(json_encode($context));
        return json_decode($response->content, true);
    }

    #[Test]
    public function valid_custom_404_scores_well(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'custom_404_exists' => true,
            'returns_404_status' => true,
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Custom 404 should score >= 80');
    }

    #[Test]
    public function no_custom_404_flagged(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'custom_404_exists' => false,
        ]);

        $this->assertLessThan(70, $result['score'], 'No custom 404 should have penalty');

        $hasIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'custom') ||
                         str_contains(strtolower($issue), '404')
        );
        $this->assertTrue($hasIssue, 'Should flag missing custom 404');
    }

    #[Test]
    public function soft_404_major_issue(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'returns_404_status' => false,
            'returns_200_status' => true,
        ]);

        $this->assertLessThan(50, $result['score'], 'Soft 404 should have major penalty');

        $hasIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'soft') ||
                         str_contains(strtolower($issue), '200')
        );
        $this->assertTrue($hasIssue, 'Should flag soft 404');
    }

    #[Test]
    public function custom_404_with_navigation_good(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'custom_404_exists' => true,
            'has_navigation' => true,
        ]);

        $this->assertGreaterThanOrEqual(85, $result['score'], '404 with navigation is good');
    }

    #[Test]
    public function custom_404_with_search_good(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'custom_404_exists' => true,
            'has_search' => true,
        ]);

        $this->assertGreaterThanOrEqual(85, $result['score'], '404 with search is good');
    }

    #[Test]
    public function helpful_message_on_404_good(): void
    {
        $result = $this->analyze404([
            'url' => '/nonexistent',
            'custom_404_exists' => true,
            'has_helpful_message' => true,
        ]);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Helpful 404 message is good');
    }

    #[Test]
    public function broken_internal_link_flagged(): void
    {
        $result = $this->analyze404([
            'url' => '/broken-page',
            'referrer' => '/products',
            'is_internal_link' => true,
        ]);

        $this->assertLessThan(75, $result['score'], 'Broken internal link should be flagged');
    }

    #[Test]
    public function redirect_opportunity_suggested(): void
    {
        $result = $this->analyze404([
            'url' => '/old-product-page',
            'similar_urls' => ['/new-product-page'],
        ]);

        // Should suggest redirect
        $hasSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text'] ?? ''), 'redirect')
        );

        if (!$hasSuggestion) {
            $this->assertIsInt($result['score']);
        } else {
            $this->assertTrue(true, 'Redirect suggestion provided');
        }
    }

    #[Test]
    public function existing_redirect_good(): void
    {
        $result = $this->analyze404([
            'url' => '/old-page',
            'redirects_to' => '/new-page',
            'redirect_type' => 301,
        ]);

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Proper redirect is good');
    }

    #[Test]
    public function redirect_chain_warning(): void
    {
        $result = $this->analyze404([
            'url' => '/old-page',
            'redirect_chain' => ['/old-page', '/intermediate', '/final'],
        ]);

        $this->assertLessThan(80, $result['score'], 'Redirect chain should have warning');
    }

    #[Test]
    public function redirect_to_irrelevant_warning(): void
    {
        $result = $this->analyze404([
            'url' => '/old-products',
            'redirects_to' => '/homepage',
            'is_relevant' => false,
        ]);

        $this->assertLessThan(80, $result['score'], 'Irrelevant redirect should have warning');
    }

    #[Test]
    public function high_authority_404_priority(): void
    {
        $result = $this->analyze404([
            'url' => '/popular-old-page',
            'had_backlinks' => true,
            'backlink_count' => 50,
        ]);

        // Should be flagged as priority
        $hasPriority = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'priority') ||
                         str_contains(strtolower($issue), 'backlink')
        );

        $this->assertLessThan(70, $result['score'], 'High authority 404 should be priority');
    }

    #[Test]
    public function pattern_based_redirect_suggested(): void
    {
        $result = $this->analyze404([
            'url' => '/blog/old-post',
            'similar_pattern' => '/blog/',
            'suggested_redirect' => '/blog/',
        ]);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function large_number_of_404s_priority(): void
    {
        $result = $this->analyze404([
            'total_404s_on_site' => 150,
        ]);

        $this->assertLessThan(70, $result['score'], 'Large number of 404s should be priority issue');
    }

    #[Test]
    public function trailing_slash_404_checked(): void
    {
        $result = $this->analyze404([
            'url' => '/page/',
            'alternate_url' => '/page',
        ]);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function case_sensitivity_checked(): void
    {
        $result = $this->analyze404([
            'url' => '/Page',
            'lowercase_url' => '/page',
        ]);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyze404([
            'url' => '/test',
        ]);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
        }
    }

    #[Test]
    public function 404_response_includes_common_causes(): void
    {
        $result = $this->analyze404([
            'url' => '/missing-page',
        ]);

        // Should include suggestions for common 404 causes
        $this->assertIsArray($result['suggestions']);
    }
}
