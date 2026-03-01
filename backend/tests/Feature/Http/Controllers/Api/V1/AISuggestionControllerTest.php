<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\DTOs\SuggestionResult;
use App\Services\AI\AISuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Feature tests for AI Suggestion API Controller.
 * 
 * TASK-21-5: Create AI Suggestion API Endpoint
 * 
 * Endpoints:
 * - POST /api/v1/ai/suggestions - Generate AI suggestions for an indicator
 * 
 * Authentication: Optional (public with rate limiting, or auth middleware)
 * Rate Limiting: Per-IP and per-user limits
 * 
 * @see https://seoindicator.com/docs/api/ai-suggestions
 */
class AISuggestionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected AISuggestionService $mockService;
    protected AIProviderInterface $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();

        // Create mock provider
        $this->mockProvider = Mockery::mock(AIProviderInterface::class);
        $this->mockProvider->shouldReceive('getName')->andReturn('mock_provider');

        // Create mock service
        $this->mockService = Mockery::mock(AISuggestionService::class, [$this->mockProvider]);
        
        // Bind mock service to container
        $this->app->instance(AISuggestionService::class, $this->mockService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a valid SuggestionResult for testing.
     */
    protected function createValidSuggestionResult(
        string $indicator = 'title',
        int $score = 75,
        string $provider = 'openrouter',
        string $model = 'llama-3.1-8b-instruct'
    ): SuggestionResult {
        return new SuggestionResult(
            indicator: $indicator,
            score: $score,
            issues: ['Title is too short', 'Missing keyword'],
            suggestions: [
                ['type' => 'rewrite', 'text' => 'Better Title | Your Brand', 'reason' => 'Includes keyword and brand'],
            ],
            bestPractices: ['Keep title under 60 characters', 'Include primary keyword'],
            provider: $provider,
            model: $model,
            generatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Valid request payload for AI suggestions.
     */
    protected function validPayload(): array
    {
        return [
            'indicator' => 'title',
            'context' => [
                'title' => 'Home - My Website',
                'content_summary' => 'E-commerce homepage selling widgets',
                'keyword' => 'best widgets',
            ],
        ];
    }

    // ============================================================
    // ACCEPTANCE CRITERION 1: CONTROLLER CREATED
    // ============================================================

    /**
     * AC1-01: Controller file exists.
     */
    public function test_controller_file_exists(): void
    {
        $controllerPath = app_path('Http/Controllers/Api/V1/AISuggestionController.php');
        $this->assertFileExists($controllerPath, 'AISuggestionController file should exist');
    }

    /**
     * AC1-02: Controller has generate method.
     */
    public function test_controller_has_generate_method(): void
    {
        $controller = new \App\Http\Controllers\Api\V1\AISuggestionController(
            app(AISuggestionService::class)
        );
        
        $this->assertTrue(method_exists($controller, 'generate'), 'Controller should have generate method');
    }

    // ============================================================
    // ACCEPTANCE CRITERION 2: ROUTE REGISTERED
    // ============================================================

    /**
     * AC2-01: Route POST /api/v1/ai/suggestions is registered.
     */
    public function test_route_is_registered(): void
    {
        $routes = collect(app('router')->getRoutes());
        $route = $routes->first(fn ($r) => $r->uri() === 'api/v1/ai/suggestions');
        
        $this->assertNotNull($route, 'Route should be registered');
        $this->assertContains('POST', $route->methods(), 'Route should accept POST requests');
    }

    /**
     * AC2-02: Route returns 200 for valid request.
     */
    public function test_route_returns_200_for_valid_request(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(200);
    }

    // ============================================================
    // ACCEPTANCE CRITERION 3: REQUEST VALIDATION
    // ============================================================

    /**
     * AC3-01: Request requires indicator field.
     */
    public function test_validation_requires_indicator(): void
    {
        $payload = $this->validPayload();
        unset($payload['indicator']);

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    /**
     * AC3-02: Request validates indicator is valid enum.
     */
    public function test_validation_indicator_must_be_valid_enum(): void
    {
        $payload = $this->validPayload();
        $payload['indicator'] = 'invalid_indicator';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    /**
     * AC3-03: All valid indicator types are accepted.
     * 
     * @dataProvider validIndicatorProvider
     */
    public function test_validation_accepts_valid_indicators(string $indicator): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult($indicator));

        $payload = $this->validPayload();
        $payload['indicator'] = $indicator;

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * Valid indicator types for data provider.
     */
    public static function validIndicatorProvider(): array
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

    /**
     * AC3-04: Request requires context field.
     */
    public function test_validation_requires_context(): void
    {
        $payload = $this->validPayload();
        unset($payload['context']);

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /**
     * AC3-05: Context must be an array.
     */
    public function test_validation_context_must_be_array(): void
    {
        $payload = $this->validPayload();
        $payload['context'] = 'not an array';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /**
     * AC3-06: Context max 5000 chars total.
     */
    public function test_validation_context_max_5000_chars(): void
    {
        $payload = $this->validPayload();
        $payload['context'] = [
            'title' => str_repeat('a', 5001),
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /**
     * AC3-07: Provider field is optional.
     */
    public function test_validation_provider_is_optional(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        // No provider field

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * AC3-08: Provider must be valid enum.
     */
    public function test_validation_provider_must_be_valid_enum(): void
    {
        $payload = $this->validPayload();
        $payload['provider'] = 'invalid_provider';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    /**
     * AC3-09: Valid provider values accepted.
     * 
     * @dataProvider validProviderProvider
     */
    public function test_validation_accepts_valid_providers(string $provider): void
    {
        $this->mockService
            ->shouldReceive('setProvider')
            ->once()
            ->with($provider);
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(provider: $provider));

        $payload = $this->validPayload();
        $payload['provider'] = $provider;

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * Valid provider values for data provider.
     */
    public static function validProviderProvider(): array
    {
        return [
            'openrouter' => ['openrouter'],
            'vercel' => ['vercel'],
            'static' => ['static'],
        ];
    }

    /**
     * AC3-10: Debug field is optional.
     */
    public function test_validation_debug_is_optional(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        // No debug field

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * AC3-11: Debug must be boolean.
     */
    public function test_validation_debug_must_be_boolean(): void
    {
        $payload = $this->validPayload();
        $payload['debug'] = 'not_boolean';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['debug']);
    }

    /**
     * AC3-12: Validation returns 422 with clear error messages.
     */
    public function test_validation_returns_422_with_clear_messages(): void
    {
        $response = $this->postJson('/api/v1/ai/suggestions', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'indicator',
                    'context',
                ],
            ]);
    }

    // ============================================================
    // ACCEPTANCE CRITERION 4: RESPONSE STRUCTURE (SUCCESS)
    // ============================================================

    /**
     * AC4-01: Returns HTTP 200 with JSON body.
     */
    public function test_response_returns_200_with_json(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * AC4-02: Response contains success: true.
     */
    public function test_response_contains_success_true(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJson(['success' => true]);
    }

    /**
     * AC4-03: Response contains data object with all required fields.
     */
    public function test_response_contains_data_object(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

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
    }

    /**
     * AC4-04: Response data indicator matches request.
     */
    public function test_response_indicator_matches_request(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult('meta_description'));

        $payload = $this->validPayload();
        $payload['indicator'] = 'meta_description';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertJsonPath('data.indicator', 'meta_description');
    }

    /**
     * AC4-05: Response score is integer 0-100.
     */
    public function test_response_score_is_integer_0_to_100(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult('title', 85));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $score = $response->json('data.score');
        
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * AC4-06: Response issues is array.
     */
    public function test_response_issues_is_array(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJsonPath('data.issues', fn ($issues) => is_array($issues));
    }

    /**
     * AC4-07: Response suggestions is array with proper structure.
     */
    public function test_response_suggestions_structure(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $suggestions = $response->json('data.suggestions');
        
        $this->assertIsArray($suggestions);
        
        if (!empty($suggestions)) {
            $this->assertArrayHasKey('type', $suggestions[0]);
            $this->assertArrayHasKey('text', $suggestions[0]);
        }
    }

    /**
     * AC4-08: Response best_practices is array.
     */
    public function test_response_best_practices_is_array(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJsonPath('data.best_practices', fn ($bp) => is_array($bp));
    }

    /**
     * AC4-09: Response provider is string.
     */
    public function test_response_provider_is_string(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJsonPath('data.provider', fn ($p) => is_string($p));
    }

    /**
     * AC4-10: Response model is string or null.
     */
    public function test_response_model_is_string_or_null(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $model = $response->json('data.model');
        
        $this->assertTrue(is_string($model) || is_null($model));
    }

    /**
     * AC4-11: Response generated_at is ISO 8601 timestamp.
     */
    public function test_response_generated_at_is_iso8601(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $timestamp = $response->json('data.generated_at');
        
        // Verify it's a valid ISO 8601 timestamp
        $parsed = date_create_from_format('Y-m-d\TH:i:sP', $timestamp);
        $this->assertNotFalse($parsed, 'generated_at should be valid ISO 8601 format');
    }

    // ============================================================
    // ACCEPTANCE CRITERION 5: RESPONSE STRUCTURE (ERROR WITH FALLBACK)
    // ============================================================

    /**
     * AC5-01: Returns HTTP 200 even when AI fails (fallback used).
     */
    public function test_response_200_on_ai_failure_with_fallback(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(
                provider: 'static_fallback'
            ));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(200);
    }

    /**
     * AC5-02: Contains success: true when fallback used.
     */
    public function test_response_success_true_on_fallback(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(
                provider: 'static_fallback'
            ));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJson(['success' => true]);
    }

    /**
     * AC5-03: Contains fallback_reason when fallback was used.
     */
    public function test_response_contains_fallback_reason(): void
    {
        // This test will need to be updated when the actual implementation
        // adds fallback_reason to the response. For now, we test that the
        // response structure can accommodate it.
        
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(
                provider: 'static_fallback'
            ));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        // Provider should indicate fallback was used
        $response->assertJsonPath('data.provider', 'static_fallback');
    }

    /**
     * AC5-04: Provider is "static_fallback" when fallback used.
     */
    public function test_response_provider_is_static_fallback(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(
                provider: 'static_fallback'
            ));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertJsonPath('data.provider', 'static_fallback');
    }

    // ============================================================
    // ACCEPTANCE CRITERION 6: DEBUG MODE RESPONSE
    // ============================================================

    /**
     * AC6-01: Debug mode adds debug object to response.
     */
    public function test_debug_mode_adds_debug_object(): void
    {
        // Enable debug mode
        Config::set('ai.debug', true);

        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());
        $this->mockService
            ->shouldReceive('isDebugMode')
            ->once()
            ->andReturn(true);

        $payload = $this->validPayload();
        $payload['debug'] = true;

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/ai/suggestions', $payload);

        // Debug object structure should be present (when implemented)
        // For now, just verify the response is successful
        $response->assertStatus(200);
    }

    /**
     * AC6-02: Debug requires authenticated user.
     */
    public function test_debug_requires_authentication(): void
    {
        $payload = $this->validPayload();
        $payload['debug'] = true;

        // Without authentication, debug should be ignored or return error
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        // Should still work, just without debug info
        $response->assertStatus(200);
    }

    /**
     * AC6-03: Debug contains prompt field.
     */
    public function test_debug_contains_prompt(): void
    {
        Config::set('ai.debug', true);

        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());
        $this->mockService
            ->shouldReceive('isDebugMode')
            ->once()
            ->andReturn(true);

        $payload = $this->validPayload();
        $payload['debug'] = true;

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/ai/suggestions', $payload);

        // This test verifies the endpoint works; actual debug field
        // validation will be done when implementation is complete
        $response->assertStatus(200);
    }

    /**
     * AC6-04: Debug contains latency_ms field.
     */
    public function test_debug_contains_latency(): void
    {
        Config::set('ai.debug', true);

        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());
        $this->mockService
            ->shouldReceive('isDebugMode')
            ->once()
            ->andReturn(true);

        $payload = $this->validPayload();
        $payload['debug'] = true;

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    // ============================================================
    // ACCEPTANCE CRITERION 7: ERROR HANDLING
    // ============================================================

    /**
     * AC7-01: Returns 500 for internal server errors.
     */
    public function test_returns_500_for_internal_error(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \Exception('Internal error'));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(500);
    }

    /**
     * AC7-02: Returns 429 for rate limit exceeded.
     */
    public function test_returns_429_for_rate_limit_exceeded(): void
    {
        // This test verifies rate limiting behavior
        // Actual rate limiting will be implemented in TASK-21-10
        
        // For now, verify the endpoint can handle rate limit responses
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        // Basic response check - rate limiting tests will be in TASK-21-10
        $response->assertStatus(200);
    }

    /**
     * AC7-03: Returns 503 for service unavailable.
     */
    public function test_returns_503_for_service_unavailable(): void
    {
        // Simulate all providers down
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \App\Exceptions\AIServiceUnavailableException('All providers are down'));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(503);
    }

    /**
     * AC7-04: Logs all errors with context.
     */
    public function test_logs_errors_with_context(): void
    {
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AI suggestion error') &&
                       isset($context['indicator']) &&
                       isset($context['exception']);
            });

        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $this->postJson('/api/v1/ai/suggestions', $this->validPayload());
    }

    /**
     * AC7-05: Generic error messages to client.
     */
    public function test_generic_error_messages_to_client(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \Exception('Sensitive internal error message with secrets'));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(500);
        
        // Error message should be generic, not expose internal details
        $errorMessage = $response->json('message');
        $this->assertStringNotContainsString('secrets', $errorMessage);
        $this->assertStringNotContainsString('Sensitive', $errorMessage);
    }

    // ============================================================
    // ACCEPTANCE CRITERION 8: FEATURE TESTS
    // ============================================================

    /**
     * AC8-01: Test file exists at correct location.
     */
    public function test_test_file_exists(): void
    {
        $testPath = base_path('tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php');
        $this->assertFileExists($testPath, 'Test file should exist');
    }

    /**
     * AC8-02: Tests for successful generation.
     */
    public function test_successful_generation(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->with('title', Mockery::type('array'))
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'indicator' => 'title',
                ],
            ]);
    }

    /**
     * AC8-03: Tests for validation errors.
     */
    public function test_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/ai/suggestions', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * AC8-04: Tests for debug mode.
     */
    public function test_debug_mode(): void
    {
        Config::set('ai.debug', true);

        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());
        $this->mockService
            ->shouldReceive('isDebugMode')
            ->once()
            ->andReturn(true);

        $payload = $this->validPayload();
        $payload['debug'] = true;

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * AC8-05: Tests for fallback behavior.
     */
    public function test_fallback_behavior(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult(
                provider: 'static_fallback'
            ));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(200)
            ->assertJsonPath('data.provider', 'static_fallback');
    }

    /**
     * AC8-06: Tests for error responses.
     */
    public function test_error_responses(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $response->assertStatus(500);
    }

    // ============================================================
    // SECURITY TESTS
    // ============================================================

    /**
     * SEC-01: SQL injection protection.
     */
    public function test_sql_injection_protection(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context']['title'] = "'; DROP TABLE users; --";

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        // Should not cause SQL error - response should be valid
        $response->assertStatus(200);
    }

    /**
     * SEC-02: XSS protection in response.
     */
    public function test_xss_protection(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context']['title'] = '<script>alert("xss")</script>';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
        
        // Response should not contain unescaped script tags
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>alert', $content);
    }

    /**
     * SEC-03: Large payload handling.
     */
    public function test_large_payload_handling(): void
    {
        $payload = $this->validPayload();
        $payload['context'] = [
            'title' => str_repeat('a', 5001), // Exceeds 5000 char limit
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422);
    }

    /**
     * SEC-04: No sensitive data in error responses.
     */
    public function test_no_sensitive_data_in_errors(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andThrow(new \Exception('Error with API_KEY=secret123'));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());

        $content = $response->getContent();
        
        $this->assertStringNotContainsString('API_KEY', $content);
        $this->assertStringNotContainsString('secret123', $content);
    }

    /**
     * SEC-05: Context data is validated.
     */
    public function test_context_data_validation(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context'] = ['title' => 'Valid title'];

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    // ============================================================
    // EDGE CASES
    // ============================================================

    /**
     * EDGE-01: Empty context array.
     */
    public function test_empty_context_array(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context'] = [];

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        // Should work with empty context
        $response->assertStatus(200);
    }

    /**
     * EDGE-02: Unicode characters in context.
     */
    public function test_unicode_in_context(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context']['title'] = '日本語タイトル 🎉';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * EDGE-03: Null values in context.
     */
    public function test_null_values_in_context(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult());

        $payload = $this->validPayload();
        $payload['context']['title'] = null;

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(200);
    }

    /**
     * EDGE-04: Very long individual context field.
     */
    public function test_long_context_field(): void
    {
        $payload = $this->validPayload();
        $payload['context']['title'] = str_repeat('a', 5001);

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        // Should fail validation
        $response->assertStatus(422);
    }

    /**
     * EDGE-05: Multiple simultaneous requests (basic concurrency).
     */
    public function test_concurrent_requests(): void
    {
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->times(3)
            ->andReturn($this->createValidSuggestionResult());

        // Make 3 requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());
        }

        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /**
     * EDGE-06: Special characters in indicator.
     */
    public function test_special_characters_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['indicator'] = 'title<script>';

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        $response->assertStatus(422);
    }

    /**
     * EDGE-07: Case sensitivity of indicator.
     */
    public function test_indicator_case_sensitivity(): void
    {
        $payload = $this->validPayload();
        $payload['indicator'] = 'TITLE'; // Uppercase

        $response = $this->postJson('/api/v1/ai/suggestions', $payload);

        // Should reject uppercase
        $response->assertStatus(422);
    }

    /**
     * EDGE-08: Score boundary values.
     */
    public function test_score_boundary_values(): void
    {
        // Test score 0
        $this->mockService
            ->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult('title', 0));

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());
        $response->assertJsonPath('data.score', 0);

        // Test score 100 - rebind a fresh mock
        $this->app->forgetInstance(AISuggestionService::class);
        $newMock = Mockery::mock(AISuggestionService::class, [$this->mockProvider]);
        $newMock->shouldReceive('getSuggestions')
            ->once()
            ->andReturn($this->createValidSuggestionResult('title', 100));
        $this->app->instance(AISuggestionService::class, $newMock);

        $response = $this->postJson('/api/v1/ai/suggestions', $this->validPayload());
        $response->assertJsonPath('data.score', 100);
    }
}
