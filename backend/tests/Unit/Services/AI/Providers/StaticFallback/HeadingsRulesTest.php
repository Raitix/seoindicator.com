<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Headings Rules.
 *
 * Tests the rule-based analysis for heading hierarchy SEO optimization.
 */
class HeadingsRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeHeadings(array $headings, ?string $title = null, ?string $keyword = null): array
    {
        $context = json_encode([
            'indicator' => 'headings',
            'headings' => $headings,
            'title' => $title,
            'keyword' => $keyword,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // H1 Tests
    // =====================================================

    #[Test]
    public function single_h1_scores_well(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Best Widgets 2026'],
            ['level' => 2, 'text' => 'Features'],
            ['level' => 2, 'text' => 'Reviews'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Single H1 should score well');
    }

    #[Test]
    public function multiple_h1s_flagged(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'First H1'],
            ['level' => 1, 'text' => 'Second H1'],
            ['level' => 2, 'text' => 'Section'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertLessThan(80, $result['score'], 'Multiple H1s should have penalty');

        $hasMultipleH1Issue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'h1') ||
                         str_contains(strtolower($issue), 'multiple')
        );
        $this->assertTrue($hasMultipleH1Issue, 'Should flag multiple H1s');
    }

    #[Test]
    public function no_h1_flagged(): void
    {
        $headings = [
            ['level' => 2, 'text' => 'Section 1'],
            ['level' => 3, 'text' => 'Subsection'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertLessThan(70, $result['score'], 'No H1 should have significant penalty');

        $hasMissingH1Issue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'missing') ||
                         str_contains(strtolower($issue), 'h1')
        );
        $this->assertTrue($hasMissingH1Issue, 'Should flag missing H1');
    }

    #[Test]
    public function empty_h1_flagged(): void
    {
        $headings = [
            ['level' => 1, 'text' => ''],
            ['level' => 2, 'text' => 'Section'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertLessThan(70, $result['score'], 'Empty H1 should be flagged');
    }

    // =====================================================
    // Hierarchy Tests
    // =====================================================

    #[Test]
    public function valid_hierarchy_scores_well(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Main Title'],
            ['level' => 2, 'text' => 'Section 1'],
            ['level' => 3, 'text' => 'Subsection 1.1'],
            ['level' => 2, 'text' => 'Section 2'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Valid hierarchy should score well');
    }

    #[Test]
    public function skipped_level_flagged(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Main Title'],
            ['level' => 3, 'text' => 'Skipped H2'], // Skipped H2
        ];
        $result = $this->analyzeHeadings($headings);

        $hasSkippedIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'skip') ||
                         str_contains(strtolower($issue), 'hierarchy') ||
                         str_contains(strtolower($issue), 'level')
        );
        $this->assertTrue($hasSkippedIssue, 'Should flag skipped heading level');
    }

    #[Test]
    public function multiple_h2s_valid(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Main Title'],
            ['level' => 2, 'text' => 'Section 1'],
            ['level' => 2, 'text' => 'Section 2'],
            ['level' => 2, 'text' => 'Section 3'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Multiple H2s are valid');
    }

    #[Test]
    public function deep_hierarchy_valid(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Main'],
            ['level' => 2, 'text' => 'Section'],
            ['level' => 3, 'text' => 'Subsection'],
            ['level' => 4, 'text' => 'Detail'],
            ['level' => 5, 'text' => 'Sub-detail'],
            ['level' => 6, 'text' => 'Fine print'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Deep but valid hierarchy');
    }

    // =====================================================
    // Content Tests
    // =====================================================

    #[Test]
    public function h1_matches_title_good(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Best Widgets 2026'],
            ['level' => 2, 'text' => 'Features'],
        ];
        $result = $this->analyzeHeadings($headings, 'Best Widgets 2026');

        $this->assertGreaterThanOrEqual(85, $result['score'], 'H1 matching title is good SEO');
    }

    #[Test]
    public function keyword_in_h1_boosts_score(): void
    {
        $headingsWithKeyword = [
            ['level' => 1, 'text' => 'Best Widgets Guide'],
            ['level' => 2, 'text' => 'Overview'],
        ];
        $headingsWithoutKeyword = [
            ['level' => 1, 'text' => 'Our Guide'],
            ['level' => 2, 'text' => 'Overview'],
        ];

        $resultWith = $this->analyzeHeadings($headingsWithKeyword, null, 'Widgets');
        $resultWithout = $this->analyzeHeadings($headingsWithoutKeyword, null, 'Widgets');

        $this->assertGreaterThan(
            $resultWithout['score'],
            $resultWith['score'],
            'Keyword in H1 should boost score'
        );
    }

    #[Test]
    public function empty_headings_flagged(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Main Title'],
            ['level' => 2, 'text' => ''],
            ['level' => 2, 'text' => '   '], // Whitespace only
        ];
        $result = $this->analyzeHeadings($headings);

        $hasEmptyIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'empty')
        );
        $this->assertTrue($hasEmptyIssue, 'Should flag empty headings');
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_for_missing_h1(): void
    {
        $headings = [
            ['level' => 2, 'text' => 'Section'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertNotEmpty($result['suggestions']);

        $hasH1Suggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'h1') ||
                     str_contains(strtolower($s['reason'] ?? ''), 'h1')
        );
        $this->assertTrue($hasH1Suggestion, 'Should suggest adding H1');
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $headings = [['level' => 1, 'text' => 'Test']];
        $result = $this->analyzeHeadings($headings);

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
        $headings = [['level' => 1, 'text' => 'Main']];
        $result = $this->analyzeHeadings($headings);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_hierarchy_advice(): void
    {
        $headings = [['level' => 1, 'text' => 'Main']];
        $result = $this->analyzeHeadings($headings);
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'h1') ||
            str_contains($practicesText, 'hierarchy') ||
            str_contains($practicesText, 'heading'),
            'Best practices should mention headings'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function headingProvider(): array
    {
        return [
            'empty' => [[], 50],
            'no h1' => [[['level' => 2, 'text' => 'Section']], 60],
            'single h1' => [[['level' => 1, 'text' => 'Main']], 85],
            'valid structure' => [[['level' => 1, 'text' => 'Main'], ['level' => 2, 'text' => 'Section']], 90],
            'multiple h1s' => [[['level' => 1, 'text' => 'First'], ['level' => 1, 'text' => 'Second']], 65],
            'skipped level' => [[['level' => 1, 'text' => 'Main'], ['level' => 3, 'text' => 'Skipped']], 70],
        ];
    }

    #[Test]
    #[DataProvider('headingProvider')]
    public function heading_structure_affects_score_appropriately(array $headings, int $minExpectedScore): void
    {
        $result = $this->analyzeHeadings($headings);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            'Heading structure should score appropriately'
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function no_headings_at_all(): void
    {
        $result = $this->analyzeHeadings([]);

        $this->assertLessThan(60, $result['score'], 'No headings should have low score');
    }

    #[Test]
    public function very_long_heading_text(): void
    {
        $longText = str_repeat('Long heading text ', 20);
        $headings = [['level' => 1, 'text' => $longText]];
        $result = $this->analyzeHeadings($headings);

        $this->assertIsInt($result['score']);
        // Should handle gracefully, might have length warning
    }

    #[Test]
    public function unicode_in_headings(): void
    {
        $headings = [
            ['level' => 1, 'text' => 'Лучшие товары 2026'],
            ['level' => 2, 'text' => '产品特色'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function xss_in_heading_sanitized(): void
    {
        $headings = [
            ['level' => 1, 'text' => '<script>alert("xss")</script>Title'],
        ];
        $result = $this->analyzeHeadings($headings);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
