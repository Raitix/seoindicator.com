<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\User;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessLocation>
 */
class BusinessLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BusinessLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory(),
            'gbp_place_id' => 'accounts/' . fake()->numerify('########') . '/locations/' . fake()->numerify('########'),
            'name' => fake()->company(),
            'address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'category' => fake()->optional()->randomElement(['Restaurant', 'Retail', 'Service', 'Healthcare', 'Entertainment']),
            'latitude' => fake()->latitude(25, 50),
            'longitude' => fake()->longitude(-125, -70),
            'gbp_connected' => fake()->boolean(80),
            'is_active' => true,
            'settings' => null,
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the location has GBP connected.
     */
    public function withGbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'gbp_connected' => true,
        ]);
    }

    /**
     * Indicate that the location has no GBP connection.
     */
    public function withoutGbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'gbp_connected' => false,
            'gbp_place_id' => null,
        ]);
    }
}
