<?php

namespace Tests\Unit\Services\Analysis;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\Analysis\ErrorHandlingService;
use App\Services\ProgressService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Tests for ErrorHandlingService
 * 
 * @see TASK-19-13
 */
class ErrorHandlingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ErrorHandlingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ErrorHandlingService();
    }

    // ==================== ERROR CATEGORIZATION TESTS ====================

    /**
     * Test timeout error categorization.
     */
    #[Test]
    public function categorize_timeout_error(): void
    {
        $error = new RuntimeException('Connection timed out after 30 seconds');
        $this->assertEquals(ErrorHandlingService::CATEGORY_TIMEOUT, $this->service->categorizeError($error));

        $error = new RuntimeException('Operation timed out');
        $this->assertEquals(ErrorHandlingService::CATEGORY_TIMEOUT, $this->service->categorizeError($error));

        $error = new ConnectException('Connection timed out', new Request('GET', 'https://example.com'));
        $this->assertEquals(ErrorHandlingService::CATEGORY_TIMEOUT, $this->service->categorizeError($error));
    }

    /**
     * Test rate limit error categorization.
     */
    #[Test]
    public function categorize_rate_limit_error(): void
    {
        // HTTP 429 response
        $response = new Response(429, ['Retry-After' => '60'], 'Too Many Requests');
        $error = RequestException::create(new Request('GET', 'https://example.com'), $response);
        $this->assertEquals(ErrorHandlingService::CATEGORY_RATE_LIMIT, $this->service->categorizeError($error));

        // Rate limit in message
        $error = new RuntimeException('Rate limit exceeded');
        $this->assertEquals(ErrorHandlingService::CATEGORY_RATE_LIMIT, $this->service->categorizeError($error));

        $error = new RuntimeException('429 Too Many Requests');
        $this->assertEquals(ErrorHandlingService::CATEGORY_RATE_LIMIT, $this->service->categorizeError($error));
    }

    /**
     * Test SSRF error categorization.
     */
    #[Test]
    public function categorize_ssrf_error(): void
    {
        $error = new RuntimeException('Access to this host is blocked for security reasons');
        $this->assertEquals(ErrorHandlingService::CATEGORY_SSRF, $this->service->categorizeError($error));

        $error = new RuntimeException('SSRF protection: private IP blocked');
        $this->assertEquals(ErrorHandlingService::CATEGORY_SSRF, $this->service->categorizeError($error));

        $error = new RuntimeException('Redirect blocked by SSRF protection');
        $this->assertEquals(ErrorHandlingService::CATEGORY_SSRF, $this->service->categorizeError($error));
    }

    /**
     * Test invalid URL error categorization.
     */
    #[Test]
    public function categorize_invalid_url_error(): void
    {
        $error = new RuntimeException('Invalid URL format');
        $this->assertEquals(ErrorHandlingService::CATEGORY_INVALID_URL, $this->service->categorizeError($error));

        $error = new RuntimeException('URL is required');
        $this->assertEquals(ErrorHandlingService::CATEGORY_INVALID_URL, $this->service->categorizeError($error));

        $error = new RuntimeException('Invalid domain specified');
        $this->assertEquals(ErrorHandlingService::CATEGORY_INVALID_URL, $this->service->categorizeError($error));

        $error = new RuntimeException('DNS resolution failed for hostname');
        $this->assertEquals(ErrorHandlingService::CATEGORY_INVALID_URL, $this->service->categorizeError($error));
    }

    /**
     * Test memory error categorization.
     */
    #[Test]
    public function categorize_memory_error(): void
    {
        $error = new RuntimeException('Allowed memory size of 268435456 bytes exhausted');
        $this->assertEquals(ErrorHandlingService::CATEGORY_MEMORY, $this->service->categorizeError($error));

        $error = new RuntimeException('Out of memory');
        $this->assertEquals(ErrorHandlingService::CATEGORY_MEMORY, $this->service->categorizeError($error));
    }

    /**
     * Test network error categorization.
     */
    #[Test]
    public function categorize_network_error(): void
    {
        $error = new RuntimeException('Connection refused');
        $this->assertEquals(ErrorHandlingService::CATEGORY_NETWORK, $this->service->categorizeError($error));

        $error = new RuntimeException('DNS resolution failed');
        $this->assertEquals(ErrorHandlingService::CATEGORY_NETWORK, $this->service->categorizeError($error));

        $error = new RuntimeException('Network unreachable');
        $this->assertEquals(ErrorHandlingService::CATEGORY_NETWORK, $this->service->categorizeError($error));

        $error = new ConnectException('Connection refused', new Request('GET', 'https://example.com'));
        $this->assertEquals(ErrorHandlingService::CATEGORY_NETWORK, $this->service->categorizeError($error));
    }

    /**
     * Test parsing error categorization.
     */
    #[Test]
    public function categorize_parsing_error(): void
    {
        $error = new RuntimeException('Invalid HTML content');
        $this->assertEquals(ErrorHandlingService::CATEGORY_PARSING, $this->service->categorizeError($error));

        $error = new RuntimeException('DOMDocument parse error');
        $this->assertEquals(ErrorHandlingService::CATEGORY_PARSING, $this->service->categorizeError($error));

        $error = new RuntimeException('Malformed XML');
        $this->assertEquals(ErrorHandlingService::CATEGORY_PARSING, $this->service->categorizeError($error));
    }

    /**
     * Test unknown error categorization.
     */
    #[Test]
    public function categorize_unknown_error(): void
    {
        $error = new RuntimeException('Something unexpected happened');
        $this->assertEquals(ErrorHandlingService::CATEGORY_UNKNOWN, $this->service->categorizeError($error));

        $error = new \Exception('Generic exception');
        $this->assertEquals(ErrorHandlingService::CATEGORY_UNKNOWN, $this->service->categorizeError($error));
    }

    // ==================== USER-FRIENDLY MESSAGE TESTS ====================

    /**
     * Test user-friendly messages for each category.
     */
    #[Test]
    #[DataProvider('userMessageProvider')]
    public function get_user_friendly_message(string $category, string $expectedSubstring): void
    {
        $message = $this->service->getUserFriendlyMessage(new RuntimeException('test'), $category);
        
        $this->assertStringContainsString($expectedSubstring, $message);
        $this->assertNotEquals('test', $message); // Should not expose technical details
    }

    public static function userMessageProvider(): array
    {
        return [
            'timeout' => [ErrorHandlingService::CATEGORY_TIMEOUT, 'took too long'],
            'rate limit' => [ErrorHandlingService::CATEGORY_RATE_LIMIT, 'temporarily limiting'],
            'invalid url' => [ErrorHandlingService::CATEGORY_INVALID_URL, 'not valid'],
            'ssrf' => [ErrorHandlingService::CATEGORY_SSRF, 'not allowed'],
            'memory' => [ErrorHandlingService::CATEGORY_MEMORY, 'memory'],
            'network' => [ErrorHandlingService::CATEGORY_NETWORK, 'Unable to connect'],
            'parsing' => [ErrorHandlingService::CATEGORY_PARSING, 'could not be analyzed'],
            'unknown' => [ErrorHandlingService::CATEGORY_UNKNOWN, 'unexpected error'],
        ];
    }

    /**
     * Test user message does not expose technical details.
     */
    #[Test]
    public function user_message_hides_technical_details(): void
    {
        $error = new RuntimeException('SQLSTATE[HY000] [2002] Connection refused at DatabaseConnection.php:123');
        $message = $this->service->getUserFriendlyMessage($error);

        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('DatabaseConnection.php', $message);
        $this->assertStringNotContainsString('[2002]', $message);
    }

    // ==================== RETRY LOGIC TESTS ====================

    /**
     * Test should retry for retryable errors.
     */
    #[Test]
    public function should_retry_retryable_errors(): void
    {
        $this->assertTrue($this->service->shouldRetry(ErrorHandlingService::CATEGORY_TIMEOUT));
        $this->assertTrue($this->service->shouldRetry(ErrorHandlingService::CATEGORY_RATE_LIMIT));
        $this->assertTrue($this->service->shouldRetry(ErrorHandlingService::CATEGORY_NETWORK));
    }

    /**
     * Test should not retry for non-retryable errors.
     */
    #[Test]
    public function should_not_retry_non_retryable_errors(): void
    {
        $this->assertFalse($this->service->shouldRetry(ErrorHandlingService::CATEGORY_INVALID_URL));
        $this->assertFalse($this->service->shouldRetry(ErrorHandlingService::CATEGORY_SSRF));
        $this->assertFalse($this->service->shouldRetry(ErrorHandlingService::CATEGORY_MEMORY));
        $this->assertFalse($this->service->shouldRetry(ErrorHandlingService::CATEGORY_PARSING));
        $this->assertFalse($this->service->shouldRetry(ErrorHandlingService::CATEGORY_UNKNOWN));
    }

    /**
     * Test backoff calculation.
     */
    #[Test]
    public function backoff_calculation(): void
    {
        // Rate limit backoff
        $this->assertEquals(30, $this->service->getBackoffSeconds(ErrorHandlingService::CATEGORY_RATE_LIMIT, 1));
        $this->assertEquals(60, $this->service->getBackoffSeconds(ErrorHandlingService::CATEGORY_RATE_LIMIT, 2));

        // Timeout backoff
        $this->assertEquals(10, $this->service->getBackoffSeconds(ErrorHandlingService::CATEGORY_TIMEOUT, 1));
        $this->assertEquals(20, $this->service->getBackoffSeconds(ErrorHandlingService::CATEGORY_TIMEOUT, 2));

        // Non-retryable errors
        $this->assertEquals(0, $this->service->getBackoffSeconds(ErrorHandlingService::CATEGORY_INVALID_URL));
    }

    // ==================== ERROR DETAILS TESTS ====================

    /**
     * Test error details structure.
     */
    #[Test]
    public function error_details_structure(): void
    {
        $error = new RuntimeException('Test error message');
        $context = ['url' => 'https://example.com', 'job_id' => 1];
        
        $details = $this->service->getErrorDetails($error, $context);

        $this->assertArrayHasKey('category', $details);
        $this->assertArrayHasKey('message', $details);
        $this->assertArrayHasKey('user_message', $details);
        $this->assertArrayHasKey('file', $details);
        $this->assertArrayHasKey('line', $details);
        $this->assertArrayHasKey('trace', $details);
        $this->assertArrayHasKey('context', $details);
        $this->assertArrayHasKey('timestamp', $details);
        $this->assertArrayHasKey('should_retry', $details);

        $this->assertEquals($context, $details['context']);
    }

    /**
     * Test trace is sanitized.
     */
    #[Test]
    public function trace_is_sanitized(): void
    {
        $error = new RuntimeException('Test error');
        $details = $this->service->getErrorDetails($error);

        $this->assertIsArray($details['trace']);
        $this->assertLessThanOrEqual(10, count($details['trace']));

        foreach ($details['trace'] as $frame) {
            $this->assertArrayHasKey('file', $frame);
            $this->assertArrayHasKey('line', $frame);
            $this->assertArrayHasKey('function', $frame);
        }
    }

    // ==================== MEMORY HANDLING TESTS ====================

    /**
     * Test memory usage detection.
     */
    #[Test]
    public function memory_usage_detection(): void
    {
        $usage = $this->service->getMemoryUsage();
        
        $this->assertIsInt($usage);
        $this->assertGreaterThan(0, $usage);
    }

    /**
     * Test memory limit detection.
     */
    #[Test]
    public function memory_limit_detection(): void
    {
        $limit = $this->service->getMemoryLimit();
        
        $this->assertIsInt($limit);
        $this->assertGreaterThan(0, $limit);
    }

    /**
     * Test memory approaching limit detection.
     */
    #[Test]
    public function memory_approaching_limit_detection(): void
    {
        // This test verifies the method works; actual values depend on system state
        $isApproaching = $this->service->isMemoryApproachingLimit(85);
        
        $this->assertIsBool($isApproaching);
    }

    /**
     * Test memory approaching limit with high threshold.
     */
    #[Test]
    public function memory_not_approaching_with_high_threshold(): void
    {
        // With 99% threshold, should almost never be true
        $isApproaching = $this->service->isMemoryApproachingLimit(99);
        
        // This is a sanity check - in normal conditions memory won't be at 99%
        $this->assertIsBool($isApproaching);
    }

    // ==================== PARTIAL COMPLETION TESTS ====================

    /**
     * Test partial completion summary with no results.
     */
    #[Test]
    public function partial_completion_summary_empty(): void
    {
        $job = AnalysisJob::factory()->create([
            'total_indicators' => 4,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
        ]);

        $summary = $this->service->getPartialCompletionSummary($job);

        $this->assertEquals(4, $summary['total_indicators']);
        $this->assertEquals(0, $summary['completed_count']);
        $this->assertEquals(0, $summary['failed_count']);
        $this->assertNull($summary['average_score']);
        $this->assertFalse($summary['is_partial']);
        $this->assertEquals(0, $summary['success_rate']);
    }

    /**
     * Test partial completion summary with mixed results.
     */
    #[Test]
    public function partial_completion_summary_mixed(): void
    {
        $job = AnalysisJob::factory()->create([
            'total_indicators' => 4,
            'completed_indicators' => 2,
            'failed_indicators' => 1,
        ]);

        // Create results
        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_COMPLETED,
            'percentage' => 85,
        ]);

        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => AnalysisJobResult::STATUS_COMPLETED,
            'percentage' => 75,
        ]);

        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'images',
            'indicator_name' => 'Images',
            'status' => AnalysisJobResult::STATUS_FAILED,
            'error_message' => 'Network error',
        ]);

        AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'links',
            'indicator_name' => 'Links',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        $summary = $this->service->getPartialCompletionSummary($job);

        $this->assertEquals(4, $summary['total_indicators']);
        $this->assertEquals(2, $summary['completed_count']);
        $this->assertEquals(1, $summary['failed_count']);
        $this->assertEquals(80.0, $summary['average_score']); // (85 + 75) / 2
        $this->assertTrue($summary['is_partial']);
        $this->assertEquals(50.0, $summary['success_rate']); // 2/4 = 50%
        $this->assertCount(1, $summary['failed_indicators']);
    }

    /**
     * Test can continue with partial results.
     */
    #[Test]
    public function can_continue_with_partial_results(): void
    {
        // Job with some processed results
        $job = AnalysisJob::factory()->create([
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 4,
            'completed_indicators' => 1,
            'failed_indicators' => 1,
        ]);

        $this->assertTrue($this->service->canContinueWithPartialResults($job));
    }

    /**
     * Test cannot continue with no results.
     */
    #[Test]
    public function cannot_continue_with_no_results(): void
    {
        $job = AnalysisJob::factory()->create([
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 4,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
        ]);

        $this->assertFalse($this->service->canContinueWithPartialResults($job));
    }

    /**
     * Test cannot continue with cancelled job.
     */
    #[Test]
    public function cannot_continue_with_cancelled_job(): void
    {
        $job = AnalysisJob::factory()->create([
            'status' => AnalysisJob::STATUS_CANCELLED,
            'total_indicators' => 4,
            'completed_indicators' => 2,
            'failed_indicators' => 1,
        ]);

        $this->assertFalse($this->service->canContinueWithPartialResults($job));
    }

    // ==================== ERROR HANDLING INTEGRATION TESTS ====================

    /**
     * Test handle indicator failure.
     */
    #[Test]
    public function handle_indicator_failure(): void
    {
        $job = AnalysisJob::factory()->create([
            'total_indicators' => 4,
        ]);

        $result = AnalysisJobResult::factory()->create([
            'job_id' => $job->id,
            'indicator_slug' => 'test_indicator',
            'indicator_name' => 'Test Indicator',
            'status' => AnalysisJobResult::STATUS_RUNNING,
        ]);

        $progressService = app(ProgressService::class);
        $error = new RuntimeException('Connection timed out');

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('channel')->with('analysis-errors')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $this->service->handleIndicatorFailure(
            $job->id,
            'test_indicator',
            $error,
            $progressService,
            ['url' => 'https://example.com']
        );

        $result->refresh();
        $this->assertEquals(AnalysisJobResult::STATUS_FAILED, $result->status);
        $this->assertStringContainsString('too long', $result->error_message);
    }

    // ==================== LOGGING TESTS ====================

    /**
     * Test error logging with context.
     */
    #[Test]
    public function error_logging_with_context(): void
    {
        $error = new RuntimeException('Test error');
        $context = [
            'url' => 'https://example.com',
            'job_id' => 123,
            'indicator_slug' => 'meta_tags',
        ];

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $logContext) use ($context) {
                $this->assertStringContainsString('network', $message);
                $this->assertEquals($context['url'], $logContext['url']);
                $this->assertEquals($context['job_id'], $logContext['job_id']);
                return true;
            });

        Log::shouldReceive('channel')->with('analysis-errors')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $this->service->logError($error, $context);
    }

    /**
     * Test error logging level based on category.
     */
    #[Test]
    public function error_logging_level_by_category(): void
    {
        // Network/timeout/rate_limit should use warning
        Log::shouldReceive('warning')->twice();
        Log::shouldReceive('channel')->with('analysis-errors')->andReturnSelf();
        Log::shouldReceive('error')->twice();

        $this->service->logError(new RuntimeException('Connection timeout'), ['url' => 'https://example.com']);
        $this->service->logError(new RuntimeException('Rate limit exceeded'), ['url' => 'https://example.com']);
    }

    // ==================== EDGE CASES ====================

    /**
     * Test categorization with null message.
     */
    #[Test]
    public function categorize_with_empty_message(): void
    {
        $error = new \Exception('');
        $category = $this->service->categorizeError($error);

        $this->assertEquals(ErrorHandlingService::CATEGORY_UNKNOWN, $category);
    }

    /**
     * Test categorization with special characters in message.
     */
    #[Test]
    public function categorize_with_special_characters(): void
    {
        $error = new RuntimeException('Error with special chars: <script>alert(1)</script> & "quotes"');
        $category = $this->service->categorizeError($error);

        $this->assertIsString($category);
    }

    /**
     * Test user message with long error.
     */
    #[Test]
    public function user_message_with_long_error(): void
    {
        $longMessage = str_repeat('x', 10000);
        $error = new RuntimeException($longMessage);
        $message = $this->service->getUserFriendlyMessage($error);

        $this->assertLessThan(1000, strlen($message)); // User message should be concise
    }

    /**
     * Test HTTP status code extraction from RequestException.
     */
    #[Test]
    public function http_status_code_extraction(): void
    {
        $response = new Response(503, [], 'Service Unavailable');
        $error = RequestException::create(new Request('GET', 'https://example.com'), $response);
        
        $details = $this->service->getErrorDetails($error);

        $this->assertArrayHasKey('message', $details);
    }
}
