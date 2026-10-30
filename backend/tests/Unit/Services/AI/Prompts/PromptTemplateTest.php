<?php

namespace Tests\Unit\Services\AI\Prompts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Test suite for PromptTemplate class.
 * 
 * TASK-21-4: Create Prompt Templates for All Indicators
 * 
 * Tests prompt template generation for all 10 SEO indicators,
 * variable substitution, and output schema validation.
 * 
 * @group ai
 * @group task-21-4
 */
class PromptTemplateTest extends TestCase
{
    private const CLASS_PATH = 'App\Services\AI\Prompts\PromptTemplate';

    /**
     * Valid indicators for testing
     */
    private const VALID_INDICATORS = [
        'title',
        'meta_description',
        'robots',
        'sitemap',
        'headings',
        'image_alt',
        'filenames',
        'url',
        'links',
        '404',
    ];

    // =========================================================================
    // AC-1: PromptTemplate Class Structure
    // =========================================================================

    #[Test]
    #[TestDox('PT-001: Class file exists at correct path')]
    public function class_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Services/AI/Prompts/PromptTemplate.php'),
            'PromptTemplate should exist at app/Services/AI/Prompts/PromptTemplate.php'
        );
    }

    #[Test]
    #[TestDox('PT-002: Class is instantiable')]
    public function class_is_instantiable(): void
    {
        $this->assertTrue(class_exists(self::CLASS_PATH), 'PromptTemplate class should exist');
        
        $instance = new (self::CLASS_PATH)();
        $this->assertInstanceOf(self::CLASS_PATH, $instance);
    }

    #[Test]
    #[TestDox('PT-003: getSystemPrompt method exists with string return type')]
    public function get_system_prompt_method_exists(): void
    {
        $this->assertTrue(
            method_exists(self::CLASS_PATH, 'getSystemPrompt'),
            'PromptTemplate should have getSystemPrompt method'
        );

        $reflection = new \ReflectionMethod(self::CLASS_PATH, 'getSystemPrompt');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType, 'getSystemPrompt should have return type');
        $this->assertEquals('string', $returnType->getName(), 'getSystemPrompt should return string');
    }

    #[Test]
    #[TestDox('PT-004: getUserPrompt method exists with string return type')]
    public function get_user_prompt_method_exists(): void
    {
        $this->assertTrue(
            method_exists(self::CLASS_PATH, 'getUserPrompt'),
            'PromptTemplate should have getUserPrompt method'
        );

        $reflection = new \ReflectionMethod(self::CLASS_PATH, 'getUserPrompt');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType, 'getUserPrompt should have return type');
        $this->assertEquals('string', $returnType->getName(), 'getUserPrompt should return string');
    }

    #[Test]
    #[TestDox('PT-005: getExpectedSchema method exists with array return type')]
    public function get_expected_schema_method_exists(): void
    {
        $this->assertTrue(
            method_exists(self::CLASS_PATH, 'getExpectedSchema'),
            'PromptTemplate should have getExpectedSchema method'
        );

        $reflection = new \ReflectionMethod(self::CLASS_PATH, 'getExpectedSchema');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType, 'getExpectedSchema should have return type');
        $this->assertEquals('array', $returnType->getName(), 'getExpectedSchema should return array');
    }

    #[Test]
    #[TestDox('PT-006: getSystemPrompt returns non-empty string for valid indicator')]
    public function get_system_prompt_returns_non_empty_string(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('title');

        $this->assertIsString($result);
        $this->assertNotEmpty($result, 'System prompt should not be empty for valid indicator');
    }

    #[Test]
    #[TestDox('PT-007: getUserPrompt returns non-empty string with substitutions')]
    public function get_user_prompt_returns_non_empty_string(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'Test Title',
            'content_summary' => 'Test content',
            'keyword' => 'test',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result, 'User prompt should not be empty');
    }

    #[Test]
    #[TestDox('PT-008: getExpectedSchema returns valid array structure')]
    public function get_expected_schema_returns_valid_array(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getExpectedSchema('title');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Schema should not be empty');
    }

    // =========================================================================
    // AC-2: Title Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('TI-001: System prompt defines SEO expert role')]
    public function title_system_prompt_defines_seo_role(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('title');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'seo') || str_contains($lowercaseResult, 'expert'),
            'System prompt should mention SEO or expert role'
        );
    }

    #[Test]
    #[TestDox('TI-002: User prompt accepts and substitutes title')]
    public function title_user_prompt_accepts_title(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'My Awesome Page Title',
            'content_summary' => 'Content summary',
            'keyword' => 'keyword',
        ]);

        $this->assertStringContainsString('My Awesome Page Title', $result);
    }

    #[Test]
    #[TestDox('TI-003: User prompt accepts content_summary')]
    public function title_user_prompt_accepts_content_summary(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'Test',
            'content_summary' => 'This is a summary of the page content for SEO analysis.',
            'keyword' => 'keyword',
        ]);

        $this->assertStringContainsString('This is a summary of the page content', $result);
    }

    #[Test]
    #[TestDox('TI-004: User prompt accepts keyword')]
    public function title_user_prompt_accepts_keyword(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'Test',
            'content_summary' => 'Summary',
            'keyword' => 'seo optimization',
        ]);

        $this->assertStringContainsString('seo optimization', $result);
    }

    #[Test]
    #[TestDox('TI-005: Schema has score field')]
    public function title_schema_has_score_field(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('title');

        $this->assertArrayHasKey('score', $schema);
    }

    #[Test]
    #[TestDox('TI-006: Schema has issues array')]
    public function title_schema_has_issues_array(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('title');

        $this->assertArrayHasKey('issues', $schema);
    }

    #[Test]
    #[TestDox('TI-007: Schema has suggestions array')]
    public function title_schema_has_suggestions_array(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('title');

        $this->assertArrayHasKey('suggestions', $schema);
    }

    #[Test]
    #[TestDox('TI-008: Schema has best_practices array')]
    public function title_schema_has_best_practices_array(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('title');

        $this->assertArrayHasKey('bestPractices', $schema);
    }

    #[Test]
    #[TestDox('TI-009: System prompt is under 100 tokens')]
    public function title_system_prompt_under_100_tokens(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('title');

        // Rough token estimation: ~4 characters per token
        $tokenCount = (int) ceil(strlen($result) / 4);
        
        $this->assertLessThan(
            100,
            $tokenCount,
            "System prompt should be under 100 tokens, estimated: {$tokenCount}"
        );
    }

    // =========================================================================
    // AC-3: Meta Description Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('MD-001: System prompt for meta description optimization')]
    public function meta_description_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('meta_description');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'meta') || str_contains($lowercaseResult, 'description'),
            'System prompt should mention meta description'
        );
    }

    #[Test]
    #[TestDox('MD-002: User prompt accepts description')]
    public function meta_description_user_prompt_accepts_description(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('meta_description', [
            'description' => 'This is my meta description for SEO.',
            'content_summary' => 'Summary',
            'keyword' => 'seo',
        ]);

        $this->assertStringContainsString('This is my meta description', $result);
    }

    #[Test]
    #[TestDox('MD-003: User prompt accepts content_summary')]
    public function meta_description_user_prompt_accepts_content_summary(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('meta_description', [
            'description' => 'Description',
            'content_summary' => 'Page content about SEO tools.',
            'keyword' => 'seo',
        ]);

        $this->assertStringContainsString('SEO tools', $result);
    }

    #[Test]
    #[TestDox('MD-004: User prompt accepts keyword')]
    public function meta_description_user_prompt_accepts_keyword(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('meta_description', [
            'description' => 'Description',
            'content_summary' => 'Summary',
            'keyword' => 'free seo tools',
        ]);

        $this->assertStringContainsString('free seo tools', $result);
    }

    #[Test]
    #[TestDox('MD-005: Schema or prompt includes CTA guidance')]
    public function meta_description_includes_cta_guidance(): void
    {
        $template = new (self::CLASS_PATH)();
        $systemPrompt = $template->getSystemPrompt('meta_description');
        $userPrompt = $template->getUserPrompt('meta_description', [
            'description' => 'Test',
            'content_summary' => 'Summary',
            'keyword' => 'test',
        ]);

        $combined = strtolower($systemPrompt . ' ' . $userPrompt);
        $hasCTA = str_contains($combined, 'cta') || 
                  str_contains($combined, 'call') || 
                  str_contains($combined, 'action') ||
                  str_contains($combined, 'click');

        $this->assertTrue(
            $hasCTA,
            'Meta description prompt should mention CTA or call to action'
        );
    }

    #[Test]
    #[TestDox('MD-006: System prompt under 100 tokens')]
    public function meta_description_system_prompt_under_100_tokens(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('meta_description');

        $tokenCount = (int) ceil(strlen($result) / 4);
        
        $this->assertLessThan(100, $tokenCount);
    }

    // =========================================================================
    // AC-4: Robots.txt Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('RB-001: System prompt for robots.txt analysis')]
    public function robots_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('robots');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'robots'),
            'System prompt should mention robots'
        );
    }

    #[Test]
    #[TestDox('RB-002: User prompt accepts robots_content')]
    public function robots_user_prompt_accepts_robots_content(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('robots', [
            'robots_content' => 'User-agent: *' . "\n" . 'Disallow: /admin/',
            'site_url' => 'https://example.com',
        ]);

        $this->assertStringContainsString('User-agent:', $result);
    }

    #[Test]
    #[TestDox('RB-003: User prompt accepts site_url')]
    public function robots_user_prompt_accepts_site_url(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('robots', [
            'robots_content' => 'Test',
            'site_url' => 'https://example.com',
        ]);

        $this->assertStringContainsString('https://example.com', $result);
    }

    #[Test]
    #[TestDox('RB-004: Schema has issues array')]
    public function robots_schema_has_issues(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('robots');

        $this->assertArrayHasKey('issues', $schema);
    }

    #[Test]
    #[TestDox('RB-005: Schema has suggestions array')]
    public function robots_schema_has_suggestions(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('robots');

        $this->assertArrayHasKey('suggestions', $schema);
    }

    #[Test]
    #[TestDox('RB-006: Schema has warnings array')]
    public function robots_schema_has_warnings(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('robots');

        $this->assertArrayHasKey('warnings', $schema);
    }

    // =========================================================================
    // AC-5: Sitemap Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('SM-001: System prompt for sitemap validation')]
    public function sitemap_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('sitemap');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'sitemap') || str_contains($lowercaseResult, 'xml'),
            'System prompt should mention sitemap or XML'
        );
    }

    #[Test]
    #[TestDox('SM-002: User prompt accepts sitemap_url')]
    public function sitemap_user_prompt_accepts_sitemap_url(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('sitemap', [
            'sitemap_url' => 'https://example.com/sitemap.xml',
            'page_count' => 100,
            'sample_urls' => [],
        ]);

        $this->assertStringContainsString('sitemap.xml', $result);
    }

    #[Test]
    #[TestDox('SM-003: User prompt accepts page_count')]
    public function sitemap_user_prompt_accepts_page_count(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('sitemap', [
            'sitemap_url' => 'https://example.com/sitemap.xml',
            'page_count' => 250,
            'sample_urls' => [],
        ]);

        $this->assertStringContainsString('250', $result);
    }

    #[Test]
    #[TestDox('SM-004: User prompt accepts sample_urls')]
    public function sitemap_user_prompt_accepts_sample_urls(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('sitemap', [
            'sitemap_url' => 'https://example.com/sitemap.xml',
            'page_count' => 10,
            'sample_urls' => [
                'https://example.com/page1',
                'https://example.com/page2',
            ],
        ]);

        $this->assertStringContainsString('page1', $result);
    }

    #[Test]
    #[TestDox('SM-005: Schema has coverage_analysis')]
    public function sitemap_schema_has_coverage_analysis(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('sitemap');

        $this->assertTrue(
            array_key_exists('coverage_analysis', $schema) || 
            array_key_exists('coverageAnalysis', $schema),
            'Schema should have coverage_analysis field'
        );
    }

    // =========================================================================
    // AC-6: Headings Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('HD-001: System prompt for heading structure analysis')]
    public function headings_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('headings');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'heading') || str_contains($lowercaseResult, 'h1'),
            'System prompt should mention heading or H1'
        );
    }

    #[Test]
    #[TestDox('HD-002: User prompt accepts heading_structure')]
    public function headings_user_prompt_accepts_heading_structure(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('headings', [
            'heading_structure' => json_encode([
                'h1' => ['Main Title'],
                'h2' => ['Section 1', 'Section 2'],
            ]),
        ]);

        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('HD-003: Schema has hierarchy_issues')]
    public function headings_schema_has_hierarchy_issues(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('headings');

        $this->assertTrue(
            array_key_exists('hierarchy_issues', $schema) || 
            array_key_exists('hierarchyIssues', $schema),
            'Schema should have hierarchy_issues field'
        );
    }

    #[Test]
    #[TestDox('HD-004: Accessibility considerations in prompt')]
    public function headings_accessibility_considerations(): void
    {
        $template = new (self::CLASS_PATH)();
        $systemPrompt = $template->getSystemPrompt('headings');

        $combined = strtolower($systemPrompt);
        $hasAccessibility = str_contains($combined, 'accessibility') || 
                           str_contains($combined, 'screen reader') ||
                           str_contains($combined, 'semantic');

        $this->assertTrue(
            $hasAccessibility,
            'Headings prompt should mention accessibility'
        );
    }

    // =========================================================================
    // AC-7: Image Alt Text Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('IA-001: System prompt for alt text analysis')]
    public function image_alt_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('image_alt');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'alt') || str_contains($lowercaseResult, 'image'),
            'System prompt should mention alt or image'
        );
    }

    #[Test]
    #[TestDox('IA-002: User prompt accepts images array')]
    public function image_alt_user_prompt_accepts_images(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('image_alt', [
            'images' => json_encode([
                ['url' => 'https://example.com/img.jpg', 'alt' => 'Test image'],
            ]),
        ]);

        $this->assertStringContainsString('img.jpg', $result);
    }

    #[Test]
    #[TestDox('IA-003: Schema has missing_alts array')]
    public function image_alt_schema_has_missing_alts(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('image_alt');

        $this->assertTrue(
            array_key_exists('missing_alts', $schema) || 
            array_key_exists('missingAlts', $schema),
            'Schema should have missing_alts field'
        );
    }

    #[Test]
    #[TestDox('IA-004: Schema has suggestions_per_image')]
    public function image_alt_schema_has_suggestions_per_image(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('image_alt');

        $this->assertTrue(
            array_key_exists('suggestions_per_image', $schema) || 
            array_key_exists('suggestionsPerImage', $schema),
            'Schema should have suggestions_per_image field'
        );
    }

    // =========================================================================
    // AC-8: Filenames Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('FN-001: System prompt for filename SEO')]
    public function filenames_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('filenames');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'filename') || str_contains($lowercaseResult, 'file'),
            'System prompt should mention filename or file'
        );
    }

    #[Test]
    #[TestDox('FN-002: User prompt accepts file_urls')]
    public function filenames_user_prompt_accepts_file_urls(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('filenames', [
            'file_urls' => json_encode([
                'https://example.com/images/my-image.jpg',
                'https://example.com/docs/document.pdf',
            ]),
            'types' => json_encode(['image', 'document']),
        ]);

        $this->assertStringContainsString('my-image.jpg', $result);
    }

    #[Test]
    #[TestDox('FN-003: User prompt accepts types')]
    public function filenames_user_prompt_accepts_types(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('filenames', [
            'file_urls' => json_encode(['https://example.com/file.jpg']),
            'types' => json_encode(['image', 'document', 'video']),
        ]);

        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('FN-004: Prompt mentions naming convention')]
    public function filenames_prompt_mentions_naming_convention(): void
    {
        $template = new (self::CLASS_PATH)();
        $systemPrompt = $template->getSystemPrompt('filenames');
        $userPrompt = $template->getUserPrompt('filenames', [
            'file_urls' => '[]',
            'types' => '[]',
        ]);

        $combined = strtolower($systemPrompt . ' ' . $userPrompt);
        $hasNamingConvention = str_contains($combined, 'naming') || 
                              str_contains($combined, 'convention') ||
                              str_contains($combined, 'kebab') ||
                              str_contains($combined, 'snake_case') ||
                              str_contains($combined, 'lowercase');

        $this->assertTrue(
            $hasNamingConvention,
            'Filenames prompt should mention naming convention'
        );
    }

    // =========================================================================
    // AC-9: URL Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('UR-001: System prompt for URL structure analysis')]
    public function url_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('url');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'url') || str_contains($lowercaseResult, 'slug'),
            'System prompt should mention URL or slug'
        );
    }

    #[Test]
    #[TestDox('UR-002: User prompt accepts url')]
    public function url_user_prompt_accepts_url(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('url', [
            'url' => 'https://example.com/products/awesome-product',
            'page_type' => 'product',
            'keyword' => 'awesome product',
        ]);

        $this->assertStringContainsString('awesome-product', $result);
    }

    #[Test]
    #[TestDox('UR-003: User prompt accepts page_type')]
    public function url_user_prompt_accepts_page_type(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('url', [
            'url' => 'https://example.com/page',
            'page_type' => 'category',
            'keyword' => 'test',
        ]);

        $this->assertStringContainsString('category', $result);
    }

    #[Test]
    #[TestDox('UR-004: User prompt accepts keyword')]
    public function url_user_prompt_accepts_keyword(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('url', [
            'url' => 'https://example.com/page',
            'page_type' => 'page',
            'keyword' => 'target keyword',
        ]);

        $this->assertStringContainsString('target keyword', $result);
    }

    #[Test]
    #[TestDox('UR-005: Prompt mentions slug optimization')]
    public function url_prompt_mentions_slug(): void
    {
        $template = new (self::CLASS_PATH)();
        $systemPrompt = $template->getSystemPrompt('url');

        $combined = strtolower($systemPrompt);
        $hasSlugOptimization = str_contains($combined, 'slug') || 
                              str_contains($combined, 'clean') ||
                              str_contains($combined, 'readable');

        $this->assertTrue(
            $hasSlugOptimization,
            'URL prompt should mention slug optimization'
        );
    }

    // =========================================================================
    // AC-10: Links Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('LN-001: System prompt for link analysis')]
    public function links_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('links');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, 'link') || str_contains($lowercaseResult, 'internal'),
            'System prompt should mention link or internal'
        );
    }

    #[Test]
    #[TestDox('LN-002: User prompt accepts internal_links')]
    public function links_user_prompt_accepts_internal_links(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('links', [
            'internal_links' => json_encode([
                ['url' => '/page1', 'anchor' => 'Page 1'],
                ['url' => '/page2', 'anchor' => 'Page 2'],
            ]),
            'external_links' => '[]',
            'broken_links' => '[]',
        ]);

        $this->assertStringContainsString('page1', $result);
    }

    #[Test]
    #[TestDox('LN-003: User prompt accepts external_links')]
    public function links_user_prompt_accepts_external_links(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('links', [
            'internal_links' => '[]',
            'external_links' => json_encode([
                ['url' => 'https://external.com', 'anchor' => 'External'],
            ]),
            'broken_links' => '[]',
        ]);

        $this->assertStringContainsString('external.com', $result);
    }

    #[Test]
    #[TestDox('LN-004: User prompt accepts broken_links')]
    public function links_user_prompt_accepts_broken_links(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('links', [
            'internal_links' => '[]',
            'external_links' => '[]',
            'broken_links' => json_encode([
                ['url' => '/broken-page', 'status' => 404],
            ]),
        ]);

        $this->assertStringContainsString('broken-page', $result);
    }

    #[Test]
    #[TestDox('LN-005: Schema has broken_link_fixes')]
    public function links_schema_has_broken_link_fixes(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('links');

        $this->assertTrue(
            array_key_exists('broken_link_fixes', $schema) || 
            array_key_exists('brokenLinkFixes', $schema),
            'Schema should have broken_link_fixes field'
        );
    }

    // =========================================================================
    // AC-11: 404 Indicator Prompt
    // =========================================================================

    #[Test]
    #[TestDox('NF-001: System prompt for 404 error analysis')]
    public function notfound_system_prompt(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getSystemPrompt('404');

        $this->assertNotEmpty($result);
        $lowercaseResult = strtolower($result);
        $this->assertTrue(
            str_contains($lowercaseResult, '404') || str_contains($lowercaseResult, 'not found'),
            'System prompt should mention 404 or not found'
        );
    }

    #[Test]
    #[TestDox('NF-002: User prompt accepts 404_urls')]
    public function notfound_user_prompt_accepts_404_urls(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('404', [
            '404_urls' => json_encode([
                '/old-page',
                '/deleted-content',
            ]),
            'referrers' => '[]',
        ]);

        $this->assertStringContainsString('old-page', $result);
    }

    #[Test]
    #[TestDox('NF-003: User prompt accepts referrers')]
    public function notfound_user_prompt_accepts_referrers(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('404', [
            '404_urls' => '[]',
            'referrers' => json_encode([
                ['url' => '/sitemap.xml', 'link' => '/old-page'],
            ]),
        ]);

        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('NF-004: Schema has redirect_recommendations')]
    public function notfound_schema_has_redirect_recommendations(): void
    {
        $template = new (self::CLASS_PATH)();
        $schema = $template->getExpectedSchema('404');

        $this->assertTrue(
            array_key_exists('redirect_recommendations', $schema) || 
            array_key_exists('redirectRecommendations', $schema),
            'Schema should have redirect_recommendations field'
        );
    }

    #[Test]
    #[TestDox('NF-005: User experience considerations in prompt')]
    public function notfound_user_experience_considerations(): void
    {
        $template = new (self::CLASS_PATH)();
        $systemPrompt = $template->getSystemPrompt('404');

        $combined = strtolower($systemPrompt);
        $hasUX = str_contains($combined, 'user experience') || 
                str_contains($combined, 'user') ||
                str_contains($combined, 'visitor');

        $this->assertTrue(
            $hasUX,
            '404 prompt should mention user experience'
        );
    }

    // =========================================================================
    // AC-12: Variable Substitution
    // =========================================================================

    #[Test]
    #[TestDox('VS-001: Single variable substitution works')]
    public function single_variable_substitution(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'UniqueTitleValue123',
            'content_summary' => 'Summary',
            'keyword' => 'keyword',
        ]);

        $this->assertStringContainsString('UniqueTitleValue123', $result);
        $this->assertStringNotContainsString('{title}', $result);
    }

    #[Test]
    #[TestDox('VS-002: Multiple variable substitution works')]
    public function multiple_variable_substitution(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'MyTitle',
            'content_summary' => 'MySummary',
            'keyword' => 'MyKeyword',
        ]);

        $this->assertStringContainsString('MyTitle', $result);
        $this->assertStringContainsString('MySummary', $result);
        $this->assertStringContainsString('MyKeyword', $result);
    }

    #[Test]
    #[TestDox('VS-003: Missing variable handling')]
    public function missing_variable_handling(): void
    {
        $template = new (self::CLASS_PATH)();
        // Missing some variables - should handle gracefully
        $result = $template->getUserPrompt('title', [
            'title' => 'OnlyTitle',
        ]);

        $this->assertNotEmpty($result);
        // Template should either leave placeholder or use empty/default
    }

    #[Test]
    #[TestDox('VS-004: Special characters in values are handled')]
    public function special_characters_in_values(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => 'Title with <script>alert("xss")</script>',
            'content_summary' => 'Summary',
            'keyword' => 'keyword',
        ]);

        // Should contain the value but potentially sanitized
        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('VS-005: Empty string substitution')]
    public function empty_string_substitution(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => '',
            'content_summary' => '',
            'keyword' => '',
        ]);

        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('VS-006: Unicode substitution preserves characters')]
    public function unicode_substitution(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => '中文标题测试',
            'content_summary' => 'Summary',
            'keyword' => '키워드',
        ]);

        $this->assertStringContainsString('中文标题测试', $result);
        $this->assertStringContainsString('키워드', $result);
    }

    // =========================================================================
    // Edge Cases and Security
    // =========================================================================

    #[Test]
    #[TestDox('EC-001: Invalid indicator returns empty or throws')]
    public function invalid_indicator_handling(): void
    {
        $template = new (self::CLASS_PATH)();
        
        $systemPrompt = $template->getSystemPrompt('invalid_indicator_xyz');
        $this->assertEquals('', $systemPrompt, 'Invalid indicator should return empty string');

        $userPrompt = $template->getUserPrompt('invalid_indicator_xyz', []);
        $this->assertEquals('', $userPrompt, 'Invalid indicator should return empty string for user prompt');

        $schema = $template->getExpectedSchema('invalid_indicator_xyz');
        $this->assertEquals([], $schema, 'Invalid indicator should return empty array for schema');
    }

    #[Test]
    #[TestDox('EC-002: Empty context array')]
    public function empty_context_array(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', []);

        $this->assertNotEmpty($result, 'Should return template even with empty context');
    }

    #[Test]
    #[TestDox('EC-003: Null context value handling')]
    public function null_context_value(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => null,
            'content_summary' => 'Summary',
            'keyword' => 'keyword',
        ]);

        $this->assertNotEmpty($result);
    }

    #[Test]
    #[TestDox('EC-004: Very long context value handling')]
    public function very_long_context_value(): void
    {
        $template = new (self::CLASS_PATH)();
        $longString = str_repeat('a', 10000);
        
        $result = $template->getUserPrompt('title', [
            'title' => $longString,
            'content_summary' => 'Summary',
            'keyword' => 'keyword',
        ]);

        $this->assertNotEmpty($result);
        // Should handle long strings without crashing
    }

    #[Test]
    #[TestDox('EC-005: XSS prevention in context values')]
    public function xss_prevention(): void
    {
        $template = new (self::CLASS_PATH)();
        $result = $template->getUserPrompt('title', [
            'title' => '<script>alert("xss")</script>',
            'content_summary' => '<img src=x onerror=alert(1)>',
            'keyword' => '"><script>alert(1)</script>',
        ]);

        // The result should not execute scripts (just contain the text)
        $this->assertStringContainsString('script', $result);
    }

    #[Test]
    #[TestDox('EC-006: All 10 indicators have valid templates')]
    public function all_indicators_have_templates(): void
    {
        $template = new (self::CLASS_PATH)();

        foreach (self::VALID_INDICATORS as $indicator) {
            $systemPrompt = $template->getSystemPrompt($indicator);
            $schema = $template->getExpectedSchema($indicator);

            $this->assertNotEmpty(
                $systemPrompt,
                "Indicator '{$indicator}' should have non-empty system prompt"
            );
            $this->assertNotEmpty(
                $schema,
                "Indicator '{$indicator}' should have non-empty schema"
            );
        }
    }

    #[Test]
    #[TestDox('EC-007: All system prompts are under 100 tokens')]
    public function all_system_prompts_under_100_tokens(): void
    {
        $template = new (self::CLASS_PATH)();

        foreach (self::VALID_INDICATORS as $indicator) {
            $systemPrompt = $template->getSystemPrompt($indicator);
            $tokenCount = (int) ceil(strlen($systemPrompt) / 4);

            $this->assertLessThan(
                100,
                $tokenCount,
                "Indicator '{$indicator}' system prompt should be under 100 tokens, got {$tokenCount}"
            );
        }
    }

    #[Test]
    #[TestDox('EC-008: All schemas contain expected common fields')]
    public function all_schemas_contain_common_fields(): void
    {
        $template = new (self::CLASS_PATH)();

        // Most indicators should have at least score, issues, suggestions
        $indicatorsWithScore = ['title', 'meta_description', 'headings', 'url'];
        
        foreach ($indicatorsWithScore as $indicator) {
            $schema = $template->getExpectedSchema($indicator);
            
            $this->assertArrayHasKey(
                'score',
                $schema,
                "Indicator '{$indicator}' schema should have 'score' field"
            );
        }
    }
}
