<?php

namespace Database\Factories;

use App\Models\PWAAnalysis;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PWAAnalysis model.
 *
 * @see TASK-24-2
 * @see TEST-24-2
 */
class PWAAnalysisFactory extends Factory
{
    protected $model = PWAAnalysis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
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
            'analyzed_at' => now(),
        ];
    }

    /**
     * Site-specific analysis.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Analysis with a valid manifest.
     */
    public function withManifest(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_manifest' => true,
            'manifest_url' => '/manifest.json',
            'manifest_valid' => true,
            'manifest_json' => [
                'name' => $this->faker->company,
                'short_name' => $this->faker->word,
                'start_url' => '/',
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#2563eb',
            ],
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => true,
            'has_theme_color' => true,
            'has_description' => false,
        ]);
    }

    /**
     * Analysis without a manifest.
     */
    public function withoutManifest(): static
    {
        return $this->state(fn (array $attributes) => [
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
        ]);
    }

    /**
     * Full PWA with all features.
     */
    public function fullPWA(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_manifest' => true,
            'manifest_url' => '/manifest.json',
            'manifest_valid' => true,
            'manifest_json' => [
                'name' => $this->faker->company,
                'short_name' => $this->faker->word,
                'start_url' => '/',
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#2563eb',
                'description' => $this->faker->sentence,
                'icons' => [
                    ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                    ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                    ['src' => '/icons/icon-180.png', 'sizes' => '180x180', 'type' => 'image/png'],
                ],
            ],
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => true,
            'has_theme_color' => true,
            'has_description' => true,
            'icons_total' => 3,
            'icons_valid' => 3,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => true,
            'icon_errors' => null,
            'pwa_score' => 85, // Max score: 20 + 15 + 15 + 25 + 10
            'readiness_level' => 'full',
        ]);
    }

    /**
     * Basic PWA with minimal features.
     */
    public function basicPWA(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_manifest' => true,
            'manifest_url' => '/manifest.json',
            'manifest_valid' => true,
            'manifest_json' => [
                'name' => $this->faker->company,
                'short_name' => $this->faker->word,
                'start_url' => '/',
                'display' => 'standalone',
            ],
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => false,
            'has_theme_color' => false,
            'has_description' => false,
            'icons_total' => 1,
            'icons_valid' => 1,
            'icons_192_present' => true,
            'icons_512_present' => false,
            'icons_180_present' => false,
            'pwa_score' => 55, // 20 + 15 + 0 + 10 + 10
            'readiness_level' => 'basic',
        ]);
    }

    /**
     * No icons present.
     */
    public function noIcons(): static
    {
        return $this->state(fn (array $attributes) => [
            'icons_total' => 0,
            'icons_valid' => 0,
            'icons_192_present' => false,
            'icons_512_present' => false,
            'icons_180_present' => false,
            'icon_errors' => null,
        ]);
    }

    /**
     * With icon errors.
     */
    public function withIconErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'icons_total' => 3,
            'icons_valid' => 1,
            'icons_192_present' => true,
            'icons_512_present' => false,
            'icons_180_present' => false,
            'icon_errors' => [
                ['size' => '512x512', 'error' => 'Icon not found', 'severity' => 'error'],
                ['size' => '180x180', 'error' => 'Icon not found', 'severity' => 'warning'],
            ],
        ]);
    }

    /**
     * Specific PWA score.
     */
    public function withScore(int $score): static
    {
        $level = match (true) {
            $score >= 90 => 'full',
            $score >= 70 => 'enhanced',
            $score >= 40 => 'basic',
            default => 'none',
        };

        return $this->state(fn (array $attributes) => [
            'pwa_score' => $score,
            'readiness_level' => $level,
        ]);
    }

    /**
     * Specific analyzed_at date.
     */
    public function analyzedAt(string|\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'analyzed_at' => is_string($date) ? $date : $date->format('Y-m-d H:i:s'),
        ]);
    }
}
