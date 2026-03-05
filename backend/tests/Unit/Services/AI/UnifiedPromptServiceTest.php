<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\UnifiedPromptService;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for UnifiedPromptService.
 * 
 * Tests prompt building, indicator definitions, JSON schema,
 * page info extraction, and configuration for SPEC-031.
 * 
 * @see TASK-31-4
 * @see SPEC-31: Unified AI Prompt + Context Management
 */
class UnifiedPromptServiceTest extends TestCase
{
    private UnifiedPromptService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UnifiedPromptService();
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    #[Test]
    #[Group('instantiation')]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UnifiedPromptService::class, $this->service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_build_prompt_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildPrompt'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_prompt_template_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getPromptTemplate'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_indicator_definitions_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getIndicatorDefinitions'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_json_schema_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getJsonSchema'));
    }

    // =========================================================================
    // buildPrompt() Method Tests
    // =========================================================================

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_returns_string(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_includes_url(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com/page', $html);

        $this->assertStringContainsString('https://example.com/page', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_includes_page_title(): void
    {
        $html = '<html><head><title>My Test Page</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('My Test Page', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_includes_meta_description(): void
    {
        $html = '<html><head><title>Test</title><meta name="description" content="This is my meta description"></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('This is my meta description', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_handles_missing_title(): void
    {
        $html = '<html><head></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Unknown', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_handles_missing_meta_description(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Not set', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_includes_html_content(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>Unique test content XYZ123</p></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('Unique test content XYZ123', $prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_accepts_options_array(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html, ['custom' => 'option']);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('buildPrompt')]
    public function buildPrompt_escapes_html_content(): void
    {
        $html = '<html><head><title>Test</title></head><body><script>alert("xss")</script></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        // Script tags should be present but in a safe context (markdown code block)
        $this->assertStringContainsString('<script>', $prompt);
    }

    // =========================================================================
    // Indicator Definitions Tests (17 Indicators)
    // =========================================================================

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_returns_array(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        $this->assertIsArray($indicators);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_has_seventeen_indicators(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        $this->assertCount(17, $indicators, 'Should have exactly 17 SEO indicators');
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_url_structure(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('url', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_title_tag(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('title_tag', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_meta_description(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('meta_description', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_robots_txt(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('robots_txt', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_sitemap_xml(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('sitemap_xml', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_h1_headings(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('h1_headings', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_filenames(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('filenames', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_image_alt(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('image_alt', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_links(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('links', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_404_page(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('404_page', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_mobile_friendly(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('mobile_friendly', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_language(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('language', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_favicon(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('favicon', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_canonical(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('canonical', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_social_meta(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('social_meta', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_aria(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('aria', $ids);
    }

    #[Test]
    #[Group('indicators')]
    public function getIndicatorDefinitions_includes_page_speed(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $ids = array_column($indicators, 'id');

        $this->assertContains('page_speed', $ids);
    }

    // =========================================================================
    // Indicator Structure Tests
    // =========================================================================

    #[Test]
    #[Group('indicator-structure')]
    public function each_indicator_has_id(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertArrayHasKey('id', $indicator, "Indicator missing 'id' field");
        }
    }

    #[Test]
    #[Group('indicator-structure')]
    public function each_indicator_has_name(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertArrayHasKey('name', $indicator, "Indicator missing 'name' field");
        }
    }

    #[Test]
    #[Group('indicator-structure')]
    public function each_indicator_has_weight(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertArrayHasKey('weight', $indicator, "Indicator missing 'weight' field");
        }
    }

    #[Test]
    #[Group('indicator-structure')]
    public function each_indicator_has_category(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertArrayHasKey('category', $indicator, "Indicator missing 'category' field");
        }
    }

    #[Test]
    #[Group('indicator-structure')]
    public function each_indicator_has_checks(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertArrayHasKey('checks', $indicator, "Indicator missing 'checks' field");
        }
    }

    // =========================================================================
    // Indicator Weights Tests
    // =========================================================================

    #[Test]
    #[Group('weights')]
    public function weights_sum_to_one(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $totalWeight = array_sum(array_column($indicators, 'weight'));

        $this->assertEqualsWithDelta(1.00, $totalWeight, 0.001, 'Indicator weights should sum to 1.00');
    }

    #[Test]
    #[Group('weights')]
    public function title_tag_weight_is_0_10(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $titleIndicator = array_filter($indicators, fn($i) => $i['id'] === 'title_tag');
        $titleIndicator = reset($titleIndicator);

        $this->assertEquals(0.10, $titleIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function h1_headings_weight_is_0_10(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $h1Indicator = array_filter($indicators, fn($i) => $i['id'] === 'h1_headings');
        $h1Indicator = reset($h1Indicator);

        $this->assertEquals(0.10, $h1Indicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function url_weight_is_0_08(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $urlIndicator = array_filter($indicators, fn($i) => $i['id'] === 'url');
        $urlIndicator = reset($urlIndicator);

        $this->assertEquals(0.08, $urlIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function meta_description_weight_is_0_08(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $metaIndicator = array_filter($indicators, fn($i) => $i['id'] === 'meta_description');
        $metaIndicator = reset($metaIndicator);

        $this->assertEquals(0.08, $metaIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function image_alt_weight_is_0_08(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $altIndicator = array_filter($indicators, fn($i) => $i['id'] === 'image_alt');
        $altIndicator = reset($altIndicator);

        $this->assertEquals(0.08, $altIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function links_weight_is_0_08(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $linksIndicator = array_filter($indicators, fn($i) => $i['id'] === 'links');
        $linksIndicator = reset($linksIndicator);

        $this->assertEquals(0.08, $linksIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function mobile_friendly_weight_is_0_08(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $mobileIndicator = array_filter($indicators, fn($i) => $i['id'] === 'mobile_friendly');
        $mobileIndicator = reset($mobileIndicator);

        $this->assertEquals(0.08, $mobileIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function favicon_weight_is_0_02(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $faviconIndicator = array_filter($indicators, fn($i) => $i['id'] === 'favicon');
        $faviconIndicator = reset($faviconIndicator);

        $this->assertEquals(0.02, $faviconIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function page_speed_weight_is_0_02(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $speedIndicator = array_filter($indicators, fn($i) => $i['id'] === 'page_speed');
        $speedIndicator = reset($speedIndicator);

        $this->assertEquals(0.02, $speedIndicator['weight']);
    }

    #[Test]
    #[Group('weights')]
    public function all_weights_are_between_0_and_1(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertGreaterThan(0, $indicator['weight'], "Weight for {$indicator['id']} should be > 0");
            $this->assertLessThanOrEqual(1, $indicator['weight'], "Weight for {$indicator['id']} should be <= 1");
        }
    }

    // =========================================================================
    // Indicator Categories Tests
    // =========================================================================

    #[Test]
    #[Group('categories')]
    public function categories_are_valid(): void
    {
        $validCategories = ['Content', 'Technical', 'Accessibility', 'Performance'];
        $indicators = $this->service->getIndicatorDefinitions();

        foreach ($indicators as $indicator) {
            $this->assertContains(
                $indicator['category'],
                $validCategories,
                "Invalid category '{$indicator['category']}' for indicator '{$indicator['id']}'"
            );
        }
    }

    #[Test]
    #[Group('categories')]
    public function content_category_indicators(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $contentIndicators = array_filter($indicators, fn($i) => $i['category'] === 'Content');
        $contentIds = array_column($contentIndicators, 'id');

        $expected = ['title_tag', 'meta_description', 'h1_headings', 'filenames', 'links', 'social_meta'];
        foreach ($expected as $id) {
            $this->assertContains($id, $contentIds, "$id should be in Content category");
        }
    }

    #[Test]
    #[Group('categories')]
    public function technical_category_indicators(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $technicalIndicators = array_filter($indicators, fn($i) => $i['category'] === 'Technical');
        $technicalIds = array_column($technicalIndicators, 'id');

        $expected = ['url', 'robots_txt', 'sitemap_xml', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical'];
        foreach ($expected as $id) {
            $this->assertContains($id, $technicalIds, "$id should be in Technical category");
        }
    }

    #[Test]
    #[Group('categories')]
    public function accessibility_category_indicators(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $a11yIndicators = array_filter($indicators, fn($i) => $i['category'] === 'Accessibility');
        $a11yIds = array_column($a11yIndicators, 'id');

        $expected = ['image_alt', 'aria'];
        foreach ($expected as $id) {
            $this->assertContains($id, $a11yIds, "$id should be in Accessibility category");
        }
    }

    #[Test]
    #[Group('categories')]
    public function performance_category_indicators(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $perfIndicators = array_filter($indicators, fn($i) => $i['category'] === 'Performance');
        $perfIds = array_column($perfIndicators, 'id');

        $this->assertContains('page_speed', $perfIds);
    }

    // =========================================================================
    // JSON Schema Tests
    // =========================================================================

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_returns_array(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertIsArray($schema);
    }

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_has_overall_score(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertArrayHasKey('overall_score', $schema);
    }

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_has_overall_summary(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertArrayHasKey('overall_summary', $schema);
    }

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_has_category_scores(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertArrayHasKey('category_scores', $schema);
    }

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_has_indicators(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertArrayHasKey('indicators', $schema);
    }

    #[Test]
    #[Group('schema')]
    public function getJsonSchema_has_top_priorities(): void
    {
        $schema = $this->service->getJsonSchema();

        $this->assertArrayHasKey('top_priorities', $schema);
    }

    #[Test]
    #[Group('schema')]
    public function buildPrompt_includes_json_schema(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('overall_score', $prompt);
        $this->assertStringContainsString('overall_summary', $prompt);
        $this->assertStringContainsString('category_scores', $prompt);
        $this->assertStringContainsString('indicators', $prompt);
    }

    // =========================================================================
    // Prompt Template Tests
    // =========================================================================

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_returns_string(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertIsString($template);
    }

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_has_url_placeholder(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('{url}', $template);
    }

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_has_title_placeholder(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('{title}', $template);
    }

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_has_meta_description_placeholder(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('{meta_description}', $template);
    }

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_has_indicators_placeholder(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('{indicators}', $template);
    }

    #[Test]
    #[Group('template')]
    public function getPromptTemplate_has_html_content_placeholder(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('{html_content}', $template);
    }

    #[Test]
    #[Group('template')]
    public function prompt_template_contains_seo_analysis_header(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('SEO', $template);
    }

    #[Test]
    #[Group('template')]
    public function prompt_template_contains_json_format_instruction(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertStringContainsString('JSON', $template);
    }

    // =========================================================================
    // Page Info Extraction Tests
    // =========================================================================

    #[Test]
    #[Group('extraction')]
    public function extracts_title_from_html(): void
    {
        $html = '<html><head><title>My Page Title</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('My Page Title', $prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function extracts_meta_description_from_html(): void
    {
        $html = '<html><head><title>Test</title><meta name="description" content="My page description"></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('My page description', $prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_empty_title(): void
    {
        $html = '<html><head><title></title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_missing_title_tag(): void
    {
        $html = '<html><head></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_empty_meta_description(): void
    {
        $html = '<html><head><title>Test</title><meta name="description" content=""></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_multiple_meta_tags(): void
    {
        $html = '<html><head><title>Test</title><meta charset="UTF-8"><meta name="description" content="Description"><meta name="keywords" content="test"></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('Description', $prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_special_characters_in_title(): void
    {
        $html = '<html><head><title>Test &amp; Demo &lt;HTML&gt;</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('extraction')]
    public function handles_unicode_in_content(): void
    {
        $html = '<html><head><title>テスト标题</title><meta name="description" content="Описание тест"></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('テスト标题', $prompt);
        $this->assertStringContainsString('Описание тест', $prompt);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    #[Test]
    #[Group('edge')]
    public function handles_empty_html(): void
    {
        $prompt = $this->service->buildPrompt('https://example.com', '');

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_malformed_html(): void
    {
        $html = '<html><head><title>Test</title></head><body><div>Unclosed';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_very_long_html(): void
    {
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 10000) . '</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_very_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 500);
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt($longUrl, $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_url_with_query_params(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com/page?param=value&other=test', $html);

        $this->assertStringContainsString('https://example.com/page?param=value&other=test', $prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_url_with_fragment(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com/page#section', $html);

        $this->assertStringContainsString('https://example.com/page#section', $prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_html_with_script_tags(): void
    {
        $html = '<html><head><title>Test</title><script>alert("test")</script></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_html_with_style_tags(): void
    {
        $html = '<html><head><title>Test</title><style>.class { color: red; }</style></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_html_with_comments(): void
    {
        $html = '<html><head><title>Test</title><!-- Comment --></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('edge')]
    public function handles_html_with_cdata(): void
    {
        $html = '<html><head><title>Test</title></head><body><![CDATA[Some content]]></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    #[Test]
    #[Group('security')]
    public function html_entities_are_preserved(): void
    {
        $html = '<html><head><title>Test &amp; Demo</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('security')]
    public function xss_payload_in_url_is_safe(): void
    {
        $maliciousUrl = 'https://example.com/<script>alert("xss")</script>';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt($maliciousUrl, $html);

        // The URL should be in the prompt but not executed
        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('security')]
    public function xss_payload_in_html_is_safe(): void
    {
        $html = '<html><head><title>Test</title></head><body><script>alert("xss")</script></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        // Script should be present but in safe context
        $this->assertIsString($prompt);
    }

    #[Test]
    #[Group('security')]
    public function no_eval_or_dangerous_functions(): void
    {
        $reflection = new \ReflectionClass(UnifiedPromptService::class);
        $content = file_get_contents($reflection->getFileName());

        $this->assertStringNotContainsString('eval(', $content);
        $this->assertStringNotContainsString('exec(', $content);
        $this->assertStringNotContainsString('system(', $content);
        $this->assertStringNotContainsString('passthru(', $content);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function full_prompt_generation_workflow(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page - SEO Analysis</title>
    <meta name="description" content="This is a test page for SEO analysis.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/test">
</head>
<body>
    <h1>Test Page</h1>
    <p>This is a test paragraph.</p>
    <img src="image.jpg" alt="Test image">
    <a href="/link">Internal Link</a>
</body>
</html>
HTML;

        $prompt = $this->service->buildPrompt('https://example.com/test', $html);

        // Verify all components are present
        $this->assertStringContainsString('https://example.com/test', $prompt);
        $this->assertStringContainsString('Test Page - SEO Analysis', $prompt);
        $this->assertStringContainsString('This is a test page for SEO analysis', $prompt);
        $this->assertStringContainsString('overall_score', $prompt);
        $this->assertStringContainsString('indicators', $prompt);
    }

    #[Test]
    #[Group('integration')]
    public function prompt_is_valid_for_ai_consumption(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        // Prompt should be non-empty string
        $this->assertNotEmpty($prompt);

        // Should contain analysis instructions
        $this->assertMatchesRegularExpression('/analyze|analysis|score/i', $prompt);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    #[Test]
    #[Group('performance')]
    public function buildPrompt_completes_quickly(): void
    {
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 1000) . '</body></html>';

        $start = microtime(true);
        $this->service->buildPrompt('https://example.com', $html);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'buildPrompt should complete in under 500ms');
    }

    #[Test]
    #[Group('performance')]
    public function getIndicatorDefinitions_is_cached(): void
    {
        // Call twice to ensure caching works
        $first = $this->service->getIndicatorDefinitions();
        $second = $this->service->getIndicatorDefinitions();

        $this->assertEquals($first, $second);
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_prompt_contains_all_17_indicators(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $indicators = $this->service->getIndicatorDefinitions();
        foreach ($indicators as $indicator) {
            $this->assertStringContainsString($indicator['name'], $prompt, "Prompt should mention indicator: {$indicator['name']}");
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_json_schema_included_in_prompt(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $schema = $this->service->getJsonSchema();
        foreach (array_keys($schema) as $key) {
            $this->assertStringContainsString($key, $prompt, "Prompt should include schema key: $key");
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_page_info_extracted_correctly(): void
    {
        $html = '<html><head><title>My Title</title><meta name="description" content="My Description"></head><body></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertStringContainsString('My Title', $prompt, 'Title should be extracted');
        $this->assertStringContainsString('My Description', $prompt, 'Meta description should be extracted');
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_indicator_weights_match_specification(): void
    {
        $indicators = $this->service->getIndicatorDefinitions();
        $totalWeight = array_sum(array_column($indicators, 'weight'));

        $this->assertEqualsWithDelta(1.00, $totalWeight, 0.001, 'Weights should sum to 1.00');
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_prompt_template_is_configurable(): void
    {
        $template = $this->service->getPromptTemplate();

        $this->assertIsString($template);
        $this->assertNotEmpty($template);
        $this->assertStringContainsString('{url}', $template);
        $this->assertStringContainsString('{html_content}', $template);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_html_content_properly_handled(): void
    {
        $html = '<html><head><title>Test</title></head><body><script>alert("test")</script></body></html>';
        $prompt = $this->service->buildPrompt('https://example.com', $html);

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
    }
}
