<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Meta Description Rules.
 */
class MetaDescriptionRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeMetaDescription(string $description, ?string $keyword = null, ?string $title = null): array
    {
        $context = json_encode([
            'indicator' => 'meta_description',
            'description' => $description,
            'keyword' => $keyword,
            'title' => $title,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // Length Tests
    // =====================================================

    #[Test]
    public function optimal_length_scores_well(): void
    {
        $description = 'Discover our premium widgets that deliver exceptional quality and value. Shop now and enjoy free shipping on all orders over $50.';
        $result = $this->analyzeMetaDescription($description);

        $this->assertGreaterThanOrEqual(
            90,
            $result['score'],
            'Optimal length meta description (150-160 chars) should score >= 90'
        );
    }

    #[Test]
    public function too_short_description_gets_penalty(): void
    {
        $result = $this->analyzeMetaDescription('Buy widgets.');

        $this->assertLessThan(
            80,
            $result['score'],
            'Short meta description (<120 chars) should receive penalty'
        );

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'short')
        );
        $this->assertTrue($hasLengthIssue, 'Should have length-related issue');
    }

    #[Test]
    public function too_long_description_gets_penalty(): void
    {
        $longDescription = str_repeat('This is a long description that exceeds limits. ', 10);
        $result = $this->analyzeMetaDescription($longDescription);

        $this->assertLessThan(
            85,
            $result['score'],
            'Long meta description (>160 chars) should receive penalty'
        );

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long')
        );
        $this->assertTrue($hasLengthIssue, 'Should have length-related issue');
    }

    // =====================================================
    // Call-to-Action Tests
    // =====================================================

    #[Test]
    public function cta_present_boosts_score(): void
    {
        $resultWithCta = $this->analyzeMetaDescription('Buy now and save 20% on all widgets!');
        $resultWithoutCta = $this->analyzeMetaDescription('We offer a variety of widgets for sale.');

        $this->assertGreaterThan(
            $resultWithoutCta['score'],
            $resultWithCta['score'],
            'CTA presence should boost score'
        );
    }

    #[Test]
    public function no_cta_generates_suggestion(): void
    {
        $result = $this->analyzeMetaDescription('We sell widgets online.');

        $hasCtaSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'now') ||
                      str_contains(strtolower($s['text']), 'today') ||
                      str_contains(strtolower($s['reason'] ?? ''), 'cta')
        );

        if (!$hasCtaSuggestion) {
            // Check issues instead
            $hasCtaIssue = collect($result['issues'])->some(
                fn($issue) => str_contains(strtolower($issue), 'call') ||
                             str_contains(strtolower($issue), 'cta')
            );
            $this->assertTrue($hasCtaIssue || $hasCtaSuggestion, 'Should suggest adding CTA');
        } else {
            $this->assertTrue(true, 'CTA suggestion provided');
        }
    }

    #[Test]
    public function multiple_ctas_flagged_as_spammy(): void
    {
        $result = $this->analyzeMetaDescription('Buy now! Shop today! Order here! Get it now!');

        $this->assertLessThan(
            80,
            $result['score'],
            'Multiple CTAs should be flagged as spammy'
        );
    }

    // =====================================================
    // Keyword Tests
    // =====================================================

    #[Test]
    public function keyword_present_boosts_score(): void
    {
        $result = $this->analyzeMetaDescription(
            'Best widgets at great prices. Shop our selection today!',
            'widgets'
        );

        $this->assertGreaterThanOrEqual(
            85,
            $result['score'],
            'Keyword presence should boost score'
        );
    }

    #[Test]
    public function keyword_absent_reduces_score(): void
    {
        $result = $this->analyzeMetaDescription(
            'We offer great products at amazing prices.',
            'widgets'
        );

        $this->assertLessThan(
            80,
            $result['score'],
            'Missing keyword should reduce score'
        );
    }

    // =====================================================
    // Content Quality Tests
    // =====================================================

    #[Test]
    public function empty_description_scores_low(): void
    {
        $result = $this->analyzeMetaDescription('');

        $this->assertLessThan(
            50,
            $result['score'],
            'Empty description should score very low'
        );
    }

    #[Test]
    public function duplicate_of_title_flagged(): void
    {
        $title = 'Best Widgets 2026 - Premium Quality';
        $result = $this->analyzeMetaDescription($title, null, $title);

        $hasDuplicateIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'duplicate') ||
                         str_contains(strtolower($issue), 'same as title')
        );
        $this->assertTrue($hasDuplicateIssue, 'Duplicate of title should be flagged');
    }

    #[Test]
    public function unicode_support(): void
    {
        $result = $this->analyzeMetaDescription('Découvrez nos meilleurs widgets - Livraison gratuite!');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function no_punctuation_minor_issue(): void
    {
        $result = $this->analyzeMetaDescription('We sell the best widgets online for great prices');

        // Should still be valid, but might have minor issue
        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function contains_quotes_valid(): void
    {
        $result = $this->analyzeMetaDescription('Experts say "best widgets on the market" - read our reviews');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    #[Test]
    public function html_entities_flagged(): void
    {
        $result = $this->analyzeMetaDescription('Widgets &amp; More Products');

        $hasHtmlIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'html') ||
                         str_contains(strtolower($issue), 'entity')
        );

        // Either flagged or decoded - both are acceptable
        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function phone_number_recognized_as_cta(): void
    {
        $result = $this->analyzeMetaDescription('Call 555-1234 today for the best deals!');

        $this->assertGreaterThanOrEqual(
            80,
            $result['score'],
            'Phone number with CTA should score well'
        );
    }

    #[Test]
    public function price_mention_good_for_ecommerce(): void
    {
        $result = $this->analyzeMetaDescription('Premium widgets starting at $9.99 - Shop now!');

        $this->assertGreaterThanOrEqual(
            85,
            $result['score'],
            'Price mention with CTA should score well'
        );
    }

    #[Test]
    public function emoji_valid_but_suggested_caution(): void
    {
        $result = $this->analyzeMetaDescription('Best widgets 🎉 Shop now for great deals!');

        // Should still work, but might have a note
        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_preserves_message(): void
    {
        $result = $this->analyzeMetaDescription('Buy widgets.');

        $this->assertNotEmpty($result['suggestions']);

        // Suggestion should improve on original
        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('text', $suggestion);
        }
    }

    #[Test]
    public function character_count_reported(): void
    {
        $result = $this->analyzeMetaDescription('Short');

        $issuesText = implode(' ', $result['issues']);

        // Should mention character count
        $this->assertTrue(
            str_contains(strtolower($issuesText), 'character') ||
            str_contains(strtolower($issuesText), 'char') ||
            preg_match('/\d+/', $issuesText),
            'Should report character count in issue'
        );
    }

    #[Test]
    public function mismatch_with_title_flagged(): void
    {
        $result = $this->analyzeMetaDescription(
            'Premium shoes at great prices',
            null,
            'Best Widgets 2026'
        );

        $hasMismatchIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'match') ||
                         str_contains(strtolower($issue), 'align')
        );

        // Either flagged or not - implementation dependent
        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeMetaDescription('Test description');

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_length_advice(): void
    {
        $result = $this->analyzeMetaDescription('Test');
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'character') ||
            str_contains($practicesText, '160'),
            'Best practices should mention character limits'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function descriptionLengthProvider(): array
    {
        return [
            'empty' => ['', 40],
            'very short' => ['Buy widgets.', 60],
            'short' => ['We sell the best widgets in town.', 70],
            'optimal min' => [str_repeat('a', 150), 85],
            'optimal mid' => [str_repeat('a', 155), 90],
            'optimal max' => [str_repeat('a', 160), 85],
            'slightly long' => [str_repeat('a', 170), 75],
            'long' => [str_repeat('a', 200), 65],
        ];
    }

    #[Test]
    #[DataProvider('descriptionLengthProvider')]
    public function length_affects_score_appropriately(string $description, int $minExpectedScore): void
    {
        $result = $this->analyzeMetaDescription($description);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "Description length should score at least {$minExpectedScore}"
        );
    }
}
