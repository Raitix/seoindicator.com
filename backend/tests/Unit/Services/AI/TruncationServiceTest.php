<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\ContextWindowManager;
use App\Services\AI\DTOs\TruncationResult;
use App\Services\AI\TruncationService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for TruncationService.
 * 
 * Tests progressive truncation strategies, result metadata,
 * and integration with ContextWindowManager for SPEC-031.
 * 
 * @see TASK-31-3
 * @see SPEC-031
 */
class TruncationServiceTest extends TestCase
{
    private TruncationService $service;
    private ContextWindowManager|Mockery\MockInterface $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ContextWindowManager dependency
        $this->tokenManager = Mockery::mock(ContextWindowManager::class);
        $this->service = new TruncationService($this->tokenManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    #[Test]
    #[Group('instantiation')]
    public function service_can_be_instantiated(): void
    {
        $service = new TruncationService($this->tokenManager);
        
        $this->assertInstanceOf(TruncationService::class, $service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_requires_context_window_manager(): void
    {
        $reflection = new \ReflectionClass(TruncationService::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertGreaterThanOrEqual(1, count($params));
        $this->assertEquals('tokenManager', $params[0]->getName());
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_truncate_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'truncate'));
        
        $reflection = new \ReflectionMethod(TruncationService::class, 'truncate');
        $params = $reflection->getParameters();
        
        $this->assertCount(2, $params);
        $this->assertEquals('html', $params[0]->getName());
        $this->assertEquals('targetTokens', $params[1]->getName());
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_available_strategies_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAvailableStrategies'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_estimate_reduction_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'estimateReduction'));
    }

    // =========================================================================
    // Strategy 1: Remove HTML Comments
    // =========================================================================

    #[Test]
    #[Group('strategy-comments')]
    public function truncate_removes_html_comments(): void
    {
        $html = '<!-- This is a comment --><p>Content</p><!-- Another comment -->';
        
        // Setup mock: 100 tokens initially, 10 tokens after
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<!--', $result->html);
        $this->assertStringNotContainsString('-->', $result->html);
        $this->assertStringContainsString('<p>Content</p>', $result->html);
    }

    #[Test]
    #[Group('strategy-comments')]
    public function truncate_removes_multiline_comments(): void
    {
        $html = "<!--\n  Multiline\n  Comment\n--><p>Content</p>";
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<!--', $result->html);
    }

    #[Test]
    #[Group('strategy-comments')]
    public function truncate_removes_conditional_comments(): void
    {
        $html = '<!--[if IE]><script>alert("IE")</script><![endif]--><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<!--[if', $result->html);
    }

    #[Test]
    #[Group('strategy-comments')]
    public function truncate_preserves_content_after_comment_removal(): void
    {
        $html = '<!-- Comment --><h1>Title</h1><p>Paragraph</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('<h1>Title</h1>', $result->html);
        $this->assertStringContainsString('<p>Paragraph</p>', $result->html);
    }

    // =========================================================================
    // Strategy 2: Collapse Whitespace
    // =========================================================================

    #[Test]
    #[Group('strategy-whitespace')]
    public function truncate_collapses_multiple_spaces(): void
    {
        $html = '<p>Multiple   spaces    here</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(3)
            ->andReturn(100, 100, 10); // Still over after comments, then under after whitespace
        
        $result = $this->service->truncate($html, 50);
        
        // After collapsing whitespace, should have single spaces
        $this->assertStringNotContainsString('   ', $result->html);
        $this->assertStringNotContainsString('    ', $result->html);
    }

    #[Test]
    #[Group('strategy-whitespace')]
    public function truncate_collapses_newlines(): void
    {
        $html = "<p>Line1\n\n\n\nLine2</p>";
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(3)
            ->andReturn(100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        // Multiple newlines should be collapsed
        $this->assertStringNotContainsString("\n\n\n", $result->html);
    }

    #[Test]
    #[Group('strategy-whitespace')]
    public function truncate_collapses_tabs(): void
    {
        $html = "<p>Tab\t\t\tseparated</p>";
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(3)
            ->andReturn(100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString("\t\t", $result->html);
    }

    #[Test]
    #[Group('strategy-whitespace')]
    public function truncate_normalizes_mixed_whitespace(): void
    {
        $html = "<p>Mixed  \t\n  whitespace</p>";
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(3)
            ->andReturn(100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        // Should have normalized to single spaces
        $this->assertStringContainsString('Mixed whitespace', $result->html);
    }

    // =========================================================================
    // Strategy 3: Remove Script Tags
    // =========================================================================

    #[Test]
    #[Group('strategy-scripts')]
    public function truncate_removes_script_tags(): void
    {
        $html = '<script>alert("test")</script><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10); // Comments, whitespace, scripts removed
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<script', $result->html);
        $this->assertStringNotContainsString('</script>', $result->html);
    }

    #[Test]
    #[Group('strategy-scripts')]
    public function truncate_removes_script_with_attributes(): void
    {
        $html = '<script type="text/javascript" src="app.js"></script><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<script', $result->html);
    }

    #[Test]
    #[Group('strategy-scripts')]
    public function truncate_removes_multiple_scripts(): void
    {
        $html = '<script>var a=1;</script><script>var b=2;</script><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<script', $result->html);
        $this->assertStringContainsString('<p>Content</p>', $result->html);
    }

    #[Test]
    #[Group('strategy-scripts')]
    public function truncate_removes_inline_event_handlers(): void
    {
        $html = '<button onclick="alert(1)">Click</button><p onmouseover="evil()">Text</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('onclick', $result->html);
        $this->assertStringNotContainsString('onmouseover', $result->html);
    }

    // =========================================================================
    // Strategy 4: Remove Style Tags
    // =========================================================================

    #[Test]
    #[Group('strategy-styles')]
    public function truncate_removes_style_tags(): void
    {
        $html = '<style>.class { color: red; }</style><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<style', $result->html);
        $this->assertStringNotContainsString('</style>', $result->html);
    }

    #[Test]
    #[Group('strategy-styles')]
    public function truncate_removes_style_with_attributes(): void
    {
        $html = '<style type="text/css">body { margin: 0; }</style><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<style', $result->html);
    }

    #[Test]
    #[Group('strategy-styles')]
    public function truncate_removes_multiple_style_blocks(): void
    {
        $html = '<style>.a{color:red;}</style><style>.b{color:blue;}</style><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<style', $result->html);
    }

    #[Test]
    #[Group('strategy-styles')]
    public function truncate_removes_inline_styles(): void
    {
        $html = '<p style="color: red; font-size: 20px;">Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('style="', $result->html);
        $this->assertStringContainsString('<p>', $result->html);
        $this->assertStringContainsString('Content', $result->html);
    }

    // =========================================================================
    // Strategy 5: Remove Base64 Images
    // =========================================================================

    #[Test]
    #[Group('strategy-base64')]
    public function truncate_removes_base64_images(): void
    {
        $html = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(6)
            ->andReturn(100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('data:image', $result->html);
        $this->assertStringNotContainsString('base64,', $result->html);
    }

    #[Test]
    #[Group('strategy-base64')]
    public function truncate_preserves_image_alt_text(): void
    {
        $html = '<img src="data:image/png;base64,iVBORw" alt="Description"><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(6)
            ->andReturn(100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('alt="Description"', $result->html);
    }

    #[Test]
    #[Group('strategy-base64')]
    public function truncate_removes_base64_in_different_formats(): void
    {
        $formats = [
            'data:image/png;base64,abc',
            'data:image/jpeg;base64,def',
            'data:image/gif;base64,ghi',
            'data:image/webp;base64,jkl',
        ];
        
        foreach ($formats as $format) {
            $html = "<img src=\"{$format}\"><p>Content</p>";
            
            $this->tokenManager = Mockery::mock(ContextWindowManager::class);
            $this->service = new TruncationService($this->tokenManager);
            
            $this->tokenManager->shouldReceive('estimateTokens')
                ->times(6)
                ->andReturn(100, 100, 100, 100, 100, 10);
            
            $result = $this->service->truncate($html, 50);
            
            $this->assertStringNotContainsString('data:image', $result->html);
        }
    }

    #[Test]
    #[Group('strategy-base64')]
    public function truncate_preserves_normal_image_src(): void
    {
        $html = '<img src="https://example.com/image.png"><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(6)
            ->andReturn(100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('src="https://example.com/image.png"', $result->html);
    }

    // =========================================================================
    // Strategy 6: Summarize Content (Truncate Text)
    // =========================================================================

    #[Test]
    #[Group('strategy-summarize')]
    public function truncate_extracts_key_semantic_elements(): void
    {
        $html = '<div><h1>Title</h1><h2>Subtitle</h2><p>Paragraph</p><a href="#">Link</a></div>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(7)
            ->andReturn(100, 100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        // Should preserve headings and links
        $this->assertStringContainsString('<h1>', $result->html);
    }

    #[Test]
    #[Group('strategy-summarize')]
    public function truncate_preserves_meta_tags(): void
    {
        $html = '<head><meta name="description" content="Test"><title>Page Title</title></head><body><p>Content</p></body>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(7)
            ->andReturn(100, 100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('<meta', $result->html);
        $this->assertStringContainsString('<title>', $result->html);
    }

    // =========================================================================
    // Strategy 7: Keep Structure Only
    // =========================================================================

    #[Test]
    #[Group('strategy-structure')]
    public function truncate_keeps_semantic_structure(): void
    {
        $html = '<div class="wrapper"><nav><ul><li><a href="#">Link</a></li></ul></nav><main><article><h1>Title</h1><p>Content</p></article></main></div>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(8)
            ->andReturn(100, 100, 100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        // Should preserve headings and links at minimum
        $this->assertStringContainsString('<h1>', $result->html);
    }

    // =========================================================================
    // Progressive Truncation Tests
    // =========================================================================

    #[Test]
    #[Group('progressive')]
    public function truncate_applies_strategies_in_priority_order(): void
    {
        $html = '<!-- Comment --><script>code</script><style>css</style><p>Content</p>';
        
        // Mock token estimation showing progressive reduction
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(
                100,  // Initial
                90,   // After comments (still over)
                80,   // After whitespace (still over)
                50,   // After scripts (at target)
                50    // Final check
            );
        
        $result = $this->service->truncate($html, 50);
        
        // Should have applied strategies in order
        $this->assertContains('removeComments', $result->strategiesApplied);
        $this->assertContains('collapseWhitespace', $result->strategiesApplied);
        $this->assertContains('removeScripts', $result->strategiesApplied);
    }

    #[Test]
    #[Group('progressive')]
    public function truncate_stops_when_target_reached(): void
    {
        $html = '<!-- Comment --><p>Small content</p>';
        
        // Initial: 100 tokens, after comments: 10 tokens (under target)
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        // Should only apply first strategy (remove comments)
        $this->assertCount(1, $result->strategiesApplied);
        $this->assertEquals('removeComments', $result->strategiesApplied[0]);
    }

    #[Test]
    #[Group('progressive')]
    public function truncate_applies_no_strategies_when_already_small(): void
    {
        $html = '<p>Small content</p>';
        
        // Already under target
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertEmpty($result->strategiesApplied);
        $this->assertEquals($html, $result->html);
    }

    #[Test]
    #[Group('progressive')]
    public function truncate_applies_all_strategies_if_needed(): void
    {
        $html = '<!-- Comment --><script>code</script><style>css</style><img src="data:image/png;base64,abc"><p>Content</p>';
        
        // Mock: always over target until last strategy
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(8)
            ->andReturn(100, 95, 90, 85, 80, 75, 70, 65);
        
        $result = $this->service->truncate($html, 50);
        
        // Should have applied multiple strategies
        $this->assertGreaterThan(3, count($result->strategiesApplied));
    }

    // =========================================================================
    // TruncationResult DTO Tests
    // =========================================================================

    #[Test]
    #[Group('dto')]
    public function truncation_result_contains_html(): void
    {
        $html = '<p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertObjectHasProperty('html', $result);
        $this->assertIsString($result->html);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_contains_original_tokens(): void
    {
        $html = '<p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(100);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertObjectHasProperty('originalTokens', $result);
        $this->assertEquals(100, $result->originalTokens);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_contains_final_tokens(): void
    {
        $html = '<p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertObjectHasProperty('finalTokens', $result);
        $this->assertEquals(10, $result->finalTokens);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_contains_strategies_applied(): void
    {
        $html = '<!-- Comment --><p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertObjectHasProperty('strategiesApplied', $result);
        $this->assertIsArray($result->strategiesApplied);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_contains_reduction_percent(): void
    {
        $html = '<!-- Comment --><p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 50);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertObjectHasProperty('reductionPercent', $result);
        $this->assertIsFloat($result->reductionPercent);
        $this->assertEquals(50.0, $result->reductionPercent);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_calculates_zero_reduction_correctly(): void
    {
        $html = '<p>Test</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(50);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertEquals(0.0, $result->reductionPercent);
    }

    #[Test]
    #[Group('dto')]
    public function truncation_result_calculates_100_reduction_correctly(): void
    {
        $html = '<script>code</script>';
        
        // All content removed
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 0);
        
        $result = $this->service->truncate($html, 0);
        
        $this->assertEquals(100.0, $result->reductionPercent);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_empty_html(): void
    {
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(0);
        
        $result = $this->service->truncate('', 50);
        
        $this->assertEquals('', $result->html);
        $this->assertEquals(0, $result->originalTokens);
        $this->assertEquals(0, $result->finalTokens);
        $this->assertEmpty($result->strategiesApplied);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_whitespace_only_html(): void
    {
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(1);
        
        $result = $this->service->truncate('   ', 50);
        
        $this->assertEmpty($result->strategiesApplied);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_zero_target_tokens(): void
    {
        $html = '<p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(8)
            ->andReturn(100, 95, 90, 85, 80, 75, 70, 65);
        
        $result = $this->service->truncate($html, 0);
        
        // Should try to reduce as much as possible
        $this->assertNotEmpty($result->strategiesApplied);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_malformed_html_gracefully(): void
    {
        $html = '<p>Unclosed paragraph<div>Nested unclosed';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        // Should not throw exception
        $result = $this->service->truncate($html, 50);
        
        $this->assertIsString($result->html);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_unicode_content(): void
    {
        $html = '<p>日本語テスト 中文 العربية 🎉</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('日本語', $result->html);
        $this->assertStringContainsString('中文', $result->html);
        $this->assertStringContainsString('🎉', $result->html);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_preserves_utf8_encoding(): void
    {
        $html = '<p>Ölümsöz ⟨-brackets⟩ «guillemets»</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        // UTF-8 characters should be preserved
        $this->assertStringContainsString('Ölümsöz', $result->html);
        $this->assertStringContainsString('«guillemets»', $result->html);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_very_large_html(): void
    {
        $html = str_repeat('<p>Paragraph content here.</p>', 10000);
        
        // Simulate truncation to fit target
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(8)
            ->andReturn(100000, 95000, 90000, 85000, 80000, 75000, 70000, 1000);
        
        $result = $this->service->truncate($html, 1000);
        
        // Should complete without memory issues
        $this->assertLessThanOrEqual(1000, $result->finalTokens);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_does_not_modify_original_html(): void
    {
        $originalHtml = '<!-- Comment --><p>Content</p>';
        $htmlCopy = $originalHtml;
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $result = $this->service->truncate($originalHtml, 50);
        
        // Original should be unchanged
        $this->assertEquals($htmlCopy, $originalHtml);
        // Result should be different (comments removed)
        $this->assertNotEquals($originalHtml, $result->html);
    }

    #[Test]
    #[Group('edge-cases')]
    public function truncate_handles_html_with_only_comments(): void
    {
        $html = '<!-- Just a comment -->';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 0);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertEquals('', trim($result->html));
        $this->assertContains('removeComments', $result->strategiesApplied);
    }

    // =========================================================================
    // ContextWindowManager Integration Tests
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function truncate_uses_context_window_manager_for_estimation(): void
    {
        $html = '<p>Test content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->with($html)
            ->andReturn(10);
        
        $this->service->truncate($html, 50);
    }

    #[Test]
    #[Group('integration')]
    public function truncate_re_estimates_after_each_strategy(): void
    {
        $html = '<!-- Comment --><p>Content</p>';
        
        // Should estimate twice: initial and after strategy
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 10);
        
        $this->service->truncate($html, 50);
    }

    #[Test]
    #[Group('integration')]
    public function truncate_works_with_real_context_window_manager(): void
    {
        // Integration test with real ContextWindowManager
        $realManager = new ContextWindowManager();
        $service = new TruncationService($realManager);
        
        $html = '<!-- Comment --><p>Content</p>';
        
        $result = $service->truncate($html, 1000);
        
        $this->assertInstanceOf(TruncationResult::class, $result);
        $this->assertLessThanOrEqual(1000, $result->finalTokens);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    #[Test]
    #[Group('security')]
    public function truncate_removes_xss_vectors(): void
    {
        $html = '<script>alert("xss")</script><img src=x onerror=alert(1)>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<script', $result->html);
        $this->assertStringNotContainsString('onerror', $result->html);
    }

    #[Test]
    #[Group('security')]
    public function truncate_removes_javascript_urls(): void
    {
        $html = '<a href="javascript:alert(1)">Click</a>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('javascript:', $result->html);
    }

    #[Test]
    #[Group('security')]
    public function truncate_removes_data_url_scripts(): void
    {
        $html = '<a href="data:text/html,<script>alert(1)</script>">Link</a>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(6)
            ->andReturn(100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('data:text/html', $result->html);
    }

    #[Test]
    #[Group('security')]
    public function truncate_removes_svg_onload(): void
    {
        $html = '<svg onload=alert(1)><circle></circle></svg>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('onload', $result->html);
    }

    #[Test]
    #[Group('security')]
    public function truncate_removes_iframe_injection(): void
    {
        $html = '<iframe src="evil.com"></iframe><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(7)
            ->andReturn(100, 100, 100, 100, 100, 100, 10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringNotContainsString('<iframe', $result->html);
    }

    // =========================================================================
    // SEO Preservation Tests
    // =========================================================================

    #[Test]
    #[Group('seo')]
    public function truncate_preserves_seo_relevant_elements(): void
    {
        $html = '<h1>Title</h1><h2>Subtitle</h2><p>Content</p><meta name="description" content="test">';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        // Should preserve headings and meta
        $this->assertStringContainsString('<h1>', $result->html);
        $this->assertStringContainsString('<meta', $result->html);
    }

    #[Test]
    #[Group('seo')]
    public function truncate_preserves_title_tag(): void
    {
        $html = '<title>Page Title | Brand</title><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('<title>', $result->html);
    }

    #[Test]
    #[Group('seo')]
    public function truncate_preserves_canonical_link(): void
    {
        $html = '<link rel="canonical" href="https://example.com/page"><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('rel="canonical"', $result->html);
    }

    #[Test]
    #[Group('seo')]
    public function truncate_preserves_open_graph_tags(): void
    {
        $html = '<meta property="og:title" content="Title"><meta property="og:description" content="Desc"><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('og:title', $result->html);
        $this->assertStringContainsString('og:description', $result->html);
    }

    #[Test]
    #[Group('seo')]
    public function truncate_preserves_anchor_hrefs(): void
    {
        $html = '<a href="/page">Link</a><a href="https://external.com">External</a>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertStringContainsString('href="/page"', $result->html);
        $this->assertStringContainsString('href="https://external.com"', $result->html);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    #[Test]
    #[Group('performance')]
    public function truncate_is_performant_for_typical_content(): void
    {
        // Typical webpage: ~50KB HTML
        $html = str_repeat('<p>Paragraph content here with some text.</p>', 500);
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(8)
            ->andReturn(10000, 9500, 9000, 8500, 8000, 7500, 7000, 1000);
        
        $start = microtime(true);
        $result = $this->service->truncate($html, 1000);
        $end = microtime(true);
        
        $durationMs = ($end - $start) * 1000;
        $this->assertLessThan(100, $durationMs, 'Truncation should complete in < 100ms');
    }

    #[Test]
    #[Group('performance')]
    public function truncate_memory_usage_is_bounded(): void
    {
        $html = str_repeat('<p>Content</p>', 1000);
        $initialMemory = memory_get_usage();
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, 50);
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (< 2x original content size)
        $this->assertLessThan(strlen($html) * 2, $memoryIncrease);
    }

    // =========================================================================
    // Helper Method Tests
    // =========================================================================

    #[Test]
    #[Group('helpers')]
    public function get_available_strategies_returns_ordered_list(): void
    {
        $strategies = $this->service->getAvailableStrategies();
        
        $this->assertIsArray($strategies);
        $this->assertCount(7, $strategies);
        
        // Verify order
        $this->assertEquals('removeComments', $strategies[0]);
        $this->assertEquals('collapseWhitespace', $strategies[1]);
        $this->assertEquals('removeScripts', $strategies[2]);
        $this->assertEquals('removeStyles', $strategies[3]);
        $this->assertEquals('removeBase64Images', $strategies[4]);
        $this->assertEquals('summarizeContent', $strategies[5]);
        $this->assertEquals('keepStructureOnly', $strategies[6]);
    }

    #[Test]
    #[Group('helpers')]
    public function estimate_reduction_returns_expected_percentages(): void
    {
        // Test each strategy's expected reduction range
        $strategies = $this->service->getAvailableStrategies();
        
        foreach ($strategies as $strategy) {
            $reduction = $this->service->estimateReduction($strategy);
            
            $this->assertIsInt($reduction);
            $this->assertGreaterThanOrEqual(0, $reduction);
            $this->assertLessThanOrEqual(100, $reduction);
        }
    }

    #[Test]
    #[Group('helpers')]
    public function estimate_reduction_for_unknown_strategy_returns_zero(): void
    {
        $reduction = $this->service->estimateRedduction('unknownStrategy');
        
        $this->assertEquals(0, $reduction);
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_truncate_reduces_content_to_fit_target_tokens(): void
    {
        // AC: truncate() reduces content to fit target tokens
        
        $html = '<!-- Comment --><script>var x = 1;</script><style>.a{color:red;}</style><p>Content</p>';
        
        // Simulate reduction: 100 → 90 → 80 → 60 → 40 (under target)
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(5)
            ->andReturn(100, 90, 80, 60, 40);
        
        $result = $this->service->truncate($html, 50);
        
        $this->assertLessThanOrEqual(50, $result->finalTokens);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_strategies_applied_in_priority_order(): void
    {
        // AC: Strategies applied in priority order (non-destructive first)
        
        $html = '<!-- Comment --><script>code</script><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->times(4)
            ->andReturn(100, 90, 80, 40);
        
        $result = $this->service->truncate($html, 50);
        
        // First strategy should be removeComments (non-destructive)
        $this->assertEquals('removeComments', $result->strategiesApplied[0]);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_non_destructive_strategies_tried_first(): void
    {
        // AC: Non-destructive strategies tried first
        
        $strategies = $this->service->getAvailableStrategies();
        
        // First two should be non-destructive
        $this->assertEquals('removeComments', $strategies[0], 'Comments removal is non-destructive');
        $this->assertEquals('collapseWhitespace', $strategies[1], 'Whitespace collapse is non-destructive');
        
        // Later strategies are destructive
        $this->assertNotEquals('removeScripts', $strategies[0]);
        $this->assertNotEquals('keepStructureOnly', $strategies[0]);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_truncation_result_contains_all_metadata(): void
    {
        // AC: TruncationResult contains all metadata
        
        $html = '<!-- Comment --><p>Content</p>';
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 50);
        
        $result = $this->service->truncate($html, 50);
        
        // Verify all required properties
        $this->assertObjectHasProperty('html', $result);
        $this->assertObjectHasProperty('originalTokens', $result);
        $this->assertObjectHasProperty('finalTokens', $result);
        $this->assertObjectHasProperty('strategiesApplied', $result);
        $this->assertObjectHasProperty('reductionPercent', $result);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_original_html_never_modified(): void
    {
        // AC: Original HTML never modified (returns new string)
        
        $originalHtml = '<!-- Comment --><p>Content</p>';
        $htmlCopy = $originalHtml;
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->twice()
            ->andReturn(100, 50);
        
        $result = $this->service->truncate($originalHtml, 50);
        
        // Original string should be unchanged
        $this->assertEquals($htmlCopy, $originalHtml);
        
        // Result should be a new string
        $this->assertNotSame($originalHtml, $result->html);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_handles_edge_cases(): void
    {
        // AC: Handles edge cases (empty HTML, already small, etc.)
        
        // Empty HTML
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(0);
        $result = $this->service->truncate('', 50);
        $this->assertEquals('', $result->html);
        
        // Already small
        Mockery::close();
        $this->tokenManager = Mockery::mock(ContextWindowManager::class);
        $this->service = new TruncationService($this->tokenManager);
        
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        $result = $this->service->truncate('<p>Small</p>', 50);
        $this->assertEmpty($result->strategiesApplied);
    }

    // =========================================================================
    // Data Providers
    // =========================================================================

    public static function htmlSizeProvider(): array
    {
        return [
            'tiny' => ['<p>X</p>', 10],
            'small' => ['<p>Content</p>', 50],
            'medium' => [str_repeat('<p>Content</p>', 10), 100],
            'large' => [str_repeat('<p>Content</p>', 100), 1000],
        ];
    }

    #[Test]
    #[DataProvider('htmlSizeProvider')]
    #[Group('data-driven')]
    public function it_handles_various_html_sizes(string $html, int $target): void
    {
        $this->tokenManager->shouldReceive('estimateTokens')
            ->once()
            ->andReturn(10);
        
        $result = $this->service->truncate($html, $target);
        
        $this->assertInstanceOf(TruncationResult::class, $result);
    }

    public static function strategyProvider(): array
    {
        return [
            'removeComments' => ['removeComments', '<!-- Comment -->', 5, 15],
            'collapseWhitespace' => ['collapseWhitespace', '<p>Multiple   spaces</p>', 3, 8],
            'removeScripts' => ['removeScripts', '<script>code</script>', 10, 40],
            'removeStyles' => ['removeStyles', '<style>.a{}</style>', 5, 20],
            'removeBase64Images' => ['removeBase64Images', '<img src="data:image/png;base64,abc">', 10, 50],
            'summarizeContent' => ['summarizeContent', '<p>Long text</p>', 50, 70],
            'keepStructureOnly' => ['keepStructureOnly', '<div><p>Content</p></div>', 70, 90],
        ];
    }

    #[Test]
    #[DataProvider('strategyProvider')]
    #[Group('data-driven')]
    public function strategy_reduction_estimates_are_reasonable(string $strategy, string $html, int $minReduction, int $maxReduction): void
    {
        $reduction = $this->service->estimateReduction($strategy);
        
        $this->assertGreaterThanOrEqual($minReduction, $reduction);
        $this->assertLessThanOrEqual($maxReduction, $reduction);
    }
}
