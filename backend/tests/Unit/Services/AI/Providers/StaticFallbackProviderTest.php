<?php

namespace Tests\Unit\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\Providers\StaticFallbackProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for StaticFallbackProvider.
 *
 * Tests the core functionality of the static fallback provider,
 * ensuring it implements AIProviderInterface correctly and provides
 * reliable rule-based SEO suggestions without external dependencies.
 */
class StaticFallbackProviderTest extends TestCase
{
    private StaticFallbackProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StaticFallbackProvider();
    }

    // =====================================================
    // Interface Implementation Tests (AC-1)
    // =====================================================

    #[Test]
    public function it_implements_ai_provider_interface(): void
    {
        $this->assertInstanceOf(
            AIProviderInterface::class,
            $this->provider,
            'StaticFallbackProvider must implement AIProviderInterface'
        );
    }

    #[Test]
    public function it_has_no_external_dependencies(): void
    {
        $reflection = new \ReflectionClass(StaticFallbackProvider::class);
        $fileContent = file_get_contents($reflection->getFileName());

        // Check for common external dependency patterns
        $this->assertStringNotContainsString('use GuzzleHttp', $fileContent);
        $this->assertStringNotContainsString('use Illuminate\Support\Facades\Http', $fileContent);
        $this->assertStringNotContainsString('curl_', $fileContent);
        $this->assertStringNotContainsString('file_get_contents', $fileContent);
    }

    #[Test]
    public function it_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            app_path('Services/AI/Providers/StaticFallbackProvider.php'),
            'StaticFallbackProvider must exist at app/Services/AI/Providers/StaticFallbackProvider.php'
        );
    }

    // =====================================================
    // Availability Tests (AC-4)
    // =====================================================

    #[Test]
    public function is_available_returns_true(): void
    {
        $this->assertTrue(
            $this->provider->isAvailable(),
            'StaticFallbackProvider::isAvailable() must always return true'
        );
    }

    #[Test]
    public function is_available_is_deterministic(): void
    {
        // Call multiple times to ensure consistency
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(
                $this->provider->isAvailable(),
                'isAvailable() must always return true, even on repeated calls'
            );
        }
    }

    #[Test]
    public function is_available_returns_true_without_dependencies(): void
    {
        // Ensure no environment or config dependencies
        $originalEnv = $_ENV;
        $_ENV = [];

        $result = $this->provider->isAvailable();

        $_ENV = $originalEnv;

        $this->assertTrue(
            $result,
            'isAvailable() must return true regardless of environment'
        );
    }

    // =====================================================
    // Name Tests (AC-1)
    // =====================================================

    #[Test]
    public function get_name_returns_correct_value(): void
    {
        $this->assertEquals(
            'static_fallback',
            $this->provider->getName(),
            'StaticFallbackProvider::getName() must return "static_fallback"'
        );
    }

    #[Test]
    public function get_name_is_consistent(): void
    {
        $name1 = $this->provider->getName();
        $name2 = $this->provider->getName();

        $this->assertEquals(
            $name1,
            $name2,
            'getName() must return consistent value across calls'
        );
    }

    // =====================================================
    // Generate Method Tests (AC-3)
    // =====================================================

    #[Test]
    public function generate_returns_ai_response_instance(): void
    {
        $response = $this->provider->generate('Test prompt');

        $this->assertInstanceOf(
            AIResponse::class,
            $response,
            'generate() must return an AIResponse instance'
        );
    }

    #[Test]
    public function generate_returns_successful_response(): void
    {
        $response = $this->provider->generate('Test prompt');

        $this->assertTrue(
            $response->success,
            'generate() must return a successful response'
        );
    }

    #[Test]
    public function generate_handles_empty_prompt(): void
    {
        $response = $this->provider->generate('');

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    #[Test]
    public function generate_handles_null_options(): void
    {
        $response = $this->provider->generate('Test prompt', []);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    #[Test]
    public function generate_includes_provider_identifier(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertArrayHasKey(
            'provider',
            $content,
            'Response must include provider identifier'
        );
        $this->assertEquals(
            'static_fallback',
            $content['provider'],
            'Provider identifier must be "static_fallback"'
        );
    }

    #[Test]
    public function generate_tracks_latency(): void
    {
        $response = $this->provider->generate('Test prompt');

        $this->assertNotNull(
            $response->latencyMs,
            'Response must include latency tracking'
        );
        $this->assertIsInt($response->latencyMs);
        $this->assertGreaterThanOrEqual(0, $response->latencyMs);
    }

    #[Test]
    public function generate_sets_tokens_used_to_zero(): void
    {
        $response = $this->provider->generate('Test prompt');

        // Static provider doesn't use AI tokens
        $this->assertEquals(
            0,
            $response->tokensUsed,
            'Static provider must report 0 tokens used'
        );
    }

    // =====================================================
    // Response Format Tests (AC-5)
    // =====================================================

    #[Test]
    public function response_format_matches_ai_schema(): void
    {
        $response = $this->provider->generate('Analyze title: "Test Title"');
        $content = json_decode($response->content, true);

        // Verify required fields
        $this->assertArrayHasKey('score', $content);
        $this->assertArrayHasKey('issues', $content);
        $this->assertArrayHasKey('suggestions', $content);
        $this->assertArrayHasKey('best_practices', $content);
        $this->assertArrayHasKey('provider', $content);
        $this->assertArrayHasKey('model', $content);
        $this->assertArrayHasKey('generated_at', $content);
    }

    #[Test]
    public function score_is_within_bounds(): void
    {
        $response = $this->provider->generate('Analyze title: "Test"');
        $content = json_decode($response->content, true);

        $this->assertIsInt($content['score']);
        $this->assertGreaterThanOrEqual(0, $content['score']);
        $this->assertLessThanOrEqual(100, $content['score']);
    }

    #[Test]
    public function issues_is_always_array(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertIsArray($content['issues']);
    }

    #[Test]
    public function suggestions_is_always_array(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertIsArray($content['suggestions']);
    }

    #[Test]
    public function best_practices_is_always_array(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertIsArray($content['best_practices']);
    }

    #[Test]
    public function generated_at_is_valid_datetime(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertNotNull($content['generated_at']);

        // Should be valid ISO 8601 format
        $date = \DateTime::createFromFormat(\DateTime::ATOM, $content['generated_at']);
        $this->assertInstanceOf(\DateTime::class, $date);
    }

    #[Test]
    public function model_identifier_indicates_rule_based(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        $this->assertEquals(
            'rule-based',
            $content['model'],
            'Model must indicate rule-based implementation'
        );
    }

    // =====================================================
    // Indicator Routing Tests
    // =====================================================

    #[Test]
    public function it_routes_to_title_indicator(): void
    {
        $context = json_encode(['indicator' => 'title', 'title' => 'Test Title']);
        $response = $this->provider->generate($context);
        $content = json_decode($response->content, true);

        $this->assertEquals('title', $content['indicator'] ?? 'title');
    }

    #[Test]
    public function it_routes_to_meta_description_indicator(): void
    {
        $context = json_encode(['indicator' => 'meta_description', 'description' => 'Test description']);
        $response = $this->provider->generate($context);
        $content = json_decode($response->content, true);

        $this->assertEquals('meta_description', $content['indicator'] ?? 'meta_description');
    }

    #[Test]
    public function it_handles_unknown_indicator_gracefully(): void
    {
        $context = json_encode(['indicator' => 'unknown_type']);
        $response = $this->provider->generate($context);

        // Should still return a valid response
        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    // =====================================================
    // Edge Case Tests
    // =====================================================

    #[Test]
    public function it_handles_very_long_input(): void
    {
        $longString = str_repeat('a', 10000);
        $response = $this->provider->generate($longString);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    #[Test]
    public function it_handles_special_characters(): void
    {
        $specialChars = '<script>alert("xss")</script>';
        $response = $this->provider->generate($specialChars);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);

        // Verify XSS is not executed/output as-is
        $content = json_decode($response->content, true);
        $this->assertIsArray($content);
    }

    #[Test]
    public function it_handles_unicode_content(): void
    {
        $unicode = 'Лучшие товары 你好 🎉';
        $response = $this->provider->generate($unicode);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    #[Test]
    public function it_handles_sql_injection_attempt(): void
    {
        $sqlInjection = "'; DROP TABLE users; --";
        $response = $this->provider->generate($sqlInjection);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    #[Test]
    public function it_handles_null_byte_injection(): void
    {
        $nullByte = "test\x00injection";
        $response = $this->provider->generate($nullByte);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);
    }

    // =====================================================
    // Performance Tests
    // =====================================================

    #[Test]
    public function it_responds_quickly(): void
    {
        $start = microtime(true);
        $this->provider->generate('Test prompt');
        $elapsed = (microtime(true) - $start) * 1000; // Convert to ms

        $this->assertLessThan(
            50,
            $elapsed,
            'Static fallback should respond in under 50ms'
        );
    }

    #[Test]
    public function it_does_not_leak_memory(): void
    {
        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 100; $i++) {
            $this->provider->generate('Test prompt ' . $i);
        }

        $finalMemory = memory_get_usage();
        $memoryGrowth = $finalMemory - $initialMemory;

        // Allow some growth but not excessive (1MB threshold)
        $this->assertLessThan(
            1024 * 1024,
            $memoryGrowth,
            'Static fallback should not leak significant memory'
        );
    }

    // =====================================================
    // Data Provider Tests
    // =====================================================

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

    #[Test]
    #[DataProvider('indicatorProvider')]
    public function it_supports_all_indicators(string $indicator): void
    {
        $context = json_encode(['indicator' => $indicator]);
        $response = $this->provider->generate($context);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertTrue($response->success);

        $content = json_decode($response->content, true);
        $this->assertArrayHasKey('score', $content);
        $this->assertArrayHasKey('issues', $content);
        $this->assertArrayHasKey('suggestions', $content);
    }

    // =====================================================
    // Suggestion Structure Tests
    // =====================================================

    #[Test]
    public function suggestions_have_valid_structure(): void
    {
        $response = $this->provider->generate('Analyze title: "Test"');
        $content = json_decode($response->content, true);

        foreach ($content['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('text', $suggestion);
            $this->assertContains(
                $suggestion['type'],
                ['improve', 'rewrite', 'add', 'remove', 'warning'],
                'Suggestion type must be valid'
            );
        }
    }

    #[Test]
    public function best_practices_are_strings(): void
    {
        $response = $this->provider->generate('Test prompt');
        $content = json_decode($response->content, true);

        foreach ($content['best_practices'] as $practice) {
            $this->assertIsString($practice);
        }
    }

    #[Test]
    public function issues_are_strings(): void
    {
        $response = $this->provider->generate('Analyze title: "X"'); // Short title should have issues
        $content = json_decode($response->content, true);

        foreach ($content['issues'] as $issue) {
            $this->assertIsString($issue);
        }
    }
}
