<?php

namespace Database\Factories;

use App\Models\GBPReview;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GBPReview>
 * @Task TASK-35-14
 */
class GBPReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = GBPReview::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'gbp_review_id' => 'review_' . fake()->bothify('??####??##'),
            'rating' => fake()->numberBetween(1, 5),
            'text' => fake()->optional(0.8)->paragraph(),
            'author' => fake()->name(),
            'reviewed_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'has_response' => fake()->boolean(30),
            'response_text' => fn (array $attributes) =>
                $attributes['has_response'] ? fake()->sentence() : null,
            'responded_at' => fn (array $attributes) =>
                $attributes['has_response'] ? fake()->dateTimeBetween($attributes['reviewed_at'], 'now') : null,
        ];
    }

    /**
     * Indicate that the review has a positive sentiment.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'positive',
            'rating' => fake()->numberBetween(4, 5),
        ]);
    }

    /**
     * Indicate that the review has a negative sentiment.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'negative',
            'rating' => fake()->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the review has a neutral sentiment.
     */
    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'neutral',
            'rating' => 3,
        ]);
    }

    /**
     * Indicate that the review has a response.
     */
    public function responded(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_response' => true,
            'response_text' => fake()->sentence(),
            'responded_at' => fake()->dateTimeBetween($attributes['reviewed_at'] ?? '-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the review has no response.
     */
    public function notResponded(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_response' => false,
            'response_text' => null,
            'responded_at' => null,
        ]);
    }
}
