<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Models\User;
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

    public function configure(): static
    {
        return $this->afterMaking(function (BusinessLocation $location): void {
            if (!$location->relationLoaded('site') && $location->site_id && !$location->site) {
                $location->setRelation('site', Site::query()->find($location->site_id));
            }

            if ($location->site && !$location->user_id) {
                $location->user_id = $location->site->user_id;
            }
        })->afterCreating(function (BusinessLocation $location): void {
            if ($location->site && $location->site->user_id !== $location->user_id) {
                $location->site->update(['user_id' => $location->user_id]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'site_id' => Site::factory()->state(fn () => ['user_id' => $user]),
            'gbp_place_id' => fake()->boolean(80)
                ? 'accounts/' . fake()->numerify('########') . '/locations/' . fake()->numerify('########')
                : null,
            'gbp_access_token' => null,
            'gbp_refresh_token' => null,
            'gbp_token_expires_at' => null,
            'name' => fake()->company(),
            'address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'category' => fake()->optional()->randomElement(['Restaurant', 'Retail', 'Service', 'Healthcare', 'Entertainment']),
            'latitude' => fake()->latitude(25, 50),
            'longitude' => fake()->longitude(-125, -70),
            'gbp_connected' => false,
            'is_active' => true,
            'settings' => fake()->optional(30)->passthrough([
                'notifications' => fake()->boolean(),
                'sync_interval' => fake()->randomElement([15, 30, 60, 120]),
            ]),
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
            'gbp_place_id' => $attributes['gbp_place_id'] ?? ('accounts/' . fake()->numerify('########') . '/locations/' . fake()->numerify('########')),
            'gbp_access_token' => fake()->sha256(),
            'gbp_refresh_token' => fake()->sha256(),
            'gbp_token_expires_at' => now()->addDays(30),
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
            'gbp_access_token' => null,
            'gbp_refresh_token' => null,
            'gbp_token_expires_at' => null,
        ]);
    }
}
