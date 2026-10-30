<?php

namespace Database\Factories;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BacklinkSnapshot>
 */
class BacklinkSnapshotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BacklinkSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'total_backlinks' => $this->faker->numberBetween(100, 5000),
            'referring_domains_count' => $this->faker->numberBetween(10, 500),
            'dofollow_count' => $this->faker->numberBetween(50, 3000),
            'nofollow_count' => $this->faker->numberBetween(10, 1000),
            'avg_domain_trust' => $this->faker->randomFloat(2, 0, 100),
            'anchor_diversity_score' => $this->faker->randomFloat(2, 0, 100),
            'snapshot_date' => $this->faker->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate the snapshot has low metrics.
     */
    public function lowMetrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_backlinks' => $this->faker->numberBetween(10, 100),
            'referring_domains_count' => $this->faker->numberBetween(1, 20),
            'avg_domain_trust' => $this->faker->randomFloat(2, 0, 30),
        ]);
    }

    /**
     * Indicate the snapshot has high metrics.
     */
    public function highMetrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_backlinks' => $this->faker->numberBetween(5000, 20000),
            'referring_domains_count' => $this->faker->numberBetween(500, 2000),
            'avg_domain_trust' => $this->faker->randomFloat(2, 60, 100),
        ]);
    }

    /**
     * Set the snapshot date to today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => today(),
        ]);
    }

    /**
     * Set the snapshot date to a specific date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => $date,
        ]);
    }
}
