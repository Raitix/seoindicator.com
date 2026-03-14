<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\ModelPricingService;
use App\Services\OpenRouterSyncService;
use App\Models\AIModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Integration tests for TASK-26-16: ModelPricingService
 *
 * Tests full workflows with real database interactions.
 *
 * @see \App\Services\ModelPricingService
 */
class ModelPricingServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ModelPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ModelPricingService::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // FULL SYNC WORKFLOW TESTS
    // =========================================================================

    /**
     * INT-001: Full sync workflow
     */
    public function test_full_sync_workflow(): void
    {
        // Mock OpenRouter API response
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'openai/gpt-4-turbo',
                        'name' => 'GPT-4 Turbo',
                        'context_length' => 128000,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00003',
                        ],
                    ],
                    [
                        'id' => 'anthropic/claude-3-opus',
                        'name' => 'Claude 3 Opus',
                        'context_length' => 200000,
                        'pricing' => [
                            'prompt' => '0.000015',
                            'completion' => '0.000075',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Sync models
        $count = $this->service->syncModelsFromOpenRouter();

        $this->assertEquals(2, $count);

        // Verify models in database
        $models = AIModel::all();
        $this->assertCount(2, $models);

        // Verify pricing
        $gpt4 = AIModel::where('model_id', 'openai/gpt-4-turbo')->first();
        $this->assertEquals(0.01, $gpt4->cost_per_1k_input);
        $this->assertEquals(0.03, $gpt4->cost_per_1k_output);

        // Verify cache was cleared
        $cachedModels = $this->service->getAvailableModels();
        $this->assertCount(2, $cachedModels);
    }

    /**
     * INT-002: User preference workflow
     */
    public function test_user_preference_workflow(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/1',
            'display_name' => 'Model 1',
            'is_active' => true,
        ]);
        $model2 = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/2',
            'display_name' => 'Model 2',
            'is_active' => true,
        ]);

        // Set preference
        $this->service->setUserDefaultModel($user, $model1->id);
        $result = $this->service->getUserDefaultModel($user);
        $this->assertEquals($model1->id, $result->id);

        // Update preference
        $this->service->setUserDefaultModel($user, $model2->id);
        $result = $this->service->getUserDefaultModel($user);
        $this->assertEquals($model2->id, $result->id);

        // Deactivate preferred model
        $model2->update(['is_active' => false]);
        Cache::forget("ai_models:user_default:{$user->id}");

        $result = $this->service->getUserDefaultModel($user);
        $this->assertNull($result);
    }

    /**
     * INT-003: Cost calculation workflow
     */
    public function test_cost_calculation_workflow(): void
    {
        // Create models with different pricing
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cheap/model',
            'display_name' => 'Cheap Model',
            'cost_per_1k_input' => 0.001,
            'cost_per_1k_output' => 0.002,
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'expensive/model',
            'display_name' => 'Expensive Model',
            'cost_per_1k_input' => 0.05,
            'cost_per_1k_output' => 0.10,
            'is_active' => true,
        ]);

        AIModel::factory()->freeTier()->create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'is_active' => true,
        ]);

        // Calculate costs for different analysis types
        $cheapQuick = $this->service->calculateAnalysisCost('cheap/model', 500, 'quick');
        $cheapStandard = $this->service->calculateAnalysisCost('cheap/model', 2000, 'standard');
        $cheapDeep = $this->service->calculateAnalysisCost('cheap/model', 5000, 'deep');

        $this->assertLessThan($cheapStandard['total_cost'], $cheapQuick['total_cost']);
        $this->assertLessThan($cheapDeep['total_cost'], $cheapStandard['total_cost']);

        // Free model should have zero cost
        $freeCost = $this->service->calculateAnalysisCost('free/model', 2000, 'standard');
        $this->assertEquals(0, $freeCost['total_cost']);

        // Expensive model should cost more
        $expensiveStandard = $this->service->calculateAnalysisCost('expensive/model', 2000, 'standard');
        $this->assertGreaterThan($cheapStandard['total_cost'], $expensiveStandard['total_cost']);
    }

    /**
     * INT-004: Cache invalidation workflow
     */
    public function test_cache_invalidation_workflow(): void
    {
        // Create initial models
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'initial/model',
            'display_name' => 'Initial Model',
            'is_active' => true,
        ]);

        // Cache models
        $models = $this->service->getAvailableModels();
        $this->assertCount(1, $models);

        // Add new model
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'new/model',
            'display_name' => 'New Model',
            'is_active' => true,
        ]);

        // Force refresh
        $models = $this->service->getAvailableModels(true);
        $this->assertCount(2, $models);

        // Deactivate one model
        AIModel::where('model_id', 'initial/model')->update(['is_active' => false]);

        // Force refresh
        $models = $this->service->getAvailableModels(true);
        $this->assertCount(1, $models);
        $this->assertEquals('new/model', $models->first()->model_id);
    }

    // =========================================================================
    // CONCURRENT OPERATION TESTS
    // =========================================================================

    /**
     * INT-005: Concurrent preference updates
     */
    public function test_concurrent_preference_updates(): void
    {
        $user = User::factory()->create();
        $models = AIModel::factory()->count(3)->create(['is_active' => true]);

        // Simulate rapid preference changes
        foreach ($models as $model) {
            $this->service->setUserDefaultModel($user, $model->id);
        }

        // Last preference should be set
        $result = $this->service->getUserDefaultModel($user);
        $this->assertEquals($models->last()->id, $result->id);
    }

    /**
     * INT-006: Sync with existing preferences
     */
    public function test_sync_with_existing_preferences(): void
    {
        $user = User::factory()->create();

        // Create initial model and set preference
        $initialModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'initial/model',
            'display_name' => 'Initial Model',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user, $initialModel->id);

        // Sync deactivates the preferred model
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [], // No models returned = deactivate all
            ], 200),
        ]);

        $this->service->syncModelsFromOpenRouter();

        // User preference should now return null (model inactive)
        Cache::forget("ai_models:user_default:{$user->id}");
        $result = $this->service->getUserDefaultModel($user);
        $this->assertNull($result);
    }

    // =========================================================================
    // ERROR RECOVERY TESTS
    // =========================================================================

    /**
     * INT-007: Recovery from API error
     */
    public function test_recovery_from_api_error(): void
    {
        // First call fails
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        $count = $this->service->syncModelsFromOpenRouter();
        $this->assertEquals(0, $count);

        // Second call succeeds
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'recovered/model',
                        'name' => 'Recovered Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModelsFromOpenRouter();
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'recovered/model',
        ]);
    }

    /**
     * INT-008: Partial sync failure
     */
    public function test_partial_sync_failure(): void
    {
        // Create existing model
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'existing/model',
            'display_name' => 'Existing Model',
            'is_active' => true,
        ]);

        // Sync fails
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        $this->service->syncModelsFromOpenRouter();

        // Existing model should still be active
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'existing/model',
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // DATA INTEGRITY TESTS
    // =========================================================================

    /**
     * INT-009: Pricing data integrity
     */
    public function test_pricing_data_integrity(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'precise/model',
            'display_name' => 'Precise Model',
            'cost_per_1k_input' => 0.012345,
            'cost_per_1k_output' => 0.098765,
            'is_active' => true,
        ]);

        // Get pricing
        $pricing = $this->service->getModelPricing('precise/model');

        // Verify exact values
        $this->assertEqualsWithDelta(0.012345, $pricing['input_per_1k'], 0.000001);
        $this->assertEqualsWithDelta(0.098765, $pricing['output_per_1k'], 0.000001);

        // Verify per-million calculation
        $this->assertEqualsWithDelta(12.345, $pricing['input_per_1m'], 0.001);
        $this->assertEqualsWithDelta(98.765, $pricing['output_per_1m'], 0.001);
    }

    /**
     * INT-010: Model data persistence
     */
    public function test_model_data_persistence(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'persistent/model',
                        'name' => 'Persistent Model',
                        'context_length' => 128000,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00003',
                        ],
                        'architecture' => [
                            'modality' => 'text+image->text',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModelsFromOpenRouter();

        // Verify all data persisted
        $model = AIModel::where('model_id', 'persistent/model')->first();

        $this->assertNotNull($model);
        $this->assertEquals('Persistent Model', $model->display_name);
        $this->assertEquals(128000, $model->context_window);
        $this->assertEquals(0.01, $model->cost_per_1k_input);
        $this->assertEquals(0.03, $model->cost_per_1k_output);
        $this->assertNotNull($model->capabilities);
        $this->assertTrue($model->capabilities['vision'] ?? false);
    }

    // =========================================================================
    // BOUNDARY CONDITION TESTS
    // =========================================================================

    /**
     * INT-011: Maximum token calculation
     */
    public function test_maximum_token_calculation(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'large/model',
            'display_name' => 'Large Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Very large token count
        $result = $this->service->calculateAnalysisCost('large/model', PHP_INT_MAX);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cost', $result);
        $this->assertIsFloat($result['total_cost']);
    }

    /**
     * INT-012: Minimum token calculation
     */
    public function test_minimum_token_calculation(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'small/model',
            'display_name' => 'Small Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Minimum token count
        $result = $this->service->calculateAnalysisCost('small/model', 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cost', $result);
        // 1 token = 0.8 input + 0.2 output (4:1 ratio)
        // Input cost: 0.8/1000 * 0.01 = 0.000008
        // Output cost: 0.2/1000 * 0.03 = 0.000006
        // Total: 0.000014
        $this->assertLessThan(0.001, $result['total_cost']);
    }

    /**
     * INT-013: Zero cost model
     */
    public function test_zero_cost_model(): void
    {
        AIModel::factory()->freeTier()->create([
            'provider' => 'openrouter',
            'model_id' => 'zero/model',
            'display_name' => 'Zero Cost Model',
            'is_active' => true,
        ]);

        $quick = $this->service->calculateAnalysisCost('zero/model', 500, 'quick');
        $standard = $this->service->calculateAnalysisCost('zero/model', 2000, 'standard');
        $deep = $this->service->calculateAnalysisCost('zero/model', 5000, 'deep');

        $this->assertEquals(0, $quick['total_cost']);
        $this->assertEquals(0, $standard['total_cost']);
        $this->assertEquals(0, $deep['total_cost']);
    }

    // =========================================================================
    // SEARCH AND FILTER TESTS
    // =========================================================================

    /**
     * INT-014: Get models by provider
     */
    public function test_get_models_by_provider(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openrouter/model',
            'display_name' => 'OpenRouter Model',
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openai',
            'model_id' => 'openai/model',
            'display_name' => 'OpenAI Model',
            'is_active' => true,
        ]);

        $openrouter = $this->service->getModelByProviderId('openrouter', 'openrouter/model');
        $openai = $this->service->getModelByProviderId('openai', 'openai/model');

        $this->assertNotNull($openrouter);
        $this->assertNotNull($openai);
        $this->assertEquals('openrouter', $openrouter->provider);
        $this->assertEquals('openai', $openai->provider);
    }

    /**
     * INT-015: Free tier model detection
     */
    public function test_free_tier_model_detection(): void
    {
        AIModel::factory()->freeTier()->create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('free/model');

        $this->assertTrue($pricing['is_free_tier']);
        $this->assertEquals(0, $pricing['input_per_1k']);
        $this->assertEquals(0, $pricing['output_per_1k']);
    }

    // =========================================================================
    // CACHE COHERENCE TESTS
    // =========================================================================

    /**
     * INT-016: Cache coherence after model update
     */
    public function test_cache_coherence_after_model_update(): void
    {
        $model = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'updatable/model',
            'display_name' => 'Updatable Model',
            'cost_per_1k_input' => 0.01,
            'is_active' => true,
        ]);

        // Cache models
        $models = $this->service->getAvailableModels();

        // Update model directly
        $model->update(['cost_per_1k_input' => 0.02]);

        // Force refresh
        $models = $this->service->getAvailableModels(true);
        $updated = $models->where('model_id', 'updatable/model')->first();

        $this->assertEquals(0.02, $updated->cost_per_1k_input);
    }

    /**
     * INT-017: Cache coherence after model deletion
     */
    public function test_cache_coherence_after_model_deletion(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'deletable/model',
            'display_name' => 'Deletable Model',
            'is_active' => true,
        ]);

        // Cache models
        $models = $this->service->getAvailableModels();
        $this->assertCount(1, $models);

        // Delete model
        AIModel::where('model_id', 'deletable/model')->delete();

        // Force refresh
        $models = $this->service->getAvailableModels(true);
        $this->assertCount(0, $models);
    }

    // =========================================================================
    // MULTI-USER ISOLATION TESTS
    // =========================================================================

    /**
     * INT-018: Multiple users with different preferences
     */
    public function test_multiple_users_different_preferences(): void
    {
        $users = User::factory()->count(3)->create();
        $models = AIModel::factory()->count(3)->create(['is_active' => true]);

        // Each user prefers a different model
        foreach ($users as $index => $user) {
            $this->service->setUserDefaultModel($user, $models[$index]->id);
        }

        // Verify each user gets their own preference
        foreach ($users as $index => $user) {
            $result = $this->service->getUserDefaultModel($user);
            $this->assertEquals($models[$index]->id, $result->id);
        }
    }

    /**
     * INT-019: User preference isolation after model deactivation
     */
    public function test_user_preference_isolation_after_deactivation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model1 = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/1',
            'display_name' => 'Model 1',
            'is_active' => true,
        ]);
        $model2 = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/2',
            'display_name' => 'Model 2',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user1, $model1->id);
        $this->service->setUserDefaultModel($user2, $model2->id);

        // Deactivate model1
        $model1->update(['is_active' => false]);
        Cache::forget("ai_models:user_default:{$user1->id}");

        // User1's preference should be null
        $result1 = $this->service->getUserDefaultModel($user1);
        $this->assertNull($result1);

        // User2's preference should still work
        $result2 = $this->service->getUserDefaultModel($user2);
        $this->assertEquals($model2->id, $result2->id);
    }

    // =========================================================================
    // COMPLEX SCENARIO TESTS
    // =========================================================================

    /**
     * INT-020: Full lifecycle scenario
     */
    public function test_full_lifecycle_scenario(): void
    {
        $user = User::factory()->create();

        // 1. Initial sync
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'openai/gpt-4-turbo',
                        'name' => 'GPT-4 Turbo',
                        'context_length' => 128000,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00003',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModelsFromOpenRouter();
        $this->assertEquals(1, $count);

        // 2. Get available models
        $models = $this->service->getAvailableModels();
        $this->assertCount(1, $models);

        // 3. Set user preference
        $gpt4 = $models->first();
        $this->service->setUserDefaultModel($user, $gpt4->id);

        // 4. Calculate cost
        $cost = $this->service->calculateAnalysisCost('openai/gpt-4-turbo', 2000, 'standard');
        $this->assertGreaterThan(0, $cost['total_cost']);

        // 5. Get pricing
        $pricing = $this->service->getModelPricing('openai/gpt-4-turbo');
        $this->assertEquals(0.01, $pricing['input_per_1k']);

        // 6. Verify user preference
        $preferred = $this->service->getUserDefaultModel($user);
        $this->assertEquals('openai/gpt-4-turbo', $preferred->model_id);

        // 7. Update sync (add new model, remove old)
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'anthropic/claude-3-opus',
                        'name' => 'Claude 3 Opus',
                        'context_length' => 200000,
                        'pricing' => [
                            'prompt' => '0.000015',
                            'completion' => '0.000075',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModelsFromOpenRouter();

        // 8. Verify GPT-4 deactivated
        $gpt4->refresh();
        $this->assertFalse($gpt4->is_active);

        // 9. Verify user preference now returns null
        Cache::forget("ai_models:user_default:{$user->id}");
        $preferred = $this->service->getUserDefaultModel($user);
        $this->assertNull($preferred);
    }

    /**
     * INT-021: Cost comparison across models
     */
    public function test_cost_comparison_across_models(): void
    {
        $models = [
            ['model_id' => 'economy/model', 'input' => 0.001, 'output' => 0.002],
            ['model_id' => 'standard/model', 'input' => 0.01, 'output' => 0.02],
            ['model_id' => 'premium/model', 'input' => 0.05, 'output' => 0.10],
        ];

        foreach ($models as $data) {
            AIModel::factory()->create([
                'provider' => 'openrouter',
                'model_id' => $data['model_id'],
                'display_name' => ucfirst(explode('/', $data['model_id'])[0]) . ' Model',
                'cost_per_1k_input' => $data['input'],
                'cost_per_1k_output' => $data['output'],
                'is_active' => true,
            ]);
        }

        // Calculate costs for standard analysis
        $costs = [];
        foreach ($models as $data) {
            $result = $this->service->calculateAnalysisCost($data['model_id'], 2000, 'standard');
            $costs[$data['model_id']] = $result['total_cost'];
        }

        // Verify ordering
        $this->assertLessThan($costs['standard/model'], $costs['economy/model']);
        $this->assertLessThan($costs['premium/model'], $costs['standard/model']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * INT-022: Handle missing model gracefully
     */
    public function test_handle_missing_model_gracefully(): void
    {
        $pricing = $this->service->getModelPricing('nonexistent/model');
        $this->assertNull($pricing);

        $result = $this->service->getModelByProviderId('nonexistent', 'model');
        $this->assertNull($result);

        $cost = $this->service->calculateAnalysisCost('nonexistent/model', 1000);
        $this->assertEquals(0, $cost['total_cost']);
    }

    /**
     * INT-023: Handle invalid input gracefully
     */
    public function test_handle_invalid_input_gracefully(): void
    {
        $result = $this->service->getModelById(-1);
        $this->assertNull($result);

        $result = $this->service->getModelById(0);
        $this->assertNull($result);

        $result = $this->service->getModelByProviderId('', '');
        $this->assertNull($result);
    }

    // =========================================================================
    // PERFORMANCE BENCHMARK TESTS
    // =========================================================================

    /**
     * INT-024: Bulk model retrieval performance
     */
    public function test_bulk_model_retrieval_performance(): void
    {
        AIModel::factory()->count(100)->create(['is_active' => true]);

        $start = microtime(true);
        $models = $this->service->getAvailableModels();
        $time = microtime(true) - $start;

        $this->assertCount(100, $models);
        $this->assertLessThan(0.1, $time, 'Bulk retrieval should complete in < 100ms');
    }

    /**
     * INT-025: Repeated cost calculations performance
     */
    public function test_repeated_cost_calculations_performance(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'perf/model',
            'display_name' => 'Performance Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->service->calculateAnalysisCost('perf/model', 2000, 'standard');
        }
        $time = microtime(true) - $start;

        $this->assertLessThan(1.0, $time, '100 cost calculations should complete in < 1s');
    }
}
