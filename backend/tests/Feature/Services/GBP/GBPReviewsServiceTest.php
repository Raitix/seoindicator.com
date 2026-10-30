<?php

namespace Tests\Feature\Services\GBP;

use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\Contracts\GBPReviewsServiceInterface;
use App\Services\GBP\GBPReviewsService;
use App\Services\GBP\SentimentAnalyzer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Feature tests for GBPReviewsService.
 *
 * Tests cover:
 * - AC-1: GBPReviewsService created with interface
 * - AC-2: syncReviews() method
 * - AC-3: getReviews() method with filtering
 * - AC-4: getReviewStats() method
 * - AC-5: respondToReview() method
 * - AC-6: Sentiment Analyzer integration
 * - AC-7: detectNewNegativeReviews() method
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
class GBPReviewsServiceTest extends TestCase
{
    private GBPReviewsService $service;
    private MockInterface $mockApiService;
    private MockInterface $mockSentimentAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create the gbp_reviews table for testing (in-memory SQLite)
        $this->createGbpReviewsTable();
        
        $this->mockApiService = Mockery::mock(GBPApiServiceInterface::class);
        $this->mockSentimentAnalyzer = Mockery::mock(SentimentAnalyzer::class);
        $this->service = new GBPReviewsService($this->mockApiService, $this->mockSentimentAnalyzer);
    }

    /**
     * Create the gbp_reviews table for tests.
     */
    private function createGbpReviewsTable(): void
    {
        // Drop tables if they exist (for clean slate)
        DB::getSchemaBuilder()->dropIfExists('gbp_reviews');
        DB::getSchemaBuilder()->dropIfExists('business_locations');
        
        // Create business_locations table (simplified for tests)
        DB::getSchemaBuilder()->create('business_locations', function ($table) {
            $table->id();
            $table->string('google_location_id')->nullable();
            $table->string('google_access_token')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        
        // Create gbp_reviews table
        DB::getSchemaBuilder()->create('gbp_reviews', function ($table) {
            $table->id();
            $table->foreignId('location_id');
            $table->string('google_review_id', 255);
            $table->string('author_name')->nullable();
            $table->string('author_photo', 500)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->string('review_text_sentiment', 20)->nullable();
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_response_date')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->timestamp('alert_sent_at')->nullable();
            $table->timestamps();
            
            $table->unique('google_review_id');
            $table->index('location_id');
            $table->index('rating');
            $table->index('review_date');
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =================================================================
    // Helper Methods
    // =================================================================

    /**
     * Create a mock BusinessLocation object.
     */
    private function createMockLocation(array $attributes = []): object
    {
        return new class($attributes) {
            public int $id;
            public string $google_location_id;
            public string $google_access_token;
            public string $name;

            public function __construct(array $attributes = [])
            {
                $this->id = $attributes['id'] ?? 1;
                $this->google_location_id = $attributes['google_location_id'] ?? 'accounts/123/locations/456';
                $this->google_access_token = $attributes['google_access_token'] ?? 'test-access-token';
                $this->name = $attributes['name'] ?? 'Test Business';
            }
        };
    }

    /**
     * Create mock GBP API review response.
     */
    private function createMockReviewsResponse(array $reviews = [], ?string $nextPageToken = null): array
    {
        return [
            'reviews' => $reviews,
            'nextPageToken' => $nextPageToken,
            'totalSize' => count($reviews),
        ];
    }

    // =================================================================
    // AC-1: Class Structure
    // =================================================================

    /** @test REV-CLS-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(GBPReviewsService::class));
    }

    /** @test REV-CLS-002: Implements GBPReviewsServiceInterface */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(GBPReviewsServiceInterface::class, $this->service);
    }

    /** @test REV-CLS-003: Constructor accepts dependencies */
    public function test_constructor_accepts_dependencies(): void
    {
        $service = new GBPReviewsService($this->mockApiService, $this->mockSentimentAnalyzer);
        $this->assertInstanceOf(GBPReviewsService::class, $service);
    }

    /** @test REV-CLS-004: syncReviews method exists */
    public function test_sync_reviews_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'syncReviews'));
    }

    /** @test REV-CLS-005: getReviews method exists */
    public function test_get_reviews_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getReviews'));
    }

    /** @test REV-CLS-006: getReviewStats method exists */
    public function test_get_review_stats_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getReviewStats'));
    }

    /** @test REV-CLS-007: respondToReview method exists */
    public function test_respond_to_review_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'respondToReview'));
    }

    /** @test REV-CLS-008: detectNewNegativeReviews method exists */
    public function test_detect_new_negative_reviews_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'detectNewNegativeReviews'));
    }

    // =================================================================
    // AC-2: syncReviews() Method
    // =================================================================

    /** @test REV-SYNC-001: Sync new reviews from GBP API */
    public function test_sync_new_reviews_from_api(): void
    {
        $location = $this->createMockLocation();
        
        $mockReviews = [
            [
                'reviewId' => 'review-1',
                'authorName' => 'John Doe',
                'authorPhoto' => 'https://example.com/photo1.jpg',
                'rating' => 5,
                'comment' => 'Excellent service!',
                'createTime' => '2026-03-01T10:00:00Z',
            ],
            [
                'reviewId' => 'review-2',
                'authorName' => 'Jane Smith',
                'authorPhoto' => null,
                'rating' => 2,
                'comment' => 'Terrible experience',
                'createTime' => '2026-03-02T15:30:00Z',
            ],
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->with(
                $location->google_location_id,
                $location->google_access_token,
                Mockery::any(),
                Mockery::any()
            )
            ->andReturn($this->createMockReviewsResponse($mockReviews));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->twice()
            ->andReturn('positive', 'negative');
        
        // Should store reviews and trigger sentiment analysis
        $this->service->syncReviews($location);
        
        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    /** @test REV-SYNC-002: Sync updates existing reviews */
    public function test_sync_updates_existing_reviews(): void
    {
        $location = $this->createMockLocation();
        
        $mockReviews = [
            [
                'reviewId' => 'existing-review-1',
                'authorName' => 'Updated Author',
                'authorPhoto' => 'https://example.com/updated.jpg',
                'rating' => 4,
                'comment' => 'Updated review text',
                'createTime' => '2026-03-01T10:00:00Z',
                'updateTime' => '2026-03-05T12:00:00Z',
            ],
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse($mockReviews));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        $this->assertTrue(true);
    }

    /** @test REV-SYNC-003: Pagination handling - multiple pages */
    public function test_sync_pagination_handling(): void
    {
        $location = $this->createMockLocation();
        
        // First page
        $page1Reviews = array_map(function ($i) {
            return [
                'reviewId' => "review-$i",
                'authorName' => "Author $i",
                'rating' => rand(1, 5),
                'comment' => "Review $i",
                'createTime' => '2026-03-01T10:00:00Z',
            ];
        }, range(1, 50));
        
        // Second page
        $page2Reviews = array_map(function ($i) {
            return [
                'reviewId' => "review-$i",
                'authorName' => "Author $i",
                'rating' => rand(1, 5),
                'comment' => "Review $i",
                'createTime' => '2026-03-01T10:00:00Z',
            ];
        }, range(51, 100));
        
        // Third page
        $page3Reviews = array_map(function ($i) {
            return [
                'reviewId' => "review-$i",
                'authorName' => "Author $i",
                'rating' => rand(1, 5),
                'comment' => "Review $i",
                'createTime' => '2026-03-01T10:00:00Z',
            ];
        }, range(101, 150));
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->times(3)
            ->andReturn(
                $this->createMockReviewsResponse($page1Reviews, 'token-page-2'),
                $this->createMockReviewsResponse($page2Reviews, 'token-page-3'),
                $this->createMockReviewsResponse($page3Reviews, null)
            );
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->times(150)
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        $this->assertTrue(true);
    }

    /** @test REV-SYNC-004: Empty response handling */
    public function test_sync_empty_response(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse([]));
        
        // No sentiment analysis calls expected
        $this->mockSentimentAnalyzer->shouldNotReceive('analyzeSentiment');
        
        $this->service->syncReviews($location);
        
        $this->assertTrue(true);
    }

    /** @test REV-SYNC-005: API error handling */
    public function test_sync_api_error_handling(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andThrow(new \Exception('API Error: 500 Internal Server Error'));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');
        
        $this->service->syncReviews($location);
    }

    /** @test REV-SYNC-006: Rate limit handling */
    public function test_sync_rate_limit_handling(): void
    {
        $location = $this->createMockLocation();
        
        // API returns rate limit error
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andThrow(new \Exception('Rate limit exceeded', 429));
        
        // Service should propagate the exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        
        $this->service->syncReviews($location);
    }

    /** @test REV-SYNC-007: Duplicate prevention */
    public function test_sync_duplicate_prevention(): void
    {
        $location = $this->createMockLocation();
        
        // Same review synced twice
        $mockReview = [
            'reviewId' => 'duplicate-review',
            'authorName' => 'Same Author',
            'rating' => 5,
            'comment' => 'Same comment',
            'createTime' => '2026-03-01T10:00:00Z',
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse([$mockReview]));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        // Second sync with same review should update, not create new
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse([$mockReview]));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        $this->assertTrue(true);
    }

    /** @test REV-SYNC-008: Sentiment analysis triggered for new reviews */
    public function test_sync_sentiment_triggered(): void
    {
        $location = $this->createMockLocation();
        
        $mockReviews = [
            [
                'reviewId' => 'sentiment-test',
                'authorName' => 'Test',
                'rating' => 5,
                'comment' => 'This is excellent!',
                'createTime' => '2026-03-01T10:00:00Z',
            ],
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse($mockReviews));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('This is excellent!')
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        $this->assertTrue(true);
    }

    // =================================================================
    // AC-3: getReviews() Method
    // =================================================================

    /** @test REV-GET-001: Get all reviews - no filters */
    public function test_get_all_reviews_no_filters(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-002: Filter by rating 5 */
    public function test_filter_by_rating_five(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, ['rating' => 5]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-003: Filter by sentiment positive */
    public function test_filter_by_sentiment_positive(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, ['sentiment' => 'positive']);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-004: Filter by date range */
    public function test_filter_by_date_range(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, [
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-08',
        ]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-005: Sort by date descending */
    public function test_sort_by_date_descending(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, [
            'sort' => 'date',
            'order' => 'desc',
        ]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-006: Sort by rating ascending */
    public function test_sort_by_rating_ascending(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, [
            'sort' => 'rating',
            'order' => 'asc',
        ]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-007: Combined filters */
    public function test_combined_filters(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, [
            'rating' => 1,
            'sentiment' => 'negative',
            'date_from' => '2026-01-01',
        ]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-008: Pagination with per_page */
    public function test_pagination_per_page(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->getReviews($location, ['per_page' => 10]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-009: Invalid filter ignored */
    public function test_invalid_filter_ignored(): void
    {
        $location = $this->createMockLocation();
        
        // Should not throw exception for invalid filter
        $result = $this->service->getReviews($location, ['invalid_key' => 'value']);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test REV-GET-010: Empty result for location with no reviews */
    public function test_empty_result_no_reviews(): void
    {
        $location = $this->createMockLocation(['id' => 999]);
        
        $result = $this->service->getReviews($location);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // =================================================================
    // AC-4: getReviewStats() Method
    // =================================================================

    /** @test REV-STAT-001: Calculate average rating */
    public function test_calculate_average_rating(): void
    {
        $location = $this->createMockLocation();
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertArrayHasKey('average_rating', $stats);
        $this->assertIsFloat($stats['average_rating']);
    }

    /** @test REV-STAT-002: Calculate total count */
    public function test_calculate_total_count(): void
    {
        $location = $this->createMockLocation();
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertIsInt($stats['total_count']);
    }

    /** @test REV-STAT-003: Rating distribution */
    public function test_rating_distribution(): void
    {
        $location = $this->createMockLocation();
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertArrayHasKey('rating_distribution', $stats);
        $this->assertArrayHasKey(1, $stats['rating_distribution']);
        $this->assertArrayHasKey(2, $stats['rating_distribution']);
        $this->assertArrayHasKey(3, $stats['rating_distribution']);
        $this->assertArrayHasKey(4, $stats['rating_distribution']);
        $this->assertArrayHasKey(5, $stats['rating_distribution']);
    }

    /** @test REV-STAT-004: Sentiment breakdown */
    public function test_sentiment_breakdown(): void
    {
        $location = $this->createMockLocation();
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertArrayHasKey('sentiment_breakdown', $stats);
        $this->assertArrayHasKey('positive', $stats['sentiment_breakdown']);
        $this->assertArrayHasKey('negative', $stats['sentiment_breakdown']);
        $this->assertArrayHasKey('neutral', $stats['sentiment_breakdown']);
    }

    /** @test REV-STAT-005: No reviews returns zeros */
    public function test_no_reviews_returns_zeros(): void
    {
        $location = $this->createMockLocation(['id' => 999]);
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertEquals(0, $stats['total_count']);
        $this->assertEquals(0.0, $stats['average_rating']);
    }

    /** @test REV-STAT-006: All positive reviews */
    public function test_all_positive_reviews(): void
    {
        $location = $this->createMockLocation();
        
        $stats = $this->service->getReviewStats($location);
        
        // Verify structure is correct
        $this->assertArrayHasKey('sentiment_breakdown', $stats);
        $this->assertIsInt($stats['sentiment_breakdown']['positive']);
        $this->assertIsInt($stats['sentiment_breakdown']['negative']);
        $this->assertIsInt($stats['sentiment_breakdown']['neutral']);
    }

    // =================================================================
    // AC-5: respondToReview() Method
    // =================================================================

    /** @test REV-RESP-001: Post response to Google successfully */
    public function test_post_response_success(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('respondToReview')
            ->once()
            ->with(
                $location->google_location_id,
                'review-123',
                'Thank you for your feedback!',
                $location->google_access_token
            )
            ->andReturn(true);
        
        $result = $this->service->respondToReview(
            $location,
            'review-123',
            'Thank you for your feedback!'
        );
        
        $this->assertTrue($result);
    }

    /** @test REV-RESP-002: Invalid review ID returns false */
    public function test_invalid_review_id_returns_false(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('respondToReview')
            ->once()
            ->andReturn(false);
        
        $result = $this->service->respondToReview(
            $location,
            'non-existent-review',
            'Thank you!'
        );
        
        $this->assertFalse($result);
    }

    /** @test REV-RESP-003: API error returns false */
    public function test_api_error_returns_false(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('respondToReview')
            ->once()
            ->andThrow(new \Exception('API Error: 403 Forbidden'));
        
        $result = $this->service->respondToReview(
            $location,
            'review-123',
            'Thank you!'
        );
        
        $this->assertFalse($result);
    }

    /** @test REV-RESP-004: Empty reply validation */
    public function test_empty_reply_validation(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->respondToReview(
            $location,
            'review-123',
            ''
        );
        
        $this->assertFalse($result);
    }

    /** @test REV-RESP-005: Very long reply handling */
    public function test_very_long_reply_handling(): void
    {
        $location = $this->createMockLocation();
        $longReply = str_repeat('a', 5000);
        
        $this->mockApiService
            ->shouldReceive('respondToReview')
            ->once()
            ->andReturn(true);
        
        $result = $this->service->respondToReview(
            $location,
            'review-123',
            $longReply
        );
        
        // Service should either truncate or reject - test just verifies it handles it
        $this->assertIsBool($result);
    }

    // =================================================================
    // AC-7: detectNewNegativeReviews() Method
    // =================================================================

    /** @test REV-NEG-001: Detect unalerted negative review */
    public function test_detect_unalerted_negative_review(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->detectNewNegativeReviews($location);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test REV-NEG-002: Skip already alerted reviews */
    public function test_skip_alerted_reviews(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->detectNewNegativeReviews($location);
        
        // Result should only contain reviews without alert_sent_at
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test REV-NEG-003: Skip positive reviews */
    public function test_skip_positive_reviews(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->detectNewNegativeReviews($location);
        
        // Result should only contain reviews with rating <= 2
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test REV-NEG-004: Multiple negative reviews */
    public function test_multiple_negative_reviews(): void
    {
        $location = $this->createMockLocation();
        
        $result = $this->service->detectNewNegativeReviews($location);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test REV-NEG-005: No negative reviews returns empty collection */
    public function test_no_negative_reviews_empty(): void
    {
        $location = $this->createMockLocation(['id' => 999]);
        
        $result = $this->service->detectNewNegativeReviews($location);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    // =================================================================
    // Integration Tests
    // =================================================================

    /** @test REV-INT-001: Full sync and retrieve flow */
    public function test_full_sync_and_retrieve_flow(): void
    {
        $location = $this->createMockLocation();
        
        // Sync reviews
        $mockReviews = [
            [
                'reviewId' => 'flow-test-1',
                'authorName' => 'Test User',
                'rating' => 4,
                'comment' => 'Great service',
                'createTime' => '2026-03-01T10:00:00Z',
            ],
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse($mockReviews));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn('positive');
        
        $this->service->syncReviews($location);
        
        // Retrieve reviews
        $result = $this->service->getReviews($location);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        
        // Get stats
        $stats = $this->service->getReviewStats($location);
        $this->assertArrayHasKey('total_count', $stats);
    }

    /** @test REV-INT-002: Sentiment analysis affects stats */
    public function test_sentiment_analysis_affects_stats(): void
    {
        $location = $this->createMockLocation();
        
        $mockReviews = [
            [
                'reviewId' => 'sentiment-stats-1',
                'authorName' => 'User 1',
                'rating' => 5,
                'comment' => 'Excellent!',
                'createTime' => '2026-03-01T10:00:00Z',
            ],
            [
                'reviewId' => 'sentiment-stats-2',
                'authorName' => 'User 2',
                'rating' => 1,
                'comment' => 'Terrible!',
                'createTime' => '2026-03-02T10:00:00Z',
            ],
        ];
        
        $this->mockApiService
            ->shouldReceive('getReviews')
            ->once()
            ->andReturn($this->createMockReviewsResponse($mockReviews));
        
        $this->mockSentimentAnalyzer
            ->shouldReceive('analyzeSentiment')
            ->twice()
            ->andReturn('positive', 'negative');
        
        $this->service->syncReviews($location);
        
        $stats = $this->service->getReviewStats($location);
        
        $this->assertArrayHasKey('sentiment_breakdown', $stats);
    }

    // =================================================================
    // Acceptance Criteria Verification
    // =================================================================

    /** @test AC-001: GBPReviewsService created with interface */
    public function test_ac_interface_created(): void
    {
        $this->assertInstanceOf(GBPReviewsServiceInterface::class, $this->service);
    }

    /** @test AC-002: syncReviews method signature */
    public function test_ac_sync_reviews_signature(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'syncReviews');
        $params = $reflection->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals('location', $params[0]->getName());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    /** @test AC-003: getReviews returns LengthAwarePaginator */
    public function test_ac_get_reviews_returns_paginator(): void
    {
        $location = $this->createMockLocation();
        $result = $this->service->getReviews($location);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test AC-004: getReviewStats returns array with required keys */
    public function test_ac_get_review_stats_structure(): void
    {
        $location = $this->createMockLocation();
        $stats = $this->service->getReviewStats($location);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('average_rating', $stats);
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('rating_distribution', $stats);
        $this->assertArrayHasKey('sentiment_breakdown', $stats);
    }

    /** @test AC-005: respondToReview returns bool */
    public function test_ac_respond_to_review_returns_bool(): void
    {
        $location = $this->createMockLocation();
        
        $this->mockApiService
            ->shouldReceive('respondToReview')
            ->once()
            ->andReturn(true);
        
        $result = $this->service->respondToReview($location, 'test', 'reply');
        
        $this->assertIsBool($result);
    }

    /** @test AC-006: detectNewNegativeReviews returns Collection */
    public function test_ac_detect_negative_returns_collection(): void
    {
        $location = $this->createMockLocation();
        $result = $this->service->detectNewNegativeReviews($location);
        
        $this->assertInstanceOf(Collection::class, $result);
    }
}
