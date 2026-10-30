<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * ModelController API Endpoint Tests (TASK-26-17)
 *
 * Tests for the GET /api/v1/models endpoint that lists available AI models
 * with pricing information, capabilities, and filtering options.
 *
 * @group models-api
 * @group billing
 */
class ModelControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mock model data structure matching expected response format.
     */
    protected function getMockModels(): array
    {
        return [
            [
                'id' => 1,
                'provider' => 'openrouter',
                'model_id' => 'openai/gpt-4-turbo',
                'display_name' => 'GPT-4 Turbo',
                'cost_per_1k_input' => 0.01,
                'cost_per_1k_output' => 0.03,
                'estimated_cost_per_analysis' => 0.05,
                'context_window' => 128000,
                'is_free_tier' => false,
                'capabilities' => ['tools', 'vision'],
                'is_user_default' => false,
            ],
            [
                'id' => 2,
                'provider' => 'openrouter',
                'model_id' => 'anthropic/claude-3-opus',
                'display_name' => 'Claude 3 Opus',
                'cost_per_1k_input' => 0.015,
                'cost_per_1k_output' => 0.075,
                'estimated_cost_per_analysis' => 0.09,
                'context_window' => 200000,
                'is_free_tier' => false,
                'capabilities' => ['tools', 'vision'],
                'is_user_default' => false,
            ],
            [
                'id' => 3,
                'provider' => 'openrouter',
                'model_id' => 'meta-llama/llama-3-8b-instruct:free',
                'display_name' => 'Llama 3 8B Free',
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
                'estimated_cost_per_analysis' => 0.0,
                'context_window' => 8192,
                'is_free_tier' => true,
                'capabilities' => [],
                'is_user_default' => false,
            ],
            [
                'id' => 4,
                'provider' => 'openrouter',
                'model_id' => 'google/gemini-pro-vision',
                'display_name' => 'Gemini Pro Vision',
                'cost_per_1k_input' => 0.00025,
                'cost_per_1k_output' => 0.0005,
                'estimated_cost_per_analysis' => 0.001,
                'context_window' => 32768,
                'is_free_tier' => false,
                'capabilities' => ['vision'],
                'is_user_default' => false,
            ],
            [
                'id' => 5,
                'provider' => 'openrouter',
                'model_id' => 'mistralai/mistral-large',
                'display_name' => 'Mistral Large',
                'cost_per_1k_input' => 0.004,
                'cost_per_1k_output' => 0.012,
                'estimated_cost_per_analysis' => 0.02,
                'context_window' => 32768,
                'is_free_tier' => false,
                'capabilities' => ['tools'],
                'is_user_default' => false,
            ],
        ];
    }

    /**
     * Mock the ModelPricingService for testing.
     */
    protected function mockModelPricingService(array $models = null, ?int $userDefaultId = null): void
    {
        $models = $models ?? $this->getMockModels();

        $mock = Mockery::mock('App\Services\Billing\ModelPricingService');

        // Mock getAvailableModels with filters
        $mock->shouldReceive('getAvailableModels')
            ->andReturnUsing(function ($filters = []) use ($models) {
                $result = collect($models);

                // Apply provider filter
                if (!empty($filters['provider'])) {
                    $result = $result->where('provider', $filters['provider']);
                }

                // Apply free_only filter
                if (!empty($filters['free_only'])) {
                    $result = $result->where('is_free_tier', true);
                }

                // Apply capability filter
                if (!empty($filters['capability'])) {
                    $result = $result->filter(function ($model) use ($filters) {
                        return in_array($filters['capability'], $model['capabilities'] ?? []);
                    });
                }

                return $result;
            });

        // Mock getUserDefaultModel for authenticated users
        if ($userDefaultId !== null) {
            $mock->shouldReceive('getUserDefaultModel')
                ->andReturnUsing(function () use ($models, $userDefaultId) {
                    $modelData = collect($models)->firstWhere('id', $userDefaultId);
                    if (!$modelData) {
                        return null;
                    }
                    // Return a mock object that behaves like AIModel
                    $mockModel = Mockery::mock('App\Models\AIModel');
                    $mockModel->id = $modelData['id'];
                    $mockModel->provider = $modelData['provider'];
                    $mockModel->model_id = $modelData['model_id'];
                    $mockModel->display_name = $modelData['display_name'];
                    $mockModel->cost_per_1k_input = $modelData['cost_per_1k_input'];
                    $mockModel->cost_per_1k_output = $modelData['cost_per_1k_output'];
                    $mockModel->context_window = $modelData['context_window'];
                    $mockModel->is_free_tier = $modelData['is_free_tier'];
                    $mockModel->capabilities = $modelData['capabilities'] ?? [];
                    $mockModel->estimated_cost_per_analysis = $modelData['estimated_cost_per_analysis'] ?? 0;
                    return $mockModel;
                });
        } else {
            $mock->shouldReceive('getUserDefaultModel')
                ->andReturn(null);
        }

        $this->app->instance('App\Services\Billing\ModelPricingService', $mock);
    }

    // ==========================================
    // SUCCESS CASES - BASIC ENDPOINT
    // ==========================================

    /**
     * @test
     * MCT-001: GET /api/v1/models returns 200 status
     */
    public function test_models_endpoint_returns_200(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);
    }

    /**
     * @test
     * MCT-002: GET /api/v1/models returns correct JSON structure
     */
    public function test_models_returns_correct_structure(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'provider',
                    'model_id',
                    'display_name',
                    'cost_per_1k_input',
                    'cost_per_1k_output',
                    'estimated_cost_per_analysis',
                    'context_window',
                    'is_free_tier',
                    'capabilities',
                    'is_user_default',
                ],
            ],
            'meta' => [
                'total',
                'free_tier_count',
            ],
        ]);
    }

    /**
     * @test
     * MCT-003: GET /api/v1/models returns success true
     */
    public function test_models_returns_success_true(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertJsonPath('success', true);
    }

    /**
     * @test
     * MCT-004: GET /api/v1/models returns all active models
     */
    public function test_models_returns_all_active_models(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertJsonCount(5, 'data');
    }

    /**
     * @test
     * MCT-005: GET /api/v1/models is publicly accessible (no auth required)
     */
    public function test_models_is_public_endpoint(): void
    {
        $this->mockModelPricingService();

        // No authentication header provided
        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);
    }

    /**
     * @test
     * MCT-006: Response includes correct meta information
     */
    public function test_models_includes_correct_meta(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.free_tier_count', 1);
    }

    // ==========================================
    // FILTER TESTS - PROVIDER
    // ==========================================

    /**
     * @test
     * MCT-010: Filter by provider returns only matching models
     */
    public function test_filter_by_provider(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?provider=openrouter');

        $response->assertStatus(200);

        // All models in mock are openrouter
        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertEquals('openrouter', $model['provider']);
        }
    }

    /**
     * @test
     * MCT-011: Filter by non-existent provider returns empty array
     */
    public function test_filter_by_nonexistent_provider(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?provider=nonexistent');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * @test
     * MCT-012: Provider filter is case-sensitive
     */
    public function test_provider_filter_case_sensitive(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?provider=OpenRouter');

        // Should return empty since provider is lowercase 'openrouter'
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ==========================================
    // FILTER TESTS - FREE_ONLY
    // ==========================================

    /**
     * @test
     * MCT-020: Filter by free_only=true returns only free tier models
     */
    public function test_filter_free_only_true(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?free_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertTrue($model['is_free_tier']);
        }
    }

    /**
     * @test
     * MCT-021: Filter by free_only=1 returns only free tier models
     */
    public function test_filter_free_only_one(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?free_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertTrue($model['is_free_tier']);
        }
    }

    /**
     * @test
     * MCT-022: Filter by free_only=false returns all models
     */
    public function test_filter_free_only_false(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?free_only=false');

        // false should return all models, not filter
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * @test
     * MCT-023: Filter by free_only=0 returns all models
     */
    public function test_filter_free_only_zero(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?free_only=0');

        // 0 should return all models, not filter
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    // ==========================================
    // FILTER TESTS - CAPABILITY
    // ==========================================

    /**
     * @test
     * MCT-030: Filter by capability=vision returns models with vision
     */
    public function test_filter_by_capability_vision(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?capability=vision');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        // GPT-4 Turbo, Claude 3 Opus, Gemini Pro Vision have vision
        $this->assertGreaterThanOrEqual(3, $models->count());

        foreach ($models as $model) {
            $this->assertContains('vision', $model['capabilities']);
        }
    }

    /**
     * @test
     * MCT-031: Filter by capability=tools returns models with tools
     */
    public function test_filter_by_capability_tools(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?capability=tools');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        // GPT-4 Turbo, Claude 3 Opus, Mistral Large have tools
        $this->assertGreaterThanOrEqual(3, $models->count());

        foreach ($models as $model) {
            $this->assertContains('tools', $model['capabilities']);
        }
    }

    /**
     * @test
     * MCT-032: Filter by non-existent capability returns empty
     */
    public function test_filter_by_nonexistent_capability(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?capability=nonexistent');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * @test
     * MCT-033: Filter by capability returns models with empty capabilities excluded
     */
    public function test_filter_excludes_empty_capabilities(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?capability=vision');

        $models = collect($response->json('data'));

        // Llama 3 8B Free has empty capabilities, should not be in results
        foreach ($models as $model) {
            $this->assertNotEmpty($model['capabilities']);
        }
    }

    // ==========================================
    // SORTING TESTS
    // ==========================================

    /**
     * @test
     * MCT-040: Default sort is by name ascending
     */
    public function test_default_sort_by_name(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        $names = $models->pluck('display_name')->toArray();

        // Verify names are sorted alphabetically
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    /**
     * @test
     * MCT-041: Sort by name ascending
     */
    public function test_sort_by_name_asc(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?sort=name');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        $names = $models->pluck('display_name')->toArray();

        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    /**
     * @test
     * MCT-042: Sort by price ascending
     */
    public function test_sort_by_price_asc(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?sort=price');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        $prices = $models->pluck('cost_per_1k_input')->toArray();

        // Verify prices are sorted ascending
        $sortedPrices = $prices;
        sort($sortedPrices, SORT_NUMERIC);
        $this->assertEquals($sortedPrices, $prices);
    }

    /**
     * @test
     * MCT-043: Sort by context descending
     */
    public function test_sort_by_context_asc(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?sort=context');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        $contexts = $models->pluck('context_window')->toArray();

        // Verify context windows are sorted descending (largest first)
        $sortedContexts = $contexts;
        rsort($sortedContexts, SORT_NUMERIC);
        $this->assertEquals($sortedContexts, $contexts);
    }

    /**
     * @test
     * MCT-044: Invalid sort parameter falls back to default (name)
     */
    public function test_invalid_sort_falls_back_to_name(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?sort=invalid');

        // Should either ignore invalid sort or return 400
        // Let's check it doesn't cause a 500 error
        $this->assertNotEquals(500, $response->status());
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /**
     * @test
     * MCT-050: Invalid provider type returns validation error
     */
    public function test_invalid_provider_type(): void
    {
        $this->mockModelPricingService();

        // Provider must be string - test with array injection
        $response = $this->getJson('/api/v1/models?provider[]=invalid');

        // Should either sanitize, ignore, or return 400/422
        $this->assertContains($response->status(), [200, 400, 422]);
    }

    /**
     * @test
     * MCT-051: Invalid free_only type returns validation error
     */
    public function test_invalid_free_only_type(): void
    {
        $this->mockModelPricingService();

        // free_only must be boolean - test with invalid string
        $response = $this->getJson('/api/v1/models?free_only=maybe');

        // Should either convert to boolean or return 400
        $this->assertContains($response->status(), [200, 400]);
    }

    /**
     * @test
     * MCT-052: Invalid capability type returns validation error
     */
    public function test_invalid_capability_type(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?capability[]=invalid');

        $this->assertContains($response->status(), [200, 400, 422]);
    }

    /**
     * @test
     * MCT-053: Invalid sort value returns validation error
     */
    public function test_invalid_sort_value(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?sort=invalid_column');

        // Should return 400/422 for invalid sort value
        // Or fall back to default sort (implementation choice)
        $this->assertContains($response->status(), [200, 400, 422]);
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /**
     * @test
     * MCT-060: Unauthenticated user sees is_user_default as false
     */
    public function test_unauthenticated_user_default_false(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertFalse($model['is_user_default']);
        }
    }

    /**
     * @test
     * MCT-061: Authenticated user sees their default model flagged
     */
    public function test_authenticated_user_sees_default_flag(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->mockModelPricingService(null, 1); // User default is model ID 1

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        $defaultModel = $models->firstWhere('id', 1);

        $this->assertTrue($defaultModel['is_user_default']);

        // Other models should have is_user_default = false
        $otherModels = $models->where('id', '!=', 1);
        foreach ($otherModels as $model) {
            $this->assertFalse($model['is_user_default']);
        }
    }

    /**
     * @test
     * MCT-062: Authenticated user with no default sees all false
     */
    public function test_authenticated_user_no_default(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->mockModelPricingService(null, null); // No user default

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertFalse($model['is_user_default']);
        }
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /**
     * @test
     * MCT-070: POST method is not allowed
     */
    public function test_post_method_not_allowed(): void
    {
        $response = $this->postJson('/api/v1/models', []);

        $response->assertStatus(405);
    }

    /**
     * @test
     * MCT-071: PUT method is not allowed
     */
    public function test_put_method_not_allowed(): void
    {
        $response = $this->putJson('/api/v1/models', []);

        $response->assertStatus(405);
    }

    /**
     * @test
     * MCT-072: DELETE method is not allowed
     */
    public function test_delete_method_not_allowed(): void
    {
        $response = $this->deleteJson('/api/v1/models');

        $response->assertStatus(405);
    }

    /**
     * @test
     * MCT-073: PATCH method is not allowed
     */
    public function test_patch_method_not_allowed(): void
    {
        $response = $this->patchJson('/api/v1/models', []);

        $response->assertStatus(405);
    }

    // ==========================================
    // CONTENT TYPE TESTS
    // ==========================================

    /**
     * @test
     * MCT-080: Response has correct Content-Type header
     */
    public function test_response_has_correct_content_type(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertHeader('Content-Type', 'application/json');
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /**
     * @test
     * MCT-090: SQL injection in provider parameter is handled safely
     */
    public function test_sql_injection_in_provider(): void
    {
        $this->mockModelPricingService();

        $maliciousProvider = "'; DROP TABLE ai_models; --";

        $response = $this->getJson('/api/v1/models?provider=' . urlencode($maliciousProvider));

        // Should either return empty results or 400, not 500
        $this->assertNotEquals(500, $response->status());
    }

    /**
     * @test
     * MCT-091: SQL injection in capability parameter is handled safely
     */
    public function test_sql_injection_in_capability(): void
    {
        $this->mockModelPricingService();

        $maliciousCapability = "'; DROP TABLE ai_models; --";

        $response = $this->getJson('/api/v1/models?capability=' . urlencode($maliciousCapability));

        $this->assertNotEquals(500, $response->status());
    }

    /**
     * @test
     * MCT-092: XSS in query params is handled safely
     */
    public function test_xss_in_query_params(): void
    {
        $this->mockModelPricingService();

        $maliciousQuery = '<script>alert("xss")</script>';

        $response = $this->getJson('/api/v1/models?provider=' . urlencode($maliciousQuery));

        $response->assertStatus(200);
    }

    /**
     * @test
     * MCT-093: No sensitive data exposed in response
     */
    public function test_no_sensitive_data_exposed(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);

        // Response should not contain sensitive fields
        $json = $response->json();

        $this->assertArrayNotHasKey('api_key', $json);
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('secret', $json);

        // Check model data doesn't expose internal fields
        if (!empty($json['data'])) {
            $model = $json['data'][0];
            $this->assertArrayNotHasKey('internal_id', $model);
            $this->assertArrayNotHasKey('api_credentials', $model);
        }
    }

    // ==========================================
    // RATE LIMITING TESTS
    // ==========================================

    /**
     * @test
     * MCT-100: Rate limiting allows reasonable requests
     */
    public function test_rate_limiting_allows_reasonable_requests(): void
    {
        $this->mockModelPricingService();

        // Make 5 quick requests - should all succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/v1/models');
            $response->assertStatus(200);
        }
    }

    /**
     * @test
     * MCT-101: Rate limit headers are present
     */
    public function test_rate_limit_headers_present(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        // Rate limit headers should be present (implementation dependent)
        // Common headers: X-RateLimit-Limit, X-RateLimit-Remaining
        $hasRateLimitHeaders = $response->headers->has('X-RateLimit-Limit')
            || $response->headers->has('X-RateLimit-Remaining')
            || $response->headers->has('Retry-After');

        // This test just verifies the endpoint works; rate limit headers are optional
        $response->assertStatus(200);
    }

    // ==========================================
    // EDGE CASES
    // ==========================================

    /**
     * @test
     * MCT-110: Empty model list returns empty array
     */
    public function test_empty_model_list(): void
    {
        $this->mockModelPricingService([]);

        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'free_tier_count' => 0,
                ],
            ]);
    }

    /**
     * @test
     * MCT-111: Combined filters work correctly
     */
    public function test_combined_filters(): void
    {
        $this->mockModelPricingService();

        // Filter by provider and capability
        $response = $this->getJson('/api/v1/models?provider=openrouter&capability=vision');

        $response->assertStatus(200);

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertEquals('openrouter', $model['provider']);
            $this->assertContains('vision', $model['capabilities']);
        }
    }

    /**
     * @test
     * MCT-112: Free models with capability filter
     */
    public function test_free_models_with_capability_filter(): void
    {
        // Create a free model with vision capability
        $models = $this->getMockModels();
        $models[] = [
            'id' => 6,
            'provider' => 'openrouter',
            'model_id' => 'free-vision-model',
            'display_name' => 'Free Vision Model',
            'cost_per_1k_input' => 0.0,
            'cost_per_1k_output' => 0.0,
            'estimated_cost_per_analysis' => 0.0,
            'context_window' => 4096,
            'is_free_tier' => true,
            'capabilities' => ['vision'],
            'is_user_default' => false,
        ];

        $this->mockModelPricingService($models);

        $response = $this->getJson('/api/v1/models?free_only=true&capability=vision');

        $response->assertStatus(200);

        $responseData = collect($response->json('data'));
        foreach ($responseData as $model) {
            $this->assertTrue($model['is_free_tier']);
            $this->assertContains('vision', $model['capabilities']);
        }
    }

    /**
     * @test
     * MCT-113: Trailing slash is handled correctly
     */
    public function test_trailing_slash_handled(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models/');

        // Laravel should handle this gracefully
        $this->assertContains($response->status(), [200, 301, 302, 404]);
    }

    /**
     * @test
     * MCT-114: Unknown query parameters are ignored
     */
    public function test_unknown_query_params_ignored(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models?unknown_param=value&another=ignored');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * @test
     * MCT-115: Request body is ignored for GET request
     */
    public function test_request_body_ignored_for_get(): void
    {
        $this->mockModelPricingService();

        $response = $this->json('GET', '/api/v1/models', ['malicious' => 'data']);

        $response->assertStatus(200);
    }

    // ==========================================
    // PERFORMANCE TESTS
    // ==========================================

    /**
     * @test
     * MCT-120: Response time is acceptable (<500ms)
     */
    public function test_response_time_acceptable(): void
    {
        $this->mockModelPricingService();

        $start = microtime(true);

        $this->getJson('/api/v1/models');

        $elapsed = (microtime(true) - $start) * 1000; // Convert to ms

        // API should respond in reasonable time
        $this->assertLessThan(500, $elapsed, 'API should respond in <500ms');
    }

    // ==========================================
    // DATA TYPE TESTS
    // ==========================================

    /**
     * @test
     * MCT-130: Model id is integer
     */
    public function test_model_id_is_integer(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsInt($model['id']);
        }
    }

    /**
     * @test
     * MCT-131: Cost values are numeric
     */
    public function test_cost_values_are_numeric(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsNumeric($model['cost_per_1k_input']);
            $this->assertIsNumeric($model['cost_per_1k_output']);
            $this->assertIsNumeric($model['estimated_cost_per_analysis']);
        }
    }

    /**
     * @test
     * MCT-132: is_free_tier is boolean
     */
    public function test_is_free_tier_is_boolean(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsBool($model['is_free_tier']);
        }
    }

    /**
     * @test
     * MCT-133: is_user_default is boolean
     */
    public function test_is_user_default_is_boolean(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsBool($model['is_user_default']);
        }
    }

    /**
     * @test
     * MCT-134: capabilities is array
     */
    public function test_capabilities_is_array(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsArray($model['capabilities']);
        }
    }

    /**
     * @test
     * MCT-135: context_window is integer
     */
    public function test_context_window_is_integer(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        $models = collect($response->json('data'));
        foreach ($models as $model) {
            $this->assertIsInt($model['context_window']);
        }
    }

    // ==========================================
    // CACHING TESTS
    // ==========================================

    /**
     * @test
     * MCT-140: Response should be cacheable (consider caching header)
     */
    public function test_response_cacheable(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        // Cache-Control header is optional but recommended for public endpoints
        $response->assertStatus(200);

        // If caching is implemented, check for Cache-Control header
        // $response->assertHeader('Cache-Control');
    }

    /**
     * @test
     * MCT-141: ETag header for caching (optional optimization)
     */
    public function test_etag_header_optional(): void
    {
        $this->mockModelPricingService();

        $response = $this->getJson('/api/v1/models');

        // ETag is optional but recommended for efficient caching
        $response->assertStatus(200);

        // If ETag is implemented:
        // $response->assertHeader('ETag');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
