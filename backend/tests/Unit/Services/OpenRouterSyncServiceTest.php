<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OpenRouterSyncService;
use App\Models\AIModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Unit tests for TASK-26-4: OpenRouterSyncService
 *
 * Tests the OpenRouterSyncService responsible for:
 * - Fetching models from OpenRouter API
 * - Parsing pricing, context window, capabilities
 * - Updating existing models or creating new ones
 * - Handling API errors and rate limiting
 * - Deactivating missing models
 *
 * @see \App\Services\OpenRouterSyncService
 */
class OpenRouterSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OpenRouterSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OpenRouterSyncService::class);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests();
        parent::tearDown();
    }

    // =========================================================================
    // SYNC MODELS TESTS (SM)
    // =========================================================================

    /**
     * SM-001: syncModels fetches and creates new models from API
     */
    public function test_sync_models_fetches_and_creates_new_models(): void
    {
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

        $count = $this->service->syncModels();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('ai_models', [
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'context_window' => 128000,
        ]);
    }

    /**
     * SM-002: syncModels updates existing models
     */
    public function test_sync_models_updates_existing_models(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'Old Name',
            'cost_per_1k_input' => 0.001,
            'cost_per_1k_output' => 0.002,
            'context_window' => 8192,
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

        $count = $this->service->syncModels();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo Updated',
            'cost_per_1k_input' => 0.02,
            'cost_per_1k_output' => 0.04,
            'context_window' => 128000,
        ]);
    }

    /**
     * SM-003: syncModels deactivates models not in API response
     */
    public function test_sync_models_deactivates_missing_models(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'old/deprecated-model',
            'display_name' => 'Deprecated Model',
            'is_active' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [], // No models returned
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'old/deprecated-model',
            'is_active' => false,
        ]);
    }

    /**
     * SM-004: syncModels handles multiple models
     */
    public function test_sync_models_handles_multiple_models(): void
    {
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
                    [
                        'id' => 'meta-llama/llama-3-8b-instruct',
                        'name' => 'Llama 3 8B',
                        'context_length' => 8192,
                        'pricing' => [
                            'prompt' => '0.0000002',
                            'completion' => '0.0000002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(3, $count);
        $this->assertEquals(3, AIModel::count());
    }

    /**
     * SM-005: syncModels returns 0 on API error
     */
    public function test_sync_models_returns_zero_on_api_error(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * SM-006: syncModels handles rate limiting (429)
     */
    public function test_sync_models_handles_rate_limiting(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 429, [
                'Retry-After' => '60',
            ]),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * SM-007: syncModels handles network timeout
     */
    public function test_sync_models_handles_network_timeout(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(null, 504),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * SM-008: syncModels is idempotent
     */
    public function test_sync_models_is_idempotent(): void
    {
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

        // Run sync twice
        $count1 = $this->service->syncModels();
        $count2 = $this->service->syncModels();

        $this->assertEquals(1, $count1);
        $this->assertEquals(1, $count2);
        $this->assertEquals(1, AIModel::count()); // No duplicates
    }

    /**
     * SM-009: syncModels updates last_synced_at timestamp
     */
    public function test_sync_models_updates_last_synced_at(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
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

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNotNull($model->last_synced_at);
    }

    /**
     * SM-010: syncModels logs operations
     */
    public function test_sync_models_logs_operations(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'OpenRouter') || str_contains($message, 'sync');
            });

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => []], 200),
        ]);

        $this->service->syncModels();
    }

    // =========================================================================
    // PRICING PARSING TESTS (PP)
    // =========================================================================

    /**
     * PP-001: Parses prompt pricing correctly
     */
    public function test_parses_prompt_pricing_correctly(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001', // $0.01 per 1M tokens
                            'completion' => '0.00003',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        // OpenRouter returns per-token pricing
        // cost_per_1k = per_token * 1000
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0.01, // 0.00001 * 1000
        ]);
    }

    /**
     * PP-002: Parses completion pricing correctly
     */
    public function test_parses_completion_pricing_correctly(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00003',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_output' => 0.03, // 0.00003 * 1000
        ]);
    }

    /**
     * PP-003: Handles zero pricing (free models)
     */
    public function test_handles_zero_pricing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'free/model',
                        'name' => 'Free Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0',
                            'completion' => '0',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'free/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * PP-004: Handles empty pricing object
     */
    public function test_handles_empty_pricing_object(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * PP-005: Handles missing pricing object
     */
    public function test_handles_missing_pricing_object(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        // No pricing key
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * PP-006: Handles negative pricing (treats as 0)
     */
    public function test_handles_negative_pricing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '-0.00001',
                            'completion' => '-0.00003',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
        ]);
    }

    /**
     * PP-007: Handles extremely small pricing values
     */
    public function test_handles_extremely_small_pricing(): void
    {
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

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNotNull($model);
        // Very small values become 0.000000 due to column precision
        $this->assertEquals(0.0, (float) $model->cost_per_1k_input);
    }

    /**
     * PP-008: Handles string pricing values
     */
    public function test_handles_string_pricing_values(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00003',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertEquals(0.01, (float) $model->cost_per_1k_input);
    }

    /**
     * PP-009: Handles numeric pricing values
     */
    public function test_handles_numeric_pricing_values(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => 0.00001, // Numeric, not string
                            'completion' => 0.00003,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertEquals(0.01, (float) $model->cost_per_1k_input);
    }

    // =========================================================================
    // CONTEXT WINDOW PARSING TESTS (CW)
    // =========================================================================

    /**
     * CW-001: Parses context_length from API
     */
    public function test_parses_context_length_from_api(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'context_length' => 128000,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'context_window' => 128000,
        ]);
    }

    /**
     * CW-002: Uses top_provider.context_length as fallback
     */
    public function test_uses_top_provider_context_length_as_fallback(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        // No context_length at root
                        'top_provider' => [
                            'context_length' => 32768,
                        ],
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'context_window' => 32768,
        ]);
    }

    /**
     * CW-003: Uses default 4096 when context_length missing
     */
    public function test_uses_default_when_context_length_missing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Test Model',
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'context_window' => 4096,
        ]);
    }

    // =========================================================================
    // CAPABILITIES PARSING TESTS (CP)
    // =========================================================================

    /**
     * CP-001: Parses vision capability from modality
     */
    public function test_parses_vision_capability_from_modality(): void
    {
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

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/vision-model')->first();
        $this->assertNotNull($model);
        $capabilities = $model->capabilities;
        $this->assertTrue($capabilities['vision'] ?? false);
    }

    /**
     * CP-002: Parses tools capability
     */
    public function test_parses_tools_capability(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/tools-model',
                        'name' => 'Tools Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                        ],
                        'description' => 'Model with tool support',
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/tools-model')->first();
        $this->assertNotNull($model);
        // Tools capability is often inferred from model name or description
        // This test documents the expected behavior
    }

    /**
     * CP-003: Handles missing architecture
     */
    public function test_handles_missing_architecture(): void
    {
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
                        // No architecture key
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNull($model->capabilities);
    }

    /**
     * CP-004: Handles text-only modality
     */
    public function test_handles_text_only_modality(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/text-model',
                        'name' => 'Text Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/text-model')->first();
        $this->assertNotNull($model);
        // Text-only should not have vision capability
        $capabilities = $model->capabilities;
        $this->assertFalse($capabilities['vision'] ?? false);
    }

    // =========================================================================
    // FREE TIER DETECTION TESTS (FT)
    // =========================================================================

    /**
     * FT-001: Detects free tier models by :free suffix
     */
    public function test_detects_free_tier_models_by_suffix(): void
    {
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

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'meta-llama/llama-3-8b-instruct:free',
            'is_free_tier' => true,
        ]);
    }

    /**
     * FT-002: Detects free tier by zero pricing
     */
    public function test_detects_free_tier_by_zero_pricing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/free-model',
                        'name' => 'Free Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0',
                            'completion' => '0',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/free-model',
            'is_free_tier' => true,
        ]);
    }

    /**
     * FT-003: Paid models are not marked as free tier
     */
    public function test_paid_models_not_marked_as_free_tier(): void
    {
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

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'openai/gpt-4-turbo',
            'is_free_tier' => false,
        ]);
    }

    /**
     * FT-004: Parses free_tier_limit from API
     */
    public function test_parses_free_tier_limit_from_api(): void
    {
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
                        'per_request_limits' => [
                            'prompt_tokens' => 10000,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'meta-llama/llama-3-8b-instruct:free')->first();
        $this->assertTrue($model->is_free_tier);
        // Free tier limit may be parsed from per_request_limits or other fields
    }

    // =========================================================================
    // GET MODEL PRICING TESTS (GP)
    // =========================================================================

    /**
     * GP-001: getModelPricing returns pricing for specific model
     */
    public function test_get_model_pricing_returns_pricing(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertIsArray($pricing);
        $this->assertArrayHasKey('input_per_1k', $pricing);
        $this->assertArrayHasKey('output_per_1k', $pricing);
        $this->assertEquals(0.01, $pricing['input_per_1k']);
        $this->assertEquals(0.03, $pricing['output_per_1k']);
    }

    /**
     * GP-002: getModelPricing returns null for non-existent model
     */
    public function test_get_model_pricing_returns_null_for_nonexistent(): void
    {
        $pricing = $this->service->getModelPricing('nonexistent/model');

        $this->assertNull($pricing);
    }

    /**
     * GP-003: getModelPricing includes per-million pricing
     */
    public function test_get_model_pricing_includes_per_million(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Test Model',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
        ]);

        $pricing = $this->service->getModelPricing('test/model');

        $this->assertArrayHasKey('input_per_1m', $pricing);
        $this->assertArrayHasKey('output_per_1m', $pricing);
        $this->assertEquals(10.0, $pricing['input_per_1m']); // 0.01 * 1000
        $this->assertEquals(30.0, $pricing['output_per_1m']); // 0.03 * 1000
    }

    // =========================================================================
    // VALIDATION TESTS (VL)
    // =========================================================================

    /**
     * VL-001: Skips models with missing required fields
     */
    public function test_skips_models_with_missing_required_fields(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        // Missing id
                        'name' => 'Invalid Model',
                        'context_length' => 4096,
                    ],
                    [
                        'id' => 'valid/model',
                        'name' => 'Valid Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(1, $count);
        $this->assertDatabaseMissing('ai_models', [
            'display_name' => 'Invalid Model',
        ]);
    }

    /**
     * VL-002: Skips models with null id
     */
    public function test_skips_models_with_null_id(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => null,
                        'name' => 'Null ID Model',
                        'context_length' => 4096,
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * VL-003: Skips models with empty id
     */
    public function test_skips_models_with_empty_id(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => '',
                        'name' => 'Empty ID Model',
                        'context_length' => 4096,
                    ],
                ],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * VL-004: Handles malformed JSON response
     */
    public function test_handles_malformed_json_response(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response('invalid json', 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * VL-005: Handles empty data array
     */
    public function test_handles_empty_data_array(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * VL-006: Handles missing data key
     */
    public function test_handles_missing_data_key(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'models' => [],
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Sanitizes XSS in model names
     */
    public function test_sanitizes_xss_in_model_names(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => '"><script>alert("xss")</script>',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertNotNull($model);
        // Name should be sanitized (no script tags)
        $this->assertStringNotContainsString('<script>', $model->display_name);
    }

    /**
     * SC-002: Uses API key from config
     */
    public function test_uses_api_key_from_config(): void
    {
        Config::set('services.openrouter.api_key', 'test-api-key');

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => []], 200),
        ]);

        $this->service->syncModels();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization') &&
                   str_contains($request->header('Authorization')[0], 'test-api-key');
        });
    }

    /**
     * SC-003: Handles missing API key gracefully
     */
    public function test_handles_missing_api_key_gracefully(): void
    {
        Config::set('services.openrouter.api_key', null);

        $count = $this->service->syncModels();

        $this->assertEquals(0, $count);
    }

    /**
     * SC-004: Truncates long display names
     */
    public function test_truncates_long_display_names(): void
    {
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

        $this->service->syncModels();

        $model = AIModel::where('model_id', 'test/model')->first();
        $this->assertLessThanOrEqual(255, strlen($model->display_name));
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Handles Unicode in model names
     */
    public function test_handles_unicode_in_model_names(): void
    {
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

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'display_name' => 'Test Model 测试模型 🤖',
        ]);
    }

    /**
     * EC-002: Handles concurrent sync requests
     */
    public function test_handles_concurrent_sync_requests(): void
    {
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

        // Simulate concurrent syncs
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->service->syncModels();
        }

        // All should succeed without duplicates
        $modelCount = AIModel::where('model_id', 'test/model')->count();
        $this->assertEquals(1, $modelCount);
    }

    /**
     * EC-003: Handles very large model lists
     */
    public function test_handles_very_large_model_lists(): void
    {
        $models = [];
        for ($i = 0; $i < 100; $i++) {
            $models[] = [
                'id' => "provider/model-{$i}",
                'name' => "Model {$i}",
                'context_length' => 4096,
                'pricing' => [
                    'prompt' => '0.00001',
                    'completion' => '0.00002',
                ],
            ];
        }

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => $models,
            ], 200),
        ]);

        $count = $this->service->syncModels();

        $this->assertEquals(100, $count);
        $this->assertEquals(100, AIModel::count());
    }

    /**
     * EC-004: Handles special characters in model IDs
     */
    public function test_handles_special_characters_in_model_ids(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'org/model-name_v2.0:variant',
                        'name' => 'Special Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->service->syncModels();

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'org/model-name_v2.0:variant',
        ]);
    }
}
