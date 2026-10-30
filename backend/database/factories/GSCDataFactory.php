<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GSCData>
 */
class GSCDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'site_id' => \App\Models\Site::factory(),
            'query' => fake()->unique()->words(2, true),
            'page' => fake()->optional()->url(),
            'clicks' => fake()->numberBetween(0, 500),
            'impressions' => fake()->numberBetween(0, 10000),
            'ctr' => fake()->randomFloat(5, 0, 1),
            'avg_position' => fake()->randomFloat(2, 1, 100),
            'data_date' => fake()->date(),
        ];
    }

    /**
     * Indicate that the data is recent (within the last 7 days).
     */
    public function recent(): static
    {
        return $this->state(fn () => [
            'data_date' => fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate high performing metrics.
     */
    public function highPerforming(): static
    {
        return $this->state(fn () => [
            'clicks' => fake()->numberBetween(500, 2000),
            'ctr' => fake()->randomFloat(5, 0.05, 0.25),
            'avg_position' => fake()->randomFloat(2, 1, 5),
        ]);
    }

    /**
     * Indicate opportunity metrics (high impressions, low CTR).
     */
    public function opportunity(): static
    {
        return $this->state(fn () => [
            'impressions' => fake()->numberBetween(2000, 20000),
            'ctr' => fake()->randomFloat(5, 0.0, 0.03),
            'avg_position' => fake()->randomFloat(2, 1, 15),
        ]);
    }
}
