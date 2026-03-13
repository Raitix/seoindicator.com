<?php

namespace App\Http\Controllers\Api\V1\Backlinks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\AnchorText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backlink Anchors Controller.
 *
 * Provides API endpoint for anchor text distribution with sorting.
 *
 * @see TASK-23-10
 * @see TEST-23-10
 * @see SPEC-023
 */
class BacklinkAnchorsController extends Controller
{
    /**
     * Get anchor text distribution for a project with sorting.
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
        $validated = $request->validate([
            'sort' => ['nullable', 'string', 'in:backlinks_count,percentage'],
        ]);

        $sort = $validated['sort'] ?? 'backlinks_count';

        // Get anchor texts with percentage calculation
        $anchors = $this->getAnchorsWithPercentage($project->id, $sort);

        return response()->json(
            ['data' => $anchors],
            200,
            [],
            JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * Get anchor texts with calculated percentages.
     *
     * @param int $projectId
     * @param string $sort
     * @return array
     */
    private function getAnchorsWithPercentage(int $projectId, string $sort): array
    {
        // Get all anchor texts for the project
        $anchors = AnchorText::where('project_id', $projectId)->get();

        // Calculate total backlinks for percentage calculation
        $totalBacklinks = $anchors->sum('backlinks_count');

        // Transform and sort
        $data = $anchors->map(function ($anchor) use ($totalBacklinks) {
            $percentage = $totalBacklinks > 0
                ? round(($anchor->backlinks_count / $totalBacklinks) * 100, 1)
                : 0.0;

            return [
                'anchor_text' => $this->escapeHtml($anchor->anchor_text),
                'backlinks_count' => $anchor->backlinks_count,
                'percentage' => (float) $percentage,
                'is_branded' => $anchor->is_branded,
                'is_exact_match' => $anchor->is_exact_match,
            ];
        });

        // Apply sorting
        $data = match ($sort) {
            'percentage' => $data->sortByDesc('percentage'),
            default => $data->sortByDesc('backlinks_count'),
        };

        // Reset keys and return as array
        return $data->values()->toArray();
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
