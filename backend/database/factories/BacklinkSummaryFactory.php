<?php

namespace Database\Factories;

use App\Models\BacklinkSummary;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BacklinkSummary>
 */
class BacklinkSummaryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BacklinkSummary::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'domain' => $this->faker->domainName(),
            'total_backlinks' => $this->faker->numberBetween(1, 1000),
            'referring_domains' => $this->faker->numberBetween(1, 100),
            'domain_authority' => $this->faker->randomFloat(2, 0, 100),
            'checked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the backlink summary has high domain authority.
     */
    public function highAuthority(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_authority' => $this->faker->randomFloat(2, 60, 90),
            'total_backlinks' => $this->faker->numberBetween(1000, 10000),
            'referring_domains' => $this->faker->numberBetween(100, 1000),
        ]);
    }

    /**
     * Indicate that the backlink summary has low domain authority.
     */
    public function lowAuthority(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_authority' => $this->faker->randomFloat(2, 0, 30),
            'total_backlinks' => $this->faker->numberBetween(1, 100),
            'referring_domains' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Indicate that the backlink summary was checked recently.
     */
    public function recentlyChecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Indicate that the backlink summary hasn't been checked for a while.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $this->faker->dateTimeBetween('-90 days', '-31 days'),
        ]);
    }
}