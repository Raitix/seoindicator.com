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
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;

/**
 * Report Branding Controller for SPEC-004-14 and SPEC-004-15.
 */
class ReportBrandingController extends Controller
{
    protected ReportBrandingRepository $brandingRepository;

    /**
     * Recommended logo dimensions.
     */
    private const MIN_LOGO_WIDTH = 50;
    private const MIN_LOGO_HEIGHT = 20;
    private const MAX_LOGO_WIDTH = 400;
    private const MAX_LOGO_HEIGHT = 100;
    private const RECOMMENDED_MIN_WIDTH = 200;
    private const RECOMMENDED_MIN_HEIGHT = 50;

    public function __construct(ReportBrandingRepository $brandingRepository)
    {
        $this->brandingRepository = $brandingRepository;
    }

    /**
     * Get branding for a site (SPEC-004-14).
     */
    public function show(Request $request, int $siteId): JsonResponse
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

        // Authorization: user must own the site
        if ($request->user() && $request->user()->id !== $site->user_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $branding = $this->brandingRepository->findBySite($siteId);

        if (!$branding) {
            // Return defaults in camelCase format
            $defaults = ReportBranding::getDefaults();
            return response()->json([
                'success' => true,
                'data' => [
                    'siteId' => $siteId,
                    'primaryColor' => $defaults['primary_color'],
                    'secondaryColor' => $defaults['secondary_color'],
                    'fontFamily' => $defaults['font_family'],
                ],
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

        // Authorization: user must own the site
        if ($request->user() && $request->user()->id !== $site->user_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
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
            $updateData['font_family'] = $this->sanitizeString($validated['fontFamily']);
        }
        if (array_key_exists('companyName', $validated)) {
            $updateData['company_name'] = $validated['companyName'] ? $this->sanitizeString($validated['companyName']) : null;
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
     *
     * Validates:
     * - File type: PNG, JPG, SVG
     * - Max size: 2MB
     * - Dimensions (PNG/JPG): recommended 200x50 to 400x100, min 50x20, max 400x100
     * - SVG security: no embedded scripts
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

        // Authorization: user must own the site
        if ($request->user() && $request->user()->id !== $site->user_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid file.',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $file = $request->file('logo');
        $extension = strtolower($file->getClientOriginalExtension());

        // SVG security check
        if ($extension === 'svg') {
            $svgContent = file_get_contents($file->getPathname());
            $securityError = $this->validateSvgSecurity($svgContent);
            if ($securityError) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SECURITY_ERROR',
                        'message' => $securityError,
                    ],
                ], 422);
            }
        }

        // Dimension validation for raster images
        $dimensionWarning = null;
        if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
            $dimensionResult = $this->validateImageDimensions($file);
            if ($dimensionResult['error']) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'DIMENSION_ERROR',
                        'message' => $dimensionResult['error'],
                    ],
                ], 422);
            }
            if ($dimensionResult['warning']) {
                $dimensionWarning = $dimensionResult['warning'];
            }
        }

        // Delete existing logo if any
        $existing = $this->brandingRepository->findBySite($siteId);
        if ($existing && $existing->logo_file) {
            $this->deleteLogoFile($existing->logo_file);
        }

        // Store new logo in storage/app/logos/
        $timestamp = time();
        $filename = "site-{$siteId}-{$timestamp}.{$extension}";
        $path = $file->storeAs('logos', $filename, 'local');

        // Update branding record
        $branding = $this->brandingRepository->updateOrCreate($siteId, [
            'logo_file' => $path,
            'logo_url' => route('api.v1.branding.logo', ['siteId' => $siteId]),
        ]);

        $responseData = [
            'logoUrl' => $branding->logo_url,
            'logoFile' => $branding->logo_file,
        ];

        if ($dimensionWarning) {
            $responseData['warning'] = $dimensionWarning;
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    /**
     * Delete logo only for a site (SPEC-004-15).
     * Removes logo file and clears logo fields but keeps branding record.
     */
    public function deleteLogo(Request $request, int $siteId): JsonResponse
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

        // Authorization: user must own the site
        if ($request->user() && $request->user()->id !== $site->user_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $branding = $this->brandingRepository->findBySite($siteId);
        if (!$branding) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No branding found for this site.',
                ],
            ], 404);
        }

        if (!$branding->logo_file && !$branding->logo_url) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No logo to delete.',
                ],
            ], 404);
        }

        // Delete logo file
        if ($branding->logo_file) {
            $this->deleteLogoFile($branding->logo_file);
        }

        // Clear logo fields
        $branding->logo_file = null;
        $branding->logo_url = null;
        $branding->save();

        return response()->json([
            'success' => true,
            'data' => $this->formatBranding($branding),
        ]);
    }

    /**
     * Delete branding for a site.
     */
    public function destroy(Request $request, int $siteId): Response
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->noContent(404);
        }

        // Authorization: user must own the site
        if ($request->user() && $request->user()->id !== $site->user_id) {
            abort(403, 'You do not have access to this site.');
        }

        $branding = $this->brandingRepository->findBySite($siteId);
        if (!$branding) {
            return response()->noContent(404);
        }

        // Delete logo file if exists
        if ($branding->logo_file) {
            $this->deleteLogoFile($branding->logo_file);
        }

        $this->brandingRepository->deleteBySite($siteId);

        return response()->noContent();
    }

    /**
     * Serve logo file (SPEC-004-15).
     */
    public function serveLogo(int $siteId): Response
    {
        $branding = $this->brandingRepository->findBySite($siteId);

        if (!$branding || !$branding->logo_file) {
            return response()->noContent(404);
        }

        if (!Storage::disk('local')->exists($branding->logo_file)) {
            return response()->noContent(404);
        }

        $mimeType = $this->getMimeType($branding->logo_file);
        $content = Storage::disk('local')->get($branding->logo_file);

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000');
    }

    /**
     * Validate SVG for security issues.
     */
    protected function validateSvgSecurity(string $content): ?string
    {
        // Check for script tags
        if (preg_match('/<script[\s\S]*?>[\s\S]*?<\/script>/i', $content)) {
            return 'SVG contains embedded script tags, which are not allowed.';
        }

        // Check for event handlers (onclick, onload, onerror, etc.)
        if (preg_match('/\bon\w+\s*=/i', $content)) {
            return 'SVG contains event handlers, which are not allowed.';
        }

        // Check for javascript: URLs
        if (preg_match('/javascript\s*:/i', $content)) {
            return 'SVG contains javascript: URLs, which are not allowed.';
        }

        // Check for data: URLs with potential script content
        if (preg_match('/data\s*:\s*[^,]*;base64/i', $content)) {
            // Allow data URLs for images only
            if (!preg_match('/data\s*:\s*image\//i', $content)) {
                return 'SVG contains non-image data: URLs, which are not allowed.';
            }
        }

        // Check for external resources
        if (preg_match('/<(?:foreignObject|iframe|embed|object)[\s\S]*?>/i', $content)) {
            return 'SVG contains potentially dangerous elements.';
        }

        return null;
    }

    /**
     * Validate image dimensions.
     */
    protected function validateImageDimensions($file): array
    {
        $result = ['error' => null, 'warning' => null];

        try {
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                $result['error'] = 'Unable to read image dimensions.';
                return $result;
            }

            [$width, $height] = $imageInfo;

            // Hard limits
            if ($width < self::MIN_LOGO_WIDTH || $height < self::MIN_LOGO_HEIGHT) {
                $result['error'] = sprintf(
                    'Logo dimensions too small. Minimum: %dx%d pixels. Got: %dx%d.',
                    self::MIN_LOGO_WIDTH,
                    self::MIN_LOGO_HEIGHT,
                    $width,
                    $height
                );
                return $result;
            }

            if ($width > self::MAX_LOGO_WIDTH || $height > self::MAX_LOGO_HEIGHT) {
                $result['error'] = sprintf(
                    'Logo dimensions too large. Maximum: %dx%d pixels. Got: %dx%d.',
                    self::MAX_LOGO_WIDTH,
                    self::MAX_LOGO_HEIGHT,
                    $width,
                    $height
                );
                return $result;
            }

            // Recommended range warning
            if ($width < self::RECOMMENDED_MIN_WIDTH || $height < self::RECOMMENDED_MIN_HEIGHT) {
                $result['warning'] = sprintf(
                    'Logo dimensions below recommended size. Recommended: %dx%d to %dx%d pixels. Got: %dx%d.',
                    self::RECOMMENDED_MIN_WIDTH,
                    self::RECOMMENDED_MIN_HEIGHT,
                    self::MAX_LOGO_WIDTH,
                    self::MAX_LOGO_HEIGHT,
                    $width,
                    $height
                );
            }

        } catch (\Exception $e) {
            $result['error'] = 'Unable to validate image dimensions: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Delete a logo file from storage.
     */
    protected function deleteLogoFile(string $path): bool
    {
        // Check both possible storage locations for backwards compatibility
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->delete($path);
        }
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Get MIME type for file.
     */
    protected function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
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

    /**
     * Sanitize a string by removing HTML tags and their content.
     */
    protected function sanitizeString(string $value): string
    {
        // Remove script tags and their content
        $value = preg_replace('/<script[\s\S]*?>[\s\S]*?<\/script>/i', '', $value);
        // Remove style tags and their content
        $value = preg_replace('/<style[\s\S]*?>[\s\S]*?<\/style>/i', '', $value);
        // Strip remaining HTML tags
        $value = strip_tags($value);
        // Trim whitespace
        return trim($value);
    }
}
