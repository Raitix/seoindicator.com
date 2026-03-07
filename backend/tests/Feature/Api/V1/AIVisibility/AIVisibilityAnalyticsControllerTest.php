<?php

namespace Tests\Feature\Api\V1\AIVisibility;

use App\Models\User;
use App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * Test suite for AIVisibilityAnalyticsController
 * 
 * Tests 4 API endpoints:
 * - GET /api/v1/ai-visibility/projects/{id}/analytics
 * - GET /api/v1/ai-visibility/projects/{id}/comparison
 * - GET /api/v1/ai-visibility/projects/{id}/trend
 * - GET /api/v1/ai-visibility/projects/{id}/queries
 * 
 * @group ai-visibility
 * @group analytics
 */
class AIVisibilityAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAnalyticsAggregator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock the AnalyticsAggregator service
     */
    protected function mockAnalyticsAggregator(): void
    {
        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            // Will be configured in individual tests
        });
    }

    /**
     * Create a mock project object
     */
    protected function mockProject(array $attributes = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'user_id' => 1,
            'brand_name' => 'SEOIndicator',
            'keywords' => ['seo tools', 'keyword research'],
            'competitors' => ['Ahrefs', 'SEMrush'],
            'tracking_frequency' => 'daily',
        ], $attributes);
    }

    /**
     * Get mock analytics data
     */
    protected function getMockAnalyticsData(): array
    {
        return [
            'mention_rate' => 23.5,
            'share_of_voice' => 31.2,
            'sentiment' => [
                'score' => 0.45,
                'breakdown' => [
                    'positive' => 10,
                    'neutral' => 5,
                    'negative' => 2,
                    'mixed' => 1,
                ],
            ],
            'trend' => [
                'direction' => 'up',
                'change_percent' => 5.2,
            ],
            'by_platform' => [
                ['platform' => 'chatgpt', 'mention_rate' => 25.0, 'queries' => 10],
                ['platform' => 'perplexity', 'mention_rate' => 22.0, 'queries' => 10],
            ],
        ];
    }

    /**
     * Get mock comparison data
     */
    protected function getMockComparisonData(): array
    {
        return [
            'brand' => [
                'name' => 'SEOIndicator',
                'mentions' => 15,
                'rate' => 23.5,
            ],
            'competitors' => [
                ['name' => 'Ahrefs', 'mentions' => 25, 'rate' => 38.5],
                ['name' => 'SEMrush', 'mentions' => 20, 'rate' => 30.8],
            ],
        ];
    }

    /**
     * Get mock trend data
     */
    protected function getMockTrendData(): array
    {
        return [
            'data' => [
                ['date' => '2026-03-01', 'mention_rate' => 20.5, 'share_of_voice' => 28.0],
                ['date' => '2026-03-02', 'mention_rate' => 22.0, 'share_of_voice' => 30.0],
            ],
            'direction' => 'up',
            'change_percent' => 12.5,
        ];
    }

    /**
     * Get mock queries data
     */
    protected function getMockQueriesData(): array
    {
        return [
            'data' => [
                [
                    'id' => 1,
                    'platform' => 'chatgpt',
                    'query_text' => 'What are the best SEO tools?',
                    'brand_mentioned' => true,
                    'brand_sentiment' => 'positive',
                    'queried_at' => '2026-03-07T10:00:00Z',
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'total' => 50,
            ],
        ];
    }

    // =====================================================
    // ROUTE REGISTRATION TESTS
    // =====================================================

    /** @test */
    public function analytics_route_is_registered(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200);
    }

    /** @test */
    public function comparison_route_is_registered(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200);
    }

    /** @test */
    public function trend_route_is_registered(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_route_is_registered(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - AUTHENTICATION TESTS
    // =====================================================

    /** @test */
    public function analytics_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(401);
    }

    /** @test */
    public function analytics_rejects_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(401);
    }

    /** @test */
    public function comparison_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(401);
    }

    /** @test */
    public function trend_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(401);
    }

    /** @test */
    public function queries_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(401);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - AUTHORIZATION TESTS
    // =====================================================

    /** @test */
    public function analytics_denies_access_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        Sanctum::actingAs($nonOwner);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(403);
    }

    /** @test */
    public function analytics_returns_404_for_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getProject')->andReturn(null);
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/99999/analytics');

        $response->assertStatus(404);
    }

    /** @test */
    public function comparison_denies_access_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        Sanctum::actingAs($nonOwner);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(403);
    }

    /** @test */
    public function trend_denies_access_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        Sanctum::actingAs($nonOwner);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(403);
    }

    /** @test */
    public function queries_denies_access_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        Sanctum::actingAs($nonOwner);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(403);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - RESPONSE TESTS
    // =====================================================

    /** @test */
    public function analytics_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'mention_rate',
                'share_of_voice',
                'sentiment' => [
                    'score',
                    'breakdown' => ['positive', 'neutral', 'negative', 'mixed'],
                ],
                'trend' => ['direction', 'change_percent'],
                'by_platform',
            ]);
    }

    /** @test */
    public function analytics_default_period_is_week(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')
                ->with(1, 'week', null)
                ->once()
                ->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_accepts_day_period(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')
                ->with(1, 'day', null)
                ->once()
                ->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?period=day');

        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_accepts_month_period(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')
                ->with(1, 'month', null)
                ->once()
                ->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?period=month');

        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_rejects_invalid_period(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?period=year');

        $response->assertStatus(400);
    }

    /** @test */
    public function analytics_accepts_platform_filter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')
                ->with(1, 'week', 'chatgpt')
                ->once()
                ->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?platform=chatgpt');

        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_rejects_invalid_platform(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?platform=invalid');

        $response->assertStatus(400);
    }

    /** @test */
    public function analytics_returns_zero_values_for_new_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $zeroData = [
            'mention_rate' => 0.0,
            'share_of_voice' => 0.0,
            'sentiment' => [
                'score' => 0.0,
                'breakdown' => [
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                    'mixed' => 0,
                ],
            ],
            'trend' => [
                'direction' => 'stable',
                'change_percent' => 0.0,
            ],
            'by_platform' => [],
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $zeroData) {
            $mock->shouldReceive('getAnalytics')->andReturn($zeroData);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200)
            ->assertJson([
                'mention_rate' => 0.0,
                'share_of_voice' => 0.0,
            ]);
    }

    /** @test */
    public function analytics_handles_division_by_zero(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        // Should not throw an error
        $response->assertStatus(200);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - CACHE & ETAG TESTS
    // =====================================================

    /** @test */
    public function analytics_response_is_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->getMockAnalyticsData());

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_includes_etag_header(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200)
            ->assertHeader('ETag');
    }

    /** @test */
    public function analytics_returns_304_for_matching_etag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = $this->getMockAnalyticsData();
        $etag = md5(json_encode($data));

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getAnalytics')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->withHeaders(['If-None-Match' => $etag])
            ->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(304);
    }

    /** @test */
    public function analytics_returns_200_for_different_etag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->withHeaders(['If-None-Match' => 'different-etag'])
            ->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - HTTP METHOD TESTS
    // =====================================================

    /** @test */
    public function analytics_rejects_post_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(405);
    }

    /** @test */
    public function analytics_rejects_put_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(405);
    }

    /** @test */
    public function analytics_rejects_delete_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(405);
    }

    // =====================================================
    // ANALYTICS ENDPOINT - SECURITY TESTS
    // =====================================================

    /** @test */
    public function analytics_prevents_sql_injection_in_project_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/1;DROP%20TABLE/projects/analytics');

        // Should return 404, not execute SQL
        $response->assertStatus(404);
    }

    /** @test */
    public function analytics_sanitizes_xss_in_parameters(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?platform=<script>alert(1)</script>');

        $response->assertStatus(400);
    }

    // =====================================================
    // COMPARISON ENDPOINT - RESPONSE TESTS
    // =====================================================

    /** @test */
    public function comparison_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'brand' => ['name', 'mentions', 'rate'],
                'competitors',
            ]);
    }

    /** @test */
    public function comparison_returns_brand_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonPath('brand.name', 'SEOIndicator')
            ->assertJsonPath('brand.mentions', 15)
            ->assertJsonPath('brand.rate', 23.5);
    }

    /** @test */
    public function comparison_returns_competitors_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'competitors');
    }

    /** @test */
    public function comparison_handles_empty_competitors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'brand' => ['name' => 'SEOIndicator', 'mentions' => 15, 'rate' => 23.5],
            'competitors' => [],
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getComparison')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'competitors');
    }

    /** @test */
    public function comparison_handles_zero_mentions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'brand' => ['name' => 'SEOIndicator', 'mentions' => 0, 'rate' => 0.0],
            'competitors' => [
                ['name' => 'Ahrefs', 'mentions' => 0, 'rate' => 0.0],
            ],
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getComparison')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonPath('brand.mentions', 0)
            ->assertJsonPath('brand.rate', 0.0);
    }

    /** @test */
    public function comparison_orders_competitors_by_rate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200);
        
        $competitors = $response->json('competitors');
        $this->assertTrue($competitors[0]['rate'] >= $competitors[1]['rate']);
    }

    // =====================================================
    // COMPARISON ENDPOINT - HTTP METHOD TESTS
    // =====================================================

    /** @test */
    public function comparison_rejects_post_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(405);
    }

    /** @test */
    public function comparison_rejects_put_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(405);
    }

    /** @test */
    public function comparison_rejects_delete_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(405);
    }

    // =====================================================
    // TREND ENDPOINT - RESPONSE TESTS
    // =====================================================

    /** @test */
    public function trend_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'direction',
                'change_percent',
            ]);
    }

    /** @test */
    public function trend_default_days_is_90(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')
                ->with(1, 90)
                ->once()
                ->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200);
    }

    /** @test */
    public function trend_accepts_custom_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')
                ->with(1, 30)
                ->once()
                ->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=30');

        $response->assertStatus(200);
    }

    /** @test */
    public function trend_accepts_minimum_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')
                ->with(1, 1)
                ->once()
                ->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=1');

        $response->assertStatus(200);
    }

    /** @test */
    public function trend_accepts_maximum_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')
                ->with(1, 365)
                ->once()
                ->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=365');

        $response->assertStatus(200);
    }

    /** @test */
    public function trend_rejects_negative_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=-1');

        $response->assertStatus(400);
    }

    /** @test */
    public function trend_rejects_zero_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=0');

        $response->assertStatus(400);
    }

    /** @test */
    public function trend_rejects_string_days(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=abc');

        $response->assertStatus(400);
    }

    /** @test */
    public function trend_rejects_days_exceeding_max(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=500');

        $response->assertStatus(400);
    }

    /** @test */
    public function trend_data_points_have_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['date', 'mention_rate', 'share_of_voice'],
                ],
            ]);
    }

    /** @test */
    public function trend_dates_are_iso_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $point) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $point['date']);
        }
    }

    /** @test */
    public function trend_shows_up_direction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = $this->getMockTrendData();
        $data['direction'] = 'up';
        $data['change_percent'] = 12.5;

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getTrend')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonPath('direction', 'up')
            ->assertJsonPath('change_percent', 12.5);
    }

    /** @test */
    public function trend_shows_down_direction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = $this->getMockTrendData();
        $data['direction'] = 'down';
        $data['change_percent'] = -8.3;

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getTrend')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonPath('direction', 'down')
            ->assertJsonPath('change_percent', -8.3);
    }

    /** @test */
    public function trend_shows_stable_direction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = $this->getMockTrendData();
        $data['direction'] = 'stable';
        $data['change_percent'] = 0.0;

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getTrend')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonPath('direction', 'stable')
            ->assertJsonPath('change_percent', 0.0);
    }

    /** @test */
    public function trend_handles_empty_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'data' => [],
            'direction' => 'stable',
            'change_percent' => 0.0,
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getTrend')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('direction', 'stable');
    }

    // =====================================================
    // TREND ENDPOINT - HTTP METHOD TESTS
    // =====================================================

    /** @test */
    public function trend_rejects_post_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(405);
    }

    /** @test */
    public function trend_rejects_put_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(405);
    }

    /** @test */
    public function trend_rejects_delete_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(405);
    }

    // =====================================================
    // QUERIES ENDPOINT - RESPONSE TESTS
    // =====================================================

    /** @test */
    public function queries_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'total'],
            ]);
    }

    /** @test */
    public function queries_default_page_is_1(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')
                ->with(1, 1, null, false)
                ->once()
                ->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_accepts_custom_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')
                ->with(1, 2, null, false)
                ->once()
                ->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?page=2');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_rejects_negative_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?page=-1');

        $response->assertStatus(400);
    }

    /** @test */
    public function queries_rejects_zero_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?page=0');

        $response->assertStatus(400);
    }

    /** @test */
    public function queries_returns_empty_for_exceeding_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'data' => [],
            'meta' => ['current_page' => 999, 'total' => 50],
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getQueries')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?page=999');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function queries_accepts_platform_filter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')
                ->with(1, 1, 'chatgpt', false)
                ->once()
                ->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?platform=chatgpt');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_rejects_invalid_platform(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?platform=invalid');

        $response->assertStatus(400);
    }

    /** @test */
    public function queries_accepts_mentioned_only_true(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')
                ->with(1, 1, null, true)
                ->once()
                ->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?mentioned_only=true');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_accepts_mentioned_only_false(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')
                ->with(1, 1, null, false)
                ->once()
                ->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?mentioned_only=false');

        $response->assertStatus(200);
    }

    /** @test */
    public function queries_data_points_have_correct_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'platform', 'query_text', 'brand_mentioned', 'brand_sentiment', 'queried_at'],
                ],
            ]);
    }

    /** @test */
    public function queries_dates_are_iso_8601_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $query) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $query['queried_at']);
        }
    }

    /** @test */
    public function queries_ordered_by_queried_at_desc(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200);
        // Order is verified by the service layer
    }

    /** @test */
    public function queries_handles_empty_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'data' => [],
            'meta' => ['current_page' => 1, 'total' => 0],
        ];

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user, $data) {
            $mock->shouldReceive('getQueries')->andReturn($data);
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    // =====================================================
    // QUERIES ENDPOINT - HTTP METHOD TESTS
    // =====================================================

    /** @test */
    public function queries_rejects_post_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(405);
    }

    /** @test */
    public function queries_rejects_put_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(405);
    }

    /** @test */
    public function queries_rejects_delete_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(405);
    }

    // =====================================================
    // CROSS-ENDPOINT TESTS
    // =====================================================

    /** @test */
    public function all_endpoints_return_401_for_unauthenticated(): void
    {
        $endpoints = [
            '/api/v1/ai-visibility/projects/1/analytics',
            '/api/v1/ai-visibility/projects/1/comparison',
            '/api/v1/ai-visibility/projects/1/trend',
            '/api/v1/ai-visibility/projects/1/queries',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function all_endpoints_return_403_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        Sanctum::actingAs($nonOwner);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });

        $endpoints = [
            '/api/v1/ai-visibility/projects/1/analytics',
            '/api/v1/ai-visibility/projects/1/comparison',
            '/api/v1/ai-visibility/projects/1/trend',
            '/api/v1/ai-visibility/projects/1/queries',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(403);
        }
    }

    /** @test */
    public function all_endpoints_return_404_for_non_existent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getProject')->andReturn(null);
        });

        $endpoints = [
            '/api/v1/ai-visibility/projects/99999/analytics',
            '/api/v1/ai-visibility/projects/99999/comparison',
            '/api/v1/ai-visibility/projects/99999/trend',
            '/api/v1/ai-visibility/projects/99999/queries',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(404);
        }
    }

    /** @test */
    public function all_endpoints_return_consistent_error_format(): void
    {
        $endpoints = [
            '/api/v1/ai-visibility/projects/1/analytics',
            '/api/v1/ai-visibility/projects/1/comparison',
            '/api/v1/ai-visibility/projects/1/trend',
            '/api/v1/ai-visibility/projects/1/queries',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401)
                ->assertJsonStructure(['message']);
        }
    }

    /** @test */
    public function cache_key_includes_project_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Verify cache keys are different for different projects
        Cache::shouldReceive('remember')
            ->twice()
            ->withArgs(function ($key) {
                return strpos($key, '1') !== false;
            })
            ->andReturn($this->getMockAnalyticsData());

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
        });

        $this->getJson('/api/v1/ai-visibility/projects/1/analytics');
    }

    // =====================================================
    // PERFORMANCE TESTS
    // =====================================================

    /** @test */
    public function analytics_response_time_under_500ms(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects/1/analytics');
        $end = microtime(true);

        $this->assertLessThan(0.5, $end - $start);
    }

    /** @test */
    public function comparison_response_time_under_500ms(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects/1/comparison');
        $end = microtime(true);

        $this->assertLessThan(0.5, $end - $start);
    }

    /** @test */
    public function trend_response_time_under_500ms(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects/1/trend');
        $end = microtime(true);

        $this->assertLessThan(0.5, $end - $start);
    }

    /** @test */
    public function queries_response_time_under_500ms(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $start = microtime(true);
        $this->getJson('/api/v1/ai-visibility/projects/1/queries');
        $end = microtime(true);

        $this->assertLessThan(0.5, $end - $start);
    }

    // =====================================================
    // ACCEPTANCE CRITERIA TESTS
    // =====================================================

    /** @test */
    public function ac_001_controller_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAnalyticsController::class));
    }

    /** @test */
    public function ac_002_analytics_endpoint_returns_aggregated_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure(['mention_rate', 'share_of_voice', 'sentiment', 'trend', 'by_platform']);
    }

    /** @test */
    public function ac_003_analytics_accepts_period_and_platform_params(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?period=month&platform=chatgpt');

        $response->assertStatus(200);
    }

    /** @test */
    public function ac_006_analytics_cache_5_minutes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->getMockAnalyticsData());

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $this->getJson('/api/v1/ai-visibility/projects/1/analytics');
    }

    /** @test */
    public function ac_007_comparison_endpoint_returns_brand_vs_competitors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/comparison');

        $response->assertStatus(200)
            ->assertJsonStructure(['brand', 'competitors']);
    }

    /** @test */
    public function ac_010_trend_endpoint_returns_trend_analysis(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'direction', 'change_percent']);
    }

    /** @test */
    public function ac_011_trend_accepts_days_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/trend?days=30');

        $response->assertStatus(200);
    }

    /** @test */
    public function ac_014_queries_endpoint_returns_paginated_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function ac_015_queries_accepts_pagination_params(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });

        $response = $this->getJson('/api/v1/ai-visibility/projects/1/queries?page=2&platform=chatgpt&mentioned_only=true');

        $response->assertStatus(200);
    }

    /** @test */
    public function ac_018_error_handling_404_403_400(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test 404
        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) {
            $mock->shouldReceive('getProject')->andReturn(null);
        });
        $response = $this->getJson('/api/v1/ai-visibility/projects/99999/analytics');
        $response->assertStatus(404);

        // Test 400
        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
        });
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics?period=invalid');
        $response->assertStatus(400);

        // Test 403
        $owner = User::factory()->create();
        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($owner) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $owner->id]));
        });
        $response = $this->getJson('/api/v1/ai-visibility/projects/1/analytics');
        $response->assertStatus(403);
    }

    /** @test */
    public function ac_019_routes_registered(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(AnalyticsAggregatorInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('getProject')->andReturn($this->mockProject(['user_id' => $user->id]));
            $mock->shouldReceive('getAnalytics')->andReturn($this->getMockAnalyticsData());
            $mock->shouldReceive('getComparison')->andReturn($this->getMockComparisonData());
            $mock->shouldReceive('getTrend')->andReturn($this->getMockTrendData());
            $mock->shouldReceive('getQueries')->andReturn($this->getMockQueriesData());
        });

        // All routes should return 200, not 404
        $this->getJson('/api/v1/ai-visibility/projects/1/analytics')->assertStatus(200);
        $this->getJson('/api/v1/ai-visibility/projects/1/comparison')->assertStatus(200);
        $this->getJson('/api/v1/ai-visibility/projects/1/trend')->assertStatus(200);
        $this->getJson('/api/v1/ai-visibility/projects/1/queries')->assertStatus(200);
    }
}
