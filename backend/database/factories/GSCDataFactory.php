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
}
