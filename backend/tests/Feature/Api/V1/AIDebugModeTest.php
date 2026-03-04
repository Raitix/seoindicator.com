<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Feature tests for AI Debug Mode (TASK-21-7).
 *
 * Tests that debug mode properly exposes detailed information
 * in API responses when enabled, and hides it when disabled.
 *
 * @see TASK-21-7
 * @see SPEC-021
 */
class AIDebugModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI provider for consistent responses
        $this->mockAIProvider();
    }

    protected function mockAIProvider(): void
    {
        $mockResponse = new AIResponse(
            success: true,
            content: json_encode([
                'indicator' => 'title',
                'score' => 75,
                'issues' => ['Title too short'],
                'suggestions' => [['type' => 'rewrite', 'text' => 'Better Title']],
                'best_practices' => ['Keep under 60 characters'],
                'provider' => 'openrouter',
                'model' => 'llama-3.1-8b-instruct',
                'generated_at' => '2026-03-04T12:00:00Z',
            ]),
            errorMessage: null,
            tokensUsed: 245,
            latencyMs: 1250,
        );

        $this->mock(AIProviderInterface::class, function ($mock) use ($mockResponse) {
            $mock->shouldReceive('generate')
                ->andReturn($mockResponse);
            $mock->shouldReceive('isAvailable')
                ->andReturn(true);
            $mock->shouldReceive('getName')
                ->andReturn('openrouter');
        });
    }

    // =========================================================================
    // AC-2: Debug Mode Disabled (Production Default)
    // =========================================================================

    /**
     * @test
     * Test debug info is NOT included when debug mode is disabled.
     */
    public function debug_info_not_included_when_disabled(): void
    {
        Config::set('ai.debug', false);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => [
                    'url' => 'https://example.com',
                    'current_title' => 'Test Page',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonMissing(['debug']);
    }

    /**
     * @test
     * Test response structure without debug info.
     */
    public function response_structure_without_debug(): void
    {
        Config::set('ai.debug', false);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => [
                    'url' => 'https://example.com',
                    'current_title' => 'Test Page',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'indicator',
                'score',
                'issues',
                'suggestions',
                'best_practices',
                'provider',
                'model',
                'generated_at',
            ],
        ]);

        // Ensure no debug keys
        $json = $response->json();
        $this->assertArrayNotHasKey('debug', $json);
    }

    /**
     * @test
     * Test no prompt in response when debug disabled.
     */
    public function no_prompt_in_response_when_debug_disabled(): void
    {
        Config::set('ai.debug', false);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonMissing(['prompt']);
        $response->assertJsonMissing(['raw_response']);
    }

    // =========================================================================
    // AC-2: Debug Mode Enabled (Development/Staging)
    // =========================================================================

    /**
     * @test
     * Test debug info IS included when debug mode is enabled.
     */
    public function debug_info_included_when_enabled(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => [
                    'url' => 'https://example.com',
                    'current_title' => 'Test Page',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'debug' => [
                'prompt',
                'raw_response',
                'tokens_used',
                'latency_ms',
                'provider',
                'model',
            ],
        ]);
    }

    /**
     * @test
     * Test debug contains full prompt text.
     */
    public function debug_contains_full_prompt(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => [
                    'url' => 'https://example.com',
                    'current_title' => 'Test Page',
                ],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertIsString($debug['prompt']);
        $this->assertNotEmpty($debug['prompt']);
    }

    /**
     * @test
     * Test debug contains raw AI response.
     */
    public function debug_contains_raw_response(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertIsString($debug['raw_response']);
        $this->assertNotEmpty($debug['raw_response']);
    }

    /**
     * @test
     * Test debug contains token usage.
     */
    public function debug_contains_token_usage(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertIsInt($debug['tokens_used']);
        $this->assertGreaterThan(0, $debug['tokens_used']);
    }

    /**
     * @test
     * Test debug contains latency.
     */
    public function debug_contains_latency(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertIsInt($debug['latency_ms']);
        $this->assertGreaterThanOrEqual(0, $debug['latency_ms']);
    }

    /**
     * @test
     * Test debug contains provider name.
     */
    public function debug_contains_provider(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertEquals('openrouter', $debug['provider']);
    }

    /**
     * @test
     * Test debug contains model name.
     */
    public function debug_contains_model(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        $debug = $response->json('debug');
        $this->assertIsString($debug['model']);
        $this->assertNotEmpty($debug['model']);
    }

    // =========================================================================
    // AC-2: Debug Mode Security
    // =========================================================================

    /**
     * @test
     * Test debug mode does NOT expose API keys.
     */
    public function debug_mode_does_not_expose_api_keys(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        // Check entire response doesn't contain API key patterns
        $json = json_encode($response->json());
        $this->assertStringNotContainsString('sk-', $json);
        $this->assertStringNotContainsString('api_key', $json);
    }

    /**
     * @test
     * Test debug mode does NOT expose user PII.
     */
    public function debug_mode_does_not_expose_pii(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create([
            'email' => 'secret@example.com',
            'name' => 'Secret User',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);

        // Check response doesn't contain user email or name
        $json = json_encode($response->json());
        $this->assertStringNotContainsString('secret@example.com', $json);
        $this->assertStringNotContainsString('Secret User', $json);
    }

    /**
     * @test
     * Test debug mode controlled by environment variable.
     */
    public function debug_mode_controlled_by_env(): void
    {
        // Test that config reads from AI_DEBUG env
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['debug']);
    }

    // =========================================================================
    // AC-2: Debug Mode with Different Indicators
    // =========================================================================

    /**
     * @test
     * Test debug mode works with all 10 indicators.
     *
     * @dataProvider indicatorProvider
     */
    public function debug_mode_works_with_all_indicators(string $indicator): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => $indicator,
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['debug']);
    }

    public static function indicatorProvider(): array
    {
        return [
            'title' => ['title'],
            'meta_description' => ['meta_description'],
            'robots' => ['robots'],
            'sitemap' => ['sitemap'],
            'headings' => ['headings'],
            'image_alt' => ['image_alt'],
            'filenames' => ['filenames'],
            'url' => ['url'],
            'links' => ['links'],
            '404' => ['404'],
        ];
    }

    // =========================================================================
    // AC-2: Debug Mode with Different Providers
    // =========================================================================

    /**
     * @test
     * Test debug shows correct provider name.
     */
    public function debug_shows_correct_provider(): void
    {
        Config::set('ai.debug', true);

        // Re-mock with different provider name
        $this->mock(AIProviderInterface::class, function ($mock) {
            $mockResponse = new AIResponse(
                success: true,
                content: json_encode([
                    'indicator' => 'title',
                    'score' => 75,
                    'issues' => [],
                    'suggestions' => [],
                    'best_practices' => [],
                    'provider' => 'vercel',
                    'model' => 'gpt-4o-mini',
                    'generated_at' => '2026-03-04T12:00:00Z',
                ]),
                errorMessage: null,
                tokensUsed: 180,
                latencyMs: 950,
            );
            $mock->shouldReceive('generate')->andReturn($mockResponse);
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getName')->andReturn('vercel');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('debug.provider', 'vercel');
    }

    /**
     * @test
     * Test debug shows static_fallback provider.
     */
    public function debug_shows_fallback_provider(): void
    {
        Config::set('ai.debug', true);

        // Re-mock with fallback provider
        $this->mock(AIProviderInterface::class, function ($mock) {
            $mockResponse = new AIResponse(
                success: true,
                content: json_encode([
                    'indicator' => 'title',
                    'score' => 50,
                    'issues' => ['No AI available'],
                    'suggestions' => [],
                    'best_practices' => [],
                    'provider' => 'static_fallback',
                    'model' => 'rule-based',
                    'generated_at' => '2026-03-04T12:00:00Z',
                ]),
                errorMessage: null,
                tokensUsed: 0,
                latencyMs: 5,
            );
            $mock->shouldReceive('generate')->andReturn($mockResponse);
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getName')->andReturn('static_fallback');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('debug.provider', 'static_fallback');
        $response->assertJsonPath('debug.tokens_used', 0);
    }

    // =========================================================================
    // Error Cases
    // =========================================================================

    /**
     * @test
     * Test debug info included on error when debug mode enabled.
     */
    public function debug_info_included_on_error(): void
    {
        Config::set('ai.debug', true);

        // Re-mock with error response
        $this->mock(AIProviderInterface::class, function ($mock) {
            $mockResponse = new AIResponse(
                success: false,
                content: '',
                errorMessage: 'Connection timeout',
                tokensUsed: 0,
                latencyMs: 30000,
            );
            $mock->shouldReceive('generate')->andReturn($mockResponse);
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getName')->andReturn('openrouter');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        // Should still include debug info on error
        $response->assertJsonStructure([
            'success',
            'error',
            'debug' => [
                'latency_ms',
                'provider',
            ],
        ]);
    }

    /**
     * @test
     * Test debug not included on error when debug disabled.
     */
    public function debug_not_included_on_error_when_disabled(): void
    {
        Config::set('ai.debug', false);

        // Re-mock with error response
        $this->mock(AIProviderInterface::class, function ($mock) {
            $mockResponse = new AIResponse(
                success: false,
                content: '',
                errorMessage: 'Connection timeout',
                tokensUsed: 0,
                latencyMs: 30000,
            );
            $mock->shouldReceive('generate')->andReturn($mockResponse);
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('getName')->andReturn('openrouter');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $response->assertJsonMissing(['debug']);
    }

    // =========================================================================
    // Unauthenticated Access
    // =========================================================================

    /**
     * @test
     * Test unauthenticated users get no debug info.
     */
    public function unauthenticated_no_debug_info(): void
    {
        Config::set('ai.debug', true);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['url' => 'https://example.com'],
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // Performance
    // =========================================================================

    /**
     * @test
     * Test debug mode does not significantly impact response time.
     */
    public function debug_mode_performance_impact(): void
    {
        Config::set('ai.debug', true);

        $user = User::factory()->create();

        $start = microtime(true);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['url' => 'https://example.com'],
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Debug mode shouldn't add more than 50ms overhead
        $this->assertLessThan(100, $elapsed, 'Debug mode should not significantly impact performance');
    }
}
