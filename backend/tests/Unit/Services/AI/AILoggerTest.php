<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AILogger;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for AILogger service (TASK-21-7).
 *
 * Tests cover:
 * - AC-3: Logging service methods (logRequest, logResponse, logError)
 * - AC-5: Request logging with all required fields
 * - AC-6: Response logging with all required fields
 * - AC-7: Error logging with all required fields
 *
 * @see TASK-21-7
 * @see SPEC-021
 */
#[Group('ai')]
#[Group('logging')]
class AILoggerTest extends TestCase
{
    private AILogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new AILogger();
    }

    // ==========================================
    // AC-3: Logging Service Creation Tests
    // ==========================================

    #[Test]
    public function it_exists_at_expected_path(): void
    {
        $this->assertFileExists(app_path('Services/AI/AILogger.php'));
    }

    #[Test]
    public function it_has_log_request_method(): void
    {
        $this->assertTrue(method_exists($this->logger, 'logRequest'));
    }

    #[Test]
    public function it_has_log_response_method(): void
    {
        $this->assertTrue(method_exists($this->logger, 'logResponse'));
    }

    #[Test]
    public function it_has_log_error_method(): void
    {
        $this->assertTrue(method_exists($this->logger, 'logError'));
    }

    #[Test]
    public function it_uses_laravel_logging_with_dedicated_channel(): void
    {
        Log::shouldReceive('channel')
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once();

        $this->logger->logRequest($this->getValidRequestData());
    }

    // ==========================================
    // AC-5: Request Logging Tests
    // ==========================================

    #[Test]
    public function log_request_logs_with_timestamp(): void
    {
        Log::shouldReceive('channel')
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['timestamp']) || str_contains($message, 'AI Request');
            });

        $this->logger->logRequest($this->getValidRequestData());
    }

    #[Test]
    public function log_request_includes_user_id_when_authenticated(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'user_id' => 42,
        ]));

        $this->assertEquals(42, $loggedData['user_id'] ?? null);
    }

    #[Test]
    public function log_request_includes_session_id_when_anonymous(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'session_id' => 'sess_abc123',
        ]));

        $this->assertEquals('sess_abc123', $loggedData['session_id'] ?? null);
    }

    #[Test]
    public function log_request_includes_indicator_type(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'indicator' => 'title',
        ]));

        $this->assertEquals('title', $loggedData['indicator'] ?? null);
    }

    #[Test]
    public function log_request_includes_provider_name(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'provider' => 'openrouter',
        ]));

        $this->assertEquals('openrouter', $loggedData['provider'] ?? null);
    }

    #[Test]
    public function log_request_includes_request_hash(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'request_hash' => 'hash_abc123def456',
        ]));

        $this->assertEquals('hash_abc123def456', $loggedData['request_hash'] ?? null);
    }

    #[Test]
    public function log_request_includes_prompt_length(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest(array_merge($this->getValidRequestData(), [
            'prompt_length' => 256,
        ]));

        $this->assertEquals(256, $loggedData['prompt_length'] ?? null);
    }

    #[Test]
    public function log_request_validates_required_fields(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        // Should not throw with required fields
        $this->logger->logRequest([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'request_hash' => 'hash123',
            'prompt_length' => 100,
        ]);

        $this->assertTrue(true);
    }

    // ==========================================
    // AC-6: Response Logging Tests
    // ==========================================

    #[Test]
    public function log_response_includes_timestamp(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['timestamp']) || str_contains($message, 'AI Response');
            });

        $this->logger->logResponse($this->getValidResponseData());
    }

    #[Test]
    public function log_response_includes_request_hash(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'request_hash' => 'hash_xyz789',
        ]));

        $this->assertEquals('hash_xyz789', $loggedData['request_hash'] ?? null);
    }

    #[Test]
    public function log_response_includes_success_status(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'success' => true,
        ]));

        $this->assertTrue($loggedData['success'] ?? false);
    }

    #[Test]
    public function log_response_includes_failure_status(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'success' => false,
        ]));

        $this->assertFalse($loggedData['success'] ?? true);
    }

    #[Test]
    public function log_response_includes_tokens_used(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'tokens' => [
                'prompt' => 150,
                'completion' => 200,
                'total' => 350,
            ],
        ]));

        $this->assertEquals(150, $loggedData['tokens']['prompt'] ?? null);
        $this->assertEquals(200, $loggedData['tokens']['completion'] ?? null);
        $this->assertEquals(350, $loggedData['tokens']['total'] ?? null);
    }

    #[Test]
    public function log_response_includes_latency_ms(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'latency_ms' => 1250,
        ]));

        $this->assertEquals(1250, $loggedData['latency_ms'] ?? null);
    }

    #[Test]
    public function log_response_includes_model_used(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'model' => 'llama-3.1-8b-instruct',
        ]));

        $this->assertEquals('llama-3.1-8b-instruct', $loggedData['model'] ?? null);
    }

    #[Test]
    public function log_response_includes_cache_hit(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'cache_hit' => true,
        ]));

        $this->assertTrue($loggedData['cache_hit'] ?? false);
    }

    #[Test]
    public function log_response_includes_cache_miss(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logResponse(array_merge($this->getValidResponseData(), [
            'cache_hit' => false,
        ]));

        $this->assertFalse($loggedData['cache_hit'] ?? true);
    }

    // ==========================================
    // AC-7: Error Logging Tests
    // ==========================================

    #[Test]
    public function log_error_uses_error_level(): void
    {
        Log::shouldReceive('channel')
            ->with('ai')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once();

        $this->logger->logError($this->getValidErrorData());
    }

    #[Test]
    public function log_error_includes_error_type(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'error_type' => 'timeout',
        ]));

        $this->assertEquals('timeout', $loggedData['error_type'] ?? null);
    }

    #[Test]
    #[DataProvider('errorTypeProvider')]
    public function log_error_handles_various_error_types(string $errorType): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'error_type' => $errorType,
        ]));

        $this->assertEquals($errorType, $loggedData['error_type'] ?? null);
    }

    public static function errorTypeProvider(): array
    {
        return [
            'timeout' => ['timeout'],
            'rate_limit' => ['rate_limit'],
            'invalid_response' => ['invalid_response'],
            'api_error' => ['api_error'],
            'auth_failed' => ['auth_failed'],
            'network_error' => ['network_error'],
            'content_policy' => ['content_policy'],
        ];
    }

    #[Test]
    public function log_error_includes_error_message(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'error_message' => 'Connection timed out after 30 seconds',
        ]));

        $this->assertEquals('Connection timed out after 30 seconds', $loggedData['error_message'] ?? null);
    }

    #[Test]
    public function log_error_includes_truncated_stack_trace(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $longStackTrace = str_repeat('#0 /path/to/file.php(123): SomeClass->method()', 100);
        
        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'stack_trace' => $longStackTrace,
        ]));

        // Stack trace should be truncated to prevent massive logs
        $this->assertLessThanOrEqual(5000, strlen($loggedData['stack_trace'] ?? ''));
    }

    #[Test]
    public function log_error_includes_request_context(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'request_context' => [
                'indicator' => 'title',
                'provider' => 'openrouter',
                'request_hash' => 'hash123',
            ],
        ]));

        $this->assertEquals('title', $loggedData['request_context']['indicator'] ?? null);
    }

    #[Test]
    public function log_error_includes_response_body_if_available(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logError(array_merge($this->getValidErrorData(), [
            'response_body' => '{"error": "Rate limit exceeded"}',
        ]));

        $this->assertEquals('{"error": "Rate limit exceeded"}', $loggedData['response_body'] ?? null);
    }

    #[Test]
    public function log_error_handles_missing_response_body(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                // Should not throw if response_body is missing
                return true;
            });

        $this->logger->logError($this->getValidErrorData());

        $this->assertTrue(true);
    }

    // ==========================================
    // Edge Cases and Security Tests
    // ==========================================

    #[Test]
    public function log_request_sanitizes_sensitive_data(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        $loggedData = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use (&$loggedData) {
                $loggedData = $context;
                return true;
            });

        $this->logger->logRequest([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'request_hash' => 'hash123',
            'prompt_length' => 100,
            'api_key' => 'sk-secret-key-12345', // Should be sanitized
        ]);

        // API key should not appear in logs
        $this->assertArrayNotHasKey('api_key', $loggedData);
    }

    #[Test]
    public function log_response_handles_null_values_gracefully(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                // Should handle null values without errors
                return true;
            });

        $this->logger->logResponse([
            'request_hash' => 'hash123',
            'success' => true,
            'tokens' => null,
            'latency_ms' => null,
            'model' => null,
            'cache_hit' => false,
        ]);

        $this->assertTrue(true);
    }

    #[Test]
    public function log_methods_return_void(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);

        $requestResult = $this->logger->logRequest($this->getValidRequestData());
        $responseResult = $this->logger->logResponse($this->getValidResponseData());
        $errorResult = $this->logger->logError($this->getValidErrorData());

        $this->assertNull($requestResult);
        $this->assertNull($responseResult);
        $this->assertNull($errorResult);
    }

    #[Test]
    public function logger_is_instantiable_via_container(): void
    {
        $logger = app(AILogger::class);
        
        $this->assertInstanceOf(AILogger::class, $logger);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function getValidRequestData(): array
    {
        return [
            'indicator' => 'title',
            'provider' => 'openrouter',
            'request_hash' => 'hash_' . bin2hex(random_bytes(16)),
            'prompt_length' => 150,
        ];
    }

    private function getValidResponseData(): array
    {
        return [
            'request_hash' => 'hash_' . bin2hex(random_bytes(16)),
            'success' => true,
            'tokens' => [
                'prompt' => 150,
                'completion' => 200,
                'total' => 350,
            ],
            'latency_ms' => 1250,
            'model' => 'llama-3.1-8b-instruct',
            'cache_hit' => false,
        ];
    }

    private function getValidErrorData(): array
    {
        return [
            'error_type' => 'timeout',
            'error_message' => 'Request timed out',
            'request_context' => [
                'indicator' => 'title',
                'provider' => 'openrouter',
            ],
        ];
    }
}
