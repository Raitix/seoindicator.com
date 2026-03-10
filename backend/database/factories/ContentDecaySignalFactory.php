<?php

namespace Database\Factories;

use App\Models\ContentDecaySignal;
use App\Models\ContentDecayCandidate;
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
        $previousStart = fake()->dateTimeBetween('-60 days', '-32 days');
        $previousEnd = (clone $previousStart)->modify('+27 days');
        $currentStart = (clone $previousEnd)->modify('+1 day');
        $currentEnd = (clone $currentStart)->modify('+27 days');

        $previousClicks = fake()->numberBetween(500, 5000);
        $currentClicks = fake()->numberBetween(100, $previousClicks);
        $previousImpressions = fake()->numberBetween(5000, 50000);
        $currentImpressions = fake()->numberBetween(2000, $previousImpressions);

        return [
            'candidate_id' => ContentDecayCandidate::factory(),
            'previous_start' => $previousStart->format('Y-m-d'),
            'previous_end' => $previousEnd->format('Y-m-d'),
            'current_start' => $currentStart->format('Y-m-d'),
            'current_end' => $currentEnd->format('Y-m-d'),
            'previous_clicks' => $previousClicks,
            'current_clicks' => $currentClicks,
            'previous_impressions' => $previousImpressions,
            'current_impressions' => $currentImpressions,
            'previous_ctr' => fake()->randomFloat(4, 0.02, 0.15),
            'current_ctr' => fake()->randomFloat(4, 0.01, 0.10),
            'previous_avg_position' => fake()->randomFloat(2, 1, 20),
            'current_avg_position' => fake()->randomFloat(2, 5, 30),
            'previous_rank_visibility' => fake()->optional(70)->randomFloat(2, 40, 100),
            'current_rank_visibility' => fake()->optional(70)->randomFloat(2, 20, 80),
            'previous_ai_visibility' => fake()->optional(50)->randomFloat(2, 20, 80),
            'current_ai_visibility' => fake()->optional(50)->randomFloat(2, 10, 60),
            'unresolved_audit_issues' => fake()->numberBetween(0, 10),
            'content_age_days' => fake()->optional(80)->numberBetween(30, 1000),
            'last_annotation_at' => fake()->optional(40)->dateTimeBetween('-90 days', 'now'),
            'evidence_json' => fake()->optional(60)->passthrough([
                'click_delta' => $currentClicks - $previousClicks,
                'impression_delta' => $currentImpressions - $previousImpressions,
                'algorithm_update' => fake()->optional(30)->randomElement(['core_update', 'helpful_content', 'link_spam']),
            ]),
        ];
    }

    /**
     * Indicate significant click decline.
     */
    public function clickDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_clicks' => fake()->numberBetween(1000, 5000),
            'current_clicks' => fake()->numberBetween(100, 500),
        ]);
    }

    /**
     * Indicate significant impression decline.
     */
    public function impressionDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_impressions' => fake()->numberBetween(10000, 50000),
            'current_impressions' => fake()->numberBetween(2000, 8000),
        ]);
    }

    /**
     * Indicate CTR decline.
     */
    public function ctrDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_ctr' => fake()->randomFloat(4, 0.08, 0.15),
            'current_ctr' => fake()->randomFloat(4, 0.02, 0.05),
        ]);
    }

    /**
     * Indicate position decline.
     */
    public function positionDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_avg_position' => fake()->randomFloat(2, 1, 10),
            'current_avg_position' => fake()->randomFloat(2, 15, 50),
        ]);
    }

    /**
     * Indicate rank visibility decline.
     */
    public function rankVisibilityDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_rank_visibility' => fake()->randomFloat(2, 70, 100),
            'current_rank_visibility' => fake()->randomFloat(2, 20, 50),
        ]);
    }

    /**
     * Indicate AI visibility decline.
     */
    public function aiVisibilityDecline(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_ai_visibility' => fake()->randomFloat(2, 50, 90),
            'current_ai_visibility' => fake()->randomFloat(2, 10, 40),
        ]);
    }

    /**
     * Indicate audit issues present.
     */
    public function withAuditIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'unresolved_audit_issues' => fake()->numberBetween(3, 15),
        ]);
    }

    /**
     * Indicate stale content.
     */
    public function staleContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_age_days' => fake()->numberBetween(365, 1000),
        ]);
    }

    /**
     * Indicate fresh content.
     */
    public function freshContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_age_days' => fake()->numberBetween(7, 90),
        ]);
    }

    /**
     * Indicate no audit issues.
     */
    public function noAuditIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'unresolved_audit_issues' => 0,
        ]);
    }

    /**
     * Indicate has recent annotation.
     */
    public function withRecentAnnotation(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_annotation_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ]);
    }

    /**
     * Indicate all metrics declining.
     */
    public function severeDecay(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_clicks' => fake()->numberBetween(2000, 5000),
            'current_clicks' => fake()->numberBetween(100, 500),
            'previous_impressions' => fake()->numberBetween(20000, 50000),
            'current_impressions' => fake()->numberBetween(3000, 8000),
            'previous_ctr' => fake()->randomFloat(4, 0.08, 0.12),
            'current_ctr' => fake()->randomFloat(4, 0.02, 0.04),
            'previous_avg_position' => fake()->randomFloat(2, 3, 8),
            'current_avg_position' => fake()->randomFloat(2, 20, 50),
            'unresolved_audit_issues' => fake()->numberBetween(5, 15),
        ]);
    }

    /**
     * Configure for a specific candidate.
     */
    public function forCandidate(int $candidateId): static
    {
        return $this->state(fn (array $attributes) => [
            'candidate_id' => $candidateId,
        ]);
    }
}
