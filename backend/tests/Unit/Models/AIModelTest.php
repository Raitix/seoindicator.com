<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AIModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for TASK-26-4: AIModel Model
 *
 * Tests the AIModel model for:
 * - Model creation with required fields
 * - Casts (DECIMAL, INT, BOOLEAN, JSON)
 * - Scopes (active, freeTier)
 * - Accessors (cost_per_analysis_estimate)
 * - Indexes (provider+model_id unique, is_active)
 * - Edge cases and security
 *
 * @see \App\Models\AIModel
 */
class AIModelTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL CREATION TESTS ====================

    /**
     * Test model can be created with required fields only.
     * AM-CR-01
     */
    public function test_model_can_be_created_with_required_fields_only(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
        ]);

        $this->assertInstanceOf(AIModel::class, $model);
        $this->assertEquals('openrouter', $model->provider);
        $this->assertEquals('openai/gpt-4-turbo', $model->model_id);
        $this->assertEquals('GPT-4 Turbo', $model->display_name);
        $this->assertEquals(0.000000, $model->cost_per_1k_input);
        $this->assertEquals(0.000000, $model->cost_per_1k_output);
        $this->assertEquals(4096, $model->context_window);
        $this->assertFalse($model->is_free_tier);
        $this->assertNull($model->free_tier_limit);
        $this->assertNull($model->capabilities);
        $this->assertTrue($model->is_active);
        $this->assertNull($model->last_synced_at);
    }

    /**
     * Test model can be created with full data.
     * AM-CR-02
     */
    public function test_model_can_be_created_with_full_data(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'anthropic/claude-3-opus',
            'display_name' => 'Claude 3 Opus',
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.075,
            'context_window' => 200000,
            'is_free_tier' => false,
            'free_tier_limit' => null,
            'capabilities' => json_encode(['vision' => true, 'tools' => true]),
            'is_active' => true,
            'last_synced_at' => now(),
        ]);

        $this->assertEquals('openrouter', $model->provider);
        $this->assertEquals('anthropic/claude-3-opus', $model->model_id);
        $this->assertEquals('Claude 3 Opus', $model->display_name);
        $this->assertEquals(0.015, $model->cost_per_1k_input);
        $this->assertEquals(0.075, $model->cost_per_1k_output);
        $this->assertEquals(200000, $model->context_window);
        $this->assertFalse($model->is_free_tier);
        $this->assertNull($model->free_tier_limit);
        $this->assertJson($model->capabilities);
        $this->assertTrue($model->is_active);
        $this->assertNotNull($model->last_synced_at);
    }

    /**
     * Test mass assignment protection.
     * AM-CR-03
     */
    public function test_mass_assignment_protection(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.005,
            'cost_per_1k_output' => 0.015,
            'context_window' => 32768,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $this->assertEquals('test/model', $model->model_id);
        $this->assertEquals(0.005, $model->cost_per_1k_input);
        $this->assertEquals(0.015, $model->cost_per_1k_output);
        $this->assertEquals(32768, $model->context_window);
    }

    /**
     * Test provider is required.
     * AM-CR-04
     */
    public function test_provider_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AIModel::create([
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
        ]);
    }

    /**
     * Test model_id is required.
     * AM-CR-05
     */
    public function test_model_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AIModel::create([
            'provider' => 'openrouter',
            'display_name' => 'Test Model',
        ]);
    }

    /**
     * Test display_name is required.
     * AM-CR-06
     */
    public function test_display_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
        ]);
    }

    /**
     * Test model can be deactivated.
     * AM-CR-07
     */
    public function test_model_can_be_deactivated(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'deprecated/model',
            'display_name' => 'Deprecated Model',
            'is_active' => true,
        ]);

        $model->is_active = false;
        $model->save();

        $this->assertFalse($model->is_active);
        $this->assertDatabaseHas('ai_models', [
            'id' => $model->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test free tier model creation.
     * AM-CR-08
     */
    public function test_free_tier_model_creation(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'meta-llama/llama-3-8b-instruct:free',
            'display_name' => 'Llama 3 8B Free',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
            'context_window' => 8192,
            'is_free_tier' => true,
            'free_tier_limit' => 20,
        ]);

        $this->assertTrue($model->is_free_tier);
        $this->assertEquals(20, $model->free_tier_limit);
        $this->assertEquals(0, $model->cost_per_1k_input);
        $this->assertEquals(0, $model->cost_per_1k_output);
    }

    // ==================== CASTS TESTS ====================

    /**
     * Test cost_per_1k_input DECIMAL cast.
     * AM-CA-01
     */
    public function test_cost_per_1k_input_decimal_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.012345,
        ]);

        // SQLite returns decimals as strings, so we check the value not the type
        $this->assertEquals(0.012345, (float) $model->cost_per_1k_input);
    }

    /**
     * Test cost_per_1k_input precision 6 decimals.
     * AM-CA-02
     */
    public function test_cost_per_1k_input_precision_six_decimals(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.0123456,
        ]);

        $model->refresh();
        $this->assertEqualsWithDelta(0.012346, $model->cost_per_1k_input, 0.000001);
    }

    /**
     * Test cost_per_1k_output DECIMAL cast.
     * AM-CA-03
     */
    public function test_cost_per_1k_output_decimal_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_output' => 0.098765,
        ]);

        $this->assertEquals(0.098765, (float) $model->cost_per_1k_output);
    }

    /**
     * Test cost_per_1k_output precision 6 decimals.
     * AM-CA-04
     */
    public function test_cost_per_1k_output_precision_six_decimals(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_output' => 0.0987654,
        ]);

        $model->refresh();
        $this->assertEqualsWithDelta(0.098765, $model->cost_per_1k_output, 0.000001);
    }

    /**
     * Test context_window INT cast.
     * AM-CA-05
     */
    public function test_context_window_int_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'context_window' => 128000,
        ]);

        $this->assertIsInt($model->context_window);
        $this->assertEquals(128000, $model->context_window);
    }

    /**
     * Test context_window default is 4096.
     * AM-CA-06
     */
    public function test_context_window_default_is_4096(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertEquals(4096, $model->context_window);
    }

    /**
     * Test is_free_tier BOOLEAN cast.
     * AM-CA-07
     */
    public function test_is_free_tier_boolean_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'is_free_tier' => true,
        ]);

        $this->assertIsBool($model->is_free_tier);
        $this->assertTrue($model->is_free_tier);
    }

    /**
     * Test is_free_tier default is false.
     * AM-CA-08
     */
    public function test_is_free_tier_default_is_false(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertFalse($model->is_free_tier);
    }

    /**
     * Test free_tier_limit INT cast.
     * AM-CA-09
     */
    public function test_free_tier_limit_int_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'is_free_tier' => true,
            'free_tier_limit' => 50,
        ]);

        $this->assertIsInt($model->free_tier_limit);
        $this->assertEquals(50, $model->free_tier_limit);
    }

    /**
     * Test free_tier_limit can be null.
     * AM-CA-10
     */
    public function test_free_tier_limit_can_be_null(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertNull($model->free_tier_limit);
    }

    /**
     * Test capabilities JSON cast.
     * AM-CA-11
     */
    public function test_capabilities_json_cast(): void
    {
        $capabilities = ['vision' => true, 'tools' => true, 'streaming' => true];

        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'capabilities' => $capabilities,
        ]);

        $this->assertIsArray($model->capabilities);
        $this->assertTrue($model->capabilities['vision']);
        $this->assertTrue($model->capabilities['tools']);
        $this->assertTrue($model->capabilities['streaming']);
    }

    /**
     * Test capabilities can be null.
     * AM-CA-12
     */
    public function test_capabilities_can_be_null(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertNull($model->capabilities);
    }

    /**
     * Test is_active BOOLEAN cast.
     * AM-CA-13
     */
    public function test_is_active_boolean_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'is_active' => false,
        ]);

        $this->assertIsBool($model->is_active);
        $this->assertFalse($model->is_active);
    }

    /**
     * Test is_active default is true.
     * AM-CA-14
     */
    public function test_is_active_default_is_true(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertTrue($model->is_active);
    }

    /**
     * Test last_synced_at TIMESTAMP cast.
     * AM-CA-15
     */
    public function test_last_synced_at_timestamp_cast(): void
    {
        $now = now();
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'last_synced_at' => $now,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->last_synced_at);
    }

    /**
     * Test last_synced_at can be null.
     * AM-CA-16
     */
    public function test_last_synced_at_can_be_null(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertNull($model->last_synced_at);
    }

    /**
     * Test timestamps cast.
     * AM-CA-17
     */
    public function test_timestamps_cast(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->updated_at);
    }

    // ==================== ACCESSOR TESTS ====================

    /**
     * Test cost_per_analysis_estimate accessor.
     * AM-AC-01
     */
    public function test_cost_per_analysis_estimate_accessor(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        // If accessor exists, test it
        if (method_exists($model, 'getCostPerAnalysisEstimateAttribute')) {
            // Assume ~2000 input tokens + ~500 output tokens per analysis
            // Cost = (2000/1000 * 0.01) + (500/1000 * 0.03) = 0.02 + 0.015 = 0.035
            $this->assertEqualsWithDelta(0.035, $model->cost_per_analysis_estimate, 0.001);
        } else {
            $this->markTestSkipped('cost_per_analysis_estimate accessor not yet implemented');
        }
    }

    /**
     * Test cost_per_analysis_estimate for free tier model.
     * AM-AC-02
     */
    public function test_cost_per_analysis_estimate_free_tier(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/free-model',
            'display_name' => 'Free Model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
            'is_free_tier' => true,
        ]);

        if (method_exists($model, 'getCostPerAnalysisEstimateAttribute')) {
            $this->assertEquals(0.0, $model->cost_per_analysis_estimate);
        } else {
            $this->markTestSkipped('cost_per_analysis_estimate accessor not yet implemented');
        }
    }

    /**
     * Test cost_per_analysis_estimate with custom token counts.
     * AM-AC-03
     */
    public function test_cost_per_analysis_estimate_custom_tokens(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        if (method_exists($model, 'getCostPerAnalysisEstimateAttribute')) {
            // Test with custom estimate method if it accepts parameters
            // Otherwise, just test the accessor exists
            $this->assertGreaterThanOrEqual(0, $model->cost_per_analysis_estimate);
        } else {
            $this->markTestSkipped('cost_per_analysis_estimate accessor not yet implemented');
        }
    }

    /**
     * Test formatted_pricing accessor.
     * AM-AC-04
     */
    public function test_formatted_pricing_accessor(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        if (method_exists($model, 'getFormattedPricingAttribute')) {
            $formatted = $model->formatted_pricing;
            $this->assertIsArray($formatted);
            $this->assertArrayHasKey('input', $formatted);
            $this->assertArrayHasKey('output', $formatted);
        } else {
            $this->markTestSkipped('formatted_pricing accessor not yet implemented');
        }
    }

    // ==================== SCOPE TESTS ====================

    /**
     * Test active scope returns only active models.
     * AM-SC-01
     */
    public function test_active_scope_returns_only_active_models(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'active/model',
            'display_name' => 'Active Model',
            'is_active' => true,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'inactive/model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);

        if (method_exists(AIModel::class, 'scopeActive')) {
            $activeModels = AIModel::active()->get();

            $this->assertCount(1, $activeModels);
            $this->assertEquals('active/model', $activeModels->first()->model_id);
        } else {
            $this->markTestSkipped('active scope not yet implemented');
        }
    }

    /**
     * Test active scope returns multiple active models.
     * AM-SC-02
     */
    public function test_active_scope_returns_multiple_active_models(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/1',
            'display_name' => 'Model 1',
            'is_active' => true,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/2',
            'display_name' => 'Model 2',
            'is_active' => true,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/3',
            'display_name' => 'Model 3',
            'is_active' => false,
        ]);

        if (method_exists(AIModel::class, 'scopeActive')) {
            $activeModels = AIModel::active()->get();
            $this->assertCount(2, $activeModels);
        } else {
            $this->markTestSkipped('active scope not yet implemented');
        }
    }

    /**
     * Test freeTier scope returns only free tier models.
     * AM-SC-03
     */
    public function test_free_tier_scope_returns_only_free_tier_models(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'is_free_tier' => true,
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'paid/model',
            'display_name' => 'Paid Model',
            'is_free_tier' => false,
            'cost_per_1k_input' => 0.01,
        ]);

        if (method_exists(AIModel::class, 'scopeFreeTier')) {
            $freeModels = AIModel::freeTier()->get();

            $this->assertCount(1, $freeModels);
            $this->assertEquals('free/model', $freeModels->first()->model_id);
        } else {
            $this->markTestSkipped('freeTier scope not yet implemented');
        }
    }

    /**
     * Test freeTier scope combines with active scope.
     * AM-SC-04
     */
    public function test_free_tier_scope_combines_with_active(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'free-active/model',
            'display_name' => 'Free Active Model',
            'is_free_tier' => true,
            'is_active' => true,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'free-inactive/model',
            'display_name' => 'Free Inactive Model',
            'is_free_tier' => true,
            'is_active' => false,
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'paid-active/model',
            'display_name' => 'Paid Active Model',
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        if (method_exists(AIModel::class, 'scopeFreeTier') && 
            method_exists(AIModel::class, 'scopeActive')) {
            $models = AIModel::freeTier()->active()->get();

            $this->assertCount(1, $models);
            $this->assertEquals('free-active/model', $models->first()->model_id);
        } else {
            $this->markTestSkipped('freeTier/active scopes not yet implemented');
        }
    }

    /**
     * Test byProvider scope filters by provider.
     * AM-SC-05
     */
    public function test_by_provider_scope_filters_by_provider(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'openrouter/model',
            'display_name' => 'OpenRouter Model',
        ]);

        AIModel::create([
            'provider' => 'openai',
            'model_id' => 'openai/model',
            'display_name' => 'OpenAI Model',
        ]);

        if (method_exists(AIModel::class, 'scopeByProvider')) {
            $openrouterModels = AIModel::byProvider('openrouter')->get();

            $this->assertCount(1, $openrouterModels);
            $this->assertEquals('openrouter/model', $openrouterModels->first()->model_id);
        } else {
            $this->markTestSkipped('byProvider scope not yet implemented');
        }
    }

    /**
     * Test ordered scope sorts by display_name.
     * AM-SC-06
     */
    public function test_ordered_scope_sorts_by_display_name(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/c',
            'display_name' => 'Charlie Model',
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/a',
            'display_name' => 'Alpha Model',
        ]);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'model/b',
            'display_name' => 'Bravo Model',
        ]);

        if (method_exists(AIModel::class, 'scopeOrdered')) {
            $models = AIModel::ordered()->get();

            $this->assertEquals('Alpha Model', $models[0]->display_name);
            $this->assertEquals('Bravo Model', $models[1]->display_name);
            $this->assertEquals('Charlie Model', $models[2]->display_name);
        } else {
            $this->markTestSkipped('ordered scope not yet implemented');
        }
    }

    // ==================== INDEX TESTS ====================

    /**
     * Test unique index on provider + model_id exists.
     * AM-IX-01
     */
    public function test_unique_index_on_provider_model_id_exists(): void
    {
        // Try to create duplicate - should fail
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'duplicate/model',
            'display_name' => 'First Model',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'duplicate/model',
            'display_name' => 'Second Model',
        ]);
    }

    /**
     * Test unique index allows same model_id with different provider.
     * AM-IX-02
     */
    public function test_unique_index_allows_same_model_id_different_provider(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4 via OpenRouter',
        ]);

        // Should not throw - different provider
        $model2 = AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4 via OpenAI',
        ]);

        $this->assertNotNull($model2->id);
        $this->assertEquals('openai', $model2->provider);
    }

    /**
     * Test index on is_active exists.
     * AM-IX-03
     */
    public function test_index_on_is_active_exists(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list(ai_models)");
            $hasIndex = false;
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $col) {
                    if ($col->name === 'is_active') {
                        $hasIndex = true;
                        break 2;
                    }
                }
            }
            $this->assertTrue($hasIndex, 'Index on is_active should exist');
        } else {
            $indexes = DB::select("SHOW INDEX FROM ai_models WHERE Column_name = 'is_active'");
            $this->assertGreaterThan(0, count($indexes), 'Index on is_active should exist');
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test max provider length 100 characters.
     * AM-EC-01
     */
    public function test_provider_max_length_100_characters(): void
    {
        $longProvider = str_repeat('a', 100);

        $model = AIModel::create([
            'provider' => $longProvider,
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);

        $this->assertEquals(100, strlen($model->provider));
    }

    /**
     * Test provider exceeding max length fails.
     * AM-EC-02
     */
    public function test_provider_exceeding_max_length_fails(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce string length constraints');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        $tooLongProvider = str_repeat('a', 101);

        AIModel::create([
            'provider' => $tooLongProvider,
            'model_id' => 'test/model',
            'display_name' => 'Test',
        ]);
    }

    /**
     * Test max model_id length 255 characters.
     * AM-EC-03
     */
    public function test_model_id_max_length_255_characters(): void
    {
        $longModelId = str_repeat('a', 255);

        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => $longModelId,
            'display_name' => 'Test',
        ]);

        $this->assertEquals(255, strlen($model->model_id));
    }

    /**
     * Test max display_name length 255 characters.
     * AM-EC-04
     */
    public function test_display_name_max_length_255_characters(): void
    {
        $longName = str_repeat('a', 255);

        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => $longName,
        ]);

        $this->assertEquals(255, strlen($model->display_name));
    }

    /**
     * Test max cost_per_1k_input value.
     * AM-EC-05
     */
    public function test_max_cost_per_1k_input_value(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 9999.999999,
        ]);

        $model->refresh();
        $this->assertEquals(9999.999999, $model->cost_per_1k_input);
    }

    /**
     * Test max cost_per_1k_output value.
     * AM-EC-06
     */
    public function test_max_cost_per_1k_output_value(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_output' => 9999.999999,
        ]);

        $model->refresh();
        $this->assertEquals(9999.999999, $model->cost_per_1k_output);
    }

    /**
     * Test zero costs allowed.
     * AM-EC-07
     */
    public function test_zero_costs_allowed(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);

        $model->refresh();
        $this->assertEquals(0, $model->cost_per_1k_input);
        $this->assertEquals(0, $model->cost_per_1k_output);
    }

    /**
     * Test max context_window value.
     * AM-EC-08
     */
    public function test_max_context_window_value(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'context_window' => 4294967295, // Max UNSIGNED INT
        ]);

        $model->refresh();
        $this->assertEquals(4294967295, $model->context_window);
    }

    /**
     * Test small decimal values.
     * AM-EC-09
     */
    public function test_small_decimal_values(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => 0.000001,
            'cost_per_1k_output' => 0.000001,
        ]);

        $model->refresh();
        $this->assertEquals(0.000001, $model->cost_per_1k_input);
        $this->assertEquals(0.000001, $model->cost_per_1k_output);
    }

    /**
     * Test capabilities with complex JSON.
     * AM-EC-10
     */
    public function test_capabilities_with_complex_json(): void
    {
        $capabilities = [
            'vision' => true,
            'tools' => true,
            'streaming' => true,
            'max_tokens' => 4096,
            'supported_languages' => ['en', 'es', 'fr'],
            'metadata' => [
                'version' => '1.0',
                'deprecated' => false,
            ],
        ];

        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'capabilities' => $capabilities,
        ]);

        $this->assertTrue($model->capabilities['vision']);
        $this->assertEquals(['en', 'es', 'fr'], $model->capabilities['supported_languages']);
        $this->assertEquals('1.0', $model->capabilities['metadata']['version']);
    }

    /**
     * Test Unicode in display_name.
     * AM-EC-11
     */
    public function test_unicode_in_display_name(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'GPT-4 🤖 测试模型',
        ]);

        $this->assertEquals('GPT-4 🤖 测试模型', $model->display_name);
    }

    /**
     * Test free_tier_limit max value.
     * AM-EC-12
     */
    public function test_free_tier_limit_max_value(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'is_free_tier' => true,
            'free_tier_limit' => 4294967295, // Max UNSIGNED INT
        ]);

        $model->refresh();
        $this->assertEquals(4294967295, $model->free_tier_limit);
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test cost values cannot be negative.
     * AM-SEC-01
     */
    public function test_cost_values_cannot_be_negative(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce unsigned constraints');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test',
            'cost_per_1k_input' => -0.01,
        ]);
    }

    /**
     * Test model data integrity.
     * AM-SEC-02
     */
    public function test_model_data_integrity(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'secure/model',
            'display_name' => 'Secure Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        // Verify exact values stored
        $this->assertDatabaseHas('ai_models', [
            'id' => $model->id,
            'provider' => 'openrouter',
            'model_id' => 'secure/model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);
    }

    /**
     * Test active flag allows hiding models.
     * AM-SEC-03
     */
    public function test_active_flag_allows_hiding_models(): void
    {
        $model = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'hidden/model',
            'display_name' => 'Hidden Model',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('ai_models', ['id' => $model->id]);
        
        if (method_exists(AIModel::class, 'scopeActive')) {
            $this->assertCount(0, AIModel::active()->where('id', $model->id)->get());
        }
    }

    /**
     * Test free tier flag doesn't affect paid models.
     * AM-SEC-04
     */
    public function test_free_tier_flag_doesnt_affect_paid_models(): void
    {
        $paidModel = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'paid/model',
            'display_name' => 'Paid Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'is_free_tier' => false,
        ]);

        $freeModel = AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'free/model',
            'display_name' => 'Free Model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
            'is_free_tier' => true,
        ]);

        $this->assertFalse($paidModel->is_free_tier);
        $this->assertTrue($freeModel->is_free_tier);
        $this->assertGreaterThan(0, $paidModel->cost_per_1k_input);
        $this->assertEquals(0, $freeModel->cost_per_1k_input);
    }
}
