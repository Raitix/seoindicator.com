<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GSCConnection;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * GSC Connection Controller.
 *
 * Manages Google Search Console OAuth connections via API endpoints.
 *
 * @see TASK-12-7
 */
class GSCConnectionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Initiate OAuth flow with authorization code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'authorization_code' => 'required|string',
            'redirect_uri' => 'required|url',
            'property_url' => 'required|url',
        ]);

        try {
            $connection = $this->oauthService->handleCallback(
                code: $request->authorization_code,
                redirectUri: $request->redirect_uri,
                user: $request->user(),
                propertyUrl: $request->property_url
            );

            return response()->json([
                'message' => 'GSC property connected successfully',
                'connection' => $this->formatConnection($connection),
            ], Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            Log::error('GSC connection failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'authorization_code' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove connection and revoke tokens.
     *
     * @param int $propertyId
     * @param Request $request
     * @return JsonResponse
     */
    public function disconnect(int $propertyId, Request $request): JsonResponse
    {
        $connection = GSCConnection::find($propertyId);

        if (!$connection) {
            return response()->json([
                'message' => 'Connection not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Authorization: users can only disconnect their own connections
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to disconnect this property',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Revoke OAuth access with Google
            $this->oauthService->revokeAccess($connection);

            // Delete the connection from database
            $connection->delete();

            return response()->json([
                'message' => 'GSC property disconnected successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('GSC disconnection failed', [
                'connection_id' => $propertyId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            // Still delete the connection even if revocation fails
            $connection->delete();

            return response()->json([
                'message' => 'GSC property disconnected (token revocation may have failed)',
            ]);
        }
    }

    /**
     * List all connected GSC properties for user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function properties(Request $request): JsonResponse
    {
        $connections = GSCConnection::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'properties' => $connections->map(fn ($connection) => $this->formatConnection($connection)),
        ]);
    }

    /**
     * Format connection for API response.
     *
     * Ensures sensitive tokens are never exposed.
     *
     * @param GSCConnection $connection
     * @return array
     */
    protected function formatConnection(GSCConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'site_url' => $connection->site_url,
            'property_url' => $connection->property_url,
            'status' => $connection->status,
            'last_synced_at' => $connection->last_synced_at?->toISOString(),
            'token_expires_at' => $connection->token_expires_at->toISOString(),
            'created_at' => $connection->created_at->toISOString(),
            // Note: access_token and refresh_token are intentionally excluded
        ];
    }
}
