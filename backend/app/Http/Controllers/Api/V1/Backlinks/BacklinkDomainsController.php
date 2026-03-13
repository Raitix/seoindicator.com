<?php

namespace App\Http\Controllers\Api\V1\Backlinks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Repositories\ReferringDomainRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backlink Domains Controller.
 *
 * Provides API endpoint for referring domains with pagination and sorting.
 *
 * @see TASK-23-10
 * @see TEST-23-10
 * @see SPEC-023
 */
class BacklinkDomainsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private ReferringDomainRepository $domains
    ) {}

    /**
     * Get referring domains for a project with pagination and sorting.
     *
     * @param Request $request
     * @param mixed $projectId
     * @return JsonResponse
     */
    public function index(Request $request, mixed $projectId): JsonResponse
    {
        // Validate projectId is numeric
        if (!is_numeric($projectId)) {
            return response()->json([
                'message' => 'Invalid project ID.',
            ], 404);
        }

        $project = Project::findOrFail((int) $projectId);

        // Authorization via ProjectPolicy
        $this->authorize('view', $project);

        // Validate query parameters
        // Note: perPage allows values > 100 but they're capped to 100 in the controller
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:backlinks_count,domain_trust_score'],
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = min($validated['perPage'] ?? 20, 100);
        $sort = $validated['sort'] ?? 'backlinks_count';

        // Get paginated domains
        $domains = $this->getPaginatedDomains($project->id, $perPage, $sort, $page);

        // Transform the data
        $data = collect($domains->items())->map(fn($domain) => $this->transformDomain($domain))->toArray();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $domains->total(),
                'page' => $domains->currentPage(),
                'perPage' => $domains->perPage(),
                'lastPage' => $domains->lastPage(),
            ],
        ]);
    }

    /**
     * Get paginated referring domains for a project.
     *
     * @param int $projectId
     * @param int $perPage
     * @param string $sort
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function getPaginatedDomains(int $projectId, int $perPage, string $sort, int $page)
    {
        $query = \App\Models\ReferringDomain::where('project_id', $projectId);

        // Apply sorting
        match ($sort) {
            'domain_trust_score' => $query->orderBy('domain_trust_score', 'desc'),
            default => $query->orderBy('backlinks_count', 'desc'),
        };

        // Add secondary sort for consistent ordering
        $query->orderBy('domain', 'asc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Transform domain data for response.
     *
     * @param \App\Models\ReferringDomain $domain
     * @return array
     */
    private function transformDomain($domain): array
    {
        return [
            'domain' => $this->escapeHtml($domain->domain),
            'backlinks_count' => $domain->backlinks_count,
            'domain_trust_score' => $domain->domain_trust_score,
            'first_seen_at' => $domain->first_seen_at?->toIso8601String(),
            'last_seen_at' => $domain->last_seen_at?->toIso8601String(),
        ];
    }

    /**
     * Escape HTML special characters for XSS prevention.
     *
     * @param string|null $value
     * @return string|null
     */
    private function escapeHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
