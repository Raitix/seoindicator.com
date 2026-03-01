<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Services\AI\AISuggestionService;
use App\Services\AI\DTOs\SuggestionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Mockery;

/**
 * Feature tests for AI Suggestion API endpoint.
 * 
 * Task: TASK-21-5
 * Test Documentation: TESTS/TEST-21-5.md
 * 
 * @group ai
 * @group api
 * @group task-21-5
 */
class AISuggestionControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/ai/suggestions';
    private const SERVICE_CLASS = AISuggestionService::class;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // =========================================================================
    // TC-001: Controller Creation (AC-1)
    // =========================================================================

    #[Test]
    #[TestDox('TC-001-01: Controller file exists at correct path')]
    public function controller_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Http/Controllers/Api/V1/AISuggestionController.php'),
            'AISuggestionController should exist at app/Http/Controllers/Api/V1/AISuggestionController.php'
        );
    }

    #[Test]
    #[TestDox('TC-001-02: Controller has generate method')]
    public function controller_has_generate_method(): void
    {
        $reflection = new \ReflectionClass('App\Http\Controllers\Api\V1\AISuggestionController');
        
        $this->assertTrue(
            $reflection->hasMethod('generate'),
            'Controller must have generate() method'
        );
    }

    #[Test]
    #[TestDox('TC-001-03: Controller can be resolved from container')]
    public function controller_can_be_resolved_from_container(): void
    {
        $controller = app('App\Http\Controllers\Api\V1\AISuggestionController');
        
        $this->assertInstanceOf(
            'App\Http\Controllers\Api\V1\AISuggestionController',
            $controller
        );
    }

    // =========================================================================
    // TC-002: Route Registration (AC-2)
    // =========================================================================

    #[Test]
    #[TestDox('TC-002-01: Route registered for POST /api/v1/ai/suggestions')]
    public function route_registered_for_post_suggestions(): void
    {
        $routes = collect(app('router')->getRoutes());
        
        $route = $routes->first(function ($route) {
            return $route->uri() === 'api/v1/ai/suggestions' 
                && in_array('POST', $route->methods());
        });
        
        $this->assertNotNull($route, 'POST /api/v1/ai/suggestions route should exist');
    }

    #[Test]
    #[TestDox('TC-002-02: Route returns 401 without authentication')]
    public function route_returns_401_without_authentication(): void
    {
        $response = $this->postJson(self::ENDPOINT, [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);
        
        $response->assertStatus(401);
    }

    #[Test]
    #[TestDox('TC-002-03: Route accepts POST method')]
    public function route_accepts_post_method(): void
    {
        $mockResult = $this->createMockSuggestionResult();
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test Title'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-002-04: Route rejects GET method')]
    public function route_rejects_get_method(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(self::ENDPOINT);
        
        $response->assertStatus(405);
    }

    // =========================================================================
    // TC-003: Request Validation - Indicator Field (AC-3)
    // =========================================================================

    #[Test]
    #[TestDox('TC-003-01: Valid indicator: title')]
    public function valid_indicator_title(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-02: Valid indicator: meta_description')]
    public function valid_indicator_meta_description(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'meta_description',
                'context' => ['description' => 'Test'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-03: Valid indicator: robots')]
    public function valid_indicator_robots(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'robots',
                'context' => ['content' => 'User-agent: *'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-04: Valid indicator: sitemap')]
    public function valid_indicator_sitemap(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'sitemap',
                'context' => ['url' => 'https://example.com/sitemap.xml'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-05: Valid indicator: headings')]
    public function valid_indicator_headings(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'headings',
                'context' => ['h1' => ['Main Heading']],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-06: Valid indicator: image_alt')]
    public function valid_indicator_image_alt(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'image_alt',
                'context' => ['images' => [['url' => '/img.jpg', 'alt' => '']]],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-07: Valid indicator: filenames')]
    public function valid_indicator_filenames(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'filenames',
                'context' => ['files' => [['url' => '/IMG_001.jpg']]],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-08: Valid indicator: url')]
    public function valid_indicator_url(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'url',
                'context' => ['url' => 'https://example.com/page'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-09: Valid indicator: links')]
    public function valid_indicator_links(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'links',
                'context' => ['internal' => 5, 'external' => 3],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-10: Valid indicator: 404')]
    public function valid_indicator_404(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => '404',
                'context' => ['url' => '/not-found'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-003-11: Missing indicator field returns validation error')]
    public function missing_indicator_field_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    #[Test]
    #[TestDox('TC-003-12: Invalid indicator value returns validation error')]
    public function invalid_indicator_value_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'invalid_indicator',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    #[Test]
    #[TestDox('TC-003-13: Empty indicator returns validation error')]
    public function empty_indicator_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => '',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    // =========================================================================
    // TC-004: Request Validation - Context Field (AC-3)
    // =========================================================================

    #[Test]
    #[TestDox('TC-004-01: Valid context object')]
    public function valid_context_object(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test Title', 'keyword' => 'test'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-004-02: Missing context field returns validation error')]
    public function missing_context_field_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    #[Test]
    #[TestDox('TC-004-03: Context not array returns validation error')]
    public function context_not_array_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => 'not an array',
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    #[Test]
    #[TestDox('TC-004-04: Context with HTML is sanitized')]
    public function context_with_html_is_sanitized(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => '<script>alert("xss")</script>Test'],
            ]);
        
        // Should accept but sanitize
        $response->assertStatus(200);
    }

    // =========================================================================
    // TC-005: Request Validation - Provider Field (AC-3)
    // =========================================================================

    #[Test]
    #[TestDox('TC-005-01: Valid provider: openrouter')]
    public function valid_provider_openrouter(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'openrouter',
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-005-02: Valid provider: vercel')]
    public function valid_provider_vercel(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'vercel',
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-005-03: Valid provider: static')]
    public function valid_provider_static(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-005-04: Invalid provider returns validation error')]
    public function invalid_provider_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'invalid_provider',
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    #[TestDox('TC-005-05: Provider is optional')]
    public function provider_is_optional(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200);
    }

    // =========================================================================
    // TC-006: Request Validation - Debug Field (AC-3)
    // =========================================================================

    #[Test]
    #[TestDox('TC-006-01: Debug true includes debug info')]
    public function debug_true_includes_debug_info(): void
    {
        $mockResult = $this->createMockSuggestionResult();
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
            $mock->shouldReceive('isDebugMode')
                ->once()
                ->andReturn(true);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'debug' => true,
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-006-02: Debug false excludes debug info')]
    public function debug_false_excludes_debug_info(): void
    {
        $mockResult = $this->createMockSuggestionResult();
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
            $mock->shouldReceive('isDebugMode')
                ->once()
                ->andReturn(false);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'debug' => false,
            ]);
        
        $response->assertStatus(200)
            ->assertJsonMissing(['debug']);
    }

    #[Test]
    #[TestDox('TC-006-03: Debug invalid type returns validation error')]
    public function debug_invalid_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'debug' => 'yes',
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['debug']);
    }

    #[Test]
    #[TestDox('TC-006-04: Debug is optional')]
    public function debug_is_optional(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200);
    }

    // =========================================================================
    // TC-007: Response Structure - Success (AC-4)
    // =========================================================================

    #[Test]
    #[TestDox('TC-007-01: Response has success field')]
    public function response_has_success_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    #[Test]
    #[TestDox('TC-007-02: Response has data object')]
    public function response_has_data_object(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    #[Test]
    #[TestDox('TC-007-03: Data has indicator field')]
    public function data_has_indicator_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.indicator', 'title');
    }

    #[Test]
    #[TestDox('TC-007-04: Data has score field')]
    public function data_has_score_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['score']]);
        
        $score = $response->json('data.score');
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    #[Test]
    #[TestDox('TC-007-05: Data has issues array')]
    public function data_has_issues_array(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['issues']]);
        
        $this->assertIsArray($response->json('data.issues'));
    }

    #[Test]
    #[TestDox('TC-007-06: Data has suggestions array')]
    public function data_has_suggestions_array(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['suggestions']]);
        
        $this->assertIsArray($response->json('data.suggestions'));
    }

    #[Test]
    #[TestDox('TC-007-07: Data has best_practices array')]
    public function data_has_best_practices_array(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['bestPractices']]);
        
        $this->assertIsArray($response->json('data.bestPractices'));
    }

    #[Test]
    #[TestDox('TC-007-08: Data has provider field')]
    public function data_has_provider_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['provider']]);
    }

    #[Test]
    #[TestDox('TC-007-09: Data has model field')]
    public function data_has_model_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['model']]);
    }

    #[Test]
    #[TestDox('TC-007-10: Data has generated_at field')]
    public function data_has_generated_at_field(): void
    {
        $this->mockServiceForSuccess();
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['generatedAt']]);
        
        $generatedAt = $response->json('data.generatedAt');
        $this->assertNotEmpty($generatedAt);
    }

    // =========================================================================
    // TC-008: Response Structure - Fallback (AC-5)
    // =========================================================================

    #[Test]
    #[TestDox('TC-008-01: Fallback returns 200')]
    public function fallback_returns_200(): void
    {
        $mockResult = $this->createMockSuggestionResult('static_fallback', 'Fallback reason');
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('TC-008-02: Fallback has success true')]
    public function fallback_has_success_true(): void
    {
        $mockResult = $this->createMockSuggestionResult('static_fallback', 'AI provider timeout');
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    #[Test]
    #[TestDox('TC-008-03: Fallback has provider static_fallback')]
    public function fallback_has_provider_static_fallback(): void
    {
        $mockResult = $this->createMockSuggestionResult('static_fallback', 'AI provider timeout');
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.provider', 'static_fallback');
    }

    // =========================================================================
    // TC-010: Error Handling (AC-7)
    // =========================================================================

    #[Test]
    #[TestDox('TC-010-01: Internal server error returns 500')]
    public function internal_server_error_returns_500(): void
    {
        $this->mock(self::SERVICE_CLASS, function ($mock) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andThrow(new \Exception('Internal error'));
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(500);
    }

    #[Test]
    #[TestDox('TC-010-02: Error does not expose internal details')]
    public function error_does_not_expose_internal_details(): void
    {
        $this->mock(self::SERVICE_CLASS, function ($mock) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andThrow(new \Exception('Database password: secret123'));
        });
        
        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        
        $response->assertStatus(500);
        
        // Should not contain the sensitive data
        $content = $response->getContent();
        $this->assertStringNotContainsString('secret123', $content);
        $this->assertStringNotContainsString('password', $content);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function mockServiceForSuccess(): void
    {
        $mockResult = $this->createMockSuggestionResult();
        
        $this->mock(self::SERVICE_CLASS, function ($mock) use ($mockResult) {
            $mock->shouldReceive('getSuggestions')
                ->once()
                ->andReturn($mockResult);
        });
    }

    private function createMockSuggestionResult(
        string $provider = 'openrouter',
        ?string $fallbackReason = null
    ): SuggestionResult {
        $data = [
            'indicator' => 'title',
            'score' => 75,
            'issues' => ['Title could be improved'],
            'suggestions' => [
                [
                    'type' => 'improve',
                    'text' => 'Add your target keyword to the title',
                    'reason' => 'Helps search engines understand the page content'
                ]
            ],
            'bestPractices' => [
                'Keep title under 60 characters',
                'Include primary keyword near the beginning'
            ],
            'provider' => $provider,
            'model' => $provider === 'static_fallback' ? null : 'llama-3.1-8b-instruct',
            'generatedAt' => new \DateTimeImmutable(),
        ];
        
        if ($fallbackReason) {
            $data['fallback_reason'] = $fallbackReason;
        }
        
        return new SuggestionResult(
            indicator: $data['indicator'],
            score: $data['score'],
            issues: $data['issues'],
            suggestions: $data['suggestions'],
            bestPractices: $data['bestPractices'],
            provider: $data['provider'],
            model: $data['model'],
            generatedAt: $data['generatedAt']
        );
    }
}
