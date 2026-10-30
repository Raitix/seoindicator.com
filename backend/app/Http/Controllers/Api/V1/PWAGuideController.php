<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * API Controller for PWA implementation guide endpoints.
 *
 * Provides educational content for users improving their PWA.
 * Public endpoints with caching for performance.
 *
 * @see TASK-24-9
 * @see SPEC-024
 * @see TEST-24-9
 */
class PWAGuideController extends Controller
{
    /**
     * Cache TTL for guide content (1 hour in seconds).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the full PWA implementation guide.
     *
     * GET /api/v1/pwa/guide
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'pwa-guide:full';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->buildGuideData();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Get a specific section of the PWA guide.
     *
     * GET /api/v1/pwa/guide/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function section(string $id): JsonResponse
    {
        // Validate section ID format (only alphanumeric and hyphens)
        if (!preg_match('/^[a-z0-9-]+$/', $id)) {
            return $this->sectionNotFound($id);
        }

        $cacheKey = "pwa-guide:section:{$id}";

        $section = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->loadSection($id);
        });

        if ($section === null) {
            return $this->sectionNotFound($id);
        }

        return response()->json([
            'success' => true,
            'data' => $section,
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Build the full guide data structure.
     *
     * @return array
     */
    protected function buildGuideData(): array
    {
        $sections = $this->loadAllSections();

        $tableOfContents = array_map(function ($section) {
            return [
                'id' => $section['id'],
                'title' => $section['title'],
            ];
        }, $sections);

        return [
            'title' => 'PWA Implementation Guide',
            'description' => 'A comprehensive guide to implementing Progressive Web App features, including manifest files, icons, iOS support, and service workers.',
            'table_of_contents' => $tableOfContents,
            'sections' => $sections,
        ];
    }

    /**
     * Load all guide sections.
     *
     * @return array
     */
    protected function loadAllSections(): array
    {
        $sectionIds = [
            'manifest-basics',
            'icon-requirements',
            'ios-support',
            'service-worker',
        ];

        $sections = [];

        foreach ($sectionIds as $id) {
            $section = $this->loadSection($id);
            if ($section !== null) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Load a specific section by ID.
     *
     * @param string $id
     * @return array|null
     */
    protected function loadSection(string $id): ?array
    {
        $path = resource_path("guides/pwa/{$id}.md");

        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $title = $this->extractTitle($content, $id);

        return [
            'id' => $id,
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * Extract title from markdown content or generate from ID.
     *
     * @param string $content
     * @param string $id
     * @return string
     */
    protected function extractTitle(string $content, string $id): string
    {
        // Try to extract the first H1 header
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: generate title from ID
        return ucwords(str_replace('-', ' ', $id));
    }

    /**
     * Return a 404 response for section not found.
     *
     * @param string $id
     * @return JsonResponse
     */
    protected function sectionNotFound(string $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'SECTION_NOT_FOUND',
                'message' => "The guide section '{$id}' was not found.",
            ],
        ], 404);
    }
}
