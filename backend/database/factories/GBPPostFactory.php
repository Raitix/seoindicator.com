<?php

namespace Database\Factories;

use App\Models\GBPPost;
use App\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for GBPPost model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPPostFactory extends Factory
{
    protected $model = GBPPost::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'location_id' => BusinessLocation::factory(),
            'google_post_id' => $this->faker->unique()->uuid(),
            'post_type' => $this->faker->randomElement(['EVENT', 'OFFER', 'UPDATE']),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'media_url' => null,
            'call_to_action' => null,
            'call_to_action_url' => null,
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
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
     * Event post.
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'EVENT',
            'title' => $this->faker->randomElement([
                'Grand Opening Celebration!',
                'Annual Sale Event',
                'Holiday Special Event',
                'Community Gathering',
            ]),
            'content' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Offer post.
     */
    public function offer(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'OFFER',
            'title' => $this->faker->randomElement([
                '20% Off All Services!',
                'Special Discount Offer',
                'Limited Time Deal',
                'Flash Sale - Today Only!',
            ]),
            'content' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Update post.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'UPDATE',
            'title' => $this->faker->randomElement([
                'New Hours of Operation',
                'Exciting News!',
                'We\'re Expanding!',
                'Service Update',
            ]),
            'content' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Published post (past published_at).
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Scheduled post (future published_at).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Draft post (null published_at).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Post with media.
     */
    public function withMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_url' => $this->faker->imageUrl(800, 600, 'business'),
        ]);
    }

    /**
     * Post with call to action.
     */
    public function withCta(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_to_action' => $this->faker->randomElement([
                'Learn More',
                'Book Now',
                'Call Now',
                'Get Directions',
                'Sign Up',
                'Shop Now',
            ]),
            'call_to_action_url' => $this->faker->url(),
        ]);
    }

    /**
     * Post with specific Google Post ID.
     */
    public function withGoogleId(string $googleId): static
    {
        return $this->state(fn (array $attributes) => [
            'google_post_id' => $googleId,
        ]);
    }

    /**
     * Post with specific type.
     */
    public function withType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => $type,
        ]);
    }

    /**
     * Post without title.
     */
    public function withoutTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => null,
        ]);
    }

    /**
     * Post without content.
     */
    public function withoutContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => null,
        ]);
    }

    /**
     * COVID-related post.
     */
    public function covid(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'COVID',
            'title' => $this->faker->randomElement([
                'COVID-19 Update',
                'Our Safety Measures',
                'We\'re Open!',
            ]),
            'content' => 'We are committed to providing a safe environment for our customers.',
        ]);
    }
}
