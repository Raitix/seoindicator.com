<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalSEOAlertService implements LocalSEOAlertServiceInterface
{
    protected LocalRankServiceInterface $rankService;
    protected GBPReviewsServiceInterface $reviewsService;
    protected GBPInsightsServiceInterface $insightsService;

    /**
     * Valid alert types.
     */
    public const ALERT_TYPES = [
        'ranking_improved',
        'ranking_dropped',
        'new_review',
        'negative_review',
        'rating_dropped',
        'competitor_overtake',
        'gbp_disconnected',
    ];

    /**
     * Valid severity levels.
     */
    public const SEVERITY_LEVELS = [
        'info',
        'warning',
        'critical',
    ];

    /**
     * Create a new service instance.
     */
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
        if (!$location) {
            return;
        }

        try {
            // Check GBP connection first
            $gbpConnected = $this->checkGbpConnection($location);

            if (!$gbpConnected) {
                $this->createGbpDisconnectedAlert($location);
                return; // Don't check other alerts if GBP is disconnected
            }

            // Run all alert checks
            $this->checkRankingChanges($location);
            $this->checkReviews($location);
            $this->checkRatingChanges($location);
        } catch (\Exception $e) {
            Log::error('Local SEO alert check failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new alert with deduplication.
     *
     * @throws \InvalidArgumentException If severity is invalid
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
        if (!in_array($severity, self::SEVERITY_LEVELS)) {
            throw new \InvalidArgumentException("Invalid severity level: {$severity}");
        }

        // Check for duplicate within 24 hours
        $existingAlert = LocalSEOAlert::where('location_id', $locationId)
            ->where('alert_type', $type)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existingAlert) {
            return $existingAlert;
        }

        // Truncate title if too long
        $title = mb_substr($title, 0, 255);

        // Truncate message if too long
        $message = mb_substr($message, 0, 1000);

        return LocalSEOAlert::create([
            'user_id' => $userId,
            'location_id' => $locationId,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Get unread alerts for a user.
     */
    public function getUnreadAlerts(int $userId, ?int $locationId = null): Collection
    {
        $query = LocalSEOAlert::where('user_id', $userId)
            ->where('is_read', false)
            ->with(['location'])
            ->orderBy('created_at', 'desc');

        if ($locationId) {
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
        $query = LocalSEOAlert::where('user_id', $userId)
            ->where('is_read', false);

        if ($locationId) {
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
    protected function checkGbpConnection(BusinessLocation $location): bool
    {
        if (!$location->gbp_place_id) {
            return false;
        }

        return $this->insightsService->getConnectionStatus($location->id);
    }

    /**
     * Create GBP disconnected alert.
     */
    protected function createGbpDisconnectedAlert(BusinessLocation $location): void
    {
        $this->createAlert(
            userId: $location->user_id,
            locationId: $location->id,
            type: 'gbp_disconnected',
            severity: 'critical',
            title: 'Google Business Profile disconnected',
            message: 'Your Google Business Profile connection has been lost. Please reconnect to continue monitoring.',
            data: ['gbp_place_id' => $location->gbp_place_id]
        );
    }

    /**
     * Check for ranking changes.
     */
    protected function checkRankingChanges(BusinessLocation $location): void
    {
        $lastRanking = $this->rankService->getLastRanking($location->id);
        $previousRanking = $this->rankService->getPreviousRanking($location->id);

        if (!$lastRanking || !$previousRanking) {
            return; // Need baseline data
        }

        $currentPosition = $lastRanking->local_pack_position ?? null;
        $previousPosition = $previousRanking->local_pack_position ?? null;

        if ($currentPosition === null || $previousPosition === null) {
            return;
        }

        // Calculate position change (lower is better in rankings)
        $positionChange = $previousPosition - $currentPosition;
        $keyword = $lastRanking->keyword ?? 'unknown';

        if ($positionChange > 0) {
            // Ranking improved
            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'ranking_improved',
                severity: 'info',
                title: "Ranking improved {$positionChange} position(s)",
                message: "Your local pack ranking improved from position {$previousPosition} to {$currentPosition} for keyword \"{$keyword}\".",
                data: [
                    'old_position' => $previousPosition,
                    'new_position' => $currentPosition,
                    'keyword' => $keyword,
                    'change' => $positionChange,
                ]
            );
        } elseif ($positionChange < 0) {
            // Ranking dropped
            $dropAmount = abs($positionChange);
            $severity = $dropAmount >= 4 ? 'critical' : 'warning';

            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'ranking_dropped',
                severity: $severity,
                title: "Ranking dropped {$dropAmount} position(s)",
                message: "Your local pack ranking dropped from position {$previousPosition} to {$currentPosition} for keyword \"{$keyword}\".",
                data: [
                    'old_position' => $previousPosition,
                    'new_position' => $currentPosition,
                    'keyword' => $keyword,
                    'change' => $positionChange,
                ]
            );
        }
    }

    /**
     * Check for new reviews.
     */
    protected function checkReviews(BusinessLocation $location): void
    {
        $newReviews = $this->reviewsService->getNewReviews($location->id);

        foreach ($newReviews as $review) {
            $rating = $review->rating ?? 0;

            // Check for negative review
            if ($rating <= 2) {
                $this->createAlert(
                    userId: $location->user_id,
                    locationId: $location->id,
                    type: 'negative_review',
                    severity: 'warning',
                    title: "Negative review received ({$rating} star(s))",
                    message: "A new {$rating}-star review was received from {$review->author}. Consider responding to address their concerns.",
                    data: [
                        'review_id' => $review->id ?? null,
                        'rating' => $rating,
                        'author' => $review->author ?? 'Unknown',
                    ]
                );
            } else {
                // Positive/neutral review
                $this->createAlert(
                    userId: $location->user_id,
                    locationId: $location->id,
                    type: 'new_review',
                    severity: 'info',
                    title: "New {$rating}-star review received",
                    message: "A new {$rating}-star review was received from {$review->author}.",
                    data: [
                        'review_id' => $review->id ?? null,
                        'rating' => $rating,
                        'author' => $review->author ?? 'Unknown',
                    ]
                );
            }
        }
    }

    /**
     * Check for rating changes.
     */
    protected function checkRatingChanges(BusinessLocation $location): void
    {
        $stats = $this->reviewsService->getReviewStats($location->id);

        $currentRating = $stats['average_rating'] ?? null;
        $previousRating = $stats['previous_rating'] ?? null;

        if ($currentRating === null || $previousRating === null) {
            return;
        }

        $ratingDrop = $previousRating - $currentRating;

        // Check if rating dropped by 0.5 or more
        if ($ratingDrop >= 0.5) {
            $severity = $ratingDrop >= 1.0 ? 'critical' : 'warning';

            $this->createAlert(
                userId: $location->user_id,
                locationId: $location->id,
                type: 'rating_dropped',
                severity: $severity,
                title: "Average rating dropped by " . number_format($ratingDrop, 1),
                message: "Your average rating dropped from " . number_format($previousRating, 1) . " to " . number_format($currentRating, 1) . " stars.",
                data: [
                    'old_rating' => $previousRating,
                    'new_rating' => $currentRating,
                    'drop' => $ratingDrop,
                ]
            );
        }
    }
}
