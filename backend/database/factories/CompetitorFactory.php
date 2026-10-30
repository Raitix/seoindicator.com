<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competitor>
 */
class CompetitorFactory extends Factory
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
            'domain' => fake()->unique()->domainName(),
            'display_name' => fake()->company(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the competitor has no display name.
     */
    public function withoutDisplayName(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_name' => null,
        ]);
    }

    /**
     * Indicate that the competitor has no notes.
     */
    public function withoutNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => null,
        ]);
    }
}
