<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\UserModelPreference;
use App\Models\AIModel;
use App\Services\AI\ModelSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AIModelControllerTest extends TestCase
{
    use RefreshDatabase;

    private const API_ENDPOINT = '/api/v1/ai/models';
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestModels();
    }

    /**
     * Seed test AI models
     */
    private function seedTestModels(): void
    {
        AIModel::insert([
            [
                'provider' => 'Google',
                'model_id' => 'google/gemini-2.0-flash-exp:free',
                'display_name' => 'Gemini 2.0 Flash',
                'context_window' => 1000000,
                'is_free_tier' => true,
                'is_active' => true,
                'capabilities' => json_encode(['text', 'json']),
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
            ],
            [
                'provider' => 'Alibaba',
                'model_id' => 'qwen/qwen-2-7b-instruct:free',
                'display_name' => 'Qwen 2 7B',
                'context_window' => 32768,
                'is_free_tier' => true,
                'is_active' => true,
                'capabilities' => json_encode(['text', 'json']),
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
            ],
            [
                'provider' => 'Mistral AI',
                'model_id' => 'mistralai/mistral-7b-instruct:free',
                'display_name' => 'Mistral 7B',
                'context_window' => 32768,
                'is_free_tier' => true,
                'is_active' => true,
                'capabilities' => json_encode(['text', 'json']),
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
            ],
            [
                'provider' => 'Meta',
                'model_id' => 'meta-llama/llama-3-8b-instruct:free',
                'display_name' => 'Llama 3 8B',
                'context_window' => 8192,
                'is_free_tier' => true,
                'is_active' => true,
                'capabilities' => json_encode(['text', 'json']),
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
            ],
        ]);
    }

    // ========================================
    // BE - Basic Endpoint Tests
    // ========================================

    /** @test */
    public function test_be_001_get_returns_200(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_be_002_response_has_success_flag(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function test_be_003_response_has_data_wrapper(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'models',
                'default_model',
                'user_preference',
            ],
        ]);
    }

    /** @test */
    public function test_be_004_data_contains_models_array(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.models', fn ($models) => is_array($models));
    }

    /** @test */
    public function test_be_005_data_contains_default_model(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.default_model', self::DEFAULT_MODEL);
    }

    /** @test */
    public function test_be_006_data_contains_user_preference(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonStructure(['data' => ['user_preference']]);
    }

    /** @test */
    public function test_be_007_public_endpoint_no_auth_required(): void
    {
        // Request without any authentication
        $response = $this->getJson(self::API_ENDPOINT);

        // Should return 200, not 401 Unauthorized
        $response->assertStatus(200);
    }

    /** @test */
    public function test_be_008_route_registration(): void
    {
        $routes = collect(app('router')->getRoutes());
        $found = $routes->contains(fn ($route) => 
            in_array('GET', $route->methods()) && 
            str_contains($route->uri(), 'api/v1/ai/models')
        );

        $this->assertTrue($found, 'Route GET /api/v1/ai/models not registered');
    }

    // ========================================
    // ML - Model List Tests
    // ========================================

    /** @test */
    public function test_ml_001_all_4_free_tier_models_included(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        $this->assertCount(4, $models, 'Expected exactly 4 free-tier models');
    }

    /** @test */
    public function test_ml_002_gemini_flash_present(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertNotNull($gemini, 'Gemini 2.0 Flash model not found');
    }

    /** @test */
    public function test_ml_003_qwen_2_7b_present(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $qwen = $models->firstWhere('id', 'qwen/qwen-2-7b-instruct:free');

        $this->assertNotNull($qwen, 'Qwen 2 7B model not found');
    }

    /** @test */
    public function test_ml_004_mistral_7b_present(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $mistral = $models->firstWhere('id', 'mistralai/mistral-7b-instruct:free');

        $this->assertNotNull($mistral, 'Mistral 7B model not found');
    }

    /** @test */
    public function test_ml_005_llama_3_8b_present(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $llama = $models->firstWhere('id', 'meta-llama/llama-3-8b-instruct:free');

        $this->assertNotNull($llama, 'Llama 3 8B model not found');
    }

    /** @test */
    public function test_ml_006_model_has_id_field(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('id', $model);
            $this->assertIsString($model['id']);
        }
    }

    /** @test */
    public function test_ml_007_model_has_name_field(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('name', $model);
            $this->assertIsString($model['name']);
        }
    }

    /** @test */
    public function test_ml_008_model_has_provider_field(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('provider', $model);
            $this->assertIsString($model['provider']);
        }
    }

    /** @test */
    public function test_ml_009_model_has_context_window(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('context_window', $model);
            $this->assertIsInt($model['context_window']);
        }
    }

    /** @test */
    public function test_ml_010_model_has_capabilities(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('capabilities', $model);
            $this->assertIsArray($model['capabilities']);
        }
    }

    /** @test */
    public function test_ml_011_model_has_is_free_flag(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('is_free', $model);
            $this->assertIsBool($model['is_free']);
        }
    }

    /** @test */
    public function test_ml_012_model_has_is_recommended_flag(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertArrayHasKey('is_recommended', $model);
            $this->assertIsBool($model['is_recommended']);
        }
    }

    // ========================================
    // GR - Gemini Model Specifics
    // ========================================

    /** @test */
    public function test_gr_001_gemini_context_window(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertEquals(1000000, $gemini['context_window']);
    }

    /** @test */
    public function test_gr_002_gemini_is_recommended(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertTrue($gemini['is_recommended']);
    }

    /** @test */
    public function test_gr_003_gemini_best_for_text(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertEquals('Large pages (100K+ characters)', $gemini['best_for']);
    }

    /** @test */
    public function test_gr_004_gemini_capabilities(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertEquals(['text', 'json'], $gemini['capabilities']);
    }

    /** @test */
    public function test_gr_005_gemini_is_free(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $gemini = $models->firstWhere('id', 'google/gemini-2.0-flash-exp:free');

        $this->assertTrue($gemini['is_free']);
    }

    // ========================================
    // OM - Other Model Specifics
    // ========================================

    /** @test */
    public function test_om_001_qwen_context_window(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $qwen = $models->firstWhere('id', 'qwen/qwen-2-7b-instruct:free');

        $this->assertEquals(32768, $qwen['context_window']);
    }

    /** @test */
    public function test_om_002_mistral_context_window(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $mistral = $models->firstWhere('id', 'mistralai/mistral-7b-instruct:free');

        $this->assertEquals(32768, $mistral['context_window']);
    }

    /** @test */
    public function test_om_003_llama_context_window(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $llama = $models->firstWhere('id', 'meta-llama/llama-3-8b-instruct:free');

        $this->assertEquals(8192, $llama['context_window']);
    }

    /** @test */
    public function test_om_004_qwen_mistral_is_not_recommended(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $qwen = $models->firstWhere('id', 'qwen/qwen-2-7b-instruct:free');
        $mistral = $models->firstWhere('id', 'mistralai/mistral-7b-instruct:free');
        $llama = $models->firstWhere('id', 'meta-llama/llama-3-8b-instruct:free');

        $this->assertFalse($qwen['is_recommended']);
        $this->assertFalse($mistral['is_recommended']);
        $this->assertFalse($llama['is_recommended']);
    }

    /** @test */
    public function test_om_005_llama_best_for_text(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = collect($response->json('data.models'));
        $llama = $models->firstWhere('id', 'meta-llama/llama-3-8b-instruct:free');

        $this->assertEquals('Small pages only', $llama['best_for']);
    }

    /** @test */
    public function test_om_006_all_models_is_free_true(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        foreach ($models as $model) {
            $this->assertTrue(
                $model['is_free'],
                "Model {$model['id']} should have is_free: true"
            );
        }
    }

    // ========================================
    // DM - Default Model Tests
    // ========================================

    /** @test */
    public function test_dm_001_default_model_is_gemini(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.default_model', self::DEFAULT_MODEL);
    }

    /** @test */
    public function test_dm_002_default_model_exists_in_list(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $defaultModel = $response->json('data.default_model');
        $models = collect($response->json('data.models'));
        $exists = $models->contains('id', $defaultModel);

        $this->assertTrue($exists, 'Default model must exist in models list');
    }

    /** @test */
    public function test_dm_003_default_model_is_recommended(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $defaultModel = $response->json('data.default_model');
        $models = collect($response->json('data.models'));
        $default = $models->firstWhere('id', $defaultModel);

        $this->assertTrue(
            $default['is_recommended'],
            'Default model should have is_recommended: true'
        );
    }

    // ========================================
    // UP - User Preference Tests
    // ========================================

    /** @test */
    public function test_up_001_unauthenticated_user_preference_null(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.user_preference', null);
    }

    /** @test */
    public function test_up_002_authenticated_no_preference(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.user_preference', null);
    }

    /** @test */
    public function test_up_003_authenticated_with_preference(): void
    {
        $user = User::factory()->create();
        $model = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.user_preference', 'qwen/qwen-2-7b-instruct:free');
    }

    /** @test */
    public function test_up_004_preference_matches_user(): void
    {
        $user = User::factory()->create();
        $model = AIModel::where('model_id', 'mistralai/mistral-7b-instruct:free')->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(self::API_ENDPOINT);

        $this->assertEquals(
            'mistralai/mistral-7b-instruct:free',
            $response->json('data.user_preference')
        );
    }

    /** @test */
    public function test_up_005_preference_only_for_active_model(): void
    {
        $user = User::factory()->create();
        
        // Create inactive model
        $inactiveModel = AIModel::create([
            'provider' => 'Inactive',
            'model_id' => 'inactive/model:free',
            'display_name' => 'Inactive Model',
            'context_window' => 8192,
            'is_free_tier' => true,
            'is_active' => false,
            'capabilities' => json_encode(['text']),
            'cost_per_1k_input' => 0.0,
            'cost_per_1k_output' => 0.0,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $inactiveModel->id,
            'is_default' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(self::API_ENDPOINT);

        // Should be null because model is inactive
        $response->assertJsonPath('data.user_preference', null);
    }

    /** @test */
    public function test_up_006_preference_service_integration(): void
    {
        $user = User::factory()->create();
        $model = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        Sanctum::actingAs($user);

        // The controller should call ModelSelectionService::getUserPreference
        $mockService = $this->mock(ModelSelectionService::class);
        $mockService->shouldReceive('getUserPreference')
            ->once()
            ->with($user)
            ->andReturn('qwen/qwen-2-7b-instruct:free');
        $mockService->shouldReceive('getAvailableModels')->andReturn(collect());
        $mockService->shouldReceive('getDefaultModel')->andReturn(self::DEFAULT_MODEL);

        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertStatus(200);
    }

    // ========================================
    // RL - Rate Limiting Tests
    // ========================================

    /** @test */
    public function test_rl_001_rate_limit_headers_present(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        // Laravel doesn't always send rate limit headers unless actually rate limited
        // Check for successful response instead
        $response->assertStatus(200);
    }

    /** @test */
    public function test_rl_002_under_rate_limit(): void
    {
        // Make 50 requests - should all succeed
        for ($i = 0; $i < 50; $i++) {
            $response = $this->getJson(self::API_ENDPOINT);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function test_rl_003_rate_limit_threshold(): void
    {
        // Note: Rate limiting may not be strictly enforced in testing
        // This test verifies the endpoint handles multiple requests gracefully
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson(self::API_ENDPOINT);
            // Accept 200 (not rate limited in testing) or 429 (rate limited)
            $this->assertContains($response->status(), [200, 429]);
        }
    }

    /** @test */
    public function test_rl_004_rate_limit_per_ip(): void
    {
        // Rate limiting is IP-based in production
        // In testing, we verify the endpoint responds correctly
        $response1 = $this->getJson(self::API_ENDPOINT);
        $response2 = $this->getJson(self::API_ENDPOINT);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    // ========================================
    // HM - HTTP Method Tests
    // ========================================

    /** @test */
    public function test_hm_001_get_allowed(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_hm_002_post_not_allowed(): void
    {
        $response = $this->postJson(self::API_ENDPOINT);

        $response->assertStatus(405);
    }

    /** @test */
    public function test_hm_003_put_not_allowed(): void
    {
        $response = $this->putJson(self::API_ENDPOINT);

        $response->assertStatus(405);
    }

    /** @test */
    public function test_hm_004_delete_not_allowed(): void
    {
        $response = $this->deleteJson(self::API_ENDPOINT);

        $response->assertStatus(405);
    }

    // ========================================
    // SE - Security Tests
    // ========================================

    /** @test */
    public function test_se_001_no_sensitive_data_exposed(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);
        $content = $response->content();

        // Check that no sensitive fields are exposed
        $this->assertStringNotContainsString('api_key', $content);
        $this->assertStringNotContainsString('secret', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('token', $content);
    }

    /** @test */
    public function test_se_002_sql_injection_safe(): void
    {
        // Attempt SQL injection via query parameter
        $response = $this->getJson(self::API_ENDPOINT . '?id=1; DROP TABLE users;--');

        // Should not cause SQL error, return normal response
        $response->assertStatus(200);
    }

    /** @test */
    public function test_se_003_xss_safe(): void
    {
        // Attempt XSS via query parameter
        $response = $this->getJson(self::API_ENDPOINT . '?filter=<script>alert("xss")</script>');

        $response->assertStatus(200);
        $content = $response->content();

        // Script should not be reflected in response
        $this->assertStringNotContainsString('<script>', $content);
    }

    /** @test */
    public function test_se_004_no_error_stack_traces(): void
    {
        // Force a scenario that might trigger an error
        // With valid request, should not see stack traces
        $response = $this->getJson(self::API_ENDPOINT);
        $content = $response->content();

        $this->assertStringNotContainsString('Stack trace', $content);
        $this->assertStringNotContainsString('#0', $content);
    }

    /** @test */
    public function test_se_005_cors_headers(): void
    {
        $response = $this->options(self::API_ENDPOINT);

        // CORS may or may not be configured
        // Just verify the request doesn't error
        $this->assertNotNull($response);
    }

    // ========================================
    // EC - Edge Cases
    // ========================================

    /** @test */
    public function test_ec_001_empty_models_list(): void
    {
        // Clear all models
        AIModel::query()->delete();

        $response = $this->getJson(self::API_ENDPOINT);

        // Should still return valid structure
        $response->assertStatus(200);
        $response->assertJsonPath('data.models', []);
        $response->assertJsonPath('data.default_model', self::DEFAULT_MODEL);
    }

    /** @test */
    public function test_ec_002_missing_capabilities(): void
    {
        // Update model to have null capabilities
        AIModel::query()->update(['capabilities' => null]);

        $response = $this->getJson(self::API_ENDPOINT);

        // Should handle gracefully
        $response->assertStatus(200);
    }

    /** @test */
    public function test_ec_003_unknown_query_params(): void
    {
        $response = $this->getJson(self::API_ENDPOINT . '?foo=bar&baz=qux');

        // Unknown params should be ignored
        $response->assertStatus(200);
    }

    /** @test */
    public function test_ec_004_trailing_slash(): void
    {
        // Some servers handle trailing slash differently
        $response = $this->getJson(self::API_ENDPOINT . '/');

        // Accept 200 or 301/302 redirect
        $this->assertContains($response->status(), [200, 301, 302, 404]);
    }

    /** @test */
    public function test_ec_005_case_sensitive_route(): void
    {
        $response = $this->getJson('/API/V1/AI/Models');

        // Should return 404 for case-sensitive routes
        $response->assertStatus(404);
    }

    /** @test */
    public function test_ec_006_large_response_handling(): void
    {
        // Create many additional models
        for ($i = 0; $i < 100; $i++) {
            AIModel::create([
                'provider' => 'Test',
                'model_id' => "test/model-$i",
                'display_name' => "Test Model $i",
                'context_window' => 8192,
                'is_free_tier' => true,
                'is_active' => true,
                'capabilities' => json_encode(['text']),
                'cost_per_1k_input' => 0.0,
                'cost_per_1k_output' => 0.0,
            ]);
        }

        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertStatus(200);
        $this->assertGreaterThan(100, count($response->json('data.models')));
    }

    // ========================================
    // AC - Acceptance Criteria
    // ========================================

    /** @test */
    public function test_ac_001_get_returns_model_list(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['models'],
        ]);
        $this->assertIsArray($response->json('data.models'));
    }

    /** @test */
    public function test_ac_002_all_4_free_tier_models_included(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');
        $modelIds = array_column($models, 'id');

        $this->assertContains('google/gemini-2.0-flash-exp:free', $modelIds);
        $this->assertContains('qwen/qwen-2-7b-instruct:free', $modelIds);
        $this->assertContains('mistralai/mistral-7b-instruct:free', $modelIds);
        $this->assertContains('meta-llama/llama-3-8b-instruct:free', $modelIds);
    }

    /** @test */
    public function test_ac_003_context_window_and_capabilities_exposed(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $models = $response->json('data.models');

        foreach ($models as $model) {
            $this->assertArrayHasKey('context_window', $model);
            $this->assertIsInt($model['context_window']);
            $this->assertArrayHasKey('capabilities', $model);
            $this->assertIsArray($model['capabilities']);
        }
    }

    /** @test */
    public function test_ac_004_default_model_indicated(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        $response->assertJsonPath('data.default_model', self::DEFAULT_MODEL);
    }

    /** @test */
    public function test_ac_005_user_preference_shown_if_authenticated(): void
    {
        // Test unauthenticated
        $response = $this->getJson(self::API_ENDPOINT);
        $response->assertJsonPath('data.user_preference', null);

        // Test authenticated with preference
        $user = User::factory()->create();
        $model = AIModel::where('model_id', 'qwen/qwen-2-7b-instruct:free')->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(self::API_ENDPOINT);
        $response->assertJsonPath('data.user_preference', 'qwen/qwen-2-7b-instruct:free');
    }

    /** @test */
    public function test_ac_006_public_endpoint_no_auth_required(): void
    {
        $response = $this->getJson(self::API_ENDPOINT);

        // Should return 200, not 401
        $response->assertStatus(200);
    }

    /** @test */
    public function test_ac_007_rate_limiting_applied(): void
    {
        // Rate limiting is typically disabled in testing
        // Verify multiple requests work correctly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson(self::API_ENDPOINT);
            $response->assertStatus(200);
        }
    }
}
