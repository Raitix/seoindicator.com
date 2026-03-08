<?php

namespace Database\Factories\AIVisibility;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIVisibility\AIMentionAnalytics>
 */
class AIMentionAnalyticsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\AIVisibility\AIMentionAnalytics::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\AIVisibility\AIVisibilityProject::factory(),
            'platform' => fake()->randomElement(['chatgpt', 'perplexity', 'all']),
            'period' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'total_queries' => fake()->numberBetween(10, 100),
            'brand_mentions' => fake()->numberBetween(0, 50),
            'mention_rate' => fake()->randomFloat(2, 0, 100),
            'positive_mentions' => fake()->numberBetween(0, 30),
            'neutral_mentions' => fake()->numberBetween(0, 20),
            'negative_mentions' => fake()->numberBetween(0, 10),
            'mixed_mentions' => fake()->numberBetween(0, 5),
            'average_sentiment' => fake()->randomFloat(2, -1, 1),
            'competitor_mentions' => [],
            'competitor_stats' => [],
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'aggregated_at' => now(),
        ];
    }

    /**
     * Indicate a specific platform.
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }

    /**
     * Indicate a specific period.
     */
    public function forPeriod(string $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }

    /**
     * Indicate specific mention rate.
     */
    public function withMentionRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'mention_rate' => $rate,
        ]);
    }

    /**
     * Indicate competitor stats.
     */
    public function withCompetitorStats(array $stats): static
    {
        return $this->state(fn (array $attributes) => [
            'competitor_stats' => $stats,
        ]);
    }

    /**
     * Indicate period range.
     */
    public function forDateRange($start, $end): static
    {
        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }
}
