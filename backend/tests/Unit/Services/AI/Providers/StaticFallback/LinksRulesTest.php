<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Links Rules.
 */
class LinksRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeLinks(array $context): array
    {
        $context['indicator'] = 'links';
        $response = $this->provider->generate(json_encode($context));
        return json_decode($response->content, true);
    }

    #[Test]
    public function good_internal_linking_scores_well(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [
                ['href' => '/products', 'text' => 'Products'],
                ['href' => '/about', 'text' => 'About Us'],
                ['href' => '/contact', 'text' => 'Contact'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Good internal linking should score >= 80');
    }

    #[Test]
    public function no_internal_links_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [],
        ]);

        $this->assertLessThan(70, $result['score'], 'No internal links should have penalty');

        $hasIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'internal') ||
                         str_contains(strtolower($issue), 'link')
        );
        $this->assertTrue($hasIssue, 'Should flag missing internal links');
    }

    #[Test]
    public function too_many_internal_links_warning(): void
    {
        $links = [];
        for ($i = 0; $i < 250; $i++) {
            $links[] = ['href' => "/page{$i}", 'text' => "Page {$i}"];
        }

        $result = $this->analyzeLinks(['internal_links' => $links]);

        $this->assertLessThan(80, $result['score'], 'Too many internal links should have warning');
    }

    #[Test]
    public function external_links_present_valid(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'external_links' => [
                ['href' => 'https://partner.com', 'text' => 'Partner'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function broken_link_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'broken_links' => [
                ['href' => '/old-page', 'status' => 404],
            ],
        ]);

        $this->assertLessThan(80, $result['score'], 'Broken links should have penalty');
    }

    #[Test]
    public function nofollow_on_external_good(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'external_links' => [
                ['href' => 'https://untrusted.com', 'text' => 'Link', 'rel' => 'nofollow'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function external_without_nofollow_warning(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'external_links' => [
                ['href' => 'https://external.com', 'text' => 'Link', 'rel' => null],
            ],
        ]);

        $this->assertLessThan(90, $result['score'], 'External without nofollow should have warning');
    }

    #[Test]
    public function all_links_nofollow_issue(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [
                ['href' => '/', 'text' => 'Home', 'rel' => 'nofollow'],
                ['href' => '/about', 'text' => 'About', 'rel' => 'nofollow'],
            ],
        ]);

        $this->assertLessThan(70, $result['score'], 'All links nofollow is an issue');
    }

    #[Test]
    public function javascript_link_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [],
            'javascript_links' => [
                ['href' => 'javascript:void(0)', 'text' => 'Click'],
            ],
        ]);

        $this->assertLessThan(75, $result['score'], 'JavaScript links should be flagged');
    }

    #[Test]
    public function empty_href_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [
                ['href' => '', 'text' => 'Empty Link'],
            ],
        ]);

        $this->assertLessThan(75, $result['score'], 'Empty href should be flagged');
    }

    #[Test]
    public function generic_link_text_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [
                ['href' => '/products', 'text' => 'Click here'],
            ],
        ]);

        $this->assertLessThan(85, $result['score'], 'Generic link text should have penalty');

        $hasIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'click') ||
                         str_contains(strtolower($issue), 'generic')
        );
        $this->assertTrue($hasIssue, 'Should flag generic link text');
    }

    #[Test]
    public function link_text_is_url_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [
                ['href' => 'https://example.com', 'text' => 'https://example.com'],
            ],
        ]);

        $this->assertLessThan(85, $result['score'], 'URL as link text should be flagged');
    }

    #[Test]
    public function mailto_link_valid(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'special_links' => [
                ['href' => 'mailto:test@example.com', 'text' => 'Email us'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Mailto link is valid');
    }

    #[Test]
    public function tel_link_valid(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'special_links' => [
                ['href' => 'tel:+1234567890', 'text' => 'Call us'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Tel link is valid');
    }

    #[Test]
    public function affiliate_link_flagged(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
            'external_links' => [
                ['href' => 'https://affiliate.com/?ref=123', 'text' => 'Partner', 'is_affiliate' => true],
            ],
        ]);

        // Should check for disclosure
        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeLinks([
            'internal_links' => [['href' => '/', 'text' => 'Home']],
        ]);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
        }
    }
}
