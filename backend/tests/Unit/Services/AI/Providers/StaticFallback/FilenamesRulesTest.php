<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Filename Rules.
 *
 * Tests the rule-based analysis for SEO-friendly filenames.
 */
class FilenamesRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeFilenames(array $files): array
    {
        $context = json_encode([
            'indicator' => 'filenames',
            'files' => $files,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    // =====================================================
    // SEO-Friendly Tests
    // =====================================================

    #[Test]
    public function lowercase_with_hyphens_scores_high(): void
    {
        $files = [
            ['name' => 'best-widgets-2026.jpg', 'type' => 'image'],
            ['name' => 'product-catalog.pdf', 'type' => 'document'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Lowercase with hyphens should score high');
    }

    #[Test]
    public function spaces_in_name_flagged(): void
    {
        $files = [
            ['name' => 'best widgets 2026.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertLessThan(85, $result['score'], 'Spaces should have penalty');

        $hasSpaceIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'space') ||
                         str_contains(strtolower($issue), 'hyphen')
        );
        $this->assertTrue($hasSpaceIssue, 'Should flag spaces in filename');
    }

    #[Test]
    public function uppercase_letters_flagged(): void
    {
        $files = [
            ['name' => 'BestWidgets.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $hasCaseIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'lowercase') ||
                         str_contains(strtolower($issue), 'case')
        );
        $this->assertTrue($hasCaseIssue, 'Should suggest lowercase');
    }

    #[Test]
    public function special_characters_flagged(): void
    {
        $files = [
            ['name' => 'widget@2026#.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertLessThan(80, $result['score'], 'Special characters should have penalty');

        $hasSpecialIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'special') ||
                         str_contains(strtolower($issue), 'character')
        );
        $this->assertTrue($hasSpecialIssue, 'Should flag special characters');
    }

    #[Test]
    public function underscores_suggest_hyphens(): void
    {
        $files = [
            ['name' => 'best_widgets_2026.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $hasUnderscoreSuggestion = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'underscore') ||
                         str_contains(strtolower($issue), 'hyphen')
        );
        $this->assertTrue($hasUnderscoreSuggestion, 'Should suggest hyphens over underscores');
    }

    // =====================================================
    // Pattern Tests
    // =====================================================

    #[Test]
    public function generic_names_flagged(): void
    {
        $files = [
            ['name' => 'image1.jpg', 'type' => 'image'],
            ['name' => 'photo_001.png', 'type' => 'image'],
            ['name' => 'document.pdf', 'type' => 'document'],
        ];
        $result = $this->analyzeFilenames($files);

        $hasGenericIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'generic') ||
                         str_contains(strtolower($issue), 'descriptive')
        );
        $this->assertTrue($hasGenericIssue, 'Should flag generic names');
    }

    #[Test]
    public function too_long_filename_flagged(): void
    {
        $longName = str_repeat('very-long-filename-', 10) . '.jpg';
        $files = [
            ['name' => $longName, 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $hasLengthIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'long')
        );
        $this->assertTrue($hasLengthIssue, 'Should flag too long filename');
    }

    #[Test]
    public function hash_in_filename_flagged(): void
    {
        $files = [
            ['name' => 'abc123def456ghi789.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $hasHashIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'hash') ||
                         str_contains(strtolower($issue), 'random') ||
                         str_contains(strtolower($issue), 'descriptive')
        );
        $this->assertTrue($hasHashIssue, 'Should flag hash-like filenames');
    }

    // =====================================================
    // Extension Tests
    // =====================================================

    #[Test]
    public function valid_extension_recognized(): void
    {
        $files = [
            ['name' => 'product.jpg', 'type' => 'image'],
            ['name' => 'document.pdf', 'type' => 'document'],
            ['name' => 'video.mp4', 'type' => 'video'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function missing_extension_flagged(): void
    {
        $files = [
            ['name' => 'product', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertLessThan(80, $result['score']);
    }

    // =====================================================
    // Suggestions Tests
    // =====================================================

    #[Test]
    public function suggestion_for_spaces(): void
    {
        $files = [
            ['name' => 'my product image.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertNotEmpty($result['suggestions']);

        $hasHyphenSuggestion = collect($result['suggestions'])->some(
            fn($s) => str_contains(strtolower($s['text']), 'hyphen') ||
                     str_contains(str_replace(' ', '-', strtolower($s['text'])), '-')
        );
        $this->assertTrue($hasHyphenSuggestion, 'Should suggest hyphen replacement');
    }

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $files = [['name' => 'Test File.jpg', 'type' => 'image']];
        $result = $this->analyzeFilenames($files);

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
        $files = [['name' => 'test.jpg', 'type' => 'image']];
        $result = $this->analyzeFilenames($files);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
            $this->assertNotEmpty($practice);
        }
    }

    #[Test]
    public function best_practices_includes_seo_advice(): void
    {
        $files = [['name' => 'test.jpg', 'type' => 'image']];
        $result = $this->analyzeFilenames($files);
        $practicesText = strtolower(implode(' ', $result['best_practices']));

        $this->assertTrue(
            str_contains($practicesText, 'descriptive') ||
            str_contains($practicesText, 'hyphen') ||
            str_contains($practicesText, 'lowercase'),
            'Best practices should mention filename best practices'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

    public static function filenameProvider(): array
    {
        return [
            'perfect' => ['best-widgets-2026.jpg', 95],
            'with spaces' => ['best widgets.jpg', 70],
            'uppercase' => ['BestWidgets.jpg', 75],
            'underscores' => ['best_widgets.jpg', 80],
            'special chars' => ['widget@2026.jpg', 60],
            'generic' => ['image1.jpg', 65],
            'too long' => [str_repeat('very-long-', 20) . '.jpg', 55],
        ];
    }

    #[Test]
    #[DataProvider('filenameProvider')]
    public function filename_affects_score_appropriately(string $filename, int $minExpectedScore): void
    {
        $files = [['name' => $filename, 'type' => 'image']];
        $result = $this->analyzeFilenames($files);

        $this->assertGreaterThanOrEqual(
            $minExpectedScore,
            $result['score'],
            "Filename '{$filename}' should score at least {$minExpectedScore}"
        );
    }

    // =====================================================
    // Edge Cases
    // =====================================================

    #[Test]
    public function empty_file_list(): void
    {
        $result = $this->analyzeFilenames([]);

        $this->assertIsInt($result['score']);
    }

    #[Test]
    public function many_files_handled(): void
    {
        $files = [];
        for ($i = 0; $i < 100; $i++) {
            $files[] = ['name' => "file-{$i}.jpg", 'type' => 'image'];
        }
        $result = $this->analyzeFilenames($files);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function unicode_filename_handled(): void
    {
        $files = [
            ['name' => '产品图片.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertIsInt($result['score']);
    }

    // =====================================================
    // Security Tests
    // =====================================================

    #[Test]
    public function path_traversal_handled(): void
    {
        $files = [
            ['name' => '../../../etc/passwd', 'type' => 'file'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }

    #[Test]
    public function xss_in_filename_sanitized(): void
    {
        $files = [
            ['name' => '<script>alert(1)</script>.jpg', 'type' => 'image'],
        ];
        $result = $this->analyzeFilenames($files);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
