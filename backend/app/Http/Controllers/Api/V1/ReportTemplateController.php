<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Repositories\ReportTemplateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Report Template Controller for SPEC-004-10.
 */
class ReportTemplateController extends Controller
{
    protected ReportTemplateRepository $templateRepository;

    /**
     * Valid section names.
     */
    protected array $validSections = [
        'cover',
        'summary',
        'keywords',
        'changes',
        'top-performers',
        'needs-attention',
        'trends',
        'competitors',
        'serp-features',
    ];

    public function __construct(ReportTemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    /**
     * List templates for a site (SPEC-004-10).
     */
    public function index(Request $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        $templates = $this->templateRepository->findBySite($siteId);

        return response()->json([
            'success' => true,
            'data' => $templates->map(fn (ReportTemplate $template) => $this->formatTemplate($template)),
        ]);
    }

    /**
     * Get a single template (SPEC-004-10).
     */
    public function show(int $templateId): JsonResponse
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Template not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTemplate($template),
        ]);
    }

    /**
     * Create a template (SPEC-004-10).
     */
    public function store(Request $request, int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sections' => 'required|array|min:1',
            'sections.*' => 'required|string|in:' . implode(',', $this->validSections),
            'isDefault' => 'nullable|boolean',
            'coverPageText' => 'nullable|string|max:2000',
            'executiveSummary' => 'nullable|string|max:5000',
            'branding' => 'nullable|array',
            'branding.logoUrl' => 'nullable|url|max:500',
            'branding.primaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.secondaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.fontFamily' => 'nullable|string|max:100',
        ]);

        // If setting as default, unset other defaults for this site
        if (!empty($validated['isDefault'])) {
            ReportTemplate::where('site_id', $siteId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template = $this->templateRepository->create([
            'site_id' => $siteId,
            'name' => $this->sanitizeString($validated['name']),
            'sections' => $validated['sections'],
            'is_default' => $validated['isDefault'] ?? false,
            'cover_page_text' => isset($validated['coverPageText']) ? $this->sanitizeString($validated['coverPageText']) : null,
            'executive_summary' => isset($validated['executiveSummary']) ? $this->sanitizeString($validated['executiveSummary']) : null,
            'branding' => $validated['branding'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatTemplate($template),
        ], 201);
    }

    /**
     * Update a template (SPEC-004-10).
     */
    public function update(Request $request, int $templateId): JsonResponse
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Template not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sections' => 'sometimes|array|min:1',
            'sections.*' => 'required|string|in:' . implode(',', $this->validSections),
            'isDefault' => 'sometimes|boolean',
            'coverPageText' => 'nullable|string|max:2000',
            'executiveSummary' => 'nullable|string|max:5000',
            'branding' => 'nullable|array',
            'branding.logoUrl' => 'nullable|url|max:500',
            'branding.primaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.secondaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.fontFamily' => 'nullable|string|max:100',
        ]);

        // If setting as default, unset other defaults for this site
        if (isset($validated['isDefault']) && $validated['isDefault']) {
            ReportTemplate::where('site_id', $template->site_id)
                ->where('id', '!=', $templateId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $this->sanitizeString($validated['name']);
        }
        if (isset($validated['sections'])) {
            $updateData['sections'] = $validated['sections'];
        }
        if (isset($validated['isDefault'])) {
            $updateData['is_default'] = $validated['isDefault'];
        }
        if (array_key_exists('coverPageText', $validated)) {
            $updateData['cover_page_text'] = $validated['coverPageText'] !== null ? $this->sanitizeString($validated['coverPageText']) : null;
        }
        if (array_key_exists('executiveSummary', $validated)) {
            $updateData['executive_summary'] = $validated['executiveSummary'] !== null ? $this->sanitizeString($validated['executiveSummary']) : null;
        }
        if (array_key_exists('branding', $validated)) {
            $updateData['branding'] = $validated['branding'];
        }

        if (!empty($updateData)) {
            $this->templateRepository->update($templateId, $updateData);
        }

        $template->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatTemplate($template),
        ]);
    }

    /**
     * Delete a template (SPEC-004-10).
     */
    public function destroy(int $templateId): Response
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            return response()->noContent(404);
        }

        $this->templateRepository->delete($templateId);

        return response()->noContent();
    }

    /**
     * Sanitize string by stripping HTML tags to prevent XSS.
     */
    protected function sanitizeString(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Format template for API response.
     */
    protected function formatTemplate(ReportTemplate $template): array
    {
        return [
            'id' => $template->id,
            'siteId' => $template->site_id,
            'name' => $template->name,
            'isDefault' => (bool) $template->is_default,
            'sections' => $template->sections,
            'coverPageText' => $template->cover_page_text,
            'executiveSummary' => $template->executive_summary,
            'branding' => $template->branding,
            'createdAt' => $template->created_at?->toISOString(),
            'updatedAt' => $template->updated_at?->toISOString(),
        ];
    }
}
