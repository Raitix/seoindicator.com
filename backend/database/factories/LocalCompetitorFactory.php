<?php

namespace Database\Factories;

use App\Models\LocalCompetitor;
use App\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocalCompetitor>
 */
class LocalCompetitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LocalCompetitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => BusinessLocation::factory(),
            'competitor_name' => fake()->company(),
            'competitor_gbp_id' => 'ChIJ' . fake()->regexify('[A-Za-z0-9_-]{27}'),
            'competitor_address' => fake()->optional(80)->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'competitor_rating' => fake()->optional(90)->randomFloat(1, 2.5, 5.0),
            'competitor_review_count' => fake()->numberBetween(0, 500),
        ];
    }

    /**
     * Indicate that the competitor has no GBP ID.
     */
    public function withoutGbpId(): static
    {
        return $this->state(fn (array $attributes) => [
            'competitor_gbp_id' => null,
        ]);
    }

    /**
     * Indicate that the competitor has high ratings.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'competitor_rating' => fake()->randomFloat(1, 4.5, 5.0),
            'competitor_review_count' => fake()->numberBetween(100, 500),
        ]);
    }
}
