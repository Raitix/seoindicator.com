<?php

namespace Tests\Unit\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\Providers\OpenRouterProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit tests for OpenRouterProvider.
 *
 * Tests cover:
 * - AC-1: Class creation and interface implementation
 * - AC-2: API integration (with HTTP mocking)
 * - AC-3: Response handling
 * - AC-4: Error handling
 * - AC-5: Availability check
 * - AC-6: Configuration
 *
 * @see TASK-21-2
 */
class OpenRouterProviderTest extends TestCase
{
    // =================================================================
    // AC-1: OpenRouterProvider Class Created
    // =================================================================

    /** @test ORC-001: Class exists at correct path */
    public function test_class_exists_at_correct_path(): void
    {
        $this->assertTrue(class_exists(OpenRouterProvider::class));
    }

    /** @test ORC-002: Implements AIProviderInterface */
    public function test_implements_ai_provider_interface(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertInstanceOf(AIProviderInterface::class, $provider);
    }

    /** @test ORC-003: Constructor accepts config array */
    public function test_constructor_accepts_config_array(): void
    {
        $config = [
            'api_key' => 'test-key',
            'model' => 'custom-model',
        ];

        $provider = new OpenRouterProvider($config);

        $this->assertInstanceOf(OpenRouterProvider::class, $provider);
    }

    /** @test ORC-004: Constructor validates required config (missing api_key) */
    public function test_constructor_validates_required_config_missing_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('api_key');

        new OpenRouterProvider(['model' => 'llama']);
    }

    /** @test ORC-005: Constructor sets default model */
    public function test_constructor_sets_default_model(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        // Model should default to Llama 3.1 8B free
        $this->assertStringContainsString('llama', strtolower($provider->getModel()));
    }

    /** @test ORC-006: Constructor sets default timeout */
    public function test_constructor_sets_default_timeout(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertEquals(30, $provider->getTimeout());
    }

    /** @test ORC-007: Constructor sets custom timeout */
    public function test_constructor_sets_custom_timeout(): void
    {
        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'timeout' => 60,
        ]);

        $this->assertEquals(60, $provider->getTimeout());
    }

    /** @test ORC-008: Dependency injection support */
    public function test_dependency_injection_support(): void
    {
        // Bind the provider in the container
        $this->app->bind(OpenRouterProvider::class, function ($app) {
            return new OpenRouterProvider([
                'api_key' => 'container-key',
            ]);
        });

        $provider = $this->app->make(OpenRouterProvider::class);

        $this->assertInstanceOf(OpenRouterProvider::class, $provider);
    }

    // =================================================================
    // AC-2: API Integration (Unit Tests with HTTP Mocking)
    // =================================================================

    /** @test API-001: Generate makes POST request */
    public function test_generate_makes_post_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST' &&
                str_contains($request->url(), 'openrouter.ai/api/v1/chat/completions');
        });
    }

    /** @test API-002: Authorization header format */
    public function test_authorization_header_format(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'secret-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-key');
        });
    }

    /** @test API-003: Required header HTTP-Referer */
    public function test_required_header_http_referer(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            return $request->hasHeader('HTTP-Referer');
        });
    }

    /** @test API-004: Required header X-Title */
    public function test_required_header_x_title(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Title');
        });
    }

    /** @test API-005: Request body includes model */
    public function test_request_body_includes_model(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'model' => 'test-model',
        ]);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['model']) && $body['model'] === 'test-model';
        });
    }

    /** @test API-006: Request body includes messages */
    public function test_request_body_includes_messages(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['messages']) && is_array($body['messages']);
        });
    }

    /** @test API-007: Request body includes max_tokens */
    public function test_request_body_includes_max_tokens(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'max_tokens' => 1000,
        ]);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['max_tokens']) && $body['max_tokens'] === 1000;
        });
    }

    /** @test API-008: Request body includes temperature */
    public function test_request_body_includes_temperature(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return array_key_exists('temperature', $body);
        });
    }

    /** @test API-009: Custom max_tokens override */
    public function test_custom_max_tokens_override(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt', ['max_tokens' => 500]);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return $body['max_tokens'] === 500;
        });
    }

    /** @test API-010: Custom temperature override */
    public function test_custom_temperature_override(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt', ['temperature' => 0.5]);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return $body['temperature'] === 0.5;
        });
    }

    /** @test API-011: Configurable timeout (default) */
    public function test_configurable_timeout_default(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertEquals(30, $provider->getTimeout());
    }

    /** @test API-012: Configurable timeout (custom) */
    public function test_configurable_timeout_custom(): void
    {
        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'timeout' => 60,
        ]);

        $this->assertEquals(60, $provider->getTimeout());
    }

    /** @test API-013: System message in request */
    public function test_system_message_in_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'system_prompt' => 'You are an SEO expert.',
        ]);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $hasSystemMessage = collect($body['messages'] ?? [])
                ->firstWhere('role', 'system');
            return $hasSystemMessage !== null;
        });
    }

    /** @test API-014: User message in request */
    public function test_user_message_in_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $provider->generate('test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $userMessage = collect($body['messages'] ?? [])
                ->firstWhere('role', 'user');
            return $userMessage !== null && $userMessage['content'] === 'test prompt';
        });
    }

    // =================================================================
    // AC-3: Response Handling
    // =================================================================

    /** @test RES-001: Parse successful JSON response */
    public function test_parse_successful_json_response(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertTrue($response->success);
    }

    /** @test RES-002: Extract content from choices */
    public function test_extract_content_from_choices(): void
    {
        $expectedContent = 'This is the AI response content.';

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => $expectedContent]],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertEquals($expectedContent, $response->content);
    }

    /** @test RES-003: Extract prompt tokens */
    public function test_extract_prompt_tokens(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'test']]],
                'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 50, 'total_tokens' => 200],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        // tokensUsed should be total tokens
        $this->assertEquals(200, $response->tokensUsed);
    }

    /** @test RES-004: Extract completion tokens */
    public function test_extract_completion_tokens(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertNotNull($response->tokensUsed);
    }

    /** @test RES-005: Calculate total tokens */
    public function test_calculate_total_tokens(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'test']]],
                'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 50, 'total_tokens' => 200],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertEquals(200, $response->tokensUsed);
    }

    /** @test RES-006: Response latency tracking */
    public function test_response_latency_tracking(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertNotNull($response->latencyMs);
        $this->assertGreaterThanOrEqual(0, $response->latencyMs);
    }

    /** @test RES-007: Handle non-streaming response */
    public function test_handle_non_streaming_response(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->successfulResponse(), 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertTrue($response->success);
        $this->assertNotNull($response->content);
    }

    /** @test RES-008: Handle empty content */
    public function test_handle_empty_content(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => '']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 0, 'total_tokens' => 10],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertTrue($response->success);
        $this->assertEquals('', $response->content);
    }

    /** @test RES-009: Handle null content gracefully */
    public function test_handle_null_content_gracefully(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => null]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 0, 'total_tokens' => 10],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertTrue($response->success);
        $this->assertNull($response->content);
    }

    /** @test RES-010: JSON response with special chars */
    public function test_json_response_with_special_chars(): void
    {
        $specialContent = 'Hello 世界 🌍 <script>alert("xss")</script>';

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => $specialContent]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertEquals($specialContent, $response->content);
    }

    // =================================================================
    // AC-4: Error Handling
    // =================================================================

    /** @test ERR-001: HTTP 400 Bad Request */
    public function test_http_400_bad_request(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Bad request']], 400),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('400', $response->errorMessage);
    }

    /** @test ERR-002: HTTP 401 Unauthorized */
    public function test_http_401_unauthorized(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Invalid API key']], 401),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('invalid', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-003: HTTP 403 Forbidden */
    public function test_http_403_forbidden(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Access denied']], 403),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('denied', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-004: HTTP 404 Not Found */
    public function test_http_404_not_found(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Not found']], 404),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertNotNull($response->errorMessage);
    }

    /** @test ERR-005: HTTP 429 Rate Limited */
    public function test_http_429_rate_limited(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429, ['Retry-After' => '60']),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('rate', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-006: HTTP 500 Server Error */
    public function test_http_500_server_error(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Internal server error']], 500),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('server', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-007: HTTP 502 Bad Gateway */
    public function test_http_502_bad_gateway(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Bad gateway']], 502),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertNotNull($response->errorMessage);
    }

    /** @test ERR-008: HTTP 503 Service Unavailable */
    public function test_http_503_service_unavailable(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'Service unavailable']], 503),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('unavailable', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-009: Request timeout */
    public function test_request_timeout(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::timeout(),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('timeout', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-010: Connection refused */
    public function test_connection_refused(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(null, 0),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertNotNull($response->errorMessage);
    }

    /** @test ERR-011: Malformed JSON response */
    public function test_malformed_json_response(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response('not valid json {{{', 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('invalid', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-012: Empty response body */
    public function test_empty_response_body(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response('', 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('empty', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-013: Missing choices array */
    public function test_missing_choices_array(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['usage' => ['total_tokens' => 10]], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('format', strtolower($response->errorMessage ?? ''));
    }

    /** @test ERR-014: Empty choices array */
    public function test_empty_choices_array(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['choices' => []], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertNotNull($response->errorMessage);
    }

    /** @test ERR-015: Missing message in choice */
    public function test_missing_message_in_choice(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['choices' => [['finish_reason' => 'stop']]], 200),
        ]);

        $provider = new OpenRouterProvider(['api_key' => 'test-key']);
        $response = $provider->generate('test prompt');

        $this->assertFalse($response->success);
        $this->assertStringContainsStringIgnoringCase('format', strtolower($response->errorMessage ?? ''));
    }

    // =================================================================
    // AC-5: Availability Check
    // =================================================================

    /** @test AVL-001: API key configured */
    public function test_is_available_with_api_key(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertTrue($provider->isAvailable());
    }

    /** @test AVL-002: API key missing */
    public function test_is_available_missing_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpenRouterProvider([]);
    }

    /** @test AVL-003: API key is null */
    public function test_is_available_null_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpenRouterProvider(['api_key' => null]);
    }

    /** @test AVL-004: API key is empty string */
    public function test_is_available_empty_api_key(): void
    {
        $provider = new OpenRouterProvider(['api_key' => '']);

        $this->assertFalse($provider->isAvailable());
    }

    /** @test AVL-005: API key is whitespace */
    public function test_is_available_whitespace_api_key(): void
    {
        $provider = new OpenRouterProvider(['api_key' => '   ']);

        $this->assertFalse($provider->isAvailable());
    }

    /** @test AVL-006: Provider name returns correctly */
    public function test_get_name_returns_openrouter(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertEquals('openrouter', $provider->getName());
    }

    // =================================================================
    // AC-6: Configuration
    // =================================================================

    /** @test CFG-001: Reads from config array */
    public function test_reads_from_config_array(): void
    {
        $config = [
            'api_key' => 'config-key',
            'model' => 'config-model',
            'timeout' => 45,
        ];

        $provider = new OpenRouterProvider($config);

        $this->assertEquals('config-model', $provider->getModel());
        $this->assertEquals(45, $provider->getTimeout());
    }

    /** @test CFG-005: Default model when not specified */
    public function test_default_model_when_not_specified(): void
    {
        $provider = new OpenRouterProvider(['api_key' => 'test-key']);

        $this->assertEquals(
            'meta-llama/llama-3.1-8b-instruct:free',
            $provider->getModel()
        );
    }

    /** @test CFG-008: Validates timeout is positive */
    public function test_validates_timeout_is_positive(): void
    {
        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'timeout' => -10,
        ]);

        // Should use default timeout
        $this->assertEquals(30, $provider->getTimeout());
    }

    /** @test CFG-009: Validates max_tokens is positive */
    public function test_validates_max_tokens_is_positive(): void
    {
        $provider = new OpenRouterProvider([
            'api_key' => 'test-key',
            'max_tokens' => 0,
        ]);

        // Should use default max_tokens
        $this->assertEquals(1000, $provider->getMaxTokens());
    }

    // =================================================================
    // Helper Methods
    // =================================================================

    /**
     * Get a successful OpenRouter response.
     */
    private function successfulResponse(): array
    {
        return [
            'id' => 'gen-test123',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'This is a test response.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 150,
                'completion_tokens' => 200,
                'total_tokens' => 350,
            ],
        ];
    }
}
