<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - URL Rules.
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

    #[Test]
    public function clean_url_structure_scores_high(): void
    {
        $result = $this->analyzeUrl('/products/best-widgets');

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Clean URL structure should score >= 90');
    }

    #[Test]
    public function keyword_in_url_boosts(): void
    {
        $result = $this->analyzeUrl('/products/best-widgets-2026', 'widgets');

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Keyword in URL should boost score');
    }

    #[Test]
    public function too_many_segments_warning(): void
    {
        $result = $this->analyzeUrl('/a/b/c/d/e/f/g/h');

        $this->assertLessThan(80, $result['score'], 'Too many segments should have warning');
    }

    #[Test]
    public function query_parameters_warning(): void
    {
        $result = $this->analyzeUrl('/page?utm_source=google');

        $this->assertLessThan(85, $result['score'], 'Query parameters should have warning');
    }

    #[Test]
    public function session_id_in_url_major_issue(): void
    {
        $result = $this->analyzeUrl('/page?sessionid=abc123');

        $this->assertLessThan(70, $result['score'], 'Session ID in URL should have major penalty');
    }

    #[Test]
    public function dynamic_url_flagged(): void
    {
        $result = $this->analyzeUrl('/page.php?id=123');

        $this->assertLessThan(75, $result['score'], 'Dynamic URL should be flagged');
    }

    #[Test]
    public function file_extension_minor_issue(): void
    {
        $result = $this->analyzeUrl('/page.html');

        $this->assertLessThan(90, $result['score'], 'File extension should have minor penalty');
    }

    #[Test]
    public function trailing_slash_valid(): void
    {
        $result = $this->analyzeUrl('/page/');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Trailing slash is valid');
    }

    #[Test]
    public function no_trailing_slash_valid(): void
    {
        $result = $this->analyzeUrl('/page');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'No trailing slash is valid');
    }

    #[Test]
    public function uppercase_in_url_flagged(): void
    {
        $result = $this->analyzeUrl('/Products/Widgets');

        $this->assertLessThan(85, $result['score'], 'Uppercase in URL should be flagged');
    }

    #[Test]
    public function non_ascii_characters_flagged(): void
    {
        $result = $this->analyzeUrl('/продукты');

        $this->assertLessThan(80, $result['score'], 'Non-ASCII characters should be flagged');
    }

    #[Test]
    public function underscores_minor_issue(): void
    {
        $result = $this->analyzeUrl('/my_page');

        $this->assertLessThan(90, $result['score'], 'Underscores should have minor penalty');
    }

    #[Test]
    public function hyphens_good(): void
    {
        $result = $this->analyzeUrl('/my-page');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Hyphens in URL are good');
    }

    #[Test]
    public function special_characters_flagged(): void
    {
        $result = $this->analyzeUrl('/page&param=value');

        $this->assertLessThan(80, $result['score'], 'Special characters should be flagged');
    }

    #[Test]
    public function hash_fragment_valid(): void
    {
        $result = $this->analyzeUrl('/page#section');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Hash fragment is valid');
    }

    #[Test]
    public function absolute_url_valid(): void
    {
        $result = $this->analyzeUrl('https://example.com/page');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Absolute URL is valid');
    }

    #[Test]
    public function long_url_warning(): void
    {
        $result = $this->analyzeUrl('/' . str_repeat('very-long-url-segment-', 15));

        $this->assertLessThan(80, $result['score'], 'Long URL should have warning');
    }

    #[Test]
    public function multiple_query_params_warning(): void
    {
        $result = $this->analyzeUrl('/page?a=1&b=2&c=3');

        $this->assertLessThan(80, $result['score'], 'Multiple query params should have warning');
    }

    #[Test]
    public function subdomain_valid(): void
    {
        $result = $this->analyzeUrl('blog.example.com/post');

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Subdomain is valid');
    }

    #[Test]
    public function deep_nesting_valid_if_organized(): void
    {
        $result = $this->analyzeUrl('/category/subcategory/item');

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Organized deep nesting is valid');
    }

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeUrl('/test');

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
        }
    }
}
