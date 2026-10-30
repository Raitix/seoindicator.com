<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Services\ContentDecay\Contracts\ContentDecayServiceInterface;

/**
 * Content Decay Service Implementation for SPEC-036.
 *
 * Main service for content decay operations:
 * - Summary statistics retrieval
 * - Settings management
 */
class ContentDecayService implements ContentDecayServiceInterface
{
    /**
     * Get summary statistics for a site's decay queue.
     *
     * @param int $siteId
     * @return array
     */
    public function getSummary(int $siteId): array
    {
        $project = ContentDecayProject::where('site_id', $siteId)->first();

        if (!$project) {
            return [
                'total_candidates' => 0,
                'critical_count' => 0,
                'high_count' => 0,
                'medium_count' => 0,
                'low_count' => 0,
                'estimated_upside_clicks' => 0,
                'recovered_pages' => 0,
                'last_scan_at' => null,
            ];
        }

        $candidates = ContentDecayCandidate::where('project_id', $project->id);

        return [
            'total_candidates' => $candidates->count(),
            'critical_count' => (clone $candidates)->where('severity', 'critical')->count(),
            'high_count' => (clone $candidates)->where('severity', 'high')->count(),
            'medium_count' => (clone $candidates)->where('severity', 'medium')->count(),
            'low_count' => (clone $candidates)->where('severity', 'low')->count(),
            'estimated_upside_clicks' => (clone $candidates)->sum('recovery_upside_estimate'),
            'recovered_pages' => (clone $candidates)->where('status', 'recovered')->count(),
            'last_scan_at' => $project->last_scanned_at?->toIso8601String(),
        ];
    }

    /**
     * Update detection settings for a site.
     *
     * @param int $siteId
     * @param array $settings
     * @return object Updated settings
     */
    public function updateSettings(int $siteId, array $settings): object
    {
        $project = ContentDecayProject::where('site_id', $siteId)->first();

        if (!$project) {
            // Create project if it doesn't exist
            $project = ContentDecayProject::create([
                'site_id' => $siteId,
                'user_id' => \App\Models\Site::find($siteId)?->user_id,
                'detection_enabled' => $settings['detection_enabled'] ?? true,
                'window_days' => $settings['window_days'] ?? 28,
                'min_clicks_threshold' => $settings['min_clicks_threshold'] ?? 10,
                'min_impressions_threshold' => $settings['min_impressions_threshold'] ?? 100,
                'min_decay_score' => $settings['min_decay_score'] ?? 20.00,
                'digest_enabled' => $settings['digest_enabled'] ?? true,
                'digest_frequency' => $settings['digest_frequency'] ?? 'weekly',
                'settings_json' => $settings['settings_json'] ?? null,
            ]);
        } else {
            // Update existing project
            $updateData = [];

            if (isset($settings['detection_enabled'])) {
                $updateData['detection_enabled'] = (bool) $settings['detection_enabled'];
            }

            if (isset($settings['window_days'])) {
                $updateData['window_days'] = (int) $settings['window_days'];
            }

            if (isset($settings['min_clicks_threshold'])) {
                $updateData['min_clicks_threshold'] = (int) $settings['min_clicks_threshold'];
            }

            if (isset($settings['min_impressions_threshold'])) {
                $updateData['min_impressions_threshold'] = (int) $settings['min_impressions_threshold'];
            }

            if (isset($settings['min_decay_score'])) {
                $updateData['min_decay_score'] = (float) $settings['min_decay_score'];
            }

            if (isset($settings['digest_enabled'])) {
                $updateData['digest_enabled'] = (bool) $settings['digest_enabled'];
            }

            if (isset($settings['digest_frequency'])) {
                $updateData['digest_frequency'] = $settings['digest_frequency'];
            }

            if (isset($settings['settings_json'])) {
                $updateData['settings_json'] = $settings['settings_json'];
            }

            $project->update($updateData);
        }

        return (object) $project->getSettings();
    }
}
