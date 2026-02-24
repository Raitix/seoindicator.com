<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReportBranding;
use App\Models\Site;
use App\Repositories\ReportBrandingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Report Branding Controller for SPEC-004-14 and SPEC-004-15.
 */
class ReportBrandingController extends Controller
{
    protected ReportBrandingRepository $brandingRepository;

    public function __construct(ReportBrandingRepository $brandingRepository)
    {
        $this->brandingRepository = $brandingRepository;
    }

    /**
     * Get branding for a site (SPEC-004-14).
     */
    public function show(int $siteId): JsonResponse
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

        $branding = $this->brandingRepository->findBySite($siteId);

        if (!$branding) {
            // Return defaults
            return response()->json([
                'success' => true,
                'data' => array_merge(
                    ['siteId' => $siteId],
                    ReportBranding::getDefaults()
                ),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatBranding($branding),
        ]);
    }

    /**
     * Update branding for a site (SPEC-004-14).
     */
    public function update(Request $request, int $siteId): JsonResponse
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
            'primaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondaryColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'fontFamily' => 'nullable|string|max:100',
            'companyName' => 'nullable|string|max:255',
            'companyWebsite' => 'nullable|url|max:500',
            'logoUrl' => 'nullable|url|max:500',
        ]);

        $updateData = ['site_id' => $siteId];

        if (isset($validated['primaryColor'])) {
            $updateData['primary_color'] = $validated['primaryColor'];
        }
        if (isset($validated['secondaryColor'])) {
            $updateData['secondary_color'] = $validated['secondaryColor'];
        }
        if (isset($validated['fontFamily'])) {
            $updateData['font_family'] = strip_tags($validated['fontFamily']);
        }
        if (array_key_exists('companyName', $validated)) {
            $updateData['company_name'] = $validated['companyName'] ? strip_tags($validated['companyName']) : null;
        }
        if (array_key_exists('companyWebsite', $validated)) {
            $updateData['company_website'] = $validated['companyWebsite'];
        }
        if (isset($validated['logoUrl'])) {
            $updateData['logo_url'] = $validated['logoUrl'];
        }

        $branding = $this->brandingRepository->updateOrCreate($siteId, $updateData);

        return response()->json([
            'success' => true,
            'data' => $this->formatBranding($branding),
        ]);
    }

    /**
     * Upload logo for a site (SPEC-004-15).
     */
    public function uploadLogo(Request $request, int $siteId): JsonResponse
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

        $request->validate([
            'logo' => 'required|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $file = $request->file('logo');
        $extension = $file->getClientOriginalExtension();
        $filename = "logos/site-{$siteId}-logo.{$extension}";

        // Delete existing logo if any
        $existing = $this->brandingRepository->findBySite($siteId);
        if ($existing && $existing->logo_file && Storage::exists($existing->logo_file)) {
            Storage::delete($existing->logo_file);
        }

        // Store new logo
        $path = $file->storeAs('uploads', $filename, 'public');

        // Update branding record
        $branding = $this->brandingRepository->updateOrCreate($siteId, [
            'logo_file' => $path,
            'logo_url' => Storage::url($path),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'logoUrl' => $branding->logo_url,
                'logoFile' => $branding->logo_file,
            ],
        ]);
    }

    /**
     * Delete branding for a site.
     */
    public function destroy(int $siteId): Response
    {
        $branding = $this->brandingRepository->findBySite($siteId);
        if (!$branding) {
            return response()->noContent(404);
        }

        // Delete logo file if exists
        if ($branding->logo_file && Storage::exists($branding->logo_file)) {
            Storage::delete($branding->logo_file);
        }

        $this->brandingRepository->deleteBySite($siteId);

        return response()->noContent();
    }

    /**
     * Format branding for API response.
     */
    protected function formatBranding(ReportBranding $branding): array
    {
        return [
            'siteId' => $branding->site_id,
            'logoUrl' => $branding->logo_url,
            'primaryColor' => $branding->primary_color,
            'secondaryColor' => $branding->secondary_color,
            'fontFamily' => $branding->font_family,
            'companyName' => $branding->company_name,
            'companyWebsite' => $branding->company_website,
            'createdAt' => $branding->created_at?->toISOString(),
            'updatedAt' => $branding->updated_at?->toISOString(),
        ];
    }
}
