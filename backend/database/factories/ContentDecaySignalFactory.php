<?php

namespace Database\Factories;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecaySignal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentDecaySignal>
 */
class ContentDecaySignalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ContentDecaySignal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousStart = fake()->dateTimeBetween('-60 days', '-30 days');
        $previousEnd = (clone $previousStart)->modify('+27 days');
        $currentStart = (clone $previousEnd)->modify('+1 day');
        $currentEnd = (clone $currentStart)->modify('+27 days');

        return [
            'candidate_id' => ContentDecayCandidate::factory(),
            'previous_start' => $previousStart->format('Y-m-d'),
            'previous_end' => $previousEnd->format('Y-m-d'),
            'current_start' => $currentStart->format('Y-m-d'),
            'current_end' => $currentEnd->format('Y-m-d'),
            'previous_clicks' => fake()->numberBetween(100, 5000),
            'current_clicks' => fake()->numberBetween(50, 3000),
            'previous_impressions' => fake()->numberBetween(1000, 50000),
            'current_impressions' => fake()->numberBetween(500, 40000),
            'previous_ctr' => fake()->randomFloat(4, 0.02, 0.15),
            'current_ctr' => fake()->randomFloat(4, 0.01, 0.10),
            'previous_avg_position' => fake()->randomFloat(2, 3, 20),
            'current_avg_position' => fake()->randomFloat(2, 5, 30),
            'previous_rank_visibility' => fake()->randomFloat(2, 50, 95),
            'current_rank_visibility' => fake()->randomFloat(2, 30, 80),
            'previous_ai_visibility' => fake()->optional(0.7)->randomFloat(2, 20, 60),
            'current_ai_visibility' => fake()->optional(0.7)->randomFloat(2, 10, 50),
            'unresolved_audit_issues' => fake()->numberBetween(0, 10),
            'content_age_days' => fake()->optional(0.8)->numberBetween(30, 730),
            'last_annotation_at' => fake()->optional(0.3)->dateTimeBetween('-30 days', 'now'),
            'evidence_json' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate a click decline signal.
     */
    public function clickDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);
    }

    /**
     * Indicate an impression decline signal.
     */
    public function impressionDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_impressions' => 10000,
            'current_impressions' => 5000,
        ]);
    }

    /**
     * Indicate a CTR decline signal.
     */
    public function ctrDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_ctr' => 0.10,
            'current_ctr' => 0.05,
        ]);
    }

    /**
     * Indicate a position decline signal.
     */
    public function positionDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_avg_position' => 5.00,
            'current_avg_position' => 15.00,
        ]);
    }

    /**
     * Indicate severe decay (large drops across metrics).
     */
    public function severeDecay(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_clicks' => 2000,
            'current_clicks' => 500,
            'previous_impressions' => 20000,
            'current_impressions' => 5000,
            'previous_ctr' => 0.10,
            'current_ctr' => 0.05,
            'previous_avg_position' => 3.00,
            'current_avg_position' => 25.00,
        ]);
    }

    /**
     * Indicate the signal has audit issues.
     */
    public function withAuditIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'unresolved_audit_issues' => fake()->numberBetween(3, 10),
        ]);
    }

    /**
     * Indicate stale content.
     */
    public function staleContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_age_days' => fake()->numberBetween(365, 730),
        ]);
    }
}
