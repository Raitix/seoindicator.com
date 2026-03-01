<?php

namespace Tests\Unit\Services\AI\Providers\StaticFallback;

use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Static Fallback Provider - Filenames Rules.
 */
class FilenamesRulesTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    private function analyzeFilenames(array $files, ?string $keyword = null): array
    {
        $context = json_encode([
            'indicator' => 'filenames',
            'files' => $files,
            'keyword' => $keyword,
        ]);
        $response = $this->provider->generate($context);
        return json_decode($response->content, true);
    }

    #[Test]
    public function seo_friendly_filename_scores_high(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/best-widgets-2026.jpg', 'type' => 'image'],
        ]);

        $this->assertGreaterThanOrEqual(90, $result['score'], 'SEO-friendly filename should score >= 90');
    }

    #[Test]
    public function keyword_in_filename_boosts(): void
    {
        $result = $this->analyzeFilenames(
            [['path' => '/images/widgets-product.jpg', 'type' => 'image']],
            'widgets'
        );

        $this->assertGreaterThanOrEqual(90, $result['score'], 'Keyword in filename should boost score');
    }

    #[Test]
    public function spaces_in_filename_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/my image.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(85, $result['score'], 'Spaces in filename should have penalty');

        $hasIssue = collect($result['issues'])->some(
            fn($issue) => str_contains(strtolower($issue), 'space')
        );
        $this->assertTrue($hasIssue, 'Should flag spaces in filename');
    }

    #[Test]
    public function special_characters_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/image@2x.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(85, $result['score'], 'Special characters should have penalty');
    }

    #[Test]
    public function uppercase_in_filename_minor_issue(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/MyImage.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(90, $result['score'], 'Uppercase should have minor penalty');
    }

    #[Test]
    public function non_ascii_characters_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/картинка.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(85, $result['score'], 'Non-ASCII characters should have penalty');
    }

    #[Test]
    public function generic_filename_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/image1.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(80, $result['score'], 'Generic filename should have penalty');
    }

    #[Test]
    public function timestamp_filename_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/20260301_123456.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(80, $result['score'], 'Timestamp filename should have penalty');
    }

    #[Test]
    public function random_hash_filename_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/a8f3b2c1.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(75, $result['score'], 'Random hash filename should have penalty');
    }

    #[Test]
    public function too_long_filename_warning(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/' . str_repeat('very-long-filename-', 15) . '.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(85, $result['score'], 'Very long filename should have warning');
    }

    #[Test]
    public function multiple_extensions_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/image.jpg.png', 'type' => 'image'],
        ]);

        $this->assertLessThan(85, $result['score'], 'Multiple extensions should be flagged');
    }

    #[Test]
    public function no_extension_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/imagefile', 'type' => 'image'],
        ]);

        $this->assertLessThan(80, $result['score'], 'No extension should be flagged');
    }

    #[Test]
    public function hyphen_separated_good(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/my-product-photo.jpg', 'type' => 'image'],
        ]);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Hyphen-separated is good');
    }

    #[Test]
    public function underscore_separated_acceptable(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/my_product_photo.jpg', 'type' => 'image'],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'], 'Underscore-separated is acceptable');
    }

    #[Test]
    public function numbers_only_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/12345.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(75, $result['score'], 'Numbers only should be flagged');
    }

    #[Test]
    public function default_cms_filename_flagged(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/untitled.jpg', 'type' => 'image'],
        ]);

        $this->assertLessThan(75, $result['score'], 'Default CMS filename should be flagged');
    }

    #[Test]
    public function category_prefix_good(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/images/product-widget-001.jpg', 'type' => 'image'],
        ]);

        $this->assertGreaterThanOrEqual(85, $result['score'], 'Category prefix is good');
    }

    #[Test]
    public function document_file_analyzed(): void
    {
        $result = $this->analyzeFilenames([
            ['path' => '/documents/user-guide.pdf', 'type' => 'document'],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    #[Test]
    public function best_practices_returned(): void
    {
        $result = $this->analyzeFilenames([['path' => '/test.jpg', 'type' => 'image']]);

        $this->assertNotEmpty($result['best_practices']);

        foreach ($result['best_practices'] as $practice) {
            $this->assertIsString($practice);
        }
    }
}
