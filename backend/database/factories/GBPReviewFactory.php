<?php

namespace Database\Factories;

use App\Models\GBPReview;
use App\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for GBPReview model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPReviewFactory extends Factory
{
    protected $model = GBPReview::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'location_id' => BusinessLocation::factory(),
            'google_review_id' => $this->faker->unique()->uuid(),
            'author_name' => $this->faker->name(),
            'author_photo' => $this->faker->imageUrl(100, 100, 'people'),
            'rating' => $this->faker->numberBetween(1, 5),
            'review_text' => $this->faker->paragraph(),
            'review_text_sentiment' => $this->faker->randomElement(['positive', 'neutral', 'negative']),
            'owner_response' => null,
            'owner_response_date' => null,
            'review_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
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
     * Positive review (4-5 stars).
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomElement([4, 5]),
            'review_text' => $this->faker->randomElement([
                'Great experience! Highly recommended.',
                'Excellent service, very professional.',
                'Wonderful! Will definitely come back.',
                'Amazing customer service and quality.',
            ]),
            'review_text_sentiment' => 'positive',
        ]);
    }

    /**
     * Negative review (1-2 stars).
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomElement([1, 2]),
            'review_text' => $this->faker->randomElement([
                'Disappointing experience.',
                'Would not recommend.',
                'Poor service quality.',
                'Expected much better.',
            ]),
            'review_text_sentiment' => 'negative',
        ]);
    }

    /**
     * Neutral review (3 stars).
     */
    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 3,
            'review_text' => $this->faker->randomElement([
                'Average experience.',
                'It was okay, nothing special.',
                'Mediocre service.',
            ]),
            'review_text_sentiment' => 'neutral',
        ]);
    }

    /**
     * Review with owner response.
     */
    public function responded(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_response' => $this->faker->randomElement([
                'Thank you for your feedback! We appreciate your business.',
                'We are glad you enjoyed your experience!',
                'Thank you for sharing your thoughts. We hope to serve you again.',
            ]),
            'owner_response_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Review without owner response.
     */
    public function unresponded(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_response' => null,
            'owner_response_date' => null,
        ]);
    }

    /**
     * Set specific sentiment.
     */
    public function withSentiment(string $sentiment): static
    {
        return $this->state(fn (array $attributes) => [
            'review_text_sentiment' => $sentiment,
        ]);
    }

    /**
     * Five-star review.
     */
    public function fiveStar(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 5,
            'review_text_sentiment' => 'positive',
        ]);
    }

    /**
     * One-star review.
     */
    public function oneStar(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 1,
            'review_text_sentiment' => 'negative',
        ]);
    }

    /**
     * Review from recent days.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'review_date' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Review with specific Google Review ID.
     */
    public function withGoogleId(string $googleId): static
    {
        return $this->state(fn (array $attributes) => [
            'google_review_id' => $googleId,
        ]);
    }

    /**
     * Anonymous review (no author name).
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_name' => null,
            'author_photo' => null,
        ]);
    }

    /**
     * Review with specific rating.
     */
    public function withRating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}
