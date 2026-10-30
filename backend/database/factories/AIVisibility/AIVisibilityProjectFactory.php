<?php

namespace Database\Factories\AIVisibility;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIVisibility\AIVisibilityProject>
 */
class AIVisibilityProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\AIVisibility\AIVisibilityProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'brand_name' => fake()->company(),
            'keywords' => fake()->words(3),
            'competitors' => [
                ['name' => fake()->company(), 'keywords' => fake()->words(2)],
            ],
            'query_templates' => [
                'What are the best tools for {category}?',
                'Can you recommend a good {category} tool?',
            ],
            'tracking_frequency' => 'weekly',
            'is_active' => true,
            'last_queried_at' => null,
        ];
    }

    /**
     * Indicate that the project is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the project has no competitors.
     */
    public function withoutCompetitors(): static
    {
        return $this->state(fn (array $attributes) => [
            'competitors' => [],
        ]);
    }

    /**
     * Indicate that the project has max competitors (3).
     */
    public function withMaxCompetitors(): static
    {
        return $this->state(fn (array $attributes) => [
            'competitors' => [
                ['name' => fake()->company(), 'keywords' => fake()->words(2)],
                ['name' => fake()->company(), 'keywords' => fake()->words(2)],
                ['name' => fake()->company(), 'keywords' => fake()->words(2)],
            ],
        ]);
    }
}
