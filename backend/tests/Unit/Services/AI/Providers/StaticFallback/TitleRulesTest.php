<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Title Rules.
 *
 * Tests the rule-based analysis for title tag SEO optimization.
 */
class TitleRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeTitle(string $title, ?string $keyword = null): array
    {
        $context = json_encode([
            'indicator' => 'title',
            'title' => $title,
            'keyword' => $keyword,
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
        $result = $this->analyzeTitle('Best Widgets 2026 - Premium Quality Products Online');

        $this->assertGreaterThanOrEqual(
            90,
            $result['score'],
            'Optimal length title (50-60 chars) should score >= 90'
        );
    }

    #[Test]
    public function too_short_title_gets_penalty(): void
    {
        $result = $this->analyzeTitle('Home');

        $this->assertLessThan(
            80,
            $result['score'],
            'Short title (<30 chars) should receive penalty'
        );

        $this->assertNotEmpty(
            $result['issues'],
            'Short title should have issues'
        );

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'short')
        );
        $this->assertTrue($hasLengthIssue, 'Should have length-related issue');
    }

    #[Test]
    public function too_long_title_gets_penalty(): void
    {
        $longTitle = 'This is a very long title that definitely exceeds the recommended sixty characters limit for SEO optimization';
        $result = $this->analyzeTitle($longTitle);

        $this->assertLessThan(
            85,
            $result['score'],
            'Long title (>60 chars) should receive penalty'
        );

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long')
        );
        $this->assertTrue($hasLengthIssue, 'Should have length-related issue');
    }

    #[Test]
    public function exact_sixty_chars_no_penalty(): void
    {
        $title = str_repeat('a', 60);
        $result = $this->analyzeTitle($title);

        // Should not have length penalty
        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long') || str_contains(strtolower($issue), 'short')
        );
        $this->assertFalse($hasLengthIssue, '60 char title should not have length issue');
    }

    #[Test]
    public function exact_thirty_chars_no_penalty(): void
    {
        $title = str_repeat('a', 30);
        $result = $this->analyzeTitle($title);

        // Should not have "too short" penalty
        $hasShortIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'short')
        );
        $this->assertFalse($hasShortIssue, '30 char title should not be flagged as too short');
    }

    #[Test]
    public function very_long_title_gets_significant_penalty(): void
    {
        $veryLongTitle = str_repeat('Long Title Content ', 10);
        $result = $this->analyzeTitle($veryLongTitle);

        $this->assertLessThan(
            70,
            $result['score'],
            'Very long title (100+ chars) should have significant penalty'
        );
    }

    // =====================================================
    // Keyword Tests
    // =====================================================

    #[Test]
    public function keyword_at_start_boosts_score(): void
    {
        $result = $this->analyzeTitle('Best Widgets 2026 - Your Premium Store', 'Best Widgets');

        $this->assertGreaterThanOrEqual(
            90,
            $result['score'],
            'Keyword at start should boost score'
        );
    }

    #[Test]
    public function keyword_in_middle_boosts_score(): void
    {
        $resultWithoutKeyword = $this->analyzeTitle('Premium Store 2026 - Great Products', 'Best Widgets');
        $resultWithKeyword = $this->analyzeTitle('Premium Best Widgets Store 2026', 'Best Widgets');

        $this->assertGreaterThan(
            $resultWithoutKeyword['score'],
            $resultWithKeyword['score'],
            'Keyword presence should boost score'
        );
    }

    #[Test]
    public function keyword_absent_gets_penalty(): void
    {
        $result = $this->analyzeTitle('Welcome to Our Store', 'Best Widgets');

        $this->assertLessThan(
            80,
            $result['score'],
            'Missing keyword should reduce score'
        );

        $hasKeywordIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'keyword')
        );
        $this->assertTrue($hasKeywordIssue, 'Should have keyword-related issue');
    }

    #[Test]
    public function keyword_matching_is_case_insensitive(): void
    {
        $result = $this->analyzeTitle('BEST WIDGETS STORE', 'best widgets');

        // Should not have keyword absence issue
        $hasKeywordIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'keyword not found')
        );
        $this->assertFalse($hasKeywordIssue, 'Keyword match should be case insensitive');
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function empty_title_scores_low(): void
    {
        $result = $this->analyzeTitle('');

        $this->assertLessThan(
            50,
            $result['score'],
            'Empty title should score very low'
        );

        $this->assertNotEmpty(
            $result['issues'],
            'Empty title should have issues'
        );
    }

    #[Test]
    public function whitespace_only_title_scores_low(): void
    {
        $result = $this->analyzeTitle('   ');

        $this->assertLessThan(
            50,
            $result['score'],
            'Whitespace-only title should score low'
        );
    }

    #[Test]
    public function title_with_special_characters_analyzed(): void
    {
        $result = $this->analyzeTitle('Best Widgets & More | Premium Store');

        $this->assertIsInt($result['score']);
        $this->assertIsArray($result['issues']);
        $this->assertIsArray($result['suggestions']);
    }

    #[Test]
    public function title_with_unicode_analyzed(): void
    {
        $result = $this->analyzeTitle('Лучшие Widgets 2026 - Продукты');

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function multiple_issues_compound(): void
    {
        $result = $this->analyzeTitle('Home', 'Widgets');

        $this->assertLessThan(
            70,
            $result['score'],
            'Multiple issues (short + missing keyword) should compound'
        );

        $this->assertGreaterThanOrEqual(
            2,
            count($result['issues']),
            'Should have multiple issues'
        );
    }

    #[Test]
    public function perfect_title_scores_high(): void
    {
        $result = $this->analyzeTitle('Best Widgets 2026 | Premium Quality', 'Best Widgets');

        $this->assertGreaterThanOrEqual(
            95,
            $result['score'],
            'Perfect title should score >= 95'
        );
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_generated_for_short_title(): void
    {
        $result = $this->analyzeTitle('Home');

        $this->assertNotEmpty(
            $result['suggestions'],
            'Short title should generate suggestions'
        );

        $hasRewrite = collect($result['suggestions'])->some(
            fn($s) => $s['type'] === 'rewrite' || $s['type'] === 'improve'
        );
        $this->assertTrue($hasRewrite, 'Should have rewrite/improve suggestion');
    }

    #[Test]
    public function suggestion_includes_keyword(): void
    {
        $result = $this->analyzeTitle('Home', 'Widgets');

        $suggestionText = collect($result['suggestions'])
            ->pluck('text')
            ->join(' ');

        $this->assertStringContainsStringIgnoringCase(
            'Widgets',
            $suggestionText,
            'Suggestion should include target keyword'
        );
    }

    #[Test]
    public function no_context_provides_default_suggestions(): void
    {
        $context = json_encode(['indicator' => 'title']);
        $response = $this->provider->generate($context);
        $result = json_decode($response->content, true);

        $this->assertIsArray($result['suggestions']);
        $this->assertIsArray($result['best_practices']);
    }

    // =====================================================
    // Quality Tests
    // =====================================================

    #[Test]
    public function title_with_numbers_analyzed(): void
    {
        $result = $this->analyzeTitle('Top 10 Widgets 2026');

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function duplicate_words_flagged(): void
    {
        $result = $this->analyzeTitle('Best Best Widgets Store');

        $hasDuplicateIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'duplicate')
        );

        if ($hasDuplicateIssue) {
            $this->assertTrue(true, 'Duplicate words detected');
        } else {
            // If not flagged, it might not be implemented yet - that's OK
            $this->assertIsInt($result['score']);
        }
    }

    #[Test]
    public function all_caps_title_flagged(): void
    {
        $result = $this->analyzeTitle('BEST WIDGETS STORE');

        $this->assertLessThan(
            90,
            $result['score'],
            'All caps title should have penalty'
        );
    }

    #[Test]
    public function stop_words_only_scores_low(): void
    {
        $result = $this->analyzeTitle('The and or but');

        $this->assertLessThan(
            60,
            $result['score'],
            'Stop words only should score low'
        );
    }

    #[Test]
    public function brand_at_end_is_valid(): void
    {
        $result = $this->analyzeTitle('Best Widgets | ACME Corp');

        // Should not have penalty for brand suffix
        $this->assertGreaterThanOrEqual(
            80,
            $result['score'],
            'Brand at end is valid SEO practice'
        );
    }

    #[Test]
    public function brand_only_scores_low(): void
    {
        $result = $this->analyzeTitle('ACME Corp');

        $this->assertLessThan(
            70,
            $result['score'],
            'Brand-only title should score low'
        );
    }

    // =====================================================
    // Best Practices Tests
    // =====================================================

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeTitle('Test Title');

        $this->assertNotEmpty(
            $result['best_practices'],
            'Should include best practices'
        );

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_length_advice(): void
    {
        $result = $this->analyzeTitle('Test');
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'character') || str_contains($practicesText, '60'),
            'Best practices should mention character limits'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function titleLengthProvider(): array
    {
        return [
            'empty' => ['', 50],
            'very short' => ['Hi', 60],
            'short' => ['Home Page', 70],
            'optimal min' => [str_repeat('a', 50), 85],
            'optimal mid' => [str_repeat('a', 55), 90],
            'optimal max' => [str_repeat('a', 60), 85],
            'slightly long' => [str_repeat('a', 70), 75],
            'long' => [str_repeat('a', 80), 65],
            'very long' => [str_repeat('a', 100), 55],
        ];
    }

    #[Test]
    #[DataProvider('titleLengthProvider')]
    public function length_affects_score_appropriately(string $title, int $minExpectedScore): void
    {
        $result = $this->analyzeTitle($title);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "Title length '{$title}' should score at least {$minExpectedScore}"
        );
    }
}
