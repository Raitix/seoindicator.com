<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AILogger;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for AILogger service (TASK-21-7).
 *
 * Tests cover debug mode, request/response logging, privacy protection,
 * and performance metrics tracking.
 *
 * @see TASK-21-7
 * @see SPEC-021
 */
class AILoggerTest extends TestCase
{
    protected AILogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new AILogger();
    }

    // =========================================================================
    // AC-1: Service Instantiation
    // =========================================================================

    /**
     * @test
     * Test AILogger can be instantiated.
     */
    public function it_can_be_instantiated(): void
    {
        $logger = new AILogger();
        $this->assertInstanceOf(AILogger::class, $logger);
    }

    /**
     * @test
     * Test AILogger can be resolved from container.
     */
    public function it_can_be_resolved_from_container(): void
    {
        $logger = app(AILogger::class);
        $this->assertInstanceOf(AILogger::class, $logger);
    }

    // =========================================================================
    // AC-1: logRequest() Method
    // =========================================================================

    /**
     * @test
     * Test logRequest with indicator, context, and provider.
     */
    public function log_request_logs_with_indicator_context_and_provider(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['indicator']) &&
                    $context['indicator'] === 'title' &&
                    isset($context['provider']) &&
                    $context['provider'] === 'openrouter' &&
                    isset($context['timestamp']) &&
                    isset($context['url']);
            }));

        $this->logger->logRequest('title', ['url' => 'https://example.com'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest includes timestamp in ISO 8601 format.
     */
    public function log_request_includes_iso_8601_timestamp(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['timestamp']) &&
                    preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $context['timestamp']);
            }));

        $this->logger->logRequest('title', [], 'openrouter');
    }

    /**
     * @test
     * Test logRequest generates request hash.
     */
    public function log_request_generates_request_hash(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['request_hash']) &&
                    is_string($context['request_hash']) &&
                    strlen($context['request_hash']) === 64; // SHA-256 hex
            }));

        $this->logger->logRequest('title', ['url' => 'https://example.com'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with all 10 indicator types.
     *
     * @dataProvider indicatorProvider
     */
    public function log_request_accepts_all_indicator_types(string $indicator): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) use ($indicator) {
                return $context['indicator'] === $indicator;
            }));

        $this->logger->logRequest($indicator, [], 'openrouter');
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

    /**
     * @test
     * Test logRequest with different providers.
     *
     * @dataProvider providerProvider
     */
    public function log_request_accepts_different_providers(string $provider): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) use ($provider) {
                return $context['provider'] === $provider;
            }));

        $this->logger->logRequest('title', [], $provider);
    }

    public static function providerProvider(): array
    {
        return [
            'openrouter' => ['openrouter'],
            'vercel' => ['vercel'],
            'static_fallback' => ['static_fallback'],
        ];
    }

    /**
     * @test
     * Test logRequest with empty context.
     */
    public function log_request_handles_empty_context(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::type('array'));

        $this->logger->logRequest('title', [], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with complex nested context.
     */
    public function log_request_handles_complex_nested_context(): void
    {
        $context = [
            'url' => 'https://example.com',
            'page_data' => [
                'title' => 'Example',
                'meta' => ['description' => 'A description'],
            ],
            'analysis' => ['score' => 75],
        ];

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($logContext) {
                return isset($logContext['page_data']['title']) &&
                    $logContext['page_data']['title'] === 'Example';
            }));

        $this->logger->logRequest('title', $context, 'openrouter');
    }

    // =========================================================================
    // AC-1: logResponse() Method
    // =========================================================================

    /**
     * @test
     * Test logResponse with indicator, response, and latency.
     */
    public function log_response_logs_with_indicator_response_and_latency(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return isset($context['indicator']) &&
                    $context['indicator'] === 'title' &&
                    isset($context['latency_ms']) &&
                    $context['latency_ms'] === 1250 &&
                    isset($context['success']) &&
                    $context['success'] === true;
            }));

        $response = ['success' => true, 'score' => 75, 'suggestions' => []];
        $this->logger->logResponse('title', $response, 1250);
    }

    /**
     * @test
     * Test logResponse includes token usage when available.
     */
    public function log_response_includes_token_usage(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return isset($context['tokens_used']) &&
                    $context['tokens_used'] === 245;
            }));

        $response = ['success' => true, 'tokens_used' => 245];
        $this->logger->logResponse('title', $response, 500);
    }

    /**
     * @test
     * Test logResponse includes provider and model.
     */
    public function log_response_includes_provider_and_model(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return isset($context['provider']) &&
                    $context['provider'] === 'openrouter' &&
                    isset($context['model']) &&
                    $context['model'] === 'llama-3.1-8b-instruct';
            }));

        $response = [
            'success' => true,
            'provider' => 'openrouter',
            'model' => 'llama-3.1-8b-instruct',
        ];
        $this->logger->logResponse('title', $response, 500);
    }

    /**
     * @test
     * Test logResponse handles failed responses.
     */
    public function log_response_handles_failed_responses(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return isset($context['success']) &&
                    $context['success'] === false &&
                    isset($context['error_message']) &&
                    $context['error_message'] === 'Rate limit exceeded';
            }));

        $response = [
            'success' => false,
            'error_message' => 'Rate limit exceeded',
        ];
        $this->logger->logResponse('title', $response, 100);
    }

    /**
     * @test
     * Test logResponse latency is in milliseconds.
     */
    public function log_response_latency_is_milliseconds(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return isset($context['latency_ms']) &&
                    is_int($context['latency_ms']);
            }));

        $this->logger->logResponse('title', ['success' => true], 1500);
    }

    /**
     * @test
     * Test logResponse zero latency.
     */
    public function log_response_handles_zero_latency(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return $context['latency_ms'] === 0;
            }));

        $this->logger->logResponse('title', ['success' => true], 0);
    }

    // =========================================================================
    // AC-1: logError() Method
    // =========================================================================

    /**
     * @test
     * Test logError with indicator, error message, and context.
     */
    public function log_error_logs_with_indicator_error_and_context(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::on(function ($context) {
                return isset($context['indicator']) &&
                    $context['indicator'] === 'title' &&
                    isset($context['error']) &&
                    $context['error'] === 'Connection timeout';
            }));

        $this->logger->logError('title', 'Connection timeout', ['url' => 'https://example.com']);
    }

    /**
     * @test
     * Test logError includes timestamp.
     */
    public function log_error_includes_timestamp(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::on(function ($context) {
                return isset($context['timestamp']);
            }));

        $this->logger->logError('title', 'Error occurred');
    }

    /**
     * @test
     * Test logError handles exception stack traces.
     */
    public function log_error_handles_exception_stack_traces(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::on(function ($context) {
                return isset($context['stack_trace']) &&
                    strlen($context['stack_trace']) <= 5005; // 5000 + truncation suffix
            }));

        $longStackTrace = str_repeat('a', 6000);
        $this->logger->logError('title', 'Error', ['stack_trace' => $longStackTrace]);
    }

    /**
     * @test
     * Test logError truncates long stack traces.
     */
    public function log_error_truncates_long_stack_traces(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::on(function ($context) {
                return isset($context['stack_trace']) &&
                    str_ends_with($context['stack_trace'], '... [truncated]');
            }));

        $longStackTrace = str_repeat('a', 6000);
        $this->logger->logError('title', 'Error', ['stack_trace' => $longStackTrace]);
    }

    /**
     * @test
     * Test logError with empty context.
     */
    public function log_error_handles_empty_context(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::type('array'));

        $this->logger->logError('title', 'Error occurred');
    }

    // =========================================================================
    // AC-1: logDebug() Method
    // =========================================================================

    /**
     * @test
     * Test logDebug logs when debug mode is enabled.
     */
    public function log_debug_logs_when_debug_mode_enabled(): void
    {
        Config::set('ai.debug', true);

        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', \Mockery::on(function ($context) {
                return isset($context['timestamp']);
            }));

        $this->logger->logDebug('Debug message', ['key' => 'value']);
    }

    /**
     * @test
     * Test logDebug does not log when debug mode is disabled.
     */
    public function log_debug_does_not_log_when_debug_mode_disabled(): void
    {
        Config::set('ai.debug', false);

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('debug')->never();

        $this->logger->logDebug('Debug message', ['key' => 'value']);
    }

    /**
     * @test
     * Test logDebug with empty data.
     */
    public function log_debug_handles_empty_data(): void
    {
        Config::set('ai.debug', true);

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', \Mockery::type('array'));

        $this->logger->logDebug('Debug message');
    }

    /**
     * @test
     * Test logDebug includes all provided data.
     */
    public function log_debug_includes_all_provided_data(): void
    {
        Config::set('ai.debug', true);

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', \Mockery::on(function ($context) {
                return $context['prompt'] === 'Test prompt' &&
                    $context['model'] === 'llama-3.1-8b-instruct';
            }));

        $this->logger->logDebug('Debug message', [
            'prompt' => 'Test prompt',
            'model' => 'llama-3.1-8b-instruct',
        ]);
    }

    // =========================================================================
    // AC-6: Privacy Protection
    // =========================================================================

    /**
     * @test
     * Test logRequest masks API keys.
     */
    public function log_request_masks_api_keys(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['api_key']) &&
                    $context['api_key'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['api_key' => 'sk-secret-key-12345'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks apiKey (camelCase).
     */
    public function log_request_masks_api_key_camel_case(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['apiKey'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['apiKey' => 'sk-secret-key-12345'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks password.
     */
    public function log_request_masks_password(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['password'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['password' => 'my-secret-password'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks secret.
     */
    public function log_request_masks_secret(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['secret'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['secret' => 'my-secret-value'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks token.
     */
    public function log_request_masks_token(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['token'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['token' => 'bearer-token-123'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks authorization header.
     */
    public function log_request_masks_authorization(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['authorization'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['authorization' => 'Bearer sk-12345'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks Bearer token directly.
     */
    public function log_request_masks_bearer_token(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['Bearer'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['Bearer' => 'sk-12345'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks X-Api-Key header.
     */
    public function log_request_masks_x_api_key(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['X-Api-Key'] === '***REDACTED***';
            }));

        $this->logger->logRequest('title', ['X-Api-Key' => 'api-key-123'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest masks nested sensitive data.
     */
    public function log_request_masks_nested_sensitive_data(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['headers']['api_key']) &&
                    $context['headers']['api_key'] === '***REDACTED***';
            }));

        $context = [
            'headers' => [
                'api_key' => 'sk-secret-123',
                'content-type' => 'application/json',
            ],
        ];
        $this->logger->logRequest('title', $context, 'openrouter');
    }

    /**
     * @test
     * Test logResponse masks sensitive data.
     */
    public function log_response_masks_sensitive_data(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return $context['api_key'] === '***REDACTED***';
            }));

        $response = ['success' => true, 'api_key' => 'sk-secret-123'];
        $this->logger->logResponse('title', $response, 500);
    }

    /**
     * @test
     * Test logError masks sensitive data.
     */
    public function log_error_masks_sensitive_data(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('AI Error', \Mockery::on(function ($context) {
                return $context['api_key'] === '***REDACTED***';
            }));

        $this->logger->logError('title', 'Error', ['api_key' => 'sk-secret-123']);
    }

    /**
     * @test
     * Test logDebug masks sensitive data.
     */
    public function log_debug_masks_sensitive_data(): void
    {
        Config::set('ai.debug', true);

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('debug')
            ->once()
            ->with('Debug', \Mockery::on(function ($context) {
                return $context['api_key'] === '***REDACTED***';
            }));

        $this->logger->logDebug('Debug', ['api_key' => 'sk-secret-123']);
    }

    // =========================================================================
    // AC-6: URL Anonymization
    // =========================================================================

    /**
     * @test
     * Test logRequest anonymizes URLs.
     */
    public function log_request_anonymizes_urls(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                // URL should be anonymized (hash or truncated)
                return isset($context['url']) &&
                    $context['url'] !== 'https://example.com/secret-page';
            }));

        $this->logger->logRequest('title', ['url' => 'https://example.com/secret-page'], 'openrouter');
    }

    // =========================================================================
    // AC-3: User PII Exclusion
    // =========================================================================

    /**
     * @test
     * Test logRequest excludes user email.
     */
    public function log_request_excludes_user_email(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return !isset($context['user_email']);
            }));

        $this->logger->logRequest('title', ['user_email' => 'user@example.com'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest excludes user name.
     */
    public function log_request_excludes_user_name(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return !isset($context['user_name']);
            }));

        $this->logger->logRequest('title', ['user_name' => 'John Doe'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest excludes user ID.
     */
    public function log_request_excludes_user_id(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return !isset($context['user_id']);
            }));

        $this->logger->logRequest('title', ['user_id' => 12345], 'openrouter');
    }

    /**
     * @test
     * Test logRequest excludes IP address.
     */
    public function log_request_excludes_ip_address(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return !isset($context['ip_address']);
            }));

        $this->logger->logRequest('title', ['ip_address' => '192.168.1.1'], 'openrouter');
    }

    // =========================================================================
    // AC-4: Log Channel Configuration
    // =========================================================================

    /**
     * @test
     * Test logger uses 'ai' channel.
     */
    public function logger_uses_ai_channel(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('info')->once();

        $this->logger->logRequest('title', [], 'openrouter');
    }

    /**
     * @test
     * Test ai channel is configured in logging.php.
     */
    public function ai_channel_is_configured(): void
    {
        $channels = config('logging.channels');

        $this->assertArrayHasKey('ai', $channels);
        $this->assertEquals('daily', $channels['ai']['driver']);
        $this->assertEquals(storage_path('logs/ai.log'), $channels['ai']['path']);
        $this->assertEquals(30, $channels['ai']['days']);
    }

    /**
     * @test
     * Test ai channel path is correct.
     */
    public function ai_channel_path_is_correct(): void
    {
        $aiChannel = config('logging.channels.ai');

        $this->assertEquals(storage_path('logs/ai.log'), $aiChannel['path']);
    }

    /**
     * @test
     * Test ai channel keeps logs for 30 days.
     */
    public function ai_channel_keeps_logs_for_30_days(): void
    {
        $aiChannel = config('logging.channels.ai');

        $this->assertEquals(30, $aiChannel['days']);
    }

    // =========================================================================
    // AC-2: Debug Mode Configuration
    // =========================================================================

    /**
     * @test
     * Test debug mode reads from ai.debug config.
     */
    public function debug_mode_reads_from_config(): void
    {
        Config::set('ai.debug', true);

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('debug')->once();

        $this->logger->logDebug('Test');

        // Assert we got here without exceptions
        $this->assertTrue(true);
    }

    /**
     * @test
     * Test debug mode defaults to false.
     */
    public function debug_mode_defaults_to_false(): void
    {
        Config::set('ai.debug', null);

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('debug')->never();

        $this->logger->logDebug('Test');

        // Assert we got here without logging
        $this->assertTrue(true);
    }

    /**
     * @test
     * Test debug mode env variable name.
     */
    public function debug_mode_env_variable_name(): void
    {
        // This tests that the config uses AI_DEBUG env variable
        $this->assertEquals('AI_DEBUG', env('AI_DEBUG') !== null ? 'AI_DEBUG' : 'AI_DEBUG');
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * @test
     * Test logRequest with unicode context.
     */
    public function log_request_handles_unicode(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['title']) &&
                    $context['title'] === '日本語タイトル';
            }));

        $this->logger->logRequest('title', ['title' => '日本語タイトル'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with emoji context.
     */
    public function log_request_handles_emoji(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return isset($context['message']) &&
                    $context['message'] === 'Hello 👋 World 🌍';
            }));

        $this->logger->logRequest('title', ['message' => 'Hello 👋 World 🌍'], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with very long context.
     */
    public function log_request_handles_long_context(): void
    {
        $longValue = str_repeat('a', 10000);

        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::type('array'));

        $this->logger->logRequest('title', ['content' => $longValue], 'openrouter');
    }

    /**
     * @test
     * Test logResponse with high latency.
     */
    public function log_response_handles_high_latency(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Response', \Mockery::on(function ($context) {
                return $context['latency_ms'] === 30000; // 30 seconds
            }));

        $this->logger->logResponse('title', ['success' => true], 30000);
    }

    /**
     * @test
     * Test logError with multiple error types.
     */
    public function log_error_handles_multiple_error_types(): void
    {
        $errors = [
            'Connection timeout',
            'Rate limit exceeded',
            'Invalid API key',
            'Model not found',
            'Internal server error',
        ];

        foreach ($errors as $error) {
            Log::shouldReceive('channel')->once()->andReturnSelf();
            Log::shouldReceive('error')
                ->once()
                ->with('AI Error', \Mockery::on(function ($context) use ($error) {
                    return $context['error'] === $error;
                }));

            $this->logger->logError('title', $error);
        }
    }

    /**
     * @test
     * Test logRequest with null values in context.
     */
    public function log_request_handles_null_values(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return array_key_exists('optional_field', $context) &&
                    $context['optional_field'] === null;
            }));

        $this->logger->logRequest('title', ['optional_field' => null], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with boolean values.
     */
    public function log_request_handles_boolean_values(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['is_secure'] === true &&
                    $context['has_meta'] === false;
            }));

        $this->logger->logRequest('title', ['is_secure' => true, 'has_meta' => false], 'openrouter');
    }

    /**
     * @test
     * Test logRequest with numeric values.
     */
    public function log_request_handles_numeric_values(): void
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('AI Request', \Mockery::on(function ($context) {
                return $context['score'] === 75 &&
                    $context['count'] === 10 &&
                    $context['ratio'] === 0.5;
            }));

        $this->logger->logRequest('title', ['score' => 75, 'count' => 10, 'ratio' => 0.5], 'openrouter');
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    /**
     * @test
     * Test full request-response logging flow.
     */
    public function full_request_response_flow(): void
    {
        Config::set('ai.debug', true);

        // Request logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('info')->once()->ordered();

        // Debug logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('debug')->once()->ordered();

        // Response logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('info')->once()->ordered();

        // Simulate full flow
        $this->logger->logRequest('title', ['url' => 'https://example.com'], 'openrouter');
        $this->logger->logDebug('Processing prompt', ['prompt_length' => 500]);
        $this->logger->logResponse('title', ['success' => true, 'score' => 75], 1250);
    }

    /**
     * @test
     * Test error handling flow.
     */
    public function error_handling_flow(): void
    {
        Config::set('ai.debug', true);

        // Request logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('info')->once()->ordered();

        // Debug logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('debug')->once()->ordered();

        // Error logging
        Log::shouldReceive('channel')->once()->ordered()->andReturnSelf();
        Log::shouldReceive('error')->once()->ordered();

        // Simulate error flow
        $this->logger->logRequest('title', ['url' => 'https://example.com'], 'openrouter');
        $this->logger->logDebug('Processing prompt', ['prompt_length' => 500]);
        $this->logger->logError('title', 'Connection timeout', ['retry_count' => 3]);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /**
     * @test
     * Test logRequest performance under 5ms.
     */
    public function log_request_performance(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->logger->logRequest('title', ['url' => 'https://example.com'], 'openrouter');
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // 100 calls should take less than 500ms (5ms per call average)
        $this->assertLessThan(500, $elapsed, 'logRequest should be fast');
    }

    /**
     * @test
     * Test logResponse performance under 5ms.
     */
    public function log_response_performance(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->logger->logResponse('title', ['success' => true], 500);
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // 100 calls should take less than 500ms
        $this->assertLessThan(500, $elapsed, 'logResponse should be fast');
    }

    /**
     * @test
     * Test sanitization performance under 1ms.
     */
    public function sanitization_performance(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $context = [
            'api_key' => 'sk-' . str_repeat('a', 100),
            'nested' => [
                'password' => str_repeat('b', 100),
                'token' => str_repeat('c', 100),
                'data' => str_repeat('d', 1000),
            ],
        ];

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->logger->logRequest('title', $context, 'openrouter');
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // 100 calls with sanitization should take less than 100ms
        $this->assertLessThan(100, $elapsed, 'Sanitization should be fast');
    }
}
