<?php

namespace App\Http\Controllers\Api\V1\GBP;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GBP\GBPReviewsRequest;
use App\Http\Requests\Api\V1\GBP\RespondToReviewRequest;
use App\Http\Resources\Api\V1\GBP\GBPReviewResource;
use App\Http\Resources\Api\V1\GBP\GBPReviewStatsResource;
use App\Models\Location;
use App\Models\GBPReview;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @Task TASK-35-14
 * @Description GBP Reviews API Controller
 */
class GBPReviewsController extends Controller
{
    /**
     * Get paginated reviews for a location.
     *
     * @param int $id Location ID
     * @param GBPReviewsRequest $request
     * @param GBPReviewsServiceInterface $service
     * @return AnonymousResourceCollection
     */
    public function index(int $id, GBPReviewsRequest $request, GBPReviewsServiceInterface $service): AnonymousResourceCollection
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('view', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            abort(422, 'Location is not connected to Google Business Profile.');
        }

        // Get filters from request
        $filters = $request->getFilters();

        // Get paginated reviews from service
        $reviews = $service->getReviews($location->id, $filters);

        return GBPReviewResource::collection($reviews)
            ->additional(['meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ]]);
    }

    /**
     * Get review statistics for a location.
     *
     * @param int $id Location ID
     * @param GBPReviewsServiceInterface $service
     * @return JsonResponse
     */
    public function stats(int $id, GBPReviewsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('view', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        // Get stats from service
        $stats = $service->getStats($location->id);

        return response()->json([
            'data' => new GBPReviewStatsResource($stats),
        ]);
    }

    /**
     * Trigger a background sync of reviews.
     *
     * @param int $id Location ID
     * @param GBPReviewsServiceInterface $service
     * @return JsonResponse
     */
    public function sync(int $id, GBPReviewsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('sync', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        try {
            $jobId = $service->syncReviews($location->id);

            return response()->json([
                'message' => 'Reviews sync initiated.',
                'job_id' => $jobId,
            ], 202);
        } catch (\Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json([
                    'message' => 'A sync is already in progress for this location.',
                ], 409);
            }

            return response()->json([
                'message' => 'Failed to initiate reviews sync.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Respond to a review.
     *
     * @param int $id Location ID
     * @param int $reviewId Review ID
     * @param RespondToReviewRequest $request
     * @param GBPReviewsServiceInterface $service
     * @return JsonResponse
     */
    public function respond(int $id, int $reviewId, RespondToReviewRequest $request, GBPReviewsServiceInterface $service): JsonResponse
    {
        $location = Location::findOrFail($id);

        // Check authorization
        $this->authorize('respondToReviews', $location);

        // Check GBP connection
        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Location is not connected to Google Business Profile.',
            ], 422);
        }

        // Verify review belongs to this location
        $review = GBPReview::where('id', $reviewId)
            ->where('location_id', $location->id)
            ->firstOrFail();

        // Check if already responded
        if ($review->has_response) {
            return response()->json([
                'message' => 'This review already has a response.',
            ], 409);
        }

        try {
            $response = $service->respondToReview($review->id, $request->input('text'));

            if (!$response['success']) {
                return response()->json([
                    'message' => $response['error'] ?? 'Failed to post response to review.',
                ], 500);
            }

            return response()->json([
                'message' => 'Response posted successfully.',
                'response_id' => $response['response_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json([
                    'message' => 'This review already has a response.',
                ], 409);
            }

            return response()->json([
                'message' => 'Failed to post response to review.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
