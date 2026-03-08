<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalSEOAlertService implements LocalSEOAlertServiceInterface
{
    protected LocalRankServiceInterface $rankService;
    protected GBPReviewsServiceInterface $reviewsService;
    protected GBPInsightsServiceInterface $insightsService;

    /**
     * Deduplication window in hours.
     */
    protected int $deduplicationWindow = 24;

    /**
     * Rating drop threshold for alerts.
     */
    protected float $ratingDropThreshold = 0.5;

    public function __construct(
        LocalRankServiceInterface $rankService,
        GBPReviewsServiceInterface $reviewsService,
        GBPInsightsServiceInterface $insightsService
    ) {
        $this->rankService = $rankService;
        $this->reviewsService = $reviewsService;
        $this->insightsService = $insightsService;
    }

    /**
     * Run all alert checks for a business location.
     */
    public function checkForAlerts(?BusinessLocation $location): void
    {
        if ($location === null) {
            return;
        }

        try {
            // Check GBP connection status first
            $this->checkGbpConnection($location);

            // If GBP is not connected, skip GBP-dependent checks
            if (!$location->gbp_connected && !$location->gbp_place_id) {
                return;
            }

            // Check ranking changes
            $this->checkRankingChanges($location);

            // Check reviews
            $this->checkReviews($location);

            // Check rating changes
            $this->checkRatingChanges($location);

            // Check competitor movements
            $this->checkCompetitorMovements($location);

        } catch (\Exception $e) {
            Log::error('Local SEO alert check failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new alert with deduplication.
     */
    public function createAlert(
        int $userId,
        int $locationId,
        string $type,
        string $severity,
        string $title,
        string $message,
        array $data = []
    ): LocalSEOAlert {
        // Validate severity
        if (!in_array($severity, LocalSEOAlert::SEVERITY_LEVELS)) {
            throw new \InvalidArgumentException("Invalid severity level: {$severity}");
        }

        // Truncate title if needed
        $title = mb_substr($title, 0, 255);
        $message = mb_substr($message, 0, 1000);

        // Check for duplicates within 24 hours
        $existingAlert = LocalSEOAlert::query()
            ->where('location_id', $locationId)
            ->where('alert_type', $type)
            ->where('created_at', '>=', now()->subHours($this->deduplicationWindow))
            ->first();

        if ($existingAlert) {
            return $existingAlert;
        }

        // Create new alert
        return LocalSEOAlert::create([
            'user_id' => $userId,
            'location_id' => $locationId,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => !empty($data) ? json_encode($data) : null,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Get unread alerts for a user.
     */
    public function getUnreadAlerts(int $userId, ?int $locationId = null): Collection
    {
        $query = LocalSEOAlert::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc');

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        return $query->get();
    }

    /**
     * Mark a specific alert as read.
     */
    public function markAsRead(int $alertId): bool
    {
        $alert = LocalSEOAlert::find($alertId);

        if (!$alert) {
            return false;
        }

        if ($alert->is_read) {
            return true;
        }

        return $alert->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark all alerts as read for a user.
     */
    public function markAllAsRead(int $userId, ?int $locationId = null): int
    {
        $query = LocalSEOAlert::query()
            ->where('user_id', $userId)
            ->where('is_read', false);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Check GBP connection status.
     */
    protected function checkGbpConnection(BusinessLocation $location): void
    {
        if (!$location->gbp_place_id) {
            return;
        }

        try {
            $isConnected = $this->insightsService->getConnectionStatus($location->id);

            if (!$isConnected) {
                $this->createAlert(
                    userId: $location->user_id,
                    locationId: $location->id,
                    type: 'gbp_disconnected',
                    severity: 'critical',
                    title: 'Google Business Profile disconnected',
                    message: 'Your Google Business Profile connection has been lost. Please reconnect to continue receiving insights.',
                    data: ['gbp_place_id' => $location->gbp_place_id]
                );
            }
        } catch (\Exception $e) {
            // If we can't check, assume disconnected
            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'gbp_disconnected',
                severity: 'critical',
                title: 'Google Business Profile disconnected',
                message: 'Unable to verify Google Business Profile connection. Please check your credentials.',
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Check for ranking changes.
     */
    protected function checkRankingChanges(BusinessLocation $location): void
    {
        $lastRanking = $this->rankService->getLastRanking($location->id);
        $previousRanking = $this->rankService->getPreviousRanking($location->id);

        if (!$lastRanking || !$previousRanking) {
            return;
        }

        $currentPosition = $lastRanking->local_pack_position ?? null;
        $previousPosition = $previousRanking->local_pack_position ?? null;

        if ($currentPosition === null || $previousPosition === null) {
            return;
        }

        $positionChange = $previousPosition - $currentPosition;
        $keyword = $lastRanking->keyword ?? 'unknown';

        if ($positionChange > 0) {
            // Ranking improved (lower position number = better)
            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'ranking_improved',
                severity: 'info',
                title: "Ranking improved {$positionChange} positions",
                message: "Your local pack ranking improved from position {$previousPosition} to {$currentPosition} for keyword \"{$keyword}\".",
                data: [
                    'old_position' => $previousPosition,
                    'new_position' => $currentPosition,
                    'keyword' => $keyword,
                ]
            );
        } elseif ($positionChange < 0) {
            // Ranking dropped (higher position number = worse)
            $dropAmount = abs($positionChange);
            $severity = $dropAmount >= 4 ? 'critical' : 'warning';

            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'ranking_dropped',
                severity: $severity,
                title: "Ranking dropped {$dropAmount} positions",
                message: "Your local pack ranking dropped from position {$previousPosition} to {$currentPosition} for keyword \"{$keyword}\".",
                data: [
                    'old_position' => $previousPosition,
                    'new_position' => $currentPosition,
                    'keyword' => $keyword,
                ]
            );
        }
    }

    /**
     * Check for new reviews.
     */
    protected function checkReviews(BusinessLocation $location): void
    {
        try {
            $newReviews = $this->reviewsService->getNewReviews($location->id);

            foreach ($newReviews as $review) {
                $rating = $review->rating ?? 0;
                $author = $review->author ?? 'Anonymous';

                if ($rating <= 2) {
                    // Negative review
                    $this->createAlert(
                        userId: $location->user_id,
                        locationId: $location->id,
                        type: 'negative_review',
                        severity: 'warning',
                        title: "Negative review received ({$rating} stars)",
                        message: "A {$rating}-star review was posted by {$author}. Consider responding to address their concerns.",
                        data: [
                            'rating' => $rating,
                            'author' => $author,
                            'review_id' => $review->id ?? null,
                        ]
                    );
                } else {
                    // Positive review
                    $this->createAlert(
                        userId: $location->user_id,
                        locationId: $location->id,
                        type: 'new_review',
                        severity: 'info',
                        title: "New {$rating}-star review received",
                        message: "A {$rating}-star review was posted by {$author}.",
                        data: [
                            'rating' => $rating,
                            'author' => $author,
                            'review_id' => $review->id ?? null,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check reviews for location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for rating changes.
     */
    protected function checkRatingChanges(BusinessLocation $location): void
    {
        try {
            $stats = $this->reviewsService->getReviewStats($location->id);

            $currentRating = $stats['average_rating'] ?? null;
            $previousRating = $stats['previous_rating'] ?? null;

            if ($currentRating === null || $previousRating === null) {
                return;
            }

            $ratingDrop = $previousRating - $currentRating;

            if ($ratingDrop >= $this->ratingDropThreshold) {
                $severity = $ratingDrop >= 1.0 ? 'critical' : 'warning';

                $this->createAlert(
                    userId: $location->user_id,
                    locationId: $location->id,
                    type: 'rating_dropped',
                    severity: $severity,
                    title: 'Average rating dropped',
                    message: "Your average rating dropped from " . number_format($previousRating, 1) . " to " . number_format($currentRating, 1) . " stars.",
                    data: [
                        'old_rating' => $previousRating,
                        'new_rating' => $currentRating,
                        'drop' => $ratingDrop,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check rating changes for location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for competitor movements.
     */
    protected function checkCompetitorMovements(BusinessLocation $location): void
    {
        try {
            $lastRanking = $this->rankService->getLastRanking($location->id);
            $previousRanking = $this->rankService->getPreviousRanking($location->id);

            if (!$lastRanking || !$previousRanking) {
                return;
            }

            $currentCompetitors = $lastRanking->competitors ?? [];
            $previousCompetitors = $previousRanking->competitors ?? [];

            $currentPosition = $lastRanking->local_pack_position ?? null;
            $previousPosition = $previousRanking->local_pack_position ?? null;

            if ($currentPosition === null || $previousPosition === null) {
                return;
            }

            // Check if any competitor overtook the business
            foreach ($currentCompetitors as $index => $competitor) {
                if (isset($competitor['is_own']) && $competitor['is_own']) {
                    continue; // Skip own business
                }

                $competitorName = $competitor['name'] ?? 'Unknown competitor';
                $competitorCurrentPosition = $competitor['position'] ?? null;

                // Find previous position of this competitor
                $competitorPreviousPosition = null;
                foreach ($previousCompetitors as $prevCompetitor) {
                    if (($prevCompetitor['name'] ?? '') === $competitorName) {
                        $competitorPreviousPosition = $prevCompetitor['position'] ?? null;
                        break;
                    }
                }

                // Check if competitor overtook
                if ($competitorCurrentPosition !== null && $competitorPreviousPosition !== null) {
                    if ($competitorCurrentPosition < $currentPosition && $competitorPreviousPosition >= $previousPosition) {
                        $positionGain = $competitorPreviousPosition - $competitorCurrentPosition;
                        $severity = $positionGain >= 2 ? 'critical' : 'warning';

                        $this->createAlert(
                            userId: $location->user_id,
                            locationId: $location->id,
                            type: 'competitor_overtake',
                            severity: $severity,
                            title: "{$competitorName} overtook your position",
                            message: "Competitor \"{$competitorName}\" moved from position {$competitorPreviousPosition} to {$competitorCurrentPosition}, overtaking your position.",
                            data: [
                                'competitor_name' => $competitorName,
                                'old_position' => $competitorPreviousPosition,
                                'new_position' => $competitorCurrentPosition,
                            ]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check competitor movements for location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
