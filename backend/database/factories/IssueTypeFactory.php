<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IssueType>
 */
class IssueTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
    'category' => 'meta',
    'name' => fake()->word(),
    'description' => fake()->sentence(),
    'severity_default' => fake()->randomElement(['critical', 'warning', 'notice']),
    'recommendation_template' => fake()->paragraph(),
            //
        ];
    }
}
