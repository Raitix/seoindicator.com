<?php

namespace Tests\Unit\Services\AI;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use App\Services\AI\ContextWindowManager;
use App\Services\AI\ModelSelectionService;
use App\Services\Billing\ModelPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for ModelSelectionService.
 * 
 * Tests model selection logic based on content size, user preferences,
 * and model availability for the Unified AI Prompt + Context Management feature.
 * 
 * @see TASK-31-8
 * @see SPEC-031
 */
class ModelSelectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ModelSelectionService $service;
    private ContextWindowManager $contextManager;
    private ModelPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->contextManager = new ContextWindowManager();
        $this->pricingService = app(ModelPricingService::class);
        $this->service = new ModelSelectionService($this->contextManager, $this->pricingService);
        
        // Seed required models
        $this->seedModels();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Seed the AI models table with test data.
     */
    private function seedModels(): void
    {
        $models = [
            [
                'provider' => 'google',
                'model_id' => 'google/gemini-2.0-flash-exp:free',
                'display_name' => 'Gemini 2.0 Flash',
                'context_window' => 1000000,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'qwen',
                'model_id' => 'qwen/qwen-2-7b-instruct:free',
                'display_name' => 'Qwen 2 7B',
                'context_window' => 32768,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'mistralai',
                'model_id' => 'mistralai/mistral-7b-instruct:free',
                'display_name' => 'Mistral 7B',
                'context_window' => 32768,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'meta-llama',
                'model_id' => 'meta-llama/llama-3-8b-instruct:free',
                'display_name' => 'Llama 3 8B',
                'context_window' => 8192,
                'is_free_tier' => true,
                'is_active' => true,
            ],
        ];

        foreach ($models as $modelData) {
            AIModel::create($modelData);
        }
    }

    // =========================================================================
    // Model Selection Tests (MS)
    // =========================================================================

    #[Test]
    #[Group('selection')]
    public function ms_001_select_smallest_model_for_tiny_content(): void
    {
        // Small content should select Llama (smallest context)
        $model = $this->service->selectModel(500);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_002_select_llama_for_content_under_4k_tokens(): void
    {
        // Content under Llama's effective limit (3972)
        $model = $this->service->selectModel(3000);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_003_select_qwen_for_4k_to_26k_tokens(): void
    {
        // Content between Llama and Qwen limits
        $model = $this->service->selectModel(10000);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_004_select_gemini_for_26k_to_896k_tokens(): void
    {
        // Content between Qwen and Gemini limits
        $model = $this->service->selectModel(50000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_005_fallback_to_gemini_for_over_896k_tokens(): void
    {
        // Content exceeding all limits, fallback to Gemini
        $model = $this->service->selectModel(1000000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_006_select_llama_at_exact_boundary(): void
    {
        // At exact Llama effective limit (3972)
        $model = $this->service->selectModel(3972);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_007_select_qwen_just_over_llama_boundary(): void
    {
        // One token over Llama limit
        $model = $this->service->selectModel(3973);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_008_select_gemini_just_over_qwen_boundary(): void
    {
        // One token over Qwen limit (26091)
        $model = $this->service->selectModel(26092);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_009_handle_zero_tokens(): void
    {
        // Zero tokens should select smallest model
        $model = $this->service->selectModel(0);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    // =========================================================================
    // User Preference Tests (UP)
    // =========================================================================

    #[Test]
    #[Group('preference')]
    public function up_001_respect_user_preference_when_fits(): void
    {
        // User prefers Qwen, content fits
        $user = User::factory()->create();
        $qwenModel = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $qwenModel->id,
            'is_default' => true,
        ]);

        // Content fits in Qwen context
        $model = $this->service->selectModel(5000, $user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_002_ignore_preference_when_too_small(): void
    {
        // User prefers Llama, but content doesn't fit
        $user = User::factory()->create();
        $llamaModel = AIModel::where('model_id', 'meta-llama/llama-3-8b-instruct:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $llamaModel->id,
            'is_default' => true,
        ]);

        // Content too large for Llama, should fall back to Qwen
        $model = $this->service->selectModel(10000, $user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_003_use_preference_for_gemini(): void
    {
        // User prefers Gemini for small content
        $user = User::factory()->create();
        $geminiModel = AIModel::where('model_id', 'google/gemini-2.0-flash-exp:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $geminiModel->id,
            'is_default' => true,
        ]);

        // Content would fit in Llama, but user prefers Gemini
        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_004_no_preference_returns_optimal(): void
    {
        // User with no preference
        $user = User::factory()->create();

        // Should return optimal model (Qwen for 5000 tokens)
        $model = $this->service->selectModel(5000, $user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_005_null_user_returns_optimal(): void
    {
        // No user provided
        $model = $this->service->selectModel(5000, null);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_006_preference_for_inactive_model_uses_optimal(): void
    {
        // User prefers inactive model
        $user = User::factory()->create();
        
        // Create inactive model
        $inactiveModel = AIModel::create([
            'provider' => 'test',
            'model_id' => 'test/inactive-model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $inactiveModel->id,
            'is_default' => true,
        ]);

        // Should select optimal model instead
        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_007_preference_for_nonexistent_model_uses_optimal(): void
    {
        // User preference points to non-existent model
        $user = User::factory()->create();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => 99999, // Non-existent
            'is_default' => true,
        ]);

        // Should select optimal model
        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    // =========================================================================
    // Model Availability Tests (MA)
    // =========================================================================

    #[Test]
    #[Group('availability')]
    public function ma_001_active_model_is_available(): void
    {
        $available = $this->service->isModelAvailable('google/gemini-2.0-flash-exp:free');

        $this->assertTrue($available);
    }

    #[Test]
    #[Group('availability')]
    public function ma_002_inactive_model_not_available(): void
    {
        // Create inactive model
        AIModel::create([
            'provider' => 'test',
            'model_id' => 'test/inactive',
            'display_name' => 'Inactive',
            'is_active' => false,
        ]);

        $available = $this->service->isModelAvailable('test/inactive');

        $this->assertFalse($available);
    }

    #[Test]
    #[Group('availability')]
    public function ma_003_unknown_model_not_available(): void
    {
        $available = $this->service->isModelAvailable('unknown/model');

        $this->assertFalse($available);
    }

    #[Test]
    #[Group('availability')]
    public function ma_004_free_tier_model_available(): void
    {
        $models = [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3-8b-instruct:free',
        ];

        foreach ($models as $modelId) {
            $this->assertTrue(
                $this->service->isModelAvailable($modelId),
                "{$modelId} should be available"
            );
        }
    }

    #[Test]
    #[Group('availability')]
    public function ma_005_all_supported_models_available(): void
    {
        $supportedModels = [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3-8b-instruct:free',
        ];

        foreach ($supportedModels as $modelId) {
            $this->assertTrue(
                $this->service->isModelAvailable($modelId),
                "Model {$modelId} should be available"
            );
        }
    }

    // =========================================================================
    // Fallback Model Tests (FM)
    // =========================================================================

    #[Test]
    #[Group('fallback')]
    public function fm_001_fallback_returns_gemini(): void
    {
        $fallback = $this->service->getFallbackModel();

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $fallback);
    }

    #[Test]
    #[Group('fallback')]
    public function fm_002_fallback_is_consistent(): void
    {
        $fallback1 = $this->service->getFallbackModel();
        $fallback2 = $this->service->getFallbackModel();
        $fallback3 = $this->service->getFallbackModel();

        $this->assertEquals($fallback1, $fallback2);
        $this->assertEquals($fallback2, $fallback3);
    }

    // =========================================================================
    // Context Integration Tests (CI)
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function ci_001_integrates_with_context_window_manager(): void
    {
        // Test that ModelSelectionService uses ContextWindowManager correctly
        $limits = $this->contextManager->getModelLimits();

        // Test at each boundary
        $testCases = [
            ['tokens' => 3000, 'expected' => 'meta-llama/llama-3-8b-instruct:free'],
            ['tokens' => 15000, 'expected' => 'qwen/qwen-2-7b-instruct:free'],
            ['tokens' => 100000, 'expected' => 'google/gemini-2.0-flash-exp:free'],
        ];

        foreach ($testCases as $case) {
            $selected = $this->service->selectModel($case['tokens']);
            $this->assertEquals(
                $case['expected'],
                $selected,
                "Expected {$case['expected']} for {$case['tokens']} tokens"
            );
        }
    }

    #[Test]
    #[Group('integration')]
    public function ci_002_respects_context_limits(): void
    {
        // Verify that content just under limit fits
        $llamaLimit = $this->contextManager->getEffectiveLimit('meta-llama/llama-3-8b-instruct:free');
        $qwenLimit = $this->contextManager->getEffectiveLimit('qwen/qwen-2-7b-instruct:free');

        // At Llama limit
        $model = $this->service->selectModel($llamaLimit);
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);

        // Just over Llama limit
        $model = $this->service->selectModel($llamaLimit + 1);
        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    // =========================================================================
    // Model Priority Matrix Tests (MP)
    // =========================================================================

    #[Test]
    #[Group('priority')]
    public function mp_001_under_8k_tokens_selects_llama(): void
    {
        $model = $this->service->selectModel(5000);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_002_8k_to_32k_tokens_selects_qwen(): void
    {
        $model = $this->service->selectModel(15000);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_003_32k_to_1m_tokens_selects_gemini(): void
    {
        $model = $this->service->selectModel(100000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_004_over_1m_tokens_selects_gemini_with_truncation(): void
    {
        // Over 1M tokens requires truncation
        $model = $this->service->selectModel(2000000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    // =========================================================================
    // Edge Cases (EC)
    // =========================================================================

    #[Test]
    #[Group('edge-cases')]
    public function ec_001_negative_tokens_handled(): void
    {
        // Negative tokens should be treated as 0 or small
        $model = $this->service->selectModel(-100);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_002_very_large_tokens(): void
    {
        // Very large tokens should fallback to Gemini
        $model = $this->service->selectModel(10000000);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_003_exactly_at_gemini_limit(): void
    {
        // At exact Gemini limit (896600)
        $model = $this->service->selectModel(896600);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_004_one_over_gemini_limit(): void
    {
        // One token over Gemini limit
        $model = $this->service->selectModel(896601);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_005_empty_user_object(): void
    {
        // User with no preferences set
        $user = User::factory()->create();

        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $model);
    }

    // =========================================================================
    // Get User Preference Tests
    // =========================================================================

    #[Test]
    #[Group('preference')]
    public function get_user_preference_returns_model_id(): void
    {
        $user = User::factory()->create();
        $qwenModel = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $qwenModel->id,
            'is_default' => true,
        ]);

        $preference = $this->service->getUserPreference($user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $preference);
    }

    #[Test]
    #[Group('preference')]
    public function get_user_preference_returns_null_when_no_preference(): void
    {
        $user = User::factory()->create();

        $preference = $this->service->getUserPreference($user);

        $this->assertNull($preference);
    }

    #[Test]
    #[Group('preference')]
    public function get_user_preference_returns_null_for_inactive_model(): void
    {
        $user = User::factory()->create();
        $inactiveModel = AIModel::create([
            'provider' => 'test',
            'model_id' => 'test/inactive',
            'display_name' => 'Inactive',
            'is_active' => false,
        ]);
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $inactiveModel->id,
            'is_default' => true,
        ]);

        $preference = $this->service->getUserPreference($user);

        $this->assertNull($preference);
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_001_select_model_returns_optimal_for_content_size(): void
    {
        // AC: selectModel() returns optimal model for content size
        
        // Small → Llama
        $this->assertEquals(
            'meta-llama/llama-3-8b-instruct:free',
            $this->service->selectModel(1000)
        );

        // Medium → Qwen
        $this->assertEquals(
            'qwen/qwen-2-7b-instruct:free',
            $this->service->selectModel(10000)
        );

        // Large → Gemini
        $this->assertEquals(
            'google/gemini-2.0-flash-exp:free',
            $this->service->selectModel(100000)
        );
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_002_user_preferences_respected_when_content_fits(): void
    {
        // AC: User preferences respected when content fits
        
        $user = User::factory()->create();
        $qwenModel = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $qwenModel->id,
            'is_default' => true,
        ]);

        // Content fits in Qwen
        $model = $this->service->selectModel(5000, $user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_003_falls_back_gracefully_when_preferred_too_small(): void
    {
        // AC: Falls back gracefully when preferred model too small
        
        $user = User::factory()->create();
        $llamaModel = AIModel::where('model_id', 'meta-llama/llama-3-8b-instruct:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $llamaModel->id,
            'is_default' => true,
        ]);

        // Content too large for Llama → fall back to Qwen
        $model = $this->service->selectModel(10000, $user);

        $this->assertEquals('qwen/qwen-2-7b-instruct:free', $model);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_004_free_tier_models_prioritized(): void
    {
        // AC: Free-tier models prioritized
        
        // All selected models should be free-tier
        $testCases = [500, 5000, 50000, 500000];

        foreach ($testCases as $tokens) {
            $modelId = $this->service->selectModel($tokens);
            $model = AIModel::where('model_id', $modelId)->first();

            $this->assertNotNull($model, "Model {$modelId} should exist");
            $this->assertTrue(
                $model->is_free_tier,
                "Model {$modelId} should be free-tier"
            );
        }
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_005_integration_with_model_pricing(): void
    {
        // AC: Integration with existing model pricing (FEAT-026)
        
        // Verify that getAvailableModels returns correct models
        $availableModels = $this->pricingService->getAvailableModels();
        
        $this->assertGreaterThanOrEqual(4, $availableModels->count());
        
        // Verify all our models are in the available list
        $modelIds = $availableModels->pluck('model_id')->toArray();
        
        $this->assertContains('google/gemini-2.0-flash-exp:free', $modelIds);
        $this->assertContains('qwen/qwen-2-7b-instruct:free', $modelIds);
        $this->assertContains('mistralai/mistral-7b-instruct:free', $modelIds);
        $this->assertContains('meta-llama/llama-3-8b-instruct:free', $modelIds);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_006_model_availability_checked(): void
    {
        // AC: Model availability checked
        
        // Active models should be available
        $this->assertTrue($this->service->isModelAvailable('google/gemini-2.0-flash-exp:free'));
        $this->assertTrue($this->service->isModelAvailable('meta-llama/llama-3-8b-instruct:free'));

        // Unknown models should not be available
        $this->assertFalse($this->service->isModelAvailable('unknown/model'));
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    #[Test]
    #[Group('performance')]
    public function model_selection_is_fast(): void
    {
        // Model selection should be < 5ms
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->service->selectModel(rand(100, 100000));
        }
        
        $end = microtime(true);
        $durationMs = ($end - $start) * 1000;

        // 100 selections should take < 500ms (5ms average)
        $this->assertLessThan(500, $durationMs, '100 model selections should take < 500ms');
    }

    #[Test]
    #[Group('performance')]
    public function availability_check_is_fast(): void
    {
        // Availability check should be fast
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->service->isModelAvailable('google/gemini-2.0-flash-exp:free');
        }
        
        $end = microtime(true);
        $durationMs = ($end - $start) * 1000;

        // 100 checks should take < 500ms
        $this->assertLessThan(500, $durationMs, '100 availability checks should take < 500ms');
    }

    // =========================================================================
    // Data Providers
    // =========================================================================

    public static function modelSelectionProvider(): array
    {
        return [
            'tiny' => [500, 'meta-llama/llama-3-8b-instruct:free'],
            'small' => [3000, 'meta-llama/llama-3-8b-instruct:free'],
            'medium' => [10000, 'qwen/qwen-2-7b-instruct:free'],
            'large' => [100000, 'google/gemini-2.0-flash-exp:free'],
            'huge' => [1000000, 'google/gemini-2.0-flash-exp:free'],
        ];
    }

    #[Test]
    #[DataProvider('modelSelectionProvider')]
    #[Group('data-driven')]
    public function it_selects_correct_model_for_token_count(int $tokens, string $expectedModel): void
    {
        $model = $this->service->selectModel($tokens);
        $this->assertEquals($expectedModel, $model);
    }

    public static function boundaryProvider(): array
    {
        return [
            'at llama limit' => [3972, 'meta-llama/llama-3-8b-instruct:free'],
            'over llama limit' => [3973, 'qwen/qwen-2-7b-instruct:free'],
            'at qwen limit' => [26091, 'qwen/qwen-2-7b-instruct:free'],
            'over qwen limit' => [26092, 'google/gemini-2.0-flash-exp:free'],
            'at gemini limit' => [896600, 'google/gemini-2.0-flash-exp:free'],
        ];
    }

    #[Test]
    #[DataProvider('boundaryProvider')]
    #[Group('data-driven')]
    public function it_handles_boundary_values_correctly(int $tokens, string $expectedModel): void
    {
        $model = $this->service->selectModel($tokens);
        $this->assertEquals($expectedModel, $model);
    }
}
