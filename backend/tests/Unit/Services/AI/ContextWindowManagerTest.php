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
        $this->assertArrayHasKey('google/gemini-2.0-flash-exp:free', $limits);
        $this->assertArrayHasKey('qwen/qwen-2-7b-instruct:free', $limits);
        $this->assertArrayHasKey('mistralai/mistral-7b-instruct:free', $limits);
        $this->assertArrayHasKey('meta-llama/llama-3-8b-instruct:free', $limits);

        $this->assertEquals(1000000, $limits['google/gemini-2.0-flash-exp:free']);
        $this->assertEquals(32768, $limits['qwen/qwen-2-7b-instruct:free']);
        $this->assertEquals(32768, $limits['mistralai/mistral-7b-instruct:free']);
        $this->assertEquals(8192, $limits['meta-llama/llama-3-8b-instruct:free']);
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
    public function it_calculates_effective_limit_for_gemini(): void
    {
        // 1,000,000 * 0.9 = 900,000 - 3400 = 896,600
        $effective = $this->manager->getEffectiveLimit('google/gemini-2.0-flash-exp:free');

        $this->assertEquals(896600, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_qwen(): void
    {
        // 32,768 * 0.9 = 29,491.2 - 3400 = 26,091
        $effective = $this->manager->getEffectiveLimit('qwen/qwen-2-7b-instruct:free');

        $this->assertEquals(26091, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_mistral(): void
    {
        // 32,768 * 0.9 = 29,491.2 - 3400 = 26,091
        $effective = $this->manager->getEffectiveLimit('mistralai/mistral-7b-instruct:free');

        $this->assertEquals(26091, $effective);
    }

    #[Test]
    #[Group('effective-limit')]
    public function it_calculates_effective_limit_for_llama(): void
    {
        // 8,192 * 0.9 = 7,372.8 - 3400 = 3,972
        $effective = $this->manager->getEffectiveLimit('meta-llama/llama-3-8b-instruct:free');

        $this->assertEquals(3972, $effective);
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
    public function it_validates_content_fits_in_gemini_context(): void
    {
        // Gemini has ~896,600 effective limit
        $fits = $this->manager->fitsInContext('google/gemini-2.0-flash-exp:free', 500000);

        $this->assertTrue($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_does_not_fit_in_gemini_context(): void
    {
        // Gemini has ~896,600 effective limit
        $fits = $this->manager->fitsInContext('google/gemini-2.0-flash-exp:free', 900000);

        $this->assertFalse($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_fits_in_llama_context(): void
    {
        // Llama has ~3,972 effective limit
        $fits = $this->manager->fitsInContext('meta-llama/llama-3-8b-instruct:free', 3000);

        $this->assertTrue($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_content_does_not_fit_in_llama_context(): void
    {
        // Llama has ~3,972 effective limit
        $fits = $this->manager->fitsInContext('meta-llama/llama-3-8b-instruct:free', 5000);

        $this->assertFalse($fits);
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_at_exact_limit_boundary(): void
    {
        // Llama has ~3,972 effective limit
        $fits = $this->manager->fitsInContext('meta-llama/llama-3-8b-instruct:free', 3972);

        $this->assertTrue($fits, 'Content exactly at limit should fit');
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_one_over_limit_boundary(): void
    {
        // Llama has ~3,972 effective limit
        $fits = $this->manager->fitsInContext('meta-llama/llama-3-8b-instruct:free', 3973);

        $this->assertFalse($fits, 'Content one token over limit should not fit');
    }

    #[Test]
    #[Group('validation')]
    public function it_validates_zero_tokens_fits_any_model(): void
    {
        $models = [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3-8b-instruct:free',
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
    public function it_selects_llama_for_small_content(): void
    {
        // Small content fits in all models, should prefer smallest (Llama)
        $model = $this->manager->selectOptimalModel(3000);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_qwen_for_medium_content(): void
    {
        // Content that doesn't fit in Llama but fits in Qwen/Mistral
        // Llama limit: ~3,972, Qwen/Mistral limit: ~26,091
        $model = $this->manager->selectOptimalModel(10000);

        // Should prefer first available (Qwen comes before Mistral in the list)
        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_gemini_for_large_content(): void
    {
        // Content that only fits in Gemini
        // Qwen/Mistral limit: ~26,091, Gemini limit: ~896,600
        $model = $this->manager->selectOptimalModel(100000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_falls_back_to_gemini_for_content_exceeding_all_limits(): void
    {
        // Content that exceeds all limits should fallback to Gemini (largest)
        $model = $this->manager->selectOptimalModel(1000000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_smallest_sufficient_model(): void
    {
        // Test the priority order: smallest sufficient model
        // Llama (3,972) < Qwen/Mistral (26,091) < Gemini (896,600)
        
        // Should select Llama
        $this->assertEquals(
            'meta-llama/llama-3-8b-instruct:free',
            $this->manager->selectOptimalModel(3500)
        );

        // Should select Qwen
        $this->assertEquals(
            'qwen/qwen-2-7b-instruct:free',
            $this->manager->selectOptimalModel(10000)
        );

        // Should select Gemini
        $this->assertEquals(
            'google/gemini-2.0-flash-exp:free',
            $this->manager->selectOptimalModel(50000)
        );
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_correct_model_at_boundary(): void
    {
        // At exact Llama limit (3,972) should still select Llama
        $model = $this->manager->selectOptimalModel(3972);
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);

        // One over Llama limit should select Qwen
        $model = $this->manager->selectOptimalModel(3973);
        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_correct_model_at_qwen_boundary(): void
    {
        // At exact Qwen limit (26,091) should select Qwen
        $model = $this->manager->selectOptimalModel(26091);
        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);

        // One over Qwen limit should select Gemini
        $model = $this->manager->selectOptimalModel(26092);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_selects_gemini_at_gemini_boundary(): void
    {
        // At exact Gemini limit (896,600) should select Gemini
        $model = $this->manager->selectOptimalModel(896600);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function it_falls_back_to_gemini_over_limit(): void
    {
        // Over Gemini limit should still return Gemini (will need truncation)
        $model = $this->manager->selectOptimalModel(896601);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_zero_tokens(): void
    {
        $model = $this->manager->selectOptimalModel(0);

        // Should select smallest model (Llama) for zero tokens
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_negative_tokens_gracefully(): void
    {
        // Negative tokens shouldn't happen in practice, but should handle gracefully
        $model = $this->manager->selectOptimalModel(-100);

        // Should select smallest model
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function it_handles_very_large_token_counts(): void
    {
        // 10 million tokens - way over any limit
        $model = $this->manager->selectOptimalModel(10000000);

        // Should fallback to Gemini
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
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
        
        // Should fit in Llama
        $fits = $this->manager->fitsInContext('meta-llama/llama-3-8b-instruct:free', $estimatedTokens);
        $this->assertTrue($fits);

        // Should select Llama (smallest sufficient)
        $selectedModel = $this->manager->selectOptimalModel($estimatedTokens);
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $selectedModel);
    }

    #[Test]
    #[Group('integration')]
    public function full_workflow_large_content(): void
    {
        // Large content
        $content = str_repeat('test content ', 10000); // ~130,000 chars
        $estimatedTokens = $this->manager->estimateTokens($content);
        
        // Should NOT fit in Qwen/Mistral
        $fitsQwen = $this->manager->fitsInContext('qwen/qwen-2-7b-instruct:free', $estimatedTokens);
        $this->assertFalse($fitsQwen);

        // Should fit in Gemini
        $fitsGemini = $this->manager->fitsInContext('google/gemini-2.0-flash-exp:free', $estimatedTokens);
        $this->assertTrue($fitsGemini);

        // Should select Gemini
        $selectedModel = $this->manager->selectOptimalModel($estimatedTokens);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $selectedModel);
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
        $models = [
            'google/gemini-2.0-flash-exp:free' => 896600,
            'qwen/qwen-2-7b-instruct:free' => 26091,
            'mistralai/mistral-7b-instruct:free' => 26091,
            'meta-llama/llama-3-8b-instruct:free' => 3972,
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
        
        // Small content → Llama
        $this->assertEquals(
            'meta-llama/llama-3-8b-instruct:free',
            $this->manager->selectOptimalModel(1000)
        );

        // Medium content → Qwen
        $this->assertEquals(
            'qwen/qwen-2-7b-instruct:free',
            $this->manager->selectOptimalModel(5000)
        );

        // Large content → Gemini
        $this->assertEquals(
            'google/gemini-2.0-flash-exp:free',
            $this->manager->selectOptimalModel(50000)
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
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3-8b-instruct:free',
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
            'gemini' => ['google/gemini-2.0-flash-exp:free', 1000000, 896600],
            'qwen' => ['qwen/qwen-2-7b-instruct:free', 32768, 26091],
            'mistral' => ['mistralai/mistral-7b-instruct:free', 32768, 26091],
            'llama' => ['meta-llama/llama-3-8b-instruct:free', 8192, 3972],
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
