<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 * @Task TASK-35-14
 */
class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'site_id' => \App\Models\Site::factory(),
            'gbp_place_id' => 'places/' . fake()->bothify('????####????'),
            'name' => fake()->company(),
            'address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'category' => fake()->optional()->randomElement(['Restaurant', 'Retail', 'Service', 'Healthcare', 'Entertainment']),
            'latitude' => fake()->latitude(25, 50),
            'longitude' => fake()->longitude(-125, -70),
            'gbp_connected' => true,
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
