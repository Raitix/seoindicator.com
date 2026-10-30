<?php

namespace App\Jobs;

use App\Models\Site;
use App\Repositories\PWAAnalysisRepository;
use App\Services\PWAScorerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job to perform PWA analysis for a site.
 *
 * Fetches manifest, validates it, checks icons, calculates score,
 * and stores the analysis results.
 *
 * @see TASK-24-8
 * @see SPEC-024
 */
class PWAAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The site ID to analyze.
     */
    protected int $siteId;

    /**
     * The job ID for tracking.
     */
    protected string $jobId;

    /**
     * Create a new job instance.
     *
     * @param int $siteId
     * @param string $jobId
     */
    public function __construct(int $siteId, string $jobId)
    {
        $this->siteId = $siteId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     *
     * @param PWAAnalysisRepository $repository
     * @param PWAScorerService $scorer
     * @return void
     */
    public function handle(PWAAnalysisRepository $repository, PWAScorerService $scorer): void
    {
        $site = Site::find($this->siteId);

        if (!$site) {
            Log::warning("PWAAnalysisJob: Site not found", ['site_id' => $this->siteId]);
            return;
        }

        Log::info("PWAAnalysisJob: Starting analysis", [
            'site_id' => $this->siteId,
            'job_id' => $this->jobId,
        ]);

        try {
            // Fetch manifest
            $manifestData = $this->fetchManifest($site);

            // Analyze the manifest
            $analysisData = $this->analyzeManifest($manifestData, $scorer);

            // Store the analysis
            $repository->create(array_merge($analysisData, [
                'site_id' => $this->siteId,
                'analyzed_at' => now(),
            ]));

            Log::info("PWAAnalysisJob: Analysis complete", [
                'site_id' => $this->siteId,
                'job_id' => $this->jobId,
                'score' => $analysisData['pwa_score'],
            ]);

        } catch (\Exception $e) {
            Log::error("PWAAnalysisJob: Analysis failed", [
                'site_id' => $this->siteId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            // Store a failed analysis record
            $repository->create([
                'site_id' => $this->siteId,
                'has_manifest' => false,
                'manifest_valid' => false,
                'pwa_score' => 0,
                'readiness_level' => 'none',
                'analyzed_at' => now(),
            ]);
        }
    }

    /**
     * Fetch the manifest from the site.
     *
     * @param Site $site
     * @return array
     */
    protected function fetchManifest(Site $site): array
    {
        $url = $site->url;

        if (!$url) {
            return ['found' => false];
        }

        try {
            // First, try to get the HTML and look for manifest link
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return ['found' => false];
            }

            $html = $response->body();

            // Look for manifest link
            if (preg_match('/<link[^>]+rel=["\']manifest["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
                $manifestUrl = $matches[1];

                // Resolve relative URL
                if (!str_starts_with($manifestUrl, 'http')) {
                    $parsedUrl = parse_url($url);
                    $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                    $manifestUrl = $baseUrl . ($manifestUrl[0] === '/' ? '' : '/') . $manifestUrl;
                }

                // Fetch manifest
                $manifestResponse = Http::timeout(10)->get($manifestUrl);

                if ($manifestResponse->successful()) {
                    $manifest = $manifestResponse->json();

                    return [
                        'found' => true,
                        'url' => $manifestUrl,
                        'data' => $manifest,
                        'valid' => is_array($manifest),
                    ];
                }
            }

            // Try default manifest.json location
            $parsedUrl = parse_url($url);
            $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
            $defaultManifestUrl = $baseUrl . '/manifest.json';

            $manifestResponse = Http::timeout(10)->get($defaultManifestUrl);

            if ($manifestResponse->successful()) {
                $manifest = $manifestResponse->json();

                return [
                    'found' => true,
                    'url' => $defaultManifestUrl,
                    'data' => $manifest,
                    'valid' => is_array($manifest),
                ];
            }

            return ['found' => false];

        } catch (\Exception $e) {
            Log::warning("PWAAnalysisJob: Failed to fetch manifest", [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return ['found' => false];
        }
    }

    /**
     * Analyze the manifest data.
     *
     * @param array $manifestData
     * @param PWAScorerService $scorer
     * @return array
     */
    protected function analyzeManifest(array $manifestData, PWAScorerService $scorer): array
    {
        if (!($manifestData['found'] ?? false)) {
            return [
                'has_manifest' => false,
                'manifest_url' => null,
                'manifest_valid' => false,
                'manifest_json' => null,
                'has_name' => false,
                'has_short_name' => false,
                'has_start_url' => false,
                'has_display' => false,
                'has_background_color' => false,
                'has_theme_color' => false,
                'has_description' => false,
                'icons_total' => 0,
                'icons_valid' => 0,
                'icons_192_present' => false,
                'icons_512_present' => false,
                'icons_180_present' => false,
                'icon_errors' => null,
                'pwa_score' => 0,
                'readiness_level' => 'none',
            ];
        }

        $manifest = $manifestData['data'] ?? [];
        $hasManifest = true;
        $manifestValid = $manifestData['valid'] ?? false;

        // Check required fields
        $hasName = isset($manifest['name']) && is_string($manifest['name']) && $manifest['name'] !== '';
        $hasShortName = isset($manifest['short_name']) && is_string($manifest['short_name']) && $manifest['short_name'] !== '';
        $hasStartUrl = isset($manifest['start_url']);
        $hasDisplay = isset($manifest['display']);
        $hasBackgroundColor = isset($manifest['background_color']);
        $hasThemeColor = isset($manifest['theme_color']);
        $hasDescription = isset($manifest['description']) && is_string($manifest['description']) && $manifest['description'] !== '';

        // Analyze icons
        $icons = $manifest['icons'] ?? [];
        $iconsTotal = count($icons);
        $iconsValid = 0;
        $icons192Present = false;
        $icons512Present = false;
        $icons180Present = false;
        $iconErrors = [];

        foreach ($icons as $icon) {
            $sizes = $icon['sizes'] ?? '';
            $src = $icon['src'] ?? null;

            if (!$src) {
                continue;
            }

            // Check for valid sizes
            if (preg_match('/^(\d+)x(\d+)$/i', $sizes, $matches)) {
                $width = (int) $matches[1];
                $height = (int) $matches[2];

                if ($width === $height) {
                    $iconsValid++;

                    if ($width === 192) {
                        $icons192Present = true;
                    }
                    if ($width === 512) {
                        $icons512Present = true;
                    }
                    if ($width === 180) {
                        $icons180Present = true;
                    }
                }
            }
        }

        // Create temporary analysis object for scoring
        $tempAnalysis = new \App\Models\PWAAnalysis();
        $tempAnalysis->has_manifest = $hasManifest;
        $tempAnalysis->manifest_valid = $manifestValid;
        $tempAnalysis->has_name = $hasName;
        $tempAnalysis->has_short_name = $hasShortName;
        $tempAnalysis->has_start_url = $hasStartUrl;
        $tempAnalysis->has_display = $hasDisplay;
        $tempAnalysis->has_background_color = $hasBackgroundColor;
        $tempAnalysis->has_theme_color = $hasThemeColor;
        $tempAnalysis->has_description = $hasDescription;
        $tempAnalysis->icons_total = $iconsTotal;
        $tempAnalysis->icons_valid = $iconsValid;
        $tempAnalysis->icons_192_present = $icons192Present;
        $tempAnalysis->icons_512_present = $icons512Present;
        $tempAnalysis->icons_180_present = $icons180Present;

        // Calculate score
        $score = $scorer->calculateScore($tempAnalysis);
        $readinessLevel = $scorer->determineReadinessLevel($score);

        return [
            'has_manifest' => $hasManifest,
            'manifest_url' => $manifestData['url'] ?? null,
            'manifest_valid' => $manifestValid,
            'manifest_json' => $manifest,
            'has_name' => $hasName,
            'has_short_name' => $hasShortName,
            'has_start_url' => $hasStartUrl,
            'has_display' => $hasDisplay,
            'has_background_color' => $hasBackgroundColor,
            'has_theme_color' => $hasThemeColor,
            'has_description' => $hasDescription,
            'icons_total' => $iconsTotal,
            'icons_valid' => $iconsValid,
            'icons_192_present' => $icons192Present,
            'icons_512_present' => $icons512Present,
            'icons_180_present' => $icons180Present,
            'icon_errors' => $iconErrors ?: null,
            'pwa_score' => $score,
            'readiness_level' => $readinessLevel,
        ];
    }
}
