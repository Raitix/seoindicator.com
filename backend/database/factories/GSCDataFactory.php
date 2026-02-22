<?php

namespace Database\Factories;

use App\Models\GSCData;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GSCData>
 */
class GSCDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GSCData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'query' => $this->faker->words(rand(2, 5), true),
            'page' => $this->faker->url(),
            'clicks' => $this->faker->numberBetween(0, 500),
            'impressions' => $this->faker->numberBetween(100, 50000),
            'ctr' => $this->faker->randomFloat(4, 0, 0.5),
            'avg_position' => $this->faker->randomFloat(1, 1, 100),
            'data_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the data is for a high-performing query.
     */
    public function highPerforming(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $this->faker->numberBetween(500, 5000),
            'impressions' => $this->faker->numberBetween(10000, 100000),
            'ctr' => $this->faker->randomFloat(4, 0.05, 0.2),
            'avg_position' => $this->faker->randomFloat(1, 1, 5),
        ]);
    }

    /**
     * Indicate that the data is for an opportunity keyword (high impressions, low CTR).
     */
    public function opportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $this->faker->numberBetween(10, 100),
            'impressions' => $this->faker->numberBetween(5000, 50000),
            'ctr' => $this->faker->randomFloat(4, 0.001, 0.02),
            'avg_position' => $this->faker->randomFloat(1, 4, 15),
        ]);
    }

    /**
     * Indicate that the data is for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'data_date' => $date,
        ]);
    }

    /**
     * Indicate that the data is for a specific query.
     */
    public function forQuery(string $query): static
    {
        return $this->state(fn (array $attributes) => [
            'query' => $query,
        ]);
    }
}
