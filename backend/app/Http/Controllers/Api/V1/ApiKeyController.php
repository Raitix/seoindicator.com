<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApiKeyRequest;
use App\Models\ApiKey;
use App\Repositories\ApiKeyRepository;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Key Management Controller for TASK-6-4.
 *
 * Endpoints:
 * - POST /api/v1/api-keys - Create new API key
 * - GET /api/v1/api-keys - List user's API keys
 * - GET /api/v1/api-keys/{id} - Get single API key
 * - DELETE /api/v1/api-keys/{id} - Revoke API key
 */
class ApiKeyController extends Controller
{
    protected ApiKeyService $apiKeyService;
    protected ApiKeyRepository $apiKeyRepository;

    public function __construct(
        ApiKeyService $apiKeyService,
        ApiKeyRepository $apiKeyRepository
    ) {
        $this->apiKeyService = $apiKeyService;
        $this->apiKeyRepository = $apiKeyRepository;
    }

    /**
     * Create a new API key.
     *
     * The full key is returned ONLY on creation.
     * Store it securely - it cannot be retrieved again.
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $this->authorize('create', ApiKey::class);

        // Generate secure key
        $keyData = $this->apiKeyService->generate();

        // Create the API key record
        $apiKey = $this->apiKeyRepository->create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'key_hash' => $keyData['hash'],
            'key_prefix' => $keyData['prefix'],
            'permissions' => $request->input('permissions'),
            'rate_limit' => $request->input('rate_limit', ApiKey::DEFAULT_RATE_LIMIT),
            'expires_at' => $request->input('expires_at'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'], // Full key shown ONLY on creation
                'prefix' => $apiKey->key_prefix,
                'permissions' => $apiKey->permissions,
                'rate_limit' => $apiKey->rate_limit,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
                'created_at' => $apiKey->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * List all API keys for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApiKey::class);

        $perPage = min((int) $request->query('per_page', 20), 100);
        $page = max(1, (int) $request->query('page', 1));
        $status = $request->query('status');

        $filters = [];
        if ($status && in_array($status, ['active', 'revoked', 'expired'])) {
            $filters['status'] = $status;
        }

        $paginator = $this->apiKeyRepository->getForUser(
            $request->user(),
            $filters,
            $perPage
        );

        // Transform keys to hide sensitive data
        $data = collect($paginator->items())->map(function (ApiKey $apiKey) {
            return $this->formatKeyForList($apiKey);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * Get a single API key.
     *
     * The full key is NOT returned - only the prefix.
     */
    public function show(int $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->findById($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'API key not found.',
                ],
            ], 404);
        }

        $this->authorize('view', $apiKey);

        return response()->json([
            'success' => true,
            'data' => $this->formatKeyForShow($apiKey),
        ]);
    }

    /**
     * Revoke an API key.
     *
     * This is a soft delete - the key is marked as revoked but not removed.
     * This is idempotent - revoking an already-revoked key is a no-op.
     */
    public function destroy(int $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->findById($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'API key not found.',
                ],
            ], 404);
        }

        $this->authorize('delete', $apiKey);

        $this->apiKeyRepository->revoke($apiKey);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Format API key for list response.
     *
     * Hides sensitive data (key_hash, full key).
     */
    protected function formatKeyForList(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'prefix' => $apiKey->key_prefix,
            'permissions' => $apiKey->permissions,
            'rate_limit' => $apiKey->rate_limit,
            'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
            'expires_at' => $apiKey->expires_at?->toIso8601String(),
            'revoked_at' => $apiKey->revoked_at?->toIso8601String(),
            'created_at' => $apiKey->created_at->toIso8601String(),
        ];
    }

    /**
     * Format API key for show response.
     *
     * Similar to list but includes more details.
     * Still hides sensitive data (key_hash, full key).
     */
    protected function formatKeyForShow(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'prefix' => $apiKey->key_prefix,
            'permissions' => $apiKey->permissions,
            'rate_limit' => $apiKey->rate_limit,
            'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
            'expires_at' => $apiKey->expires_at?->toIso8601String(),
            'revoked_at' => $apiKey->revoked_at?->toIso8601String(),
            'created_at' => $apiKey->created_at->toIso8601String(),
            'updated_at' => $apiKey->updated_at->toIso8601String(),
        ];
    }
}
