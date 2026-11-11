<?php

namespace Tests\Unit\Services\AI;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use App\Services\AI\ContextWindowManager;
use App\Services\AI\ModelSelectionService;
use App\Services\Billing\ModelPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
                'provider' => 'baidu',
                'model_id' => 'baidu/ernie-5.5-flash-preview:free',
                'display_name' => 'ERNIE 5.5 Flash',
                'context_window' => 1024000,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'nvidia',
                'model_id' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
                'display_name' => 'Nemotron 3 Nano Omni 30B',
                'context_window' => 256000,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'liquid',
                'model_id' => 'liquid/lfm-3-1.7b:free',
                'display_name' => 'LFM 3 1.7B',
                'context_window' => 262144,
                'is_free_tier' => true,
                'is_active' => true,
            ],
            [
                'provider' => 'liquid',
                'model_id' => 'liquid/lfm-2.5-2.6b:free',
                'display_name' => 'LFM 2.5 2.6B',
                'context_window' => 65536,
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

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_002_select_smallest_for_content_under_55k_tokens(): void
    {
        // Content under the smallest model's effective limit (55582)
        $model = $this->service->selectModel(3000);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_003_select_second_model_for_55k_to_227k_tokens(): void
    {
        // Content between the smallest (55582) and second model (227000) limits
        $model = $this->service->selectModel(100000);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_004_select_largest_for_232k_to_918k_tokens(): void
    {
        // Content between the third model (232529) and largest model (918200) limits
        $model = $this->service->selectModel(500000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_005_fallback_to_largest_for_over_918k_tokens(): void
    {
        // Content exceeding all limits, fallback to the largest model
        $model = $this->service->selectModel(1000000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_006_select_smallest_at_exact_boundary(): void
    {
        // At exact smallest-model effective limit (55582)
        $model = $this->service->selectModel(55582);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_007_select_second_model_just_over_smallest_boundary(): void
    {
        // One token over the smallest model's limit
        $model = $this->service->selectModel(55583);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_008_select_largest_just_over_third_boundary(): void
    {
        // One token over the third model's limit (232529)
        $model = $this->service->selectModel(232530);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('selection')]
    public function ms_009_handle_zero_tokens(): void
    {
        // Zero tokens should select smallest model
        $model = $this->service->selectModel(0);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    // =========================================================================
    // User Preference Tests (UP)
    // =========================================================================

    #[Test]
    #[Group('preference')]
    public function up_001_respect_user_preference_when_fits(): void
    {
        // User prefers the second model, content fits
        $user = User::factory()->create();
        $qwenModel = AIModel::where('model_id', 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $qwenModel->id,
            'is_default' => true,
        ]);

        // Content fits in the preferred model's context
        $model = $this->service->selectModel(5000, $user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_002_ignore_preference_when_too_small(): void
    {
        // User prefers the smallest model, but content doesn't fit
        $user = User::factory()->create();
        $llamaModel = AIModel::where('model_id', 'liquid/lfm-2.5-2.6b:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $llamaModel->id,
            'is_default' => true,
        ]);

        // Content too large for the smallest model -> next model up
        $model = $this->service->selectModel(100000, $user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_003_use_preference_for_largest_model(): void
    {
        // User prefers the largest model for small content
        $user = User::factory()->create();
        $geminiModel = AIModel::where('model_id', 'baidu/ernie-5.5-flash-preview:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $geminiModel->id,
            'is_default' => true,
        ]);

        // Content would fit the smallest model, but the user prefers the largest
        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_004_no_preference_returns_optimal(): void
    {
        // User with no preference
        $user = User::factory()->create();

        // Should return the optimal model for 100000 tokens
        $model = $this->service->selectModel(100000, $user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_005_null_user_returns_optimal(): void
    {
        // No user provided
        $model = $this->service->selectModel(100000, null);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
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

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('preference')]
    public function up_007_preference_for_nonexistent_model_uses_optimal(): void
    {
        // User preference points to non-existent model
        // This is tested by having the preference point to a model that doesn't exist in the DB
        // Since we can't bypass FK constraints in SQLite, we test this via getUserPreference
        $user = User::factory()->create();
        
        // Test that getUserPreference returns null for non-existent model
        // by mocking the scenario where preference exists but model doesn't
        // We can simulate this by deleting the model after creating a preference
        
        $tempModel = AIModel::create([
            'provider' => 'temp',
            'model_id' => 'temp/model-to-delete',
            'display_name' => 'Temp Model',
            'context_window' => 8192,
            'is_free_tier' => true,
            'is_active' => true,
        ]);
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $tempModel->id,
            'is_default' => true,
        ]);
        
        // Delete the model to simulate non-existent reference
        $tempModel->delete();
        
        // Clear the cache to ensure fresh lookup
        $this->service->invalidateCache();
        Cache::forget("user_preferred_model:{$user->id}");

        // Should return null for preference, then select optimal model
        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    // =========================================================================
    // Model Availability Tests (MA)
    // =========================================================================

    #[Test]
    #[Group('availability')]
    public function ma_001_active_model_is_available(): void
    {
        $available = $this->service->isModelAvailable('baidu/ernie-5.5-flash-preview:free');

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
            'baidu/ernie-5.5-flash-preview:free',
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'liquid/lfm-3-1.7b:free',
            'liquid/lfm-2.5-2.6b:free',
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
            'baidu/ernie-5.5-flash-preview:free',
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'liquid/lfm-3-1.7b:free',
            'liquid/lfm-2.5-2.6b:free',
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
    public function fm_001_fallback_returns_largest_model(): void
    {
        $fallback = $this->service->getFallbackModel();

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $fallback);
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
            ['tokens' => 3000, 'expected' => 'liquid/lfm-2.5-2.6b:free'],
            ['tokens' => 100000, 'expected' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free'],
            ['tokens' => 500000, 'expected' => 'baidu/ernie-5.5-flash-preview:free'],
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
        $smallestLimit = $this->contextManager->getEffectiveLimit('liquid/lfm-2.5-2.6b:free');
        $secondLimit = $this->contextManager->getEffectiveLimit('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free');

        // At the smallest model's limit
        $model = $this->service->selectModel($smallestLimit);
        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);

        // Just over the smallest model's limit
        $model = $this->service->selectModel($smallestLimit + 1);
        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);

        // At the second model's limit
        $model = $this->service->selectModel($secondLimit);
        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);

        // Just over the second model's limit
        $model = $this->service->selectModel($secondLimit + 1);
        $this->assertEquals('liquid/lfm-3-1.7b:free', $model);
    }

    // =========================================================================
    // Model Priority Matrix Tests (MP)
    // =========================================================================

    #[Test]
    #[Group('priority')]
    public function mp_001_under_55k_tokens_selects_smallest_model(): void
    {
        // The smallest model's effective limit is 55582 tokens (65536 * 0.9 - 3400)
        // Content under this limit should select the smallest model
        $model = $this->service->selectModel(3000);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_002_55k_to_227k_tokens_selects_second_model(): void
    {
        // The second model's effective limit is 227000 tokens (256000 * 0.9 - 3400)
        // Content between the smallest limit (55582) and the second limit should
        // select the second model
        $model = $this->service->selectModel(100000);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_003_232k_to_918k_tokens_selects_largest_model(): void
    {
        // The largest model's effective limit is 918200 tokens (1024000 * 0.9 - 3400)
        // Content between the third limit (232529) and the largest limit should
        // select the largest model
        $model = $this->service->selectModel(500000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('priority')]
    public function mp_004_over_1m_tokens_selects_largest_with_truncation(): void
    {
        // Over 1M tokens requires truncation
        $model = $this->service->selectModel(2000000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
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

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_002_very_large_tokens(): void
    {
        // Very large tokens should fallback to the largest model
        $model = $this->service->selectModel(10000000);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_003_exactly_at_largest_limit(): void
    {
        // At exact largest-model limit (918200)
        $model = $this->service->selectModel(918200);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_004_one_over_largest_limit(): void
    {
        // One token over the largest model's limit
        $model = $this->service->selectModel(918201);

        $this->assertEquals('baidu/ernie-5.5-flash-preview:free', $model);
    }

    #[Test]
    #[Group('edge-cases')]
    public function ec_005_empty_user_object(): void
    {
        // User with no preferences set
        $user = User::factory()->create();

        $model = $this->service->selectModel(3000, $user);

        $this->assertEquals('liquid/lfm-2.5-2.6b:free', $model);
    }

    // =========================================================================
    // Get User Preference Tests
    // =========================================================================

    #[Test]
    #[Group('preference')]
    public function get_user_preference_returns_model_id(): void
    {
        $user = User::factory()->create();
        $qwenModel = AIModel::where('model_id', 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $qwenModel->id,
            'is_default' => true,
        ]);

        $preference = $this->service->getUserPreference($user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $preference);
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
        
        // Small -> smallest model
        $this->assertEquals(
            'liquid/lfm-2.5-2.6b:free',
            $this->service->selectModel(1000)
        );

        // Medium -> second model
        $this->assertEquals(
            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            $this->service->selectModel(100000)
        );

        // Large -> largest model
        $this->assertEquals(
            'baidu/ernie-5.5-flash-preview:free',
            $this->service->selectModel(500000)
        );
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_002_user_preferences_respected_when_content_fits(): void
    {
        // AC: User preferences respected when content fits
        
        $user = User::factory()->create();
        $preferredModel = AIModel::where('model_id', 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $preferredModel->id,
            'is_default' => true,
        ]);

        // Content fits in the preferred model
        $model = $this->service->selectModel(5000, $user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_003_falls_back_gracefully_when_preferred_too_small(): void
    {
        // AC: Falls back gracefully when preferred model too small
        
        $user = User::factory()->create();
        $smallestModel = AIModel::where('model_id', 'liquid/lfm-2.5-2.6b:free')->first();
        
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $smallestModel->id,
            'is_default' => true,
        ]);

        // Content too large for the smallest model -> next model up
        $model = $this->service->selectModel(100000, $user);

        $this->assertEquals('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $model);
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
        
        $this->assertContains('baidu/ernie-5.5-flash-preview:free', $modelIds);
        $this->assertContains('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free', $modelIds);
        $this->assertContains('liquid/lfm-3-1.7b:free', $modelIds);
        $this->assertContains('liquid/lfm-2.5-2.6b:free', $modelIds);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_006_model_availability_checked(): void
    {
        // AC: Model availability checked
        
        // Active models should be available
        $this->assertTrue($this->service->isModelAvailable('baidu/ernie-5.5-flash-preview:free'));
        $this->assertTrue($this->service->isModelAvailable('liquid/lfm-2.5-2.6b:free'));

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
            $this->service->isModelAvailable('baidu/ernie-5.5-flash-preview:free');
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
            'tiny' => [500, 'liquid/lfm-2.5-2.6b:free'],
            'small' => [3000, 'liquid/lfm-2.5-2.6b:free'],
            'medium' => [100000, 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free'],
            'large' => [500000, 'baidu/ernie-5.5-flash-preview:free'],
            'huge' => [1000000, 'baidu/ernie-5.5-flash-preview:free'],
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
            'at smallest limit' => [55582, 'liquid/lfm-2.5-2.6b:free'],
            'over smallest limit' => [55583, 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free'],
            'at second limit' => [227000, 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free'],
            'over second limit' => [227001, 'liquid/lfm-3-1.7b:free'],
            'at third limit' => [232529, 'liquid/lfm-3-1.7b:free'],
            'over third limit' => [232530, 'baidu/ernie-5.5-flash-preview:free'],
            'at largest limit' => [918200, 'baidu/ernie-5.5-flash-preview:free'],
            'over largest limit' => [918201, 'baidu/ernie-5.5-flash-preview:free'],
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
