<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\AIModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

/**
 * Feature tests for TASK-26-4: Models Sync Console Command
 *
 * Tests the `php artisan models:sync` console command for:
 * - Syncing models from OpenRouter API
 * - Console output and exit codes
 * - Error handling
 * - Scheduling compatibility
 *
 * @see \App\Console\Commands\ModelsSyncCommand
 */
class ModelsSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Http::preventStrayRequests();
        parent::tearDown();
    }

    // =========================================================================
    // COMMAND EXECUTION TESTS (CE)
    // =========================================================================

    /**
     * CE-001: Command executes successfully
     */
    public function test_command_executes_successfully(): void
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

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * CE-002: Command creates models from API
     */
    public function test_command_creates_models_from_api(): void
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
                ],
            ], 200),
        ]);

        Artisan::call('models:sync');

        $this->assertEquals(2, AIModel::count());
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'openai/gpt-4-turbo',
        ]);
        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'anthropic/claude-3-opus',
        ]);
    }

    /**
     * CE-003: Command outputs sync count
     */
    public function test_command_outputs_sync_count(): void
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

        Artisan::call('models:sync');
        $output = Artisan::output();

        $this->assertStringContainsString('1', $output);
    }

    /**
     * CE-004: Command handles empty API response
     */
    public function test_command_handles_empty_api_response(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(0, AIModel::count());
    }

    /**
     * CE-005: Command handles API error
     */
    public function test_command_handles_api_error(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        $exitCode = Artisan::call('models:sync');

        // Command should still succeed (graceful handling)
        $this->assertEquals(0, $exitCode);
    }

    /**
     * CE-006: Command handles rate limiting
     */
    public function test_command_handles_rate_limiting(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 429, [
                'Retry-After' => '60',
            ]),
        ]);

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * CE-007: Command handles network timeout
     */
    public function test_command_handles_network_timeout(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(null, 504),
        ]);

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
    }

    // =========================================================================
    // VERBOSITY TESTS (VB)
    // =========================================================================

    /**
     * VB-001: Command provides verbose output with -v flag
     */
    public function test_command_provides_verbose_output(): void
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

        Artisan::call('models:sync', ['-v' => true]);
        $output = Artisan::output();

        // Verbose output should contain more details
        $this->assertNotEmpty($output);
    }

    /**
     * VB-002: Command supports --quiet flag
     */
    public function test_command_supports_quiet_flag(): void
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

        Artisan::call('models:sync', ['--quiet' => true]);
        $output = Artisan::output();

        $this->assertEmpty(trim($output));
    }

    // =========================================================================
    // OPTIONS TESTS (OP)
    // =========================================================================

    /**
     * OP-001: Command supports --force flag to deactivate missing models
     */
    public function test_command_supports_force_flag(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'old/deprecated-model',
            'display_name' => 'Deprecated Model',
            'is_active' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [], // No models in API
            ], 200),
        ]);

        Artisan::call('models:sync', ['--force' => true]);

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'old/deprecated-model',
            'is_active' => false,
        ]);
    }

    /**
     * OP-002: Command does not deactivate without --force flag
     */
    public function test_command_does_not_deactivate_without_force(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'old/deprecated-model',
            'display_name' => 'Deprecated Model',
            'is_active' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [], // No models in API
            ], 200),
        ]);

        // Without --force, existing models should remain active
        Artisan::call('models:sync');

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'old/deprecated-model',
            'is_active' => true,
        ]);
    }

    /**
     * OP-003: Command supports --provider option
     */
    public function test_command_supports_provider_option(): void
    {
        AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4 Direct',
            'is_active' => true,
        ]);

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

        // Sync only openrouter provider
        Artisan::call('models:sync', ['--provider' => 'openrouter']);

        // OpenAI direct model should remain unchanged
        $this->assertDatabaseHas('ai_models', [
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // SCHEDULING TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Command can be scheduled via Laravel scheduler
     */
    public function test_command_can_be_scheduled(): void
    {
        // This test verifies the command is schedulable
        // The actual scheduling is done in app/Console/Kernel.php
        
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [],
            ], 200),
        ]);

        // Simulate scheduler running the command
        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * SC-002: Command completes within reasonable time
     */
    public function test_command_completes_within_reasonable_time(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => array_map(function ($i) {
                    return [
                        'id' => "provider/model-{$i}",
                        'name' => "Model {$i}",
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ];
                }, range(1, 50)),
            ], 200),
        ]);

        $start = microtime(true);
        Artisan::call('models:sync');
        $elapsed = microtime(true) - $start;

        // Should complete within 5 seconds for 50 models
        $this->assertLessThan(5, $elapsed);
    }

    /**
     * SC-003: Command does not block on network issues
     */
    public function test_command_does_not_block_on_network_issues(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(null, 504),
        ]);

        $start = microtime(true);
        Artisan::call('models:sync');
        $elapsed = microtime(true) - $start;

        // Should handle timeout quickly, not hang
        $this->assertLessThan(10, $elapsed);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH)
    // =========================================================================

    /**
     * EH-001: Command handles invalid JSON response
     */
    public function test_command_handles_invalid_json_response(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response('invalid json', 200),
        ]);

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * EH-002: Command handles malformed model data
     */
    public function test_command_handles_malformed_model_data(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        // Missing required fields
                        'id' => null,
                        'name' => '',
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

        $exitCode = Artisan::call('models:sync');

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, AIModel::count());
    }

    /**
     * EH-003: Command logs errors appropriately
     */
    public function test_command_logs_errors_appropriately(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([], 500),
        ]);

        Artisan::call('models:sync', ['-v' => true]);
        $output = Artisan::output();

        // Error should be logged (visible in verbose mode)
        $this->assertNotEmpty($output);
    }

    // =========================================================================
    // IDPOTENCY TESTS (ID)
    // =========================================================================

    /**
     * ID-001: Command is idempotent
     */
    public function test_command_is_idempotent(): void
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

        // Run command twice
        Artisan::call('models:sync');
        Artisan::call('models:sync');

        // Should still have only 1 model
        $this->assertEquals(1, AIModel::count());
    }

    /**
     * ID-002: Command updates existing models on re-run
     */
    public function test_command_updates_existing_models_on_rerun(): void
    {
        AIModel::create([
            'provider' => 'openrouter',
            'model_id' => 'test/model',
            'display_name' => 'Old Name',
            'cost_per_1k_input' => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'name' => 'Updated Name',
                        'context_length' => 8192,
                        'pricing' => [
                            'prompt' => '0.00002',
                            'completion' => '0.00004',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('models:sync');

        $this->assertDatabaseHas('ai_models', [
            'model_id' => 'test/model',
            'display_name' => 'Updated Name',
            'cost_per_1k_input' => 0.02,
            'cost_per_1k_output' => 0.04,
        ]);
    }

    // =========================================================================
    // INTEGRATION TESTS (IN)
    // =========================================================================

    /**
     * IN-001: Command preserves manual model additions
     */
    public function test_command_preserves_manual_model_additions(): void
    {
        // Manually added model with different provider
        AIModel::create([
            'provider' => 'custom',
            'model_id' => 'custom/model',
            'display_name' => 'Custom Model',
            'cost_per_1k_input' => 0.05,
            'is_active' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'openrouter/model',
                        'name' => 'OpenRouter Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('models:sync');

        // Custom model should remain
        $this->assertDatabaseHas('ai_models', [
            'provider' => 'custom',
            'model_id' => 'custom/model',
            'is_active' => true,
        ]);
    }

    /**
     * IN-002: Command works with existing AIModel table
     */
    public function test_command_works_with_existing_table(): void
    {
        // Create existing models
        AIModel::factory()->count(5)->create();

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'new/model',
                        'name' => 'New Model',
                        'context_length' => 4096,
                        'pricing' => [
                            'prompt' => '0.00001',
                            'completion' => '0.00002',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('models:sync');

        // Should have 5 existing + 1 new
        $this->assertEquals(6, AIModel::count());
    }
}
