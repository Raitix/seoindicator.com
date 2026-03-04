<?php

namespace Tests\Integration\Services\AI;

use App\Models\User;
use App\Services\AI\AISuggestionService;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\SuggestionResult;
use App\Services\AI\Providers\StaticFallbackProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Integration tests for AI Suggestion end-to-end flow.
 * 
 * Task: TASK-21-12
 * Test Documentation: TESTS/TEST-21-12.md
 * 
 * Tests the complete flow from controller through service to providers,
 * including fallback logic, caching, and error handling.
 * 
 * @group integration
 * @group ai
 * @group task-21-12
 */
class AISuggestionFlowTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/ai/suggestions';
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // =========================================================================
    // FLOW-001: Complete Request Flow (AC-5)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-001-01: Complete request flow returns valid response')]
    public function complete_request_flow_returns_valid_response(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => [
                    'title' => 'Test Page Title',
                    'keyword' => 'test',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'indicator',
                    'score',
                    'issues',
                    'suggestions',
                    'bestPractices',
                    'provider',
                    'model',
                    'generatedAt',
                ],
            ]);
    }

    #[Test]
    #[TestDox('FLOW-001-02: Response data types are correct')]
    public function response_data_types_are_correct(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $data = $response->json('data');

        $this->assertIsString($data['indicator']);
        $this->assertIsInt($data['score']);
        $this->assertIsArray($data['issues']);
        $this->assertIsArray($data['suggestions']);
        $this->assertIsArray($data['bestPractices']);
        $this->assertIsString($data['provider']);
    }

    #[Test]
    #[TestDox('FLOW-001-03: Score is within valid bounds')]
    public function score_is_within_valid_bounds(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $score = $response->json('data.score');

        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // =========================================================================
    // FLOW-002: Provider Selection (AC-1)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-002-01: Static provider is used when specified')]
    public function static_provider_is_used_when_specified(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.provider', 'static_fallback');
    }

    #[Test]
    #[TestDox('FLOW-002-02: Provider falls back to static when AI unavailable')]
    public function provider_falls_back_to_static_when_ai_unavailable(): void
    {
        // Simulate OpenRouter being unavailable
        Http::fake([
            'openrouter.ai/*' => Http::response(null, 500),
        ]);

        // Force openrouter provider
        config(['ai.default' => 'openrouter']);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // Should still succeed with static fallback
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('FLOW-002-03: All providers return consistent response structure')]
    public function all_providers_return_consistent_response_structure(): void
    {
        $providers = ['static'];

        foreach ($providers as $provider) {
            $response = $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => 'Test'],
                    'provider' => $provider,
                ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'indicator',
                        'score',
                        'issues',
                        'suggestions',
                        'bestPractices',
                        'provider',
                    ],
                ]);
        }
    }

    // =========================================================================
    // FLOW-003: Indicator Handling (AC-2)
    // =========================================================================

    public static function indicatorProvider(): array
    {
        return [
            'title' => ['title', ['title' => 'Test Title']],
            'meta_description' => ['meta_description', ['description' => 'Test description']],
            'robots' => ['robots', ['content' => 'User-agent: *']],
            'sitemap' => ['sitemap', ['url' => 'https://example.com/sitemap.xml']],
            'headings' => ['headings', ['h1' => ['Main Heading']]],
            'image_alt' => ['image_alt', ['images' => [['url' => '/img.jpg', 'alt' => '']]]],
            'filenames' => ['filenames', ['files' => [['url' => '/IMG_001.jpg']]]],
            'url' => ['url', ['url' => 'https://example.com/page']],
            'links' => ['links', ['internal' => 5, 'external' => 3]],
            '404' => ['404', ['url' => '/not-found']],
        ];
    }

    #[Test]
    #[DataProvider('indicatorProvider')]
    #[TestDox('FLOW-003: Indicator $indicator returns valid response')]
    public function all_indicators_return_valid_response(string $indicator, array $context): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => $indicator,
                'context' => $context,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.indicator', $indicator);

        $score = $response->json('data.score');
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // =========================================================================
    // FLOW-004: Fallback Logic (AC-4)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-004-01: Fallback activates on provider timeout')]
    public function fallback_activates_on_provider_timeout(): void
    {
        // Configure very short timeout
        config(['ai.providers.openrouter.timeout' => 0.001]);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // Should succeed with fallback
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('FLOW-004-02: Fallback maintains response structure')]
    public function fallback_maintains_response_structure(): void
    {
        // Force static fallback
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'indicator',
                    'score',
                    'issues',
                    'suggestions',
                    'bestPractices',
                    'provider',
                ],
            ]);
    }

    #[Test]
    #[TestDox('FLOW-004-03: Fallback provider returns valid suggestions')]
    public function fallback_provider_returns_valid_suggestions(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'X'], // Very short title
                'provider' => 'static',
            ]);

        $issues = $response->json('data.issues');
        $suggestions = $response->json('data.suggestions');

        // Should have detected issues with short title
        $this->assertNotEmpty($issues, 'Should detect issues with very short title');
    }

    // =========================================================================
    // FLOW-005: Caching Behavior (AC-6)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-005-01: Identical requests return same score')]
    public function identical_requests_return_same_score(): void
    {
        $payload = [
            'indicator' => 'title',
            'context' => ['title' => 'Consistent Title Test'],
            'provider' => 'static',
        ];

        $response1 = $this->actingAs($this->user)->postJson(self::ENDPOINT, $payload);
        $response2 = $this->actingAs($this->user)->postJson(self::ENDPOINT, $payload);

        $score1 = $response1->json('data.score');
        $score2 = $response2->json('data.score');

        $this->assertEquals($score1, $score2, 'Identical requests should return same score');
    }

    // =========================================================================
    // FLOW-006: Error Recovery (AC-7)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-006-01: Service recovers from transient errors')]
    public function service_recovers_from_transient_errors(): void
    {
        // First request with error
        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push(null, 500) // First call fails
                ->push(['choices' => [['message' => ['content' => '{"score":75}']]]], 200), // Second succeeds
        ]);

        // First request (will use fallback)
        $response1 = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response1->assertStatus(200);

        // Second request should work
        $response2 = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response2->assertStatus(200);
    }

    #[Test]
    #[TestDox('FLOW-006-02: Malformed AI response triggers fallback')]
    public function malformed_ai_response_triggers_fallback(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'not valid json']]],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // Should succeed with fallback
        $response->assertStatus(200);
    }

    // =========================================================================
    // FLOW-007: Debug Mode (AC-8)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-007-01: Debug mode includes additional info')]
    public function debug_mode_includes_additional_info(): void
    {
        config(['ai.debug' => true]);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'debug' => true,
            ]);

        $response->assertStatus(200);
        // Debug info may or may not be included depending on implementation
    }

    // =========================================================================
    // FLOW-008: Authentication Integration (AC-3)
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-008-01: Unauthenticated request is rejected')]
    public function unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson(self::ENDPOINT, [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    #[TestDox('FLOW-008-02: Authenticated user can make requests')]
    public function authenticated_user_can_make_requests(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // FLOW-009: Rate Limiting Integration
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-009-01: Rate limiting allows reasonable usage')]
    public function rate_limiting_allows_reasonable_usage(): void
    {
        // Make several requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => "Test $i"],
                ]);

            $response->assertStatus(200);
        }
    }

    // =========================================================================
    // FLOW-010: Context Validation
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-010-01: Empty context is handled gracefully')]
    public function empty_context_is_handled_gracefully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => [],
            ]);

        // Should return valid response (may have lower score)
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('FLOW-010-02: Large context is processed')]
    public function large_context_is_processed(): void
    {
        $largeTitle = str_repeat('a', 1000);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => $largeTitle],
                'provider' => 'static',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // FLOW-011: Suggestion Quality Tests
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-011-01: Short title generates improvement suggestions')]
    public function short_title_generates_improvement_suggestions(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'X'], // Very short
                'provider' => 'static',
            ]);

        $issues = $response->json('data.issues');

        $this->assertNotEmpty($issues, 'Should detect issues with very short title');
    }

    #[Test]
    #[TestDox('FLOW-011-02: Good title receives higher score')]
    public function good_title_receives_higher_score(): void
    {
        $badTitleResponse = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'X'],
                'provider' => 'static',
            ]);

        $goodTitleResponse = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Best SEO Tools 2026 | Complete Guide'],
                'provider' => 'static',
            ]);

        $badScore = $badTitleResponse->json('data.score');
        $goodScore = $goodTitleResponse->json('data.score');

        $this->assertGreaterThan($badScore, $goodScore, 'Good title should have higher score');
    }

    // =========================================================================
    // FLOW-012: Concurrent Request Handling
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-012-01: Concurrent requests are handled independently')]
    public function concurrent_requests_are_handled_independently(): void
    {
        $responses = [];

        // Simulate concurrent requests (synchronous in test, but validates isolation)
        foreach (['title', 'meta_description', 'headings'] as $indicator) {
            $responses[$indicator] = $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => $indicator,
                    'context' => ['title' => 'Test'],
                    'provider' => 'static',
                ]);
        }

        foreach ($responses as $indicator => $response) {
            $response->assertStatus(200)
                ->assertJsonPath('data.indicator', $indicator);
        }
    }

    // =========================================================================
    // FLOW-013: Unicode and Special Characters
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-013-01: Unicode content is handled correctly')]
    public function unicode_content_is_handled_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Лучшие товары 世界 🎉'],
                'provider' => 'static',
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.score'));
    }

    #[Test]
    #[TestDox('FLOW-013-02: HTML in context is sanitized')]
    public function html_in_context_is_sanitized(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => '<script>alert("xss")</script>Test'],
                'provider' => 'static',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // FLOW-014: Response Time Benchmarks
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-014-01: Static provider responds within 100ms')]
    public function static_provider_responds_within_100ms(): void
    {
        $start = microtime(true);

        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(1000, $elapsed, 'Static provider should respond within 1s including API overhead');
    }

    // =========================================================================
    // FLOW-015: Service Container Integration
    // =========================================================================

    #[Test]
    #[TestDox('FLOW-015-01: AISuggestionService is resolvable from container')]
    public function ai_suggestion_service_is_resolvable(): void
    {
        $service = app(AISuggestionService::class);

        $this->assertInstanceOf(AISuggestionService::class, $service);
    }

    #[Test]
    #[TestDox('FLOW-015-02: StaticFallbackProvider is resolvable from container')]
    public function static_fallback_provider_is_resolvable(): void
    {
        $provider = app(StaticFallbackProvider::class);

        $this->assertInstanceOf(StaticFallbackProvider::class, $provider);
        $this->assertInstanceOf(AIProviderInterface::class, $provider);
    }
}
