<?php

namespace Database\Factories;

use App\Models\GBPInsight;
use App\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for GBPInsight model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPInsightFactory extends Factory
{
    protected $model = GBPInsight::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'location_id' => BusinessLocation::factory(),
            'views' => $this->faker->numberBetween(0, 1000),
            'searches' => $this->faker->numberBetween(0, 500),
            'calls' => $this->faker->numberBetween(0, 100),
            'directions' => $this->faker->numberBetween(0, 100),
            'website_clicks' => $this->faker->numberBetween(0, 150),
            'photos_views' => $this->faker->numberBetween(0, 500),
            'insights_date' => $this->faker->date(),
        ];
    }

    /**
     * Set a specific location.
     */
    public function forLocation(int $locationId): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $locationId,
        ]);
    }

    /**
     * Set a specific insights date.
     */
    public function withDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'insights_date' => $date,
        ]);
    }

    /**
     * High engagement metrics.
     */
    public function highEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'views' => $this->faker->numberBetween(500, 5000),
            'searches' => $this->faker->numberBetween(100, 1000),
            'calls' => $this->faker->numberBetween(20, 200),
            'directions' => $this->faker->numberBetween(20, 200),
            'website_clicks' => $this->faker->numberBetween(30, 300),
            'photos_views' => $this->faker->numberBetween(100, 1000),
        ]);
    }

    /**
     * No engagement (all zeros).
     */
    public function noEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'views' => 0,
            'searches' => 0,
            'calls' => 0,
            'directions' => 0,
            'website_clicks' => 0,
            'photos_views' => 0,
        ]);
    }

    /**
     * Insights from last week.
     */
    public function lastWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'insights_date' => now()->subDays(7)->format('Y-m-d'),
        ]);
    }

    /**
     * Insights from last month.
     */
    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'insights_date' => now()->subDays(30)->format('Y-m-d'),
        ]);
    }

    /**
     * Insights from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'insights_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Insights from a specific number of days ago.
     */
    public function daysAgo(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'insights_date' => now()->subDays($days)->format('Y-m-d'),
        ]);
    }
}
