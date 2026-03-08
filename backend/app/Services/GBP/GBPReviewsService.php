<?php

namespace App\Services\GBP;

use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\Contracts\GBPReviewsServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Google Business Profile reviews.
 *
 * Handles syncing reviews from GBP API, filtering, statistics,
 * and sentiment analysis integration.
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
class GBPReviewsService implements GBPReviewsServiceInterface
{
    private GBPApiServiceInterface $apiService;
    private SentimentAnalyzer $sentimentAnalyzer;

    public function __construct(
        GBPApiServiceInterface $apiService,
        SentimentAnalyzer $sentimentAnalyzer
    ) {
        $this->apiService = $apiService;
        $this->sentimentAnalyzer = $sentimentAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function syncReviews($location): void
    {
        $locationName = $location->google_location_id;
        $accessToken = $location->google_access_token ?? null;

        if (empty($locationName)) {
            Log::warning('GBPReviewsService: Location missing google_location_id', [
                'location_id' => $location->id ?? null,
            ]);
            return;
        }

        try {
            $pageToken = null;
            $totalSynced = 0;

            do {
                $response = $this->apiService->getReviews(
                    $locationName,
                    $accessToken ?? '',
                    50,
                    $pageToken
                );

                $reviews = $response['reviews'] ?? [];
                $pageToken = $response['nextPageToken'] ?? null;

                foreach ($reviews as $reviewData) {
                    $this->syncSingleReview($location->id, $reviewData);
                    $totalSynced++;
                }
            } while ($pageToken !== null);

            Log::info('GBPReviewsService: Synced reviews', [
                'location_id' => $location->id ?? null,
                'total_synced' => $totalSynced,
            ]);
        } catch (\Exception $e) {
            Log::error('GBPReviewsService: Failed to sync reviews', [
                'location_id' => $location->id ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single review to the database.
     *
     * @param int $locationId
     * @param array $reviewData
     * @return void
     */
    private function syncSingleReview(int $locationId, array $reviewData): void
    {
        $googleReviewId = $reviewData['reviewId'] ?? null;

        if (empty($googleReviewId)) {
            return;
        }

        $reviewText = $reviewData['comment'] ?? '';
        $sentiment = null;

        // Analyze sentiment if review has text
        if (!empty($reviewText)) {
            $sentiment = $this->sentimentAnalyzer->analyzeSentiment($reviewText);
        }

        // Check if review already exists
        $existingReview = DB::table('gbp_reviews')
            ->where('google_review_id', $googleReviewId)
            ->first();

        $reviewRecord = [
            'location_id' => $locationId,
            'google_review_id' => $googleReviewId,
            'author_name' => $reviewData['authorName'] ?? null,
            'author_photo' => $reviewData['authorPhoto'] ?? null,
            'rating' => $reviewData['rating'] ?? 0,
            'review_text' => $reviewText,
            'review_text_sentiment' => $sentiment,
            'review_date' => isset($reviewData['createTime'])
                ? \Carbon\Carbon::parse($reviewData['createTime'])->toDateTimeString()
                : null,
            'updated_at' => now(),
        ];

        if ($existingReview) {
            // Update existing review
            DB::table('gbp_reviews')
                ->where('google_review_id', $googleReviewId)
                ->update($reviewRecord);
        } else {
            // Insert new review
            $reviewRecord['created_at'] = now();
            DB::table('gbp_reviews')->insert($reviewRecord);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getReviews($location, array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('gbp_reviews')
            ->where('location_id', $location->id);

        // Apply rating filter
        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        // Apply sentiment filter
        if (isset($filters['sentiment'])) {
            $query->where('review_text_sentiment', $filters['sentiment']);
        }

        // Apply date range filter
        if (isset($filters['date_from'])) {
            $query->where('review_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('review_date', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'review_date';
        $sortOrder = $filters['order'] ?? 'desc';

        if (in_array($sortField, ['review_date', 'rating', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('review_date', 'desc');
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviewStats($location): array
    {
        $reviews = DB::table('gbp_reviews')
            ->where('location_id', $location->id)
            ->get();

        $totalCount = $reviews->count();

        if ($totalCount === 0) {
            return [
                'average_rating' => 0.0,
                'total_count' => 0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'sentiment_breakdown' => ['positive' => 0, 'negative' => 0, 'neutral' => 0],
            ];
        }

        // Calculate average rating
        $averageRating = $reviews->avg('rating');

        // Calculate rating distribution
        $ratingDistribution = [
            1 => $reviews->where('rating', 1)->count(),
            2 => $reviews->where('rating', 2)->count(),
            3 => $reviews->where('rating', 3)->count(),
            4 => $reviews->where('rating', 4)->count(),
            5 => $reviews->where('rating', 5)->count(),
        ];

        // Calculate sentiment breakdown
        $sentimentBreakdown = [
            'positive' => $reviews->where('review_text_sentiment', 'positive')->count(),
            'negative' => $reviews->where('review_text_sentiment', 'negative')->count(),
            'neutral' => $reviews->where('review_text_sentiment', 'neutral')->count(),
        ];

        return [
            'average_rating' => round($averageRating, 2),
            'total_count' => $totalCount,
            'rating_distribution' => $ratingDistribution,
            'sentiment_breakdown' => $sentimentBreakdown,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function respondToReview($location, string $reviewId, string $reply): bool
    {
        // Validate reply is not empty
        if (empty(trim($reply))) {
            return false;
        }

        $accessToken = $location->google_access_token ?? null;

        try {
            $success = $this->apiService->respondToReview(
                $location->google_location_id,
                $reviewId,
                $reply,
                $accessToken ?? ''
            );

            if ($success) {
                // Store response locally
                DB::table('gbp_reviews')
                    ->where('google_review_id', $reviewId)
                    ->update([
                        'owner_response' => $reply,
                        'owner_response_date' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('GBPReviewsService: Failed to respond to review', [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detectNewNegativeReviews($location): Collection
    {
        $reviews = DB::table('gbp_reviews')
            ->where('location_id', $location->id)
            ->where('rating', '<=', 2)
            ->whereNull('alert_sent_at')
            ->get();

        return collect($reviews);
    }
}
