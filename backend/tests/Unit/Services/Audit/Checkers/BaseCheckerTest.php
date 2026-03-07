<?php

namespace Tests\Unit\Services\Audit\Checkers;

use Tests\TestCase;
use App\Services\Audit\Checkers\BaseChecker;
use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

// Concrete implementation for testing abstract class
class TestableBaseChecker extends BaseChecker
{
    public function check($result): ?IssueFinding
    {
        return null;
    }

    // Expose protected methods for testing
    public function testExtractTitle(Crawler $crawler): ?string
    {
        return $this->extractTitle($crawler);
    }

    public function testExtractMetaDescription(Crawler $crawler): ?string
    {
        return $this->extractMetaDescription($crawler);
    }

    public function testExtractH1s(Crawler $crawler): array
    {
        return $this->extractH1s($crawler);
    }

    public function testHasViewport(Crawler $crawler): bool
    {
        return $this->hasViewport($crawler);
    }

    public function testCreateFinding(
        string $issueType,
        string $severity,
        string $description,
        string $recommendation,
        array $context = []
    ): IssueFinding {
        return $this->createFinding($issueType, $severity, $description, $recommendation, $context);
    }
}

class BaseCheckerTest extends TestCase
{
    protected TestableBaseChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new TestableBaseChecker();
    }

    // =========================================================================
    // Extract Title Tests
    // =========================================================================

    public function test_extract_title_returns_title_text(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test Title</title></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertEquals('Test Title', $title);
    }

    public function test_extract_title_returns_null_when_missing(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertNull($title);
    }

    public function test_extract_title_trims_whitespace(): void
    {
        $html = '<!DOCTYPE html><html><head><title>   Trimmed Title   </title></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertEquals('Trimmed Title', $title);
    }

    public function test_extract_title_with_nested_elements(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Main <span>Title</span></title></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertEquals('Main Title', $title);
    }

    public function test_extract_title_uses_first_title(): void
    {
        $html = '<!DOCTYPE html><html><head><title>First</title><title>Second</title></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertEquals('First', $title);
    }

    // =========================================================================
    // Extract Meta Description Tests
    // =========================================================================

    public function test_extract_meta_description_returns_content(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Test description"></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertEquals('Test description', $description);
    }

    public function test_extract_meta_description_returns_null_when_missing(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertNull($description);
    }

    public function test_extract_meta_description_trims_whitespace(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="   Trimmed description   "></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertEquals('Trimmed description', $description);
    }

    public function test_extract_meta_description_case_insensitive(): void
    {
        $html = '<!DOCTYPE html><html><head><meta NAME="Description" content="Test"></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertEquals('Test', $description);
    }

    public function test_extract_meta_description_uses_first_match(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="First"><meta name="description" content="Second"></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertEquals('First', $description);
    }

    public function test_extract_meta_description_missing_content_attribute(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description"></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertNull($description);
    }

    // =========================================================================
    // Extract H1s Tests
    // =========================================================================

    public function test_extract_h1s_returns_array_of_texts(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>First</h1><h1>Second</h1></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        $this->assertCount(2, $h1s);
        $this->assertEquals('First', $h1s[0]);
        $this->assertEquals('Second', $h1s[1]);
    }

    public function test_extract_h1s_returns_empty_array_when_missing(): void
    {
        $html = '<!DOCTYPE html><html><body></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        $this->assertIsArray($h1s);
        $this->assertEmpty($h1s);
    }

    public function test_extract_h1s_with_nested_elements(): void
    {
        $html = '<!DOCTYPE html><html><body><h1><span>Main</span> Title</h1></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        $this->assertCount(1, $h1s);
        $this->assertEquals('Main Title', $h1s[0]);
    }

    public function test_extract_h1s_trims_whitespace(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>   Trimmed H1   </h1></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        $this->assertEquals('Trimmed H1', $h1s[0]);
    }

    public function test_extract_h1s_excludes_empty_elements(): void
    {
        $html = '<!DOCTYPE html><html><body><h1></h1><h1>Valid</h1><h1>   </h1></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        // Empty H1s should be filtered out
        $this->assertCount(1, $h1s);
        $this->assertEquals('Valid', $h1s[0]);
    }

    // =========================================================================
    // Has Viewport Tests
    // =========================================================================

    public function test_has_viewport_returns_true_when_present(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $crawler = new Crawler($html);

        $hasViewport = $this->checker->testHasViewport($crawler);

        $this->assertTrue($hasViewport);
    }

    public function test_has_viewport_returns_false_when_missing(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = new Crawler($html);

        $hasViewport = $this->checker->testHasViewport($crawler);

        $this->assertFalse($hasViewport);
    }

    public function test_has_viewport_case_insensitive(): void
    {
        $html = '<!DOCTYPE html><html><head><meta NAME="Viewport" content="width=device-width"></head><body></body></html>';
        $crawler = new Crawler($html);

        $hasViewport = $this->checker->testHasViewport($crawler);

        $this->assertTrue($hasViewport);
    }

    public function test_has_viewport_ignores_other_meta_tags(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="test"><meta name="keywords" content="test"></head><body></body></html>';
        $crawler = new Crawler($html);

        $hasViewport = $this->checker->testHasViewport($crawler);

        $this->assertFalse($hasViewport);
    }

    // =========================================================================
    // Create Finding Tests
    // =========================================================================

    public function test_create_finding_returns_issue_finding_dto(): void
    {
        $finding = $this->checker->testCreateFinding(
            'title_tag',
            'critical',
            'Title tag is missing',
            'Add a title tag to your page'
        );

        $this->assertInstanceOf(IssueFinding::class, $finding);
    }

    public function test_create_finding_sets_all_properties(): void
    {
        $finding = $this->checker->testCreateFinding(
            'meta_description',
            'warning',
            'Meta description is missing',
            'Add a meta description',
            ['length' => 0]
        );

        $this->assertEquals('meta_description', $finding->issueType);
        $this->assertEquals('warning', $finding->severity);
        $this->assertEquals('Meta description is missing', $finding->description);
        $this->assertEquals('Add a meta description', $finding->recommendation);
        $this->assertEquals(['length' => 0], $finding->context);
    }

    public function test_create_finding_defaults_empty_context(): void
    {
        $finding = $this->checker->testCreateFinding(
            'h1_tag',
            'notice',
            'Multiple H1 tags found',
            'Use only one H1 tag per page'
        );

        $this->assertEquals([], $finding->context);
    }

    public function test_create_finding_validates_severity(): void
    {
        // All valid severities should work
        $critical = $this->checker->testCreateFinding('test', 'critical', 'Test', 'Test');
        $warning = $this->checker->testCreateFinding('test', 'warning', 'Test', 'Test');
        $notice = $this->checker->testCreateFinding('test', 'notice', 'Test', 'Test');

        $this->assertEquals('critical', $critical->severity);
        $this->assertEquals('warning', $warning->severity);
        $this->assertEquals('notice', $notice->severity);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    public function test_extract_title_with_unicode(): void
    {
        $html = '<!DOCTYPE html><html><head><title>日本語タイトル</title></head><body></body></html>';
        $crawler = new Crawler($html);

        $title = $this->checker->testExtractTitle($crawler);

        $this->assertEquals('日本語タイトル', $title);
    }

    public function test_extract_meta_description_with_special_chars(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="Title &amp; Description"></head><body></body></html>';
        $crawler = new Crawler($html);

        $description = $this->checker->testExtractMetaDescription($crawler);

        $this->assertStringContainsString('Title', $description);
    }

    public function test_extract_h1s_with_emoji(): void
    {
        $html = '<!DOCTYPE html><html><body><h1>Welcome 🚀</h1></body></html>';
        $crawler = new Crawler($html);

        $h1s = $this->checker->testExtractH1s($crawler);

        $this->assertEquals('Welcome 🚀', $h1s[0]);
    }

    public function test_malformed_html_graceful_handling(): void
    {
        $html = '<html><title>Test';

        // Should not throw exception
        $crawler = new Crawler($html);
        $title = $this->checker->testExtractTitle($crawler);

        // May or may not find title depending on parser
        $this->assertTrue($title === null || is_string($title));
    }
}
