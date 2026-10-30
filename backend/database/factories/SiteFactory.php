<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'url' => fake()->unique()->url(),
            'name' => fake()->optional()->company(),
            'enable_serp_features' => false,
        ];
    }

    /**
     * Indicate that the site has SERP features enabled.
     */
    public function withSerpFeatures(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_serp_features' => true,
        ]);
    }
}
