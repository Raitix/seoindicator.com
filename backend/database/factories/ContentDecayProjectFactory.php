<?php

namespace Database\Factories;

use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentDecayProject>
 */
class ContentDecayProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ContentDecayProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'site_id' => Site::factory()->state(fn () => ['user_id' => $user]),
            'detection_enabled' => true,
            'window_days' => 28,
            'min_clicks_threshold' => 10,
            'min_impressions_threshold' => 100,
            'min_decay_score' => 20.00,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'settings_json' => null,
            'last_scanned_at' => null,
        ];
    }

    /**
     * Indicate that detection is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'detection_enabled' => false,
        ]);
    }

    /**
     * Indicate that digest is disabled.
     */
    public function noDigest(): static
    {
        return $this->state(fn (array $attributes) => [
            'digest_enabled' => false,
        ]);
    }

    /**
     * Indicate weekly digest frequency.
     */
    public function weeklyDigest(): static
    {
        return $this->state(fn (array $attributes) => [
            'digest_frequency' => 'weekly',
        ]);
    }

    /**
     * Indicate monthly digest frequency.
     */
    public function monthlyDigest(): static
    {
        return $this->state(fn (array $attributes) => [
            'digest_frequency' => 'monthly',
        ]);
    }

    /**
     * Indicate 7-day window.
     */
    public function window7Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 7,
        ]);
    }

    /**
     * Indicate 14-day window.
     */
    public function window14Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 14,
        ]);
    }

    /**
     * Indicate 56-day window.
     */
    public function window56Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 56,
        ]);
    }

    /**
     * Indicate never scanned.
     */
    public function neverScanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scanned_at' => null,
        ]);
    }

    /**
     * Indicate recently scanned.
     */
    public function recentlyScanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scanned_at' => now(),
        ]);
    }
}
