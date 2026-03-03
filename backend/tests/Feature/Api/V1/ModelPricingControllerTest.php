<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Model Pricing API Endpoint Tests (TASK-26-18)
 *
 * Tests for the GET /api/v1/models/{id}/pricing endpoint that returns
 * detailed pricing information for a specific AI model.
 *
 * @group models-api
 * @group billing
 */
class ModelPricingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mock model data for pricing tests.
     */
    protected function getMockModel(): array
    {
        return [
            'id' => 1,
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'context_window' => 128000,
            'is_free_tier' => false,
            'capabilities' => ['tools', 'vision'],
        ];
    }

    /**
     * Mock free tier model.
     */
    protected function getFreeMockModel(): array
    {
        return [
            'id' => 2,
            'provider' => 'openrouter',
            'model_id' => 'meta-llama/llama-3-8b-instruct:free',
            'display_name' => 'Llama 3 8B Free',
            'cost_per_1k_input' => 0.0,
            'cost_per_1k_output' => 0.0,
            'context_window' => 8192,
            'is_free_tier' => true,
            'capabilities' => [],
        ];
    }

    /**
     * Mock the ModelPricingService.
     */
    protected function mockModelPricingService(?array $model = null): void
    {
        $model = $model ?? $this->getMockModel();

        $mock = Mockery::mock('App\Services\Billing\ModelPricingService');

        // Mock getModelById
        $mock->shouldReceive('getModelById')
            ->andReturnUsing(function (int $id) use ($model) {
                if ($model && $model['id'] === $id) {
                    $mockModel = Mockery::mock('App\Models\AIModel');
                    $mockModel->id = $model['id'];
                    $mockModel->provider = $model['provider'];
                    $mockModel->model_id = $model['model_id'];
                    $mockModel->display_name = $model['display_name'];
                    $mockModel->cost_per_1k_input = $model['cost_per_1k_input'];
                    $mockModel->cost_per_1k_output = $model['cost_per_1k_output'];
                    $mockModel->context_window = $model['context_window'];
                    $mockModel->is_free_tier = $model['is_free_tier'];
                    $mockModel->capabilities = $model['capabilities'];
                    return $mockModel;
                }
                return null;
            });

        // Mock getModelPricing
        $mock->shouldReceive('getModelPricing')
            ->andReturnUsing(function (string $modelId) use ($model) {
                if ($model && $model['model_id'] === $modelId) {
                    return [
                        'input_per_1k' => (float) $model['cost_per_1k_input'],
                        'output_per_1k' => (float) $model['cost_per_1k_output'],
                        'input_per_1m' => (float) $model['cost_per_1k_input'] * 1000,
                        'output_per_1m' => (float) $model['cost_per_1k_output'] * 1000,
                    ];
                }
                return null;
            });

        // Mock calculateAnalysisCost
        $mock->shouldReceive('calculateAnalysisCost')
            ->andReturnUsing(function (string $modelId, int $tokens, ?int $output = null) use ($model) {
                if ($model && $model['model_id'] === $modelId) {
                    $inputCost = ($tokens / 1000) * $model['cost_per_1k_input'];
                    $outputTokens = $output ?? (int)($tokens * 0.3);
                    $outputCost = ($outputTokens / 1000) * $model['cost_per_1k_output'];
                    return round($inputCost + $outputCost, 2);
                }
                return 0.0;
            });

        $this->app->instance('App\Services\Billing\ModelPricingService', $mock);
    }

    // ==========================================
    // SUCCESS CASES - BASIC ENDPOINT
    // ==========================================

    /**
     * @test
     * MPC-001: GET /api/v1/models/{id}/pricing returns 200 status
     */
    public function test_pricing_endpoint_returns_200(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertStatus(200);
    }

    /**
     * @test
     * MPC-002: GET /api/v1/models/{id}/pricing returns correct JSON structure
     */
    public function test_pricing_returns_correct_structure(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'model' => [
                    'id',
                    'display_name',
                    'provider',
                    'model_id',
                ],
                'pricing' => [
                    'cost_per_1m_input',
                    'cost_per_1m_output',
                    'cost_per_1k_input',
                    'cost_per_1k_output',
                ],
                'estimates' => [
                    'quick_analysis',
                    'standard_analysis',
                    'deep_analysis',
                ],
                'free_tier' => [
                    'available',
                    'limit',
                ],
            ],
        ]);
    }

    /**
     * @test
     * MPC-003: GET /api/v1/models/{id}/pricing returns success true
     */
    public function test_pricing_returns_success_true(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonPath('success', true);
    }

    /**
     * @test
     * MPC-004: GET /api/v1/models/{id}/pricing is publicly accessible
     */
    public function test_pricing_is_public_endpoint(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertStatus(200);
    }

    /**
     * @test
     * MPC-005: Response includes correct model information
     */
    public function test_pricing_includes_model_info(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonPath('data.model.id', 1)
            ->assertJsonPath('data.model.display_name', 'GPT-4 Turbo')
            ->assertJsonPath('data.model.provider', 'openrouter')
            ->assertJsonPath('data.model.model_id', 'openai/gpt-4-turbo');
    }

    /**
     * @test
     * MPC-006: Response includes correct pricing breakdown
     */
    public function test_pricing_includes_pricing_breakdown(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonPath('data.pricing.cost_per_1k_input', 0.01)
            ->assertJsonPath('data.pricing.cost_per_1k_output', 0.03)
            ->assertJsonPath('data.pricing.cost_per_1m_input', 10.0)
            ->assertJsonPath('data.pricing.cost_per_1m_output', 30.0);
    }

    /**
     * @test
     * MPC-007: Response includes default analysis estimates
     */
    public function test_pricing_includes_default_estimates(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonStructure([
            'data' => [
                'estimates' => [
                    'quick_analysis' => ['tokens', 'cost'],
                    'standard_analysis' => ['tokens', 'cost'],
                    'deep_analysis' => ['tokens', 'cost'],
                ],
            ],
        ]);

        // Quick analysis: ~500 tokens
        $this->assertEquals(500, $response->json('data.estimates.quick_analysis.tokens'));
        
        // Standard analysis: ~2000 tokens
        $this->assertEquals(2000, $response->json('data.estimates.standard_analysis.tokens'));
        
        // Deep analysis: ~5000 tokens
        $this->assertEquals(5000, $response->json('data.estimates.deep_analysis.tokens'));
    }

    /**
     * @test
     * MPC-008: Response includes free tier information for non-free model
     */
    public function test_pricing_includes_free_tier_info_non_free(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertJsonPath('data.free_tier.available', false)
            ->assertJsonPath('data.free_tier.limit', null);
    }

    /**
     * @test
     * MPC-009: Response includes free tier information for free model
     */
    public function test_pricing_includes_free_tier_info_free_model(): void
    {
        $this->mockModelPricingService($this->getFreeMockModel());

        $response = $this->getJson('/api/v1/models/2/pricing');

        $response->assertJsonPath('data.free_tier.available', true);
    }

    // ==========================================
    // 404 ERROR TESTS
    // ==========================================

    /**
     * @test
     * MPC-010: Non-existent model returns 404
     */
    public function test_nonexistent_model_returns_404(): void
    {
        $this->mockModelPricingService(null);

        $response = $this->getJson('/api/v1/models/999/pricing');

        $response->assertStatus(404);
    }

    /**
     * @test
     * MPC-011: Non-existent model returns error response
     */
    public function test_nonexistent_model_returns_error_message(): void
    {
        $this->mockModelPricingService(null);

        $response = $this->getJson('/api/v1/models/999/pricing');

        $response->assertJson([
            'success' => false,
            'message' => 'Model not found',
        ]);
    }

    /**
     * @test
     * MPC-012: Invalid model ID returns 404
     */
    public function test_invalid_model_id_returns_404(): void
    {
        $this->mockModelPricingService();

        // String ID should be handled gracefully
        $response = $this->getJson('/api/v1/models/abc/pricing');

        // Should return 404 (route constraint) or 422 (validation)
        $this->assertContains($response->status(), [404, 422]);
    }

    // ==========================================
    // ANALYSIS TYPE PARAMETER TESTS
    // ==========================================

    /**
     * @test
     * MPC-020: analysis_type=quick returns quick analysis estimate
     */
    public function test_analysis_type_quick(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=quick');

        $response->assertStatus(200);
        
        // Should return single estimate for quick analysis
        $this->assertArrayHasKey('tokens', $response->json('data.estimate'));
        $this->assertEquals(500, $response->json('data.estimate.tokens'));
    }

    /**
     * @test
     * MPC-021: analysis_type=standard returns standard analysis estimate
     */
    public function test_analysis_type_standard(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=standard');

        $response->assertStatus(200);
        
        $this->assertEquals(2000, $response->json('data.estimate.tokens'));
    }

    /**
     * @test
     * MPC-022: analysis_type=deep returns deep analysis estimate
     */
    public function test_analysis_type_deep(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=deep');

        $response->assertStatus(200);
        
        $this->assertEquals(5000, $response->json('data.estimate.tokens'));
    }

    /**
     * @test
     * MPC-023: Invalid analysis_type returns validation error
     */
    public function test_invalid_analysis_type_returns_error(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=invalid');

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-024: analysis_type validation error message is clear
     */
    public function test_analysis_type_validation_message(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=invalid');

        $response->assertJsonStructure([
            'message',
            'errors' => ['analysis_type'],
        ]);
    }

    // ==========================================
    // CUSTOM TOKEN ESTIMATE TESTS
    // ==========================================

    /**
     * @test
     * MPC-030: estimated_tokens parameter calculates custom cost
     */
    public function test_custom_token_estimate(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=3000');

        $response->assertStatus(200);
        
        // Should return custom estimate
        $this->assertArrayHasKey('custom_estimate', $response->json('data'));
        $this->assertEquals(3000, $response->json('data.custom_estimate.tokens'));
    }

    /**
     * @test
     * MPC-031: estimated_tokens with analysis_type returns both
     */
    public function test_custom_tokens_with_analysis_type(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=standard&estimated_tokens=3000');

        $response->assertStatus(200);
        
        // Should include both standard estimate and custom estimate
        $this->assertArrayHasKey('estimate', $response->json('data'));
        $this->assertArrayHasKey('custom_estimate', $response->json('data'));
    }

    /**
     * @test
     * MPC-032: estimated_tokens must be positive integer
     */
    public function test_estimated_tokens_must_be_positive(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=-100');

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-033: estimated_tokens zero returns error
     */
    public function test_estimated_tokens_zero_invalid(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=0');

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-034: estimated_tokens must be integer
     */
    public function test_estimated_tokens_must_be_integer(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=100.5');

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-035: Large estimated_tokens is handled
     */
    public function test_large_estimated_tokens(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=1000000');

        $response->assertStatus(200);
        
        // Should handle large token counts
        $this->assertEquals(1000000, $response->json('data.custom_estimate.tokens'));
    }

    // ==========================================
    // FREE TIER MODEL TESTS
    // ==========================================

    /**
     * @test
     * MPC-040: Free tier model shows zero costs
     */
    public function test_free_tier_model_zero_costs(): void
    {
        $this->mockModelPricingService($this->getFreeMockModel());

        $response = $this->getJson('/api/v1/models/2/pricing');

        $response->assertStatus(200)
            ->assertJsonPath('data.pricing.cost_per_1k_input', 0.0)
            ->assertJsonPath('data.pricing.cost_per_1k_output', 0.0);
    }

    /**
     * @test
     * MPC-041: Free tier model estimates are zero
     */
    public function test_free_tier_estimates_zero(): void
    {
        $this->mockModelPricingService($this->getFreeMockModel());

        $response = $this->getJson('/api/v1/models/2/pricing');

        $response->assertStatus(200);
        
        // All estimates should be zero cost
        $this->assertEquals(0.0, $response->json('data.estimates.quick_analysis.cost'));
        $this->assertEquals(0.0, $response->json('data.estimates.standard_analysis.cost'));
        $this->assertEquals(0.0, $response->json('data.estimates.deep_analysis.cost'));
    }

    /**
     * @test
     * MPC-042: Free tier available flag is true
     */
    public function test_free_tier_available_true(): void
    {
        $this->mockModelPricingService($this->getFreeMockModel());

        $response = $this->getJson('/api/v1/models/2/pricing');

        $response->assertJsonPath('data.free_tier.available', true);
    }

    /**
     * @test
     * MPC-043: Custom tokens on free model still zero cost
     */
    public function test_custom_tokens_free_model_zero(): void
    {
        $this->mockModelPricingService($this->getFreeMockModel());

        $response = $this->getJson('/api/v1/models/2/pricing?estimated_tokens=5000');

        $response->assertStatus(200)
            ->assertJsonPath('data.custom_estimate.cost', 0.0);
    }

    // ==========================================
    // COST CALCULATION TESTS
    // ==========================================

    /**
     * @test
     * MPC-050: Quick analysis cost is calculated correctly
     */
    public function test_quick_analysis_cost_calculation(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        // GPT-4 Turbo: $0.01/1k input, $0.03/1k output
        // Quick: 500 tokens (350 input + 150 output estimated)
        // Expected: (350/1000 * 0.01) + (150/1000 * 0.03) = 0.0035 + 0.0045 = 0.008
        $quickCost = $response->json('data.estimates.quick_analysis.cost');
        $this->assertGreaterThan(0, $quickCost);
        $this->assertLessThan(0.02, $quickCost);
    }

    /**
     * @test
     * MPC-051: Standard analysis cost is calculated correctly
     */
    public function test_standard_analysis_cost_calculation(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $standardCost = $response->json('data.estimates.standard_analysis.cost');
        $quickCost = $response->json('data.estimates.quick_analysis.cost');
        
        // Standard should cost more than quick
        $this->assertGreaterThan($quickCost, $standardCost);
    }

    /**
     * @test
     * MPC-052: Deep analysis cost is calculated correctly
     */
    public function test_deep_analysis_cost_calculation(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $deepCost = $response->json('data.estimates.deep_analysis.cost');
        $standardCost = $response->json('data.estimates.standard_analysis.cost');
        
        // Deep should cost more than standard
        $this->assertGreaterThan($standardCost, $deepCost);
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /**
     * @test
     * MPC-060: POST method is not allowed
     */
    public function test_post_method_not_allowed(): void
    {
        $response = $this->postJson('/api/v1/models/1/pricing', []);

        $response->assertStatus(405);
    }

    /**
     * @test
     * MPC-061: PUT method is not allowed
     */
    public function test_put_method_not_allowed(): void
    {
        $response = $this->putJson('/api/v1/models/1/pricing', []);

        $response->assertStatus(405);
    }

    /**
     * @test
     * MPC-062: DELETE method is not allowed
     */
    public function test_delete_method_not_allowed(): void
    {
        $response = $this->deleteJson('/api/v1/models/1/pricing');

        $response->assertStatus(405);
    }

    /**
     * @test
     * MPC-063: PATCH method is not allowed
     */
    public function test_patch_method_not_allowed(): void
    {
        $response = $this->patchJson('/api/v1/models/1/pricing', []);

        $response->assertStatus(405);
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /**
     * @test
     * MPC-070: SQL injection in model ID is handled safely
     */
    public function test_sql_injection_in_model_id(): void
    {
        $this->mockModelPricingService();

        $maliciousId = "1; DROP TABLE ai_models; --";

        $response = $this->getJson('/api/v1/models/' . urlencode($maliciousId) . '/pricing');

        // Should return 404 (not found) or 422 (validation), not 500
        $this->assertContains($response->status(), [404, 422]);
    }

    /**
     * @test
     * MPC-071: XSS in query parameters is handled safely
     */
    public function test_xss_in_query_params(): void
    {
        $this->mockModelPricingService();

        $maliciousQuery = '<script>alert("xss")</script>';

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=' . urlencode($maliciousQuery));

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-072: No sensitive data exposed in response
     */
    public function test_no_sensitive_data_exposed(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $response->assertStatus(200);

        $json = $response->json();

        // Should not expose sensitive fields
        $this->assertArrayNotHasKey('api_key', $json);
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('secret', $json);
        
        // Model data should not expose internal fields
        if (isset($json['data']['model'])) {
            $this->assertArrayNotHasKey('internal_id', $json['data']['model']);
            $this->assertArrayNotHasKey('api_credentials', $json['data']['model']);
        }
    }

    /**
     * @test
     * MPC-073: Large model ID is handled safely
     */
    public function test_large_model_id(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/999999999999/pricing');

        $response->assertStatus(404);
    }

    /**
     * @test
     * MPC-074: Negative model ID is handled safely
     */
    public function test_negative_model_id(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/-1/pricing');

        $response->assertStatus(404);
    }

    // ==========================================
    // RATE LIMITING TESTS
    // ==========================================

    /**
     * @test
     * MPC-080: Rate limiting allows reasonable requests
     */
    public function test_rate_limiting_allows_reasonable_requests(): void
    {
        $this->mockModelPricingService();

        // Make 5 quick requests - should all succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/v1/models/1/pricing');
            $response->assertStatus(200);
        }
    }

    // ==========================================
    // EDGE CASES
    // ==========================================

    /**
     * @test
     * MPC-090: Unknown query parameters are ignored
     */
    public function test_unknown_query_params_ignored(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?unknown_param=value');

        $response->assertStatus(200);
    }

    /**
     * @test
     * MPC-091: Request body is ignored for GET request
     */
    public function test_request_body_ignored_for_get(): void
    {
        $this->mockModelPricingService();

        $response = $this->json('GET', '/api/v1/models/1/pricing', ['malicious' => 'data']);

        $response->assertStatus(200);
    }

    /**
     * @test
     * MPC-092: Unicode in analysis_type is handled
     */
    public function test_unicode_in_analysis_type(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=' . urlencode('快速'));

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-093: Maximum estimated_tokens is handled
     */
    public function test_max_estimated_tokens(): void
    {
        $this->mockModelPricingService();

        // Test with a large but reasonable value (1 billion tokens)
        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=' . PHP_INT_MAX);

        // Should either succeed with large cost or return validation error
        $this->assertContains($response->status(), [200, 422]);
    }

    /**
     * @test
     * MPC-094: String estimated_tokens is rejected
     */
    public function test_string_estimated_tokens_rejected(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?estimated_tokens=abc');

        $response->assertStatus(422);
    }

    /**
     * @test
     * MPC-095: Model with high pricing shows correct values
     */
    public function test_high_pricing_model(): void
    {
        $highPriceModel = [
            'id' => 3,
            'provider' => 'openrouter',
            'model_id' => 'expensive/model',
            'display_name' => 'Expensive Model',
            'cost_per_1k_input' => 0.10,  // $100 per 1M tokens
            'cost_per_1k_output' => 0.30,  // $300 per 1M tokens
            'context_window' => 200000,
            'is_free_tier' => false,
            'capabilities' => [],
        ];

        $this->mockModelPricingService($highPriceModel);

        $response = $this->getJson('/api/v1/models/3/pricing');

        $response->assertStatus(200)
            ->assertJsonPath('data.pricing.cost_per_1m_input', 100.0)
            ->assertJsonPath('data.pricing.cost_per_1m_output', 300.0);
    }

    // ==========================================
    // PERFORMANCE TESTS
    // ==========================================

    /**
     * @test
     * MPC-100: Response time is acceptable (<500ms)
     */
    public function test_response_time_acceptable(): void
    {
        $this->mockModelPricingService();

        $start = microtime(true);

        $this->getJson('/api/v1/models/1/pricing');

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'API should respond in <500ms');
    }

    // ==========================================
    // DATA TYPE TESTS
    // ==========================================

    /**
     * @test
     * MPC-110: Model ID is integer
     */
    public function test_model_id_is_integer(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $this->assertIsInt($response->json('data.model.id'));
    }

    /**
     * @test
     * MPC-111: Cost values are numeric
     */
    public function test_cost_values_are_numeric(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $this->assertIsNumeric($response->json('data.pricing.cost_per_1k_input'));
        $this->assertIsNumeric($response->json('data.pricing.cost_per_1k_output'));
        $this->assertIsNumeric($response->json('data.pricing.cost_per_1m_input'));
        $this->assertIsNumeric($response->json('data.pricing.cost_per_1m_output'));
    }

    /**
     * @test
     * MPC-112: Estimate tokens are integers
     */
    public function test_estimate_tokens_are_integers(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $this->assertIsInt($response->json('data.estimates.quick_analysis.tokens'));
        $this->assertIsInt($response->json('data.estimates.standard_analysis.tokens'));
        $this->assertIsInt($response->json('data.estimates.deep_analysis.tokens'));
    }

    /**
     * @test
     * MPC-113: Estimate costs are numeric
     */
    public function test_estimate_costs_are_numeric(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $this->assertIsNumeric($response->json('data.estimates.quick_analysis.cost'));
        $this->assertIsNumeric($response->json('data.estimates.standard_analysis.cost'));
        $this->assertIsNumeric($response->json('data.estimates.deep_analysis.cost'));
    }

    /**
     * @test
     * MPC-114: free_tier.available is boolean
     */
    public function test_free_tier_available_is_boolean(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing');

        $this->assertIsBool($response->json('data.free_tier.available'));
    }

    // ==========================================
    // COMBINED PARAMETER TESTS
    // ==========================================

    /**
     * @test
     * MPC-120: All valid parameters together
     */
    public function test_all_valid_parameters(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=standard&estimated_tokens=3000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'model',
                    'pricing',
                    'estimates',
                    'estimate',  // From analysis_type
                    'custom_estimate',  // From estimated_tokens
                    'free_tier',
                ],
            ]);
    }

    /**
     * @test
     * MPC-121: Multiple invalid parameters
     */
    public function test_multiple_invalid_parameters(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/1/pricing?analysis_type=invalid&estimated_tokens=-100');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
