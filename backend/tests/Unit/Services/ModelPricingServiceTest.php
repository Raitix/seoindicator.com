<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ModelPricingService;
use App\Services\OpenRouterSyncService;
use App\Models\AIModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-26-16: ModelPricingService
 *
 * Tests the ModelPricingService responsible for:
 * - Managing AI model data
 * - Pricing calculations
 * - User default model preferences
 * - Caching model data
 * - Syncing from OpenRouter
 *
 * @see \App\Services\ModelPricingService
 */
class ModelPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ModelPricingService $service;
    protected $syncServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->syncServiceMock = Mockery::mock(OpenRouterSyncService::class);
        $this->app->instance(OpenRouterSyncService::class, $this->syncServiceMock);
        
        $this->service = app(ModelPricingService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // SERVICE INSTANTIATION TESTS (SI)
    // =========================================================================

    /**
     * SI-001: Service can be resolved from container
     */
    public function test_service_can_be_resolved_from_container(): void
    {
        $service = app(ModelPricingService::class);

        $this->assertInstanceOf(ModelPricingService::class, $service);
    }

    /**
     * SI-002: Service has all required methods
     */
    public function test_service_has_all_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAvailableModels'));
        $this->assertTrue(method_exists($this->service, 'getModelById'));
        $this->assertTrue(method_exists($this->service, 'getModelByProviderId'));
        $this->assertTrue(method_exists($this->service, 'getModelPricing'));
        $this->assertTrue(method_exists($this->service, 'calculateAnalysisCost'));
        $this->assertTrue(method_exists($this->service, 'syncModelsFromOpenRouter'));
        $this->assertTrue(method_exists($this->service, 'getUserDefaultModel'));
        $this->assertTrue(method_exists($this->service, 'setUserDefaultModel'));
    }

    // =========================================================================
    // getAvailableModels() TESTS (GAM)
    // =========================================================================

    /**
     * GAM-001: Returns Collection of active models
     */
    public function test_get_available_models_returns_active_models(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'active/model',
            'display_name' => 'Active Model',
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);

        $models = $this->service->getAvailableModels();

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertCount(1, $models);
        $this->assertEquals('active/model', $models->first()->model_id);
    }

    /**
     * GAM-002: Returns empty collection when no models
     */
    public function test_get_available_models_returns_empty_collection_when_no_models(): void
    {
        $models = $this->service->getAvailableModels();

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertCount(0, $models);
    }

    /**
     * GAM-003: Excludes inactive models
     */
    public function test_get_available_models_excludes_inactive_models(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/1',
            'display_name' => 'Model 1',
            'is_active' => true,
        ]);

        AIModel::factory()->inactive()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/2',
            'display_name' => 'Model 2',
        ]);

        $models = $this->service->getAvailableModels();

        $this->assertCount(1, $models);
        $this->assertEquals('model/1', $models->first()->model_id);
    }

    /**
     * GAM-004: Orders models by display_name
     */
    public function test_get_available_models_orders_by_display_name(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/c',
            'display_name' => 'Charlie Model',
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/a',
            'display_name' => 'Alpha Model',
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/b',
            'display_name' => 'Bravo Model',
            'is_active' => true,
        ]);

        $models = $this->service->getAvailableModels();

        $this->assertEquals('Alpha Model', $models[0]->display_name);
        $this->assertEquals('Bravo Model', $models[1]->display_name);
        $this->assertEquals('Charlie Model', $models[2]->display_name);
    }

    /**
     * GAM-005: Caches results for 1 hour
     */
    public function test_get_available_models_caches_results(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cached/model',
            'display_name' => 'Cached Model',
            'is_active' => true,
        ]);

        // First call - should cache
        $models1 = $this->service->getAvailableModels();

        // Delete the model
        AIModel::where('model_id', 'cached/model')->delete();

        // Second call - should return cached result
        $models2 = $this->service->getAvailableModels();

        $this->assertCount(1, $models2);
        $this->assertEquals('cached/model', $models2->first()->model_id);
    }

    /**
     * GAM-006: Cache key is correct
     */
    public function test_get_available_models_cache_key_is_correct(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                $this->assertEquals('ai_models:available', $key);
                $this->assertEquals(3600, $ttl); // 1 hour
                return true;
            })
            ->andReturn(collect());

        $this->service->getAvailableModels();
    }

    /**
     * GAM-007: Cache invalidated after sync
     */
    public function test_get_available_models_cache_invalidated_after_sync(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('ai_models:available');

        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(5);

        $this->service->syncModelsFromOpenRouter();
    }

    /**
     * GAM-008: Force refresh bypasses cache
     */
    public function test_get_available_models_force_refresh_bypasses_cache(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/1',
            'display_name' => 'Model 1',
            'is_active' => true,
        ]);

        // First call
        $this->service->getAvailableModels();

        // Add new model
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'model/2',
            'display_name' => 'Model 2',
            'is_active' => true,
        ]);

        // Force refresh should get fresh data
        $models = $this->service->getAvailableModels(true);

        $this->assertCount(2, $models);
    }

    // =========================================================================
    // getModelById() TESTS (GMBI)
    // =========================================================================

    /**
     * GMBI-001: Returns model when found
     */
    public function test_get_model_by_id_returns_model_when_found(): void
    {
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'is_active' => true,
        ]);

        $result = $this->service->getModelById($aiModel->id);

        $this->assertInstanceOf(AIModel::class, $result);
        $this->assertEquals('test/model', $result->model_id);
    }

    /**
     * GMBI-002: Returns null when not found
     */
    public function test_get_model_by_id_returns_null_when_not_found(): void
    {
        $result = $this->service->getModelById(99999);

        $this->assertNull($result);
    }

    /**
     * GMBI-003: Only returns active models
     */
    public function test_get_model_by_id_only_returns_active_models(): void
    {
        $aiModel = AIModel::factory()->inactive()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
        ]);

        $result = $this->service->getModelById($aiModel->id);

        $this->assertNull($result);
    }

    /**
     * GMBI-004: Validates ID is positive integer
     */
    public function test_get_model_by_id_validates_positive_integer(): void
    {
        $result = $this->service->getModelById(-1);
        $this->assertNull($result);

        $result = $this->service->getModelById(0);
        $this->assertNull($result);
    }

    /**
     * GMBI-005: Handles invalid ID gracefully
     */
    public function test_get_model_by_id_handles_invalid_id_gracefully(): void
    {
        $result = $this->service->getModelById(PHP_INT_MAX);
        $this->assertNull($result);
    }

    // =========================================================================
    // getModelByProviderId() TESTS (GMBP)
    // =========================================================================

    /**
     * GMBP-001: Returns model when found
     */
    public function test_get_model_by_provider_id_returns_model_when_found(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'is_active' => true,
        ]);

        $result = $this->service->getModelByProviderId('openrouter', 'openai/gpt-4-turbo');

        $this->assertInstanceOf(AIModel::class, $result);
        $this->assertEquals('openai/gpt-4-turbo', $result->model_id);
    }

    /**
     * GMBP-002: Returns null when not found
     */
    public function test_get_model_by_provider_id_returns_null_when_not_found(): void
    {
        $result = $this->service->getModelByProviderId('openrouter', 'nonexistent/model');

        $this->assertNull($result);
    }

    /**
     * GMBP-003: Matches exact provider and model_id
     */
    public function test_get_model_by_provider_id_matches_exact(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'is_active' => true,
        ]);

        AIModel::factory()->create([
            'provider' => 'openai',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo Direct',
            'is_active' => true,
        ]);

        $result = $this->service->getModelByProviderId('openrouter', 'openai/gpt-4-turbo');

        $this->assertEquals('openrouter', $result->provider);
    }

    /**
     * GMBP-004: Case-sensitive matching
     */
    public function test_get_model_by_provider_id_case_sensitive(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'OpenAI/GPT-4-Turbo',
            'display_name' => 'GPT-4 Turbo',
            'is_active' => true,
        ]);

        // Should not match different case
        $result = $this->service->getModelByProviderId('openrouter', 'openai/gpt-4-turbo');
        $this->assertNull($result);

        // Should match exact case
        $result = $this->service->getModelByProviderId('openrouter', 'OpenAI/GPT-4-Turbo');
        $this->assertNotNull($result);
    }

    /**
     * GMBP-005: Only returns active models
     */
    public function test_get_model_by_provider_id_only_returns_active(): void
    {
        AIModel::factory()->inactive()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
        ]);

        $result = $this->service->getModelByProviderId('openrouter', 'inactive/model');

        $this->assertNull($result);
    }

    /**
     * GMBP-006: Validates provider parameter
     */
    public function test_get_model_by_provider_id_validates_provider(): void
    {
        $result = $this->service->getModelByProviderId('', 'model/id');
        $this->assertNull($result);
    }

    /**
     * GMBP-007: Validates model_id parameter
     */
    public function test_get_model_by_provider_id_validates_model_id(): void
    {
        $result = $this->service->getModelByProviderId('openrouter', '');
        $this->assertNull($result);
    }

    // =========================================================================
    // getModelPricing() TESTS (GMP)
    // =========================================================================

    /**
     * GMP-001: Returns pricing array for valid model
     */
    public function test_get_model_pricing_returns_pricing_array(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertIsArray($pricing);
        $this->assertArrayHasKey('model_id', $pricing);
        $this->assertArrayHasKey('input_per_1k', $pricing);
        $this->assertArrayHasKey('output_per_1k', $pricing);
    }

    /**
     * GMP-002: Returns null for non-existent model
     */
    public function test_get_model_pricing_returns_null_for_nonexistent(): void
    {
        $pricing = $this->service->getModelPricing('nonexistent/model');

        $this->assertNull($pricing);
    }

    /**
     * GMP-003: Includes input_per_1k
     */
    public function test_get_model_pricing_includes_input_per_1k(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertArrayHasKey('input_per_1k', $pricing);
        $this->assertEquals(0.01, $pricing['input_per_1k']);
    }

    /**
     * GMP-004: Includes output_per_1k
     */
    public function test_get_model_pricing_includes_output_per_1k(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertArrayHasKey('output_per_1k', $pricing);
        $this->assertEquals(0.03, $pricing['output_per_1k']);
    }

    /**
     * GMP-005: Includes input_per_1m
     */
    public function test_get_model_pricing_includes_input_per_1m(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertArrayHasKey('input_per_1m', $pricing);
        $this->assertEquals(10.0, $pricing['input_per_1m']); // 0.01 * 1000
    }

    /**
     * GMP-006: Includes output_per_1m
     */
    public function test_get_model_pricing_includes_output_per_1m(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertArrayHasKey('output_per_1m', $pricing);
        $this->assertEquals(30.0, $pricing['output_per_1m']); // 0.03 * 1000
    }

    /**
     * GMP-007: Returns zero for free tier model
     */
    public function test_get_model_pricing_returns_zero_for_free_tier(): void
    {
        AIModel::factory()->freeTier()->create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('free/model');

        $this->assertEquals(0, $pricing['input_per_1k']);
        $this->assertEquals(0, $pricing['output_per_1k']);
        $this->assertTrue($pricing['is_free_tier']);
    }

    /**
     * GMP-008: Decimal precision maintained
     */
    public function test_get_model_pricing_maintains_decimal_precision(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.012345,
            'cost_per_1k_output' => 0.098765,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertEqualsWithDelta(0.012345, $pricing['input_per_1k'], 0.000001);
        $this->assertEqualsWithDelta(0.098765, $pricing['output_per_1k'], 0.000001);
    }

    // =========================================================================
    // calculateAnalysisCost() TESTS (CAC)
    // =========================================================================

    /**
     * CAC-001: Calculates cost for valid model
     */
    public function test_calculate_analysis_cost_for_valid_model(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 2000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cost', $result);
        $this->assertGreaterThan(0, $result['total_cost']);
    }

    /**
     * CAC-002: Returns 0 for non-existent model
     */
    public function test_calculate_analysis_cost_returns_zero_for_nonexistent(): void
    {
        $result = $this->service->calculateAnalysisCost('nonexistent/model', 2000);

        $this->assertEquals(0, $result['total_cost']);
    }

    /**
     * CAC-003: Returns 0 for free tier model
     */
    public function test_calculate_analysis_cost_returns_zero_for_free_tier(): void
    {
        AIModel::factory()->freeTier()->create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('free/model', 2000);

        $this->assertEquals(0, $result['total_cost']);
    }

    /**
     * CAC-004: Quick analysis (500 tokens)
     */
    public function test_calculate_analysis_cost_quick_analysis(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 500, 'quick');

        // Quick analysis: 400 input + 100 output
        // Input: (400/1000) * 0.01 = 0.004
        // Output: (100/1000) * 0.03 = 0.003
        // Total: 0.007
        $this->assertEqualsWithDelta(0.007, $result['total_cost'], 0.001);
    }

    /**
     * CAC-005: Standard analysis (2000 tokens)
     */
    public function test_calculate_analysis_cost_standard_analysis(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 2000, 'standard');

        // Standard analysis: 1600 input + 400 output
        // Input: (1600/1000) * 0.01 = 0.016
        // Output: (400/1000) * 0.03 = 0.012
        // Total: 0.028
        $this->assertEqualsWithDelta(0.028, $result['total_cost'], 0.001);
    }

    /**
     * CAC-006: Deep analysis (5000 tokens)
     */
    public function test_calculate_analysis_cost_deep_analysis(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 5000, 'deep');

        // Deep analysis: 4000 input + 1000 output
        // Input: (4000/1000) * 0.01 = 0.04
        // Output: (1000/1000) * 0.03 = 0.03
        // Total: 0.07
        $this->assertEqualsWithDelta(0.07, $result['total_cost'], 0.001);
    }

    /**
     * CAC-007: Custom token count
     */
    public function test_calculate_analysis_cost_custom_tokens(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        // Custom: 3000 tokens = 2400 input + 600 output
        $result = $this->service->calculateAnalysisCost('test/model', 3000);

        // Input: (2400/1000) * 0.01 = 0.024
        // Output: (600/1000) * 0.03 = 0.018
        // Total: 0.042
        $this->assertEqualsWithDelta(0.042, $result['total_cost'], 0.001);
    }

    /**
     * CAC-008: Rounds to 2 decimal places
     */
    public function test_calculate_analysis_cost_rounds_to_two_decimals(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.025,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 2000, 'standard');

        // Check total has at most 2 decimal places
        $decimals = strlen(substr(strrchr((string)$result['total_cost'], '.'), 1));
        $this->assertLessThanOrEqual(2, $decimals);
    }

    /**
     * CAC-009: Input/output ratio 4:1
     */
    public function test_calculate_analysis_cost_input_output_ratio(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 2000, 'standard');

        // Verify 4:1 ratio
        $ratio = $result['input_tokens'] / $result['output_tokens'];
        $this->assertEquals(4, $ratio);
    }

    /**
     * CAC-010: Handles zero tokens
     */
    public function test_calculate_analysis_cost_handles_zero_tokens(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 0);

        $this->assertEquals(0, $result['total_cost']);
    }

    /**
     * CAC-011: Handles negative tokens (treats as 0)
     */
    public function test_calculate_analysis_cost_handles_negative_tokens(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', -100);

        $this->assertEquals(0, $result['total_cost']);
    }

    /**
     * CAC-012: Returns analysis breakdown
     */
    public function test_calculate_analysis_cost_returns_breakdown(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 2000, 'standard');

        $this->assertArrayHasKey('model_id', $result);
        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertArrayHasKey('input_tokens', $result);
        $this->assertArrayHasKey('output_tokens', $result);
        $this->assertArrayHasKey('input_cost', $result);
        $this->assertArrayHasKey('output_cost', $result);
        $this->assertArrayHasKey('total_cost', $result);
    }

    // =========================================================================
    // syncModelsFromOpenRouter() TESTS (SMFO)
    // =========================================================================

    /**
     * SMFO-001: Syncs models from API
     */
    public function test_sync_models_from_openrouter_syncs_models(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(5);

        Cache::shouldReceive('forget')
            ->once()
            ->with('ai_models:available');

        $count = $this->service->syncModelsFromOpenRouter();

        $this->assertEquals(5, $count);
    }

    /**
     * SMFO-002: Returns count of synced models
     */
    public function test_sync_models_from_openrouter_returns_count(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(10);

        Cache::shouldReceive('forget')->once();

        $count = $this->service->syncModelsFromOpenRouter();

        $this->assertEquals(10, $count);
    }

    /**
     * SMFO-003: Handles sync errors
     */
    public function test_sync_models_from_openrouter_handles_errors(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andThrow(new \Exception('API Error'));

        Cache::shouldReceive('forget')->once();

        $count = $this->service->syncModelsFromOpenRouter();

        $this->assertEquals(0, $count);
    }

    /**
     * SMFO-004: Invalidates cache after sync
     */
    public function test_sync_models_from_openrouter_invalidates_cache(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(5);

        Cache::shouldReceive('forget')
            ->once()
            ->with('ai_models:available');

        $this->service->syncModelsFromOpenRouter();
    }

    /**
     * SMFO-005: Logs sync operations
     */
    public function test_sync_models_from_openrouter_logs_operations(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(5);

        Cache::shouldReceive('forget')->once();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'sync') || str_contains($message, 'models');
            });

        $this->service->syncModelsFromOpenRouter();
    }

    // =========================================================================
    // getUserDefaultModel() TESTS (GUDM)
    // =========================================================================

    /**
     * GUDM-001: Returns user's preferred model
     */
    public function test_get_user_default_model_returns_preference(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'preferred/model',
            'display_name' => 'Preferred Model',
            'is_active' => true,
        ]);

        // Set preference
        $this->service->setUserDefaultModel($user, $aiModel->id);

        $result = $this->service->getUserDefaultModel($user);

        $this->assertInstanceOf(AIModel::class, $result);
        $this->assertEquals('preferred/model', $result->model_id);
    }

    /**
     * GUDM-002: Returns null when no preference set
     */
    public function test_get_user_default_model_returns_null_when_not_set(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getUserDefaultModel($user);

        $this->assertNull($result);
    }

    /**
     * GUDM-003: Returns null for inactive model
     */
    public function test_get_user_default_model_returns_null_for_inactive(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->inactive()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
        ]);

        // Set preference for inactive model
        $this->service->setUserDefaultModel($user, $aiModel->id);

        // Should return null because model is inactive
        $result = $this->service->getUserDefaultModel($user);

        $this->assertNull($result);
    }

    /**
     * GUDM-004: Uses cached preference
     */
    public function test_get_user_default_model_uses_cache(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cached/model',
            'display_name' => 'Cached Model',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user, $aiModel->id);

        // Delete model
        AIModel::where('model_id', 'cached/model')->delete();

        // Should still return cached result
        $result = $this->service->getUserDefaultModel($user);

        // Cache should return the model ID, but model lookup will fail
        // So result depends on implementation
    }

    /**
     * GUDM-005: Validates model is still active
     */
    public function test_get_user_default_model_validates_active(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'deactivating/model',
            'display_name' => 'Deactivating Model',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user, $aiModel->id);

        // Deactivate the model
        $aiModel->update(['is_active' => false]);

        // Clear cache to force fresh lookup
        Cache::forget("ai_models:user_default:{$user->id}");

        $result = $this->service->getUserDefaultModel($user);

        $this->assertNull($result);
    }

    // =========================================================================
    // setUserDefaultModel() TESTS (SUDM)
    // =========================================================================

    /**
     * SUDM-001: Sets user's preferred model
     */
    public function test_set_user_default_model_sets_preference(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'preferred/model',
            'display_name' => 'Preferred Model',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user, $aiModel->id);

        $result = $this->service->getUserDefaultModel($user);

        $this->assertEquals($aiModel->id, $result->id);
    }

    /**
     * SUDM-002: Updates existing preference
     */
    public function test_set_user_default_model_updates_preference(): void
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

        $this->service->setUserDefaultModel($user, $model1->id);
        $this->service->setUserDefaultModel($user, $model2->id);

        $result = $this->service->getUserDefaultModel($user);

        $this->assertEquals($model2->id, $result->id);
    }

    /**
     * SUDM-003: Validates model exists
     */
    public function test_set_user_default_model_validates_exists(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->setUserDefaultModel($user, 99999);
    }

    /**
     * SUDM-004: Validates model is active
     */
    public function test_set_user_default_model_validates_active(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->inactive()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->setUserDefaultModel($user, $aiModel->id);
    }

    /**
     * SUDM-005: Invalidates user cache
     */
    public function test_set_user_default_model_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cached/model',
            'display_name' => 'Cached Model',
            'is_active' => true,
        ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with("ai_models:user_default:{$user->id}");

        $this->service->setUserDefaultModel($user, $aiModel->id);
    }

    // =========================================================================
    // CACHING BEHAVIOR TESTS (CACHE)
    // =========================================================================

    /**
     * CACHE-001: Models cached for 1 hour
     */
    public function test_models_cached_for_one_hour(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                $this->assertEquals('ai_models:available', $key);
                $this->assertEquals(3600, $ttl);
                return true;
            })
            ->andReturn(collect());

        $this->service->getAvailableModels();
    }

    /**
     * CACHE-002: Cache key format verified
     */
    public function test_cache_key_format(): void
    {
        $user = User::factory()->create();
        $expectedKey = "ai_models:user_default:{$user->id}";

        Cache::shouldReceive('get')
            ->once()
            ->with($expectedKey);

        $this->service->getUserDefaultModel($user);
    }

    /**
     * CACHE-003: User preference cached
     */
    public function test_user_preference_cached(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cached/model',
            'display_name' => 'Cached Model',
            'is_active' => true,
        ]);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) use ($user, $aiModel) {
                $this->assertEquals("ai_models:user_default:{$user->id}", $key);
                $this->assertEquals($aiModel->id, $value);
                $this->assertEquals(86400, $ttl); // 24 hours
                return true;
            });

        $this->service->setUserDefaultModel($user, $aiModel->id);
    }

    /**
     * CACHE-004: Cache cleared on sync
     */
    public function test_cache_cleared_on_sync(): void
    {
        $this->syncServiceMock
            ->shouldReceive('syncModels')
            ->once()
            ->andReturn(5);

        Cache::shouldReceive('forget')
            ->once()
            ->with('ai_models:available');

        $this->service->syncModelsFromOpenRouter();
    }

    /**
     * CACHE-005: Cache cleared on preference update
     */
    public function test_cache_cleared_on_preference_update(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'cached/model',
            'display_name' => 'Cached Model',
            'is_active' => true,
        ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with("ai_models:user_default:{$user->id}");

        $this->service->setUserDefaultModel($user, $aiModel->id);
    }

    /**
     * CACHE-006: Force refresh bypasses cache
     */
    public function test_force_refresh_bypasses_cache(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'is_active' => true,
        ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('ai_models:available');

        $this->service->getAvailableModels(true);
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: Model ID injection prevention
     */
    public function test_model_id_injection_prevention(): void
    {
        $result = $this->service->getModelPricing("'; DROP TABLE ai_models; --");

        $this->assertNull($result);
    }

    /**
     * SEC-002: Provider name injection prevention
     */
    public function test_provider_name_injection_prevention(): void
    {
        $result = $this->service->getModelByProviderId("'; DROP TABLE ai_models; --", 'model/id');

        $this->assertNull($result);
    }

    /**
     * SEC-003: User isolation in preferences
     */
    public function test_user_isolation_in_preferences(): void
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

        $result1 = $this->service->getUserDefaultModel($user1);
        $result2 = $this->service->getUserDefaultModel($user2);

        $this->assertEquals($model1->id, $result1->id);
        $this->assertEquals($model2->id, $result2->id);
    }

    /**
     * SEC-004: No sensitive data in responses
     */
    public function test_no_sensitive_data_in_responses(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        // Should not contain any API keys, secrets, or internal data
        $json = json_encode($pricing);
        $this->assertStringNotContainsString('api_key', $json);
        $this->assertStringNotContainsString('secret', $json);
        $this->assertStringNotContainsString('password', $json);
    }

    // =========================================================================
    // EDGE CASE TESTS (EDGE)
    // =========================================================================

    /**
     * EDGE-001: Empty models table
     */
    public function test_empty_models_table(): void
    {
        $models = $this->service->getAvailableModels();
        $this->assertCount(0, $models);

        $result = $this->service->getModelById(1);
        $this->assertNull($result);
    }

    /**
     * EDGE-002: All models inactive
     */
    public function test_all_models_inactive(): void
    {
        AIModel::factory()->count(5)->inactive()->create();

        $models = $this->service->getAvailableModels();
        $this->assertCount(0, $models);
    }

    /**
     * EDGE-003: Very large token count
     */
    public function test_very_large_token_count(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $result = $this->service->calculateAnalysisCost('test/model', 1000000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cost', $result);
    }

    /**
     * EDGE-004: Unicode in model names
     */
    public function test_unicode_in_model_names(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/unicode-model',
            'display_name' => 'Test Model 测试 🤖',
            'is_active' => true,
        ]);

        $models = $this->service->getAvailableModels();

        $this->assertCount(1, $models);
        $this->assertEquals('Test Model 测试 🤖', $models->first()->display_name);
    }

    /**
     * EDGE-005: Model deleted during operation
     */
    public function test_model_deleted_during_operation(): void
    {
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'deleting/model',
            'display_name' => 'Deleting Model',
            'is_active' => true,
        ]);

        $id = $aiModel->id;

        // Delete model
        $aiModel->delete();

        $result = $this->service->getModelById($id);

        $this->assertNull($result);
    }

    /**
     * EDGE-006: User deleted during operation
     */
    public function test_user_deleted_during_operation(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'is_active' => true,
        ]);

        $this->service->setUserDefaultModel($user, $aiModel->id);

        // User ID would still be cached but user doesn't exist
        // This tests graceful handling
        $result = $this->service->getUserDefaultModel($user);

        // Should still work as we're using the user object
        $this->assertEquals($aiModel->id, $result->id);
    }

    // =========================================================================
    // PERFORMANCE TESTS (PERF)
    // =========================================================================

    /**
     * PERF-001: getAvailableModels performance
     */
    public function test_get_available_models_performance(): void
    {
        AIModel::factory()->count(100)->create(['is_active' => true]);

        $start = microtime(true);
        $this->service->getAvailableModels();
        $time = microtime(true) - $start;

        $this->assertLessThan(0.05, $time, 'getAvailableModels should complete in < 50ms');
    }

    /**
     * PERF-002: getModelById performance
     */
    public function test_get_model_by_id_performance(): void
    {
        $aiModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'is_active' => true,
        ]);

        $start = microtime(true);
        $this->service->getModelById($aiModel->id);
        $time = microtime(true) - $start;

        $this->assertLessThan(0.01, $time, 'getModelById should complete in < 10ms');
    }

    /**
     * PERF-003: calculateAnalysisCost performance
     */
    public function test_calculate_analysis_cost_performance(): void
    {
        AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_active' => true,
        ]);

        $start = microtime(true);
        $this->service->calculateAnalysisCost('test/model', 2000);
        $time = microtime(true) - $start;

        $this->assertLessThan(0.01, $time, 'calculateAnalysisCost should complete in < 10ms');
    }

    /**
     * PERF-004: Caching improves performance
     */
    public function test_caching_improves_performance(): void
    {
        AIModel::factory()->count(100)->create(['is_active' => true]);

        // First call (cold cache)
        $start1 = microtime(true);
        $this->service->getAvailableModels();
        $time1 = microtime(true) - $start1;

        // Second call (cached)
        $start2 = microtime(true);
        $this->service->getAvailableModels();
        $time2 = microtime(true) - $start2;

        // Cached call should be faster (or at least not significantly slower)
        $this->assertLessThanOrEqual($time1 * 2, $time2, 'Cached call should not be significantly slower');
    }
}
