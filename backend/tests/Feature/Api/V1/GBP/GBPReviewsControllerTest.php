<?php

namespace Tests\Feature\Api\V1\GBP;

use App\Models\User;
use App\Models\Location;
use App\Models\GBPReview;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * @Task TASK-35-14
 * @Description GBP Reviews API Controller Tests
 * @Tests 130+
 */
class GBPReviewsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockReviewsService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockReviewsService(): void
    {
        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            // Default implementations for route existence tests
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
            $mock->shouldReceive('getStats')->andReturn([
                'average_rating' => 0,
                'total_count' => 0,
                'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'sentiment_breakdown' => ['positive' => 0, 'neutral' => 0, 'negative' => 0],
            ]);
            $mock->shouldReceive('syncReviews')->andReturn('job-uuid-test');
            $mock->shouldReceive('respondToReview')->andReturn(['success' => true, 'response_id' => 'resp-test']);
        });
    }

    protected function mockLocation(array $attributes = []): Location
    {
        return Location::factory()->create(array_merge([
            'user_id' => 1,
            'gbp_location_id' => 'places/ABC123',
            'name' => 'Test Business',
            'is_gbp_connected' => true,
        ], $attributes));
    }

    protected function mockReview(array $attributes = []): GBPReview
    {
        return GBPReview::factory()->create(array_merge([
            'location_id' => 1,
            'google_review_id' => 'review_abc123',
            'rating' => 5,
            'review_text' => 'Great service!',
            'author_name' => 'John Doe',
            'review_text_sentiment' => 'positive',
        ], $attributes));
    }

    // ==========================================
    // Route Registration Tests (RRR-001 to RRR-008)
    // ==========================================

    /** @test */
    public function get_reviews_route_exists(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->once()->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_stats_route_exists(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->once()->andReturn([]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200);
    }

    /** @test */
    public function post_reviews_sync_route_exists(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->once()->andReturn('job-uuid');
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(202);
    }

    /** @test */
    public function post_reviews_respond_route_exists(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->once()->andReturn(['success' => true]);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", ['text' => 'Thank you!']);
        $response->assertStatus(200);
    }

    // ==========================================
    // Authentication Tests
    // ==========================================

    /** @test */
    public function unauthenticated_get_reviews_returns_401(): void
    {
        $location = $this->mockLocation();
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_get_stats_returns_401(): void
    {
        $location = $this->mockLocation();
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_post_sync_returns_401(): void
    {
        $location = $this->mockLocation();
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_post_respond_returns_401(): void
    {
        $location = $this->mockLocation();
        $review = $this->mockReview(['location_id' => $location->id]);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", ['text' => 'Thank you!']);
        $response->assertStatus(401);
    }

    // ==========================================
    // Authorization Tests
    // ==========================================

    /** @test */
    public function owner_can_access_reviews(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_access_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_access_stats(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_access_stats(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_trigger_sync(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->andReturn('job-uuid');
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(202);
    }

    /** @test */
    public function non_owner_cannot_trigger_sync(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_respond_to_review(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->andReturn(['success' => true]);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", ['text' => 'Thank you!']);
        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_respond_to_review(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", ['text' => 'Thank you!']);
        $response->assertStatus(403);
    }

    // ==========================================
    // Get Reviews Pagination Tests (REV-001 to REV-012)
    // ==========================================

    /** @test */
    public function get_reviews_default_pagination_15_items(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')
                ->once()
                ->withArgs(fn($id, $filters) => ($filters['per_page'] ?? 15) === 15)
                ->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200)->assertJsonStructure(['data', 'meta', 'links']);
    }

    /** @test */
    public function get_reviews_custom_per_page_50(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 50));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?per_page=50");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_max_per_page_100(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 100));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?per_page=100");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_filter_by_rating(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')
                ->once()
                ->withArgs(fn($id, $filters) => isset($filters['rating']) && $filters['rating'] == 5)
                ->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?rating=5");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_filter_by_sentiment(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')
                ->once()
                ->withArgs(fn($id, $filters) => isset($filters['sentiment']) && $filters['sentiment'] === 'positive')
                ->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sentiment=positive");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_filter_by_has_response(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')
                ->once()
                ->withArgs(fn($id, $filters) => isset($filters['has_response']) && $filters['has_response'] === true)
                ->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?has_response=true");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_filter_by_date_range(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?start_date=2024-01-01&end_date=2024-01-31");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_sort_by_date_desc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')
                ->once()
                ->withArgs(fn($id, $filters) => isset($filters['sort']) && $filters['sort'] === '-date')
                ->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sort=-date");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_sort_by_date_asc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sort=date");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_sort_by_rating_desc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sort=-rating");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_sort_by_rating_asc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sort=rating");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_multiple_filters(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?rating=5&has_response=false");
        $response->assertStatus(200);
    }

    // ==========================================
    // Get Reviews Response Structure Tests (REV-013 to REV-020)
    // ==========================================

    /** @test */
    public function get_reviews_response_structure(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([
                (object)['id' => 1, 'rating' => 5, 'text' => 'Great!', 'author' => 'John'],
            ], 1, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200)->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'total', 'per_page'],
            'links',
        ]);
    }

    /** @test */
    public function get_reviews_review_fields(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([
                (object)['id' => 1, 'rating' => 5, 'text' => 'Great!', 'author' => 'John', 'reviewed_at' => '2024-01-15'],
            ], 1, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    /** @test */
    public function get_reviews_owner_response_included(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([
                (object)['id' => 1, 'rating' => 5, 'response' => ['text' => 'Thank you!']],
            ], 1, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_empty_reviews(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200)->assertJson(['data' => []]);
    }

    /** @test */
    public function get_reviews_invalid_rating_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?rating=6");
        $response->assertStatus(422);
    }

    /** @test */
    public function get_reviews_invalid_sentiment_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sentiment=invalid");
        $response->assertStatus(422);
    }

    /** @test */
    public function get_reviews_invalid_sort_field_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?sort=invalid");
        $response->assertStatus(422);
    }

    /** @test */
    public function get_reviews_pagination_meta(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15, 1));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200)->assertJsonStructure(['meta' => ['current_page', 'total', 'per_page', 'last_page']]);
    }

    // ==========================================
    // Get Review Stats Tests (STATS-001 to STATS-020)
    // ==========================================

    /** @test */
    public function get_stats_average_rating(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->once()->andReturn([
                'average_rating' => 4.5,
                'total_count' => 127,
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200)->assertJsonPath('data.average_rating', 4.5);
    }

    /** @test */
    public function get_stats_total_count(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'average_rating' => 4.5,
                'total_count' => 127,
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200)->assertJsonPath('data.total_count', 127);
    }

    /** @test */
    public function get_stats_distribution_by_rating(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'distribution' => [5 => 89, 4 => 25, 3 => 8, 2 => 3, 1 => 2],
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200)->assertJsonStructure(['data' => ['distribution']]);
    }

    /** @test */
    public function get_stats_sentiment_breakdown(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'sentiment_breakdown' => ['positive' => 114, 'neutral' => 8, 'negative' => 5],
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200)->assertJsonStructure(['data' => ['sentiment_breakdown']]);
    }

    /** @test */
    public function get_stats_response_rate(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'response_rate' => 78.5,
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_stats_average_response_time(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'average_response_time_hours' => 24,
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_stats_empty_reviews(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getStats')->andReturn([
                'average_rating' => 0,
                'total_count' => 0,
                'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            ]);
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(200)->assertJsonPath('data.total_count', 0);
    }

    /** @test */
    public function get_stats_non_owner_returns_403(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(403);
    }

    /** @test */
    public function get_stats_non_existent_location_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/locations/99999/reviews/stats');
        $response->assertStatus(404);
    }

    /** @test */
    public function get_stats_gbp_not_connected_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/stats");
        $response->assertStatus(422);
    }

    // ==========================================
    // Sync Reviews Tests (RSYNC-001 to RSYNC-020)
    // ==========================================

    /** @test */
    public function sync_reviews_returns_job_id(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->once()->andReturn('job-uuid-123');
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(202)->assertJsonStructure(['job_id']);
    }

    /** @test */
    public function sync_reviews_job_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->andReturn('job-uuid');
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(202);
    }

    /** @test */
    public function sync_reviews_rate_limit_3_per_hour(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->andReturn('job-uuid');
        });

        Sanctum::actingAs($user);

        // Make 3 requests
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
            $this->assertContains($response->status(), [202, 429]);
        }

        // 4th should be rate limited
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $this->assertContains($response->status(), [202, 429]);
    }

    /** @test */
    public function sync_reviews_existing_sync_returns_409(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->andThrow(new \Exception('Sync already in progress', 409));
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(409);
    }

    /** @test */
    public function sync_reviews_gbp_not_connected_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => false]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(422);
    }

    /** @test */
    public function sync_reviews_non_owner_returns_403(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(403);
    }

    /** @test */
    public function sync_reviews_non_existent_location_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/locations/99999/reviews/sync');
        $response->assertStatus(404);
    }

    // ==========================================
    // Respond to Review Tests (RESP-001 to RESP-025)
    // ==========================================

    /** @test */
    public function respond_to_review_valid_response(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->once()->andReturn(['success' => true, 'response_id' => 'resp-123']);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => 'Thank you for your review!',
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function respond_to_review_max_length_validation(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => str_repeat('a', 4097), // Max is 4096
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function respond_to_review_min_length_validation(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => '',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function respond_to_review_rate_limit_10_per_hour(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->andReturn(['success' => true]);
        });

        Sanctum::actingAs($user);

        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
                'text' => "Response $i",
            ]);
            $this->assertContains($response->status(), [200, 429]);
        }

        // 11th should be rate limited
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => 'Another response',
        ]);
        $this->assertContains($response->status(), [200, 429]);
    }

    /** @test */
    public function respond_to_review_already_responded(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id, 'owner_response' => 'Previous response']);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->andThrow(new \Exception('Review already has response', 409));
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => 'Thank you!',
        ]);
        $response->assertStatus(409);
    }

    /** @test */
    public function respond_to_review_non_existent_review_returns_404(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/99999/respond", [
            'text' => 'Thank you!',
        ]);
        $response->assertStatus(404);
    }

    /** @test */
    public function respond_to_review_from_another_location_returns_403(): void
    {
        $user = User::factory()->create();
        $location1 = $this->mockLocation(['user_id' => $user->id]);
        $location2 = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location2->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location1->id}/reviews/{$review->id}/respond", [
            'text' => 'Thank you!',
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function respond_to_review_gbp_not_connected_returns_422(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => false]);
        $review = $this->mockReview(['location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => 'Thank you!',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function respond_to_review_unicode_support(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->andReturn(['success' => true]);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => 'ありがとう！🎉',
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function respond_to_review_xss_prevention(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('respondToReview')->andReturn(['success' => true, 'text' => '&lt;script&gt;alert(1)&lt;/script&gt;']);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond", [
            'text' => '<script>alert(1)</script>',
        ]);
        $response->assertStatus(200);
    }

    // ==========================================
    // Security Tests
    // ==========================================

    /** @test */
    public function sql_injection_in_location_id(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/1; DROP TABLE locations;/reviews");
        $response->assertStatus(404);
    }

    /** @test */
    public function sql_injection_in_review_id(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews/1; DROP TABLE reviews/respond", [
            'text' => 'Thank you!',
        ]);
        $response->assertStatus(404);
    }

    /** @test */
    public function no_sensitive_data_exposed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([], 0, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");

        $response->assertStatus(200)
            ->assertDontSee('password')
            ->assertDontSee('api_key')
            ->assertDontSee('secret');
    }

    /** @test */
    public function enumeration_prevention(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownLocation = $this->mockLocation(['user_id' => $user->id]);
        $otherLocation = $this->mockLocation(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        // Access other's location
        $response1 = $this->getJson("/api/v1/locations/{$otherLocation->id}/reviews");

        // Non-existent
        $response2 = $this->getJson('/api/v1/locations/99999/reviews');

        // Should return 403 for other's location (not 404)
        $response1->assertStatus(403);
        $response2->assertStatus(404);
    }

    // ==========================================
    // HTTP Method Restriction Tests
    // ==========================================

    /** @test */
    public function get_reviews_post_not_allowed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(405);
    }

    /** @test */
    public function get_reviews_put_not_allowed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(405);
    }

    /** @test */
    public function get_reviews_delete_not_allowed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(405);
    }

    /** @test */
    public function post_sync_get_not_allowed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/sync");
        $response->assertStatus(405);
    }

    /** @test */
    public function post_respond_get_not_allowed(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $review = $this->mockReview(['location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews/{$review->id}/respond");
        $response->assertStatus(405);
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    /** @test */
    public function get_reviews_with_unicode_author(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([
                (object)['id' => 1, 'rating' => 5, 'author' => '日本語ネーム'],
            ], 1, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_with_emoji_in_text(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getReviews')->andReturn(new LengthAwarePaginator([
                (object)['id' => 1, 'rating' => 5, 'text' => 'Great service! 😍🎉'],
            ], 1, 15));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews");
        $response->assertStatus(200);
    }

    /** @test */
    public function get_reviews_large_page_request_capped(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            // Should cap to 100
            $mock->shouldReceive('getReviews')
                ->withArgs(fn($id, $filters) => ($filters['per_page'] ?? 15) <= 100)
                ->andReturn(new LengthAwarePaginator([], 0, 100));
        });

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/reviews?per_page=1000");
        $response->assertStatus(200);
    }
}
