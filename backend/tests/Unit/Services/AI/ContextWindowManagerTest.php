<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\ContextWindowManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for ContextWindowManager service.
 * 
 * Tests token estimation, context window validation, and model selection
 * for the Unified AI Prompt + Context Management feature (SPEC-031).
 * 
 * @see TASK-31-2
 */
class ContextWindowManagerTest extends TestCase
{
    private ContextWindowManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ContextWindowManager();
    }

    // =========================================================================
    // Model Limits Constants
    // =========================================================================

    #[Test]
    #[Group('constants')]
    public function it_has_correct_model_limits_defined(): void
    {
        $limits = $this->manager->getModelLimits();

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('baidu/ernie-5.5-flash-preview:free', $limits);
        $this->assertArrayHasKey('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $limits);
        $this->assertArrayHasKey('liquid/lfm-3-1.7b:free', $limits);
        $this->assertArrayHasKey('liquid/lfm-2.5-2.6b:free', $limits);

        $this->assertEquals(1024000, $limits['baidu/ernie-5.5-flash-preview:free']);
        $this->assertEquals(256000, $limits['nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free']);
        $this->assertEquals(262144, $limits['liquid/lfm-3-1.7b:free']);
        $this->assertEquals(65536, $limits['liquid/lfm-2.5-2.6b:free']);
    }

    #[Test]
    #[Group('constants')]
    public function it_returns_all_four_free_tier_models(): void
    {
        $limits = $this->manager->getModelLimits();

        $this->assertCount(4, $limits, 'Should have exactly 4 free-tier models');
    }

    #[Test]
    #[Group('constants')]
    public function it_has_safety_margin_defined(): void
    {
        $reflection = new \ReflectionClass(ContextWindowManager::class);
        $constant = $reflection->getConstant('SAFETY_MARGIN');

        $this->assertEquals(0.10, $constant, 'Safety margin should be 10%');
    }

    #[Test]
    #[Group('constants')]
    public function it_has_base_prompt_overhead_defined(): void
    {
        $reflection = new \ReflectionClass(ContextWindowManager::class);
        $constant = $reflection->getConstant('BASE_PROMPT_OVERHEAD');

        $this->assertEquals(3400, $constant, 'Base prompt overhead should be 3400 tokens');
    }

    // =========================================================================
    // Token Estimation
    // =========================================================================

    #[Test]
    #[Group('estimation')]
    public function it_estimates_tokens_from_content(): void
    {
        // 1 token ≈ 4 characters
        $content = str_repeat('a', 400); // 100 tokens
        $tokens = $this->manager->estimateTokens($content);

        $this->assertEquals(100, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_tokens_with_ceiling(): void
    {
        // 401 characters = 100.25 tokens → ceil to 101
        $content = str_repeat('a', 401);
        $tokens = $this->manager->estimateTokens($content);

        $this->assertEquals(101, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_zero_tokens_for_empty_content(): void
    {
        $tokens = $this->manager->estimateTokens('');

        $this->assertEquals(0, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_one_token_for_single_character(): void
    {
        $tokens = $this->manager->estimateTokens('a');

        $this->assertEquals(1, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_tokens_for_multibyte_characters(): void
    {
        // UTF-8 multibyte characters
        $content = '你好世界'; // 4 Chinese characters = 12 bytes
        $tokens = $this->manager->estimateTokens($content);

        // strlen() counts bytes in PHP, so 12 bytes / 4 = 3 tokens
        $this->assertEquals(3, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_tokens_for_emoji(): void
    {
        $content = '😀👍🎉'; // Multiple 4-byte emoji
        $tokens = $this->manager->estimateTokens($content);

        // Each emoji is 4 bytes in UTF-8 = 12 bytes total / 4 = 3 tokens
        $this->assertGreaterThanOrEqual(3, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function it_estimates_tokens_for_large_content(): void
    {
        // 1 million characters = 250,000 tokens
        $content = str_repeat('a', 1000000);
        $tokens = $this->manager->estimateTokens($content);

        $this->assertEquals(250000, $tokens);
    }

    #[Test]
    #[Group('estimation')]
    public function token_estimation_accuracy_within_20_percent(): void
    {
        // Real-world test: average English text
        $content = 'The quick brown fox jumps over the lazy dog. This is a sample sentence for testing.';
        $estimated = $this->manager->estimateTokens($content);
        
        // Using tiktoken or similar, this would be ~22 tokens
        // Our estimation: 77 chars / 4 = ~20 tokens
        // Within 20% of 22 would be 17.6 to 26.4
        $this->assertGreaterThanOrEqual(17, $estimated);
        $this->assertLessThanOrEqual(27, $estimated);
    }

    // =========================================================================
    // Effective Limit Calculation
    // =========================================================================

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_largest_model(): void
    {
        // 1,024,000 * 0.9 = 921,600 - 3400 = 918,200
        $effective = $this->manager->getEffectiveLimit('baidu/ernie-5.5-flash-preview:free');

        $this->assertEquals(918200, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_second_model(): void
    {
        // 256,000 * 0.9 = 230,400 - 3400 = 227,000
        $effective = $this->manager->getEffectiveLimit('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free');

        $this->assertEquals(227000, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_third_model(): void
    {
        // 256,000 * 0.9 = 230,400 - 3400 = 227,000
        $effective = $this->manager->getEffectiveLimit('liquid/lfm-3-1.7b:free');

        $this->assertEquals(232529, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_smallest_model(): void
    {
        // 1,000,000 * 0.9 = 900,000 - 3400 = 896,600
        $effective = $this->manager->getEffectiveLimit('liquid/lfm-2.5-2.6b:free');

        $this->assertEquals(55582, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_returns_default_limit_for_unknown_model(): void
    {
        // Unknown model should return default of 8192 * 0.9 - 3400 = 3972
        $effective = $this->manager->getEffectiveLimit('unknown/model');

        $this->assertEquals(3972, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function effective_limit_applies_safety_margin(): void
    {
        $limits = $this->manager->getModelLimits();

        foreach ($limits as $model => $limit) {
            $effective = $this->manager->getEffectiveLimit($model);
            
            // Effective should be less than raw limit
            $this->assertLessThan($limit, $effective);
            
            // Should have at least 10% margin + 3400 overhead
            $expectedMin = (int) ($limit * 0.9) - 3400;
            $this->assertEquals($expectedMin, $effective);
        }
    }

    // =========================================================================
    // Context Window Validation
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function it_validates_content_fits_in_largest_context(): void
    {
        // ERNIE 5.5 Flash has ~918,200 effective limit
        $fits = $this->manager->fitsInContext('baidu/ernie-5.5-flash-preview:free', 500000);

        $this->assertTrue($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_does_not_fit_in_largest_context(): void
    {
        // ERNIE 5.5 Flash has ~918,200 effective limit
        $fits = $this->manager->fitsInContext('baidu/ernie-5.5-flash-preview:free', 920000);

        $this->assertFalse($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_fits_in_smallest_context(): void
    {
        // LFM 2.5 2.6B has ~55,582 effective limit
        $fits = $this->manager->fitsInContext('liquid/lfm-2.5-2.6b:free', 30000);

        $this->assertTrue($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_does_not_fit_in_smallest_context(): void
    {
        // LFM 2.5 2.6B has ~55,582 effective limit
        $fits = $this->manager->fitsInContext('liquid/lfm-2.5-2.6b:free', 60000);

        $this->assertFalse($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_at_exact_limit_boundary(): void
    {
        // LFM 2.5 2.6B has ~55,582 effective limit
        $fits = $this->manager->fitsInContext('liquid/lfm-2.5-2.6b:free', 55582);

        $this->assertTrue($fits, 'Content exactly at limit should fit');
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_one_over_limit_boundary(): void
    {
        // LFM 2.5 2.6B has ~55,582 effective limit
        $fits = $this->manager->fitsInContext('liquid/lfm-2.5-2.6b:free', 55583);

        $this->assertFalse($fits, 'Content one token over limit should not fit');
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_zero_tokens_fits_any_model(): void
    {
        $models = [
            'baidu/ernie-5.5-flash-preview:free',
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'liquid/lfm-3-1.7b:free',
            'liquid/lfm-2.5-2.6b:free',
        ];

        foreach ($models as $model) {
            $fits = $this->manager->fitsInContext($model, 0);
            $this->assertTrue($fits, "Zero tokens should fit in {$model}");
        }
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_unknown_model_uses_default(): void
    {
        // Unknown model uses default 8192 limit → ~3,972 effective
        $fits = $this->manager->fitsInContext('unknown/model', 3000);

        $this->assertTrue($fits);
    }

    // =========================================================================
    // Model Selection
    // =========================================================================

    #[Test]
    #[Group('selection')]
    public function it_selects_smallest_model_for_small_content(): void
    {
        // Small content fits in all models, should prefer the smallest (LFM 2.5)
        $model = $this->manager->selectOptimalModel(3000);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_second_model_for_medium_content(): void
    {
        // Content that doesn't fit in LFM 2.5 but fits in Nemotron Nano Omni
        // LFM 2.5 limit: ~55,582, Nemotron Nano Omni limit: ~227,000
        $model = $this->manager->selectOptimalModel(100000);

        // Should prefer first available (Nemotron Nano Omni comes before LFM 3 in the list)
        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_largest_model_for_large_content(): void
    {
        // Content that only fits in ERNIE 5.5 Flash
        // LFM 3 limit: ~232,529, ERNIE limit: ~918,200
        $model = $this->manager->selectOptimalModel(500000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_falls_back_to_largest_for_content_exceeding_all_limits(): void
    {
        // Content that exceeds all limits should fallback to ERNIE (largest)
        $model = $this->manager->selectOptimalModel(1000000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_smallest_sufficient_model(): void
    {
        // Test the priority order: smallest sufficient model
        // LFM 2.5 (55,582) < Nemotron Nano Omni (227,000) < LFM 3 (232,529) < ERNIE (918,200)

        // Should select LFM 2.5
        $this->assertEquals(
            'liquid/lfm-2.5-2.6b:free',
            $this->manager->selectOptimalModel(3500)
        );

        // Should select Nemotron Nano Omni
        $this->assertEquals(
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            $this->manager->selectOptimalModel(100000)
        );

        // Should select LFM 3
        $this->assertEquals(
            'liquid/lfm-3-1.7b:free',
            $this->manager->selectOptimalModel(230000)
        );
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_correct_model_at_boundary(): void
    {
        // At exact LFM 2.5 limit (55,582) should still select LFM 2.5
        $model = $this->manager->selectOptimalModel(55582);
        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);

        // One over the LFM 2.5 limit should select Nemotron Nano Omni
        $model = $this->manager->selectOptimalModel(55583);
        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_correct_model_at_second_boundary(): void
    {
        // At exact Nemotron Nano Omni limit (227,000) should select it
        $model = $this->manager->selectOptimalModel(227000);
        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);

        // One over that limit should select LFM 3
        $model = $this->manager->selectOptimalModel(227001);
        $this->assertEquals('liquid/lfm-3-1.7b:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_largest_model_at_largest_boundary(): void
    {
        // At exact ERNIE limit (918,200) should select ERNIE
        $model = $this->manager->selectOptimalModel(918200);
        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_falls_back_to_largest_over_limit(): void
    {
        // Over the ERNIE limit should still return ERNIE (will need truncation)
        $model = $this->manager->selectOptimalModel(918201);
        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_zero_tokens(): void
    {
        $model = $this->manager->selectOptimalModel(0);

        // Should select smallest model (LFM 2.5) for zero tokens
        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_negative_tokens_gracefully(): void
    {
        // Negative tokens shouldn't happen in practice, but should handle gracefully
        $model = $this->manager->selectOptimalModel(-100);

        // Should select smallest model
        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_very_large_token_counts(): void
    {
        // 10 million tokens - way over any limit
        $model = $this->manager->selectOptimalModel(10000000);

        // Should fallback to ERNIE
        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_content_with_only_whitespace(): void
    {
        $tokens = $this->manager->estimateTokens('   ');

        // 3 spaces = 3 characters = 1 token (ceiling)
        $this->assertEquals(1, $tokens);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_content_with_newlines(): void
    {
        // 4 newlines = 4 characters = 1 token
        $tokens = $this->manager->estimateTokens("\n\n\n\n");

        $this->assertEquals(1, $tokens);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_mixed_content(): void
    {
        // Mix of ASCII, unicode, emoji
        $content = "Hello 世界 🌍\nNew line";
        $tokens = $this->manager->estimateTokens($content);

        // Should estimate based on byte count
        $this->assertIsInt($tokens);
        $this->assertGreaterThan(0, $tokens);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function full_workflow_estimate_validate_select(): void
    {
        // Simulate full workflow
        $content = str_repeat('test content ', 100); // ~1300 chars
        $estimatedTokens = $this->manager->estimateTokens($content);
        
        // Should fit in LFM 2.5
        $fits = $this->manager->fitsInContext('liquid/lfm-2.5-2.6b:free', $estimatedTokens);
        $this->assertTrue($fits);

        // Should select LFM 2.5 (smallest sufficient)
        $selectedModel = $this->manager->selectOptimalModel($estimatedTokens);
        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $selectedModel);
    }

    #[Test]
    #[Group('integration')]
    public function full_workflow_large_content(): void
    {
        // Large content
        $content = str_repeat('test content ', 100000); // ~1,300,000 chars
        $estimatedTokens = $this->manager->estimateTokens($content);

        // Should NOT fit in Nemotron Nano Omni
        $fitsQwen = $this->manager->fitsInContext('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $estimatedTokens);
        $this->assertFalse($fitsQwen);

        // Should fit in ERNIE
        $fitsGemini = $this->manager->fitsInContext('baidu/ernie-5.5-flash-preview:free', $estimatedTokens);
        $this->assertTrue($fitsGemini);

        // Should select ERNIE
        $selectedModel = $this->manager->selectOptimalModel($estimatedTokens);
        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $selectedModel);
    }

    #[Test]
    #[Group('integration')]
    public function all_models_can_be_validated(): void
    {
        $limits = $this->manager->getModelLimits();

        foreach ($limits as $model => $limit) {
            $effectiveLimit = $this->manager->getEffectiveLimit($model);
            $fits = $this->manager->fitsInContext($model, $effectiveLimit);
            
            $this->assertTrue($fits, "Model {$model} should fit at its effective limit");
        }
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_estimate_tokens_returns_reasonable_estimates(): void
    {
        // AC: estimateTokens() returns reasonable token estimates (±20% accuracy)
        
        // Test various content lengths
        $testCases = [
            ['content' => 'Short text', 'expected' => 3],
            ['content' => str_repeat('a', 100), 'expected' => 25],
            ['content' => str_repeat('a', 400), 'expected' => 100],
            ['content' => str_repeat('a', 1000), 'expected' => 250],
        ];

        foreach ($testCases as $case) {
            $estimated = $this->manager->estimateTokens($case['content']);
            $minExpected = $case['expected'] * 0.8;
            $maxExpected = $case['expected'] * 1.2;

            $this->assertGreaterThanOrEqual(
                $minExpected,
                $estimated,
                "Estimate {$estimated} is below 20% tolerance for expected {$case['expected']}"
            );
            $this->assertLessThanOrEqual(
                $maxExpected,
                $estimated,
                "Estimate {$estimated} is above 20% tolerance for expected {$case['expected']}"
            );
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_fits_in_context_validates_correctly(): void
    {
        // AC: fitsInContext() correctly validates against model limits
        
        // Test each model with known limits
        // Effective limits (raw context * 0.9 - 3400 prompt overhead)
        $models = [
            'baidu/ernie-5.5-flash-preview:free' => 918200,
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free' => 227000,
            'liquid/lfm-3-1.7b:free' => 232529,
            'liquid/lfm-2.5-2.6b:free' => 55582,
        ];

        foreach ($models as $model => $effectiveLimit) {
            // Should fit at limit
            $this->assertTrue(
                $this->manager->fitsInContext($model, $effectiveLimit),
                "{$model} should fit at effective limit {$effectiveLimit}"
            );

            // Should not fit over limit
            $this->assertFalse(
                $this->manager->fitsInContext($model, $effectiveLimit + 1),
                "{$model} should not fit over effective limit"
            );
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_select_optimal_model_returns_smallest_sufficient(): void
    {
        // AC: selectOptimalModel() returns smallest sufficient model
        
        // Small content -> LFM 2.5
        $this->assertEquals(
            'liquid/lfm-2.5-2.6b:free',
            $this->manager->selectOptimalModel(1000)
        );

        // Medium content -> Nemotron Nano Omni
        $this->assertEquals(
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            $this->manager->selectOptimalModel(100000)
        );

        // Large content -> ERNIE
        $this->assertEquals(
            'baidu/ernie-5.5-flash-preview:free',
            $this->manager->selectOptimalModel(500000)
        );
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_safety_margin_applied_to_all_limits(): void
    {
        // AC: Safety margin applied to all model limits
        
        $limits = $this->manager->getModelLimits();
        $safetyMargin = 0.10;
        $baseOverhead = 3400;

        foreach ($limits as $model => $rawLimit) {
            $effective = $this->manager->getEffectiveLimit($model);
            $expectedEffective = (int) ($rawLimit * (1 - $safetyMargin)) - $baseOverhead;

            $this->assertEquals(
                $expectedEffective,
                $effective,
                "{$model} effective limit should include safety margin"
            );
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_base_prompt_overhead_accounted_for(): void
    {
        // AC: Base prompt overhead accounted for
        
        // Verify that effective limits are lower than (limit * 0.9)
        $limits = $this->manager->getModelLimits();
        $baseOverhead = 3400;

        foreach ($limits as $model => $rawLimit) {
            $effective = $this->manager->getEffectiveLimit($model);
            $limitWithMargin = (int) ($rawLimit * 0.9);

            $this->assertEquals(
                $limitWithMargin - $baseOverhead,
                $effective,
                "{$model} should have base prompt overhead subtracted"
            );
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_all_four_free_tier_models_supported(): void
    {
        // AC: All 4 free-tier models supported
        
        $limits = $this->manager->getModelLimits();

        $this->assertCount(4, $limits, 'Should support exactly 4 free-tier models');
        
        $expectedModels = [
            'baidu/ernie-5.5-flash-preview:free',
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'liquid/lfm-3-1.7b:free',
            'liquid/lfm-2.5-2.6b:free',
        ];

        foreach ($expectedModels as $expectedModel) {
            $this->assertArrayHasKey(
                $expectedModel,
                $limits,
                "Model {$expectedModel} should be supported"
            );
        }
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    #[Test]
    #[Group('performance')]
    public function token_estimation_is_fast(): void
    {
        // Token estimation should be < 1ms for typical content
        $content = str_repeat('test content ', 1000);

        $start = microtime(true);
        $this->manager->estimateTokens($content);
        $end = microtime(true);

        $durationMs = ($end - $start) * 1000;
        $this->assertLessThan(1, $durationMs, 'Token estimation should take < 1ms');
    }

    #[Test]
    #[Group('performance')]
    public function model_selection_is_fast(): void
    {
        // Model selection should be < 1ms
        $start = microtime(true);
        $this->manager->selectOptimalModel(50000);
        $end = microtime(true);

        $durationMs = ($end - $start) * 1000;
        $this->assertLessThan(1, $durationMs, 'Model selection should take < 1ms');
    }

    #[Test]
    #[Group('performance')]
    public function get_model_limits_is_cached_or_fast(): void
    {
        // Getting model limits should be instant
        $start = microtime(true);
        $this->manager->getModelLimits();
        $end = microtime(true);

        $durationMs = ($end - $start) * 1000;
        $this->assertLessThan(1, $durationMs, 'Getting model limits should take < 1ms');
    }

    // =========================================================================
    // Data Providers
    // =========================================================================

    public static function modelLimitProvider(): array
    {
        return [
            'ernie' => ['baidu/ernie-5.5-flash-preview:free', 1024000, 918200],
            'lfm3' => ['liquid/lfm-3-1.7b:free', 262144, 232529],
            'nano_omni' => ['nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', 256000, 227000],
            'lfm2' => ['liquid/lfm-2.5-2.6b:free', 65536, 55582],
        ];
    }

    #[Test]
    #[DataProvider('modelLimitProvider')]
    #[Group('data-driven')]
    public function it_validates_all_models_correctly(string $model, int $rawLimit, int $effectiveLimit): void
    {
        $this->assertEquals($rawLimit, $this->manager->getModelLimits()[$model]);
        $this->assertEquals($effectiveLimit, $this->manager->getEffectiveLimit($model));
    }
}
