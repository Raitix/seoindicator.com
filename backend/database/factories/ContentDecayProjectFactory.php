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
            'min_clicks_threshold' => fake()->numberBetween(5, 20),
            'min_impressions_threshold' => fake()->numberBetween(50, 200),
            'min_decay_score' => fake()->randomFloat(2, 15.00, 30.00),
            'digest_enabled' => true,
            'digest_frequency' => fake()->randomElement(['weekly', 'monthly']),
            'settings_json' => fake()->optional(30)->passthrough([
                'custom_thresholds' => fake()->boolean(),
                'auto_planning' => fake()->boolean(),
            ]),
            'last_scanned_at' => fake()->optional(70)->dateTimeBetween('-7 days', 'now'),
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
     * Indicate a 7-day window.
     */
    public function window7Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 7,
        ]);
    }

    /**
     * Indicate a 14-day window.
     */
    public function window14Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 14,
        ]);
    }

    /**
     * Indicate a 56-day window.
     */
    public function window56Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_days' => 56,
        ]);
    }

    /**
     * Indicate that project has never been scanned.
     */
    public function neverScanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scanned_at' => null,
        ]);
    }

    /**
     * Indicate that project was scanned recently.
     */
    public function recentlyScanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scanned_at' => now()->subHours(fake()->numberBetween(1, 12)),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ContentDecayProject $project): void {
            // Ensure site belongs to the same user
            if (!$project->relationLoaded('site') && $project->site_id && !$project->site) {
                $project->setRelation('site', Site::query()->find($project->site_id));
            }

            if ($project->site && !$project->user_id) {
                $project->user_id = $project->site->user_id;
            }
        })->afterCreating(function (ContentDecayProject $project): void {
            // Sync site user_id if needed
            if ($project->site && $project->site->user_id !== $project->user_id) {
                $project->site->update(['user_id' => $project->user_id]);
            }
        });
    }
}
