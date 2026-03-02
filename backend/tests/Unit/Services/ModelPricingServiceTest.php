<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ModelPricingService;
use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-26-16: ModelPricingService
 *
 * Tests the ModelPricingService responsible for:
 * - Managing AI model data and pricing
 * - Calculating analysis costs based on tokens
 * - Syncing models from OpenRouter API
 * - User default model preferences
 * - Model caching
 *
 * @see \App\Services\ModelPricingService
 */
class ModelPricingServiceTest extends TestCase
{
    protected ModelPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ModelPricingService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // MODEL LISTING TESTS (ML)
    // =========================================================================

    /**
     * ML-001: getAvailableModels returns collection of active models
     */
    public function test_get_available_models_returns_active_models(): void
    {
        // Arrange: Create mock models
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'is_active' => true,
        ]);
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'anthropic/claude-3',
            'display_name' => 'Claude 3',
            'is_active' => true,
        ]);
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);

        // Act
        $models = $this->service->getAvailableModels();

        // Assert
        $this->assertInstanceOf(Collection::class, $models);
        $this->assertEquals(2, $models->count());
        $this->assertTrue($models->every(fn($m) => $m->is_active === true));
    }

    /**
     * ML-002: getAvailableModels returns empty collection when no models
     */
    public function test_get_available_models_returns_empty_when_no_models(): void
    {
        // Arrange: Clear all models
        AIModel::query()->delete();

        // Act
        $models = $this->service->getAvailableModels();

        // Assert
        $this->assertInstanceOf(Collection::class, $models);
        $this->assertEquals(0, $models->count());
    }

    /**
     * ML-003: getAvailableModels caches results for 1 hour
     */
    public function test_get_available_models_caches_results(): void
    {
        // Arrange
        AIModel::factory()->create(['is_active' => true]);
        Cache::flush();

        // Act - First call
        $models1 = $this->service->getAvailableModels();

        // Assert - Cache key exists
        $this->assertTrue(Cache::has('ai_models_available'));

        // Act - Second call should use cache
        $models2 = $this->service->getAvailableModels();

        $this->assertEquals($models1, $models2);
    }

    /**
     * ML-004: getAvailableModels cache invalidated after sync
     */
    public function test_get_available_models_cache_invalidated_after_sync(): void
    {
        // Arrange
        AIModel::factory()->create(['is_active' => true]);
        Cache::flush();

        // Prime cache
        $this->service->getAvailableModels();
        $this->assertTrue(Cache::has('ai_models_available'));

        // Act - Sync invalidates cache
        $this->service->invalidateModelCache();

        // Assert
        $this->assertFalse(Cache::has('ai_models_available'));
    }

    // =========================================================================
    // MODEL LOOKUP TESTS (MO)
    // =========================================================================

    /**
     * MO-001: getModelById returns model when exists
     */
    public function test_get_model_by_id_returns_model_when_exists(): void
    {
        // Arrange
        $model = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
        ]);

        // Act
        $result = $this->service->getModelById($model->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($model->id, $result->id);
        $this->assertEquals('GPT-4 Turbo', $result->display_name);
    }

    /**
     * MO-002: getModelById returns null when not found
     */
    public function test_get_model_by_id_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->service->getModelById(99999);

        // Assert
        $this->assertNull($result);
    }

    /**
     * MO-003: getModelByProviderId returns model when exists
     */
    public function test_get_model_by_provider_id_returns_model(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
        ]);

        // Act
        $result = $this->service->getModelByProviderId('openrouter', 'openai/gpt-4-turbo');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('openrouter', $result->provider);
        $this->assertEquals('openai/gpt-4-turbo', $result->model_id);
    }

    /**
     * MO-004: getModelByProviderId returns null for wrong provider
     */
    public function test_get_model_by_provider_id_returns_null_wrong_provider(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
        ]);

        // Act
        $result = $this->service->getModelByProviderId('openai', 'openai/gpt-4-turbo');

        // Assert
        $this->assertNull($result);
    }

    /**
     * MO-005: getModelByProviderId returns null for wrong model_id
     */
    public function test_get_model_by_provider_id_returns_null_wrong_model_id(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
        ]);

        // Act
        $result = $this->service->getModelByProviderId('openrouter', 'nonexistent-model');

        // Assert
        $this->assertNull($result);
    }

    /**
     * MO-006: getModelByProviderId ignores inactive models
     */
    public function test_get_model_by_provider_id_ignores_inactive_models(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'is_active' => false,
        ]);

        // Act
        $result = $this->service->getModelByProviderId('openrouter', 'inactive/model');

        // Assert
        $this->assertNull($result);
    }

    // =========================================================================
    // PRICING CALCULATION TESTS (PC)
    // =========================================================================

    /**
     * PC-001: getModelPricing returns pricing breakdown
     */
    public function test_get_model_pricing_returns_breakdown(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Act
        $pricing = $this->service->getModelPricing('test-model');

        // Assert
        $this->assertIsArray($pricing);
        $this->assertArrayHasKey('input_per_1k', $pricing);
        $this->assertArrayHasKey('output_per_1k', $pricing);
        $this->assertArrayHasKey('input_per_1m', $pricing);
        $this->assertArrayHasKey('output_per_1m', $pricing);
    }

    /**
     * PC-002: getModelPricing calculates per-million costs correctly
     */
    public function test_get_model_pricing_calculates_per_million(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Act
        $pricing = $this->service->getModelPricing('test-model');

        // Assert: per 1M = per 1K * 1000
        $this->assertEquals(10.0, $pricing['input_per_1m']);
        $this->assertEquals(30.0, $pricing['output_per_1m']);
    }

    /**
     * PC-003: getModelPricing returns null for non-existent model
     */
    public function test_get_model_pricing_returns_null_for_nonexistent(): void
    {
        // Act
        $pricing = $this->service->getModelPricing('nonexistent-model');

        // Assert
        $this->assertNull($pricing);
    }

    /**
     * PC-004: calculateAnalysisCost calculates correctly
     */
    public function test_calculate_analysis_cost_calculates_correctly(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01, // $10 per 1M input
            'cost_per_1k_output' => 0.03, // $30 per 1M output
            'is_active' => true,
        ]);

        // 1000 input tokens + 500 output tokens
        // Input: (1000 / 1000) * 0.01 = 0.01
        // Output: (500 / 1000) * 0.03 = 0.015
        // Total: 0.025

        // Act
        $cost = $this->service->calculateAnalysisCost('test-model', 1000, 500);

        // Assert
        $this->assertEquals(0.025, $cost);
    }

    /**
     * PC-005: calculateAnalysisCost rounds to 2 decimal places
     */
    public function test_calculate_analysis_cost_rounds_to_two_decimals(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.025,
            'is_active' => true,
        ]);

        // Act - This would produce 0.035 with many decimals
        $cost = $this->service->calculateAnalysisCost('test-model', 1000, 500);

        // Assert - Should be rounded to 2 decimals
        $this->assertEquals(0.04, $cost);
    }

    /**
     * PC-006: calculateAnalysisCost returns 0 for non-existent model
     */
    public function test_calculate_analysis_cost_returns_zero_for_nonexistent(): void
    {
        // Act
        $cost = $this->service->calculateAnalysisCost('nonexistent-model', 1000, 500);

        // Assert
        $this->assertEquals(0.0, $cost);
    }

    /**
     * PC-007: calculateAnalysisCost handles zero tokens
     */
    public function test_calculate_analysis_cost_handles_zero_tokens(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Act
        $cost = $this->service->calculateAnalysisCost('test-model', 0, 0);

        // Assert
        $this->assertEquals(0.0, $cost);
    }

    /**
     * PC-008: calculateAnalysisCost handles free tier model
     */
    public function test_calculate_analysis_cost_handles_free_tier(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'free-model',
            'cost_per_1k_input' => 0.00,
            'cost_per_1k_output' => 0.00,
            'is_free_tier' => true,
            'is_active' => true,
        ]);

        // Act
        $cost = $this->service->calculateAnalysisCost('free-model', 10000, 5000);

        // Assert
        $this->assertEquals(0.0, $cost);
    }

    /**
     * PC-009: calculateAnalysisCost estimates tokens when only total given
     */
    public function test_calculate_analysis_cost_estimates_tokens(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // When only total tokens provided, estimate 70% input / 30% output
        // Total 1000 tokens: 700 input + 300 output
        // Input: 0.7 * 0.01 = 0.007
        // Output: 0.3 * 0.03 = 0.009
        // Total: 0.016

        // Act
        $cost = $this->service->calculateAnalysisCost('test-model', 1000);

        // Assert
        $this->assertEqualsWithDelta(0.016, $cost, 0.001);
    }

    // =========================================================================
    // OPENROUTER SYNC TESTS (OS)
    // =========================================================================

    /**
     * OS-001: syncModelsFromOpenRouter fetches models from API
     */
    public function test_sync_models_from_openrouter_fetches_models(): void
    {
        // Arrange
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
                        'top_provider' => [
                            'context_length' => 128000,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('ai_models', [
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
        ]);
    }

    /**
     * OS-002: syncModelsFromOpenRouter updates existing models
     */
    public function test_sync_models_updates_existing(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'Old Name',
            'cost_per_1k_input' => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'openai/gpt-4-turbo',
                        'name' => 'GPT-4 Turbo Updated',
                        'context_length' => 128000,
                        'pricing' => [
                            'prompt' => '0.00002',
                            'completion' => '0.00004',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo Updated',
            'cost_per_1k_input' => 0.02, // 0.00002 * 1000
            'cost_per_1k_output' => 0.04, // 0.00004 * 1000
        ]);
    }

    /**
     * OS-003: syncModelsFromOpenRouter deactivates missing models
     */
    public function test_sync_models_deactivates_missing(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'old/deprecated-model',
            'is_active' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [], // No models returned
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'old/deprecated-model',
            'is_active' => false,
        ]);
    }

    /**
     * OS-004: syncModelsFromOpenRouter handles API errors gracefully
     */
    public function test_sync_models_handles_api_errors(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        // Act
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertEquals(0, $count);
    }

    /**
     * OS-005: syncModelsFromOpenRouter handles rate limiting
     */
    public function test_sync_models_handles_rate_limiting(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 429, [
                'Retry-After' => '60',
            ]),
        ]);

        // Act
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertEquals(0, $count);
    }

    /**
     * OS-006: syncModelsFromOpenRouter parses context window correctly
     */
    public function test_sync_models_parses_context_window(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 32000,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'context_window' => 32000,
        ]);
    }

    /**
     * OS-007: syncModelsFromOpenRouter parses capabilities
     */
    public function test_sync_models_parses_capabilities(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/vision-model',
                        'name' => 'Vision Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                        'architecture' => [
                            'modality' => 'text+image->text',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert
        $model = AIModel::where('model_id', 'test/vision-model')->first();
        $this->assertNotNull($model);
        $capabilities = json_decode($model->capabilities, true);
        $this->assertTrue($capabilities['vision'] ?? false);
    }

    /**
     * OS-008: syncModelsFromOpenRouter marks free tier models
     */
    public function test_sync_models_marks_free_tier(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-llama/llama-3-8b-instruct:free',
                        'name' => 'Llama 3 8B Free',
                        'context_length' => 8192,
                        'pricing' => [
                            'prompt' => '0',
                            'completion' => '0',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'meta-llama/llama-3-8b-instruct:free',
            'is_free_tier' => true,
        ]);
    }

    /**
     * OS-009: syncModelsFromOpenRouter updates last_synced_at timestamp
     */
    public function test_sync_models_updates_last_synced_at(): void
    {
        // Arrange
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'last_synced_at' => null,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert
        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNotNull($model->last_synced_at);
    }

    /**
     * OS-010: syncModelsFromOpenRouter logs sync operations
     */
    public function test_sync_models_logs_operations(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'OpenRouter sync');
            });

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => []], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Log assertion in arrange
    }

    // =========================================================================
    // USER DEFAULT MODEL TESTS (UD)
    // =========================================================================

    /**
     * UD-001: getUserDefaultModel returns user's default
     */
    public function test_get_user_default_model_returns_user_default(): void
    {
        // Arrange
        $user = User::factory()->create();
        $model = AIModel::factory()->create(['is_active' => true]);
        UserModelPreference::factory()->create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // Act
        $result = $this->service->getUserDefaultModel($user);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($model->id, $result->id);
    }

    /**
     * UD-002: getUserDefaultModel returns null when no preference
     */
    public function test_get_user_default_model_returns_null_no_preference(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->getUserDefaultModel($user);

        // Assert
        $this->assertNull($result);
    }

    /**
     * UD-003: getUserDefaultModel ignores inactive models
     */
    public function test_get_user_default_model_ignores_inactive(): void
    {
        // Arrange
        $user = User::factory()->create();
        $model = AIModel::factory()->create(['is_active' => false]);
        UserModelPreference::factory()->create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // Act
        $result = $this->service->getUserDefaultModel($user);

        // Assert
        $this->assertNull($result);
    }

    /**
     * UD-004: setUserDefaultModel sets preference
     */
    public function test_set_user_default_model_sets_preference(): void
    {
        // Arrange
        $user = User::factory()->create();
        $model = AIModel::factory()->create(['is_active' => true]);

        // Act
        $this->service->setUserDefaultModel($user, $model->id);

        // Assert
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);
    }

    /**
     * UD-005: setUserDefaultModel updates existing preference
     */
    public function test_set_user_default_model_updates_existing(): void
    {
        // Arrange
        $user = User::factory()->create();
        $oldModel = AIModel::factory()->create(['is_active' => true]);
        $newModel = AIModel::factory()->create(['is_active' => true]);

        UserModelPreference::factory()->create([
            'user_id' => $user->id,
            'model_id' => $oldModel->id,
            'is_default' => true,
        ]);

        // Act
        $this->service->setUserDefaultModel($user, $newModel->id);

        // Assert
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $newModel->id,
            'is_default' => true,
        ]);

        // Old preference should no longer be default
        $this->assertDatabaseMissing('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $oldModel->id,
            'is_default' => true,
        ]);
    }

    /**
     * UD-006: setUserDefaultModel validates model is active
     */
    public function test_set_user_default_model_validates_active(): void
    {
        // Arrange
        $user = User::factory()->create();
        $inactiveModel = AIModel::factory()->create(['is_active' => false]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->service->setUserDefaultModel($user, $inactiveModel->id);
    }

    /**
     * UD-007: setUserDefaultModel validates model exists
     */
    public function test_set_user_default_model_validates_exists(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->setUserDefaultModel($user, 99999);
    }

    /**
     * UD-008: getUserDefaultModel caches user preference
     */
    public function test_get_user_default_model_caches_preference(): void
    {
        // Arrange
        $user = User::factory()->create();
        $model = AIModel::factory()->create(['is_active' => true]);
        UserModelPreference::factory()->create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // Act - First call primes cache
        $this->service->getUserDefaultModel($user);

        // Assert - Cache key exists
        $cacheKey = "user_default_model:{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: getModelById prevents SQL injection
     */
    public function test_get_model_by_id_prevents_sql_injection(): void
    {
        // Arrange
        $maliciousId = "1; DROP TABLE ai_models; --";

        // Act - Should not throw
        $result = $this->service->getModelById($maliciousId);

        // Assert - Returns null (not found), no SQL error
        $this->assertNull($result);
    }

    /**
     * SC-002: getModelByProviderId sanitizes input
     */
    public function test_get_model_by_provider_id_sanitizes_input(): void
    {
        // Arrange
        $maliciousProvider = "openrouter'; DROP TABLE ai_models; --";
        $maliciousModelId = "model'; DELETE FROM users; --";

        // Act - Should not throw
        $result = $this->service->getModelByProviderId($maliciousProvider, $maliciousModelId);

        // Assert - Returns null, no SQL error
        $this->assertNull($result);
    }

    /**
     * SC-003: calculateAnalysisCost handles large token counts
     */
    public function test_calculate_analysis_cost_handles_large_tokens(): void
    {
        // Arrange
        AIModel::factory()->create([
            'model_id' => 'test-model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Act - Very large token count
        $cost = $this->service->calculateAnalysisCost('test-model', 1000000, 500000);

        // Assert - Should calculate correctly without overflow
        // Input: (1000000 / 1000) * 0.01 = 10.0
        // Output: (500000 / 1000) * 0.03 = 15.0
        // Total: 25.0
        $this->assertEquals(25.0, $cost);
    }

    /**
     * SC-004: syncModelsFromOpenRouter validates API response
     */
    public function test_sync_models_validates_api_response(): void
    {
        // Arrange - Malformed response
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        // Missing required fields
                        'id' => null,
                        'name' => '',
                    ],
                ],
            ], 200),
        ]);

        // Act - Should handle gracefully
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert - No models synced, no exception
        $this->assertEquals(0, $count);
    }

    /**
     * SC-005: syncModelsFromOpenRouter handles malicious model data
     */
    public function test_sync_models_handles_malicious_data(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => '<script>alert("xss")</script>',
                        'name' => '"><img src=x onerror=alert(1)>',
                        'context_length' => 'javascript:alert(1)',
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act - Should sanitize or reject
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert - Model should be sanitized
        $model = AIModel::where('display_name', 'like', '%script%')->first();
        $this->assertNull($model); // Should not have stored XSS payload
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Handles empty pricing data
     */
    public function test_handles_empty_pricing_data(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [], // Empty pricing
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Should use default 0 pricing
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * EC-002: Handles negative pricing (treats as 0)
     */
    public function test_handles_negative_pricing(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '-0.00001',
                            'completion' => '-0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Negative should become 0
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * EC-003: Handles extremely small pricing values
     */
    public function test_handles_extremely_small_pricing(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.0000000001',
                            'completion' => '0.0000000002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Should store as DECIMAL(10,6)
        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNotNull($model);
        // Very small values become 0.000000 due to column precision
        $this->assertEquals(0.0, (float) $model->cost_per_1k_input);
    }

    /**
     * EC-004: Handles concurrent sync requests
     */
    public function test_handles_concurrent_sync_requests(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act - Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->service->syncModelsFromOpenRouter();
        }

        // Assert - All should succeed without duplicates
        $modelCount = AIModel::where('model_id', 'test/model')->count();
        $this->assertEquals(1, $modelCount); // No duplicates
    }

    /**
     * EC-005: Handles Unicode in model names
     */
    public function test_handles_unicode_in_model_names(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model 测试模型 🤖',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Unicode should be stored correctly
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'display_name' => 'Test Model 测试模型 🤖',
        ]);
    }

    /**
     * EC-006: Handles very long model names
     */
    public function test_handles_very_long_model_names(): void
    {
        // Arrange
        $longName = str_repeat('a', 300);
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => $longName,
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Should truncate to VARCHAR(255)
        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertLessThanOrEqual(255, strlen($model->display_name));
    }

    /**
     * EC-007: Handles network timeout during sync
     */
    public function test_handles_network_timeout_during_sync(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(null, 504),
        ]);

        // Act
        $count = $this->service->syncModelsFromOpenRouter();

        // Assert - Should return 0 without exception
        $this->assertEquals(0, $count);
    }

    /**
     * EC-008: Handles missing pricing object
     */
    public function test_handles_missing_pricing_object(): void
    {
        // Arrange
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        // Missing pricing key entirely
                    ],
                ],
            ], 200),
        ]);

        // Act
        $this->service->syncModelsFromOpenRouter();

        // Assert - Should use default pricing
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }
}
